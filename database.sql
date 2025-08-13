--
-- Base de datos: `kpi_dashboard`
--
CREATE DATABASE IF NOT EXISTS `kpi_dashboard` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `kpi_dashboard`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agentes`
--

CREATE TABLE `agentes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `sede_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `agentes`
--

INSERT INTO `agentes` (`id`, `nombre`, `sede_id`) VALUES
(1, 'Carlos Pérez', 1),
(2, 'Ana Gómez', 2),
(3, 'Luis Rodríguez', 1),
(4, 'María Hernández', 3),
(5, 'José García', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `closers`
--

CREATE TABLE `closers` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `closers`
--

INSERT INTO `closers` (`id`, `nombre`) VALUES
(1, 'Ricardo Dávila'),
(2, 'Verónica Rivas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_financieros`
--

CREATE TABLE `datos_financieros` (
  `id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `facturacion_cobrada` decimal(15,2) DEFAULT 0.00,
  `facturas_cobradas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `datos_financieros`
--

INSERT INTO `datos_financieros` (`id`, `mes`, `anio`, `facturacion_cobrada`, `facturas_cobradas`) VALUES
(1, 2, 2025, 152432.00, 1520),
(2, 3, 2025, 161543.00, 1610),
(3, 4, 2025, 172341.00, 1720),
(4, 5, 2025, 180321.00, 1800),
(5, 6, 2025, 185432.00, 1850),
(6, 7, 2025, 198765.00, 1980);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas_generales`
--

CREATE TABLE `metas_generales` (
  `id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `meta_instalaciones` int(11) DEFAULT 0,
  `meta_clientes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metas_generales`
--

INSERT INTO `metas_generales` (`id`, `mes`, `anio`, `meta_instalaciones`, `meta_clientes`) VALUES
(1, 7, 2025, 400, 10000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rendimiento_agentes`
--

CREATE TABLE `rendimiento_agentes` (
  `id` int(11) NOT NULL,
  `agente_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `cierres` int(11) DEFAULT 0,
  `prospectos` int(11) DEFAULT 0,
  `meta_cierres` int(11) DEFAULT 0,
  `meta_prospectos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rendimiento_agentes`
--

INSERT INTO `rendimiento_agentes` (`id`, `agente_id`, `mes`, `anio`, `cierres`, `prospectos`, `meta_cierres`, `meta_prospectos`) VALUES
(1, 1, 2, 2025, 10, 50, 0, 0),
(2, 1, 3, 2025, 12, 55, 0, 0),
(3, 1, 4, 2025, 11, 52, 0, 0),
(4, 1, 5, 2025, 13, 58, 0, 0),
(5, 1, 6, 2025, 14, 60, 0, 0),
(6, 1, 7, 2025, 12, 61, 15, 60),
(7, 2, 2, 2025, 12, 55, 0, 0),
(8, 2, 3, 2025, 11, 58, 0, 0),
(9, 2, 4, 2025, 13, 60, 0, 0),
(10, 2, 5, 2025, 14, 62, 0, 0),
(11, 2, 6, 2025, 15, 65, 0, 0),
(12, 2, 7, 2025, 16, 68, 15, 60),
(13, 3, 7, 2025, 10, 53, 12, 50),
(14, 4, 7, 2025, 18, 70, 18, 65),
(15, 5, 7, 2025, 11, 54, 12, 55);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rendimiento_closers`
--

CREATE TABLE `rendimiento_closers` (
  `id` int(11) NOT NULL,
  `closer_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `cierres` int(11) DEFAULT 0,
  `meta_cierres` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rendimiento_closers`
--

INSERT INTO `rendimiento_closers` (`id`, `closer_id`, `mes`, `anio`, `cierres`, `meta_cierres`) VALUES
(1, 1, 2, 2025, 25, 0),
(2, 1, 3, 2025, 28, 0),
(3, 1, 4, 2025, 26, 0),
(4, 1, 5, 2025, 30, 0),
(5, 1, 6, 2025, 32, 0),
(6, 1, 7, 2025, 31, 30),
(7, 2, 2, 2025, 28, 0),
(8, 2, 3, 2025, 30, 0),
(9, 2, 4, 2025, 32, 0),
(10, 2, 5, 2025, 31, 0),
(11, 2, 6, 2025, 34, 0),
(12, 2, 7, 2025, 35, 32);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rendimiento_sedes`
--

CREATE TABLE `rendimiento_sedes` (
  `id` int(11) NOT NULL,
  `sede_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `instalaciones` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rendimiento_sedes`
--

INSERT INTO `rendimiento_sedes` (`id`, `sede_id`, `mes`, `anio`, `instalaciones`) VALUES
(1, 1, 2, 2025, 20),
(2, 1, 3, 2025, 22),
(3, 1, 4, 2025, 25),
(4, 1, 5, 2025, 24),
(5, 1, 6, 2025, 28),
(6, 1, 7, 2025, 30),
(7, 2, 2, 2025, 35),
(8, 2, 3, 2025, 38),
(9, 2, 4, 2025, 40),
(10, 2, 5, 2025, 42),
(11, 2, 6, 2025, 45),
(12, 2, 7, 2025, 48),
(13, 3, 7, 2025, 36),
(14, 4, 7, 2025, 31),
(15, 5, 7, 2025, 26),
(16, 6, 7, 2025, 23);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`) VALUES
(1, 'Sede Zulia'),
(2, 'Sede Capital'),
(3, 'Sede Carabobo'),
(4, 'Sede Aragua'),
(5, 'Sede Lara'),
(6, 'Sede Anzoátegui');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agentes`
--
ALTER TABLE `agentes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `closers`
--
ALTER TABLE `closers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `datos_financieros`
--
ALTER TABLE `datos_financieros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mes_anio` (`mes`,`anio`);

--
-- Indices de la tabla `metas_generales`
--
ALTER TABLE `metas_generales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mes_anio` (`mes`,`anio`);

--
-- Indices de la tabla `rendimiento_agentes`
--
ALTER TABLE `rendimiento_agentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agente_mes_anio` (`agente_id`,`mes`,`anio`),
  ADD KEY `agente_id` (`agente_id`);

--
-- Indices de la tabla `rendimiento_closers`
--
ALTER TABLE `rendimiento_closers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `closer_mes_anio` (`closer_id`,`mes`,`anio`),
  ADD KEY `closer_id` (`closer_id`);

--
-- Indices de la tabla `rendimiento_sedes`
--
ALTER TABLE `rendimiento_sedes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sede_mes_anio` (`sede_id`,`mes`,`anio`),
  ADD KEY `sede_id` (`sede_id`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agentes`
--
ALTER TABLE `agentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `closers`
--
ALTER TABLE `closers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `datos_financieros`
--
ALTER TABLE `datos_financieros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `metas_generales`
--
ALTER TABLE `metas_generales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `rendimiento_agentes`
--
ALTER TABLE `rendimiento_agentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `rendimiento_closers`
--
ALTER TABLE `rendimiento_closers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `rendimiento_sedes`
--
ALTER TABLE `rendimiento_sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `rendimiento_agentes`
--
ALTER TABLE `rendimiento_agentes`
  ADD CONSTRAINT `rendimiento_agentes_ibfk_1` FOREIGN KEY (`agente_id`) REFERENCES `agentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rendimiento_closers`
--
ALTER TABLE `rendimiento_closers`
  ADD CONSTRAINT `rendimiento_closers_ibfk_1` FOREIGN KEY (`closer_id`) REFERENCES `closers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rendimiento_sedes`
--
ALTER TABLE `rendimiento_sedes`
  ADD CONSTRAINT `rendimiento_sedes_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

INSERT INTO `usuarios` (`username`, `password`) VALUES ('admin', '$2y$10$6k/BoQ/wXrEfzJC.eogg/uuyyx.o1kFhNbXoeBTM0adyUXm4oVEMq');