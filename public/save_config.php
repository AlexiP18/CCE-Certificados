<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $configId = intval($_POST['config_id'] ?? 0);
    
    if (!$configId) {
        throw new Exception('ID de configuración no válido');
    }
    
    // Validar y sanitizar datos
    $data = [
        'fuente_nombre' => trim($_POST['fuente_nombre'] ?? ''),
        'tamanio_fuente' => intval($_POST['tamanio_fuente'] ?? 48),
        'color_texto' => $_POST['color_texto'] ?? '#000000',
        'posicion_nombre_x' => intval($_POST['posicion_nombre_x'] ?? 400),
        'posicion_nombre_y' => intval($_POST['posicion_nombre_y'] ?? 300),
        'posicion_qr_x' => intval($_POST['posicion_qr_x'] ?? 50),
        'posicion_qr_y' => intval($_POST['posicion_qr_y'] ?? 50),
        'posicion_qr' => $_POST['posicion_qr'] ?? 'bottom-right'
    ];
    
    // Validaciones
    if ($data['tamanio_fuente'] < 10 || $data['tamanio_fuente'] > 200) {
        throw new Exception('Tamaño de fuente debe estar entre 10 y 200');
    }
    
    if (!preg_match('/^#[0-9A-F]{6}$/i', $data['color_texto'])) {
        throw new Exception('Color de texto no válido');
    }
    
    $validPositions = ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'];
    if (!in_array($data['posicion_qr'], $validPositions)) {
        throw new Exception('Posición del QR no válida');
    }
    
    // Actualizar configuración
    $stmt = $pdo->prepare("
        UPDATE configuracion_plantillas 
        SET fuente_nombre = ?,
            tamanio_fuente = ?,
            color_texto = ?,
            posicion_nombre_x = ?,
            posicion_nombre_y = ?,
            posicion_qr_x = ?,
            posicion_qr_y = ?,
            posicion_qr = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['fuente_nombre'] ?: null,
        $data['tamanio_fuente'],
        $data['color_texto'],
        $data['posicion_nombre_x'],
        $data['posicion_nombre_y'],
        $data['posicion_qr_x'],
        $data['posicion_qr_y'],
        $data['posicion_qr'],
        $configId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración actualizada correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
