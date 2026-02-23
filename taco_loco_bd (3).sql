-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-02-2026 a las 01:07:36
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
(1, 1, 1, 8, 18.00),
(2, 2, 2, 2, 18.00),
(3, 2, 1, 3, 18.00),
(4, 2, 11, 2, 25.00),
(5, 2, 12, 1, 30.00),
(6, 2, 3, 2, 45.00),
(7, 2, 7, 1, 30.00),
(8, 2, 6, 1, 20.00),
(9, 2, 9, 1, 35.00),
(10, 3, 11, 1, 25.00),
(11, 3, 2, 1, 18.00),
(12, 3, 3, 1, 45.00),
(13, 4, 1, 1, 18.00),
(14, 4, 2, 1, 18.00),
(15, 4, 4, 1, 22.00),
(16, 4, 7, 1, 30.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `fecha`, `total`) VALUES
(1, 4, '2026-01-21 19:50:04', 144.00),
(2, 4, '2026-01-21 19:50:39', 345.00),
(3, 4, '2026-01-21 19:52:34', 88.00),
(4, 4, '2026-01-21 19:52:51', 88.00);

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
(1, 'Taco al Pastor', 'El clásico rey. Cerdo marinado con achiote y piña asada.', 18.00, 'Tacos', 88, 'https://images.unsplash.com/photo-1599974579688-8dbdd335c77f?w=500'),
(2, 'Taco de Suadero', 'Carne suave confitada en manteca, con cilantro y cebolla.', 18.00, 'Tacos', 96, 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=500'),
(3, 'Gringa de Pastor', 'Doble tortilla de harina, mucho queso fundido y carne al pastor.', 45.00, 'Especialidades', 97, 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=500'),
(4, 'Taco Campechano', 'La mezcla perfecta: Bistec y Longaniza en una sola tortilla.', 22.00, 'Tacos', 99, 'https://images.unsplash.com/photo-1613514785940-daed07799d9b?w=500'),
(5, 'Coca-Cola Vidrio', 'La clásica de 500ml bien fría.', 25.00, 'Bebidas', 100, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=500'),
(6, 'Agua de Horchata', 'Receta de la abuela, con canela y arroz artesanal.', 20.00, 'Bebidas', 99, 'https://images.unsplash.com/photo-1546171753-97d7676e4602?w=500'),
(7, 'Volcán de Queso', 'Tortilla tostada a las brasas con costra de queso manchego.', 30.00, 'Especialidades', 98, 'https://images.unsplash.com/photo-1504544750208-dc0358e63f7f?w=500'),
(8, 'Taco Vegano', 'Setas al ajillo con guacamole y pico de gallo.', 25.00, 'Tacos', 100, 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=500'),
(9, 'Cerveza Corona', '355ml para acompañar tus tacos.', 35.00, 'Bebidas', 99, 'https://images.unsplash.com/photo-1608270586620-248524c67de9?w=500'),
(10, 'Flan Napolitano', 'Cremosito y con caramelo quemado.', 35.00, 'Postres', 100, 'https://images.unsplash.com/photo-1551024601-5629436bb94f?w=500'),
(11, 'Churros', 'Orden de 3 churros con azúcar y canela.', 25.00, 'Postres', 97, 'https://images.unsplash.com/photo-1624371414361-e670edf4898d?w=500'),
(12, 'Jericalla', 'Postre típico tapatío, similar al flan.', 30.00, 'Postres', 99, 'https://images.unsplash.com/photo-1620054701258-298ae8f9f52f?w=500');

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
  `nivel` varchar(20) DEFAULT 'Bronce'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `username`, `password`, `rol`, `puntos`, `nivel`) VALUES
(1, 'Administrador General', 'admin', '$2y$10$6FmCD.cNoNQMOmK/s6GAcOdmLNG5iRYRXqaH6fT0a7F1NwVWqdIB6', 'admin', 0, 'Admin'),
(2, 'Roberto Carlos Camacho', 'Roberto', '$2y$10$W/qQP65st4Q5Hw9GrK8BkOC6WmkZwmu.WI5Nqmw5MRDHKEhZHagtq', 'admin', 0, 'Admin'),
(3, 'Victor Adrian Pérez', 'Victor', '$2y$10$3ZtBjt/mdNaJ7u0tdY9Jz.WFvgmFWX7FIIA8zKEnFGMiuBug5ZzpG', 'admin', 0, 'Admin');

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
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
