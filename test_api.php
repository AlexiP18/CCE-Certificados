<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/database.php';
$conn = getConnection();
$grupo_id = 73;
$plantilla_id = null;
$response = ['success' => true];

try {
    if (!$plantilla_id) {
        $stmt = $conn->prepare("SELECT id, nombre, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = ? ORDER BY orden ASC");
        $stmt->execute([$grupo_id]);
        $response['plantillas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($response['plantillas'] as $p) {
            if ($p['es_activa']) {
                $plantilla_id = $p['id'];
                break;
            }
        }
        
        $stmt = $conn->prepare("
            SELECT g.nombre, 
                   (SELECT nombre FROM categorias WHERE grupo_id = g.id LIMIT 1) as categoria_nombre 
            FROM grupos g 
            WHERE g.id = ?
        ");
        $stmt->execute([$grupo_id]);
        $response['grupo'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("SELECT * FROM estudiantes LIMIT 1");
        $stmt->execute();
        $estudianteMock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nCompleto = 'Juan Pérez García';
        if ($estudianteMock) {
            $nCompleto = $estudianteMock['nombre'] ?? '';
            if (isset($estudianteMock['apellidos'])) {
                $nCompleto .= ' ' . $estudianteMock['apellidos'];
            } elseif (isset($estudianteMock['nombre_completo'])) {
                $nCompleto = $estudianteMock['nombre_completo'];
            }
        }
        $response['estudiante'] = ['nombre_completo' => trim($nCompleto) ?: 'Juan Pérez García'];
    }
    
    if ($plantilla_id) {
        $stmt = $conn->prepare("SELECT * FROM grupo_plantillas WHERE id = ? AND grupo_id = ?");
        $stmt->execute([$plantilla_id, $grupo_id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            if (empty($config['variables_habilitadas'])) {
                $config['variables_habilitadas'] = json_encode(['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado']);
            }
            if (!isset($config['posicion_destacado_x']) && isset($config['destacado_posicion_x'])) {
                $config['posicion_destacado_x'] = $config['destacado_posicion_x'];
                $config['posicion_destacado_y'] = $config['destacado_posicion_y'];
            }
            
            $config['plantilla_id'] = $config['id'];
            $response['config'] = $config;
        } else {
            echo "Plantilla no encontrada\n";
        }
    }
    echo json_encode($response);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
