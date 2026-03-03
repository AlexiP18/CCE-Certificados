<?php
/**
 * API para gestionar estudiantes por categoría
 */
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Verificar autenticación
Auth::requireAuth();

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ============================================================================
 * FUNCIÓN PARA ENVÍO DE CORREOS DE MATRÍCULA
 * ============================================================================
 * 
 * INSTRUCCIONES PARA ACTIVAR EN CPANEL:
 * 1. Descomentar la función enviarCorreoMatricula() abajo
 * 2. Configurar las variables $remitente y $nombreInstitucion
 * 3. Descomentar las llamadas a enviarCorreoMatricula() en las acciones:
 *    - 'agregar' (línea aprox. ~180)
 *    - 'procesar_archivo' (línea aprox. ~520)
 * 4. Opcionalmente instalar PHPMailer para mejor compatibilidad SMTP:
 *    composer require phpmailer/phpmailer
 * 
 * NOTA: La función mail() de PHP funciona directamente en cPanel si el 
 * hosting tiene configurado un servidor de correo (sendmail/postfix).
 * ============================================================================
 */

/*
// === DESCOMENTAR ESTA FUNCIÓN PARA ACTIVAR ENVÍO DE CORREOS ===

function enviarCorreoMatricula($pdo, $estudiante_id, $categoria_id) {
    // Configuración - MODIFICAR SEGÚN TU DOMINIO
    $remitente = 'no-reply@tudominio.com';
    $nombreInstitucion = 'Centro Cultural Ecuatoriano';
    
    try {
        // Obtener datos del estudiante
        $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->execute([$estudiante_id]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$estudiante) return false;
        
        // Obtener datos de la categoría y grupo
        $stmt = $pdo->prepare("
            SELECT c.nombre as categoria, g.nombre as grupo 
            FROM categorias c 
            INNER JOIN grupos g ON c.grupo_id = g.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$categoria_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$info) return false;
        
        // Determinar destinatario (estudiante o representante si es menor)
        $destinatario = $estudiante['es_menor'] ? $estudiante['representante_email'] : $estudiante['email'];
        $nombreDestinatario = $estudiante['es_menor'] ? $estudiante['representante_nombre'] : $estudiante['nombre'];
        
        if (empty($destinatario)) return false; // No hay email
        
        // Construir el correo
        $asunto = "Confirmación de Matrícula - {$info['grupo']}";
        
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 25px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #667eea; }
                .footer { background: #333; color: #aaa; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
                .highlight { color: #667eea; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0;'>🎓 Matrícula Confirmada</h1>
                </div>
                <div class='content'>
                    <p>Estimado/a <strong>{$nombreDestinatario}</strong>,</p>
                    
                    <p>Le confirmamos que la matrícula ha sido registrada exitosamente en nuestro sistema.</p>
                    
                    <div class='info-box'>
                        <p><strong>📋 Detalles de la matrícula:</strong></p>
                        <p>• <strong>Estudiante:</strong> {$estudiante['nombre']}</p>
                        <p>• <strong>Grupo:</strong> <span class='highlight'>{$info['grupo']}</span></p>
                        <p>• <strong>Categoría:</strong> <span class='highlight'>{$info['categoria']}</span></p>
                        <p>• <strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
                    </div>
                    ";
        
        // Si es menor, agregar info del representante
        if ($estudiante['es_menor']) {
            $mensaje .= "
                    <div class='info-box' style='border-left-color: #9b59b6;'>
                        <p><strong>👨‍👩‍👧 Representante Legal:</strong></p>
                        <p>• <strong>Nombre:</strong> {$estudiante['representante_nombre']}</p>
                        <p>• <strong>Cédula:</strong> {$estudiante['representante_cedula']}</p>
                    </div>
                    ";
        }
        
        $mensaje .= "
                    <p>Si tiene alguna consulta, no dude en contactarnos.</p>
                    
                    <p>Atentamente,<br>
                    <strong>{$nombreInstitucion}</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un correo automático, por favor no responda a este mensaje.</p>
                    <p>© " . date('Y') . " {$nombreInstitucion}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Headers del correo
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $nombreInstitucion . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Enviar correo
        $enviado = mail($destinatario, $asunto, $mensaje, implode("\r\n", $headers));
        
        // Log opcional para debug
        if (!$enviado) {
            error_log("Error al enviar correo de matrícula a: {$destinatario}");
        }
        
        return $enviado;
        
    } catch (Exception $e) {
        error_log("Excepción en enviarCorreoMatricula: " . $e->getMessage());
        return false;
    }
}

// === FIN FUNCIÓN DE CORREO ===
*/

header('Content-Type: application/json');

$pdo = getConnection();

// Manejar GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'listar_matriculados':
            // Listar estudiantes matriculados con información de certificados
            $categoria_id = $_GET['categoria_id'] ?? 0;
            $periodo_id = $_GET['periodo_id'] ?? null;
            
            if (!$categoria_id) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                exit;
            }
            
            // Obtener grupo_id de la categoría
            $stmtCat = $pdo->prepare("SELECT grupo_id FROM categorias WHERE id = ?");
            $stmtCat->execute([$categoria_id]);
            $catInfo = $stmtCat->fetch(PDO::FETCH_ASSOC);
            $grupo_id = $catInfo ? $catInfo['grupo_id'] : 0;
            
            $sql = "
                      SELECT ce.id as matricula_id, ce.estudiante_id, ce.fecha_matricula, ce.estado, ce.notas, ce.periodo_id,
                      e.id, e.nombre, e.cedula, e.celular, e.email, e.fecha_nacimiento, e.destacado as es_destacado,
                      e.es_menor, e.representante_nombre, e.representante_cedula,
                      e.representante_celular, e.representante_email, e.representante_fecha_nacimiento,
                      e.representante_id,
                      cert.codigo as certificado_codigo, cert.fecha as certificado_fecha, cert.id as certificado_id,
                      cert.estado as certificado_estado, cert.razon as certificado_razon,
                      cert.fecha_creacion as certificado_fecha_creacion,
                      (SELECT COUNT(*) FROM estudiantes_referencias er WHERE er.estudiante_id = e.id) as tiene_referencias
                FROM categoria_estudiantes ce
                INNER JOIN estudiantes e ON ce.estudiante_id = e.id
                LEFT JOIN certificados cert ON cert.nombre = e.nombre AND cert.categoria_id = ? AND cert.grupo_id = ?
                WHERE ce.categoria_id = ?
            ";
            
            $params = [$categoria_id, $grupo_id, $categoria_id];
            
            if ($periodo_id) {
                $sql .= " AND ce.periodo_id = ?";
                $params[] = $periodo_id;
            }
            
            $sql .= " ORDER BY e.nombre ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes,
                'total' => count($estudiantes)
            ]);
            break;

        case 'obtener_estudiante':
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, nombre, cedula, celular, email, fecha_nacimiento, destacado as es_destacado, es_menor, es_solo_representante FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $est = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($est) {
                echo json_encode(['success' => true, 'estudiante' => $est]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
            }
            break;
            
        case 'list':
            // Listar estudiantes de una categoría (y período si se especifica)
            $categoria_id = $_GET['categoria_id'] ?? 0;
            $periodo_id = $_GET['periodo_id'] ?? null;
            
            if (!$categoria_id) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                exit;
            }
            
            // Construir consulta según si hay período o no
            $sql = "
                SELECT ce.id, ce.estudiante_id, ce.fecha_matricula, ce.estado, ce.notas, ce.periodo_id,
                       e.nombre, e.cedula, e.celular, e.email, e.fecha_nacimiento,
                       e.es_menor, 
                       COALESCE(rep.nombre, e.representante_nombre) as representante_nombre,
                       COALESCE(rep.cedula, e.representante_cedula) as representante_cedula,
                       COALESCE(rep.celular, e.representante_celular) as representante_celular,
                       COALESCE(rep.email, e.representante_email) as representante_email,
                       COALESCE(rep.fecha_nacimiento, e.representante_fecha_nacimiento) as representante_fecha_nacimiento,
                       e.representante_id
                FROM categoria_estudiantes ce
                INNER JOIN estudiantes e ON ce.estudiante_id = e.id
                LEFT JOIN estudiantes rep ON e.representante_id = rep.id
                WHERE ce.categoria_id = ?
            ";
            
            $params = [$categoria_id];
            
            if ($periodo_id) {
                $sql .= " AND ce.periodo_id = ?";
                $params[] = $periodo_id;
            }
            
            $sql .= " ORDER BY e.nombre ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes,
                'total' => count($estudiantes)
            ]);
            break;
            
        case 'search':
            // Buscar estudiantes para agregar
            $q = $_GET['q'] ?? '';
            $categoria_id = $_GET['categoria_id'] ?? 0;
            $periodo_id = $_GET['periodo_id'] ?? null;
            
            if (strlen($q) < 2) {
                echo json_encode(['success' => true, 'estudiantes' => []]);
                exit;
            }
            
            // Verificar si está matriculado en la categoría Y período específico
            $sql = "
                SELECT e.id, e.nombre, e.cedula, e.celular, e.email, e.es_solo_representante,
                       (SELECT COUNT(*) FROM categoria_estudiantes ce 
                        WHERE ce.estudiante_id = e.id 
                        AND ce.categoria_id = ?
                        AND (ce.periodo_id = ? OR (ce.periodo_id IS NULL AND ? IS NULL))
                       ) as ya_matriculado
                FROM estudiantes e
                WHERE e.activo = 1 AND (e.nombre LIKE ? OR e.cedula LIKE ?)
                ORDER BY e.nombre ASC
                LIMIT 20
            ";
            $searchTerm = "%{$q}%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria_id, $periodo_id, $periodo_id, $searchTerm, $searchTerm]);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir ya_matriculado a booleano
            foreach ($estudiantes as &$est) {
                $est['ya_matriculado'] = (bool)$est['ya_matriculado'];
            }
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes
            ]);
            break;
            
        case 'verificar_cedula':
            // Verificar si una cédula ya existe en la base de datos
            // También devuelve info de "solo representantes" para autocompletado
            $cedula = $_GET['cedula'] ?? '';
            $excluir_id = $_GET['excluir_id'] ?? null; // Para edición, excluir el mismo estudiante
            $para_representante = isset($_GET['para_representante']) ? true : false; // Si es para campo de representante
            
            if (empty($cedula) || strlen($cedula) !== 10) {
                echo json_encode(['success' => true, 'existe' => false]);
                exit;
            }
            
            $sql = "SELECT id, nombre, celular, email, fecha_nacimiento, es_solo_representante, es_menor 
                    FROM estudiantes WHERE cedula = ? AND activo = 1";
            $params = [$cedula];
            
            if ($excluir_id) {
                $sql .= " AND id != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determinar si es un estudiante válido o solo representante
            $esSoloRepresentante = $estudiante && $estudiante['es_solo_representante'] == 1;
            
            echo json_encode([
                'success' => true,
                'existe' => (bool)$estudiante,
                'es_solo_representante' => $esSoloRepresentante,
                'estudiante' => $estudiante ?: null
            ]);
            break;

        case 'verificar_email':
            // Verificar si un email ya existe
            $email = $_GET['email'] ?? '';
            $excluir_id = $_GET['excluir_id'] ?? null;
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => true, 'existe' => false]);
                exit;
            }
            
            $sql = "SELECT id, nombre FROM estudiantes WHERE email = ? AND activo = 1";
            $params = [$email];
            
            if ($excluir_id) {
                $sql .= " AND id != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'existe' => (bool)$estudiante,
                'estudiante' => $estudiante ?: null
            ]);
            break;

        case 'listar_referencias':
            $estudiante_id = $_GET['estudiante_id'] ?? 0;
            if (!$estudiante_id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }

            // Obtener nombre del estudiante
            $stmtNombre = $pdo->prepare("SELECT nombre FROM estudiantes WHERE id = ?");
            $stmtNombre->execute([$estudiante_id]);
            $nombre = $stmtNombre->fetchColumn() ?: 'Estudiante';

            // Obtener referencias
            $stmtRef = $pdo->prepare("SELECT nombre, telefono, relacion FROM estudiantes_referencias WHERE estudiante_id = ? ORDER BY id ASC");
            $stmtRef->execute([$estudiante_id]);
            $referencias = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'estudiante_nombre' => $nombre,
                'referencias' => $referencias
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    exit;
}

// Manejar POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $jsonData = json_decode(file_get_contents('php://input'), true);
        $action = $jsonData['action'] ?? '';
    } else {
        $action = $_POST['action'] ?? '';
        $jsonData = null;
    }
    
    switch ($action) {
        case 'crear_menores_con_representante':
            // Crear múltiples menores con un mismo representante legal
            if (!$jsonData) {
                echo json_encode(['success' => false, 'message' => 'Datos JSON requeridos']);
                exit;
            }
            
            $categoria_id = $jsonData['categoria_id'] ?? 0;
            $periodo_id = $jsonData['periodo_id'] ?? null;
            $representante = $jsonData['representante'] ?? [];
            $menores = $jsonData['menores'] ?? [];
            
            if (!$categoria_id || empty($representante) || empty($menores)) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            
            // Validar datos del representante
            if (empty($representante['nombre']) || empty($representante['cedula']) || empty($representante['celular'])) {
                echo json_encode(['success' => false, 'message' => 'Datos del representante incompletos']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();
                $estudiantesCreados = 0;
                
                // Buscar si el representante ya existe como estudiante o como solo representante
                $representante_id = null;
                $stmt = $pdo->prepare("SELECT id, es_solo_representante FROM estudiantes WHERE cedula = ? AND activo = 1");
                $stmt->execute([$representante['cedula']]);
                $repExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($repExistente) {
                    $representante_id = $repExistente['id'];
                    // Actualizar datos del representante si es necesario
                    $stmt = $pdo->prepare("
                        UPDATE estudiantes SET 
                            nombre = COALESCE(NULLIF(?, ''), nombre),
                            celular = COALESCE(NULLIF(?, ''), celular),
                            email = COALESCE(NULLIF(?, ''), email),
                            fecha_nacimiento = COALESCE(NULLIF(?, ''), fecha_nacimiento)
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $representante['nombre'],
                        $representante['celular'],
                        $representante['email'] ?? null,
                        $representante['fecha_nacimiento'] ?? null,
                        $representante_id
                    ]);
                } else {
                    // Crear representante como "solo representante" (no estudiante activo aún)
                    $stmt = $pdo->prepare("
                        INSERT INTO estudiantes (
                            nombre, cedula, celular, email, fecha_nacimiento,
                            es_menor, es_solo_representante, activo
                        ) VALUES (?, ?, ?, ?, ?, 0, 1, 1)
                    ");
                    $stmt->execute([
                        $representante['nombre'],
                        $representante['cedula'],
                        $representante['celular'],
                        $representante['email'] ?? null,
                        $representante['fecha_nacimiento'] ?? null
                    ]);
                    $representante_id = $pdo->lastInsertId();
                }
                
                foreach ($menores as $menor) {
                    if (empty($menor['nombre']) || empty($menor['fecha_nacimiento'])) {
                        continue; // Saltar menores sin datos completos
                    }
                    
                    // Crear estudiante menor (incluye cédula, celular y email opcionales del menor)
                    $stmt = $pdo->prepare("
                        INSERT INTO estudiantes (
                            nombre, cedula, celular, email, fecha_nacimiento, es_menor,
                            representante_nombre, representante_cedula,
                            representante_celular, representante_email, representante_fecha_nacimiento, representante_id, activo
                        ) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $menor['nombre'],
                        !empty($menor['cedula']) ? $menor['cedula'] : null,
                        !empty($menor['celular']) ? $menor['celular'] : null,
                        !empty($menor['email']) ? $menor['email'] : null,
                        $menor['fecha_nacimiento'],
                        $representante['nombre'],
                        $representante['cedula'],
                        $representante['celular'],
                        !empty($representante['email']) ? $representante['email'] : null,
                        $representante['fecha_nacimiento'] ?? null,
                        $representante_id
                    ]);
                    $estudiante_id = $pdo->lastInsertId();
                    
                    // Matricular en la categoría (con período si está disponible)
                    $stmt = $pdo->prepare("
                        INSERT INTO categoria_estudiantes (categoria_id, periodo_id, estudiante_id, fecha_matricula, estado)
                        VALUES (?, ?, ?, ?, 'activo')
                        ON DUPLICATE KEY UPDATE estado = 'activo'
                    ");
                    $stmt->execute([$categoria_id, $periodo_id, $estudiante_id, date('Y-m-d')]);
                    
                    // === DESCOMENTAR PARA ENVIAR CORREO DE MATRÍCULA ===
                    // enviarCorreoMatricula($pdo, $estudiante_id, $categoria_id);
                    
                    $estudiantesCreados++;
                }
                
                // Guardar referencias del representante
                if (!empty($representante['referencias'])) {
                    guardarReferencias($pdo, $representante_id, $representante['referencias']);
                }

                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => "$estudiantesCreados estudiante(s) creado(s) y matriculado(s)",
                    'cantidad' => $estudiantesCreados
                ]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'matricular':
            // Matricular estudiante existente en categoría
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $periodo_id = $_POST['periodo_id'] ?? null;
            $estudiante_id = $_POST['estudiante_id'] ?? 0;
            $fecha_matricula = $_POST['fecha_matricula'] ?? date('Y-m-d');
            $convertir_representante = ($_POST['convertir_representante'] ?? '0') === '1';
            
            if (!$categoria_id || !$estudiante_id) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            
            try {
                // Si es un "solo representante", convertirlo a estudiante activo
                if ($convertir_representante) {
                    $stmt = $pdo->prepare("UPDATE estudiantes SET es_solo_representante = 0 WHERE id = ?");
                    $stmt->execute([$estudiante_id]);
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO categoria_estudiantes (categoria_id, periodo_id, estudiante_id, fecha_matricula, estado)
                    VALUES (?, ?, ?, ?, 'activo')
                    ON DUPLICATE KEY UPDATE estado = 'activo', fecha_actualizacion = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$categoria_id, $periodo_id, $estudiante_id, $fecha_matricula]);
                
                // === DESCOMENTAR PARA ENVIAR CORREO DE MATRÍCULA ===
                // enviarCorreoMatricula($pdo, $estudiante_id, $categoria_id);
                
                echo json_encode(['success' => true, 'message' => 'Estudiante matriculado']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al matricular: ' . $e->getMessage()]);
            }
            break;
            
        case 'crear_y_matricular':
            // Crear nuevo estudiante y matricularlo
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $periodo_id = $_POST['periodo_id'] ?? null;
            $nombre = trim($_POST['nombre'] ?? '');
            $cedula = trim($_POST['cedula'] ?? '');
            $celular = trim($_POST['celular'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
            
            // Campos de menor de edad y representante
            $es_menor = isset($_POST['es_menor']) && $_POST['es_menor'] === 'on' ? 1 : 0;
            $representante_nombre = trim($_POST['representante_nombre'] ?? '');
            $representante_cedula = trim($_POST['representante_cedula'] ?? '');
            $representante_celular = trim($_POST['representante_celular'] ?? '');
            $representante_email = trim($_POST['representante_email'] ?? '');
            $representante_fecha_nacimiento = $_POST['representante_fecha_nacimiento'] ?? null;
            
            if (!$categoria_id || !$nombre) {
                echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
                exit;
            }
            
            // Validaciones para menor de edad
            if ($es_menor) {
                if (empty($fecha_nacimiento)) {
                    echo json_encode(['success' => false, 'message' => 'Fecha de nacimiento requerida para menores']);
                    exit;
                }
                if (empty($representante_nombre) || empty($representante_cedula) || empty($representante_celular)) {
                    echo json_encode(['success' => false, 'message' => 'Datos del representante legal requeridos']);
                    exit;
                }
            }
            
            try {
                $pdo->beginTransaction();
                
                // Verificar si ya existe por cédula (solo si no es menor y tiene cédula)
                $estudiante_id = null;
                if (!$es_menor && !empty($cedula)) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ?");
                    $stmt->execute([$cedula]);
                    $existente = $stmt->fetch();
                    if ($existente) {
                        $estudiante_id = $existente['id'];
                    }
                }
                
                // Si no existe, crear
                if (!$estudiante_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO estudiantes (
                            nombre, cedula, celular, email, fecha_nacimiento,
                            es_menor, representante_nombre, representante_cedula,
                            representante_celular, representante_email, representante_fecha_nacimiento,
                            activo
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $nombre,
                        !empty($cedula) ? $cedula : null,
                        !empty($celular) ? $celular : null,
                        !empty($email) ? $email : null,
                        !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                        $es_menor,
                        !empty($representante_nombre) ? $representante_nombre : null,
                        !empty($representante_cedula) ? $representante_cedula : null,
                        !empty($representante_celular) ? $representante_celular : null,
                        !empty($representante_email) ? $representante_email : null,
                        !empty($representante_fecha_nacimiento) ? $representante_fecha_nacimiento : null
                    ]);
                    $estudiante_id = $pdo->lastInsertId();
                }
                
                // Matricular (con período si está disponible)
                $stmt = $pdo->prepare("
                    INSERT INTO categoria_estudiantes (categoria_id, periodo_id, estudiante_id, fecha_matricula, estado)
                    VALUES (?, ?, ?, ?, 'activo')
                    ON DUPLICATE KEY UPDATE estado = 'activo'
                ");
                $stmt->execute([$categoria_id, $periodo_id, $estudiante_id, date('Y-m-d')]);
                
                // Guardar referencias (si es mayor de edad)
                if (isset($_POST['referencias_mayor']) && is_array($_POST['referencias_mayor'])) {
                    guardarReferencias($pdo, $estudiante_id, $_POST['referencias_mayor']);
                }

                $pdo->commit();
                
                // === DESCOMENTAR PARA ENVIAR CORREO DE MATRÍCULA ===
                // enviarCorreoMatricula($pdo, $estudiante_id, $categoria_id);
                
                echo json_encode(['success' => true, 'message' => 'Estudiante creado y matriculado']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'quitar_multiple':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $categoria_id = $_POST['categoria_id'] ?? 0;
            
            if (!is_array($ids) || empty($ids) || !$categoria_id) {
                echo json_encode(['success' => false, 'message' => 'IDs y categoría requeridos']);
                exit;
            }
            
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql = "DELETE FROM categoria_estudiantes WHERE estudiante_id IN ($placeholders) AND categoria_id = ?";
                $params = array_merge($ids, [$categoria_id]);
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo json_encode(['success' => true, 'message' => count($ids) . ' estudiante(s) removido(s)']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'quitar':
            // Quitar estudiante de la categoría
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("DELETE FROM categoria_estudiantes WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Estudiante removido de la categoría']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al quitar: ' . $e->getMessage()]);
            }
            break;
            
        case 'editar_estudiante':
            // Editar información básica de estudiante
            $id = $_POST['id'] ?? 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $cedula = trim($_POST['cedula'] ?? '');
            $celular = trim($_POST['celular'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
            $es_destacado = isset($_POST['es_destacado']) ? (int)$_POST['es_destacado'] : 0;
            
            if (!$id || !$nombre) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();

                // Verificar si la cédula ya existe en otro estudiante
                if (!empty($cedula)) {
                    $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ? AND id != ?");
                    $stmt->execute([$cedula, $id]);
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada en otro estudiante']);
                        exit;
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE estudiantes SET 
                        nombre = ?, 
                        cedula = ?, 
                        celular = ?, 
                        email = ?, 
                        fecha_nacimiento = ?,
                        destacado = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $nombre,
                    !empty($cedula) ? $cedula : null,
                    !empty($celular) ? $celular : null,
                    !empty($email) ? $email : null,
                    !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                    $es_destacado,
                    $id
                ]);

                // Actualizar referencias si se enviaron
                if (isset($_POST['referencias'])) {
                    $referencias = json_decode($_POST['referencias'], true);
                    if (is_array($referencias)) {
                        // Eliminar referencias anteriores
                        $stmtDel = $pdo->prepare("DELETE FROM estudiantes_referencias WHERE estudiante_id = ?");
                        $stmtDel->execute([$id]);
                        
                        // Insertar nuevas referencias
                        if (!empty($referencias)) {
                            $stmtIns = $pdo->prepare("INSERT INTO estudiantes_referencias (estudiante_id, nombre, telefono, relacion) VALUES (?, ?, ?, ?)");
                            foreach ($referencias as $ref) {
                                $stmtIns->execute([
                                    $id,
                                    trim($ref['nombre']),
                                    trim($ref['telefono'] ?? ''),
                                    trim($ref['relacion'] ?? '')
                                ]);
                            }
                        }
                    }
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Estudiante actualizado correctamente']);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
            }
            break;

        case 'establecer_destacados':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'IDs requeridos']);
                exit;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE estudiantes SET destacado = 1 WHERE id IN ($placeholders)");
                $stmt->execute($ids);

                echo json_encode([
                    'success' => true,
                    'message' => count($ids) . ' estudiante(s) marcado(s) como destacado(s)'
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'quitar_destacados':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'IDs requeridos']);
                exit;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE estudiantes SET destacado = 0 WHERE id IN ($placeholders)");
                $stmt->execute($ids);

                echo json_encode([
                    'success' => true,
                    'message' => count($ids) . ' estudiante(s): se quitó el estado destacado'
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'cambiar_estado':
            // Cambiar estado de matrícula
            $id = $_POST['id'] ?? 0;
            $estado = $_POST['estado'] ?? 'activo';
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE categoria_estudiantes SET estado = ? WHERE id = ?");
                $stmt->execute([$estado, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'preview_file':
            // Previsualizar archivo de estudiantes
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Error al cargar archivo']);
                exit;
            }
            
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                echo json_encode(['success' => false, 'message' => 'Formato no soportado']);
                exit;
            }
            
            try {
                $rows = leerArchivoEstudiantes($file['tmp_name'], $ext);
                
                if (empty($rows)) {
                    echo json_encode(['success' => false, 'message' => 'No se encontraron datos']);
                    exit;
                }
                
                // Verificar cuáles ya existen (mayores de edad por cédula)
                $cedulas = array_filter(array_column($rows, 'cedula'));
                $existentes_cedula = [];
                
                if (!empty($cedulas)) {
                    $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
                    $stmt = $pdo->prepare("SELECT cedula, id FROM estudiantes WHERE cedula IN ($placeholders)");
                    $stmt->execute($cedulas);
                    while ($row = $stmt->fetch()) {
                        $existentes_cedula[$row['cedula']] = $row['id'];
                    }
                }
                
                $nuevos = 0;
                $existentes = 0;
                
                foreach ($rows as &$row) {
                    $es_menor_val = strtolower(trim($row['es_menor'] ?? ''));
                    $es_menor = ($es_menor_val === 'si' || $es_menor_val === 'sí' || $es_menor_val === '1');
                    
                    $row['es_nuevo'] = true; // Por defecto es nuevo
                    
                    if ($es_menor) {
                        // Para menores: buscar por nombre + fecha_nacimiento + representante_cedula
                        $nombre = trim($row['nombre'] ?? '');
                        $fecha_nac = trim($row['fecha_nacimiento'] ?? '');
                        $rep_cedula = trim($row['representante_cedula'] ?? '');
                        
                        if (!empty($nombre) && !empty($fecha_nac) && !empty($rep_cedula)) {
                            $stmt = $pdo->prepare("
                                SELECT id FROM estudiantes 
                                WHERE nombre = ? AND fecha_nacimiento = ? AND representante_cedula = ? AND es_menor = 1
                            ");
                            $stmt->execute([$nombre, $fecha_nac, $rep_cedula]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $row['es_nuevo'] = false;
                                $row['estudiante_id'] = $existente['id'];
                                $existentes++;
                            } else {
                                $nuevos++;
                            }
                        } elseif (!empty($nombre) && !empty($fecha_nac)) {
                            // Sin cédula de representante, buscar por nombre + fecha
                            $stmt = $pdo->prepare("
                                SELECT id FROM estudiantes 
                                WHERE nombre = ? AND fecha_nacimiento = ? AND es_menor = 1
                            ");
                            $stmt->execute([$nombre, $fecha_nac]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $row['es_nuevo'] = false;
                                $row['estudiante_id'] = $existente['id'];
                                $existentes++;
                            } else {
                                $nuevos++;
                            }
                        } else {
                            $nuevos++;
                        }
                    } else {
                        // Para mayores: buscar por cédula
                        if (!empty($row['cedula']) && isset($existentes_cedula[$row['cedula']])) {
                            $row['es_nuevo'] = false;
                            $row['estudiante_id'] = $existentes_cedula[$row['cedula']];
                            $existentes++;
                        } else {
                            $nuevos++;
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'total' => count($rows),
                    'nuevos' => $nuevos,
                    'existentes' => $existentes,
                    'estudiantes' => $rows
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'procesar_archivo':
            // Procesar archivo y matricular estudiantes
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Error al cargar archivo']);
                exit;
            }
            
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $periodo_id = $_POST['periodo_id'] ?? null;
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            try {
                $rows = leerArchivoEstudiantes($file['tmp_name'], $ext);
                
                if (empty($rows)) {
                    echo json_encode(['success' => false, 'message' => 'No se encontraron datos']);
                    exit;
                }
                
                $pdo->beginTransaction();
                $matriculados = 0;
                
                foreach ($rows as $row) {
                    $nombre = trim($row['nombre'] ?? '');
                    if (empty($nombre)) continue;
                    
                    $fecha_nacimiento = trim($row['fecha_nacimiento'] ?? '');
                    $cedula = trim($row['cedula'] ?? '');
                    $celular = trim($row['celular'] ?? '');
                    $email = trim($row['email'] ?? '');
                    
                    // Campos para menores de edad
                    $es_menor_val = trim($row['es_menor'] ?? '');
                    $es_menor = (strtolower($es_menor_val) === 'si' || strtolower($es_menor_val) === 'sí' || $es_menor_val === '1') ? 1 : 0;
                    
                    $representante_nombre = trim($row['representante_nombre'] ?? '');
                    $representante_cedula = trim($row['representante_cedula'] ?? '');
                    $representante_celular = trim($row['representante_celular'] ?? '');
                    $representante_email = trim($row['representante_email'] ?? '');
                    $representante_fecha_nacimiento = trim($row['representante_fecha_nacimiento'] ?? '');
                    
                    // Formatear celulares con +593 si es necesario
                    if (!empty($celular) && !str_starts_with($celular, '+')) {
                        if (str_starts_with($celular, '0')) $celular = substr($celular, 1);
                        $celular = '+593' . $celular;
                    }
                    if (!empty($representante_celular) && !str_starts_with($representante_celular, '+')) {
                        if (str_starts_with($representante_celular, '0')) $representante_celular = substr($representante_celular, 1);
                        $representante_celular = '+593' . $representante_celular;
                    }
                    
                    // Buscar o crear estudiante
                    $estudiante_id = null;
                    
                    if ($es_menor) {
                        // Para menores: buscar por nombre + fecha_nacimiento + representante_cedula
                        // Esto evita duplicados cuando se carga el mismo menor varias veces
                        if (!empty($representante_cedula) && !empty($fecha_nacimiento)) {
                            $stmt = $pdo->prepare("
                                SELECT id FROM estudiantes 
                                WHERE nombre = ? 
                                AND fecha_nacimiento = ? 
                                AND representante_cedula = ?
                                AND es_menor = 1
                            ");
                            $stmt->execute([$nombre, $fecha_nacimiento, $representante_cedula]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $estudiante_id = $existente['id'];
                            }
                        } elseif (!empty($fecha_nacimiento)) {
                            // Si no hay cédula de representante, buscar por nombre + fecha_nacimiento
                            $stmt = $pdo->prepare("
                                SELECT id FROM estudiantes 
                                WHERE nombre = ? 
                                AND fecha_nacimiento = ?
                                AND es_menor = 1
                            ");
                            $stmt->execute([$nombre, $fecha_nacimiento]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $estudiante_id = $existente['id'];
                            }
                        }
                    } else {
                        // Para mayores: buscar por cédula (método original)
                        if (!empty($cedula)) {
                            $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ?");
                            $stmt->execute([$cedula]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $estudiante_id = $existente['id'];
                            }
                        }
                    }
                    
                    if (!$estudiante_id) {
                        $stmt = $pdo->prepare("
                            INSERT INTO estudiantes (
                                nombre, fecha_nacimiento, cedula, celular, email,
                                es_menor, representante_nombre, representante_cedula,
                                representante_celular, representante_email, representante_fecha_nacimiento,
                                activo
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            $nombre,
                            !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                            !empty($cedula) ? $cedula : null,
                            !empty($celular) ? $celular : null,
                            !empty($email) ? $email : null,
                            $es_menor,
                            !empty($representante_nombre) ? $representante_nombre : null,
                            !empty($representante_cedula) ? $representante_cedula : null,
                            !empty($representante_celular) ? $representante_celular : null,
                            !empty($representante_email) ? $representante_email : null,
                            !empty($representante_fecha_nacimiento) ? $representante_fecha_nacimiento : null
                        ]);
                        $estudiante_id = $pdo->lastInsertId();
                    }
                    
                    // Matricular (con período si está disponible)
                    $stmt = $pdo->prepare("
                        INSERT INTO categoria_estudiantes (categoria_id, periodo_id, estudiante_id, fecha_matricula, estado)
                        VALUES (?, ?, ?, ?, 'activo')
                        ON DUPLICATE KEY UPDATE estado = 'activo'
                    ");
                    $stmt->execute([$categoria_id, $periodo_id, $estudiante_id, date('Y-m-d')]);
                    
                    // === DESCOMENTAR PARA ENVIAR CORREO DE MATRÍCULA (carga masiva) ===
                    // enviarCorreoMatricula($pdo, $estudiante_id, $categoria_id);
                    
                    $matriculados++;
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'matriculados' => $matriculados,
                    'message' => "$matriculados estudiantes matriculados"
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
        
        case 'obtener_estudiante':
            // Obtener datos de un estudiante para edición
            $matricula_id = $_POST['matricula_id'] ?? ($jsonData['matricula_id'] ?? 0);
            $estudiante_id = $_POST['estudiante_id'] ?? ($jsonData['estudiante_id'] ?? 0);
            
            if (!$estudiante_id) {
                echo json_encode(['success' => false, 'message' => 'ID de estudiante requerido']);
                exit;
            }
            
            try {
                // Obtener datos del estudiante
                $stmt = $pdo->prepare("
                    SELECT id, nombre, cedula, celular, email, fecha_nacimiento,
                           es_menor, representante_nombre, representante_cedula,
                           representante_celular, representante_email, representante_fecha_nacimiento
                    FROM estudiantes
                    WHERE id = ?
                ");
                $stmt->execute([$estudiante_id]);
                $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$estudiante) {
                    echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
                    exit;
                }
                
                // Obtener datos de la matrícula si se proporcionó
                $matricula = null;
                if ($matricula_id) {
                    $stmt = $pdo->prepare("
                        SELECT id, fecha_matricula, estado, notas
                        FROM categoria_estudiantes
                        WHERE id = ?
                    ");
                    $stmt->execute([$matricula_id]);
                    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                echo json_encode([
                    'success' => true,
                    'estudiante' => $estudiante,
                    'matricula' => $matricula
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
        
        case 'actualizar_estudiante':
            // Actualizar datos de un estudiante
            if ($jsonData) {
                $estudiante_id = $jsonData['estudiante_id'] ?? 0;
                $matricula_id = $jsonData['matricula_id'] ?? 0;
                $nombre = trim($jsonData['nombre'] ?? '');
                $fecha_nacimiento = $jsonData['fecha_nacimiento'] ?? null;
                $cedula = trim($jsonData['cedula'] ?? '');
                $celular = trim($jsonData['celular'] ?? '');
                $email = trim($jsonData['email'] ?? '');
                $estado = $jsonData['estado'] ?? 'activo';
                
                // Campos de representante (para menores)
                $representante_nombre = trim($jsonData['representante_nombre'] ?? '');
                $representante_cedula = trim($jsonData['representante_cedula'] ?? '');
                $representante_celular = trim($jsonData['representante_celular'] ?? '');
                $representante_email = trim($jsonData['representante_email'] ?? '');
                $representante_fecha_nacimiento = $jsonData['representante_fecha_nacimiento'] ?? null;
            } else {
                $estudiante_id = $_POST['estudiante_id'] ?? 0;
                $matricula_id = $_POST['matricula_id'] ?? 0;
                $nombre = trim($_POST['nombre'] ?? '');
                $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
                $cedula = trim($_POST['cedula'] ?? '');
                $celular = trim($_POST['celular'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $estado = $_POST['estado'] ?? 'activo';
                
                // Campos de representante (para menores)
                $representante_nombre = trim($_POST['representante_nombre'] ?? '');
                $representante_cedula = trim($_POST['representante_cedula'] ?? '');
                $representante_celular = trim($_POST['representante_celular'] ?? '');
                $representante_email = trim($_POST['representante_email'] ?? '');
                $representante_fecha_nacimiento = $_POST['representante_fecha_nacimiento'] ?? null;
            }
            
            if (!$estudiante_id || !$nombre) {
                echo json_encode(['success' => false, 'message' => 'ID de estudiante y nombre requeridos']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();
                
                // Actualizar estudiante
                $stmt = $pdo->prepare("
                    UPDATE estudiantes SET
                        nombre = ?,
                        fecha_nacimiento = ?,
                        cedula = ?,
                        celular = ?,
                        email = ?,
                        representante_nombre = ?,
                        representante_cedula = ?,
                        representante_celular = ?,
                        representante_email = ?,
                        representante_fecha_nacimiento = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nombre,
                    !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                    !empty($cedula) ? $cedula : null,
                    !empty($celular) ? $celular : null,
                    !empty($email) ? $email : null,
                    !empty($representante_nombre) ? $representante_nombre : null,
                    !empty($representante_cedula) ? $representante_cedula : null,
                    !empty($representante_celular) ? $representante_celular : null,
                    !empty($representante_email) ? $representante_email : null,
                    !empty($representante_fecha_nacimiento) ? $representante_fecha_nacimiento : null,
                    $estudiante_id
                ]);
                
                // Actualizar estado de matrícula si se proporcionó
                if ($matricula_id && $estado) {
                    $stmt = $pdo->prepare("UPDATE categoria_estudiantes SET estado = ? WHERE id = ?");
                    $stmt->execute([$estado, $matricula_id]);
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Estudiante actualizado correctamente']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'cambiar_estado_masivo':
            // Cambiar estado de múltiples matrículas
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $estado = $_POST['estado'] ?? 'activo';
            
            if (empty($ids) || !is_array($ids)) {
                echo json_encode(['success' => false, 'message' => 'No se seleccionaron estudiantes']);
                exit;
            }
            
            if (!in_array($estado, ['activo', 'inactivo'])) {
                echo json_encode(['success' => false, 'message' => 'Estado no válido']);
                exit;
            }
            
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE categoria_estudiantes SET estado = ? WHERE id IN ($placeholders)");
                $params = array_merge([$estado], $ids);
                $stmt->execute($params);
                
                $affected = $stmt->rowCount();
                $estadoText = $estado === 'activo' ? 'activados' : 'desactivados';
                echo json_encode(['success' => true, 'message' => "$affected estudiantes $estadoText correctamente"]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'eliminar_masivo':
            // Eliminar múltiples matrículas de la categoría
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            
            if (empty($ids) || !is_array($ids)) {
                echo json_encode(['success' => false, 'message' => 'No se seleccionaron estudiantes']);
                exit;
            }
            
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM categoria_estudiantes WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                
                $affected = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "$affected estudiantes removidos de la categoría"]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    exit;
}

/**
 * Función para leer archivo de estudiantes (CSV o Excel)
 */
function leerArchivoEstudiantes($filepath, $ext) {
    $rows = [];
    
    if ($ext === 'csv') {
        if (($handle = fopen($filepath, 'r')) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ',');
            
            // Limpiar BOM
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            
            // Normalizar headers
            $headers = array_map(function($h) {
                return strtolower(trim($h));
            }, $headers);
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (count($data) === count($headers)) {
                    $row = array_combine($headers, $data);
                    if (!empty(trim($row['nombre'] ?? ''))) {
                        $rows[] = $row;
                    }
                }
            }
            fclose($handle);
        }
    } else {
        $spreadsheet = IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        // Leer headers
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $value = $worksheet->getCell($columnLetter . '1')->getValue();
            if ($value !== null) {
                $headers[] = strtolower(trim($value));
            }
        }
        
        // Leer datos
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 1; $col <= count($headers); $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                $rowData[$headers[$col - 1]] = $cellValue;
                if (!empty($cellValue)) $hasData = true;
            }
            
            if ($hasData && !empty(trim($rowData['nombre'] ?? ''))) {
                $rows[] = $rowData;
            }
        }
    }
    
    return $rows;
}

/**
 * Guardar referencias personales de un estudiante
 */
function guardarReferencias($pdo, $estudiante_id, $referencias) {
    if (empty($referencias) || !is_array($referencias)) return;
    
    try {
        // Eliminar referencias anteriores para reemplazar
        $stmt = $pdo->prepare("DELETE FROM estudiantes_referencias WHERE estudiante_id = ?");
        $stmt->execute([$estudiante_id]);

        $stmtInsert = $pdo->prepare("INSERT INTO estudiantes_referencias (estudiante_id, nombre, telefono, relacion) VALUES (?, ?, ?, ?)");

        foreach ($referencias as $ref) {
            $nombre = trim($ref['nombre'] ?? '');
            $telefono = trim($ref['telefono'] ?? '');
            $relacion = trim($ref['relacion'] ?? '');

            if (!empty($nombre)) {
                $stmtInsert->execute([$estudiante_id, $nombre, $telefono, $relacion]);
            }
        }
    } catch (PDOException $e) {
        error_log("Error guardando referencias: " . $e->getMessage());
        // No interrumpimos el flujo principal por error en referencias
    }
}
