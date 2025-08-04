-- Crear tabla para las empresas
CREATE TABLE `empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar una empresa de ejemplo
INSERT INTO `empresas` (`nombre`) VALUES ('Empresa Demo');

-- Crear tabla para relacionar usuarios y empresas
CREATE TABLE `usuario_empresa` (
  `usuario_id` INT(11) NOT NULL,
  `empresa_id` INT(11) NOT NULL,
  PRIMARY KEY (`usuario_id`, `empresa_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Asociar el usuario admin a la empresa de ejemplo
INSERT INTO `usuario_empresa` (`usuario_id`, `empresa_id`) VALUES (1, 1);

-- Modificar tablas existentes para añadir empresa_id y asignar un valor por defecto
ALTER TABLE `agentes` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `sede_id`;
ALTER TABLE `closers` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `nombre`;
ALTER TABLE `datos_financieros` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `anio`;
ALTER TABLE `metas_generales` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `anio`;
ALTER TABLE `rendimiento_agentes` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `anio`;
ALTER TABLE `rendimiento_closers` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `anio`;
ALTER TABLE `rendimiento_sedes` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `anio`;
ALTER TABLE `sedes` ADD `empresa_id` INT(11) NOT NULL DEFAULT 1 AFTER `nombre`;

-- Añadir claves foráneas para empresa_id
ALTER TABLE `agentes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `closers` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `datos_financieros` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `metas_generales` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_agentes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_closers` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `rendimiento_sedes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `sedes` ADD FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;