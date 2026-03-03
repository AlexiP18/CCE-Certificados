<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    // Check main grupos table
    $stmt = $pdo->prepare("SELECT id, nombre, plantilla FROM grupos WHERE id = ?");
    $stmt->execute([73]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "=== Group 73 in 'grupos' Table ===\n";
    if ($group) {
        echo "ID: " . $group['id'] . "\n";
        echo "Nombre: " . $group['nombre'] . "\n";
        echo "Plantilla (Legacy): " . ($group['plantilla'] ? $group['plantilla'] : "NULL/Empty") . "\n";
    } else {
        echo "Group 73 not found.\n";
    }

    // Check grupo_plantillas table
    $stmt = $pdo->prepare("SELECT id, nombre, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = ?");
    $stmt->execute([73]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== Templates in 'grupo_plantillas' Table ===\n";
    if (count($templates) > 0) {
        foreach ($templates as $t) {
            echo "ID: " . $t['id'] . " | Nombre: " . $t['nombre'] . " | Archivo: " . $t['archivo'] . " | Activa: " . $t['es_activa'] . "\n";
            $filePath = __DIR__ . '/public/uploads/grupos/73/' . $t['archivo'];
            echo "  File path: $filePath\n";
            echo "  Exists: " . (file_exists($filePath) ? "YES" : "NO") . "\n";
        }
    } else {
        echo "No templates found for group 73.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
