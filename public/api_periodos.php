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
    
    // Leer input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = array_merge($_GET, $_POST);
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener':
            // Verificar permiso
            Auth::requirePermission('periodos', 'ver');
            
            // Obtener un período específico
            $id = $input['id'] ?? $_GET['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM periodos WHERE id = ?");
            $stmt->execute([$id]);
            $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$periodo) {
                throw new Exception('Período no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'periodo' => $periodo
            ]);
            break;
            
        case 'list':
            // Verificar permiso
            Auth::requirePermission('periodos', 'ver');
            
            // Listar períodos con conteo de categorías
            $grupo_id = $input['grupo_id'] ?? $_GET['grupo_id'] ?? null;
            
            if ($grupo_id) {
                // Filtrar por grupo específico
                $stmt = $pdo->prepare("
                    SELECT p.*,
                           COUNT(DISTINCT cp.categoria_id) as total_categorias
                    FROM periodos p
                    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id AND gp.grupo_id = ? AND gp.activo = 1
                    LEFT JOIN categoria_periodos cp ON p.id = cp.periodo_id AND cp.activo = 1
                    WHERE p.activo = 1
                    GROUP BY p.id
                    ORDER BY p.fecha_inicio DESC
                ");
                $stmt->execute([$grupo_id]);
            } else {
                // Listar todos los períodos
                $stmt = $pdo->query("
                    SELECT p.*,
                           COUNT(DISTINCT cp.categoria_id) as total_categorias
                    FROM periodos p
                    LEFT JOIN categoria_periodos cp ON p.id = cp.periodo_id AND cp.activo = 1
                    WHERE p.activo = 1
                    GROUP BY p.id
                    ORDER BY p.fecha_inicio DESC
                ");
            }
            
            echo json_encode([
                'success' => true,
                'periodos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
            
        case 'crear':
        case 'create':
            // Verificar permiso de crear períodos
            Auth::requirePermission('periodos', 'crear');
            
            $nombre = $input['nombre'] ?? '';
            $descripcion = $input['descripcion'] ?? '';
            $fecha_inicio = $input['fecha_inicio'] ?? '';
            $fecha_fin = $input['fecha_fin'] ?? '';
            $color = $input['color'] ?? '#3498db';
            $grupo_id = $input['grupo_id'] ?? null;
            $categorias = $input['categorias'] ?? '';
            
            // Decodificar categorías si vienen como JSON string
            if (is_string($categorias) && !empty($categorias)) {
                $categorias = json_decode($categorias, true) ?? [];
            }
            
            if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
                throw new Exception('Nombre y fechas son requeridos');
            }
            
            if ($fecha_fin < $fecha_inicio) {
                throw new Exception('La fecha fin debe ser posterior a la fecha inicio');
            }
            
            // Si hay grupo_id, verificar que no exista un periodo con las mismas fechas para ese grupo
            if ($grupo_id) {
                $stmt = $pdo->prepare("
                    SELECT p.id, p.nombre 
                    FROM periodos p
                    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
                    WHERE gp.grupo_id = ? 
                    AND p.fecha_inicio = ? 
                    AND p.fecha_fin = ? 
                    AND p.activo = 1 
                    AND gp.activo = 1
                    LIMIT 1
                ");
                $stmt->execute([$grupo_id, $fecha_inicio, $fecha_fin]);
                $periodoExistentePorGrupo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($periodoExistentePorGrupo) {
                    throw new Exception('Ya existe un periodo con estas fechas para este grupo: ' . $periodoExistentePorGrupo['nombre']);
                }
            }
            
            // Verificar si ya existe un periodo global con el mismo nombre y fechas
            $stmt = $pdo->prepare("
                SELECT id FROM periodos 
                WHERE nombre = ? AND fecha_inicio = ? AND fecha_fin = ? AND activo = 1
                LIMIT 1
            ");
            $stmt->execute([$nombre, $fecha_inicio, $fecha_fin]);
            $periodoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($periodoExistente) {
                // Reutilizar el periodo existente
                $periodo_id = $periodoExistente['id'];
            } else {
                // Crear nuevo periodo solo si no existe
                $stmt = $pdo->prepare("
                    INSERT INTO periodos (nombre, descripcion, fecha_inicio, fecha_fin, color)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, $color]);
                $periodo_id = $pdo->lastInsertId();
            }
            
            // Si hay grupo_id, crear la relación en grupo_periodos
            if ($grupo_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO grupo_periodos (grupo_id, periodo_id, activo)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE activo = 1
                ");
                $stmt->execute([$grupo_id, $periodo_id]);
                
                // Asignar categorías seleccionadas al período
                if (!empty($categorias) && is_array($categorias)) {
                    $stmtCat = $pdo->prepare("
                        INSERT INTO categoria_periodos (categoria_id, periodo_id, activo)
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE activo = 1
                    ");
                    foreach ($categorias as $cat_id) {
                        $stmtCat->execute([$cat_id, $periodo_id]);
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Período creado correctamente',
                'id' => $periodo_id,
                'periodo_id' => $periodo_id
            ]);
            break;
        
        case 'actualizar_nombre':
            // Verificar permiso de editar períodos
            Auth::requirePermission('periodos', 'editar');
            
            // Solo actualizar el nombre del período
            $id = $input['id'] ?? 0;
            $nombre = trim($input['nombre'] ?? '');
            
            if (empty($id) || empty($nombre)) {
                throw new Exception('ID y nombre son requeridos');
            }
            
            $stmt = $pdo->prepare("UPDATE periodos SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Nombre del período actualizado correctamente'
            ]);
            break;
            
        case 'actualizar':
        case 'update':
            // Verificar permiso de editar períodos
            Auth::requirePermission('periodos', 'editar');
            
            $id = $input['id'] ?? 0;
            $nombre = $input['nombre'] ?? '';
            $descripcion = $input['descripcion'] ?? '';
            $fecha_inicio = $input['fecha_inicio'] ?? '';
            $fecha_fin = $input['fecha_fin'] ?? '';
            $color = $input['color'] ?? '#3498db';
            
            if (empty($id) || empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
                throw new Exception('Datos incompletos');
            }
            
            if ($fecha_fin < $fecha_inicio) {
                throw new Exception('La fecha fin debe ser posterior a la fecha inicio');
            }
            
            $stmt = $pdo->prepare("
                UPDATE periodos 
                SET nombre = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, color = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, $color, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Período actualizado correctamente'
            ]);
            break;
            
        case 'eliminar':
        case 'delete':
            // Verificar permiso de eliminar períodos
            Auth::requirePermission('periodos', 'eliminar');
            
            $id = $input['id'] ?? 0;
            $grupo_id = $input['grupo_id'] ?? null;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            // Si es específico de un grupo, eliminar la relación y luego el periodo si no tiene más grupos
            if ($grupo_id) {
                // Eliminar relación grupo-periodo (hard delete)
                $stmt = $pdo->prepare("DELETE FROM grupo_periodos WHERE grupo_id = ? AND periodo_id = ?");
                $stmt->execute([$grupo_id, $id]);
                
                // Eliminar relaciones categoria-periodo para este grupo
                $stmt = $pdo->prepare("
                    DELETE cp FROM categoria_periodos cp
                    INNER JOIN categorias c ON cp.categoria_id = c.id
                    WHERE cp.periodo_id = ? AND c.grupo_id = ?
                ");
                $stmt->execute([$id, $grupo_id]);
                
                // Verificar si el período tiene otros grupos asociados
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM grupo_periodos WHERE periodo_id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si no tiene más grupos, eliminar el período completamente
                if ($result['total'] == 0) {
                    // Eliminar estudiantes matriculados en este periodo
                    $stmt = $pdo->prepare("DELETE FROM categoria_estudiantes WHERE periodo_id = ?");
                    $stmt->execute([$id]);
                    
                    // Eliminar el periodo
                    $stmt = $pdo->prepare("DELETE FROM periodos WHERE id = ?");
                    $stmt->execute([$id]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Período eliminado del grupo correctamente'
                ]);
            } else {
                // Eliminar todas las relaciones primero
                $stmt = $pdo->prepare("DELETE FROM grupo_periodos WHERE periodo_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $pdo->prepare("DELETE FROM categoria_periodos WHERE periodo_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $pdo->prepare("DELETE FROM categoria_estudiantes WHERE periodo_id = ?");
                $stmt->execute([$id]);
                
                // Eliminar el periodo completamente
                $stmt = $pdo->prepare("DELETE FROM periodos WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Período eliminado correctamente'
                ]);
            }
            break;
            
        case 'get_categorias':
            // Verificar permiso
            Auth::requirePermission('periodos', 'ver');
            
            // Obtener categorías de un grupo con su estado de asignación a un período
            $periodo_id = $input['periodo_id'] ?? 0;
            $grupo_id = $input['grupo_id'] ?? 0;
            
            if (empty($periodo_id) || empty($grupo_id)) {
                throw new Exception('periodo_id y grupo_id son requeridos');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.id, c.nombre, c.icono, c.color,
                       CASE WHEN cp.id IS NOT NULL AND cp.activo = 1 THEN 1 ELSE 0 END as asignado
                FROM categorias c
                LEFT JOIN categoria_periodos cp ON c.id = cp.categoria_id AND cp.periodo_id = ?
                WHERE c.grupo_id = ? AND c.activo = 1
                ORDER BY c.nombre
            ");
            
            $stmt->execute([$periodo_id, $grupo_id]);
            
            echo json_encode([
                'success' => true,
                'categorias' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
            
        case 'asignar_categoria':
            // Verificar permiso de editar períodos
            Auth::requirePermission('periodos', 'editar');
            
            // Asignar o desasignar una categoría a un período
            $categoria_id = $input['categoria_id'] ?? 0;
            $periodo_id = $input['periodo_id'] ?? 0;
            $asignar = $input['asignar'] ?? 1;
            
            if (empty($categoria_id) || empty($periodo_id)) {
                throw new Exception('categoria_id y periodo_id son requeridos');
            }
            
            if ($asignar) {
                // Asignar categoría al período
                $stmt = $pdo->prepare("
                    INSERT INTO categoria_periodos (categoria_id, periodo_id, activo) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE activo = 1
                ");
                $stmt->execute([$categoria_id, $periodo_id]);
                $message = 'Categoría asignada correctamente';
            } else {
                // Desasignar categoría del período
                $stmt = $pdo->prepare("
                    UPDATE categoria_periodos 
                    SET activo = 0 
                    WHERE categoria_id = ? AND periodo_id = ?
                ");
                $stmt->execute([$categoria_id, $periodo_id]);
                $message = 'Categoría desasignada correctamente';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;
            
        case 'copiar_categorias':
            // Verificar permiso de editar períodos
            Auth::requirePermission('periodos', 'editar');
            
            $periodo_destino_id = $input['periodo_destino_id'] ?? 0;
            $categorias_ids = $input['categorias_ids'] ?? '';
            
            if (empty($periodo_destino_id)) {
                throw new Exception('Período destino es requerido');
            }
            
            // Decodificar IDs de categorías
            if (is_string($categorias_ids)) {
                $categorias_ids = json_decode($categorias_ids, true) ?? [];
            }
            
            if (empty($categorias_ids)) {
                throw new Exception('Debe seleccionar al menos una categoría');
            }
            
            $copiadas = 0;
            foreach ($categorias_ids as $categoria_id) {
                // Asignar la categoría al nuevo período
                $stmt = $pdo->prepare("
                    INSERT INTO categoria_periodos (categoria_id, periodo_id, activo)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE activo = 1
                ");
                $stmt->execute([$categoria_id, $periodo_destino_id]);
                $copiadas++;
            }
            
            echo json_encode([
                'success' => true,
                'message' => "$copiadas categoría(s) copiada(s) correctamente",
                'copiadas' => $copiadas
            ]);
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
