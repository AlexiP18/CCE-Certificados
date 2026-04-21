<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';
require_once '../../vendor/tecnickcom/tcpdf/tcpdf.php';

Auth::requireAuth();

$estudiante_id = $_GET['estudiante_id'] ?? 0;
if (empty($estudiante_id)) {
    die('ID de estudiante requerido');
}

$pdo = getConnection();

// Obtener nombre estudiante
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

// Consultar certificados
$sql = "
    SELECT c.archivo_imagen, c.codigo 
    FROM certificados c
    WHERE c.estudiante_id = ?
";
$params = [$estudiante_id];

if (!empty($codigos_filter)) {
    $placeholders = str_repeat('?,', count($codigos_filter) - 1) . '?';
    $sql .= " AND c.codigo IN ($placeholders)";
    $params = array_merge($params, $codigos_filter);
}

// Ordenar por fecha desc (o como prefiera el usuario)
$sql .= " ORDER BY c.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($certificados)) {
    // Fallback busqueda por nombre si no filtro por codigos y no encontro por ID
    if (empty($codigos_filter)) {
         $sql = "
            SELECT c.archivo_imagen, c.codigo 
            FROM certificados c
            WHERE c.nombre = ? OR c.nombre LIKE ?
            ORDER BY c.fecha DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$estudiante['nombre'], '%' . $estudiante['nombre'] . '%']);
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (empty($certificados)) {
    die('No hay certificados disponibles para descargar');
}

// Inicializar TCPDF
// Orientación L (Landscape) por defecto, pero ajustaremos por página
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configuración básica
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetMargins(0, 0, 0);

$uploadsPath = dirname(__DIR__) . '/uploads/';
$added = 0;

foreach ($certificados as $cert) {
    if (!empty($cert['archivo_imagen']) && file_exists($uploadsPath . $cert['archivo_imagen'])) {
        $imgPath = $uploadsPath . $cert['archivo_imagen'];
        
        // Obtener dimensiones imagen
        $imgSize = getimagesize($imgPath);
        $w = $imgSize[0];
        $h = $imgSize[1];
        
        // Determinar orientación
        $orientation = ($w > $h) ? 'L' : 'P';
        
        // Calcular dimensiones en mm
        $pdfW = $w * 0.264583;
        $pdfH = $h * 0.264583;

        // Añadir página
        $pdf->AddPage($orientation, [$pdfW, $pdfH]);
        
        // Insertar imagen ajustada exactamente a la página
        // Image($file, $x, $y, $w, $h, ...)
        $pdf->Image($imgPath, 0, 0, $pdfW, $pdfH, '', '', '', false, 300, '', false, false, 0);
        
        $added++;
    }
}

if ($added === 0) {
    die('No se encontraron las imágenes base de los certificados.');
}

// Output
$filename = 'certificados_' . preg_replace('/[^a-zA-Z0-9]/', '_', $estudiante['nombre']) . '.pdf';
$pdf->Output($filename, 'D'); // D = Download
