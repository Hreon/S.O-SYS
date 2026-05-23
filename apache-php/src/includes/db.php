<?php
/**
 * Capa de acceso a datos — PDO con prepared statements (anti-SQLi).
 * Singleton: una sola conexión por request.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('DB_HOST') ?: 'db';
    $name = getenv('DB_NAME') ?: 'sysmarket';
    $user = getenv('DB_USER') ?: 'sysuser';
    $pass = getenv('DB_PASS') ?: 'SysMarket2026!';

    $dsn  = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Reintentos por si MySQL aún no arrancó en el contenedor
    for ($i = 0; $i < 10; $i++) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $opts);
            return $pdo;
        } catch (PDOException $e) {
            if ($i === 9) throw $e;
            sleep(2);
        }
    }
    return $pdo;
}
