<?php
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Rutas absolutas para evitar errores de include
require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
$logFile = __DIR__ . '/debug_api.log';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

header('Content-Type: application/json');

// Verificar autenticación
Auth::requireAuth();

try {
    $pdo = getConnection();
    $usuario = Auth::user();
    $esInstructor = Auth::isInstructor();
    
    // Leer datos JSON del body o FormData POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay JSON, intentar con POST/GET
    if (!$input) {
        $input = array_merge($_GET, $_POST);
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    file_put_contents($logFile, "Auth Passed. Action resolved: '$action'\n", FILE_APPEND);
    
    // Función auxiliar para obtener categorías asignadas al instructor
    function getCategoriasInstructor($pdo, $usuario_id) {
        $stmt = $pdo->prepare("SELECT categoria_id FROM instructor_categorias WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Función auxiliar para obtener grupos asignados al instructor
    function getGruposInstructor($pdo, $usuario_id) {
        $stmt = $pdo->prepare("SELECT grupo_id FROM instructor_grupos WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    switch ($action) {
        case 'list':
            // Verificar permiso de ver estudiantes
            Auth::requirePermission('estudiantes', 'ver');
            
            $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
            $search = $input['search'] ?? '';
            
            // Si es instructor, filtrar solo estudiantes de sus categorías
            if ($esInstructor) {
                $categoriasIds = getCategoriasInstructor($pdo, $usuario['id']);
                if (empty($categoriasIds)) {
                    echo json_encode(['success' => true, 'estudiantes' => [], 'total' => 0]);
                    break;
                }
                $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
                $sql = "SELECT DISTINCT e.* FROM estudiantes e
                        INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                        WHERE ce.categoria_id IN ($placeholders)";
                $params = $categoriasIds;
                
                if (!empty($search)) {
                    $sql .= " AND (e.nombre LIKE ? OR e.cedula LIKE ? OR e.celular LIKE ?)";
                    $searchTerm = "%$search%";
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                }
            } else {
                $sql = "SELECT * FROM estudiantes";
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " WHERE nombre LIKE ? OR cedula LIKE ? OR celular LIKE ?";
                    $searchTerm = "%$search%";
                    $params = [$searchTerm, $searchTerm, $searchTerm];
                }
            }
            
            $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total
            $countSql = "SELECT COUNT(*) FROM estudiantes";
            if (!empty($search)) {
                $countSql .= " WHERE nombre LIKE ? OR cedula LIKE ? OR celular LIKE ?";
                $stmt = $pdo->prepare($countSql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($countSql);
            }
            $total = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes,
                'total' => $total
            ]);
            break;
        
        case 'buscar':
            // Verificar permiso
            Auth::requirePermission('estudiantes', 'ver');
            
            // Buscar estudiante por cédula o ID exacto
            $q = trim($input['q'] ?? $_GET['q'] ?? '');
            
            if (empty($q)) {
                echo json_encode(['success' => false, 'message' => 'Parámetro de búsqueda requerido']);
                break;
            }
            
            // Buscar primero por cédula exacta, luego por ID
            $stmt = $pdo->prepare("
                SELECT * FROM estudiantes 
                WHERE cedula = ? OR id = ?
                LIMIT 1
            ");
            $stmt->execute([$q, $q]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estudiante) {
                echo json_encode([
                    'success' => true,
                    'estudiante' => $estudiante
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se encontró ningún estudiante con esa cédula o ID'
                ]);
            }
            break;
        
        case 'buscar_disponibles':
            // Buscar estudiantes que NO están matriculados en una categoría específica
            // Incluye también "solo representantes" que pueden convertirse en estudiantes
            Auth::requirePermission('estudiantes', 'ver');
            
            $q = trim($input['q'] ?? $_GET['q'] ?? '');
            $categoria_id = $input['categoria_id'] ?? $_GET['categoria_id'] ?? 0;
            $periodo_id = $input['periodo_id'] ?? $_GET['periodo_id'] ?? 0;
            
            if (strlen($q) < 2) {
                echo json_encode(['success' => true, 'estudiantes' => []]);
                break;
            }
            
            $searchTerm = "%$q%";
            
            // Buscar estudiantes que no están en la categoría (incluye solo representantes)
            // Buscar estudiantes (incluidos los ya matriculados para mostrar mensaje)
            // Si se proporciona periodo_id, verificar matrícula específica en ese periodo
            $sqlMatriculado = "SELECT COUNT(*) FROM categoria_estudiantes ce WHERE ce.estudiante_id = e.id AND ce.categoria_id = ?";
            $paramsMatricula = [$categoria_id];
            
            if (!empty($periodo_id)) {
                $sqlMatriculado .= " AND ce.periodo_id = ?";
                $paramsMatricula[] = $periodo_id;
            }
            
            // Construir consulta principal
            $stmt = $pdo->prepare("
                SELECT e.id, e.nombre, e.cedula, e.celular, e.email, e.es_solo_representante,
                ($sqlMatriculado) as ya_matriculado
                FROM estudiantes e
                WHERE (e.nombre LIKE ? OR e.cedula LIKE ?)
                AND e.activo = 1
                AND e.es_menor = 0
                ORDER BY e.es_solo_representante DESC, e.nombre
                LIMIT 10
            ");
            
            // Combinar parámetros: [params matricula subquery] + [search term, search term]
            $allParams = array_merge($paramsMatricula, [$searchTerm, $searchTerm]);
            $stmt->execute($allParams);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes
            ]);
            break;
        
        case 'list_with_details':
            // Verificar permiso
            Auth::requirePermission('estudiantes', 'ver');
            
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
            $search = $input['search'] ?? '';
            $grupo_id = $input['grupo_id'] ?? '';
            $categoria_id = $input['categoria_id'] ?? '';
            
            // Construir consulta con JOINs para obtener grupos y categorías
            // Usamos categoria_estudiantes para obtener las inscripciones reales, no solo certificados
            $sql = "SELECT DISTINCT e.*, 
                    GROUP_CONCAT(DISTINCT CONCAT(g.id, '##', g.nombre, '##', COALESCE(g.color, '#cccccc'), '##', COALESCE(g.icono, ''), '##', cat.id, '##', cat.nombre, '##', COALESCE(cat.icono, ''), '##', COALESCE(p.nombre, '-')) SEPARATOR '||') as enrollment_data,
                    COUNT(DISTINCT ec.id) as total_certificados,
                    (SELECT COUNT(*) FROM estudiantes child WHERE child.representante_id = e.id) as num_hijos
                    FROM estudiantes e
                    LEFT JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                    LEFT JOIN categorias cat ON ce.categoria_id = cat.id
                    LEFT JOIN grupos g ON cat.grupo_id = g.id
                    LEFT JOIN periodos p ON ce.periodo_id = p.id
                    LEFT JOIN estudiante_certificados ec ON e.id = ec.estudiante_id";
            
            $where = [];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(e.nombre LIKE ? OR e.cedula LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($grupo_id)) {
                $where[] = "ce.grupo_id = ?"; // Using ce (enrollments) effectively via category join? Wait, ce doesn't have grupo_id directly.
                // Correct logic: ce -> cat -> grupo
                $where[] = "g.id = ?";
                $params[] = $grupo_id;
            }
            
            if (!empty($categoria_id)) {
                $where[] = "ce.categoria_id = ?";
                $params[] = $categoria_id;
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Order by Family (Representative ID or own ID), then by Role (Representative=0, Minor=1), then by ID
            $sql .= " GROUP BY e.id ORDER BY COALESCE(e.representante_id, e.id) DESC, e.es_menor ASC, e.id ASC LIMIT $limit OFFSET $offset";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $countSql = "SELECT COUNT(DISTINCT e.id) FROM estudiantes e
                        LEFT JOIN estudiante_certificados ec ON e.id = ec.estudiante_id";
            
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(" AND ", $where);
            }
            
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantes,
                'total' => (int)$total
            ]);
            break;
        

            
        case 'get':
            // Verificar permiso
            Auth::requirePermission('estudiantes', 'ver');
            
            $id = $input['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'estudiante' => $estudiante
            ]);
            break;
            
        case 'create':
            // Verificar permiso de crear estudiantes
            Auth::requirePermission('estudiantes', 'crear');
            
            $nombre = $input['nombre'] ?? '';
            $cedula = $input['cedula'] ?? null;
            $celular = $input['celular'] ?? null;
            $fecha_nacimiento = $input['fecha_nacimiento'] ?? null;
            $destacado = isset($input['destacado']) ? (int)$input['destacado'] : 0;
            
            if (empty($nombre)) {
                throw new Exception('El nombre es requerido');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO estudiantes (nombre, cedula, celular, fecha_nacimiento, destacado)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$nombre, $cedula, $celular, $fecha_nacimiento, $destacado]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante creado correctamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'update':
            // Verificar permiso de editar estudiantes
            Auth::requirePermission('estudiantes', 'editar');
            
            $id = $input['id'] ?? 0;
            $nombre = $input['nombre'] ?? '';
            $cedula = $input['cedula'] ?? null;
            $celular = $input['celular'] ?? null;
            $email = $input['email'] ?? null;
            $fecha_nacimiento = $input['fecha_nacimiento'] ?? null;
            $destacado = isset($input['destacado']) ? (int)$input['destacado'] : 0;
            $es_menor = isset($input['es_menor']) ? (int)$input['es_menor'] : 0;
            
            if (empty($id) || empty($nombre)) {
                throw new Exception('ID y nombre son requeridos');
            }
            
            // Formatear celular si viene con prefijo
            if ($celular) {
                $celular = preg_replace('/[^0-9]/', '', $celular);
                if (strlen($celular) === 9) {
                    $celular = '+593' . $celular;
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE estudiantes 
                SET nombre = ?, cedula = ?, celular = ?, email = ?, fecha_nacimiento = ?, destacado = ?, es_menor = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$nombre, $cedula, $celular, $email, $fecha_nacimiento, $destacado, $es_menor, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante actualizado correctamente'
            ]);
            break;
            
        case 'delete':
            // Verificar permiso de eliminar estudiantes
            Auth::requirePermission('estudiantes', 'eliminar');
            
            $id = $input['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante eliminado correctamente'
            ]);
            break;

        case 'quitar_de_grupo':
            // Verificar permiso de editar estudiantes/grupos
            Auth::requirePermission('estudiantes', 'editar');

            $estudiante_id = $input['id'] ?? 0;
            $grupo_id = $input['grupo_id'] ?? 0;

            if (empty($estudiante_id) || empty($grupo_id)) {
                throw new Exception('ID de estudiante y grupo son requeridos');
            }

            // Eliminar inscripciones del estudiante en todas las categorías de este grupo
            $stmt = $pdo->prepare("
                DELETE ce 
                FROM categoria_estudiantes ce
                INNER JOIN categorias c ON ce.categoria_id = c.id
                WHERE ce.estudiante_id = ? AND c.grupo_id = ?
            ");
            $stmt->execute([$estudiante_id, $grupo_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Estudiante quitado del grupo correctamente'
            ]);
            break;
        
        case 'listar_grupo':
            // Verificar permiso
            Auth::requirePermission('estudiantes', 'ver');
            
            // Listar todos los estudiantes de un grupo (todas las categorías y períodos)
            $grupo_id = $input['grupo_id'] ?? $_GET['grupo_id'] ?? 0;
            
            if (empty($grupo_id)) {
                throw new Exception('ID del grupo es requerido');
            }
            
            // Si es instructor, verificar que tenga acceso al grupo
            if ($esInstructor) {
                $gruposIds = getGruposInstructor($pdo, $usuario['id']);
                if (!in_array($grupo_id, $gruposIds)) {
                    throw new Exception('No tiene acceso a este grupo');
                }
            }
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    e.id,
                    e.nombre,
                    e.cedula,
                    e.celular,
                    e.email,
                    e.email,
                    e.fecha_nacimiento,
                    e.fecha_creacion,
                    e.es_menor,
                    e.representante_nombre,
                    e.representante_cedula,
                    e.representante_celular,
                    e.representante_email,
                    ce.estado,
                    ce.categoria_id,
                    ce.periodo_id,
                    c.nombre as categoria_nombre,
                    c.icono as categoria_icono,
                    c.color as categoria_color,
                    p.nombre as periodo_nombre,
                    p.fecha_inicio as periodo_fecha_inicio
                FROM estudiantes e
                INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                INNER JOIN categorias c ON ce.categoria_id = c.id
                INNER JOIN periodos p ON ce.periodo_id = p.id
                WHERE c.grupo_id = ?
                ORDER BY c.nombre ASC, e.nombre ASC
            ");
            $stmt->execute([$grupo_id]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por estudiante
            $estudiantesMap = [];
            
            foreach ($resultados as $row) {
                $id = $row['id'];
                
                if (!isset($estudiantesMap[$id])) {
                    // Datos base del estudiante
                    $estudiantesMap[$id] = [
                        'id' => $row['id'],
                        'nombre' => $row['nombre'],
                        'cedula' => $row['cedula'],
                        'celular' => $row['celular'],
                        'email' => $row['email'],
                        'fecha_nacimiento' => $row['fecha_nacimiento'],
                        'fecha_creacion' => $row['fecha_creacion'],
                        'es_menor' => $row['es_menor'],
                        'representante_nombre' => $row['representante_nombre'],
                        'representante_cedula' => $row['representante_cedula'],
                        'representante_celular' => $row['representante_celular'],
                        'representante_email' => $row['representante_email'],
                        // Estado general (tomamos el del registro actual, aunque podría variar por categoría)
                        'estado' => $row['estado'],
                        'categorias' => []
                    ];
                }
                
                // Agregar categoría a la lista
                $estudiantesMap[$id]['categorias'][] = [
                    'id' => $row['categoria_id'],
                    'nombre' => $row['categoria_nombre'],
                    'icono' => $row['categoria_icono'],
                    'color' => $row['categoria_color'],
                    'periodo' => $row['periodo_nombre'],
                    'fecha_inicio' => $row['periodo_fecha_inicio'],
                    'periodo_id' => $row['periodo_id'],
                    'estado' => $row['estado']
                ];
            }
            
            // Convertir a array indexado
            $estudiantesFinal = array_values($estudiantesMap);
            
            echo json_encode([
                'success' => true,
                'estudiantes' => $estudiantesFinal,
                'total' => count($estudiantesFinal)
            ]);
            break;
        
        case 'primer_estudiante':
            // Obtener el primer estudiante de un grupo (para preview de plantilla)
            $grupo_id = $input['grupo_id'] ?? $_GET['grupo_id'] ?? 0;
            
            if (!$grupo_id) {
                echo json_encode(['success' => false, 'message' => 'grupo_id requerido']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT e.nombre, c.nombre as categoria_nombre
                FROM estudiantes e
                INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                INNER JOIN categorias c ON ce.categoria_id = c.id
                WHERE c.grupo_id = ?
                ORDER BY e.id ASC
                LIMIT 1
            ");
            $stmt->execute([$grupo_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estudiante) {
                echo json_encode([
                    'success' => true,
                    'estudiante' => [
                        'nombre_completo' => trim($estudiante['nombre']),
                        'categoria_nombre' => $estudiante['categoria_nombre']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No hay estudiantes en este grupo'
                ]);
            }
            break;

        case 'get_details':
            // Verificar permiso
            Auth::requirePermission('estudiantes', 'ver');

            $id = $input['id'] ?? $_GET['id'] ?? 0;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ACTION: get_details - ID: $id\n", FILE_APPEND);
            
            if (!$id) {
                throw new Exception('ID requerido');
            }

            // 1. Intentar buscar por estudiante_id (más robusto)
            $sql = "
                SELECT DISTINCT c.codigo, c.fecha, c.fechas_generacion, 
                       cat.nombre as categoria_nombre, cat.icono as categoria_icono, cat.color as categoria_color,
                       g.id as grupo_id, g.nombre as grupo_nombre,
                       p.nombre as periodo_nombre
                FROM certificados c
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                LEFT JOIN grupos g ON cat.grupo_id = g.id
                -- Link via GROUP and NAME to find distinct subject enrollments across periods
                LEFT JOIN categorias cat_linked ON cat_linked.grupo_id = g.id AND cat_linked.nombre = cat.nombre
                LEFT JOIN categoria_estudiantes ce ON ce.categoria_id = cat_linked.id AND ce.estudiante_id = c.estudiante_id
                LEFT JOIN periodos p ON ce.periodo_id = p.id
                WHERE c.estudiante_id = ?
                AND p.id IS NOT NULL 
                ORDER BY p.fecha_inicio DESC, c.fecha DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            file_put_contents($logFile, "Result count for ID $id: " . count($certificados) . "\n", FILE_APPEND);

            // 2. Si no hay certificados por ID, intentar por nombre (fallback compatibilidad)
            if (empty($certificados)) {
                // Obtener nombre del estudiante
                $stmtEst = $pdo->prepare("SELECT nombre FROM estudiantes WHERE id = ?");
                $stmtEst->execute([$id]);
                $nombreEstudiante = $stmtEst->fetchColumn();

                if ($nombreEstudiante) {
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT c.codigo, c.fecha, c.fechas_generacion, 
                               cat.nombre as categoria_nombre, cat.icono as categoria_icono, cat.color as categoria_color,
                               g.id as grupo_id, g.nombre as grupo_nombre,
                               p.nombre as periodo_nombre
                        FROM certificados c
                        LEFT JOIN categorias cat ON c.categoria_id = cat.id
                        LEFT JOIN grupos g ON cat.grupo_id = g.id
                        -- Link via GROUP and NAME
                        LEFT JOIN categorias cat_linked ON cat_linked.grupo_id = g.id AND cat_linked.nombre = cat.nombre
                        LEFT JOIN categoria_estudiantes ce ON ce.categoria_id = cat_linked.id AND ce.estudiante_id = c.estudiante_id
                        LEFT JOIN periodos p ON ce.periodo_id = p.id
                        WHERE (TRIM(c.nombre) = TRIM(?) OR c.nombre LIKE ?)
                        AND p.id IS NOT NULL
                        ORDER BY p.fecha_inicio DESC, c.fecha DESC
                    ");
                    $stmt->execute([$nombreEstudiante, '%' . $nombreEstudiante . '%']);
                    $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // Obtener datos del estudiante
            $stmtEstData = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
            $stmtEstData->execute([$id]);
            $estudianteData = $stmtEstData->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'estudiante' => $estudianteData,
                'certificados' => $certificados,
                'debug_info' => [
                    'received_id' => $id,
                    'count' => count($certificados),
                    'fallback_used' => empty($certificados) && !empty($nombreEstudiante)
                ]
            ]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
