-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-02-2026 a las 00:25:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `taco_loco_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedidos`
--

CREATE TABLE `detalle_pedidos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedidos`
--

INSERT INTO `detalle_pedidos` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1, 18.00),
(2, 1, 2, 1, 18.00),
(3, 1, 4, 1, 22.00),
(4, 2, 1, 1, 18.00),
(5, 2, 2, 1, 18.00),
(6, 2, 3, 1, 45.00),
(8, 3, 4, 2, 22.00),
(9, 3, 8, 1, 25.00),
(10, 3, 7, 1, 30.00),
(11, 3, 6, 1, 20.00),
(12, 3, 1, 1, 18.00),
(13, 3, 2, 1, 18.00),
(14, 3, 3, 1, 45.00),
(15, 3, 9, 1, 35.00),
(16, 3, 10, 1, 35.00),
(17, 3, 12, 1, 30.00),
(18, 4, 1, 1, 18.00),
(19, 5, 1, 2, 18.00),
(20, 5, 2, 1, 18.00),
(21, 5, 4, 1, 22.00),
(22, 6, 4, 1, 22.00),
(23, 7, 1, 10, 18.00),
(24, 7, 10, 1, 35.00),
(25, 7, 8, 1, 25.00),
(26, 7, 11, 1, 25.00),
(27, 7, 6, 1, 20.00),
(28, 8, 3, 1, 45.00),
(29, 9, 1, 2, 18.00),
(30, 10, 4, 3, 27.00),
(31, 10, 2, 4, 23.00),
(32, 11, 1, 3, 18.00),
(33, 12, 1, 3, 23.00),
(34, 13, 1, 5, 23.00),
(35, 13, 6, 1, 20.00),
(36, 14, 3, 3, 45.00),
(37, 14, 9, 1, 35.00),
(38, 14, 6, 1, 20.00),
(39, 14, 4, 1, 27.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) DEFAULT 'General',
  `cantidad` decimal(10,2) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `costo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id`, `nombre`, `categoria`, `cantidad`, `unidad`, `proveedor`, `costo`) VALUES
(1, 'Carne Pastor', 'Carnes', 50.00, 'Kg', 'Carnicería La Estrella', 4500.00),
(2, 'Tortillas', 'Abarrotes', 100.00, 'Kg', 'Tortillería Luz', 1200.00),
(4, 'Lechuga', 'Verduras', 7.00, '1', NULL, 15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `fecha`, `total`, `direccion`, `telefono`) VALUES
(1, 3, '2026-01-12 12:44:09', 58.00, NULL, NULL),
(2, 4, '2026-01-12 17:00:30', 81.00, NULL, NULL),
(3, 4, '2026-01-12 18:26:25', 300.00, NULL, NULL),
(4, 4, '2026-01-12 18:26:41', 18.00, NULL, NULL),
(5, 1, '2026-01-15 17:46:32', 76.00, NULL, NULL),
(6, 1, '2026-01-15 17:50:26', 22.00, NULL, NULL),
(7, 5, '2026-01-21 19:02:45', 285.00, 'pocollo', '72239086765'),
(8, 1, '2026-01-21 19:22:07', 45.00, 'pocollo', '72239086765'),
(9, 6, '2026-01-29 09:59:08', 36.00, 'lago de bustillos 518', '72239086765'),
(10, 1, '2026-02-06 09:05:45', 173.00, 'lago de bustillos 518', '72239086765'),
(11, 1, '2026-02-10 11:37:00', 36.00, 'lago de bustillos 518', NULL),
(12, 1, '2026-02-10 12:30:56', 46.00, 'lago de bustillos 518', '72239086765'),
(13, 2, '2026-02-10 17:25:33', 89.00, 'lago de bustillos 518', '72239086765'),
(14, 8, '2026-02-11 17:09:49', 217.00, 'lago de bustillos 518', '72239086765');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `puesto` varchar(50) NOT NULL,
  `turno` varchar(20) NOT NULL,
  `salario` decimal(10,2) NOT NULL,
  `fecha_ingreso` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personal`
--

INSERT INTO `personal` (`id`, `nombre`, `puesto`, `turno`, `salario`, `fecha_ingreso`) VALUES
(1, 'Carlos \"El Rápido\"', 'Mesero', 'Vespertino', 1200.00, '2026-01-19'),
(2, 'Doña Pelos', 'Chef Principal', 'Matutino', 2500.00, '2026-01-19'),
(3, 'Beto', 'Cajero', 'Mixto', 1500.00, '2026-01-19'),
(5, 'Nosotras', 'Chef', 'Vespertino', 1200.00, '2026-02-10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 100,
  `imagen_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `categoria`, `stock`, `imagen_url`) VALUES
(1, 'Taco al Pastor', 'El clásico rey. Cerdo marinado con achiote y piña asada.', 18.00, 'Tacos', 71, 'img/pastor.jpg'),
(2, 'Taco de Suadero', 'Carne suave confitada en manteca, con cilantro y cebolla.', 18.00, 'Tacos', 92, 'img/suadero.jpg'),
(3, 'Gringa de Pastor', 'Doble tortilla de harina, mucho queso fundido y carne al pastor.', 45.00, 'Especialidades', 94, 'img/gringa.jpg'),
(4, 'Taco Campechano', 'La mezcla perfecta: Bistec y Longaniza en una sola tortilla.', 22.00, 'Tacos', 91, 'img/campechano.jpg'),
(5, 'Coca-Cola Vidrio', 'La clásica de 500ml bien fría.', 25.00, 'Bebidas', 100, 'img/coca.jpg'),
(6, 'Agua de Horchata', 'Receta de la abuela, con canela y arroz artesanal.', 20.00, 'Bebidas', 96, 'img/horchata.jpg'),
(7, 'Volcán de Queso', 'Tortilla tostada a las brasas con costra de queso manchego.', 30.00, 'Especialidades', 99, 'img/volcan.jpg'),
(8, 'Taco Vegano', 'Setas al ajillo con guacamole y pico de gallo.', 25.00, 'Tacos', 98, 'img/vegano.jpg'),
(9, 'Cerveza Corona', '355ml para acompañar tus tacos.', 35.00, 'Bebidas', 98, 'img/cerveza.jpg'),
(10, 'Flan Napolitano', 'Cremosito y con caramelo quemado.', 35.00, 'Postres', 98, 'img/flan.jpg'),
(11, 'Churros', 'Orden de 3 churros con azúcar y canela.', 25.00, 'Postres', 99, 'img/churros.jpg'),
(12, 'Jericalla', 'Postre típico tapatío, similar al flan.', 30.00, 'Postres', 99, 'img/jericalla.jpg'),
(14, 'Tunas', 'dulce', -1.00, 'Postres', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'cliente',
  `puntos` int(11) DEFAULT 0,
  `nivel` varchar(20) DEFAULT 'Bronce',
  `direccion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `username`, `password`, `rol`, `puntos`, `nivel`, `direccion`) VALUES
(1, 'Administrador General', 'admin', '$2y$10$ebhUjeWc.IbAGX1WfIEcq.hXy4cJlqlvdgWQuUJXABP6naUgaOUJK', 'admin', 37, 'Bronce', NULL),
(2, 'Roberto Carlos Camacho', 'Roberto', '$2y$10$RPFqJA4KEY48U3Uc2gsg9.pqnSWLvjsry4lwccBPTKHWCgtEkNl7S', 'admin', 8, 'Bronce', NULL),
(3, 'Victor Adrian Pérez', 'Victor', '$2y$10$d4r9anX3Lkdlbc8nYuWlwuwPKL6uBEEZyPN9I5UFWGNXrFVp6sWme', 'admin', 0, 'Admin', NULL),
(4, 'Carlos Vazquez', 'Carlos', '$2y$10$RmLm1Qheke6CXreGi5UNcu.wJC3OjH8MZq8/OlriZGTgcvP795n5.', 'cliente', 39, 'Bronce', NULL),
(5, 'Mike', 'Migue', '$2y$10$wL2eBgmPKu1OT2oev5ONmeozIg5Av5Ii6o2bF8iRFJY01eJWAPYDq', 'cliente', 28, 'Bronce', NULL),
(6, 'Ale', 'Ale', '$2y$10$T6fU71.Au28uOqLTw.KR9.vUp.biPQp.vCPNMM36VFJhwOI5yJPXy', 'cliente', 3, 'Bronce', NULL),
(7, 'FaMa', 'Fa_Machef', '$2y$10$qDOjL0J.ZGJOyEUngw.WCO28DohwODEvyE0ky0JJ2/Wvl7pgEJQhS', 'cliente', 0, 'Bronce', NULL),
(8, '123456789', 'Morsho', '$2y$10$c2LOnS4hDS89oOfiyw4OMesgy7uwFIlFw0H6CRKArg3lm4nkKiiUS', 'cliente', 21, 'Bronce', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `detalle_pedidos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedidos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
