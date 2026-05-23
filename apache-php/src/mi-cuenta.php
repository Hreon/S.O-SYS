<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$db = getDB();
$user = currentUser();
$stmt = $db->prepare("
    SELECT id, total, estado, direccion, created_at
    FROM pedidos WHERE id_usuario = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$pedidos = $stmt->fetchAll();

$page_title = 'Mi Cuenta — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="sm-card sm-card-pad text-center">
        <div class="sm-avatar mx-auto" style="width:80px;height:80px;font-size:2rem;border-radius:20px"><?= strtoupper(mb_substr($user['nombre'],0,1)) ?></div>
        <h4 class="mt-3 mb-1"><?= h($user['nombre'].' '.$user['apellido']) ?></h4>
        <div class="text-secondary small"><?= h($user['email']) ?></div>
        <span class="badge bg-primary mt-2"><?= h($user['rol']) ?></span>
      </div>
    </div>
    <div class="col-lg-8">
      <h4 class="mb-3" style="font-weight:800"><i class="bi bi-bag-check text-primary me-2"></i>Mis pedidos</h4>
      <?php if (empty($pedidos)): ?>
        <div class="sm-card sm-card-pad text-center py-4">
          <p class="text-secondary mb-3">Aún no tienes pedidos.</p>
          <a href="/productos.php" class="btn sm-btn-primary">Ir al catálogo</a>
        </div>
      <?php else: ?>
        <div class="sm-table-wrap">
          <table class="sm-table">
            <thead><tr><th>#</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Envío</th></tr></thead>
            <tbody>
              <?php foreach ($pedidos as $p):
                $colors = ['pendiente'=>'warning','pagado'=>'info','enviado'=>'primary','entregado'=>'success','cancelado'=>'danger'];
                $c = $colors[$p['estado']] ?? 'secondary';
              ?>
              <tr>
                <td class="font-mono">#<?= $p['id'] ?></td>
                <td><?= h(substr($p['created_at'],0,16)) ?></td>
                <td class="font-mono fw-bold">S/ <?= number_format($p['total'],2) ?></td>
                <td><span class="badge bg-<?= $c ?>"><?= h($p['estado']) ?></span></td>
                <td class="small text-secondary"><?= h($p['direccion']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
