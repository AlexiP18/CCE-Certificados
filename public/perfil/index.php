<?php
require_once '../../includes/Auth.php';
require_once '../../includes/SiteSettings.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

// Obtener información completa del usuario
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT u.*, r.nombre as rol_nombre 
    FROM usuarios u 
    LEFT JOIN roles r ON u.rol_id = r.id 
    WHERE u.id = ?
");
$stmt->execute([$usuario['id']]);
$usuarioCompleto = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si es superadmin
$esSuperAdmin = isset($usuarioCompleto['es_superadmin']) && $usuarioCompleto['es_superadmin'] == 1;

$siteConfig = SiteSettings::toViewModel(SiteSettings::get($pdo), BASE_URL);

// Cargar la vista
require_once '../../app/Views/mi_perfil/index.php';
