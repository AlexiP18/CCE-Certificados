-- Migración: Agregar columnas de configuración a grupo_plantillas
-- Esto permite que cada plantilla tenga su propia configuración de posiciones y estilos

ALTER TABLE grupo_plantillas
ADD COLUMN IF NOT EXISTS posicion_nombre_x INT DEFAULT 400,
ADD COLUMN IF NOT EXISTS posicion_nombre_y INT DEFAULT 300,
ADD COLUMN IF NOT EXISTS posicion_razon_x INT DEFAULT 400,
ADD COLUMN IF NOT EXISTS posicion_razon_y INT DEFAULT 360,
ADD COLUMN IF NOT EXISTS posicion_fecha_x INT DEFAULT 400,
ADD COLUMN IF NOT EXISTS posicion_fecha_y INT DEFAULT 420,
ADD COLUMN IF NOT EXISTS posicion_qr_x INT DEFAULT 920,
ADD COLUMN IF NOT EXISTS posicion_qr_y INT DEFAULT 419,
ADD COLUMN IF NOT EXISTS posicion_firma_x INT DEFAULT 800,
ADD COLUMN IF NOT EXISTS posicion_firma_y INT DEFAULT 850,
ADD COLUMN IF NOT EXISTS fuente_nombre VARCHAR(100) DEFAULT 'Roboto-Regular',
ADD COLUMN IF NOT EXISTS fuente_razon VARCHAR(100) DEFAULT 'Roboto-Regular',
ADD COLUMN IF NOT EXISTS fuente_fecha VARCHAR(100) DEFAULT 'Roboto-Regular',
ADD COLUMN IF NOT EXISTS tamanio_fuente INT DEFAULT 50,
ADD COLUMN IF NOT EXISTS tamanio_razon INT DEFAULT 24,
ADD COLUMN IF NOT EXISTS tamanio_fecha INT DEFAULT 20,
ADD COLUMN IF NOT EXISTS tamanio_qr INT DEFAULT 200,
ADD COLUMN IF NOT EXISTS tamanio_firma INT DEFAULT 200,
ADD COLUMN IF NOT EXISTS color_texto VARCHAR(7) DEFAULT '#000000',
ADD COLUMN IF NOT EXISTS color_razon VARCHAR(7) DEFAULT '#333333',
ADD COLUMN IF NOT EXISTS color_fecha VARCHAR(7) DEFAULT '#333333',
ADD COLUMN IF NOT EXISTS razon_defecto TEXT,
ADD COLUMN IF NOT EXISTS formato_fecha VARCHAR(50) DEFAULT 'd de F de Y',
ADD COLUMN IF NOT EXISTS variables_habilitadas TEXT;

-- Si la base de datos no soporta IF NOT EXISTS en ALTER TABLE, usar esta versión alternativa:
-- Primero verificar si las columnas existen antes de agregarlas
