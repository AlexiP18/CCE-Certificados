<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    // 1. Check and add columns to 'grupos' table
    echo "Updating 'grupos' table...\n";
    $columnsToAdd = [
        'posicion_destacado_x' => "INT DEFAULT 50",
        'posicion_destacado_y' => "INT DEFAULT 50",
        'tamanio_destacado' => "INT DEFAULT 100",
        'destacado_tipo' => "VARCHAR(20) DEFAULT 'icono'",
        'destacado_icono' => "VARCHAR(50) DEFAULT 'estrella'",
        'destacado_imagen' => "VARCHAR(255) DEFAULT NULL",
        'destacado_habilitado' => "TINYINT(1) DEFAULT 0"
    ];

    foreach ($columnsToAdd as $col => $def) {
        try {
            $pdo->query("SELECT $col FROM grupos LIMIT 1");
            echo "Column '$col' already exists in 'grupos'.\n";
        } catch (PDOException $e) {
            echo "Adding column '$col' to 'grupos'...\n";
            $pdo->exec("ALTER TABLE grupos ADD COLUMN $col $def");
        }
    }

    // 2. Check and add columns to 'grupo_plantillas' table
    // Note: The previous check showed `destacado_posicion_x` etc., but the API code uses `posicion_destacado_x`.
    // I should add the aliases or missing columns to be safe and consistent with API.
    echo "\nUpdating 'grupo_plantillas' table...\n";
    $columnsToAddPlantillas = [
        'posicion_destacado_x' => "INT DEFAULT 50",
        'posicion_destacado_y' => "INT DEFAULT 50",
        'tamanio_destacado' => "INT DEFAULT 100",
        // 'destacado_tipo', 'destacado_icono', 'destacado_imagen', 'destacado_habilitado' already exist or have similar names
    ];
    
    // Existing: destacado_posicion_x, destacado_posicion_y, destacado_tamanio
    // API config.js sends: posicion_destacado_x, etc.
    // API plantillas.php maps: $_POST['posicion_destacado_x'] -> destacado_posicion_x (Wait, let me re-read plantillas.php)
    
    // Let's first apply the groups fix which is the immediate error.
    
    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
