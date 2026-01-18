<?php
/**
 * Script para crear la tabla estudiantes
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS estudiantes (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Tabla 'estudiantes' creada exitosamente.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
