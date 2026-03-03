<?php
/**
 * API para gestionar plantillas múltiples de grupos
 */
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
Auth::requireAuth();

try {
    $conn = getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$grupo_id = $_GET['grupo_id'] ?? $_POST['grupo_id'] ?? null;

if (!$grupo_id) {
    echo json_encode(['success' => false, 'message' => 'ID de grupo requerido']);
    exit;
}

switch ($action) {
    case 'list':
        // Listar todas las plantillas del grupo
        try {
            $stmt = $conn->prepare("
                SELECT id, nombre, archivo, es_activa, orden, fecha_creacion 
                FROM grupo_plantillas 
                WHERE grupo_id = ? 
                ORDER BY orden ASC, fecha_creacion DESC
            ");
            $stmt->execute([$grupo_id]);
            $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'plantillas' => $plantillas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'upload':
        // Subir nueva plantilla
        if (!isset($_FILES['plantilla']) || $_FILES['plantilla']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
            exit;
        }
        
        $file = $_FILES['plantilla'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
            exit;
        }
        
        // Crear directorio si no existe
        $uploadDir = '../../uploads/grupos/' . $grupo_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'plantilla_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
            exit;
        }
        
        try {
            // Obtener el máximo orden actual
            $stmt = $conn->prepare("SELECT MAX(orden) as max_orden FROM grupo_plantillas WHERE grupo_id = ?");
            $stmt->execute([$grupo_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoOrden = ($result['max_orden'] ?? -1) + 1;
            
            // Insertar en la base de datos
            $nombre = $_POST['nombre'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
            $stmt = $conn->prepare("
                INSERT INTO grupo_plantillas (grupo_id, nombre, archivo, orden, es_activa) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$grupo_id, $nombre, $filename, $nuevoOrden]);
            
            $newId = $conn->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Plantilla subida correctamente',
                'plantilla' => [
                    'id' => $newId,
                    'nombre' => $nombre,
                    'archivo' => $filename,
                    'es_activa' => 0,
                    'orden' => $nuevoOrden
                ]
            ]);
        } catch (Exception $e) {
            // Si falla la BD, eliminar el archivo
            unlink($filepath);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'add_default':
        // Agrega una nueva instancia de la plantilla por defecto (sistema)
        try {
            // Desactivar plantillas previas
            $stmt = $conn->prepare("UPDATE grupo_plantillas SET es_activa = 0 WHERE grupo_id = ?");
            $stmt->execute([$grupo_id]);
            
            // Obtener el máximo orden actual
            $stmt = $conn->prepare("SELECT MAX(orden) as max_orden FROM grupo_plantillas WHERE grupo_id = ?");
            $stmt->execute([$grupo_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoOrden = ($result['max_orden'] ?? -1) + 1;
            
            // Insertar plantilla (archivo vacío hace que el UI use default_template.png)
            $stmt = $conn->prepare("
                INSERT INTO grupo_plantillas (grupo_id, nombre, archivo, orden, es_activa) 
                VALUES (?, 'Plantilla del Sistema', '', ?, 1)
            ");
            $stmt->execute([$grupo_id, $nuevoOrden]);
            
            echo json_encode(['success' => true, 'message' => 'Plantilla por defecto agregada y activada']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'set_active':
        // Establecer una plantilla como activa
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        try {
            // Desactivar todas las plantillas del grupo
            $stmt = $conn->prepare("UPDATE grupo_plantillas SET es_activa = 0 WHERE grupo_id = ?");
            $stmt->execute([$grupo_id]);
            
            // Activar la plantilla seleccionada
            $stmt = $conn->prepare("UPDATE grupo_plantillas SET es_activa = 1 WHERE id = ? AND grupo_id = ?");
            $stmt->execute([$plantilla_id, $grupo_id]);
            
            // Obtener el nombre del archivo para actualizar también en la tabla grupos
            $stmt = $conn->prepare("SELECT archivo FROM grupo_plantillas WHERE id = ?");
            $stmt->execute([$plantilla_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plantilla) {
                // Actualizar la plantilla activa en la tabla grupos
                $stmt = $conn->prepare("UPDATE grupos SET plantilla = ? WHERE id = ?");
                $stmt->execute([$plantilla['archivo'], $grupo_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Plantilla activada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'delete':
        // Eliminar una plantilla
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        try {
            // Obtener información de la plantilla
            $stmt = $conn->prepare("SELECT archivo, es_activa FROM grupo_plantillas WHERE id = ? AND grupo_id = ?");
            $stmt->execute([$plantilla_id, $grupo_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plantilla) {
                echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
                exit;
            }
            
            // Eliminar de la base de datos
            $stmt = $conn->prepare("DELETE FROM grupo_plantillas WHERE id = ? AND grupo_id = ?");
            $stmt->execute([$plantilla_id, $grupo_id]);
            
            // Eliminar archivo físico
            $filepath = '../../uploads/grupos/' . $grupo_id . '/' . $plantilla['archivo'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Si era la plantilla activa, limpiar en grupos
            if ($plantilla['es_activa']) {
                $stmt = $conn->prepare("UPDATE grupos SET plantilla = NULL WHERE id = ?");
                $stmt->execute([$grupo_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Plantilla eliminada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'reorder':
        // Reordenar plantillas
        $orden = json_decode($_POST['orden'] ?? '[]', true);
        
        if (empty($orden)) {
            echo json_encode(['success' => false, 'message' => 'Orden no especificado']);
            exit;
        }
        
        try {
            foreach ($orden as $index => $plantilla_id) {
                $stmt = $conn->prepare("UPDATE grupo_plantillas SET orden = ? WHERE id = ? AND grupo_id = ?");
                $stmt->execute([$index, $plantilla_id, $grupo_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'get_config':
        // Obtener configuración de una plantilla específica o inicialización general
        $plantilla_id = $_GET['plantilla_id'] ?? null;
        
        $response = ['success' => true];
        
        try {
            // 1. Obtener todas las plantillas del grupo SIEMPRE
            $stmt = $conn->prepare("SELECT id, nombre, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = ? ORDER BY orden ASC");
            $stmt->execute([$grupo_id]);
            $response['plantillas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si piden explícitamente el sistema, no auto-resolver la activa
            if ($plantilla_id === 'system') {
                $plantilla_id = null; // Lo volvemos nulo para que el bloque de abajo reciba config vacia
            } 
            // Si no se especifica ID (carga inicial), buscar si hay una activa por defecto
            else if (!$plantilla_id) {
                // Determinar la plantilla activa
                foreach ($response['plantillas'] as $p) {
                    if ($p['es_activa']) {
                        $plantilla_id = $p['id'];
                        break;
                    }
                }
            }
                
            // 3. Obtener info del grupo y estudiante de prueba SIEMPRE
            $stmt = $conn->prepare("SELECT * FROM grupos WHERE id = ?");
            $stmt->execute([$grupo_id]);
            $grupoMock = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['grupo'] = [
                'nombre' => $grupoMock ? $grupoMock['nombre'] : 'Nombre del Grupo',
                'categoria_nombre' => 'Categoría de Ejemplo'
            ];
            
            // Flexible mock student lookup handles both new and old schemas safely
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
            
            // Si hay una plantilla resolvida (ya sea pasada o la activa encontrada), obtener su config
            if ($plantilla_id) {
                // Use SELECT * so we don't crash if new refactoring removes obsolete columns
                $stmt = $conn->prepare("SELECT * FROM grupo_plantillas WHERE id = ? AND grupo_id = ?");
                $stmt->execute([$plantilla_id, $grupo_id]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    if (empty($config['variables_habilitadas'])) {
                        $config['variables_habilitadas'] = json_encode(['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado']);
                    }
                    // Map legacy column names if they exist inside the DB so JS logic handles it seamlessly
                    if (!isset($config['posicion_destacado_x']) && isset($config['destacado_posicion_x'])) {
                        $config['posicion_destacado_x'] = $config['destacado_posicion_x'];
                        $config['posicion_destacado_y'] = $config['destacado_posicion_y'];
                    }
                    
                    $config['plantilla_id'] = $config['id']; // Important for the frontend state
                    $response['config'] = $config;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
                    exit;
                }
            } else {
                 // Es la plantilla del sistema (null o 'system')
                 // Cargar config desde la tabla 'grupos'
                 $stmt = $conn->prepare("SELECT * FROM grupos WHERE id = ?");
                 $stmt->execute([$grupo_id]);
                 $grupoConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                 
                 if ($grupoConfig) {
                     if (empty($grupoConfig['variables_habilitadas'])) {
                         $grupoConfig['variables_habilitadas'] = json_encode(['nombre', 'razon', 'fecha', 'qr', 'firma']);
                     }
                     // Map schema column names so JS logic handles it seamlessly
                     if (!isset($grupoConfig['posicion_destacado_x']) && isset($grupoConfig['destacado_posicion_x'])) {
                         $grupoConfig['posicion_destacado_x'] = $grupoConfig['destacado_posicion_x'];
                         $grupoConfig['posicion_destacado_y'] = $grupoConfig['destacado_posicion_y'];
                     }
                     
                     $grupoConfig['plantilla_id'] = null; // system template
                     $response['config'] = $grupoConfig;
                 }
            }
            
            echo json_encode($response);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error de Base de Datos: ' . $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    
    case 'save_config':
        // Guardar configuración de una plantilla específica
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        $isSystem = empty($plantilla_id) || $plantilla_id === 'system';
        
        try {
            // Manejar subida de imagen personalizada de destacado
            $destacadoImagenFilename = $_POST['destacado_imagen_actual'] ?? null;
            
            if (isset($_FILES['destacado_imagen']) && $_FILES['destacado_imagen']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__) . '/uploads/stickers/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['destacado_imagen'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $newFilename = 'sticker_' . $grupo_id . '_' . ($isSystem ? 'sys' : $plantilla_id) . '_' . time() . '.' . $extension;
                    $destPath = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $destacadoImagenFilename = $newFilename;
                    }
                }
            }
            
            if ($isSystem) {
                // The 'grupos' table has 'posicion_destacado_x' (not 'destacado_posicion_x')
                // and does NOT have 'alineacion_razon'
                $sql = "UPDATE grupos SET
                    posicion_nombre_x = ?, posicion_nombre_y = ?,
                    posicion_razon_x = ?, posicion_razon_y = ?,
                    posicion_fecha_x = ?, posicion_fecha_y = ?,
                    posicion_qr_x = ?, posicion_qr_y = ?,
                    posicion_firma_x = ?, posicion_firma_y = ?,
                    fuente_nombre = ?, formato_nombre = ?,
                    fuente_razon = ?, fuente_fecha = ?,
                    tamanio_fuente = ?, tamanio_razon = ?, tamanio_fecha = ?,
                    tamanio_qr = ?, tamanio_firma = ?,
                    color_texto = ?, color_razon = ?, color_fecha = ?,
                    razon_defecto = ?, formato_fecha = ?, variables_habilitadas = ?,
                    ancho_razon = ?,
                    destacado_habilitado = ?, destacado_tipo = ?, destacado_icono = ?, destacado_imagen = ?,
                    posicion_destacado_x = ?, posicion_destacado_y = ?, tamanio_destacado = ?
                WHERE id = ?";
            } else {
                $sql = "UPDATE grupo_plantillas SET
                    posicion_nombre_x = ?, posicion_nombre_y = ?,
                    posicion_razon_x = ?, posicion_razon_y = ?,
                    posicion_fecha_x = ?, posicion_fecha_y = ?,
                    posicion_qr_x = ?, posicion_qr_y = ?,
                    posicion_firma_x = ?, posicion_firma_y = ?,
                    fuente_nombre = ?, formato_nombre = ?,
                    fuente_razon = ?, fuente_fecha = ?,
                    tamanio_fuente = ?, tamanio_razon = ?, tamanio_fecha = ?,
                    tamanio_qr = ?, tamanio_firma = ?,
                    color_texto = ?, color_razon = ?, color_fecha = ?,
                    razon_defecto = ?, formato_fecha = ?, variables_habilitadas = ?,
                    ancho_razon = ?, alineacion_razon = ?,
                    destacado_habilitado = ?, destacado_tipo = ?, destacado_icono = ?, destacado_imagen = ?,
                    destacado_posicion_x = ?, destacado_posicion_y = ?, destacado_tamanio = ?
                WHERE id = ? AND grupo_id = ?";
            }
            $stmt = $conn->prepare($sql);
            
            // Determinar si destacado está habilitado según variables_habilitadas
            $variablesHab = $_POST['variables_habilitadas'] ?? '["nombre","razon","fecha","qr","firma","destacado"]';
            $varsArray = json_decode($variablesHab, true) ?: [];
            $destacadoHabilitado = in_array('destacado', $varsArray) ? 1 : 0;
            
            $binds = [
                $_POST['posicion_nombre_x'] ?? 400,
                $_POST['posicion_nombre_y'] ?? 300,
                $_POST['posicion_razon_x'] ?? 400,
                $_POST['posicion_razon_y'] ?? 360,
                $_POST['posicion_fecha_x'] ?? 400,
                $_POST['posicion_fecha_y'] ?? 420,
                $_POST['posicion_qr_x'] ?? 920,
                $_POST['posicion_qr_y'] ?? 419,
                $_POST['posicion_firma_x'] ?? 800,
                $_POST['posicion_firma_y'] ?? 850,
                $_POST['fuente_nombre'] ?? 'Roboto-Regular',
                $_POST['formato_nombre'] ?? 'mayusculas',
                $_POST['fuente_razon'] ?? 'Roboto-Regular',
                $_POST['fuente_fecha'] ?? 'Roboto-Regular',
                $_POST['tamanio_fuente'] ?? 50,
                $_POST['tamanio_razon'] ?? 24,
                $_POST['tamanio_fecha'] ?? 20,
                $_POST['tamanio_qr'] ?? 200,
                $_POST['tamanio_firma'] ?? 200,
                $_POST['color_texto'] ?? '#000000',
                $_POST['color_razon'] ?? '#333333',
                $_POST['color_fecha'] ?? '#333333',
                $_POST['razon_defecto'] ?? '',
                $_POST['formato_fecha'] ?? 'd de F de Y',
                $_POST['variables_habilitadas'] ?? '["nombre","razon","fecha","qr","firma","destacado"]',
                $_POST['ancho_razon'] ?? 600
            ];
            
            // alineacion_razon only exists in grupo_plantillas, not in grupos
            if (!$isSystem) {
                $binds[] = $_POST['alineacion_razon'] ?? 'justify';
            }
            
            // Destacado columns exist in BOTH tables
            $binds[] = $destacadoHabilitado;
            $binds[] = $_POST['destacado_tipo'] ?? 'icono';
            $binds[] = $_POST['destacado_icono'] ?? 'estrella';
            $binds[] = $destacadoImagenFilename;
            $binds[] = $_POST['posicion_destacado_x'] ?? 50;
            $binds[] = $_POST['posicion_destacado_y'] ?? 50;
            $binds[] = $_POST['destacado_tamanio'] ?? 100;
            
            if ($isSystem) {
                $binds[] = $grupo_id;
            } else {
                $binds[] = $plantilla_id;
                $binds[] = $grupo_id;
            }
            
            $stmt->execute($binds);
            
            echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
