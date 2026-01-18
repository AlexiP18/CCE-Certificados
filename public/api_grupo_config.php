<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar autenticación
Auth::requireAuth();

try {
    $pdo = getConnection();
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
            $stmt->execute([$id]);
            $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$grupo) {
                throw new Exception('Grupo no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'grupo' => $grupo
            ]);
            break;
            
        case 'update_config':
            $id = $_POST['grupo_id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            // Manejar subida de archivos
            $plantilla = null;
            $firma_imagen = null;
            
            $uploadDir = dirname(__DIR__) . '/assets/templates/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Subir plantilla
            if (isset($_FILES['plantilla']) && $_FILES['plantilla']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['plantilla']['name'], PATHINFO_EXTENSION);
                $plantilla = 'grupo_' . $id . '_template.' . $ext;
                $destino = $uploadDir . $plantilla;
                
                if (!move_uploaded_file($_FILES['plantilla']['tmp_name'], $destino)) {
                    throw new Exception('Error al subir la plantilla');
                }
            }
            
            // Subir firma
            $firmaDir = dirname(__DIR__) . '/assets/firmas/';
            if (!is_dir($firmaDir)) {
                mkdir($firmaDir, 0755, true);
            }
            
            if (isset($_FILES['firma_imagen']) && $_FILES['firma_imagen']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['firma_imagen']['name'], PATHINFO_EXTENSION);
                $firma_imagen = 'grupo_' . $id . '_firma.' . $ext;
                $destino = $firmaDir . $firma_imagen;
                
                if (!move_uploaded_file($_FILES['firma_imagen']['tmp_name'], $destino)) {
                    throw new Exception('Error al subir la firma');
                }
            }
            
            // Construir UPDATE query
            $campos = [
                'razon_defecto' => $_POST['razon_defecto'] ?? null,
                'firma_nombre' => $_POST['firma_nombre'] ?? null,
                'firma_cargo' => $_POST['firma_cargo'] ?? null,
                'fuente_nombre' => $_POST['fuente_nombre'] ?? 'Roboto-Regular',
                'tamanio_fuente' => intval($_POST['tamanio_fuente'] ?? 48),
                'color_texto' => $_POST['color_texto'] ?? '#000000',
                'posicion_nombre_x' => intval($_POST['posicion_nombre_x'] ?? 400),
                'posicion_nombre_y' => intval($_POST['posicion_nombre_y'] ?? 300),
                'posicion_razon_x' => intval($_POST['posicion_razon_x'] ?? 400),
                'posicion_razon_y' => intval($_POST['posicion_razon_y'] ?? 360),
                'posicion_qr_x' => intval($_POST['posicion_qr_x'] ?? 920),
                'posicion_qr_y' => intval($_POST['posicion_qr_y'] ?? 419),
                'posicion_firma_x' => intval($_POST['posicion_firma_x'] ?? 800),
                'posicion_firma_y' => intval($_POST['posicion_firma_y'] ?? 850),
                'posicion_fecha_x' => intval($_POST['posicion_fecha_x'] ?? 400),
                'posicion_fecha_y' => intval($_POST['posicion_fecha_y'] ?? 420),
                'tamanio_qr' => intval($_POST['tamanio_qr'] ?? 200),
                'tamanio_firma' => intval($_POST['tamanio_firma'] ?? 150),
                'variables_habilitadas' => $_POST['variables_habilitadas'] ?? '["nombre","razon","qr","firma","fecha"]',
                // Configuración de Razón
                'fuente_razon' => $_POST['fuente_razon'] ?? 'Roboto-Regular',
                'tamanio_razon' => intval($_POST['tamanio_razon'] ?? 24),
                'color_razon' => $_POST['color_razon'] ?? '#333333',
                'ancho_razon' => intval($_POST['ancho_razon'] ?? 600),
                // Configuración de Fecha
                'fuente_fecha' => $_POST['fuente_fecha'] ?? 'Roboto-Regular',
                'formato_fecha' => $_POST['formato_fecha'] ?? 'd de F de Y',
                'usar_fecha_especifica' => isset($_POST['usar_fecha_especifica']) ? 1 : 0,
                'fecha_especifica' => !empty($_POST['fecha_especifica']) ? $_POST['fecha_especifica'] : null,
                'tamanio_fecha' => intval($_POST['tamanio_fecha'] ?? 20),
                'color_fecha' => $_POST['color_fecha'] ?? '#333333'
            ];
            
            if ($plantilla) {
                $campos['plantilla'] = $plantilla;
            }
            
            if ($firma_imagen) {
                $campos['firma_imagen'] = $firma_imagen;
            }
            
            $sets = [];
            $valores = [];
            
            foreach ($campos as $campo => $valor) {
                $sets[] = "$campo = ?";
                $valores[] = $valor;
            }
            
            $valores[] = $id;
            
            $sql = "UPDATE grupos SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
