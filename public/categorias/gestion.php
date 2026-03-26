<?php
/**
 * Gestión de Categoría - Estudiantes y Certificados
 * Página unificada para gestionar estudiantes y certificados de una categoría específica
 */
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();
$esAdmin = (isset($usuario['rol_nombre']) && in_array($usuario['rol_nombre'], ['admin', 'superadmin', 'administrador', 'Administrador']))
         || (isset($usuario['es_superadmin']) && $usuario['es_superadmin'] == 1);

$categoria_id = $_GET['categoria_id'] ?? $_GET['categoria'] ?? 0;
$periodo_id = $_GET['periodo_id'] ?? $_GET['periodo'] ?? null;

if (empty($categoria_id)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$pdo = getConnection();

// Obtener información de la categoría, grupo e instructor asignado
$stmt = $pdo->prepare("
    SELECT c.*, 
           g.id as grupo_id, g.nombre as grupo_nombre, g.color as grupo_color, g.icono as grupo_icono,
           iu.id as instructor_id, iu.nombre_completo as instructor_nombre, iu.cedula as instructor_cedula
    FROM categorias c
    JOIN grupos g ON c.grupo_id = g.id
    LEFT JOIN (
        SELECT ic.categoria_id, MIN(ic.usuario_id) as usuario_id
        FROM instructor_categorias ic
        GROUP BY ic.categoria_id
    ) icx ON c.id = icx.categoria_id
    LEFT JOIN usuarios iu ON icx.usuario_id = iu.id
    WHERE c.id = ? AND c.activo = 1
");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: ../dashboard/index.php');
    exit;
}

$grupo_id = $categoria['grupo_id'];

// Obtener períodos de la categoría
$stmt = $pdo->prepare("
    SELECT DISTINCT p.* 
    FROM periodos p
    INNER JOIN categoria_periodos cp ON p.id = cp.periodo_id
    WHERE cp.categoria_id = ? AND p.activo = 1 AND cp.activo = 1
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute([$categoria_id]);
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay periodo seleccionado, usar el primero
if (empty($periodo_id) && !empty($periodos)) {
    $periodo_id = $periodos[0]['id'];
}

// Obtener período actual
$periodo_actual = null;
foreach ($periodos as $p) {
    if ($p['id'] == $periodo_id) {
        $periodo_actual = $p;
        break;
    }
}

$color_principal = $categoria['grupo_color'] ?? '#3498db';

// Cargar la vista
require_once '../../app/Views/estudiantes/gestion.php';
