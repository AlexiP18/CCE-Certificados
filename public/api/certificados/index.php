<?php
// Suprimir errores de PHP para que no rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

// Capturar cualquier salida no deseada
ob_start();

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

// Limpiar cualquier salida previa
ob_end_clean();

header('Content-Type: application/json');

// Verificar autenticación
Auth::requireAuth();

$pdo = getConnection();
$usuario = Auth::user();

/**
 * Resuelve la ruta absoluta de un archivo dentro de /public/uploads.
 * Acepta formatos almacenados como "cert_xxx.ext" o "uploads/cert_xxx.ext".
 */
function resolveUploadsAbsolutePath($archivo) {
    $archivo = trim((string)$archivo);
    if ($archivo === '') return '';
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    $relative = ltrim($archivo, '/');
    if (strpos($relative, 'uploads/') === 0) {
        $relative = substr($relative, strlen('uploads/'));
    }
    $relative = ltrim($relative, '/');

    $candidates = [
        $projectRoot . '/uploads/' . $relative,
        $projectRoot . '/public/uploads/' . $relative,
        rtrim(sys_get_temp_dir(), '/') . '/cce_certificados/uploads/' . $relative
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
}

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Si es POST con JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data) {
        $action = $data['action'] ?? $action;
    }
}

try {
    switch ($action) {
        case 'listar':
            // Listar certificados por categoría y/o grupo
            $categoria_id = $_GET['categoria_id'] ?? 0;
            $grupo_id = $_GET['grupo_id'] ?? 0;
            
            $sql = "SELECT c.*, cat.nombre as categoria_nombre, g.nombre as grupo_nombre
                    FROM certificados c
                    LEFT JOIN categorias cat ON c.categoria_id = cat.id
                    LEFT JOIN grupos g ON c.grupo_id = g.id
                    WHERE 1=1";
            $params = [];
            
            if ($categoria_id) {
                $sql .= " AND c.categoria_id = ?";
                $params[] = $categoria_id;
            }
            if ($grupo_id) {
                $sql .= " AND c.grupo_id = ?";
                $params[] = $grupo_id;
            }
            
            $sql .= " ORDER BY c.fecha_creacion DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'certificados' => $certificados,
                'total' => count($certificados)
            ]);
            break;
            
        case 'get_by_code':
            $code = $data['code'] ?? $_GET['code'] ?? '';
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Código no proporcionado']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
            $stmt->execute([$code]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cert) {
                // Verificar si tiene imagen y si existe el archivo
                $tieneImagen = !empty($cert['archivo_imagen']) && file_exists(resolveUploadsAbsolutePath($cert['archivo_imagen']));
                $cert['tiene_imagen'] = $tieneImagen;
                
                echo json_encode(['success' => true, 'certificado' => $cert]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Certificado no encontrado']);
            }
            break;
        
        case 'get_image':
            // Obtener o regenerar la imagen de un certificado
            $code = $data['code'] ?? $_GET['code'] ?? '';
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Código no proporcionado']);
                exit;
            }
            
            try {
                // Suprimir errores durante la carga
                ob_start();
                require_once dirname(dirname(dirname(__DIR__))) . '/includes/Certificate.php';
                ob_end_clean();
                
                $certificate = new \CCE\Certificate($pdo);
                
                $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
                $stmt->execute([$code]);
                $cert = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cert) {
                    echo json_encode(['success' => false, 'message' => 'Certificado no encontrado']);
                    exit;
                }
                
                // Verificar si existe la imagen
                $imagePath = resolveUploadsAbsolutePath($cert['archivo_imagen'] ?? '');
                
                if (!empty($cert['archivo_imagen']) && file_exists($imagePath)) {
                    // Devolver la imagen existente como base64
                    $imageData = @file_get_contents($imagePath);
                    if ($imageData !== false) {
                        $base64 = 'data:image/png;base64,' . base64_encode($imageData);
                        echo json_encode(['success' => true, 'image' => $base64]);
                        exit;
                    }
                }
                
                // Regenerar el certificado si no existe imagen
                ob_start();
                $result = $certificate->regenerate(
                    $code,
                    '',
                    [
                        'usuario_id' => isset($usuario['id']) ? (int)$usuario['id'] : null,
                        'usuario_nombre' => (string)($usuario['nombre_completo'] ?? $usuario['username'] ?? '')
                    ]
                );
                ob_end_clean();
                
                if ($result && isset($result['success']) && $result['success'] && isset($result['imagen_path'])) {
                    if (file_exists($result['imagen_path'])) {
                        $imageData = @file_get_contents($result['imagen_path']);
                        if ($imageData !== false) {
                            $base64 = 'data:image/png;base64,' . base64_encode($imageData);
                            echo json_encode(['success' => true, 'image' => $base64]);
                            exit;
                        }
                    }
                    echo json_encode(['success' => false, 'message' => 'Imagen generada pero no accesible']);
                } else {
                    $errorMsg = isset($result['error']) ? $result['error'] : 'No se pudo regenerar el certificado';
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            } catch (Error $e) {
                echo json_encode(['success' => false, 'message' => 'Error PHP: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete':
            $id = $data['id'] ?? $_POST['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                exit;
            }
            
            // Obtener información del certificado antes de eliminar
            $stmt = $pdo->prepare("SELECT archivo_pdf, archivo_imagen FROM certificados WHERE id = ?");
            $stmt->execute([$id]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                echo json_encode(['success' => false, 'message' => 'Certificado no encontrado']);
                exit;
            }
            
            // Eliminar archivo PDF si existe
            $pdfPath = resolveUploadsAbsolutePath($cert['archivo_pdf'] ?? '');
            if (!empty($cert['archivo_pdf']) && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
            // Eliminar archivo de imagen si existe
            $imgPath = resolveUploadsAbsolutePath($cert['archivo_imagen'] ?? '');
            if (!empty($cert['archivo_imagen']) && file_exists($imgPath)) {
                unlink($imgPath);
            }
            
            // Eliminar registro de la base de datos
            $stmt = $pdo->prepare("DELETE FROM certificados WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificado eliminado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar el certificado de la base de datos'
                ]);
            }
            break;

        case 'aprobar':
            // Cambiar estado de un certificado a activo (aprobado)
            $id = $data['id'] ?? $_POST['id'] ?? 0;

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE certificados SET estado = 'activo' WHERE id = ?");
            $ok = $stmt->execute([$id]);

            echo json_encode([
                'success' => (bool)$ok,
                'message' => $ok ? 'Certificado aprobado' : 'No se pudo aprobar el certificado'
            ]);
            break;

        case 'aprobar_batch':
            // Aprobar múltiples certificados a la vez
            $ids = $data['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) {
                echo json_encode(['success' => false, 'message' => 'IDs no proporcionados']);
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE certificados SET estado = 'activo' WHERE id IN ($placeholders)");
            $ok = $stmt->execute($ids);

            echo json_encode([
                'success' => (bool)$ok,
                'message' => $ok ? 'Certificados aprobados' : 'No se pudieron aprobar los certificados'
            ]);
            break;
        
        case 'regenerar':
            // Regenerar un certificado existente
            $codigo = $data['codigo'] ?? '';
            
            if (empty($codigo)) {
                echo json_encode(['success' => false, 'message' => 'Código no proporcionado']);
                exit;
            }
            
            try {
                ob_start();
                require_once dirname(dirname(dirname(__DIR__))) . '/includes/Certificate.php';
                ob_end_clean();
                
                $certificate = new \CCE\Certificate($pdo);
                
                ob_start();
                $result = $certificate->regenerate(
                    $codigo,
                    '',
                    [
                        'usuario_id' => isset($usuario['id']) ? (int)$usuario['id'] : null,
                        'usuario_nombre' => (string)($usuario['nombre_completo'] ?? $usuario['username'] ?? '')
                    ]
                );
                ob_end_clean();
                
                if ($result && isset($result['success']) && $result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Certificado regenerado correctamente'
                    ]);
                } else {
                    $errorMsg = isset($result['error']) ? $result['error'] : 'No se pudo regenerar el certificado';
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
        
        case 'historial':
            // Obtener historial de generaciones de certificados de una categoría
            $categoria_id = $_GET['categoria_id'] ?? $data['categoria_id'] ?? 0;
            
            if (empty($categoria_id)) {
                echo json_encode(['success' => false, 'message' => 'Categoría no especificada']);
                exit;
            }
            
            $sql = "SELECT c.id, c.codigo, c.nombre, c.fecha_creacion, c.fechas_generacion
                    FROM certificados c
                    WHERE c.categoria_id = ?
                    ORDER BY c.fecha_creacion DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria_id]);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar historial de generaciones
            $historial = [];
            foreach ($certificados as $cert) {
                $fechasGeneracion = [];
                if (!empty($cert['fechas_generacion'])) {
                    $fechasGeneracion = json_decode($cert['fechas_generacion'], true) ?: [];
                }
                
                $historial[] = [
                    'id' => $cert['id'],
                    'codigo' => $cert['codigo'],
                    'nombre' => $cert['nombre'],
                    'fecha_creacion' => $cert['fecha_creacion'],
                    'total_generaciones' => count($fechasGeneracion) ?: 1,
                    'ultima_generacion' => !empty($fechasGeneracion) ? end($fechasGeneracion) : null
                ];
            }
            
            echo json_encode([
                'success' => true,
                'historial' => $historial
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
