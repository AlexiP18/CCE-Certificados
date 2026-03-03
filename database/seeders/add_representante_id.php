<?php
/**
 * Script para agregar campo representante_id a la tabla estudiantes
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

echo "Agregando campo representante_id...\n";

try {
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM estudiantes LIKE 'representante_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE estudiantes ADD COLUMN representante_id INT NULL AFTER representante_fecha_nacimiento");
        echo "✓ Campo representante_id agregado\n";
    } else {
        echo "• Campo representante_id ya existe\n";
    }
    
    // Actualizar estudiantes menores existentes para vincular con su representante si existe como estudiante
    echo "Vinculando menores con representantes existentes...\n";
    $pdo->exec("
        UPDATE estudiantes e
        INNER JOIN estudiantes rep ON rep.cedula = e.representante_cedula AND rep.activo = 1
        SET e.representante_id = rep.id
        WHERE e.es_menor = 1 AND e.representante_cedula IS NOT NULL AND e.representante_id IS NULL
    ");
    
    $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
    echo "✓ $affected estudiantes actualizados\n";
    
    echo "\n✅ Proceso completado\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
