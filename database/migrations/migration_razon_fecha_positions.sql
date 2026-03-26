-- Migración: Agregar posiciones para razón y fecha
-- Fecha: 2025-11-22

USE cce_certificados;

-- Agregar columnas de posición para razón
ALTER TABLE grupos 
ADD COLUMN IF NOT EXISTS posicion_razon_x INT DEFAULT 400,
ADD COLUMN IF NOT EXISTS posicion_razon_y INT DEFAULT 360;

-- Agregar columnas de posición para fecha
ALTER TABLE grupos 
ADD COLUMN IF NOT EXISTS posicion_fecha_x INT DEFAULT 400,
ADD COLUMN IF NOT EXISTS posicion_fecha_y INT DEFAULT 420;

-- Actualizar valores por defecto para registros existentes
UPDATE grupos 
SET 
    posicion_razon_x = 400,
    posicion_razon_y = 360,
    posicion_fecha_x = 400,
    posicion_fecha_y = 420
WHERE 
    posicion_razon_x IS NULL 
    OR posicion_razon_y IS NULL
    OR posicion_fecha_x IS NULL
    OR posicion_fecha_y IS NULL;
