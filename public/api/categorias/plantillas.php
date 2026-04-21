<?php
/**
 * API para gestionar plantillas múltiples de categorías
 * Máximo 6 plantillas por categoría
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

const MAX_PLANTILLAS_CATEGORIA = 6;

try {
    $conn = getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? $_POST['categoria_id'] ?? null;

if (!$categoria_id) {
    echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
    exit;
}

// Obtener información del grupo padre para heredar configuración
function getGrupoInfo($conn, $categoria_id) {
    $stmt = $conn->prepare("
        SELECT g.id as grupo_id, g.nombre as grupo_nombre, g.firma_imagen,
               gp.archivo as plantilla_activa_grupo,
               gp.posicion_nombre_x, gp.posicion_nombre_y,
               gp.posicion_razon_x, gp.posicion_razon_y,
               gp.posicion_fecha_x, gp.posicion_fecha_y,
               gp.posicion_qr_x, gp.posicion_qr_y,
               gp.posicion_firma_x, gp.posicion_firma_y,
               gp.fuente_nombre, gp.fuente_razon, gp.fuente_fecha,
               gp.tamanio_fuente, gp.tamanio_razon, gp.tamanio_fecha,
               gp.tamanio_qr, gp.tamanio_firma,
               gp.color_texto, gp.color_razon, gp.color_fecha,
               gp.razon_defecto, gp.formato_fecha, gp.variables_habilitadas,
               gp.ancho_razon,
               gp.formato_nombre, gp.alineacion_razon,
               gp.firma_imagen as gp_firma_imagen,
               gp.destacado_posicion_x, gp.destacado_posicion_y,
               gp.destacado_tamanio, gp.destacado_tipo, gp.destacado_icono, gp.destacado_imagen
        FROM categorias c
        INNER JOIN grupos g ON c.grupo_id = g.id
        LEFT JOIN grupo_plantillas gp ON gp.grupo_id = g.id AND gp.es_activa = 1
        WHERE c.id = ?
    ");
    $stmt->execute([$categoria_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {
    case 'list':
        // Listar todas las plantillas de la categoría
        try {
            $stmt = $conn->prepare("
                SELECT id, nombre, archivo, es_activa, orden, fecha_creacion 
                FROM categoria_plantillas 
                WHERE categoria_id = ? 
                ORDER BY orden ASC, fecha_creacion DESC
            ");
            $stmt->execute([$categoria_id]);
            $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // También obtener info del grupo para mostrar opción de usar plantilla del grupo
            $grupoInfo = getGrupoInfo($conn, $categoria_id);
            
            echo json_encode([
                'success' => true, 
                'plantillas' => $plantillas,
                'grupo_info' => $grupoInfo,
                'max_plantillas' => MAX_PLANTILLAS_CATEGORIA
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'upload':
        // Subir nueva plantilla
        try {
            // Verificar límite de plantillas
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM categoria_plantillas WHERE categoria_id = ?");
            $stmt->execute([$categoria_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] >= MAX_PLANTILLAS_CATEGORIA) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Límite máximo de ' . MAX_PLANTILLAS_CATEGORIA . ' plantillas alcanzado'
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        
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
        
        // Crear directorio si no existe (ruta absoluta desde la raíz de public/)
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/categorias/' . $categoria_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
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
            $stmt = $conn->prepare("SELECT MAX(orden) as max_orden FROM categoria_plantillas WHERE categoria_id = ?");
            $stmt->execute([$categoria_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoOrden = ($result['max_orden'] ?? -1) + 1;
            
            // Obtener configuración del grupo para heredar valores iniciales
            $grupoInfo = getGrupoInfo($conn, $categoria_id);
            
            // Insertar en la base de datos con valores heredados del grupo
            $nombre = $_POST['nombre'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
            $stmt = $conn->prepare("
                INSERT INTO categoria_plantillas (
                    categoria_id, nombre, archivo, orden, es_activa,
                    posicion_nombre_x, posicion_nombre_y,
                    posicion_razon_x, posicion_razon_y,
                    posicion_fecha_x, posicion_fecha_y,
                    posicion_qr_x, posicion_qr_y,
                    posicion_firma_x, posicion_firma_y,
                    fuente_nombre, fuente_razon, fuente_fecha,
                    tamanio_fuente, tamanio_razon, tamanio_fecha,
                    tamanio_qr, tamanio_firma,
                    color_texto, color_razon, color_fecha,
                    razon_defecto, formato_fecha, variables_habilitadas,
                    ancho_razon, firma_imagen, firma_nombre, firma_cargo,
                    formato_nombre, alineacion_razon,
                    destacado_posicion_x, destacado_posicion_y, destacado_tamanio,
                    destacado_tipo, destacado_icono, destacado_imagen
                ) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoria_id, $nombre, $filename, $nuevoOrden,
                $grupoInfo['posicion_nombre_x'] ?? 400,
                $grupoInfo['posicion_nombre_y'] ?? 300,
                $grupoInfo['posicion_razon_x'] ?? 400,
                $grupoInfo['posicion_razon_y'] ?? 360,
                $grupoInfo['posicion_fecha_x'] ?? 400,
                $grupoInfo['posicion_fecha_y'] ?? 420,
                $grupoInfo['posicion_qr_x'] ?? 920,
                $grupoInfo['posicion_qr_y'] ?? 419,
                $grupoInfo['posicion_firma_x'] ?? 800,
                $grupoInfo['posicion_firma_y'] ?? 850,
                $grupoInfo['fuente_nombre'] ?? 'Roboto-Regular',
                $grupoInfo['fuente_razon'] ?? 'Roboto-Regular',
                $grupoInfo['fuente_fecha'] ?? 'Roboto-Regular',
                $grupoInfo['tamanio_fuente'] ?? 50,
                $grupoInfo['tamanio_razon'] ?? 24,
                $grupoInfo['tamanio_fecha'] ?? 20,
                $grupoInfo['tamanio_qr'] ?? 200,
                $grupoInfo['tamanio_firma'] ?? 200,
                $grupoInfo['color_texto'] ?? '#000000',
                $grupoInfo['color_razon'] ?? '#333333',
                $grupoInfo['color_fecha'] ?? '#333333',
                $grupoInfo['razon_defecto'] ?? '',
                $grupoInfo['formato_fecha'] ?? 'd de F de Y',
                $grupoInfo['variables_habilitadas'] ?? '["nombre","razon","fecha","qr","firma"]',
                $grupoInfo['ancho_razon'] ?? 600,
                $grupoInfo['firma_imagen'] ?? null,
                $grupoInfo['firma_nombre'] ?? null,
                $grupoInfo['firma_cargo'] ?? null,
                $grupoInfo['formato_nombre'] ?? 'mayusculas',
                $grupoInfo['alineacion_razon'] ?? 'justified',
                $grupoInfo['destacado_posicion_x'] ?? 50,
                $grupoInfo['destacado_posicion_y'] ?? 50,
                $grupoInfo['destacado_tamanio'] ?? 100,
                $grupoInfo['destacado_tipo'] ?? 'icono',
                $grupoInfo['destacado_icono'] ?? 'estrella',
                $grupoInfo['destacado_imagen'] ?? null
            ]);
            
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
        
    case 'set_active':
        // Establecer una plantilla como activa
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        try {
            // Desactivar todas las plantillas de la categoría
            $stmt = $conn->prepare("UPDATE categoria_plantillas SET es_activa = 0 WHERE categoria_id = ?");
            $stmt->execute([$categoria_id]);
            
            // Activar la plantilla seleccionada
            $stmt = $conn->prepare("UPDATE categoria_plantillas SET es_activa = 1 WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$plantilla_id, $categoria_id]);
            
            // Marcar que la categoría usa plantilla propia
            $stmt = $conn->prepare("UPDATE categorias SET usar_plantilla_propia = 1 WHERE id = ?");
            $stmt->execute([$categoria_id]);
            
            echo json_encode(['success' => true, 'message' => 'Plantilla activada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'use_grupo':
        // Usar plantilla del grupo padre (desactivar plantillas propias)
        try {
            // Desactivar todas las plantillas de la categoría
            $stmt = $conn->prepare("UPDATE categoria_plantillas SET es_activa = 0 WHERE categoria_id = ?");
            $stmt->execute([$categoria_id]);
            
            // Marcar que la categoría NO usa plantilla propia
            $stmt = $conn->prepare("UPDATE categorias SET usar_plantilla_propia = 0 WHERE id = ?");
            $stmt->execute([$categoria_id]);
            
            echo json_encode(['success' => true, 'message' => 'Ahora se usa la plantilla del grupo']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'use_propias':
        // Usar plantillas propias (no las del grupo)
        try {
            // Marcar que la categoría usa plantilla propia
            $stmt = $conn->prepare("UPDATE categorias SET usar_plantilla_propia = 1 WHERE id = ?");
            $stmt->execute([$categoria_id]);
            
            // Si no hay plantillas activas, activar la primera si existe
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM categoria_plantillas WHERE categoria_id = ? AND es_activa = 1");
            $stmt->execute([$categoria_id]);
            $activas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activas['total'] == 0) {
                // Activar la primera plantilla si existe
                $stmt = $conn->prepare("UPDATE categoria_plantillas SET es_activa = 1 WHERE categoria_id = ? ORDER BY orden LIMIT 1");
                $stmt->execute([$categoria_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Ahora se usan plantillas propias']);
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
            $stmt = $conn->prepare("SELECT archivo, es_activa FROM categoria_plantillas WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$plantilla_id, $categoria_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plantilla) {
                echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
                exit;
            }
            
            // Eliminar de la base de datos
            $stmt = $conn->prepare("DELETE FROM categoria_plantillas WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$plantilla_id, $categoria_id]);
            
            // Eliminar archivo físico
            $filepath = dirname(dirname(__DIR__)) . '/uploads/categorias/' . $categoria_id . '/' . $plantilla['archivo'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Si era la plantilla activa, volver a usar la del grupo
            if ($plantilla['es_activa']) {
                $stmt = $conn->prepare("UPDATE categorias SET usar_plantilla_propia = 0 WHERE id = ?");
                $stmt->execute([$categoria_id]);
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
                $stmt = $conn->prepare("UPDATE categoria_plantillas SET orden = ? WHERE id = ? AND categoria_id = ?");
                $stmt->execute([$index, $plantilla_id, $categoria_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'get_config':
        // Obtener configuración de una plantilla específica
        $plantilla_id = $_GET['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT 
                    id, nombre, archivo, es_activa,
                    posicion_nombre_x, posicion_nombre_y,
                    posicion_razon_x, posicion_razon_y,
                    posicion_fecha_x, posicion_fecha_y,
                    posicion_qr_x, posicion_qr_y,
                    posicion_firma_x, posicion_firma_y,
                    fuente_nombre, fuente_razon, fuente_fecha,
                    tamanio_fuente, tamanio_razon, tamanio_fecha,
                    tamanio_qr, tamanio_firma,
                    color_texto, color_razon, color_fecha,
                    razon_defecto, formato_fecha, variables_habilitadas,
                    ancho_razon, firma_imagen, firma_nombre, firma_cargo,
                    formato_nombre, alineacion_razon,
                    destacado_posicion_x, destacado_posicion_y, destacado_tamanio,
                    destacado_tipo, destacado_icono, destacado_imagen
                FROM categoria_plantillas 
                WHERE id = ? AND categoria_id = ?
            ");
            $stmt->execute([$plantilla_id, $categoria_id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                echo json_encode(['success' => true, 'config' => $config]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'save_config':
        // Guardar configuración de una plantilla específica
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                UPDATE categoria_plantillas SET
                    posicion_nombre_x = ?,
                    posicion_nombre_y = ?,
                    posicion_razon_x = ?,
                    posicion_razon_y = ?,
                    posicion_fecha_x = ?,
                    posicion_fecha_y = ?,
                    posicion_qr_x = ?,
                    posicion_qr_y = ?,
                    posicion_firma_x = ?,
                    posicion_firma_y = ?,
                    fuente_nombre = ?,
                    fuente_razon = ?,
                    fuente_fecha = ?,
                    tamanio_fuente = ?,
                    tamanio_razon = ?,
                    tamanio_fecha = ?,
                    tamanio_qr = ?,
                    tamanio_firma = ?,
                    color_texto = ?,
                    color_razon = ?,
                    color_fecha = ?,
                    razon_defecto = ?,
                    formato_fecha = ?,
                    variables_habilitadas = ?,
                    ancho_razon = ?,
                    firma_imagen = ?,
                    firma_nombre = ?,
                    firma_cargo = ?,
                    formato_nombre = ?,
                    alineacion_razon = ?,
                    destacado_posicion_x = ?,
                    destacado_posicion_y = ?,
                    destacado_tamanio = ?,
                    destacado_tipo = ?,
                    destacado_icono = ?,
                    destacado_imagen = ?
                WHERE id = ? AND categoria_id = ?
            ");
            
            $stmt->execute([
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
                $_POST['variables_habilitadas'] ?? '["nombre","razon","fecha","qr","firma"]',
                $_POST['ancho_razon'] ?? 600,
                $_POST['firma_imagen'] ?? null,
                $_POST['firma_nombre'] ?? null,
                $_POST['firma_cargo'] ?? null,
                $_POST['formato_nombre'] ?? 'mayusculas',
                $_POST['alineacion_razon'] ?? 'justified',
                $_POST['posicion_destacado_x'] ?? 50,
                $_POST['posicion_destacado_y'] ?? 50,
                $_POST['destacado_tamanio'] ?? ($_POST['tamanio_destacado'] ?? 100),
                $_POST['destacado_tipo'] ?? 'icono',
                $_POST['destacado_icono'] ?? 'estrella',
                $_POST['destacado_imagen'] ?? null,
                $plantilla_id,
                $categoria_id
            ]);
            
            // Also mark this template as active
            $conn->prepare("UPDATE categoria_plantillas SET es_activa = 0 WHERE categoria_id = ?")->execute([$categoria_id]);
            $conn->prepare("UPDATE categoria_plantillas SET es_activa = 1 WHERE id = ? AND categoria_id = ?")->execute([$plantilla_id, $categoria_id]);

            echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente', 'plantilla_id' => $plantilla_id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'upload_firma':
        // Subir firma para una plantilla específica
        $plantilla_id = $_POST['plantilla_id'] ?? null;
        
        if (!$plantilla_id) {
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            exit;
        }
        
        if (!isset($_FILES['firma']) || $_FILES['firma']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir la firma']);
            exit;
        }
        
        $file = $_FILES['firma'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
            exit;
        }
        
        // Crear directorio si no existe
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/categorias/' . $categoria_id . '/firmas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'firma_' . $plantilla_id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la firma']);
            exit;
        }
        
        try {
            // Eliminar firma anterior si existe
            $stmt = $conn->prepare("SELECT firma_imagen FROM categoria_plantillas WHERE id = ?");
            $stmt->execute([$plantilla_id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($old && $old['firma_imagen']) {
                $oldPath = $uploadDir . $old['firma_imagen'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Actualizar en BD con ruta relativa (para que el frontend pueda mostrarla)
            $relativePath = 'uploads/categorias/' . $categoria_id . '/firmas/' . $filename;
            $stmt = $conn->prepare("UPDATE categoria_plantillas SET firma_imagen = ? WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$relativePath, $plantilla_id, $categoria_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Firma subida correctamente',
                'firma_imagen' => $relativePath
            ]);
        } catch (Exception $e) {
            unlink($filepath);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
