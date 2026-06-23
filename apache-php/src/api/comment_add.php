<?php
/**
 * SysMarket v2 — Endpoint: agregar comentario/reseña
 * POST: { producto_id, rating (1-5), comentario }
 * Respuesta: { ok, comentario: {...} }
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
    echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión para comentar']);
    exit;
}

$producto_id = (int) ($_POST['producto_id'] ?? 0);
$rating      = (int) ($_POST['rating'] ?? 0);
$comentario  = trim((string) ($_POST['comentario'] ?? ''));

if ($producto_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'producto_id inválido']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'rating debe estar entre 1 y 5']);
    exit;
}
if (mb_strlen($comentario) < 5) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El comentario debe tener al menos 5 caracteres']);
    exit;
}
if (mb_strlen($comentario) > 1000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El comentario excede 1000 caracteres']);
    exit;
}

$usuario_id = (int) $_SESSION['user_id'];
$db = getDB();

try {
    $stmt = $db->prepare('SELECT id FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$producto_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado']);
        exit;
    }

    $stmt = $db->prepare(
        'INSERT INTO comentarios (producto_id, usuario_id, rating, comentario)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$producto_id, $usuario_id, $rating, $comentario]);
    $id = (int) $db->lastInsertId();

    $stmt = $db->prepare(
        'SELECT c.id, c.rating, c.comentario, c.creado_en,
                u.nombre AS user_nombre, u.apellido AS user_apellido
         FROM comentarios c
         JOIN usuarios u ON u.id = c.usuario_id
         WHERE c.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'comentario' => [
            'id'             => (int) $row['id'],
            'rating'         => (int) $row['rating'],
            'comentario'     => htmlspecialchars($row['comentario'], ENT_QUOTES, 'UTF-8'),
            'creado_en'      => $row['creado_en'],
            'user_nombre'    => htmlspecialchars($row['user_nombre'], ENT_QUOTES, 'UTF-8'),
            'user_apellido'  => htmlspecialchars($row['user_apellido'], ENT_QUOTES, 'UTF-8'),
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('comment_add: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
}
