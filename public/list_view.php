<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los Certificados - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .main-content {
            display: block !important;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        @media (max-width: 968px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 1200px) {
            .certificates-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .certificates-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .cert-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .cert-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--grupo-color, #3498db);
        }
        
        .cert-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .cert-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .cert-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .cert-title {
            flex: 1;
            min-width: 0;
        }
        
        .cert-title h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cert-code {
            font-size: 12px;
            color: #7f8c8d;
            font-family: monospace;
        }
        
        .cert-info {
            margin-bottom: 12px;
        }
        
        .cert-reason {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .cert-grupo-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #95a5a6;
        }
        
        .cert-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 1000px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-modal {
            background: #e74c3c;
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .close-modal:hover {
            background: #c0392b;
            transform: rotate(90deg);
        }
        
        .cert-preview {
            text-align: center;
            margin: 20px 0;
        }
        
        .cert-preview img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }
        
        .cert-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .cert-details-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .cert-details-item:last-child {
            border-bottom: none;
        }
        
        .cert-details-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }
        
        .cert-details-value {
            color: #6c757d;
            flex: 1;
        }
        
        .download-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn-download {
            padding: 12px 30px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .btn-download-img {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-download-img:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-download-pdf {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-download-pdf:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination span {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            grid-column: 1/-1;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .stats-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .stats-bar .total {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .stats-bar .total strong {
            color: #2c3e50;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="../assets/logos/logo-cce.png" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
            <h1>Todos los Certificados</h1>
            <p class="subtitle">Sistema de Certificados CCE</p>
            <div class="action-buttons">
                <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
            </div>
        </header>

        <div class="main-content">
            <!-- Filtros -->
            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="searchText">Buscar por nombre o código</label>
                        <input type="text" id="searchText" placeholder="Buscar...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterGrupo">Filtrar por grupo</label>
                        <select id="filterGrupo">
                            <option value="">Todos los grupos</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterFecha">Ordenar por</label>
                        <select id="filterFecha">
                            <option value="desc">Más recientes</option>
                            <option value="asc">Más antiguos</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="applyFilters()">🔍 Buscar</button>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="stats-bar">
                <div class="total">
                    Total: <strong id="totalCerts">0</strong> certificados
                </div>
            </div>
            
            <!-- Grid de certificados -->
            <div id="certificatesList" class="certificates-grid">
                <p class="loading" style="grid-column: 1/-1; text-align: center;">Cargando certificados...</p>
            </div>
            
            <!-- Paginación -->
            <div class="pagination" id="pagination" style="display: none;">
                <button id="btnPrev" onclick="prevPage()"><i class="fas fa-arrow-left"></i> Anterior</button>
                <span id="pageInfo">Página 1</span>
                <button id="btnNext" onclick="nextPage()">Siguiente <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Modal para ver certificado -->
    <div id="certModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Certificado</h3>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            
            <div class="cert-preview">
                <img id="certImage" src="" alt="Certificado">
            </div>
            
            <div class="cert-details" id="certDetails">
                <!-- Detalles del certificado -->
            </div>
            
            <div class="download-actions">
                <a id="downloadImg" href="" download class="btn-download btn-download-img">
                    <span>🖼️</span>
                    <span>Descargar Imagen</span>
                </a>
                <a id="downloadPdf" href="" download class="btn-download btn-download-pdf">
                    <span>📄</span>
                    <span>Descargar PDF</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        const iconEmojis = {
            workshop: '🛠️',
            course: '📚',
            trophy: '🏆',
            seminar: '🎓',
            award: '🏅',
            certificate: '📜',
            medal: '🥇',
            star: '⭐',
            rocket: '🚀',
            book: '📖'
        };
        
        let currentPage = 1;
        const itemsPerPage = 12;
        let allCertificates = [];
        let filteredCertificates = [];
        
        async function loadGrupos() {
            try {
                const response = await fetch('api_grupos.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('filterGrupo');
                    data.grupos.forEach(grupo => {
                        const option = document.createElement('option');
                        option.value = grupo.id;
                        option.textContent = grupo.nombre;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error cargando grupos:', error);
            }
        }
        
        async function loadCertificates() {
            try {
                const response = await fetch('list.php?limit=1000');
                const data = await response.json();
                
                if (data.success) {
                    allCertificates = data.certificados;
                    applyFilters();
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('certificatesList').innerHTML = 
                    '<p class="empty-state">Error al cargar certificados</p>';
            }
        }
        
        function applyFilters() {
            const searchText = document.getElementById('searchText').value.toLowerCase();
            const grupoId = document.getElementById('filterGrupo').value;
            const sortOrder = document.getElementById('filterFecha').value;
            
            filteredCertificates = allCertificates.filter(cert => {
                const matchSearch = !searchText || 
                    cert.nombre.toLowerCase().includes(searchText) || 
                    cert.codigo.toLowerCase().includes(searchText);
                const matchGrupo = !grupoId || cert.grupo_id == grupoId;
                return matchSearch && matchGrupo;
            });
            
            // Ordenar
            filteredCertificates.sort((a, b) => {
                const dateA = new Date(a.fecha_creacion);
                const dateB = new Date(b.fecha_creacion);
                return sortOrder === 'desc' ? dateB - dateA : dateA - dateB;
            });
            
            currentPage = 1;
            displayCertificates();
        }
        
        function displayCertificates() {
            const container = document.getElementById('certificatesList');
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageCertificates = filteredCertificates.slice(start, end);
            
            document.getElementById('totalCerts').textContent = filteredCertificates.length;
            
            if (pageCertificates.length > 0) {
                container.innerHTML = pageCertificates.map(cert => {
                    const grupoColor = cert.grupo_color || '#3498db';
                    const grupoIcon = cert.grupo_icono ? iconEmojis[cert.grupo_icono] : '📜';
                    
                    return `
                        <div class="cert-card" onclick='viewCertificate(${JSON.stringify(cert)})' style="--grupo-color: ${grupoColor};">
                            <div class="cert-card-header">
                                <div class="cert-icon">${grupoIcon}</div>
                                <div class="cert-title">
                                    <h4>${cert.nombre}</h4>
                                    <div class="cert-code">${cert.codigo}</div>
                                </div>
                            </div>
                            ${cert.grupo_nombre ? `
                                <div class="cert-grupo-badge" style="background-color: ${grupoColor}; color: white;">
                                    <span>${grupoIcon}</span>
                                    <span>${cert.grupo_nombre}</span>
                                </div>
                            ` : ''}
                            <div class="cert-info">
                                <div class="cert-reason">${cert.razon}</div>
                            </div>
                            <div class="cert-footer">
                                <div class="cert-date">
                                    <span>📅</span>
                                    <span>${new Date(cert.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                // Mostrar paginación
                const totalPages = Math.ceil(filteredCertificates.length / itemsPerPage);
                if (totalPages > 1) {
                    document.getElementById('pagination').style.display = 'flex';
                    document.getElementById('pageInfo').textContent = `Página ${currentPage} de ${totalPages}`;
                    document.getElementById('btnPrev').disabled = currentPage === 1;
                    document.getElementById('btnNext').disabled = currentPage === totalPages;
                } else {
                    document.getElementById('pagination').style.display = 'none';
                }
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📜</div>
                        <h3>No se encontraron certificados</h3>
                        <p>Intenta con otros filtros de búsqueda</p>
                    </div>
                `;
                document.getElementById('pagination').style.display = 'none';
            }
        }
        
        function nextPage() {
            currentPage++;
            displayCertificates();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function prevPage() {
            currentPage--;
            displayCertificates();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function viewCertificate(cert) {
            const modal = document.getElementById('certModal');
            const certImage = document.getElementById('certImage');
            const certDetails = document.getElementById('certDetails');
            const downloadImg = document.getElementById('downloadImg');
            const downloadPdf = document.getElementById('downloadPdf');
            
            const imagePath = `/cce-certificados/uploads/${cert.archivo_imagen}`;
            certImage.src = imagePath;
            certImage.onerror = function() {
                this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="800" height="600"%3E%3Crect fill="%23f0f0f0" width="800" height="600"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-family="Arial" font-size="24"%3EImagen no disponible%3C/text%3E%3C/svg%3E';
            };
            
            certDetails.innerHTML = `
                <div class="cert-details-item">
                    <div class="cert-details-label">Código:</div>
                    <div class="cert-details-value">${cert.codigo}</div>
                </div>
                <div class="cert-details-item">
                    <div class="cert-details-label">Nombre:</div>
                    <div class="cert-details-value">${cert.nombre}</div>
                </div>
                <div class="cert-details-item">
                    <div class="cert-details-label">Razón:</div>
                    <div class="cert-details-value">${cert.razon}</div>
                </div>
                ${cert.grupo_nombre ? `
                    <div class="cert-details-item">
                        <div class="cert-details-label">Grupo:</div>
                        <div class="cert-details-value">${cert.grupo_nombre}</div>
                    </div>
                ` : ''}
                <div class="cert-details-item">
                    <div class="cert-details-label">Fecha:</div>
                    <div class="cert-details-value">${new Date(cert.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })}</div>
                </div>
                <div class="cert-details-item">
                    <div class="cert-details-label">Generado:</div>
                    <div class="cert-details-value">${new Date(cert.fecha_creacion).toLocaleString('es-ES')}</div>
                </div>
            `;
            
            downloadImg.href = `/cce-certificados/uploads/${cert.archivo_imagen}`;
            downloadImg.download = `certificado_${cert.codigo}.png`;
            
            if (cert.archivo_pdf) {
                downloadPdf.href = `/cce-certificados/uploads/${cert.archivo_pdf}`;
                downloadPdf.download = `certificado_${cert.codigo}.pdf`;
                downloadPdf.style.display = 'inline-flex';
            } else {
                downloadPdf.style.display = 'none';
            }
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('certModal').classList.remove('active');
        }
        
        document.getElementById('certModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Búsqueda en tiempo real
        document.getElementById('searchText').addEventListener('input', () => {
            applyFilters();
        });
        
        // Cargar datos al iniciar
        loadGrupos();
        loadCertificates();
    </script>
</body>
</html>
