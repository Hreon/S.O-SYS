<?php
require_once __DIR__ . '/includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($r['ok']) { header('Location: /index.php'); exit; }
    $error = $r['msg'];
}
$page_title = 'Ingresar — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="sm-auth-wrap">
  <div class="sm-auth-card sm-fade-in">
    <div class="sm-auth-head">
      <div class="icon"><i class="bi bi-cpu-fill"></i></div>
      <h3>Bienvenido de vuelta</h3>
      <p>Ingresa a tu cuenta de SysMarket</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger sm-flash py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="sm-label">Correo electrónico</label>
        <input type="email" name="email" class="sm-input" required autofocus
               placeholder="tu@email.com" value="<?= h($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-4">
        <label class="sm-label">Contraseña</label>
        <input type="password" name="password" class="sm-input" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn sm-btn-primary w-100 btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
      </button>
    </form>

    <div class="text-center mt-4 small text-secondary">
      ¿No tienes cuenta? <a href="/register.php" class="text-primary fw-semibold">Crea una gratis</a>
    </div>

    <div class="sm-card sm-card-pad mt-4" style="background:rgba(59,130,246,.08);border-color:rgba(59,130,246,.2);padding:14px">
      <div class="small fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i>Cuentas demo:</div>
      <div class="font-mono small" style="color:var(--sm-text-mute)">
        admin@sysmarket.com / password<br>
        cliente@sysmarket.com / password
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
