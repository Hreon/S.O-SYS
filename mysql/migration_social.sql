-- ============================================================
-- SysMarket v2 — Migración Social Commerce
-- ============================================================
-- Aplica este script UNA SOLA VEZ sobre una BD existente.
-- Agrega: producto_likes, comentarios, publicaciones
-- NO altera tablas existentes ni borra datos.
-- ============================================================

USE sysmarket;

-- ------------------------------------------------------------
-- TABLA: producto_likes
-- Permite que cada usuario marque un producto con "me gusta".
-- Restricción única (usuario_id, producto_id) evita duplicados.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_likes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    producto_id  INT NOT NULL,
    usuario_id   INT NOT NULL,
    creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_product (usuario_id, producto_id),
    KEY idx_producto (producto_id),
    CONSTRAINT fk_likes_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: comentarios (reseñas con estrellas)
-- Cada usuario puede dejar varios comentarios por producto.
-- Rating 1-5 validado por CHECK constraint.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comentarios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    producto_id  INT NOT NULL,
    usuario_id   INT NOT NULL,
    rating       TINYINT NOT NULL DEFAULT 5,
    comentario   TEXT NOT NULL,
    creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_producto_fecha (producto_id, creado_en),
    KEY idx_usuario (usuario_id),
    CONSTRAINT fk_coment_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_coment_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: publicaciones (posts sociales con producto etiquetado)
-- Implementa "Shop the Post" — un post puede mencionar 1 producto.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publicaciones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT NOT NULL,
    producto_id  INT NULL,
    titulo       VARCHAR(150) NOT NULL,
    contenido    TEXT NOT NULL,
    creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fecha (creado_en DESC),
    KEY idx_producto (producto_id),
    CONSTRAINT fk_pub_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pub_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DATOS DEMO (solo para que la vista tenga contenido en la demo)
-- ------------------------------------------------------------

INSERT IGNORE INTO producto_likes (producto_id, usuario_id) VALUES
    (1, 2), (3, 2), (5, 2), (7, 2),
    (1, 1), (2, 1), (3, 1), (5, 1);

INSERT INTO comentarios (producto_id, usuario_id, rating, comentario) VALUES
    (1, 2, 5, 'Excelente procesador, rinde increible en juegos y multitasking. 100% recomendado.'),
    (3, 2, 4, 'Buena tarjeta grafica, pero esperaba mas rendimiento en 4K. Calidad-precio aceptable.'),
    (5, 2, 5, 'La velocidad de esta RAM hace que mi PC vuele. Encaja perfecto en mi placa.'),
    (7, 1, 5, 'SSD NVMe muy rapido, los tiempos de carga del SO bajaron a 8 segundos.');

INSERT INTO publicaciones (usuario_id, producto_id, titulo, contenido) VALUES
    (2, 1, 'Mi nuevo setup gamer',
     'Acabo de armar mi PC con este procesador y la verdad es una bestia. Lo recomiendo a quien busca alto rendimiento sin gastar de mas.'),
    (2, 3, 'Vale la pena esta GPU?',
     'Llevo 2 semanas usandola y para 1440p esta perfecta. Para 4K se queda un poco corta pero es excelente para el precio.'),
    (1, 5, 'Tip: la RAM importa mas de lo que crees',
     'Mucha gente subestima la velocidad de la RAM. Este modelo me bajo los tiempos de compilacion a la mitad.');

-- ------------------------------------------------------------
-- VERIFICACION
-- ------------------------------------------------------------
SELECT 'producto_likes' AS tabla, COUNT(*) AS filas FROM producto_likes
UNION ALL
SELECT 'comentarios', COUNT(*) FROM comentarios
UNION ALL
SELECT 'publicaciones', COUNT(*) FROM publicaciones;
