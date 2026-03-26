<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$pdo = getConnection();

// Obtener fuentes de la base de datos
$fontsList = [];
try {
    $stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE activo = 1 ORDER BY categoria ASC, es_sistema DESC, nombre ASC");
    $fontsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla no existe, usar fuentes predeterminadas
    $fontsList = [
        ['nombre' => 'Roboto', 'nombre_archivo' => 'Roboto-Regular', 'archivo' => 'Roboto-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Open Sans', 'nombre_archivo' => 'OpenSans', 'archivo' => 'OpenSans.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Lato', 'nombre_archivo' => 'Lato-Regular', 'archivo' => 'Lato-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Montserrat', 'nombre_archivo' => 'Montserrat', 'archivo' => 'Montserrat.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Poppins', 'nombre_archivo' => 'Poppins-Regular', 'archivo' => 'Poppins-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Playfair Display', 'nombre_archivo' => 'PlayfairDisplay', 'archivo' => 'PlayfairDisplay.ttf', 'tipo' => 'ttf', 'categoria' => 'serif'],
        ['nombre' => 'Motterdam', 'nombre_archivo' => 'Motterdam', 'archivo' => 'Motterdam.ttf', 'tipo' => 'ttf', 'categoria' => 'handwriting'],
    ];
}

// Map for category labels
$categoryLabels = [
    'sans-serif' => 'Sans Serif',
    'serif' => 'Serif',
    'display' => 'Display',
    'handwriting' => 'Manuscrita',
    'monospace' => 'Monospace'
];

// Obtener categoría si se especifica ID
$categoria_id = $_GET['id'] ?? null;
$categoria = [];
$grupo = [];

if ($categoria_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, g.id as grupo_id, g.nombre as grupo_nombre, g.icono as grupo_icono, g.color as grupo_color
            FROM categorias c
            INNER JOIN grupos g ON c.grupo_id = g.id
            WHERE c.id = ? AND c.activo = 1
        ");
        $stmt->execute([$categoria_id]);
        $categoriaData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($categoriaData) {
            $categoria = $categoriaData;
            $grupo = [
                'id' => $categoriaData['grupo_id'],
                'nombre' => $categoriaData['grupo_nombre']
            ];
        }
    } catch (PDOException $e) {
        error_log("Error fetching category: " . $e->getMessage());
    }
}

// Función para formatear fecha con meses en español (usada en la vista)
function formatearFechaEjemplo($formato) {
    $mesesEspanol = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    
    $fecha = date($formato);
    return str_replace(array_keys($mesesEspanol), array_values($mesesEspanol), $fecha);
}

// Configuración de rutas
$basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$basePath = str_replace('\\', '/', $basePath);

if (strpos($basePath, '/public') !== false) {
    $projectRoot = str_replace('/public', '', $basePath);
    $assetsPath = $basePath . '/assets';
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
} else {
    $projectRoot = $basePath;
    $assetsPath = $basePath . '/public/assets';
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
}

// Renderizar la vista
require '../../app/Views/categorias/config_new.php';
?>
