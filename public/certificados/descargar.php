<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

Auth::requireAuth();

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die('Código requerido');
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
$stmt->execute([$codigo]);
$certificado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificado) {
    die('Certificado no encontrado');
}

$uploadsPath = dirname(__DIR__) . '/uploads/';
$archivo = $uploadsPath . $certificado['archivo_pdf'];

if (!file_exists($archivo) || !$certificado['archivo_pdf']) {
    die('Archivo físico no encontrado');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificado_' . $certificado['nombre'] . '.pdf"');
readfile($archivo);
