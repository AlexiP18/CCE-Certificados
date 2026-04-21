-- Migración: Agregar soporte para representante legal (menores de edad)
-- Fecha: 2025-12-07

-- Agregar campos a la tabla estudiantes para representante legal
ALTER TABLE estudiantes
ADD COLUMN es_menor TINYINT(1) DEFAULT 0 AFTER email,
ADD COLUMN representante_nombre VARCHAR(255) NULL AFTER es_menor,
ADD COLUMN representante_cedula VARCHAR(20) NULL AFTER representante_nombre,
ADD COLUMN representante_celular VARCHAR(15) NULL AFTER representante_cedula,
ADD COLUMN representante_email VARCHAR(255) NULL AFTER representante_celular,
ADD COLUMN representante_fecha_nacimiento DATE NULL AFTER representante_email;

-- Índice para búsquedas por representante
ALTER TABLE estudiantes ADD INDEX idx_representante_cedula (representante_cedula);
ALTER TABLE estudiantes ADD INDEX idx_es_menor (es_menor);

-- Nota: Para estudiantes menores de edad:
-- - es_menor = 1
-- - nombre = nombre del estudiante menor
-- - fecha_nacimiento = fecha de nacimiento del menor (obligatorio para menores)
-- - cedula, celular, email pueden quedar NULL (los datos de contacto van en el representante)
-- - representante_* = datos del representante legal
