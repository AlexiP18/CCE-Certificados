-- Migración: Sistema de Periodos Académicos
-- Fecha: 2025-11-25

-- Tabla de periodos
CREATE TABLE IF NOT EXISTS periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla relacional: grupos en periodos
CREATE TABLE IF NOT EXISTS grupo_periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    periodo_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grupo_periodo (grupo_id, periodo_id),
    INDEX idx_grupo (grupo_id),
    INDEX idx_periodo (periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar periodo por defecto (actual)
INSERT INTO periodos (nombre, descripcion, fecha_inicio, fecha_fin, color) 
VALUES 
('2025 - Año Completo', 'Periodo general para el año 2025', '2025-01-01', '2025-12-31', '#3498db');

-- Asignar todos los grupos existentes al periodo por defecto
INSERT INTO grupo_periodos (grupo_id, periodo_id, activo)
SELECT id, 1, 1 FROM grupos WHERE activo = 1;

-- Verificar la creación
SELECT 'Tablas creadas correctamente' as status;
SELECT * FROM periodos;
SELECT COUNT(*) as grupos_asignados FROM grupo_periodos;
