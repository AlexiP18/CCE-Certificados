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
    SELECT c.nombre as categoria_nombre, c.grupo_id, g.nombre as grupo_nombre, p.fecha_inicio, p.fecha_fin
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

$grupo_id = $info['grupo_id'];

// Construir consulta de estudiantes
$sql = "
    SELECT 
        e.id,
        e.nombre, 
        e.cedula, 
        e.fecha_nacimiento, 
        e.celular, 
        e.email, 
        e.representante_id,
        rep.nombre as representante_nombre, 
        rep.cedula as representante_cedula,
        rep.celular as representante_celular,
        rep.email as representante_email,
        e.es_menor,
        ce.es_destacado,
        cert.fecha_aprobacion
    FROM categoria_estudiantes ce
    INNER JOIN estudiantes e ON ce.estudiante_id = e.id
    LEFT JOIN estudiantes rep ON e.representante_id = rep.id
    LEFT JOIN certificados cert ON cert.nombre = e.nombre AND cert.categoria_id = ? AND cert.grupo_id = ? AND cert.periodo_id <=> ?
    WHERE ce.categoria_id = ? AND ce.estado = 'activo'
";

$params = [$categoria_id, $grupo_id, $periodo_id, $categoria_id];

if ($periodo_id) {
    $sql .= " AND ce.periodo_id = ?";
    $params[] = $periodo_id;
} else {
    $sql .= " AND ce.periodo_id IS NULL";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estudiantesEnLote = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// LÓGICA DE AGRUPACIÓN (Igual a preparacion JS)
// ==========================================
$menoresPorRep = [];
$idsEnLista = [];
foreach ($estudiantesEnLote as $est) {
    $idsEnLista[] = (int)$est['id'];
}

foreach ($estudiantesEnLote as $est) {
    if ($est['es_menor'] && !empty($est['representante_cedula'])) {
        $repId = !empty($est['representante_id']) ? (int)$est['representante_id'] : null;
        $key = null;
        if ($repId && in_array($repId, $idsEnLista)) {
            $key = $repId;
        } else {
            $key = 'rep_' . $est['representante_cedula'];
        }
        if (!isset($menoresPorRep[$key])) {
            $menoresPorRep[$key] = [];
        }
        $menoresPorRep[$key][] = $est;
    }
}

$procesados = [];
$datosAgrupados = [];

foreach ($estudiantesEnLote as $est) {
    if ($est['es_menor']) continue; 

    $filaMayor = [
        'tipo' => 'estudiante',
        'data' => $est,
        'menores' => []
    ];
    $repId = (int)$est['id'];
    if (isset($menoresPorRep[$repId])) {
        $filaMayor['menores'] = $menoresPorRep[$repId];
        foreach ($menoresPorRep[$repId] as $m) {
            $procesados[] = $m['id'];
        }
    }
    $datosAgrupados[] = $filaMayor;
}

foreach ($estudiantesEnLote as $est) {
    if ($est['es_menor'] && !in_array($est['id'], $procesados)) {
        $key = 'rep_' . $est['representante_cedula'];
        
        $yaAgregado = false;
        foreach ($datosAgrupados as $f) {
            if ($f['tipo'] === 'representante_virtual' && $f['cedula'] === $est['representante_cedula']) {
                $yaAgregado = true;
                break;
            }
        }
        
        if (!$yaAgregado && isset($menoresPorRep[$key])) {
            $grupoMenores = $menoresPorRep[$key];
            $datosAgrupados[] = [
                'tipo' => 'representante_virtual',
                'nombre' => $est['representante_nombre'],
                'cedula' => $est['representante_cedula'],
                'celular' => $est['representante_celular'],
                'email' => $est['representante_email'],
                'menores' => $grupoMenores
            ];
            foreach ($grupoMenores as $m) {
                $procesados[] = $m['id'];
            }
        } else if (!$yaAgregado) {
            $datosAgrupados[] = [
                'tipo' => 'estudiante',
                'data' => $est,
                'menores' => []
            ];
        }
    }
}

// Ordenar alfabéticamente
usort($datosAgrupados, function($a, $b) {
    $nombreA = $a['tipo'] === 'estudiante' ? $a['data']['nombre'] : $a['nombre'];
    $nombreB = $b['tipo'] === 'estudiante' ? $b['data']['nombre'] : $b['nombre'];
    return strcmp($nombreA, $nombreB);
});


// ==========================================
// REFERENCIAS
// ==========================================

$sql_ref = "
    SELECT r.nombre, r.telefono, r.relacion, e.nombre as estudiante_nombre 
    FROM estudiantes_referencias r 
    JOIN estudiantes e ON r.estudiante_id = e.id 
    JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id 
    WHERE ce.categoria_id = ? AND ce.estado = 'activo'
";
$params_ref = [$categoria_id];

if ($periodo_id) {
    $sql_ref .= " AND ce.periodo_id = ?";
    $params_ref[] = $periodo_id;
}
$sql_ref .= " ORDER BY e.nombre ASC, r.id ASC";

$stmt = $pdo->prepare($sql_ref);
$stmt->execute($params_ref);
$referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// PREPARATIVOS DEL PDF
// ==========================================

// Custom PDF Class
class MYPDF extends TCPDF {
    public $header_titulo = "";
    public $header_subtitulo = "";
    public $logo_path = "";
    
    public function Header() {
        if (file_exists($this->logo_path)) {
            $this->Image($this->logo_path, 15, 8, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(50, 10);
        $this->Cell(0, 10, $this->header_titulo, 0, 1, 'L');
        $this->SetFont('helvetica', '', 11);
        $this->SetXY(50, 18);
        $this->Cell(0, 10, $this->header_subtitulo, 0, 1, 'L');
        
        $this->Line(15, 30, $this->getPageWidth() - 15, 30);
    }
}

$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configuracion General
$pdf->SetCreator('CCE Certificados');
$pdf->SetAuthor('Sistema CCE');
$pdf->SetTitle('Listado de Estudiantes - ' . $info['categoria_nombre']);

$pdf->header_titulo = $info['grupo_nombre'] . ' - ' . $info['categoria_nombre'];
$pdf->header_subtitulo = 'Listado de Estudiantes';
if ($info['fecha_inicio']) {
    $pdf->header_subtitulo .= ' | Periodo: ' . $info['fecha_inicio'] . ' al ' . ($info['fecha_fin'] ?? 'Actual');
}
$pdf->logo_path = dirname(dirname(dirname(__DIR__))) . '/public/assets/logos/logo-cce.png';

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 8));

$pdf->AddPage();

// ==========================================
// TABLA DE ESTUDIANTES
// ==========================================

$style = '
<style>
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #f2f2f2; font-weight: bold; padding: 6px; border: 1px solid #ccc; text-align: center; font-size: 9pt; }
    td { padding: 6px; border: 1px solid #ccc; font-size: 9pt; vertical-align: middle; }
    .center { text-align: center; }
    .destacado { color: #f39c12; font-weight: bold; }
    .muted { color: #7f8c8d; }
    .menor-nombre { padding-left: 15px; }
    a { color: #3498db; text-decoration: none; }
</style>
';

$html = $style . '
<h2 style="text-align: center; margin-bottom: 5px;">Listado de Estudiantes Matriculados</h2>
<table cellpadding="4">
    <thead>
        <tr>
            <th width="3%">#</th>
            <th width="30%">Apellidos y Nombres</th>
            <th width="10%">Cédula</th>
            <th width="10%">Edad/F.Nac</th>
            <th width="11%">Celular</th>
            <th width="16%">Email / Razón</th>
            <th width="9%">Destacado</th>
            <th width="11%">Aprobación</th>
        </tr>
    </thead>
    <tbody>';

$count = 1;

function calcularEdad($fecha) {
    if (!$fecha || $fecha === '-' || $fecha === '0000-00-00') return '-';
    try {
        $nac = new DateTime($fecha);
        $hoy = new DateTime();
        return $hoy->diff($nac)->y;
    } catch (Exception $e) {
        return '-';
    }
}

function formatPhoneLink($phone) {
    if (!$phone) return '-';
    $raw = preg_replace('/[^0-9+]/', '', $phone);
    
    $waNumber = $raw;
    if (strlen($waNumber) == 10 && substr($waNumber, 0, 1) == '0') {
        $waNumber = '593' . substr($waNumber, 1);
    } else if (strlen($raw) == 9 && substr($raw, 0, 1) != '0') {
        $waNumber = '593' . $raw;
        $phone = '0' . $phone; // keep original chars if any but pad
    }
    
    return '<a href="https://wa.me/' . $waNumber . '">' . htmlspecialchars($phone) . '</a>';
}

function formatEmailLink($email) {
    if (!$email) return '-';
    return '<a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>';
}

function formatearFechaAprobacion($fecha) {
    if (!$fecha) return '<span style="color:#e74c3c">Pdte.</span>';
    return date('Y-m-d', strtotime($fecha));
}

function renderFilaToHtml(&$html, $est, &$count, $isMenor = false, $isVirtual = false, $isAmbos = false) {
    $id = $est['id'] ?? '';
    
    // Contact Info
    $celular = $isVirtual ? $est['celular'] : ($est['celular'] ?? '-');
    $email = $isVirtual ? $est['email'] : ($est['email'] ?? '-');
    
    $celularLink = formatPhoneLink($celular);
    $emailLink = formatEmailLink($email);
    
    // Dates & Ages
    $fechaNac = $isVirtual ? '-' : ($est['fecha_nacimiento'] ?? '');
    $edadStr = '-';
    if ($fechaNac && $fechaNac !== '-' && $fechaNac !== '0000-00-00') {
        $edadStr = calcularEdad($fechaNac) . ' años<br><span style="font-size:7pt; color:#666;">' . $fechaNac . '</span>';
    }
    
    // Names
    if ($isVirtual) {
        $nombreHtml = '<span class="muted" style="font-weight:bold;">' . htmlspecialchars($est['nombre']) . ' (Rep)</span>';
        $emailLink = '<span class="muted">' . $emailLink . '</span>';
    } else if ($isMenor) {
        // Tabbed to the right
        $nombreHtml = '<span class="menor-nombre">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- ' . htmlspecialchars($est['nombre']) . ' <br><span style="font-size:7pt; color:#3498db;">[Menor]</span></span>';
    } else {
        if ($isAmbos) {
            $nombreHtml = '<span style="font-weight:bold;">' . htmlspecialchars($est['nombre']) . '</span> <span style="font-size:7pt; color:#8e44ad;">[Est/Rep]</span>';
        } else {
            $nombreHtml = htmlspecialchars($est['nombre']);
        }
    }

    $cedula = $isVirtual ? $est['cedula'] : ($est['cedula'] ?? '-');
    if ($isVirtual) $cedula = '<span class="muted">' . $cedula . '</span>';

    // Badge
    $destacado = 'Normal';
    if (!$isVirtual && isset($est['es_destacado']) && $est['es_destacado']) {
        $destacado = '<span class="destacado">Destacado</span>';
    }

    // Aprobacion
    $aprobacion = '-';
    if (!$isVirtual) {
        $aprobacion = formatearFechaAprobacion($est['fecha_aprobacion'] ?? null);
    }
    
    $numeroStr = $isVirtual ? '-' : $count++;
    
    $html .= '<tr>
        <td width="3%" class="center">' . $numeroStr . '</td>
        <td width="30%">' . $nombreHtml . '</td>
        <td width="10%" class="center">' . $cedula . '</td>
        <td width="10%" class="center">' . $edadStr . '</td>
        <td width="11%" class="center">' . $celularLink . '</td>
        <td width="16%" class="center" style="font-size:8pt;">' . $emailLink . '</td>
        <td width="9%" class="center">' . ($isVirtual ? '-' : $destacado) . '</td>
        <td width="11%" class="center">' . $aprobacion . '</td>
    </tr>';
}

// Draw main table rows
foreach ($datosAgrupados as $item) {
    if ($item['tipo'] === 'estudiante') {
        $est = $item['data'];
        $hasMenores = !empty($item['menores']);
        
        renderFilaToHtml($html, $est, $count, false, false, $hasMenores);
        
        if ($hasMenores) {
            foreach ($item['menores'] as $menor) {
                renderFilaToHtml($html, $menor, $count, true, false, false);
            }
        }
    } else if ($item['tipo'] === 'representante_virtual') {
        renderFilaToHtml($html, $item, $count, false, true, false);
        
        foreach ($item['menores'] as $menor) {
            renderFilaToHtml($html, $menor, $count, true, false);
        }
    }
}

if (count($estudiantesEnLote) == 0) {
    $html .= '<tr><td colspan="8" class="center">No hay estudiantes matriculados en esta categoría.</td></tr>';
}

$html .= '</tbody></table>';

// ==========================================
// TABLA DE REFERENCIAS
// ==========================================

if (count($referencias) > 0) {
    $html .= '<br><br>
    <h2 style="text-align: center; margin-bottom: 5px;">Referencias de Estudiantes</h2>
    <table cellpadding="4">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="35%">Estudiante</th>
                <th width="35%">Nombre de Referencia</th>
                <th width="12%">Relación</th>
                <th width="13%">Celular</th>
            </tr>
        </thead>
        <tbody>';
    
    $rcount = 1;
    $ultimoEstudiante = '';
    
    foreach ($referencias as $ref) {
        $refPhoneLink = formatPhoneLink($ref['telefono'] ?? '');
        
        $estudianteNombre = '';
        if ($ref['estudiante_nombre'] !== $ultimoEstudiante) {
            $estudianteNombre = htmlspecialchars($ref['estudiante_nombre']);
            $ultimoEstudiante = $ref['estudiante_nombre'];
        }
        
        $html .= '<tr>
            <td width="5%" class="center">' . $rcount++ . '</td>
            <td width="35%">' . $estudianteNombre . '</td>
            <td width="35%">' . htmlspecialchars($ref['nombre']) . '</td>
            <td width="12%" class="center">' . htmlspecialchars($ref['relacion'] ?? '-') . '</td>
            <td width="13%" class="center">' . $refPhoneLink . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
}

$html .= '<br><p style="font-size: 8pt; color: #666;">Generado el: ' . date('Y-m-d H:i:s') . '</p>';

// Imprimir tabla
$pdf->writeHTML($html, true, false, true, false, '');

// Salida
$pdf->Output('Listado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $info['categoria_nombre']) . '.pdf', 'I');
