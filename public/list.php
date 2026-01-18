<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use CCE\Certificate;

header('Content-Type: application/json');

try {
    $certificate = new Certificate($pdo);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $grupoId = isset($_GET['grupo']) ? (int)$_GET['grupo'] : null;
    $categoriaId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
    
    $certificates = $certificate->getAll($limit, $offset, $grupoId, $categoriaId);
    
    echo json_encode([
        'success' => true,
        'data' => $certificates,
        'certificados' => $certificates
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
