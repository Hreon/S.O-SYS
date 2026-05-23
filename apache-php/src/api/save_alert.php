<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /monitor.php'); exit; }

$id_metrica = (int)($_POST['id_metrica'] ?? 0);
$umbral     = (float)($_POST['umbral'] ?? 0);
$operador   = $_POST['operador'] ?? '>';
if (!in_array($operador, ['>', '<', '='], true) || $id_metrica <= 0) {
    flashSet('danger', 'Datos inválidos.');
    header('Location: /monitor.php'); exit;
}

$db = getDB();
$db->prepare("INSERT INTO alertas (id_usuario, id_metrica, umbral, operador) VALUES (?, ?, ?, ?)")
   ->execute([$_SESSION['user_id'], $id_metrica, $umbral, $operador]);

flashSet('success', "Alerta configurada: disparará cuando la métrica $operador $umbral.");
header('Location: /monitor.php'); exit;
