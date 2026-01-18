<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

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
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $icono = $_POST['icono'] ?? '📚';
            $color = $_POST['color'] ?? '#3498db';
            
            if (empty($grupo_id) || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }
            
            $periodo_id = $_POST['periodo_id'] ?? null;
            
            $stmt = $pdo->prepare("
                INSERT INTO categorias (grupo_id, nombre, descripcion, icono, color) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$grupo_id, $nombre, $descripcion, $icono, $color]);
            $categoria_id = $pdo->lastInsertId();
            
            // Si se especificó un periodo, asignar la categoría a ese periodo
            if ($periodo_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO categoria_periodos (categoria_id, periodo_id, activo) 
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$categoria_id, $periodo_id]);
            } else {
                // Si no se especificó periodo, asignar al periodo activo más reciente del grupo
                $stmt = $pdo->prepare("
                    SELECT p.id FROM periodos p
                    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
                    WHERE gp.grupo_id = ? AND p.activo = 1
                    ORDER BY p.fecha_inicio DESC
                    LIMIT 1
                ");
                $stmt->execute([$grupo_id]);
                $periodo_reciente = $stmt->fetchColumn();
                
                if ($periodo_reciente) {
                    $stmt = $pdo->prepare("
                        INSERT INTO categoria_periodos (categoria_id, periodo_id, activo) 
                        VALUES (?, ?, 1)
                    ");
                    $stmt->execute([$categoria_id, $periodo_reciente]);
                }
            }
            
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
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $icono = $_POST['icono'] ?? '📚';
            $color = $_POST['color'] ?? '#3498db';
            
            if (empty($id) || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }
            
            $stmt = $pdo->prepare("
                UPDATE categorias 
                SET nombre = ?, descripcion = ?, icono = ?, color = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $descripcion, $icono, $color, $id]);
            
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
            
            $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND activo = 1");
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
                           COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
                    FROM categorias c
                    INNER JOIN categoria_periodos cp ON c.id = cp.categoria_id 
                        AND cp.periodo_id = ? AND cp.activo = 1
                    LEFT JOIN categoria_estudiantes ce ON c.id = ce.categoria_id 
                        AND ce.estado = 'activo' AND ce.periodo_id = ?
                    WHERE c.grupo_id = ? AND c.activo = 1
                    GROUP BY c.id
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
                           COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
                    FROM categorias c
                    LEFT JOIN categoria_estudiantes ce ON c.id = ce.categoria_id 
                        AND ce.estado = 'activo'
                    WHERE c.grupo_id = ? AND c.activo = 1
                    GROUP BY c.id
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
