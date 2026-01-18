<?php
require_once dirname(__DIR__) . '/config/database.php';

// Usar $pdo que es la variable definida en database.php
$conn = $pdo;

try {
    // Verificar columnas existentes
    $stmt = $conn->query('DESCRIBE grupo_plantillas');
    $columns = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    echo "Columnas actuales:\n";
    print_r($columns);
    
    // Verificar si existen las columnas de destacado
    $destacadoColumns = ['destacado_habilitado', 'destacado_tipo', 'destacado_icono', 
                         'destacado_imagen', 'destacado_posicion_x', 'destacado_posicion_y', 
                         'destacado_tamanio'];
    
    echo "\n\nColumnas de destacado:\n";
    foreach ($destacadoColumns as $col) {
        $exists = in_array($col, $columns) ? '✓ Existe' : '✗ NO existe';
        echo "  $col: $exists\n";
    }
    
    // Si faltan columnas, agregarlas
    $missingColumns = array_diff($destacadoColumns, $columns);
    if (!empty($missingColumns)) {
        echo "\n\nAgregando columnas faltantes...\n";
        
        $alterStatements = [
            'destacado_habilitado' => "ADD COLUMN destacado_habilitado TINYINT(1) DEFAULT 0",
            'destacado_tipo' => "ADD COLUMN destacado_tipo VARCHAR(20) DEFAULT 'icono'",
            'destacado_icono' => "ADD COLUMN destacado_icono VARCHAR(50) DEFAULT 'estrella'",
            'destacado_imagen' => "ADD COLUMN destacado_imagen VARCHAR(255) DEFAULT NULL",
            'destacado_posicion_x' => "ADD COLUMN destacado_posicion_x INT DEFAULT 50",
            'destacado_posicion_y' => "ADD COLUMN destacado_posicion_y INT DEFAULT 50",
            'destacado_tamanio' => "ADD COLUMN destacado_tamanio INT DEFAULT 100"
        ];
        
        foreach ($missingColumns as $col) {
            if (isset($alterStatements[$col])) {
                $sql = "ALTER TABLE grupo_plantillas " . $alterStatements[$col];
                echo "  Ejecutando: $sql\n";
                $conn->exec($sql);
                echo "  ✓ Columna $col agregada\n";
            }
        }
    }
    
    // Actualizar valores por defecto incorrectos
    $stmt = $conn->query("SELECT id, destacado_icono FROM grupo_plantillas WHERE destacado_icono = 'estrella_dorada' OR destacado_icono IS NULL");
    $toUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($toUpdate)) {
        echo "\n\nActualizando valores por defecto de destacado_icono a 'estrella'...\n";
        $conn->exec("UPDATE grupo_plantillas SET destacado_icono = 'estrella' WHERE destacado_icono = 'estrella_dorada' OR destacado_icono IS NULL OR destacado_icono = ''");
        echo "  ✓ " . count($toUpdate) . " registros actualizados\n";
    }
    
    echo "\n\n¡Migración completada!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
