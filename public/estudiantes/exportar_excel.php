<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Verificar autenticación
Auth::requireAuth();

$grupo_id = $_GET['id'] ?? $_GET['grupo'] ?? 0;

if (empty($grupo_id)) {
    die('ID de grupo no especificado');
}

$pdo = getConnection();

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    die('Grupo no encontrado');
}

// Construir Query
$sql = "
    SELECT DISTINCT 
        e.id, 
        e.nombre, 
        e.cedula, 
        e.email, 
        e.celular,
        e.fecha_nacimiento,
        e.es_menor,
        e.representante_nombre,
        e.representante_cedula,
        e.representante_celular,
        e.representante_email,
        ce.estado,
        c.nombre as categoria_nombre,
        c.color as categoria_color,
        p.nombre as periodo_nombre
    FROM estudiantes e
    INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
    INNER JOIN categorias c ON ce.categoria_id = c.id
    INNER JOIN periodos p ON ce.periodo_id = p.id
    WHERE c.grupo_id = ?
";

$params = [$grupo_id];

// Filtros
if (!empty($_GET['categoria'])) {
    $sql .= " AND c.id = ?";
    $params[] = $_GET['categoria'];
}

if (!empty($_GET['periodo'])) {
    $sql .= " AND p.id = ?";
    $params[] = $_GET['periodo'];
}

if (!empty($_GET['estado'])) {
    $sql .= " AND ce.estado = ?";
    $params[] = $_GET['estado'];
}

if (!empty($_GET['busqueda'])) {
    $busqueda = '%' . $_GET['busqueda'] . '%';
    $sql .= " AND (e.nombre LIKE ? OR e.cedula LIKE ? OR e.representante_nombre LIKE ? OR e.representante_cedula LIKE ? OR c.nombre LIKE ?)";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
}

$sql .= " ORDER BY e.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica de Agrupación y Jerarquía (Duplicada desde exportar_pdf) ---

// 1. Agrupar por ID
$uniq_students = [];
$students_by_cedula = [];

foreach ($rows as $row) {
    $id = $row['id'];
    if (!isset($uniq_students[$id])) {
        $uniq_students[$id] = $row;
        $uniq_students[$id]['categorias'] = [];
        if (!empty($row['cedula'])) {
            $students_by_cedula[$row['cedula']] = $id;
        }
    }
    
    $cat_exists = false;
    foreach($uniq_students[$id]['categorias'] as $ec) {
        if($ec['nombre'] == $row['categoria_nombre'] && $ec['periodo'] == $row['periodo_nombre']) $cat_exists = true;
    }
    
    if(!$cat_exists) {
        $uniq_students[$id]['categorias'][] = [
            'nombre' => $row['categoria_nombre'],
            'color' => $row['categoria_color'],
            'periodo' => $row['periodo_nombre'],
            'estado' => $row['estado']
        ];
    }
}

// 2. Ordenamiento Jerárquico
$map_padre_hijos = [];
foreach ($uniq_students as $est) {
    if ($est['es_menor'] == 1 && !empty($est['representante_cedula'])) {
        $cedula_padre = $est['representante_cedula'];
        if (isset($students_by_cedula[$cedula_padre])) {
            if (!isset($map_padre_hijos[$cedula_padre])) {
                $map_padre_hijos[$cedula_padre] = [];
            }
            $map_padre_hijos[$cedula_padre][] = $est['id'];
        }
    }
}

$lista_final = [];
$procesados = [];

foreach ($uniq_students as $est) {
    $id = $est['id'];
    if (in_array($id, $procesados)) continue;
    
    $es_hijo_en_lista = false;
    if ($est['es_menor'] == 1 && !empty($est['representante_cedula'])) {
        if (isset($students_by_cedula[$est['representante_cedula']])) {
            $es_hijo_en_lista = true;
        }
    }
    
    if ($es_hijo_en_lista) continue; 
    
    $lista_final[] = $est;
    $procesados[] = $id;
    
    if (!empty($est['cedula']) && isset($map_padre_hijos[$est['cedula']])) {
        foreach ($map_padre_hijos[$est['cedula']] as $hijo_id) {
            if (!in_array($hijo_id, $procesados)) {
                $hijo = $uniq_students[$hijo_id];
                $hijo['_is_child_node'] = true;
                $lista_final[] = $hijo;
                $procesados[] = $hijo_id;
            }
        }
    }
}

// --- Generar Excel ---

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Lista Estudiantes');

// Metadatos
$spreadsheet->getProperties()
    ->setCreator("CCE Certificados")
    ->setLastModifiedBy("CCE Certificados")
    ->setTitle("Estudiantes - " . $grupo['nombre'])
    ->setSubject("Reporte de Estudiantes")
    ->setDescription("Lista exportada del grupo " . $grupo['nombre']);

// Estilos
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['merge' => true, 'argb' => str_replace('#', '', $grupo['color'])]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$rowStyleOdd = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']]];
$rowStyleEven = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F9F9F9']]];
$rowStyleMinor = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8E1']]]; // Amber lighten-5 equivalent

// Encabezados de Columna
$headers = ['#', 'Estudiante', 'Cédula', 'Fecha Nac.', 'Edad', 'Contacto', 'Estado', 'Categorías'];
$sheet->fromArray($headers, NULL, 'A1');

// Aplicar estilo headers
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(30);

// Helper formateo celular
function formatCelularEx($num) {
    if (!$num) return '';
    $num = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $num); 
    $num = preg_replace('/[^\d+]/', '', $num);
    if (strpos($num, '+593') === 0) $num = '0' . substr($num, 4);
    else if (strpos($num, '593') === 0 && strlen($num) == 12) $num = '0' . substr($num, 3);
    return $num;
}

// Llenar Datos
$rowIdx = 2;
$i = 0;

foreach ($lista_final as $est) {
    $i++;
    
    // Preparar datos
    $nombre = $est['nombre'];
    if ($est['es_menor'] == 1 && empty($est['_is_child_node'])) {
        $nombre .= "\n(Rep: " . $est['representante_nombre'] . ")";
    }
    
    $cedula = $est['cedula'];
    
    // Fecha Nacimiento y Edad
    $fechaNac = '-';
    $edad = '-';
    if (!empty($est['fecha_nacimiento'])) {
        $dob = new DateTime($est['fecha_nacimiento']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $fechaNac = $dob->format('d/m/Y');
        $edad = $diff->y; 
    }
    
    // Contacto (Celular / Email)
    $contacto = [];
    $cel = formatCelularEx($est['celular']);
    if ($cel) $contacto[] = "C: $cel";
    if ($est['email']) $contacto[] = "E: " . $est['email'];
    
    if (empty($contacto) && $est['es_menor'] == 1) {
        $celRep = formatCelularEx($est['representante_celular']);
        if ($celRep) $contacto[] = "Rep C: $celRep";
        if ($est['representante_email']) $contacto[] = "Rep E: " . $est['representante_email'];
    }
    $contactoStr = implode("\n", $contacto);
    
    // Categorías (Concatenadas)
    $cats = [];
    foreach ($est['categorias'] as $cat) {
        $info = $cat['nombre'];
        if ($cat['periodo']) $info .= " [" . $cat['periodo'] . "]";
        $cats[] = $info;
    }
    $catsStr = implode("\n", $cats);
    
    // Escribir fila
    $sheet->setCellValue('A'.$rowIdx, $i);
    $sheet->setCellValue('B'.$rowIdx, $nombre);
    $sheet->setCellValue('C'.$rowIdx, $cedula . ' '); 
    $sheet->setCellValue('D'.$rowIdx, $fechaNac); // Nueva Columna
    $sheet->setCellValue('E'.$rowIdx, $edad);
    $sheet->setCellValue('F'.$rowIdx, $contactoStr);
    $sheet->setCellValue('G'.$rowIdx, ucfirst($est['estado'] ?? ''));
    $sheet->setCellValue('H'.$rowIdx, $catsStr);
    
    // Indentación
    if (!empty($est['_is_child_node'])) {
        $sheet->getStyle('B'.$rowIdx)->getAlignment()->setIndent(2);
    }
    
    // Estilo Fila
    if (!empty($est['es_menor']) && $est['es_menor'] == 1) {
        $sheet->getStyle('A'.$rowIdx.':H'.$rowIdx)->applyFromArray($rowStyleMinor);
    } else {
        if ($i % 2 == 0) $sheet->getStyle('A'.$rowIdx.':H'.$rowIdx)->applyFromArray($rowStyleEven);
    }
    
    // Alineación
    $sheet->getStyle('A'.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C'.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
    $sheet->getStyle('D'.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
    $sheet->getStyle('E'.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
    
    // Wrap text
    $sheet->getStyle('B'.$rowIdx)->getAlignment()->setWrapText(true);
    $sheet->getStyle('F'.$rowIdx)->getAlignment()->setWrapText(true);
    $sheet->getStyle('H'.$rowIdx)->getAlignment()->setWrapText(true);
    
    $rowIdx++;
}

// AutoSize Columns
foreach(range('A','H') as $col) {
    if ($col == 'B' || $col == 'F' || $col == 'H') {
        $sheet->getColumnDimension($col)->setWidth(35); // Fixed width for text fields
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Salida
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Estudiantes_' . preg_replace('/[^a-zA-Z0-9]/', '_', $grupo['nombre']) . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
