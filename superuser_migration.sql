-- Añadir la columna is_superuser a la tabla de usuarios
ALTER TABLE `usuarios` ADD `is_superuser` BOOLEAN NOT NULL DEFAULT FALSE AFTER `password`;

-- Establecer al usuario con ID 1 como superusuario
UPDATE `usuarios` SET `is_superuser` = TRUE WHERE `id` = 1;
