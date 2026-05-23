<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$db = getDB();
$catalogo = $db->query("SELECT * FROM metricas_catalogo WHERE activa=1 ORDER BY orden")->fetchAll();
$page_title = 'Monitor de Servidor — SysMarket Admin';
include __DIR__ . '/includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<div class="container-xl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h2 style="font-weight:800;letter-spacing:-.02em" class="mb-1">
        <i class="bi bi-activity text-primary me-2"></i>Monitor de Servidor
      </h2>
      <p class="text-secondary mb-0">Métricas en vivo del SO que aloja SysMarket — actualiza cada 5s</p>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="sm-live-pulse">EN VIVO</span>
      <span id="server-time" class="font-mono small text-secondary">--:--:--</span>
    </div>
  </div>

  <!-- KPI GRID -->
  <div class="sm-kpi-grid mb-4 sm-stagger">
    <?php foreach ($catalogo as $m):
      $key = strtolower($m['nombre']);
    ?>
    <div class="sm-kpi" id="kpi-<?= h($key) ?>">
      <div class="sm-kpi-h">
        <div class="sm-kpi-icon"><i class="bi <?= h($m['icono']) ?>"></i></div>
        <button class="btn btn-sm sm-btn-ghost" style="padding:4px 10px;font-size:.75rem"
                onclick="openAlert(<?= (int)$m['id'] ?>, '<?= h($m['nombre']) ?>')">
          <i class="bi bi-bell"></i>
        </button>
      </div>
      <div class="sm-kpi-label"><?= h($m['nombre']) ?></div>
      <div class="sm-kpi-value" id="val-<?= h($key) ?>">—</div>
      <div class="sm-kpi-progress"><span id="bar-<?= h($key) ?>" style="width:0%"></span></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- CHARTS -->
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="sm-card sm-card-pad">
        <div class="d-flex justify-content-between mb-3">
          <strong><i class="bi bi-cpu text-primary me-2"></i>CPU — Historial (últimos 30 puntos)</strong>
          <span class="badge bg-primary">/proc/stat</span>
        </div>
        <canvas id="cpuChart" height="100"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="sm-card sm-card-pad h-100">
        <div class="mb-3"><strong><i class="bi bi-pie-chart text-warning me-2"></i>Distribución actual</strong></div>
        <canvas id="donutChart" style="max-height:240px"></canvas>
      </div>
    </div>
  </div>

  <!-- TABLA DE PROCESOS -->
  <div class="sm-card mb-4">
    <div class="sm-card-pad pb-2">
      <strong><i class="bi bi-list-task text-info me-2"></i>Top procesos por uso de RAM</strong>
      <span class="badge bg-secondary ms-2 font-mono">/proc/[pid]/status</span>
    </div>
    <div class="sm-table-wrap" style="border:none;border-radius:0;border-top:1px solid var(--sm-border)">
      <table class="sm-table">
        <thead>
          <tr><th>PID</th><th>Proceso</th><th>Estado</th><th>RAM (KB)</th><th>Hilos</th></tr>
        </thead>
        <tbody id="proc-tbody">
          <tr><td colspan="5" class="text-center text-secondary py-3">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL ALERTA -->
<div class="modal fade" id="alertModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-bell-fill text-warning me-2"></i>Configurar alerta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/api/save_alert.php">
        <div class="modal-body">
          <input type="hidden" name="id_metrica" id="modal-id">
          <p>Métrica: <strong id="modal-nombre" class="text-primary"></strong></p>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label small">Operador</label>
              <select name="operador" class="form-select">
                <option value=">">Mayor que &gt;</option>
                <option value="<">Menor que &lt;</option>
                <option value="=">Igual a =</option>
              </select>
            </div>
            <div class="col-8">
              <label class="form-label small">Valor umbral</label>
              <input type="number" name="umbral" class="form-control" required step="0.1" min="0" placeholder="Ej: 80">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn sm-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn sm-btn-primary"><i class="bi bi-bell-fill me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── Open modal alerta ───────────────────────────────────────────
function openAlert(id, nombre) {
  document.getElementById('modal-id').value = id;
  document.getElementById('modal-nombre').textContent = nombre;
  new bootstrap.Modal(document.getElementById('alertModal')).show();
}

// ── Chart CPU línea ─────────────────────────────────────────────
const cpuChart = new Chart(document.getElementById('cpuChart'), {
  type: 'line',
  data: { labels: [], datasets: [{
    label: 'CPU %', data: [],
    borderColor: '#3b82f6',
    backgroundColor: ctx => {
      const g = ctx.chart.ctx.createLinearGradient(0,0,0,300);
      g.addColorStop(0, 'rgba(59,130,246,.5)'); g.addColorStop(1, 'rgba(59,130,246,0)');
      return g;
    },
    borderWidth: 2.5, fill: true, tension: .4, pointRadius: 0, pointHoverRadius: 4
  }]},
  options: {
    responsive: true, animation: { duration: 800, easing: 'easeOutQuart' },
    scales: {
      y: { min: 0, max: 100, ticks: { color:'#94a3b8', callback: v => v+'%' }, grid: { color:'rgba(35,49,86,.5)' } },
      x: { ticks: { color:'#94a3b8', maxTicksLimit: 8 }, grid: { display:false } }
    },
    plugins: { legend: { display: false } }
  }
});

// ── Chart donut ─────────────────────────────────────────────────
const donutChart = new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: { labels: ['CPU','RAM','Disco','Swap'], datasets: [{ data:[0,0,0,0],
    backgroundColor:['#3b82f6','#22c55e','#f59e0b','#ef4444'], borderColor:'#0b1224', borderWidth:3 }]},
  options: { plugins: { legend: { labels:{ color:'#e2e8f0', font:{ size:11 } } } }, cutout: '68%' }
});

const stateBadge = s => ({
  R: '<span class="badge bg-success">Running</span>',
  S: '<span class="badge bg-secondary">Sleep</span>',
  Z: '<span class="badge bg-danger">Zombie</span>',
  I: '<span class="badge bg-dark">Idle</span>',
  D: '<span class="badge bg-warning text-dark">Disk wait</span>',
  T: '<span class="badge bg-info">Stopped</span>',
}[s] || `<span class="badge bg-dark">${s}</span>`);

async function fetchMetrics() {
  try {
    const res = await fetch('/api/metrics.php');
    const data = await res.json();
    if (!data.ok) return;
    document.getElementById('server-time').textContent = data.server_time;

    const mMap = {};
    (data.metrics || []).forEach(m => mMap[m.nombre] = m);

    const updateKpi = (key, label, value, max=100) => {
      const v   = parseFloat(value || 0);
      const el  = document.getElementById('val-'+key);
      const bar = document.getElementById('bar-'+key);
      const card= document.getElementById('kpi-'+key);
      if (el)  el.textContent  = isFinite(v) ? (v.toFixed(key==='carga'?2:1) + (key==='procesos'||key==='carga'?'':'%')) : '—';
      if (bar) bar.style.width = Math.min(100, (v/max)*100) + '%';
      if (card) {
        card.classList.remove('alert','critical');
        if (key !== 'procesos' && key !== 'carga') {
          if (v >= 85) card.classList.add('critical');
          else if (v >= 65) card.classList.add('alert');
        }
      }
    };

    updateKpi('cpu',      'CPU',      mMap['CPU']?.valor);
    updateKpi('ram',      'RAM',      mMap['RAM']?.valor);
    updateKpi('disco',    'Disco',    mMap['Disco']?.valor);
    updateKpi('procesos', 'Procesos', mMap['Procesos']?.valor, 500);
    updateKpi('carga',    'Carga',    mMap['Carga']?.valor, 4);
    updateKpi('swap',     'Swap',     mMap['Swap']?.valor);

    // CPU chart
    if (data.cpu_history?.length) {
      cpuChart.data.labels = data.cpu_history.map(r => r.timestamp.substr(11,8));
      cpuChart.data.datasets[0].data = data.cpu_history.map(r => parseFloat(r.valor));
      cpuChart.update('none');
    }
    // Donut
    donutChart.data.datasets[0].data = [
      parseFloat(mMap['CPU']?.valor || 0),
      parseFloat(mMap['RAM']?.valor || 0),
      parseFloat(mMap['Disco']?.valor || 0),
      parseFloat(mMap['Swap']?.valor || 0),
    ];
    donutChart.update();

    // Procesos
    const tbody = document.getElementById('proc-tbody');
    if (data.processes?.length) {
      tbody.innerHTML = data.processes.map(p => `
        <tr>
          <td class="font-mono text-secondary">${p.pid}</td>
          <td class="fw-semibold">${p.name}</td>
          <td>${stateBadge(p.state)}</td>
          <td class="font-mono ${p.vmrss_kb > 100000 ? 'text-warning' : ''}">${p.vmrss_kb.toLocaleString()}</td>
          <td class="text-secondary">${p.threads}</td>
        </tr>`).join('');
    } else {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-3">Sin datos (¿el monitor está activo?)</td></tr>';
    }
  } catch (e) { console.error('metrics fetch:', e); }
}
fetchMetrics();
setInterval(fetchMetrics, 5000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
