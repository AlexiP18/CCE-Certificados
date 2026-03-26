<?php
/**
 * Script de instalación para migración de aprobación de certificados
 * Ejecutar una sola vez para agregar campos de aprobación
 */

require_once '../config/database.php';

echo "<pre>\n";
echo "==============================================\n";
echo "Migración: Sistema de Aprobación de Certificados\n";
echo "==============================================\n\n";

try {
    $pdo = getConnection();
    
    // 1. Verificar si ya existe la columna aprobado
    $stmt = $pdo->query("SHOW COLUMNS FROM certificados LIKE 'aprobado'");
    if ($stmt->rowCount() > 0) {
        echo "✓ La columna 'aprobado' ya existe en certificados\n";
    } else {
        echo "► Agregando campos de aprobación a certificados...\n";
        $pdo->exec("
            ALTER TABLE certificados 
            ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Si el certificado ha sido aprobado para generación' AFTER estado,
            ADD COLUMN aprobado_por INT NULL COMMENT 'Usuario administrador que aprobó' AFTER aprobado,
            ADD COLUMN fecha_aprobacion TIMESTAMP NULL COMMENT 'Fecha y hora de aprobación' AFTER aprobado_por,
            ADD COLUMN requiere_aprobacion TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Si este certificado requiere aprobación antes de generarse' AFTER fecha_aprobacion
        ");
        echo "✓ Campos de aprobación agregados\n";
    }
    
    // 2. Verificar índice
    $stmt = $pdo->query("SHOW INDEX FROM certificados WHERE Key_name = 'idx_certificados_aprobado'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Índice idx_certificados_aprobado ya existe\n";
    } else {
        echo "► Creando índice para búsquedas por aprobación...\n";
        $pdo->exec("CREATE INDEX idx_certificados_aprobado ON certificados(aprobado)");
        echo "✓ Índice creado\n";
    }
    
    // 3. Verificar FK (puede fallar si ya existe)
    try {
        $pdo->exec("
            ALTER TABLE certificados 
            ADD CONSTRAINT fk_certificados_aprobado_por 
            FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        ");
        echo "✓ Foreign key agregada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Foreign key ya existe\n";
        } else {
            echo "⚠ Advertencia FK: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Marcar certificados existentes como aprobados
    $stmt = $pdo->query("SELECT COUNT(*) FROM certificados WHERE aprobado = 0 AND requiere_aprobacion = 1");
    $pendientes = $stmt->fetchColumn();
    if ($pendientes > 0) {
        echo "► Marcando {$pendientes} certificados existentes como aprobados...\n";
        $pdo->exec("UPDATE certificados SET aprobado = 1, requiere_aprobacion = 0 WHERE id > 0");
        echo "✓ Certificados existentes marcados\n";
    } else {
        echo "✓ No hay certificados pendientes de marcar\n";
    }
    
    // 5. Crear tabla de historial de aprobaciones
    $stmt = $pdo->query("SHOW TABLES LIKE 'certificados_aprobaciones'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabla certificados_aprobaciones ya existe\n";
    } else {
        echo "► Creando tabla de historial de aprobaciones...\n";
        $pdo->exec("
            CREATE TABLE certificados_aprobaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                certificado_id INT NOT NULL,
                usuario_id INT NOT NULL COMMENT 'Usuario que realizó la acción',
                accion ENUM('aprobar', 'rechazar', 'revocar') NOT NULL,
                comentario TEXT NULL,
                fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (certificado_id) REFERENCES certificados(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Tabla de historial creada\n";
        
        // Crear índices
        $pdo->exec("CREATE INDEX idx_aprobaciones_certificado ON certificados_aprobaciones(certificado_id)");
        $pdo->exec("CREATE INDEX idx_aprobaciones_usuario ON certificados_aprobaciones(usuario_id)");
        $pdo->exec("CREATE INDEX idx_aprobaciones_fecha ON certificados_aprobaciones(fecha_accion)");
        echo "✓ Índices de historial creados\n";
    }
    
    echo "\n==============================================\n";
    echo "✅ Migración completada exitosamente\n";
    echo "==============================================\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error en la migración: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "</pre>";
