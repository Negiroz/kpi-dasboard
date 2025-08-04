-- 1. Crear tablas de empresa y la relación con usuarios.
-- Se usa IF NOT EXISTS para evitar errores si ya se ejecutó parte del script anterior.
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `usuario_empresa` (
  `usuario_id` INT(11) NOT NULL,
  `empresa_id` INT(11) NOT NULL,
  PRIMARY KEY (`usuario_id`, `empresa_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Insertar la empresa y usuario por defecto.
-- Se usa INSERT IGNORE para que no falle si ya existen.
INSERT IGNORE INTO `empresas` (`id`, `nombre`) VALUES (1, 'Empresa Demo');
INSERT IGNORE INTO `usuario_empresa` (`usuario_id`, `empresa_id`) VALUES (1, 1);

-- 3. Añadir la columna empresa_id a cada tabla si no existe.
-- NOTA: Si alguna de estas líneas da un error de 'Duplicate column', puedes ignorarlo.
ALTER TABLE `agentes` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `closers` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `datos_financieros` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `metas_generales` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `rendimiento_agentes` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `rendimiento_closers` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `rendimiento_sedes` ADD COLUMN `empresa_id` INT(11);
ALTER TABLE `sedes` ADD COLUMN `empresa_id` INT(11);

-- 4. Actualizar TODOS los registros existentes para asignarles la empresa_id por defecto.
-- Esto es CRUCIAL para que las claves foráneas se puedan crear.
UPDATE `agentes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `closers` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `datos_financieros` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `metas_generales` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `rendimiento_agentes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `rendimiento_closers` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `rendimiento_sedes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;
UPDATE `sedes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL;

-- 5. Ahora sí, crear las claves foráneas.
-- Esto debería funcionar porque todos los registros ya tienen un empresa_id válido.
ALTER TABLE `agentes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `closers` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `datos_financieros` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `metas_generales` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_agentes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_closers` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_sedes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `sedes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
