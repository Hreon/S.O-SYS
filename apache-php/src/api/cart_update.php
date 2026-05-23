<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

$pid   = (int)($_POST['id_producto'] ?? 0);
$delta = (int)($_POST['delta'] ?? 0);
if ($pid <= 0 || $delta === 0) { echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']); exit; }

$uid = $_SESSION['user_id'];
$db  = getDB();
$stmt = $db->prepare("
    SELECT c.cantidad, p.stock FROM carrito c JOIN productos p ON p.id=c.id_producto
    WHERE c.id_usuario = ? AND c.id_producto = ?
");
$stmt->execute([$uid, $pid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['ok'=>false,'msg'=>'No está en el carrito']); exit; }

$nueva = max(0, min((int)$row['stock'], (int)$row['cantidad'] + $delta));
if ($nueva === 0) {
    $db->prepare("DELETE FROM carrito WHERE id_usuario=? AND id_producto=?")->execute([$uid, $pid]);
} else {
    $db->prepare("UPDATE carrito SET cantidad=? WHERE id_usuario=? AND id_producto=?")
       ->execute([$nueva, $uid, $pid]);
}
echo json_encode(['ok'=>true]);
