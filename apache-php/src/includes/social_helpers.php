<?php
/**
 * SysMarket v2 — Funciones helper para Social Commerce
 * Adaptado al esquema real del proyecto (usa getDB() y tablas existentes).
 */

require_once __DIR__ . '/db.php';

function likesCount(int $producto_id): int
{
    $stmt = getDB()->prepare('SELECT COUNT(*) AS total FROM producto_likes WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    return (int) $stmt->fetch()['total'];
}

function userLiked(int $producto_id, int $usuario_id): bool
{
    $stmt = getDB()->prepare(
        'SELECT 1 FROM producto_likes WHERE producto_id = ? AND usuario_id = ? LIMIT 1'
    );
    $stmt->execute([$producto_id, $usuario_id]);
    return (bool) $stmt->fetch();
}

function getComments(int $producto_id, int $limit = 20): array
{
    $sql = 'SELECT c.id, c.rating, c.comentario, c.creado_en,
                   u.nombre AS user_nombre, u.apellido AS user_apellido
            FROM comentarios c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.producto_id = ?
            ORDER BY c.creado_en DESC
            LIMIT ' . (int) $limit;
    $stmt = getDB()->prepare($sql);
    $stmt->execute([$producto_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function avgRating(int $producto_id): float
{
    $stmt = getDB()->prepare(
        'SELECT AVG(rating) AS promedio, COUNT(*) AS total
         FROM comentarios WHERE producto_id = ?'
    );
    $stmt->execute([$producto_id]);
    $row = $stmt->fetch();
    return $row['total'] > 0 ? round((float) $row['promedio'], 1) : 0.0;
}

function topLikedProducts(int $limit = 6): array
{
    $sql = 'SELECT p.id, p.nombre, p.precio, p.imagen, p.stock, p.marca,
                   c.icono AS cat_icono, c.nombre AS cat_nombre, c.slug AS cat_slug,
                   COUNT(pl.id) AS total_likes
            FROM productos p
            LEFT JOIN producto_likes pl ON pl.producto_id = p.id
            JOIN categorias c ON c.id = p.id_categoria
            WHERE p.activo = 1
            GROUP BY p.id
            HAVING total_likes > 0
            ORDER BY total_likes DESC, p.id ASC
            LIMIT ' . (int) $limit;
    return getDB()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function recentPosts(int $limit = 20): array
{
    $sql = 'SELECT pu.id, pu.titulo, pu.contenido, pu.creado_en,
                   u.nombre AS user_nombre, u.apellido AS user_apellido,
                   p.id AS producto_id, p.nombre AS producto_nombre,
                   p.precio AS producto_precio, p.marca AS producto_marca,
                   c.icono AS producto_icono, c.slug AS producto_cat_slug
            FROM publicaciones pu
            JOIN usuarios u ON u.id = pu.usuario_id
            LEFT JOIN productos p ON p.id = pu.producto_id
            LEFT JOIN categorias c ON c.id = p.id_categoria
            ORDER BY pu.creado_en DESC
            LIMIT ' . (int) $limit;
    return getDB()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function socialKpis(): array
{
    $db = getDB();
    return [
        'total_likes'         => (int) $db->query('SELECT COUNT(*) FROM producto_likes')->fetchColumn(),
        'total_comentarios'   => (int) $db->query('SELECT COUNT(*) FROM comentarios')->fetchColumn(),
        'total_publicaciones' => (int) $db->query('SELECT COUNT(*) FROM publicaciones')->fetchColumn(),
        'rating_promedio'     => (float) $db->query('SELECT COALESCE(AVG(rating),0) FROM comentarios')->fetchColumn(),
    ];
}

function renderStars(float $rating, int $max = 5): string
{
    $full  = (int) floor($rating);
    $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = $max - $full - $half;
    $html  = '<span class="sm-stars">';
    for ($i = 0; $i < $full; $i++)  $html .= '<i class="bi bi-star-fill"></i>';
    if ($half)                       $html .= '<i class="bi bi-star-half"></i>';
    for ($i = 0; $i < $empty; $i++)  $html .= '<i class="bi bi-star"></i>';
    $html .= '</span>';
    return $html;
}
