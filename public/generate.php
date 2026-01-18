<?php
require_once '../includes/Auth.php';
require_once '../vendor/autoload.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();

use CCE\Certificate;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del formulario
    $data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'razon' => trim($_POST['razon'] ?? ''),
        'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
        'codigo' => !empty($_POST['codigo']) ? trim($_POST['codigo']) : null,
        'grupo_id' => !empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null,
        'categoria_id' => !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null
    ];
    
    // Crear certificado
    $certificate = new Certificate($pdo);
    $result = $certificate->create($data);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
