<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "=== Columns in 'grupos' Table ===\n";
    $stmt = $pdo->query("DESCRIBE grupos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);

    echo "\n=== Columns in 'grupo_plantillas' Table ===\n";
    $stmt = $pdo->query("DESCRIBE grupo_plantillas");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
