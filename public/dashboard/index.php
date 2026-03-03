<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();
$esInstructor = Auth::isInstructor();

// Verificar si existe la tabla de grupo_periodos para evitar errores si no se ha creado aún
$tableExists = $pdo->query("SHOW TABLES LIKE 'grupo_periodos'")->rowCount() > 0;

$periodosJoin = "";
$periodosSelect = "";
if ($tableExists) {
    $periodosSelect = ", GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ', ') as periodo_nombre";
    $periodosJoin = "
        LEFT JOIN grupo_periodos gp ON g.id = gp.grupo_id AND gp.activo = 1
        LEFT JOIN periodos p ON gp.periodo_id = p.id
    ";
} else {
    $periodosSelect = ", '' as periodo_nombre";
}

// Obtener todos los grupos activos con conteo de categorías y certificados
// Si es instructor, solo mostrar sus grupos asignados
if ($esInstructor) {
    $gruposIds = Auth::getGruposAsignados();
    if (!empty($gruposIds)) {
        $placeholders = implode(',', array_fill(0, count($gruposIds), '?'));
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   COUNT(DISTINCT c.id) as total_categorias,
                   COUNT(DISTINCT cert.id) as total_certificados
                   $periodosSelect
            FROM grupos g
            LEFT JOIN categorias c ON g.id = c.grupo_id AND c.activo = 1
            LEFT JOIN certificados cert ON g.id = cert.grupo_id
            $periodosJoin
            WHERE g.activo = 1 AND g.id IN ($placeholders)
            GROUP BY g.id
            ORDER BY g.fecha_creacion DESC
        ");
        $stmt->execute($gruposIds);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $grupos = [];
    }
} else {
    $stmt = $pdo->query("
        SELECT g.*, 
               COUNT(DISTINCT c.id) as total_categorias,
               COUNT(DISTINCT cert.id) as total_certificados
               $periodosSelect
        FROM grupos g
        LEFT JOIN categorias c ON g.id = c.grupo_id AND c.activo = 1
        LEFT JOIN certificados cert ON g.id = cert.grupo_id
        $periodosJoin
        WHERE g.activo = 1
        GROUP BY g.id
        ORDER BY g.fecha_creacion DESC
    ");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener todos los períodos activos (agrupados por nombre y fechas para evitar duplicados)
// EXCLUIR periodos que sean años completos (01/01 a 31/12 del mismo año con nombre "Año XXXX")
$stmt = $pdo->query("
    SELECT MIN(id) as id, nombre, fecha_inicio, fecha_fin, MIN(color) as color, 
           MIN(descripcion) as descripcion, COUNT(*) as grupos_count
    FROM periodos 
    WHERE activo = 1 
    AND NOT (
        nombre LIKE 'Año %' 
        AND DATE_FORMAT(fecha_inicio, '%m-%d') = '01-01'
        AND DATE_FORMAT(fecha_fin, '%m-%d') = '12-31'
        AND YEAR(fecha_inicio) = YEAR(fecha_fin)
    )
    GROUP BY nombre, fecha_inicio, fecha_fin
    ORDER BY fecha_inicio DESC
");
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// El año solo es un filtro, NO se crea como periodo automáticamente
// Los periodos deben ser creados manualmente por el usuario
$anioActual = date('Y');
$hayPeriodosReales = count($periodos) > 0;

// Configuración de rutas — subir un nivel porque estamos en public/dashboard/
$basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
// Si estamos en Windows, corregir separadores
$basePath = str_replace('\\', '/', $basePath);

// Determinar la raíz del proyecto
// Si public está en el path, la raíz es el padre
if (strpos($basePath, '/public') !== false) {
    $projectRoot = str_replace('/public', '', $basePath);
    $assetsPath = $projectRoot . '/assets';
    $cssPath = $basePath . '/css';
    $jsPath = $basePath . '/js';
} else {
    // Si no (ej. rewrite), asumimos que assets está en /assets y css en /public/css o /css
    $projectRoot = $basePath;
    $assetsPath = $basePath . '/assets';
    $cssPath = $basePath . '/css'; // Ajustar según estructura si rewrite oculta public
    $jsPath = $basePath . '/js';
}

// Renderizar la vista
require '../../app/Views/dashboard/index.php';
?>