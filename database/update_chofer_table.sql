-- Actualizar tabla chofer para perfil de gestion
-- Ejecutar en la base de datos proyecto_fulopp

ALTER TABLE `chofer`
    ADD COLUMN `apellidos` VARCHAR(255) NULL AFTER `nombre`,
    ADD COLUMN `rol` VARCHAR(30) NOT NULL DEFAULT 'chofer' AFTER `password`,
    ADD COLUMN `vehiculo_id` INT UNSIGNED NULL AFTER `rol`;

ALTER TABLE `chofer`
    ADD INDEX `idx_chofer_vehiculo` (`vehiculo_id`);

ALTER TABLE `chofer`
    ADD CONSTRAINT `fk_chofer_vehiculo`
        FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`)
        ON DELETE SET NULL
        ON UPDATE RESTRICT;

