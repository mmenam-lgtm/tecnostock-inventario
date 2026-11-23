CREATE DATABASE tecnostock_db;
USE tecnostock_db;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN DEFAULT 1
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    estado BOOLEAN DEFAULT 1
);

CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) UNSIGNED NOT NULL,
    stock_actual INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 5,
    estado BOOLEAN DEFAULT 1,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    observacion TEXT,
    id_producto INT,
    id_usuario INT,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

INSERT INTO usuarios (nombre, correo, clave) VALUES ('Admin', 'admin@tecnostock.cl', '123456');
INSERT INTO categorias (nombre, descripcion) VALUES 
('Cables y Adaptadores', 'Cables USB, HDMI y adaptadores'),
('Periféricos', 'Mouse, teclados y audífonos'),
('Almacenamiento', 'Pendrives y discos externos');