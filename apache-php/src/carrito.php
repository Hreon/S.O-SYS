<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$db = getDB();
$user = currentUser();

$stmt = $db->prepare("
    SELECT c.id, c.cantidad, c.id_producto,
           p.nombre, p.marca, p.precio, p.stock, p.imagen,
           cat.icono AS cat_icono
    FROM carrito c
    JOIN productos p ON p.id = c.id_producto
    JOIN categorias cat ON cat.id = p.id_categoria
    WHERE c.id_usuario = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$subtotal = 0;
foreach ($items as $i) $subtotal += $i['cantidad'] * $i['precio'];
$envio = $subtotal > 0 ? 15.00 : 0;
$total = $subtotal + $envio;

$page_title = 'Mi Carrito — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <h2 class="mb-4" style="font-weight:800;letter-spacing:-.02em">
    <i class="bi bi-cart3 text-primary me-2"></i>Mi Carrito
    <span class="badge bg-primary ms-2"><?= count($items) ?> items</span>
  </h2>

  <?php if (empty($items)): ?>
    <div class="sm-card sm-card-pad text-center py-5">
      <i class="bi bi-cart-x" style="font-size:4rem;color:var(--sm-text-mute)"></i>
      <h4 class="mt-3">Tu carrito está vacío</h4>
      <p class="text-secondary">Explora el catálogo y agrega productos.</p>
      <a href="/productos.php" class="btn sm-btn-primary mt-2">Ir al catálogo</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <?php foreach ($items as $it): ?>
        <div class="sm-cart-item">
          <div class="sm-cart-thumb"><i class="bi <?= h($it['cat_icono']) ?>"></i></div>
          <div>
            <div class="sm-prod-marca"><?= h($it['marca']) ?></div>
            <a href="/producto.php?id=<?= $it['id_producto'] ?>" class="fw-semibold text-decoration-none" style="color:var(--sm-text)"><?= h($it['nombre']) ?></a>
            <div class="small text-secondary mt-1">S/ <?= number_format($it['precio'],2) ?> c/u</div>
          </div>
          <div class="sm-qty-ctrl">
            <button onclick="smCartUpdate(<?= $it['id_producto'] ?>, -1)"><i class="bi bi-dash"></i></button>
            <input type="text" value="<?= $it['cantidad'] ?>" readonly>
            <button onclick="smCartUpdate(<?= $it['id_producto'] ?>, 1)" <?= $it['cantidad'] >= $it['stock'] ? 'disabled' : '' ?>><i class="bi bi-plus"></i></button>
          </div>
          <div class="text-end fw-bold font-mono" style="min-width:90px">S/ <?= number_format($it['cantidad']*$it['precio'],2) ?></div>
          <button class="sm-btn-danger" onclick="smCartRemove(<?= $it['id_producto'] ?>)"><i class="bi bi-trash"></i></button>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="col-lg-4">
        <div class="sm-summary">
          <h5 class="mb-3" style="font-weight:800">Resumen del pedido</h5>
          <div class="sm-summary-row">
            <span class="text-secondary">Subtotal (<?= array_sum(array_column($items,'cantidad')) ?> items)</span>
            <span class="font-mono">S/ <?= number_format($subtotal,2) ?></span>
          </div>
          <div class="sm-summary-row">
            <span class="text-secondary">Envío</span>
            <span class="font-mono">S/ <?= number_format($envio,2) ?></span>
          </div>
          <div class="sm-summary-row total">
            <span>Total</span>
            <span class="font-mono" style="color:var(--sm-primary)">S/ <?= number_format($total,2) ?></span>
          </div>
          <form method="POST" action="/api/checkout.php" class="mt-3">
            <div class="mb-3">
              <label class="sm-label">Dirección de envío</label>
              <input type="text" name="direccion" class="sm-input" required placeholder="Av. La Marina 2500, San Miguel, Lima">
            </div>
            <button type="submit" class="btn sm-btn-primary w-100 btn-lg">
              <i class="bi bi-credit-card me-2"></i>Confirmar pedido
            </button>
            <a href="/productos.php" class="btn sm-btn-ghost w-100 mt-2">
              <i class="bi bi-arrow-left me-2"></i>Seguir comprando
            </a>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
