-- Base de datos para certificados CCE
CREATE DATABASE IF NOT EXISTS cce_certificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cce_certificados;

-- Tabla de certificados
CREATE TABLE certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    razon TEXT NOT NULL,
    fecha DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fechas_generacion JSON DEFAULT NULL COMMENT 'Historial de fechas de generación',
    archivo_imagen VARCHAR(255),
    archivo_pdf VARCHAR(255),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    INDEX idx_codigo (codigo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración de plantillas
CREATE TABLE configuracion_plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    archivo_plantilla VARCHAR(255) NOT NULL,
    fuente_nombre VARCHAR(100) DEFAULT 'Arial',
    tamanio_fuente INT DEFAULT 48,
    color_texto VARCHAR(7) DEFAULT '#000000',
    posicion_nombre_x INT DEFAULT 400,
    posicion_nombre_y INT DEFAULT 300,
    posicion_qr_x INT DEFAULT 50,
    posicion_qr_y INT DEFAULT 50,
    posicion_qr VARCHAR(20) DEFAULT 'bottom-right',
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar plantilla por defecto
INSERT INTO configuracion_plantillas (nombre, archivo_plantilla) 
VALUES ('Plantilla Predeterminada', 'default_template.png');

-- Tabla de registro de verificaciones
CREATE TABLE verificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificado_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_verificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (certificado_id) REFERENCES certificados(id) ON DELETE CASCADE,
    INDEX idx_certificado (certificado_id),
    INDEX idx_fecha (fecha_verificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NULL,
    nombre VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE NULL,
    celular VARCHAR(15) NULL,
    email VARCHAR(255) NULL,
    destacado TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cedula (cedula),
    INDEX idx_nombre (nombre),
    INDEX idx_destacado (destacado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de relación estudiantes-certificados
CREATE TABLE IF NOT EXISTS estudiante_certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    certificado_id INT NOT NULL,
    categoria_id INT NULL,
    grupo_id INT NULL,
    fecha_emision DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (certificado_id) REFERENCES certificados(id) ON DELETE CASCADE,
    INDEX idx_estudiante (estudiante_id),
    INDEX idx_certificado (certificado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de relación categoría-estudiantes (matriculación)
CREATE TABLE IF NOT EXISTS categoria_estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    periodo_id INT NULL,
    estudiante_id INT NOT NULL,
    fecha_matricula DATE NOT NULL,
    estado ENUM('activo', 'inactivo', 'completado') DEFAULT 'activo',
    notas TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_categoria_estudiante_periodo (categoria_id, estudiante_id, periodo_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_periodo (periodo_id),
    INDEX idx_estudiante (estudiante_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
