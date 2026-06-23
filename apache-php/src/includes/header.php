<?php
require_once __DIR__ . '/auth.php';
$user        = currentUser();
$cart_total  = cartCount();
$current_pg  = basename($_SERVER['PHP_SELF']);
$page_title  = $page_title ?? 'SysMarket — Tienda Tech';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="sm-navbar">
  <div class="sm-navbar-inner container-xl">
    <a class="sm-brand" href="/index.php">
      <span class="sm-brand-icon"><i class="bi bi-cpu-fill"></i></span>
      <span class="sm-brand-text">Sys<span>Market</span></span>
    </a>

    <ul class="sm-nav-links">
      <li><a href="/index.php"     class="<?= $current_pg==='index.php'?'active':'' ?>">Inicio</a></li>
      <li><a href="/productos.php" class="<?= $current_pg==='productos.php'?'active':'' ?>">Productos</a></li>
      <li><a href="/feed.php"      class="<?= $current_pg==='feed.php'?'active':'' ?>">
        <i class="bi bi-heart-fill text-danger"></i> Feed Social
      </a></li>
      <?php if ($user && $user['rol']==='admin'): ?>
        <li><a href="/monitor.php" class="<?= $current_pg==='monitor.php'?'active':'' ?>">
          <i class="bi bi-activity"></i> Monitor SO
        </a></li>
        <li><a href="/admin.php" class="<?= $current_pg==='admin.php'?'active':'' ?>">
          <i class="bi bi-gear-fill"></i> Admin
        </a></li>
      <?php endif; ?>
    </ul>

    <div class="sm-nav-actions">
      <?php if ($user): ?>
        <a href="/carrito.php" class="sm-cart-btn" title="Carrito">
          <i class="bi bi-cart3"></i>
          <?php if ($cart_total > 0): ?>
            <span class="sm-cart-badge"><?= $cart_total ?></span>
          <?php endif; ?>
        </a>
        <div class="sm-user-menu">
          <button class="sm-user-btn" type="button" data-bs-toggle="dropdown">
            <span class="sm-avatar"><?= strtoupper(mb_substr($user['nombre'],0,1)) ?></span>
            <span class="sm-user-name"><?= h($user['nombre']) ?></span>
            <i class="bi bi-chevron-down"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end sm-dropdown">
            <li class="px-3 py-2">
              <div class="small text-muted"><?= h($user['email']) ?></div>
              <div class="small"><span class="badge bg-secondary"><?= h($user['rol']) ?></span></div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/mi-cuenta.php"><i class="bi bi-person me-2"></i>Mi cuenta</a></li>
            <li><a class="dropdown-item" href="/carrito.php"><i class="bi bi-cart3 me-2"></i>Carrito</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="/login.php"    class="btn sm-btn-ghost btn-sm">Ingresar</a>
        <a href="/register.php" class="btn sm-btn-primary btn-sm">Crear cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<?php $flash = flashGet(); if ($flash): ?>
  <div class="container-xl mt-3">
    <div class="alert alert-<?= h($flash['type']) ?> sm-flash alert-dismissible fade show">
      <?= h($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php endif; ?>
