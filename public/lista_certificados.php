<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['grupo'] ?? 0;

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

// Obtener todos los certificados del grupo
$stmt = $pdo->prepare("
    SELECT c.*, cat.nombre as categoria_nombre, cat.color as categoria_color, g.nombre as grupo_nombre
    FROM certificados c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN grupos g ON c.grupo_id = g.id
    WHERE c.grupo_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$grupo_id]);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar totales
$total_certificados = count($certificados);
$certificados_con_categoria = count(array_filter($certificados, fn($c) => !empty($c['categoria_id'])));
$certificados_sin_categoria = $total_certificados - $certificados_con_categoria;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados - <?= htmlspecialchars($grupo['nombre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .grupo-header {
            background: linear-gradient(135deg, <?= $grupo['color'] ?>dd 0%, <?= $grupo['color'] ?> 100%);
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
            font-size: 32px;
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
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .view-toggle {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 4px;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .view-btn.active {
            background: <?= $grupo['color'] ?>;
            color: white;
        }
        
        .view-btn:hover:not(.active) {
            background: white;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
            flex: 1;
        }
        
        .filters input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 14px;
        }
        
        .filters select {
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .certificados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .certificados-grid.hidden {
            display: none;
        }
        
        /* Vista de Tabla */
        .table-view {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: none;
        }
        
        .table-view.active {
            display: block;
        }
        
        .table-view table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #ecf0f1;
            border-radius: 10px 10px 0 0;
        }
        
        .select-all-table {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .table-selected-info {
            display: none;
            align-items: center;
            gap: 8px;
            color: #856404;
            font-weight: 600;
            padding: 8px 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .table-selected-info.active {
            display: flex;
        }
        
        .table-actions-buttons {
            display: none;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        
        .table-actions-buttons.active {
            display: flex;
        }
        
        .btn-table-action {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-unselect-table {
            background: #95a5a6;
            color: white;
        }
        
        .btn-unselect-table:hover {
            background: #7f8c8d;
            transform: scale(1.1);
        }
        
        .btn-delete-table {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete-table:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .table-view th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .table-view td {
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            color: #34495e;
        }
        
        .table-view tbody tr:hover {
            background: #f8f9fa;
        }
        
        .certificate-code {
            font-family: monospace;
            font-weight: 600;
            color: <?= $grupo['color'] ?>;
        }
        
        .certificate-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-table {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-table:hover {
            transform: scale(1.1);
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-view:hover {
            background: #2980b9;
        }
        
        .btn-download {
            background: #2ecc71;
            color: white;
        }
        
        .btn-download:hover {
            background: #27ae60;
        }
        
        .categoria-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .sin-categoria-tag {
            background: #95a5a6;
        }
        
        .certificado-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid <?= $grupo['color'] ?>;
            position: relative;
        }
        
        .certificado-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .certificado-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .certificado-codigo {
            font-size: 12px;
            color: #7f8c8d;
            font-family: monospace;
            background: #ecf0f1;
            padding: 4px 8px;
            border-radius: 5px;
        }
        
        .certificado-card .certificado-codigo {
            margin-left: 35px;
        }
        
        .categoria-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        
        .sin-categoria {
            background: #95a5a6;
        }
        
        .certificado-nombre {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .certificado-razon {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .certificado-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }
        
        .certificado-fecha {
            font-size: 12px;
            color: #95a5a6;
        }
        
        .certificado-acciones {
            display: flex;
            gap: 8px;
        }
        
        .btn-accion {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-accion:hover {
            transform: scale(1.1);
        }
        
        .btn-ver {
            background: #3498db;
            color: white;
        }
        
        .btn-ver:hover {
            background: #2980b9;
        }
        
        .btn-descargar {
            background: #2ecc71;
            color: white;
        }
        
        .btn-descargar:hover {
            background: #27ae60;
        }
        
        .btn-imagen {
            background: #9b59b6;
            color: white;
        }
        
        .btn-imagen:hover {
            background: #8e44ad;
        }
        
        .card-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 10;
            appearance: none;
            -webkit-appearance: none;
            border: 2px solid #bdc3c7;
            border-radius: 50%;
            background: white;
            transition: all 0.3s;
        }
        
        .card-checkbox:checked {
            background: #3498db;
            border-color: #3498db;
        }
        
        .card-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        
        .card-checkbox:hover {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
        }
        
        .cards-header-actions {
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .select-all-cards {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .cards-selected-info {
            display: none;
            align-items: center;
            gap: 8px;
            color: #856404;
            font-weight: 600;
            padding: 8px 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .cards-selected-info.active {
            display: flex;
        }
        
        .cards-actions-buttons {
            display: none;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        
        .cards-actions-buttons.active {
            display: flex;
        }
        
        .btn-card-action {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-unselect-cards {
            background: #95a5a6;
            color: white;
        }
        
        .btn-unselect-cards:hover {
            background: #7f8c8d;
            transform: scale(1.1);
        }
        
        .btn-delete-cards {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete-cards:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .select-all-cards input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        /* Modal de Visualización */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 32px;
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.2s;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #e74c3c;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .certificate-preview {
            text-align: center;
            margin-bottom: 30px;
            min-height: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .certificate-preview img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            object-fit: contain;
            margin: 0 auto;
        }
        
        .certificate-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-label i {
            font-size: 14px;
            color: #3498db;
        }
        
        .detail-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .btn-modal {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-modal i {
            font-size: 18px;
        }
        
        .btn-modal-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-modal-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="grupo-header">
            <div class="header-top">
                <div class="header-title">
                    <span style="font-size: 48px;"><?= $grupo['icono'] ?></span>
                    <h1>Todos los Certificados</h1>
                </div>
                <a href="grupo_detalle.php?id=<?= $grupo_id ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_certificados ?></div>
                    <div class="stat-label">Total de Certificados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $certificados_con_categoria ?></div>
                    <div class="stat-label">Con Categoría</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $certificados_sin_categoria ?></div>
                    <div class="stat-label">Sin Categoría</div>
                </div>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="view-toggle">
                <button class="view-btn" onclick="changeView('cards')" data-view="cards">
                    <i class="fas fa-grip-horizontal"></i>
                </button>
                <button class="view-btn active" onclick="changeView('table')" data-view="table">
                    <i class="fas fa-table"></i>
                </button>
            </div>
            
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Buscar por nombre, código o razón..." onkeyup="filtrarCertificados()">
                <select id="categoriaFilter" onchange="filtrarCertificados()">
                    <option value="">Todas las categorías</option>
                    <option value="sin_categoria">Sin categoría</option>
                    <?php
                    $stmt = $pdo->prepare("SELECT id, nombre, color FROM categorias WHERE grupo_id = ? AND activo = 1 ORDER BY nombre");
                    $stmt->execute([$grupo_id]);
                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categorias as $cat):
                    ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Vista de Tabla -->
        <div class="table-view active" id="tableView">
            <?php if (empty($certificados)): ?>
            <div class="no-results">
                <div class="no-results-icon">📭</div>
                <h3>No hay certificados aún</h3>
                <p>Los certificados generados aparecerán aquí</p>
            </div>
            <?php else: ?>
            <div class="table-header-actions">
                <label class="select-all-table">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                    <span>Seleccionar todos</span>
                </label>
                
                <div class="table-selected-info" id="tableSelectedInfo">
                    <i class="fas fa-check-circle" style="color: #ffc107; font-size: 18px;"></i>
                    <span id="selectedCount">0 seleccionados</span>
                </div>
                
                <div class="table-actions-buttons" id="tableActionsButtons">
                    <button onclick="regenerarSeleccionados()" class="btn-table-action btn-regenerate-table" title="Regenerar certificados seleccionados" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button onclick="desmarcarTodos()" class="btn-table-action btn-unselect-table" title="Desmarcar todos">
                        <i class="fas fa-times"></i>
                    </button>
                    <button onclick="eliminarSeleccionados()" class="btn-table-action btn-delete-table" title="Eliminar seleccionados">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Razón</th>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $cert): ?>
                    <tr class="certificado-row" 
                        data-nombre="<?= htmlspecialchars(strtolower($cert['nombre'])) ?>"
                        data-codigo="<?= htmlspecialchars(strtolower($cert['codigo'])) ?>"
                        data-razon="<?= htmlspecialchars(strtolower($cert['razon'])) ?>"
                        data-categoria="<?= $cert['categoria_id'] ?? 'sin_categoria' ?>"
                        data-cert-id="<?= $cert['id'] ?>">
                        <td style="text-align: center; color: #95a5a6; font-weight: 500;">
                            <input type="checkbox" class="cert-checkbox" value="<?= $cert['id'] ?>" onchange="updateBulkActions()" style="width: 18px; height: 18px; cursor: pointer; vertical-align: middle;">
                        </td>
                        <td>
                            <span class="certificate-code"><?= htmlspecialchars($cert['codigo']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($cert['nombre']) ?></td>
                        <td><?= htmlspecialchars($cert['razon']) ?></td>
                        <td><?= date('d/m/Y', strtotime($cert['fecha'])) ?></td>
                        <td>
                            <?php if ($cert['categoria_nombre']): ?>
                            <span class="categoria-tag" style="background: <?= $cert['categoria_color'] ?>;">
                                <?= htmlspecialchars($cert['categoria_nombre']) ?>
                            </span>
                            <?php else: ?>
                            <span class="categoria-tag sin-categoria-tag">Sin categoría</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="certificate-actions">
                                <button onclick="viewCertificate('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-table btn-view" title="Ver certificado">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadCertificatePDF('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-table btn-download" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button onclick="downloadCertificateImage('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-table btn-imagen" title="Descargar imagen">
                                    <i class="fas fa-image"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Vista de Cards -->
        <div class="cards-header-actions">
            <label class="select-all-cards">
                <input type="checkbox" id="selectAllCards" onchange="toggleSelectAllCards()">
                <span>Seleccionar todos</span>
            </label>
            
            <div class="cards-selected-info" id="cardsSelectedInfo">
                <i class="fas fa-check-circle" style="color: #ffc107; font-size: 18px;"></i>
                <span id="selectedCountCards">0 seleccionados</span>
            </div>
            
            <div class="cards-actions-buttons" id="cardsActionsButtons">
                <button onclick="regenerarSeleccionadosCards()" class="btn-card-action btn-regenerate-cards" title="Regenerar certificados seleccionados" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button onclick="desmarcarTodosCards()" class="btn-card-action btn-unselect-cards" title="Desmarcar todos">
                    <i class="fas fa-times"></i>
                </button>
                <button onclick="eliminarSeleccionadosCards()" class="btn-card-action btn-delete-cards" title="Eliminar seleccionados">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="certificados-grid" id="certificadosGrid">
            <?php if (empty($certificados)): ?>
            <div class="no-results">
                <div class="no-results-icon">📭</div>
                <h3>No hay certificados aún</h3>
                <p>Los certificados generados aparecerán aquí</p>
            </div>
            <?php else: ?>
                <?php foreach ($certificados as $cert): ?>
                <div class="certificado-card" 
                     data-nombre="<?= htmlspecialchars(strtolower($cert['nombre'])) ?>"
                     data-codigo="<?= htmlspecialchars(strtolower($cert['codigo'])) ?>"
                     data-razon="<?= htmlspecialchars(strtolower($cert['razon'])) ?>"
                     data-categoria="<?= $cert['categoria_id'] ?? 'sin_categoria' ?>"
                     data-cert-id="<?= $cert['id'] ?>">
                    <input type="checkbox" class="card-checkbox" value="<?= $cert['id'] ?>" onchange="updateBulkActionsCards()">
                    <div class="certificado-header">
                        <span class="certificado-codigo"><?= htmlspecialchars($cert['codigo']) ?></span>
                        <?php if ($cert['categoria_nombre']): ?>
                        <span class="categoria-badge" style="background: <?= $cert['categoria_color'] ?>;">
                            <?= htmlspecialchars($cert['categoria_nombre']) ?>
                        </span>
                        <?php else: ?>
                        <span class="categoria-badge sin-categoria">Sin categoría</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="certificado-nombre"><?= htmlspecialchars($cert['nombre']) ?></div>
                    <div class="certificado-razon"><?= htmlspecialchars($cert['razon']) ?></div>
                    
                    <div class="certificado-footer">
                        <span class="certificado-fecha">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($cert['fecha'])) ?>
                        </span>
                        <div class="certificado-acciones">
                            <button onclick="viewCertificate('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-accion btn-ver" title="Ver certificado">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="downloadCertificatePDF('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-accion btn-descargar" title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button onclick="downloadCertificateImage('<?= htmlspecialchars($cert['codigo']) ?>')" class="btn-accion btn-imagen" title="Descargar imagen">
                                <i class="fas fa-image"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Visualización -->
    <div id="viewModal" class="modal" onclick="if(event.target === this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-certificate"></i> Detalle del Certificado</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="certificate-preview">
                    <img id="certificateImage" src="" alt="Certificado" style="display: none;">
                </div>
                <div class="certificate-details">
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-barcode"></i> Código</div>
                        <div class="detail-value" id="modalCertCode">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-user"></i> Nombre Completo</div>
                        <div class="detail-value" id="modalCertName">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-clipboard"></i> Razón</div>
                        <div class="detail-value" id="modalCertReason">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-calendar-alt"></i> Fecha de Emisión</div>
                        <div class="detail-value" id="modalCertDate">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-layer-group"></i> Grupo</div>
                        <div class="detail-value" id="modalCertGroup">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-tag"></i> Categoría</div>
                        <div class="detail-value" id="modalCertCategory">-</div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn-modal btn-modal-primary" id="btnModalDownloadPDF">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                    <button class="btn-modal btn-modal-primary" id="btnModalDownloadImage" style="background: #9b59b6;">
                        <i class="fas fa-image"></i> Descargar Imagen
                    </button>
                    <button class="btn-modal btn-modal-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Array de todos los certificados para búsqueda en el modal
        const allCertificates = <?= json_encode($certificados) ?>;
        let currentView = 'table';
        
        function changeView(view) {
            currentView = view;
            const tableView = document.getElementById('tableView');
            const cardsView = document.getElementById('certificadosGrid');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            if (view === 'table') {
                tableView.classList.add('active');
                cardsView.classList.add('hidden');
            } else {
                tableView.classList.remove('active');
                cardsView.classList.remove('hidden');
            }
        }
        
        function filtrarCertificados() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoriaFilter = document.getElementById('categoriaFilter').value;
            
            // Filtrar cards
            const cards = document.querySelectorAll('.certificado-card');
            cards.forEach(card => {
                const nombre = card.dataset.nombre;
                const codigo = card.dataset.codigo;
                const razon = card.dataset.razon;
                const categoria = card.dataset.categoria;
                
                const matchesSearch = nombre.includes(searchTerm) || 
                                    codigo.includes(searchTerm) || 
                                    razon.includes(searchTerm);
                                    
                const matchesCategoria = !categoriaFilter || categoria === categoriaFilter;
                
                if (matchesSearch && matchesCategoria) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Filtrar filas de tabla
            const rows = document.querySelectorAll('.certificado-row');
            rows.forEach(row => {
                const nombre = row.dataset.nombre;
                const codigo = row.dataset.codigo;
                const razon = row.dataset.razon;
                const categoria = row.dataset.categoria;
                
                const matchesSearch = nombre.includes(searchTerm) || 
                                    codigo.includes(searchTerm) || 
                                    razon.includes(searchTerm);
                                    
                const matchesCategoria = !categoriaFilter || categoria === categoriaFilter;
                
                if (matchesSearch && matchesCategoria) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function viewCertificate(codigo) {
            // Buscar el certificado completo
            const cert = allCertificates.find(c => c.codigo === codigo);
            if (!cert) return;
            
            // Llenar el modal con los datos
            document.getElementById('modalCertCode').textContent = cert.codigo;
            document.getElementById('modalCertName').textContent = cert.nombre;
            document.getElementById('modalCertReason').textContent = cert.razon || 'N/A';
            document.getElementById('modalCertDate').textContent = new Date(cert.fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('modalCertGroup').textContent = cert.grupo_nombre || '<?= htmlspecialchars($grupo['nombre']) ?>';
            document.getElementById('modalCertCategory').textContent = cert.categoria_nombre || 'Sin categoría';
            
            // Mostrar imagen si existe
            const previewImg = document.getElementById('certificateImage');
            const previewContainer = document.querySelector('.certificate-preview');
            
            if (cert.archivo_imagen) {
                // Construir la ruta correcta desde la raíz del proyecto
                const basePath = window.location.pathname.includes('/public/') 
                    ? '../uploads/' 
                    : '/cce-certificados/uploads/';
                previewImg.src = basePath + cert.archivo_imagen;
                previewImg.style.display = 'block';
                previewContainer.style.display = 'block';
                
                // Manejar error de carga de imagen
                previewImg.onerror = function() {
                    console.error('Error al cargar imagen:', cert.archivo_imagen);
                    console.error('Ruta intentada:', this.src);
                    this.style.display = 'none';
                    previewContainer.innerHTML = '<p style="color: #e74c3c; padding: 20px;">⚠️ No se pudo cargar la imagen del certificado</p>';
                };
                
                previewImg.onload = function() {
                    console.log('Imagen cargada correctamente:', cert.archivo_imagen);
                };
            } else {
                previewImg.style.display = 'none';
                previewContainer.innerHTML = '<p style="color: #95a5a6; padding: 20px;">📭 Este certificado no tiene imagen de previsualización</p>';
            }
            
            // Configurar botones de descarga
            const btnPDF = document.getElementById('btnModalDownloadPDF');
            const btnImage = document.getElementById('btnModalDownloadImage');
            
            btnPDF.onclick = () => downloadCertificatePDF(codigo);
            btnImage.onclick = () => downloadCertificateImage(codigo);
            
            // Habilitar/deshabilitar botones según disponibilidad
            btnPDF.disabled = !cert.archivo_pdf;
            btnImage.disabled = !cert.archivo_imagen;
            
            if (!cert.archivo_pdf) {
                btnPDF.style.opacity = '0.5';
                btnPDF.style.cursor = 'not-allowed';
            } else {
                btnPDF.style.opacity = '1';
                btnPDF.style.cursor = 'pointer';
            }
            
            if (!cert.archivo_imagen) {
                btnImage.style.opacity = '0.5';
                btnImage.style.cursor = 'not-allowed';
            } else {
                btnImage.style.opacity = '1';
                btnImage.style.cursor = 'pointer';
            }
            
            // Mostrar modal
            document.getElementById('viewModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        function downloadCertificatePDF(codigo) {
            const cert = allCertificates.find(c => c.codigo === codigo);
            if (cert && cert.archivo_pdf) {
                const basePath = window.location.pathname.includes('/public/') 
                    ? '../uploads/' 
                    : '/cce-certificados/uploads/';
                const link = document.createElement('a');
                link.href = basePath + cert.archivo_pdf;
                link.download = cert.archivo_pdf;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('❌ No se encontró el archivo PDF del certificado');
            }
        }
        
        function downloadCertificateImage(codigo) {
            const cert = allCertificates.find(c => c.codigo === codigo);
            if (cert && cert.archivo_imagen) {
                const basePath = window.location.pathname.includes('/public/') 
                    ? '../uploads/' 
                    : '/cce-certificados/uploads/';
                const link = document.createElement('a');
                link.href = basePath + cert.archivo_imagen;
                link.download = cert.archivo_imagen;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('❌ No se encontró el archivo de imagen del certificado');
            }
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Funciones de selección masiva
        function toggleSelectAll(checkbox) {
            const rows = document.querySelectorAll('.certificado-row');
            rows.forEach(row => {
                // Solo seleccionar checkboxes de filas visibles
                if (row.style.display !== 'none') {
                    const cb = row.querySelector('.cert-checkbox');
                    if (cb) {
                        cb.checked = checkbox.checked;
                    }
                }
            });
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.cert-checkbox:checked');
            const count = checkboxes.length;
            const selectedInfo = document.getElementById('tableSelectedInfo');
            const actionsButtons = document.getElementById('tableActionsButtons');
            const countSpan = document.getElementById('selectedCount');
            
            if (count > 0) {
                selectedInfo.classList.add('active');
                actionsButtons.classList.add('active');
                countSpan.textContent = `${count} seleccionado${count > 1 ? 's' : ''}`;
            } else {
                selectedInfo.classList.remove('active');
                actionsButtons.classList.remove('active');
                document.getElementById('selectAll').checked = false;
            }
        }
        
        function desmarcarTodos() {
            const checkboxes = document.querySelectorAll('.cert-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActions();
        }
        
        async function eliminarSeleccionados() {
            const checkboxes = document.querySelectorAll('.cert-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('No hay certificados seleccionados');
                return;
            }
            
            if (!confirm(`¿Estás seguro de eliminar ${ids.length} certificado${ids.length > 1 ? 's' : ''}?\n\nEsta acción no se puede deshacer y eliminará los archivos PDF e imagen asociados.`)) {
                return;
            }
            
            try {
                const promises = ids.map(id => {
                    return fetch('api_certificados.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: id
                        })
                    }).then(res => res.json());
                });
                
                const results = await Promise.all(promises);
                const failed = results.filter(r => !r.success);
                
                if (failed.length > 0) {
                    alert(`⚠️ Se eliminaron ${ids.length - failed.length} certificados. ${failed.length} fallaron.`);
                } else {
                    alert(`✅ ${ids.length} certificado${ids.length > 1 ? 's eliminados' : ' eliminado'} correctamente`);
                }
                location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al eliminar los certificados');
            }
        }
        
        async function regenerarSeleccionados() {
            const checkboxes = document.querySelectorAll('.cert-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('No hay certificados seleccionados');
                return;
            }
            
            if (!confirm(`¿Desea regenerar ${ids.length} certificado${ids.length > 1 ? 's' : ''}?\n\nEsto actualizará los archivos PDF e imagen con la plantilla actual.`)) {
                return;
            }
            
            // Obtener los códigos de los certificados seleccionados
            const codigos = [];
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                const codigoCell = row.querySelector('.certificate-code');
                if (codigoCell) {
                    codigos.push(codigoCell.textContent.trim());
                }
            });
            
            const btn = document.querySelector('.btn-regenerate-table');
            const originalHTML = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            let exitosos = 0;
            let errores = 0;
            
            try {
                for (const codigo of codigos) {
                    try {
                        const response = await fetch('api_generar_certificados.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'regenerar',
                                codigo: codigo
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            exitosos++;
                        } else {
                            errores++;
                            console.error(`Error regenerando ${codigo}:`, result.error);
                        }
                    } catch (err) {
                        errores++;
                        console.error(`Error en petición para ${codigo}:`, err);
                    }
                }
                
                if (errores === 0) {
                    alert(`✅ ${exitosos} certificado${exitosos > 1 ? 's regenerados' : ' regenerado'} correctamente`);
                } else if (exitosos > 0) {
                    alert(`⚠️ ${exitosos} regenerado${exitosos > 1 ? 's' : ''}, ${errores} error${errores > 1 ? 'es' : ''}`);
                } else {
                    alert('❌ Error al regenerar los certificados');
                }
                
                location.reload();
            } catch (error) {
                console.error('Error general:', error);
                alert('❌ Error al procesar la regeneración');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        }
        
        // Funciones de selección masiva para cards
        function toggleSelectAllCards() {
            const checkbox = document.getElementById('selectAllCards');
            const cards = document.querySelectorAll('.certificado-card');
            cards.forEach(card => {
                // Solo seleccionar checkboxes de cards visibles
                if (card.style.display !== 'none') {
                    const cb = card.querySelector('.card-checkbox');
                    if (cb) {
                        cb.checked = checkbox.checked;
                    }
                }
            });
            updateBulkActionsCards();
        }
        
        function updateBulkActionsCards() {
            const checkboxes = document.querySelectorAll('.card-checkbox:checked');
            const count = checkboxes.length;
            const selectedInfo = document.getElementById('cardsSelectedInfo');
            const actionsButtons = document.getElementById('cardsActionsButtons');
            const countSpan = document.getElementById('selectedCountCards');
            
            if (count > 0) {
                selectedInfo.classList.add('active');
                actionsButtons.classList.add('active');
                countSpan.textContent = `${count} seleccionado${count > 1 ? 's' : ''}`;
            } else {
                selectedInfo.classList.remove('active');
                actionsButtons.classList.remove('active');
                document.getElementById('selectAllCards').checked = false;
            }
        }
        
        function desmarcarTodosCards() {
            const checkboxes = document.querySelectorAll('.card-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            const selectAllCheckbox = document.getElementById('selectAllCards');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActionsCards();
        }
        
        async function eliminarSeleccionadosCards() {
            const checkboxes = document.querySelectorAll('.card-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('No hay certificados seleccionados');
                return;
            }
            
            if (!confirm(`¿Estás seguro de eliminar ${ids.length} certificado${ids.length > 1 ? 's' : ''}?\n\nEsta acción no se puede deshacer y eliminará los archivos PDF e imagen asociados.`)) {
                return;
            }
            
            try {
                const promises = ids.map(id => {
                    return fetch('api_certificados.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: id
                        })
                    }).then(res => res.json());
                });
                
                const results = await Promise.all(promises);
                const failed = results.filter(r => !r.success);
                
                if (failed.length > 0) {
                    alert(`⚠️ Se eliminaron ${ids.length - failed.length} certificados. ${failed.length} fallaron.`);
                } else {
                    alert(`✅ ${ids.length} certificado${ids.length > 1 ? 's eliminados' : ' eliminado'} correctamente`);
                }
                location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al eliminar los certificados');
            }
        }
        
        async function regenerarSeleccionadosCards() {
            const checkboxes = document.querySelectorAll('.card-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('No hay certificados seleccionados');
                return;
            }
            
            if (!confirm(`¿Desea regenerar ${ids.length} certificado${ids.length > 1 ? 's' : ''}?\n\nEsto actualizará los archivos PDF e imagen con la plantilla actual.`)) {
                return;
            }
            
            // Obtener los códigos de los certificados seleccionados
            const codigos = [];
            checkboxes.forEach(cb => {
                const card = cb.closest('.certificado-card');
                const codigoSpan = card.querySelector('.card-code');
                if (codigoSpan) {
                    codigos.push(codigoSpan.textContent.trim());
                }
            });
            
            const btn = document.querySelector('.btn-regenerate-cards');
            const originalHTML = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            let exitosos = 0;
            let errores = 0;
            
            try {
                for (const codigo of codigos) {
                    try {
                        const response = await fetch('api_generar_certificados.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'regenerar',
                                codigo: codigo
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            exitosos++;
                        } else {
                            errores++;
                            console.error(`Error regenerando ${codigo}:`, result.error);
                        }
                    } catch (err) {
                        errores++;
                        console.error(`Error en petición para ${codigo}:`, err);
                    }
                }
                
                if (errores === 0) {
                    alert(`✅ ${exitosos} certificado${exitosos > 1 ? 's regenerados' : ' regenerado'} correctamente`);
                } else if (exitosos > 0) {
                    alert(`⚠️ ${exitosos} regenerado${exitosos > 1 ? 's' : ''}, ${errores} error${errores > 1 ? 'es' : ''}`);
                } else {
                    alert('❌ Error al regenerar los certificados');
                }
                
                location.reload();
            } catch (error) {
                console.error('Error general:', error);
                alert('❌ Error al procesar la regeneración');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        }
    </script>
</body>
</html>
