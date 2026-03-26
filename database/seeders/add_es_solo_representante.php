<?php
/**
 * Script para agregar campo es_solo_representante a la tabla estudiantes
 * Esto permite distinguir entre estudiantes activos y representantes que aún no son estudiantes
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

echo "Agregando campo es_solo_representante...\n";

try {
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM estudiantes LIKE 'es_solo_representante'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE estudiantes ADD COLUMN es_solo_representante TINYINT(1) DEFAULT 0 AFTER representante_id");
        echo "✓ Campo es_solo_representante agregado\n";
    } else {
        echo "• Campo es_solo_representante ya existe\n";
    }
    
    echo "\n✅ Proceso completado\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
