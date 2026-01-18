-- Migración: Agregar configuración de sticker "Destacado" a grupo_plantillas
-- Esto permite configurar un sticker que aparece en los certificados de estudiantes destacados

ALTER TABLE grupo_plantillas
ADD COLUMN IF NOT EXISTS destacado_habilitado TINYINT(1) DEFAULT 0 COMMENT 'Si está habilitado el sticker de destacado',
ADD COLUMN IF NOT EXISTS destacado_tipo ENUM('icono', 'imagen') DEFAULT 'icono' COMMENT 'Tipo de sticker: icono predeterminado o imagen personalizada',
ADD COLUMN IF NOT EXISTS destacado_icono VARCHAR(50) DEFAULT 'estrella_dorada' COMMENT 'Nombre del icono predeterminado',
ADD COLUMN IF NOT EXISTS destacado_imagen VARCHAR(255) DEFAULT NULL COMMENT 'Ruta de imagen personalizada para destacado',
ADD COLUMN IF NOT EXISTS destacado_posicion_x INT DEFAULT 50 COMMENT 'Posición X del sticker',
ADD COLUMN IF NOT EXISTS destacado_posicion_y INT DEFAULT 50 COMMENT 'Posición Y del sticker',
ADD COLUMN IF NOT EXISTS destacado_tamanio INT DEFAULT 100 COMMENT 'Tamaño del sticker en píxeles';

-- Índice para búsqueda rápida de plantillas con destacado habilitado
-- CREATE INDEX idx_destacado_habilitado ON grupo_plantillas(destacado_habilitado);
