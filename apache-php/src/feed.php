<?php
/**
 * SysMarket v2 — Feed Social (Shop the Post)
 * Página pública con publicaciones de la comunidad y productos etiquetados.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/social_helpers.php';

$posts        = recentPosts(20);
$top_products = topLikedProducts(6);
$is_logged    = isLoggedIn();

// Catálogo para el dropdown de etiquetar producto al publicar
$catalogo = getDB()->query(
    "SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre ASC LIMIT 50"
)->fetchAll();

$page_title = 'Feed Social — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/social.css">

<div class="container-xl py-4">

    <!-- HERO del feed -->
    <div class="d-flex flex-wrap align-items-end justify-content-between mb-4 gap-3">
      <div>
        <span class="sm-hero-eyebrow">
          <i class="bi bi-heart-fill text-danger"></i>
          Social Commerce
        </span>
        <h2 class="mt-2 mb-1" style="font-weight:800;letter-spacing:-.02em">
          Feed de la comunidad
        </h2>
        <p class="text-secondary mb-0">
          Publicaciones de usuarios reales sobre los productos.
          Etiqueta un producto y compra directo desde el post.
        </p>
      </div>
    </div>

    <div class="row g-4">
      <!-- COLUMNA PRINCIPAL: posts -->
      <div class="col-lg-8">

        <?php if ($is_logged): ?>
        <!-- Formulario crear publicación -->
        <div class="sm-card sm-card-pad mb-4">
          <h5 class="mb-3">
            <i class="bi bi-pencil-square text-primary me-2"></i>
            Comparte tu experiencia
          </h5>
          <form id="frmPublicacion">
            <input type="text" name="titulo" maxlength="150" required
                   class="form-control sm-form-dark mb-2"
                   placeholder="Título de tu publicación...">
            <textarea name="contenido" maxlength="2000" required rows="3"
                      class="form-control sm-form-dark mb-2"
                      placeholder="¿Qué quieres compartir sobre algún producto?"></textarea>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <select name="producto_id" class="form-select sm-form-dark" style="max-width:320px;">
                <option value="">— Sin producto etiquetado —</option>
                <?php foreach ($catalogo as $cp): ?>
                  <option value="<?= (int) $cp['id'] ?>"><?= h($cp['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn sm-btn-primary">
                <i class="bi bi-send me-1"></i>Publicar
              </button>
            </div>
          </form>
        </div>
        <?php else: ?>
        <div class="sm-card sm-card-pad mb-4 text-center">
          <p class="text-secondary mb-2">
            <a href="/login.php" class="text-info">Inicia sesión</a> para publicar y comentar.
          </p>
        </div>
        <?php endif; ?>

        <!-- Feed de publicaciones -->
        <div id="feedPosts">
          <?php if (empty($posts)): ?>
            <div class="sm-card sm-card-pad text-center py-5">
              <p class="text-secondary mb-0">
                Aún no hay publicaciones. ¡Sé el primero en compartir!
              </p>
            </div>
          <?php else: foreach ($posts as $post): ?>
            <article class="sm-card sm-card-pad mb-3">
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="sm-avatar">
                  <?= strtoupper(mb_substr($post['user_nombre'], 0, 1)) ?>
                </span>
                <div>
                  <div class="fw-semibold"><?= h($post['user_nombre'] . ' ' . $post['user_apellido']) ?></div>
                  <div class="small text-secondary"><?= h($post['creado_en']) ?></div>
                </div>
              </div>
              <h4 class="mb-2" style="font-weight:700;letter-spacing:-.01em">
                <?= h($post['titulo']) ?>
              </h4>
              <p class="text-secondary" style="line-height:1.6">
                <?= nl2br(h($post['contenido'])) ?>
              </p>

              <?php if ($post['producto_id']): ?>
              <!-- Shop the Post -->
              <a href="/producto.php?id=<?= (int) $post['producto_id'] ?>"
                 class="sm-shop-the-post text-decoration-none d-block mt-3 p-3">
                <div class="small fw-bold mb-2" style="color:var(--sm-accent);letter-spacing:.12em">
                  <i class="bi bi-bag-heart me-1"></i>SHOP THE POST
                </div>
                <div class="d-flex align-items-center gap-3">
                  <div class="sm-shop-thumb">
                    <i class="bi <?= h($post['producto_icono'] ?? 'bi-box') ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="small text-secondary"><?= h($post['producto_marca']) ?></div>
                    <div class="fw-semibold"><?= h($post['producto_nombre']) ?></div>
                    <div class="fw-bold" style="color:var(--sm-primary)">
                      S/ <?= number_format((float) $post['producto_precio'], 2) ?>
                    </div>
                  </div>
                  <div class="ms-auto small fw-semibold">
                    Ver producto <i class="bi bi-arrow-right ms-1"></i>
                  </div>
                </div>
              </a>
              <?php endif; ?>
            </article>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- COLUMNA LATERAL: top likeados -->
      <aside class="col-lg-4">
        <div class="sm-card sm-card-pad sticky-top" style="top:1rem;">
          <h5 class="mb-3">
            <i class="bi bi-heart-fill text-danger me-2"></i>
            Más likeados
          </h5>
          <?php if (empty($top_products)): ?>
            <p class="text-secondary small">Aún no hay productos con likes.</p>
          <?php else: ?>
            <ul class="list-unstyled mb-0">
              <?php foreach ($top_products as $tp): ?>
              <li class="sm-top-item">
                <a href="/producto.php?id=<?= (int) $tp['id'] ?>"
                   class="d-flex align-items-center gap-2 text-decoration-none py-2">
                  <div class="sm-mini-thumb">
                    <i class="bi <?= h($tp['cat_icono']) ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="small fw-semibold text-light"><?= h($tp['nombre']) ?></div>
                    <div class="small text-secondary">
                      <i class="bi bi-heart-fill text-danger"></i>
                      <?= (int) $tp['total_likes'] ?> likes
                    </div>
                  </div>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </aside>
    </div>
</div>

<script src="/assets/js/social.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
