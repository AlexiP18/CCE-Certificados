<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['id'] ?? $_GET['grupo'] ?? 0;

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

// Obtener categorías del grupo
$stmt = $pdo->prepare("SELECT id, nombre, icono, color FROM categorias WHERE grupo_id = ? AND activo = 1 ORDER BY nombre");
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - <?= htmlspecialchars($grupo['nombre']) ?> - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            padding-top: 80px;
        }

        /* Header estándar */
        .top-nav {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .nav-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            gap: 8px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            color: #5a6c7d;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: #f0f2f5;
            color: #667eea;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .nav-link i {
            font-size: 16px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #e5e7eb;
        }

        .logout-link {
            padding: 8px 12px !important;
            color: #dc2626 !important;
        }

        .logout-link:hover {
            background: #fee2e2 !important;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb span {
            color: #95a5a6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>cc, <?= htmlspecialchars($grupo['color']) ?>);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 15px <?= htmlspecialchars($grupo['color']) ?>40;
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>cc, <?= htmlspecialchars($grupo['color']) ?>);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px <?= htmlspecialchars($grupo['color']) ?>40;
        }

        .btn-secondary {
            background: white;
            color: #2c3e50;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            border-color: <?= htmlspecialchars($grupo['color']) ?>;
            color: <?= htmlspecialchars($grupo['color']) ?>;
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46, 204, 113, 0.4);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 3px;
        }

        .stat-info p {
            color: #95a5a6;
            font-size: 0.85rem;
        }

        /* Filtros */
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: <?= htmlspecialchars($grupo['color']) ?>;
            box-shadow: 0 0 0 3px <?= htmlspecialchars($grupo['color']) ?>20;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }

        .search-input-wrapper input {
            padding-left: 45px;
        }

        /* Tabla */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header .count-badge {
            background: <?= htmlspecialchars($grupo['color']) ?>20;
            color: <?= htmlspecialchars($grupo['color']) ?>;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 14px;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .student-info strong {
            display: block;
            margin-bottom: 2px;
        }

        .student-info small {
            color: #95a5a6;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-categoria {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-activo {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactivo {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-menor {
            background: #fff3cd;
            color: #856404;
            font-size: 11px;
            padding: 3px 8px;
        }

        /* Representante info */
        .representante-info {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 8px;
            font-size: 12px;
        }

        .representante-info .rep-label {
            font-weight: 600;
            color: #f57c00;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }

        .representante-info .rep-data {
            color: #5d4037;
        }

        .representante-info .rep-data span {
            display: block;
            margin-bottom: 2px;
        }

        .representante-info .rep-data span i {
            width: 16px;
            margin-right: 5px;
            color: #f57c00;
        }

        .actions-cell {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit:hover {
            background: #f57c00;
            color: white;
        }

        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }

        .btn-delete:hover {
            background: #c62828;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            padding: 20px;
            border-top: 1px solid #f1f3f4;
        }

        .pagination button {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .pagination button:hover:not(:disabled) {
            border-color: <?= htmlspecialchars($grupo['color']) ?>;
            color: <?= htmlspecialchars($grupo['color']) ?>;
        }

        .pagination button.active {
            background: <?= htmlspecialchars($grupo['color']) ?>;
            border-color: <?= htmlspecialchars($grupo['color']) ?>;
            color: white;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Checkbox de selección */
        .select-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: <?= htmlspecialchars($grupo['color']) ?>;
            margin: 0 auto;
            display: block;
        }

        table th:first-child,
        table td:first-child {
            text-align: center;
            padding: 8px 5px;
        }

        /* Barra de acciones masivas */
        .bulk-actions-bar {
            display: none;
            background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>, <?= htmlspecialchars($grupo['color']) ?>dd);
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideDown 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .bulk-actions-bar.visible {
            display: flex !important;
        }

        /* Fila seleccionada */
        tr.row-selected {
            background: <?= htmlspecialchars($grupo['color']) ?>15 !important;
        }

        tr.row-selected td {
            border-color: <?= htmlspecialchars($grupo['color']) ?>30;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bulk-actions-bar .selected-count {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bulk-actions-bar .selected-count i {
            font-size: 18px;
        }

        .bulk-actions-bar .actions-group {
            display: flex !important;
            gap: 10px;
            margin-left: auto;
            visibility: visible !important;
        }

        .bulk-actions-bar .btn-bulk {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .bulk-actions-bar .btn-bulk-regenerate {
            background: white !important;
            color: <?= htmlspecialchars($grupo['color']) ?>;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .bulk-actions-bar .btn-bulk-regenerate:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .bulk-actions-bar .btn-bulk-cancel {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .bulk-actions-bar .btn-bulk-cancel:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Loading */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay i {
            font-size: 2rem;
            color: <?= htmlspecialchars($grupo['color']) ?>;
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.2rem;
            color: #2c3e50;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        .modal-body {
            padding: 25px;
        }

        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            width: 140px;
            font-weight: 600;
            color: #7f8c8d;
            font-size: 13px;
        }

        .detail-value {
            flex: 1;
            color: #2c3e50;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .page-header {
                flex-direction: column;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Notificaciones */
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
        }

        .notification.success { background: #27ae60; }
        .notification.error { background: #e74c3c; }
        .notification.info { background: #3498db; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Header Estándar -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <?php if (puede('estudiantes', 'ver')): ?>
            <li><a href="estudiantes.php" class="nav-link active"><i class="fas fa-users"></i> Estudiantes</a></li>
            <?php endif; ?>
            <?php if (puede('plantillas', 'ver') || esAdmin()): ?>
            <li><a href="admin_fuentes.php" class="nav-link"><i class="fas fa-font"></i> Fuentes</a></li>
            <?php endif; ?>
            <li><a href="verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <?php if (puede('usuarios', 'ver')): ?>
            <li><a href="usuarios.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <?php endif; ?>
            <li class="nav-user">
                <a href="mi_perfil.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
            <span>/</span>
            <a href="grupo_detalle.php?id=<?= $grupo_id ?>"><?= htmlspecialchars($grupo['icono'] . ' ' . $grupo['nombre']) ?></a>
            <span>/</span>
            <span>Estudiantes</span>
        </div>

        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="icon"><?= htmlspecialchars($grupo['icono']) ?></div>
                <div>
                    <h1>Estudiantes del Grupo</h1>
                    <p><?= htmlspecialchars($grupo['nombre']) ?> - <?= htmlspecialchars($grupo['descripcion'] ?: 'Gestión de estudiantes inscritos') ?></p>
                </div>
            </div>
            <div class="header-actions">
                <button onclick="exportarEstudiantes()" class="btn btn-secondary">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <a href="grupo_detalle.php?id=<?= $grupo_id ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>cc, <?= htmlspecialchars($grupo['color']) ?>);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3 id="totalEstudiantes"><?= $totalEstudiantes ?></h3>
                    <p>Estudiantes Totales</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?= count($categorias) ?></h3>
                    <p>Categorías</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-info">
                    <h3><?= count($periodos) ?></h3>
                    <p>Períodos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-child"></i>
                </div>
                <div class="stat-info">
                    <h3 id="totalMenores">0</h3>
                    <p>Menores de Edad</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-search"></i> Buscar</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar por nombre, cédula, representante..." oninput="filtrarEstudiantes()">
                    </div>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-folder"></i> Categoría</label>
                    <select id="filterCategoria" onchange="filtrarEstudiantes()">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['icono'] . ' ' . $cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Período</label>
                    <select id="filterPeriodo" onchange="filtrarEstudiantes()">
                        <option value="">Todos los períodos</option>
                        <?php foreach ($periodos as $per): ?>
                        <option value="<?= $per['id'] ?>"><?= htmlspecialchars($per['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-toggle-on"></i> Estado</label>
                    <select id="filterEstado" onchange="filtrarEstudiantes()">
                        <option value="">Todos</option>
                        <option value="activo">Activos</option>
                        <option value="inactivo">Inactivos</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-container" style="position: relative;">
            <!-- Barra de acciones masivas -->
            <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none; background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>, <?= htmlspecialchars($grupo['color']) ?>dd); padding: 15px 25px; border-radius: 12px; margin-bottom: 20px; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); flex-wrap: wrap;">
                <span style="color: white; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i>
                    <span id="selectedCountText">0 seleccionados</span>
                </span>
                <div id="bulkButtonsContainer" style="display: flex !important; gap: 10px; margin-left: auto;">
                    <button type="button" id="btnRegenerarCerts" onclick="regenerarCertificadosSeleccionados()" style="padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; display: inline-flex !important; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; background: white; color: <?= htmlspecialchars($grupo['color']) ?>; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <i class="fas fa-sync-alt"></i> Regenerar Certificados
                    </button>
                    <button type="button" id="btnCancelarSeleccion" onclick="deseleccionarTodos()" style="padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; display: inline-flex !important; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            <div class="table-header">
                <h3>
                    <i class="fas fa-list"></i> Listado de Estudiantes
                    <span class="count-badge" id="countBadge">0 estudiantes</span>
                </h3>
            </div>
            
            <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 3%">
                            <input type="checkbox" class="select-checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" title="Seleccionar todos">
                        </th>
                        <th style="width: 4%">#</th>
                        <th style="width: 24%">Estudiante</th>
                        <th style="width: 12%">Cédula</th>
                        <th style="width: 17%">Categoría</th>
                        <th style="width: 14%">Período</th>
                        <th style="width: 10%">Celular</th>
                        <th style="width: 8%">Estado</th>
                        <th style="width: 8%">Acciones</th>
                    </tr>
                </thead>
                <tbody id="estudiantesBody">
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Cargando estudiantes...</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- Modal Detalle Estudiante -->
    <div id="detalleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Detalle del Estudiante</h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body" id="detalleContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let estudiantesData = [];
        let estudiantesFiltrados = [];
        let estudiantesSeleccionados = new Set();
        let paginaActual = 1;
        const porPagina = 20;
        const grupoId = <?= $grupo_id ?>;
        const grupoColor = '<?= htmlspecialchars($grupo['color']) ?>';

        // Cargar estudiantes al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarEstudiantes();
        });

        async function cargarEstudiantes() {
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            try {
                const response = await fetch(`api_estudiantes.php?action=listar_grupo&grupo_id=${grupoId}`);
                const data = await response.json();
                
                if (data.success) {
                    estudiantesData = data.estudiantes || [];
                    estudiantesFiltrados = [...estudiantesData];
                    
                    // Actualizar stats
                    document.getElementById('totalEstudiantes').textContent = estudiantesData.length;
                    const menores = estudiantesData.filter(e => e.es_menor == 1).length;
                    document.getElementById('totalMenores').textContent = menores;
                    
                    renderTabla();
                } else {
                    mostrarError('Error al cargar estudiantes');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión');
            } finally {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        }

        function filtrarEstudiantes() {
            const busqueda = document.getElementById('searchInput').value.toLowerCase().trim();
            const categoriaId = document.getElementById('filterCategoria').value;
            const periodoId = document.getElementById('filterPeriodo').value;
            const estado = document.getElementById('filterEstado').value;

            estudiantesFiltrados = estudiantesData.filter(est => {
                let cumple = true;

                // Búsqueda (incluye representante)
                if (busqueda) {
                    cumple = cumple && (
                        (est.nombre && est.nombre.toLowerCase().includes(busqueda)) ||
                        (est.cedula && est.cedula.includes(busqueda)) ||
                        (est.categoria_nombre && est.categoria_nombre.toLowerCase().includes(busqueda)) ||
                        (est.representante_nombre && est.representante_nombre.toLowerCase().includes(busqueda)) ||
                        (est.representante_cedula && est.representante_cedula.includes(busqueda))
                    );
                }

                // Categoría
                if (categoriaId) {
                    cumple = cumple && (est.categoria_id == categoriaId);
                }

                // Período
                if (periodoId) {
                    cumple = cumple && (est.periodo_id == periodoId);
                }

                // Estado
                if (estado) {
                    cumple = cumple && (est.estado === estado);
                }

                return cumple;
            });

            paginaActual = 1;
            renderTabla();
        }

        function renderTabla() {
            const tbody = document.getElementById('estudiantesBody');
            const inicio = (paginaActual - 1) * porPagina;
            const fin = inicio + porPagina;
            const paginados = estudiantesFiltrados.slice(inicio, fin);

            document.getElementById('countBadge').textContent = `${estudiantesFiltrados.length} estudiantes`;

            if (paginados.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No se encontraron estudiantes</h3>
                            <p>Intenta con otros filtros de búsqueda</p>
                        </td>
                    </tr>
                `;
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            tbody.innerHTML = paginados.map((est, idx) => {
                const iniciales = est.nombre ? est.nombre.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : '?';
                const colorCat = est.categoria_color || '#95a5a6';
                const esMenor = est.es_menor == 1;
                const isSelected = estudiantesSeleccionados.has(est.id);
                
                // Info del representante si es menor
                let representanteHtml = '';
                if (esMenor && est.representante_nombre) {
                    representanteHtml = `
                        <div class="representante-info">
                            <div class="rep-label"><i class="fas fa-user-tie"></i> Representante Legal</div>
                            <div class="rep-data">
                                <span><strong>${escapeHtml(est.representante_nombre)}</strong></span>
                                ${est.representante_cedula ? `<span><i class="fas fa-id-card"></i> ${est.representante_cedula}</span>` : ''}
                                ${est.representante_celular ? `<span><i class="fas fa-phone"></i> +593${est.representante_celular}</span>` : ''}
                            </div>
                        </div>
                    `;
                }
                
                return `
                    <tr class="${isSelected ? 'row-selected' : ''}">
                        <td>
                            <input type="checkbox" class="select-checkbox" data-id="${est.id}" 
                                   onchange="toggleSelectStudent(${est.id})" ${isSelected ? 'checked' : ''}>
                        </td>
                        <td style="text-align: center; color: #95a5a6;">${inicio + idx + 1}</td>
                        <td>
                            <div class="student-name">
                                <div class="student-avatar" style="background: linear-gradient(135deg, ${colorCat}cc, ${colorCat});">${iniciales}</div>
                                <div class="student-info">
                                    <strong>${escapeHtml(est.nombre)}</strong>
                                    ${esMenor ? '<span class="badge badge-menor"><i class="fas fa-child"></i> Menor de edad</span>' : ''}
                                </div>
                            </div>
                            ${representanteHtml}
                        </td>
                        <td><code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">${est.cedula || 'N/A'}</code></td>
                        <td>
                            <span class="badge badge-categoria" style="background: ${colorCat}20; color: ${colorCat};">
                                ${est.categoria_icono || '📁'} ${escapeHtml(est.categoria_nombre || 'Sin categoría')}
                            </span>
                        </td>
                        <td style="font-size: 13px; color: #6c757d;">${escapeHtml(est.periodo_nombre || 'N/A')}</td>
                        <td>${est.celular ? '+593' + est.celular : (esMenor && est.representante_celular ? '<small style="color:#f39c12">Rep: +593' + est.representante_celular + '</small>' : '-')}</td>
                        <td>
                            <span class="badge ${est.estado === 'activo' ? 'badge-activo' : 'badge-inactivo'}">
                                ${est.estado === 'activo' ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-icon btn-view" onclick="verDetalle(${est.id})" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            renderPaginacion();
        }

        function renderPaginacion() {
            const totalPaginas = Math.ceil(estudiantesFiltrados.length / porPagina);
            const pagination = document.getElementById('pagination');

            if (totalPaginas <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = `
                <button onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;

            for (let i = 1; i <= totalPaginas; i++) {
                if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
                    html += `<button onclick="cambiarPagina(${i})" class="${i === paginaActual ? 'active' : ''}">${i}</button>`;
                } else if (i === paginaActual - 3 || i === paginaActual + 3) {
                    html += `<span style="padding: 0 10px;">...</span>`;
                }
            }

            html += `
                <button onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;

            pagination.innerHTML = html;
        }

        function cambiarPagina(pagina) {
            const totalPaginas = Math.ceil(estudiantesFiltrados.length / porPagina);
            if (pagina < 1 || pagina > totalPaginas) return;
            paginaActual = pagina;
            renderTabla();
            window.scrollTo({ top: 300, behavior: 'smooth' });
        }

        async function verDetalle(estudianteId) {
            const estudiante = estudiantesData.find(e => e.id == estudianteId);
            if (!estudiante) return;

            const esMenor = estudiante.es_menor == 1;
            
            // Sección de representante legal si es menor
            let representanteSection = '';
            if (esMenor) {
                representanteSection = `
                    <div class="detail-section" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #fff3cd;">
                        <div style="font-size: 14px; font-weight: 600; color: #f57c00; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user-tie"></i> Representante Legal
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre</span>
                            <span class="detail-value">${escapeHtml(estudiante.representante_nombre) || 'No registrado'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Cédula</span>
                            <span class="detail-value">${estudiante.representante_cedula || 'No registrada'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Celular</span>
                            <span class="detail-value">${estudiante.representante_celular ? '+593 ' + estudiante.representante_celular : 'No registrado'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">${estudiante.representante_email || 'No registrado'}</span>
                        </div>
                    </div>
                `;
            }

            const content = document.getElementById('detalleContent');
            content.innerHTML = `
                <div class="detail-row">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value"><strong>${escapeHtml(estudiante.nombre)}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cédula</span>
                    <span class="detail-value">${estudiante.cedula || 'No registrada'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Celular</span>
                    <span class="detail-value">${estudiante.celular ? '+593 ' + estudiante.celular : 'No registrado'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">${estudiante.email || 'No registrado'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha Nacimiento</span>
                    <span class="detail-value">${estudiante.fecha_nacimiento ? new Date(estudiante.fecha_nacimiento).toLocaleDateString('es-EC') : 'No registrada'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Es Menor</span>
                    <span class="detail-value">${esMenor ? '<span class="badge badge-menor"><i class="fas fa-child"></i> Sí, menor de edad</span>' : 'No'}</span>
                </div>
                ${representanteSection}
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div style="font-size: 14px; font-weight: 600; color: #667eea; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-folder"></i> Inscripción
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Categoría</span>
                        <span class="detail-value">
                            <span class="badge badge-categoria" style="background: ${estudiante.categoria_color || '#95a5a6'}20; color: ${estudiante.categoria_color || '#95a5a6'};">
                                ${estudiante.categoria_icono || '📁'} ${escapeHtml(estudiante.categoria_nombre || 'Sin categoría')}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Período</span>
                        <span class="detail-value">${escapeHtml(estudiante.periodo_nombre || 'N/A')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">
                            <span class="badge ${estudiante.estado === 'activo' ? 'badge-activo' : 'badge-inactivo'}">
                                ${estudiante.estado === 'activo' ? 'Activo' : 'Inactivo'}
                            </span>
                        </span>
                    </div>
                </div>
            `;

            document.getElementById('detalleModal').classList.add('active');
        }

        // ========== FUNCIONES DE SELECCIÓN MÚLTIPLE ==========
        
        function toggleSelectAll(checkbox) {
            const isChecked = checkbox.checked;
            
            if (isChecked) {
                // Seleccionar todos los estudiantes filtrados
                estudiantesFiltrados.forEach(est => {
                    estudiantesSeleccionados.add(est.id);
                });
            } else {
                // Deseleccionar todos
                estudiantesSeleccionados.clear();
            }
            
            renderTabla();
            updateBulkActionsBar();
        }
        
        function toggleSelectStudent(id) {
            if (estudiantesSeleccionados.has(id)) {
                estudiantesSeleccionados.delete(id);
            } else {
                estudiantesSeleccionados.add(id);
            }
            
            // Actualizar checkbox de seleccionar todos
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                const todosSeleccionados = estudiantesFiltrados.every(est => estudiantesSeleccionados.has(est.id));
                const algunosSeleccionados = estudiantesFiltrados.some(est => estudiantesSeleccionados.has(est.id));
                selectAllCheckbox.checked = todosSeleccionados;
                selectAllCheckbox.indeterminate = algunosSeleccionados && !todosSeleccionados;
            }
            
            // Actualizar visual de la fila
            const checkbox = document.querySelector(`.select-checkbox[data-id="${id}"]`);
            if (checkbox) {
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.toggle('row-selected', estudiantesSeleccionados.has(id));
                }
            }
            
            updateBulkActionsBar();
        }
        
        function updateBulkActionsBar() {
            const bar = document.getElementById('bulkActionsBar');
            const countText = document.getElementById('selectedCountText');
            const count = estudiantesSeleccionados.size;
            
            console.log('updateBulkActionsBar llamada, seleccionados:', count);
            
            if (count > 0) {
                bar.style.display = 'flex';
                countText.textContent = `${count} seleccionado${count > 1 ? 's' : ''}`;
                console.log('Barra mostrada');
            } else {
                bar.style.display = 'none';
                console.log('Barra oculta');
            }
        }
        
        function deseleccionarTodos() {
            estudiantesSeleccionados.clear();
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            renderTabla();
            updateBulkActionsBar();
        }
        
        async function regenerarCertificadosSeleccionados() {
            const count = estudiantesSeleccionados.size;
            
            if (count === 0) {
                mostrarNotificacion('No hay estudiantes seleccionados', 'error');
                return;
            }
            
            const confirmar = confirm(`¿Desea regenerar los certificados de ${count} estudiante${count > 1 ? 's' : ''} seleccionado${count > 1 ? 's' : ''}?\n\nEsto sobrescribirá los certificados existentes.`);
            
            if (!confirmar) return;
            
            const estudianteIds = Array.from(estudiantesSeleccionados);
            const btnRegenerar = event.target.closest('button') || document.querySelector('#bulkActionsBar button');
            const originalText = btnRegenerar ? btnRegenerar.innerHTML : '';
            
            // Estado de carga
            if (btnRegenerar) {
                btnRegenerar.disabled = true;
                btnRegenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regenerando...';
            }
            
            let exitosos = 0;
            let errores = 0;
            
            try {
                for (const estudianteId of estudianteIds) {
                    try {
                        const response = await fetch('api_generar_certificados.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'regenerar_estudiante',
                                grupo_id: grupoId,
                                estudiante_id: estudianteId,
                                forzar_regenerar: true
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            exitosos++;
                        } else {
                            errores++;
                            console.error(`Error regenerando certificado para estudiante ${estudianteId}:`, result.error);
                        }
                    } catch (err) {
                        errores++;
                        console.error(`Error en petición para estudiante ${estudianteId}:`, err);
                    }
                }
                
                // Mostrar resultado
                if (errores === 0) {
                    mostrarNotificacion(`✅ ${exitosos} certificado${exitosos > 1 ? 's' : ''} regenerado${exitosos > 1 ? 's' : ''} exitosamente`, 'success');
                } else if (exitosos > 0) {
                    mostrarNotificacion(`⚠️ ${exitosos} regenerado${exitosos > 1 ? 's' : ''}, ${errores} error${errores > 1 ? 'es' : ''}`, 'warning');
                } else {
                    mostrarNotificacion(`❌ Error al regenerar certificados`, 'error');
                }
                
                // Limpiar selección
                deseleccionarTodos();
                
            } catch (error) {
                console.error('Error general:', error);
                mostrarNotificacion('Error al procesar la regeneración', 'error');
            } finally {
                if (btnRegenerar) {
                    btnRegenerar.disabled = false;
                    btnRegenerar.innerHTML = originalText;
                }
            }
        }

        function cerrarModal() {
            document.getElementById('detalleModal').classList.remove('active');
        }

        function exportarEstudiantes() {
            if (estudiantesFiltrados.length === 0) {
                mostrarNotificacion('No hay estudiantes para exportar', 'error');
                return;
            }

            // Crear CSV con datos del representante
            let csv = 'Nombre,Cédula,Celular,Email,Categoría,Período,Estado,Es Menor,Representante Nombre,Representante Cédula,Representante Celular,Representante Email\n';
            
            estudiantesFiltrados.forEach(est => {
                csv += `"${est.nombre || ''}","${est.cedula || ''}","${est.celular ? '+593' + est.celular : ''}","${est.email || ''}","${est.categoria_nombre || ''}","${est.periodo_nombre || ''}","${est.estado || ''}","${est.es_menor == 1 ? 'Sí' : 'No'}","${est.representante_nombre || ''}","${est.representante_cedula || ''}","${est.representante_celular ? '+593' + est.representante_celular : ''}","${est.representante_email || ''}"\n`;
            });

            // Descargar
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `estudiantes_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $grupo['nombre']) ?>_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();

            mostrarNotificacion(`Exportados ${estudiantesFiltrados.length} estudiantes`, 'success');
        }

        // Utilidades
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function mostrarNotificacion(mensaje, tipo = 'info') {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notif = document.createElement('div');
            notif.className = `notification ${tipo}`;
            notif.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : tipo === 'error' ? 'times' : 'info'}-circle"></i> ${mensaje}`;
            document.body.appendChild(notif);

            setTimeout(() => notif.remove(), 4000);
        }

        function mostrarError(mensaje) {
            document.getElementById('estudiantesBody').innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                        <h3>${mensaje}</h3>
                    </td>
                </tr>
            `;
        }

        // Cerrar modal con clic fuera
        document.getElementById('detalleModal').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModal();
        });
    </script>
</body>
</html>
