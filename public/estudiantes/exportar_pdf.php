<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

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

// Helper para convertir HEX a RGB
function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return array($r, $g, $b);
}

// Helper para formatear celular
function formatCelular($num) {
    if (!$num) return '';
    // Eliminar emojis y caracteres no deseados
    $num = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $num); 
    // Eliminar todo excepto dígitos y el signo +
    $num = preg_replace('/[^\d+]/', '', $num);
    
    // Reemplazar prefijo +593 por 0
    if (strpos($num, '+593') === 0) {
        $num = '0' . substr($num, 4);
    } else if (strpos($num, '593') === 0 && strlen($num) == 12) { // Caso 593... sin +
        $num = '0' . substr($num, 3);
    }
    
    return $num;
}

// Configurar TCPDF
class MYPDF extends TCPDF {
    protected $grupo_nombre;
    protected $grupo_icono;
    protected $grupo_color;
    
    public function setGrupoInfo($nombre, $icono, $color) {
        $this->grupo_nombre = $nombre;
        $this->grupo_icono = $icono;
        $this->grupo_color = $color;
    }

    public function Header() {
        // Convertir color de grupo a RGB
        $rgb = hex2rgb($this->grupo_color);
        
        // Fondo del Header (Reducido más, a 18mm)
        $headerH = 18;
        $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $this->Rect(0, 0, $this->getPageWidth(), $headerH, 'F');
        
        // Configuración Logo
        $logoPath = 'images/logo_cce.png';
        // Logo cuadrado (reducción proporcional)
        $logoSize = 12; 
        $logoW = $logoSize;
        $logoH = $logoSize;
        $gap = 5;
        
        // Configuración Texto
        $title = 'CCE Certificados';
        $subtitle = 'Estudiantes - ' . $this->grupo_nombre;
        
        // Calcular Anchos de Texto
        $this->SetFont('helvetica', 'B', 14);
        $titleW = $this->GetStringWidth($title);
        
        $this->SetFont('helvetica', '', 9); // Fuente ligeramente reducida para el subtítulo
        $subW = $this->GetStringWidth($subtitle);
        
        $textW = max($titleW, $subW);
        $totalBlockW = $logoW + $gap + $textW;
        
        // Calcular Posición X Inicial para Centrar Todo el Bloque
        $startX = ($this->getPageWidth() - $totalBlockW) / 2;
        
        // Centrado Vertical del Bloque dentro del Header
        $startY = ($headerH - $logoH) / 2;
        
        // Dibujar Logo
        if (file_exists($logoPath)) {
            $this->Image($logoPath, $startX, $startY, $logoW, $logoH, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Dibujar Texto (al lado del logo)
        $this->SetTextColor(255, 255, 255);
        $textX = $startX + $logoW + $gap;
        
        // Título - Ajustado verticalmente para alinearse con la parte superior del logo
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY($textX, $startY - 1.5); 
        $this->Cell($textW, 7, $title, 0, 0, 'L');
        
        // Subtítulo - Debajo del título
        $this->SetFont('helvetica', '', 9);
        $this->SetXY($textX, $startY + 5.5);
        $this->Cell($textW, 5, $subtitle, 0, 0, 'L');
        
        // Restaurar posición y color para contenido (comienzo reducido más a 22mm para pegar la tabla)
        $this->SetY(22);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().' - Generado el '.date('d/m/Y H:i'), 0, 0, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Construir Query con filtros ampliados
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
    // Búsqueda en múltiples campos para coincidir con la web
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

// 1. Agrupar por ID (para consolidar categorías)
$uniq_students = [];
$students_by_cedula = [];

foreach ($rows as $row) {
    $id = $row['id'];
    if (!isset($uniq_students[$id])) {
        $uniq_students[$id] = $row;
        $uniq_students[$id]['categorias'] = [];
        
        // Mapear cédula para búsqueda jerárquica
        if (!empty($row['cedula'])) {
            $students_by_cedula[$row['cedula']] = $id;
        }
    }
    
    // Agregar categoría única
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

// 2. Ordenamiento Jerárquico (Padres -> Hijos)
$map_padre_hijos = []; // cedula_padre => [id_hijo, id_hijo...]

// Identificar hijos cuyo padre ESTÁ en la lista actual
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

// Función recursiva o iterativa para añadir en orden
foreach ($uniq_students as $est) {
    $id = $est['id'];
    
    // Si ya fue procesado (p.ej. añadió como hijo de otro), saltar
    if (in_array($id, $procesados)) continue;
    
    // Verificar si ESTE estudiante es hijo de alguien QUE ESTÁ en la lista
    $es_hijo_en_lista = false;
    if ($est['es_menor'] == 1 && !empty($est['representante_cedula'])) {
        if (isset($students_by_cedula[$est['representante_cedula']])) {
            $es_hijo_en_lista = true;
        }
    }
    
    if ($es_hijo_en_lista) {
        // Es hijo de alguien en la lista; esperar a que aparezca el padre
        continue; 
    }
    
    // Es un "padre" o estudiante independiente
    $lista_final[] = $est;
    $procesados[] = $id;
    
    // Buscar si tiene hijos en el mapa
    if (!empty($est['cedula']) && isset($map_padre_hijos[$est['cedula']])) {
        foreach ($map_padre_hijos[$est['cedula']] as $hijo_id) {
            if (!in_array($hijo_id, $procesados)) {
                // Añadir hijo inmediatamente después
                $hijo = $uniq_students[$hijo_id];
                $hijo['_is_child_node'] = true; // Marcar visualmente
                $lista_final[] = $hijo;
                $procesados[] = $hijo_id;
            }
        }
    }
}

// 3. Generar PDF
// Cambiado a 'P' (Portrait/Vertical)
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('CCE Certificados');
$pdf->SetAuthor('Sistema CCE');
$pdf->SetTitle('Estudiantes - ' . $grupo['nombre']);
$pdf->setGrupoInfo($grupo['nombre'], $grupo['icono'], $grupo['color']);

// Margenes (Reducido margen superior de 30 a 25 para subir la tabla)
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

$style = '
<style>
    table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        font-family: helvetica;
        font-size: 10px;
    }
    th {
        background-color: '.$grupo['color'].';
        color: #ffffff;
        font-weight: bold;
        padding: 5px; /* Padding reducido en header */
        text-align: left;
        border: none;
    }
    td {
        padding: 5px; /* Padding reducido en celdas */
        border-bottom: 1px solid #eeeeee;
        vertical-align: top;
    }
    .text-center { text-align: center; }
    .badge-menor {
        color: #e67e22;
        font-weight: bold;
        font-size: 8px;
    }
    .rep-info {
        color: #7f8c8d;
        font-size: 8px;
        margin-top: 2px;
    }
</style>
';

// Ajuste de anchos de columna:
// Estudiante: Reducido para dar espacio (30% -> 25%) - Suficiente para 2 nombres
// Cédula: Aumentado (12% -> 14%)
// Categoría: Aumentado significativamente (20% -> 23%)
// Contacto: Ajustado (20% -> 20%)
// Edad: Ajustado (13% -> 12%)
// #: Mantenido (5% -> 6%)

$html = $style;
$html .= '<table cellspacing="0" cellpadding="4" border="0">
    <thead>
        <tr>
            <th width="6%" class="text-center">#</th>
            <th width="20%">Estudiante</th>
            <th width="14%">Cédula</th>
            <th width="12%">Edad</th>
            <th width="22%">Contacto</th>
            <th width="26%">Categoría(s)</th>
        </tr>
    </thead>
    <tbody>';

if (empty($lista_final)) {
    $html .= '<tr><td colspan="6" align="center" style="padding: 20px;">No hay estudiantes registrados con los filtros actuales.</td></tr>';
} else {
    $i = 0;
    foreach ($lista_final as $est) {
        $i++;
        $bg = '#ffffff';
        
        // Indentación visual para hijos jerárquicos
        $indentStyle = '';
        $iconPrefix = '';
        
        // Determinar si es padre/representante de alguien en la lista
        $es_representante_activo = !empty($est['cedula']) && isset($map_padre_hijos[$est['cedula']]);

        if (!empty($est['_is_child_node'])) {
            $indentStyle = 'padding-left: 15px; border-left: 3px solid #e67e22;';
            $iconPrefix = '<span style="color: #e67e22; font-weight:bold;">&gt;&gt;</span> ';
            $bg = '#fff8e1'; // Fondo amarillo para menores/hijos
        } else if ($est['es_menor'] == 1) {
            $bg = '#fff8e1'; // Fondo amarillo para menores independientes
        } else if ($es_representante_activo) {
            // Es Representante de alguien en la lista -> Fondo Azul Ligero
            $bg = '#eaf2f8'; 
        } else {
            // Adulto normal (sin hijos en lista) -> Alternar blanco/gris
            if ($i % 2 == 0) $bg = '#fcfcfc'; else $bg = '#ffffff';
        }

        // Edad
        $edadStr = '-';
        if (!empty($est['fecha_nacimiento'])) {
            $dob = new DateTime($est['fecha_nacimiento']);
            $now = new DateTime();
            $diff = $now->diff($dob);
            // Aumentar tamaño de fuente para la edad
            $edadStr = $dob->format('d/m/Y') . '<br><span style="font-size: 9px; font-weight: bold;">(' . $diff->y . ' años)</span>';
        }

        // Información detallada
        $nombreHtml = '<div style="'.$indentStyle.'">';
        $nombreHtml .= $iconPrefix . '<b>' . htmlspecialchars($est['nombre']) . '</b>';
        if ($est['es_menor'] == 1) {
            $nombreHtml .= '<br><span class="badge-menor">MENOR DE EDAD</span>';
            // Mostrar rep si no es un hijo jerárquico (si es jerárquico, su padre está justo arriba)
            if (empty($est['_is_child_node'])) {
                $nombreHtml .= '<div class="rep-info"><b>Rep:</b> ' . htmlspecialchars($est['representante_nombre']) . '</div>';
            }
        }
        $nombreHtml .= '</div>';

        // Contacto (formateado)
        $contactoHtml = '';
        $celular = formatCelular($est['celular']);
        $email = $est['email'];
        
        // Si no tiene propio y es menor, usar rep
        if (empty($celular) && $est['es_menor'] == 1) {
            $celular = formatCelular($est['representante_celular']);
            if($celular) $celular .= ' (Rep)';
        }
        if (empty($email) && $est['es_menor'] == 1) {
            $email = $est['representante_email'];
            if($email) $email .= ' (Rep)';
        }

        // Usar etiquetas de texto en lugar de emojis
        if ($celular) $contactoHtml .= '<b>Cel:</b> ' . $celular . '<br>';
        if ($email) $contactoHtml .= '<b>Email:</b> ' . $email;

        // Categorías
        $catsHtml = '';
        foreach ($est['categorias'] as $cat) {
            $color = $cat['color'] ?: '#999999';
            // Etiqueta de categoría
            $catsHtml .= '<span style="background-color: '.$color.'; color: white;">&nbsp;' . htmlspecialchars($cat['nombre']) . '&nbsp;</span><br>';
            // Período
            if ($cat['periodo']) {
                $catsHtml .= '<span style="color: #555555; font-size: 9px;">Período: ' . htmlspecialchars($cat['periodo']) . '</span><br>';
            }
        }

        $html .= '<tr style="background-color: '.$bg.';">
            <td width="6%" align="center">'.$i.'</td>
            <td width="20%">'.$nombreHtml.'</td>
            <td width="14%">'.($est['cedula'] ?: '-').'</td>
            <td width="12%">'.$edadStr.'</td>
            <td width="22%">'.$contactoHtml.'</td>
            <td width="26%">'.$catsHtml.'</td>
        </tr>';
    }
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(5);

// --- Calcular Estadísticas ---
$totalEstudiantes = count($lista_final);
$totalMenores = 0;
$statsCategorias = [];
$statsPeriodos = [];

foreach ($lista_final as $est) {
    if ($est['es_menor'] == 1) {
        $totalMenores++;
    }
    
    // Contar Categorías y Periodos
    // Nota: Un estudiante puede tener múltiples
    if (isset($est['categorias']) && is_array($est['categorias'])) {
        foreach ($est['categorias'] as $cat) {
            $catName = $cat['nombre'];
            if (!isset($statsCategorias[$catName])) $statsCategorias[$catName] = 0;
            $statsCategorias[$catName]++;
            
            $perName = $cat['periodo'];
            if ($perName) {
                if (!isset($statsPeriodos[$perName])) $statsPeriodos[$perName] = 0;
                $statsPeriodos[$perName]++;
            }
        }
    }
}

// --- Generar HTML de Badges ---
$badgesHtml = '<div style="text-align: right; font-family: helvetica; font-size: 8px;">';

// Total
$badgesHtml .= '<span style="background-color: #3f3f3f; color: white; padding: 3px 8px; border-radius: 4px; margin-right: 5px;"><b>Total:</b> ' . $totalEstudiantes . '</span> &nbsp; ';

// Menores
$badgesHtml .= '<span style="background-color: #e67e22; color: white; padding: 3px 8px; border-radius: 4px; margin-right: 5px;"><b>Menores:</b> ' . $totalMenores . '</span> &nbsp; ';

// Separador
$badgesHtml .= '<br><br>';

// Categorías
$badgesHtml .= '<span style="color: #7f8c8d;">Categorías:</span> &nbsp; ';
foreach ($statsCategorias as $cat => $count) {
    // Usamos un color aleatorio suave o fijo para badges de estadistica
    $badgesHtml .= '<span style="background-color: #f1f2f6; color: #2c3e50; border: 1px solid #dcdde1; padding: 2px 6px; border-radius: 4px; margin-right: 4px;">' . htmlspecialchars($cat) . ': <b>' . $count . '</b></span> &nbsp; ';
}

// Separador
$badgesHtml .= '<br><br>';

// Periodos
$badgesHtml .= '<span style="color: #7f8c8d;">Períodos:</span> &nbsp; ';
foreach ($statsPeriodos as $per => $count) {
    $badgesHtml .= '<span style="background-color: #e8f0fe; color: #1967d2; border: 1px solid #d2e3fc; padding: 2px 6px; border-radius: 4px; margin-right: 4px;">' . htmlspecialchars($per) . ': <b>' . $count . '</b></span> &nbsp; ';
}

$badgesHtml .= '</div>';

$pdf->writeHTML($badgesHtml, true, false, true, false, '');

$pdf->Output('Lista_Estudiantes_' . preg_replace('/[^a-zA-Z0-9]/', '_', $grupo['nombre']) . '.pdf', 'I');
