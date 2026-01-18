<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

try {
    $pdo = getConnection();
    
    // Obtener grupos para el filtro
    $stmt = $pdo->query("SELECT id, nombre, color FROM grupos ORDER BY nombre");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para el filtro
    $stmt = $pdo->query("SELECT c.id, c.nombre, c.grupo_id, g.nombre as grupo_nombre 
                         FROM categorias c 
                         LEFT JOIN grupos g ON c.grupo_id = g.id 
                         ORDER BY g.nombre, c.nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $grupos = [];
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== MENÚ DE NAVEGACIÓN FIJO ===== */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
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
        
        /* Usuario en el menú */
        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #e5e7eb;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #f0f2f5;
            border-radius: 20px;
            font-size: 13px;
            color: #374151;
            font-weight: 500;
        }
        
        .logout-link {
            padding: 8px 12px !important;
            color: #dc2626 !important;
        }
        
        .logout-link:hover {
            background: #fee2e2 !important;
        }
        
        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-user {
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                padding-top: 10px;
                border-top: 1px solid #e5e7eb;
            }
        }
        
        /* Espacio para el menú fijo */
        body {
            padding-top: 80px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* ===== HEADER ===== */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header h1 i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        /* ===== FILTROS ===== */
        .filters-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .filters-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filters-title i {
            color: #667eea;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: #5a6c7d;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e8ecf0;
            color: #5a6c7d;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
        }
        
        /* ===== RESULTADOS ===== */
        .results-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .results-count {
            font-size: 16px;
            color: #5a6c7d;
        }
        
        .results-count strong {
            color: #2c3e50;
        }
        
        /* ===== TABLA ===== */
        .table-wrapper {
            overflow-x: auto;
        }
        
        .estudiantes-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .estudiantes-table th,
        .estudiantes-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e8ecf0;
        }
        
        .estudiantes-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .estudiantes-table tr:hover {
            background: #f8f9fa;
        }
        
        .estudiantes-table td {
            font-size: 14px;
            color: #5a6c7d;
        }
        
        .nombre-cell {
            font-weight: 600;
            color: #2c3e50 !important;
        }
        
        .cedula-cell {
            font-family: monospace;
            background: #f0f2f5;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-grupo {
            background: #e8ecf0;
            color: #5a6c7d;
        }
        
        .badge-categoria {
            background: #eef2ff;
            color: #667eea;
        }
        
        .badge-destacado {
            background: #fef3c7;
            color: #d97706;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #eef2ff;
            color: #667eea;
        }
        
        .btn-view:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-delete {
            background: #fef2f2;
            color: #e74c3c;
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        .btn-certificate {
            background: #f0fdf4;
            color: #16a34a;
        }
        
        .btn-certificate:hover {
            background: #16a34a;
            color: white;
        }
        
        /* ===== CHECKBOX SELECCIÓN ===== */
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .select-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        /* ===== BARRA DE ACCIONES MASIVAS ===== */
        .bulk-actions-bar {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            animation: slideDown 0.3s ease;
        }
        
        .bulk-actions-bar.active {
            display: flex;
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
        
        .bulk-actions-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }
        
        .bulk-actions-info i {
            font-size: 20px;
        }
        
        .selected-count {
            font-weight: 600;
            font-size: 16px;
        }
        
        .bulk-actions-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-bulk {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-bulk-success {
            background: rgba(255,255,255,0.95);
            color: #16a34a;
        }
        
        .btn-bulk-success:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-bulk-cancel {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-bulk-cancel:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* ===== MODAL CERTIFICADO ===== */
        .modal-certificado {
            max-width: 700px;
        }
        
        .modal-section {
            margin-bottom: 25px;
        }
        
        .modal-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #5a6c7d;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 14px;
            border: 2px solid #e8ecf0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .selected-students-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .selected-student-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .selected-student-item:last-child {
            margin-bottom: 0;
        }
        
        .student-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .student-info {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .btn-remove-student {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .btn-remove-student:hover {
            background: #fef2f2;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf0;
        }
        
        .btn-modal {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-modal-cancel {
            background: #e8ecf0;
            color: #5a6c7d;
        }
        
        .btn-modal-cancel:hover {
            background: #d5dbdb;
        }
        
        .btn-modal-success {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
        }
        
        .btn-modal-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.4);
        }
        
        .btn-modal-success:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Alerta de información */
        .info-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .info-alert i {
            color: #3b82f6;
            font-size: 18px;
            margin-top: 2px;
        }
        
        .info-alert p {
            margin: 0;
            font-size: 13px;
            color: #1e40af;
            line-height: 1.5;
        }
        
        /* ===== PAGINACIÓN ===== */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination button {
            padding: 10px 16px;
            border: 2px solid #e8ecf0;
            background: white;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination button:hover:not(:disabled) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            color: #5a6c7d;
            font-size: 14px;
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
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
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
        
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e8ecf0;
        }
        
        .detail-label {
            flex: 0 0 150px;
            font-weight: 600;
            color: #5a6c7d;
        }
        
        .detail-value {
            color: #2c3e50;
        }
        
        .certificados-list {
            margin-top: 20px;
        }
        
        .certificados-list h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
        }
        
        .certificado-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .certificado-item strong {
            color: #2c3e50;
        }
        
        .certificado-item small {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="estudiantes.php" class="nav-link active"><i class="fas fa-users"></i> Estudiantes</a></li>
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
        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Gestión de Estudiantes</h1>
            <p>Administra y consulta los estudiantes registrados en el sistema</p>
        </div>
        
        <!-- Filtros -->
        <div class="filters-section">
            <h3 class="filters-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="searchInput"><i class="fas fa-search"></i> Buscar por nombre o cédula</label>
                    <input type="text" id="searchInput" placeholder="Escribe para buscar...">
                </div>
                <div class="filter-group">
                    <label for="grupoFilter"><i class="fas fa-layer-group"></i> Grupo</label>
                    <select id="grupoFilter">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($grupos as $grupo): ?>
                        <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="categoriaFilter"><i class="fas fa-tag"></i> Categoría</label>
                    <select id="categoriaFilter">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-grupo="<?= $cat['grupo_id'] ?>">
                            <?= htmlspecialchars($cat['nombre']) ?> 
                            <?php if ($cat['grupo_nombre']): ?>(<?= htmlspecialchars($cat['grupo_nombre']) ?>)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </button>
            </div>
        </div>
        
        <!-- Resultados -->
        <div class="results-section">
            <!-- Barra de acciones masivas -->
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <div class="bulk-actions-info">
                    <i class="fas fa-check-circle"></i>
                    <span class="selected-count"><span id="selectedCount">0</span> estudiantes seleccionados</span>
                </div>
                <div class="bulk-actions-buttons">
                    <button type="button" class="btn-bulk btn-bulk-success" onclick="abrirModalGenerarCertificados()">
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                    <button type="button" class="btn-bulk btn-bulk-cancel" onclick="cancelarSeleccion()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            <div class="results-header">
                <div class="results-count">
                    Mostrando <strong id="showingCount">0</strong> de <strong id="totalCount">0</strong> estudiantes
                </div>
                <div>
                    <button type="button" class="btn btn-primary" id="btnVerCertificados" style="display: none;" onclick="irACertificados()">
                        <i class="fas fa-eye"></i> Ver certificados de la categoría
                    </button>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="estudiantes-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" class="select-checkbox" id="selectAll" title="Seleccionar todos" onchange="toggleSelectAll(this)">
                            </th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Celular</th>
                            <th>Grupo</th>
                            <th>Categoría</th>
                            <th>Certificados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="estudiantesTableBody">
                        <tr>
                            <td colspan="7">
                                <div class="loading">
                                    <i class="fas fa-spinner"></i>
                                    <p>Cargando estudiantes...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="pagination">
                <button id="btnPrev" onclick="prevPage()" disabled>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-info">Página <span id="currentPage">1</span> de <span id="totalPages">1</span></span>
                <button id="btnNext" onclick="nextPage()" disabled>
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalles -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Detalles del Estudiante</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
    
    <!-- Modal de Generación de Certificados -->
    <div class="modal-overlay" id="modalCertificadoOverlay" onclick="cerrarModalCertificado(event)">
        <div class="modal modal-certificado" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3><i class="fas fa-certificate" style="color: #16a34a;"></i> Generar Certificados</h3>
                <button class="modal-close" onclick="cerrarModalCertificado()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-users"></i> Estudiantes Seleccionados</div>
                <div class="selected-students-list" id="selectedStudentsList">
                    <!-- Lista de estudiantes seleccionados -->
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-cog"></i> Configuración del Certificado</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="certGrupo"><i class="fas fa-layer-group"></i> Grupo *</label>
                        <select id="certGrupo" onchange="cargarCategoriasGrupo(this.value)" required>
                            <option value="">Seleccione un grupo</option>
                            <?php foreach ($grupos as $grupo): ?>
                            <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="certCategoria"><i class="fas fa-tag"></i> Categoría *</label>
                        <select id="certCategoria" required disabled>
                            <option value="">Primero seleccione un grupo</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="certFecha"><i class="fas fa-calendar"></i> Fecha del Certificado *</label>
                        <input type="date" id="certFecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="certRazon"><i class="fas fa-comment"></i> Razón / Motivo (opcional)</label>
                    <textarea id="certRazon" rows="2" placeholder="Ej: Por haber completado satisfactoriamente el curso..."></textarea>
                </div>
                
                <div class="info-alert">
                    <i class="fas fa-info-circle"></i>
                    <p>
                        <strong>Nota:</strong> Los certificados se generarán usando la plantilla configurada para el grupo/categoría seleccionado. 
                        Si un estudiante ya tiene certificado en esta categoría, se actualizará la fecha de generación.
                    </p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalCertificado()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-modal btn-modal-success" id="btnGenerarCertificados" onclick="generarCertificados()" disabled>
                    <i class="fas fa-certificate"></i> Generar <span id="cantidadCertificados">0</span> Certificado(s)
                </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '/cce-certificados/';
        
        // Estado de la aplicación
        let currentPage = 1;
        const perPage = 20;
        let totalItems = 0;
        let currentFilters = {
            search: '',
            grupo_id: '',
            categoria_id: ''
        };

        const urlParams = new URLSearchParams(window.location.search);
        const initialGrupoId = urlParams.get('grupo_id') || urlParams.get('grupo');
        const initialCategoriaId = urlParams.get('categoria_id') || urlParams.get('categoria');
        
        // Cargar datos de categorías para filtrado dinámico
        const categoriasData = <?= json_encode($categorias) ?>;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar filtros iniciales si vienen por URL
            if (initialGrupoId) {
                document.getElementById('grupoFilter').value = initialGrupoId;
                currentFilters.grupo_id = initialGrupoId;
                filterCategoriasByGrupo(initialGrupoId);
            }
            if (initialCategoriaId) {
                document.getElementById('categoriaFilter').value = initialCategoriaId;
                currentFilters.categoria_id = initialCategoriaId;
            }

            actualizarBotonCertificados();
            loadEstudiantes();
            
            // Búsqueda en tiempo real
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value;
                    currentPage = 1;
                    loadEstudiantes();
                }, 300);
            });
            
            // Filtrar categorías por grupo
            document.getElementById('grupoFilter').addEventListener('change', function() {
                filterCategoriasByGrupo(this.value);
                currentFilters.grupo_id = this.value;
                currentPage = 1;
                loadEstudiantes();
                actualizarBotonCertificados();
            });
            
            document.getElementById('categoriaFilter').addEventListener('change', function() {
                currentFilters.categoria_id = this.value;
                currentPage = 1;
                loadEstudiantes();
                actualizarBotonCertificados();
            });
        });
        
        // Filtrar opciones de categoría según el grupo seleccionado
        function filterCategoriasByGrupo(grupoId) {
            const categoriaSelect = document.getElementById('categoriaFilter');
            const options = categoriaSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else if (!grupoId || option.dataset.grupo === grupoId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Resetear selección si la categoría actual no corresponde al grupo
            if (grupoId && categoriaSelect.value) {
                const selectedOption = categoriaSelect.querySelector(`option[value="${categoriaSelect.value}"]`);
                if (selectedOption && selectedOption.dataset.grupo !== grupoId) {
                    categoriaSelect.value = '';
                    currentFilters.categoria_id = '';
                }
            }
        }
        
        async function loadEstudiantes() {
            const tableBody = document.getElementById('estudiantesTableBody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p>Cargando estudiantes...</p>
                        </div>
                    </td>
                </tr>
            `;
            
            try {
                const offset = (currentPage - 1) * perPage;
                const params = new URLSearchParams({
                    action: 'list_with_details',
                    limit: perPage,
                    offset: offset,
                    search: currentFilters.search,
                    grupo_id: currentFilters.grupo_id,
                    categoria_id: currentFilters.categoria_id
                });
                
                const response = await fetch(`api_estudiantes.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                totalItems = data.total;
                updatePagination();
                renderEstudiantes(data.estudiantes);
                
            } catch (error) {
                console.error('Error:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error al cargar los datos</h3>
                                <p>${error.message}</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
        
        function renderEstudiantes(estudiantes) {
            const tableBody = document.getElementById('estudiantesTableBody');
            
            if (!estudiantes || estudiantes.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h3>No se encontraron estudiantes</h3>
                                <p>Intenta con otros filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tableBody.innerHTML = estudiantes.map(est => `
                <tr data-estudiante-id="${est.id}">
                    <td class="checkbox-cell">
                        <input type="checkbox" class="select-checkbox estudiante-checkbox" 
                               value="${est.id}" 
                               data-nombre="${escapeHtml(est.nombre)}"
                               data-cedula="${escapeHtml(est.cedula || '')}"
                               data-grupos="${escapeHtml(est.grupos || '')}"
                               data-categorias="${escapeHtml(est.categorias || '')}"
                               onchange="actualizarSeleccion()">
                    </td>
                    <td class="nombre-cell">
                        ${est.destacado == 1 ? '<i class="fas fa-star" style="color: #f39c12; margin-right: 5px;"></i>' : ''}
                        ${escapeHtml(est.nombre)}
                    </td>
                    <td>
                        ${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color: #aaa;">—</span>'}
                    </td>
                    <td>${est.celular || '<span style="color: #aaa;">—</span>'}</td>
                    <td>
                        ${est.grupos ? est.grupos.split(',').map(g => `<span class="badge badge-grupo">${escapeHtml(g.trim())}</span>`).join(' ') : '<span style="color: #aaa;">—</span>'}
                    </td>
                    <td>
                        ${est.categorias ? est.categorias.split(',').map(c => `<span class="badge badge-categoria">${escapeHtml(c.trim())}</span>`).join(' ') : '<span style="color: #aaa;">—</span>'}
                    </td>
                    <td>
                        <span class="badge ${est.total_certificados > 0 ? 'badge-categoria' : ''}" style="${est.total_certificados == 0 ? 'background: #f0f0f0; color: #999;' : ''}">
                            ${est.total_certificados || 0} certificado(s)
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-certificate" onclick="abrirModalCertificadoIndividual(${est.id}, '${escapeHtml(est.nombre)}')" title="Generar certificado">
                                <i class="fas fa-certificate"></i>
                            </button>
                            <button class="btn-icon btn-view" onclick="viewEstudiante(${est.id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-delete" onclick="deleteEstudiante(${est.id}, '${escapeHtml(est.nombre)}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            // Resetear el checkbox "seleccionar todos"
            document.getElementById('selectAll').checked = false;
        }
        
        function updatePagination() {
            const totalPages = Math.ceil(totalItems / perPage);
            
            document.getElementById('showingCount').textContent = Math.min(perPage, totalItems - (currentPage - 1) * perPage);
            document.getElementById('totalCount').textContent = totalItems;
            document.getElementById('currentPage').textContent = currentPage;
            document.getElementById('totalPages').textContent = totalPages || 1;
            
            document.getElementById('btnPrev').disabled = currentPage <= 1;
            document.getElementById('btnNext').disabled = currentPage >= totalPages;
        }
        
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadEstudiantes();
            }
        }
        
        function nextPage() {
            const totalPages = Math.ceil(totalItems / perPage);
            if (currentPage < totalPages) {
                currentPage++;
                loadEstudiantes();
            }
        }
        
        function applyFilters() {
            currentFilters.search = document.getElementById('searchInput').value;
            currentFilters.grupo_id = document.getElementById('grupoFilter').value;
            currentFilters.categoria_id = document.getElementById('categoriaFilter').value;
            currentPage = 1;
            actualizarBotonCertificados();
            loadEstudiantes();
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('grupoFilter').value = '';
            document.getElementById('categoriaFilter').value = '';
            currentFilters = { search: '', grupo_id: '', categoria_id: '' };
            
            // Mostrar todas las categorías
            filterCategoriasByGrupo('');
            
            currentPage = 1;
            actualizarBotonCertificados();
            loadEstudiantes();
        }
        
        async function viewEstudiante(id) {
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Cargando...</p></div>';
            document.getElementById('modalOverlay').classList.add('active');
            
            try {
                const response = await fetch(`api_estudiantes.php?action=get_details&id=${id}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                const est = data.estudiante;
                const certificados = data.certificados || [];
                
                modalContent.innerHTML = `
                    <div class="detail-row">
                        <div class="detail-label">Nombre</div>
                        <div class="detail-value">${escapeHtml(est.nombre)} ${est.destacado == 1 ? '<i class="fas fa-star" style="color: #f39c12;"></i>' : ''}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Cédula</div>
                        <div class="detail-value">${est.cedula || '—'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Celular</div>
                        <div class="detail-value">${est.celular || '—'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${est.email || '—'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Fecha de Nacimiento</div>
                        <div class="detail-value">${est.fecha_nacimiento || '—'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Registrado</div>
                        <div class="detail-value">${formatDate(est.fecha_creacion)}</div>
                    </div>
                    
                    <div class="certificados-list">
                        <h4><i class="fas fa-certificate"></i> Certificados (${certificados.length})</h4>
                        ${certificados.length > 0 ? certificados.map(cert => `
                            <div class="certificado-item">
                                <strong>${escapeHtml(cert.razon || 'Sin razón')}</strong><br>
                                <small>
                                    <i class="fas fa-calendar"></i> ${cert.fecha || '—'} &nbsp;|&nbsp;
                                    <i class="fas fa-layer-group"></i> ${cert.grupo_nombre || '—'} &nbsp;|&nbsp;
                                    <i class="fas fa-tag"></i> ${cert.categoria_nombre || '—'}
                                </small>
                            </div>
                        `).join('') : '<p style="color: #999;">Este estudiante aún no tiene certificados.</p>'}
                    </div>
                `;
                
            } catch (error) {
                modalContent.innerHTML = `<p style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> ${error.message}</p>`;
            }
        }
        
        function closeModal(event) {
            if (!event || event.target.id === 'modalOverlay') {
                document.getElementById('modalOverlay').classList.remove('active');
            }
        }
        
        async function deleteEstudiante(id, nombre) {
            if (!confirm(`¿Estás seguro de eliminar al estudiante "${nombre}"?\n\nEsta acción eliminará también sus certificados asociados.`)) {
                return;
            }
            
            try {
                const response = await fetch('api_estudiantes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                alert('Estudiante eliminado correctamente');
                loadEstudiantes();
                
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        // Mostrar/ocultar botón de certificados según categoría seleccionada
        function actualizarBotonCertificados() {
            const btn = document.getElementById('btnVerCertificados');
            if (!btn) return;
            if (currentFilters.categoria_id) {
                btn.style.display = 'inline-flex';
                btn.dataset.categoriaId = currentFilters.categoria_id;
                btn.dataset.grupoId = currentFilters.grupo_id || '';
            } else {
                btn.style.display = 'none';
            }
        }

        function irACertificados() {
            const btn = document.getElementById('btnVerCertificados');
            if (!btn || !btn.dataset.categoriaId) return;
            const params = new URLSearchParams();
            params.set('categoria', btn.dataset.categoriaId);
            if (btn.dataset.grupoId) params.set('grupo', btn.dataset.grupoId);
            window.location.href = `visualizar_certificados.php?${params.toString()}`;
        }
        
        // ====== GESTIÓN DE CERTIFICADOS ======
        
        // Almacenar estudiantes seleccionados
        let estudiantesSeleccionados = [];
        
        // Categorías cargadas para el modal
        let categoriasPorGrupo = {};
        
        // Toggle seleccionar todos
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.estudiante-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            actualizarSeleccion();
        }
        
        // Actualizar lista de seleccionados
        function actualizarSeleccion() {
            const checkboxes = document.querySelectorAll('.estudiante-checkbox:checked');
            estudiantesSeleccionados = Array.from(checkboxes).map(cb => ({
                id: cb.value,
                nombre: cb.dataset.nombre,
                cedula: cb.dataset.cedula,
                grupos: cb.dataset.grupos,
                categorias: cb.dataset.categorias
            }));
            
            const count = estudiantesSeleccionados.length;
            document.getElementById('selectedCount').textContent = count;
            
            // Mostrar/ocultar barra de acciones
            const bulkBar = document.getElementById('bulkActionsBar');
            if (count > 0) {
                bulkBar.classList.add('active');
            } else {
                bulkBar.classList.remove('active');
            }
            
            // Actualizar estado del checkbox "seleccionar todos"
            const allCheckboxes = document.querySelectorAll('.estudiante-checkbox');
            const selectAll = document.getElementById('selectAll');
            if (allCheckboxes.length > 0) {
                selectAll.checked = checkboxes.length === allCheckboxes.length;
                selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
            }
        }
        
        // Cancelar selección
        function cancelarSeleccion() {
            document.querySelectorAll('.estudiante-checkbox').forEach(cb => {
                cb.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            estudiantesSeleccionados = [];
            document.getElementById('bulkActionsBar').classList.remove('active');
        }
        
        // Abrir modal para certificado individual
        function abrirModalCertificadoIndividual(estudianteId, nombre) {
            // Buscar el checkbox de este estudiante para obtener sus datos
            const checkbox = document.querySelector(`.estudiante-checkbox[value="${estudianteId}"]`);
            if (checkbox) {
                estudiantesSeleccionados = [{
                    id: estudianteId,
                    nombre: nombre,
                    cedula: checkbox.dataset.cedula,
                    grupos: checkbox.dataset.grupos,
                    categorias: checkbox.dataset.categorias
                }];
            } else {
                estudiantesSeleccionados = [{
                    id: estudianteId,
                    nombre: nombre,
                    cedula: '',
                    grupos: '',
                    categorias: ''
                }];
            }
            abrirModalGenerarCertificados();
        }
        
        // Abrir modal de generación de certificados
        function abrirModalGenerarCertificados() {
            if (estudiantesSeleccionados.length === 0) {
                alert('Selecciona al menos un estudiante');
                return;
            }
            
            // Renderizar lista de estudiantes seleccionados
            const listContainer = document.getElementById('selectedStudentsList');
            listContainer.innerHTML = estudiantesSeleccionados.map(est => `
                <div class="selected-student-item" data-id="${est.id}">
                    <div>
                        <span class="student-name">${escapeHtml(est.nombre)}</span>
                        ${est.cedula ? `<span class="student-info"> - ${escapeHtml(est.cedula)}</span>` : ''}
                    </div>
                    <button type="button" class="btn-remove-student" onclick="removerEstudianteDelModal(${est.id})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
            
            // Actualizar contador
            document.getElementById('cantidadCertificados').textContent = estudiantesSeleccionados.length;
            
            // Resetear formulario
            document.getElementById('certGrupo').value = '';
            document.getElementById('certCategoria').value = '';
            document.getElementById('certCategoria').disabled = true;
            document.getElementById('certCategoria').innerHTML = '<option value="">Primero seleccione un grupo</option>';
            document.getElementById('certRazon').value = '';
            document.getElementById('btnGenerarCertificados').disabled = true;
            
            // Mostrar modal
            document.getElementById('modalCertificadoOverlay').classList.add('active');
        }
        
        // Remover estudiante del modal
        function removerEstudianteDelModal(estudianteId) {
            estudiantesSeleccionados = estudiantesSeleccionados.filter(e => e.id != estudianteId);
            
            // También desmarcar el checkbox si está visible
            const checkbox = document.querySelector(`.estudiante-checkbox[value="${estudianteId}"]`);
            if (checkbox) checkbox.checked = false;
            
            if (estudiantesSeleccionados.length === 0) {
                cerrarModalCertificado();
                actualizarSeleccion();
                return;
            }
            
            // Re-renderizar lista
            const listContainer = document.getElementById('selectedStudentsList');
            listContainer.innerHTML = estudiantesSeleccionados.map(est => `
                <div class="selected-student-item" data-id="${est.id}">
                    <div>
                        <span class="student-name">${escapeHtml(est.nombre)}</span>
                        ${est.cedula ? `<span class="student-info"> - ${escapeHtml(est.cedula)}</span>` : ''}
                    </div>
                    <button type="button" class="btn-remove-student" onclick="removerEstudianteDelModal(${est.id})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
            
            document.getElementById('cantidadCertificados').textContent = estudiantesSeleccionados.length;
            actualizarSeleccion();
        }
        
        // Cerrar modal de certificados
        function cerrarModalCertificado(event) {
            if (!event || event.target.id === 'modalCertificadoOverlay') {
                document.getElementById('modalCertificadoOverlay').classList.remove('active');
            }
        }
        
        // Cargar categorías por grupo
        async function cargarCategoriasGrupo(grupoId) {
            const selectCategoria = document.getElementById('certCategoria');
            const btnGenerar = document.getElementById('btnGenerarCertificados');
            
            if (!grupoId) {
                selectCategoria.disabled = true;
                selectCategoria.innerHTML = '<option value="">Primero seleccione un grupo</option>';
                btnGenerar.disabled = true;
                return;
            }
            
            selectCategoria.disabled = true;
            selectCategoria.innerHTML = '<option value="">Cargando categorías...</option>';
            
            try {
                // Cargar categorías del grupo seleccionado
                const response = await fetch(`api_categorias.php?action=listar&grupo_id=${grupoId}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar categorías');
                }
                
                if (data.categorias && data.categorias.length > 0) {
                    selectCategoria.innerHTML = '<option value="">Seleccione una categoría</option>' +
                        data.categorias.map(cat => 
                            `<option value="${cat.id}">${escapeHtml(cat.nombre)}</option>`
                        ).join('');
                    selectCategoria.disabled = false;
                } else {
                    selectCategoria.innerHTML = '<option value="">No hay categorías en este grupo</option>';
                }
                
                btnGenerar.disabled = true;
                
            } catch (error) {
                console.error('Error:', error);
                selectCategoria.innerHTML = '<option value="">Error al cargar</option>';
            }
        }
        
        // Validar formulario y habilitar botón
        document.addEventListener('DOMContentLoaded', function() {
            // Ya existente: loadEstudiantes() se llama
            
            // Agregar listeners para validación del modal
            const certGrupo = document.getElementById('certGrupo');
            const certCategoria = document.getElementById('certCategoria');
            const certFecha = document.getElementById('certFecha');
            
            function validarFormularioCertificado() {
                const btnGenerar = document.getElementById('btnGenerarCertificados');
                const grupoValido = certGrupo && certGrupo.value;
                const categoriaValida = certCategoria && certCategoria.value;
                const fechaValida = certFecha && certFecha.value;
                
                btnGenerar.disabled = !(grupoValido && categoriaValida && fechaValida && estudiantesSeleccionados.length > 0);
            }
            
            if (certCategoria) {
                certCategoria.addEventListener('change', validarFormularioCertificado);
            }
            if (certFecha) {
                certFecha.addEventListener('change', validarFormularioCertificado);
            }
        });
        
        // Generar certificados
        async function generarCertificados() {
            const grupoId = document.getElementById('certGrupo').value;
            const categoriaId = document.getElementById('certCategoria').value;
            const fecha = document.getElementById('certFecha').value;
            const razon = document.getElementById('certRazon').value.trim();
            
            if (!grupoId || !categoriaId || !fecha) {
                alert('Por favor complete todos los campos requeridos');
                return;
            }
            
            if (estudiantesSeleccionados.length === 0) {
                alert('No hay estudiantes seleccionados');
                return;
            }
            
            const btnGenerar = document.getElementById('btnGenerarCertificados');
            const textoOriginal = btnGenerar.innerHTML;
            btnGenerar.disabled = true;
            btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            
            try {
                const response = await fetch('api_generar_certificados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generar_batch',
                        grupo_id: grupoId,
                        categoria_id: categoriaId,
                        estudiantes_ids: estudiantesSeleccionados.map(e => e.id),
                        fecha: fecha,
                        razon: razon
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    if (data.error_type === 'PLANTILLA_NO_CONFIGURADA') {
                        alert(`⚠️ ${data.message}\n\n${data.detalle}`);
                    } else {
                        throw new Error(data.message || 'Error al generar certificados');
                    }
                    return;
                }
                
                // Mostrar resumen
                const exitosos = data.exitosos || 0;
                const errores = data.errores || 0;
                
                let mensaje = `✅ Se generaron ${exitosos} certificado(s) exitosamente.`;
                if (errores > 0) {
                    mensaje += `\n⚠️ ${errores} certificado(s) no pudieron generarse.`;
                }
                
                alert(mensaje);
                
                // Cerrar modal y refrescar
                cerrarModalCertificado();
                cancelarSeleccion();
                loadEstudiantes();
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            } finally {
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = textoOriginal;
            }
        }
    </script>
</body>
</html>
