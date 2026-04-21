-- =====================================================
-- MIGRACIÓN: Sistema de Aprobación de Certificados
-- Fecha: 2024-12-23
-- Descripción: 
--   - Agrega campos para control de aprobación de certificados
--   - La primera generación requiere aprobación de admin
--   - Registra quién aprobó y cuándo
-- =====================================================

-- 1. Agregar campos de aprobación a la tabla certificados
ALTER TABLE certificados 
ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Si el certificado ha sido aprobado para generación' AFTER estado,
ADD COLUMN aprobado_por INT NULL COMMENT 'Usuario administrador que aprobó' AFTER aprobado,
ADD COLUMN fecha_aprobacion TIMESTAMP NULL COMMENT 'Fecha y hora de aprobación' AFTER aprobado_por,
ADD COLUMN requiere_aprobacion TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Si este certificado requiere aprobación antes de generarse' AFTER fecha_aprobacion;

-- 2. Agregar índice para búsquedas por estado de aprobación
CREATE INDEX idx_certificados_aprobado ON certificados(aprobado);

-- 3. Agregar FK a usuarios para quien aprobó
ALTER TABLE certificados 
ADD CONSTRAINT fk_certificados_aprobado_por 
FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- 4. Marcar los certificados existentes como aprobados (no requieren re-aprobación)
UPDATE certificados SET aprobado = 1, requiere_aprobacion = 0 WHERE id > 0;

-- 5. Crear tabla para historial de aprobaciones
CREATE TABLE IF NOT EXISTS certificados_aprobaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificado_id INT NOT NULL,
    usuario_id INT NOT NULL COMMENT 'Usuario que realizó la acción',
    accion ENUM('aprobar', 'rechazar', 'revocar') NOT NULL,
    comentario TEXT NULL,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (certificado_id) REFERENCES certificados(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Crear índices para el historial
CREATE INDEX idx_aprobaciones_certificado ON certificados_aprobaciones(certificado_id);
CREATE INDEX idx_aprobaciones_usuario ON certificados_aprobaciones(usuario_id);
CREATE INDEX idx_aprobaciones_fecha ON certificados_aprobaciones(fecha_accion);

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
-- Después de ejecutar, verificar con:
-- DESCRIBE certificados;
-- SELECT * FROM certificados WHERE aprobado = 0;
-- SHOW TABLES LIKE 'certificados_aprobaciones';
