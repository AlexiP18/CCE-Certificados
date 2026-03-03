<?php
/**
 * API para Visualización de Certificados
 * Permite obtener estudiantes, generar previews y descargar certificados
 */
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/Certificate.php';

// Verificar autenticación
Auth::requireAuth();

header('Content-Type: application/json');

$pdo = getConnection();

// Obtener acción
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?? [];
    $action = $data['action'] ?? $action;
} else {
    $data = $_GET;
}

try {
    switch ($action) {
        case 'obtener_estudiantes':
            // Obtener estudiantes con su estado de certificado
            $categoria_id = $data['categoria_id'] ?? null;
            $grupo_id = $data['grupo_id'] ?? null;
            
            if ($categoria_id) {
                // Estudiantes de una categoría específica
                $sql = "
                    SELECT 
                        e.id,
                        e.nombre,
                        e.cedula,
                        e.es_menor,
                        ce.periodo_id,
                        p.fecha_inicio as periodo_inicio,
                        p.fecha_fin as periodo_fin,
                        c.id as categoria_id,
                        c.nombre as categoria_nombre,
                        c.color as categoria_color,
                        cert.id as certificado_id,
                        cert.codigo as certificado_codigo,
                        cert.fecha as certificado_fecha
                    FROM categoria_estudiantes ce
                    INNER JOIN estudiantes e ON ce.estudiante_id = e.id
                    INNER JOIN categorias c ON ce.categoria_id = c.id
                    LEFT JOIN periodos p ON ce.periodo_id = p.id
                    LEFT JOIN certificados cert ON (
                        cert.nombre = e.nombre 
                        AND cert.categoria_id = ce.categoria_id 
                        AND cert.grupo_id = c.grupo_id
                    )
                    WHERE ce.categoria_id = ? AND e.activo = 1 AND ce.estado = 'activo'
                    ORDER BY p.fecha_inicio DESC, e.nombre ASC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$categoria_id]);
            } else if ($grupo_id) {
                // Estudiantes de todas las categorías de un grupo
                $sql = "
                    SELECT 
                        e.id,
                        e.nombre,
                        e.cedula,
                        e.es_menor,
                        ce.periodo_id,
                        p.fecha_inicio as periodo_inicio,
                        p.fecha_fin as periodo_fin,
                        c.id as categoria_id,
                        c.nombre as categoria_nombre,
                        c.color as categoria_color,
                        cert.id as certificado_id,
                        cert.codigo as certificado_codigo,
                        cert.fecha as certificado_fecha
                    FROM categoria_estudiantes ce
                    INNER JOIN estudiantes e ON ce.estudiante_id = e.id
                    INNER JOIN categorias c ON ce.categoria_id = c.id
                    LEFT JOIN periodos p ON ce.periodo_id = p.id
                    LEFT JOIN certificados cert ON (
                        cert.nombre = e.nombre 
                        AND cert.categoria_id = ce.categoria_id 
                        AND cert.grupo_id = c.grupo_id
                    )
                    WHERE c.grupo_id = ? AND e.activo = 1 AND c.activo = 1 AND ce.estado = 'activo'
                    ORDER BY c.nombre ASC, p.fecha_inicio DESC, e.nombre ASC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$grupo_id]);
            } else {
                throw new Exception('Debe especificar categoria_id o grupo_id');
            }
            
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes,
                'total' => count($estudiantes)
            ]);
            break;
            
        case 'preview':
            // Generar vista previa del certificado
            $estudiante_id = $data['estudiante_id'] ?? 0;
            $categoria_id = $data['categoria_id'] ?? 0;
            
            if (!$estudiante_id || !$categoria_id) {
                throw new Exception('Faltan parámetros requeridos');
            }
            
            // Obtener datos del estudiante
            $stmt = $pdo->prepare("
                SELECT e.*, c.grupo_id, c.nombre as categoria_nombre
                FROM estudiantes e
                JOIN categorias c ON e.categoria_id = c.id
                WHERE e.id = ?
            ");
            $stmt->execute([$estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            // Generar preview usando la API existente
            $previewData = [
                'tipo' => 'categoria',
                'id' => $categoria_id,
                'nombre' => $estudiante['nombre'],
                'razon' => '',
                'fecha' => date('Y-m-d')
            ];
            
            // Incluir el código de preview
            ob_start();
            $_POST = $previewData;
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // Simular petición de preview
            $preview = generarPreviewCertificado($pdo, $estudiante, $categoria_id);
            
            if ($preview) {
                echo json_encode([
                    'success' => true,
                    'preview' => $preview
                ]);
            } else {
                throw new Exception('No se pudo generar la vista previa. Verifique la configuración de la plantilla.');
            }
            break;
            
        case 'descargar':
            // Descargar certificado en PDF o imagen
            $estudiante_id = $data['estudiante_id'] ?? 0;
            $categoria_id = $data['categoria_id'] ?? 0;
            $formato = $data['formato'] ?? 'pdf';
            
            if (!$estudiante_id || !$categoria_id) {
                throw new Exception('Faltan parámetros requeridos');
            }
            
            // Obtener datos del estudiante
            $stmt = $pdo->prepare("
                SELECT e.*, c.grupo_id, c.nombre as categoria_nombre
                FROM estudiantes e
                JOIN categorias c ON e.categoria_id = c.id
                WHERE e.id = ?
            ");
            $stmt->execute([$estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            // Verificar si ya existe el certificado
            $stmt = $pdo->prepare("
                SELECT * FROM certificados 
                WHERE nombre = ? AND categoria_id = ? AND grupo_id = ?
            ");
            $stmt->execute([$estudiante['nombre'], $categoria_id, $estudiante['grupo_id']]);
            $certificadoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($certificadoExistente) {
                // Si existe, descargar el archivo existente
                $uploadsPath = dirname(__DIR__) . '/uploads/';
                
                if ($formato === 'pdf' && $certificadoExistente['archivo_pdf']) {
                    $archivo = $uploadsPath . $certificadoExistente['archivo_pdf'];
                    if (file_exists($archivo)) {
                        $base64 = base64_encode(file_get_contents($archivo));
                        echo json_encode([
                            'success' => true,
                            'archivo' => 'data:application/pdf;base64,' . $base64,
                            'nombre_archivo' => 'certificado_' . sanitizeFilename($estudiante['nombre']) . '.pdf'
                        ]);
                        exit;
                    }
                } else if ($formato === 'imagen' && $certificadoExistente['archivo_imagen']) {
                    $archivo = $uploadsPath . $certificadoExistente['archivo_imagen'];
                    if (file_exists($archivo)) {
                        $ext = pathinfo($archivo, PATHINFO_EXTENSION);
                        $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
                        $mime = $mimeTypes[$ext] ?? 'image/png';
                        $base64 = base64_encode(file_get_contents($archivo));
                        echo json_encode([
                            'success' => true,
                            'archivo' => "data:{$mime};base64," . $base64,
                            'nombre_archivo' => 'certificado_' . sanitizeFilename($estudiante['nombre']) . '.' . $ext
                        ]);
                        exit;
                    }
                }
            }
            
            // Si no existe o no se encontró el archivo, generar al vuelo
            $result = generarCertificadoAlVuelo($pdo, $estudiante, $categoria_id, $formato);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                throw new Exception($result['error'] ?? 'Error al generar el certificado');
            }
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

/**
 * Generar preview del certificado
 */
function generarPreviewCertificado($pdo, $estudiante, $categoria_id) {
    try {
        // Obtener categoría y grupo
        $stmt = $pdo->prepare("
            SELECT c.*, g.id as grupo_id 
            FROM categorias c 
            JOIN grupos g ON c.grupo_id = g.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$categoria_id]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoria) {
            return null;
        }
        
        // Verificar si ya existe el certificado
        $stmt = $pdo->prepare("
            SELECT * FROM certificados 
            WHERE nombre = ? AND categoria_id = ? AND grupo_id = ?
        ");
        $stmt->execute([$estudiante['nombre'], $categoria_id, $categoria['grupo_id']]);
        $certExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($certExistente && $certExistente['archivo_imagen']) {
            // Si ya existe, devolver la imagen existente
            $uploadsPath = dirname(__DIR__) . '/uploads/';
            $archivo = $uploadsPath . $certExistente['archivo_imagen'];
            if (file_exists($archivo)) {
                $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
                $mime = $mimeTypes[$ext] ?? 'image/png';
                $base64 = base64_encode(file_get_contents($archivo));
                return "data:{$mime};base64," . $base64;
            }
        }
        
        // Si no existe, generar certificado temporal
        $certificate = new \CCE\Certificate($pdo);
        
        // Preparar datos
        $dataCert = [
            'nombre' => $estudiante['nombre'],
            'razon' => '',
            'fecha' => date('Y-m-d'),
            'grupo_id' => $categoria['grupo_id'],
            'categoria_id' => $categoria_id,
            'estudiante_id' => $estudiante['id'],
            'es_destacado' => isset($estudiante['destacado']) ? (bool)$estudiante['destacado'] : false
        ];
        
        // Crear certificado
        $result = $certificate->create($dataCert);
        
        if ($result['success'] && isset($result['imagen'])) {
            $uploadsPath = dirname(__DIR__) . '/uploads/';
            $archivo = $uploadsPath . $result['imagen'];
            if (file_exists($archivo)) {
                $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
                $mime = $mimeTypes[$ext] ?? 'image/png';
                $base64 = base64_encode(file_get_contents($archivo));
                return "data:{$mime};base64," . $base64;
            }
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error generando preview: " . $e->getMessage());
        return null;
    }
}

/**
 * Generar certificado al vuelo sin guardarlo en BD
 */
function generarCertificadoAlVuelo($pdo, $estudiante, $categoria_id, $formato = 'pdf') {
    try {
        $certificate = new \CCE\Certificate($pdo);
        
        // Obtener categoría
        $stmt = $pdo->prepare("SELECT grupo_id FROM categorias WHERE id = ?");
        $stmt->execute([$categoria_id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cat) {
            return ['success' => false, 'error' => 'Categoría no encontrada'];
        }
        
        // Datos del certificado
        $dataCert = [
            'nombre' => $estudiante['nombre'],
            'razon' => '',
            'fecha' => date('Y-m-d'),
            'grupo_id' => $cat['grupo_id'],
            'categoria_id' => $categoria_id,
            'estudiante_id' => $estudiante['id'],
            'es_destacado' => isset($estudiante['destacado']) ? (bool)$estudiante['destacado'] : false
        ];
        
        // Verificar si ya existe para regenerar
        $stmt = $pdo->prepare("
            SELECT codigo FROM certificados 
            WHERE nombre = ? AND categoria_id = ? AND grupo_id = ?
        ");
        $stmt->execute([$estudiante['nombre'], $categoria_id, $cat['grupo_id']]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Regenerar el existente
            $result = $certificate->regenerate($existente['codigo']);
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Error al regenerar'];
            }
            $codigo = $existente['codigo'];
        } else {
            // Crear nuevo
            $result = $certificate->create($dataCert);
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Error al crear'];
            }
            $codigo = $result['codigo'];
        }
        
        // Obtener el certificado generado
        $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cert) {
            return ['success' => false, 'error' => 'No se encontró el certificado generado'];
        }
        
        $uploadsPath = dirname(__DIR__) . '/uploads/';
        
        if ($formato === 'pdf' && $cert['archivo_pdf']) {
            $archivo = $uploadsPath . $cert['archivo_pdf'];
            if (file_exists($archivo)) {
                $base64 = base64_encode(file_get_contents($archivo));
                return [
                    'success' => true,
                    'archivo' => 'data:application/pdf;base64,' . $base64,
                    'nombre_archivo' => 'certificado_' . sanitizeFilename($estudiante['nombre']) . '.pdf'
                ];
            }
        } else if ($formato === 'imagen' && $cert['archivo_imagen']) {
            $archivo = $uploadsPath . $cert['archivo_imagen'];
            if (file_exists($archivo)) {
                $ext = pathinfo($archivo, PATHINFO_EXTENSION);
                $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
                $mime = $mimeTypes[$ext] ?? 'image/png';
                $base64 = base64_encode(file_get_contents($archivo));
                return [
                    'success' => true,
                    'archivo' => "data:{$mime};base64," . $base64,
                    'nombre_archivo' => 'certificado_' . sanitizeFilename($estudiante['nombre']) . '.' . $ext
                ];
            }
        }
        
        return ['success' => false, 'error' => 'No se pudo generar el archivo'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sanitizar nombre de archivo
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', $filename);
    return substr($filename, 0, 100);
}
