<?php
require_once __DIR__ . '/includes/auth.php';
$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = registerUser($_POST['nombre']??'', $_POST['apellido']??'', $_POST['email']??'', $_POST['password']??'');
    if ($r['ok']) { $success = true; } else { $error = $r['msg']; }
}
$page_title = 'Crear cuenta — SysMarket';
include __DIR__ . '/includes/header.php';
?>

<div class="sm-auth-wrap">
  <div class="sm-auth-card sm-fade-in">
    <div class="sm-auth-head">
      <div class="icon"><i class="bi bi-person-plus-fill"></i></div>
      <h3>Crear cuenta</h3>
      <p>Únete a SysMarket en menos de un minuto</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger sm-flash py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success sm-flash py-2 small">
        <i class="bi bi-check-circle me-1"></i>Cuenta creada. <a href="/login.php" class="fw-semibold">Inicia sesión</a>.
      </div>
    <?php else: ?>
    <form method="POST">
      <div class="row g-2">
        <div class="col-6">
          <label class="sm-label">Nombre</label>
          <input type="text" name="nombre" class="sm-input" required>
        </div>
        <div class="col-6">
          <label class="sm-label">Apellido</label>
          <input type="text" name="apellido" class="sm-input" required>
        </div>
      </div>
      <div class="mb-3 mt-3">
        <label class="sm-label">Correo electrónico</label>
        <input type="email" name="email" class="sm-input" required placeholder="tu@email.com">
      </div>
      <div class="mb-4">
        <label class="sm-label">Contraseña</label>
        <input type="password" name="password" class="sm-input" required minlength="6" placeholder="Mínimo 6 caracteres">
      </div>
      <button type="submit" class="btn sm-btn-primary w-100 btn-lg">
        <i class="bi bi-rocket-takeoff me-2"></i>Crear cuenta
      </button>
    </form>
    <?php endif; ?>

    <div class="text-center mt-4 small text-secondary">
      ¿Ya tienes cuenta? <a href="/login.php" class="text-primary fw-semibold">Inicia sesión</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
