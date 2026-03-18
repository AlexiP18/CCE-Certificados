<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/SiteSettings.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

header('Content-Type: application/json');

Auth::requireAuth();

$pdo = getConnection();

$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = array_merge($_GET, $_POST);
}

$action = $input['action'] ?? $_GET['action'] ?? 'get';

function guardarLogoConfiguracion(array $file, string $prefijo): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir archivo de logo');
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'ico'], true)) {
        throw new Exception('Formato de logo no permitido');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new Exception('El logo excede el limite de 5MB');
    }

    $dir = dirname(dirname(__DIR__)) . '/uploads/configuracion/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception('No se pudo crear directorio para logos');
    }

    $filename = $prefijo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('No se pudo guardar el logo');
    }

    return 'uploads/configuracion/' . $filename;
}

try {
    switch ($action) {
        case 'get':
            $settings = SiteSettings::get($pdo);
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;

        case 'save':
            if (!Auth::isAdmin()) {
                throw new Exception('Solo administradores pueden modificar la configuracion del sitio');
            }

            $current = SiteSettings::get($pdo);

            $siteName = trim($input['site_name'] ?? $current['site_name']);
            $institutionName = trim($input['institution_name'] ?? $current['institution_name']);
            $primaryColor = SiteSettings::sanitizeColor(trim($input['primary_color'] ?? $current['primary_color']));
            $secondaryColor = SiteSettings::sanitizeColor(trim($input['secondary_color'] ?? $current['secondary_color']));

            if ($siteName === '') {
                throw new Exception('El nombre del sistema es requerido');
            }

            if ($institutionName === '') {
                throw new Exception('El nombre institucional es requerido');
            }

            $logoNav = $current['logo_nav'];
            $logoHeader = $current['logo_header'];
            $favicon = $current['favicon'] ?? '';

            if (isset($_FILES['logo_nav_file']) && ($_FILES['logo_nav_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $logoNav = guardarLogoConfiguracion($_FILES['logo_nav_file'], 'logo_nav');
            }

            if (isset($_FILES['logo_header_file']) && ($_FILES['logo_header_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $logoHeader = guardarLogoConfiguracion($_FILES['logo_header_file'], 'logo_header');
            }

            if (isset($_FILES['favicon_file']) && ($_FILES['favicon_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $favicon = guardarLogoConfiguracion($_FILES['favicon_file'], 'favicon');
            }

            SiteSettings::save($pdo, [
                'site_name' => $siteName,
                'institution_name' => $institutionName,
                'primary_color' => $primaryColor,
                'secondary_color' => $secondaryColor,
                'logo_nav' => $logoNav,
                'logo_header' => $logoHeader,
                'favicon' => $favicon
            ]);

            Auth::logActivity($_SESSION['usuario_id'] ?? null, 'actualizar_configuracion_sitio', 'Configuracion general actualizada');

            echo json_encode([
                'success' => true,
                'message' => 'Configuracion guardada correctamente'
            ]);
            break;

        default:
            throw new Exception('Accion no valida');
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
