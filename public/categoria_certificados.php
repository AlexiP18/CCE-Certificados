<?php
require_once '../config/database.php';

$categoria_id = $_GET['id'] ?? 0;

if (empty($categoria_id)) {
    header('Location: index.php');
    exit;
}

$pdo = getConnection();

// Obtener información de la categoría y su grupo
$stmt = $pdo->prepare("
    SELECT c.*, g.nombre as grupo_nombre, g.icono as grupo_icono, g.color as grupo_color
    FROM categorias c
    INNER JOIN grupos g ON c.grupo_id = g.id
    WHERE c.id = ? AND c.activo = 1
");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header('Location: index.php');
    exit;
}

$grupo_id = $categoria['grupo_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nombre']) ?> - Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .categoria-header {
            background: linear-gradient(135deg, <?= $categoria['color'] ?>dd 0%, <?= $categoria['color'] ?> 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .breadcrumb a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: opacity 0.2s;
        }
        
        .breadcrumb a:hover {
            opacity: 0.8;
        }
        
        .breadcrumb-separator {
            opacity: 0.6;
        }
        
        .categoria-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .categoria-title-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .categoria-icon-large {
            font-size: 72px;
            background: rgba(255,255,255,0.2);
            width: 100px;
            height: 100px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .categoria-info h1 {
            margin: 0 0 8px 0;
            font-size: 32px;
        }
        
        .categoria-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-header {
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(10px);
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-header:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .toolbar {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .toolbar-left {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .view-toggle {
            display: flex;
            gap: 5px;
            background: #ecf0f1;
            padding: 5px;
            border-radius: 10px;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .view-btn.active {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: #ecf0f1;
            padding: 10px 15px;
            border-radius: 10px;
            gap: 8px;
        }
        
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 250px;
            font-size: 14px;
        }
        
        .btn-generate {
            padding: 12px 24px;
            background: <?= $categoria['color'] ?>;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-generate:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Vista de Tabla */
        .table-view {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .table-view table {
            width: 100%;
            border-collapse: collapse;
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
            color: <?= $categoria['color'] ?>;
        }
        
        .certificate-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            position: relative;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-download {
            background: #2ecc71;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-icon:hover {
            transform: scale(1.05);
        }
        
        .btn-download-pdf {
            background: #e74c3c;
            color: white;
        }
        
        .btn-download-img {
            background: #9b59b6;
            color: white;
        }
        
        /* Vista de Cards */
        .cards-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .certificate-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .card-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, <?= $categoria['color'] ?>40 0%, <?= $categoria['color'] ?>80 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            color: white;
        }
        
        .card-content {
            padding: 20px;
        }
        
        .card-code {
            font-family: monospace;
            font-weight: 600;
            color: <?= $categoria['color'] ?>;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .card-name {
            font-size: 18px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        
        .card-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .card-info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .card-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #95a5a6;
        }
        
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #7f8c8d;
        }
        
        .empty-state p {
            margin: 0 0 25px 0;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 2px solid #ecf0f1;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination button:hover:not(:disabled) {
            border-color: <?= $categoria['color'] ?>;
            color: <?= $categoria['color'] ?>;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination .page-info {
            padding: 8px 16px;
            color: #7f8c8d;
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
        }
        
        .certificate-preview img {
            max-width: 100%;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        
        .btn-modal-primary {
            background: <?= $categoria['color'] ?>;
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
        
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="categoria-header">
            <div class="breadcrumb">
                <a href="index.php">
                    <span><i class="fas fa-home"></i></span>
                    <span>Inicio</span>
                </a>
                <span class="breadcrumb-separator">›</span>
                <a href="grupo_detalle.php?id=<?= $grupo_id ?>">
                    <span><?= htmlspecialchars($categoria['grupo_icono']) ?></span>
                    <span><?= htmlspecialchars($categoria['grupo_nombre']) ?></span>
                </a>
                <span class="breadcrumb-separator">›</span>
                <span><?= htmlspecialchars($categoria['nombre']) ?></span>
            </div>
            
            <div class="categoria-title-section">
                <div class="categoria-title-left">
                    <div class="categoria-icon-large"><?= htmlspecialchars($categoria['icono']) ?></div>
                    <div class="categoria-info">
                        <h1><?= htmlspecialchars($categoria['nombre']) ?></h1>
                        <p><?= htmlspecialchars($categoria['descripcion']) ?: 'Certificados de esta categoría' ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="config_categoria.php?id=<?= $categoria_id ?>" class="btn-header" style="background: #9b59b6; color: white;">
                        <i class="fas fa-cog"></i> Configurar Plantilla
                    </a>
                    <button onclick="window.history.back()" class="btn-header">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <div class="view-toggle">
                    <button class="view-btn active" onclick="changeView('table')" data-view="table">
                        <i class="fas fa-table"></i>
                    </button>
                    <button class="view-btn" onclick="changeView('cards')" data-view="cards">
                        <i class="fas fa-grip-horizontal"></i>
                    </button>
                </div>
                <div class="search-box">
                    <span><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" placeholder="Buscar por código o nombre..." onkeyup="filterCertificates()">
                </div>
            </div>
            <a href="generate_form.php?grupo=<?= $grupo_id ?>&categoria=<?= $categoria_id ?>" class="btn-generate">
                <i class="fas fa-plus"></i> Generar Certificado
            </a>
        </div>

        <!-- Vista de Tabla -->
        <div id="tableView" class="table-view">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Razón</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <div style="color: #95a5a6;">Cargando certificados...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Vista de Cards -->
        <div id="cardsView" class="cards-view hidden">
            <!-- Se llenará con JavaScript -->
        </div>

        <!-- Paginación -->
        <div class="pagination" id="pagination">
            <button onclick="previousPage()" id="btnPrev">← Anterior</button>
            <span class="page-info" id="pageInfo">Página 1</span>
            <button onclick="nextPage()" id="btnNext">Siguiente →</button>
        </div>
    </div>

    <!-- Modal de Visualización -->
    <div id="viewModal" class="modal" onclick="if(event.target === this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📜 Detalle del Certificado</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="certificate-preview">
                    <img id="certificateImage" src="" alt="Certificado" style="display: none;">
                </div>
                <div class="certificate-details">
                    <div class="detail-item">
                        <div class="detail-label">🔑 Código</div>
                        <div class="detail-value" id="modalCertCode">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">👤 Nombre Completo</div>
                        <div class="detail-value" id="modalCertName">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📝 Razón</div>
                        <div class="detail-value" id="modalCertReason">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📅 Fecha de Emisión</div>
                        <div class="detail-value" id="modalCertDate">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📦 Grupo</div>
                        <div class="detail-value" id="modalCertGroup">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📊 Categoría</div>
                        <div class="detail-value" id="modalCertCategory">-</div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn-modal btn-modal-primary" id="btnModalDownloadPDF">
                        📄 Descargar PDF
                    </button>
                    <button class="btn-modal btn-modal-primary" id="btnModalDownloadImage" style="background: #9b59b6;">
                        🖼️ Descargar Imagen
                    </button>
                    <button class="btn-modal btn-modal-secondary" onclick="closeModal()">
                        ✕ Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentView = 'table';
        let currentPage = 1;
        let totalPages = 1;
        let allCertificates = [];
        let filteredCertificates = [];
        const perPage = 20;
        
        async function loadCertificates() {
            try {
                const response = await fetch(`list.php?categoria=<?= $categoria_id ?>`);
                const data = await response.json();
                
                console.log('Respuesta de la API:', data);
                
                if (data.success && (data.data || data.certificados)) {
                    allCertificates = data.data || data.certificados || [];
                    filteredCertificates = allCertificates;
                    totalPages = Math.ceil(filteredCertificates.length / perPage);
                    
                    console.log('Certificados cargados:', allCertificates.length);
                    
                    renderCertificates();
                } else {
                    console.log('No se encontraron certificados');
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error al cargar certificados:', error);
                showEmptyState();
            }
        }
        
        function renderCertificates() {
            if (filteredCertificates.length === 0) {
                showEmptyState();
                return;
            }
            
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            const pageCertificates = filteredCertificates.slice(start, end);
            
            if (currentView === 'table') {
                renderTableView(pageCertificates);
            } else {
                renderCardsView(pageCertificates);
            }
            
            updatePagination();
        }
        
        function renderTableView(certificates) {
            const tbody = document.getElementById('tableBody');
            
            if (certificates.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <div style="color: #95a5a6;">No se encontraron certificados</div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = certificates.map(cert => `
                <tr>
                    <td><span class="certificate-code">${cert.codigo}</span></td>
                    <td>${cert.nombre}</td>
                    <td>${cert.razon || '-'}</td>
                    <td>${new Date(cert.fecha).toLocaleDateString('es-ES')}</td>
                    <td>
                        <div class="certificate-actions">
                            <button class="btn-icon btn-view" onclick="viewCertificate('${cert.codigo}')" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-download-pdf" onclick="downloadCertificatePDF('${cert.codigo}')" title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button class="btn-icon btn-download-img" onclick="downloadCertificateImage('${cert.codigo}')" title="Descargar Imagen">
                                <i class="fas fa-image"></i>
                            </button>
                            <button class="btn-icon btn-delete" onclick="deleteCertificate(${cert.id}, '${cert.nombre}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function renderCardsView(certificates) {
            const container = document.getElementById('cardsView');
            
            if (certificates.length === 0) {
                showEmptyState();
                return;
            }
            
            container.innerHTML = certificates.map(cert => `
                <div class="certificate-card">
                    <div class="card-image">📜</div>
                    <div class="card-content">
                        <div class="card-code">${cert.codigo}</div>
                        <div class="card-name">${cert.nombre}</div>
                        <div class="card-info">
                            <div class="card-info-item">
                                <span>📅</span>
                                <span>${new Date(cert.fecha).toLocaleDateString('es-ES')}</span>
                            </div>
                            ${cert.razon ? `
                            <div class="card-info-item">
                                <span>📝</span>
                                <span>${cert.razon}</span>
                            </div>
                            ` : ''}
                        </div>
                        <div class="card-actions">
                            <button class="btn-icon btn-view" onclick="viewCertificate('${cert.codigo}')" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-download-pdf" onclick="downloadCertificatePDF('${cert.codigo}')" title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button class="btn-icon btn-download-img" onclick="downloadCertificateImage('${cert.codigo}')" title="Descargar Imagen">
                                <i class="fas fa-image"></i>
                            </button>
                            <button class="btn-icon btn-delete" onclick="deleteCertificate(${cert.id}, '${cert.nombre}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function showEmptyState() {
            const emptyHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-clipboard"></i></div>
                    <h3>No hay certificados</h3>
                    <p>Aún no se han generado certificados en esta categoría</p>
                    <a href="generate_form.php?grupo=<?= $grupo_id ?>&categoria=<?= $categoria_id ?>" class="btn-generate">
                        <i class="fas fa-plus"></i> Generar Primer Certificado
                    </a>
                </div>
            `;
            
            if (currentView === 'table') {
                document.getElementById('tableBody').innerHTML = `
                    <tr><td colspan="5">${emptyHTML}</td></tr>
                `;
            } else {
                document.getElementById('cardsView').innerHTML = emptyHTML;
            }
            
            document.getElementById('pagination').style.display = 'none';
        }
        
        function changeView(view) {
            currentView = view;
            
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            if (view === 'table') {
                document.getElementById('tableView').classList.remove('hidden');
                document.getElementById('cardsView').classList.add('hidden');
            } else {
                document.getElementById('tableView').classList.add('hidden');
                document.getElementById('cardsView').classList.remove('hidden');
            }
            
            renderCertificates();
        }
        
        function filterCertificates() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            
            if (search === '') {
                filteredCertificates = allCertificates;
            } else {
                filteredCertificates = allCertificates.filter(cert => 
                    cert.codigo.toLowerCase().includes(search) ||
                    cert.nombre.toLowerCase().includes(search) ||
                    (cert.razon && cert.razon.toLowerCase().includes(search))
                );
            }
            
            currentPage = 1;
            totalPages = Math.ceil(filteredCertificates.length / perPage);
            renderCertificates();
        }
        
        function updatePagination() {
            document.getElementById('pageInfo').textContent = `Página ${currentPage} de ${totalPages}`;
            document.getElementById('btnPrev').disabled = currentPage === 1;
            document.getElementById('btnNext').disabled = currentPage === totalPages;
            document.getElementById('pagination').style.display = totalPages > 1 ? 'flex' : 'none';
        }
        
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderCertificates();
            }
        }
        
        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                renderCertificates();
            }
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
            document.getElementById('modalCertGroup').textContent = cert.grupo_nombre || 'N/A';
            document.getElementById('modalCertCategory').textContent = cert.categoria_nombre || 'N/A';
            
            // Mostrar imagen si existe
            const previewImg = document.getElementById('certificateImage');
            if (cert.archivo_imagen) {
                previewImg.src = '../uploads/' + cert.archivo_imagen;
                previewImg.style.display = 'block';
            } else {
                previewImg.style.display = 'none';
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
                const link = document.createElement('a');
                link.href = '../uploads/' + cert.archivo_pdf;
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
                const link = document.createElement('a');
                link.href = '../uploads/' + cert.archivo_imagen;
                link.download = cert.archivo_imagen;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('❌ No se encontró el archivo de imagen del certificado');
            }
        }
        
        function deleteCertificate(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar el certificado de "${nombre}"?`)) {
                console.log('Eliminando certificado:', id);
                
                fetch('api_certificados.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', id: id})
                })
                .then(response => {
                    console.log('Respuesta HTTP:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    if (data.success) {
                        alert('✅ Certificado eliminado correctamente');
                        loadCertificates();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en la petición:', error);
                    alert('❌ Error al eliminar el certificado: ' + error.message);
                });
            }
        }
        
        // Cargar certificados al inicio
        loadCertificates();
    </script>
</body>
</html>
