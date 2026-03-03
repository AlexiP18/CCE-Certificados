-- Migración: Agregar campos de Razón y Fecha a categorías
-- Fecha: 2025-12-01
-- Descripción: Agrega columnas para configurar razón y fecha en plantillas de categorías

USE cce_certificados;

-- Agregar columnas de Razón
ALTER TABLE categorias
ADD COLUMN IF NOT EXISTS plantilla_razon_defecto TEXT DEFAULT NULL COMMENT 'Texto predeterminado para la razón',
ADD COLUMN IF NOT EXISTS plantilla_tamanio_razon INT DEFAULT 24 COMMENT 'Tamaño de fuente para la razón',
ADD COLUMN IF NOT EXISTS plantilla_color_razon VARCHAR(7) DEFAULT '#333333' COMMENT 'Color del texto de la razón';

-- Agregar columnas de Fecha
ALTER TABLE categorias
ADD COLUMN IF NOT EXISTS plantilla_formato_fecha VARCHAR(50) DEFAULT 'd de F de Y' COMMENT 'Formato de fecha (usando tokens de PHP date)',
ADD COLUMN IF NOT EXISTS plantilla_fecha_especifica DATE DEFAULT NULL COMMENT 'Fecha específica (si se desea usar una fecha fija)',
ADD COLUMN IF NOT EXISTS plantilla_tamanio_fecha INT DEFAULT 20 COMMENT 'Tamaño de fuente para la fecha',
ADD COLUMN IF NOT EXISTS plantilla_color_fecha VARCHAR(7) DEFAULT '#333333' COMMENT 'Color del texto de la fecha';

-- Actualizar valores por defecto para registros existentes
UPDATE categorias 
SET 
    plantilla_razon_defecto = COALESCE(plantilla_razon_defecto, 'Por su destacada participación en el {grupo} de {categoria}.'),
    plantilla_tamanio_razon = COALESCE(plantilla_tamanio_razon, 24),
    plantilla_color_razon = COALESCE(plantilla_color_razon, '#333333'),
    plantilla_formato_fecha = COALESCE(plantilla_formato_fecha, 'd de F de Y'),
    plantilla_tamanio_fecha = COALESCE(plantilla_tamanio_fecha, 20),
    plantilla_color_fecha = COALESCE(plantilla_color_fecha, '#333333')
WHERE plantilla_razon_defecto IS NULL 
   OR plantilla_formato_fecha IS NULL;
