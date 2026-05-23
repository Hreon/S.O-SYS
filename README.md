# SysMarket v2 — Tienda Tech + Monitor de Servidor

**Trabajo Final — Sistemas Operativos**  
Universidad San Ignacio de Loyola · 2026-01  
Grupo 1: Fabian Roncal · Luz Rios · Piero Valencia

---

## Concepto

Tienda de e-commerce especializada en piezas de computadora y periféricos cuyo **panel administrativo** incluye un monitor en tiempo real de los recursos del SO que aloja el servicio.

Cumple los requisitos del curso aplicando:
- **Hilos POSIX concurrentes** (Python `threading.Thread`) en el daemon de monitoreo
- **Mutex** (`threading.Lock`) para proteger la sección crítica
- **Llamadas al kernel** vía `/proc` y `os.statvfs()`
- **Contenedores Docker** orquestados con Docker Compose
- **Despliegue cloud** en AWS EC2 (Ubuntu 22.04 LTS)
- **Arquitectura en 3 capas** (Apache/PHP, MySQL, Daemon Python)

## Stack

| Capa | Tecnología |
|---|---|
| Frontend | HTML5 + Bootstrap 5.3 + Chart.js + JS vanilla |
| Backend  | PHP 8.2 + Apache (PDO + prepared statements) |
| Daemon SO | Python 3.11 (`threading`, `os.statvfs`, lectura `/proc`) |
| BD       | MySQL 8.0 |
| Contenedores | Docker + Docker Compose |
| Cloud    | AWS EC2 t2.micro (Ubuntu 22.04) |

## Estructura del proyecto

```
sysmarket_v2/
├── docker-compose.yml
├── .env
├── apache-php/
│   ├── Dockerfile, apache.conf
│   └── src/
│       ├── index.php          # Landing tienda
│       ├── productos.php      # Catálogo
│       ├── producto.php       # Detalle
│       ├── carrito.php
│       ├── mi-cuenta.php
│       ├── login.php, register.php, logout.php
│       ├── monitor.php        # Dashboard OS (admin)
│       ├── admin.php          # Panel admin
│       ├── includes/
│       │   ├── auth.php       # bcrypt, sesiones, guards
│       │   ├── db.php         # PDO singleton
│       │   ├── header.php, footer.php
│       ├── api/
│       │   ├── cart_add.php, cart_update.php, cart_remove.php
│       │   ├── checkout.php
│       │   ├── metrics.php    # JSON para dashboard OS
│       │   └── save_alert.php
│       └── assets/
│           ├── css/style.css  # Estilo tipo Stitch
│           └── js/app.js
├── mysql/
│   └── init.sql               # 10 tablas + datos demo
└── monitor/
    ├── monitor.py             # 5 hilos POSIX + mutex
    ├── Dockerfile, requirements.txt
```

## Arrancar localmente (Windows + WSL2 o Linux)

```bash
docker compose up -d --build
# Esperar ~15s a que MySQL inicialice
# Abrir http://localhost
```

## Cuentas demo

| Email | Password | Rol |
|---|---|---|
| admin@sysmarket.com    | password | admin   |
| cliente@sysmarket.com  | password | cliente |

## Demostrar concurrencia y mutex en la presentación

1. **`docker compose logs -f monitor`** → ver el daemon corriendo los 5 hilos cada 5s
2. **Abrir `/monitor.php` con sesión admin** → KPIs actualizándose en vivo
3. **Configurar una alerta** (CPU > 50) → forzar carga con `stress` y ver la alerta disparada
4. **Mostrar el código de `monitor.py`**: las secciones marcadas `── SECCIÓN CRÍTICA ──` evidencian el uso del mutex

## Despliegue en AWS EC2

```bash
# En la instancia Ubuntu 22.04
git clone https://github.com/Hreon/sysmarket-v2.git
cd sysmarket-v2
bash deploy_aws.sh    # instala Docker + dependencias
docker compose up -d --build
# Abrir http://<IP_ELASTICA>
```
