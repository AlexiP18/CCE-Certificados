-- Migración: Agregar periodo_id a categoria_estudiantes
-- Fecha: 2025-12-07
-- Descripción: Vincula las matrículas de estudiantes a períodos específicos

-- Agregar columna periodo_id a categoria_estudiantes
ALTER TABLE categoria_estudiantes 
ADD COLUMN periodo_id INT NULL AFTER categoria_id;

-- Agregar índice para el período
ALTER TABLE categoria_estudiantes 
ADD INDEX idx_periodo (periodo_id);

-- Modificar la restricción única para incluir el período
-- Primero eliminar la restricción existente
ALTER TABLE categoria_estudiantes 
DROP INDEX unique_categoria_estudiante;

-- Crear nueva restricción única que incluye el período
ALTER TABLE categoria_estudiantes 
ADD UNIQUE KEY unique_categoria_estudiante_periodo (categoria_id, estudiante_id, periodo_id);

-- Actualizar registros existentes asignándoles el período más reciente de su categoría
UPDATE categoria_estudiantes ce
SET periodo_id = (
    SELECT cp.periodo_id 
    FROM categoria_periodos cp 
    WHERE cp.categoria_id = ce.categoria_id 
    AND cp.activo = 1
    ORDER BY cp.id DESC 
    LIMIT 1
)
WHERE ce.periodo_id IS NULL;

-- Si aún hay registros sin período, asignar el período más reciente del grupo
UPDATE categoria_estudiantes ce
JOIN categorias c ON ce.categoria_id = c.id
SET ce.periodo_id = (
    SELECT gp.periodo_id 
    FROM grupo_periodos gp 
    WHERE gp.grupo_id = c.grupo_id 
    AND gp.activo = 1
    ORDER BY gp.id DESC 
    LIMIT 1
)
WHERE ce.periodo_id IS NULL;

SELECT 'Migración completada: periodo_id agregado a categoria_estudiantes' AS resultado;
