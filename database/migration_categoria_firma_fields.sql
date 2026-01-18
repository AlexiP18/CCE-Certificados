-- Migración: Agregar campos de firma a categorías
-- Fecha: 2025-11-27
-- Descripción: Agrega campos para nombre y cargo de firma en categorías

USE cce_certificados;

-- Agregar columnas de firma a la tabla categorias
ALTER TABLE categorias
ADD COLUMN plantilla_firma_nombre VARCHAR(255) DEFAULT NULL COMMENT 'Nombre de quien firma el certificado',
ADD COLUMN plantilla_firma_cargo VARCHAR(255) DEFAULT NULL COMMENT 'Cargo de quien firma el certificado',
ADD COLUMN plantilla_tamanio_firma INT DEFAULT 150 COMMENT 'Tamaño de la imagen de firma';
