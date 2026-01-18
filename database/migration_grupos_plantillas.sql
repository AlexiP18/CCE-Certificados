-- Migración para agregar configuración personalizada por grupo

USE cce_certificados;

-- Agregar campos a la tabla grupos para configuración personalizada
ALTER TABLE grupos 
ADD COLUMN plantilla VARCHAR(255) NULL AFTER color,
ADD COLUMN razon_defecto TEXT NULL AFTER plantilla,
ADD COLUMN firma_nombre VARCHAR(255) NULL AFTER razon_defecto,
ADD COLUMN firma_cargo VARCHAR(255) NULL AFTER firma_nombre,
ADD COLUMN firma_imagen VARCHAR(255) NULL AFTER firma_cargo,
ADD COLUMN fuente_nombre VARCHAR(100) DEFAULT 'Arial' AFTER firma_imagen,
ADD COLUMN tamanio_fuente INT DEFAULT 48 AFTER fuente_nombre,
ADD COLUMN color_texto VARCHAR(7) DEFAULT '#000000' AFTER tamanio_fuente,
ADD COLUMN posicion_nombre_x INT DEFAULT 400 AFTER color_texto,
ADD COLUMN posicion_nombre_y INT DEFAULT 300 AFTER posicion_nombre_x,
ADD COLUMN posicion_qr_x INT DEFAULT 920 AFTER posicion_nombre_y,
ADD COLUMN posicion_qr_y INT DEFAULT 419 AFTER posicion_qr_x;

-- Comentarios explicativos
-- plantilla: nombre del archivo de plantilla específico del grupo (si es NULL, usa la plantilla global)
-- razon_defecto: texto predeterminado para la razón del certificado
-- firma_nombre: nombre de quien firma
-- firma_cargo: cargo de quien firma
-- firma_imagen: archivo de imagen de la firma (opcional)
-- Los demás campos son para la configuración de posicionamiento igual que la tabla configuracion_plantillas
