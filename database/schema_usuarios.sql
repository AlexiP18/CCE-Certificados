-- ============================================
-- SISTEMA DE AUTENTICACIÓN Y GESTIÓN DE USUARIOS
-- CCE Certificados
-- ============================================

USE cce_certificados;

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    permisos JSON COMMENT 'Lista de permisos del rol',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    token_recuperacion VARCHAR(255) NULL,
    token_expira DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones activas (opcional, para control de sesiones múltiples)
CREATE TABLE IF NOT EXISTS sesiones_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token_sesion),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de log de actividad
CREATE TABLE IF NOT EXISTS log_actividad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    datos_adicionales JSON,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTAR ROLES POR DEFECTO
-- ============================================

INSERT INTO roles (nombre, descripcion, permisos) VALUES 
('administrador', 'Acceso completo al sistema', JSON_OBJECT(
    'grupos', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'categorias', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'periodos', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'estudiantes', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'certificados', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar', 'generar', 'descargar'),
    'plantillas', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'usuarios', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'),
    'configuracion', JSON_ARRAY('ver', 'editar')
)),
('editor', 'Puede gestionar contenido pero no usuarios ni configuración', JSON_OBJECT(
    'grupos', JSON_ARRAY('ver', 'crear', 'editar'),
    'categorias', JSON_ARRAY('ver', 'crear', 'editar'),
    'periodos', JSON_ARRAY('ver', 'crear', 'editar'),
    'estudiantes', JSON_ARRAY('ver', 'crear', 'editar'),
    'certificados', JSON_ARRAY('ver', 'crear', 'editar', 'generar', 'descargar'),
    'plantillas', JSON_ARRAY('ver', 'editar'),
    'usuarios', JSON_ARRAY(),
    'configuracion', JSON_ARRAY('ver')
)),
('operador', 'Solo puede ver y generar certificados', JSON_OBJECT(
    'grupos', JSON_ARRAY('ver'),
    'categorias', JSON_ARRAY('ver'),
    'periodos', JSON_ARRAY('ver'),
    'estudiantes', JSON_ARRAY('ver'),
    'certificados', JSON_ARRAY('ver', 'generar', 'descargar'),
    'plantillas', JSON_ARRAY('ver'),
    'usuarios', JSON_ARRAY(),
    'configuracion', JSON_ARRAY()
)),
('visualizador', 'Solo puede ver información sin modificar', JSON_OBJECT(
    'grupos', JSON_ARRAY('ver'),
    'categorias', JSON_ARRAY('ver'),
    'periodos', JSON_ARRAY('ver'),
    'estudiantes', JSON_ARRAY('ver'),
    'certificados', JSON_ARRAY('ver', 'descargar'),
    'plantillas', JSON_ARRAY('ver'),
    'usuarios', JSON_ARRAY(),
    'configuracion', JSON_ARRAY()
));

-- ============================================
-- INSERTAR USUARIO ADMINISTRADOR POR DEFECTO
-- Contraseña: admin123 (CAMBIAR INMEDIATAMENTE)
-- ============================================

INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id) VALUES 
('admin', 'admin@cce.com', '$2y$10$k/v5rQlS907kw4Rf0S09he6L5COXdg/YmQFO8lswN6A2G3GsZU3dO', 'Administrador', 1);

-- Nota: El hash anterior corresponde a la contraseña 'admin123'
-- Para generar un nuevo hash usar: password_hash('tu_contraseña', PASSWORD_DEFAULT)
