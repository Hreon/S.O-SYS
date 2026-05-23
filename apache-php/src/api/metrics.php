<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn() || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado']);
    exit;
}

$db = getDB();

// Métricas más recientes por tipo
$rows = $db->query("
    SELECT mc.id, mc.nombre, mc.unidad, l.valor, l.extra_json, l.timestamp
    FROM metricas_catalogo mc
    LEFT JOIN lecturas l ON l.id = (
        SELECT id FROM lecturas WHERE id_metrica = mc.id ORDER BY id DESC LIMIT 1
    )
    WHERE mc.activa = 1
    ORDER BY mc.orden
")->fetchAll();

// Histórico CPU últimos 30 puntos
$cpu_hist = $db->query("
    SELECT valor, timestamp FROM lecturas
    WHERE id_metrica = 1
    ORDER BY id DESC LIMIT 30
")->fetchAll();
$cpu_hist = array_reverse($cpu_hist);

// Lista de procesos (extra_json del último 'Procesos')
$proc_row = $db->query("
    SELECT extra_json FROM lecturas WHERE id_metrica = 4 ORDER BY id DESC LIMIT 1
")->fetch();
$procs = $proc_row && $proc_row['extra_json'] ? json_decode($proc_row['extra_json'], true) : [];

// Notificaciones no leídas
$notif_count = (int)$db->query("SELECT COUNT(*) FROM notificaciones WHERE leida=0")->fetchColumn();

echo json_encode([
    'ok'           => true,
    'server_time'  => date('H:i:s'),
    'metrics'      => $rows,
    'cpu_history'  => $cpu_hist,
    'processes'    => $procs,
    'notif_count'  => $notif_count,
]);
