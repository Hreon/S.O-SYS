<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'No autenticado']); exit; }

$pid = (int)($_POST['id_producto'] ?? 0);
$qty = max(1, (int)($_POST['cantidad'] ?? 1));
if ($pid <= 0) { echo json_encode(['ok'=>false,'msg'=>'Producto inválido']); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT stock FROM productos WHERE id = ? AND activo = 1");
$stmt->execute([$pid]);
$prod = $stmt->fetch();
if (!$prod) { echo json_encode(['ok'=>false,'msg'=>'Producto no existe']); exit; }
if ($prod['stock'] < $qty) { echo json_encode(['ok'=>false,'msg'=>'Stock insuficiente']); exit; }

$uid = $_SESSION['user_id'];
// UPSERT: si ya hay, sumar; si no, insertar
$db->prepare("
    INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE cantidad = LEAST(cantidad + VALUES(cantidad), ?)
")->execute([$uid, $pid, $qty, $prod['stock']]);

echo json_encode(['ok'=>true, 'cart_count'=>cartCount()]);
