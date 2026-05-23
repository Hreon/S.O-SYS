"""
SysMarket — Daemon de Monitoreo de Recursos del SO
==================================================
Hilos POSIX concurrentes + mutex (threading.Lock) que leen del
sistema de archivos virtual /proc del kernel Linux y persisten
las métricas en MySQL cada 5 segundos.

Conceptos de Sistemas Operativos aplicados:
  - Procesos / Hilos       : threading.Thread (mapean a POSIX pthreads)
  - Sincronización          : threading.Lock() como mutex de sección crítica
  - Llamadas al kernel      : lectura directa de /proc (interfaz VFS)
                              + syscall statvfs() para info de disco
  - Manejo de recursos      : context managers (with) para FDs y conexiones
  - Concurrencia controlada : join() para sincronizar fin de hilos antes
                              de persistir un snapshot consistente
"""
import threading
import time
import os
import json
import mysql.connector
from mysql.connector import pooling
from datetime import datetime

# ─── Configuración ─────────────────────────────────────────────
PROC_PATH = "/host/proc"           # /proc del host montado en el contenedor
INTERVAL  = 5                      # segundos entre snapshots
DB_CONFIG = {
    "host":     os.getenv("DB_HOST", "db"),
    "database": os.getenv("DB_NAME", "sysmarket"),
    "user":     os.getenv("DB_USER", "sysuser"),
    "password": os.getenv("DB_PASS", "SysMarket2026!"),
}

# ─── Estado compartido protegido por MUTEX ──────────────────────
# Esta es la sección crítica del programa: cinco hilos escriben sobre
# este diccionario concurrentemente. Sin el lock habría race conditions.
metrics_lock = threading.Lock()
shared_metrics = {
    "cpu": 0.0, "ram_pct": 0.0, "swap_pct": 0.0, "disk_pct": 0.0,
    "load_1": 0.0, "load_5": 0.0, "load_15": 0.0,
    "proc_count": 0, "proc_list": [],
}

# Caché para deltas de CPU entre lecturas (CPU se mide como tasa)
_cpu_cache = {"idle": 0, "total": 0}


# ─── Hilo 1: CPU desde /proc/stat ──────────────────────────────
def read_cpu():
    try:
        with open(f"{PROC_PATH}/stat", "r") as f:
            fields = f.readline().split()
        total = sum(int(x) for x in fields[1:])
        idle  = int(fields[4]) + int(fields[5])      # idle + iowait

        d_total = total - _cpu_cache["total"]
        d_idle  = idle  - _cpu_cache["idle"]
        cpu_pct = round(100.0 * (1 - d_idle / d_total), 2) if d_total > 0 else 0.0

        _cpu_cache["total"], _cpu_cache["idle"] = total, idle

        with metrics_lock:                           # ── SECCIÓN CRÍTICA ──
            shared_metrics["cpu"] = cpu_pct
    except Exception as e:
        print(f"[ERR] read_cpu: {e}")


# ─── Hilo 2: RAM y Swap desde /proc/meminfo ────────────────────
def read_memory():
    try:
        mem = {}
        with open(f"{PROC_PATH}/meminfo", "r") as f:
            for line in f:
                parts = line.split()
                if len(parts) >= 2:
                    mem[parts[0].rstrip(":")] = int(parts[1])

        total_ram, avail_ram = mem.get("MemTotal", 1), mem.get("MemAvailable", 0)
        total_swap, free_swap = mem.get("SwapTotal", 1), mem.get("SwapFree", 0)

        ram_pct  = round(100.0 * (total_ram - avail_ram) / total_ram, 2)
        swap_pct = round(100.0 * (total_swap - free_swap) / max(total_swap, 1), 2)

        with metrics_lock:                           # ── SECCIÓN CRÍTICA ──
            shared_metrics["ram_pct"]  = ram_pct
            shared_metrics["swap_pct"] = swap_pct
    except Exception as e:
        print(f"[ERR] read_memory: {e}")


# ─── Hilo 3: Disco vía syscall statvfs() ───────────────────────
def read_disk():
    try:
        s = os.statvfs("/")
        total = s.f_blocks * s.f_frsize
        free  = s.f_bfree  * s.f_frsize
        disk_pct = round(100.0 * (total - free) / max(total, 1), 2)

        with metrics_lock:                           # ── SECCIÓN CRÍTICA ──
            shared_metrics["disk_pct"] = disk_pct
    except Exception as e:
        print(f"[ERR] read_disk: {e}")


# ─── Hilo 4: Carga promedio desde /proc/loadavg ────────────────
def read_loadavg():
    try:
        with open(f"{PROC_PATH}/loadavg", "r") as f:
            parts = f.read().split()
        with metrics_lock:                           # ── SECCIÓN CRÍTICA ──
            shared_metrics["load_1"]  = float(parts[0])
            shared_metrics["load_5"]  = float(parts[1])
            shared_metrics["load_15"] = float(parts[2])
    except Exception as e:
        print(f"[ERR] read_loadavg: {e}")


# ─── Hilo 5: Procesos activos desde /proc/[pid]/status ─────────
def read_processes():
    try:
        procs = []
        for entry in os.listdir(PROC_PATH):
            if not entry.isdigit():
                continue
            try:
                info = {"pid": int(entry), "name": "?", "state": "?",
                        "vmrss_kb": 0, "threads": 1}
                with open(f"{PROC_PATH}/{entry}/status", "r") as f:
                    for line in f:
                        if   line.startswith("Name:"):    info["name"]     = line.split(":")[1].strip()
                        elif line.startswith("State:"):   info["state"]    = line.split(":")[1].strip()[:1]
                        elif line.startswith("VmRSS:"):   info["vmrss_kb"] = int(line.split()[1])
                        elif line.startswith("Threads:"): info["threads"]  = int(line.split(":")[1].strip())
                procs.append(info)
            except (PermissionError, FileNotFoundError, ProcessLookupError):
                pass  # El proceso pudo terminar entre listdir() y open()

        procs.sort(key=lambda p: p["vmrss_kb"], reverse=True)
        with metrics_lock:                           # ── SECCIÓN CRÍTICA ──
            shared_metrics["proc_count"] = len(procs)
            shared_metrics["proc_list"]  = procs[:30]
    except Exception as e:
        print(f"[ERR] read_processes: {e}")


# ─── Persistencia en MySQL ─────────────────────────────────────
def save_to_db(pool):
    ID_MAP = {"cpu": 1, "ram_pct": 2, "disk_pct": 3,
              "proc_count": 4, "load_1": 5, "swap_pct": 6}
    conn = cur = None
    try:
        conn = pool.get_connection()
        cur  = conn.cursor()
        with metrics_lock:                           # leer snapshot consistente
            snap = {k: shared_metrics[k] for k in ID_MAP}
            proc_json = json.dumps(shared_metrics["proc_list"][:10])
        rows = [(mid, snap[k], proc_json if k == "proc_count" else None)
                for k, mid in ID_MAP.items()]
        cur.executemany(
            "INSERT INTO lecturas (id_metrica, valor, extra_json) VALUES (%s, %s, %s)", rows)
        conn.commit()

        # Evaluar alertas: cualquier umbral disparado genera notificación
        cur.execute("""
          SELECT a.id, a.id_metrica, a.umbral, a.operador
          FROM alertas a WHERE a.activa = 1
        """)
        alerts = cur.fetchall()
        name_by_id = {1: "cpu", 2: "ram_pct", 3: "disk_pct",
                      4: "proc_count", 5: "load_1", 6: "swap_pct"}
        for aid, mid, umbral, op in alerts:
            key = name_by_id.get(mid)
            if key is None: continue
            v = snap[key]
            fire = (op == ">" and v > umbral) or \
                   (op == "<" and v < umbral) or \
                   (op == "=" and abs(v - umbral) < 0.01)
            if fire:
                cur.execute(
                  "INSERT INTO notificaciones (id_alerta, valor_detectado) VALUES (%s, %s)",
                  (aid, v))
        conn.commit()
    except Exception as e:
        print(f"[ERR] save_to_db: {e}")
    finally:
        try:
            if cur:  cur.close()
            if conn: conn.close()
        except Exception:
            pass


# ─── Ciclo principal ───────────────────────────────────────────
def collect_cycle(pool):
    """Lanza 5 hilos concurrentes y los sincroniza con join() antes de persistir."""
    targets = [read_cpu, read_memory, read_disk, read_loadavg, read_processes]
    threads = [threading.Thread(target=fn, name=fn.__name__) for fn in targets]
    for t in threads: t.start()
    for t in threads: t.join(timeout=4)             # ── SINCRONIZACIÓN ──
    save_to_db(pool)


def main():
    print("=" * 60)
    print(" SysMarket Monitor Daemon — arrancando...")
    print(f" /proc montado en: {PROC_PATH}")
    print(f" Intervalo: {INTERVAL}s  |  Hilos concurrentes: 5")
    print("=" * 60)

    pool = None
    for attempt in range(30):
        try:
            pool = pooling.MySQLConnectionPool(pool_name="syspool", pool_size=3, **DB_CONFIG)
            print(f"[OK] MySQL conectado (intento {attempt+1})")
            break
        except Exception as e:
            print(f"[WAIT] MySQL no disponible, reintentando en 5s... ({e})")
            time.sleep(5)
    if pool is None:
        print("[FATAL] No se pudo conectar a MySQL. Saliendo.")
        return

    read_cpu(); time.sleep(1)                       # warm-up del delta de CPU

    print("[OK] Iniciando ciclos de monitoreo concurrente...\n")
    while True:
        t0 = time.time()
        collect_cycle(pool)
        elapsed = time.time() - t0
        with metrics_lock:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] "
                  f"CPU={shared_metrics['cpu']:5.1f}% | "
                  f"RAM={shared_metrics['ram_pct']:5.1f}% | "
                  f"Disco={shared_metrics['disk_pct']:5.1f}% | "
                  f"Procs={shared_metrics['proc_count']:4d} | "
                  f"Load={shared_metrics['load_1']:.2f}  ({elapsed:.2f}s)")
        time.sleep(max(0, INTERVAL - elapsed))


if __name__ == "__main__":
    main()
