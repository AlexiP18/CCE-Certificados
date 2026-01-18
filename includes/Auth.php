<?php
/**
 * Sistema de Autenticación - CCE Certificados
 * 
 * Este archivo maneja la autenticación y autorización de usuarios.
 * Incluir al inicio de cada página protegida.
 */

session_start();

require_once __DIR__ . '/../config/database.php';

class Auth {
    private static $pdo = null;
    
    /**
     * Obtener conexión a la base de datos
     */
    private static function getConnection() {
        if (self::$pdo === null) {
            self::$pdo = getConnection();
        }
        return self::$pdo;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function check(): bool {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Obtener el usuario autenticado actual
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, r.permisos as rol_permisos,
                   pu.permisos_custom
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            LEFT JOIN permisos_usuario pu ON u.id = pu.usuario_id
            WHERE u.id = ? AND u.activo = 1
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no se encuentra el usuario, limpiar la sesión y retornar null
        if (!$usuario) {
            self::logout();
            return null;
        }
        
        $usuario['rol_permisos'] = json_decode($usuario['rol_permisos'], true);
        // Si tiene permisos personalizados, usar esos en lugar del rol
        if (!empty($usuario['permisos_custom'])) {
            $usuario['permisos_efectivos'] = json_decode($usuario['permisos_custom'], true);
        } else {
            $usuario['permisos_efectivos'] = $usuario['rol_permisos'];
        }
        
        return $usuario;
    }
    
    /**
     * Iniciar sesión
     */
    public static function login(string $username, string $password): array {
        $pdo = self::getConnection();
        
        // Buscar usuario por username o email
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, r.permisos as rol_permisos
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE (u.username = ? OR u.email = ?)
        ");
        $stmt->execute([$username, $username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            self::logActivity(null, 'login_fallido', "Intento de login con usuario inexistente: $username");
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }
        
        // Verificar si está bloqueado
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            $tiempoRestante = ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
            return ['success' => false, 'message' => "Cuenta bloqueada. Intenta en $tiempoRestante minutos."];
        }
        
        // Verificar si está activo
        if (!$usuario['activo']) {
            return ['success' => false, 'message' => 'Esta cuenta está desactivada'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $usuario['password_hash'])) {
            // Incrementar intentos fallidos
            $intentos = $usuario['intentos_fallidos'] + 1;
            $bloqueado_hasta = null;
            
            // Bloquear después de 5 intentos por 15 minutos
            if ($intentos >= 5) {
                $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $intentos = 0;
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
            $stmt->execute([$intentos, $bloqueado_hasta, $usuario['id']]);
            
            self::logActivity($usuario['id'], 'login_fallido', 'Contraseña incorrecta');
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }
        
        // Login exitoso - resetear intentos y actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        
        // Establecer sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
        $_SESSION['usuario_rol'] = $usuario['rol_nombre'];
        $_SESSION['usuario_permisos'] = json_decode($usuario['rol_permisos'], true);
        $_SESSION['es_superadmin'] = (bool)$usuario['es_superadmin'];
        $_SESSION['login_time'] = time();
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        self::logActivity($usuario['id'], 'login_exitoso', 'Inicio de sesión exitoso');
        
        return [
            'success' => true, 
            'message' => 'Inicio de sesión exitoso',
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre_completo'],
                'rol' => $usuario['rol_nombre']
            ]
        ];
    }
    
    /**
     * Cerrar sesión
     */
    public static function logout(): void {
        if (self::check()) {
            self::logActivity($_SESSION['usuario_id'], 'logout', 'Cierre de sesión');
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function can(string $modulo, string $accion): bool {
        if (!self::check()) {
            return false;
        }
        
        $permisos = $_SESSION['usuario_permisos'] ?? [];
        
        if (!isset($permisos[$modulo])) {
            return false;
        }
        
        return in_array($accion, $permisos[$modulo]);
    }
    
    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin(): bool {
        return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador';
    }
    
    /**
     * Verificar si el usuario es superadministrador
     */
    public static function isSuperAdmin(): bool {
        return self::isAdmin() && isset($_SESSION['es_superadmin']) && $_SESSION['es_superadmin'] === true;
    }
    
    /**
     * Verificar si el usuario es instructor
     */
    public static function isInstructor(): bool {
        return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'instructor';
    }
    
    /**
     * Verificar si el usuario es oficinista
     */
    public static function isOficinista(): bool {
        return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'oficinista';
    }
    
    /**
     * Obtener el conteo actual de administradores
     */
    public static function getAdminCount(): int {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE r.nombre = 'administrador' AND u.activo = 1
        ");
        $stmt->execute();
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Verificar si se puede crear un nuevo administrador (límite de 3)
     */
    public static function canCreateAdmin(): bool {
        return self::getAdminCount() < 3;
    }
    
    /**
     * Requerir autenticación (redirigir si no está autenticado)
     * Para peticiones AJAX/API devuelve JSON en lugar de redirigir
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            // Detectar si es una petición AJAX o API
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
            $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', 'api_') !== false;
            
            if ($isAjax || $isJson || $isApi) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No autenticado', 'redirect' => 'login.php']);
                exit;
            }
            
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Requerir un permiso específico
     */
    public static function requirePermission(string $modulo, string $accion): void {
        self::requireAuth();
        
        if (!self::can($modulo, $accion)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción']);
            exit;
        }
    }
    
    /**
     * Registrar actividad en el log
     */
    public static function logActivity(?int $usuario_id, string $accion, string $descripcion = '', array $datos = []): void {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO log_actividad (usuario_id, accion, descripcion, ip_address, datos_adicionales)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $usuario_id,
                $accion,
                $descripcion,
                $_SERVER['REMOTE_ADDR'] ?? null,
                !empty($datos) ? json_encode($datos) : null
            ]);
        } catch (Exception $e) {
            // Silenciar errores de log para no interrumpir la aplicación
            error_log("Error al registrar actividad: " . $e->getMessage());
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public static function changePassword(int $usuario_id, string $password_actual, string $password_nueva): array {
        $pdo = self::getConnection();
        
        // Obtener usuario
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        // Verificar contraseña actual
        if (!password_verify($password_actual, $usuario['password_hash'])) {
            return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
        }
        
        // Validar nueva contraseña
        if (strlen($password_nueva) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
        }
        
        // Actualizar contraseña
        $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->execute([$nuevo_hash, $usuario_id]);
        
        self::logActivity($usuario_id, 'cambio_password', 'Contraseña actualizada');
        
        return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
    }
    
    /**
     * Obtener IDs de grupos asignados al instructor actual
     */
    public static function getGruposAsignados(): array {
        if (!self::isInstructor()) {
            return [];
        }
        
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        if (!$usuario_id) {
            return [];
        }
        
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT grupo_id FROM instructor_grupos WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Obtener IDs de categorías asignadas al instructor actual
     */
    public static function getCategoriasAsignadas(): array {
        if (!self::isInstructor()) {
            return [];
        }
        
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        if (!$usuario_id) {
            return [];
        }
        
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT categoria_id FROM instructor_categorias WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Verificar si el instructor tiene acceso a un grupo específico
     */
    public static function tieneAccesoGrupo(int $grupo_id): bool {
        // Admins y oficinistas tienen acceso a todos los grupos
        if (self::isAdmin() || self::isOficinista()) {
            return true;
        }
        
        if (!self::isInstructor()) {
            return false;
        }
        
        $grupos = self::getGruposAsignados();
        return in_array($grupo_id, $grupos);
    }
    
    /**
     * Verificar si el instructor tiene acceso a una categoría específica
     */
    public static function tieneAccesoCategoria(int $categoria_id): bool {
        // Admins y oficinistas tienen acceso a todas las categorías
        if (self::isAdmin() || self::isOficinista()) {
            return true;
        }
        
        if (!self::isInstructor()) {
            return false;
        }
        
        $categorias = self::getCategoriasAsignadas();
        return in_array($categoria_id, $categorias);
    }
}

/**
 * Función helper para verificar permisos en las vistas
 */
function puede(string $modulo, string $accion): bool {
    return Auth::can($modulo, $accion);
}

/**
 * Función helper para verificar si es admin
 */
function esAdmin(): bool {
    return Auth::isAdmin();
}

/**
 * Función helper para verificar si es superadmin
 */
function esSuperAdmin(): bool {
    return Auth::isSuperAdmin();
}

/**
 * Función helper para verificar si es instructor
 */
function esInstructor(): bool {
    return Auth::isInstructor();
}

/**
 * Función helper para verificar si es oficinista
 */
function esOficinista(): bool {
    return Auth::isOficinista();
}

/**
 * Función helper para verificar acceso a grupo
 */
function tieneAccesoGrupo(int $grupo_id): bool {
    return Auth::tieneAccesoGrupo($grupo_id);
}

/**
 * Función helper para verificar acceso a categoría
 */
function tieneAccesoCategoria(int $categoria_id): bool {
    return Auth::tieneAccesoCategoria($categoria_id);
}
