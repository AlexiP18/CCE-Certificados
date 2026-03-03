<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['id'] ?? 0;

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

// Obtener el año seleccionado (por defecto el año actual)
$anio_seleccionado = $_GET['anio'] ?? date('Y');

// Obtener los años disponibles con períodos para este grupo
$stmt = $pdo->prepare("
    SELECT DISTINCT YEAR(p.fecha_inicio) as anio
    FROM periodos p
    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
    WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
    ORDER BY anio DESC
");
$stmt->execute([$grupo_id]);
$anios_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Determinar el año seleccionado
$periodo_seleccionado_id = isset($_GET['periodo_id']) ? (int)$_GET['periodo_id'] : null;
$anio_seleccionado = $_GET['anio'] ?? null;

// Si viene un periodo_id, forzar el año de ese periodo
if ($periodo_seleccionado_id) {
    $stmt = $pdo->prepare("SELECT YEAR(fecha_inicio) FROM periodos WHERE id = ?");
    $stmt->execute([$periodo_seleccionado_id]);
    $anio_periodo = $stmt->fetchColumn();
    if ($anio_periodo) {
        $anio_seleccionado = $anio_periodo;
    }
}

// Si no hay año seleccionado (o no se pudo obtener del periodo), usar el actual o el más reciente
if (!$anio_seleccionado) {
    $anio_seleccionado = date('Y');
}

// Validar que el año tenga datos, si no, usar el primero disponible (si hay disponibles)
if (!empty($anios_disponibles) && !in_array($anio_seleccionado, $anios_disponibles)) {
    // Si el año seleccionado no está en la lista (y no viene forzado por un periodo válido), usar el más reciente
    if (!$periodo_seleccionado_id) {
        $anio_seleccionado = $anios_disponibles[0];
    }
}

// Obtener los períodos del año seleccionado para este grupo
$stmt = $pdo->prepare("
    SELECT p.* FROM periodos p
    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
    WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
    AND YEAR(p.fecha_inicio) = ?
    ORDER BY (DATEDIFF(p.fecha_fin, p.fecha_inicio) > 35) ASC, p.fecha_inicio ASC
");
$stmt->execute([$grupo_id, $anio_seleccionado]);
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Identificar el periodo actual según la fecha
$fecha_hoy = date('Y-m-d');
$periodo_actual_id = null;
foreach ($periodos as $periodo) {
    if ($fecha_hoy >= $periodo['fecha_inicio'] && $fecha_hoy <= $periodo['fecha_fin']) {
        $periodo_actual_id = $periodo['id'];
        break;
    }
}

// Obtener categorías por cada período
$categorias_por_periodo = [];
foreach ($periodos as $periodo) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT cert.id) as total_certificados,
               COUNT(DISTINCT ce.estudiante_id) as total_estudiantes
        FROM categorias c
        INNER JOIN categoria_periodos cp ON c.id = cp.categoria_id AND cp.periodo_id = ? AND cp.activo = 1
        LEFT JOIN certificados cert ON c.id = cert.categoria_id
        LEFT JOIN categoria_estudiantes ce ON c.id = ce.categoria_id AND ce.estado = 'activo' AND ce.periodo_id = ?
        WHERE c.grupo_id = ? AND c.activo = 1
        GROUP BY c.id
        ORDER BY c.nombre ASC
    ");
    $stmt->execute([$periodo['id'], $periodo['id'], $grupo_id]);
    $categorias_por_periodo[$periodo['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar la vista refactorizada
require_once '../../app/Views/grupos/detalle.php';
