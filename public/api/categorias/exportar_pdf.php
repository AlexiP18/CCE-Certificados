<?php
/**
 * API para Exportar Lista de Estudiantes a PDF
 */

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Verificar autenticación
Auth::requireAuth();

$pdo = getConnection();

$categoria_id = $_GET['categoria_id'] ?? null;
$periodo_id = $_GET['periodo_id'] ?? null;

if (!$categoria_id) {
    die('Categoría requerida');
}

// Obtener información de la categoría y grupo
$stmt = $pdo->prepare("
    SELECT c.nombre as categoria_nombre, g.nombre as grupo_nombre, p.fecha_inicio, p.fecha_fin
    FROM categorias c 
    JOIN grupos g ON c.grupo_id = g.id 
    LEFT JOIN periodos p ON p.id = ?
    WHERE c.id = ?
");
$stmt->execute([$periodo_id, $categoria_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    die('Categoría no encontrada');
}

// Construir consulta de estudiantes
$sql = "
    SELECT 
        e.nombre, 
        e.cedula, 
        e.fecha_nacimiento, 
        e.celular, 
        e.email, 
        e.representante_nombre, 
        e.representante_celular,
        e.es_menor,
        e.destacado
    FROM categoria_estudiantes ce
    INNER JOIN estudiantes e ON ce.estudiante_id = e.id
    WHERE ce.categoria_id = ? AND ce.estado = 'activo'
";

$params = [$categoria_id];

if ($periodo_id) {
    $sql .= " AND ce.periodo_id = ?";
    $params[] = $periodo_id;
}

$sql .= " ORDER BY e.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iniciar PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator('CCE Certificados');
$pdf->SetAuthor('Sistema CCE');
$pdf->SetTitle('Listado de Estudiantes - ' . $info['categoria_nombre']);
$pdf->SetSubject('Listado de Estudiantes');

// Encabezado y Pie de página
$tituloHeader = $info['grupo_nombre'] . ' - ' . $info['categoria_nombre'];
$subtituloHeader = 'Listado de Estudiantes';
if ($info['fecha_inicio']) {
    $subtituloHeader .= ' | Periodo: ' . $info['fecha_inicio'] . ' - ' . ($info['fecha_fin'] ?? 'Actual');
}

$pdf->SetHeaderData('', 0, $tituloHeader, $subtituloHeader);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 12));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 8));

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Agregar página
$pdf->AddPage();

// Estilos CSS para la tabla
$style = '
<style>
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #f2f2f2; font-weight: bold; padding: 5px; border: 1px solid #ccc; text-align: center; }
    td { padding: 5px; border: 1px solid #ccc; font-size: 9pt; }
    .center { text-align: center; }
    .destacado { color: #f39c12; font-weight: bold; }
</style>
';

$html = $style . '
<h2 style="text-align: center;">Listado de Estudiantes Matriculados</h2>
<table cellpadding="4">
    <thead>
        <tr>
            <th width="30">#</th>
            <th width="200">Apellidos y Nombres</th>
            <th width="80">Cédula</th>
            <th width="80">Edad</th>
            <th width="80">Celular</th>
            <th width="150">Representante / Email</th>
            <th width="60">Estado</th>
        </tr>
    </thead>
    <tbody>';

$count = 1;

// Función auxiliar para edad
function calcularEdad($fecha) {
    if (!$fecha) return '-';
    $nac = new DateTime($fecha);
    $hoy = new DateTime();
    return $hoy->diff($nac)->y;
}

foreach ($estudiantes as $est) {
    $edad = calcularEdad($est['fecha_nacimiento']);
    $contacto = $est['celular'];
    $representante = $est['email']; // Por defecto email
    
    if ($est['es_menor']) {
        $representante = "Rep: " . $est['representante_nombre'] . '<br>Cel: ' . $est['representante_celular'];
    }

    $destacado = $est['destacado'] ? '<span class="destacado">Destacado</span>' : 'Normal';
    
    $html .= '<tr>
        <td width="30" class="center">' . $count++ . '</td>
        <td width="200">' . htmlspecialchars($est['nombre']) . '</td>
        <td width="80" class="center">' . $est['cedula'] . '</td>
        <td width="80" class="center">' . $edad . ' años</td>
        <td width="80" class="center">' . $contacto . '</td>
        <td width="150">' . $representante . '</td>
        <td width="60" class="center">' . $destacado . '</td>
    </tr>';
}

if (count($estudiantes) == 0) {
    $html .= '<tr><td colspan="7" class="center">No hay estudiantes matriculados en esta categoría.</td></tr>';
}

$html .= '</tbody></table>';

$html .= '<p style="font-size: 8pt; color: #666;">Generado el: ' . date('Y-m-d H:i:s') . '</p>';

// Imprimir tabla
$pdf->writeHTML($html, true, false, true, false, '');

// Salida
$pdf->Output('Listado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $info['categoria_nombre']) . '.pdf', 'I');
