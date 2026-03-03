<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/estudiantes/index.css">
</head>
<body>

    <!-- MENÚ DE NAVEGACIÓN FIJO -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        
        <ul class="nav-menu">
            <li>
                <a href="<?= $basePath ?>/dashboard/index.php" class="nav-link">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </li>
            <li>
                <a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link active">
                    <i class="fas fa-user-graduate"></i> Estudiantes
                </a>
            </li>
            <?php if (esAdmin() || ($usuario['rol_nombre'] ?? '') === 'Administrador'): ?>
            <li>
                <a href="<?= $basePath ?>/grupos/config.php" class="nav-link">
                    <i class="fas fa-cog"></i> Configuración
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="nav-user">
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></span>
                <span style="opacity: 0.7;">|</span>
                <span><?= htmlspecialchars($usuario['rol_nombre'] ?? ($usuario['rol'] ?? 'Rol')) ?></span>
            </div>
            <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sessión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>

    <div class="container">
        
        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-user-graduate"></i> Gestión de Estudiantes</h1>
                <p>Administra la información de los estudiantes, sus grupos y certificados.</p>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <?php if (esAdmin() || ($usuario['rol_nombre'] ?? '') === 'Administrador'): ?>
                <a href="<?= $basePath ?>/importar_estudiantes.php" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Importar CSV
                </a>
                <a href="<?= $basePath ?>/crear_estudiante.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Estudiante
                </a>
                <?php endif; ?>
                
                <div class="dropdown-export" style="position: relative; display: inline-block;">
                    <button class="btn btn-secondary" onclick="toggleExportMenu(event)">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <div id="exportMenu" class="export-menu" style="display: none; position: absolute; right: 0; top: 100%; background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); z-index: 100; min-width: 150px; padding: 5px 0;">
                        <a href="#" onclick="exportarLista('excel'); return false;" style="display: block; padding: 10px 15px; color: #333; text-decoration: none; transition: background 0.2s;">
                            <i class="fas fa-file-excel" style="color: #217346; margin-right: 8px;"></i> Excel
                        </a>
                        <a href="#" onclick="exportarLista('pdf'); return false;" style="display: block; padding: 10px 15px; color: #333; text-decoration: none; transition: background 0.2s;">
                            <i class="fas fa-file-pdf" style="color: #f40f02; margin-right: 8px;"></i> PDF
                        </a>
                    </div>
                </div>
                
                <a href="<?= $basePath ?>/dashboard/index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-section">
            <div class="filters-title">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </div>
            
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="searchInput">Buscar Estudiante</label>
                    <div style="position: relative;">
                        <input type="text" id="searchInput" placeholder="Nombre, cédula, email..." style="width: 100%; padding-left: 35px; box-sizing: border-box;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #95a5a6;"></i>
                    </div>
                </div>
                
                <div class="filter-group">
                    <label for="grupoFilter">Filtrar por Grupo</label>
                    <select id="grupoFilter">
                        <option value="">Todos los Grupos</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="categoriaFilter">Filtrar por Categoría</label>
                    <select id="categoriaFilter">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-grupo="<?= $cat['grupo_id'] ?>">
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-check"></i> Aplicar Filtros
                </button>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Limpiar
                </button>
                
                <button id="btnVerCertificados" class="btn btn-secondary" style="display: none; background: #eef2ff; color: #667eea;" onclick="irACertificados()">
                    <i class="fas fa-certificate"></i> Ver Certificados
                </button>
            </div>
        </div>

        <!-- RESULTADOS -->
        <div class="results-section">
            
            <!-- BARRA DE ACCIONES MASIVAS -->
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <div class="bulk-actions-info">
                    <i class="fas fa-check-circle"></i>
                    <span><span id="selectedCount" class="selected-count">0</span> estudiantes seleccionados</span>
                </div>
                <div class="bulk-actions-buttons">
                    <button class="btn-bulk btn-bulk-success" onclick="abrirModalGenerarCertificados()">
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                    <button class="btn-bulk btn-bulk-cancel" onclick="cancelarSeleccion()">
                        Cancelar
                    </button>
                </div>
            </div>

            <div class="results-header">
                <div class="results-count">
                    Mostrando <strong id="pagStart">0</strong> - <strong id="pagEnd">0</strong> de <strong id="pagTotal">0</strong> estudiantes
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <label for="perPageSelect" style="font-size: 14px; color: #5a6c7d;">Mostrar:</label>
                    <select id="perPageSelect" onchange="cambiarFilasPorPagina(this.value)" style="padding: 5px 10px; border-radius: 5px; border: 1px solid #e0e0e0; color: #5a6c7d;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <div class="table-wrapper table-scroll-wrapper">
                <table class="estudiantes-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell sticky-col sticky-left-1" style="background: #f8f9fa;">
                                <input type="checkbox" class="select-checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                            </th>
                            <th class="sticky-col sticky-left-2" style="background: #f8f9fa;">Estudiante</th>
                            <th>Cédula</th>
                            <th>Edad / Cumpleaños</th>
                            <th>Contacto</th>
                            <th style="min-width: 150px;">Grupos</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Actualizado</th>
                            <th>Certificados</th>
                            <th class="sticky-col sticky-right" style="text-align: right; background: #f8f9fa;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="estudiantesTableBody">
                        <!-- Dynamic Content -->
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINACIÓN -->
            <div class="pagination-footer">
                <div class="pagination-info">
                    Página <span id="pageInputDisplay" style="font-weight: bold;">1</span> de <span id="totalPages">1</span>
                </div>
                <div class="pagination-buttons">
                    <button id="btnPageFirst" class="btn-page" onclick="irPagina(1)" disabled><i class="fas fa-angle-double-left"></i></button>
                    <button id="btnPagePrev" class="btn-page" onclick="prevPagina()" disabled><i class="fas fa-angle-left"></i></button>
                    
                    <div class="pagination-current">
                        <input type="number" id="pageInput" value="1" min="1" onchange="irPaginaManual(this.value)">
                    </div>
                    
                    <button id="btnPageNext" class="btn-page" onclick="nextPagina()"><i class="fas fa-angle-right"></i></button>
                    <button id="btnPageLast" class="btn-page" onclick="irPagina('last')"><i class="fas fa-angle-double-right"></i></button>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL DETALLES ESTUDIANTE -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-graduate"></i> Detalles del Estudiante</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Dynamic -->
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancel" onclick="closeModal()">Cerrar</button>
            </div>
        </div>
    </div>
    
    <!-- MODAL GENERAR CERTIFICADOS -->
    <div class="modal-overlay" id="modalCertificadoOverlay" onclick="cerrarModalCertificado(event)">
        <div class="modal modal-certificado">
            <div class="modal-header">
                <h3><i class="fas fa-certificate"></i> Generar Certificados</h3>
                <button class="modal-close" onclick="cerrarModalCertificado()">×</button>
            </div>
            <div class="modal-body">
                
                <div class="info-alert">
                    <i class="fas fa-info-circle"></i>
                    <p>
                        Estás a punto de generar certificados para <strong id="cantidadCertificados">0</strong> estudiante(s).
                        Asegúrese de seleccionar el grupo y la categoría correcta.
                    </p>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Estudiantes Seleccionados</div>
                    <div class="selected-students-list" id="selectedStudentsList">
                        <!-- Dynamic List -->
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Configuración del Certificado</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="certGrupo">Grupo *</label>
                            <select id="certGrupo" onchange="cargarCategoriasGrupo(this.value)">
                                <option value="">Seleccione un grupo...</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="certCategoria">Categoría *</label>
                            <select id="certCategoria" disabled>
                                <option value="">Primero seleccione un grupo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="certFecha">Fecha de Emisión *</label>
                            <input type="date" id="certFecha" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="certRazon">Mérrito / Razón (Opcional)</label>
                            <input type="text" id="certRazon" placeholder="Ej: Excelencia Académica">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancel" onclick="cerrarModalCertificado()">Cancelar</button>
                <button id="btnGenerarCertificados" class="btn-modal btn-modal-success" onclick="generarCertificados()" disabled>
                    <i class="fas fa-check"></i> Generar Certificados
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Info Grupos -->
    <div id="infoGruposModal" class="info-modal-overlay">
        <div class="info-modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group"></i> Grupos y Categorías</h3>
                <button class="modal-close" onclick="cerrarInfoGruposModal()">&times;</button>
            </div>
            <div class="modal-body" id="infoGruposContent" style="padding: 20px;">
                <!-- Dynamic Content -->
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancel" onclick="cerrarInfoGruposModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        window.basePath = "<?= $basePath ?>";
        window.initialGrupoId = "<?= $_GET['grupo_id'] ?? ($_GET['grupo'] ?? '') ?>";
        window.initialCategoriaId = "<?= $_GET['categoria_id'] ?? ($_GET['categoria'] ?? '') ?>";
    </script>
    <script src="<?= $jsPath ?>/estudiantes/index.js"></script>
</body>
</html>
