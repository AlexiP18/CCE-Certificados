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
    
    // Función auxiliar para obtener grupos asignados al instructor
    function getGruposInstructor($pdo, $usuario_id) {
        $stmt = $pdo->prepare("SELECT grupo_id FROM instructor_grupos WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Leer datos JSON del body o FormData POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay JSON, intentar con POST/GET
    if (!$input) {
        $input = array_merge($_GET, $_POST);
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            // Verificar permiso
            Auth::requirePermission('grupos', 'ver');
            
            // Si es instructor, filtrar solo sus grupos asignados
            if ($esInstructor) {
                $gruposIds = getGruposInstructor($pdo, $usuario['id']);
                if (empty($gruposIds)) {
                    echo json_encode(['success' => true, 'grupos' => []]);
                    break;
                }
                $placeholders = implode(',', array_fill(0, count($gruposIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        g.*,
                        COUNT(c.id) as total_certificados,
                        MAX(c.fecha_creacion) as ultimo_certificado
                    FROM grupos g
                    LEFT JOIN certificados c ON g.id = c.grupo_id AND c.estado = 'activo'
                    WHERE g.activo = 1 AND g.id IN ($placeholders)
                    GROUP BY g.id
                    ORDER BY g.fecha_creacion DESC
                ");
                $stmt->execute($gruposIds);
            } else {
                $stmt = $pdo->query("
                    SELECT 
                        g.*,
                        COUNT(c.id) as total_certificados,
                        MAX(c.fecha_creacion) as ultimo_certificado
                    FROM grupos g
                    LEFT JOIN certificados c ON g.id = c.grupo_id AND c.estado = 'activo'
                    WHERE g.activo = 1
                    GROUP BY g.id
                    ORDER BY g.fecha_creacion DESC
                ");
            }
            
            $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'grupos' => $grupos
            ]);
            break;
            
        case 'create':
            // Verificar permiso de crear grupos
            Auth::requirePermission('grupos', 'crear');
            
            $nombre = $input['nombre'] ?? '';
            $descripcion = $input['descripcion'] ?? '';
            $icono = $input['icono'] ?? 'workshop';
            $color = $input['color'] ?? '#3498db';
            
            if (empty($nombre)) {
                throw new Exception('El nombre es requerido');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO grupos (nombre, descripcion, icono, color)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$nombre, $descripcion, $icono, $color]);
            
            $grupoId = $pdo->lastInsertId();
            
            // No crear períodos automáticamente - los períodos se asignan manualmente después
            
            echo json_encode([
                'success' => true,
                'message' => 'Grupo creado correctamente',
                'id' => $grupoId,
                'grupo_id' => $grupoId
            ]);
            break;
            
        case 'assign_periodos':
            // Verificar permiso de editar grupos
            Auth::requirePermission('grupos', 'editar');
            
            $grupo_id = $input['grupo_id'] ?? 0;
            $periodos = $input['periodos'] ?? [];
            
            if (empty($grupo_id)) {
                throw new Exception('ID de grupo es requerido');
            }
            
            // Obtener nombre del grupo
            $stmt = $pdo->prepare("SELECT nombre FROM grupos WHERE id = ?");
            $stmt->execute([$grupo_id]);
            $grupoNombre = $stmt->fetchColumn() ?: 'Grupo';
            
            if (!empty($periodos)) {
                // Verificar si la tabla grupo_periodos existe, si no, crearla
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS grupo_periodos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        grupo_id INT NOT NULL,
                        periodo_id INT NOT NULL,
                        activo TINYINT(1) DEFAULT 1,
                        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
                        FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_grupo_periodo (grupo_id, periodo_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Para cada periodo seleccionado, asignarlo al grupo (reutilizando periodos globales)
                $stmtInsertGrupoPeriodo = $pdo->prepare("
                    INSERT INTO grupo_periodos (grupo_id, periodo_id, activo)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE activo = 1
                ");
                
                foreach ($periodos as $periodo_id) {
                    // Asignar el periodo existente al grupo (sin crear copias)
                    $stmtInsertGrupoPeriodo->execute([$grupo_id, $periodo_id]);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Períodos asignados correctamente'
            ]);
            break;

        case 'update':
            // Verificar permiso de editar grupos
            Auth::requirePermission('grupos', 'editar');
            
            $id = $input['id'] ?? 0;
            $nombre = $input['nombre'] ?? '';
            $descripcion = $input['descripcion'] ?? '';
            $icono = $input['icono'] ?? 'workshop';
            $color = $input['color'] ?? '#3498db';
            
            if (empty($id) || empty($nombre)) {
                throw new Exception('ID y nombre son requeridos');
            }
            
            // Actualizar grupo
            $stmt = $pdo->prepare("
                UPDATE grupos 
                SET nombre = ?, descripcion = ?, icono = ?, color = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$nombre, $descripcion, $icono, $color, $id]);
            
            // También actualizar el color de todas las categorías de este grupo
            $stmt = $pdo->prepare("
                UPDATE categorias 
                SET color = ?
                WHERE grupo_id = ? AND activo = 1
            ");
            $stmt->execute([$color, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Grupo actualizado correctamente'
            ]);
            break;
            
        case 'delete':
            // Verificar permiso de eliminar grupos
            Auth::requirePermission('grupos', 'eliminar');
            
            $id = $input['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            // Soft delete
            $stmt = $pdo->prepare("UPDATE grupos SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Grupo eliminado correctamente'
            ]);
            break;
            
        case 'get':
            // Verificar permiso
            Auth::requirePermission('grupos', 'ver');
            
            $id = $input['id'] ?? $_GET['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID es requerido');
            }
            
            // Si es instructor, verificar acceso al grupo
            if ($esInstructor) {
                $gruposIds = getGruposInstructor($pdo, $usuario['id']);
                if (!in_array($id, $gruposIds)) {
                    throw new Exception('No tiene acceso a este grupo');
                }
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    g.*,
                    COUNT(c.id) as total_certificados,
                    MAX(c.fecha_creacion) as ultimo_certificado
                FROM grupos g
                LEFT JOIN certificados c ON g.id = c.grupo_id AND c.estado = 'activo'
                WHERE g.id = ? AND g.activo = 1
                GROUP BY g.id
            ");
            
            $stmt->execute([$id]);
            $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$grupo) {
                throw new Exception('Grupo no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'grupo' => $grupo
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
