<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$db = getDB();

// KPIs
$kpi_usuarios  = (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$kpi_productos = (int)$db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
$kpi_pedidos   = (int)$db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$ingresos      = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado IN ('pagado','enviado','entregado')")->fetchColumn();
$kpi_alertas   = (int)$db->query("SELECT COUNT(*) FROM alertas WHERE activa=1")->fetchColumn();
$kpi_notifs    = (int)$db->query("SELECT COUNT(*) FROM notificaciones WHERE DATE(fired_at)=CURDATE()")->fetchColumn();

$usuarios = $db->query("SELECT id,nombre,apellido,email,rol,activo,created_at FROM usuarios ORDER BY id DESC")->fetchAll();
$pedidos  = $db->query("
    SELECT p.id, p.total, p.estado, p.created_at, u.email
    FROM pedidos p JOIN usuarios u ON u.id=p.id_usuario
    ORDER BY p.created_at DESC LIMIT 25
")->fetchAll();
$alertas_disparadas = $db->query("
    SELECT n.fired_at, n.valor_detectado, mc.nombre as metrica, u.email, a.umbral, a.operador
    FROM notificaciones n
    JOIN alertas a ON a.id=n.id_alerta
    JOIN metricas_catalogo mc ON mc.id=a.id_metrica
    JOIN usuarios u ON u.id=a.id_usuario
    ORDER BY n.fired_at DESC LIMIT 15
")->fetchAll();
$top_productos = $db->query("
    SELECT p.nombre, p.marca, p.precio, p.stock, p.imagen,
           COALESCE(SUM(pd.cantidad),0) AS vendidos
    FROM productos p
    LEFT JOIN pedidos_detalle pd ON pd.id_producto = p.id
    GROUP BY p.id ORDER BY vendidos DESC LIMIT 10
")->fetchAll();

$page_title = 'Panel Admin — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <h2 class="mb-4" style="font-weight:800;letter-spacing:-.02em">
    <i class="bi bi-gear-fill text-warning me-2"></i>Panel de Administración
  </h2>

  <div class="row g-3 mb-4 sm-stagger">
    <?php
    $kpis = [
      ['Usuarios',     'bi-people-fill',         'primary', $kpi_usuarios],
      ['Productos',    'bi-box-seam',            'info',    $kpi_productos],
      ['Pedidos',      'bi-receipt',             'success', $kpi_pedidos],
      ['Ingresos S/',  'bi-cash-coin',           'warning', number_format($ingresos,2)],
      ['Alertas SO',   'bi-bell-fill',           'danger',  $kpi_alertas],
      ['Notif. hoy',   'bi-exclamation-triangle','danger',  $kpi_notifs],
    ];
    foreach ($kpis as [$l,$i,$c,$v]):
    ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="sm-card sm-card-pad">
        <div style="width:36px;height:36px;border-radius:10px;background:var(--bs-<?=$c?>);opacity:.85;display:flex;align-items:center;justify-content:center;color:white">
          <i class="bi <?=$i?>"></i>
        </div>
        <div class="fs-3 fw-bold mt-2" style="letter-spacing:-.02em"><?=$v?></div>
        <div class="small text-secondary"><?=$l?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <ul class="nav nav-tabs border-secondary mb-3" id="adminTabs">
    <li class="nav-item"><button class="nav-link active text-light" data-bs-toggle="tab" data-bs-target="#t-users"><i class="bi bi-people me-1"></i>Usuarios</button></li>
    <li class="nav-item"><button class="nav-link text-light" data-bs-toggle="tab" data-bs-target="#t-orders"><i class="bi bi-bag me-1"></i>Pedidos</button></li>
    <li class="nav-item"><button class="nav-link text-light" data-bs-toggle="tab" data-bs-target="#t-products"><i class="bi bi-trophy me-1"></i>Top productos</button></li>
    <li class="nav-item"><button class="nav-link text-light" data-bs-toggle="tab" data-bs-target="#t-alerts"><i class="bi bi-bell me-1"></i>Alertas disparadas</button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="t-users">
      <div class="sm-table-wrap">
        <table class="sm-table">
          <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Registro</th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u):
              $rolColor = ['admin'=>'warning','cliente'=>'primary'][$u['rol']] ?? 'secondary'; ?>
            <tr>
              <td class="text-secondary font-mono"><?=$u['id']?></td>
              <td class="fw-semibold"><?= h($u['nombre'].' '.$u['apellido']) ?></td>
              <td><?= h($u['email']) ?></td>
              <td><span class="badge bg-<?=$rolColor?>"><?= h($u['rol']) ?></span></td>
              <td><?= $u['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></td>
              <td class="small text-secondary"><?= substr($u['created_at'],0,10) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="t-orders">
      <div class="sm-table-wrap">
        <table class="sm-table">
          <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php if (empty($pedidos)): ?>
              <tr><td colspan="5" class="text-center text-secondary py-3">Aún no hay pedidos.</td></tr>
            <?php else: foreach ($pedidos as $p):
              $col = ['pendiente'=>'warning','pagado'=>'info','enviado'=>'primary','entregado'=>'success','cancelado'=>'danger'][$p['estado']] ?? 'secondary'; ?>
              <tr>
                <td class="font-mono">#<?= $p['id'] ?></td>
                <td><?= h($p['email']) ?></td>
                <td class="font-mono fw-bold">S/ <?= number_format($p['total'],2) ?></td>
                <td><span class="badge bg-<?=$col?>"><?= h($p['estado']) ?></span></td>
                <td class="small text-secondary"><?= substr($p['created_at'],0,16) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="t-products">
      <div class="sm-table-wrap">
        <table class="sm-table">
          <thead><tr><th>Producto</th><th>Marca</th><th>Precio</th><th>Stock</th><th>Vendidos</th></tr></thead>
          <tbody>
            <?php foreach ($top_productos as $p): ?>
            <tr>
              <td class="fw-semibold"><?= h($p['nombre']) ?></td>
              <td class="text-secondary"><?= h($p['marca']) ?></td>
              <td class="font-mono">S/ <?= number_format($p['precio'],2) ?></td>
              <td><span class="badge bg-<?= $p['stock']>10?'success':($p['stock']>0?'warning':'danger') ?>"><?= $p['stock'] ?></span></td>
              <td class="font-mono fw-bold text-primary"><?= $p['vendidos'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="t-alerts">
      <div class="sm-table-wrap">
        <table class="sm-table">
          <thead><tr><th>Fecha</th><th>Métrica</th><th>Usuario</th><th>Regla</th><th>Valor detectado</th></tr></thead>
          <tbody>
            <?php if (empty($alertas_disparadas)): ?>
              <tr><td colspan="5" class="text-center text-secondary py-3">No hay alertas disparadas aún.</td></tr>
            <?php else: foreach ($alertas_disparadas as $n): ?>
              <tr>
                <td class="font-mono text-secondary"><?= substr($n['fired_at'],0,19) ?></td>
                <td class="fw-semibold text-warning"><?= h($n['metrica']) ?></td>
                <td><?= h($n['email']) ?></td>
                <td class="font-mono"><?= h($n['operador']) ?> <?= $n['umbral'] ?></td>
                <td class="text-danger fw-bold"><?= round($n['valor_detectado'],2) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
