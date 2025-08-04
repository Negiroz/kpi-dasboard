-- Paso 1: Asegurarse de que la empresa de demostración exista.
INSERT IGNORE INTO `empresas` (`id`, `nombre`) VALUES (1, 'Empresa Demo');

-- Paso 2: Asegurarse de que la columna `empresa_id` exista y no permita nulos.
-- Se cambia la columna para que tenga un valor por defecto de 1, que corresponde a 'Empresa Demo'.
ALTER TABLE `agentes` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `closers` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `datos_financieros` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `metas_generales` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `rendimiento_agentes` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `rendimiento_closers` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `rendimiento_sedes` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `sedes` CHANGE `empresa_id` `empresa_id` INT(11) NOT NULL DEFAULT 1;

-- Paso 3: Volver a ejecutar los UPDATE para garantizar que todos los registros existentes tengan la empresa_id correcta.
UPDATE `agentes` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `closers` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `datos_financieros` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `metas_generales` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `rendimiento_agentes` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `rendimiento_closers` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `rendimiento_sedes` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;
UPDATE `sedes` SET `empresa_id` = 1 WHERE `empresa_id` = 0 OR `empresa_id` IS NULL;

-- Paso 4: Intentar añadir las claves foráneas de nuevo.
-- Se añade un nombre único a cada constraint para evitar errores si ya existen con otro nombre.
ALTER TABLE `agentes` ADD CONSTRAINT `fk_agentes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `closers` ADD CONSTRAINT `fk_closers_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `datos_financieros` ADD CONSTRAINT `fk_datos_financieros_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `metas_generales` ADD CONSTRAINT `fk_metas_generales_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_agentes` ADD CONSTRAINT `fk_rendimiento_agentes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_closers` ADD CONSTRAINT `fk_rendimiento_closers_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_sedes` ADD CONSTRAINT `fk_rendimiento_sedes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `sedes` ADD CONSTRAINT `fk_sedes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
