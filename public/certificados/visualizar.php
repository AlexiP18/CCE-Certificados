<?php
/**
 * Visualización de Certificados por Categoría
 * Muestra todos los estudiantes de todos los períodos con opción de previsualizar y descargar
 */
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$categoria_id = $_GET['categoria'] ?? 0;
$grupo_id = $_GET['grupo'] ?? 0;

$pdo = getConnection();

// Si viene categoria, obtener su grupo
if (!empty($categoria_id)) {
    $stmt = $pdo->prepare("SELECT c.*, g.nombre as grupo_nombre, g.color as grupo_color, g.icono as grupo_icono, g.id as grupo_id 
                           FROM categorias c 
                           JOIN grupos g ON c.grupo_id = g.id 
                           WHERE c.id = ? AND c.activo = 1");
    $stmt->execute([$categoria_id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        header('Location: ../dashboard/index.php');
        exit;
    }
    $grupo_id = $categoria['grupo_id'];
} else if (!empty($grupo_id)) {
    // Si solo viene grupo, mostrar todas las categorías del grupo
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$grupo) {
        header('Location: ../dashboard/index.php');
        exit;
    }
    $categoria = null;
} else {
    header('Location: ../dashboard/index.php');
    exit;
}

// Obtener todos los períodos de la categoría o grupo
if ($categoria) {
    // Períodos relacionados con la categoría a través de categoria_periodos
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.* 
        FROM periodos p
        INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
        WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
        ORDER BY p.fecha_inicio DESC
    ");
    $stmt->execute([$grupo_id]);
} else {
    // Obtener períodos del grupo
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.*
        FROM periodos p
        INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id
        WHERE gp.grupo_id = ? AND p.activo = 1 AND gp.activo = 1
        ORDER BY p.fecha_inicio DESC
    ");
    $stmt->execute([$grupo_id]);
}
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías del grupo para el selector
$stmt = $pdo->prepare("SELECT id, nombre, color FROM categorias WHERE grupo_id = ? AND activo = 1 ORDER BY nombre");
$stmt->execute([$grupo_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Color principal
$color_principal = $categoria ? $categoria['color'] : ($grupo['color'] ?? '#3498db');
$titulo = $categoria ? $categoria['nombre'] : $grupo['nombre'];
$icono = $categoria ? '📋' : ($grupo['icono'] ?? '📂');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Certificados - <?= htmlspecialchars($titulo) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
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
            margin-bottom: 20px;
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
            transform: translateX(-5px);
        }
        
        .header-subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* Stats */
        .stats-row {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-pill {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
        }
        
        .stat-pill i {
            font-size: 18px;
        }
        
        .stat-pill .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .stat-pill .label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Filtros */
        .filters-bar {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            min-width: 180px;
            transition: all 0.2s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: <?= $color_principal ?>;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 14px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2395a5a6' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E") no-repeat 12px center;
            background-size: 20px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: <?= $color_principal ?>;
        }
        
        /* Tabla de estudiantes */
        .students-table-wrapper {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-count {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .students-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #34495e;
            vertical-align: middle;
        }
        
        .students-table tbody tr {
            transition: background 0.2s;
        }
        
        .students-table tbody tr:hover {
            background: #f8fbff;
        }
        
        .student-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .student-cedula {
            font-size: 13px;
            color: #7f8c8d;
            font-family: monospace;
        }
        
        .periodo-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .categoria-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        
        .cert-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .cert-status.generado {
            background: #d4edda;
            color: #155724;
        }
        
        .cert-status.pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        .btn-preview {
            background: #3498db;
            color: white;
        }
        
        .btn-preview:hover {
            background: #2980b9;
        }
        
        .btn-pdf {
            background: #e74c3c;
            color: white;
        }
        
        .btn-pdf:hover {
            background: #c0392b;
        }
        
        .btn-img {
            background: #9b59b6;
            color: white;
        }
        
        .btn-img:hover {
            background: #8e44ad;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #7f8c8d;
        }
        
        .empty-state p {
            margin: 0;
        }
        
        /* Modal de Preview */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 1000px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            width: 40px;
            height: 40px;
            border: none;
            background: #ecf0f1;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            color: #7f8c8d;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            background: #e74c3c;
            color: white;
        }
        
        .modal-body {
            padding: 25px;
            overflow-y: auto;
            flex: 1;
        }
        
        .preview-container {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .preview-loading {
            padding: 60px;
            color: #7f8c8d;
        }
        
        .preview-loading i {
            font-size: 48px;
            margin-bottom: 15px;
            color: <?= $color_principal ?>;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 450px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .preview-error {
            padding: 40px;
            background: #fff3cd;
            border-radius: 10px;
            color: #856404;
        }
        
        .preview-error i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        .info-card .label {
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-card .value {
            font-size: 15px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            justify-content: center;
            gap: 15px;
            background: #f8f9fa;
        }
        
        .btn-modal {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .btn-download-pdf {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-download-img {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }
        
        .btn-close-modal {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #ecf0f1;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #ecf0f1;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #2c3e50;
            transition: all 0.2s;
        }
        
        .page-btn:hover {
            border-color: <?= $color_principal ?>;
            color: <?= $color_principal ?>;
        }
        
        .page-btn.active {
            background: <?= $color_principal ?>;
            border-color: <?= $color_principal ?>;
            color: white;
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group select,
            .filter-group input {
                width: 100%;
            }
            
            .stats-row {
                flex-wrap: wrap;
            }
            
            .students-table {
                font-size: 13px;
            }
            
            .students-table th,
            .students-table td {
                padding: 10px 12px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
        
        /* Notificación */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }
        
        .notification.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-top">
                <div class="header-title">
                    <span class="header-icon"><?= $icono ?></span>
                    <div>
                        <h1>Visualización de Certificados</h1>
                        <div class="header-subtitle">
                            <?php if ($categoria): ?>
                                <?= htmlspecialchars($categoria['grupo_nombre']) ?> &raquo; <?= htmlspecialchars($categoria['nombre']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($grupo['nombre']) ?> - Todas las categorías
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="../grupos/detalle.php?id=<?= $grupo_id ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
            </div>
            
            <div class="stats-row">
                <div class="stat-pill">
                    <i class="fas fa-users"></i>
                    <span class="value" id="totalEstudiantes">0</span>
                    <span class="label">Estudiantes</span>
                </div>
                <div class="stat-pill">
                    <i class="fas fa-certificate"></i>
                    <span class="value" id="totalGenerados">0</span>
                    <span class="label">Certificados Generados</span>
                </div>
                <div class="stat-pill">
                    <i class="fas fa-clock"></i>
                    <span class="value" id="totalPendientes">0</span>
                    <span class="label">Pendientes</span>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-bar">
            <?php if (!$categoria): ?>
            <div class="filter-group">
                <label>Categoría</label>
                <select id="filterCategoria" onchange="filtrarEstudiantes()">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <label>Período</label>
                <select id="filterPeriodo" onchange="filtrarEstudiantes()">
                    <option value="">Todos los períodos</option>
                    <?php foreach ($periodos as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
                        <?php if (isset($p['categoria_nombre'])): ?> (<?= $p['categoria_nombre'] ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Estado</label>
                <select id="filterEstado" onchange="filtrarEstudiantes()">
                    <option value="">Todos</option>
                    <option value="generado">Con certificado</option>
                    <option value="pendiente">Sin certificado</option>
                </select>
            </div>
            
            <div class="filter-group search-box">
                <label>Buscar</label>
                <input type="text" id="searchInput" placeholder="Buscar por nombre o cédula..." onkeyup="filtrarEstudiantes()">
            </div>
        </div>
        
        <!-- Tabla de Estudiantes -->
        <div class="students-table-wrapper">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Lista de Estudiantes</h3>
                <span class="results-count" id="resultsCount">Cargando...</span>
            </div>
            
            <table class="students-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Estudiante</th>
                        <th>Cédula</th>
                        <?php if (!$categoria): ?>
                        <th>Categoría</th>
                        <?php endif; ?>
                        <th>Período</th>
                        <th>Estado</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="<?= $categoria ? 6 : 7 ?>" style="text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin fa-2x" style="color: <?= $color_principal ?>;"></i>
                            <p style="margin: 15px 0 0; color: #7f8c8d;">Cargando estudiantes...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="pagination" id="pagination"></div>
        </div>
    </div>
    
    <!-- Modal de Preview -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Vista Previa del Certificado</h2>
                <button class="modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-info-grid" id="studentInfoGrid">
                    <!-- Info del estudiante -->
                </div>
                <div class="preview-container" id="previewContainer">
                    <div class="preview-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Generando vista previa...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-download-pdf" id="btnDownloadPdf" onclick="descargarPDF()">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <button class="btn-modal btn-download-img" id="btnDownloadImg" onclick="descargarImagen()">
                    <i class="fas fa-file-image"></i> Descargar Imagen
                </button>
                <button class="btn-modal btn-close-modal" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Variables globales
        let allStudents = [];
        let filteredStudents = [];
        let currentPage = 1;
        const perPage = 20;
        let currentStudent = null;
        const categoriaId = <?= $categoria_id ?: 'null' ?>;
        const grupoId = <?= $grupo_id ?>;
        const tieneCategoria = <?= $categoria ? 'true' : 'false' ?>;
        
        // Cargar estudiantes al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstudiantes);
        
        async function cargarEstudiantes() {
            try {
                let url = 'api/certificados/visualizar.php?action=obtener_estudiantes';
                if (categoriaId) {
                    url += '&categoria_id=' + categoriaId;
                } else {
                    url += '&grupo_id=' + grupoId;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    allStudents = data.estudiantes;
                    filteredStudents = [...allStudents];
                    actualizarEstadisticas();
                    renderizarTabla();
                } else {
                    mostrarError('Error al cargar estudiantes: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar estudiantes');
            }
        }
        
        function actualizarEstadisticas() {
            const total = filteredStudents.length;
            const generados = filteredStudents.filter(e => e.certificado_id).length;
            const pendientes = total - generados;
            
            document.getElementById('totalEstudiantes').textContent = total;
            document.getElementById('totalGenerados').textContent = generados;
            document.getElementById('totalPendientes').textContent = pendientes;
        }
        
        function filtrarEstudiantes() {
            const categoriaFilter = document.getElementById('filterCategoria')?.value || '';
            const periodoFilter = document.getElementById('filterPeriodo').value;
            const estadoFilter = document.getElementById('filterEstado').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            filteredStudents = allStudents.filter(est => {
                // Filtro por categoría
                if (categoriaFilter && est.categoria_id != categoriaFilter) return false;
                
                // Filtro por período
                if (periodoFilter && est.periodo_id != periodoFilter) return false;
                
                // Filtro por estado
                if (estadoFilter === 'generado' && !est.certificado_id) return false;
                if (estadoFilter === 'pendiente' && est.certificado_id) return false;
                
                // Búsqueda
                if (searchTerm) {
                    const nombre = (est.nombre || '').toLowerCase();
                    const cedula = (est.cedula || '').toLowerCase();
                    if (!nombre.includes(searchTerm) && !cedula.includes(searchTerm)) return false;
                }
                
                return true;
            });
            
            currentPage = 1;
            actualizarEstadisticas();
            renderizarTabla();
        }
        
        function renderizarTabla() {
            const tbody = document.getElementById('studentsTableBody');
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            const pageStudents = filteredStudents.slice(start, end);
            
            document.getElementById('resultsCount').textContent = 
                `Mostrando ${Math.min(end, filteredStudents.length)} de ${filteredStudents.length} estudiantes`;
            
            if (pageStudents.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="${tieneCategoria ? 6 : 7}">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h3>No se encontraron estudiantes</h3>
                                <p>Intenta ajustar los filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                `;
                document.getElementById('pagination').innerHTML = '';
                return;
            }
            
            let html = '';
            pageStudents.forEach((est, idx) => {
                const periodoLabel = est.periodo_inicio && est.periodo_fin 
                    ? `${formatDate(est.periodo_inicio)} - ${formatDate(est.periodo_fin)}`
                    : 'Sin período';
                
                html += `
                    <tr data-id="${est.id}">
                        <td style="text-align: center; color: #95a5a6;">${start + idx + 1}</td>
                        <td>
                            <div class="student-name">${escapeHtml(est.nombre)}</div>
                            ${est.es_menor ? '<small style="color: #9b59b6;"><i class="fas fa-child"></i> Menor de edad</small>' : ''}
                        </td>
                        <td><span class="student-cedula">${est.cedula || '-'}</span></td>
                        ${!tieneCategoria ? `<td><span class="categoria-badge" style="background: ${est.categoria_color || '#95a5a6'};">${escapeHtml(est.categoria_nombre || 'Sin categoría')}</span></td>` : ''}
                        <td><span class="periodo-badge">${periodoLabel}</span></td>
                        <td>
                            ${est.certificado_id 
                                ? `<span class="cert-status generado"><i class="fas fa-check-circle"></i> Generado</span>` 
                                : `<span class="cert-status pendiente"><i class="fas fa-clock"></i> Pendiente</span>`
                            }
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-preview" onclick="previsualizarCertificado(${est.id})" title="Ver certificado">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-pdf" onclick="descargarDirectoPDF(${est.id})" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="btn-action btn-img" onclick="descargarDirectoImagen(${est.id})" title="Descargar Imagen">
                                    <i class="fas fa-file-image"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            renderizarPaginacion();
        }
        
        function renderizarPaginacion() {
            const totalPages = Math.ceil(filteredStudents.length / perPage);
            const pagination = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // Botón anterior
            html += `<button class="page-btn" onclick="cambiarPagina(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>`;
            
            // Páginas
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="cambiarPagina(${i})">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<button class="page-btn" disabled>...</button>`;
                }
            }
            
            // Botón siguiente
            html += `<button class="page-btn" onclick="cambiarPagina(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>`;
            
            pagination.innerHTML = html;
        }
        
        function cambiarPagina(page) {
            const totalPages = Math.ceil(filteredStudents.length / perPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderizarTabla();
            document.querySelector('.students-table-wrapper').scrollIntoView({ behavior: 'smooth' });
        }
        
        async function previsualizarCertificado(estudianteId) {
            currentStudent = allStudents.find(e => e.id === estudianteId);
            if (!currentStudent) return;
            
            // Abrir modal
            document.getElementById('previewModal').classList.add('active');
            
            // Mostrar info del estudiante
            document.getElementById('studentInfoGrid').innerHTML = `
                <div class="info-card">
                    <div class="label">Nombre</div>
                    <div class="value">${escapeHtml(currentStudent.nombre)}</div>
                </div>
                <div class="info-card">
                    <div class="label">Cédula</div>
                    <div class="value">${currentStudent.cedula || 'No registrada'}</div>
                </div>
                <div class="info-card">
                    <div class="label">Categoría</div>
                    <div class="value">${escapeHtml(currentStudent.categoria_nombre || 'Sin categoría')}</div>
                </div>
                <div class="info-card">
                    <div class="label">Estado</div>
                    <div class="value">${currentStudent.certificado_id ? '✅ Certificado Generado' : '⏳ Pendiente de generar'}</div>
                </div>
            `;
            
            // Mostrar loading
            document.getElementById('previewContainer').innerHTML = `
                <div class="preview-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Generando vista previa...</p>
                </div>
            `;
            
            try {
                // Generar preview
                const response = await fetch('../api/certificados/visualizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'preview',
                        estudiante_id: estudianteId,
                        categoria_id: currentStudent.categoria_id
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.preview) {
                    document.getElementById('previewContainer').innerHTML = `
                        <img src="${data.preview}" alt="Vista previa del certificado" class="preview-image">
                    `;
                } else {
                    document.getElementById('previewContainer').innerHTML = `
                        <div class="preview-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h4>No se pudo generar la vista previa</h4>
                            <p>${data.message || 'Verifique que la plantilla esté configurada correctamente'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('previewContainer').innerHTML = `
                    <div class="preview-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Error de conexión</h4>
                        <p>No se pudo conectar con el servidor</p>
                    </div>
                `;
            }
        }
        
        function cerrarModal() {
            document.getElementById('previewModal').classList.remove('active');
            currentStudent = null;
        }
        
        async function descargarPDF() {
            if (!currentStudent) return;
            await descargarCertificado(currentStudent.id, 'pdf');
        }
        
        async function descargarImagen() {
            if (!currentStudent) return;
            await descargarCertificado(currentStudent.id, 'imagen');
        }
        
        async function descargarDirectoPDF(estudianteId) {
            await descargarCertificado(estudianteId, 'pdf');
        }
        
        async function descargarDirectoImagen(estudianteId) {
            await descargarCertificado(estudianteId, 'imagen');
        }
        
        async function descargarCertificado(estudianteId, formato) {
            const estudiante = allStudents.find(e => e.id === estudianteId);
            if (!estudiante) return;
            
            mostrarNotificacion('Generando ' + (formato === 'pdf' ? 'PDF' : 'imagen') + '...', 'warning');
            
            try {
                const response = await fetch('../api/certificados/visualizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'descargar',
                        estudiante_id: estudianteId,
                        categoria_id: estudiante.categoria_id,
                        formato: formato
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.archivo) {
                    // Crear enlace de descarga
                    const link = document.createElement('a');
                    link.href = data.archivo;
                    link.download = data.nombre_archivo || `certificado_${estudiante.nombre}.${formato === 'pdf' ? 'pdf' : 'png'}`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    mostrarNotificacion('Descarga iniciada correctamente', 'success');
                } else {
                    mostrarNotificacion(data.message || 'Error al generar el archivo', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
            }
        }
        
        // Utilidades
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-EC', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function mostrarError(mensaje) {
            document.getElementById('studentsTableBody').innerHTML = `
                <tr>
                    <td colspan="${tieneCategoria ? 6 : 7}">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                            <h3>Error</h3>
                            <p>${mensaje}</p>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        function mostrarNotificacion(mensaje, tipo = 'success') {
            // Remover notificaciones anteriores
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notif = document.createElement('div');
            notif.className = `notification ${tipo}`;
            notif.innerHTML = `
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'times-circle' : 'info-circle'}"></i>
                ${mensaje}
            `;
            document.body.appendChild(notif);
            
            setTimeout(() => notif.remove(), 4000);
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('previewModal').addEventListener('click', (e) => {
            if (e.target.id === 'previewModal') cerrarModal();
        });
    </script>
</body>
</html>
