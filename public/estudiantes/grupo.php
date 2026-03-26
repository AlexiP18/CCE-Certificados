<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['id'] ?? $_GET['grupo'] ?? 0;

if (empty($grupo_id)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$pdo = getConnection();

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Obtener categorías del grupo
$stmt = $pdo->prepare("SELECT id, nombre, icono FROM categorias WHERE grupo_id = ? AND activo = 1 ORDER BY nombre");
$stmt->execute([$grupo_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener períodos del grupo
$stmt = $pdo->prepare("
    SELECT DISTINCT p.id, p.nombre, p.fecha_inicio, p.fecha_fin
    FROM periodos p
    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
    WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute([$grupo_id]);
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar estudiantes totales
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ce.estudiante_id) as total
    FROM categoria_estudiantes ce
    INNER JOIN categorias c ON ce.categoria_id = c.id
    WHERE c.grupo_id = ?
");
$stmt->execute([$grupo_id]);
$totalEstudiantes = $stmt->fetchColumn();

// Configuración de rutas
$basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$basePath = str_replace('\\', '/', $basePath);

// Determinar rutas de assets
if (strpos($basePath, '/public') !== false) {
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
} else {
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
}

// Cargar la vista
require_once '../../app/Views/estudiantes/grupo_gestion.php';
?>
