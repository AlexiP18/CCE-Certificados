<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

Auth::requireAuth();

$estudiante_id = $_GET['estudiante_id'] ?? 0;

if (empty($estudiante_id)) {
    die('ID de estudiante requerido');
}

$pdo = getConnection();

// Obtener datos del estudiante
$stmt = $pdo->prepare("SELECT nombre FROM estudiantes WHERE id = ?");
$stmt->execute([$estudiante_id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    die('Estudiante no encontrado');
}

// Filtro de códigos opcionales
$codigos_filter = [];
if (!empty($_GET['codigos'])) {
    $raw_codes = explode(',', $_GET['codigos']);
    foreach($raw_codes as $rc) {
        $rc = trim($rc);
        if(!empty($rc)) $codigos_filter[] = $rc;
    }
}

// 1. Intentar buscar por estudiante_id (más robusto)
$sql = "
    SELECT c.archivo_pdf, c.codigo, cat.nombre as categoria_nombre 
    FROM certificados c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    WHERE c.estudiante_id = ?
";
$params = [$estudiante_id];

if (!empty($codigos_filter)) {
    $placeholders = str_repeat('?,', count($codigos_filter) - 1) . '?';
    $sql .= " AND c.codigo IN ($placeholders)";
    $params = array_merge($params, $codigos_filter);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Si no hay certificados por ID, intentar por nombre
if (empty($certificados)) {
    $sql = "
        SELECT c.archivo_pdf, c.codigo, cat.nombre as categoria_nombre 
        FROM certificados c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE (c.nombre = ? OR c.nombre LIKE ?)
    ";
    $params = [$estudiante['nombre'], '%' . $estudiante['nombre'] . '%'];

    if (!empty($codigos_filter)) {
        $placeholders = str_repeat('?,', count($codigos_filter) - 1) . '?';
        $sql .= " AND c.codigo IN ($placeholders)";
        $params = array_merge($params, $codigos_filter);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($certificados)) {
    die('No hay certificados para descargar');
}

$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'zip');
$zipName = 'certificados_' . preg_replace('/[^a-zA-Z0-9]/', '_', $estudiante['nombre']) . '.zip';

if ($zip->open($tempFile, ZipArchive::CREATE) !== TRUE) {
    die("Error al crear ZIP");
}

$uploadsPath = dirname(__DIR__) . '/uploads/';
$added = 0;

foreach ($certificados as $cert) {
    if (!empty($cert['archivo_pdf']) && file_exists($uploadsPath . $cert['archivo_pdf'])) {
        $catName = $cert['categoria_nombre'] ?? 'certificado';
        $fileName = 'certificado_' . $cert['codigo'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $catName) . '.pdf';
        $zip->addFile($uploadsPath . $cert['archivo_pdf'], $fileName);
        $added++;
    }
}

$zip->close();

if ($added === 0) {
    unlink($tempFile); // Cleanup empty zip file hook
    die('No se encontraron archivos físicos de certificados para comprimir');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tempFile));
header('Pragma: no-cache'); 
header('Expires: 0'); 
readfile($tempFile);
unlink($tempFile);
