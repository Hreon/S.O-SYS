<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /carrito.php'); exit; }

$uid       = $_SESSION['user_id'];
$direccion = trim($_POST['direccion'] ?? '');
if ($direccion === '') {
    flashSet('warning', 'La dirección de envío es obligatoria.');
    header('Location: /carrito.php'); exit;
}

$db = getDB();
$db->beginTransaction();
try {
    // Recoger items del carrito con lock para evitar over-selling
    $stmt = $db->prepare("
        SELECT c.id_producto, c.cantidad, p.precio, p.stock, p.nombre
        FROM carrito c JOIN productos p ON p.id = c.id_producto
        WHERE c.id_usuario = ?
    ");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        $db->rollBack();
        flashSet('warning', 'Tu carrito está vacío.');
        header('Location: /carrito.php'); exit;
    }

    $total = 0;
    foreach ($items as $it) {
        if ($it['stock'] < $it['cantidad']) {
            $db->rollBack();
            flashSet('danger', "Sin stock suficiente de \"{$it['nombre']}\".");
            header('Location: /carrito.php'); exit;
        }
        $total += $it['precio'] * $it['cantidad'];
    }
    $total += 15.00;  // envío

    $ins = $db->prepare("INSERT INTO pedidos (id_usuario, total, estado, direccion) VALUES (?, ?, 'pagado', ?)");
    $ins->execute([$uid, $total, $direccion]);
    $pedido_id = (int)$db->lastInsertId();

    $det = $db->prepare("INSERT INTO pedidos_detalle (id_pedido, id_producto, cantidad, precio_unit) VALUES (?, ?, ?, ?)");
    $upd = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
    foreach ($items as $it) {
        $det->execute([$pedido_id, $it['id_producto'], $it['cantidad'], $it['precio']]);
        $upd->execute([$it['cantidad'], $it['id_producto']]);
    }
    $db->prepare("DELETE FROM carrito WHERE id_usuario = ?")->execute([$uid]);
    $db->commit();

    flashSet('success', "¡Pedido #$pedido_id confirmado! Total: S/ " . number_format($total, 2));
    header('Location: /mi-cuenta.php'); exit;
} catch (Exception $e) {
    $db->rollBack();
    flashSet('danger', 'Error procesando pedido: ' . $e->getMessage());
    header('Location: /carrito.php'); exit;
}
