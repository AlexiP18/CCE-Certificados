<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

header('Content-Type: application/json');

$pdo = getConnection();

// Manejar GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'actividad':
            // Obtener actividad reciente del usuario
            try {
                $stmt = $pdo->prepare("
                    SELECT accion, descripcion, ip_address, fecha_creacion 
                    FROM log_actividad 
                    WHERE usuario_id = ? 
                    ORDER BY fecha_creacion DESC 
                    LIMIT 20
                ");
                $stmt->execute([$usuario['id']]);
                $actividad = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'actividad' => $actividad]);
            } catch (PDOException $e) {
                // Si la tabla no existe, devolver lista vacía
                echo json_encode(['success' => true, 'actividad' => []]);
            }
            break;
            
        case 'info':
            // Obtener información del usuario
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.email, u.nombre_completo, u.rol_id, u.activo,
                       u.ultimo_acceso, u.fecha_creacion, r.nombre as rol_nombre
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$usuario['id']]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                unset($info['password_hash']); // Nunca enviar el hash
                echo json_encode(['success' => true, 'usuario' => $info]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    exit;
}

// Manejar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'actualizar_perfil':
            $nombre_completo = trim($input['nombre_completo'] ?? '');
            $email = trim($input['email'] ?? '');
            
            // Validaciones
            if (empty($nombre_completo)) {
                echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
                exit;
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email no válido']);
                exit;
            }
            
            // Verificar que el email no esté en uso por otro usuario
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario['id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'El email ya está en uso por otro usuario']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nombre_completo = ?, email = ?, fecha_actualizacion = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nombre_completo, $email, $usuario['id']]);
                
                // Actualizar sesión
                $_SESSION['usuario']['nombre_completo'] = $nombre_completo;
                $_SESSION['usuario']['email'] = $email;
                
                // Registrar actividad
                registrarActividad($pdo, $usuario['id'], 'actualizar_perfil', 'Actualizó su información personal');
                
                echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar el perfil']);
            }
            break;
            
        case 'cambiar_password':
            $password_actual = $input['password_actual'] ?? '';
            $password_nuevo = $input['password_nuevo'] ?? '';
            $password_confirmar = $input['password_confirmar'] ?? '';
            
            // Validaciones
            if (empty($password_actual)) {
                echo json_encode(['success' => false, 'error' => 'Debes ingresar tu contraseña actual']);
                exit;
            }
            
            if (strlen($password_nuevo) < 8) {
                echo json_encode(['success' => false, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres']);
                exit;
            }
            
            if ($password_nuevo !== $password_confirmar) {
                echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden']);
                exit;
            }
            
            // Validar complejidad
            if (!preg_match('/[A-Z]/', $password_nuevo)) {
                echo json_encode(['success' => false, 'error' => 'La contraseña debe contener al menos una mayúscula']);
                exit;
            }
            if (!preg_match('/[a-z]/', $password_nuevo)) {
                echo json_encode(['success' => false, 'error' => 'La contraseña debe contener al menos una minúscula']);
                exit;
            }
            if (!preg_match('/[0-9]/', $password_nuevo)) {
                echo json_encode(['success' => false, 'error' => 'La contraseña debe contener al menos un número']);
                exit;
            }
            
            // Verificar contraseña actual
            $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password_actual, $user['password_hash'])) {
                echo json_encode(['success' => false, 'error' => 'La contraseña actual es incorrecta']);
                exit;
            }
            
            // Actualizar contraseña
            try {
                $nuevo_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET password_hash = ?, fecha_actualizacion = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_hash, $usuario['id']]);
                
                // Registrar actividad
                registrarActividad($pdo, $usuario['id'], 'cambiar_password', 'Cambió su contraseña');
                
                echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Error al cambiar la contraseña']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    exit;
}

// Función para registrar actividad
function registrarActividad($pdo, $usuario_id, $accion, $descripcion) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_actividad (usuario_id, accion, descripcion, ip_address, fecha_creacion)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $usuario_id,
            $accion,
            $descripcion,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (PDOException $e) {
        // Ignorar si la tabla no existe
    }
}
