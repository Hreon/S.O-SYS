<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();
// Productos destacados (max 8)
$destacados = $db->query("
    SELECT p.*, c.slug AS cat_slug, c.icono AS cat_icono
    FROM productos p
    JOIN categorias c ON c.id = p.id_categoria
    WHERE p.activo = 1 AND p.destacado = 1
    ORDER BY RAND() LIMIT 8
")->fetchAll();
$categorias = $db->query("SELECT * FROM categorias ORDER BY id")->fetchAll();

// Conteo simple para hero
$total_productos = (int)$db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();

$page_title = 'SysMarket — Tienda Tech + Monitor de Servidor';
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="sm-hero">
  <div class="container-xl">
    <span class="sm-hero-eyebrow">
      <i class="bi bi-lightning-charge-fill"></i>
      Trabajo Final — Sistemas Operativos USIL 2026
    </span>
    <h1 class="sm-hero-title">
      Componentes de PC<br>
      <span class="grad">de alto rendimiento</span><br>
      con monitor de servidor en vivo.
    </h1>
    <p class="sm-hero-sub">
      SysMarket es una tienda de hardware donde la integridad del servicio se monitorea en tiempo real. Lectura directa del kernel Linux, hilos POSIX concurrentes y mutex para una arquitectura confiable.
    </p>
    <div class="sm-hero-actions">
      <a href="/productos.php" class="btn sm-btn-primary">
        <i class="bi bi-grid-3x3-gap me-2"></i>Explorar catálogo
      </a>
      <a href="#destacados" class="btn sm-btn-ghost">
        <i class="bi bi-stars me-2"></i>Ver destacados
      </a>
    </div>
    <div class="sm-hero-stats">
      <div class="sm-hero-stat">
        <div class="v"><?= $total_productos ?>+</div>
        <div class="l">Productos en stock</div>
      </div>
      <div class="sm-hero-stat">
        <div class="v">5</div>
        <div class="l">Hilos concurrentes monitor</div>
      </div>
      <div class="sm-hero-stat">
        <div class="v">/proc</div>
        <div class="l">Lectura del kernel Linux</div>
      </div>
      <div class="sm-hero-stat">
        <div class="v">99.5%</div>
        <div class="l">Uptime objetivo</div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORÍAS -->
<section class="sm-section">
  <div class="container-xl">
    <div class="sm-section-h">
      <div>
        <h2>Comprar por categoría</h2>
        <p>Encuentra exactamente lo que necesitas para tu build.</p>
      </div>
    </div>
    <div class="row g-3 sm-stagger">
      <?php foreach ($categorias as $cat): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <a href="/productos.php?cat=<?= h($cat['slug']) ?>" class="sm-card sm-card-pad text-decoration-none d-flex flex-column align-items-center text-center" style="color: var(--sm-text); transition: all .25s ease;">
          <div style="width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,var(--sm-primary),var(--sm-accent));display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:white;margin-bottom:12px;">
            <i class="bi <?= h($cat['icono']) ?>"></i>
          </div>
          <div class="fw-semibold"><?= h($cat['nombre']) ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DESTACADOS -->
<section id="destacados" class="sm-section">
  <div class="container-xl">
    <div class="sm-section-h">
      <div>
        <h2>Productos destacados <i class="bi bi-stars text-warning"></i></h2>
        <p>Los más vendidos y mejor valorados.</p>
      </div>
      <a href="/productos.php" class="btn sm-btn-ghost btn-sm">Ver todo <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="sm-prod-grid sm-stagger">
      <?php foreach ($destacados as $p):
        $stock_cls = $p['stock'] > 10 ? '' : ($p['stock'] > 0 ? 'low' : 'out');
        $stock_txt = $p['stock'] > 10 ? "Stock: {$p['stock']}" : ($p['stock'] > 0 ? "Quedan {$p['stock']}" : "Agotado");
      ?>
      <div class="sm-prod-card">
        <div class="sm-prod-img">
          <span class="sm-prod-badge">★ Destacado</span>
          <span class="sm-prod-stock <?= $stock_cls ?>"><?= $stock_txt ?></span>
          <i class="bi <?= h($p['cat_icono']) ?>"></i>
        </div>
        <div class="sm-prod-body">
          <div class="sm-prod-marca"><?= h($p['marca']) ?></div>
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
  </div>
</section>

<!-- DIFERENCIADOR TÉCNICO -->
<section class="sm-section">
  <div class="container-xl">
    <div class="sm-card sm-card-pad" style="padding: 40px;">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <span class="sm-hero-eyebrow"><i class="bi bi-cpu"></i> Arquitectura técnica</span>
          <h2 class="mt-3 mb-3" style="font-weight:800;letter-spacing:-.02em">¿Por qué SysMarket es diferente?</h2>
          <p class="text-secondary">
            No solo vendemos hardware. Demostramos cómo administramos los recursos del sistema operativo que aloja nuestra tienda: monitoreo en tiempo real del servidor mediante hilos POSIX concurrentes, mutex para evitar condiciones de carrera, y lectura directa del kernel Linux a través del sistema de archivos virtual <code class="font-mono text-primary">/proc</code>.
          </p>
          <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="badge bg-primary-subtle text-primary border border-primary">Python 3.11 threading</span>
            <span class="badge bg-primary-subtle text-primary border border-primary">PHP 8.2 + Apache</span>
            <span class="badge bg-primary-subtle text-primary border border-primary">MySQL 8.0</span>
            <span class="badge bg-primary-subtle text-primary border border-primary">Docker Compose</span>
            <span class="badge bg-primary-subtle text-primary border border-primary">AWS EC2</span>
          </div>
        </div>
        <div class="col-lg-5">
          <pre class="font-mono p-3 mb-0" style="background:var(--sm-bg-0);border:1px solid var(--sm-border);border-radius:12px;color:#a5f3fc;font-size:.78rem;line-height:1.5;overflow:auto"><span style="color:#94a3b8"># /host/proc/stat — kernel syscall</span>
with metrics_lock:   <span style="color:#fbbf24"># mutex</span>
    cpu = read_cpu_from_proc()

<span style="color:#94a3b8"># 5 hilos concurrentes corriendo:</span>
read_cpu()        <span style="color:#22c55e">✓ OK</span>
read_memory()     <span style="color:#22c55e">✓ OK</span>
read_disk()       <span style="color:#22c55e">✓ OK</span>
read_loadavg()    <span style="color:#22c55e">✓ OK</span>
read_processes()  <span style="color:#22c55e">✓ OK</span>

<span style="color:#94a3b8"># Persistir snapshot:</span>
save_to_db(snapshot)</pre>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
