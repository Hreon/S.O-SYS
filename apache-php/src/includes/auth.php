<?php
/**
 * Autenticación, registro, gestión de sesiones y guards de acceso.
 * Seguridad: password_hash bcrypt, prepared statements, validación de input.
 */
require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'nombre'   => $_SESSION['user_nombre'] ?? '',
        'apellido' => $_SESSION['user_apellido'] ?? '',
        'email'    => $_SESSION['user_email'] ?? '',
        'rol'      => $_SESSION['user_rol'] ?? 'cliente',
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_rol'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Acceso denegado: se requiere rol administrador.";
        exit;
    }
}

function loginUser(string $email, string $password): array {
    startSession();
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'msg' => 'Email inválido.'];
    }
    $stmt = getDB()->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['password'])) {
        return ['ok' => false, 'msg' => 'Credenciales incorrectas.'];
    }
    $_SESSION['user_id']       = (int)$u['id'];
    $_SESSION['user_nombre']   = $u['nombre'];
    $_SESSION['user_apellido'] = $u['apellido'];
    $_SESSION['user_email']    = $u['email'];
    $_SESSION['user_rol']      = $u['rol'];
    return ['ok' => true];
}

function registerUser(string $nombre, string $apellido, string $email, string $password): array {
    $nombre   = trim($nombre);
    $apellido = trim($apellido);
    $email    = trim($email);

    if ($nombre === '' || $apellido === '')        return ['ok' => false, 'msg' => 'Nombre y apellido son obligatorios.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'msg' => 'Email inválido.'];
    if (strlen($password) < 6)                       return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.'];

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) return ['ok' => false, 'msg' => 'Ya existe una cuenta con ese email.'];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES (?, ?, ?, ?, 'cliente')");
    $ins->execute([$nombre, $apellido, $email, $hash]);
    return ['ok' => true];
}

/** Cuenta items en el carrito del usuario actual (badge del navbar). */
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $stmt = getDB()->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

/** Escapa output HTML — atajo. */
function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Devuelve y consume un mensaje flash (pasaje entre redirects). */
function flashGet(): ?array {
    startSession();
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function flashSet(string $type, string $msg): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
