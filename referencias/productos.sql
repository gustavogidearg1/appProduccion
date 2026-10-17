-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-10-2026 a las 00:51:42
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
-- Base de datos: `comofras_bd_cotizacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `um_id` bigint(20) UNSIGNED NOT NULL,
  `detalle` text DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `img_1` varchar(255) DEFAULT NULL,
  `img_2` varchar(255) DEFAULT NULL,
  `img_3` varchar(255) DEFAULT NULL,
  `links` varchar(255) DEFAULT NULL,
  `volumen_carga_m3` decimal(8,2) DEFAULT NULL,
  `potencia_requerida_hp` varchar(50) DEFAULT NULL,
  `toma_potencia_tom` varchar(50) DEFAULT NULL,
  `tiempo_descarga_aprx_min` varchar(50) DEFAULT NULL,
  `balanza` varchar(50) DEFAULT NULL,
  `camaras` varchar(50) DEFAULT NULL,
  `altura_maxima_mm` decimal(8,2) DEFAULT NULL,
  `altura_carga_mm` decimal(8,2) DEFAULT NULL,
  `longitud_total_mm` decimal(8,2) DEFAULT NULL,
  `peso_vacio_kg` decimal(8,2) DEFAULT NULL,
  `de_serie` varchar(255) DEFAULT NULL,
  `opcional` varchar(255) DEFAULT NULL,
  `colores` varchar(255) DEFAULT NULL,
  `familia_id` bigint(20) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `um_id`, `detalle`, `img`, `img_1`, `img_2`, `img_3`, `links`, `volumen_carga_m3`, `potencia_requerida_hp`, `toma_potencia_tom`, `tiempo_descarga_aprx_min`, `balanza`, `camaras`, `altura_maxima_mm`, `altura_carga_mm`, `longitud_total_mm`, `peso_vacio_kg`, `de_serie`, `opcional`, `colores`, `familia_id`, `activo`, `tipo_id`, `user_id`, `created_at`, `updated_at`) VALUES
(3, 2200, 'TA-30 \"INNOVA X-TREME\"', 1, 'Cap. 30.000 L (24-25 Tn.) - Ruedas 23-1x30\r\nMedidas totales: Largo 8.500 mm. - Ancho 3.315 mm. - Alto 3.335 mm.\r\nModelos: de 2 ejes\r\nTren giratorio con plato a bolitas - \"SISTEMA BASCULANTE CENTRAL\" - TUBO DE DESCARGA 450 mm Sistema AGDP incoporado - Cámara y Tablet', '/storage/img/rY0S5wfP7mSnjnrNfgOL4bdhnWiQtb3vZmKzWTkl.png', '/storage/img/OUajqtGYrKYqMBAU9rtuRjsvJSW75ZYybU6FUfXn.jpg', '/storage/img/NMeSKJMQlYnWfu2Us2x1LeM9Y3W7LakxNXw65zkL.jpg', '/storage/img/y65iKR8MdmvAz9mNzWBiK3tHeZDHmJs9hgmxQgHD.jpg', 'https://comofrasrl.com.ar/tolvas-autodescargables/', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 5, '2025-05-11 21:16:49', '2026-02-24 12:43:50'),
(4, 19905244, 'DIFERENCIA DE LLANTAS 20x30x5/8\" (23-1x30) POR 21x32x5/8\" (24-5x32)', 1, NULL, '/storage/img/Fi8mrbG0q5IWFCLHHtURTPcE2S4o1HiKW6WkQOdq.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 1, 23, 5, '2025-05-17 22:24:14', '2025-10-08 12:09:37'),
(5, 19904073, 'Neumatico 23:1x30 - BKT - 16 T. - P/ACOP. TA-17,5 - TA-26 Y TA-30', 1, NULL, '/storage/img/XiSfMlOPrWO0iKsWuS9SaKus8U94qUoEpHOmIVk0.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8, 1, 14, 5, '2025-05-17 22:26:42', '2025-05-29 20:57:49'),
(6, 177, 'Rueda', 1, NULL, '/storage/img/mFntUIURSD4d9FyioQ11Zpio7jmcQeY0ZGclKoLm.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8, 1, 2, 3, '2025-05-17 22:34:33', '2025-05-17 22:36:26'),
(7, 5500, 'ATF 26000 L TUBO DESCARGA CON SIN FIN', 1, 'MODELOS ATF - LINEA QUANTUM\r\nACOPLADO TOLVA PARA SEMILLAS Y FERTILIZANTES A GRANEL\r\nEquipados con kits de luces Led, lona destape rápido, divisorio central, doble boquillas y escaleras tubo descarga 190 mm., cabezal giratorio, motor hidráulico 200 mm. transmisión a engranjes y cadena, Fondo y Pintura tipo EPOXI - ALTO SOLIDO.\r\nModelo ATF 26000 L - Capacidad 26.000 LITROS (20-22 Tn.) - Ruedas 275/80x22,5\r\nMedidas Caja: Largo 5.500 mm. - Ancho 2.900 mm. - Alto 3.570 mm. -', '/storage/img/qt5rfr59ueAh6JBPxE7QaYfZEswCvoL2DEGBeFHS.png', '/storage/img/7gb76kgcIHWdeRb8FRwMDlCAfk3Il76yhWOf4VxK.png', NULL, NULL, 'https://drive.google.com/file/d/1oHggEanwQT2EyxroTP18uLK8vqItRY2Q/view?usp=drive_link', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, 1, 5, '2025-05-17 22:37:03', '2026-03-31 19:02:28');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productos_codigo_unique` (`codigo`),
  ADD UNIQUE KEY `productos_nombre_unique` (`nombre`),
  ADD KEY `productos_um_id_foreign` (`um_id`),
  ADD KEY `productos_familia_id_foreign` (`familia_id`),
  ADD KEY `productos_tipo_id_foreign` (`tipo_id`),
  ADD KEY `productos_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
