<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['id'] ?? 0;

if (empty($grupo_id)) {
    header('Location: index.php');
    exit;
}

$pdo = getConnection();

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: index.php');
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

// Si no hay años, usar el año actual como filtro por defecto
if (empty($anios_disponibles)) {
    $anio_seleccionado = date('Y');
} else {
    // Asegurar que el año seleccionado está en la lista
    if (!in_array($anio_seleccionado, $anios_disponibles)) {
        $anio_seleccionado = $anios_disponibles[0];
    }
}

// Obtener los períodos del año seleccionado para este grupo
$stmt = $pdo->prepare("
    SELECT p.* FROM periodos p
    INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
    WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
    AND YEAR(p.fecha_inicio) = ?
    ORDER BY p.fecha_inicio ASC
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

// Función para generar nombre de período automáticamente
function generarNombrePeriodo($fecha_inicio, $fecha_fin) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    
    $mes_inicio = (int)$inicio->format('n');
    $mes_fin = (int)$fin->format('n');
    $anio_inicio = $inicio->format('Y');
    $anio_fin = $fin->format('Y');
    $dia_inicio = (int)$inicio->format('j');
    $dia_fin = (int)$fin->format('j');
    
    // Si es el mismo mes
    if ($mes_inicio === $mes_fin && $anio_inicio === $anio_fin) {
        return $meses[$mes_inicio] . " $dia_inicio-$dia_fin, $anio_inicio";
    }
    
    // Si son meses diferentes del mismo año
    if ($anio_inicio === $anio_fin) {
        return $meses[$mes_inicio] . " - " . $meses[$mes_fin] . " $anio_inicio";
    }
    
    // Si son años diferentes
    return $meses[$mes_inicio] . " $anio_inicio - " . $meses[$mes_fin] . " $anio_fin";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($grupo['nombre']) ?> - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        .breadcrumb-nav {
            margin-bottom: 20px;
        }
        
        .breadcrumb-link {
            color: #2c3e50;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            padding: 8px 0;
        }
        
        .breadcrumb-link:hover {
            color: #3498db;
            gap: 8px;
        }
        
        .breadcrumb-link i {
            font-size: 13px;
        }
        
        .grupo-header {
            background: linear-gradient(135deg, <?= $grupo['color'] ?>dd 0%, <?= $grupo['color'] ?> 100%);
            color: white;
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .grupo-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
        }
        
        .grupo-header-left {
            display: flex;
            align-items: center;
            gap: 25px;
            flex: 1;
            min-width: 0;
        }
        
        .grupo-icon-large {
            font-size: 70px;
            background: rgba(255,255,255,0.2);
            width: 100px;
            height: 100px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        
        .grupo-info {
            flex: 1;
            min-width: 0;
        }
        
        .grupo-info h1 {
            margin: 0 0 8px 0;
            font-size: 32px;
        }
        
        .grupo-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 15px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .btn-header {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.2s ease;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            backdrop-filter: blur(10px);
            white-space: nowrap;
            cursor: pointer;
        }
        
        .btn-header:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-header-icon {
            padding: 10px;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .btn-header-icon i {
            font-size: 20px;
        }
        
        .categorias-container {
            margin-top: 30px;
        }
        
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title h2 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        
        /* ============ Tabs de Períodos ============ */
        .periodo-tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        
        .periodo-tabs-nav {
            display: flex;
            flex-direction: row;
            gap: 0;
            border-bottom: 3px solid #e9ecef;
            background: #fafbfc;
            overflow-x: auto;
            overflow-y: hidden;
        }
        
        .periodo-tab {
            padding: 16px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            background: white;
            border-right: 1px solid #e9ecef;
            flex-shrink: 0;
            min-width: fit-content;
            position: relative;
        }
        
        .periodo-tab:hover {
            background: #f8f9fa;
            border-bottom-color: #dee2e6;
        }
        
        .periodo-tab.active {
            background: linear-gradient(180deg, #f8f3ff 0%, #fff 100%);
            border-bottom-color: #9b59b6;
        }
        
        /* Periodo vigente (en curso) */
        .periodo-tab.periodo-vigente {
            background: linear-gradient(180deg, #e8f5e9 0%, #fff 100%);
            border-left: 3px solid #4caf50;
        }
        
        .periodo-tab.periodo-vigente.active {
            background: linear-gradient(180deg, #e8f5e9 0%, #f8f3ff 50%, #fff 100%);
            border-bottom-color: #9b59b6;
        }
        
        .badge-vigente {
            position: absolute;
            top: 4px;
            right: 8px;
            background: #4caf50;
            color: white;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }
        
        .badge-vigente i {
            font-size: 6px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .tab-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .tab-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .tab-title {
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
        }
        
        .periodo-tab.active .tab-title {
            color: #9b59b6;
        }
        
        .tab-subtitle {
            font-size: 12px;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        
        /* Ocultar info extra en tabs inactivos */
        .periodo-tab:not(.active) .tab-stats {
            display: none;
        }
        
        .periodo-tab:not(.active) .tab-actions {
            display: none;
        }
        
        .tab-stats {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: 12px;
        }
        
        .tab-badge {
            font-size: 11px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #f1f3f4;
            border-radius: 10px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .periodo-tab.active .tab-badge {
            background: #e8d9f3;
            color: #9b59b6;
        }
        
        .tab-badge i {
            font-size: 10px;
        }
        
        .tab-actions {
            display: flex;
            gap: 5px;
            margin-left: 12px;
        }
        
        .btn-tab-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: #f8f9fa;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-tab-action:hover {
            background: #e9ecef;
            color: #3498db;
            transform: scale(1.05);
        }
        
        .btn-tab-action.btn-danger:hover {
            background: #fee;
            color: #e74c3c;
        }
        
        .periodo-tab-content {
            display: none;
            padding: 25px;
            background: #fafbfc;
            animation: fadeIn 0.3s ease;
        }
        
        .periodo-tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ============ Modal de Período ============ */
        .periodo-modal-body {
            padding: 25px;
        }
        
        .periodo-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .periodo-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .periodo-form-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .periodo-form-group input {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .periodo-form-group input:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.1);
        }
        
        .periodo-preview {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 15px 20px;
            margin-top: 10px;
        }
        
        .periodo-preview-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .periodo-preview-nombre {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .categorias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .categoria-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--categoria-color);
            display: flex;
            flex-direction: column;
            height: 400px;
        }
        
        .categoria-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .categoria-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .categoria-icon {
            font-size: 42px;
            background: linear-gradient(135deg, var(--categoria-color)cc 0%, var(--categoria-color) 100%);
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 8px;
        }
        
        .categoria-info {
            flex: 1;
            min-width: 0;
        }
        
        .categoria-info h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 19px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .categoria-descripcion {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 15px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 42px;
        }
        
        .categoria-stats {
            display: flex;
            gap: 20px;
            padding: 12px 0;
            border-top: 1px solid #ecf0f1;
            border-bottom: 1px solid #ecf0f1;
            margin: 0 0 15px 0;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .stat-number {
            font-weight: 600;
            color: var(--categoria-color);
            font-size: 18px;
        }
        
        .categoria-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: auto;
        }
        
        .btn-categoria {
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--categoria-color);
            color: white;
        }
        
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: scale(1.02);
        }
        
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
        }
        
        .btn-estudiantes {
            background: #9b59b6;
            color: white;
        }
        
        .btn-estudiantes:hover {
            background: #8e44ad;
            transform: scale(1.02);
        }
        
        .btn-actions {
            display: flex;
            gap: 8px;
            grid-column: 1 / -1;
        }
        
        .btn-icon {
            padding: 8px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 17px;
            transition: all 0.2s;
            flex: 1;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .add-categoria-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            min-height: 280px;
            border: 3px dashed rgba(255,255,255,0.3);
        }
        
        .add-categoria-card:hover {
            border-color: rgba(255,255,255,0.6);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .add-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .add-text {
            font-size: 18px;
            font-weight: 500;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        /* Tabs para modal de categoría */
        .categoria-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .categoria-tab {
            flex: 1;
            padding: 12px 15px;
            border: none;
            background: #f8f9fa;
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 10px 10px 0 0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .categoria-tab:hover {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .categoria-tab.active {
            background: #667eea;
            color: white;
        }
        
        .categoria-tab-content {
            display: none;
        }
        
        .categoria-tab-content.active {
            display: block;
        }
        
        .categorias-lista-copiar {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ecf0f1;
            border-radius: 10px;
            padding: 10px;
        }
        
        .categoria-copiar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .categoria-copiar-item:hover {
            background: #ecf0f1;
        }
        
        .categoria-copiar-item.selected {
            background: #e8f4fc;
            border: 2px solid #667eea;
        }
        
        .categoria-copiar-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .categoria-copiar-icono {
            font-size: 24px;
        }
        
        .categoria-copiar-info {
            flex: 1;
        }
        
        .categoria-copiar-nombre {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .categoria-copiar-desc {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        .categoria-copiar-estudiantes {
            font-size: 12px;
            color: #667eea;
            font-weight: 500;
        }
        
        .modal-large {
            max-width: 1100px;
            width: 95%;
            padding: 25px 30px;
        }
        
        .modal-body-estudiantes {
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }
        
        .estudiantes-actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .estudiantes-count-badge {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .estudiantes-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-action-estudiantes {
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .btn-agregar {
            background: #27ae60;
            color: white;
        }
        
        .btn-agregar:hover {
            background: #219a52;
        }
        
        .btn-archivo {
            background: #3498db;
            color: white;
        }
        
        .btn-archivo:hover {
            background: #2980b9;
        }
        
        .agregar-estudiante-section,
        .carga-archivo-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-header h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .btn-close-sm {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #95a5a6;
        }
        
        .input-busqueda {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .input-busqueda:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .resultados-busqueda {
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .resultado-estudiante {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .resultado-estudiante:hover {
            background: #e8f5e9;
        }
        
        .resultado-estudiante.ya-matriculado {
            background: #f5f5f5;
            cursor: default;
            opacity: 0.7;
        }
        
        .resultado-info strong {
            display: block;
            color: #2c3e50;
        }
        
        .resultado-info small {
            color: #95a5a6;
        }
        
        .resultado-action {
            color: #27ae60;
            font-size: 18px;
        }
        
        .estudiantes-list-container {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .estudiantes-list-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .estudiantes-list-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .estudiantes-list-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .estudiantes-list-table tr:hover {
            background: #f8f9fa;
        }
        
        .estado-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .estado-activo {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .estado-completado {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .estado-inactivo {
            background: #ffebee;
            color: #e74c3c;
        }
        
        .btn-action-sm {
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin: 0 2px;
        }
        
        .btn-edit-sm {
            background: #3498db;
            color: white;
        }
        
        .btn-delete-sm {
            background: #e74c3c;
            color: white;
        }
        
        .btn-action-sm:hover {
            transform: scale(1.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group-sm {
            margin-bottom: 0;
        }
        
        .form-group-sm label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group-sm input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group-sm input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group-sm input.input-error {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .form-group-sm input.input-valid {
            border-color: #27ae60;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 11px;
            margin-top: 4px;
            display: block;
        }
        
        .celular-input-wrapper {
            display: flex;
            align-items: center;
        }
        
        .celular-prefix-sm {
            background: #ecf0f1;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 8px 0 0 8px;
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .celular-input-wrapper input {
            border-radius: 0 8px 8px 0 !important;
            flex: 1;
        }
        
        /* Estilos para la nueva estructura de búsqueda/agregar */
        .busqueda-agregar-row {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .busqueda-container {
            flex: 1;
            min-width: 250px;
        }
        
        .busqueda-container label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .separador-o {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            color: #95a5a6;
            font-size: 13px;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .btn-nuevo-estudiante {
            padding: 12px 20px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            margin-top: 20px;
            transition: all 0.2s;
        }
        
        .btn-nuevo-estudiante:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .btn-nuevo-estudiante.active {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .section-header-nuevo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-header-nuevo h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 15px;
        }
        
        .btn-plantilla {
            background: linear-gradient(135deg, #17a2b8, #138496) !important;
        }
        
        .btn-plantilla:hover {
            background: linear-gradient(135deg, #138496, #117a8b) !important;
        }
        
        /* Estilos para menor de edad y representante legal */
        .menor-edad-toggle {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ffcc80;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            color: #e65100;
            font-weight: 500;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #e65100;
        }
        
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-section-title.representante {
            border-bottom-color: #9b59b6;
            color: #8e44ad;
            margin-top: 15px;
        }
        
        .menores-info {
            background: #e8f4fd;
            padding: 10px 12px;
            border-radius: 8px;
            border-left: 3px solid #3498db;
        }
        
        .menores-info label {
            color: #2980b9 !important;
            font-weight: 600;
        }
        
        .menores-info p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #5a6a7a;
            line-height: 1.4;
        }
        
        #datosRepresentante {
            background: linear-gradient(135deg, #f3e5f5, #e1bee7);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #ce93d8;
        }
        
        .badge-menor {
            display: inline-block;
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        /* Badge para representante */
        .badge-representante {
            display: inline-block;
            background: linear-gradient(135deg, #e8daef, #d2b4de);
            color: #6c3483;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
            vertical-align: middle;
            font-weight: 600;
        }
        
        .badge-representante-label {
            display: inline-block;
            background: #8e44ad;
            color: white;
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        /* Fila del representante */
        .fila-representante {
            background: linear-gradient(135deg, #f5eef8, #ebdef0) !important;
            border-left: 4px solid #8e44ad;
        }
        
        .fila-representante:hover {
            background: linear-gradient(135deg, #ebdef0, #e8daef) !important;
        }
        
        /* Botón expandir/contraer */
        .btn-expand-rep {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #8e44ad;
            padding: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-expand-rep:hover {
            color: #6c3483;
            transform: scale(1.2);
        }
        
        .btn-expand-rep i {
            transition: transform 0.3s ease;
        }
        
        /* Fila de menor (sub-fila) */
        .fila-menor {
            background: #faf5ff !important;
            border-left: 4px solid #d5b8e8;
        }
        
        .fila-menor:hover {
            background: #f3e5f5 !important;
        }

        /* Enlaces de contacto (WhatsApp y Email) */
        .link-whatsapp {
            color: #25D366;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .link-whatsapp:hover {
            color: #128C7E;
            text-decoration: underline;
        }
        
        .link-whatsapp i {
            margin-right: 4px;
        }
        
        .link-email {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            word-break: break-all;
        }
        
        .link-email:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .link-email i {
            margin-right: 4px;
        }
        
        /* Estilos para lista de menores */
        .form-section-title.menores-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom-color: #e67e22;
            color: #d35400;
            margin-top: 20px;
        }
        
        .btn-add-menor {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-add-menor:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
        }
        
        .menor-item {
            background: white;
            border: 2px solid #e67e22;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .menor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .menor-numero {
            font-weight: 600;
            color: #e67e22;
            font-size: 13px;
        }
        
        .btn-remove-menor {
            background: #e74c3c;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .btn-remove-menor:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        #listaMenores {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .filtros-estudiantes {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .btn-filtro-representantes {
            padding: 10px 18px;
            border: 2px solid #8e44ad;
            background: white;
            color: #8e44ad;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-filtro-representantes:hover {
            background: #f5eef8;
        }
        
        .btn-filtro-representantes.active {
            background: #8e44ad;
            color: white;
        }
        
        .filtro-stats {
            margin-left: auto;
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .filtro-stats strong {
            color: #2c3e50;
        }
        
        .acciones-masivas {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            margin-bottom: 15px;
            color: white;
        }
        
        .acciones-masivas-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .acciones-masivas-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-masivo {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-masivo.btn-activar {
            background: #27ae60;
            color: white;
        }
        
        .btn-masivo.btn-activar:hover {
            background: #219a52;
        }
        
        .btn-masivo.btn-desactivar {
            background: #f39c12;
            color: white;
        }
        
        .btn-masivo.btn-desactivar:hover {
            background: #d68910;
        }
        
        .btn-masivo.btn-eliminar {
            background: #e74c3c;
            color: white;
        }
        
        .btn-masivo.btn-eliminar:hover {
            background: #c0392b;
        }
        
        .btn-masivo.btn-cancelar {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-masivo.btn-cancelar:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .estudiantes-list-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .row-selected {
            background: #f0f3ff !important;
        }
        
        .form-actions-sm {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .btn-cancel-sm {
            padding: 10px 20px;
            border: none;
            background: #ecf0f1;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-submit-sm {
            padding: 10px 20px;
            border: none;
            background: #27ae60;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-submit-sm:hover {
            background: #219a52;
        }
        
        .info-archivo {
            background: #e3f2fd;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #1565c0;
        }
        
        .info-archivo p {
            margin: 5px 0;
        }
        
        .file-input-wrapper {
            position: relative;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px dashed #bdc3c7;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            color: #7f8c8d;
        }
        
        .file-label:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .mini-table th, .mini-table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .mini-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .btn-submit {
            padding: 12px 24px;
            border: none;
            background: #27ae60;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn-submit:hover {
            background: #219a52;
        }
        
        .btn-link {
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        /* Header especial para modal de estudiantes */
        #estudiantesModalHeader {
            margin: -30px -30px 25px -30px;
            padding: 20px 30px;
            border-radius: 12px 12px 0 0;
        }
        
        #estudiantesModalHeader h2 {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        #estudiantesModalHeader .btn-close {
            color: white !important;
            opacity: 0.9;
            font-size: 32px;
        }
        
        #estudiantesModalHeader .btn-close:hover {
            opacity: 1;
            color: white !important;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .btn-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.2s;
        }
        
        .btn-close:hover {
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group select {
            background-color: white;
            cursor: pointer;
            appearance: menulist;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .icon-option,
        .icon-option-grupo {
            font-size: 32px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        
        .icon-option:hover,
        .icon-option.selected {
            border-color: #3498db;
            background: #e8f4f8;
            transform: scale(1.1);
        }
        
        .icon-option-grupo:hover,
        .icon-option-grupo.selected {
            border-color: #9b59b6;
            background: #f3e5f5;
            transform: scale(1.1);
        }
        
        .icon-option-custom {
            grid-column: span 2;
            padding: 12px;
            border: 2px dashed #3498db;
            border-radius: 10px;
            cursor: pointer;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            font-size: 14px;
            color: #3498db;
            font-weight: 500;
        }
        
        .icon-option-custom:hover {
            background: #e8f4f8;
            transform: scale(1.05);
        }
        
        .icon-option-custom i {
            font-size: 18px;
        }
        
        .icon-option-custom-grupo {
            grid-column: span 2;
            padding: 12px;
            border: 2px dashed #9b59b6;
            border-radius: 10px;
            cursor: pointer;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            font-size: 14px;
            color: #9b59b6;
            font-weight: 500;
        }
        
        .icon-option-custom-grupo:hover {
            background: #f3e5f5;
            transform: scale(1.05);
        }
        
        .icon-option-custom-grupo i {
            font-size: 18px;
        }
        
        .icon-option-custom-grupo.selected {
            background: #9b59b6;
            color: white;
            border-color: #8e44ad;
        }
        
        #emojiPickerContainerCat,
        #emojiPickerContainerGrupo {
            background: white;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        emoji-picker {
            width: 100%;
            height: 350px;
            --border-radius: 10px;
        }
        
        .color-preview {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .color-presets {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }
        
        .color-preset {
            width: 100%;
            height: 45px;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
            position: relative;
        }
        
        .color-preset:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .color-preset.selected {
            border-color: #2c3e50;
            box-shadow: 0 0 0 2px white, 0 0 0 4px #2c3e50;
        }
        
        .color-preset.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 20px;
            font-weight: bold;
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }
        
        .color-custom {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-top: 10px;
            border-top: 2px solid #ecf0f1;
        }
        
        .color-custom input[type="color"] {
            width: 70px;
            height: 50px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .color-custom label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 17px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary-modal {
            background: #3498db;
            color: white;
        }
        
        .btn-primary-modal:hover {
            background: #2980b9;
        }
        
        .btn-secondary-modal {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary-modal:hover {
            background: #d5dbdb;
        }
        
        /* ============ Modal Generar Certificados ============ */
        #generarCertificadosModal .modal-content {
            max-width: 900px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        #generarModalHeader {
            margin: -30px -30px 0 -30px;
            padding: 20px 30px;
            border-radius: 12px 12px 0 0;
        }
        
        #generarModalHeader h2 {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        #generarModalHeader .btn-close {
            color: white !important;
            opacity: 0.9;
            font-size: 32px;
        }
        
        #generarModalHeader .btn-close:hover {
            opacity: 1;
        }
        
        .generar-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
        }
        
        .generar-info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .generar-stats {
            display: flex;
            gap: 25px;
        }
        
        .generar-stat {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .generar-stat i {
            font-size: 18px;
            color: #3498db;
        }
        
        .generar-stat .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .generar-stat .stat-label {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .generar-opciones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .generar-opcion {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .generar-opcion label {
            font-size: 13px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .generar-opcion input,
        .generar-opcion select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .generar-opcion input:focus,
        .generar-opcion select:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .generar-table-container {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            max-height: 350px;
            overflow-y: auto;
        }
        
        .generar-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .generar-table th {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .generar-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .generar-table tr:hover {
            background: #f8f9fa;
        }
        
        .generar-table tr.ya-generado {
            background: #e8f8f5;
        }
        
        .generar-table tr.ya-generado td {
            color: #27ae60;
        }
        
        .badge-generado {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #27ae60;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .badge-pendiente {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f39c12;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .badge-generaciones {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #3498db;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 5px;
            cursor: help;
        }
        
        /* Botón de estudiante destacado (estrella) */
        .btn-destacado {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #bdc3c7;
            padding: 5px 10px;
            transition: all 0.2s ease;
            border-radius: 5px;
        }
        
        .btn-destacado:hover {
            color: #f1c40f;
            transform: scale(1.2);
            background: rgba(241, 196, 15, 0.1);
        }
        
        .btn-destacado.destacado-activo {
            color: #f1c40f;
        }
        
        .btn-destacado.destacado-activo:hover {
            color: #d4ac0d;
        }
        
        .btn-destacado i {
            transition: transform 0.2s ease;
        }
        
        .btn-destacado:active i {
            transform: scale(0.9);
        }
        
        .generar-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            margin-top: 20px;
        }
        
        .generar-footer-info {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .generar-footer-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-generar-certs {
            padding: 12px 25px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-generar-certs:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }
        
        .btn-generar-certs:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Sección de resultados de generación */
        .generar-resultados {
            display: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .generar-resultados.active {
            display: block;
        }
        
        .resultado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .resultado-header h4 {
            margin: 0;
            color: #27ae60;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .resultado-header.con-errores h4 {
            color: #f39c12;
        }
        
        .resultado-header.solo-errores h4 {
            color: #e74c3c;
        }
        
        .resultado-stats {
            display: flex;
            gap: 20px;
        }
        
        .resultado-stat {
            text-align: center;
        }
        
        .resultado-stat .num {
            font-size: 24px;
            font-weight: 700;
        }
        
        .resultado-stat .num.success {
            color: #27ae60;
        }
        
        .resultado-stat .num.error {
            color: #e74c3c;
        }
        
        .resultado-stat .label {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .descargas-section {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #27ae60;
        }
        
        .descargas-section h5 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .descargas-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn-descarga {
            padding: 10px 20px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #2c3e50;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-descarga:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .btn-descarga.btn-pdf {
            border-color: #e74c3c;
            color: #e74c3c;
        }
        
        .btn-descarga.btn-pdf:hover {
            background: #e74c3c;
            color: white;
        }
        
        .btn-descarga.btn-img {
            border-color: #9b59b6;
            color: #9b59b6;
        }
        
        .btn-descarga.btn-img:hover {
            background: #9b59b6;
            color: white;
        }
        
        .btn-descarga.btn-zip {
            border-color: #3498db;
            color: #3498db;
        }
        
        .btn-descarga.btn-zip:hover {
            background: #3498db;
            color: white;
        }
        
        /* Sección de errores detallados */
        .errores-detalle-section {
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .errores-alerta {
            display: flex;
            gap: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border: 1px solid #ffc107;
            border-radius: 10px;
            align-items: flex-start;
        }
        
        .errores-alerta > i {
            font-size: 24px;
            color: #856404;
            margin-top: 2px;
        }
        
        .errores-mensaje {
            flex: 1;
        }
        
        .errores-mensaje strong {
            color: #856404;
            font-size: 14px;
        }
        
        .errores-mensaje p {
            margin: 5px 0 0 0;
            color: #6c5c00;
            font-size: 13px;
        }
        
        .errores-lista {
            margin-top: 10px;
            max-height: 150px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .error-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        
        .error-item:last-child {
            border-bottom: none;
        }
        
        .error-item .nombre {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .error-item .error-msg {
            color: #e74c3c;
            font-size: 12px;
        }
        
        /* Alerta de plantilla no configurada */
        .plantilla-no-configurada {
            display: none;
            padding: 30px;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }
        
        .plantilla-no-configurada.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .alerta-icono {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .alerta-icono > .fa-image {
            font-size: 60px;
            color: #bdc3c7;
        }
        
        .alerta-icono .alerta-badge {
            position: absolute;
            bottom: -5px;
            right: -10px;
            font-size: 28px;
            color: #e74c3c;
            background: white;
            border-radius: 50%;
        }
        
        .alerta-contenido h4 {
            margin: 0 0 10px 0;
            color: #e74c3c;
            font-size: 20px;
        }
        
        .alerta-contenido > p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        .alerta-pasos {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-bottom: 20px;
        }
        
        .alerta-pasos p {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .alerta-pasos ol {
            margin: 0;
            padding-left: 20px;
            color: #5a6c7d;
        }
        
        .alerta-pasos li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .btn-ir-plantillas {
            padding: 12px 25px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-ir-plantillas:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        /* Progreso de generación */
        .generar-progress {
            display: none;
            margin-top: 20px;
        }
        
        .generar-progress.active {
            display: block;
        }
        
        .progress-bar-container {
            background: #ecf0f1;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            width: 0%;
            transition: width 0.3s;
            border-radius: 10px;
        }
        
        .progress-text {
            text-align: center;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-nav" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="breadcrumb-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="color: #6b7280; font-size: 13px;"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></span>
                <a href="logout.php" style="color: #dc2626; font-size: 13px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <!-- Header del Grupo -->
        <div class="grupo-header">
            <div class="grupo-header-content">
                <div class="grupo-header-left">
                    <div class="grupo-icon-large"><?= htmlspecialchars($grupo['icono']) ?></div>
                    <div class="grupo-info">
                        <h1><?= htmlspecialchars($grupo['nombre']) ?></h1>
                        <p><?= htmlspecialchars($grupo['descripcion']) ?: 'Sin descripción' ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="estudiantes_grupo.php?id=<?= $grupo_id ?>" class="btn-header" style="background: rgba(46, 204, 113, 0.3); border-color: rgba(46, 204, 113, 0.4);">
                        <i class="fas fa-users"></i> Estudiantes del Grupo
                    </a>
                    <a href="visualizar_certificados.php?grupo=<?= $grupo_id ?>" class="btn-header" style="background: rgba(155, 89, 182, 0.3); border-color: rgba(155, 89, 182, 0.4);">
                        <i class="fas fa-eye"></i> Visualizar Certificados
                    </a>
                    <a href="lista_certificados.php?grupo=<?= $grupo_id ?>" class="btn-header" style="background: rgba(52, 152, 219, 0.3); border-color: rgba(52, 152, 219, 0.4);">
                        <i class="fas fa-list"></i> Ver Certificados
                    </a>
                    <a href="carga_masiva.php?grupo=<?= $grupo_id ?>" class="btn-header" style="background: rgba(46, 204, 113, 0.3); border-color: rgba(46, 204, 113, 0.4);">
                        <i class="fas fa-upload"></i> Carga Masiva
                    </a>
                    <a href="config_grupo.php?id=<?= $grupo_id ?>" class="btn-header">
                        <i class="fas fa-cog"></i> Configurar
                    </a>
                    <button onclick="editarGrupo()" class="btn-header btn-header-icon" title="Editar Grupo">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="eliminarGrupo()" class="btn-header btn-header-icon" style="background: rgba(231, 76, 60, 0.3); border-color: rgba(231, 76, 60, 0.4);" title="Eliminar Grupo">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Categorías -->
        <div class="categorias-container">
            <div class="section-title">
                <h2><i class="fas fa-folder-open"></i> Categorías</h2>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <!-- Selector de Año (filtro) -->
                    <div class="periodo-selector" style="background: #f8f9fa; padding: 12px 20px; border-radius: 12px; border: 2px solid #e9ecef; display: flex; align-items: center; gap: 12px;">
                        <label style="font-weight: 600; color: #495057; white-space: nowrap;"><i class="fas fa-calendar-alt"></i> Año:</label>
                        <select id="anioSelect" onchange="cambiarAnio(this.value)" style="padding: 8px 16px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 15px; color: #495057; background: white; cursor: pointer; min-width: 120px;">
                            <?php foreach ($anios_disponibles as $anio): ?>
                                <option value="<?= $anio ?>" <?= $anio == $anio_seleccionado ? 'selected' : '' ?>>
                                    <?= $anio ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Botón para agregar período -->
                    <button onclick="abrirModalPeriodo()" class="btn-admin" style="background: #9b59b6; padding: 12px 20px; color: white; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500;" title="Nuevo Período">
                        <i class="fas fa-plus"></i> Nuevo Período
                    </button>
                </div>
            </div>
            
            <!-- Información del año -->
            <div style="margin-bottom: 20px; padding: 12px 20px; background: #e8f5e9; border-radius: 10px; border-left: 4px solid #4caf50; display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6c757d;">
                <i class="fas fa-info-circle" style="color: #4caf50;"></i>
                <span>
                    Mostrando <strong><?= count($periodos) ?></strong> período(s) del año <strong><?= $anio_seleccionado ?></strong>
                </span>
            </div>
            
            <!-- Tabs de Períodos -->
            <?php if (count($periodos) > 0): ?>
                <!-- Contenedor de Tabs -->
                <div class="periodo-tabs-container">
                    <!-- Navegación de Tabs -->
                    <div class="periodo-tabs-nav">
                        <?php foreach ($periodos as $idx => $periodo): 
                            $es_periodo_actual = ($periodo['id'] == $periodo_actual_id);
                        ?>
                        <div class="periodo-tab <?= $idx === 0 ? 'active' : '' ?> <?= $es_periodo_actual ? 'periodo-vigente' : '' ?>" 
                             data-periodo-id="<?= $periodo['id'] ?>"
                             onclick="cambiarTab(<?= $periodo['id'] ?>)">
                            <?php if ($es_periodo_actual): ?>
                                <div class="badge-vigente" title="Período en curso">
                                    <i class="fas fa-circle"></i> En curso
                                </div>
                            <?php endif; ?>
                            <div class="tab-icon" style="background: <?= htmlspecialchars($periodo['color'] ?? '#3498db') ?>;">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="tab-info">
                                <div class="tab-title"><?= htmlspecialchars($periodo['nombre']) ?></div>
                                <div class="tab-subtitle">
                                    <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                                </div>
                            </div>
                            <div class="tab-stats">
                                <span class="tab-badge">
                                    <i class="fas fa-folder"></i> <?= count($categorias_por_periodo[$periodo['id']] ?? []) ?>
                                </span>
                                <span class="tab-badge">
                                    <i class="fas fa-users"></i> <?= array_sum(array_column($categorias_por_periodo[$periodo['id']] ?? [], 'total_estudiantes')) ?>
                                </span>
                            </div>
                            <div class="tab-actions" onclick="event.stopPropagation();">
                                <button onclick="editarPeriodo(<?= $periodo['id'] ?>)" class="btn-tab-action" title="Editar período">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="eliminarPeriodo(<?= $periodo['id'] ?>, '<?= htmlspecialchars(addslashes($periodo['nombre'])) ?>')" class="btn-tab-action btn-danger" title="Eliminar período">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Contenido de Tabs -->
                    <?php foreach ($periodos as $idx => $periodo): ?>
                    <div class="periodo-tab-content <?= $idx === 0 ? 'active' : '' ?>" id="tab-content-<?= $periodo['id'] ?>" data-periodo-id="<?= $periodo['id'] ?>">
                        <div class="categorias-grid">
                            <!-- Card para agregar nueva categoría a este período -->
                            <div class="categoria-card add-categoria-card" onclick="openModal(<?= $periodo['id'] ?>)">
                                <div class="add-icon"><i class="fas fa-plus"></i></div>
                                <div class="add-text">Nueva Categoría</div>
                            </div>

                            <?php if (count($categorias_por_periodo[$periodo['id']] ?? []) > 0): ?>
                                <?php foreach ($categorias_por_periodo[$periodo['id']] as $categoria): ?>
                                <div class="categoria-card" style="--categoria-color: <?= htmlspecialchars($categoria['color']) ?>">
                                    <div class="categoria-header">
                                        <div class="categoria-icon" style="background: linear-gradient(135deg, <?= htmlspecialchars($categoria['color']) ?>cc, <?= htmlspecialchars($categoria['color']) ?>);"><?= htmlspecialchars($categoria['icono']) ?></div>
                                        <div class="categoria-info">
                                            <h3><?= htmlspecialchars($categoria['nombre']) ?></h3>
                                        </div>
                                    </div>
                                    
                                    <p class="categoria-descripcion"><?= htmlspecialchars($categoria['descripcion']) ?: 'Sin descripción' ?></p>
                                
                                    <div class="categoria-stats">
                                        <div class="stat-item">
                                            <span>👥</span>
                                            <span class="stat-number"><?= $categoria['total_estudiantes'] ?></span>
                                            <span>estudiantes</span>
                                        </div>
                                        <div class="stat-item">
                                            <span>📄</span>
                                            <span class="stat-number"><?= $categoria['total_certificados'] ?></span>
                                            <span>certificados</span>
                                        </div>
                                    </div>
                                    
                                    <div class="categoria-actions">
                                        <a href="gestion_categoria.php?categoria_id=<?= $categoria['id'] ?>&periodo_id=<?= $periodo['id'] ?>" class="btn-categoria btn-primary" style="grid-column: 1 / -1;">
                                            <i class="fas fa-users"></i> Gestionar Estudiantes / Certificados
                                        </a>
                                        <div class="btn-actions">
                                            <a href="config_categoria.php?id=<?= $categoria['id'] ?>" 
                                               class="btn-icon" 
                                               style="background: #9b59b6; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"
                                               title="Configurar Plantilla">
                                                <i class="fas fa-cog"></i>
                                            </a>
                                            <button onclick="editarCategoria(<?= $categoria['id'] ?>)" class="btn-icon btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="eliminarCategoria(<?= $categoria['id'] ?>, '<?= htmlspecialchars($categoria['nombre']) ?>')" 
                                                    class="btn-icon btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #7f8c8d; background: #f8f9fa; border-radius: 12px;">
                    <div style="font-size: 80px; margin-bottom: 20px; opacity: 0.3;"><i class="fas fa-calendar-plus"></i></div>
                    <h3 style="margin: 0 0 10px 0; color: #2c3e50;">No hay períodos para el año <?= $anio_seleccionado ?></h3>
                    <p>Crea un nuevo período para comenzar a gestionar las categorías</p>
                    <button onclick="abrirModalPeriodo()" 
                            style="margin-top: 20px; padding: 12px 24px; background: #9b59b6; color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-plus"></i> Crear Período
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para editar grupo -->
    <div id="grupoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Grupo</h2>
                <button class="btn-close" onclick="closeGrupoModal()">×</button>
            </div>
            <form id="grupoForm">
                <input type="hidden" name="id" value="<?= $grupo_id ?>">
                
                <div class="form-group">
                    <label for="grupo_nombre">Nombre del Grupo *</label>
                    <input type="text" id="grupo_nombre" name="nombre" required 
                           value="<?= htmlspecialchars($grupo['nombre']) ?>"
                           placeholder="Ej: Talleres, Cursos, Concursos">
                </div>
                
                <div class="form-group">
                    <label for="grupo_descripcion">
                        Descripción
                        <span id="grupoCharCounter" style="float: right; font-size: 12px; color: #95a5a6;">0 / 250</span>
                    </label>
                    <textarea id="grupo_descripcion" name="descripcion" 
                              placeholder="Describe este grupo..."
                              maxlength="250"
                              oninput="updateCharCounterGrupo(this)"
                              onpaste="handlePasteGrupo(event)"><?= htmlspecialchars($grupo['descripcion']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Icono *</label>
                    <input type="hidden" id="grupo_icono" name="icono" value="<?= htmlspecialchars($grupo['icono']) ?>">
                    <div class="icon-selector" id="grupoIconSelector">
                        <div class="icon-option-grupo" data-icon="🎻">🎻</div>
                        <div class="icon-option-grupo" data-icon="🎹">🎹</div>
                        <div class="icon-option-grupo" data-icon="💃">💃</div>
                        <div class="icon-option-grupo" data-icon="🎺">🎺</div>
                        <div class="icon-option-grupo" data-icon="🥁">🥁</div>
                        <div class="icon-option-grupo" data-icon="🎸">🎸</div>
                        <div class="icon-option-grupo" data-icon="🎤">🎤</div>
                        <div class="icon-option-grupo" data-icon="🎭">🎭</div>
                        <div class="icon-option-grupo" data-icon="🎨">🎨</div>
                        <div class="icon-option-grupo" data-icon="📚">📚</div>
                        <div class="icon-option-grupo" data-icon="✍️">✍️</div>
                        <div class="icon-option-grupo" data-icon="🏆">🏆</div>
                        <button type="button" class="icon-option-custom-grupo" onclick="toggleEmojiPickerGrupo()">
                            <i class="fas fa-search"></i>
                            <span>Buscar más...</span>
                        </button>
                    </div>
                    <div id="emojiPickerContainerGrupo" style="display: none; margin-top: 10px; position: relative;">
                        <emoji-picker id="emojiPickerGrupo"></emoji-picker>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Color</label>
                    <input type="hidden" id="grupo_color" name="color" value="<?= htmlspecialchars($grupo['color']) ?>">
                    <div class="color-preview">
                        <div class="color-presets">
                            <div class="color-preset" data-color="#3498db" style="background: #3498db;" onclick="selectPresetColorGrupo(this, '#3498db')" title="Azul"></div>
                            <div class="color-preset" data-color="#e74c3c" style="background: #e74c3c;" onclick="selectPresetColorGrupo(this, '#e74c3c')" title="Rojo"></div>
                            <div class="color-preset" data-color="#2ecc71" style="background: #2ecc71;" onclick="selectPresetColorGrupo(this, '#2ecc71')" title="Verde"></div>
                            <div class="color-preset" data-color="#f39c12" style="background: #f39c12;" onclick="selectPresetColorGrupo(this, '#f39c12')" title="Naranja"></div>
                            <div class="color-preset" data-color="#9b59b6" style="background: #9b59b6;" onclick="selectPresetColorGrupo(this, '#9b59b6')" title="Púrpura"></div>
                            <div class="color-preset" data-color="#1abc9c" style="background: #1abc9c;" onclick="selectPresetColorGrupo(this, '#1abc9c')" title="Turquesa"></div>
                            <div class="color-preset" data-color="#34495e" style="background: #34495e;" onclick="selectPresetColorGrupo(this, '#34495e')" title="Gris Oscuro"></div>
                            <div class="color-preset" data-color="#e67e22" style="background: #e67e22;" onclick="selectPresetColorGrupo(this, '#e67e22')" title="Zanahoria"></div>
                            <div class="color-preset" data-color="#16a085" style="background: #16a085;" onclick="selectPresetColorGrupo(this, '#16a085')" title="Verde Azulado"></div>
                            <div class="color-preset" data-color="#c0392b" style="background: #c0392b;" onclick="selectPresetColorGrupo(this, '#c0392b')" title="Rojo Oscuro"></div>
                            <div class="color-preset" data-color="#8e44ad" style="background: #8e44ad;" onclick="selectPresetColorGrupo(this, '#8e44ad')" title="Púrpura Oscuro"></div>
                            <div class="color-preset" data-color="#2980b9" style="background: #2980b9;" onclick="selectPresetColorGrupo(this, '#2980b9')" title="Azul Oscuro"></div>
                        </div>
                        <div class="color-custom">
                            <label for="grupo_customColor">Personalizado:</label>
                            <input type="color" id="grupo_customColor" value="<?= htmlspecialchars($grupo['color']) ?>" onchange="selectCustomColorGrupo(this.value)">
                            <span id="grupoColorHex" style="font-family: monospace; font-weight: 600; color: #7f8c8d;"><?= htmlspecialchars($grupo['color']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary-modal" onclick="closeGrupoModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para crear/editar categoría -->
    <div id="categoriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Categoría</h2>
                <button class="btn-close" onclick="closeModal()">×</button>
            </div>
            
            <!-- Tabs para elegir entre crear nueva o copiar de otro periodo -->
            <div id="categoriaTabs" class="categoria-tabs">
                <button type="button" class="categoria-tab active" onclick="switchCategoriaTab('nueva')">
                    <i class="fas fa-plus"></i> Crear Nueva
                </button>
                <button type="button" class="categoria-tab" onclick="switchCategoriaTab('copiar')">
                    <i class="fas fa-copy"></i> Copiar de otro período
                </button>
            </div>
            
            <!-- Contenido: Crear Nueva Categoría -->
            <div id="tabNuevaCategoria" class="categoria-tab-content active">
                <form id="categoriaForm">
                    <input type="hidden" id="categoria_id" name="id">
                    <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
                    <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre de la Categoría *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Violín, Piano, Danza">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">
                            Descripción
                            <span id="catCharCounter" style="float: right; font-size: 12px; color: #95a5a6;">0 / 250</span>
                        </label>
                        <textarea id="descripcion" name="descripcion" 
                                  placeholder="Describe esta categoría..."
                                  maxlength="250"
                                  oninput="updateCharCounterCat(this)"
                                  onpaste="handlePasteCat(event)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Icono</label>
                        <input type="hidden" id="icono" name="icono" value="📚">
                        <div class="icon-selector">
                            <div class="icon-option" data-icon="🎻">🎻</div>
                            <div class="icon-option" data-icon="🎹">🎹</div>
                            <div class="icon-option" data-icon="💃">💃</div>
                            <div class="icon-option" data-icon="🎺">🎺</div>
                            <div class="icon-option" data-icon="🥁">🥁</div>
                            <div class="icon-option" data-icon="🎸">🎸</div>
                            <div class="icon-option" data-icon="🎤">🎤</div>
                            <div class="icon-option" data-icon="🎭">🎭</div>
                            <div class="icon-option" data-icon="🎨">🎨</div>
                            <div class="icon-option" data-icon="📚">📚</div>
                            <div class="icon-option" data-icon="✍️">✍️</div>
                            <div class="icon-option" data-icon="🏆">🏆</div>
                            <button type="button" class="icon-option-custom" onclick="toggleEmojiPickerCat()">
                                <i class="fas fa-search"></i>
                                <span>Buscar más...</span>
                            </button>
                        </div>
                        <div id="emojiPickerContainerCat" style="display: none; margin-top: 10px; position: relative;">
                            <emoji-picker id="emojiPickerCat"></emoji-picker>
                        </div>
                    </div>
                    
                    <input type="hidden" id="color" name="color" value="<?= htmlspecialchars($grupo['color']) ?>">
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary-modal" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary-modal">Guardar</button>
                    </div>
                </form>
            </div>
            
            <!-- Contenido: Copiar de otro período -->
            <div id="tabCopiarCategoria" class="categoria-tab-content">
                <div class="form-group">
                    <label for="periodoOrigen">Seleccionar Período de Origen</label>
                    <select id="periodoOrigen" class="form-control" onchange="cargarCategoriasDeOtroPeriodo()">
                        <option value="">-- Seleccionar período --</option>
                        <?php foreach ($periodos as $p): ?>
                        <option value="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
                            <?= htmlspecialchars($p['nombre']) ?> 
                            (<?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="categoriasOtroPeriodoContainer" style="display: none;">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="selectAllCategoriasOtro" onchange="toggleSelectAllCategoriasOtro()">
                            Seleccionar todas las categorías
                        </label>
                    </div>
                    
                    <div id="listaCategoriasOtroPeriodo" class="categorias-lista-copiar">
                        <!-- Se llena dinámicamente -->
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="copiarEstudiantes" checked>
                            También copiar estudiantes matriculados
                        </label>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary-modal" onclick="closeModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary-modal" onclick="copiarCategoriasSeleccionadas()" id="btnCopiarCategorias" disabled>
                        <i class="fas fa-copy"></i> Copiar Categorías
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver todos los estudiantes del grupo -->
    <div id="estudiantesGrupoModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header" id="estudiantesGrupoModalHeader" style="background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>dd 0%, <?= htmlspecialchars($grupo['color']) ?> 100%);">
                <h2 style="color: white;"><i class="fas fa-users"></i> Estudiantes - <?= htmlspecialchars($grupo['nombre']) ?></h2>
                <button class="btn-close" onclick="cerrarEstudiantesGrupoModal()" style="color: white;">×</button>
            </div>
            
            <div class="modal-body-estudiantes">
                <!-- Barra de acciones -->
                <div class="estudiantes-actions-bar">
                    <div class="estudiantes-count-badge">
                        <i class="fas fa-users"></i> <span id="estudiantesGrupoCount">0</span> estudiantes en total
                    </div>
                    <div class="estudiantes-buttons">
                        <select id="filtroCategoria" onchange="filtrarEstudiantesPorCategoria()" style="padding: 8px 15px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; cursor: pointer;">
                            <option value="">Todas las categorías</option>
                        </select>
                        <select id="filtroPeriodo" onchange="filtrarEstudiantesPorPeriodo()" style="padding: 8px 15px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; cursor: pointer;">
                            <option value="">Todos los períodos</option>
                        </select>
                        <button onclick="exportarEstudiantesGrupo()" class="btn-action-estudiantes btn-plantilla" title="Exportar a Excel">
                            <i class="fas fa-file-excel"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <!-- Búsqueda -->
                <div style="margin: 15px 0;">
                    <input type="text" id="buscarEstudianteGrupo" 
                           placeholder="Buscar por nombre, cédula o categoría..." 
                           oninput="buscarEstudianteEnGrupo(this.value)"
                           style="width: 100%; padding: 12px 15px; border: 2px solid #dee2e6; border-radius: 10px; font-size: 14px;">
                </div>
                
                <!-- Tabla de estudiantes -->
                <div class="estudiantes-list-container">
                    <table class="estudiantes-list-table">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 25%">Nombre</th>
                                <th style="width: 15%">Cédula</th>
                                <th style="width: 20%">Categoría</th>
                                <th style="width: 15%">Período</th>
                                <th style="width: 10%">Celular</th>
                                <th style="width: 10%">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="estudiantesGrupoListBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para gestión de estudiantes por categoría -->
    <div id="estudiantesModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header" id="estudiantesModalHeader">
                <h2 id="estudiantesModalTitle"><i class="fas fa-users"></i> Estudiantes</h2>
                <button class="btn-close" onclick="cerrarEstudiantesModal()">×</button>
            </div>
            
            <div class="modal-body-estudiantes">
                <!-- Barra de acciones -->
                <div class="estudiantes-actions-bar">
                    <div class="estudiantes-count-badge">
                        <i class="fas fa-users"></i> <span id="estudiantesCount">0</span> estudiantes
                    </div>
                    <div class="estudiantes-buttons">
                        <button onclick="descargarPlantillaEstudiantes()" class="btn-action-estudiantes btn-plantilla" title="Descargar plantilla Excel">
                            <i class="fas fa-download"></i> Plantilla
                        </button>
                        <button onclick="mostrarCargaArchivo()" class="btn-action-estudiantes btn-archivo">
                            <i class="fas fa-file-upload"></i> Cargar Archivo
                        </button>
                    </div>
                </div>
                
                <!-- Sección de búsqueda/agregar estudiante (siempre visible) -->
                <div class="agregar-estudiante-section">
                    <div class="busqueda-agregar-row">
                        <div class="busqueda-container">
                            <label><i class="fas fa-search"></i> Buscar Estudiante Existente</label>
                            <input type="text" id="buscarEstudianteInput" 
                                   placeholder="Buscar por nombre o cédula..." 
                                   oninput="buscarEstudiante(this.value)"
                                   class="input-busqueda">
                            <div id="resultadosBusqueda" class="resultados-busqueda"></div>
                        </div>
                        <div class="separador-o">o</div>
                        <button onclick="toggleFormNuevoEstudiante()" class="btn-nuevo-estudiante" id="btnNuevoEstudiante">
                            <i class="fas fa-user-plus"></i> Crear Nuevo Estudiante
                        </button>
                    </div>
                    
                    <!-- Formulario para nuevo estudiante -->
                    <div id="nuevoEstudianteForm" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div class="section-header-nuevo">
                            <h4><i class="fas fa-user-plus"></i> Datos del Nuevo Estudiante</h4>
                            <button type="button" onclick="toggleFormNuevoEstudiante()" class="btn-close-sm">×</button>
                        </div>
                        <form id="formNuevoEstudiante" onsubmit="guardarNuevoEstudiante(event)" novalidate>
                            <!-- Checkbox para menor de edad -->
                            <div class="menor-edad-toggle">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="es_menor" name="es_menor" onchange="toggleMenorEdad(this.checked)">
                                    <span class="checkmark"></span>
                                    <span><i class="fas fa-child"></i> Es menor de edad (requiere representante legal)</span>
                                </label>
                            </div>
                            
                            <!-- Datos del estudiante (mayor de edad) -->
                            <div id="datosEstudianteMayor">
                                <div class="form-section-title">
                                    <i class="fas fa-user-graduate"></i> Datos del Estudiante
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm">
                                        <label>Nombre del Estudiante *</label>
                                        <input type="text" name="nombre" id="nuevo_nombre" required placeholder="Nombre completo del estudiante">
                                    </div>
                                    <div class="form-group-sm">
                                        <label>Fecha de Nacimiento *</label>
                                        <input type="date" name="fecha_nacimiento" id="nuevo_fecha_nacimiento" 
                                               max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm">
                                        <label>Cédula * <small style="color:#95a5a6">(10 dígitos)</small></label>
                                        <input type="text" name="cedula" id="nuevo_cedula" 
                                               placeholder="Ej: 1712345678" 
                                               maxlength="10"
                                               pattern="[0-9]{10}"
                                               required
                                               oninput="validarCedulaInput(this)"
                                               onblur="verificarCedula(this)">
                                        <small id="cedula_error" class="field-error"></small>
                                    </div>
                                    <div class="form-group-sm">
                                        <label>Celular * <small style="color:#95a5a6">(9 dígitos)</small></label>
                                        <div class="celular-input-wrapper">
                                            <span class="celular-prefix-sm">+593</span>
                                            <input type="text" name="celular" id="nuevo_celular" 
                                                   placeholder="9XXXXXXXX" 
                                                   maxlength="9"
                                                   pattern="[0-9]{9}"
                                                   required
                                                   oninput="validarCelularInput(this)"
                                                   onblur="verificarCelular(this)">
                                        </div>
                                        <small id="celular_error" class="field-error"></small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm" style="grid-column: span 2;">
                                        <label>Email</label>
                                        <input type="email" name="email" id="nuevo_email" 
                                               placeholder="correo@ejemplo.com"
                                               onblur="verificarEmail(this)">
                                        <small id="email_error" class="field-error"></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sección para menores de edad con representante -->
                            <div id="datosRepresentante" style="display: none;">
                                <!-- Datos del representante legal -->
                                <div class="form-section-title representante">
                                    <i class="fas fa-user-tie"></i> Datos del Representante Legal
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm">
                                        <label>Nombre del Representante *</label>
                                        <input type="text" name="representante_nombre" id="representante_nombre" 
                                               placeholder="Nombre completo del representante">
                                    </div>
                                    <div class="form-group-sm">
                                        <label>Cédula del Representante * <small style="color:#95a5a6">(10 dígitos)</small></label>
                                        <input type="text" name="representante_cedula" id="representante_cedula" 
                                               placeholder="Ej: 1712345678" 
                                               maxlength="10"
                                               pattern="[0-9]{10}"
                                               oninput="validarCedulaInput(this)"
                                               onblur="verificarCedulaRepresentante(this)">
                                        <small id="representante_cedula_error" class="field-error"></small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm">
                                        <label>Celular del Representante * <small style="color:#95a5a6">(9 dígitos)</small></label>
                                        <div class="celular-input-wrapper">
                                            <span class="celular-prefix-sm">+593</span>
                                            <input type="text" name="representante_celular" id="representante_celular" 
                                                   placeholder="9XXXXXXXX" 
                                                   maxlength="9"
                                                   pattern="[0-9]{9}"
                                                   oninput="validarCelularInput(this)"
                                                   onblur="verificarCelularRepresentante(this)">
                                        </div>
                                        <small id="representante_celular_error" class="field-error"></small>
                                    </div>
                                    <div class="form-group-sm">
                                        <label>Email del Representante</label>
                                        <input type="email" name="representante_email" id="representante_email" 
                                               placeholder="correo@ejemplo.com"
                                               onblur="verificarEmailRepresentante(this)">
                                        <small id="representante_email_error" class="field-error"></small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group-sm">
                                        <label>Fecha de Nacimiento del Representante</label>
                                        <input type="date" name="representante_fecha_nacimiento" id="representante_fecha_nacimiento" 
                                               max="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group-sm" style="display: flex; align-items: flex-end;">
                                        <small style="color: #7f8c8d;"><i class="fas fa-info-circle"></i> Opcional, para registro de datos del representante</small>
                                    </div>
                                </div>
                                
                                <!-- Lista de menores a cargo -->
                                <div class="form-section-title menores-section">
                                    <i class="fas fa-children"></i> Menores a Cargo
                                    <button type="button" onclick="agregarMenor()" class="btn-add-menor">
                                        <i class="fas fa-plus"></i> Agregar otro menor
                                    </button>
                                </div>
                                
                                <div id="listaMenores">
                                    <!-- Primer menor (siempre visible) -->
                                    <div class="menor-item" data-index="0">
                                        <div class="menor-header">
                                            <span class="menor-numero"><i class="fas fa-child"></i> Menor #1</span>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group-sm">
                                                <label>Nombre del Menor *</label>
                                                <input type="text" name="menores[0][nombre]" 
                                                       placeholder="Nombre completo del menor">
                                            </div>
                                            <div class="form-group-sm">
                                                <label>Fecha de Nacimiento *</label>
                                                <input type="date" name="menores[0][fecha_nacimiento]"
                                                       max="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group-sm">
                                                <label>Cédula <small style="color:#95a5a6">(10 dígitos)</small></label>
                                                <input type="text" name="menores[0][cedula]" 
                                                       placeholder="Ej: 1712345678"
                                                       maxlength="10"
                                                       pattern="[0-9]{10}"
                                                       oninput="validarCedulaInput(this)"
                                                       onblur="verificarCedulaMenor(this)">
                                                <small class="field-error menor-cedula-error"></small>
                                            </div>
                                            <div class="form-group-sm">
                                                <label>Celular <small style="color:#95a5a6">(9 dígitos)</small></label>
                                                <div class="celular-input-wrapper">
                                                    <span class="celular-prefix-sm">+593</span>
                                                    <input type="text" name="menores[0][celular]" 
                                                           placeholder="9XXXXXXXX"
                                                           maxlength="9"
                                                           pattern="[0-9]{9}"
                                                           oninput="validarCelularInput(this)"
                                                           onblur="verificarCelularMenor(this)">
                                                </div>
                                                <small class="field-error menor-celular-error"></small>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group-sm" style="flex: 1;">
                                                <label>Correo Electrónico</label>
                                                <input type="email" name="menores[0][email]" 
                                                       placeholder="correo@ejemplo.com"
                                                       onblur="verificarEmailMenor(this)">
                                                <small class="field-error menor-email-error"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions-sm">
                                <button type="button" onclick="toggleFormNuevoEstudiante()" class="btn-cancel-sm">Cancelar</button>
                                <button type="submit" class="btn-submit-sm"><i class="fas fa-save"></i> Guardar y Matricular</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sección para cargar archivo -->
                <div id="cargaArchivoSection" style="display: none;" class="carga-archivo-section">
                    <div class="section-header">
                        <h4><i class="fas fa-file-upload"></i> Cargar Archivo de Estudiantes</h4>
                        <button onclick="ocultarCargaArchivo()" class="btn-close-sm">×</button>
                    </div>
                    <div class="info-archivo">
                        <p><i class="fas fa-info-circle"></i> El archivo debe tener las columnas: <strong>nombre</strong> (obligatorio), cedula, celular, email</p>
                        <p>Formatos soportados: .csv, .xlsx, .xls</p>
                    </div>
                    <div class="file-input-wrapper">
                        <input type="file" id="archivoEstudiantes" accept=".csv,.xlsx,.xls" onchange="previsualizarArchivoEstudiantes()">
                        <label for="archivoEstudiantes" class="file-label">
                            <i class="fas fa-cloud-upload-alt"></i> Seleccionar archivo
                        </label>
                    </div>
                    <div id="previewCargaEstudiantes"></div>
                </div>
                
                <!-- Filtro de representantes -->
                <div class="filtros-estudiantes">
                    <button type="button" id="btnVerRepresentantes" class="btn-filtro-representantes" onclick="toggleVistaRepresentantes()">
                        <i class="fas fa-user-tie"></i> Ver Representantes Legales
                    </button>
                    <div class="filtro-stats">
                        <span id="filtroResultados"></span>
                    </div>
                </div>
                
                <!-- Barra de acciones masivas (oculta por defecto) -->
                <div id="accionesMasivas" class="acciones-masivas" style="display: none;">
                    <div class="acciones-masivas-info">
                        <i class="fas fa-check-square"></i>
                        <span id="contadorSeleccionados">0</span> seleccionado(s)
                    </div>
                    <div class="acciones-masivas-btns">
                        <button type="button" class="btn-masivo btn-activar" onclick="cambiarEstadoMasivo('activo')">
                            <i class="fas fa-check"></i> Activar
                        </button>
                        <button type="button" class="btn-masivo btn-desactivar" onclick="cambiarEstadoMasivo('inactivo')">
                            <i class="fas fa-pause"></i> Desactivar
                        </button>
                        <button type="button" class="btn-masivo btn-eliminar" onclick="eliminarMasivo()">
                            <i class="fas fa-trash"></i> Quitar
                        </button>
                        <button type="button" class="btn-masivo btn-cancelar" onclick="cancelarSeleccion()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
                
                <!-- Lista de estudiantes -->
                <div class="estudiantes-list-container">
                    <table class="estudiantes-list-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAllEstudiantes" onchange="toggleSelectAll(this)"></th>
                                <th style="width: 40px;">#</th>
                                <th>Estudiante</th>
                                <th style="width: 60px;">Edad</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 90px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="estudiantesListBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar estudiante -->
    <div id="editarEstudianteModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Editar Estudiante</h2>
                <button class="btn-close" onclick="cerrarEditarEstudianteModal()">×</button>
            </div>
            <form id="formEditarEstudiante" onsubmit="guardarEdicionEstudiante(event)">
                <input type="hidden" id="edit_estudiante_id" name="estudiante_id">
                <input type="hidden" id="edit_matricula_id" name="matricula_id">
                
                <!-- Info de menor -->
                <div id="edit_info_menor" style="display: none;" class="menor-edad-toggle">
                    <label class="checkbox-label" style="cursor: default;">
                        <i class="fas fa-child"></i> Este estudiante es menor de edad
                    </label>
                </div>
                
                <!-- Datos del estudiante -->
                <div class="form-section-title">
                    <i class="fas fa-user-graduate"></i> Datos del Estudiante
                </div>
                <div class="form-row">
                    <div class="form-group-sm">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" required placeholder="Nombre completo">
                    </div>
                    <div class="form-group-sm">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <!-- Datos de contacto (mayor de edad) -->
                <div id="edit_datos_mayor">
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Cédula <small style="color:#95a5a6">(10 dígitos)</small></label>
                            <input type="text" name="cedula" id="edit_cedula" 
                                   placeholder="Ej: 1712345678" 
                                   maxlength="10"
                                   oninput="validarCedulaInput(this)">
                        </div>
                        <div class="form-group-sm">
                            <label>Celular <small style="color:#95a5a6">(9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix-sm">+593</span>
                                <input type="text" name="celular" id="edit_celular" 
                                       placeholder="9XXXXXXXX" 
                                       maxlength="9"
                                       oninput="validarCelularInput(this)">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm" style="grid-column: span 2;">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" placeholder="correo@ejemplo.com">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del representante (menor de edad) -->
                <div id="edit_datos_representante" style="display: none;">
                    <div class="form-section-title representante">
                        <i class="fas fa-user-tie"></i> Datos del Representante Legal
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Nombre del Representante *</label>
                            <input type="text" name="representante_nombre" id="edit_representante_nombre" 
                                   placeholder="Nombre completo">
                        </div>
                        <div class="form-group-sm">
                            <label>Cédula del Representante *</label>
                            <input type="text" name="representante_cedula" id="edit_representante_cedula" 
                                   placeholder="Ej: 1712345678" 
                                   maxlength="10"
                                   oninput="validarCedulaInput(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Celular del Representante *</label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix-sm">+593</span>
                                <input type="text" name="representante_celular" id="edit_representante_celular" 
                                       placeholder="9XXXXXXXX" 
                                       maxlength="9"
                                       oninput="validarCelularInput(this)">
                            </div>
                        </div>
                        <div class="form-group-sm">
                            <label>Email del Representante</label>
                            <input type="email" name="representante_email" id="edit_representante_email" 
                                   placeholder="correo@ejemplo.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Fecha de Nacimiento del Representante</label>
                            <input type="date" name="representante_fecha_nacimiento" id="edit_representante_fecha_nacimiento" 
                                   max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group-sm"></div>
                    </div>
                </div>
                
                <!-- Estado de matrícula -->
                <div class="form-section-title" style="border-bottom-color: #27ae60;">
                    <i class="fas fa-clipboard-check"></i> Estado de Matrícula
                </div>
                <div class="form-row">
                    <div class="form-group-sm">
                        <label>Estado</label>
                        <select name="estado" id="edit_estado" class="select-estado">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>
                    <div class="form-group-sm">
                        <label>Fecha de Matrícula</label>
                        <input type="date" name="fecha_matricula" id="edit_fecha_matricula" readonly 
                               style="background: #f5f5f5;">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary-modal" onclick="cerrarEditarEstudianteModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal"><i class="fas fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Generar Certificados -->
    <div id="generarCertificadosModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header" id="generarModalHeader">
                <h2 id="generarModalTitle"><i class="fas fa-certificate"></i> Generar Certificados</h2>
                <button class="btn-close" onclick="cerrarGenerarModal()">×</button>
            </div>
            
            <div class="generar-body">
                <!-- Barra de información -->
                <div class="generar-info-bar">
                    <div class="generar-stats">
                        <div class="generar-stat">
                            <i class="fas fa-users"></i>
                            <div>
                                <span class="stat-value" id="genTotalEstudiantes">0</span>
                                <span class="stat-label">Estudiantes</span>
                            </div>
                        </div>
                        <div class="generar-stat">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <span class="stat-value" id="genCertificadosGenerados">0</span>
                                <span class="stat-label">Generados</span>
                            </div>
                        </div>
                        <div class="generar-stat">
                            <i class="fas fa-clock"></i>
                            <div>
                                <span class="stat-value" id="genPendientes">0</span>
                                <span class="stat-label">Pendientes</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="selectAllGenerar" onchange="toggleSelectAllGenerar(this)">
                            <span>Seleccionar todos</span>
                        </label>
                    </div>
                </div>
                
                <!-- Opciones de generación -->
                <div class="generar-opciones">
                    <div class="generar-opcion">
                        <label><i class="fas fa-calendar"></i> Fecha del Certificado</label>
                        <input type="date" id="genFecha" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="generar-opcion" style="grid-column: span 2;">
                        <label><i class="fas fa-comment"></i> Razón (opcional, usa la configurada por defecto)</label>
                        <input type="text" id="genRazon" placeholder="Dejar vacío para usar la razón por defecto de la plantilla">
                    </div>
                </div>
                
                <!-- Tabla de estudiantes -->
                <div class="generar-table-container">
                    <table class="generar-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllGenerarHeader" onchange="toggleSelectAllGenerar(this)" title="Seleccionar todos">
                                </th>
                                <th style="width: 40px;">#</th>
                                <th>Estudiante</th>
                                <th style="width: 120px;">Cédula</th>
                                <th style="width: 130px;">Estado</th>
                                <th style="width: 140px;">Fecha Generación</th>
                                <th style="width: 80px; text-align: center;"><i class="fas fa-star" style="color: #f1c40f;"></i> Destacado</th>
                            </tr>
                        </thead>
                        <tbody id="generarListBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>Cargando estudiantes...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Progreso de generación -->
                <div class="generar-progress" id="generarProgress">
                    <div class="progress-bar-container">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                    <p class="progress-text" id="progressText">Generando certificados...</p>
                </div>
                
                <!-- Resultados de generación -->
                <div class="generar-resultados" id="generarResultados">
                    <div class="resultado-header">
                        <h4><i class="fas fa-check-circle"></i> Generación Completada</h4>
                        <div class="resultado-stats">
                            <div class="resultado-stat">
                                <div class="num success" id="resExitosos">0</div>
                                <div class="label">Exitosos</div>
                            </div>
                            <div class="resultado-stat">
                                <div class="num error" id="resErrores">0</div>
                                <div class="label">Errores</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de detalles de errores -->
                    <div class="errores-detalle-section" id="erroresDetalleSection" style="display: none;">
                        <div class="errores-alerta">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="errores-mensaje">
                                <strong id="erroresTitulo">Algunos certificados no se pudieron generar</strong>
                                <p id="erroresDescripcion"></p>
                            </div>
                        </div>
                        <div class="errores-lista" id="erroresLista"></div>
                    </div>
                    
                    <div class="descargas-section" id="descargasSection">
                        <h5><i class="fas fa-download"></i> Descargar Certificados</h5>
                        <div class="descargas-btns">
                            <button onclick="descargarTodosPDF()" class="btn-descarga btn-pdf">
                                <i class="fas fa-file-pdf"></i> Descargar todos (PDF)
                            </button>
                            <button onclick="descargarTodosImg()" class="btn-descarga btn-img">
                                <i class="fas fa-file-image"></i> Descargar todos (Imagen)
                            </button>
                            <button onclick="descargarZIP('pdf')" class="btn-descarga btn-zip">
                                <i class="fas fa-file-archive"></i> ZIP (PDF)
                            </button>
                            <button onclick="descargarZIP('img')" class="btn-descarga btn-zip">
                                <i class="fas fa-file-archive"></i> ZIP (Imágenes)
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Alerta de plantilla no configurada -->
                <div class="plantilla-no-configurada" id="plantillaNoConfigurada" style="display: none;">
                    <div class="alerta-icono">
                        <i class="fas fa-image"></i>
                        <i class="fas fa-times-circle alerta-badge"></i>
                    </div>
                    <div class="alerta-contenido">
                        <h4>Plantilla No Configurada</h4>
                        <p>No se puede generar certificados porque no hay una plantilla configurada para este grupo.</p>
                        <div class="alerta-pasos">
                            <p><strong>Para configurar una plantilla:</strong></p>
                            <ol>
                                <li>Vaya a la pestaña <strong>"Plantillas"</strong> del grupo</li>
                                <li>Suba una imagen de plantilla (.jpg, .png)</li>
                                <li>Configure las posiciones de los elementos</li>
                                <li>Active la plantilla haciendo clic en "Activar"</li>
                            </ol>
                        </div>
                        <button class="btn-ir-plantillas" onclick="irAPlantillas()">
                            <i class="fas fa-cog"></i> Ir a Configurar Plantillas
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Footer con acciones -->
            <div class="generar-footer">
                <div class="generar-footer-info">
                    <span id="seleccionadosCount">0</span> estudiantes seleccionados
                </div>
                <div class="generar-footer-actions">
                    <button type="button" class="btn btn-secondary-modal" onclick="cerrarGenerarModal()">Cerrar</button>
                    <button type="button" class="btn-generar-certs" id="btnGenerarCerts" onclick="generarCertificadosBatch()" disabled>
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Período -->
    <div id="periodoModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div id="periodoModalHeader" class="modal-header" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); margin: -30px -30px 25px -30px; padding: 20px 30px; border-radius: 12px 12px 0 0;">
                <h2 id="periodoModalTitle" style="color: white !important; text-shadow: 0 1px 2px rgba(0,0,0,0.2);"><i class="fas fa-calendar-plus"></i> Nuevo Período</h2>
                <button class="btn-close" onclick="cerrarModalPeriodo()" style="color: white !important; opacity: 0.9; font-size: 32px;">×</button>
            </div>
            <form id="periodoForm" onsubmit="guardarPeriodo(event)">
                <div class="periodo-modal-body">
                    <!-- Contenedor para modo CREAR -->
                    <div id="periodoModoCrear">
                        <div class="periodo-form-row">
                            <div class="periodo-form-group">
                                <label><i class="fas fa-calendar-day"></i> Fecha Inicio</label>
                                <input type="date" id="periodoFechaInicio" onchange="actualizarPreviewPeriodo()">
                            </div>
                            <div class="periodo-form-group">
                                <label><i class="fas fa-calendar-check"></i> Fecha Fin</label>
                                <input type="date" id="periodoFechaFin" onchange="actualizarPreviewPeriodo()">
                            </div>
                        </div>
                        
                        <!-- Preview del nombre generado -->
                        <div class="periodo-preview">
                            <div class="periodo-preview-label">
                                <i class="fas fa-magic"></i> Nombre del período:
                            </div>
                            <div class="periodo-preview-nombre" id="periodoPreviewNombre">
                                Selecciona las fechas...
                            </div>
                        </div>
                    
                        <!-- Seleccionar categorías a incluir -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                            <label style="font-weight: 600; color: #2c3e50; font-size: 14px; display: block; margin-bottom: 10px;">
                                <i class="fas fa-folder"></i> Categorías a incluir en este período:
                            </label>
                            <div id="categoriasPeriodoList" style="max-height: 150px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; padding: 10px;">
                                <!-- Se carga dinámicamente -->
                            </div>
                        
                            <!-- Opción para copiar estudiantes matriculados -->
                            <div id="copiarEstudiantesPeriodoContainer" style="margin-top: 12px; display: none;">
                                <label class="checkbox-label" style="color: #9b59b6;">
                                    <input type="checkbox" id="copiarEstudiantesPeriodo" onchange="togglePeriodoOrigenSelector()">
                                    También copiar estudiantes matriculados
                                </label>
                            
                                <!-- Selector de periodo de origen -->
                                <div id="periodoOrigenSelectorContainer" style="display: none; margin-top: 10px;">
                                    <label style="font-size: 13px; color: #6c757d; display: block; margin-bottom: 5px;">
                                        <i class="fas fa-calendar"></i> Copiar estudiantes del período:
                                    </label>
                                    <select id="periodoOrigenSelector" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                                        <?php foreach ($periodos as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            
                                <small style="display: block; margin-top: 5px; color: #7f8c8d; font-size: 12px;">
                                    <i class="fas fa-info-circle"></i> Los estudiantes de las categorías seleccionadas se copiarán al nuevo período
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenedor para modo EDITAR (solo nombre) -->
                    <div id="periodoModoEditar" style="display: none;">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #2c3e50; font-size: 14px; display: block; margin-bottom: 10px;">
                                <i class="fas fa-edit"></i> Nombre del Período
                            </label>
                            <input type="text" id="periodoNombreEditar" placeholder="Nombre del período" 
                                   style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px;">
                        </div>
                        <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; color: #7f8c8d; font-size: 13px;">
                                <i class="fas fa-calendar"></i>
                                <span id="periodoFechasInfo">Fechas del período</span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="periodoId" value="">
                </div>
                
                <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary-modal" onclick="cerrarModalPeriodo()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal" style="background: #9b59b6;">
                        <i class="fas fa-save"></i> Guardar Período
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar período -->
    <div id="confirmarEliminarPeriodoModal" class="modal">
        <div class="modal-content" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Eliminar Período</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de eliminar el período:
                </p>
                <p id="confirmarEliminarPeriodoNombre" style="color: #e74c3c; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre del período -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> Esto NO eliminará las categorías ni estudiantes, solo la asociación con este período.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary-modal" onclick="cerrarConfirmarEliminarPeriodo()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarEliminarPeriodo" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="confirmarEliminarPeriodo()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Datos del grupo
        const grupoId = <?= $grupo_id ?>;
        const anioActual = <?= $anio_seleccionado ?>;
        
        // ========== FUNCIÓN UTILIDAD PARA ESCAPAR HTML ==========
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // ========== SISTEMA DE NOTIFICACIONES ==========
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.app-notification');
            if (existing) existing.remove();
            
            const colors = {
                success: '#27ae60',
                error: '#e74c3c',
                warning: '#f39c12',
                info: '#3498db'
            };
            
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const notification = document.createElement('div');
            notification.className = 'app-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 99999;
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
                font-weight: 500;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = `
                <span style="font-size: 20px; font-weight: bold;">${icons[type]}</span>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Estilos de animación para notificaciones
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        // ========== FUNCIONES DE PERÍODOS ==========
        
        // Meses en español
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        function generarNombrePeriodo(fechaInicio, fechaFin) {
            if (!fechaInicio || !fechaFin) return 'Selecciona las fechas...';
            
            const inicio = new Date(fechaInicio + 'T00:00:00');
            const fin = new Date(fechaFin + 'T00:00:00');
            
            const mesInicio = inicio.getMonth();
            const mesFin = fin.getMonth();
            const anioInicio = inicio.getFullYear();
            const anioFin = fin.getFullYear();
            const diaInicio = inicio.getDate();
            const diaFin = fin.getDate();
            
            // Si es el mismo mes
            if (mesInicio === mesFin && anioInicio === anioFin) {
                return `${meses[mesInicio]} ${diaInicio}-${diaFin}, ${anioInicio}`;
            }
            
            // Si son meses diferentes del mismo año
            if (anioInicio === anioFin) {
                return `${meses[mesInicio]} - ${meses[mesFin]} ${anioInicio}`;
            }
            
            // Si son años diferentes
            return `${meses[mesInicio]} ${anioInicio} - ${meses[mesFin]} ${anioFin}`;
        }
        
        function actualizarPreviewPeriodo() {
            const fechaInicio = document.getElementById('periodoFechaInicio').value;
            const fechaFin = document.getElementById('periodoFechaFin').value;
            const nombre = generarNombrePeriodo(fechaInicio, fechaFin);
            document.getElementById('periodoPreviewNombre').textContent = nombre;
        }
        
        // Variable para almacenar info de categorías con sus periodos
        let categoriasConPeriodoInfo = [];
        
        async function cargarCategoriasParaPeriodo() {
            const container = document.getElementById('categoriasPeriodoList');
            const copiarContainer = document.getElementById('copiarEstudiantesPeriodoContainer');
            container.innerHTML = '<div style="text-align: center; padding: 10px; color: #7f8c8d;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            if (copiarContainer) copiarContainer.style.display = 'none';
            
            // Verificar si hay periodos existentes
            const hayPeriodos = <?= count($periodos) > 0 ? 'true' : 'false' ?>;
            
            try {
                const response = await fetch(`api_categorias.php?action=listar&grupo_id=${grupoId}`);
                const data = await response.json();
                
                if (data.success && data.categorias.length > 0) {
                    categoriasConPeriodoInfo = data.categorias;
                    let html = '';
                    let tieneEstudiantes = false;
                    
                    for (const cat of data.categorias) {
                        // Verificar si tiene estudiantes en algún periodo
                        const totalEst = cat.total_estudiantes || 0;
                        if (totalEst > 0) tieneEstudiantes = true;
                        
                        html += `
                            <label style="display: flex; align-items: center; gap: 8px; padding: 8px; cursor: pointer; border-radius: 6px; transition: background 0.2s;">
                                <input type="checkbox" name="categorias_periodo[]" value="${cat.id}" checked>
                                <span style="font-size: 18px;">${cat.icono}</span>
                                <span style="font-size: 14px;">${cat.nombre}</span>
                                ${totalEst > 0 ? `<span style="font-size: 11px; color: #7f8c8d; margin-left: auto;"><i class="fas fa-users"></i> ${totalEst}</span>` : ''}
                            </label>
                        `;
                    }
                    container.innerHTML = html;
                    
                    // Mostrar opción de copiar estudiantes si hay categorías con estudiantes y hay periodos
                    if (tieneEstudiantes && hayPeriodos && copiarContainer) {
                        copiarContainer.style.display = 'block';
                    }
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 15px; color: #7f8c8d; font-size: 13px;">No hay categorías. Se crearán después.</div>';
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = '<div style="text-align: center; padding: 10px; color: #e74c3c;">Error al cargar categorías</div>';
            }
        }
        
        function togglePeriodoOrigenSelector() {
            const checkbox = document.getElementById('copiarEstudiantesPeriodo');
            const container = document.getElementById('periodoOrigenSelectorContainer');
            if (!checkbox || !container) return;
            container.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        function abrirModalPeriodo() {
            document.getElementById('periodoModalTitle').innerHTML = '<i class="fas fa-calendar-plus"></i> Nuevo Período';
            const periodoForm = document.getElementById('periodoForm');
            const periodoIdInput = document.getElementById('periodoId');
            const previewNombre = document.getElementById('periodoPreviewNombre');
            if (periodoForm) periodoForm.reset();
            if (periodoIdInput) periodoIdInput.value = '';
            if (previewNombre) previewNombre.textContent = 'Selecciona las fechas...';
            
            // Mostrar modo crear, ocultar modo editar
            const modoCrear = document.getElementById('periodoModoCrear');
            const modoEditar = document.getElementById('periodoModoEditar');
            if (modoCrear) modoCrear.style.display = 'block';
            if (modoEditar) modoEditar.style.display = 'none';
            
            // Resetear opciones de copiar estudiantes
            const copiarContainer = document.getElementById('copiarEstudiantesPeriodoContainer');
            if (copiarContainer) copiarContainer.style.display = 'none';
            const copiarCheckbox = document.getElementById('copiarEstudiantesPeriodo');
            if (copiarCheckbox) copiarCheckbox.checked = false;
            const selectorContainer = document.getElementById('periodoOrigenSelectorContainer');
            if (selectorContainer) selectorContainer.style.display = 'none';
            
            // Establecer fecha por defecto (primer día del próximo mes)
            const hoy = new Date();
            const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            
            const periodoFechaInicio = document.getElementById('periodoFechaInicio');
            const periodoFechaFin = document.getElementById('periodoFechaFin');
            if (periodoFechaInicio) periodoFechaInicio.value = primerDia.toISOString().split('T')[0];
            if (periodoFechaFin) periodoFechaFin.value = ultimoDia.toISOString().split('T')[0];
            actualizarPreviewPeriodo();
            
            cargarCategoriasParaPeriodo();
            const periodoModal = document.getElementById('periodoModal');
            if (periodoModal) periodoModal.classList.add('active');
        }
        
        async function editarPeriodo(periodoId) {
            document.getElementById('periodoModalTitle').innerHTML = '<i class="fas fa-calendar-edit"></i> Editar Período';
            document.getElementById('periodoId').value = periodoId;
            
            // Mostrar modo editar, ocultar modo crear
            document.getElementById('periodoModoCrear').style.display = 'none';
            document.getElementById('periodoModoEditar').style.display = 'block';
            
            try {
                const response = await fetch(`api_periodos.php?action=obtener&id=${periodoId}`);
                const data = await response.json();
                
                if (data.success) {
                    // Solo permitir editar el nombre
                    document.getElementById('periodoNombreEditar').value = data.periodo.nombre;
                    
                    // Mostrar información de las fechas (solo lectura)
                    // Usar split para evitar problemas de zona horaria con new Date()
                    const [anioInicio, mesInicio, diaInicio] = data.periodo.fecha_inicio.split('-');
                    const [anioFin, mesFin, diaFin] = data.periodo.fecha_fin.split('-');
                    
                    const fechaInicioObj = new Date(anioInicio, mesInicio - 1, diaInicio);
                    const fechaFinObj = new Date(anioFin, mesFin - 1, diaFin);
                    
                    const fechaInicio = fechaInicioObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
                    const fechaFin = fechaFinObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
                    document.getElementById('periodoFechasInfo').textContent = `${fechaInicio} - ${fechaFin}`;
                    
                    document.getElementById('periodoModal').classList.add('active');
                }
            } catch (error) {
                console.error(error);
                showNotification('Error al cargar el período', 'error');
            }
        }
        
        function cerrarModalPeriodo() {
            document.getElementById('periodoModal').classList.remove('active');
        }
        
        async function guardarPeriodo(event) {
            event.preventDefault();
            
            const periodoId = document.getElementById('periodoId').value;
            
            // Si es modo edición, solo actualizar el nombre
            if (periodoId) {
                const nombreEditar = document.getElementById('periodoNombreEditar').value.trim();
                if (!nombreEditar) {
                    showNotification('El nombre del período es requerido', 'warning');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'actualizar_nombre');
                formData.append('id', periodoId);
                formData.append('nombre', nombreEditar);
                
                try {
                    const response = await fetch('api_periodos.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('Nombre del período actualizado correctamente', 'success');
                        cerrarModalPeriodo();
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showNotification('Error al actualizar el período', 'error');
                }
                return;
            }
            
            // Modo creación
            const fechaInicio = document.getElementById('periodoFechaInicio').value;
            const fechaFin = document.getElementById('periodoFechaFin').value;
            const copiarEstudiantes = document.getElementById('copiarEstudiantesPeriodo')?.checked || false;
            const periodoOrigenSelector = document.getElementById('periodoOrigenSelector');
            const periodoOrigenId = periodoOrigenSelector ? periodoOrigenSelector.value : null;
            
            // Validar fechas
            if (!fechaInicio || !fechaFin) {
                showNotification('Las fechas son requeridas', 'warning');
                return;
            }
            
            if (new Date(fechaFin) < new Date(fechaInicio)) {
                showNotification('La fecha de fin debe ser posterior a la fecha de inicio', 'warning');
                return;
            }
            
            // Generar nombre automáticamente basado en las fechas
            let nombre = generarNombrePeriodo(fechaInicio, fechaFin);
            
            // Obtener categorías seleccionadas
            const categoriasCheckboxes = document.querySelectorAll('input[name="categorias_periodo[]"]:checked');
            const categorias = Array.from(categoriasCheckboxes).map(cb => cb.value);
            
            const formData = new FormData();
            formData.append('action', periodoId ? 'actualizar' : 'crear');
            formData.append('grupo_id', grupoId);
            formData.append('nombre', nombre);
            formData.append('fecha_inicio', fechaInicio);
            formData.append('fecha_fin', fechaFin);
            formData.append('categorias', JSON.stringify(categorias));
            
            if (periodoId) {
                formData.append('id', periodoId);
            }
            
            try {
                const response = await fetch('api_periodos.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    // Si es nuevo período y hay que copiar estudiantes
                    if (!periodoId && copiarEstudiantes && data.periodo_id && periodoOrigenId) {
                        const nuevoPeriodoId = data.periodo_id;
                        let copiadosOk = 0;
                        
                        for (const catId of categorias) {
                            // Obtener estudiantes del periodo origen seleccionado
                            const estResponse = await fetch(`api_categoria_estudiantes.php?action=list&categoria_id=${catId}&periodo_id=${periodoOrigenId}`);
                            const estData = await estResponse.json();
                            
                            if (estData.success && estData.estudiantes.length > 0) {
                                // Matricular en el nuevo período
                                for (const est of estData.estudiantes) {
                                    const matriculaForm = new FormData();
                                    matriculaForm.append('action', 'matricular');
                                    matriculaForm.append('categoria_id', catId);
                                    matriculaForm.append('estudiante_id', est.estudiante_id);
                                    matriculaForm.append('periodo_id', nuevoPeriodoId);
                                    
                                    await fetch('api_categoria_estudiantes.php', {
                                        method: 'POST',
                                        body: matriculaForm
                                    });
                                    copiadosOk++;
                                }
                            }
                        }
                        
                        if (copiadosOk > 0) {
                            showNotification(`Período creado con ${copiadosOk} estudiante(s) copiado(s)`, 'success');
                        } else {
                            showNotification('Período creado correctamente', 'success');
                        }
                    } else {
                        const mensaje = periodoId ? 'Período actualizado correctamente' : 'Período creado correctamente';
                        showNotification(mensaje, 'success');
                    }
                    cerrarModalPeriodo();
                    
                    // Recargar con el año del período
                    setTimeout(() => {
                        const anioNuevo = new Date(fechaInicio).getFullYear();
                        window.location.href = `grupo_detalle.php?id=${grupoId}&anio=${anioNuevo}`;
                    }, 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showNotification('Error al guardar el período', 'error');
            }
        }
        
        // Variables para el modal de confirmación de eliminación de período
        let periodoIdAEliminar = null;
        
        function eliminarPeriodo(periodoId, nombre) {
            periodoIdAEliminar = periodoId;
            document.getElementById('confirmarEliminarPeriodoNombre').textContent = nombre;
            document.getElementById('confirmarEliminarPeriodoModal').classList.add('active');
        }
        
        function cerrarConfirmarEliminarPeriodo() {
            document.getElementById('confirmarEliminarPeriodoModal').classList.remove('active');
            periodoIdAEliminar = null;
        }
        
        async function confirmarEliminarPeriodo() {
            if (!periodoIdAEliminar) return;
            
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', periodoIdAEliminar);
            formData.append('grupo_id', grupoId);
            
            try {
                const response = await fetch('api_periodos.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    cerrarConfirmarEliminarPeriodo();
                    showNotification('Período eliminado correctamente', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showNotification('Error al eliminar el período', 'error');
            }
        }
        
        // ========== FUNCIONES DE TABS ==========
        
        function cambiarTab(periodoId) {
            // Ocultar todos los tabs y remover active
            document.querySelectorAll('.periodo-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.periodo-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activar el tab seleccionado
            const tabSeleccionado = document.querySelector(`.periodo-tab[data-periodo-id="${periodoId}"]`);
            const contentSeleccionado = document.getElementById(`tab-content-${periodoId}`);
            
            if (tabSeleccionado && contentSeleccionado) {
                tabSeleccionado.classList.add('active');
                contentSeleccionado.classList.add('active');
            }
        }
        
        function expandirTodos() {
            // Ya no es necesario con tabs, pero mantenemos la función por compatibilidad
            showNotification('Todos los períodos están visibles en las pestañas', 'info');
        }
        
        function cambiarAnio(anio) {
            window.location.href = `grupo_detalle.php?id=${grupoId}&anio=${anio}`;
        }
        
        // Inicializar primer tab como activo
        document.addEventListener('DOMContentLoaded', function() {
            const primerTab = document.querySelector('.periodo-tab');
            const primerContent = document.querySelector('.periodo-tab-content');
            
            if (primerTab) primerTab.classList.add('active');
            if (primerContent) primerContent.classList.add('active');
        });
        
        // ========== FUNCIONES ORIGINALES ==========
        
        // Selector de iconos para categorías
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('icono').value = this.dataset.icon;
                // Ocultar picker si está abierto
                document.getElementById('emojiPickerContainerCat').style.display = 'none';
            });
        });
        
        // Toggle emoji picker para categorías
        function toggleEmojiPickerCat() {
            const container = document.getElementById('emojiPickerContainerCat');
            container.style.display = container.style.display === 'none' ? 'block' : 'none';
        }
        
        // Configurar emoji picker para categorías cuando se carga
        document.addEventListener('DOMContentLoaded', function() {
            const picker = document.getElementById('emojiPickerCat');
            if (picker) {
                picker.addEventListener('emoji-click', event => {
                    const emoji = event.detail.unicode;
                    // Deseleccionar opciones predefinidas
                    document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                    // Establecer el emoji seleccionado
                    document.getElementById('icono').value = emoji;
                    // Ocultar el picker
                    document.getElementById('emojiPickerContainerCat').style.display = 'none';
                    // Mostrar feedback visual permanente
                    const customBtn = document.querySelector('.icon-option-custom');
                    customBtn.innerHTML = `<span style="font-size: 24px;">${emoji}</span><span>Cambiar...</span>`;
                    customBtn.classList.add('selected');
                });
            }
        });
        
        // Selector de iconos para grupos
        document.querySelectorAll('.icon-option-grupo').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('grupo_icono').value = this.dataset.icon;
                // Ocultar picker si está abierto
                document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
                // Resetear botón personalizado
                const customBtn = document.querySelector('.icon-option-custom-grupo');
                if (customBtn) {
                    customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar más...</span>';
                    customBtn.classList.remove('selected');
                }
            });
        });
        
        // Toggle emoji picker para grupos
        function toggleEmojiPickerGrupo() {
            const container = document.getElementById('emojiPickerContainerGrupo');
            container.style.display = container.style.display === 'none' ? 'block' : 'none';
        }
        
        // Configurar emoji picker para grupos cuando se carga
        document.addEventListener('DOMContentLoaded', function() {
            const pickerGrupo = document.getElementById('emojiPickerGrupo');
            if (pickerGrupo) {
                pickerGrupo.addEventListener('emoji-click', event => {
                    const emoji = event.detail.unicode;
                    // Deseleccionar opciones predefinidas
                    document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
                    // Establecer el emoji seleccionado
                    document.getElementById('grupo_icono').value = emoji;
                    // Ocultar el picker
                    document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
                    // Mostrar feedback visual permanente
                    const customBtn = document.querySelector('.icon-option-custom-grupo');
                    if (customBtn) {
                        customBtn.innerHTML = `<span style="font-size: 24px;">${emoji}</span><span>Cambiar...</span>`;
                        customBtn.classList.add('selected');
                    }
                });
            }
        });
        
        // Color picker para categorías
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('colorHex').textContent = this.value;
        });
        
        // Funciones para contador de caracteres - Grupo
        function updateCharCounterGrupo(textarea) {
            const count = textarea.value.length;
            const max = textarea.getAttribute('maxlength');
            const counter = document.getElementById('grupoCharCounter');
            counter.textContent = `${count} / ${max}`;
            
            if (count > max * 0.9) {
                counter.style.color = '#e74c3c';
            } else if (count > max * 0.7) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = '#95a5a6';
            }
        }
        
        function handlePasteGrupo(event) {
            const textarea = event.target;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            
            setTimeout(() => {
                if (textarea.value.length > maxLength) {
                    textarea.value = textarea.value.substring(0, maxLength);
                    updateCharCounterGrupo(textarea);
                    alert(`El texto ha sido recortado al límite de ${maxLength} caracteres.`);
                }
            }, 0);
        }
        
        // Funciones para selector de color - Grupo
        function selectPresetColorGrupo(element, color) {
            document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('grupo_color').value = color;
            document.getElementById('grupo_customColor').value = color;
            document.getElementById('grupoColorHex').textContent = color;
        }
        
        function selectCustomColorGrupo(color) {
            document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            document.getElementById('grupo_color').value = color;
            document.getElementById('grupoColorHex').textContent = color;
        }
        
        // Funciones para contador de caracteres - Categoría
        function updateCharCounterCat(textarea) {
            const count = textarea.value.length;
            const max = textarea.getAttribute('maxlength');
            const counter = document.getElementById('catCharCounter');
            counter.textContent = `${count} / ${max}`;
            
            if (count > max * 0.9) {
                counter.style.color = '#e74c3c';
            } else if (count > max * 0.7) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = '#95a5a6';
            }
        }
        
        function handlePasteCat(event) {
            const textarea = event.target;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            
            setTimeout(() => {
                if (textarea.value.length > maxLength) {
                    textarea.value = textarea.value.substring(0, maxLength);
                    updateCharCounterCat(textarea);
                    alert(`El texto ha sido recortado al límite de ${maxLength} caracteres.`);
                }
            }, 0);
        }
        
        // Funciones para selector de color - Categoría
        function selectPresetColorCat(element, color) {
            document.querySelectorAll('#categoriaModal .color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('color').value = color;
            document.getElementById('cat_customColor').value = color;
            document.getElementById('colorHex').textContent = color;
        }
        
        function selectCustomColorCat(color) {
            document.querySelectorAll('#categoriaModal .color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            document.getElementById('color').value = color;
            document.getElementById('colorHex').textContent = color;
        }
        
        // Variable para período al crear categoría
        let periodoParaCategoria = null;
        
        // Función para cambiar entre tabs del modal de categoría
        function switchCategoriaTab(tab) {
            // Actualizar tabs
            document.querySelectorAll('.categoria-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.categoria-tab-content').forEach(c => c.classList.remove('active'));
            
            if (tab === 'nueva') {
                document.querySelector('.categoria-tab:first-child').classList.add('active');
                document.getElementById('tabNuevaCategoria').classList.add('active');
                document.getElementById('modalTitle').textContent = 'Nueva Categoría';
            } else {
                document.querySelector('.categoria-tab:last-child').classList.add('active');
                document.getElementById('tabCopiarCategoria').classList.add('active');
                document.getElementById('modalTitle').textContent = 'Copiar Categorías';
                
                // Filtrar el período actual del select
                const selectPeriodo = document.getElementById('periodoOrigen');
                Array.from(selectPeriodo.options).forEach(opt => {
                    if (opt.value == periodoParaCategoria) {
                        opt.style.display = 'none';
                    } else {
                        opt.style.display = '';
                    }
                });
            }
        }
        
        // Cargar categorías de otro período
        async function cargarCategoriasDeOtroPeriodo() {
            const periodoId = document.getElementById('periodoOrigen').value;
            const container = document.getElementById('categoriasOtroPeriodoContainer');
            const lista = document.getElementById('listaCategoriasOtroPeriodo');
            
            if (!periodoId) {
                container.style.display = 'none';
                return;
            }
            
            lista.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            container.style.display = 'block';
            
            try {
                const response = await fetch(`api_categorias.php?action=listar&grupo_id=<?= $grupo_id ?>&periodo_id=${periodoId}`);
                const data = await response.json();
                
                if (data.success && data.categorias.length > 0) {
                    let html = '';
                    for (const cat of data.categorias) {
                        // Obtener cantidad de estudiantes
                        const estResponse = await fetch(`api_categoria_estudiantes.php?action=list&categoria_id=${cat.id}&periodo_id=${periodoId}`);
                        const estData = await estResponse.json();
                        const cantEstudiantes = estData.success ? estData.estudiantes.length : 0;
                        
                        html += `
                            <label class="categoria-copiar-item" data-id="${cat.id}">
                                <input type="checkbox" class="check-categoria-copiar" 
                                       data-id="${cat.id}" 
                                       data-nombre="${cat.nombre}"
                                       data-icono="${cat.icono}"
                                       data-descripcion="${cat.descripcion || ''}"
                                       data-color="${cat.color}"
                                       onchange="actualizarBotonCopiar()">
                                <span class="categoria-copiar-icono">${cat.icono}</span>
                                <div class="categoria-copiar-info">
                                    <div class="categoria-copiar-nombre">${cat.nombre}</div>
                                    ${cat.descripcion ? `<div class="categoria-copiar-desc">${cat.descripcion}</div>` : ''}
                                    <div class="categoria-copiar-estudiantes">
                                        <i class="fas fa-users"></i> ${cantEstudiantes} estudiante(s)
                                    </div>
                                </div>
                            </label>
                        `;
                    }
                    lista.innerHTML = html;
                } else {
                    lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #7f8c8d;">No hay categorías en este período</div>';
                }
            } catch (error) {
                console.error(error);
                lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error al cargar categorías</div>';
            }
            
            document.getElementById('selectAllCategoriasOtro').checked = false;
            actualizarBotonCopiar();
        }
        
        // Toggle seleccionar todas las categorías
        function toggleSelectAllCategoriasOtro() {
            const selectAll = document.getElementById('selectAllCategoriasOtro').checked;
            document.querySelectorAll('.check-categoria-copiar').forEach(cb => cb.checked = selectAll);
            actualizarBotonCopiar();
        }
        
        // Actualizar estado del botón copiar
        function actualizarBotonCopiar() {
            const seleccionadas = document.querySelectorAll('.check-categoria-copiar:checked').length;
            document.getElementById('btnCopiarCategorias').disabled = seleccionadas === 0;
        }
        
        // Copiar categorías seleccionadas al período actual
        async function copiarCategoriasSeleccionadas() {
            const checkboxes = document.querySelectorAll('.check-categoria-copiar:checked');
            if (checkboxes.length === 0) {
                showNotification('Selecciona al menos una categoría', 'warning');
                return;
            }
            
            const copiarEstudiantes = document.getElementById('copiarEstudiantes').checked;
            const periodoOrigen = document.getElementById('periodoOrigen').value;
            const periodoDestino = periodoParaCategoria;
            
            const btn = document.getElementById('btnCopiarCategorias');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copiando...';
            
            let exitosos = 0;
            let errores = 0;
            
            for (const cb of checkboxes) {
                try {
                    // Crear la categoría en el nuevo período
                    const formData = new FormData();
                    formData.append('nombre', cb.dataset.nombre);
                    formData.append('icono', cb.dataset.icono);
                    formData.append('descripcion', cb.dataset.descripcion || '');
                    formData.append('color', cb.dataset.color);
                    formData.append('grupo_id', <?= $grupo_id ?>);
                    formData.append('periodo_id', periodoDestino);
                    
                    const response = await fetch('api_categorias.php?action=create', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        exitosos++;
                        
                        // Si hay que copiar estudiantes
                        if (copiarEstudiantes) {
                            const categoriaOrigenId = cb.dataset.id;
                            const categoriaDestinoId = data.id;
                            
                            // Obtener estudiantes de la categoría origen
                            const estResponse = await fetch(`api_categoria_estudiantes.php?action=list&categoria_id=${categoriaOrigenId}&periodo_id=${periodoOrigen}`);
                            const estData = await estResponse.json();
                            
                            if (estData.success && estData.estudiantes.length > 0) {
                                // Matricular estudiantes en la nueva categoría
                                for (const est of estData.estudiantes) {
                                    const matriculaForm = new FormData();
                                    matriculaForm.append('action', 'matricular');
                                    matriculaForm.append('categoria_id', categoriaDestinoId);
                                    matriculaForm.append('estudiante_id', est.estudiante_id);
                                    matriculaForm.append('periodo_id', periodoDestino);
                                    
                                    await fetch('api_categoria_estudiantes.php', {
                                        method: 'POST',
                                        body: matriculaForm
                                    });
                                }
                            }
                        }
                    } else {
                        errores++;
                    }
                } catch (e) {
                    console.error(e);
                    errores++;
                }
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-copy"></i> Copiar Categorías';
            
            if (exitosos > 0) {
                showNotification(`${exitosos} categoría(s) copiada(s) correctamente${copiarEstudiantes ? ' con sus estudiantes' : ''}`, 'success');
                closeModal();
                location.reload(); // Recargar para ver las nuevas categorías
            }
            
            if (errores > 0) {
                showNotification(`${errores} categoría(s) no pudieron copiarse`, 'error');
            }
        }
        
        // Modal de categorías
        function openModal(periodoId = null) {
            // Verificar si hay períodos antes de abrir el modal
            const hasPeriodos = <?= count($periodos) > 0 ? 'true' : 'false' ?>;
            
            if (!hasPeriodos && !periodoId) {
                showNotification('Es necesario establecer un período para este grupo antes de crear categorías', 'warning');
                return;
            }
            
            periodoParaCategoria = periodoId;
            
            // Mostrar tabs al crear nueva categoría
            document.getElementById('categoriaTabs').style.display = 'flex';
            
            // Resetear tabs al abrir
            switchCategoriaTab('nueva');
            
            // Resetear select de período origen
            document.getElementById('periodoOrigen').value = '';
            document.getElementById('categoriasOtroPeriodoContainer').style.display = 'none';
            document.getElementById('selectAllCategoriasOtro').checked = false;
            document.getElementById('copiarEstudiantes').checked = true;
            
            document.getElementById('categoriaModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Nueva Categoría';
            document.getElementById('categoriaForm').reset();
            document.getElementById('categoria_id').value = '';
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            document.querySelector('.icon-option[data-icon="📚"]').classList.add('selected');
            document.getElementById('icono').value = '📚';
            document.getElementById('emojiPickerContainerCat').style.display = 'none';
            // Resetear botón personalizado
            const customBtn = document.querySelector('.icon-option-custom');
            if (customBtn) {
                customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar más...</span>';
                customBtn.classList.remove('selected');
            }
            
            // Resetear contador de caracteres
            document.getElementById('catCharCounter').textContent = '0 / 250';
            document.getElementById('catCharCounter').style.color = '#95a5a6';
            
            // El color se hereda automáticamente del grupo
        }
        
        function closeModal() {
            document.getElementById('categoriaModal').classList.remove('active');
            document.getElementById('emojiPickerContainerCat').style.display = 'none';
        }
        
        // Modal de grupo
        function editarGrupo() {
            const iconoActual = '<?= htmlspecialchars($grupo['icono']) ?>';
            
            // Resetear picker
            document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
            const customBtn = document.querySelector('.icon-option-custom-grupo');
            
            // Verificar si el icono actual está en las opciones predefinidas
            const iconoPredefinido = document.querySelector(`.icon-option-grupo[data-icon="${iconoActual}"]`);
            
            if (iconoPredefinido) {
                // Es un icono predefinido
                document.querySelectorAll('.icon-option-grupo').forEach(o => {
                    o.classList.toggle('selected', o.dataset.icon === iconoActual);
                });
                if (customBtn) {
                    customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar más...</span>';
                    customBtn.classList.remove('selected');
                }
            } else {
                // Es un emoji personalizado
                document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
                if (customBtn) {
                    customBtn.innerHTML = `<span style="font-size: 24px;">${iconoActual}</span><span>Cambiar...</span>`;
                    customBtn.classList.add('selected');
                }
            }
            
            // Reconfigurar event listeners para los iconos del grupo cada vez que se abre el modal
            document.querySelectorAll('.icon-option-grupo').forEach(option => {
                // Remover listeners antiguos clonando el elemento
                const newOption = option.cloneNode(true);
                option.parentNode.replaceChild(newOption, option);
                
                // Agregar nuevo listener
                newOption.addEventListener('click', function() {
                    document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('grupo_icono').value = this.dataset.icon;
                    // Ocultar picker si está abierto
                    document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
                    // Resetear botón personalizado
                    if (customBtn) {
                        customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar más...</span>';
                        customBtn.classList.remove('selected');
                    }
                });
            });
            
            // Actualizar contador de caracteres
            const descripcion = '<?= htmlspecialchars($grupo['descripcion']) ?>';
            const length = descripcion.length;
            document.getElementById('grupoCharCounter').textContent = `${length} / 250`;
            if (length > 200) {
                document.getElementById('grupoCharCounter').style.color = '#e74c3c';
            } else if (length > 150) {
                document.getElementById('grupoCharCounter').style.color = '#f39c12';
            } else {
                document.getElementById('grupoCharCounter').style.color = '#95a5a6';
            }
            
            // Seleccionar color preset
            const colorActual = '<?= htmlspecialchars($grupo['color']) ?>';
            document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            const colorPreset = document.querySelector(`#grupoModal .color-preset[data-color="${colorActual}"]`);
            if (colorPreset) {
                colorPreset.classList.add('selected');
            }
            document.getElementById('grupo_customColor').value = colorActual;
            document.getElementById('grupoColorHex').textContent = colorActual;
            
            document.getElementById('grupoModal').classList.add('active');
        }
        
        function closeGrupoModal() {
            document.getElementById('grupoModal').classList.remove('active');
            document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
        }
        
        async function editarCategoria(id) {
            try {
                console.log('Editando categoría ID:', id);
                const response = await fetch(`api_categorias.php?action=get&id=${id}`);
                console.log('Response status:', response.status);
                const text = await response.text();
                console.log('Response text:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parseando JSON:', e);
                    showNotification('Error: Respuesta inválida del servidor', 'error');
                    return;
                }
                
                if (data.success) {
                    const cat = data.categoria;
                    
                    // Ocultar tabs al editar (solo mostrar formulario)
                    document.getElementById('categoriaTabs').style.display = 'none';
                    document.getElementById('tabNuevaCategoria').classList.add('active');
                    document.getElementById('tabCopiarCategoria').classList.remove('active');
                    
                    document.getElementById('modalTitle').textContent = 'Editar Categoría';
                    document.getElementById('categoria_id').value = cat.id;
                    document.getElementById('nombre').value = cat.nombre;
                    document.getElementById('descripcion').value = cat.descripcion || '';
                    document.getElementById('icono').value = cat.icono;
                    document.getElementById('color').value = cat.color;
                    
                    document.querySelectorAll('.icon-option').forEach(o => {
                        o.classList.toggle('selected', o.dataset.icon === cat.icono);
                    });
                    
                    // Actualizar contador de caracteres
                    const descripcion = cat.descripcion || '';
                    const length = descripcion.length;
                    document.getElementById('catCharCounter').textContent = `${length} / 250`;
                    if (length > 200) {
                        document.getElementById('catCharCounter').style.color = '#e74c3c';
                    } else if (length > 150) {
                        document.getElementById('catCharCounter').style.color = '#f39c12';
                    } else {
                        document.getElementById('catCharCounter').style.color = '#95a5a6';
                    }
                    
                    // El color se hereda automáticamente del grupo
                    
                    document.getElementById('categoriaModal').classList.add('active');
                } else {
                    alert('Error: ' + (data.message || 'No se pudo cargar la categoría'));
                }
            } catch (error) {
                console.error('Error en editarCategoria:', error);
                alert('Error al cargar la categoría: ' + error.message);
            }
        }
        
        function eliminarCategoria(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar la categoría "${nombre}"?\n\nLos certificados no se eliminarán, pero perderán la asociación con esta categoría.`)) {
                fetch('api_categorias.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', id: id})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function eliminarGrupo() {
            if (confirm('¿Estás seguro de eliminar este grupo?\n\nSe eliminarán todas sus categorías y certificados asociados.')) {
                fetch('api_grupos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', id: <?= $grupo_id ?>})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        // Submit del formulario de grupo
        document.getElementById('grupoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            // Obtener el nuevo color
            const nuevoColor = formData.get('color');
            
            try {
                const response = await fetch('api_grupos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar el color del header del grupo
                    const groupHeader = document.querySelector('.grupo-header');
                    if (groupHeader) {
                        groupHeader.style.background = `linear-gradient(135deg, ${nuevoColor}dd 0%, ${nuevoColor} 100%)`;
                    }
                    
                    // Actualizar los cards de las categorías con el nuevo color del grupo
                    document.querySelectorAll('.categoria-card:not(.add-categoria-card)').forEach(card => {
                        card.style.setProperty('--categoria-color', nuevoColor);
                        
                        // Actualizar también el gradiente del icono
                        const iconEl = card.querySelector('.categoria-icon');
                        if (iconEl) {
                            iconEl.style.background = `linear-gradient(135deg, ${nuevoColor}cc, ${nuevoColor})`;
                        }
                    });
                    
                    // Cerrar modal
                    closeGrupoModal();
                    
                    // Mostrar mensaje de éxito
                    showNotification('Grupo actualizado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al guardar el grupo', 'error');
            }
        });
        
        // Submit del formulario de categoría
        document.getElementById('categoriaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', document.getElementById('categoria_id').value ? 'update' : 'create');
            
            // Agregar período si está definido
            if (periodoParaCategoria) {
                formData.append('periodo_id', periodoParaCategoria);
            }
            
            try {
                const response = await fetch('api_categorias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al guardar la categoría', 'error');
            }
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('categoriaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.getElementById('grupoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGrupoModal();
            }
        });
        
        // ========== GESTIÓN DE ESTUDIANTES DEL GRUPO COMPLETO ==========
        let estudiantesGrupoCompleto = [];
        let estudiantesGrupoFiltrados = [];
        
        async function abrirEstudiantesGrupo() {
            document.getElementById('estudiantesGrupoModal').classList.add('active');
            await cargarEstudiantesGrupo();
            await cargarFiltrosGrupo();
        }
        
        function cerrarEstudiantesGrupoModal() {
            document.getElementById('estudiantesGrupoModal').classList.remove('active');
            estudiantesGrupoCompleto = [];
            estudiantesGrupoFiltrados = [];
        }
        
        async function cargarEstudiantesGrupo() {
            const tbody = document.getElementById('estudiantesGrupoListBody');
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Cargando estudiantes del grupo...</td></tr>';
            
            try {
                const response = await fetch(`api_estudiantes.php?action=listar_grupo&grupo_id=<?= $grupo_id ?>`);
                const data = await response.json();
                
                if (data.success) {
                    estudiantesGrupoCompleto = data.estudiantes || [];
                    estudiantesGrupoFiltrados = [...estudiantesGrupoCompleto];
                    document.getElementById('estudiantesGrupoCount').textContent = estudiantesGrupoCompleto.length;
                    renderEstudiantesGrupo();
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px; color: #e74c3c;">Error al cargar estudiantes</td></tr>';
                }
            } catch (error) {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px; color: #e74c3c;">Error de conexión</td></tr>';
            }
        }
        
        async function cargarFiltrosGrupo() {
            try {
                // Cargar categorías del grupo
                const respCat = await fetch(`api_categorias.php?action=list&grupo_id=<?= $grupo_id ?>`);
                const dataCat = await respCat.json();
                
                const selectCat = document.getElementById('filtroCategoria');
                selectCat.innerHTML = '<option value="">Todas las categorías</option>';
                if (dataCat.success && dataCat.categorias) {
                    dataCat.categorias.forEach(cat => {
                        selectCat.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                    });
                }
                
                // Cargar períodos del grupo
                const selectPer = document.getElementById('filtroPeriodo');
                selectPer.innerHTML = '<option value="">Todos los períodos</option>';
                <?php foreach ($periodos as $periodo): ?>
                selectPer.innerHTML += `<option value="<?= $periodo['id'] ?>"><?= htmlspecialchars($periodo['nombre']) ?></option>`;
                <?php endforeach; ?>
            } catch (error) {
                console.error('Error cargando filtros:', error);
            }
        }
        
        function renderEstudiantesGrupo() {
            const tbody = document.getElementById('estudiantesGrupoListBody');
            
            if (estudiantesGrupoFiltrados.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #95a5a6;">
                            <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; display: block;"></i>
                            No se encontraron estudiantes
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = estudiantesGrupoFiltrados.map((est, index) => `
                <tr>
                    <td style="text-align: center; color: #95a5a6;">${index + 1}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            ${est.es_menor == 1 ? '<i class="fas fa-child" style="color: #f39c12;" title="Menor de edad"></i>' : ''}
                            <strong>${escapeHtml(est.nombre)}</strong>
                        </div>
                    </td>
                    <td><code style="background: #f8f9fa; padding: 3px 8px; border-radius: 4px;">${est.cedula || 'N/A'}</code></td>
                    <td>
                        <span style="background: ${est.categoria_color || '#95a5a6'}20; color: ${est.categoria_color || '#95a5a6'}; padding: 4px 10px; border-radius: 15px; font-size: 12px;">
                            ${est.categoria_icono || '📁'} ${escapeHtml(est.categoria_nombre || 'Sin categoría')}
                        </span>
                    </td>
                    <td style="font-size: 12px; color: #6c757d;">${escapeHtml(est.periodo_nombre || 'N/A')}</td>
                    <td style="font-size: 13px;">${est.celular ? '+593' + est.celular : '-'}</td>
                    <td>
                        <span style="background: ${est.estado === 'activo' ? '#2ecc7120' : '#e74c3c20'}; color: ${est.estado === 'activo' ? '#27ae60' : '#c0392b'}; padding: 3px 10px; border-radius: 10px; font-size: 12px;">
                            ${est.estado === 'activo' ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }
        
        function buscarEstudianteEnGrupo(termino) {
            termino = termino.toLowerCase().trim();
            
            if (!termino) {
                estudiantesGrupoFiltrados = [...estudiantesGrupoCompleto];
            } else {
                estudiantesGrupoFiltrados = estudiantesGrupoCompleto.filter(est => 
                    (est.nombre && est.nombre.toLowerCase().includes(termino)) ||
                    (est.cedula && est.cedula.includes(termino)) ||
                    (est.categoria_nombre && est.categoria_nombre.toLowerCase().includes(termino))
                );
            }
            
            aplicarFiltrosGrupo();
        }
        
        function filtrarEstudiantesPorCategoria() {
            aplicarFiltrosGrupo();
        }
        
        function filtrarEstudiantesPorPeriodo() {
            aplicarFiltrosGrupo();
        }
        
        function aplicarFiltrosGrupo() {
            const categoriaId = document.getElementById('filtroCategoria').value;
            const periodoId = document.getElementById('filtroPeriodo').value;
            const termino = document.getElementById('buscarEstudianteGrupo').value.toLowerCase().trim();
            
            estudiantesGrupoFiltrados = estudiantesGrupoCompleto.filter(est => {
                let cumple = true;
                
                // Filtro por búsqueda
                if (termino) {
                    cumple = cumple && (
                        (est.nombre && est.nombre.toLowerCase().includes(termino)) ||
                        (est.cedula && est.cedula.includes(termino)) ||
                        (est.categoria_nombre && est.categoria_nombre.toLowerCase().includes(termino))
                    );
                }
                
                // Filtro por categoría
                if (categoriaId) {
                    cumple = cumple && (est.categoria_id == categoriaId);
                }
                
                // Filtro por período
                if (periodoId) {
                    cumple = cumple && (est.periodo_id == periodoId);
                }
                
                return cumple;
            });
            
            document.getElementById('estudiantesGrupoCount').textContent = estudiantesGrupoFiltrados.length;
            renderEstudiantesGrupo();
        }
        
        function exportarEstudiantesGrupo() {
            // Crear CSV
            let csv = 'Nombre,Cédula,Categoría,Período,Celular,Email,Estado,Es Menor\n';
            
            estudiantesGrupoFiltrados.forEach(est => {
                csv += `"${est.nombre || ''}","${est.cedula || ''}","${est.categoria_nombre || ''}","${est.periodo_nombre || ''}","${est.celular ? '+593' + est.celular : ''}","${est.email || ''}","${est.estado || ''}","${est.es_menor == 1 ? 'Sí' : 'No'}"\n`;
            });
            
            // Descargar
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `estudiantes_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $grupo['nombre']) ?>_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            showNotification(`Exportados ${estudiantesGrupoFiltrados.length} estudiantes`, 'success');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('estudiantesGrupoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarEstudiantesGrupoModal();
            }
        });
        
        // ========== GESTIÓN DE ESTUDIANTES POR CATEGORÍA ==========
        let categoriaEstudiantesActual = null;
        let periodoEstudiantesActual = null;
        let estudiantesCategoria = [];
        
        // Datos del grupo disponibles desde PHP
        const grupoNombre = '<?= addslashes(htmlspecialchars($grupo['nombre'])) ?>';
        const grupoColor = '<?= htmlspecialchars($grupo['color']) ?>';
        
        function abrirGestionEstudiantes(categoriaId, categoriaNombre, categoriaColor, periodoId) {
            categoriaEstudiantesActual = categoriaId;
            periodoEstudiantesActual = periodoId;
            document.getElementById('estudiantesModal').classList.add('active');
            
            // Actualizar título con formato: Estudiantes - GRUPO - Categoría
            document.getElementById('estudiantesModalTitle').innerHTML = `<i class="fas fa-users"></i> Estudiantes - ${grupoNombre} - ${categoriaNombre}`;
            document.getElementById('estudiantesModalTitle').style.color = 'white';
            
            // Aplicar color del grupo al header del modal
            const modalHeader = document.getElementById('estudiantesModalHeader');
            modalHeader.style.background = `linear-gradient(135deg, ${grupoColor}dd 0%, ${grupoColor} 100%)`;
            modalHeader.style.borderBottom = 'none';
            
            // Asegurar que el botón de cerrar sea visible (blanco)
            modalHeader.querySelector('.btn-close').style.color = 'white';
            modalHeader.querySelector('.btn-close').style.opacity = '0.9';
            
            // Cargar estudiantes de esta categoría y período
            cargarEstudiantesCategoria(categoriaId);
        }
        
        function cerrarEstudiantesModal() {
            document.getElementById('estudiantesModal').classList.remove('active');
            categoriaEstudiantesActual = null;
            periodoEstudiantesActual = null;
            estudiantesCategoria = [];
        }
        
        async function cargarEstudiantesCategoria(categoriaId) {
            const tbody = document.getElementById('estudiantesListBody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
            
            // Resetear filtro de representantes
            document.getElementById('btnVerRepresentantes').classList.remove('active');
            vistaRepresentantes = false;
            
            try {
                // Incluir periodo_id en la consulta
                let url = `api_categoria_estudiantes.php?action=list&categoria_id=${categoriaId}`;
                if (periodoEstudiantesActual) {
                    url += `&periodo_id=${periodoEstudiantesActual}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    estudiantesCategoria = data.estudiantes;
                    renderEstudiantesCategoria();
                    document.getElementById('estudiantesCount').textContent = data.total || 0;
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #e74c3c;">Error al cargar estudiantes</td></tr>';
                }
            } catch (error) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #e74c3c;">Error de conexión</td></tr>';
            }
        }
        
        let vistaRepresentantes = false;
        
        function toggleVistaRepresentantes() {
            vistaRepresentantes = !vistaRepresentantes;
            const btn = document.getElementById('btnVerRepresentantes');
            
            if (vistaRepresentantes) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-users"></i> Ver Todos los Estudiantes';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-user-tie"></i> Ver Representantes Legales';
            }
            
            renderEstudiantesCategoria();
        }
        
        function renderVistaRepresentantes() {
            const tbody = document.getElementById('estudiantesListBody');
            
            // Agrupar menores por representante
            const representantes = new Map();
            
            estudiantesCategoria.forEach(est => {
                if (est.es_menor == 1 && est.representante_cedula) {
                    if (!representantes.has(est.representante_cedula)) {
                        representantes.set(est.representante_cedula, {
                            nombre: est.representante_nombre,
                            cedula: est.representante_cedula,
                            celular: est.representante_celular,
                            email: est.representante_email,
                            menores: []
                        });
                    }
                    representantes.get(est.representante_cedula).menores.push(est);
                }
            });
            
            if (representantes.size === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <i class="fas fa-user-tie" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px; display: block;"></i>
                            No hay representantes legales en esta categoría.<br>
                            <small>Los representantes aparecen cuando hay estudiantes menores de edad.</small>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            let index = 0;
            representantes.forEach((rep, cedula) => {
                index++;
                const menoresNombres = rep.menores.map(m => m.nombre).join(', ');
                
                // Formatear celular para WhatsApp
                const celularWhatsapp = rep.celular ? rep.celular.replace(/[\s\-\(\)]/g, '').replace(/^0/, '593') : '';
                const whatsappLink = celularWhatsapp ? `https://wa.me/${celularWhatsapp}` : '';
                const mailtoLink = rep.email ? `mailto:${rep.email}` : '';
                
                html += `
                    <tr style="background: #f5eef8;">
                        <td style="text-align: center; font-weight: 600; color: #8e44ad;">${index}</td>
                        <td>
                            <strong style="color: #8e44ad;"><i class="fas fa-user-tie"></i> ${rep.nombre}</strong>
                            <br><small style="color: #95a5a6;">CI: ${rep.cedula}</small>
                            <br><small style="color: #7f8c8d;"><i class="fas fa-child"></i> Menores: ${rep.menores.length}</small>
                        </td>
                        <td style="text-align: center;"><span style="color:#95a5a6">-</span></td>
                        <td>${rep.celular ? `<a href="${whatsappLink}" target="_blank" class="link-whatsapp" title="Abrir WhatsApp"><i class="fab fa-whatsapp"></i> ${rep.celular}</a>` : '<span style="color:#95a5a6">-</span>'}</td>
                        <td>${rep.email ? `<a href="${mailtoLink}" class="link-email" title="Enviar correo"><i class="fas fa-envelope"></i> ${rep.email}</a>` : '<span style="color:#95a5a6">-</span>'}</td>
                        <td><span class="badge-menor" style="font-size: 11px;">${rep.menores.length} menor${rep.menores.length > 1 ? 'es' : ''}</span></td>
                        <td></td>
                    </tr>
                `;
                
                // Mostrar los menores de este representante
                rep.menores.forEach(menor => {
                    html += `
                        <tr style="background: #fdfbfe;">
                            <td style="text-align: center; color: #bdc3c7; padding-left: 25px;">↳</td>
                            <td style="padding-left: 30px;">
                                <i class="fas fa-child" style="color: #e67e22;"></i> ${menor.nombre}
                            </td>
                            <td style="text-align: center;">${calcularEdadSimple(menor.fecha_nacimiento)}</td>
                            <td colspan="2" style="color: #95a5a6; font-style: italic; font-size: 12px;">
                                Contacto a través del representante
                            </td>
                            <td><span class="estado-badge ${menor.estado === 'activo' ? 'estado-activo' : 'estado-inactivo'}">${menor.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></td>
                            <td style="text-align: center;">
                                <button class="btn-action-sm btn-edit-sm" onclick="editarEstudianteCategoria(${menor.id}, ${menor.estudiante_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            });
            
            tbody.innerHTML = html;
        }
        
        function calcularEdadSimple(fechaNacimiento) {
            if (!fechaNacimiento) return '<span style="color:#95a5a6">-</span>';
            const hoy = new Date();
            const nacimiento = new Date(fechaNacimiento);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            return `${edad} años`;
        }

        function renderEstudiantesCategoria() {
            const tbody = document.getElementById('estudiantesListBody');
            
            // Actualizar estadísticas
            const statsDiv = document.getElementById('filtroResultados');
            const totalMenores = estudiantesCategoria.filter(e => e.es_menor == 1).length;
            statsDiv.innerHTML = `<strong>${estudiantesCategoria.length}</strong> estudiantes (${totalMenores} menores)`;
            
            // Si está en vista de representantes, mostrar esa vista
            if (vistaRepresentantes) {
                renderVistaRepresentantes();
                return;
            }
            
            if (estudiantesCategoria.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <i class="fas fa-user-plus" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px; display: block;"></i>
                            No hay estudiantes matriculados en esta categoría.<br>
                            <small>Agrega estudiantes usando el botón "Agregar" o "Cargar Archivo".</small>
                        </td>
                    </tr>
                `;
                return;
            }
            
            // Función para calcular edad desde fecha de nacimiento
            function calcularEdad(fechaNacimiento) {
                if (!fechaNacimiento) return null;
                const hoy = new Date();
                const nacimiento = new Date(fechaNacimiento);
                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const mes = hoy.getMonth() - nacimiento.getMonth();
                if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }
                return edad;
            }
            
            // Separar estudiantes mayores y agrupar menores por representante
            const mayores = [];
            const representantes = new Map();
            
            estudiantesCategoria.forEach(est => {
                if (est.es_menor == 1 && est.representante_cedula) {
                    // Es menor con representante - agrupar
                    if (!representantes.has(est.representante_cedula)) {
                        representantes.set(est.representante_cedula, {
                            nombre: est.representante_nombre,
                            cedula: est.representante_cedula,
                            celular: est.representante_celular,
                            email: est.representante_email,
                            menores: []
                        });
                    }
                    representantes.get(est.representante_cedula).menores.push(est);
                } else {
                    // Es mayor de edad o menor sin representante
                    mayores.push(est);
                }
            });
            
            let html = '';
            let index = 0;
            
            // Primero mostrar representantes con sus menores
            representantes.forEach((rep, cedula) => {
                index++;
                const repId = `rep_${cedula.replace(/\D/g, '')}`;
                
                // Formatear celular para WhatsApp
                const celularWhatsapp = rep.celular ? rep.celular.replace(/[\s\-\(\)]/g, '').replace(/^0/, '593') : '';
                const whatsappLink = celularWhatsapp ? `https://wa.me/${celularWhatsapp}` : '';
                const mailtoLink = rep.email ? `mailto:${rep.email}` : '';
                
                // Fila del representante (expandible)
                html += `
                    <tr class="fila-representante" data-rep-id="${repId}">
                        <td style="text-align: center;">
                            <button class="btn-expand-rep" onclick="toggleMenoresRep('${repId}')" title="Ver menores a cargo">
                                <i class="fas fa-plus-circle" id="icon_${repId}"></i>
                            </button>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #8e44ad;">${index}</td>
                        <td>
                            <strong style="color: #8e44ad;"><i class="fas fa-user-tie"></i> ${rep.nombre}</strong>
                            <span class="badge-representante">${rep.menores.length} menor${rep.menores.length > 1 ? 'es' : ''}</span>
                            <br><small style="color: #95a5a6;">CI: ${rep.cedula}</small>
                        </td>
                        <td style="text-align: center;"><span style="color:#95a5a6">-</span></td>
                        <td>
                            ${rep.celular ? `<a href="${whatsappLink}" target="_blank" class="link-whatsapp" title="Abrir WhatsApp"><i class="fab fa-whatsapp"></i> ${rep.celular}</a>` : '<span style="color:#95a5a6">-</span>'}
                        </td>
                        <td>
                            ${rep.email ? `<a href="${mailtoLink}" class="link-email" title="Enviar correo"><i class="fas fa-envelope"></i> ${rep.email}</a>` : '<span style="color:#95a5a6">-</span>'}
                        </td>
                        <td><span class="badge-representante-label"><i class="fas fa-user-shield"></i> Representante</span></td>
                        <td></td>
                    </tr>
                `;
                
                // Filas de menores (ocultas inicialmente)
                rep.menores.forEach((menor, mIndex) => {
                    const edad = calcularEdad(menor.fecha_nacimiento);
                    const edadTexto = edad !== null ? `${edad} años` : '<span style="color:#95a5a6">-</span>';
                    const estadoClass = menor.estado === 'activo' ? 'estado-activo' : (menor.estado === 'completado' ? 'estado-completado' : 'estado-inactivo');
                    const estadoLabel = menor.estado === 'activo' ? 'Activo' : (menor.estado === 'completado' ? 'Completado' : 'Inactivo');
                    
                    // Datos propios del menor (si los tiene)
                    const celularMenor = menor.celular || '';
                    const emailMenor = menor.email || '';
                    const celularWhatsappMenor = celularMenor ? celularMenor.replace(/[\s\-\(\)]/g, '').replace(/^0/, '593') : '';
                    const whatsappLinkMenor = celularWhatsappMenor ? `https://wa.me/${celularWhatsappMenor}` : '';
                    const mailtoLinkMenor = emailMenor ? `mailto:${emailMenor}` : '';
                    
                    html += `
                        <tr class="fila-menor fila-menor-${repId}" style="display: none; background: #faf5ff;">
                            <td style="text-align: center;">
                                <input type="checkbox" class="checkbox-estudiante" value="${menor.id}" onchange="actualizarSeleccion()">
                            </td>
                            <td style="text-align: center; color: #bdc3c7; padding-left: 15px;">
                                <i class="fas fa-level-up-alt fa-rotate-90" style="color: #d5b8e8;"></i>
                            </td>
                            <td style="padding-left: 25px;">
                                <i class="fas fa-child" style="color: #e67e22;"></i> <strong>${menor.nombre}</strong>
                                <span class="badge-menor"><i class="fas fa-child"></i> Menor</span>
                                ${menor.cedula ? `<br><small style="color: #95a5a6;">CI: ${menor.cedula}</small>` : ''}
                            </td>
                            <td style="text-align: center;">${edadTexto}</td>
                            <td>
                                ${celularMenor ? `<a href="${whatsappLinkMenor}" target="_blank" class="link-whatsapp" title="Abrir WhatsApp"><i class="fab fa-whatsapp"></i> ${celularMenor}</a>` : '<span style="color:#ccc; font-size:11px;">-</span>'}
                            </td>
                            <td>
                                ${emailMenor ? `<a href="${mailtoLinkMenor}" class="link-email" title="Enviar correo"><i class="fas fa-envelope"></i> ${emailMenor}</a>` : '<span style="color:#ccc; font-size:11px;">-</span>'}
                            </td>
                            <td><span class="estado-badge ${estadoClass}">${estadoLabel}</span></td>
                            <td style="text-align: center;">
                                <button class="btn-action-sm btn-edit-sm" onclick="editarEstudianteCategoria(${menor.id}, ${menor.estudiante_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action-sm btn-delete-sm" onclick="quitarEstudianteCategoria(${menor.id}, '${menor.nombre}')" title="Quitar">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            });
            
            // Luego mostrar estudiantes mayores de edad
            mayores.forEach((est) => {
                index++;
                const estadoClass = est.estado === 'activo' ? 'estado-activo' : (est.estado === 'completado' ? 'estado-completado' : 'estado-inactivo');
                const estadoLabel = est.estado === 'activo' ? 'Activo' : (est.estado === 'completado' ? 'Completado' : 'Inactivo');
                
                const edad = calcularEdad(est.fecha_nacimiento);
                const edadTexto = edad !== null ? `${edad} años` : '<span style="color:#95a5a6">-</span>';
                
                const celularWhatsapp = est.celular ? est.celular.replace(/[\s\-\(\)]/g, '').replace(/^0/, '593') : '';
                const whatsappLink = celularWhatsapp ? `https://wa.me/${celularWhatsapp}` : '';
                const mailtoLink = est.email ? `mailto:${est.email}` : '';
                
                html += `
                    <tr data-id="${est.id}" data-estudiante-id="${est.estudiante_id}">
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-estudiante" value="${est.id}" onchange="actualizarSeleccion()">
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #7f8c8d;">${index}</td>
                        <td>
                            <strong>${est.nombre}</strong>
                            ${est.cedula ? `<br><small style="color: #95a5a6;">CI: ${est.cedula}</small>` : ''}
                        </td>
                        <td style="text-align: center;">${edadTexto}</td>
                        <td>
                            ${est.celular ? `<a href="${whatsappLink}" target="_blank" class="link-whatsapp" title="Abrir WhatsApp"><i class="fab fa-whatsapp"></i> ${est.celular}</a>` : '<span style="color:#95a5a6">-</span>'}
                        </td>
                        <td>
                            ${est.email ? `<a href="${mailtoLink}" class="link-email" title="Enviar correo"><i class="fas fa-envelope"></i> ${est.email}</a>` : '<span style="color:#95a5a6">-</span>'}
                        </td>
                        <td><span class="estado-badge ${estadoClass}">${estadoLabel}</span></td>
                        <td style="text-align: center;">
                            <button class="btn-action-sm btn-edit-sm" onclick="editarEstudianteCategoria(${est.id}, ${est.estudiante_id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action-sm btn-delete-sm" onclick="quitarEstudianteCategoria(${est.id}, '${est.nombre}')" title="Quitar">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            
            // Resetear checkbox de seleccionar todos
            document.getElementById('selectAllEstudiantes').checked = false;
            ocultarAccionesMasivas();
        }
        
        // Función para expandir/contraer menores de un representante
        function toggleMenoresRep(repId) {
            const filasMenores = document.querySelectorAll(`.fila-menor-${repId}`);
            const icon = document.getElementById(`icon_${repId}`);
            const estaExpandido = icon.classList.contains('fa-minus-circle');
            
            filasMenores.forEach(fila => {
                fila.style.display = estaExpandido ? 'none' : 'table-row';
            });
            
            if (estaExpandido) {
                icon.classList.remove('fa-minus-circle');
                icon.classList.add('fa-plus-circle');
            } else {
                icon.classList.remove('fa-plus-circle');
                icon.classList.add('fa-minus-circle');
            }
        }
        
        // Función para descargar la plantilla de estudiantes en Excel
        function descargarPlantillaEstudiantes() {
            window.location.href = 'descargar_plantilla_estudiantes.php';
        }
        
        // Toggle para mostrar/ocultar formulario nuevo estudiante
        function toggleFormNuevoEstudiante() {
            const form = document.getElementById('nuevoEstudianteForm');
            const btn = document.getElementById('btnNuevoEstudiante');
            
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                btn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                btn.classList.add('active');
                document.getElementById('nuevo_nombre').focus();
            } else {
                form.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Crear Nuevo Estudiante';
                btn.classList.remove('active');
                document.getElementById('formNuevoEstudiante').reset();
                // Limpiar errores
                document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
                document.querySelectorAll('.input-error, .input-valid').forEach(el => {
                    el.classList.remove('input-error', 'input-valid');
                });
                // Resetear sección de menor de edad
                toggleMenorEdad(false);
                // Resetear lista de menores
                resetearListaMenores();
            }
        }
        
        // Contador de menores
        let contadorMenores = 1;
        
        // Toggle para mostrar/ocultar campos de menor de edad
        function toggleMenorEdad(esMenor) {
            const datosMayor = document.getElementById('datosEstudianteMayor');
            const datosRepresentante = document.getElementById('datosRepresentante');
            const listaMenores = document.getElementById('listaMenores');
            
            if (esMenor) {
                datosMayor.style.display = 'none';
                datosRepresentante.style.display = 'block';
                // Hacer obligatorios los campos del representante
                document.getElementById('representante_nombre').required = true;
                document.getElementById('representante_cedula').required = true;
                document.getElementById('representante_celular').required = true;
                // Quitar required del nombre de estudiante mayor
                document.getElementById('nuevo_nombre').required = false;
                // Activar required en campos de menores
                listaMenores.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                    input.required = true;
                });
            } else {
                datosMayor.style.display = 'block';
                datosRepresentante.style.display = 'none';
                // Quitar obligatorios del representante
                document.getElementById('representante_nombre').required = false;
                document.getElementById('representante_cedula').required = false;
                document.getElementById('representante_celular').required = false;
                // Restaurar required del nombre
                document.getElementById('nuevo_nombre').required = true;
                // Quitar required de campos de menores (están ocultos)
                listaMenores.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                    input.required = false;
                });
            }
        }
        
        // Agregar un nuevo menor a la lista
        function agregarMenor() {
            const lista = document.getElementById('listaMenores');
            const index = contadorMenores;
            contadorMenores++;
            
            // Solo poner required si es_menor está activo (lo cual siempre será true al agregar)
            const esMenorActivo = document.getElementById('es_menor').checked;
            const reqAttr = esMenorActivo ? 'required' : '';
            
            const menorHtml = `
                <div class="menor-item" data-index="${index}">
                    <div class="menor-header">
                        <span class="menor-numero"><i class="fas fa-child"></i> Menor #${index + 1}</span>
                        <button type="button" onclick="removerMenor(${index})" class="btn-remove-menor" title="Quitar menor">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Nombre del Menor *</label>
                            <input type="text" name="menores[${index}][nombre]" ${reqAttr}
                                   placeholder="Nombre completo del menor">
                        </div>
                        <div class="form-group-sm">
                            <label>Fecha de Nacimiento *</label>
                            <input type="date" name="menores[${index}][fecha_nacimiento]" ${reqAttr}
                                   max="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Cédula <small style="color:#95a5a6">(10 dígitos)</small></label>
                            <input type="text" name="menores[${index}][cedula]"
                                   placeholder="Ej: 1712345678"
                                   maxlength="10"
                                   pattern="[0-9]{10}"
                                   oninput="validarCedulaInput(this)"
                                   onblur="verificarCedulaMenor(this)">
                            <small class="field-error menor-cedula-error"></small>
                        </div>
                        <div class="form-group-sm">
                            <label>Celular <small style="color:#95a5a6">(9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix-sm">+593</span>
                                <input type="text" name="menores[${index}][celular]"
                                       placeholder="9XXXXXXXX"
                                       maxlength="9"
                                       pattern="[0-9]{9}"
                                       oninput="validarCelularInput(this)"
                                       onblur="verificarCelularMenor(this)">
                            </div>
                            <small class="field-error menor-celular-error"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm" style="flex: 1;">
                            <label>Correo Electrónico</label>
                            <input type="email" name="menores[${index}][email]"
                                   placeholder="correo@ejemplo.com"
                                   onblur="verificarEmailMenor(this)">
                            <small class="field-error menor-email-error"></small>
                        </div>
                    </div>
                </div>
            `;
            
            lista.insertAdjacentHTML('beforeend', menorHtml);
            actualizarNumerosMenores();
            
            // Scroll al nuevo menor
            lista.scrollTop = lista.scrollHeight;
        }
        
        // Remover un menor de la lista
        function removerMenor(index) {
            const item = document.querySelector(`.menor-item[data-index="${index}"]`);
            if (item) {
                item.remove();
                actualizarNumerosMenores();
            }
        }
        
        // Actualizar los números de los menores
        function actualizarNumerosMenores() {
            const items = document.querySelectorAll('.menor-item');
            items.forEach((item, i) => {
                const numero = item.querySelector('.menor-numero');
                if (numero) {
                    numero.innerHTML = `<i class="fas fa-child"></i> Menor #${i + 1}`;
                }
            });
        }
        
        // Resetear la lista de menores
        function resetearListaMenores() {
            contadorMenores = 1;
            const lista = document.getElementById('listaMenores');
            lista.innerHTML = `
                <div class="menor-item" data-index="0">
                    <div class="menor-header">
                        <span class="menor-numero"><i class="fas fa-child"></i> Menor #1</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Nombre del Menor *</label>
                            <input type="text" name="menores[0][nombre]" 
                                   placeholder="Nombre completo del menor">
                        </div>
                        <div class="form-group-sm">
                            <label>Fecha de Nacimiento *</label>
                            <input type="date" name="menores[0][fecha_nacimiento]"
                                   max="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm">
                            <label>Cédula <small style="color:#95a5a6">(10 dígitos)</small></label>
                            <input type="text" name="menores[0][cedula]"
                                   placeholder="Ej: 1712345678"
                                   maxlength="10"
                                   pattern="[0-9]{10}"
                                   oninput="validarCedulaInput(this)"
                                   onblur="verificarCedulaMenor(this)">
                            <small class="field-error menor-cedula-error"></small>
                        </div>
                        <div class="form-group-sm">
                            <label>Celular <small style="color:#95a5a6">(9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix-sm">+593</span>
                                <input type="text" name="menores[0][celular]"
                                       placeholder="9XXXXXXXX"
                                       maxlength="9"
                                       pattern="[0-9]{9}"
                                       oninput="validarCelularInput(this)"
                                       onblur="verificarCelularMenor(this)">
                            </div>
                            <small class="field-error menor-celular-error"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-sm" style="flex: 1;">
                            <label>Correo Electrónico</label>
                            <input type="email" name="menores[0][email]"
                                   placeholder="correo@ejemplo.com"
                                   onblur="verificarEmailMenor(this)">
                            <small class="field-error menor-email-error"></small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Validaciones para campos del menor (opcionales)
        async function verificarCedulaMenor(input) {
            const cedula = input.value.trim();
            const formGroup = input.closest('.form-group-sm');
            const errorEl = formGroup.querySelector('.menor-cedula-error');
            
            if (cedula === '') {
                input.classList.remove('input-error', 'input-valid');
                if (errorEl) errorEl.textContent = '';
                return true;
            }
            
            if (cedula.length !== 10) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'La cédula debe tener 10 dígitos';
                return false;
            }
            
            if (!validarCedulaEcuatoriana(cedula)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'Cédula inválida';
                return false;
            }
            
            // Verificar que la cédula sea única en la base de datos
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                
                if (data.existe) {
                    input.classList.add('input-error');
                    input.classList.remove('input-valid');
                    if (errorEl) errorEl.textContent = `Cédula ya registrada (${data.estudiante.nombre})`;
                    return false;
                }
            } catch (error) {
                console.error('Error verificando cédula:', error);
            }
            
            // Verificar que no se repita con otras cédulas en el mismo formulario
            const todasCedulas = document.querySelectorAll('input[name*="[cedula]"]');
            let duplicada = false;
            todasCedulas.forEach(otroInput => {
                if (otroInput !== input && otroInput.value.trim() === cedula) {
                    duplicada = true;
                }
            });
            
            // También verificar con la cédula del representante
            const cedulaRepresentante = document.getElementById('representante_cedula');
            if (cedulaRepresentante && cedulaRepresentante.value.trim() === cedula) {
                duplicada = true;
            }
            
            if (duplicada) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'Cédula duplicada en el formulario';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (errorEl) errorEl.textContent = '';
            return true;
        }
        
        function verificarCelularMenor(input) {
            const celular = input.value.trim();
            // El input está dentro de celular-input-wrapper, así que el error está en el form-group-sm (abuelo)
            const formGroup = input.closest('.form-group-sm');
            const errorEl = formGroup.querySelector('.menor-celular-error');
            
            if (celular === '') {
                input.classList.remove('input-error', 'input-valid');
                if (errorEl) errorEl.textContent = '';
                return true;
            }
            
            // Quitar el 0 inicial si existe para validar
            let celularLimpio = celular;
            if (celularLimpio.startsWith('0')) celularLimpio = celularLimpio.substring(1);
            
            if (celularLimpio.length !== 9) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'El celular debe tener 9 dígitos';
                return false;
            }
            
            if (!celularLimpio.startsWith('9')) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'Número inválido (debe empezar con 9)';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (errorEl) errorEl.textContent = '';
            return true;
        }
        
        function verificarEmailMenor(input) {
            const email = input.value.trim();
            const formGroup = input.closest('.form-group-sm');
            const errorEl = formGroup.querySelector('.menor-email-error');
            
            if (email === '') {
                input.classList.remove('input-error', 'input-valid');
                if (errorEl) errorEl.textContent = '';
                return true;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorEl) errorEl.textContent = 'Email inválido';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (errorEl) errorEl.textContent = '';
            return true;
        }
        
        // Validaciones para representante
        async function verificarCedulaRepresentante(input) {
            const cedula = input.value.trim();
            const errorEl = document.getElementById('representante_cedula_error');
            
            if (cedula === '') {
                if (document.getElementById('es_menor').checked) {
                    input.classList.add('input-error');
                    errorEl.textContent = 'La cédula del representante es obligatoria';
                    return false;
                }
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            if (cedula.length !== 10) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'La cédula debe tener 10 dígitos';
                return false;
            }
            
            if (!validarCedulaEcuatoriana(cedula)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Cédula inválida';
                return false;
            }
            
            // Verificar que la cédula sea única en la base de datos
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                
                if (data.existe) {
                    input.classList.add('input-error');
                    input.classList.remove('input-valid');
                    errorEl.textContent = `Cédula ya registrada (${data.estudiante.nombre})`;
                    return false;
                }
            } catch (error) {
                console.error('Error verificando cédula:', error);
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }
        
        function verificarCelularRepresentante(input) {
            const celular = input.value.trim();
            const errorEl = document.getElementById('representante_celular_error');
            
            if (celular === '') {
                if (document.getElementById('es_menor').checked) {
                    input.classList.add('input-error');
                    errorEl.textContent = 'El celular del representante es obligatorio';
                    return false;
                }
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            if (celular.length !== 9) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'El celular debe tener 9 dígitos';
                return false;
            }
            
            if (!celular.startsWith('9')) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Número de celular inválido (debe empezar con 9)';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }
        
        function verificarEmailRepresentante(input) {
            const email = input.value.trim();
            const errorEl = document.getElementById('representante_email_error');
            
            if (email === '') {
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Email inválido';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }

        let busquedaTimeout;
        function buscarEstudiante(query) {
            clearTimeout(busquedaTimeout);
            const resultados = document.getElementById('resultadosBusqueda');
            
            if (query.length < 2) {
                resultados.innerHTML = '<div style="padding: 15px; color: #95a5a6; text-align: center;">Escribe al menos 2 caracteres...</div>';
                return;
            }
            
            resultados.innerHTML = '<div style="padding: 15px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            
            busquedaTimeout = setTimeout(async () => {
                try {
                    // Incluir periodo_id en la búsqueda para verificar matrícula en este período
                    let url = `api_categoria_estudiantes.php?action=search&q=${encodeURIComponent(query)}&categoria_id=${categoriaEstudiantesActual}`;
                    if (periodoEstudiantesActual) {
                        url += `&periodo_id=${periodoEstudiantesActual}`;
                    }
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.success && data.estudiantes.length > 0) {
                        let html = '';
                        data.estudiantes.forEach(est => {
                            const yaMatriculado = est.ya_matriculado;
                            html += `
                                <div class="resultado-estudiante ${yaMatriculado ? 'ya-matriculado' : ''}" 
                                     ${!yaMatriculado ? `onclick="matricularEstudiante(${est.id}, '${est.nombre}')"` : ''}>
                                    <div class="resultado-info">
                                        <strong>${est.nombre}</strong>
                                        ${est.cedula ? `<small>CI: ${est.cedula}</small>` : ''}
                                    </div>
                                    <div class="resultado-action">
                                        ${yaMatriculado ? '<span style="color: #27ae60;"><i class="fas fa-check"></i> Ya matriculado</span>' : '<i class="fas fa-plus"></i>'}
                                    </div>
                                </div>
                            `;
                        });
                        resultados.innerHTML = html;
                    } else {
                        resultados.innerHTML = `
                            <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                                No se encontraron estudiantes.<br>
                                <button onclick="mostrarFormNuevoEstudiante()" class="btn-link" style="margin-top: 10px; color: #3498db;">
                                    <i class="fas fa-user-plus"></i> Crear nuevo estudiante
                                </button>
                            </div>
                        `;
                    }
                } catch (error) {
                    resultados.innerHTML = '<div style="padding: 15px; color: #e74c3c; text-align: center;">Error en la búsqueda</div>';
                }
            }, 300);
        }
        
        async function matricularEstudiante(estudianteId, nombre) {
            if (!confirm(`¿Matricular a "${nombre}" en esta categoría?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'matricular');
                formData.append('categoria_id', categoriaEstudiantesActual);
                formData.append('estudiante_id', estudianteId);
                formData.append('fecha_matricula', new Date().toISOString().split('T')[0]);
                if (periodoEstudiantesActual) {
                    formData.append('periodo_id', periodoEstudiantesActual);
                }
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('buscarEstudianteInput').value = '';
                    document.getElementById('resultadosBusqueda').innerHTML = '';
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    // Actualizar contador en la card
                    actualizarContadorCard();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al matricular estudiante', 'error');
            }
        }
        
        function mostrarFormNuevoEstudiante() {
            // Usar la función toggle
            const form = document.getElementById('nuevoEstudianteForm');
            if (form.style.display === 'none' || form.style.display === '') {
                toggleFormNuevoEstudiante();
            }
        }
        
        // Validaciones de campos
        function validarCedulaInput(input) {
            // Solo permitir números
            input.value = input.value.replace(/[^0-9]/g, '');
        }
        
        function validarCelularInput(input) {
            // Solo permitir números
            input.value = input.value.replace(/[^0-9]/g, '');
        }
        
        async function verificarCedula(input) {
            const cedula = input.value.trim();
            const errorEl = document.getElementById('cedula_error');
            
            if (cedula === '') {
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            if (cedula.length !== 10) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'La cédula debe tener 10 dígitos';
                return false;
            }
            
            // Validar cédula ecuatoriana
            if (!validarCedulaEcuatoriana(cedula)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Cédula inválida';
                return false;
            }
            
            // Verificar que la cédula sea única en la base de datos
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                
                if (data.existe) {
                    input.classList.add('input-error');
                    input.classList.remove('input-valid');
                    errorEl.textContent = `Cédula ya registrada (${data.estudiante.nombre})`;
                    return false;
                }
            } catch (error) {
                console.error('Error verificando cédula:', error);
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }
        
        function validarCedulaEcuatoriana(cedula) {
            if (!/^[0-9]{10}$/.test(cedula)) return false;
            
            const provincia = parseInt(cedula.substring(0, 2));
            if (provincia < 1 || provincia > 24) return false;
            
            const tercerDigito = parseInt(cedula.charAt(2));
            if (tercerDigito > 6) return false;
            
            // Algoritmo de validación módulo 10
            const coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
            let suma = 0;
            
            for (let i = 0; i < 9; i++) {
                let valor = parseInt(cedula.charAt(i)) * coeficientes[i];
                if (valor > 9) valor -= 9;
                suma += valor;
            }
            
            const digitoVerificador = (10 - (suma % 10)) % 10;
            return digitoVerificador === parseInt(cedula.charAt(9));
        }
        
        function verificarCelular(input) {
            const celular = input.value.trim();
            const errorEl = document.getElementById('celular_error');
            
            if (celular === '') {
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            // Validar que tenga exactamente 9 dígitos (sin el código de país)
            if (celular.length !== 9) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'El celular debe tener 9 dígitos';
                return false;
            }
            
            // Validar que empiece con 9 (celular ecuatoriano)
            if (!celular.startsWith('9')) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Número de celular inválido (debe empezar con 9)';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }
        
        function verificarEmail(input) {
            const email = input.value.trim();
            const errorEl = document.getElementById('email_error');
            
            if (email === '') {
                input.classList.remove('input-error', 'input-valid');
                errorEl.textContent = '';
                return true;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                errorEl.textContent = 'Email inválido';
                return false;
            }
            
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            errorEl.textContent = '';
            return true;
        }
        
        async function guardarNuevoEstudiante(event) {
            event.preventDefault();
            
            const esMenor = document.getElementById('es_menor').checked;
            let errores = false;
            
            if (esMenor) {
                // Validar datos del representante
                const repNombre = document.getElementById('representante_nombre');
                const repCedula = document.getElementById('representante_cedula');
                const repCelular = document.getElementById('representante_celular');
                const repEmail = document.getElementById('representante_email');
                
                if (!repNombre.value.trim()) {
                    showNotification('El nombre del representante es obligatorio', 'warning');
                    repNombre.focus();
                    return;
                }
                
                // verificarCedulaRepresentante es async
                const cedulaRepValida = await verificarCedulaRepresentante(repCedula);
                if (!cedulaRepValida) errores = true;
                if (!verificarCelularRepresentante(repCelular)) errores = true;
                if (!verificarEmailRepresentante(repEmail)) errores = true;
                
                // Validar que hay al menos un menor con datos completos
                const menoresItems = document.querySelectorAll('.menor-item');
                if (menoresItems.length === 0) {
                    showNotification('Debe agregar al menos un menor', 'warning');
                    return;
                }
                
                // Validar cada menor
                let menoresValidos = [];
                for (const item of menoresItems) {
                    const nombreInput = item.querySelector('input[name*="[nombre]"]');
                    const fechaInput = item.querySelector('input[name*="[fecha_nacimiento]"]');
                    const cedulaInput = item.querySelector('input[name*="[cedula]"]');
                    const celularInput = item.querySelector('input[name*="[celular]"]');
                    const emailInput = item.querySelector('input[name*="[email]"]');
                    
                    if (!nombreInput.value.trim()) {
                        showNotification('El nombre del menor es obligatorio', 'warning');
                        nombreInput.focus();
                        return;
                    }
                    if (!fechaInput.value) {
                        showNotification('La fecha de nacimiento del menor es obligatoria', 'warning');
                        fechaInput.focus();
                        return;
                    }
                    
                    // Validar campos opcionales del menor (solo si tienen valor)
                    if (cedulaInput && cedulaInput.value.trim()) {
                        const cedulaValida = await verificarCedulaMenor(cedulaInput);
                        if (!cedulaValida) {
                            errores = true;
                            cedulaInput.focus();
                        }
                    }
                    if (celularInput && celularInput.value.trim() && !verificarCelularMenor(celularInput)) {
                        errores = true;
                        celularInput.focus();
                    }
                    if (emailInput && emailInput.value.trim() && !verificarEmailMenor(emailInput)) {
                        errores = true;
                        emailInput.focus();
                    }
                    
                    // Formatear celular del menor si existe
                    let celularMenor = celularInput ? celularInput.value.trim() : '';
                    if (celularMenor) {
                        if (celularMenor.startsWith('0')) celularMenor = celularMenor.substring(1);
                        celularMenor = '+593' + celularMenor;
                    }
                    
                    menoresValidos.push({
                        nombre: nombreInput.value.trim(),
                        fecha_nacimiento: fechaInput.value,
                        cedula: cedulaInput ? cedulaInput.value.trim() : '',
                        celular: celularMenor,
                        email: emailInput ? emailInput.value.trim() : ''
                    });
                }
                
                if (errores) {
                    showNotification('Por favor corrige los errores en el formulario', 'error');
                    return;
                }
                
                // Formatear celular del representante
                let repCelularVal = repCelular.value.trim();
                if (repCelularVal.startsWith('0')) repCelularVal = repCelularVal.substring(1);
                repCelularVal = '+593' + repCelularVal;
                
                // Enviar datos de múltiples menores
                const datosEnvio = {
                    action: 'crear_menores_con_representante',
                    categoria_id: categoriaEstudiantesActual,
                    periodo_id: periodoEstudiantesActual,
                    representante: {
                        nombre: repNombre.value.trim(),
                        cedula: repCedula.value.trim(),
                        celular: repCelularVal,
                        email: repEmail.value.trim()
                    },
                    menores: menoresValidos
                };
                
                try {
                    const response = await fetch('api_categoria_estudiantes.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(datosEnvio)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toggleFormNuevoEstudiante();
                        document.getElementById('buscarEstudianteInput').value = '';
                        document.getElementById('resultadosBusqueda').innerHTML = '';
                        cargarEstudiantesCategoria(categoriaEstudiantesActual);
                        actualizarContadorCard();
                        const cantidad = data.cantidad || menoresValidos.length;
                        showNotification(`${cantidad} estudiante(s) creado(s) y matriculado(s) correctamente`, 'success');
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showNotification('Error al crear estudiantes', 'error');
                }
                
            } else {
                // Validar campos del estudiante (mayor de edad)
                const cedulaInput = document.getElementById('nuevo_cedula');
                const celularInput = document.getElementById('nuevo_celular');
                const emailInput = document.getElementById('nuevo_email');
                
                // verificarCedula es async, necesitamos await
                const cedulaValida = await verificarCedula(cedulaInput);
                if (!cedulaValida) errores = true;
                if (!verificarCelular(celularInput)) errores = true;
                if (!verificarEmail(emailInput)) errores = true;
                
                if (errores) {
                    showNotification('Por favor corrige los errores en el formulario', 'error');
                    return;
                }
                
                const formData = new FormData(event.target);
                formData.append('action', 'crear_y_matricular');
                formData.append('categoria_id', categoriaEstudiantesActual);
                if (periodoEstudiantesActual) {
                    formData.append('periodo_id', periodoEstudiantesActual);
                }
                
                // Formatear celular con código de país
                let celular = celularInput.value.trim();
                if (celular) {
                    if (celular.startsWith('0')) celular = celular.substring(1);
                    formData.set('celular', '+593' + celular);
                }
                
                try {
                    const response = await fetch('api_categoria_estudiantes.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toggleFormNuevoEstudiante();
                        document.getElementById('buscarEstudianteInput').value = '';
                        document.getElementById('resultadosBusqueda').innerHTML = '';
                        cargarEstudiantesCategoria(categoriaEstudiantesActual);
                        actualizarContadorCard();
                        showNotification('Estudiante creado y matriculado correctamente', 'success');
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Error al crear estudiante', 'error');
                }
            }
        }
        
        async function quitarEstudianteCategoria(id, nombre) {
            if (!confirm(`¿Quitar a "${nombre}" de esta categoría?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'quitar');
                formData.append('id', id);
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    actualizarContadorCard();
                    showNotification('Estudiante quitado de la categoría', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al quitar estudiante', 'error');
            }
        }
        
        // ========== FUNCIONES DE SELECCIÓN MASIVA ==========
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.checkbox-estudiante');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
                const row = cb.closest('tr');
                if (checkbox.checked) {
                    row.classList.add('row-selected');
                } else {
                    row.classList.remove('row-selected');
                }
            });
            actualizarSeleccion();
        }
        
        function actualizarSeleccion() {
            const checkboxes = document.querySelectorAll('.checkbox-estudiante:checked');
            const total = checkboxes.length;
            
            document.getElementById('contadorSeleccionados').textContent = total;
            
            if (total > 0) {
                document.getElementById('accionesMasivas').style.display = 'flex';
            } else {
                document.getElementById('accionesMasivas').style.display = 'none';
            }
            
            // Actualizar estado visual de las filas
            document.querySelectorAll('.checkbox-estudiante').forEach(cb => {
                const row = cb.closest('tr');
                if (cb.checked) {
                    row.classList.add('row-selected');
                } else {
                    row.classList.remove('row-selected');
                }
            });
            
            // Actualizar checkbox "seleccionar todos"
            const allCheckboxes = document.querySelectorAll('.checkbox-estudiante');
            const selectAll = document.getElementById('selectAllEstudiantes');
            if (allCheckboxes.length > 0 && total === allCheckboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else if (total > 0) {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }
        
        function ocultarAccionesMasivas() {
            document.getElementById('accionesMasivas').style.display = 'none';
            document.getElementById('contadorSeleccionados').textContent = '0';
        }
        
        function cancelarSeleccion() {
            document.querySelectorAll('.checkbox-estudiante').forEach(cb => {
                cb.checked = false;
                cb.closest('tr').classList.remove('row-selected');
            });
            document.getElementById('selectAllEstudiantes').checked = false;
            document.getElementById('selectAllEstudiantes').indeterminate = false;
            ocultarAccionesMasivas();
        }
        
        function obtenerIdsSeleccionados() {
            const checkboxes = document.querySelectorAll('.checkbox-estudiante:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        async function cambiarEstadoMasivo(nuevoEstado) {
            const ids = obtenerIdsSeleccionados();
            if (ids.length === 0) return;
            
            const estadoLabel = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
            if (!confirm(`¿${estadoLabel.charAt(0).toUpperCase() + estadoLabel.slice(1)} ${ids.length} estudiante(s)?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'cambiar_estado_masivo');
                formData.append('ids', JSON.stringify(ids));
                formData.append('estado', nuevoEstado);
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    cancelarSeleccion();
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al cambiar estado', 'error');
            }
        }
        
        async function eliminarMasivo() {
            const ids = obtenerIdsSeleccionados();
            if (ids.length === 0) return;
            
            if (!confirm(`¿Quitar ${ids.length} estudiante(s) de esta categoría? Esta acción no se puede deshacer.`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'eliminar_masivo');
                formData.append('ids', JSON.stringify(ids));
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    cancelarSeleccion();
                    actualizarContadorCard();
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al quitar estudiantes', 'error');
            }
        }
        
        function actualizarContadorCard() {
            // Recargar la página para actualizar contadores (simplificado)
            // En una versión más avanzada se podría actualizar solo el contador
        }
        
        // Modal para cargar archivo de estudiantes
        function mostrarCargaArchivo() {
            document.getElementById('cargaArchivoSection').style.display = 'block';
        }
        
        function ocultarCargaArchivo() {
            document.getElementById('cargaArchivoSection').style.display = 'none';
            document.getElementById('archivoEstudiantes').value = '';
            document.getElementById('previewCargaEstudiantes').innerHTML = '';
        }
        
        async function previsualizarArchivoEstudiantes() {
            const fileInput = document.getElementById('archivoEstudiantes');
            if (!fileInput.files.length) {
                showNotification('Selecciona un archivo', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'preview_file');
            formData.append('file', fileInput.files[0]);
            formData.append('categoria_id', categoriaEstudiantesActual);
            
            const preview = document.getElementById('previewCargaEstudiantes');
            preview.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Procesando archivo...</div>';
            
            try {
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Contar menores y representantes únicos
                    const menores = data.estudiantes.filter(e => e.es_menor && (e.es_menor.toLowerCase() === 'si' || e.es_menor.toLowerCase() === 'sí' || e.es_menor === '1'));
                    const representantes = new Set(menores.map(e => e.representante_cedula).filter(c => c));
                    
                    let html = `
                        <div style="margin-bottom: 15px; padding: 10px; background: #e8f5e9; border-radius: 8px;">
                            <strong>${data.total}</strong> estudiantes | 
                            <strong>${data.nuevos}</strong> nuevos | 
                            <strong>${data.existentes}</strong> existentes
                            ${menores.length > 0 ? ` | <span style="color:#8e44ad"><i class="fas fa-child"></i> ${menores.length} menores</span> | <span style="color:#8e44ad"><i class="fas fa-user-tie"></i> ${representantes.size} representante(s)</span>` : ''}
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="mini-table">
                                <thead>
                                    <tr>
                                        <th style="width:30px">#</th>
                                        <th>Estudiante</th>
                                        <th>Fecha Nac.</th>
                                        <th>Tipo</th>
                                        <th>Contacto / Representante</th>
                                        <th style="width:70px">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.estudiantes.forEach((est, i) => {
                        const esMenor = est.es_menor && (est.es_menor.toLowerCase() === 'si' || est.es_menor.toLowerCase() === 'sí' || est.es_menor === '1');
                        const tipoLabel = esMenor ? 
                            '<span style="color:#8e44ad"><i class="fas fa-child"></i> Menor</span>' : 
                            '<span style="color:#27ae60"><i class="fas fa-user"></i> Mayor</span>';
                        
                        let contactoInfo = '';
                        if (esMenor) {
                            contactoInfo = `
                                <div style="font-size:11px; color:#8e44ad;">
                                    <strong><i class="fas fa-user-tie"></i> ${est.representante_nombre || '-'}</strong><br>
                                    CI: ${est.representante_cedula || '-'} | 
                                    Cel: ${est.representante_celular || '-'}
                                    ${est.representante_email ? '<br>' + est.representante_email : ''}
                                </div>
                            `;
                        } else {
                            contactoInfo = `
                                <div style="font-size:11px;">
                                    CI: ${est.cedula || '-'} | 
                                    Cel: ${est.celular || '-'}
                                    ${est.email ? '<br>' + est.email : ''}
                                </div>
                            `;
                        }
                        
                        html += `<tr style="${esMenor ? 'background:#faf5fc;' : ''}">
                            <td style="text-align:center">${i + 1}</td>
                            <td><strong>${est.nombre}</strong></td>
                            <td style="font-size:11px">${est.fecha_nacimiento || '-'}</td>
                            <td>${tipoLabel}</td>
                            <td>${contactoInfo}</td>
                            <td>${est.es_nuevo ? '<span style="color:#27ae60; font-size:11px"><i class="fas fa-plus-circle"></i> Nuevo</span>' : '<span style="color:#3498db; font-size:11px"><i class="fas fa-check-circle"></i> Existe</span>'}</td>
                        </tr>`;
                    });
                    
                    html += `</tbody></table></div>
                        <button onclick="procesarArchivoEstudiantes()" class="btn-submit" style="margin-top: 15px; width: 100%;">
                            <i class="fas fa-check"></i> Matricular ${data.total} Estudiantes
                        </button>
                    `;
                    
                    preview.innerHTML = html;
                } else {
                    preview.innerHTML = `<div style="color: #e74c3c; padding: 15px; text-align: center;">${data.message}</div>`;
                }
            } catch (error) {
                preview.innerHTML = '<div style="color: #e74c3c; padding: 15px; text-align: center;">Error al procesar archivo</div>';
            }
        }
        
        async function procesarArchivoEstudiantes() {
            const fileInput = document.getElementById('archivoEstudiantes');
            if (!fileInput.files.length) return;
            
            const formData = new FormData();
            formData.append('action', 'procesar_archivo');
            formData.append('file', fileInput.files[0]);
            formData.append('categoria_id', categoriaEstudiantesActual);
            if (periodoEstudiantesActual) {
                formData.append('periodo_id', periodoEstudiantesActual);
            }
            
            try {
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    ocultarCargaArchivo();
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    actualizarContadorCard();
                    showNotification(`${data.matriculados} estudiantes matriculados correctamente`, 'success');
                    location.reload(); // Recargar para actualizar contadores
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al procesar archivo', 'error');
            }
        }
        
        // Cerrar modal de estudiantes al hacer clic fuera
        document.getElementById('estudiantesModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarEstudiantesModal();
            }
        });
        
        // Cerrar modal de editar estudiante al hacer clic fuera
        document.getElementById('editarEstudianteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarEditarEstudianteModal();
            }
        });
        
        // ============== Funciones para editar estudiante ==============
        
        let matriculaEditandoId = null;
        let estudianteEditandoId = null;
        
        async function editarEstudianteCategoria(matriculaId, estudianteId) {
            matriculaEditandoId = matriculaId;
            estudianteEditandoId = estudianteId;
            
            try {
                const formData = new FormData();
                formData.append('action', 'obtener_estudiante');
                formData.append('matricula_id', matriculaId);
                formData.append('estudiante_id', estudianteId);
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const est = data.estudiante;
                    
                    // Llenar campos del formulario
                    document.getElementById('edit_estudiante_id').value = est.id;
                    document.getElementById('edit_matricula_id').value = matriculaId;
                    document.getElementById('edit_nombre').value = est.nombre || '';
                    document.getElementById('edit_fecha_nacimiento').value = est.fecha_nacimiento || '';
                    document.getElementById('edit_email').value = est.email || '';
                    document.getElementById('edit_estado').value = data.matricula.estado || 'activo';
                    document.getElementById('edit_fecha_matricula').value = data.matricula.fecha_matricula || '';
                    
                    // Formatear cédula
                    document.getElementById('edit_cedula').value = est.cedula || '';
                    
                    // Formatear celular (quitar +593)
                    let celular = est.celular || '';
                    if (celular.startsWith('+593')) {
                        celular = celular.substring(4);
                    }
                    document.getElementById('edit_celular').value = celular;
                    
                    // Verificar si es menor
                    const esMenor = est.es_menor == 1;
                    
                    if (esMenor) {
                        document.getElementById('edit_info_menor').style.display = 'block';
                        document.getElementById('edit_datos_mayor').style.display = 'none';
                        document.getElementById('edit_datos_representante').style.display = 'block';
                        
                        // Datos del representante
                        document.getElementById('edit_representante_nombre').value = est.representante_nombre || '';
                        document.getElementById('edit_representante_cedula').value = est.representante_cedula || '';
                        document.getElementById('edit_representante_email').value = est.representante_email || '';
                        document.getElementById('edit_representante_fecha_nacimiento').value = est.representante_fecha_nacimiento || '';
                        
                        // Formatear celular del representante
                        let repCelular = est.representante_celular || '';
                        if (repCelular.startsWith('+593')) {
                            repCelular = repCelular.substring(4);
                        }
                        document.getElementById('edit_representante_celular').value = repCelular;
                    } else {
                        document.getElementById('edit_info_menor').style.display = 'none';
                        document.getElementById('edit_datos_mayor').style.display = 'block';
                        document.getElementById('edit_datos_representante').style.display = 'none';
                    }
                    
                    // Mostrar modal
                    document.getElementById('editarEstudianteModal').style.display = 'flex';
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showNotification('Error al cargar datos del estudiante', 'error');
            }
        }
        
        function cerrarEditarEstudianteModal() {
            document.getElementById('editarEstudianteModal').style.display = 'none';
            document.getElementById('formEditarEstudiante').reset();
            matriculaEditandoId = null;
            estudianteEditandoId = null;
        }
        
        async function guardarEdicionEstudiante(event) {
            event.preventDefault();
            
            const esMenor = document.getElementById('edit_info_menor').style.display !== 'none';
            let errores = false;
            
            // Obtener datos del formulario
            const estudianteId = document.getElementById('edit_estudiante_id').value;
            const matriculaId = document.getElementById('edit_matricula_id').value;
            const nombre = document.getElementById('edit_nombre').value.trim();
            const fechaNacimiento = document.getElementById('edit_fecha_nacimiento').value;
            const estado = document.getElementById('edit_estado').value;
            
            if (!nombre) {
                showNotification('El nombre es obligatorio', 'warning');
                document.getElementById('edit_nombre').focus();
                return;
            }
            
            let datosEnvio = {
                action: 'actualizar_estudiante',
                estudiante_id: estudianteId,
                matricula_id: matriculaId,
                nombre: nombre,
                fecha_nacimiento: fechaNacimiento,
                estado: estado
            };
            
            if (esMenor) {
                // Datos del representante
                const repNombre = document.getElementById('edit_representante_nombre').value.trim();
                const repCedula = document.getElementById('edit_representante_cedula').value.trim();
                let repCelular = document.getElementById('edit_representante_celular').value.trim();
                const repEmail = document.getElementById('edit_representante_email').value.trim();
                const repFechaNacimiento = document.getElementById('edit_representante_fecha_nacimiento').value;
                
                if (!repNombre) {
                    showNotification('El nombre del representante es obligatorio', 'warning');
                    document.getElementById('edit_representante_nombre').focus();
                    return;
                }
                
                // Formatear celular
                if (repCelular) {
                    if (repCelular.startsWith('0')) repCelular = repCelular.substring(1);
                    repCelular = '+593' + repCelular;
                }
                
                datosEnvio.representante_nombre = repNombre;
                datosEnvio.representante_cedula = repCedula;
                datosEnvio.representante_celular = repCelular;
                datosEnvio.representante_email = repEmail;
                datosEnvio.representante_fecha_nacimiento = repFechaNacimiento;
            } else {
                // Datos de contacto del estudiante
                const cedula = document.getElementById('edit_cedula').value.trim();
                let celular = document.getElementById('edit_celular').value.trim();
                const email = document.getElementById('edit_email').value.trim();
                
                // Formatear celular
                if (celular) {
                    if (celular.startsWith('0')) celular = celular.substring(1);
                    celular = '+593' + celular;
                }
                
                datosEnvio.cedula = cedula;
                datosEnvio.celular = celular;
                datosEnvio.email = email;
            }
            
            try {
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datosEnvio)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cerrarEditarEstudianteModal();
                    cargarEstudiantesCategoria(categoriaEstudiantesActual);
                    showNotification('Estudiante actualizado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showNotification('Error al actualizar estudiante', 'error');
            }
        }
        
        // ==========================================
        // MODAL GENERAR CERTIFICADOS
        // ==========================================
        
        let categoriaGenerarActual = null;
        let grupoGenerarActual = null;
        let periodoGenerarActual = null;
        let estudiantesParaGenerar = [];
        let certificadosGenerados = [];
        
        async function abrirGenerarCertificados(categoriaId, categoriaNombre, categoriaColor, grupoId, periodoId) {
            categoriaGenerarActual = categoriaId;
            grupoGenerarActual = grupoId;
            periodoGenerarActual = periodoId;
            estudiantesParaGenerar = [];
            certificadosGenerados = [];
            
            // Actualizar título y estilo del modal
            document.getElementById('generarModalTitle').innerHTML = `<i class="fas fa-certificate"></i> Generar Certificados - <?= htmlspecialchars($grupo['nombre']) ?> - ${categoriaNombre}`;
            
            // Aplicar color al header
            const modalHeader = document.getElementById('generarModalHeader');
            modalHeader.style.background = `linear-gradient(135deg, ${categoriaColor}dd 0%, ${categoriaColor} 100%)`;
            
            // Resetear estado
            document.getElementById('generarResultados').classList.remove('active');
            document.getElementById('generarProgress').classList.remove('active');
            document.getElementById('selectAllGenerar').checked = false;
            document.getElementById('genRazon').value = '';
            document.getElementById('genFecha').value = '<?= date('Y-m-d') ?>';
            
            // Mostrar modal
            document.getElementById('generarCertificadosModal').classList.add('active');
            
            // Cargar estudiantes
            await cargarEstudiantesParaGenerar(categoriaId);
        }
        
        function cerrarGenerarModal() {
            document.getElementById('generarCertificadosModal').classList.remove('active');
            // Resetear estado del modal
            document.getElementById('generarResultados').classList.remove('active');
            document.getElementById('plantillaNoConfigurada').classList.remove('active');
            document.getElementById('erroresDetalleSection').style.display = 'none';
            document.getElementById('generarProgress').classList.remove('active');
            document.getElementById('btnGenerarCerts').disabled = true;
        }
        
        async function cargarEstudiantesParaGenerar(categoriaId) {
            const tbody = document.getElementById('generarListBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando estudiantes...</p>
                    </td>
                </tr>
            `;
            
            try {
                const response = await fetch(`api_generar_certificados.php?action=obtener_estudiantes&categoria_id=${categoriaId}&periodo_id=${periodoGenerarActual}`);
                const data = await response.json();
                
                if (data.success) {
                    estudiantesParaGenerar = data.estudiantes;
                    
                    // Actualizar estadísticas
                    const generados = data.estudiantes.filter(e => e.certificado_id).length;
                    const pendientes = data.total - generados;
                    
                    document.getElementById('genTotalEstudiantes').textContent = data.total;
                    document.getElementById('genCertificadosGenerados').textContent = generados;
                    document.getElementById('genPendientes').textContent = pendientes;
                    
                    // Renderizar tabla
                    if (data.estudiantes.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    <i class="fas fa-user-slash fa-2x"></i>
                                    <p>No hay estudiantes matriculados en esta categoría</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    let html = '';
                    data.estudiantes.forEach((est, idx) => {
                        const yaGenerado = est.certificado_id ? true : false;
                        
                        // Formatear historial de fechas de generación
                        let fechaGeneracionHtml = '-';
                        let fechasArray = [];
                        
                        if (est.certificado_fechas_generacion) {
                            try {
                                fechasArray = JSON.parse(est.certificado_fechas_generacion);
                            } catch(e) {
                                fechasArray = [];
                            }
                        }
                        
                        // Si no hay historial pero hay certificado_fecha, usar esa
                        if (fechasArray.length === 0 && est.certificado_fecha) {
                            fechasArray = [est.certificado_fecha];
                        }
                        
                        if (fechasArray.length > 0) {
                            // Mostrar la última fecha y un indicador de cuántas veces se generó
                            const ultimaFecha = new Date(fechasArray[fechasArray.length - 1]);
                            const fechaFormateada = ultimaFecha.toLocaleDateString('es-EC', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            // Crear tooltip con todas las fechas
                            const todasFechasHtml = fechasArray.map((f, i) => {
                                const fecha = new Date(f);
                                return `${i + 1}. ${fecha.toLocaleDateString('es-EC', {
                                    day: '2-digit', month: '2-digit', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                })}`;
                            }).join('&#10;');
                            
                            const badge = fechasArray.length > 1 
                                ? `<span class="badge-generaciones" title="Generado ${fechasArray.length} veces&#10;${todasFechasHtml}">${fechasArray.length}x</span>` 
                                : '';
                            
                            fechaGeneracionHtml = `<i class="fas fa-calendar-check"></i> ${fechaFormateada} ${badge}`;
                        }
                        
                        html += `
                            <tr class="${yaGenerado ? 'ya-generado' : ''}" data-estudiante-id="${est.id}">
                                <td>
                                    <input type="checkbox" class="check-estudiante-gen" 
                                           data-id="${est.id}" 
                                           onchange="actualizarContadorGenerar()">
                                </td>
                                <td>${idx + 1}</td>
                                <td>
                                    <strong>${est.nombre}</strong>
                                    ${est.es_menor ? '<small style="color:#9b59b6;"><i class="fas fa-child"></i> Menor</small>' : ''}
                                </td>
                                <td>${est.cedula || '-'}</td>
                                <td>
                                    ${yaGenerado 
                                        ? `<span class="badge-generado"><i class="fas fa-check"></i> ${est.certificado_codigo}</span>` 
                                        : '<span class="badge-pendiente"><i class="fas fa-clock"></i> Pendiente</span>'
                                    }
                                </td>
                                <td style="font-size: 12px; color: ${yaGenerado ? '#27ae60' : '#95a5a6'};">
                                    ${yaGenerado ? fechaGeneracionHtml : '-'}
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-destacado ${est.es_destacado ? 'destacado-activo' : ''}" 
                                            data-id="${est.id}" 
                                            onclick="toggleDestacado(this)" 
                                            title="${est.es_destacado ? 'Quitar destacado' : 'Marcar como destacado'}">
                                        <i class="${est.es_destacado ? 'fas' : 'far'} fa-star"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tbody.innerHTML = html;
                    actualizarContadorGenerar();
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #e74c3c;">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                                <p>Error: ${data.message}</p>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error(error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                            <p>Error al cargar estudiantes</p>
                        </td>
                    </tr>
                `;
            }
        }
        
        // Toggle para marcar/desmarcar estudiante destacado
        function toggleDestacado(btn) {
            const isDestacado = btn.classList.contains('destacado-activo');
            const icon = btn.querySelector('i');
            
            if (isDestacado) {
                btn.classList.remove('destacado-activo');
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.title = 'Marcar como destacado';
            } else {
                btn.classList.add('destacado-activo');
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.title = 'Quitar destacado';
            }
        }
        
        function toggleSelectAllGenerar(checkbox) {
            const checks = document.querySelectorAll('.check-estudiante-gen');
            checks.forEach(c => c.checked = checkbox.checked);
            
            // Sincronizar ambos checkboxes de "seleccionar todos"
            const headerCheckbox = document.getElementById('selectAllGenerarHeader');
            const barCheckbox = document.getElementById('selectAllGenerar');
            if (headerCheckbox) headerCheckbox.checked = checkbox.checked;
            if (barCheckbox) barCheckbox.checked = checkbox.checked;
            
            actualizarContadorGenerar();
        }
        
        function actualizarContadorGenerar() {
            const checks = document.querySelectorAll('.check-estudiante-gen:checked');
            const allChecks = document.querySelectorAll('.check-estudiante-gen');
            const count = checks.length;
            document.getElementById('seleccionadosCount').textContent = count;
            document.getElementById('btnGenerarCerts').disabled = count === 0;
            
            // Actualizar estado de checkboxes "seleccionar todos"
            const allSelected = allChecks.length > 0 && checks.length === allChecks.length;
            const headerCheckbox = document.getElementById('selectAllGenerarHeader');
            const barCheckbox = document.getElementById('selectAllGenerar');
            if (headerCheckbox) headerCheckbox.checked = allSelected;
            if (barCheckbox) barCheckbox.checked = allSelected;
        }
        
        async function generarCertificadosBatch() {
            const checks = document.querySelectorAll('.check-estudiante-gen:checked');
            if (checks.length === 0) {
                showNotification('Selecciona al menos un estudiante', 'warning');
                return;
            }
            
            // Verificar que tenemos la categoría seleccionada
            if (!categoriaGenerarActual) {
                showNotification('Error: No se ha seleccionado una categoría', 'error');
                return;
            }
            
            // Obtener IDs y estado de destacado para cada estudiante seleccionado
            const estudiantesData = Array.from(checks).map(c => {
                const estudianteId = parseInt(c.dataset.id);
                const fila = c.closest('tr');
                const btnDestacado = fila.querySelector('.btn-destacado');
                const esDestacado = btnDestacado ? btnDestacado.classList.contains('destacado-activo') : false;
                return {
                    id: estudianteId,
                    es_destacado: esDestacado
                };
            });
            
            const estudiantesIds = estudiantesData.map(e => e.id);
            const fecha = document.getElementById('genFecha').value;
            const razon = document.getElementById('genRazon').value.trim();
            
            // Mostrar progreso
            document.getElementById('generarProgress').classList.add('active');
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressText').textContent = `Generando certificados... (0/${estudiantesIds.length})`;
            
            // Deshabilitar botón
            document.getElementById('btnGenerarCerts').disabled = true;
            
            try {
                const requestData = {
                    action: 'generar_batch',
                    categoria_id: categoriaGenerarActual,
                    grupo_id: grupoGenerarActual,
                    estudiantes_ids: estudiantesIds,
                    estudiantes_data: estudiantesData,
                    fecha: fecha,
                    razon: razon || null
                };
                
                console.log('Enviando datos para generar certificados:', requestData);
                
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                });
                
                const data = await response.json();
                
                console.log('Respuesta del servidor:', data);
                
                // Ocultar progreso
                document.getElementById('generarProgress').classList.remove('active');
                
                // Verificar si es error de plantilla no configurada
                if (data.error_type === 'PLANTILLA_NO_CONFIGURADA') {
                    // Mostrar notificación
                    showNotification('⚠️ No hay plantilla configurada. Debe configurar una plantilla antes de generar certificados.', 'error');
                    // Mostrar mensaje específico de plantilla no configurada en el modal
                    document.getElementById('plantillaNoConfigurada').classList.add('active');
                    document.getElementById('generarResultados').classList.remove('active');
                    document.getElementById('btnGenerarCerts').disabled = false;
                    return;
                }
                
                // Verificar si la respuesta fue exitosa
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Error en la respuesta del servidor');
                }
                
                // Actualizar progreso
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressText').textContent = 'Completado';
                
                // Guardar certificados generados
                certificadosGenerados = (data.resultados || []).filter(r => r.success);
                
                // Mostrar resultados
                document.getElementById('generarResultados').classList.add('active');
                document.getElementById('plantillaNoConfigurada').classList.remove('active');
                
                document.getElementById('resExitosos').textContent = data.resumen?.exitosos || 0;
                document.getElementById('resErrores').textContent = data.resumen?.errores || 0;
                
                // Actualizar estilo del header según resultados
                const resultHeader = document.querySelector('.resultado-header');
                const resultHeaderTitle = resultHeader.querySelector('h4');
                resultHeader.classList.remove('con-errores', 'solo-errores');
                
                if (data.resumen?.errores > 0 && data.resumen?.exitosos > 0) {
                    // Hubo exitosos y errores
                    resultHeader.classList.add('con-errores');
                    resultHeaderTitle.innerHTML = '<i class="fas fa-exclamation-circle"></i> Generación Completada con Advertencias';
                } else if (data.resumen?.errores > 0 && data.resumen?.exitosos === 0) {
                    // Solo hubo errores
                    resultHeader.classList.add('solo-errores');
                    resultHeaderTitle.innerHTML = '<i class="fas fa-times-circle"></i> Error en la Generación';
                } else {
                    // Todo exitoso
                    resultHeaderTitle.innerHTML = '<i class="fas fa-check-circle"></i> Generación Completada';
                }
                
                // Mostrar u ocultar sección de descargas según exitosos
                const descargasSection = document.getElementById('descargasSection');
                if (descargasSection) {
                    descargasSection.style.display = (data.resumen?.exitosos > 0) ? 'block' : 'none';
                }
                
                // Manejar errores detallados
                const erroresSection = document.getElementById('erroresDetalleSection');
                const erroresLista = document.getElementById('erroresLista');
                const erroresTitulo = document.getElementById('erroresTitulo');
                const erroresDescripcion = document.getElementById('erroresDescripcion');
                
                if (data.resumen?.errores > 0 && data.resultados) {
                    const errores = data.resultados.filter(r => !r.success);
                    
                    // Verificar si hay un error común (como plantilla no configurada)
                    if (data.resumen?.error_comun === 'PLANTILLA_NO_CONFIGURADA') {
                        erroresTitulo.textContent = 'Plantilla no configurada';
                        erroresDescripcion.textContent = 'Los certificados no se pudieron generar porque no hay una plantilla activa configurada. Vaya a la pestaña "Plantillas" para configurar una.';
                    } else {
                        erroresTitulo.textContent = `${errores.length} certificado${errores.length > 1 ? 's' : ''} no se ${errores.length > 1 ? 'pudieron' : 'pudo'} generar`;
                        
                        // Analizar errores por tipo
                        const tiposError = data.resumen?.errores_por_tipo || {};
                        const tiposArr = Object.entries(tiposError);
                        if (tiposArr.length === 1) {
                            erroresDescripcion.textContent = tiposArr[0][0];
                        } else if (tiposArr.length > 1) {
                            erroresDescripcion.textContent = 'Se encontraron diferentes tipos de errores. Revise el detalle a continuación.';
                        } else {
                            erroresDescripcion.textContent = 'Revise el detalle de errores a continuación.';
                        }
                    }
                    
                    // Mostrar lista de errores
                    let listaHtml = '';
                    errores.forEach(err => {
                        listaHtml += `
                            <div class="error-item">
                                <span class="nombre"><i class="fas fa-user"></i> ${err.nombre}</span>
                                <span class="error-msg">${err.error || 'Error desconocido'}</span>
                            </div>
                        `;
                    });
                    erroresLista.innerHTML = listaHtml;
                    erroresSection.style.display = 'block';
                } else {
                    erroresSection.style.display = 'none';
                }
                
                // Recargar lista
                cargarEstudiantesParaGenerar(categoriaGenerarActual);
                
            } catch (error) {
                console.error('Error generando certificados:', error);
                showNotification('Error al generar certificados: ' + error.message, 'error');
                document.getElementById('generarProgress').classList.remove('active');
                document.getElementById('btnGenerarCerts').disabled = false;
            }
        }
        
        // Función para ir a la pestaña de plantillas
        function irAPlantillas() {
            cerrarGenerarModal();
            // Activar la pestaña de plantillas
            const tabPlantillas = document.querySelector('.tab[data-tab="plantillas"]');
            if (tabPlantillas) {
                tabPlantillas.click();
            }
        }
        
        async function descargarTodosPDF() {
            if (certificadosGenerados.length === 0) {
                showNotification('No hay certificados para descargar', 'warning');
                return;
            }
            
            // Mostrar indicador de carga
            document.getElementById('generarProgress').classList.add('active');
            document.getElementById('progressBar').style.width = '50%';
            document.getElementById('progressText').textContent = 'Preparando PDF para descarga...';
            
            try {
                const codigos = certificadosGenerados.map(c => c.codigo);
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'descargar_pdf_consolidado',
                        codigos: codigos
                    })
                });
                
                const data = await response.json();
                
                document.getElementById('progressBar').style.width = '100%';
                
                if (data.success && data.pdf_base64) {
                    // Descargar usando base64
                    const byteCharacters = atob(data.pdf_base64);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const blob = new Blob([byteArray], { type: 'application/pdf' });
                    
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = data.nombre;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                    
                    showNotification(`Descargando ${data.total} certificado(s)...`, 'success');
                } else {
                    throw new Error(data.message || 'Error al generar PDF');
                }
            } catch (error) {
                console.error('Error en descarga:', error);
                showNotification('Error al descargar: ' + error.message, 'error');
            } finally {
                // Ocultar indicador de carga
                setTimeout(() => {
                    document.getElementById('generarProgress').classList.remove('active');
                }, 500);
            }
        }
        
        async function descargarTodosImg() {
            if (certificadosGenerados.length === 0) {
                showNotification('No hay certificados para descargar', 'warning');
                return;
            }
            
            // Usar la función de ZIP para imágenes
            await descargarZIP('img');
        }
        
        async function descargarZIP(formato) {
            if (certificadosGenerados.length === 0) {
                showNotification('No hay certificados para descargar', 'warning');
                return;
            }
            
            // Mostrar indicador de carga
            document.getElementById('generarProgress').classList.add('active');
            document.getElementById('progressBar').style.width = '50%';
            document.getElementById('progressText').textContent = `Preparando ${formato === 'img' ? 'imágenes' : 'PDFs'} para descarga...`;
            
            try {
                const codigos = certificadosGenerados.map(c => c.codigo);
                
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'descargar_zip',
                        codigos: codigos,
                        formato: formato
                    })
                });
                
                const data = await response.json();
                
                document.getElementById('progressBar').style.width = '100%';
                
                if (data.success && data.zip_base64) {
                    // Descargar usando base64
                    const byteCharacters = atob(data.zip_base64);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const blob = new Blob([byteArray], { type: 'application/zip' });
                    
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = data.nombre;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                    
                    showNotification(`Descargando ${data.total} archivo(s)...`, 'success');
                } else if (data.success) {
                    // Fallback a URL si no hay base64
                    const link = document.createElement('a');
                    link.href = '../' + data.archivo;
                    link.download = data.nombre;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    showNotification(`Descargando ${data.total} archivo(s)...`, 'success');
                } else {
                    throw new Error(data.message || 'Error al generar ZIP');
                }
            } catch (error) {
                console.error('Error en descarga:', error);
                showNotification('Error al descargar: ' + error.message, 'error');
            } finally {
                // Ocultar indicador de carga
                setTimeout(() => {
                    document.getElementById('generarProgress').classList.remove('active');
                }, 500);
            }
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('generarCertificadosModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarGenerarModal();
            }
        });
    </script>
</body>
</html>
