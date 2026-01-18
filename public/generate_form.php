<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Certificado - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .grupo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .grupo-badge .icon {
            font-size: 20px;
        }
        
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .cert-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
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
        
        .cert-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            padding: 6px 10px;
            font-size: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-view:hover {
            background: #2980b9;
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
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #95a5a6;
        }
        
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .razon-info {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .razon-info .info-label {
            font-weight: 600;
            color: #0c5460;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .razon-info .info-text {
            color: #155724;
            font-size: 14px;
            line-height: 1.5;
            font-style: italic;
        }
        
        /* Estilos para búsqueda de estudiante */
        .search-container {
            position: relative;
        }
        
        .search-input-group {
            display: flex;
            gap: 10px;
        }
        
        .search-input-group input {
            flex: 1;
        }
        
        .search-input-group .btn-search {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .search-input-group .btn-search:hover {
            background: #2980b9;
        }
        
        .search-input-group .btn-search:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .estudiante-card {
            background: #f8f9fa;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }
        
        .estudiante-card.active {
            display: block;
        }
        
        .estudiante-card .estudiante-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .estudiante-card .estudiante-nombre {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .estudiante-card .estudiante-nombre .check-icon {
            color: #27ae60;
        }
        
        .estudiante-card .btn-clear {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .estudiante-card .btn-clear:hover {
            background: #c0392b;
        }
        
        .estudiante-card .estudiante-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .estudiante-card .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .search-error {
            background: #fdf2f2;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
            font-size: 14px;
        }
        
        .search-error.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="../assets/logos/logo-cce.png" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
            <h1>Generar Certificado</h1>
            <div class="action-buttons">
                <a href="index.php" class="btn" id="btnVolver"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </header>

        <div class="main-content">
            <div class="card">
                <div id="grupoBadge"></div>
                
                <h2>Nuevo Certificado</h2>
                
                <form id="certificateForm">
                    <input type="hidden" id="grupo_id" name="grupo_id">
                    <input type="hidden" id="razon" name="razon">
                    <input type="hidden" id="estudiante_id" name="estudiante_id">
                    <input type="hidden" id="nombre" name="nombre">
                    
                    <!-- Búsqueda de estudiante -->
                    <div class="form-group">
                        <label for="buscar_estudiante">Buscar Estudiante *</label>
                        <div class="search-container">
                            <div class="search-input-group">
                                <input type="text" id="buscar_estudiante" 
                                       placeholder="Ingrese cédula o ID del estudiante"
                                       autocomplete="off">
                                <button type="button" class="btn-search" id="btnBuscar" onclick="buscarEstudiante()">
                                    <i class="fas fa-search"></i>
                                    <span>Buscar</span>
                                </button>
                            </div>
                            
                            <div id="searchError" class="search-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span id="searchErrorText">No se encontró ningún estudiante</span>
                            </div>
                            
                            <div id="estudianteCard" class="estudiante-card">
                                <div class="estudiante-header">
                                    <div class="estudiante-nombre">
                                        <i class="fas fa-check-circle check-icon"></i>
                                        <span id="estudianteNombre"></span>
                                    </div>
                                    <button type="button" class="btn-clear" onclick="limpiarEstudiante()">
                                        <i class="fas fa-times"></i> Cambiar
                                    </button>
                                </div>
                                <div class="estudiante-info">
                                    <div class="info-item">
                                        <i class="fas fa-id-card"></i>
                                        <span>Cédula: <strong id="estudianteCedula"></strong></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-envelope"></i>
                                        <span id="estudianteEmail"></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span id="estudianteCelular"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fecha">Fecha de Certificación *</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    
                    <!-- Info de razón configurada -->
                    <div id="razonInfo" class="razon-info" style="display: none;">
                        <div class="info-label"><i class="fas fa-info-circle"></i> Razón configurada:</div>
                        <div id="razonTexto" class="info-text"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="btn-text">Generar Certificado</span>
                        <span class="loader" style="display: none;"></span>
                    </button>
                </form>

                <div id="result" class="result" style="display: none;"></div>
            </div>

            <div class="card">
                <h2>Certificados Recientes del Grupo</h2>
                <div id="certificatesList" class="certificates-grid">
                    <p class="loading" style="grid-column: 1/-1; text-align: center;">Cargando...</p>
                </div>
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

        // Obtener grupo_id y categoria de URL
        const urlParams = new URLSearchParams(window.location.search);
        const grupoId = urlParams.get('grupo');
        const categoriaId = urlParams.get('categoria');
        
        if (grupoId) {
            document.getElementById('grupo_id').value = grupoId;
            // Actualizar enlace de volver para ir al grupo
            document.getElementById('btnVolver').href = `grupo_detalle.php?id=${grupoId}`;
            
            // Si hay categoría, cargar su información primero
            if (categoriaId) {
                loadCategoria(categoriaId);
            } else {
                loadGrupo(grupoId);
            }
            loadCertificados(grupoId, categoriaId);
        } else {
            document.getElementById('grupoBadge').innerHTML = `
                <div class="grupo-badge" style="background: #ecf0f1; color: #7f8c8d;">
                    <span class="icon">📜</span>
                    <span>Certificado General</span>
                </div>
            `;
            loadCertificados();
        }
        
        // Agregar campo oculto de categoria_id si existe
        if (categoriaId) {
            const categoriaInput = document.createElement('input');
            categoriaInput.type = 'hidden';
            categoriaInput.id = 'categoria_id';
            categoriaInput.name = 'categoria_id';
            categoriaInput.value = categoriaId;
            document.getElementById('certificateForm').appendChild(categoriaInput);
        }

        // Establecer fecha actual por defecto
        document.getElementById('fecha').valueAsDate = new Date();

        // Variable para almacenar estudiante seleccionado
        let estudianteSeleccionado = null;

        // Búsqueda de estudiante al presionar Enter
        document.getElementById('buscar_estudiante').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarEstudiante();
            }
        });

        async function buscarEstudiante() {
            const busqueda = document.getElementById('buscar_estudiante').value.trim();
            
            if (!busqueda) {
                mostrarErrorBusqueda('Ingrese una cédula o ID para buscar');
                return;
            }
            
            const btnBuscar = document.getElementById('btnBuscar');
            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            
            try {
                const response = await fetch(`api_estudiantes.php?action=buscar&q=${encodeURIComponent(busqueda)}`);
                const data = await response.json();
                
                if (data.success && data.estudiante) {
                    mostrarEstudiante(data.estudiante);
                } else {
                    mostrarErrorBusqueda(data.message || 'No se encontró ningún estudiante con esa cédula o ID');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarErrorBusqueda('Error al buscar estudiante');
            } finally {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="fas fa-search"></i> <span>Buscar</span>';
            }
        }

        function mostrarEstudiante(estudiante) {
            estudianteSeleccionado = estudiante;
            
            // Ocultar error si estaba visible
            document.getElementById('searchError').classList.remove('active');
            
            // Llenar datos
            document.getElementById('estudiante_id').value = estudiante.id;
            document.getElementById('nombre').value = estudiante.nombre;
            document.getElementById('estudianteNombre').textContent = estudiante.nombre;
            document.getElementById('estudianteCedula').textContent = estudiante.cedula || 'No registrada';
            document.getElementById('estudianteEmail').textContent = estudiante.email || 'Sin email';
            document.getElementById('estudianteCelular').textContent = estudiante.celular || 'Sin celular';
            
            // Mostrar card y ocultar input
            document.getElementById('estudianteCard').classList.add('active');
            document.getElementById('buscar_estudiante').style.display = 'none';
            document.getElementById('btnBuscar').style.display = 'none';
        }

        function mostrarErrorBusqueda(mensaje) {
            document.getElementById('searchErrorText').textContent = mensaje;
            document.getElementById('searchError').classList.add('active');
            document.getElementById('estudianteCard').classList.remove('active');
        }

        function limpiarEstudiante() {
            estudianteSeleccionado = null;
            document.getElementById('estudiante_id').value = '';
            document.getElementById('nombre').value = '';
            document.getElementById('buscar_estudiante').value = '';
            document.getElementById('estudianteCard').classList.remove('active');
            document.getElementById('searchError').classList.remove('active');
            document.getElementById('buscar_estudiante').style.display = 'block';
            document.getElementById('btnBuscar').style.display = 'flex';
            document.getElementById('buscar_estudiante').focus();
        }

        async function loadCategoria(id) {
            try {
                const response = await fetch(`api_categoria_config.php?action=get&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const categoria = data.categoria;
                    const color = categoria.color || '#3498db';
                    
                    document.getElementById('grupoBadge').innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="grupo-badge" style="background-color: ${color}; color: white;">
                                <span class="icon">${categoria.icono || '📁'}</span>
                                <span>${categoria.nombre}</span>
                            </div>
                            <div style="font-size: 13px; color: #7f8c8d;">
                                <i class="fas fa-folder" style="color: ${categoria.grupo_color || '#9b59b6'};"></i>
                                Grupo: ${categoria.grupo_nombre || 'Sin grupo'}
                            </div>
                        </div>
                    `;
                    
                    // Actualizar título de la página
                    document.querySelector('header h1').textContent = `Generar Certificado - ${categoria.nombre}`;
                    
                    // Establecer la razón desde la categoría o el grupo
                    let razonDefecto = '';
                    if (categoria.usar_plantilla_propia && categoria.plantilla_razon_defecto) {
                        razonDefecto = categoria.plantilla_razon_defecto;
                    } else {
                        // Cargar razón del grupo
                        const grupoResponse = await fetch(`api_grupo_config.php?action=get&id=${categoria.grupo_id}`);
                        const grupoData = await grupoResponse.json();
                        if (grupoData.success && grupoData.grupo.razon_defecto) {
                            razonDefecto = grupoData.grupo.razon_defecto;
                        }
                    }
                    
                    if (razonDefecto) {
                        // Reemplazar variables en la razón
                        razonDefecto = razonDefecto.replace(/\{categoria\}/gi, categoria.nombre);
                        razonDefecto = razonDefecto.replace(/\{grupo\}/gi, categoria.grupo_nombre || '');
                        
                        document.getElementById('razon').value = razonDefecto;
                        document.getElementById('razonTexto').textContent = razonDefecto;
                        document.getElementById('razonInfo').style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Error al cargar categoría:', error);
                // Si falla, intentar cargar el grupo
                if (grupoId) {
                    loadGrupo(grupoId);
                }
            }
        }

        async function loadGrupo(id) {
            try {
                const response = await fetch(`api_grupo_config.php?action=get&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const grupo = data.grupo;
                    document.getElementById('grupoBadge').innerHTML = `
                        <div class="grupo-badge" style="background-color: ${grupo.color}; color: white;">
                            <span class="icon">${iconEmojis[grupo.icono] || '📁'}</span>
                            <span>${grupo.nombre}</span>
                        </div>
                    `;
                    
                    // Establecer razón por defecto si existe
                    if (grupo.razon_defecto) {
                        let razonDefecto = grupo.razon_defecto;
                        // Reemplazar variables
                        razonDefecto = razonDefecto.replace(/\{grupo\}/gi, grupo.nombre);
                        
                        document.getElementById('razon').value = razonDefecto;
                        document.getElementById('razonTexto').textContent = razonDefecto;
                        document.getElementById('razonInfo').style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Error al cargar grupo:', error);
            }
        }

        async function loadCertificados(grupoId = null, categoriaId = null) {
            try {
                let url = 'list.php?format=json&limit=6';
                if (grupoId) {
                    url += `&grupo=${grupoId}`;
                }
                if (categoriaId) {
                    url += `&categoria=${categoriaId}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                const container = document.getElementById('certificatesList');
                
                if (data.success && data.certificados.length > 0) {
                    container.innerHTML = data.certificados.map(cert => `
                        <div class="cert-card" onclick='viewCertificate(${JSON.stringify(cert)})'>
                            <div class="cert-card-header">
                                <div class="cert-icon">📜</div>
                                <div class="cert-title">
                                    <h4>${cert.nombre}</h4>
                                    <div class="cert-code">${cert.codigo}</div>
                                </div>
                            </div>
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
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state" style="grid-column: 1/-1;">
                            <div class="icon">📜</div>
                            <p>No hay certificados en este grupo</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('certificatesList').innerHTML = 
                    '<p style="color: #e74c3c; grid-column: 1/-1; text-align: center;">Error al cargar certificados</p>';
            }
        }
        
        function viewCertificate(cert) {
            const modal = document.getElementById('certModal');
            const certImage = document.getElementById('certImage');
            const certDetails = document.getElementById('certDetails');
            const downloadImg = document.getElementById('downloadImg');
            const downloadPdf = document.getElementById('downloadPdf');
            
            console.log('Certificado:', cert); // Debug
            console.log('Archivo imagen:', cert.archivo_imagen);
            
            // Configurar imagen - ruta desde public/
            const imagePath = `/cce-certificados/uploads/${cert.archivo_imagen}`;
            certImage.src = imagePath;
            certImage.onerror = function() {
                console.error('Error cargando imagen:', imagePath);
                this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="800" height="600"%3E%3Crect fill="%23f0f0f0" width="800" height="600"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-family="Arial" font-size="24"%3EImagen no disponible%3C/text%3E%3C/svg%3E';
            };
            
            // Configurar detalles
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
                <div class="cert-details-item">
                    <div class="cert-details-label">Fecha:</div>
                    <div class="cert-details-value">${new Date(cert.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })}</div>
                </div>
                <div class="cert-details-item">
                    <div class="cert-details-label">Generado:</div>
                    <div class="cert-details-value">${new Date(cert.fecha_creacion).toLocaleString('es-ES')}</div>
                </div>
            `;
            
            // Configurar enlaces de descarga
            downloadImg.href = `/cce-certificados/uploads/${cert.archivo_imagen}`;
            downloadImg.download = `certificado_${cert.codigo}.png`;
            
            if (cert.archivo_pdf) {
                downloadPdf.href = `/cce-certificados/uploads/${cert.archivo_pdf}`;
                downloadPdf.download = `certificado_${cert.codigo}.pdf`;
                downloadPdf.style.display = 'inline-flex';
            } else {
                downloadPdf.style.display = 'none';
            }
            
            // Mostrar modal
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('certModal').classList.remove('active');
        }
        
        // Cerrar modal con ESC o click fuera
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

        document.getElementById('certificateForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validar que haya un estudiante seleccionado
            if (!estudianteSeleccionado) {
                alert('Debe buscar y seleccionar un estudiante antes de generar el certificado');
                document.getElementById('buscar_estudiante').focus();
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const loader = submitBtn.querySelector('.loader');
            const resultDiv = document.getElementById('result');
            
            // Deshabilitar botón y mostrar loader
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loader.style.display = 'inline-block';
            resultDiv.style.display = 'none';
            
            try {
                const formData = new FormData(e.target);
                
                const response = await fetch('generate.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Debug: mostrar configuración usada
                if (data.debug_config) {
                    console.log('=== DEBUG CONFIG ===');
                    console.log('Configuración usada para generar certificado:', data.debug_config);
                    console.log('variables_habilitadas:', data.debug_config.variables_habilitadas);
                    console.log('firma_imagen:', data.debug_config.firma_imagen);
                    console.log('posicion_qr:', data.debug_config.posicion_qr_x, data.debug_config.posicion_qr_y);
                    console.log('posicion_firma:', data.debug_config.posicion_firma_x, data.debug_config.posicion_firma_y);
                }
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <h3><i class="fas fa-check-circle"></i> Certificado Generado</h3>
                        <p><strong>Código:</strong> ${data.codigo}</p>
                        <p><strong>Nombre:</strong> ${data.nombre}</p>
                        <div style="margin-top: 15px;">
                            <a href="${data.url_verificacion}" target="_blank" class="btn"><i class="fas fa-eye"></i> Ver Certificado</a>
                            ${data.archivo_pdf ? `<a href="${data.archivo_pdf}" target="_blank" class="btn"><i class="fas fa-download"></i> Descargar PDF</a>` : ''}
                        </div>
                    `;
                    
                    // Resetear formulario
                    e.target.reset();
                    document.getElementById('fecha').valueAsDate = new Date();
                    if (grupoId) {
                        document.getElementById('grupo_id').value = grupoId;
                    }
                    
                    // Limpiar estudiante seleccionado
                    limpiarEstudiante();
                    
                    // Recargar lista
                    loadCertificados(grupoId, categoriaId);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <h3>❌ Error</h3>
                        <p>${data.error || 'No se pudo generar el certificado'}</p>
                    `;
                }
                
                resultDiv.style.display = 'block';
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Error</h3>
                    <p>Error de conexión. Por favor intenta nuevamente.</p>
                `;
                resultDiv.style.display = 'block';
            } finally {
                // Rehabilitar botón
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                loader.style.display = 'none';
            }
        });
    </script>
</body>
</html>
