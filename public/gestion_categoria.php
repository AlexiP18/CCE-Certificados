<?php
/**
 * Gestión de Categoría - Estudiantes y Certificados
 * Página unificada para gestionar estudiantes y certificados de una categoría específica
 */
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();
$esAdmin = (isset($usuario['rol_nombre']) && in_array($usuario['rol_nombre'], ['admin', 'superadmin', 'administrador', 'Administrador']))
         || (isset($usuario['es_superadmin']) && $usuario['es_superadmin'] == 1);

$categoria_id = $_GET['categoria_id'] ?? $_GET['categoria'] ?? 0;
$periodo_id = $_GET['periodo_id'] ?? $_GET['periodo'] ?? null;

if (empty($categoria_id)) {
    header('Location: index.php');
    exit;
}

$pdo = getConnection();

// Obtener información de la categoría y grupo
$stmt = $pdo->prepare("
    SELECT c.*, g.id as grupo_id, g.nombre as grupo_nombre, g.color as grupo_color, g.icono as grupo_icono
    FROM categorias c
    JOIN grupos g ON c.grupo_id = g.id
    WHERE c.id = ? AND c.activo = 1
");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: index.php');
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

$color_principal = $categoria['color'] ?: '#3498db';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nombre']) ?> - Gestión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { box-sizing: border-box; }
        
        body {
            background: #f5f7fa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* ===== HEADER ===== */
        .page-header {
            background: linear-gradient(135deg, <?= $color_principal ?>dd 0%, <?= $color_principal ?> 100%);
            color: white;
            padding: 30px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header-icon {
            font-size: 42px;
        }
        
        .header-subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 15px;
        }
        
        .header-subtitle i {
            margin-right: 5px;
        }
        
        .btn-back {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* ===== TABS ===== */
        .tabs-container {
            background: white;
            border-radius: 15px;
            padding: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5a6c7d;
        }
        
        .tab-btn:hover {
            background: #f0f2f5;
        }
        
        .tab-btn.active {
            background: <?= $color_principal ?>;
            color: white;
        }
        
        .tab-btn .badge {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .tab-btn.active .badge {
            background: rgba(255,255,255,0.3);
        }
        
        /* ===== PERÍODO SELECTOR ===== */
        .periodo-selector {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .periodo-selector label {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .periodo-selector select {
            padding: 10px 15px;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            font-size: 14px;
            min-width: 250px;
            cursor: pointer;
        }
        
        .periodo-selector select:focus {
            outline: none;
            border-color: <?= $color_principal ?>;
        }
        
        /* ===== CONTENT PANELS ===== */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .content-panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .panel-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-title i {
            color: <?= $color_principal ?>;
        }
        
        /* ===== SEARCH BAR ===== */
        .search-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-input {
            padding: 10px 15px;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            font-size: 14px;
            min-width: 250px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: <?= $color_principal ?>;
        }
        
        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }
        
        .btn-primary {
            background: <?= $color_principal ?>;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px <?= $color_principal ?>66;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }
        
        .btn-secondary {
            background: #e8ecf0;
            color: #5a6c7d;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid <?= $color_principal ?>;
            color: <?= $color_principal ?>;
        }
        
        .btn-outline:hover {
            background: <?= $color_principal ?>;
            color: white;
        }
        
        .btn-outline.active {
            background: <?= $color_principal ?>;
            color: white;
        }

        /* ===== TABLA ===== */
        .table-wrapper {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 14px 15px;
            text-align: left;
            border-bottom: 1px solid #e8ecf0;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tr.estudiante-menor {
            background: #f0f8ff;
        }
        .data-table tr.estudiante-menor:hover {
            background: #e3f2fd;
        }
        .data-table tr.estudiante-menor td:first-child {
            border-left: 3px solid #3498db;
        }
        
        .data-table tr.estudiante-representante {
            background: #f8f0ff;
        }
        .data-table tr.estudiante-representante:hover {
            background: #f3e5f5;
        }
        .data-table tr.estudiante-representante td:first-child {
            border-left: 3px solid #9b59b6;
        }
        
        .data-table tr.representante-virtual {
            background: #fafafa;
            border-top: 2px solid #e0e0e0;
        }
        .data-table tr.representante-virtual td {
            color: #7f8c8d;
            font-style: italic;
        }
        .data-table tr.representante-virtual td:first-child {
            border-left: 3px solid #bdc3c7;
        }
        
        .data-table td {
            font-size: 14px;
            color: #5a6c7d;
        }
        
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .select-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: <?= $color_principal ?>;
        }
        
        .nombre-cell {
            font-weight: 600;
            color: #2c3e50 !important;
        }
        
        .cedula-cell {
            font-family: monospace;
            background: #f0f2f5;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #e7f1ff;
            color: <?= $color_principal ?>;
        }
        
        .badge-secondary {
            background: #e8ecf0;
            color: #5a6c7d;
        }
        
        /* ===== CHIPS DE CONTACTO ===== */
        .contact-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .contact-chip-whatsapp {
            background: #dcf8c6;
            color: #25d366;
        }
        .contact-chip-whatsapp:hover {
            background: #25d366;
            color: white;
        }
        
        .contact-chip-email {
            background: #fce4ec;
            color: #e91e63;
        }
        .contact-chip-email:hover {
            background: #e91e63;
            color: white;
        }
        
        .contact-chip i {
            font-size: 11px;
        }
        
        /* ===== DROPDOWN FILTRO ===== */
        .dropdown-filter {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-filter-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .dropdown-filter-menu.show {
            display: block;
        }
        
        .dropdown-filter-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .dropdown-filter-menu a:hover {
            background: #f8f9fa;
        }
        
        .dropdown-filter-menu a.active {
            background: linear-gradient(135deg, <?= $color_principal ?>15, <?= $color_principal ?>25);
            color: <?= $color_principal ?>;
            font-weight: 600;
        }
        
        .dropdown-filter-menu a i {
            width: 18px;
            text-align: center;
            color: #888;
        }
        
        .dropdown-filter-menu a.active i {
            color: <?= $color_principal ?>;
        }

        /* ===== ACCIONES ===== */
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #e7f1ff;
            color: <?= $color_principal ?>;
        }
        
        .btn-view:hover {
            background: <?= $color_principal ?>;
            color: white;
        }
        
        .btn-certificate {
            background: #d4edda;
            color: #155724;
        }
        
        .btn-certificate:hover {
            background: #27ae60;
            color: white;
        }
        
        .btn-download {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-download:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .btn-edit:hover {
            background: #3498db;
            color: white;
        }

        /* ===== BULK ACTIONS ===== */
        .bulk-actions {
            display: none;
            background: linear-gradient(135deg, <?= $color_principal ?>dd, <?= $color_principal ?>);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            animation: slideDown 0.3s ease;
        }
        
        .bulk-actions.active {
            display: flex;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .bulk-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-weight: 500;
        }
        
        .bulk-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-bulk {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-bulk-primary {
            background: white;
            color: <?= $color_principal ?>;
        }
        
        .btn-bulk-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-bulk-cancel {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #5a6c7d;
        }
        
        /* ===== LOADING ===== */
        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .loading i {
            font-size: 32px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        /* ===== STATS CARDS ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .stat-icon.estudiantes {
            background: linear-gradient(135deg, #667eeacc, #764ba2);
            color: white;
        }
        
        .stat-icon.certificados {
            background: linear-gradient(135deg, #27ae60cc, #2ecc71);
            color: white;
        }
        
        .stat-icon.pendientes {
            background: linear-gradient(135deg, #f39c12cc, #e67e22);
            color: white;
        }
        
        .stat-info h4 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 13px;
        }
        
        /* ===== CERTIFICADO PREVIEW ===== */
        .certificado-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        
        .certificado-item:hover {
            background: #f0f2f5;
        }
        
        .certificado-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .certificado-info {
            flex: 1;
        }
        
        .certificado-info h4 {
            margin: 0;
            font-size: 15px;
            color: #2c3e50;
        }
        
        .certificado-info p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .certificado-actions {
            display: flex;
            gap: 8px;
        }
        
        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 700px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .modal-xl {
            max-width: 950px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #f0f2f5;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: #e74c3c;
            color: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: <?= $color_principal ?>;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
        }
        
        /* Checkbox toggle para menor de edad */
        .toggle-menor {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .toggle-menor input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: <?= $color_principal ?>;
        }
        
        .toggle-menor label {
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
        }
        
        /* Secciones del formulario */
        .form-section {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .form-section-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        
        .form-section-title i {
            color: <?= $color_principal ?>;
        }
        
        /* Lista de menores */
        .menores-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .menor-item {
            background: white;
            border: 2px solid #e8ecf0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }
        
        .menor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e8ecf0;
        }
        
        .menor-numero {
            font-weight: 600;
            color: #8e44ad;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-remove-menor {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .btn-remove-menor:hover {
            background: #dc2626;
            color: white;
        }
        
        .btn-add-menor {
            width: 100%;
            padding: 12px;
            border: 2px dashed #d5dbdb;
            border-radius: 10px;
            background: transparent;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-add-menor:hover {
            border-color: <?= $color_principal ?>;
            color: <?= $color_principal ?>;
            background: #f8f9fa;
        }
        
        /* Input celular con prefijo */
        .celular-input-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .celular-input-wrapper:focus-within {
            border-color: <?= $color_principal ?>;
        }
        
        .celular-prefix {
            padding: 10px 12px;
            background: #f0f2f5;
            color: #5a6c7d;
            font-weight: 500;
            font-size: 14px;
        }
        
        .celular-input-wrapper input {
            flex: 1;
            border: none !important;
            padding: 10px 14px;
            font-size: 14px;
        }
        
        .celular-input-wrapper input:focus {
            outline: none;
        }
        
        /* Validación de campos */
        .field-error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }
        
        .input-error {
            border-color: #dc2626 !important;
            background: #fef2f2 !important;
        }
        
        .input-valid {
            border-color: #16a34a !important;
        }
        
        /* Certificado icons en tabla */
        .cert-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .cert-generated {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        .cert-approved {
            background: linear-gradient(135deg, #cce5ff, #b8daff);
            color: #004085;
        }
        
        .cert-pending {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        
        .cert-none {
            background: #e8ecf0;
            color: #7f8c8d;
        }
        
        /* Tooltip para certificados */
        .cert-tooltip-trigger {
            position: relative;
            cursor: pointer;
        }
        
        .cert-info-icon {
            font-size: 10px;
            margin-left: 4px;
            opacity: 0.7;
        }
        
        .cert-tooltip-trigger:hover .cert-info-icon {
            opacity: 1;
        }
        
        .cert-tooltip-trigger::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2c3e50;
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
            max-width: 300px;
            white-space: normal;
            text-align: left;
            line-height: 1.5;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            pointer-events: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 8px;
        }
        
        .cert-tooltip-trigger::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #2c3e50;
            margin-bottom: -4px;
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }
        
        .cert-tooltip-trigger:hover::after,
        .cert-tooltip-trigger:hover::before {
            opacity: 1;
            visibility: visible;
        }
        
        /* ===== MODAL PREVISUALIZACIÓN CERTIFICADO ===== */
        .preview-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .preview-container {
                grid-template-columns: 1fr;
            }
        }
        
        .preview-image-wrapper {
            background: #f0f2f5;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
        }
        
        .preview-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .preview-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .preview-info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px;
        }
        
        .preview-info-card h4 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e8ecf0;
            font-size: 14px;
        }
        
        .preview-info-row:last-child {
            border-bottom: none;
        }
        
        .preview-info-row .label {
            color: #7f8c8d;
        }
        
        .preview-info-row .value {
            color: #2c3e50;
            font-weight: 500;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        
        .preview-razon {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 12px;
            padding: 15px;
        }
        
        .preview-razon h4 {
            margin: 0 0 8px 0;
            color: #2e7d32;
            font-size: 13px;
        }
        
        .preview-razon p {
            margin: 0;
            color: #1b5e20;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .preview-downloads {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        
        .preview-downloads .btn {
            flex: 1;
            justify-content: center;
        }
        
        .preview-badge-valid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        /* Resultados búsqueda estudiante */
        .resultado-estudiante {
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        
        .resultado-estudiante:hover {
            background: #e8f4fd;
        }
        
        .resultado-estudiante.ya-matriculado {
            opacity: 0.6;
            cursor: default;
        }
        
        /* Dropdown de descargas */
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            color: #2c3e50;
            transition: background 0.2s;
        }
        
        .dropdown-item:hover {
            background: #f0f2f5;
        }
        
        .dropdown-item:first-child {
            border-radius: 10px 10px 0 0;
        }
        
        .dropdown-item:last-child {
            border-radius: 0 0 10px 10px;
        }
        
        /* Dropdown mini para acciones de fila */
        .btn-dropdown-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .btn-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 150px;
            margin-top: 4px;
            overflow: hidden;
        }
        
        .btn-dropdown-menu.show {
            display: block;
        }
        
        .btn-dropdown-menu .dropdown-item {
            padding: 10px 14px;
            font-size: 13px;
        }
        
        .btn-dropdown-menu .dropdown-item i {
            width: 16px;
            text-align: center;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf0;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .tabs-container {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
            
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .search-input {
                width: 100%;
                min-width: auto;
            }
        }

        /* ===== NOTIFICACIONES ===== */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification.success { background: #27ae60; }
        .notification.error { background: #e74c3c; }
        .notification.warning { background: #f39c12; }
        .notification.info { background: #3498db; }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-top">
                <div class="header-title">
                    <span class="header-icon"><?= htmlspecialchars($categoria['icono'] ?: '📁') ?></span>
                    <div>
                        <h1><?= htmlspecialchars($categoria['nombre']) ?></h1>
                        <div class="header-subtitle">
                            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($categoria['grupo_nombre']) ?>
                            <?php if ($periodo_actual): ?>
                            &nbsp;|&nbsp; <i class="fas fa-calendar"></i> <?= htmlspecialchars($periodo_actual['nombre']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="grupo_detalle.php?id=<?= $grupo_id ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
            </div>
        </div>
        
        <!-- Selector de Período -->
        <?php if (count($periodos) > 1): ?>
        <div class="periodo-selector">
            <label><i class="fas fa-calendar-alt"></i> Período:</label>
            <select id="periodoSelect" onchange="cambiarPeriodo(this.value)">
                <?php foreach ($periodos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $periodo_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon estudiantes">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalEstudiantes">0</h4>
                    <p>Estudiantes matriculados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon menores" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-child"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalMenores">0</h4>
                    <p>Estudiantes menores de edad</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon certificados">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalCertificados">0</h4>
                    <p>Certificados generados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pendientes">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalPendientes">0</h4>
                    <p>Pendientes de certificado</p>
                </div>
            </div>
        </div>
        
        <!-- Panel único de estudiantes con certificados integrados -->
        <div class="content-panel">
            <!-- Acciones masivas -->
            <div class="bulk-actions" id="bulkActions">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><span id="selectedCount">0</span> estudiantes seleccionados</span>
                </div>
                <div class="bulk-buttons">
                    <button class="btn-bulk btn-bulk-primary" id="btnGenerarSeleccionados" onclick="generarCertificadosSeleccionados()" style="display: none;">
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnRegenerarSeleccionados" onclick="regenerarCertificadosSeleccionados()" style="display: none; background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-sync-alt"></i> Regenerar Certificados
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnDescargarPdfSeleccionados" onclick="descargarCertificadosSeleccionados('pdf')" style="display: none;">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnDescargarImgSeleccionados" onclick="descargarCertificadosSeleccionados('imagen')" style="display: none;">
                        <i class="fas fa-image"></i> Descargar Imagen
                    </button>
                    <button class="btn-bulk btn-bulk-cancel" onclick="cancelarSeleccion()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            <div class="panel-header">
                <h3 class="panel-title"><i class="fas fa-users"></i> Estudiantes y Certificados</h3>
                <div class="search-bar">
                    <input type="text" class="search-input" id="searchEstudiantes" placeholder="Buscar por nombre o cédula...">
                    
                    <!-- Dropdown filtro -->
                    <div class="dropdown-filter">
                        <button class="btn btn-outline" onclick="toggleDropdownFiltro()" id="btnFiltro">
                            <i class="fas fa-filter"></i> <span id="filtroTexto">Todos</span> <i class="fas fa-caret-down" style="margin-left: 5px;"></i>
                        </button>
                        <div class="dropdown-filter-menu" id="dropdownFiltro">
                            <a onclick="aplicarFiltro('todos')" class="active">
                                <i class="fas fa-users"></i> Todos
                            </a>
                            <a onclick="aplicarFiltro('representante')">
                                <i class="fas fa-user-tie"></i> Con Representante
                            </a>
                            <a onclick="aplicarFiltro('mayores')">
                                <i class="fas fa-user-graduate"></i> Mayores de Edad
                            </a>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline" onclick="abrirModalAgregar()">
                        <i class="fas fa-user-plus"></i> Agregar
                    </button>
                    <button class="btn btn-outline" id="btnHistorial" onclick="abrirModalHistorial()" style="display: none;">
                        <i class="fas fa-history"></i> Historial
                    </button>
                    <button class="btn btn-success" id="btnGenerarTodos" onclick="generarTodosCertificados()" style="display: none;">
                        <i class="fas fa-magic"></i> Generar Todos
                    </button>
                    <div class="dropdown" id="dropdownDescargasContainer" style="position: relative; display: none;">
                        <button class="btn btn-primary" onclick="toggleDropdownDescargas()" id="btnDescargas">
                            <i class="fas fa-download"></i> Descargar <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdownDescargas" style="display: none; position: absolute; right: 0; top: 100%; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); z-index: 100; min-width: 180px; margin-top: 5px;">
                            <button class="dropdown-item" onclick="descargarTodosCertificados('pdf')">
                                <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> Todos en PDF
                            </button>
                            <button class="dropdown-item" onclick="descargarTodosCertificados('imagen')">
                                <i class="fas fa-images" style="color: #3498db;"></i> Todos en Imágenes (ZIP)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" class="select-checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Celular</th>
                            <th>Correo</th>
                            <th>Certificado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEstudiantes">
                        <tr>
                            <td colspan="7">
                                <div class="loading">
                                    <i class="fas fa-spinner"></i>
                                    <p>Cargando...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar estudiante -->
    <div class="modal-overlay" id="modalAgregar">
        <div class="modal modal-lg" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-user-plus"></i> Agregar Estudiante</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalAgregar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <!-- Búsqueda de estudiante existente -->
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Buscar estudiante existente</label>
                    <input type="text" id="buscarExistente" placeholder="Escribe nombre o cédula del estudiante...">
                    <div id="resultadosBusqueda" style="margin-top: 10px; max-height: 150px; overflow-y: auto;"></div>
                </div>
                
                <div style="display: flex; align-items: center; margin: 25px 0;">
                    <div style="flex: 1; border-top: 2px dashed #e8ecf0;"></div>
                    <span style="padding: 0 15px; color: #7f8c8d; font-size: 14px; white-space: nowrap;"><i class="fas fa-plus-circle"></i> O crear nuevo estudiante</span>
                    <div style="flex: 1; border-top: 2px dashed #e8ecf0;"></div>
                </div>
            
            <form id="formNuevoEstudiante" onsubmit="guardarNuevoEstudiante(event)">
                <!-- Toggle menor de edad -->
                <div class="toggle-menor">
                    <input type="checkbox" id="es_menor" onchange="toggleMenorEdad()">
                    <label for="es_menor"><i class="fas fa-child"></i> El/los estudiante(s) son menor(es) de edad</label>
                </div>
                
                <!-- SECCIÓN: Estudiante Mayor de Edad -->
                <div id="datosEstudianteMayor">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user"></i> Datos del Estudiante</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo *</label>
                                <input type="text" id="nuevo_nombre" name="nombre" placeholder="Nombre completo" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" id="nuevo_fecha_nacimiento" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cédula <small style="color:#95a5a6">(opcional - 10 dígitos)</small></label>
                                <input type="text" id="nuevo_cedula" name="cedula" 
                                       placeholder="Ej: 1712345678" maxlength="10" pattern="(^$|[0-9]{10})"
                                       oninput="validarCedulaInput(this)" onblur="verificarCedula(this)">
                                <small class="field-error" id="cedula_error"></small>
                            </div>
                            <div class="form-group">
                                <label>Celular <small style="color:#95a5a6">(opcional - 9 dígitos)</small></label>
                                <div class="celular-input-wrapper">
                                    <span class="celular-prefix">+593</span>
                                    <input type="text" id="nuevo_celular" name="celular" 
                                           placeholder="9XXXXXXXX" maxlength="9" pattern="(^$|[0-9]{9})"
                                           oninput="validarCelularInput(this)" onblur="verificarCelular(this)">
                                </div>
                                <small class="field-error" id="celular_error"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico <small style="color:#95a5a6">(opcional)</small></label>
                            <input type="email" id="nuevo_email" name="email" 
                                   placeholder="correo@ejemplo.com" onblur="verificarEmail(this)">
                            <small class="field-error" id="email_error"></small>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN: Menores de Edad con Representante -->
                <div id="datosRepresentante" style="display: none;">
                    <!-- Datos del Representante -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user-tie"></i> Datos del Representante</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre del Representante *</label>
                                <input type="text" id="representante_nombre" placeholder="Nombre completo del representante">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" id="representante_fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cédula del Representante * <small style="color:#95a5a6">(10 dígitos)</small></label>
                                <input type="text" id="representante_cedula" 
                                       placeholder="Ej: 1712345678" maxlength="10" pattern="[0-9]{10}"
                                       oninput="validarCedulaInput(this)" onblur="verificarCedulaRepresentante(this)">
                                <small class="field-error" id="representante_cedula_error"></small>
                            </div>
                            <div class="form-group">
                                <label>Celular del Representante * <small style="color:#95a5a6">(9 dígitos)</small></label>
                                <div class="celular-input-wrapper">
                                    <span class="celular-prefix">+593</span>
                                    <input type="text" id="representante_celular" 
                                           placeholder="9XXXXXXXX" maxlength="9" pattern="[0-9]{9}"
                                           oninput="validarCelularInput(this)" onblur="verificarCelularRepresentante(this)">
                                </div>
                                <small class="field-error" id="representante_celular_error"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Correo del Representante</label>
                            <input type="email" id="representante_email" 
                                   placeholder="correo@ejemplo.com" onblur="verificarEmailRepresentante(this)">
                            <small class="field-error" id="representante_email_error"></small>
                        </div>
                    </div>
                    
                    <!-- Lista de Menores -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-child"></i> Menores de Edad</div>
                        <div class="menores-list" id="listaMenores">
                            <!-- Primer menor (template) -->
                            <div class="menor-item" data-index="0">
                                <div class="menor-header">
                                    <span class="menor-numero"><i class="fas fa-child"></i> Menor #1</span>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nombre del Menor *</label>
                                        <input type="text" name="menores[0][nombre]" placeholder="Nombre completo del menor">
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha de Nacimiento *</label>
                                        <input type="date" name="menores[0][fecha_nacimiento]" max="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Cédula <small style="color:#95a5a6">(opcional - 10 dígitos)</small></label>
                                        <input type="text" name="menores[0][cedula]" 
                                               placeholder="Ej: 1712345678" maxlength="10"
                                               oninput="validarCedulaInput(this)" onblur="verificarCedulaMenor(this)">
                                        <small class="field-error menor-cedula-error"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Celular <small style="color:#95a5a6">(opcional - 9 dígitos)</small></label>
                                        <div class="celular-input-wrapper">
                                            <span class="celular-prefix">+593</span>
                                            <input type="text" name="menores[0][celular]" 
                                                   placeholder="9XXXXXXXX" maxlength="9"
                                                   oninput="validarCelularInput(this)" onblur="verificarCelularMenor(this)">
                                        </div>
                                        <small class="field-error menor-celular-error"></small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" name="menores[0][email]" 
                                           placeholder="correo@ejemplo.com" onblur="verificarEmailMenor(this)">
                                    <small class="field-error menor-email-error"></small>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-add-menor" onclick="agregarMenor()">
                            <i class="fas fa-plus-circle"></i> Agregar otro menor
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalAgregar')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar y Matricular
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para generar certificado -->
    <div class="modal-overlay" id="modalGenerar" onclick="cerrarModal(event, 'modalGenerar')">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 1100px; width: 95%; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, #27ae60, #229954); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-certificate"></i> Generar Certificados</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalGenerar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalGenerarContent" class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <!-- Alerta si plantilla no configurada -->
                <div id="generarPlantillaAlerta" style="display: none; background: linear-gradient(135deg, #fff3cd, #ffeeba); border: 1px solid #ffc107; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: #856404; font-size: 24px;"></i>
                        <div>
                            <strong style="color: #856404;">Plantilla no configurada</strong>
                            <p style="margin: 5px 0 0 0; color: #856404; font-size: 13px;">Debe configurar la plantilla del certificado antes de generar. <a id="linkConfigPlantilla" href="#" style="color: #0056b3; font-weight: 600;">Ir a configuración</a></p>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                    <!-- Columna izquierda: Preview -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-eye" style="color: #27ae60;"></i> Previsualización de Plantilla</h4>
                        <div id="generarPreviewContainer" style="background: linear-gradient(145deg, #f8f9fa, #e9ecef); border-radius: 12px; padding: 20px; min-height: 350px; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6;">
                            <div id="generarPreviewLoading" style="text-align: center;">
                                <i class="fas fa-spinner fa-spin fa-2x" style="color: #27ae60;"></i>
                                <p style="margin-top: 10px; color: #7f8c8d;">Cargando previsualización...</p>
                            </div>
                            <img id="generarPreviewImage" src="" alt="Previsualización" style="display: none; max-width: 100%; max-height: 450px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        </div>
                        <p style="color: #7f8c8d; font-size: 12px; margin-top: 12px; text-align: center;">
                            <i class="fas fa-info-circle"></i> Así lucirán los certificados generados
                        </p>
                    </div>
                    
                    <!-- Columna derecha: Opciones y lista -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-cog" style="color: #27ae60;"></i> Opciones de Generación</h4>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 600; color: #34495e; display: block; margin-bottom: 8px;"><i class="fas fa-calendar" style="color: #27ae60;"></i> Fecha del Certificado</label>
                            <input type="date" id="fechaCertificado" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: border-color 0.3s;" onfocus="this.style.borderColor='#27ae60'" onblur="this.style.borderColor='#e0e0e0'">
                        </div>
                        
                        <h4 style="margin: 20px 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-users" style="color: #27ae60;"></i> Estudiantes a certificar (<span id="generarCount" style="color: #27ae60; font-weight: 700;">0</span>)</h4>
                        <div id="listaEstudiantesGenerar" style="max-height: 220px; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 12px; border: 1px solid #e0e0e0;">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalGenerar')" style="padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn" id="btnConfirmarGenerar" onclick="confirmarGeneracion()" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 12px 24px; font-weight: 600;">
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para regenerar certificados -->
    <div class="modal-overlay" id="modalRegenerar" onclick="cerrarModal(event, 'modalRegenerar')">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 1100px; width: 95%; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-sync-alt"></i> Regenerar Certificados</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalRegenerar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                    <!-- Columna izquierda: Preview (más ancha) -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-eye" style="color: <?= $color_principal ?>;"></i> Previsualización de Plantilla</h4>
                        <div id="regenerarPreviewContainer" style="background: linear-gradient(145deg, #f8f9fa, #e9ecef); border-radius: 12px; padding: 20px; min-height: 350px; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6;">
                            <div id="regenerarPreviewLoading" style="text-align: center;">
                                <i class="fas fa-spinner fa-spin fa-2x" style="color: <?= $color_principal ?>;"></i>
                                <p style="margin-top: 10px; color: #7f8c8d;">Cargando previsualización...</p>
                            </div>
                            <img id="regenerarPreviewImage" src="" alt="Previsualización" style="display: none; max-width: 100%; max-height: 450px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        </div>
                        <p style="color: #7f8c8d; font-size: 12px; margin-top: 12px; text-align: center;">
                            <i class="fas fa-info-circle"></i> Esta es la configuración actual de la plantilla
                        </p>
                    </div>
                    
                    <!-- Columna derecha: Opciones y lista -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-cog" style="color: #3498db;"></i> Opciones de Regeneración</h4>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 600; color: #34495e; display: block; margin-bottom: 8px;"><i class="fas fa-comment-alt" style="color: #3498db;"></i> Razón de la regeneración</label>
                            <textarea id="regenerarRazon" rows="3" placeholder="Ej: Actualización de plantilla, corrección de diseño, cambio de logo..." style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical; transition: border-color 0.3s;" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'"></textarea>
                            <small style="color: #95a5a6; display: block; margin-top: 6px;">Opcional: Se guardará en el historial del certificado</small>
                        </div>
                        
                        <h4 style="margin: 20px 0 15px 0; color: #2c3e50; font-size: 16px;"><i class="fas fa-users" style="color: #3498db;"></i> Estudiantes a regenerar (<span id="regenerarCount" style="color: #3498db; font-weight: 700;">0</span>)</h4>
                        <div id="listaEstudiantesRegenerar" style="max-height: 220px; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 12px; border: 1px solid #e0e0e0;">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalRegenerar')" style="padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn" id="btnConfirmarRegenerar" onclick="confirmarRegeneracion()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 12px 24px; font-weight: 600;">
                        <i class="fas fa-sync-alt"></i> Regenerar Certificados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar estudiante -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-user-edit"></i> Editar Estudiante</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalEditar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formEditarEstudiante" onsubmit="guardarEdicionEstudiante(event)" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                <input type="hidden" id="edit_estudiante_id">
                <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" id="edit_nombre" required placeholder="Nombre completo del estudiante">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" id="edit_fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Cédula <small style="color:#95a5a6">(opcional - 10 dígitos)</small></label>
                            <input type="text" id="edit_cedula" placeholder="Ej: 1712345678" maxlength="10" oninput="validarCedulaInput(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Celular <small style="color:#95a5a6">(opcional - 9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix">+593</span>
                                <input type="text" id="edit_celular" placeholder="9XXXXXXXX" maxlength="9" oninput="validarCelularInput(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico <small style="color:#95a5a6">(opcional)</small></label>
                            <input type="email" id="edit_email" placeholder="correo@ejemplo.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="edit_es_menor" style="width: auto;">
                                <span><i class="fas fa-child"></i> Es menor de edad</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="edit_destacado" style="width: auto;">
                                <span><i class="fas fa-star" style="color: #f39c12;"></i> Estudiante destacado</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; flex-shrink: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalEditar')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para previsualizar certificado -->
    <div class="modal-overlay" id="modalPreview" onclick="cerrarModal(event, 'modalPreview')">
        <div class="modal modal-xl" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-eye"></i> <span id="previewTitulo">Certificado</span></h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalPreview')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div class="preview-container">
                    <!-- Imagen del certificado -->
                    <div class="preview-image-wrapper">
                        <div id="previewLoading" style="padding: 60px; color: #7f8c8d;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Cargando certificado...</p>
                        </div>
                        <img id="previewImage" class="preview-image" style="display: none;" alt="Certificado">
                    </div>
                    
                    <!-- Información del certificado -->
                    <div class="preview-info">
                        <div class="preview-badge-valid">
                            <i class="fas fa-check-circle"></i> Certificado Válido
                        </div>
                        
                        <div class="preview-info-card">
                            <h4><i class="fas fa-info-circle"></i> Información</h4>
                            <div class="preview-info-row">
                                <span class="label">Código</span>
                                <span class="value" id="previewCodigo">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Nombre</span>
                                <span class="value" id="previewNombre">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Fecha Certificación</span>
                                <span class="value" id="previewFecha">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Emitido el</span>
                                <span class="value" id="previewFechaCreacion">—</span>
                            </div>
                        </div>
                        
                        <div class="preview-razon" id="previewRazonContainer" style="display: none;">
                            <h4><i class="fas fa-quote-left"></i> Razón</h4>
                            <p id="previewRazon"></p>
                        </div>
                        
                        <div class="preview-downloads">
                            <button class="btn btn-secondary" id="btnPreviewDescargarImg" onclick="descargarDesdePreview('imagen')">
                                <i class="fas fa-image"></i> Imagen
                            </button>
                            <button class="btn btn-primary" id="btnPreviewDescargarPdf" onclick="descargarDesdePreview('pdf')">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Historial de Generaciones -->
    <div class="modal-overlay" id="modalHistorial" onclick="cerrarModal(event, 'modalHistorial')">
        <div class="modal modal-lg" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-history"></i> Historial de Generaciones</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalHistorial')" style="background: rgba(255,255,255,0.2); color: white;">×</button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div id="historialContainer">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para quitar estudiante -->
    <div class="modal-overlay" id="modalConfirmarQuitar">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-user-times" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Quitar Estudiante</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de quitar a:
                </p>
                <p id="confirmarQuitarNombre" style="color: #e74c3c; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre del estudiante -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> El estudiante será quitado de esta categoría pero no será eliminado del sistema.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarConfirmarQuitar()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarQuitar" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="confirmarQuitarEstudiante()">
                    <i class="fas fa-user-times"></i> Quitar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Configuración
        const categoriaId = <?= $categoria_id ?>;
        const grupoId = <?= $grupo_id ?>;
        let periodoId = <?= $periodo_id ?: 'null' ?>;
        let currentPreviewCode = null;
        const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;
        
        let estudiantes = [];
        let certificados = [];
        let seleccionados = [];
        let contadorMenores = 1;
        let filtroActual = 'todos'; // todos, representante, mayores
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            cargarDatos();
            toggleMenorEdad(false);
            resetearListaMenores();
            
            // Búsqueda en tiempo real
            let timeout;
            document.getElementById('searchEstudiantes').addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => filtrarEstudiantes(this.value), 300);
            });
            
            const searchCert = document.getElementById('searchCertificados');
            if (searchCert) {
                searchCert.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => filtrarCertificados(this.value), 300);
                });
            }
            
            // Búsqueda de estudiante existente
            document.getElementById('buscarExistente').addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => buscarEstudianteExistente(this.value), 300);
            });
        });
        
        // Cambiar período
        function cambiarPeriodo(nuevoPeriodo) {
            periodoId = nuevoPeriodo;
            const url = new URL(window.location);
            url.searchParams.set('periodo_id', nuevoPeriodo);
            window.history.pushState({}, '', url);
            cargarDatos();
        }
        
        // Cambiar tab
        function cambiarTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
        }
        
        // Cargar datos
        async function cargarDatos() {
            await cargarEstudiantes();
            if (document.getElementById('listaCertificados')) {
                await cargarCertificados();
            }
            actualizarStats();
        }
        
        // Cargar estudiantes
        async function cargarEstudiantes() {
            const tbody = document.getElementById('tablaEstudiantes');
            tbody.innerHTML = '<tr><td colspan="7"><div class="loading"><i class="fas fa-spinner"></i><p>Cargando...</p></div></td></tr>';
            
            try {
                const params = new URLSearchParams({
                    action: 'listar_matriculados',
                    categoria_id: categoriaId
                });
                if (periodoId) params.append('periodo_id', periodoId);
                
                const response = await fetch(`api_categoria_estudiantes.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    estudiantes = data.estudiantes || [];
                    renderEstudiantes(estudiantes);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error al cargar</h3><p>' + error.message + '</p></div></td></tr>';
            }
        }
        
        // Renderizar estudiantes con estado de certificado/ aprobación
        function renderEstudiantes(lista) {
            const tbody = document.getElementById('tablaEstudiantes');
            
            if (!lista || lista.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><h3>No hay estudiantes</h3><p>Agrega estudiantes a esta categoría</p></div></td></tr>';
                return;
            }
            
            // Separar representantes (mayores de edad y representantes de menores) de menores
            const representantes = new Map(); // cédula representante -> menores
            
            // Primero identificar todos los representantes que están en la lista
            const idsEnLista = new Set(lista.map(e => parseInt(e.id)));
            
            // Agrupar menores por representante
            lista.forEach(est => {
                if (est.es_menor == 1 && est.representante_cedula) {
                    const repId = est.representante_id ? parseInt(est.representante_id) : null;
                    if (repId && idsEnLista.has(repId)) {
                        // El representante está en la lista
                        if (!representantes.has(repId)) {
                            representantes.set(repId, []);
                        }
                        representantes.get(repId).push(est);
                    } else {
                        // El representante NO está en la lista - crear grupo virtual
                        const repKey = 'rep_' + est.representante_cedula;
                        if (!representantes.has(repKey)) {
                            representantes.set(repKey, {
                                virtual: true,
                                nombre: est.representante_nombre,
                                cedula: est.representante_cedula,
                                celular: est.representante_celular,
                                email: est.representante_email,
                                menores: []
                            });
                        }
                        representantes.get(repKey).menores.push(est);
                    }
                }
            });
            
            // Generar HTML
            let html = '';
            
            lista.forEach(est => {
                // Saltar menores, se renderizan bajo su representante
                if (est.es_menor == 1) return;
                
                const estId = parseInt(est.id);
                const tieneMenuores = representantes.has(estId);
                html += renderFilaEstudiante(est, false, tieneMenuores);
                
                // Renderizar menores debajo del representante
                if (tieneMenuores) {
                    const menores = representantes.get(estId);
                    menores.forEach(menor => {
                        html += renderFilaEstudiante(menor, true, false);
                    });
                }
            });
            
            // Renderizar representantes virtuales (no están en la lista)
            representantes.forEach((value, key) => {
                if (typeof key === 'string' && key.startsWith('rep_')) {
                    // Fila del representante virtual (no estudiante del grupo)
                    const celularChip = value.celular ? crearChipWhatsApp(value.celular) : '<span style="color:#aaa">—</span>';
                    const emailChip = value.email ? crearChipEmail(value.email) : '<span style="color:#aaa">—</span>';
                    
                    html += `
                        <tr class="representante-virtual">
                            <td class="checkbox-cell"></td>
                            <td class="nombre-cell">
                                <i class="fas fa-user-tie" style="color: #9b59b6; margin-right: 5px;"></i>
                                ${escapeHtml(value.nombre)}
                                <span class="badge badge-secondary" style="font-size: 10px; margin-left: 5px;">Representante</span>
                            </td>
                            <td><span class="cedula-cell">${escapeHtml(value.cedula)}</span></td>
                            <td>${celularChip}</td>
                            <td>${emailChip}</td>
                            <td><span class="cert-status cert-none" style="opacity: 0.5;"><i class="fas fa-ban"></i> No estudiante</span></td>
                            <td></td>
                        </tr>
                    `;
                    // Menores del representante virtual
                    value.menores.forEach(menor => {
                        html += renderFilaEstudiante(menor, true, false);
                    });
                }
            });
            
            tbody.innerHTML = html;
            
            document.getElementById('selectAll').checked = false;
            const badgeEst = document.getElementById('badgeEstudiantes');
            if (badgeEst) badgeEst.textContent = lista.length;
        }
        
        // Crear chip de WhatsApp
        function crearChipWhatsApp(celular) {
            if (!celular) return '<span style="color:#aaa">—</span>';
            // Limpiar número: quitar + y espacios
            let numero = celular.replace(/[^0-9]/g, '');
            // Asegurar que tenga código de país
            if (!numero.startsWith('593') && numero.length === 9) {
                numero = '593' + numero;
            }
            // Formato para mostrar
            const mostrar = celular.replace('+593', '0');
            return `<a href="https://wa.me/${numero}" target="_blank" class="contact-chip contact-chip-whatsapp" title="Abrir WhatsApp">
                <i class="fab fa-whatsapp"></i> ${mostrar}
            </a>`;
        }
        
        // Crear chip de Email
        function crearChipEmail(email) {
            if (!email) return '<span style="color:#aaa">—</span>';
            return `<a href="mailto:${email}" class="contact-chip contact-chip-email" title="Enviar correo">
                <i class="fas fa-envelope"></i> ${escapeHtml(email)}
            </a>`;
        }
        
        // Renderizar una fila de estudiante
        function renderFilaEstudiante(est, esMenor, tieneMenuores) {
            const certCodigo = est.certificado_codigo;
            const certEstado = est.certificado_estado || null;
            const certId = est.certificado_id;
            let certBadge = '<span class="cert-status cert-none"><i class="fas fa-minus-circle"></i> Sin certificado</span>';
            let accionesCert = '';
            
            if (certCodigo) {
                // Badge simple del certificado
                certBadge = `<span class="cert-status cert-generated">
                    <i class="fas fa-check-circle"></i> ${certCodigo}
                </span>`;
                accionesCert = `
                    <button class="btn-icon btn-view" onclick="previsualizarCertificado('${certCodigo}')" title="Ver certificado">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="btn-dropdown-wrapper">
                        <button class="btn-icon btn-download" onclick="toggleDownloadDropdown(event, '${certCodigo}')" title="Descargar">
                            <i class="fas fa-download"></i>
                        </button>
                        <div class="btn-dropdown-menu" id="downloadMenu-${certCodigo}">
                            <button class="dropdown-item" onclick="descargarCertificado('${certCodigo}')">
                                <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> PDF
                            </button>
                            <button class="dropdown-item" onclick="descargarCertificadoImagen('${certCodigo}')">
                                <i class="fas fa-image" style="color: #3498db;"></i> Imagen
                            </button>
                        </div>
                    </div>
                    <button class="btn-icon btn-certificate" onclick="regenerarCertificado('${certCodigo}', '${escapeHtml(est.nombre)}')" title="Regenerar certificado">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                `;
            } else {
                accionesCert = `
                    <button class="btn-icon btn-certificate" onclick="generarCertificadoIndividual(${est.id}, '${escapeHtml(est.nombre)}')" title="Generar certificado">
                        <i class="fas fa-certificate"></i>
                    </button>
                `;
            }
            
            const accionesBase = `
                ${accionesCert}
                <button class="btn-icon btn-edit" onclick="abrirModalEditar(${est.id})" title="Editar estudiante">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="quitarEstudiante(${est.matricula_id}, '${escapeHtml(est.nombre)}')" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            const clasesFila = esMenor ? 'estudiante-menor' : (tieneMenuores ? 'estudiante-representante' : '');
            const iconoMenor = esMenor ? '<i class="fas fa-child" style="color: #3498db; margin-right: 5px;"></i>' : '';
            const iconoRepresentante = tieneMenuores ? '<i class="fas fa-users" style="color: #9b59b6; margin-right: 5px;" title="Tiene menores a cargo"></i>' : '';
            
            // Chips de contacto
            const celularChip = crearChipWhatsApp(est.celular);
            const emailChip = crearChipEmail(est.email);
            
            return `
                <tr data-id="${est.id}" data-es-menor="${est.es_menor || 0}" data-tiene-representante="${est.representante_id ? 1 : 0}" class="${clasesFila}">
                    <td class="checkbox-cell">
                        <input type="checkbox" class="select-checkbox est-checkbox" value="${est.id}" data-cert-id="${certId || ''}" onchange="actualizarSeleccion()">
                    </td>
                    <td class="nombre-cell">
                        ${esMenor ? '<span style="display: inline-block; width: 20px;"></span>' : ''}
                        ${iconoRepresentante}
                        ${iconoMenor}
                        ${est.destacado == 1 ? '<i class="fas fa-star" style="color: #f39c12; margin-right: 5px;"></i>' : ''}
                        ${escapeHtml(est.nombre)}
                        ${esMenor ? '<span class="badge badge-info" style="font-size: 10px; margin-left: 5px;">Menor</span>' : ''}
                    </td>
                    <td>${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color:#aaa">—</span>'}</td>
                    <td>${celularChip}</td>
                    <td>${emailChip}</td>
                    <td>${certBadge}</td>
                    <td><div class="action-buttons">${accionesBase}</div></td>
                </tr>
            `;
        }
        
        // Filtrar estudiantes
        function filtrarEstudiantes(query) {
            let filtrados = estudiantes;
            
            // Aplicar filtro por tipo
            if (filtroActual === 'representante') {
                filtrados = filtrados.filter(e => e.representante_id != null || e.es_menor == 1);
            } else if (filtroActual === 'mayores') {
                filtrados = filtrados.filter(e => e.es_menor != 1);
            }
            
            // Aplicar filtro por búsqueda
            if (query) {
                filtrados = filtrados.filter(e => 
                    e.nombre.toLowerCase().includes(query.toLowerCase()) ||
                    (e.cedula && e.cedula.includes(query))
                );
            }
            
            renderEstudiantes(filtrados);
        }
        
        // Toggle dropdown de filtro
        function toggleDropdownFiltro() {
            const dropdown = document.getElementById('dropdownFiltro');
            dropdown.classList.toggle('show');
        }
        
        // Aplicar filtro por tipo
        function aplicarFiltro(tipo) {
            filtroActual = tipo;
            
            // Actualizar texto del botón
            const textos = {
                'todos': 'Todos',
                'representante': 'Con Representante',
                'mayores': 'Mayores de Edad'
            };
            document.getElementById('filtroTexto').textContent = textos[tipo];
            
            // Marcar opción activa
            document.querySelectorAll('.dropdown-filter-menu a').forEach(a => {
                a.classList.remove('active');
            });
            event.target.closest('a').classList.add('active');
            
            // Cerrar dropdown
            document.getElementById('dropdownFiltro').classList.remove('show');
            
            // Aplicar filtro
            const searchQuery = document.getElementById('searchEstudiantes').value;
            filtrarEstudiantes(searchQuery);
        }
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-filter')) {
                const dropdown = document.getElementById('dropdownFiltro');
                if (dropdown) dropdown.classList.remove('show');
            }
        });

        // Cargar certificados
        async function cargarCertificados() {
            const container = document.getElementById('listaCertificados');
            if (!container) return;
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Cargando...</p></div>';
            
            try {
                const params = new URLSearchParams({
                    action: 'listar',
                    categoria_id: categoriaId,
                    grupo_id: grupoId
                });
                
                const response = await fetch(`api_certificados.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    certificados = data.certificados || [];
                    renderCertificados(certificados);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error al cargar</h3></div>';
            }
        }
        
        // Renderizar certificados
        function renderCertificados(lista) {
            const container = document.getElementById('listaCertificados');
            
            if (!lista || lista.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-certificate"></i><h3>No hay certificados</h3><p>Genera certificados desde la pestaña de estudiantes</p></div>';
                return;
            }
            
            container.innerHTML = lista.map(cert => `
                <div class="certificado-item">
                    <div class="certificado-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="certificado-info">
                        <h4>${escapeHtml(cert.nombre)}</h4>
                        <p><i class="fas fa-hashtag"></i> ${cert.codigo} &nbsp;|&nbsp; <i class="fas fa-calendar"></i> ${cert.fecha || '—'}</p>
                    </div>
                    <div class="certificado-actions">
                        <button class="btn-icon btn-view" onclick="previsualizarCertificado('${cert.codigo}')" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-download" onclick="descargarCertificado('${cert.codigo}')" title="Descargar">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            const badgeCert = document.getElementById('badgeCertificados');
            if (badgeCert) badgeCert.textContent = lista.length;
        }
        
        // Filtrar certificados
        function filtrarCertificados(query) {
            if (!query) {
                renderCertificados(certificados);
                return;
            }
            const filtrados = certificados.filter(c => 
                c.nombre.toLowerCase().includes(query.toLowerCase()) ||
                c.codigo.toLowerCase().includes(query.toLowerCase())
            );
            renderCertificados(filtrados);
        }
        
        // Actualizar estadísticas
        function actualizarStats() {
            const total = estudiantes.length;
            const conCert = estudiantes.filter(e => e.certificado_codigo).length;
            const pendientes = total - conCert;
            const menores = estudiantes.filter(e => e.es_menor == 1).length;
            
            document.getElementById('totalEstudiantes').textContent = total;
            document.getElementById('totalCertificados').textContent = conCert;
            document.getElementById('totalPendientes').textContent = pendientes;
            document.getElementById('totalMenores').textContent = menores;
            
            // Mostrar/ocultar botones según estudiantes pendientes y con certificado
            const btnGenerarTodos = document.getElementById('btnGenerarTodos');
            const btnHistorial = document.getElementById('btnHistorial');
            const dropdownDescargas = document.getElementById('dropdownDescargasContainer');
            
            if (btnGenerarTodos) {
                btnGenerarTodos.style.display = pendientes > 0 ? 'inline-flex' : 'none';
            }
            if (btnHistorial) {
                btnHistorial.style.display = conCert > 0 ? 'inline-flex' : 'none';
            }
            if (dropdownDescargas) {
                dropdownDescargas.style.display = conCert > 0 ? 'inline-block' : 'none';
            }
        }
        
        // Selección
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.est-checkbox').forEach(cb => cb.checked = checked);
            actualizarSeleccion();
        }
        
        function actualizarSeleccion() {
            const checkboxes = document.querySelectorAll('.est-checkbox:checked');
            seleccionados = Array.from(checkboxes).map(cb => cb.value);
            
            document.getElementById('selectedCount').textContent = seleccionados.length;
            document.getElementById('bulkActions').classList.toggle('active', seleccionados.length > 0);
            
            // Actualizar selectAll
            const total = document.querySelectorAll('.est-checkbox').length;
            const selectAll = document.getElementById('selectAll');
            selectAll.checked = seleccionados.length === total && total > 0;
            selectAll.indeterminate = seleccionados.length > 0 && seleccionados.length < total;
            
            // Mostrar/ocultar botones de acción masiva según selección
            const seleccionadosData = estudiantes.filter(e => seleccionados.includes(String(e.id)));
            const sinCert = seleccionadosData.filter(e => !e.certificado_codigo).length;
            const conCert = seleccionadosData.filter(e => e.certificado_codigo).length;
            
            const btnGenerar = document.getElementById('btnGenerarSeleccionados');
            const btnRegenerar = document.getElementById('btnRegenerarSeleccionados');
            const btnDescargarPdf = document.getElementById('btnDescargarPdfSeleccionados');
            const btnDescargarImg = document.getElementById('btnDescargarImgSeleccionados');
            
            if (btnGenerar) btnGenerar.style.display = sinCert > 0 ? 'inline-flex' : 'none';
            if (btnRegenerar) btnRegenerar.style.display = conCert > 0 ? 'inline-flex' : 'none';
            if (btnDescargarPdf) btnDescargarPdf.style.display = conCert > 0 ? 'inline-flex' : 'none';
            if (btnDescargarImg) btnDescargarImg.style.display = conCert > 0 ? 'inline-flex' : 'none';
        }
        
        function cancelarSeleccion() {
            document.querySelectorAll('.est-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            seleccionados = [];
            document.getElementById('bulkActions').classList.remove('active');
        }
        
        // Generar certificados
        function generarCertificadoIndividual(estudianteId, nombre) {
            seleccionados = [estudianteId];
            abrirModalGenerar([{ id: estudianteId, nombre: nombre }]);
        }
        
        function generarCertificadosSeleccionados() {
            const estudiantesSelec = estudiantes.filter(e => seleccionados.includes(String(e.id)));
            if (estudiantesSelec.length === 0) return;
            abrirModalGenerar(estudiantesSelec);
        }
        
        async function regenerarCertificadosSeleccionados() {
            const estudiantesSelec = estudiantes.filter(e => seleccionados.includes(String(e.id)) && e.certificado_codigo);
            if (estudiantesSelec.length === 0) {
                showNotification('No hay estudiantes con certificados para regenerar', 'warning');
                return;
            }
            
            // Abrir modal de regeneración
            abrirModalRegenerar(estudiantesSelec);
        }
        
        let estudiantesParaRegenerar = [];
        
        function actualizarListaRegenerar() {
            const container = document.getElementById('listaEstudiantesRegenerar');
            
            if (estudiantesParaRegenerar.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                        <i class="fas fa-user-slash fa-2x" style="margin-bottom: 10px; opacity: 0.5;"></i>
                        <p style="margin: 0;">No hay estudiantes en la lista</p>
                    </div>
                `;
                document.getElementById('btnConfirmarRegenerar').disabled = true;
                document.getElementById('btnConfirmarRegenerar').style.opacity = '0.5';
            } else {
                container.innerHTML = estudiantesParaRegenerar.map((e, index) => `
                    <div style="padding: 8px 12px; background: white; border-radius: 8px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e0e0e0; transition: all 0.2s;">
                        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                            <span style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(e.nombre)}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <span class="badge" style="background: #27ae60; color: white; font-size: 11px;">${e.certificado_codigo}</span>
                            <button type="button" onclick="quitarDeRegenerar(${index})" 
                                    style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: all 0.2s;"
                                    onmouseover="this.style.background='#fee2e2'" 
                                    onmouseout="this.style.background='none'"
                                    title="Quitar de la lista">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
                document.getElementById('btnConfirmarRegenerar').disabled = false;
                document.getElementById('btnConfirmarRegenerar').style.opacity = '1';
            }
            
            document.getElementById('regenerarCount').textContent = estudiantesParaRegenerar.length;
        }
        
        function quitarDeRegenerar(index) {
            estudiantesParaRegenerar.splice(index, 1);
            actualizarListaRegenerar();
            
            // Si la lista queda vacía, mostrar mensaje
            if (estudiantesParaRegenerar.length === 0) {
                showToast('No hay estudiantes para regenerar', 'warning');
            }
        }
        
        async function abrirModalRegenerar(lista) {
            estudiantesParaRegenerar = [...lista];
            
            actualizarListaRegenerar();
            
            document.getElementById('regenerarCount').textContent = estudiantesParaRegenerar.length;
            document.getElementById('regenerarRazon').value = '';
            
            // Mostrar loading de preview
            document.getElementById('regenerarPreviewLoading').style.display = 'block';
            document.getElementById('regenerarPreviewImage').style.display = 'none';
            
            // Abrir modal
            document.getElementById('modalRegenerar').classList.add('active');
            
            // Cargar previsualización
            try {
                const formData = new FormData();
                formData.append('tipo', 'categoria');
                formData.append('id', categoriaId);
                
                const response = await fetch('api_preview.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.preview_url) {
                    const img = document.getElementById('regenerarPreviewImage');
                    img.src = data.preview_url + '?t=' + Date.now();
                    img.onload = function() {
                        document.getElementById('regenerarPreviewLoading').style.display = 'none';
                        img.style.display = 'block';
                    };
                    img.onerror = function() {
                        document.getElementById('regenerarPreviewLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i><p>Error al cargar previsualización</p>';
                    };
                } else {
                    document.getElementById('regenerarPreviewLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i><p>' + (data.error || 'No se pudo generar la previsualización') + '</p>';
                }
            } catch (error) {
                console.error('Error cargando preview:', error);
                document.getElementById('regenerarPreviewLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i><p>Error de conexión</p>';
            }
        }
        
        async function confirmarRegeneracion() {
            if (estudiantesParaRegenerar.length === 0) return;
            
            const razon = document.getElementById('regenerarRazon').value.trim();
            
            const btn = document.getElementById('btnConfirmarRegenerar');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regenerando...';
            
            let exitosos = 0;
            let errores = 0;
            
            try {
                for (const est of estudiantesParaRegenerar) {
                    try {
                        const response = await fetch('api_generar_certificados.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'regenerar',
                                codigo: est.certificado_codigo,
                                razon_regeneracion: razon
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            exitosos++;
                        } else {
                            errores++;
                            console.error(`Error regenerando ${est.certificado_codigo}:`, result.error);
                        }
                    } catch (err) {
                        errores++;
                        console.error(`Error en petición para ${est.certificado_codigo}:`, err);
                    }
                }
                
                if (errores === 0) {
                    showNotification(`✅ ${exitosos} certificado${exitosos > 1 ? 's regenerados' : ' regenerado'} correctamente`, 'success');
                } else if (exitosos > 0) {
                    showNotification(`⚠️ ${exitosos} regenerado${exitosos > 1 ? 's' : ''}, ${errores} error${errores > 1 ? 'es' : ''}`, 'warning');
                } else {
                    showNotification('❌ Error al regenerar los certificados', 'error');
                }
                
                // Cerrar modal y recargar datos
                cerrarModal(null, 'modalRegenerar');
                cancelarSeleccion();
                cargarEstudiantes();
                
            } catch (error) {
                console.error('Error general:', error);
                showNotification('Error al procesar la regeneración', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }
        
        function generarTodosCertificados() {
            const sinCertificado = estudiantes.filter(e => !e.certificado_codigo);
            if (sinCertificado.length === 0) {
                showNotification('Todos los estudiantes ya tienen certificado', 'info');
                return;
            }
            seleccionados = sinCertificado.map(e => String(e.id));
            abrirModalGenerar(sinCertificado);
        }
        
        // Variable para almacenar estudiantes a generar (permite quitar de la lista)
        let estudiantesParaGenerar = [];
        
        async function abrirModalGenerar(lista) {
            // Guardar copia de la lista para poder modificarla
            estudiantesParaGenerar = [...lista];
            
            // Renderizar lista
            actualizarListaGenerar();
            
            // Resetear estado del preview
            document.getElementById('generarPreviewLoading').style.display = 'block';
            document.getElementById('generarPreviewLoading').innerHTML = '<i class="fas fa-spinner fa-spin fa-2x" style="color: #27ae60;"></i><p style="margin-top: 10px; color: #7f8c8d;">Cargando previsualización...</p>';
            document.getElementById('generarPreviewImage').style.display = 'none';
            document.getElementById('generarPlantillaAlerta').style.display = 'none';
            document.getElementById('linkConfigPlantilla').href = `config_grupo.php?id=${grupoId}`;
            document.getElementById('btnConfirmarGenerar').disabled = false;
            
            // Abrir modal
            document.getElementById('modalGenerar').classList.add('active');
            
            // Cargar previsualización
            try {
                const formData = new FormData();
                formData.append('tipo', 'categoria');
                formData.append('id', categoriaId);
                
                const response = await fetch('api_preview.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.preview_url) {
                    const img = document.getElementById('generarPreviewImage');
                    img.src = data.preview_url + '?t=' + Date.now();
                    img.onload = function() {
                        document.getElementById('generarPreviewLoading').style.display = 'none';
                        img.style.display = 'block';
                    };
                    img.onerror = function() {
                        document.getElementById('generarPreviewLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 24px;"></i><p style="margin-top: 10px; color: #7f8c8d;">Error al cargar previsualización</p>';
                    };
                } else {
                    // Plantilla no configurada
                    document.getElementById('generarPreviewLoading').innerHTML = '<i class="fas fa-image" style="color: #bdc3c7; font-size: 48px;"></i><p style="margin-top: 15px; color: #95a5a6;">Sin previsualización disponible</p>';
                    document.getElementById('generarPlantillaAlerta').style.display = 'block';
                    document.getElementById('btnConfirmarGenerar').disabled = true;
                }
            } catch (error) {
                console.error('Error cargando preview:', error);
                document.getElementById('generarPreviewLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 24px;"></i><p style="margin-top: 10px; color: #7f8c8d;">Error de conexión</p>';
            }
        }
        
        // Actualizar la lista visual de estudiantes a generar
        function actualizarListaGenerar() {
            const container = document.getElementById('listaEstudiantesGenerar');
            const sinCert = estudiantesParaGenerar.filter(e => !e.certificado_codigo);
            
            if (sinCert.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #95a5a6;"><i class="fas fa-users-slash" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>No hay estudiantes en la lista</div>';
                document.getElementById('generarCount').textContent = '0';
                document.getElementById('btnConfirmarGenerar').innerHTML = '<i class="fas fa-certificate"></i> Generar 0 Certificado(s)';
                document.getElementById('btnConfirmarGenerar').disabled = true;
                return;
            }
            
            container.innerHTML = estudiantesParaGenerar.map(e => `
                <div style="padding: 10px 14px; background: white; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e8e8e8;" data-estudiante-id="${e.id}">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                        <span style="font-weight: 500; color: #2c3e50;">${escapeHtml(e.nombre)}</span>
                        ${e.certificado_codigo ? '<span class="badge badge-success" style="font-size: 11px;">Ya tiene</span>' : '<span class="badge" style="background: #e8f5e9; color: #27ae60; font-size: 11px;">Nuevo</span>'}
                    </div>
                    ${!e.certificado_codigo ? `<button type="button" onclick="quitarDeListaGenerar(${e.id})" style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 5px 8px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'" title="Quitar de la lista"><i class="fas fa-times"></i></button>` : ''}
                </div>
            `).join('');
            
            document.getElementById('generarCount').textContent = sinCert.length;
            document.getElementById('btnConfirmarGenerar').innerHTML = `<i class="fas fa-certificate"></i> Generar ${sinCert.length} Certificado(s)`;
            document.getElementById('btnConfirmarGenerar').disabled = false;
            
            // Actualizar array de seleccionados
            seleccionados = sinCert.map(e => String(e.id));
        }
        
        // Quitar estudiante de la lista de generación
        function quitarDeListaGenerar(estudianteId) {
            estudiantesParaGenerar = estudiantesParaGenerar.filter(e => e.id != estudianteId);
            actualizarListaGenerar();
            
            if (estudiantesParaGenerar.filter(e => !e.certificado_codigo).length === 0) {
                showNotification('No hay estudiantes para generar certificados', 'warning');
            }
        }
        
        async function confirmarGeneracion() {
            const fecha = document.getElementById('fechaCertificado').value;
            
            const btn = document.getElementById('btnConfirmarGenerar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            
            try {
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generar_batch',
                        categoria_id: categoriaId,
                        grupo_id: grupoId,
                        estudiantes_ids: seleccionados,
                        fecha: fecha
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(`${data.exitosos || seleccionados.length} certificado(s) generado(s)`, 'success');
                    cerrarModal(null, 'modalGenerar');
                    cancelarSeleccion();
                    cargarDatos();
                } else {
                    if (data.error_type === 'PLANTILLA_NO_CONFIGURADA') {
                        showNotification(data.message, 'warning');
                    } else {
                        throw new Error(data.message);
                    }
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                const sinCert = seleccionados.length;
                btn.innerHTML = `<i class="fas fa-certificate"></i> Generar ${sinCert} Certificado(s)`;
            }
        }
        
        // Ver/Descargar certificado
        async function previsualizarCertificado(codigo) {
            currentPreviewCode = codigo;
            
            // Mostrar modal con loading
            document.getElementById('previewLoading').style.display = 'block';
            document.getElementById('previewLoading').innerHTML = '<i class="fas fa-spinner fa-spin fa-2x"></i><p>Cargando certificado...</p>';
            document.getElementById('previewImage').style.display = 'none';
            document.getElementById('previewTitulo').textContent = 'Cargando...';
            document.getElementById('previewCodigo').textContent = '—';
            document.getElementById('previewNombre').textContent = '—';
            document.getElementById('previewFecha').textContent = '—';
            document.getElementById('previewFechaCreacion').textContent = '—';
            document.getElementById('previewRazonContainer').style.display = 'none';
            
            document.getElementById('modalPreview').classList.add('active');
            
            try {
                // Obtener datos del certificado
                const response = await fetch('api_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_by_code', code: codigo })
                });
                
                const data = await response.json();
                
                if (data.success && data.certificado) {
                    const cert = data.certificado;
                    
                    // Llenar información
                    document.getElementById('previewTitulo').textContent = cert.nombre;
                    document.getElementById('previewCodigo').textContent = cert.codigo;
                    document.getElementById('previewNombre').textContent = cert.nombre;
                    document.getElementById('previewFecha').textContent = formatearFecha(cert.fecha);
                    document.getElementById('previewFechaCreacion').textContent = formatearFechaHora(cert.fecha_creacion);
                    
                    // Razón
                    if (cert.razon) {
                        document.getElementById('previewRazon').textContent = cert.razon;
                        document.getElementById('previewRazonContainer').style.display = 'block';
                    }
                    
                    // Cargar imagen usando la API get_image
                    cargarImagenCertificado(codigo);
                } else {
                    throw new Error(data.message || 'No se encontró el certificado');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
                cerrarModal(null, 'modalPreview');
            }
        }
        
        // Cargar imagen del certificado
        async function cargarImagenCertificado(codigo) {
            try {
                const response = await fetch('api_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_image', code: codigo })
                });
                
                const data = await response.json();
                
                if (data.success && data.image) {
                    const img = document.getElementById('previewImage');
                    img.onload = function() {
                        document.getElementById('previewLoading').style.display = 'none';
                        img.style.display = 'block';
                    };
                    img.onerror = function() {
                        document.getElementById('previewLoading').innerHTML = '<i class="fas fa-image" style="color: #7f8c8d;"></i><p>No se pudo cargar la imagen</p>';
                    };
                    img.src = data.image;
                } else {
                    document.getElementById('previewLoading').innerHTML = `<i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i><p>${data.message || 'No se pudo cargar la imagen'}</p>`;
                }
            } catch (error) {
                console.error('Error cargando imagen:', error);
                document.getElementById('previewLoading').innerHTML = '<i class="fas fa-image" style="color: #7f8c8d;"></i><p>Error al cargar la imagen</p>';
            }
        }
        
        // Descargar desde el modal de preview
        function descargarDesdePreview(formato) {
            if (!currentPreviewCode) return;
            
            if (formato === 'pdf') {
                window.location.href = `api_generar_certificados.php?action=descargar_pdf&codigo=${currentPreviewCode}`;
            } else {
                window.location.href = `api_generar_certificados.php?action=descargar_imagen&codigo=${currentPreviewCode}`;
            }
        }
        
        // Formatear fechas
        function formatearFecha(fecha) {
            if (!fecha) return '—';
            const d = new Date(fecha);
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
        }
        
        function formatearFechaHora(fecha) {
            if (!fecha) return '—';
            const d = new Date(fecha);
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
        
        function descargarCertificado(codigo) {
            window.location.href = `api_generar_certificados.php?action=descargar_pdf&codigo=${codigo}`;
            cerrarTodosDropdownsDescarga();
        }
        
        // Descargar imagen de certificado
        function descargarCertificadoImagen(codigo) {
            window.location.href = `api_generar_certificados.php?action=descargar_imagen&codigo=${codigo}`;
            cerrarTodosDropdownsDescarga();
        }
        
        // Toggle dropdown de descarga individual
        function toggleDownloadDropdown(event, codigo) {
            event.stopPropagation();
            const menu = document.getElementById(`downloadMenu-${codigo}`);
            const isOpen = menu.classList.contains('show');
            
            // Cerrar todos los dropdowns primero
            cerrarTodosDropdownsDescarga();
            
            // Si no estaba abierto, abrirlo
            if (!isOpen) {
                menu.classList.add('show');
            }
        }
        
        // Cerrar todos los dropdowns de descarga
        function cerrarTodosDropdownsDescarga() {
            document.querySelectorAll('.btn-dropdown-menu').forEach(m => m.classList.remove('show'));
        }
        
        // Cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-dropdown-wrapper')) {
                cerrarTodosDropdownsDescarga();
            }
        });
        
        // Descarga masiva de certificados
        async function descargarCertificadosSeleccionados(formato) {
            // Obtener códigos de certificados de los estudiantes seleccionados que tienen certificado
            const codigosCert = estudiantes
                .filter(e => seleccionados.includes(String(e.id)) && e.certificado_codigo)
                .map(e => e.certificado_codigo);
            
            if (codigosCert.length === 0) {
                showNotification('Selecciona estudiantes con certificados para descargar', 'info');
                return;
            }
            
            showNotification(`Preparando descarga de ${codigosCert.length} certificado(s)...`, 'info');
            
            try {
                if (formato === 'pdf') {
                    // Generar PDF consolidado con todos los certificados
                    const response = await fetch('api_generar_certificados.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'descargar_pdf_consolidado',
                            codigos: codigosCert
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Descargar el archivo generado
                        window.location.href = data.archivo;
                        showNotification(`${codigosCert.length} certificados descargados en PDF`, 'success');
                    } else {
                        throw new Error(data.message || 'Error al generar PDF');
                    }
                } else {
                    // Descargar imágenes en ZIP
                    const response = await fetch('api_generar_certificados.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'descargar_imagenes_zip',
                            codigos: codigosCert
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = data.archivo;
                        showNotification(`${codigosCert.length} certificados descargados en ZIP`, 'success');
                    } else {
                        throw new Error(data.message || 'Error al generar ZIP');
                    }
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }
        
        // Descargar todos los certificados de la categoría
        async function descargarTodosCertificados(formato) {
            const codigosCert = estudiantes
                .filter(e => e.certificado_codigo)
                .map(e => e.certificado_codigo);
            
            if (codigosCert.length === 0) {
                showNotification('No hay certificados para descargar', 'info');
                return;
            }
            
            // Cerrar dropdown
            document.getElementById('dropdownDescargas').style.display = 'none';
            
            if (!confirm(`¿Descargar ${codigosCert.length} certificado(s) en formato ${formato.toUpperCase()}?`)) {
                return;
            }
            
            showNotification(`Preparando descarga de ${codigosCert.length} certificado(s)...`, 'info');
            
            try {
                const action = formato === 'pdf' ? 'descargar_pdf_consolidado' : 'descargar_imagenes_zip';
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        codigos: codigosCert
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.archivo;
                    showNotification(`${codigosCert.length} certificados descargados`, 'success');
                } else {
                    throw new Error(data.message || 'Error al generar archivo');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }
        
        // Toggle dropdown de descargas
        function toggleDropdownDescargas() {
            const dropdown = document.getElementById('dropdownDescargas');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
        
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownDescargas');
            const btn = document.getElementById('btnDescargas');
            if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
        
        // Modal agregar estudiante
        function abrirModalAgregar() {
            const form = document.getElementById('formNuevoEstudiante');
            if (form) form.reset();
            document.getElementById('resultadosBusqueda').innerHTML = '';
            document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
            document.querySelectorAll('.input-error, .input-valid').forEach(el => el.classList.remove('input-error', 'input-valid'));
            toggleMenorEdad(false);
            resetearListaMenores();
            contadorMenores = 1;
            document.getElementById('modalAgregar').classList.add('active');
        }
        
        async function buscarEstudianteExistente(query) {
            const container = document.getElementById('resultadosBusqueda');
            if (!query || query.length < 2) {
                container.innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`api_estudiantes.php?action=buscar_disponibles&q=${encodeURIComponent(query)}&categoria_id=${categoriaId}`);
                const data = await response.json();
                
                if (data.success && data.estudiantes && data.estudiantes.length > 0) {
                    container.innerHTML = data.estudiantes.map(e => {
                        const esSoloRep = e.es_solo_representante == 1;
                        const badgeRep = esSoloRep ? '<span class="badge badge-secondary" style="font-size: 10px; margin-left: 5px;">Representante</span>' : '';
                        return `
                            <div style="padding: 10px; background: ${esSoloRep ? '#f3e5f5' : '#f8f9fa'}; border-radius: 8px; margin-bottom: 5px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="matricularExistente(${e.id}, '${escapeHtml(e.nombre)}', ${esSoloRep})">
                                <div>
                                    <strong>${escapeHtml(e.nombre)}</strong>${badgeRep}
                                    ${e.cedula ? `<small style="color: #7f8c8d;"> - ${e.cedula}</small>` : ''}
                                </div>
                                <i class="fas fa-plus-circle" style="color: <?= $color_principal ?>"></i>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = '<p style="color: #7f8c8d; text-align: center; padding: 10px;">No se encontraron estudiantes</p>';
                }
            } catch (error) {
                console.error(error);
            }
        }
        
        async function matricularExistente(estudianteId, nombre, esSoloRepresentante = false) {
            try {
                const formData = new FormData();
                formData.append('action', 'matricular');
                formData.append('estudiante_id', estudianteId);
                formData.append('categoria_id', categoriaId);
                formData.append('convertir_representante', esSoloRepresentante ? '1' : '0');
                if (periodoId) formData.append('periodo_id', periodoId);
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const msg = esSoloRepresentante 
                        ? `${nombre} convertido a estudiante y matriculado` 
                        : `${nombre} agregado correctamente`;
                    showNotification(msg, 'success');
                    cerrarModal(null, 'modalAgregar');
                    cargarDatos();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }

        // ===== Helpers formulario completo =====
        function validarCedulaInput(input) { input.value = input.value.replace(/[^0-9]/g, ''); }
        function validarCelularInput(input) { input.value = input.value.replace(/[^0-9]/g, ''); }
        
        function validarCedulaEcuatoriana(cedula) {
            if (!/^[0-9]{10}$/.test(cedula)) return false;
            const provincia = parseInt(cedula.substring(0, 2));
            if (provincia < 1 || provincia > 24) return false;
            const tercerDigito = parseInt(cedula.charAt(2));
            if (tercerDigito > 6) return false;
            const coef = [2,1,2,1,2,1,2,1,2];
            let suma = 0;
            for (let i = 0; i < 9; i++) { let val = parseInt(cedula.charAt(i)) * coef[i]; if (val > 9) val -= 9; suma += val; }
            const digito = (10 - (suma % 10)) % 10;
            return digito === parseInt(cedula.charAt(9));
        }
        
        async function verificarCedula(input) {
            const cedula = input.value.trim();
            const errorEl = document.getElementById('cedula_error');
            if (cedula === '') { input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true; }
            if (cedula.length !== 10) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='La cédula debe tener 10 dígitos'; return false; }
            if (!validarCedulaEcuatoriana(cedula)) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Cédula inválida'; return false; }
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                if (data.existe) {
                    // Si es solo representante, autocompletar datos y permitir
                    if (data.es_solo_representante) {
                        const est = data.estudiante;
                        document.getElementById('nuevo_nombre').value = est.nombre || '';
                        if (est.fecha_nacimiento) {
                            document.getElementById('nuevo_fecha_nacimiento').value = est.fecha_nacimiento;
                        }
                        if (est.celular) {
                            let celular = est.celular.replace(/^\+?593/, '');
                            document.getElementById('nuevo_celular').value = celular;
                            document.getElementById('nuevo_celular').classList.add('input-valid');
                        }
                        if (est.email) {
                            document.getElementById('nuevo_email').value = est.email;
                            document.getElementById('nuevo_email').classList.add('input-valid');
                        }
                        input.classList.remove('input-error'); 
                        input.classList.add('input-valid'); 
                        errorEl.innerHTML = `<span style="color: #27ae60;"><i class="fas fa-check-circle"></i> Datos cargados (era representante): ${est.nombre}</span>`;
                        // Guardar ID para usar al matricular
                        input.dataset.representanteId = est.id;
                        return true;
                    }
                    input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent = `Cédula ya registrada (${data.estudiante.nombre})`; return false;
                }
            } catch (err) { console.error('Error verificando cédula:', err); }
            input.dataset.representanteId = ''; // Limpiar ID si no es representante
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        function verificarCelular(input) {
            const celular = input.value.trim();
            const errorEl = document.getElementById('celular_error');
            if (celular === '') { input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true; }
            if (celular.length !== 9) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='El celular debe tener 9 dígitos'; return false; }
            if (!celular.startsWith('9')) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Número de celular inválido (debe empezar con 9)'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        function verificarEmail(input) {
            const email = input.value.trim();
            const errorEl = document.getElementById('email_error');
            if (email === '') { input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true; }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Email inválido'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        async function verificarCedulaMenor(input) {
            const cedula = input.value.trim();
            const formGroup = input.closest('.form-group') || input.closest('.form-group-sm');
            const errorEl = formGroup ? formGroup.querySelector('.menor-cedula-error') : null;
            if (cedula === '') { input.classList.remove('input-error','input-valid'); if (errorEl) errorEl.textContent=''; return true; }
            if (cedula.length !== 10) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='La cédula debe tener 10 dígitos'; return false; }
            if (!validarCedulaEcuatoriana(cedula)) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='Cédula inválida'; return false; }
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                if (data.existe) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent = `Cédula ya registrada (${data.estudiante.nombre})`; return false; }
            } catch (err) { console.error('Error verificando cédula:', err); }
            const todasCedulas = document.querySelectorAll('input[name*="[cedula]"]');
            let duplicada = false;
            todasCedulas.forEach(otro => { if (otro !== input && otro.value.trim() === cedula) duplicada = true; });
            const cedulaRep = document.getElementById('representante_cedula');
            if (cedulaRep && cedulaRep.value.trim() === cedula) duplicada = true;
            if (duplicada) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='Cédula duplicada en el formulario'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); if (errorEl) errorEl.textContent=''; return true;
        }
        
        function verificarCelularMenor(input) {
            const celular = input.value.trim();
            const formGroup = input.closest('.form-group') || input.closest('.form-group-sm');
            const errorEl = formGroup ? formGroup.querySelector('.menor-celular-error') : null;
            if (celular === '') { input.classList.remove('input-error','input-valid'); if (errorEl) errorEl.textContent=''; return true; }
            let limpio = celular; if (limpio.startsWith('0')) limpio = limpio.substring(1);
            if (limpio.length !== 9) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='El celular debe tener 9 dígitos'; return false; }
            if (!limpio.startsWith('9')) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='Número inválido (debe empezar con 9)'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); if (errorEl) errorEl.textContent=''; return true;
        }
        
        function verificarEmailMenor(input) {
            const email = input.value.trim();
            const formGroup = input.closest('.form-group') || input.closest('.form-group-sm');
            const errorEl = formGroup ? formGroup.querySelector('.menor-email-error') : null;
            if (email === '') { input.classList.remove('input-error','input-valid'); if (errorEl) errorEl.textContent=''; return true; }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) { input.classList.add('input-error'); input.classList.remove('input-valid'); if (errorEl) errorEl.textContent='Email inválido'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); if (errorEl) errorEl.textContent=''; return true;
        }
        
        async function verificarCedulaRepresentante(input) {
            const cedula = input.value.trim();
            const errorEl = document.getElementById('representante_cedula_error');
            if (cedula === '') {
                if (document.getElementById('es_menor').checked) { input.classList.add('input-error'); errorEl.textContent='La cédula del representante es obligatoria'; return false; }
                input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true;
            }
            if (cedula.length !== 10) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='La cédula debe tener 10 dígitos'; return false; }
            if (!validarCedulaEcuatoriana(cedula)) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Cédula inválida'; return false; }
            try {
                const response = await fetch(`api_categoria_estudiantes.php?action=verificar_cedula&cedula=${cedula}`);
                const data = await response.json();
                if (data.existe) {
                    // Si existe como estudiante, autocompletar datos del representante
                    const est = data.estudiante;
                    document.getElementById('representante_nombre').value = est.nombre || '';
                    if (est.fecha_nacimiento) {
                        document.getElementById('representante_fecha_nacimiento').value = est.fecha_nacimiento;
                    }
                    if (est.celular) {
                        // Quitar prefijo 593 si existe
                        let celular = est.celular.replace(/^\+?593/, '');
                        document.getElementById('representante_celular').value = celular;
                        document.getElementById('representante_celular').classList.add('input-valid');
                    }
                    if (est.email) {
                        document.getElementById('representante_email').value = est.email;
                        document.getElementById('representante_email').classList.add('input-valid');
                    }
                    // Mostrar mensaje informativo (no error)
                    input.classList.remove('input-error'); 
                    input.classList.add('input-valid'); 
                    errorEl.innerHTML = `<span style="color: #27ae60;"><i class="fas fa-check-circle"></i> Datos cargados de: ${est.nombre}</span>`;
                    return true;
                }
            } catch (err) { console.error('Error verificando cédula:', err); }
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        function verificarCelularRepresentante(input) {
            const celular = input.value.trim();
            const errorEl = document.getElementById('representante_celular_error');
            if (celular === '') { if (document.getElementById('es_menor').checked) { input.classList.add('input-error'); errorEl.textContent='El celular del representante es obligatorio'; return false; } input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true; }
            if (celular.length !== 9) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='El celular debe tener 9 dígitos'; return false; }
            if (!celular.startsWith('9')) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Número de celular inválido (debe empezar con 9)'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        function verificarEmailRepresentante(input) {
            const email = input.value.trim();
            const errorEl = document.getElementById('representante_email_error');
            if (email === '') { input.classList.remove('input-error','input-valid'); errorEl.textContent=''; return true; }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) { input.classList.add('input-error'); input.classList.remove('input-valid'); errorEl.textContent='Email inválido'; return false; }
            input.classList.remove('input-error'); input.classList.add('input-valid'); errorEl.textContent=''; return true;
        }
        
        // Gestión de menores
        function toggleMenorEdad(esMenor = document.getElementById('es_menor')?.checked) {
            const datosMayor = document.getElementById('datosEstudianteMayor');
            const datosRepresentante = document.getElementById('datosRepresentante');
            const listaMenores = document.getElementById('listaMenores');
            if (esMenor) {
                datosMayor.style.display = 'none';
                datosRepresentante.style.display = 'block';
                document.getElementById('representante_nombre').required = true;
                document.getElementById('representante_cedula').required = true;
                document.getElementById('representante_celular').required = true;
                document.getElementById('nuevo_nombre').required = false;
                // Solo nombre y fecha de nacimiento son obligatorios para menores
                listaMenores.querySelectorAll('input[name*="[nombre]"], input[name*="[fecha_nacimiento]"]').forEach(inp => inp.required = true);
                // Cédula, celular y email son opcionales
                listaMenores.querySelectorAll('input[name*="[cedula]"], input[name*="[celular]"], input[name*="[email]"]').forEach(inp => inp.required = false);
            } else {
                datosMayor.style.display = 'block';
                datosRepresentante.style.display = 'none';
                document.getElementById('representante_nombre').required = false;
                document.getElementById('representante_cedula').required = false;
                document.getElementById('representante_celular').required = false;
                document.getElementById('nuevo_nombre').required = true;
                listaMenores.querySelectorAll('input[name*="[nombre]"], input[name*="[fecha_nacimiento]"]').forEach(inp => inp.required = false);
            }
        }
        
        function agregarMenor() {
            const lista = document.getElementById('listaMenores');
            const index = contadorMenores;
            contadorMenores++;
            const reqAttr = document.getElementById('es_menor').checked ? 'required' : '';
            const menorHtml = `
                <div class="menor-item" data-index="${index}">
                    <div class="menor-header">
                        <span class="menor-numero"><i class="fas fa-child"></i> Menor #${index + 1}</span>
                        <button type="button" onclick="removerMenor(${index})" class="btn-remove-menor" title="Quitar menor">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Menor *</label>
                            <input type="text" name="menores[${index}][nombre]" ${reqAttr} placeholder="Nombre completo del menor">
                        </div>
                        <div class="form-group">
                            <label>Fecha de Nacimiento *</label>
                            <input type="date" name="menores[${index}][fecha_nacimiento]" ${reqAttr} max="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cédula <small style="color:#95a5a6">(opcional - 10 dígitos)</small></label>
                            <input type="text" name="menores[${index}][cedula]" maxlength="10" placeholder="Ej: 1712345678" oninput="validarCedulaInput(this)" onblur="verificarCedulaMenor(this)">
                            <small class="field-error menor-cedula-error"></small>
                        </div>
                        <div class="form-group">
                            <label>Celular <small style="color:#95a5a6">(opcional - 9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix">+593</span>
                                <input type="text" name="menores[${index}][celular]" maxlength="9" placeholder="9XXXXXXXX" oninput="validarCelularInput(this)" onblur="verificarCelularMenor(this)">
                            </div>
                            <small class="field-error menor-celular-error"></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="menores[${index}][email]" placeholder="correo@ejemplo.com" onblur="verificarEmailMenor(this)">
                        <small class="field-error menor-email-error"></small>
                    </div>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', menorHtml);
            actualizarNumerosMenores();
            lista.scrollTop = lista.scrollHeight;
        }
        
        function removerMenor(index) {
            const item = document.querySelector(`.menor-item[data-index="${index}"]`);
            if (item) { item.remove(); actualizarNumerosMenores(); }
        }
        
        function actualizarNumerosMenores() {
            document.querySelectorAll('.menor-item').forEach((item, i) => {
                const numero = item.querySelector('.menor-numero');
                if (numero) numero.innerHTML = `<i class="fas fa-child"></i> Menor #${i + 1}`;
            });
        }
        
        function resetearListaMenores() {
            const lista = document.getElementById('listaMenores');
            contadorMenores = 1;
            lista.innerHTML = `
                <div class="menor-item" data-index="0">
                    <div class="menor-header">
                        <span class="menor-numero"><i class="fas fa-child"></i> Menor #1</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Menor *</label>
                            <input type="text" name="menores[0][nombre]" placeholder="Nombre completo del menor">
                        </div>
                        <div class="form-group">
                            <label>Fecha de Nacimiento *</label>
                            <input type="date" name="menores[0][fecha_nacimiento]" max="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cédula <small style="color:#95a5a6">(opcional - 10 dígitos)</small></label>
                            <input type="text" name="menores[0][cedula]" maxlength="10" placeholder="Ej: 1712345678" oninput="validarCedulaInput(this)" onblur="verificarCedulaMenor(this)">
                            <small class="field-error menor-cedula-error"></small>
                        </div>
                        <div class="form-group">
                            <label>Celular <small style="color:#95a5a6">(opcional - 9 dígitos)</small></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix">+593</span>
                                <input type="text" name="menores[0][celular]" maxlength="9" placeholder="9XXXXXXXX" oninput="validarCelularInput(this)" onblur="verificarCelularMenor(this)">
                            </div>
                            <small class="field-error menor-celular-error"></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="menores[0][email]" placeholder="correo@ejemplo.com" onblur="verificarEmailMenor(this)">
                        <small class="field-error menor-email-error"></small>
                    </div>
                </div>
            `;
        }
        
        async function guardarNuevoEstudiante(event) {
            event.preventDefault();
            const esMenor = document.getElementById('es_menor').checked;
            let errores = false;
            
            if (esMenor) {
                const repNombre = document.getElementById('representante_nombre');
                const repCedula = document.getElementById('representante_cedula');
                const repCelular = document.getElementById('representante_celular');
                const repEmail = document.getElementById('representante_email');
                
                if (!repNombre.value.trim()) {
                    showNotification('El nombre del representante es obligatorio', 'warning');
                    repNombre.focus();
                    return;
                }
                const cedulaRepValida = await verificarCedulaRepresentante(repCedula);
                if (!cedulaRepValida) errores = true;
                if (!verificarCelularRepresentante(repCelular)) errores = true;
                if (!verificarEmailRepresentante(repEmail)) errores = true;
                
                const menoresItems = document.querySelectorAll('.menor-item');
                if (menoresItems.length === 0) {
                    showNotification('Debe agregar al menos un menor', 'warning');
                    return;
                }
                
                let menoresValidos = [];
                for (const item of menoresItems) {
                    const nombreInput = item.querySelector('input[name*="[nombre]"]');
                    const fechaInput = item.querySelector('input[name*="[fecha_nacimiento]"]');
                    const cedulaInput = item.querySelector('input[name*="[cedula]"]');
                    const celularInput = item.querySelector('input[name*="[celular]"]');
                    const emailInput = item.querySelector('input[name*="[email]"]');
                    
                    if (!nombreInput.value.trim()) { showNotification('El nombre del menor es obligatorio', 'warning'); nombreInput.focus(); return; }
                    if (!fechaInput.value) { showNotification('La fecha de nacimiento del menor es obligatoria', 'warning'); fechaInput.focus(); return; }
                    
                    if (cedulaInput && cedulaInput.value.trim()) {
                        const cedulaValida = await verificarCedulaMenor(cedulaInput);
                        if (!cedulaValida) { errores = true; cedulaInput.focus(); }
                    }
                    if (celularInput && celularInput.value.trim() && !verificarCelularMenor(celularInput)) { errores = true; celularInput.focus(); }
                    if (emailInput && emailInput.value.trim() && !verificarEmailMenor(emailInput)) { errores = true; emailInput.focus(); }
                    
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
                
                if (errores) { showNotification('Por favor corrige los errores en el formulario', 'error'); return; }
                
                let repCelularVal = repCelular.value.trim();
                if (repCelularVal.startsWith('0')) repCelularVal = repCelularVal.substring(1);
                repCelularVal = '+593' + repCelularVal;
                
                const datosEnvio = {
                    action: 'crear_menores_con_representante',
                    categoria_id: categoriaId,
                    periodo_id: periodoId,
                    representante: {
                        nombre: repNombre.value.trim(),
                        cedula: repCedula.value.trim(),
                        celular: repCelularVal,
                        email: repEmail.value.trim(),
                        fecha_nacimiento: document.getElementById('representante_fecha_nacimiento').value || null
                    },
                    menores: menoresValidos
                };
                
                try {
                    const response = await fetch('api_categoria_estudiantes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(datosEnvio)
                    });
                    const data = await response.json();
                    if (data.success) {
                        cerrarModal(null, 'modalAgregar');
                        document.getElementById('buscarExistente').value = '';
                        document.getElementById('resultadosBusqueda').innerHTML = '';
                        cargarDatos();
                        const cantidad = data.cantidad || menoresValidos.length;
                        showNotification(`${cantidad} estudiante(s) creado(s) y matriculado(s) correctamente`, 'success');
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Error al crear estudiantes', 'error');
                }
            } else {
                const cedulaInput = document.getElementById('nuevo_cedula');
                const celularInput = document.getElementById('nuevo_celular');
                const emailInput = document.getElementById('nuevo_email');
                const cedulaValida = await verificarCedula(cedulaInput);
                if (!cedulaValida) errores = true;
                if (!verificarCelular(celularInput)) errores = true;
                if (!verificarEmail(emailInput)) errores = true;
                if (errores) { showNotification('Por favor corrige los errores en el formulario', 'error'); return; }
                
                // Verificar si es un representante existente que se está convirtiendo en estudiante
                const representanteId = cedulaInput.dataset.representanteId;
                
                if (representanteId) {
                    // El representante ya existe, solo matricularlo y actualizar sus datos
                    try {
                        // Primero actualizar datos si es necesario
                        let celular = celularInput.value.trim();
                        if (celular) {
                            if (celular.startsWith('0')) celular = celular.substring(1);
                            celular = '+593' + celular;
                        }
                        
                        const formData = new FormData();
                        formData.append('action', 'matricular');
                        formData.append('estudiante_id', representanteId);
                        formData.append('categoria_id', categoriaId);
                        formData.append('convertir_representante', '1');
                        if (periodoId) formData.append('periodo_id', periodoId);
                        
                        const response = await fetch('api_categoria_estudiantes.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.success) {
                            cerrarModal(null, 'modalAgregar');
                            document.getElementById('buscarExistente').value = '';
                            document.getElementById('resultadosBusqueda').innerHTML = '';
                            cargarDatos();
                            showNotification('Representante convertido a estudiante y matriculado', 'success');
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Error al matricular estudiante', 'error');
                    }
                } else {
                    // Crear nuevo estudiante
                    const formData = new FormData(event.target);
                    formData.append('action', 'crear_y_matricular');
                    formData.append('categoria_id', categoriaId);
                    if (periodoId) formData.append('periodo_id', periodoId);
                    let celular = celularInput.value.trim();
                    if (celular) {
                        if (celular.startsWith('0')) celular = celular.substring(1);
                        formData.set('celular', '+593' + celular);
                    }
                    try {
                        const response = await fetch('api_categoria_estudiantes.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.success) {
                            cerrarModal(null, 'modalAgregar');
                            document.getElementById('buscarExistente').value = '';
                            document.getElementById('resultadosBusqueda').innerHTML = '';
                            cargarDatos();
                            showNotification('Estudiante creado y matriculado correctamente', 'success');
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Error al crear estudiante', 'error');
                    }
                }
            }
        }
        
        // Abrir modal para editar estudiante
        function abrirModalEditar(estudianteId) {
            const est = estudiantes.find(e => e.id == estudianteId);
            if (!est) {
                showNotification('Estudiante no encontrado', 'error');
                return;
            }
            
            // Llenar el formulario con los datos actuales
            document.getElementById('edit_estudiante_id').value = est.id;
            document.getElementById('edit_nombre').value = est.nombre || '';
            document.getElementById('edit_fecha_nacimiento').value = est.fecha_nacimiento || '';
            document.getElementById('edit_cedula').value = est.cedula || '';
            
            // Celular: quitar +593 para mostrarlo
            let celular = est.celular || '';
            if (celular.startsWith('+593')) {
                celular = celular.substring(4);
            }
            document.getElementById('edit_celular').value = celular;
            
            document.getElementById('edit_email').value = est.email || '';
            document.getElementById('edit_es_menor').checked = est.es_menor == 1;
            document.getElementById('edit_destacado').checked = est.destacado == 1;
            
            document.getElementById('modalEditar').classList.add('active');
        }
        
        // Guardar edición de estudiante
        async function guardarEdicionEstudiante(event) {
            event.preventDefault();
            
            const id = document.getElementById('edit_estudiante_id').value;
            const nombre = document.getElementById('edit_nombre').value.trim();
            const fecha_nacimiento = document.getElementById('edit_fecha_nacimiento').value;
            const cedula = document.getElementById('edit_cedula').value.trim();
            const celular = document.getElementById('edit_celular').value.trim();
            const email = document.getElementById('edit_email').value.trim();
            const es_menor = document.getElementById('edit_es_menor').checked ? 1 : 0;
            const destacado = document.getElementById('edit_destacado').checked ? 1 : 0;
            
            if (!nombre) {
                showNotification('El nombre es requerido', 'warning');
                return;
            }
            
            try {
                const response = await fetch('api_estudiantes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        id: id,
                        nombre: nombre,
                        fecha_nacimiento: fecha_nacimiento || null,
                        cedula: cedula || null,
                        celular: celular || null,
                        email: email || null,
                        es_menor: es_menor,
                        destacado: destacado
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Estudiante actualizado correctamente', 'success');
                    cerrarModal(null, 'modalEditar');
                    cargarDatos();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }
        
        // Variables para el modal de confirmación de quitar estudiante
        let matriculaIdAQuitar = null;
        let nombreEstudianteAQuitar = null;
        
        // Quitar estudiante - Abrir modal de confirmación
        function quitarEstudiante(matriculaId, nombre) {
            matriculaIdAQuitar = matriculaId;
            nombreEstudianteAQuitar = nombre;
            document.getElementById('confirmarQuitarNombre').textContent = nombre;
            document.getElementById('modalConfirmarQuitar').classList.add('active');
        }
        
        function cerrarConfirmarQuitar() {
            document.getElementById('modalConfirmarQuitar').classList.remove('active');
            matriculaIdAQuitar = null;
            nombreEstudianteAQuitar = null;
        }
        
        async function confirmarQuitarEstudiante() {
            if (!matriculaIdAQuitar) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'quitar');
                formData.append('id', matriculaIdAQuitar);
                
                const response = await fetch('api_categoria_estudiantes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cerrarConfirmarQuitar();
                    showNotification('Estudiante quitado de la categoría', 'success');
                    cargarDatos();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }
        
        // Utilidades
        function cerrarModal(event, modalId) {
            if (!event || event.target.classList.contains('modal-overlay')) {
                document.getElementById(modalId).classList.remove('active');
                
                // Limpiar campos del modal de agregar estudiante
                if (modalId === 'modalAgregar') {
                    limpiarModalAgregar();
                }
            }
        }
        
        function limpiarModalAgregar() {
            // Limpiar búsqueda de estudiante existente
            const buscarExistente = document.getElementById('buscarExistente');
            if (buscarExistente) {
                buscarExistente.value = '';
            }
            const resultados = document.getElementById('resultadosBusqueda');
            if (resultados) {
                resultados.innerHTML = '';
            }
            
            // Limpiar formulario de nuevo estudiante
            const form = document.getElementById('formNuevoEstudiante');
            if (form) {
                form.reset();
            }
            
            // Limpiar clases de validación
            const inputs = document.querySelectorAll('#modalAgregar input');
            inputs.forEach(input => {
                input.classList.remove('input-error', 'input-valid');
                delete input.dataset.representanteId;
            });
            
            // Limpiar mensajes de error
            const errores = document.querySelectorAll('#modalAgregar .field-error');
            errores.forEach(error => error.textContent = '');
            
            // Resetear toggle de menor de edad
            const esMenor = document.getElementById('es_menor');
            if (esMenor) {
                esMenor.checked = false;
                toggleMenorEdad();
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();
            
            const div = document.createElement('div');
            div.className = `notification ${type}`;
            div.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation' : 'info'}-circle"></i> ${message}`;
            document.body.appendChild(div);
            
            setTimeout(() => div.remove(), 4000);
        }
        
        // Regenerar certificado individual usando el modal
        async function regenerarCertificado(codigo, nombre) {
            // Buscar el estudiante por código de certificado
            const estudiante = estudiantes.find(est => est.certificado_codigo === codigo);
            if (!estudiante) {
                showNotification('No se encontró el estudiante', 'error');
                return;
            }
            
            // Usar el modal de regenerar con solo este estudiante
            abrirModalRegenerar([{
                id: estudiante.id,
                nombre: estudiante.nombre,
                certificado_codigo: codigo
            }]);
        }
        
        // Modal de historial de generaciones
        function abrirModalHistorial() {
            document.getElementById('modalHistorial').classList.add('active');
            cargarHistorial();
        }
        
        async function cargarHistorial() {
            const container = document.getElementById('historialContainer');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Cargando historial...</p></div>';
            
            try {
                const response = await fetch(`api_certificados.php?action=historial&categoria_id=${categoriaId}`);
                const data = await response.json();
                
                if (data.success && data.historial && data.historial.length > 0) {
                    container.innerHTML = `
                        <table class="data-table" style="font-size: 14px;">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Código</th>
                                    <th>Fecha Emisión</th>
                                    <th>Generaciones</th>
                                    <th>Última Generación</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.historial.map(h => `
                                    <tr>
                                        <td>${escapeHtml(h.nombre)}</td>
                                        <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">${h.codigo}</code></td>
                                        <td>${formatearFechaHora(h.fecha_creacion)}</td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-info">${h.total_generaciones || 1}</span>
                                        </td>
                                        <td>${h.ultima_generacion ? formatearFechaHora(h.ultima_generacion) : '—'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #7f8c8d;"><i class="fas fa-history fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i><p>No hay certificados generados aún</p></div>';
                }
            } catch (error) {
                container.innerHTML = `<div style="text-align: center; padding: 40px; color: #e74c3c;"><i class="fas fa-exclamation-circle fa-2x"></i><p>Error al cargar historial: ${error.message}</p></div>`;
            }
        }
    </script>
</body>
</html>
