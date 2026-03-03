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

// Obtener grupo si se especifica ID
$grupo_id = $_GET['id'] ?? null;
$grupo = [];

if ($grupo_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
        $stmt->execute([$grupo_id]);
        $grupoData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($grupoData) {
            $grupo = $grupoData;
        }
    } catch (PDOException $e) {
        error_log("Error fetching group: " . $e->getMessage());
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
// Si estamos en Windows, corregir separadores
$basePath = str_replace('\\', '/', $basePath);

// Determinar la raíz del proyecto
// Si public está en el path, la raíz es el padre
    // Si public está en el path, la raíz es el padre
if (strpos($basePath, '/public') !== false) {
    $projectRoot = str_replace('/public', '', $basePath);
    $assetsPath = $basePath . '/assets'; // CHANGE: Point to public/assets
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
} else {
    // Si no (ej. rewrite), asumimos que assets está en /assets y css en /public/css o /css
    $projectRoot = $basePath;
    $assetsPath = $basePath . '/public/assets'; // CHANGE: Point to public/assets
    $cssPath = $basePath . '/css'; // Ajustar según estructura si rewrite oculta public
    $jsPath = $basePath . '/js';
}

// Renderizar la vista
require '../../app/Views/grupos/config.php';
?>
