<?php
/**
 * SysMarket v2 — Endpoint: alternar like en producto
 * POST: { producto_id: int }
 * Respuesta: { ok: bool, liked: bool, total: int }
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
    echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión']);
    exit;
}

$producto_id = (int) ($_POST['producto_id'] ?? 0);
if ($producto_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'producto_id inválido']);
    exit;
}

$usuario_id = (int) $_SESSION['user_id'];
$db = getDB();

try {
    // Verificar que el producto existe
    $stmt = $db->prepare('SELECT id FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$producto_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado']);
        exit;
    }

    // Toggle dentro de transacción para evitar race conditions
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT id FROM producto_likes WHERE usuario_id = ? AND producto_id = ? LIMIT 1'
    );
    $stmt->execute([$usuario_id, $producto_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $db->prepare('DELETE FROM producto_likes WHERE id = ?')->execute([$existing['id']]);
        $liked = false;
    } else {
        $db->prepare(
            'INSERT INTO producto_likes (producto_id, usuario_id) VALUES (?, ?)'
        )->execute([$producto_id, $usuario_id]);
        $liked = true;
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM producto_likes WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    $total = (int) $stmt->fetchColumn();

    $db->commit();

    echo json_encode([
        'ok'    => true,
        'liked' => $liked,
        'total' => $total,
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    error_log('like_toggle: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
}
