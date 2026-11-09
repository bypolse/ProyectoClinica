-- ============================================================================
-- Proyecto Académico: Sistema Web de Gestión de Pacientes
-- Institución: IUJO - Algoritmo y Programación II
-- Entidad: Clínica Salud Total C.A.
-- Motor: MySQL / MariaDB (Compatible con phpMyAdmin - XAMPP / WampServer)
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS `clinica_gestion`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `clinica_gestion`;

-- Eliminación previa de tablas en orden inverso por integridad referencial
DROP TABLE IF EXISTS `entidad_gestion`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;

-- ----------------------------------------------------------------------------
-- 1. Estructura de tabla para `roles`
-- ----------------------------------------------------------------------------
CREATE TABLE `roles` (
  `id_rol` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_rol` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Estructura de tabla para `usuarios`
-- ----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rol` INT DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `estado` TINYINT DEFAULT 1,
  CONSTRAINT `fk_usuarios_rol`
    FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Estructura de tabla para `entidad_gestion` (Pacientes de la Clínica)
-- ----------------------------------------------------------------------------
CREATE TABLE `entidad_gestion` (
  `id_registro` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario_responsable` INT DEFAULT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `cedula_identidad` VARCHAR(50) NOT NULL UNIQUE,
  `datos_contacto` VARCHAR(255) DEFAULT NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_paciente_usuario`
    FOREIGN KEY (`id_usuario_responsable`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERCIÓN DE DATOS SEMILLA (SEED DATA)
-- ============================================================================

-- 1. Roles del sistema
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso total al sistema: creación, consulta, edición, eliminación y exportación de pacientes.'),
(2, 'Empleado', 'Acceso de gestión básica: registro, consulta, edición y exportación de pacientes (sin permisos de eliminación).');

-- 2. Usuarios de prueba (Hashes generados con password_hash(..., PASSWORD_BCRYPT) de PHP)
-- Usuario 'admin'    -> contraseña en texto plano: admin123
-- Usuario 'empleado' -> contraseña en texto plano: 123456
INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `username`, `password`, `estado`) VALUES
(1, 1, 'admin', '$2y$10$CCHDx8knwUANl0oy6hLbpOZZVlxQcudTlYH3KFVmWE/0y1k74P1m6', 1),
(2, 2, 'empleado', '$2y$10$tRytzhCwz3y6BbnRxcbpCuegOc7/64E79TgKQc84ovyfDNSAYjv2a', 1);

-- 3. Registros de pacientes de prueba para la Clínica Salud Total C.A.
INSERT INTO `entidad_gestion` (`id_registro`, `id_usuario_responsable`, `nombres`, `apellidos`, `cedula_identidad`, `datos_contacto`, `fecha_registro`) VALUES
(1, 1, 'María Fernanda', 'Gómez Pérez', 'V-18452910', 'Tel: 0414-5551234 | Seguro: Seguros Caracas (Póliza #SC-9841)', '2026-11-01 08:30:00'),
(2, 2, 'Carlos Eduardo', 'Rodríguez Méndez', 'V-24891035', 'Tel: 0412-8904321 | Seguro: Mercantil Seguros (Titular)', '2026-11-03 10:15:00'),
(3, 1, 'Ana Gabriela', 'Rivas Castellanos', 'V-29104782', 'Tel: 0424-3321987 | Seguro: Particular (Sin póliza activa)', '2026-11-06 14:45:00');

COMMIT;

