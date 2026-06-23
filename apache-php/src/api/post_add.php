<?php
/**
 * SysMarket v2 — Endpoint: crear publicación social (Shop the Post)
 * POST: { titulo, contenido, producto_id (opcional) }
 * Respuesta: { ok, id }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión para publicar']);
    exit;
}

$titulo      = trim((string) ($_POST['titulo'] ?? ''));
$contenido   = trim((string) ($_POST['contenido'] ?? ''));
$producto_id = !empty($_POST['producto_id']) ? (int) $_POST['producto_id'] : null;

if (mb_strlen($titulo) < 5 || mb_strlen($titulo) > 150) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El título debe tener entre 5 y 150 caracteres']);
    exit;
}
if (mb_strlen($contenido) < 10 || mb_strlen($contenido) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El contenido debe tener entre 10 y 2000 caracteres']);
    exit;
}

$usuario_id = (int) $_SESSION['user_id'];
$db = getDB();

try {
    if ($producto_id) {
        $stmt = $db->prepare('SELECT id FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$producto_id]);
        if (!$stmt->fetch()) $producto_id = null;
    }

    $stmt = $db->prepare(
        'INSERT INTO publicaciones (usuario_id, producto_id, titulo, contenido)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuario_id, $producto_id, $titulo, $contenido]);

    echo json_encode([
        'ok' => true,
        'id' => (int) $db->lastInsertId(),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('post_add: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
}
