<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - <?= htmlspecialchars($siteConfig['site_name']) ?></title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/estudiantes/index.css">
    <style>
        :root {
            --site-primary: <?= htmlspecialchars($siteConfig['primary_color']) ?>;
            --site-secondary: <?= htmlspecialchars($siteConfig['secondary_color']) ?>;
        }

        .nav-logo-image {
            width: 34px;
            height: 34px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
            padding: 2px;
        }
    </style>
</head>
<body>

    <!-- MENÚ DE NAVEGACIÓN FIJO -->
    <nav class="top-nav">
        <div class="nav-logo">
            <?php if (!empty($siteConfig['logo_nav_url'])): ?>
            <img src="<?= htmlspecialchars($siteConfig['logo_nav_url']) ?>" alt="Logo" class="nav-logo-image" onerror="this.style.display='none'">
            <?php endif; ?>
            <i class="fas fa-graduation-cap"></i>
            <span><?= htmlspecialchars($siteConfig['site_name']) ?></span>
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
            <li>
                <a href="<?= $basePath ?>/configuracion/index.php" class="nav-link">
                    <i class="fas fa-sliders-h"></i> Sitio
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

        <!-- RESULTADOS -->
        <div class="results-section">
            <div class="table-wrapper table-scroll-wrapper">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i> Listado de Estudiantes
                        <span class="count-badge" id="countBadge">0 estudiantes</span>
                    </h3>
                    <div class="table-header-actions">
                        <div class="table-filter-grid">
                            <div class="table-filter-group table-filter-group-search">
                                <label for="searchInput"><i class="fas fa-search"></i> Buscar</label>
                                <div class="table-search-wrap">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="searchInput" placeholder="Nombre, cédula o email...">
                                </div>
                            </div>

                            <div class="table-filter-group">
                                <label for="grupoFilter"><i class="fas fa-layer-group"></i> Grupo</label>
                                <select id="grupoFilter">
                                    <option value="">Todos los Grupos</option>
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="table-filter-group">
                                <label for="categoriaFilter"><i class="fas fa-folder"></i> Categoría</label>
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

                        <div class="table-filter-actions">
                            <div class="dropdown-filter table-dropdown-filter">
                                <button type="button" class="btn" id="btnFiltroTabla" onclick="toggleDropdownFiltroTabla(event)">
                                    <i class="fas fa-filter"></i>
                                    <span id="filtroTextoTabla">Todos</span>
                                    <i class="fas fa-caret-down"></i>
                                </button>
                                <div class="dropdown-filter-menu" id="dropdownFiltroTabla">
                                    <a data-filtro="todos" onclick="aplicarFiltroTabla('todos')" class="active">
                                        <i class="fas fa-users"></i> Todos
                                    </a>
                                    <a data-filtro="representante" onclick="aplicarFiltroTabla('representante')">
                                        <i class="fas fa-user-tie"></i> Con Representante
                                    </a>
                                    <a data-filtro="mayores" onclick="aplicarFiltroTabla('mayores')">
                                        <i class="fas fa-user-graduate"></i> Mayores de Edad
                                    </a>
                                    <a data-filtro="destacados" onclick="aplicarFiltroTabla('destacados')">
                                        <i class="fas fa-star"></i> Destacados
                                    </a>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-check"></i> Aplicar
                            </button>
                            <button class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                            <button id="btnVerCertificados" class="btn btn-secondary btn-filter-certificados" style="display: none;" onclick="irACertificados()">
                                <i class="fas fa-certificate"></i> Ver Certificados
                            </button>
                        </div>
                    </div>
                </div>

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

                <div class="table-inner-scroll">
                    <table class="estudiantes-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell sticky-col sticky-left-1" style="background: #f8f9fa;">
                                    <input type="checkbox" class="select-checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                                </th>
                                <th class="sticky-col sticky-left-2" style="background: #f8f9fa; min-width: 340px;">Estudiante</th>
                                <th>Cédula</th>
                                <th>Edad / Cumpleaños</th>
                                <th>Contacto</th>
                                <th style="min-width: 150px;">Grupos</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th>Actualizado</th>
                                <th>Certificados</th>
                                <th style="text-align: center;">Historial</th>
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
                        Mostrando <span id="pagStart">0</span> - <span id="pagEnd">0</span> de <span id="pagTotal">0</span> estudiantes
                    </div>

                    <div class="pagination-controls">
                        <div>
                            <label for="rowsPerPageSelect">Filas por página:</label>
                            <select id="rowsPerPageSelect" onchange="cambiarFilasPorPagina(this.value)">
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="-1">Todas</option>
                            </select>
                        </div>

                        <div class="pagination-buttons">
                            <button id="btnPageFirst" class="btn-page" onclick="irPagina(1)" disabled><i class="fas fa-angle-double-left"></i></button>
                            <button id="btnPagePrev" class="btn-page" onclick="prevPagina()" disabled><i class="fas fa-angle-left"></i></button>

                            <span class="pagination-current">
                                Página <input type="number" id="pageInput" value="1" min="1" onchange="irPaginaManual(this.value)"> de <span id="totalPages">1</span>
                            </span>

                            <button id="btnPageNext" class="btn-page" onclick="nextPagina()"><i class="fas fa-angle-right"></i></button>
                            <button id="btnPageLast" class="btn-page" onclick="irPagina('last')"><i class="fas fa-angle-double-right"></i></button>
                        </div>
                    </div>
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

    <!-- MODAL EDITAR ESTUDIANTE -->
    <div class="modal-overlay" id="modalEditarOverlay" onclick="cerrarModalEditar(event)">
        <div class="modal modal-lg edit-modal" style="max-width: 760px;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Editar Estudiante</h3>
                <button class="modal-close" onclick="cerrarModalEditar()">×</button>
            </div>
            <form id="editEstudianteForm" onsubmit="guardarEdicionEstudiante(event)">
                <div class="modal-body edit-modal-body">
                    <input type="hidden" id="edit_estudiante_id" name="id">
                    <input type="hidden" id="edit_es_menor_actual" name="es_menor_actual" value="0">

                    <div id="editMinorWarning" class="edit-minor-warning" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Este estudiante es menor de edad. La edición desde este modal está bloqueada para menores.
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_nombre">Nombre Completo *</label>
                            <input type="text" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_cedula">Cédula</label>
                            <input type="text" id="edit_cedula" name="cedula" maxlength="10" oninput="sanitizarCedulaEdit(this)">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_celular">Celular</label>
                            <div class="input-prefix-wrap">
                                <span class="input-prefix-label">+593</span>
                                <input type="text" id="edit_celular" name="celular" maxlength="9" oninput="sanitizarCelularEdit(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="edit_fecha_nacimiento" name="fecha_nacimiento">
                        </div>
                    </div>

                    <div class="edit-meta-grid">
                        <div class="edit-meta-item">
                            <span class="edit-meta-label"><i class="fas fa-calendar-plus"></i> Registro</span>
                            <span id="editMetaRegistro">-</span>
                        </div>
                        <div class="edit-meta-item">
                            <span class="edit-meta-label"><i class="fas fa-clock"></i> Actualización</span>
                            <span id="editMetaActualizacion">-</span>
                        </div>
                    </div>

                    <div class="edit-enrollment-section">
                        <h4><i class="fas fa-layer-group"></i> Grupos, Categorías y Períodos</h4>
                        <div id="editEnrollmentResumen" class="edit-enrollment-list">
                            <div class="empty-state" style="padding:20px 0;">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Cargando información de inscripción...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="editModalEstado" style="margin-right: auto; color: #64748b; font-size: 13px;"></div>
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalEditar()">Cancelar</button>
                    <button type="submit" id="btnGuardarEdicionEstudiante" class="btn-modal btn-modal-success">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HISTORIAL DE CAMBIOS -->
    <div class="modal-overlay" id="modalHistorialOverlay" onclick="cerrarModalHistorial(event)">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Historial de Cambios</h3>
                <button class="modal-close" onclick="cerrarModalHistorial()">×</button>
            </div>
            <div class="modal-body">
                <div id="historialContainer">
                    <!-- Dynamic Content -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancel" onclick="cerrarModalHistorial()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- MODAL REFERENCIAS -->
    <div class="modal-overlay" id="modalReferenciasOverlay" onclick="cerrarModalReferencias(event)">
        <div class="modal" style="max-width: 520px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 id="modalReferenciasTitle" style="color: white; margin: 0;">
                    <i class="fas fa-address-book"></i> Referencias
                </h3>
                <button class="modal-close" onclick="cerrarModalReferencias()" style="background: rgba(255,255,255,0.2); color: white;">×</button>
            </div>
            <div id="modalReferenciasBody" class="modal-body" style="padding: 25px;">
                <!-- Dynamic Content -->
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
