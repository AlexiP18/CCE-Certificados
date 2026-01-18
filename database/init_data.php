<?php
/**
 * Script de inicialización de datos esenciales
 * Ejecutar después de vaciar la base de datos para crear:
 * - Roles del sistema
 * - Usuario administrador por defecto
 */

require_once '../config/database.php';

echo "<pre style='font-family: monospace; padding: 20px;'>\n";
echo "==============================================\n";
echo "Inicialización de Base de Datos CCE\n";
echo "==============================================\n\n";

try {
    $pdo = getConnection();
    
    // 1. Verificar/Crear tabla de roles
    echo "► Verificando tabla de roles...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL UNIQUE,
            descripcion TEXT,
            permisos JSON NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla roles verificada\n";
    
    // 2. Insertar roles si no existen
    echo "\n► Creando roles del sistema...\n";
    
    // Rol Administrador
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'administrador'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("
            INSERT INTO roles (nombre, descripcion, permisos) VALUES (
                'administrador',
                'Administrador con acceso total al sistema',
                '{\"grupos\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"categorias\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"periodos\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"estudiantes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"certificados\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"aprobar\",\"generar\",\"descargar\"],\"plantillas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"configuracion\":[\"ver\",\"editar\"],\"reportes\":[\"ver\",\"generar\",\"exportar\"]}'
            )
        ");
        echo "✓ Rol 'administrador' creado\n";
    } else {
        echo "• Rol 'administrador' ya existe\n";
    }
    
    // Rol Instructor
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'instructor'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("
            INSERT INTO roles (nombre, descripcion, permisos) VALUES (
                'instructor',
                'Instructor con acceso a sus categorías y grupos asignados',
                '{\"grupos\":[\"ver\"],\"categorias\":[\"ver\"],\"periodos\":[\"ver\",\"crear\",\"editar\"],\"estudiantes\":[\"ver\",\"destacar\"],\"certificados\":[\"ver\",\"generar\",\"descargar\"],\"plantillas\":[\"ver\"],\"usuarios\":[],\"configuracion\":[],\"reportes\":[\"ver\",\"generar\"]}'
            )
        ");
        echo "✓ Rol 'instructor' creado\n";
    } else {
        echo "• Rol 'instructor' ya existe\n";
    }
    
    // Rol Oficinista
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'oficinista'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("
            INSERT INTO roles (nombre, descripcion, permisos) VALUES (
                'oficinista',
                'Personal de oficina con permisos configurables por administrador',
                '{\"grupos\":[\"ver\",\"crear\",\"editar\"],\"categorias\":[\"ver\",\"crear\",\"editar\"],\"periodos\":[\"ver\",\"crear\",\"editar\"],\"estudiantes\":[\"ver\",\"crear\",\"editar\"],\"certificados\":[\"ver\",\"crear\",\"editar\"],\"plantillas\":[\"ver\",\"crear\",\"editar\"],\"usuarios\":[],\"configuracion\":[\"ver\"]}'
            )
        ");
        echo "✓ Rol 'oficinista' creado\n";
    } else {
        echo "• Rol 'oficinista' ya existe\n";
    }
    
    // 3. Verificar/Crear tabla de usuarios
    echo "\n► Verificando tabla de usuarios...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            nombre_completo VARCHAR(150) NOT NULL,
            rol_id INT NOT NULL,
            es_superadmin TINYINT(1) DEFAULT 0,
            cedula VARCHAR(20) NULL,
            telefono VARCHAR(20) NULL,
            direccion TEXT NULL,
            foto VARCHAR(255) NULL,
            activo TINYINT(1) DEFAULT 1,
            intentos_fallidos INT DEFAULT 0,
            bloqueado_hasta TIMESTAMP NULL,
            ultimo_login TIMESTAMP NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rol_id) REFERENCES roles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla usuarios verificada\n";
    
    // 4. Crear usuario administrador por defecto
    echo "\n► Creando usuario administrador...\n";
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // Obtener ID del rol administrador
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'administrador'");
        $stmt->execute();
        $rolAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rolAdmin) {
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, es_superadmin, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(['admin', 'admin@cce.local', $passwordHash, 'Administrador Principal', $rolAdmin['id'], 1, 1]);
            echo "✓ Usuario 'admin' creado (contraseña: admin123)\n";
        }
    } else {
        echo "• Usuario 'admin' ya existe\n";
    }
    
    // 5. Crear tabla de log de actividad si no existe
    echo "\n► Verificando tabla de log de actividad...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS log_actividad (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            accion VARCHAR(100) NOT NULL,
            descripcion TEXT,
            ip_address VARCHAR(45),
            datos_adicionales JSON,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla log_actividad verificada\n";
    
    // 6. Crear tablas adicionales necesarias
    echo "\n► Verificando tablas adicionales...\n";
    
    // Tabla de grupos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grupos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            icono VARCHAR(50) DEFAULT '📁',
            color VARCHAR(20) DEFAULT '#3498db',
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla grupos verificada\n";
    
    // Tabla de periodos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS periodos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            color VARCHAR(20) DEFAULT '#3498db',
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla periodos verificada\n";
    
    // Tabla de categorias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grupo_id INT NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            icono VARCHAR(50) DEFAULT '📚',
            color VARCHAR(20) DEFAULT '#3498db',
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla categorias verificada\n";
    
    // Tabla de estudiantes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estudiantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(200) NOT NULL,
            cedula VARCHAR(20),
            celular VARCHAR(20),
            email VARCHAR(100),
            fecha_nacimiento DATE,
            es_menor TINYINT(1) DEFAULT 0,
            representante_nombre VARCHAR(200),
            representante_cedula VARCHAR(20),
            representante_celular VARCHAR(20),
            representante_email VARCHAR(100),
            destacado TINYINT(1) DEFAULT 0,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla estudiantes verificada\n";
    
    // Tabla grupo_periodos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grupo_periodos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grupo_id INT NOT NULL,
            periodo_id INT NOT NULL,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
            FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_grupo_periodo (grupo_id, periodo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla grupo_periodos verificada\n";
    
    // Tabla categoria_periodos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categoria_periodos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria_id INT NOT NULL,
            periodo_id INT NOT NULL,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
            FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_categoria_periodo (categoria_id, periodo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla categoria_periodos verificada\n";
    
    // Tabla categoria_estudiantes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categoria_estudiantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria_id INT NOT NULL,
            estudiante_id INT NOT NULL,
            periodo_id INT NOT NULL,
            estado ENUM('activo', 'inactivo', 'completado') DEFAULT 'activo',
            fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
            FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
            FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_inscripcion (categoria_id, estudiante_id, periodo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla categoria_estudiantes verificada\n";
    
    // Tablas de asignación de instructor
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS instructor_grupos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            grupo_id INT NOT NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_asignacion (usuario_id, grupo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla instructor_grupos verificada\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS instructor_categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            categoria_id INT NOT NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
            UNIQUE KEY unique_asignacion (usuario_id, categoria_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla instructor_categorias verificada\n";
    
    // Tabla de permisos personalizados
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permisos_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            permisos_custom JSON NOT NULL,
            asignado_por INT NOT NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla permisos_usuario verificada\n";
    
    echo "\n==============================================\n";
    echo "✅ Inicialización completada exitosamente\n";
    echo "==============================================\n\n";
    echo "Credenciales de acceso:\n";
    echo "  Usuario: admin\n";
    echo "  Contraseña: admin123\n";
    echo "\n⚠️  IMPORTANTE: Cambia la contraseña después de iniciar sesión\n";
    echo "\n<a href='/cce-certificados/public/login.php' style='color: #3498db; font-size: 16px;'>→ Ir al Login</a>\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}

echo "</pre>";
