<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

$pid = (int)($_POST['id_producto'] ?? 0);
if ($pid <= 0) { echo json_encode(['ok'=>false]); exit; }

getDB()->prepare("DELETE FROM carrito WHERE id_usuario = ? AND id_producto = ?")
      ->execute([$_SESSION['user_id'], $pid]);
echo json_encode(['ok'=>true]);
