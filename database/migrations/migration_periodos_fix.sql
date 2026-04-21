-- Eliminar las tablas anteriores incorrectas
DROP TABLE IF EXISTS grupo_periodos;

-- Modificar la relación: los períodos deben estar asociados a categorías, no a grupos
-- Renombrar tabla periodos para mantener los datos
ALTER TABLE periodos CHANGE COLUMN id id INT AUTO_INCREMENT;

-- Crear tabla para relacionar categorías con períodos
CREATE TABLE IF NOT EXISTS categoria_periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    periodo_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_categoria_periodo (categoria_id, periodo_id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignar todas las categorías existentes al período por defecto "2025 - Año Completo"
INSERT INTO categoria_periodos (categoria_id, periodo_id, activo)
SELECT c.id, 1, 1
FROM categorias c
WHERE c.activo = 1
AND NOT EXISTS (
    SELECT 1 FROM categoria_periodos cp 
    WHERE cp.categoria_id = c.id AND cp.periodo_id = 1
);
