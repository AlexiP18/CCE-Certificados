-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-03-2026 a las 11:30:57
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
-- Base de datos: `cce_certificados`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT '????',
  `color` varchar(7) DEFAULT '#3498db',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `plantilla_archivo` varchar(255) DEFAULT NULL COMMENT 'Archivo de plantilla espec??fica para esta categor??a',
  `plantilla_fuente` varchar(100) DEFAULT 'Arial' COMMENT 'Fuente para el texto',
  `plantilla_tamanio_fuente` int(11) DEFAULT 48 COMMENT 'Tama??o de fuente',
  `plantilla_color_texto` varchar(7) DEFAULT '#000000' COMMENT 'Color del texto en formato hex',
  `plantilla_variables_habilitadas` text DEFAULT NULL COMMENT 'Variables habilitadas (JSON: nombre, razon, qr, firma, fecha)',
  `plantilla_pos_nombre_x` int(11) DEFAULT 400 COMMENT 'Posici??n X del nombre',
  `plantilla_pos_nombre_y` int(11) DEFAULT 300 COMMENT 'Posici??n Y del nombre',
  `plantilla_pos_razon_x` int(11) DEFAULT 400 COMMENT 'Posici??n X de la raz??n',
  `plantilla_pos_razon_y` int(11) DEFAULT 360 COMMENT 'Posici??n Y de la raz??n',
  `plantilla_pos_qr_x` int(11) DEFAULT 920 COMMENT 'Posici??n X del c??digo QR',
  `plantilla_pos_qr_y` int(11) DEFAULT 419 COMMENT 'Posici??n Y del c??digo QR',
  `plantilla_pos_firma_x` int(11) DEFAULT 800 COMMENT 'Posici??n X de la firma',
  `plantilla_pos_firma_y` int(11) DEFAULT 850 COMMENT 'Posici??n Y de la firma',
  `plantilla_pos_fecha_x` int(11) DEFAULT 400 COMMENT 'Posici??n X de la fecha',
  `plantilla_pos_fecha_y` int(11) DEFAULT 420 COMMENT 'Posici??n Y de la fecha',
  `plantilla_destacado_imagen` varchar(255) DEFAULT NULL,
  `plantilla_destacado_icono` varchar(50) DEFAULT 'star',
  `plantilla_destacado_tipo` varchar(20) DEFAULT 'icono',
  `plantilla_tamanio_destacado` int(11) DEFAULT 100,
  `plantilla_pos_destacado_y` int(11) DEFAULT 50,
  `plantilla_pos_destacado_x` int(11) DEFAULT 50,
  `plantilla_tamanio_qr` int(11) DEFAULT 150 COMMENT 'Tama??o del c??digo QR',
  `plantilla_archivo_firma` varchar(255) DEFAULT NULL COMMENT 'Archivo de imagen de firma',
  `usar_plantilla_propia` tinyint(1) DEFAULT 0 COMMENT 'Si es 1, usa configuraci??n propia; si es 0, hereda del grupo',
  `plantilla_firma_nombre` varchar(255) DEFAULT NULL COMMENT 'Nombre de quien firma el certificado',
  `plantilla_firma_cargo` varchar(255) DEFAULT NULL COMMENT 'Cargo de quien firma el certificado',
  `plantilla_tamanio_firma` int(11) DEFAULT 150 COMMENT 'Tamaño de la imagen de firma',
  `plantilla_razon_defecto` text DEFAULT NULL,
  `plantilla_tamanio_razon` int(11) DEFAULT 24,
  `plantilla_color_razon` varchar(7) DEFAULT '#333333',
  `plantilla_formato_fecha` varchar(50) DEFAULT 'd de F de Y',
  `plantilla_fecha_especifica` date DEFAULT NULL,
  `plantilla_tamanio_fecha` int(11) DEFAULT 20,
  `plantilla_color_fecha` varchar(7) DEFAULT '#333333',
  `plantilla_fuente_razon` varchar(100) DEFAULT 'Arial',
  `plantilla_fuente_fecha` varchar(100) DEFAULT 'Arial'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `grupo_id`, `nombre`, `descripcion`, `icono`, `color`, `activo`, `fecha_creacion`, `plantilla_archivo`, `plantilla_fuente`, `plantilla_tamanio_fuente`, `plantilla_color_texto`, `plantilla_variables_habilitadas`, `plantilla_pos_nombre_x`, `plantilla_pos_nombre_y`, `plantilla_pos_razon_x`, `plantilla_pos_razon_y`, `plantilla_pos_qr_x`, `plantilla_pos_qr_y`, `plantilla_pos_firma_x`, `plantilla_pos_firma_y`, `plantilla_pos_fecha_x`, `plantilla_pos_fecha_y`, `plantilla_destacado_imagen`, `plantilla_destacado_icono`, `plantilla_destacado_tipo`, `plantilla_tamanio_destacado`, `plantilla_pos_destacado_y`, `plantilla_pos_destacado_x`, `plantilla_tamanio_qr`, `plantilla_archivo_firma`, `usar_plantilla_propia`, `plantilla_firma_nombre`, `plantilla_firma_cargo`, `plantilla_tamanio_firma`, `plantilla_razon_defecto`, `plantilla_tamanio_razon`, `plantilla_color_razon`, `plantilla_formato_fecha`, `plantilla_fecha_especifica`, `plantilla_tamanio_fecha`, `plantilla_color_fecha`, `plantilla_fuente_razon`, `plantilla_fuente_fecha`) VALUES
(84, 73, 'Danza', 'La danza o el baile es un arte donde se utiliza el movimiento del cuerpo, normalmente con música, como una forma de expresión y de interacción social con fines de entretenimiento y artísticos.', '💃', '#9b59b6', 0, '2026-02-13 23:47:16', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(85, 73, 'Pintura', 'Taller de pintura ofrecido por la CCE', '🎨', '#9b59b6', 1, '2026-02-14 23:48:34', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(86, 73, 'DJ', 'Taller de DJ ofrecido por la CCE', '🎵', '#9b59b6', 1, '2026-02-14 23:56:29', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(87, 73, ' Danza', 'Taller de danza de la CCE', '💃', '#9b59b6', 0, '2026-02-15 22:00:09', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(88, 73, 'Danza', 'Taller danza de la CCE', '💃', '#9b59b6', 1, '2026-02-17 06:41:57', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(89, 73, 'Batería', 'Taller de batería ofrecido por la CCE', '🥁', '#9b59b6', 1, '2026-02-17 07:28:03', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial'),
(90, 73, 'Trompeta', 'Taller de trompeta ofrecido por la CCE', '🎺', '#9b59b6', 1, '2026-02-17 07:30:05', NULL, 'Arial', 48, '#000000', NULL, 400, 300, 400, 360, 920, 419, 800, 850, 400, 420, NULL, 'star', 'icono', 100, 50, 50, 150, NULL, 0, NULL, NULL, 150, NULL, 24, '#333333', 'd de F de Y', NULL, 20, '#333333', 'Arial', 'Arial');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_estudiantes`
--

CREATE TABLE `categoria_estudiantes` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `periodo_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) NOT NULL,
  `fecha_matricula` date NOT NULL,
  `estado` enum('activo','inactivo','completado') DEFAULT 'activo',
  `notas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria_estudiantes`
--

INSERT INTO `categoria_estudiantes` (`id`, `categoria_id`, `periodo_id`, `estudiante_id`, `fecha_matricula`, `estado`, `notas`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(142, 88, 112, 114, '2026-02-17', 'activo', NULL, '2026-02-17 09:48:35', '2026-02-17 09:48:35'),
(143, 88, 112, 116, '2026-02-17', 'activo', NULL, '2026-02-18 00:09:38', '2026-02-18 00:09:38'),
(144, 88, 112, 117, '2026-02-17', 'activo', NULL, '2026-02-18 00:09:38', '2026-02-18 00:09:38'),
(145, 88, 112, 115, '2026-02-17', 'activo', NULL, '2026-02-18 00:34:34', '2026-02-18 00:34:34'),
(146, 88, 112, 118, '2026-02-18', 'activo', NULL, '2026-02-18 05:32:26', '2026-02-18 05:32:26'),
(147, 88, 112, 119, '2026-02-18', 'activo', NULL, '2026-02-18 05:33:36', '2026-02-18 05:33:36'),
(148, 88, 112, 120, '2026-02-18', 'activo', NULL, '2026-02-18 10:05:24', '2026-02-18 10:05:24'),
(149, 88, 112, 121, '2026-02-18', 'activo', NULL, '2026-02-19 01:36:45', '2026-02-19 01:36:45'),
(150, 88, 112, 122, '2026-02-18', 'activo', NULL, '2026-02-19 01:38:24', '2026-02-19 01:38:24'),
(151, 88, 112, 123, '2026-02-18', 'activo', NULL, '2026-02-19 01:59:48', '2026-02-19 01:59:48'),
(152, 88, 112, 125, '2026-02-18', 'activo', NULL, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(153, 88, 112, 126, '2026-02-18', 'activo', NULL, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(154, 88, 112, 127, '2026-02-18', 'activo', NULL, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(155, 88, 112, 128, '2026-02-18', 'activo', NULL, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(156, 88, 112, 129, '2026-02-19', 'activo', NULL, '2026-02-19 21:04:30', '2026-02-19 21:04:30'),
(157, 88, 112, 130, '2026-02-19', 'activo', NULL, '2026-02-19 21:06:19', '2026-02-19 21:06:19'),
(158, 88, 112, 132, '2026-03-05', 'activo', NULL, '2026-03-05 09:41:30', '2026-03-05 09:41:30'),
(159, 88, 112, 133, '2026-03-05', 'activo', NULL, '2026-03-05 09:57:27', '2026-03-05 09:57:27'),
(160, 88, 112, 135, '2026-03-05', 'activo', NULL, '2026-03-05 09:59:59', '2026-03-05 09:59:59'),
(161, 88, 112, 137, '2026-03-05', 'activo', NULL, '2026-03-05 10:15:10', '2026-03-05 10:15:10'),
(162, 88, 112, 139, '2026-03-05', 'activo', NULL, '2026-03-05 10:28:18', '2026-03-05 10:28:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_periodos`
--

CREATE TABLE `categoria_periodos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria_periodos`
--

INSERT INTO `categoria_periodos` (`id`, `categoria_id`, `periodo_id`, `activo`, `fecha_asignacion`) VALUES
(128, 84, 112, 1, '2026-02-13 23:47:16'),
(129, 85, 112, 1, '2026-02-14 23:48:34'),
(130, 86, 112, 1, '2026-02-14 23:56:29'),
(131, 87, 112, 1, '2026-02-15 22:00:09'),
(132, 84, 113, 1, '2026-02-16 08:17:52'),
(133, 88, 112, 1, '2026-02-17 06:41:57'),
(134, 88, 113, 1, '2026-02-17 06:42:23'),
(135, 86, 113, 1, '2026-02-17 07:11:27'),
(136, 85, 113, 1, '2026-02-17 07:11:43'),
(137, 89, 113, 1, '2026-02-17 07:28:03'),
(138, 90, 112, 1, '2026-02-17 07:30:05'),
(139, 89, 112, 1, '2026-02-19 10:18:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_plantillas`
--

CREATE TABLE `categoria_plantillas` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `archivo` varchar(255) NOT NULL,
  `es_activa` tinyint(1) DEFAULT 0,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `posicion_nombre_x` int(11) DEFAULT 400,
  `posicion_nombre_y` int(11) DEFAULT 300,
  `posicion_razon_x` int(11) DEFAULT 400,
  `posicion_razon_y` int(11) DEFAULT 360,
  `posicion_fecha_x` int(11) DEFAULT 400,
  `posicion_fecha_y` int(11) DEFAULT 420,
  `posicion_qr_x` int(11) DEFAULT 920,
  `posicion_qr_y` int(11) DEFAULT 419,
  `posicion_firma_x` int(11) DEFAULT 800,
  `posicion_firma_y` int(11) DEFAULT 850,
  `fuente_nombre` varchar(100) DEFAULT 'Roboto-Regular',
  `formato_nombre` varchar(20) DEFAULT 'mayusculas',
  `fuente_razon` varchar(100) DEFAULT 'Roboto-Regular',
  `fuente_fecha` varchar(100) DEFAULT 'Roboto-Regular',
  `tamanio_fuente` int(11) DEFAULT 50,
  `tamanio_razon` int(11) DEFAULT 24,
  `tamanio_fecha` int(11) DEFAULT 20,
  `tamanio_qr` int(11) DEFAULT 200,
  `tamanio_firma` int(11) DEFAULT 200,
  `color_texto` varchar(7) DEFAULT '#000000',
  `color_razon` varchar(7) DEFAULT '#333333',
  `color_fecha` varchar(7) DEFAULT '#333333',
  `razon_defecto` text DEFAULT NULL,
  `formato_fecha` varchar(50) DEFAULT 'd de F de Y',
  `variables_habilitadas` text DEFAULT NULL,
  `ancho_razon` int(11) DEFAULT 600,
  `firma_imagen` varchar(255) DEFAULT NULL,
  `firma_nombre` varchar(255) DEFAULT NULL,
  `firma_cargo` varchar(255) DEFAULT NULL,
  `alineacion_razon` varchar(20) DEFAULT 'justified',
  `destacado_posicion_x` int(11) DEFAULT 50,
  `destacado_posicion_y` int(11) DEFAULT 50,
  `destacado_tamanio` int(11) DEFAULT 100,
  `destacado_tipo` varchar(20) DEFAULT 'icono',
  `destacado_icono` varchar(50) DEFAULT 'star',
  `destacado_imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificados`
--

CREATE TABLE `certificados` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `razon` text NOT NULL,
  `fecha` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivo_imagen` varchar(255) DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `aprobado` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si el certificado ha sido aprobado para generación',
  `aprobado_por` int(11) DEFAULT NULL COMMENT 'Usuario administrador que aprobó',
  `fecha_aprobacion` timestamp NULL DEFAULT NULL COMMENT 'Fecha y hora de aprobación',
  `requiere_aprobacion` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si este certificado requiere aprobación antes de generarse',
  `fechas_generacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Historial de fechas de generación' CHECK (json_valid(`fechas_generacion`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificados_aprobaciones`
--

CREATE TABLE `certificados_aprobaciones` (
  `id` int(11) NOT NULL,
  `certificado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Usuario que realizó la acción',
  `accion` enum('aprobar','rechazar','revocar') NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_plantillas`
--

CREATE TABLE `configuracion_plantillas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `archivo_plantilla` varchar(255) NOT NULL,
  `fuente_nombre` varchar(100) DEFAULT 'Arial',
  `tamanio_fuente` int(11) DEFAULT 48,
  `color_texto` varchar(7) DEFAULT '#000000',
  `posicion_nombre_x` int(11) DEFAULT 400,
  `posicion_nombre_y` int(11) DEFAULT 300,
  `posicion_qr_x` int(11) DEFAULT 50,
  `posicion_qr_y` int(11) DEFAULT 50,
  `posicion_qr` varchar(20) DEFAULT 'bottom-right',
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `es_menor` tinyint(1) DEFAULT 0,
  `representante_nombre` varchar(255) DEFAULT NULL,
  `representante_cedula` varchar(20) DEFAULT NULL,
  `representante_celular` varchar(15) DEFAULT NULL,
  `representante_email` varchar(255) DEFAULT NULL,
  `representante_fecha_nacimiento` date DEFAULT NULL,
  `representante_id` int(11) DEFAULT NULL,
  `es_solo_representante` tinyint(1) DEFAULT 0,
  `destacado` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `cedula`, `nombre`, `fecha_nacimiento`, `celular`, `email`, `es_menor`, `representante_nombre`, `representante_cedula`, `representante_celular`, `representante_email`, `representante_fecha_nacimiento`, `representante_id`, `es_solo_representante`, `destacado`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(114, '1850351840', 'Cristal Yanela Acosta', '2000-02-10', '945564654', 'cristal@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, '2026-02-17 09:48:35', '2026-02-19 00:50:39'),
(115, '1805244199', 'Edison Joel Nuñez Acosta', '1994-05-10', '997854564', 'edison@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-18 00:09:38', '2026-02-20 01:30:23'),
(116, '1804960308', 'Jordy Nuñez', '2015-03-10', NULL, NULL, 1, 'Edison Joel Nuñez Acosta', '1805244199', '997854564', 'edison@gmail.com', NULL, 115, 0, 1, 1, '2026-02-18 00:09:38', '2026-02-20 01:29:56'),
(117, '1805218607', 'Antony Nuñez', '2016-04-10', '966546546', 'antony@gmail.com', 1, 'Edison Joel Nuñez Acosta', '1805244199', '997854564', 'edison@gmail.com', NULL, 115, 0, 0, 1, '2026-02-18 00:09:38', '2026-02-19 01:14:16'),
(118, '1850426212', 'Jonathan Christian Villareal Calero', '2000-05-10', '021346574', 'jonathan@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, '2026-02-18 05:32:26', '2026-02-19 01:00:56'),
(119, '1755559711', 'Alejandro Ramiro Chasi Padilla', '1995-03-02', '956546544', 'alejandro@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, '2026-02-18 05:33:36', '2026-02-19 00:33:26'),
(120, '1850975051', 'Ruben Alexander Chicaiza Aucapiña', '1990-07-10', '991254654', 'ruben@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-18 10:05:24', '2026-02-18 10:05:24'),
(121, '1850082502', 'Omar Joel Chaglla Chito', '2000-05-10', '962321654', 'omar@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, '2026-02-19 01:36:45', '2026-03-04 02:31:13'),
(122, '1850500636', 'Carlos Sebastián Guaman Cholota', '1990-01-05', '963215465', 'carlos@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-19 01:38:24', '2026-02-19 01:38:24'),
(123, '1804820866', 'Diego Alexander Cocha Cocha', '1990-05-10', '985646546', 'diego@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-19 01:59:48', '2026-02-19 01:59:48'),
(124, '1756078380', 'Michel Alexander Falcon Yacchirema', '1990-04-02', '944216546', 'michel@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(125, NULL, 'Maria Falcon', '2010-05-10', NULL, NULL, 1, 'Michel Alexander Falcon Yacchirema', '1756078380', '944216546', 'michel@gmail.com', NULL, 124, 0, 0, 1, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(126, '1851046795', 'Jorge Falcon', '2011-02-06', NULL, NULL, 1, 'Michel Alexander Falcon Yacchirema', '1756078380', '944216546', 'michel@gmail.com', NULL, 124, 0, 1, 1, '2026-02-19 02:04:36', '2026-02-20 01:29:29'),
(127, '1805790191', 'Mauricio Falcon', '2012-05-25', '956565465', NULL, 1, 'Michel Alexander Falcon Yacchirema', '1756078380', '944216546', 'michel@gmail.com', NULL, 124, 0, 0, 1, '2026-02-19 02:04:36', '2026-02-19 02:04:36'),
(128, '0503957912', 'Jofre Falcon', '2015-05-11', '956546546', 'marlon@gmail.com', 1, 'Michel Alexander Falcon Yacchirema', '1756078380', '944216546', 'michel@gmail.com', NULL, 124, 0, 1, 1, '2026-02-19 02:04:36', '2026-02-20 01:29:29'),
(129, '1850010206', 'Stalin Alejandro Laura Laura', '1985-05-10', '992654654', 'stalin@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-19 21:04:30', '2026-02-19 21:04:30'),
(130, '0503179616', 'Diego Armando Lema Constante', '1985-05-10', '956541654', 'armando@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-02-19 21:06:19', '2026-02-19 21:06:19'),
(131, '1850376482', 'Kevin Daniel Vayas Guerrero', '1990-05-10', '963696456', 'kevin@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-03-05 09:41:30', '2026-03-05 09:41:30'),
(132, NULL, 'Juana Vayas', '2010-05-10', NULL, NULL, 1, 'Kevin Daniel Vayas Guerrero', '1850376482', '963696456', 'kevin@gmail.com', '1990-05-10', 131, 0, 0, 1, '2026-03-05 09:41:30', '2026-03-05 09:41:30'),
(133, '1805803572', 'Erick Fernando Santana Lopez', '1991-05-10', '946646546', 'erick@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-03-05 09:57:27', '2026-03-05 09:57:27'),
(134, '1850572767', 'Fernando Javier Yugsi Lucio', '1995-01-05', '995654654', 'fernando@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-03-05 09:59:59', '2026-03-05 09:59:59'),
(135, NULL, 'Josue Yugsi', '2010-05-10', NULL, NULL, 1, 'Fernando Javier Yugsi Lucio', '1850572767', '995654654', 'fernando@gmail.com', '1995-01-05', 134, 0, 0, 1, '2026-03-05 09:59:59', '2026-03-05 09:59:59'),
(136, '1804721155', 'Marlon Isaac Malan Vallejo', '1989-04-20', '955646546', 'isaac@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-03-05 10:15:10', '2026-03-05 10:15:10'),
(137, NULL, 'Steven Malan', '2015-05-10', '956465465', NULL, 1, 'Marlon Isaac Malan Vallejo', '1804721155', '955646546', 'isaac@gmail.com', '1989-04-20', 136, 0, 0, 1, '2026-03-05 10:15:10', '2026-03-05 10:15:10'),
(138, '0605809813', 'Nelly Maribel Marcatoma Huaraca', '1990-05-10', '995646546', 'nelly@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-03-05 10:28:18', '2026-03-05 10:28:18'),
(139, NULL, 'Alex Marcatoma', '2015-01-05', '916341654', NULL, 1, 'Nelly Maribel Marcatoma Huaraca', '0605809813', '995646546', 'nelly@gmail.com', '1990-05-10', 138, 0, 0, 1, '2026-03-05 10:28:18', '2026-03-05 10:28:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes_destacados`
--

CREATE TABLE `estudiantes_destacados` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL COMMENT 'ID del usuario instructor que destac├│',
  `motivo` varchar(255) DEFAULT NULL COMMENT 'Motivo por el que se destaca',
  `fecha_destacado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes_referencias`
--

CREATE TABLE `estudiantes_referencias` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `relacion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes_referencias`
--

INSERT INTO `estudiantes_referencias` (`id`, `estudiante_id`, `nombre`, `telefono`, `relacion`) VALUES
(1, 120, 'Ana Aucapiña', '964565465', 'Madre'),
(2, 120, 'Jorge Chicaiza', '996564654', 'Padre'),
(3, 120, 'Luis Chicaiza', '946545465', 'Hermano'),
(6, 122, 'Luisa Guaman', '955315546', 'Madre'),
(7, 122, 'Steven Guaman', '935152465', 'Primo'),
(8, 122, 'Sofia Palacios', '949846511', 'Novia'),
(9, 123, 'Armando Cocha', '995126546', 'Hermano'),
(10, 123, 'Jorge Cocha', '689746541', 'Primo'),
(11, 123, 'Steven Lasluisa', '345654656', 'AMigo'),
(15, 130, 'Diego Constante', '989654654', ''),
(16, 121, 'Juan Chaglla', '935165465', 'Padre'),
(17, 121, 'Ana Chaglla', '953241656', 'Tia'),
(18, 131, 'Marlon Diaz', '965654654', 'Amigo'),
(19, 124, 'Jorge Falcon', '567484987', 'Hermano'),
(20, 124, 'Steven Flacon', '324667979', 'Padre'),
(21, 124, 'Ana Lasluisa', '359798465', 'Amiga'),
(22, 133, 'Marlon Laura', '965446546', 'Tio'),
(23, 134, 'Ana Yugsi', '965656546', 'Hermana'),
(24, 136, 'Juanita Burbano', '956465465', 'Amiga'),
(25, 138, 'Sofia Buenaño', '955456465', 'Amiga');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_certificados`
--

CREATE TABLE `estudiante_certificados` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `certificado_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fuentes_personalizadas`
--

CREATE TABLE `fuentes_personalizadas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre para mostrar',
  `nombre_archivo` varchar(255) NOT NULL COMMENT 'Nombre del archivo sin extensión',
  `archivo` varchar(255) NOT NULL COMMENT 'Nombre completo del archivo con extensión',
  `tipo` enum('ttf','otf','woff','woff2') DEFAULT 'ttf',
  `categoria` enum('sans-serif','serif','display','handwriting','monospace') DEFAULT 'sans-serif',
  `activo` tinyint(1) DEFAULT 1,
  `es_sistema` tinyint(1) DEFAULT 0 COMMENT 'Si es una fuente del sistema (no se puede eliminar)',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fuentes_personalizadas`
--

INSERT INTO `fuentes_personalizadas` (`id`, `nombre`, `nombre_archivo`, `archivo`, `tipo`, `categoria`, `activo`, `es_sistema`, `fecha_creacion`) VALUES
(36, 'BBHHegarty Regular', 'BBHHegarty-Regular', 'BBHHegarty-Regular.ttf', 'ttf', 'sans-serif', 1, 0, '2026-01-13 03:17:16'),
(37, 'Montserrat', 'Montserrat', 'Montserrat.ttf', 'ttf', 'sans-serif', 1, 0, '2026-01-13 03:17:30'),
(38, 'Dancing Script', 'Dancing-Script', 'Dancing-Script.ttf', 'ttf', 'handwriting', 1, 0, '2026-01-13 03:17:52'),
(39, 'Roboto Regular', 'Roboto-Regular', 'Roboto-Regular.ttf', 'ttf', 'sans-serif', 1, 0, '2026-01-13 03:18:20'),
(40, 'Bodoni-Moda', 'Bodoni-Moda', 'Bodoni-Moda.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(41, 'Lato-Regular', 'Lato-Regular', 'Lato-Regular.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(42, 'Motterdam', 'Motterdam', 'Motterdam.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(43, 'OpenSans', 'OpenSans', 'OpenSans.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(44, 'PlayfairDisplay', 'PlayfairDisplay', 'PlayfairDisplay.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(45, 'Poppins-Regular', 'Poppins-Regular', 'Poppins-Regular.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(46, 'google_Abril_Fatface', 'google_Abril_Fatface', 'google_Abril_Fatface.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(47, 'google_Allura', 'google_Allura', 'google_Allura.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(48, 'google_Bebas_Neue', 'google_Bebas_Neue', 'google_Bebas_Neue.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(49, 'google_Cinzel', 'google_Cinzel', 'google_Cinzel.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(50, 'google_Dancing_Script', 'google_Dancing_Script', 'google_Dancing_Script.ttf', 'ttf', 'handwriting', 1, 0, '2026-02-15 07:47:43'),
(51, 'google_Great_Vibes', 'google_Great_Vibes', 'google_Great_Vibes.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(52, 'google_Lato', 'google_Lato', 'google_Lato.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(53, 'google_Libre_Baskerville', 'google_Libre_Baskerville', 'google_Libre_Baskerville.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(54, 'google_Lora', 'google_Lora', 'google_Lora.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(55, 'google_Merriweather', 'google_Merriweather', 'google_Merriweather.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(56, 'google_Montserrat', 'google_Montserrat', 'google_Montserrat.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(57, 'google_Open_Sans', 'google_Open_Sans', 'google_Open_Sans.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(58, 'google_Oswald', 'google_Oswald', 'google_Oswald.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(59, 'google_PT_Serif', 'google_PT_Serif', 'google_PT_Serif.ttf', 'ttf', 'serif', 1, 0, '2026-02-15 07:47:43'),
(60, 'google_Pacifico', 'google_Pacifico', 'google_Pacifico.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(61, 'google_Playfair_Display', 'google_Playfair_Display', 'google_Playfair_Display.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(62, 'google_Poppins', 'google_Poppins', 'google_Poppins.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(63, 'google_Righteous', 'google_Righteous', 'google_Righteous.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(64, 'google_Roboto', 'google_Roboto', 'google_Roboto.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:43'),
(65, 'google_Roboto_Mono', 'google_Roboto_Mono', 'google_Roboto_Mono.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:44'),
(66, 'google_Sacramento', 'google_Sacramento', 'google_Sacramento.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:44'),
(67, 'google_Satisfy', 'google_Satisfy', 'google_Satisfy.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:44'),
(68, 'google_Source_Code_Pro', 'google_Source_Code_Pro', 'google_Source_Code_Pro.ttf', 'ttf', 'sans-serif', 1, 0, '2026-02-15 07:47:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'workshop',
  `color` varchar(7) DEFAULT '#3498db',
  `plantilla` varchar(255) DEFAULT NULL,
  `razon_defecto` text DEFAULT NULL,
  `firma_nombre` varchar(255) DEFAULT NULL,
  `firma_cargo` varchar(255) DEFAULT NULL,
  `firma_imagen` varchar(255) DEFAULT NULL,
  `fuente_nombre` varchar(100) DEFAULT 'Arial',
  `formato_nombre` varchar(20) DEFAULT 'mayusculas',
  `tamanio_fuente` int(11) DEFAULT 48,
  `color_texto` varchar(7) DEFAULT '#000000',
  `posicion_nombre_x` int(11) DEFAULT 400,
  `posicion_nombre_y` int(11) DEFAULT 300,
  `posicion_qr_x` int(11) DEFAULT 920,
  `posicion_qr_y` int(11) DEFAULT 419,
  `tamanio_qr` int(11) DEFAULT 200,
  `posicion_firma_x` int(11) DEFAULT 800,
  `posicion_firma_y` int(11) DEFAULT 850,
  `tamanio_firma` int(11) DEFAULT 150,
  `variables_habilitadas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["nombre","razon","qr","firma"]' CHECK (json_valid(`variables_habilitadas`)),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `posicion_razon_x` int(11) DEFAULT 400,
  `posicion_razon_y` int(11) DEFAULT 360,
  `posicion_fecha_x` int(11) DEFAULT 400,
  `posicion_fecha_y` int(11) DEFAULT 420,
  `fuente_razon` varchar(100) DEFAULT 'Arial',
  `fuente_fecha` varchar(100) DEFAULT 'Arial',
  `tamanio_razon` int(11) DEFAULT 24,
  `color_razon` varchar(7) DEFAULT '#333333',
  `formato_fecha` varchar(50) DEFAULT 'd de F de Y',
  `usar_fecha_especifica` tinyint(1) DEFAULT 0,
  `fecha_especifica` date DEFAULT NULL,
  `tamanio_fecha` int(11) DEFAULT 20,
  `color_fecha` varchar(7) DEFAULT '#333333',
  `ancho_razon` int(11) DEFAULT 600,
  `posicion_destacado_x` int(11) DEFAULT 50,
  `posicion_destacado_y` int(11) DEFAULT 50,
  `tamanio_destacado` int(11) DEFAULT 100,
  `destacado_tipo` varchar(20) DEFAULT 'icono',
  `destacado_icono` varchar(50) DEFAULT 'estrella',
  `destacado_imagen` varchar(255) DEFAULT NULL,
  `destacado_habilitado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `nombre`, `descripcion`, `icono`, `color`, `plantilla`, `razon_defecto`, `firma_nombre`, `firma_cargo`, `firma_imagen`, `fuente_nombre`, `formato_nombre`, `tamanio_fuente`, `color_texto`, `posicion_nombre_x`, `posicion_nombre_y`, `posicion_qr_x`, `posicion_qr_y`, `tamanio_qr`, `posicion_firma_x`, `posicion_firma_y`, `tamanio_firma`, `variables_habilitadas`, `fecha_creacion`, `activo`, `posicion_razon_x`, `posicion_razon_y`, `posicion_fecha_x`, `posicion_fecha_y`, `fuente_razon`, `fuente_fecha`, `tamanio_razon`, `color_razon`, `formato_fecha`, `usar_fecha_especifica`, `fecha_especifica`, `tamanio_fecha`, `color_fecha`, `ancho_razon`, `posicion_destacado_x`, `posicion_destacado_y`, `tamanio_destacado`, `destacado_tipo`, `destacado_icono`, `destacado_imagen`, `destacado_habilitado`) VALUES
(71, 'Taller', 'Talleres ofrecidos por la CCE', '📚', '#3498db', NULL, NULL, NULL, NULL, NULL, 'Arial', 'mayusculas', 48, '#000000', 400, 300, 920, 419, 200, 800, 850, 150, '[\"nombre\",\"razon\",\"qr\",\"firma\"]', '2026-02-13 22:07:23', 1, 400, 360, 400, 420, 'Arial', 'Arial', 24, '#333333', 'd de F de Y', 0, NULL, 20, '#333333', 600, 50, 50, 100, 'icono', 'estrella', NULL, 0),
(72, 'Cursos', 'Cursos ofrecidos por la CCE', '🎭', '#f39c12', NULL, NULL, NULL, NULL, NULL, 'Arial', 'mayusculas', 48, '#000000', 400, 300, 920, 419, 200, 800, 850, 150, '[\"nombre\",\"razon\",\"qr\",\"firma\"]', '2026-02-13 22:09:37', 1, 400, 360, 400, 420, 'Arial', 'Arial', 24, '#333333', 'd de F de Y', 0, NULL, 20, '#333333', 600, 50, 50, 100, 'icono', 'estrella', NULL, 0),
(73, 'Concurso', 'Concursos de la CCE', '🏆', '#9b59b6', NULL, 'Otorgado a {nombre}. Por su destacada participación en el {grupo} de {categoria}. Otorgado el {fecha}', 'Mgtr. Noemi Caiza', 'Directora de la Casa de la Cultura', 'uploads/firmas/firma_73_sys_1772519428.jpg', 'BBHHegarty-Regular', 'minusculas', 50, '#7300ff', 310, 493, 292, 665, 310, 1079, 683, 300, '[\"nombre\",\"firma\",\"destacado\",\"razon\",\"fecha\",\"qr\"]', '2026-02-13 22:10:18', 1, 153, 563, 624, 410, 'google_Pacifico', 'google_Righteous', 30, '#333333', 'd de F de Y', 0, NULL, 35, '#ff0000', 1300, 1263, 48, 300, 'icono', 'trofeo', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_periodos`
--

CREATE TABLE `grupo_periodos` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupo_periodos`
--

INSERT INTO `grupo_periodos` (`id`, `grupo_id`, `periodo_id`, `activo`, `fecha_creacion`) VALUES
(134, 73, 112, 1, '2026-02-13 22:23:15'),
(135, 73, 113, 1, '2026-02-15 22:47:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_plantillas`
--

CREATE TABLE `grupo_plantillas` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `archivo` varchar(255) NOT NULL,
  `es_activa` tinyint(1) DEFAULT 0,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `posicion_nombre_x` int(11) DEFAULT 400,
  `posicion_nombre_y` int(11) DEFAULT 300,
  `posicion_razon_x` int(11) DEFAULT 400,
  `posicion_razon_y` int(11) DEFAULT 360,
  `posicion_fecha_x` int(11) DEFAULT 400,
  `posicion_fecha_y` int(11) DEFAULT 420,
  `posicion_qr_x` int(11) DEFAULT 920,
  `posicion_qr_y` int(11) DEFAULT 419,
  `posicion_firma_x` int(11) DEFAULT 800,
  `posicion_firma_y` int(11) DEFAULT 850,
  `fuente_nombre` varchar(100) DEFAULT 'Roboto-Regular',
  `formato_nombre` varchar(20) DEFAULT 'mayusculas',
  `fuente_razon` varchar(100) DEFAULT 'Roboto-Regular',
  `fuente_fecha` varchar(100) DEFAULT 'Roboto-Regular',
  `tamanio_fuente` int(11) DEFAULT 50,
  `tamanio_razon` int(11) DEFAULT 24,
  `tamanio_fecha` int(11) DEFAULT 20,
  `tamanio_qr` int(11) DEFAULT 200,
  `tamanio_firma` int(11) DEFAULT 200,
  `color_texto` varchar(7) DEFAULT '#000000',
  `color_razon` varchar(7) DEFAULT '#333333',
  `color_fecha` varchar(7) DEFAULT '#333333',
  `razon_defecto` text DEFAULT NULL,
  `formato_fecha` varchar(50) DEFAULT 'd de F de Y',
  `variables_habilitadas` text DEFAULT NULL,
  `ancho_razon` int(11) DEFAULT 600,
  `lineas_razon` int(11) DEFAULT 1,
  `alineacion_razon` varchar(20) DEFAULT 'justified',
  `destacado_habilitado` tinyint(1) DEFAULT 0,
  `destacado_tipo` varchar(20) DEFAULT 'icono',
  `destacado_icono` varchar(50) DEFAULT 'estrella',
  `destacado_imagen` varchar(255) DEFAULT NULL,
  `destacado_posicion_x` int(11) DEFAULT 50,
  `destacado_posicion_y` int(11) DEFAULT 50,
  `destacado_tamanio` int(11) DEFAULT 100,
  `firma_imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupo_plantillas`
--

INSERT INTO `grupo_plantillas` (`id`, `grupo_id`, `nombre`, `archivo`, `es_activa`, `orden`, `fecha_creacion`, `posicion_nombre_x`, `posicion_nombre_y`, `posicion_razon_x`, `posicion_razon_y`, `posicion_fecha_x`, `posicion_fecha_y`, `posicion_qr_x`, `posicion_qr_y`, `posicion_firma_x`, `posicion_firma_y`, `fuente_nombre`, `formato_nombre`, `fuente_razon`, `fuente_fecha`, `tamanio_fuente`, `tamanio_razon`, `tamanio_fecha`, `tamanio_qr`, `tamanio_firma`, `color_texto`, `color_razon`, `color_fecha`, `razon_defecto`, `formato_fecha`, `variables_habilitadas`, `ancho_razon`, `lineas_razon`, `alineacion_razon`, `destacado_habilitado`, `destacado_tipo`, `destacado_icono`, `destacado_imagen`, `destacado_posicion_x`, `destacado_posicion_y`, `destacado_tamanio`, `firma_imagen`) VALUES
(41, 73, 'plantilla_certificado', 'plantilla_1772014613_699ecc1504f91.jpg', 0, 1, '2026-02-25 10:16:53', 374, 405, 258, 518, 610, 311, 391, 777, 972, 850, 'PlayfairDisplay', 'mayusculas', 'OpenSans', 'Poppins-Regular', 65, 30, 40, 300, 285, '#0040ff', '#ff0000', '#00b33e', 'Por su destacada participación en el {grupo} de {categoria}. Otorgado el {fecha}.', 'd de F de Y', '[\"nombre\",\"razon\",\"fecha\",\"qr\",\"firma\",\"destacado\"]', 1147, 1, 'justified', 1, 'icono', 'capitan', NULL, 1298, 50, 230, 'uploads/firmas/firma_73_41_1772521300.png'),
(43, 73, 'certificado_default', 'plantilla_1772142806_69a0c0d6a6ee4.png', 0, 2, '2026-02-26 21:53:26', 367, 484, 261, 655, 680, 367, 284, 712, 1123, 745, 'google_Pacifico', 'mayusculas', 'Roboto-Regular', 'Roboto-Regular', 60, 30, 20, 250, 300, '#000000', '#333333', '#333333', 'Por su destacada participación en el {grupo} de {categoria}.', 'd de F de Y', '[\"nombre\",\"razon\",\"fecha\",\"qr\",\"firma\",\"destacado\"]', 1107, 1, 'center', 1, 'icono', 'laurel', NULL, 1349, 31, 230, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructor_categorias`
--

CREATE TABLE `instructor_categorias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'ID del instructor',
  `categoria_id` int(11) NOT NULL COMMENT 'ID de la categor├¡a asignada',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructor_grupos`
--

CREATE TABLE `instructor_grupos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'ID del instructor',
  `grupo_id` int(11) NOT NULL COMMENT 'ID del grupo asignado',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_actividad`
--

CREATE TABLE `log_actividad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `datos_adicionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_adicionales`)),
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `log_actividad`
--

INSERT INTO `log_actividad` (`id`, `usuario_id`, `accion`, `descripcion`, `ip_address`, `datos_adicionales`, `fecha`) VALUES
(98, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-13 21:33:14'),
(99, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-14 09:56:51'),
(100, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-14 23:46:03'),
(101, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-15 21:12:27'),
(102, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-16 08:16:22'),
(103, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-17 06:41:05'),
(104, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-17 22:54:29'),
(105, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-18 10:01:02'),
(106, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-18 20:11:45'),
(107, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-18 22:44:43'),
(108, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-19 05:14:19'),
(109, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-19 08:58:22'),
(110, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-19 20:15:27'),
(111, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-20 05:48:00'),
(112, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-20 21:04:05'),
(113, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-24 03:46:03'),
(114, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-24 20:12:19'),
(115, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-25 09:03:02'),
(116, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-25 20:09:02'),
(117, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-26 21:12:22'),
(118, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-27 00:51:10'),
(119, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-02-28 06:28:54'),
(120, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-02 06:16:47'),
(121, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-03 01:32:37'),
(122, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-03 20:10:25'),
(123, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-04 01:47:00'),
(124, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-04 08:20:40'),
(125, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-04 20:48:19'),
(126, 4, 'login_exitoso', 'Inicio de sesión exitoso', '::1', NULL, '2026-03-05 09:33:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil_instructor`
--

CREATE TABLE `perfil_instructor` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `especialidad` varchar(255) DEFAULT NULL COMMENT '├ürea de especializaci├│n',
  `titulo_academico` varchar(255) DEFAULT NULL COMMENT 'T├¡tulo acad├®mico principal',
  `institucion_titulo` varchar(255) DEFAULT NULL COMMENT 'Instituci├│n donde obtuvo el t├¡tulo',
  `anio_titulo` year(4) DEFAULT NULL COMMENT 'A├▒o de obtenci├│n del t├¡tulo',
  `certificaciones` text DEFAULT NULL COMMENT 'JSON array de certificaciones adicionales',
  `experiencia_anios` int(11) DEFAULT NULL COMMENT 'A├▒os de experiencia',
  `biografia` text DEFAULT NULL COMMENT 'Biograf├¡a corta del instructor',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `color` varchar(7) DEFAULT '#3498db',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `color`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(112, 'Febrero 1-28, 2026', '', '2026-02-01', '2026-02-28', '#3498db', 1, '2026-02-13 22:23:15', '2026-02-13 22:23:15'),
(113, 'Marzo 1-31, 2026', '', '2026-03-01', '2026-03-31', '#3498db', 1, '2026-02-15 22:47:08', '2026-02-15 22:47:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_usuario`
--

CREATE TABLE `permisos_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `permisos_custom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Permisos personalizados que sobreescriben el rol' CHECK (json_valid(`permisos_custom`)),
  `asignado_por` int(11) NOT NULL COMMENT 'Admin que asign├│ los permisos',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Lista de permisos del rol' CHECK (json_valid(`permisos`)),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `permisos`, `fecha_creacion`) VALUES
(10, 'administrador', 'Administrador con acceso total al sistema', '{\"grupos\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"categorias\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"periodos\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"estudiantes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"certificados\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"aprobar\",\"generar\",\"descargar\"],\"plantillas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"configuracion\":[\"ver\",\"editar\"],\"reportes\":[\"ver\",\"generar\",\"exportar\"]}', '2025-12-29 00:02:47'),
(11, 'instructor', 'Instructor con acceso a sus categorías y grupos asignados', '{\"grupos\":[\"ver\"],\"categorias\":[\"ver\"],\"periodos\":[\"ver\",\"crear\",\"editar\"],\"estudiantes\":[\"ver\",\"destacar\"],\"certificados\":[\"ver\",\"generar\",\"descargar\"],\"plantillas\":[\"ver\"],\"usuarios\":[],\"configuracion\":[],\"reportes\":[\"ver\",\"generar\"]}', '2025-12-29 00:02:47'),
(12, 'oficinista', 'Personal de oficina con permisos configurables por administrador', '{\"grupos\":[\"ver\",\"crear\",\"editar\"],\"categorias\":[\"ver\",\"crear\",\"editar\"],\"periodos\":[\"ver\",\"crear\",\"editar\"],\"estudiantes\":[\"ver\",\"crear\",\"editar\"],\"certificados\":[\"ver\",\"crear\",\"editar\"],\"plantillas\":[\"ver\",\"crear\",\"editar\"],\"usuarios\":[],\"configuracion\":[\"ver\"]}', '2025-12-29 00:02:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_usuario`
--

CREATE TABLE `sesiones_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_sesion` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `es_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `nombre_completo`, `cedula`, `telefono`, `direccion`, `foto`, `rol_id`, `es_superadmin`, `activo`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `token_recuperacion`, `token_expira`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(4, 'admin', 'admin@cce.local', '$2y$10$VESYiNg7E9u68RlOyWQeKue76QodY7e944bfC5yunONRVKOqVEosW', 'Administrador Principal', NULL, NULL, NULL, NULL, 10, 1, 1, '2026-03-05 04:33:46', 0, NULL, NULL, NULL, '2025-12-29 00:02:47', '2026-03-05 09:33:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificaciones`
--

CREATE TABLE `verificaciones` (
  `id` int(11) NOT NULL,
  `certificado_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_verificacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_grupo_activo` (`grupo_id`,`activo`),
  ADD KEY `idx_usar_plantilla_propia` (`usar_plantilla_propia`);

--
-- Indices de la tabla `categoria_estudiantes`
--
ALTER TABLE `categoria_estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_categoria_estudiante_periodo` (`categoria_id`,`estudiante_id`,`periodo_id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_periodo` (`periodo_id`);

--
-- Indices de la tabla `categoria_periodos`
--
ALTER TABLE `categoria_periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_categoria_periodo` (`categoria_id`,`periodo_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indices de la tabla `categoria_plantillas`
--
ALTER TABLE `categoria_plantillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_activa` (`es_activa`);

--
-- Indices de la tabla `certificados`
--
ALTER TABLE `certificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_grupo` (`grupo_id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_certificados_aprobado` (`aprobado`),
  ADD KEY `fk_certificados_aprobado_por` (`aprobado_por`),
  ADD KEY `idx_estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `certificados_aprobaciones`
--
ALTER TABLE `certificados_aprobaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aprobaciones_certificado` (`certificado_id`),
  ADD KEY `idx_aprobaciones_usuario` (`usuario_id`),
  ADD KEY `idx_aprobaciones_fecha` (`fecha_accion`);

--
-- Indices de la tabla `configuracion_plantillas`
--
ALTER TABLE `configuracion_plantillas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cedula` (`cedula`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_destacado` (`destacado`),
  ADD KEY `idx_representante_cedula` (`representante_cedula`),
  ADD KEY `idx_es_menor` (`es_menor`);

--
-- Indices de la tabla `estudiantes_destacados`
--
ALTER TABLE `estudiantes_destacados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_destacado` (`estudiante_id`,`instructor_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indices de la tabla `estudiantes_referencias`
--
ALTER TABLE `estudiantes_referencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `estudiante_certificados`
--
ALTER TABLE `estudiante_certificados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_certificado` (`certificado_id`);

--
-- Indices de la tabla `fuentes_personalizadas`
--
ALTER TABLE `fuentes_personalizadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nombre_archivo` (`nombre_archivo`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `grupo_periodos`
--
ALTER TABLE `grupo_periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grupo_periodo` (`grupo_id`,`periodo_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indices de la tabla `grupo_plantillas`
--
ALTER TABLE `grupo_plantillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indices de la tabla `instructor_categorias`
--
ALTER TABLE `instructor_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asignacion` (`usuario_id`,`categoria_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `idx_instructor_categorias_usuario` (`usuario_id`);

--
-- Indices de la tabla `instructor_grupos`
--
ALTER TABLE `instructor_grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asignacion` (`usuario_id`,`grupo_id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `idx_instructor_grupos_usuario` (`usuario_id`);

--
-- Indices de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `perfil_instructor`
--
ALTER TABLE `perfil_instructor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario` (`usuario_id`),
  ADD KEY `idx_perfil_instructor_usuario` (`usuario_id`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `permisos_usuario`
--
ALTER TABLE `permisos_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario` (`usuario_id`),
  ADD KEY `asignado_por` (`asignado_por`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_sesion` (`token_sesion`),
  ADD KEY `idx_token` (`token_sesion`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_usuarios_superadmin` (`es_superadmin`);

--
-- Indices de la tabla `verificaciones`
--
ALTER TABLE `verificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificado` (`certificado_id`),
  ADD KEY `idx_fecha` (`fecha_verificacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de la tabla `categoria_estudiantes`
--
ALTER TABLE `categoria_estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT de la tabla `categoria_periodos`
--
ALTER TABLE `categoria_periodos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `categoria_plantillas`
--
ALTER TABLE `categoria_plantillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `certificados`
--
ALTER TABLE `certificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=248;

--
-- AUTO_INCREMENT de la tabla `certificados_aprobaciones`
--
ALTER TABLE `certificados_aprobaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_plantillas`
--
ALTER TABLE `configuracion_plantillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `estudiantes_destacados`
--
ALTER TABLE `estudiantes_destacados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes_referencias`
--
ALTER TABLE `estudiantes_referencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `estudiante_certificados`
--
ALTER TABLE `estudiante_certificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `fuentes_personalizadas`
--
ALTER TABLE `fuentes_personalizadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de la tabla `grupo_periodos`
--
ALTER TABLE `grupo_periodos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `grupo_plantillas`
--
ALTER TABLE `grupo_plantillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `instructor_categorias`
--
ALTER TABLE `instructor_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `instructor_grupos`
--
ALTER TABLE `instructor_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT de la tabla `perfil_instructor`
--
ALTER TABLE `perfil_instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de la tabla `permisos_usuario`
--
ALTER TABLE `permisos_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `verificaciones`
--
ALTER TABLE `verificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categoria_periodos`
--
ALTER TABLE `categoria_periodos`
  ADD CONSTRAINT `categoria_periodos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categoria_periodos_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categoria_plantillas`
--
ALTER TABLE `categoria_plantillas`
  ADD CONSTRAINT `categoria_plantillas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `certificados`
--
ALTER TABLE `certificados`
  ADD CONSTRAINT `certificados_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_certificados_aprobado_por` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_certificados_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `certificados_aprobaciones`
--
ALTER TABLE `certificados_aprobaciones`
  ADD CONSTRAINT `certificados_aprobaciones_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificados_aprobaciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estudiantes_destacados`
--
ALTER TABLE `estudiantes_destacados`
  ADD CONSTRAINT `estudiantes_destacados_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estudiantes_destacados_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estudiantes_referencias`
--
ALTER TABLE `estudiantes_referencias`
  ADD CONSTRAINT `estudiantes_referencias_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupo_periodos`
--
ALTER TABLE `grupo_periodos`
  ADD CONSTRAINT `grupo_periodos_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grupo_periodos_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupo_plantillas`
--
ALTER TABLE `grupo_plantillas`
  ADD CONSTRAINT `grupo_plantillas_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `instructor_categorias`
--
ALTER TABLE `instructor_categorias`
  ADD CONSTRAINT `instructor_categorias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructor_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `instructor_grupos`
--
ALTER TABLE `instructor_grupos`
  ADD CONSTRAINT `instructor_grupos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructor_grupos_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD CONSTRAINT `log_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `perfil_instructor`
--
ALTER TABLE `perfil_instructor`
  ADD CONSTRAINT `perfil_instructor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permisos_usuario`
--
ALTER TABLE `permisos_usuario`
  ADD CONSTRAINT `permisos_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_usuario_ibfk_2` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  ADD CONSTRAINT `sesiones_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `verificaciones`
--
ALTER TABLE `verificaciones`
  ADD CONSTRAINT `verificaciones_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
