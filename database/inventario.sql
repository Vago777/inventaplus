-- ============================================================
-- Proyecto   : InventaPlus - Sistema de Gestión de Equipos
-- Evidencia  : GA7-220501096-AA2-EV01
-- Descripción: Script de creación de la base de datos e
--              inserción de datos iniciales (MySQL / MariaDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventario
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventario;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- Almacena los aprendices / funcionarios del SENA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificacion  VARCHAR(20)  NOT NULL UNIQUE,
    nombre          VARCHAR(100) NOT NULL,
    correo_sena     VARCHAR(100) NOT NULL UNIQUE,
    telefono        VARCHAR(15)  NOT NULL,
    area            VARCHAR(60)  NOT NULL
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Tabla: administradores
-- Credenciales de acceso al panel (relacionadas con un usuario)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS administradores (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    estado     ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_admin_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Tabla: equipos
-- Inventario de equipos de cómputo (mesa y portátiles)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS equipos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_equipo       ENUM('equipo_mesa', 'portatil') NOT NULL,
    serial            VARCHAR(50) NOT NULL UNIQUE,
    placa             VARCHAR(20) NOT NULL UNIQUE,
    tiene_mouse       TINYINT(1)  NOT NULL DEFAULT 0,
    tiene_teclado     TINYINT(1)  NOT NULL DEFAULT 0,
    tiene_cargador    TINYINT(1)  NOT NULL DEFAULT 0,
    tiene_rj45_tipo_c TINYINT(1)  NOT NULL DEFAULT 0,
    estado            ENUM('disponible', 'asignado', 'mantenimiento')
                      NOT NULL DEFAULT 'disponible'
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Tabla: asignaciones
-- Registro de entrega y devolución de equipos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asignaciones (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id        INT UNSIGNED NOT NULL,
    equipo_id         INT UNSIGNED NOT NULL,
    admin_id          INT UNSIGNED NOT NULL COMMENT 'Administrador que realiza la asignación',
    estado_asignacion ENUM('activa', 'devuelta') NOT NULL DEFAULT 'activa',
    fecha_asignacion  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion  DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_asig_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_asig_equipo
        FOREIGN KEY (equipo_id)  REFERENCES equipos (id),
    CONSTRAINT fk_asig_admin
        FOREIGN KEY (admin_id)   REFERENCES usuarios (id)
) ENGINE = InnoDB;

-- ============================================================
-- Datos iniciales
-- ============================================================

-- Usuario administrador del sistema
INSERT INTO usuarios (identificacion, nombre, correo_sena, telefono, area)
VALUES ('1090880011', 'Administrador del Sistema', 'admin@sena.edu.co', '3000000000', 'Ambientes de Formación');

-- Usuario administrador. La contraseña inicial se define en config_local.php
-- o debe cambiarse inmediatamente despues de la primera instalacion.
INSERT INTO administradores (usuario_id, username, password, estado)
VALUES (1, 'admin', '$2y$10$d8eeh6nEr9fsQdqrcru2Ke8BuGgkaD4hg/n/L1/KXt/MII/P1.7pS', 'activo');

-- Usuarios de prueba
INSERT INTO usuarios (identificacion, nombre, correo_sena, telefono, area) VALUES
('1020304050', 'Ana María Gómez',   'anagomez@sena.edu.co',  '3111111111', 'Software'),
('1098765432', 'Carlos Andrés Ruiz','caruiz@sena.edu.co',    '3222222222', 'Redes'),
('53123456',   'Laura Sofía Pérez', 'lperez@sena.edu.co',    '3333333333', 'Electrónica');

-- Equipos de prueba
INSERT INTO equipos (tipo_equipo, serial, placa, tiene_mouse, tiene_teclado, tiene_cargador, tiene_rj45_tipo_c, estado) VALUES
('equipo_mesa', 'SN-DT-2024-001', 'PL-8001', 1, 1, 0, 1, 'disponible'),
('portatil',    'SN-LT-2024-002', 'PL-8002', 0, 0, 1, 0, 'disponible'),
('portatil',    'SN-LT-2024-003', 'PL-8003', 0, 0, 1, 1, 'disponible'),
('equipo_mesa', 'SN-DT-2024-004', 'PL-8004', 1, 1, 0, 0, 'mantenimiento');
