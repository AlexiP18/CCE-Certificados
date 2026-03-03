-- Migración: Sistema de categorías por grupo
-- Fecha: 2025-11-23

USE cce_certificados;

-- Crear tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT '📚',
    color VARCHAR(7) DEFAULT '#3498db',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    INDEX idx_grupo_activo (grupo_id, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna categoria_id a la tabla certificados
ALTER TABLE certificados 
ADD COLUMN categoria_id INT DEFAULT NULL AFTER grupo_id,
ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
ADD INDEX idx_categoria (categoria_id);

-- Insertar categorías de ejemplo para el grupo de Talleres (ID 4)
INSERT INTO categorias (grupo_id, nombre, descripcion, icono, color) VALUES
(4, 'Violín', 'Taller de violín clásico y contemporáneo', '🎻', '#e74c3c'),
(4, 'Piano', 'Taller de piano para todos los niveles', '🎹', '#3498db'),
(4, 'Danza', 'Taller de danza folklórica y contemporánea', '💃', '#9b59b6'),
(4, 'Instrumentos Andinos', 'Taller de charango, quena y zampoña', '🎺', '#e67e22'),
(4, 'Percusión', 'Taller de percusión latina y africana', '🥁', '#1abc9c');
