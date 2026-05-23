<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();
$cat_slug = $_GET['cat'] ?? '';
$search   = trim($_GET['q'] ?? '');

$where  = "p.activo = 1";
$params = [];
if ($cat_slug !== '') { $where .= " AND c.slug = ?"; $params[] = $cat_slug; }
if ($search !== '')   { $where .= " AND (p.nombre LIKE ? OR p.marca LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
    SELECT p.*, c.nombre AS cat_nombre, c.slug AS cat_slug, c.icono AS cat_icono
    FROM productos p
    JOIN categorias c ON c.id = p.id_categoria
    WHERE $where
    ORDER BY p.destacado DESC, p.id DESC
");
$stmt->execute($params);
$productos = $stmt->fetchAll();
$categorias = $db->query("SELECT * FROM categorias ORDER BY id")->fetchAll();

$page_title = 'Catálogo — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-5">
  <div class="sm-section-h">
    <div>
      <h2><?= $cat_slug ? h(array_values(array_filter($categorias, fn($c)=>$c['slug']===$cat_slug))[0]['nombre'] ?? 'Catálogo') : 'Catálogo completo' ?></h2>
      <p class="mb-0"><?= count($productos) ?> productos disponibles<?= $search !== '' ? ' para "' . h($search) . '"' : '' ?></p>
    </div>
    <form class="d-flex gap-2" method="GET">
      <?php if ($cat_slug): ?><input type="hidden" name="cat" value="<?= h($cat_slug) ?>"><?php endif; ?>
      <input type="search" name="q" class="form-control" placeholder="Buscar..." value="<?= h($search) ?>" style="min-width:240px;">
      <button class="btn sm-btn-primary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="sm-cat-pills">
    <a href="/productos.php" class="sm-cat-pill <?= $cat_slug === '' ? 'active' : '' ?>">
      <i class="bi bi-grid-3x3-gap"></i> Todas
    </a>
    <?php foreach ($categorias as $c): ?>
    <a href="/productos.php?cat=<?= h($c['slug']) ?>" class="sm-cat-pill <?= $cat_slug === $c['slug'] ? 'active' : '' ?>">
      <i class="bi <?= h($c['icono']) ?>"></i> <?= h($c['nombre']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($productos)): ?>
    <div class="text-center py-5 text-secondary">
      <i class="bi bi-search" style="font-size:3rem"></i>
      <p class="mt-3">No se encontraron productos.</p>
      <a href="/productos.php" class="btn sm-btn-primary mt-2">Ver todos</a>
    </div>
  <?php else: ?>
    <div class="sm-prod-grid sm-stagger">
      <?php foreach ($productos as $p):
        $stock_cls = $p['stock'] > 10 ? '' : ($p['stock'] > 0 ? 'low' : 'out');
        $stock_txt = $p['stock'] > 10 ? "Stock: {$p['stock']}" : ($p['stock'] > 0 ? "Quedan {$p['stock']}" : "Agotado");
      ?>
      <div class="sm-prod-card">
        <div class="sm-prod-img">
          <?php if ($p['destacado']): ?><span class="sm-prod-badge">★ Destacado</span><?php endif; ?>
          <span class="sm-prod-stock <?= $stock_cls ?>"><?= $stock_txt ?></span>
          <i class="bi <?= h($p['cat_icono']) ?>"></i>
        </div>
        <div class="sm-prod-body">
          <div class="sm-prod-marca"><?= h($p['marca']) ?> · <?= h($p['cat_nombre']) ?></div>
          <div class="sm-prod-name"><?= h($p['nombre']) ?></div>
          <div class="sm-prod-price">S/ <?= number_format((float)$p['precio'], 2) ?> <small>PEN</small></div>
          <div class="sm-prod-actions">
            <button class="btn-add" <?= $p['stock'] == 0 ? 'disabled' : '' ?>
                    onclick="smAddToCart(<?= (int)$p['id'] ?>, this)">
              <i class="bi bi-cart-plus me-1"></i>Agregar
            </button>
            <a href="/producto.php?id=<?= (int)$p['id'] ?>" class="btn-view">
              <i class="bi bi-eye"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
