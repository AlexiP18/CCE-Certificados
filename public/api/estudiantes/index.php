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
            $tipo_filtro = strtolower(trim($input['tipo_filtro'] ?? 'todos'));
            $tiposPermitidos = ['todos', 'representante', 'mayores', 'destacados'];
            if (!in_array($tipo_filtro, $tiposPermitidos, true)) {
                $tipo_filtro = 'todos';
            }
            
            // Construir consulta con JOINs para obtener grupos y categorías
            // Usamos categoria_estudiantes para obtener las inscripciones reales, no solo certificados
            $sql = "SELECT DISTINCT e.*, 
                    GROUP_CONCAT(DISTINCT CONCAT(
                        g.id, '##', g.nombre, '##', COALESCE(g.color, '#cccccc'), '##', COALESCE(g.icono, ''), '##',
                        cat.id, '##', cat.nombre, '##', COALESCE(cat.icono, ''), '##', COALESCE(p.nombre, '-'), '##',
                        COALESCE(ce.es_destacado, 0), '##', COALESCE(DATE_FORMAT(p.fecha_inicio, '%Y-%m-%d'), '0000-00-00'), '##',
                        COALESCE(DATE_FORMAT(ce.fecha_matricula, '%Y-%m-%d'), '')
                    ) SEPARATOR '||') as enrollment_data,
                    COUNT(DISTINCT c.id) as total_certificados,
                    (SELECT COUNT(*) FROM estudiantes_referencias er WHERE er.estudiante_id = e.id) as tiene_referencias,
                    (SELECT COUNT(*) FROM estudiantes child WHERE child.representante_id = e.id) as num_hijos
                    FROM estudiantes e
                    LEFT JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                    LEFT JOIN categorias cat ON ce.categoria_id = cat.id
                    LEFT JOIN grupos g ON cat.grupo_id = g.id
                    LEFT JOIN periodos p ON ce.periodo_id = p.id
                    LEFT JOIN certificados c ON e.id = c.estudiante_id";
            
            $where = [];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(e.nombre LIKE ? OR e.cedula LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($grupo_id)) {
                $where[] = "g.id = ?";
                $params[] = $grupo_id;
            }
            
            if (!empty($categoria_id)) {
                $where[] = "cat.id = ?";
                $params[] = $categoria_id;
            }

            if ($tipo_filtro === 'representante') {
                $where[] = "(e.es_menor = 1 OR COALESCE(e.representante_id, 0) > 0 OR EXISTS (SELECT 1 FROM estudiantes eh WHERE eh.representante_id = e.id))";
            } elseif ($tipo_filtro === 'mayores') {
                $where[] = "e.es_menor = 0";
            } elseif ($tipo_filtro === 'destacados') {
                $where[] = "(COALESCE(e.destacado, 0) = 1 OR EXISTS (
                    SELECT 1
                    FROM categoria_estudiantes cex
                    WHERE cex.estudiante_id = e.id
                      AND COALESCE(cex.es_destacado, 0) = 1
                ))";
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
                        LEFT JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                        LEFT JOIN categorias cat ON ce.categoria_id = cat.id
                        LEFT JOIN grupos g ON cat.grupo_id = g.id
                        LEFT JOIN periodos p ON ce.periodo_id = p.id";
            
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
            if (empty($nombre)) {
                throw new Exception('El nombre es requerido');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO estudiantes (nombre, cedula, celular, fecha_nacimiento)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$nombre, $cedula, $celular, $fecha_nacimiento]);
            
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
            $es_menor = isset($input['es_menor']) ? (int)$input['es_menor'] : 0;
            $representante_nombre = $input['representante_nombre'] ?? null;
            $representante_cedula = $input['representante_cedula'] ?? null;
            $representante_celular = $input['representante_celular'] ?? null;
            $representante_email = $input['representante_email'] ?? null;
            $representante_fecha_nacimiento = $input['representante_fecha_nacimiento'] ?? null;
            $referencias = null;
            if (array_key_exists('referencias', $input)) {
                $referenciasRaw = $input['referencias'];
                if (is_string($referenciasRaw)) {
                    $decodedRefs = json_decode($referenciasRaw, true);
                    $referencias = is_array($decodedRefs) ? $decodedRefs : [];
                } elseif (is_array($referenciasRaw)) {
                    $referencias = $referenciasRaw;
                } else {
                    $referencias = [];
                }
            }
            
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

            // Formatear celular representante
            if ($representante_celular) {
                $representante_celular = preg_replace('/[^0-9]/', '', $representante_celular);
                if (strlen($representante_celular) === 9) {
                    $representante_celular = '+593' . $representante_celular;
                }
            }
            
            // Fetch old state for audit
            $stmtOld = $pdo->prepare("SELECT nombre, cedula, celular, email, fecha_nacimiento, es_menor FROM estudiantes WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            // Fetch old representative data for audit if minor
            $oldRepData = [];
            if ($es_menor && $oldData) {
                $r_stmt = $pdo->prepare("SELECT representante_id FROM estudiantes WHERE id = ?");
                $r_stmt->execute([$id]);
                $r_id = $r_stmt->fetchColumn();
                if ($r_id) {
                    $stmtOldRep = $pdo->prepare("SELECT nombre as representante_nombre, cedula as representante_cedula, celular as representante_celular, email as representante_email, fecha_nacimiento as representante_fecha_nacimiento FROM estudiantes WHERE id = ?");
                    $stmtOldRep->execute([$r_id]);
                    $oldRepData = $stmtOldRep->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            $newData = [
                'nombre' => $nombre,
                'cedula' => !empty($cedula) ? $cedula : null,
                'celular' => !empty($celular) ? $celular : null,
                'email' => !empty($email) ? $email : null,
                'fecha_nacimiento' => !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                'es_menor' => $es_menor
            ];
            
            $cambios = [];
            if ($oldData) {
                foreach ($newData as $key => $val) {
                    if ($oldData[$key] != $val) {
                        $cambios[$key] = [
                            'old' => $oldData[$key],
                            'new' => $val
                        ];
                    }
                }
            }

            $stmt = $pdo->prepare("
                UPDATE estudiantes 
                SET nombre = ?, cedula = ?, celular = ?, email = ?, fecha_nacimiento = ?, 
                    es_menor = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $newData['nombre'], $newData['cedula'], $newData['celular'], $newData['email'], $newData['fecha_nacimiento'], 
                $newData['es_menor'],
                $id
            ]);
            
            // Sincronización de datos de representante (solo se actualiza su propia fila)
            if ($es_menor) {
                $r_stmt = $pdo->prepare("SELECT representante_id FROM estudiantes WHERE id = ?");
                $r_stmt->execute([$id]);
                $r_id = $r_stmt->fetchColumn();
                
                if ($r_id) {
                    $newRepData = [
                        'representante_nombre' => $representante_nombre,
                        'representante_cedula' => !empty($representante_cedula) ? $representante_cedula : null,
                        'representante_celular' => !empty($representante_celular) ? $representante_celular : null,
                        'representante_email' => !empty($representante_email) ? $representante_email : null,
                        'representante_fecha_nacimiento' => !empty($representante_fecha_nacimiento) ? $representante_fecha_nacimiento : null
                    ];

                    if ($oldRepData) {
                        foreach ($newRepData as $key => $val) {
                            if ($oldRepData[$key] != $val) {
                                $cambios[$key] = [
                                    'old' => $oldRepData[$key],
                                    'new' => $val
                                ];
                            }
                        }
                    }

                    // Actualizar fila real del representante
                    $upd_rep = $pdo->prepare("
                        UPDATE estudiantes 
                        SET nombre = ?, cedula = ?, celular = ?, email = ?, fecha_nacimiento = ?
                        WHERE id = ?
                    ");
                    $upd_rep->execute([
                        $newRepData['representante_nombre'], $newRepData['representante_cedula'], $newRepData['representante_celular'], $newRepData['representante_email'],
                        $newRepData['representante_fecha_nacimiento'],
                        $r_id
                    ]);
                }
            }

            // Actualizar referencias personales si se enviaron desde el frontend
            if ($referencias !== null) {
                $stmtDelRefs = $pdo->prepare("DELETE FROM estudiantes_referencias WHERE estudiante_id = ?");
                $stmtDelRefs->execute([$id]);

                if (!empty($referencias)) {
                    $stmtInsRefs = $pdo->prepare("INSERT INTO estudiantes_referencias (estudiante_id, nombre, telefono, relacion) VALUES (?, ?, ?, ?)");
                    $countRefs = 0;
                    foreach ($referencias as $ref) {
                        if ($countRefs >= 3) break;
                        if (!is_array($ref)) continue;

                        $refNombre = trim((string)($ref['nombre'] ?? ''));
                        $refRelacion = trim((string)($ref['relacion'] ?? ''));
                        $refTelefono = preg_replace('/[^0-9]/', '', (string)($ref['telefono'] ?? ''));
                        if (strlen($refTelefono) > 9) {
                            $refTelefono = substr($refTelefono, -9);
                        }

                        if ($refNombre === '') continue;

                        $stmtInsRefs->execute([$id, $refNombre, $refTelefono, $refRelacion]);
                        $countRefs++;
                    }
                }
            }

            // Insert into audit table if there are changes
            if (!empty($cambios)) {
                $stmtAudit = $pdo->prepare("INSERT INTO estudiantes_auditoria (estudiante_id, usuario_id, accion, detalles) VALUES (?, ?, 'actualizacion', ?)");
                $stmtAudit->execute([$id, $_SESSION['usuario_id'] ?? null, json_encode($cambios)]);
            }
            
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
            
            $stmt = $pdo->prepare("UPDATE estudiantes SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante dado de baja correctamente'
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
                    e.fecha_nacimiento,
                    e.fecha_creacion,
                    e.fecha_actualizacion,
                    e.es_menor,
                    COALESCE(ce.es_destacado, 0) as es_destacado,
                    rep.nombre as representante_nombre,
                    rep.cedula as representante_cedula,
                    rep.celular as representante_celular,
                    rep.email as representante_email,
                    rep.fecha_nacimiento as representante_fecha_nacimiento,
                    e.representante_id,
                    (SELECT COUNT(*) FROM estudiantes_referencias er WHERE er.estudiante_id = e.id) as tiene_referencias,
                    ce.estado,
                    ce.categoria_id,
                    ce.periodo_id,
                    ce.fecha_matricula as categoria_fecha_matricula,
                    c.nombre as categoria_nombre,
                    c.icono as categoria_icono,
                    p.nombre as periodo_nombre,
                    p.fecha_inicio as periodo_fecha_inicio,
                    (
                        SELECT MAX(
                            CASE
                                WHEN IFNULL(cert_apr.aprobado, 0) = 1 OR cert_apr.id IS NOT NULL THEN 1
                                ELSE 0
                            END
                        )
                        FROM certificados cert_apr
                        WHERE cert_apr.estudiante_id = e.id
                          AND cert_apr.categoria_id = ce.categoria_id
                          AND cert_apr.periodo_id <=> ce.periodo_id
                    ) as cert_aprobado,
                    (
                        SELECT MAX(
                            CASE
                                WHEN cert_gen.id IS NOT NULL
                                     AND (
                                         NULLIF(TRIM(cert_gen.archivo_pdf), '') IS NOT NULL
                                         OR NULLIF(TRIM(cert_gen.archivo_imagen), '') IS NOT NULL
                                     ) THEN 1
                                ELSE 0
                            END
                        )
                        FROM certificados cert_gen
                        WHERE cert_gen.estudiante_id = e.id
                          AND cert_gen.categoria_id = ce.categoria_id
                          AND cert_gen.periodo_id <=> ce.periodo_id
                    ) as cert_generado
                FROM estudiantes e
                LEFT JOIN estudiantes rep ON e.representante_id = rep.id
                INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
                INNER JOIN categorias c ON ce.categoria_id = c.id
                LEFT JOIN periodos p ON ce.periodo_id = p.id
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
                        'fecha_actualizacion' => $row['fecha_actualizacion'],
                        'es_menor' => $row['es_menor'],
                        'es_destacado' => $row['es_destacado'],
                        'representante_nombre' => $row['representante_nombre'],
                        'representante_cedula' => $row['representante_cedula'],
                        'representante_celular' => $row['representante_celular'],
                        'representante_email' => $row['representante_email'],
                        'representante_fecha_nacimiento' => $row['representante_fecha_nacimiento'],
                        'representante_id' => $row['representante_id'],
                        'tiene_referencias' => $row['tiene_referencias'],
                        // Estado general (tomamos el del registro actual, aunque podría variar por categoría)
                        'estado' => $row['estado'],
                        'categorias' => []
                    ];
                }
                
                $estudiantesMap[$id]['categorias'][] = [
                    'id' => $row['categoria_id'],
                    'nombre' => $row['categoria_nombre'],
                    'icono' => $row['categoria_icono'],
                    'color' => '#3498db', // Fallback color as column dropped
                    'periodo' => $row['periodo_nombre'],
                    'fecha_inicio' => $row['periodo_fecha_inicio'],
                    'fecha_matricula' => $row['categoria_fecha_matricula'],
                    'periodo_id' => $row['periodo_id'],
                    'estado' => $row['estado'],
                    'es_destacado' => $row['es_destacado'],
                    'cert_aprobado' => (int)($row['cert_aprobado'] ?? 0),
                    'cert_generado' => (int)($row['cert_generado'] ?? 0)
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
            $nombreEstudiante = null;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ACTION: get_details - ID: $id\n", FILE_APPEND);
            
            if (!$id) {
                throw new Exception('ID requerido');
            }

            // 1. Intentar buscar por estudiante_id (más robusto)
            $sql = "
                SELECT DISTINCT 
                       c.id,
                       c.estudiante_id,
                       c.categoria_id,
                       c.periodo_id,
                       c.codigo,
                       c.fecha,
                       c.fecha_creacion,
                       c.aprobado,
                       c.aprobado_por,
                       c.fecha_aprobacion,
                       ua.nombre_completo as aprobado_por_nombre,
                       c.archivo_pdf,
                       c.archivo_imagen,
                       c.fechas_generacion, 
                       cat.nombre as categoria_nombre,
                       cat.icono as categoria_icono,
                       '#e67e22' as categoria_color,
                       g.id as grupo_id,
                       g.nombre as grupo_nombre,
                       COALESCE(p_cert.nombre, 'Sin período') as periodo_nombre,
                       COALESCE(p_cert.fecha_inicio, '0000-00-00') as periodo_fecha_inicio,
                       (
                           SELECT COALESCE(MAX(cex.es_destacado), 0)
                           FROM categoria_estudiantes cex
                           WHERE cex.estudiante_id = c.estudiante_id
                             AND cex.categoria_id = c.categoria_id
                             AND cex.periodo_id <=> c.periodo_id
                       ) as es_destacado
                FROM certificados c
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                LEFT JOIN grupos g ON cat.grupo_id = g.id
                LEFT JOIN periodos p_cert ON c.periodo_id = p_cert.id
                LEFT JOIN usuarios ua ON c.aprobado_por = ua.id
                WHERE c.estudiante_id = ?
                  AND (
                      NULLIF(TRIM(c.archivo_pdf), '') IS NOT NULL
                      OR NULLIF(TRIM(c.archivo_imagen), '') IS NOT NULL
                  )
                ORDER BY periodo_fecha_inicio DESC, c.fecha DESC
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
                        SELECT DISTINCT 
                               c.id,
                               c.estudiante_id,
                               c.categoria_id,
                               c.periodo_id,
                               c.codigo,
                               c.fecha,
                               c.fecha_creacion,
                               c.aprobado,
                               c.aprobado_por,
                               c.fecha_aprobacion,
                               ua.nombre_completo as aprobado_por_nombre,
                               c.archivo_pdf,
                               c.archivo_imagen,
                               c.fechas_generacion, 
                               cat.nombre as categoria_nombre,
                               cat.icono as categoria_icono,
                               '#e67e22' as categoria_color,
                               g.id as grupo_id,
                               g.nombre as grupo_nombre,
                               COALESCE(p_cert.nombre, 'Sin período') as periodo_nombre,
                               COALESCE(p_cert.fecha_inicio, '0000-00-00') as periodo_fecha_inicio,
                               (
                                   SELECT COALESCE(MAX(cex.es_destacado), 0)
                                   FROM categoria_estudiantes cex
                                   WHERE cex.estudiante_id = c.estudiante_id
                                     AND cex.categoria_id = c.categoria_id
                                     AND cex.periodo_id <=> c.periodo_id
                               ) as es_destacado
                        FROM certificados c
                        LEFT JOIN categorias cat ON c.categoria_id = cat.id
                        LEFT JOIN grupos g ON cat.grupo_id = g.id
                        LEFT JOIN periodos p_cert ON c.periodo_id = p_cert.id
                        LEFT JOIN usuarios ua ON c.aprobado_por = ua.id
                        WHERE (TRIM(c.nombre) = TRIM(?) OR c.nombre LIKE ?)
                          AND (
                              NULLIF(TRIM(c.archivo_pdf), '') IS NOT NULL
                              OR NULLIF(TRIM(c.archivo_imagen), '') IS NOT NULL
                          )
                        ORDER BY periodo_fecha_inicio DESC, c.fecha DESC
                    ");
                    $stmt->execute([$nombreEstudiante, '%' . $nombreEstudiante . '%']);
                    $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // Obtener datos del estudiante + datos de su representante (si aplica)
            $stmtEstData = $pdo->prepare("
                SELECT 
                    e.*,
                    rep.nombre as representante_nombre,
                    rep.cedula as representante_cedula,
                    rep.celular as representante_celular,
                    rep.email as representante_email,
                    rep.fecha_nacimiento as representante_fecha_nacimiento,
                    (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(
                            g2.id, '##', g2.nombre, '##', COALESCE(g2.color, '#cccccc'), '##', COALESCE(g2.icono, ''), '##',
                            c2.id, '##', c2.nombre, '##', COALESCE(c2.icono, ''), '##', COALESCE(p2.nombre, '-'), '##',
                            COALESCE(ce2.es_destacado, 0), '##', COALESCE(DATE_FORMAT(p2.fecha_inicio, '%Y-%m-%d'), '0000-00-00'), '##',
                            COALESCE(DATE_FORMAT(ce2.fecha_matricula, '%Y-%m-%d'), '')
                        ) SEPARATOR '||')
                        FROM categoria_estudiantes ce2
                        LEFT JOIN categorias c2 ON ce2.categoria_id = c2.id
                        LEFT JOIN grupos g2 ON c2.grupo_id = g2.id
                        LEFT JOIN periodos p2 ON ce2.periodo_id = p2.id
                        WHERE ce2.estudiante_id = e.id
                    ) as enrollment_data
                FROM estudiantes e
                LEFT JOIN estudiantes rep ON e.representante_id = rep.id
                WHERE e.id = ?
                LIMIT 1
            ");
            $stmtEstData->execute([$id]);
            $estudianteData = $stmtEstData->fetch(PDO::FETCH_ASSOC);

            // Seguimiento por categoría/período (incluye aprobados sin archivos generados)
            $sqlSeguimiento = "
                SELECT
                       c.id,
                       c.estudiante_id,
                       c.categoria_id,
                       c.periodo_id,
                       c.codigo,
                       c.fecha,
                       c.fecha_creacion,
                       c.aprobado,
                       c.aprobado_por,
                       c.fecha_aprobacion,
                       ua.nombre_completo as aprobado_por_nombre,
                       c.archivo_pdf,
                       c.archivo_imagen,
                       c.fechas_generacion,
                       cat.nombre as categoria_nombre,
                       cat.icono as categoria_icono,
                       '#e67e22' as categoria_color,
                       g.id as grupo_id,
                       g.nombre as grupo_nombre,
                       COALESCE(p_cert.nombre, 'Sin período') as periodo_nombre,
                       COALESCE(DATE_FORMAT(p_cert.fecha_inicio, '%Y-%m-%d'), '0000-00-00') as periodo_fecha_inicio,
                       (
                           SELECT COALESCE(MAX(cex.es_destacado), 0)
                           FROM categoria_estudiantes cex
                           WHERE cex.estudiante_id = c.estudiante_id
                             AND cex.categoria_id = c.categoria_id
                             AND cex.periodo_id <=> c.periodo_id
                       ) as es_destacado,
                       (
                           SELECT COALESCE(DATE_FORMAT(MIN(cex.fecha_matricula), '%Y-%m-%d %H:%i:%s'), '')
                           FROM categoria_estudiantes cex
                           WHERE cex.estudiante_id = c.estudiante_id
                             AND cex.categoria_id = c.categoria_id
                             AND cex.periodo_id <=> c.periodo_id
                       ) as fecha_matricula
                FROM certificados c
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                LEFT JOIN grupos g ON cat.grupo_id = g.id
                LEFT JOIN periodos p_cert ON c.periodo_id = p_cert.id
                LEFT JOIN usuarios ua ON c.aprobado_por = ua.id
                WHERE c.estudiante_id = ?
                ORDER BY c.fecha_creacion DESC, c.id DESC
            ";

            $stmtSeguimiento = $pdo->prepare($sqlSeguimiento);
            $stmtSeguimiento->execute([$id]);
            $certificadosSeguimientoRaw = $stmtSeguimiento->fetchAll(PDO::FETCH_ASSOC);

            if (empty($certificadosSeguimientoRaw) && empty($nombreEstudiante) && !empty($estudianteData['nombre'])) {
                $nombreEstudiante = $estudianteData['nombre'];
            }

            if (empty($certificadosSeguimientoRaw) && !empty($nombreEstudiante)) {
                $stmtSeguimiento = $pdo->prepare("
                    SELECT
                           c.id,
                           c.estudiante_id,
                           c.categoria_id,
                           c.periodo_id,
                           c.codigo,
                           c.fecha,
                           c.fecha_creacion,
                           c.aprobado,
                           c.aprobado_por,
                           c.fecha_aprobacion,
                           ua.nombre_completo as aprobado_por_nombre,
                           c.archivo_pdf,
                           c.archivo_imagen,
                           c.fechas_generacion,
                           cat.nombre as categoria_nombre,
                           cat.icono as categoria_icono,
                           '#e67e22' as categoria_color,
                           g.id as grupo_id,
                           g.nombre as grupo_nombre,
                           COALESCE(p_cert.nombre, 'Sin período') as periodo_nombre,
                           COALESCE(DATE_FORMAT(p_cert.fecha_inicio, '%Y-%m-%d'), '0000-00-00') as periodo_fecha_inicio,
                           (
                               SELECT COALESCE(MAX(cex.es_destacado), 0)
                               FROM categoria_estudiantes cex
                               WHERE cex.estudiante_id = c.estudiante_id
                                 AND cex.categoria_id = c.categoria_id
                                 AND cex.periodo_id <=> c.periodo_id
                           ) as es_destacado,
                           (
                               SELECT COALESCE(DATE_FORMAT(MIN(cex.fecha_matricula), '%Y-%m-%d %H:%i:%s'), '')
                               FROM categoria_estudiantes cex
                               WHERE cex.estudiante_id = c.estudiante_id
                                 AND cex.categoria_id = c.categoria_id
                                 AND cex.periodo_id <=> c.periodo_id
                           ) as fecha_matricula
                    FROM certificados c
                    LEFT JOIN categorias cat ON c.categoria_id = cat.id
                    LEFT JOIN grupos g ON cat.grupo_id = g.id
                    LEFT JOIN periodos p_cert ON c.periodo_id = p_cert.id
                    LEFT JOIN usuarios ua ON c.aprobado_por = ua.id
                    WHERE (TRIM(c.nombre) = TRIM(?) OR c.nombre LIKE ?)
                    ORDER BY c.fecha_creacion DESC, c.id DESC
                ");
                $stmtSeguimiento->execute([$nombreEstudiante, '%' . $nombreEstudiante . '%']);
                $certificadosSeguimientoRaw = $stmtSeguimiento->fetchAll(PDO::FETCH_ASSOC);
            }

            $normalizarHistorialGeneraciones = function ($raw): array {
                if (is_array($raw)) return $raw;
                if (!is_string($raw)) return [];
                $text = trim($raw);
                if ($text === '') return [];
                $decoded = json_decode($text, true);
                return is_array($decoded) ? $decoded : [];
            };

            $compararFechas = function (string $a, string $b): int {
                if ($a === $b) return 0;
                return $a < $b ? -1 : 1;
            };

            $seguimientoMap = [];
            foreach ($certificadosSeguimientoRaw as $row) {
                $categoriaId = isset($row['categoria_id']) && $row['categoria_id'] !== null ? (int)$row['categoria_id'] : null;
                $periodoId = isset($row['periodo_id']) && $row['periodo_id'] !== null ? (int)$row['periodo_id'] : null;
                $mapKey = ($categoriaId !== null ? (string)$categoriaId : 'null') . '__' . ($periodoId !== null ? (string)$periodoId : 'null');

                $archivoPdf = trim((string)($row['archivo_pdf'] ?? ''));
                $archivoImagen = trim((string)($row['archivo_imagen'] ?? ''));
                $tieneArchivos = ($archivoPdf !== '' || $archivoImagen !== '');

                $historialRaw = $normalizarHistorialGeneraciones($row['fechas_generacion'] ?? null);
                $historialFechas = [];
                foreach ($historialRaw as $item) {
                    if (is_string($item)) {
                        $fechaHist = trim($item);
                        if ($fechaHist !== '') {
                            $historialFechas[] = ['fecha' => $fechaHist, 'usuario' => ''];
                        }
                        continue;
                    }
                    if (is_array($item)) {
                        $fechaHist = trim((string)($item['fecha'] ?? ''));
                        if ($fechaHist === '') continue;
                        $usuarioHist = trim((string)($item['usuario'] ?? ($item['usuario_nombre'] ?? '')));
                        $historialFechas[] = ['fecha' => $fechaHist, 'usuario' => $usuarioHist];
                    }
                }

                if (!empty($historialFechas)) {
                    usort($historialFechas, function ($a, $b) use ($compararFechas) {
                        return $compararFechas((string)($a['fecha'] ?? ''), (string)($b['fecha'] ?? ''));
                    });
                }

                $primeraGenFecha = '';
                $ultimaGenFecha = '';
                $primeraGenUsuario = '';
                $ultimaGenUsuario = '';
                if (!empty($historialFechas)) {
                    $firstHist = $historialFechas[0];
                    $lastHist = $historialFechas[count($historialFechas) - 1];
                    $primeraGenFecha = (string)($firstHist['fecha'] ?? '');
                    $ultimaGenFecha = (string)($lastHist['fecha'] ?? '');
                    $primeraGenUsuario = (string)($firstHist['usuario'] ?? '');
                    $ultimaGenUsuario = (string)($lastHist['usuario'] ?? '');
                }

                if (($primeraGenFecha === '' || $ultimaGenFecha === '') && $tieneArchivos) {
                    $fallbackGen = trim((string)($row['fecha_creacion'] ?? $row['fecha'] ?? ''));
                    if ($fallbackGen !== '') {
                        if ($primeraGenFecha === '') $primeraGenFecha = $fallbackGen;
                        if ($ultimaGenFecha === '') $ultimaGenFecha = $fallbackGen;
                    }
                }

                $estaAprobado = ((int)($row['aprobado'] ?? 0) === 1)
                    || trim((string)($row['fecha_aprobacion'] ?? '')) !== ''
                    || !empty($row['aprobado_por']);
                $estaGenerado = $tieneArchivos || $primeraGenFecha !== '' || $ultimaGenFecha !== '';

                if (!isset($seguimientoMap[$mapKey])) {
                    $seguimientoMap[$mapKey] = [
                        'categoria_id' => $categoriaId,
                        'periodo_id' => $periodoId,
                        'categoria_nombre' => (string)($row['categoria_nombre'] ?? 'Sin categoría'),
                        'categoria_icono' => (string)($row['categoria_icono'] ?? ''),
                        'categoria_color' => (string)($row['categoria_color'] ?? '#e67e22'),
                        'grupo_id' => isset($row['grupo_id']) ? (int)$row['grupo_id'] : null,
                        'grupo_nombre' => (string)($row['grupo_nombre'] ?? ''),
                        'periodo_nombre' => (string)($row['periodo_nombre'] ?? 'Sin período'),
                        'periodo_fecha_inicio' => (string)($row['periodo_fecha_inicio'] ?? '0000-00-00'),
                        'fecha_matricula' => (string)($row['fecha_matricula'] ?? ''),
                        'es_destacado' => (int)($row['es_destacado'] ?? 0) === 1 ? 1 : 0,
                        'aprobado' => $estaAprobado ? 1 : 0,
                        'fecha_aprobacion' => (string)($row['fecha_aprobacion'] ?? ''),
                        'aprobado_por' => isset($row['aprobado_por']) && $row['aprobado_por'] !== null ? (int)$row['aprobado_por'] : null,
                        'aprobado_por_nombre' => (string)($row['aprobado_por_nombre'] ?? ''),
                        'generado' => $estaGenerado ? 1 : 0,
                        'codigo_generado' => $estaGenerado ? (string)($row['codigo'] ?? '') : '',
                        'fecha_generacion_primera' => $primeraGenFecha,
                        'fecha_generacion_ultima' => $ultimaGenFecha,
                        'generado_por_nombre' => $primeraGenUsuario !== '' ? $primeraGenUsuario : $ultimaGenUsuario
                    ];
                    continue;
                }

                $actual = &$seguimientoMap[$mapKey];

                if (!empty($row['fecha_matricula']) && empty($actual['fecha_matricula'])) {
                    $actual['fecha_matricula'] = (string)$row['fecha_matricula'];
                }
                if ((int)($row['es_destacado'] ?? 0) === 1) {
                    $actual['es_destacado'] = 1;
                }

                if ($estaAprobado) {
                    $actual['aprobado'] = 1;
                    $fechaAprobRow = trim((string)($row['fecha_aprobacion'] ?? ''));
                    if ($fechaAprobRow !== '') {
                        $fechaAprobActual = trim((string)($actual['fecha_aprobacion'] ?? ''));
                        if ($fechaAprobActual === '' || $compararFechas($fechaAprobRow, $fechaAprobActual) < 0) {
                            $actual['fecha_aprobacion'] = $fechaAprobRow;
                        }
                    }
                    if (empty($actual['aprobado_por_nombre']) && !empty($row['aprobado_por_nombre'])) {
                        $actual['aprobado_por_nombre'] = (string)$row['aprobado_por_nombre'];
                    }
                    if (empty($actual['aprobado_por']) && !empty($row['aprobado_por'])) {
                        $actual['aprobado_por'] = (int)$row['aprobado_por'];
                    }
                }

                if ($estaGenerado) {
                    $actual['generado'] = 1;
                    if (empty($actual['codigo_generado']) && !empty($row['codigo'])) {
                        $actual['codigo_generado'] = (string)$row['codigo'];
                    }

                    if ($primeraGenFecha !== '') {
                        $actualPrimera = trim((string)($actual['fecha_generacion_primera'] ?? ''));
                        if ($actualPrimera === '' || $compararFechas($primeraGenFecha, $actualPrimera) < 0) {
                            $actual['fecha_generacion_primera'] = $primeraGenFecha;
                            if ($primeraGenUsuario !== '') {
                                $actual['generado_por_nombre'] = $primeraGenUsuario;
                            }
                        }
                    }

                    if ($ultimaGenFecha !== '') {
                        $actualUltima = trim((string)($actual['fecha_generacion_ultima'] ?? ''));
                        if ($actualUltima === '' || $compararFechas($ultimaGenFecha, $actualUltima) > 0) {
                            $actual['fecha_generacion_ultima'] = $ultimaGenFecha;
                        }
                    }

                    if (empty($actual['generado_por_nombre']) && $ultimaGenUsuario !== '') {
                        $actual['generado_por_nombre'] = $ultimaGenUsuario;
                    }
                }

                unset($actual);
            }

            $certificadosSeguimiento = array_values($seguimientoMap);
            usort($certificadosSeguimiento, function ($a, $b) {
                $catCmp = strcasecmp((string)($a['categoria_nombre'] ?? ''), (string)($b['categoria_nombre'] ?? ''));
                if ($catCmp !== 0) return $catCmp;

                $fa = (string)($a['periodo_fecha_inicio'] ?? '0000-00-00');
                $fb = (string)($b['periodo_fecha_inicio'] ?? '0000-00-00');
                if ($fa !== $fb) return $fa < $fb ? -1 : 1;

                return strcasecmp((string)($a['periodo_nombre'] ?? ''), (string)($b['periodo_nombre'] ?? ''));
            });

            $fallbackUsed = empty($certificados) && !empty($nombreEstudiante ?? null);

            echo json_encode([
                'success' => true,
                'estudiante' => $estudianteData,
                'certificados' => $certificados,
                'certificados_seguimiento' => $certificadosSeguimiento,
                'debug_info' => [
                    'received_id' => $id,
                    'count' => count($certificados),
                    'fallback_used' => $fallbackUsed
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
