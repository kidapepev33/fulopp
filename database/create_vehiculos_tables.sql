-- Crear tablas para vehiculos y asignacion de rutas por chofer
-- Ejecutar en la base de datos proyecto_fulopp

CREATE TABLE IF NOT EXISTS `vehiculos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `placa` VARCHAR(20) NOT NULL,
    `capacidad` INT UNSIGNED NOT NULL,
    `estado` VARCHAR(40) NOT NULL,
    `codigo_interno` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vehiculos_placa` (`placa`),
    UNIQUE KEY `uq_vehiculos_codigo` (`codigo_interno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `chofer_rutas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chofer_id` INT(255) NOT NULL,
    `ruta_id` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chofer_ruta` (`chofer_id`, `ruta_id`),
    KEY `idx_chofer_ruta_ruta` (`ruta_id`),
    CONSTRAINT `fk_chofer_ruta_chofer` FOREIGN KEY (`chofer_id`) REFERENCES `chofer` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `fk_chofer_ruta_ruta` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
