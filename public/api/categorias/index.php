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

try {
    $pdo = getConnection();
    $usuario = Auth::user();
    $esInstructor = Auth::isInstructor();
    
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
    
    function obtenerInstructorId($jsonData) {
        $raw = $_POST['instructor_id'] ?? $_GET['instructor_id'] ?? $jsonData['instructor_id'] ?? '';
        $raw = trim((string)$raw);
        return $raw === '' ? null : (int)$raw;
    }
    
    function validarInstructor($pdo, $instructor_id) {
        if ($instructor_id === null) {
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT u.id
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ? AND u.activo = 1 AND r.nombre = 'instructor'
            LIMIT 1
        ");
        $stmt->execute([$instructor_id]);
        
        if (!$stmt->fetchColumn()) {
            throw new Exception('El instructor seleccionado no es válido');
        }
    }
    
    function asignarInstructorCategoria($pdo, $categoria_id, $instructor_id) {
        // Mantener un único instructor "a cargo" por categoría desde este flujo.
        $stmtDelete = $pdo->prepare("DELETE FROM instructor_categorias WHERE categoria_id = ?");
        $stmtDelete->execute([$categoria_id]);
        
        if ($instructor_id !== null) {
            $stmtInsert = $pdo->prepare("INSERT INTO instructor_categorias (usuario_id, categoria_id) VALUES (?, ?)");
            $stmtInsert->execute([$instructor_id, $categoria_id]);
        }
    }
    
    // Leer datos JSON si se envían con Content-Type: application/json
    $jsonData = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? $jsonData['action'] ?? '';
    
    switch ($action) {
        case 'create':
            // Verificar permiso de crear categorías
            Auth::requirePermission('categorias', 'crear');
            
            $grupo_id = $_POST['grupo_id'] ?? 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = $_POST['descripcion'] ?? '';
            $icono = $_POST['icono'] ?? '📚';
            $instructor_id = obtenerInstructorId($jsonData);
            
            if (empty($grupo_id) || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }
            
            validarInstructor($pdo, $instructor_id);

            $periodo_id = $_POST['periodo_id'] ?? null;
            
            // Si no se especificó periodo, buscar el periodo activo más reciente del grupo
            if (!$periodo_id) {
                $stmtP = $pdo->prepare("
                    SELECT p.id FROM periodos p
                    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
                    WHERE gp.grupo_id = ? AND p.activo = 1
                    ORDER BY p.fecha_inicio DESC
                    LIMIT 1
                ");
                $stmtP->execute([$grupo_id]);
                $periodo_id = $stmtP->fetchColumn();
            }

            if (!$periodo_id) {
                throw new Exception("Es necesario establecer un período para este grupo antes de crear categorías");
            }

            $import_mode = $_POST['import_mode'] ?? $jsonData['import_mode'] ?? 0;

            // 1. Buscar si ya existe una categoría con ese nombre en el grupo
            $stmtExist = $pdo->prepare("SELECT id FROM categorias WHERE grupo_id = ? AND nombre = ? AND activo = 1 LIMIT 1");
            $stmtExist->execute([$grupo_id, $nombre]);
            $categoria_id = $stmtExist->fetchColumn();

            if ($categoria_id) {
                // Existe en el grupo, verificar si ya está en este período
                $stmtCheckPeriodo = $pdo->prepare("SELECT COUNT(*) FROM categoria_periodos WHERE categoria_id = ? AND periodo_id = ? AND activo = 1");
                $stmtCheckPeriodo->execute([$categoria_id, $periodo_id]);
                if ($stmtCheckPeriodo->fetchColumn() > 0) {
                    throw new Exception("La categoría '$nombre' ya existe en este período.");
                }
                
                // Si existe en el grupo pero no en el periodo:
                // Solo permitimos vincular si estamos en modo importación
                if (!$import_mode) {
                    throw new Exception("La categoría '$nombre' ya existe en este grupo. Por favor, use la pestaña 'Importar' para añadirla a este período.");
                }

                // Vinculamos la categoría existente
                $stmtLink = $pdo->prepare("
                    INSERT INTO categoria_periodos (categoria_id, periodo_id, activo) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE activo = 1
                ");
                $stmtLink->execute([$categoria_id, $periodo_id]);
            } else {
                // No existe en el grupo, crearla
                $stmt = $pdo->prepare("
                    INSERT INTO categorias (grupo_id, nombre, descripcion, icono) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$grupo_id, $nombre, $descripcion, $icono]);
                $categoria_id = $pdo->lastInsertId();
                
                // Vincular al periodo
                $stmtLink = $pdo->prepare("
                    INSERT INTO categoria_periodos (categoria_id, periodo_id, activo) 
                    VALUES (?, ?, 1)
                ");
                $stmtLink->execute([$categoria_id, $periodo_id]);
            }
            
            asignarInstructorCategoria($pdo, $categoria_id, $instructor_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Categoría creada correctamente',
                'id' => $categoria_id
            ]);
            break;
            
        case 'update':
            // Verificar permiso de editar categorías
            Auth::requirePermission('categorias', 'editar');
            
            $id = $_POST['id'] ?? 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = $_POST['descripcion'] ?? '';
            $icono = $_POST['icono'] ?? '📚';
            $instructor_id = obtenerInstructorId($jsonData);
            
            if (empty($id) || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }
            
            validarInstructor($pdo, $instructor_id);
            
            // Validar nombre duplicado
            $stmtGrp = $pdo->prepare("SELECT grupo_id FROM categorias WHERE id = ?");
            $stmtGrp->execute([$id]);
            $grupo_id_check = $stmtGrp->fetchColumn();
            
            if ($grupo_id_check) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE grupo_id = ? AND nombre = ? AND id != ? AND activo = 1");
                $stmtCheck->execute([$grupo_id_check, $nombre, $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception("Ya existe una categoría llamada '$nombre' en este grupo.");
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE categorias 
                SET nombre = ?, descripcion = ?, icono = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $descripcion, $icono, $id]);
            
            asignarInstructorCategoria($pdo, $id, $instructor_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Categoría actualizada correctamente'
            ]);
            break;
            
        case 'delete':
            // Verificar permiso de eliminar categorías
            Auth::requirePermission('categorias', 'eliminar');
            
            $id = $jsonData['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID requerido');
            }
            
            $stmt = $pdo->prepare("UPDATE categorias SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Categoría eliminada correctamente'
            ]);
            break;
            
        case 'get':
            // Verificar permiso
            Auth::requirePermission('categorias', 'ver');
            
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID requerido');
            }
            
            // Si es instructor, verificar acceso a la categoría
            if ($esInstructor) {
                $categoriasIds = getCategoriasInstructor($pdo, $usuario['id']);
                if (!in_array($id, $categoriasIds)) {
                    throw new Exception('No tiene acceso a esta categoría');
                }
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, iu.id as instructor_id, iu.nombre_completo as instructor_nombre
                FROM categorias c
                LEFT JOIN (
                    SELECT categoria_id, MIN(usuario_id) as usuario_id
                    FROM instructor_categorias
                    GROUP BY categoria_id
                ) icx ON c.id = icx.categoria_id
                LEFT JOIN usuarios iu ON icx.usuario_id = iu.id
                WHERE c.id = ? AND c.activo = 1
            ");
            $stmt->execute([$id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                throw new Exception('Categoría no encontrada');
            }
            
            echo json_encode([
                'success' => true,
                'categoria' => $categoria
            ]);
            break;
            
        case 'listar':
            // Verificar permiso
            Auth::requirePermission('categorias', 'ver');
            
            $grupo_id = $_GET['grupo_id'] ?? 0;
            $periodo_id = $_GET['periodo_id'] ?? null;
            
            if (empty($grupo_id)) {
                throw new Exception('grupo_id requerido');
            }
            
            // Si es instructor, verificar acceso al grupo
            if ($esInstructor) {
                $gruposIds = getGruposInstructor($pdo, $usuario['id']);
                if (!in_array($grupo_id, $gruposIds)) {
                    throw new Exception('No tiene acceso a este grupo');
                }
            }
            
            // Si hay periodo_id, filtrar por ese periodo específico
            if ($periodo_id) {
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           ? as periodo_id,
                           iu.id as instructor_id,
                           iu.nombre_completo as instructor_nombre,
                           COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
                    FROM categorias c
                    INNER JOIN categoria_periodos cp ON c.id = cp.categoria_id 
                        AND cp.periodo_id = ? AND cp.activo = 1
                    LEFT JOIN categoria_estudiantes ce ON c.id = ce.categoria_id 
                        AND ce.estado = 'activo' AND ce.periodo_id = ?
                    LEFT JOIN (
                        SELECT categoria_id, MIN(usuario_id) as usuario_id
                        FROM instructor_categorias
                        GROUP BY categoria_id
                    ) icx ON c.id = icx.categoria_id
                    LEFT JOIN usuarios iu ON icx.usuario_id = iu.id
                    WHERE c.grupo_id = ? AND c.activo = 1
                    GROUP BY c.id, iu.id, iu.nombre_completo
                    ORDER BY c.nombre ASC
                ");
                $stmt->execute([$periodo_id, $periodo_id, $periodo_id, $grupo_id]);
            } else {
                // Sin filtro de periodo, listar todas las categorías del grupo con su periodo más reciente
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           (SELECT cp2.periodo_id FROM categoria_periodos cp2 
                            INNER JOIN periodos p ON cp2.periodo_id = p.id 
                            WHERE cp2.categoria_id = c.id AND cp2.activo = 1 
                            ORDER BY p.fecha_inicio DESC LIMIT 1) as periodo_id,
                           iu.id as instructor_id,
                           iu.nombre_completo as instructor_nombre,
                           COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
                    FROM categorias c
                    LEFT JOIN categoria_estudiantes ce ON c.id = ce.categoria_id 
                        AND ce.estado = 'activo'
                    LEFT JOIN (
                        SELECT categoria_id, MIN(usuario_id) as usuario_id
                        FROM instructor_categorias
                        GROUP BY categoria_id
                    ) icx ON c.id = icx.categoria_id
                    LEFT JOIN usuarios iu ON icx.usuario_id = iu.id
                    WHERE c.grupo_id = ? AND c.activo = 1
                    GROUP BY c.id, iu.id, iu.nombre_completo
                    ORDER BY c.nombre ASC
                ");
                $stmt->execute([$grupo_id]);
            }
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
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
