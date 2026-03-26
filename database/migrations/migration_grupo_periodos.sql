-- Migración: Crear tabla de relación grupo-periodos
-- Fecha: 2025-11-28
-- Descripción: Permite asignar períodos específicos a cada grupo

USE cce_certificados;

-- Crear tabla de relación grupo-periodos
CREATE TABLE IF NOT EXISTS grupo_periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    periodo_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grupo_periodo (grupo_id, periodo_id),
    INDEX idx_grupo_activo (grupo_id, activo),
    INDEX idx_periodo_activo (periodo_id, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar grupos existentes: asignarles todos los períodos activos
INSERT IGNORE INTO grupo_periodos (grupo_id, periodo_id)
SELECT g.id, p.id 
FROM grupos g
CROSS JOIN periodos p
WHERE g.activo = 1 AND p.activo = 1;
