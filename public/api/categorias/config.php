<?php
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

header('Content-Type: application/json');

// Verificar autenticación
Auth::requireAuth();

$action = $_REQUEST['action'] ?? '';
$pdo = getConnection();

try {
    switch ($action) {
        case 'get':
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado']);
                exit;
            }
            
            // Obtener información de la categoría con su grupo
            $stmt = $pdo->prepare("
                SELECT c.*, g.nombre as grupo_nombre, g.icono as grupo_icono, g.color as grupo_color,
                       g.firma_nombre as grupo_firma_nombre, g.firma_cargo as grupo_firma_cargo,
                       g.plantilla as grupo_plantilla, g.firma_imagen as grupo_firma_imagen
                FROM categorias c
                INNER JOIN grupos g ON c.grupo_id = g.id
                WHERE c.id = ? AND c.activo = 1
            ");
            $stmt->execute([$id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
                exit;
            }
            
            // Siempre incluir plantilla heredada del grupo como respaldo
            $categoria['plantilla_archivo_heredada'] = $categoria['grupo_plantilla'] ?? null;
            $categoria['plantilla_archivo_firma_heredada'] = $categoria['grupo_firma_imagen'] ?? null;
            
            // Si la categoría no usa plantilla propia, usar valores del grupo como referencia
            if (!$categoria['usar_plantilla_propia']) {
                $categoria['firma_nombre'] = $categoria['grupo_firma_nombre'] ?? '';
                $categoria['firma_cargo'] = $categoria['grupo_firma_cargo'] ?? '';
            }
            
            echo json_encode([
                'success' => true,
                'categoria' => $categoria
            ]);
            break;
            
        case 'update_config':
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $usar_plantilla_propia = isset($_POST['usar_plantilla_propia']) ? 1 : 0;
            
            // Debug logging
            error_log("update_config - categoria_id: $categoria_id, usar_plantilla_propia: $usar_plantilla_propia");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            if (empty($categoria_id)) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado']);
                exit;
            }
            
            // Verificar que la categoría existe y obtener datos actuales
            $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND activo = 1");
            $stmt->execute([$categoria_id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
                exit;
            }
            
            // Preparar datos para actualizar
            $updateData = [
                'usar_plantilla_propia' => $usar_plantilla_propia
            ];
            
            // Si usa plantilla propia, guardar toda la configuración
            if ($usar_plantilla_propia) {
                $updateData['plantilla_fuente'] = $_POST['fuente_nombre'] ?? 'Arial';
                $updateData['plantilla_tamanio_fuente'] = intval($_POST['tamanio_fuente'] ?? 48);
                $updateData['plantilla_color_texto'] = $_POST['color_texto'] ?? '#000000';
                $updateData['plantilla_variables_habilitadas'] = $_POST['variables_habilitadas'] ?? '["nombre","qr","firma","fecha","destacado"]';
                $updateData['plantilla_pos_nombre_x'] = intval($_POST['posicion_nombre_x'] ?? 400);
                $updateData['plantilla_pos_nombre_y'] = intval($_POST['posicion_nombre_y'] ?? 300);
                $updateData['plantilla_pos_razon_x'] = intval($_POST['posicion_razon_x'] ?? 400);
                $updateData['plantilla_pos_razon_y'] = intval($_POST['posicion_razon_y'] ?? 360);
                $updateData['plantilla_pos_qr_x'] = intval($_POST['posicion_qr_x'] ?? 920);
                $updateData['plantilla_pos_qr_y'] = intval($_POST['posicion_qr_y'] ?? 419);
                $updateData['plantilla_pos_firma_x'] = intval($_POST['posicion_firma_x'] ?? 800);
                $updateData['plantilla_pos_firma_y'] = intval($_POST['posicion_firma_y'] ?? 850);
                $updateData['plantilla_pos_fecha_x'] = intval($_POST['posicion_fecha_x'] ?? 400);
                $updateData['plantilla_pos_fecha_y'] = intval($_POST['posicion_fecha_y'] ?? 420);
                $updateData['plantilla_tamanio_qr'] = intval($_POST['tamanio_qr'] ?? 200);
                $updateData['plantilla_tamanio_firma'] = intval($_POST['tamanio_firma'] ?? 150);
                $updateData['plantilla_firma_nombre'] = $_POST['firma_nombre'] ?? '';
                $updateData['plantilla_firma_cargo'] = $_POST['firma_cargo'] ?? '';
                
                // Campos de Razón
                $updateData['plantilla_razon_defecto'] = $_POST['razon_defecto'] ?? '';
                $updateData['plantilla_tamanio_razon'] = intval($_POST['tamanio_razon'] ?? 24);
                $updateData['plantilla_color_razon'] = $_POST['color_razon'] ?? '#333333';
                
                // Campos de Fecha
                $updateData['plantilla_formato_fecha'] = $_POST['formato_fecha'] ?? 'd de F de Y';
                $updateData['plantilla_fecha_especifica'] = !empty($_POST['fecha_especifica']) ? $_POST['fecha_especifica'] : null;
                $updateData['plantilla_tamanio_fecha'] = intval($_POST['tamanio_fecha'] ?? 20);
                $updateData['plantilla_color_fecha'] = $_POST['color_fecha'] ?? '#333333';
                
                // Campos de Destacado
                $updateData['plantilla_pos_destacado_x'] = intval($_POST['posicion_destacado_x'] ?? 50);
                $updateData['plantilla_pos_destacado_y'] = intval($_POST['posicion_destacado_y'] ?? 50);
                $updateData['plantilla_tamanio_destacado'] = intval($_POST['tamanio_destacado'] ?? 100);
                $updateData['plantilla_destacado_tipo'] = $_POST['destacado_tipo'] ?? 'icono';
                $updateData['plantilla_destacado_icono'] = $_POST['destacado_icono'] ?? 'estrella';
                
                // Manejar archivo de plantilla
                if (isset($_FILES['plantilla']) && $_FILES['plantilla']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../assets/templates/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['plantilla']['name'], PATHINFO_EXTENSION);
                    $newFileName = 'categoria_' . $categoria_id . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['plantilla']['tmp_name'], $uploadPath)) {
                        $updateData['plantilla_archivo'] = $newFileName;
                    }
                }
                
                // Manejar archivo de firma
                if (isset($_FILES['firma_imagen']) && $_FILES['firma_imagen']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../assets/templates/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['firma_imagen']['name'], PATHINFO_EXTENSION);
                    $newFileName = 'firma_categoria_' . $categoria_id . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['firma_imagen']['tmp_name'], $uploadPath)) {
                        $updateData['plantilla_archivo_firma'] = $newFileName;
                    }
                }
                
                // Manejar archivo de destacado (sticker personalizado)
                if (isset($_FILES['destacado_imagen_input']) && $_FILES['destacado_imagen_input']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../assets/stickers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['destacado_imagen_input']['name'], PATHINFO_EXTENSION);
                    $newFileName = 'destacado_cat_' . $categoria_id . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['destacado_imagen_input']['tmp_name'], $uploadPath)) {
                        $updateData['plantilla_destacado_imagen'] = $newFileName;
                    }
                }
            } else {
                // Si no usa plantilla propia, NO borrar la configuración existente
                // Solo actualizar usar_plantilla_propia = 0 para que use la del grupo
                // La configuración propia se mantiene por si el usuario vuelve a activarla
            }
            
            // Construir query UPDATE
            $setClause = [];
            $params = [];
            
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = ?";
                $params[] = $value;
            }
            
            $params[] = $categoria_id;
            
            $sql = "UPDATE categorias SET " . implode(', ', $setClause) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuración guardada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al guardar la configuración'
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en api_categoria_config.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en api_categoria_config.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
