<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/database.php';
$conn = getConnection();
try {
    $stmt = $conn->prepare("SELECT nombre, apellidos FROM estudiantes LIMIT 1");
    $stmt->execute();
    var_dump($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Estudiantes error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $conn->prepare("
        SELECT g.nombre, c.nombre as categoria_nombre 
        FROM grupos g 
        LEFT JOIN categorias c ON g.categoria_id = c.id 
        WHERE g.id = 73
    ");
    $stmt->execute();
    var_dump($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Grupos error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $conn->prepare("SELECT id, nombre, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = 73 ORDER BY orden ASC");
    $stmt->execute();
    var_dump($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Grupo Plantillas error: " . $e->getMessage() . "\n";
}
