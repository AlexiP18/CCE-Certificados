-- =====================================================
-- MIGRACIÓN: Sistema de Roles v2
-- Fecha: 2024-12-19
-- Descripción: 
--   - Agrega campo es_superadmin a usuarios
--   - Crea roles: instructor, oficinista
--   - Crea tabla perfil_instructor para datos extendidos
--   - Agrega campos extendidos a usuarios
-- =====================================================

-- 1. Agregar campo es_superadmin a usuarios
ALTER TABLE usuarios 
ADD COLUMN es_superadmin TINYINT(1) NOT NULL DEFAULT 0 AFTER rol_id;

-- 2. Agregar campos extendidos de perfil a usuarios
ALTER TABLE usuarios 
ADD COLUMN cedula VARCHAR(20) NULL AFTER nombre_completo,
ADD COLUMN telefono VARCHAR(20) NULL AFTER cedula,
ADD COLUMN direccion TEXT NULL AFTER telefono,
ADD COLUMN foto VARCHAR(255) NULL AFTER direccion;

-- 3. Crear rol Instructor
INSERT INTO roles (nombre, descripcion, permisos) VALUES (
    'instructor',
    'Instructor con acceso a sus categorías y grupos asignados',
    JSON_OBJECT(
        'grupos', JSON_ARRAY('ver'),
        'categorias', JSON_ARRAY('ver'),
        'periodos', JSON_ARRAY('ver', 'crear', 'editar'),
        'estudiantes', JSON_ARRAY('ver', 'destacar'),
        'certificados', JSON_ARRAY('ver', 'generar', 'descargar'),
        'plantillas', JSON_ARRAY('ver'),
        'usuarios', JSON_ARRAY(),
        'configuracion', JSON_ARRAY(),
        'reportes', JSON_ARRAY('ver', 'generar')
    )
);

-- 4. Crear rol Oficinista (basado en editor, permisos configurables)
INSERT INTO roles (nombre, descripcion, permisos) VALUES (
    'oficinista',
    'Personal de oficina con permisos configurables por administrador',
    JSON_OBJECT(
        'grupos', JSON_ARRAY('ver', 'crear', 'editar'),
        'categorias', JSON_ARRAY('ver', 'crear', 'editar'),
        'periodos', JSON_ARRAY('ver', 'crear', 'editar'),
        'estudiantes', JSON_ARRAY('ver', 'crear', 'editar'),
        'certificados', JSON_ARRAY('ver', 'crear', 'editar'),
        'plantillas', JSON_ARRAY('ver', 'crear', 'editar'),
        'usuarios', JSON_ARRAY(),
        'configuracion', JSON_ARRAY('ver')
    )
);

-- 5. Crear tabla para perfil extendido de instructores
CREATE TABLE IF NOT EXISTS perfil_instructor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    especialidad VARCHAR(255) NULL COMMENT 'Área de especialización',
    titulo_academico VARCHAR(255) NULL COMMENT 'Título académico principal',
    institucion_titulo VARCHAR(255) NULL COMMENT 'Institución donde obtuvo el título',
    anio_titulo YEAR NULL COMMENT 'Año de obtención del título',
    certificaciones TEXT NULL COMMENT 'JSON array de certificaciones adicionales',
    experiencia_anios INT NULL COMMENT 'Años de experiencia',
    biografia TEXT NULL COMMENT 'Biografía corta del instructor',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Crear tabla para asignación de instructores a categorías
CREATE TABLE IF NOT EXISTS instructor_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'ID del instructor',
    categoria_id INT NOT NULL COMMENT 'ID de la categoría asignada',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (usuario_id, categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Crear tabla para asignación de instructores a grupos
CREATE TABLE IF NOT EXISTS instructor_grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'ID del instructor',
    grupo_id INT NOT NULL COMMENT 'ID del grupo asignado',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (usuario_id, grupo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Crear tabla para estudiantes destacados por instructor
CREATE TABLE IF NOT EXISTS estudiantes_destacados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    instructor_id INT NOT NULL COMMENT 'ID del usuario instructor que destacó',
    motivo VARCHAR(255) NULL COMMENT 'Motivo por el que se destaca',
    fecha_destacado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_destacado (estudiante_id, instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Crear tabla para permisos personalizados por usuario (oficinistas)
CREATE TABLE IF NOT EXISTS permisos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    permisos_custom JSON NOT NULL COMMENT 'Permisos personalizados que sobreescriben el rol',
    asignado_por INT NOT NULL COMMENT 'Admin que asignó los permisos',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Marcar al primer administrador como superadmin (si existe)
UPDATE usuarios u
INNER JOIN roles r ON u.rol_id = r.id
SET u.es_superadmin = 1
WHERE r.nombre = 'administrador'
ORDER BY u.id ASC
LIMIT 1;

-- 11. Crear índices para mejor rendimiento
CREATE INDEX idx_usuarios_superadmin ON usuarios(es_superadmin);
CREATE INDEX idx_perfil_instructor_usuario ON perfil_instructor(usuario_id);
CREATE INDEX idx_instructor_categorias_usuario ON instructor_categorias(usuario_id);
CREATE INDEX idx_instructor_grupos_usuario ON instructor_grupos(usuario_id);

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
-- Después de ejecutar, verificar con:
-- SELECT * FROM roles;
-- DESCRIBE usuarios;
-- SELECT id, username, nombre_completo, es_superadmin FROM usuarios WHERE rol_id = 1;
