-- =============================================================
-- SysMarket v2 — Tienda Tech + Monitor de Recursos del SO
-- Universidad San Ignacio de Loyola — Sistemas Operativos 2026
-- =============================================================
SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- =============================================================
-- BLOQUE A — USUARIOS Y SEGURIDAD
-- =============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    apellido    VARCHAR(80)  NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rol         ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    activo      TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- BLOQUE B — E-COMMERCE (TIENDA REAL DE PIEZAS DE PC)
-- =============================================================
CREATE TABLE IF NOT EXISTS categorias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL,
    slug        VARCHAR(60) NOT NULL UNIQUE,
    icono       VARCHAR(40) DEFAULT 'bi-box'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria  INT NOT NULL,
    nombre        VARCHAR(150) NOT NULL,
    marca         VARCHAR(60),
    descripcion   TEXT,
    precio        DECIMAL(10,2) NOT NULL,
    stock         INT DEFAULT 0,
    imagen        VARCHAR(255) DEFAULT 'placeholder.png',
    destacado     TINYINT(1) DEFAULT 0,
    activo        TINYINT(1) DEFAULT 1,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carrito (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT NOT NULL,
    id_producto  INT NOT NULL,
    cantidad     INT NOT NULL DEFAULT 1,
    added_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usr_prod (id_usuario, id_producto),
    FOREIGN KEY (id_usuario)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT NOT NULL,
    total        DECIMAL(10,2) NOT NULL,
    estado       ENUM('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente',
    direccion    VARCHAR(255),
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos_detalle (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido    INT NOT NULL,
    id_producto  INT NOT NULL,
    cantidad     INT NOT NULL,
    precio_unit  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pedido)   REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- BLOQUE C — MONITOREO DE RECURSOS DEL SISTEMA OPERATIVO
-- (cumple consigna del curso: gestión de procesos, memoria, etc)
-- =============================================================
CREATE TABLE IF NOT EXISTS metricas_catalogo (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60)  NOT NULL,
    descripcion TEXT,
    icono       VARCHAR(50)  DEFAULT 'bi-cpu',
    unidad      VARCHAR(20)  DEFAULT '%',
    activa      TINYINT(1)   DEFAULT 1,
    orden       INT          DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lecturas (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_metrica  INT NOT NULL,
    valor       FLOAT NOT NULL,
    extra_json  TEXT,
    timestamp   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metrica_time (id_metrica, timestamp),
    FOREIGN KEY (id_metrica) REFERENCES metricas_catalogo(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alertas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT NOT NULL,
    id_metrica  INT NOT NULL,
    umbral      FLOAT NOT NULL,
    operador    ENUM('>','<','=') NOT NULL DEFAULT '>',
    activa      TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_metrica) REFERENCES metricas_catalogo(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_alerta       INT NOT NULL,
    valor_detectado FLOAT NOT NULL,
    leida           TINYINT(1) DEFAULT 0,
    fired_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alerta) REFERENCES alertas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- DATOS INICIALES
-- =============================================================

-- Usuarios demo. Password de ambos: "password"
INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES
('Administrador','SysMarket','admin@sysmarket.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin'),
('Cliente','Demo','cliente@sysmarket.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cliente');

-- Categorías de la tienda
INSERT INTO categorias (nombre, slug, icono) VALUES
('Procesadores',  'cpu',        'bi-cpu'),
('Tarjetas Gráficas','gpu',     'bi-gpu-card'),
('Memorias RAM',  'ram',        'bi-memory'),
('Almacenamiento','storage',    'bi-hdd'),
('Placas Madre',  'motherboard','bi-motherboard'),
('Periféricos',   'peripherals','bi-mouse');

-- Productos demo (24 items)
INSERT INTO productos (id_categoria, nombre, marca, descripcion, precio, stock, destacado) VALUES
(1, 'Intel Core i5-13600K', 'Intel', 'Procesador 14 núcleos (6P+8E), 20 hilos, hasta 5.1 GHz. Socket LGA1700.', 1199.00, 18, 1),
(1, 'Intel Core i7-14700K', 'Intel', 'Procesador 20 núcleos, 28 hilos, hasta 5.6 GHz. Para gaming y workstation.', 1799.00, 9, 1),
(1, 'AMD Ryzen 5 7600X',    'AMD',   'Procesador 6 núcleos, 12 hilos, hasta 5.3 GHz. Socket AM5.',                999.00,  22, 0),
(1, 'AMD Ryzen 7 7800X3D',  'AMD',   'Procesador 8 núcleos, 16 hilos, 3D V-Cache. El rey del gaming.',           1899.00, 6,  1),
(2, 'NVIDIA RTX 4060 Ti',   'MSI',   'GPU 8GB GDDR6, DLSS 3, Ray Tracing. Ventus 2X OC.',                        1850.00, 12, 1),
(2, 'NVIDIA RTX 4070 SUPER','ASUS',  'GPU 12GB GDDR6X, TUF Gaming, triple fan.',                                 2899.00, 7,  1),
(2, 'AMD Radeon RX 7800 XT','Sapphire','GPU 16GB GDDR6, Pulse, refrigeración dual.',                              2399.00, 10, 0),
(2, 'NVIDIA RTX 4090',      'Gigabyte','GPU 24GB GDDR6X, AORUS Master, top de gama.',                            8999.00, 3,  1),
(3, 'Corsair Vengeance 16GB DDR5','Corsair','Kit 2x8GB DDR5-5600 CL36, RGB.',                                    349.00, 30, 0),
(3, 'Kingston Fury Beast 32GB DDR5','Kingston','Kit 2x16GB DDR5-6000 CL36.',                                     589.00, 25, 1),
(3, 'G.Skill Trident Z5 RGB 32GB','G.Skill','Kit 2x16GB DDR5-6400 CL32 RGB.',                                    749.00, 14, 0),
(3, 'Crucial Pro 16GB DDR4','Crucial','Kit 2x8GB DDR4-3200 CL22.',                                                189.00, 40, 0),
(4, 'Samsung 990 PRO 1TB',  'Samsung','SSD NVMe Gen4, 7450 MB/s lectura. Para gaming.',                          499.00, 28, 1),
(4, 'WD Black SN850X 2TB',  'WD',     'SSD NVMe Gen4, 7300 MB/s. PS5 compatible.',                               799.00, 11, 0),
(4, 'Seagate Barracuda 4TB','Seagate','HDD 4TB 5400rpm, ideal almacenamiento masivo.',                            349.00, 35, 0),
(4, 'Kingston KC3000 500GB','Kingston','SSD NVMe Gen4, 7000 MB/s.',                                               289.00, 20, 0),
(5, 'ASUS ROG STRIX B650-E','ASUS',   'Placa AM5, DDR5, WiFi 6E, PCIe 5.0.',                                    1199.00, 8,  1),
(5, 'MSI MAG B760 Tomahawk','MSI',    'Placa LGA1700, DDR5, WiFi 6.',                                            899.00, 12, 0),
(5, 'Gigabyte Z790 AORUS Elite','Gigabyte','Placa LGA1700, DDR5, PCIe 5.0.',                                    1399.00, 5,  0),
(6, 'Logitech G Pro X Superlight 2','Logitech','Mouse gaming inalámbrico, 95g, HERO 2 sensor.',                  599.00, 25, 1),
(6, 'Keychron Q1 Pro',      'Keychron','Teclado mecánico 75%, hot-swap, QMK/VIA.',                                749.00, 14, 0),
(6, 'Logitech MX Master 3S','Logitech','Mouse productividad, scroll MagSpeed, multidispositivo.',                429.00, 30, 0),
(6, 'HyperX Cloud III',     'HyperX','Auriculares gaming, drivers 53mm, mic certificado Discord.',              399.00, 22, 0),
(6, 'Razer BlackWidow V4 Pro','Razer','Teclado mecánico full-size, switches verdes, RGB Chroma.',                 949.00, 9,  0);

-- Catálogo de métricas del SO (para la sección admin)
INSERT INTO metricas_catalogo (nombre, descripcion, icono, unidad, orden) VALUES
('CPU',      'Uso del procesador del servidor que aloja la tienda.',                'bi-cpu',          '%',    1),
('RAM',      'Memoria RAM utilizada por el sistema operativo.',                     'bi-memory',       '%',    2),
('Disco',    'Espacio en disco ocupado.',                                           'bi-hdd',          '%',    3),
('Procesos', 'Número de procesos activos en el servidor.',                          'bi-diagram-3',    'unid', 4),
('Carga',    'Promedio de carga (load average) del sistema.',                        'bi-activity',     'avg',  5),
('Swap',     'Memoria swap utilizada — indica presión sobre la RAM física.',         'bi-arrow-repeat', '%',    6);
