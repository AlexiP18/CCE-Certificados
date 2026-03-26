-- Migración: Agregar configuración de plantilla a categorías
-- Fecha: 2025-11-24
-- Descripción: Permite que cada categoría tenga su propia configuración de plantilla

USE cce_certificados;

-- Agregar columnas de configuración de plantilla a la tabla categorias
ALTER TABLE categorias
ADD COLUMN plantilla_archivo VARCHAR(255) DEFAULT NULL COMMENT 'Archivo de plantilla específica para esta categoría',
ADD COLUMN plantilla_fuente VARCHAR(100) DEFAULT 'Arial' COMMENT 'Fuente para el texto',
ADD COLUMN plantilla_tamanio_fuente INT DEFAULT 48 COMMENT 'Tamaño de fuente',
ADD COLUMN plantilla_color_texto VARCHAR(7) DEFAULT '#000000' COMMENT 'Color del texto en formato hex',
ADD COLUMN plantilla_variables_habilitadas TEXT DEFAULT NULL COMMENT 'Variables habilitadas (JSON: nombre, razon, qr, firma, fecha)',
ADD COLUMN plantilla_pos_nombre_x INT DEFAULT 400 COMMENT 'Posición X del nombre',
ADD COLUMN plantilla_pos_nombre_y INT DEFAULT 300 COMMENT 'Posición Y del nombre',
ADD COLUMN plantilla_pos_razon_x INT DEFAULT 400 COMMENT 'Posición X de la razón',
ADD COLUMN plantilla_pos_razon_y INT DEFAULT 360 COMMENT 'Posición Y de la razón',
ADD COLUMN plantilla_pos_qr_x INT DEFAULT 920 COMMENT 'Posición X del código QR',
ADD COLUMN plantilla_pos_qr_y INT DEFAULT 419 COMMENT 'Posición Y del código QR',
ADD COLUMN plantilla_pos_firma_x INT DEFAULT 800 COMMENT 'Posición X de la firma',
ADD COLUMN plantilla_pos_firma_y INT DEFAULT 850 COMMENT 'Posición Y de la firma',
ADD COLUMN plantilla_pos_fecha_x INT DEFAULT 400 COMMENT 'Posición X de la fecha',
ADD COLUMN plantilla_pos_fecha_y INT DEFAULT 420 COMMENT 'Posición Y de la fecha',
ADD COLUMN plantilla_tamanio_qr INT DEFAULT 150 COMMENT 'Tamaño del código QR',
ADD COLUMN plantilla_archivo_firma VARCHAR(255) DEFAULT NULL COMMENT 'Archivo de imagen de firma',
ADD COLUMN usar_plantilla_propia TINYINT(1) DEFAULT 0 COMMENT 'Si es 1, usa configuración propia; si es 0, hereda del grupo';

-- Índice para optimizar búsquedas
CREATE INDEX idx_usar_plantilla_propia ON categorias(usar_plantilla_propia);

-- Nota: Si una categoría tiene usar_plantilla_propia = 0, debe heredar la configuración del grupo al que pertenece
