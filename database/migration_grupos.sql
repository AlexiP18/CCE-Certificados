-- Migración para agregar sistema de grupos de certificados

USE cce_certificados;

-- Tabla de grupos
CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'workshop',
    color VARCHAR(7) DEFAULT '#3498db',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1,
    INDEX idx_nombre (nombre),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna grupo_id a la tabla certificados
ALTER TABLE certificados 
ADD COLUMN grupo_id INT NULL AFTER id,
ADD CONSTRAINT fk_certificados_grupo 
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL,
ADD INDEX idx_grupo (grupo_id);

-- Insertar grupos predeterminados
INSERT INTO grupos (nombre, descripcion, icono, color) VALUES
('Talleres', 'Certificados de talleres y capacitaciones prácticas', 'workshop', '#3498db'),
('Cursos', 'Certificados de cursos y programas de formación', 'course', '#2ecc71'),
('Concursos', 'Certificados de participación en concursos y competencias', 'trophy', '#f39c12'),
('Seminarios', 'Certificados de asistencia a seminarios y conferencias', 'seminar', '#9b59b6'),
('Reconocimientos', 'Certificados de reconocimiento y méritos especiales', 'award', '#e74c3c');
