<?php
/**
 * API de Usuarios - CCE Certificados
 */

require_once '../includes/Auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar autenticación
Auth::requireAuth();

$pdo = getConnection();

// Leer datos de entrada
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = array_merge($_GET, $_POST);
}

$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // ==================== LISTAR USUARIOS ====================
        case 'list':
        case 'listar':
            Auth::requirePermission('usuarios', 'ver');
            
            $stmt = $pdo->query("
                SELECT u.id, u.username, u.email, u.nombre_completo, u.cedula, u.telefono, 
                       u.direccion, u.foto, u.activo, u.es_superadmin,
                       u.ultimo_acceso, u.fecha_creacion,
                       r.id as rol_id, r.nombre as rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                ORDER BY u.nombre_completo ASC
            ");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar administradores activos
            $adminCount = Auth::getAdminCount();
            
            echo json_encode([
                'success' => true,
                'usuarios' => $usuarios,
                'admin_count' => $adminCount,
                'max_admins' => 3
            ]);
            break;
        
        // ==================== OBTENER USUARIO ====================
        case 'get':
        case 'obtener':
            Auth::requirePermission('usuarios', 'ver');
            
            $id = $input['id'] ?? 0;
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.email, u.nombre_completo, u.cedula, u.telefono,
                       u.direccion, u.foto, u.activo, u.es_superadmin,
                       u.ultimo_acceso, u.fecha_creacion,
                       r.id as rol_id, r.nombre as rol_nombre,
                       pu.permisos_custom
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                LEFT JOIN permisos_usuario pu ON u.id = pu.usuario_id
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Si es instructor, obtener perfil extendido
            if ($usuario['rol_nombre'] === 'instructor') {
                $stmt = $pdo->prepare("SELECT * FROM perfil_instructor WHERE usuario_id = ?");
                $stmt->execute([$id]);
                $usuario['perfil_instructor'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                
                // Obtener categorías asignadas
                $stmt = $pdo->prepare("
                    SELECT c.id, c.nombre 
                    FROM instructor_categorias ic
                    INNER JOIN categorias c ON ic.categoria_id = c.id
                    WHERE ic.usuario_id = ?
                ");
                $stmt->execute([$id]);
                $usuario['categorias_asignadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Obtener grupos asignados
                $stmt = $pdo->prepare("
                    SELECT g.id, g.nombre 
                    FROM instructor_grupos ig
                    INNER JOIN grupos g ON ig.grupo_id = g.id
                    WHERE ig.usuario_id = ?
                ");
                $stmt->execute([$id]);
                $usuario['grupos_asignados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
            break;
        
        // ==================== CREAR USUARIO ====================
        case 'create':
        case 'crear':
            Auth::requirePermission('usuarios', 'crear');
            
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $nombre_completo = trim($input['nombre_completo'] ?? '');
            $password = $input['password'] ?? '';
            $rol_id = intval($input['rol_id'] ?? 0);
            $activo = isset($input['activo']) ? intval($input['activo']) : 1;
            $cedula = trim($input['cedula'] ?? '');
            $telefono = trim($input['telefono'] ?? '');
            $direccion = trim($input['direccion'] ?? '');
            $es_superadmin = isset($input['es_superadmin']) ? intval($input['es_superadmin']) : 0;
            
            // Validaciones
            if (empty($username) || empty($email) || empty($nombre_completo) || empty($password) || !$rol_id) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if (strlen($username) < 3) {
                throw new Exception('El nombre de usuario debe tener al menos 3 caracteres');
            }
            
            // Verificar rol y límites
            $stmt = $pdo->prepare("SELECT nombre FROM roles WHERE id = ?");
            $stmt->execute([$rol_id]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rol) {
                throw new Exception('Rol inválido');
            }
            
            // Verificar límite de 3 administradores
            if ($rol['nombre'] === 'administrador' && !Auth::canCreateAdmin()) {
                throw new Exception('Se ha alcanzado el límite máximo de 3 administradores');
            }
            
            // Solo superadmin puede crear otros superadmins
            if ($es_superadmin && !Auth::isSuperAdmin()) {
                throw new Exception('Solo el superadministrador puede crear otros superadministradores');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            // Verificar que el username no exista
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            // Verificar que el email no exista
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado');
            }
            
            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, email, nombre_completo, password_hash, rol_id, activo, cedula, telefono, direccion, es_superadmin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $nombre_completo, $password_hash, $rol_id, $activo, $cedula ?: null, $telefono ?: null, $direccion ?: null, $es_superadmin]);
            $nuevo_id = $pdo->lastInsertId();
            
            // Si es instructor, crear perfil extendido
            if ($rol['nombre'] === 'instructor') {
                $stmt = $pdo->prepare("INSERT INTO perfil_instructor (usuario_id) VALUES (?)");
                $stmt->execute([$nuevo_id]);
            }
            
            Auth::logActivity($_SESSION['usuario_id'], 'crear_usuario', "Usuario creado: $username", ['usuario_id' => $nuevo_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'id' => $nuevo_id
            ]);
            break;
        
        // ==================== ACTUALIZAR USUARIO ====================
        case 'update':
        case 'actualizar':
            Auth::requirePermission('usuarios', 'editar');
            
            $id = intval($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $nombre_completo = trim($input['nombre_completo'] ?? '');
            $rol_id = intval($input['rol_id'] ?? 0);
            $activo = isset($input['activo']) ? intval($input['activo']) : 1;
            $password = $input['password'] ?? ''; // Opcional
            $cedula = trim($input['cedula'] ?? '');
            $telefono = trim($input['telefono'] ?? '');
            $direccion = trim($input['direccion'] ?? '');
            $es_superadmin = isset($input['es_superadmin']) ? intval($input['es_superadmin']) : 0;
            
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            // Validaciones
            if (empty($username) || empty($email) || empty($nombre_completo) || !$rol_id) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            // Verificar que el usuario exista y obtener rol actual
            $stmt = $pdo->prepare("
                SELECT u.id, u.es_superadmin, r.nombre as rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            $usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuarioActual) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Verificar nuevo rol
            $stmt = $pdo->prepare("SELECT nombre FROM roles WHERE id = ?");
            $stmt->execute([$rol_id]);
            $nuevoRol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$nuevoRol) {
                throw new Exception('Rol inválido');
            }
            
            // Verificar límite de admins si cambia a administrador
            if ($nuevoRol['nombre'] === 'administrador' && $usuarioActual['rol_nombre'] !== 'administrador') {
                if (!Auth::canCreateAdmin()) {
                    throw new Exception('Se ha alcanzado el límite máximo de 3 administradores');
                }
            }
            
            // No se puede quitar superadmin al único superadmin
            if ($usuarioActual['es_superadmin'] && !$es_superadmin) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE es_superadmin = 1");
                $totalSuperadmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                if ($totalSuperadmins <= 1) {
                    throw new Exception('Debe existir al menos un superadministrador');
                }
            }
            
            // Solo superadmin puede modificar campo es_superadmin
            if ($es_superadmin !== $usuarioActual['es_superadmin'] && !Auth::isSuperAdmin()) {
                throw new Exception('Solo el superadministrador puede modificar este privilegio');
            }
            
            // Verificar que el username no exista en otro usuario
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            // Verificar que el email no exista en otro usuario
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado');
            }
            
            // Actualizar usuario
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('La contraseña debe tener al menos 6 caracteres');
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET username = ?, email = ?, nombre_completo = ?, password_hash = ?, rol_id = ?, activo = ?,
                        cedula = ?, telefono = ?, direccion = ?, es_superadmin = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $nombre_completo, $password_hash, $rol_id, $activo, 
                               $cedula ?: null, $telefono ?: null, $direccion ?: null, $es_superadmin, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET username = ?, email = ?, nombre_completo = ?, rol_id = ?, activo = ?,
                        cedula = ?, telefono = ?, direccion = ?, es_superadmin = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $nombre_completo, $rol_id, $activo,
                               $cedula ?: null, $telefono ?: null, $direccion ?: null, $es_superadmin, $id]);
            }
            
            Auth::logActivity($_SESSION['usuario_id'], 'actualizar_usuario', "Usuario actualizado: $username", ['usuario_id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);
            break;
        
        // ==================== ELIMINAR USUARIO ====================
        case 'delete':
        case 'eliminar':
            Auth::requirePermission('usuarios', 'eliminar');
            
            $id = intval($input['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            // No permitir eliminar el propio usuario
            if ($id == $_SESSION['usuario_id']) {
                throw new Exception('No puedes eliminar tu propia cuenta');
            }
            
            // Obtener info del usuario antes de eliminar
            $stmt = $pdo->prepare("SELECT username, es_superadmin FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // No permitir eliminar superadmin si es el único
            if ($usuario['es_superadmin']) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE es_superadmin = 1");
                $totalSuperadmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                if ($totalSuperadmins <= 1) {
                    throw new Exception('No se puede eliminar al único superadministrador');
                }
                // Solo superadmin puede eliminar otro superadmin
                if (!Auth::isSuperAdmin()) {
                    throw new Exception('Solo el superadministrador puede eliminar a otro superadministrador');
                }
            }
            
            // Eliminar usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            Auth::logActivity($_SESSION['usuario_id'], 'eliminar_usuario', "Usuario eliminado: {$usuario['username']}", ['usuario_id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ]);
            break;
        
        // ==================== LISTAR ROLES ====================
        case 'roles':
        case 'listar_roles':
            $stmt = $pdo->query("SELECT id, nombre, descripcion FROM roles ORDER BY id ASC");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar información del límite de admins
            $adminCount = Auth::getAdminCount();
            
            echo json_encode([
                'success' => true,
                'roles' => $roles,
                'admin_count' => $adminCount,
                'max_admins' => 3,
                'can_create_admin' => Auth::canCreateAdmin()
            ]);
            break;
        
        // ==================== ACTUALIZAR PERFIL INSTRUCTOR ====================
        case 'actualizar_perfil_instructor':
            Auth::requirePermission('usuarios', 'editar');
            
            $usuario_id = intval($input['usuario_id'] ?? 0);
            if (!$usuario_id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Verificar que sea instructor
            $stmt = $pdo->prepare("
                SELECT r.nombre FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$usuario_id]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rol || $rol['nombre'] !== 'instructor') {
                throw new Exception('El usuario no es instructor');
            }
            
            $especialidad = trim($input['especialidad'] ?? '');
            $titulo_academico = trim($input['titulo_academico'] ?? '');
            $institucion_titulo = trim($input['institucion_titulo'] ?? '');
            $anio_titulo = intval($input['anio_titulo'] ?? 0) ?: null;
            $certificaciones = $input['certificaciones'] ?? null;
            $experiencia_anios = intval($input['experiencia_anios'] ?? 0) ?: null;
            $biografia = trim($input['biografia'] ?? '');
            
            // Actualizar o insertar perfil
            $stmt = $pdo->prepare("
                INSERT INTO perfil_instructor 
                (usuario_id, especialidad, titulo_academico, institucion_titulo, anio_titulo, certificaciones, experiencia_anios, biografia)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                especialidad = VALUES(especialidad),
                titulo_academico = VALUES(titulo_academico),
                institucion_titulo = VALUES(institucion_titulo),
                anio_titulo = VALUES(anio_titulo),
                certificaciones = VALUES(certificaciones),
                experiencia_anios = VALUES(experiencia_anios),
                biografia = VALUES(biografia)
            ");
            $stmt->execute([
                $usuario_id, 
                $especialidad ?: null, 
                $titulo_academico ?: null, 
                $institucion_titulo ?: null, 
                $anio_titulo,
                is_array($certificaciones) ? json_encode($certificaciones) : $certificaciones,
                $experiencia_anios,
                $biografia ?: null
            ]);
            
            Auth::logActivity($_SESSION['usuario_id'], 'actualizar_perfil_instructor', "Perfil actualizado", ['usuario_id' => $usuario_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Perfil de instructor actualizado correctamente'
            ]);
            break;
        
        // ==================== ASIGNAR CATEGORÍAS A INSTRUCTOR ====================
        case 'asignar_categorias_instructor':
            Auth::requirePermission('usuarios', 'editar');
            
            $usuario_id = intval($input['usuario_id'] ?? 0);
            $categorias = $input['categorias'] ?? [];
            
            if (!$usuario_id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Eliminar asignaciones actuales
            $stmt = $pdo->prepare("DELETE FROM instructor_categorias WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            
            // Insertar nuevas asignaciones
            if (!empty($categorias)) {
                $stmt = $pdo->prepare("INSERT INTO instructor_categorias (usuario_id, categoria_id) VALUES (?, ?)");
                foreach ($categorias as $cat_id) {
                    $stmt->execute([$usuario_id, intval($cat_id)]);
                }
            }
            
            Auth::logActivity($_SESSION['usuario_id'], 'asignar_categorias_instructor', "Categorías asignadas", ['usuario_id' => $usuario_id, 'categorias' => $categorias]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Categorías asignadas correctamente'
            ]);
            break;
        
        // ==================== ASIGNAR GRUPOS A INSTRUCTOR ====================
        case 'asignar_grupos_instructor':
            Auth::requirePermission('usuarios', 'editar');
            
            $usuario_id = intval($input['usuario_id'] ?? 0);
            $grupos = $input['grupos'] ?? [];
            
            if (!$usuario_id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Eliminar asignaciones actuales
            $stmt = $pdo->prepare("DELETE FROM instructor_grupos WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            
            // Insertar nuevas asignaciones
            if (!empty($grupos)) {
                $stmt = $pdo->prepare("INSERT INTO instructor_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                foreach ($grupos as $grupo_id) {
                    $stmt->execute([$usuario_id, intval($grupo_id)]);
                }
            }
            
            Auth::logActivity($_SESSION['usuario_id'], 'asignar_grupos_instructor', "Grupos asignados", ['usuario_id' => $usuario_id, 'grupos' => $grupos]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Grupos asignados correctamente'
            ]);
            break;
        
        // ==================== ASIGNAR PERMISOS PERSONALIZADOS (OFICINISTA) ====================
        case 'asignar_permisos_custom':
            if (!Auth::isAdmin()) {
                throw new Exception('Solo administradores pueden asignar permisos');
            }
            
            $usuario_id = intval($input['usuario_id'] ?? 0);
            $permisos = $input['permisos'] ?? [];
            
            if (!$usuario_id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Verificar que sea oficinista
            $stmt = $pdo->prepare("
                SELECT r.nombre FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$usuario_id]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rol || $rol['nombre'] !== 'oficinista') {
                throw new Exception('Solo se pueden asignar permisos personalizados a oficinistas');
            }
            
            if (empty($permisos)) {
                // Eliminar permisos personalizados (usar los del rol)
                $stmt = $pdo->prepare("DELETE FROM permisos_usuario WHERE usuario_id = ?");
                $stmt->execute([$usuario_id]);
            } else {
                // Insertar o actualizar permisos
                $stmt = $pdo->prepare("
                    INSERT INTO permisos_usuario (usuario_id, permisos_custom, asignado_por)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    permisos_custom = VALUES(permisos_custom),
                    asignado_por = VALUES(asignado_por)
                ");
                $stmt->execute([$usuario_id, json_encode($permisos), $_SESSION['usuario_id']]);
            }
            
            Auth::logActivity($_SESSION['usuario_id'], 'asignar_permisos', "Permisos personalizados asignados", ['usuario_id' => $usuario_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Permisos asignados correctamente'
            ]);
            break;
        
        // ==================== DESTACAR ESTUDIANTE (INSTRUCTOR) ====================
        case 'destacar_estudiante':
            if (!Auth::isInstructor() && !Auth::isAdmin()) {
                throw new Exception('No tiene permisos para esta acción');
            }
            
            $estudiante_id = intval($input['estudiante_id'] ?? 0);
            $motivo = trim($input['motivo'] ?? '');
            
            if (!$estudiante_id) {
                throw new Exception('ID de estudiante requerido');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO estudiantes_destacados (estudiante_id, instructor_id, motivo)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE motivo = VALUES(motivo), fecha_destacado = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$estudiante_id, $_SESSION['usuario_id'], $motivo ?: null]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante destacado correctamente'
            ]);
            break;
        
        // ==================== QUITAR DESTACADO ESTUDIANTE ====================
        case 'quitar_destacado':
            if (!Auth::isInstructor() && !Auth::isAdmin()) {
                throw new Exception('No tiene permisos para esta acción');
            }
            
            $estudiante_id = intval($input['estudiante_id'] ?? 0);
            
            if (!$estudiante_id) {
                throw new Exception('ID de estudiante requerido');
            }
            
            $stmt = $pdo->prepare("DELETE FROM estudiantes_destacados WHERE estudiante_id = ? AND instructor_id = ?");
            $stmt->execute([$estudiante_id, $_SESSION['usuario_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Destacado removido'
            ]);
            break;
        
        // ==================== LISTAR MIS CATEGORÍAS (INSTRUCTOR) ====================
        case 'mis_categorias':
            if (!Auth::isInstructor()) {
                throw new Exception('Acción solo disponible para instructores');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.* FROM categorias c
                INNER JOIN instructor_categorias ic ON c.id = ic.categoria_id
                WHERE ic.usuario_id = ?
                ORDER BY c.nombre
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            
            echo json_encode([
                'success' => true,
                'categorias' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
        
        // ==================== LISTAR MIS GRUPOS (INSTRUCTOR) ====================
        case 'mis_grupos':
            if (!Auth::isInstructor()) {
                throw new Exception('Acción solo disponible para instructores');
            }
            
            $stmt = $pdo->prepare("
                SELECT g.* FROM grupos g
                INNER JOIN instructor_grupos ig ON g.id = ig.grupo_id
                WHERE ig.usuario_id = ?
                ORDER BY g.nombre
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            
            echo json_encode([
                'success' => true,
                'grupos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
        
        // ==================== CAMBIAR MI CONTRASEÑA ====================
        case 'cambiar_password':
            $password_actual = $input['password_actual'] ?? '';
            $password_nueva = $input['password_nueva'] ?? '';
            
            if (empty($password_actual) || empty($password_nueva)) {
                throw new Exception('Ambas contraseñas son requeridas');
            }
            
            $resultado = Auth::changePassword($_SESSION['usuario_id'], $password_actual, $password_nueva);
            echo json_encode($resultado);
            break;
        
        // ==================== MI PERFIL ====================
        case 'mi_perfil':
            $usuario = Auth::user();
            if (!$usuario) {
                throw new Exception('No autenticado');
            }
            
            unset($usuario['password_hash']);
            unset($usuario['token_recuperacion']);
            unset($usuario['token_expira']);
            
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
            break;
        
        // ==================== ACTUALIZAR MI PERFIL ====================
        case 'actualizar_perfil':
            $nombre_completo = trim($input['nombre_completo'] ?? '');
            $email = trim($input['email'] ?? '');
            
            if (empty($nombre_completo) || empty($email)) {
                throw new Exception('Nombre y email son requeridos');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            // Verificar que el email no exista en otro usuario
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['usuario_id']]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado por otro usuario');
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, email = ? WHERE id = ?");
            $stmt->execute([$nombre_completo, $email, $_SESSION['usuario_id']]);
            
            // Actualizar sesión
            $_SESSION['usuario_nombre'] = $nombre_completo;
            
            Auth::logActivity($_SESSION['usuario_id'], 'actualizar_perfil', 'Perfil actualizado');
            
            echo json_encode([
                'success' => true,
                'message' => 'Perfil actualizado correctamente'
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
