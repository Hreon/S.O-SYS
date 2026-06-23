<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/social_helpers.php';
$id = (int)($_GET['id'] ?? 0);
$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, c.nombre AS cat_nombre, c.slug AS cat_slug, c.icono AS cat_icono
    FROM productos p JOIN categorias c ON c.id = p.id_categoria
    WHERE p.id = ? AND p.activo = 1
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    flashSet('warning', 'Producto no encontrado.');
    header('Location: /productos.php'); exit;
}

// Relacionados (misma categoría, distinto id)
$rel = $db->prepare("
    SELECT p.*, c.icono AS cat_icono FROM productos p JOIN categorias c ON c.id=p.id_categoria
    WHERE p.id_categoria = ? AND p.id <> ? AND p.activo=1 ORDER BY RAND() LIMIT 4
");
$rel->execute([$p['id_categoria'], $p['id']]);
$relacionados = $rel->fetchAll();

$page_title = h($p['nombre']) . ' — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <nav class="small mb-3" style="color:var(--sm-text-mute)">
    <a href="/index.php" style="color:var(--sm-text-mute)">Inicio</a> /
    <a href="/productos.php?cat=<?= h($p['cat_slug']) ?>" style="color:var(--sm-text-mute)"><?= h($p['cat_nombre']) ?></a> /
    <span><?= h($p['nombre']) ?></span>
  </nav>

  <div class="sm-product-detail">
    <div class="sm-detail-img">
      <i class="bi <?= h($p['cat_icono']) ?>"></i>
    </div>
    <div>
      <div class="sm-prod-marca mb-1"><?= h($p['marca']) ?> · <?= h($p['cat_nombre']) ?></div>
      <h2 class="mb-3" style="font-weight:800;letter-spacing:-.02em"><?= h($p['nombre']) ?></h2>

      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="sm-prod-price" style="font-size:2rem">S/ <?= number_format((float)$p['precio'], 2) ?> <small>PEN</small></div>
        <?php
          $cls = $p['stock'] > 10 ? '' : ($p['stock'] > 0 ? 'low' : 'out');
          $txt = $p['stock'] > 10 ? "✓ En stock ({$p['stock']})"
               : ($p['stock'] > 0 ? "⚠ Últimos {$p['stock']}" : "✗ Agotado");
        ?>
        <span class="sm-prod-stock <?= $cls ?>" style="position:static"><?= $txt ?></span>
      </div>

      <p style="color:var(--sm-text-mute);line-height:1.7"><?= nl2br(h($p['descripcion'])) ?></p>

      <div class="d-flex gap-2 mt-4">
        <button class="btn sm-btn-primary btn-lg" style="flex:1" <?= $p['stock'] == 0 ? 'disabled' : '' ?>
                onclick="smAddToCart(<?= (int)$p['id'] ?>, this)">
          <i class="bi bi-cart-plus me-2"></i>Agregar al carrito
        </button>
        <a href="/carrito.php" class="btn sm-btn-ghost btn-lg">
          <i class="bi bi-cart3"></i>
        </a>
      </div>

      <div class="row g-3 mt-4">
        <div class="col-6">
          <div class="sm-card sm-card-pad">
            <i class="bi bi-truck text-primary fs-4"></i>
            <div class="fw-semibold mt-2">Envío gratis</div>
            <div class="small text-secondary">Lima Metropolitana</div>
          </div>
        </div>
        <div class="col-6">
          <div class="sm-card sm-card-pad">
            <i class="bi bi-shield-check text-success fs-4"></i>
            <div class="fw-semibold mt-2">Garantía 1 año</div>
            <div class="small text-secondary">Cobertura completa</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       BLOQUE SOCIAL — likes + reseñas
       ============================================================ -->
  <hr class="my-5" style="border-color:var(--sm-border)">
  <link rel="stylesheet" href="/assets/css/social.css">
  <?php
    $sm_total_likes = likesCount((int)$p['id']);
    $sm_user_liked  = isLoggedIn() ? userLiked((int)$p['id'], (int)$_SESSION['user_id']) : false;
    $sm_avg         = avgRating((int)$p['id']);
    $sm_reviews     = getComments((int)$p['id'], 20);
  ?>

  <div class="row g-4">
    <div class="col-lg-12">
      <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
        <button class="sm-like-btn <?= $sm_user_liked ? 'liked' : '' ?>"
                data-producto-id="<?= (int)$p['id'] ?>">
          <i class="bi bi-heart-fill like-icon"></i>
          <span><?= $sm_user_liked ? 'Te gusta' : 'Me gusta' ?></span>
          <span class="like-count"><?= $sm_total_likes ?></span>
        </button>

        <?php if (count($sm_reviews) > 0): ?>
          <div>
            <?= renderStars($sm_avg) ?>
            <span class="text-secondary ms-2">
              <?= number_format($sm_avg, 1) ?>
              <span class="small">(<?= count($sm_reviews) ?> reseña<?= count($sm_reviews) > 1 ? 's' : '' ?>)</span>
            </span>
          </div>
        <?php endif; ?>
      </div>

      <h4 class="mb-3"><i class="bi bi-chat-quote me-2"></i>Reseñas y comentarios</h4>

      <?php if (isLoggedIn()): ?>
      <form id="frmComentario" class="sm-card sm-card-pad mb-4">
        <input type="hidden" name="producto_id" value="<?= (int)$p['id'] ?>">

        <label class="d-block fw-semibold mb-2">Tu calificación</label>
        <div class="sm-rating-input mb-3">
          <input type="radio" id="r5" name="rating" value="5"><label for="r5"><i class="bi bi-star-fill"></i></label>
          <input type="radio" id="r4" name="rating" value="4"><label for="r4"><i class="bi bi-star-fill"></i></label>
          <input type="radio" id="r3" name="rating" value="3"><label for="r3"><i class="bi bi-star-fill"></i></label>
          <input type="radio" id="r2" name="rating" value="2"><label for="r2"><i class="bi bi-star-fill"></i></label>
          <input type="radio" id="r1" name="rating" value="1"><label for="r1"><i class="bi bi-star-fill"></i></label>
        </div>

        <textarea name="comentario" rows="3" required maxlength="1000"
                  class="form-control sm-form-dark mb-2"
                  placeholder="Cuenta tu experiencia con este producto..."></textarea>

        <button type="submit" class="btn sm-btn-primary">
          <i class="bi bi-send me-1"></i>Publicar reseña
        </button>
      </form>
      <?php else: ?>
      <div class="sm-card sm-card-pad text-center py-3 mb-4">
        <p class="text-secondary mb-0">
          <a href="/login.php" class="text-info">Inicia sesión</a> para dejar una reseña.
        </p>
      </div>
      <?php endif; ?>

      <div id="reviewList">
        <?php if (empty($sm_reviews)): ?>
          <div class="text-secondary text-center py-3 empty-state">
            Aún no hay reseñas. ¡Sé el primero en opinar!
          </div>
        <?php else: foreach ($sm_reviews as $r): ?>
          <div class="sm-review-item">
            <div class="d-flex justify-content-between mb-2">
              <span class="fw-semibold"><?= h($r['user_nombre'] . ' ' . $r['user_apellido']) ?></span>
              <span class="small text-secondary"><?= h($r['creado_en']) ?></span>
            </div>
            <div class="sm-stars mb-2"><?= renderStars((float)$r['rating']) ?></div>
            <p class="text-secondary mb-0"><?= nl2br(h($r['comentario'])) ?></p>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <script src="/assets/js/social.js"></script>
  <!-- ============================================================
       FIN BLOQUE SOCIAL
       ============================================================ -->

  <?php if (!empty($relacionados)): ?>
  <hr class="my-5" style="border-color:var(--sm-border)">
  <h4 class="mb-4">También te puede interesar</h4>
  <div class="sm-prod-grid sm-stagger">
    <?php foreach ($relacionados as $r):
      $cls = $r['stock'] > 10 ? '' : ($r['stock'] > 0 ? 'low' : 'out');
      $txt = $r['stock'] > 10 ? "Stock: {$r['stock']}" : ($r['stock'] > 0 ? "Quedan {$r['stock']}" : "Agotado");
    ?>
    <div class="sm-prod-card">
      <div class="sm-prod-img">
        <span class="sm-prod-stock <?= $cls ?>"><?= $txt ?></span>
        <i class="bi <?= h($r['cat_icono']) ?>"></i>
      </div>
      <div class="sm-prod-body">
        <div class="sm-prod-marca"><?= h($r['marca']) ?></div>
        <div class="sm-prod-name"><?= h($r['nombre']) ?></div>
        <div class="sm-prod-price">S/ <?= number_format((float)$r['precio'], 2) ?></div>
        <div class="sm-prod-actions">
          <button class="btn-add" <?= $r['stock']==0?'disabled':'' ?> onclick="smAddToCart(<?= (int)$r['id'] ?>, this)">
            <i class="bi bi-cart-plus me-1"></i>Agregar
          </button>
          <a href="/producto.php?id=<?= (int)$r['id'] ?>" class="btn-view"><i class="bi bi-eye"></i></a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
