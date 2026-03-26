<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes del Grupo - Sistema de Certificados</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/estudiantes/grupo_gestion.css?v=<?= time() ?>">
    <style>
        :root {
            --color-grupo: <?= htmlspecialchars($grupo['color']) ?>;
            --color-grupo-rgb: <?= sscanf($grupo['color'], "#%02x%02x%02x", $r, $g, $b); echo "$r, $g, $b"; ?>;
        }
    </style>
</head>
<body>
    <!-- Grupo Header -->
    <div class="container">
        <div class="grupo-header">
            <div class="header-content">
                <div class="header-info">
                    <div class="header-icon">
                        <?= htmlspecialchars($grupo['icono']) ?>
                    </div>
                    <div class="header-details">
                        <h1>Estudiantes del Grupo - <?= htmlspecialchars($grupo['nombre']) ?></h1>
                        <p><?= htmlspecialchars($grupo['nombre']) ?> - <?= htmlspecialchars($grupo['descripcion'] ?: 'Gestión de estudiantes inscritos') ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <button onclick="exportarEstudiantes()" class="btn-header">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button onclick="exportarPDF()" class="btn-header">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                    <a href="<?= $basePath ?>/grupos/detalle.php?id=<?= $grupo_id ?>" class="btn-header">
                        <i class="fas fa-arrow-left"></i> Volver al Grupo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--color-grupo), var(--color-grupo)cc);">
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
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-container" style="position: relative;">
            <!-- Barra de acciones masivas -->
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span class="selected-count" id="selectedCountText">
                    <i class="fas fa-check-circle"></i> 0 seleccionados
                </span>
                <div class="actions-group">
                    <button type="button" id="btnGenerarLote" onclick="abrirModalGeneracionLote()" class="btn-bulk" style="background: white; color: var(--color-grupo); border:none; border-radius:8px; padding:10px 15px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-certificate"></i> Generar Certificados
                    </button>
                    <button type="button" id="btnCancelarSeleccion" onclick="deseleccionarTodos()" class="btn-bulk" style="background: rgba(255,255,255,0.3); color: white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            <div class="table-header">
                <h3>
                    <i class="fas fa-list"></i> Listado de Estudiantes
                    <span class="count-badge" id="countBadge">0 estudiantes</span>
                </h3>
                <div class="table-header-actions">
                    <div class="estado-toggle-wrap" title="Filtrar por estado de certificados">
                        <span class="estado-label estado-label-left active" id="estadoLabelAprobados">Aprobados</span>
                        <label class="estado-switch" for="filterModoEstado">
                            <input type="checkbox" id="filterModoEstado" onchange="actualizarModoFiltro(); filtrarEstudiantes()">
                            <span class="estado-slider"></span>
                        </label>
                        <span class="estado-label estado-label-right" id="estadoLabelCertificados">Certificados</span>
                    </div>
                </div>
            </div>
            
            <div class="table-wrapper table-scroll-wrapper">
                <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>

                <table class="estudiantes-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell sticky-col sticky-left-1" style="background: #f8f9fa;">
                                <input type="checkbox" class="select-checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" title="Seleccionar todos">
                            </th>
                            <th class="sticky-col sticky-left-2" style="background: #f8f9fa;">Estudiante</th>
                            <th>Cédula</th>
                            <th>Fecha Nac</th>
                            <th style="min-width: 260px;">Contacto</th>
                            <th style="min-width: 380px;">Categoría</th>
                            <th style="min-width: 230px;">Fec. Matricula</th>
                            <th>Historial</th>
                            <th class="sticky-col sticky-right" style="text-align: right; background: #f8f9fa;">Acciones</th>
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
            </div>

            <!-- Pagination Footer -->
            <div class="pagination-footer" id="paginationFooter" style="display: none;">
                <div class="pagination-info">
                    Mostrando <span id="pagStart">0</span> - <span id="pagEnd">0</span> de <span id="pagTotal">0</span> estudiantes
                </div>
                <div class="pagination-controls">
                    <div>
                        <label>Filas por página:</label>
                        <select id="rowsPerPageSelect" onchange="cambiarFilasPorPagina(this.value)">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="-1">Todas</option>
                        </select>
                    </div>
                    
                    <div class="pagination-buttons">
                        <button class="btn-page" id="btnPageFirst" onclick="irPagina(1)" title="Primera"><i class="fas fa-angle-double-left"></i></button>
                        <button class="btn-page" id="btnPagePrev" onclick="prevPagina()" title="Anterior"><i class="fas fa-angle-left"></i></button>
                        
                        <span class="pagination-current">
                            Página <input type="number" id="pageInput" value="1" min="1" onchange="irPaginaManual(this.value)"> de <span id="totalPages">1</span>
                        </span>
                        
                        <button class="btn-page" id="btnPageNext" onclick="nextPagina()" title="Siguiente"><i class="fas fa-angle-right"></i></button>
                        <button class="btn-page" id="btnPageLast" onclick="irPagina('last')" title="Última"><i class="fas fa-angle-double-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Generación por Lote -->
    <div id="modalGeneracionLote" class="modal" onclick="cerrarModal(event, 'modalGeneracionLote')">
        <div class="modal-content modal-lg" onclick="event.stopPropagation()" style="max-width: 1100px; width: 95%; max-height: 90vh; padding: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div class="modal-header" style="background-color: var(--color-grupo); color: white;">
                <h3 style="margin:0;"><i class="fas fa-certificate"></i> Generación de Certificados por Lote</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalGeneracionLote')">&times;</button>
            </div>

            <div class="modal-body" style="padding: 25px; overflow-y: auto; overflow-x: auto; flex: 1;">
                <div class="gen-lote-tabs-container" style="margin-bottom: 20px;">
                    <div class="gen-lote-tabs" style="display: flex; gap: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px;">
                        <button class="gen-lote-tab active" onclick="switchGenLoteTab('gen-lote-tab-lista', this)" style="background: none; border: none; padding: 10px 15px; font-weight: 600; cursor: pointer; color: var(--color-grupo); border-bottom: 3px solid var(--color-grupo); font-size: 15px;">
                            <i class="fas fa-list"></i> Estudiantes y Categorías
                        </button>
                        <button class="gen-lote-tab" onclick="switchGenLoteTab('gen-lote-tab-preview', this)" style="background: none; border: none; padding: 10px 15px; font-weight: 600; cursor: pointer; color: #6b7280; border-bottom: 3px solid transparent; font-size: 15px; transition: all 0.2s;">
                            <i class="fas fa-image"></i> Previsualización de Plantillas
                        </button>
                    </div>
                </div>
                <div class="gen-lote-tab-content active" id="gen-lote-tab-lista">
                    <div class="gen-lote-lista">
                        <div class="gen-lote-head">
                            <div>
                                <strong>Estudiantes y categorías listos para generar</strong>
                                <p class="gen-muted" id="genLoteResumenText">Cargando...</p>
                            </div>
                        </div>

                        <div id="genLoteListTabsContainer"></div>

                        <div class="bulk-actions" id="bulkActionsGenLote" style="display:none; padding: 12px 15px; background: #e8f0fe; border-radius: 8px; margin: 12px 14px 8px; align-items: center; justify-content: space-between; border: 1px solid #c2dbfe;">
                            <div class="bulk-info" style="color: #1967d2; font-weight: 500;">
                                <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                                <span><span id="selectedCountGenLote">0</span> filas marcadas</span>
                            </div>
                            <div class="bulk-buttons">
                                <button class="btn-bulk btn-bulk-cancel" onclick="quitarSeleccionadosGeneracionLote()" style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                                    <i class="fas fa-user-times"></i> Quitar del lote
                                </button>
                            </div>
                        </div>

                        <div class="gen-lote-tools" style="display: flex; gap: 10px; margin: 0 14px 12px;">
                            <div class="search-input-wrapper" style="border: 1px solid #ccc; border-radius: 8px; overflow: hidden; display: flex; align-items: center; padding: 0 10px; background: white; flex: 1;">
                                <i class="fas fa-search" style="color: #7f8c8d;"></i>
                                <input type="text" id="searchInputGenLote" placeholder="Buscar por nombre o cédula..." style="border: none; padding: 10px; width: 100%; outline: none;" oninput="filtrarGeneracionLote()">
                            </div>

                            <div class="filter-group-gen-lote" style="width: 200px;">
                                <select id="filterPeriodoGenLote" onchange="generacionLotePeriodoFiltro = this.value; currentPageGenLote = 1; renderGeneracionLoteTabla();" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; outline: none; background: white; font-family: inherit; color: #475569; appearance: auto;">
                                    <option value="">Todos los períodos</option>
                                </select>
                            </div>

                            <div class="dropdown-filter" style="position: relative;">
                                <button class="btn btn-outline" onclick="toggleDropdownFiltroGenLote()" id="btnFiltroGenLote">
                                    <i class="fas fa-filter"></i> <span id="filtroTextoGenLote">Todos</span> <i class="fas fa-caret-down" style="margin-left: 5px;"></i>
                                </button>
                                <div class="dropdown-filter-menu" id="dropdownFiltroGenLote" style="display: none; position: absolute; top: calc(100% + 5px); right: 0; left: auto; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; border: 1px solid #eee; min-width: 200px; z-index: 1000; overflow: hidden;">
                                    <a onclick="aplicarFiltroGeneracionLote('todos')" class="active" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-users" style="width: 20px; color: #3498db;"></i> Todos</a>
                                    <a onclick="aplicarFiltroGeneracionLote('representante')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-users" style="width: 20px; color: #2ecc71;"></i> Con Representante</a>
                                    <a onclick="aplicarFiltroGeneracionLote('mayores')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-user-graduate" style="width: 20px; color: #9b59b6;"></i> Mayores de edad</a>
                                    <a onclick="aplicarFiltroGeneracionLote('destacados')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; cursor: pointer;"><i class="fas fa-star" style="width: 20px; color: #f1c40f;"></i> Destacados</a>
                                </div>
                            </div>
                        </div>

                        <div class="gen-lote-table-wrap" style="overflow-x: visible;">
                            <table class="gen-lote-table data-table" style="width: 100%; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                        <th style="padding: 12px; text-align: left; width: 42px;"><input type="checkbox" id="genLoteSelectAll" onchange="toggleGeneracionLoteAll(this.checked)" style="cursor: pointer;"></th>
                                        <th style="padding: 12px; text-align: left; width: 56px;">N°</th>
                                        <th style="padding: 12px; text-align: left;">Nombre</th>
                                        <th style="padding: 12px; text-align: left;">Cédula</th>
                                        <th style="padding: 12px; text-align: left; width: 180px;">Períodos</th>
                                        <th style="padding: 12px; text-align:center; width: 120px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="genLoteBody">
                                    <tr>
                                        <td colspan="6" class="gen-empty">Sin datos</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination-footer" id="paginationFooterGenLote" style="display: none; flex-wrap: nowrap !important; white-space: nowrap !important; justify-content: space-between; align-items: center; padding: 10px 14px 6px; min-width: 760px;">
                            <div class="pagination-info" style="white-space: nowrap !important; flex-shrink: 0; margin-right: 15px;">
                                Mostrando <span id="pagStartGenLote">0</span> - <span id="pagEndGenLote">0</span> de <span id="pagTotalGenLote">0</span> registros
                            </div>
                            <div class="pagination-controls" style="flex-wrap: nowrap !important; white-space: nowrap !important; flex-shrink: 0; display: flex; align-items: center; gap: 15px;">
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <label style="margin: 0;">Filas por página:</label>
                                    <select id="rowsPerPageGenLote" onchange="cambiarFilasPorPaginaGeneracionLote(this.value)">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="-1">Todas</option>
                                    </select>
                                </div>

                                <div class="pagination-buttons">
                                    <button class="btn-page" id="btnPageFirstGenLote" onclick="irPaginaGeneracionLote(1)" title="Primera"><i class="fas fa-angle-double-left"></i></button>
                                    <button class="btn-page" id="btnPagePrevGenLote" onclick="prevPaginaGeneracionLote()" title="Anterior"><i class="fas fa-angle-left"></i></button>

                                    <span class="pagination-current">
                                        Página <input type="number" id="pageInputGenLote" value="1" min="1" onchange="irPaginaManualGeneracionLote(this.value)"> de <span id="totalPagesGenLote">1</span>
                                    </span>

                                    <button class="btn-page" id="btnPageNextGenLote" onclick="nextPaginaGeneracionLote()" title="Siguiente"><i class="fas fa-angle-right"></i></button>
                                    <button class="btn-page" id="btnPageLastGenLote" onclick="irPaginaGeneracionLote('last')" title="Última"><i class="fas fa-angle-double-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gen-lote-tab-content" id="gen-lote-tab-preview" style="display: none;">
                    <div class="gen-lote-preview">
                        <div id="genLotePreviewPlantillas" class="gen-preview-list"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: #fff;">
                <div style="font-weight: bold; color: var(--color-grupo); display: flex; align-items: center; gap: 15px; font-size: 14px;">
                    <div>Total a generar: <span id="genLoteCount" style="font-size: 16px;">0</span></div>
                    <div style="color: #f39c12;" title="Seleccionados Destacados"><i class="fas fa-star"></i> <span id="genLoteDestacados">0</span></div>
                    <div style="color: #9b59b6;" title="Seleccionados Menores de Edad"><i class="fas fa-child"></i> <span id="genLoteMenores">0</span></div>
                    <div style="color: #3498db;" title="Seleccionados Con Representante"><i class="fas fa-user-tie"></i> <span id="genLoteRepresentantes">0</span></div>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary" style="padding: 12px 24px;" onclick="cerrarModal(null, 'modalGeneracionLote')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" id="btnConfirmGeneracionLote" class="btn" style="background: linear-gradient(135deg, var(--color-grupo), var(--color-grupo)); color: white; padding: 12px 24px; font-weight: 600;" onclick="confirmarGeneracionLote()">
                        <i class="fas fa-cogs"></i> Generar Certificados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Certificados -->
    <div id="certificadosModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background-color: var(--color-grupo); color: white;">
                <h3 id="certificadosModalTitle"><i class="fas fa-certificate"></i> Certificados</h3>
                <button class="modal-close" onclick="cerrarCertificadosModal()">&times;</button>
            </div>
            <div class="modal-body" id="certificadosContent" style="max-height: 60vh; overflow-y: auto;">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer" id="certificadosModalFooter" style="padding: 15px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; background: #fff; border-radius: 0 0 8px 8px;">
                <!-- Botón inyectado por JS -->
            </div>
        </div>
    </div>

    <!-- Modal Editar Estudiante -->
    <div id="editEstudianteModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header" style="background-color: var(--color-grupo); color: white;">
                <h3><i class="fas fa-user-edit"></i> Editar Estudiante</h3>
                <button class="modal-close" onclick="cerrarEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editEstudianteForm" onsubmit="guardarEdicionEstudiante(event)">
                    <input type="hidden" name="id" id="edit_estudiante_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre Completo *</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required style="flex: 1;">
                                <label class="star-toggle" title="Marcar como estudiante destacado">
                                    <input type="checkbox" name="destacado" id="edit_destacado">
                                    <i class="fas fa-star"></i>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cédula</label>
                            <input type="text" name="cedula" id="edit_cedula" class="form-control" maxlength="10" oninput="validarCedulaInput(this)" onblur="verificarCedula(this)">
                            <div class="error-feedback">Cédula no válida</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Celular</label>
                            <div class="celular-input-wrapper" id="wrapper_edit_celular">
                                <span class="celular-prefix">+593</span>
                                <input type="text" name="celular" id="edit_celular" class="form-control" placeholder="999999999" maxlength="9" oninput="validarCelularInput(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" onblur="verificarEmail(this)">
                            <div class="error-feedback">Email no válido</div>
                        </div>
                    </div>

                    <div class="form-row" style="grid-template-columns: 1fr;">
                        <div class="form-group">
                            <label>Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control" onchange="toggleEditRepresentante()">
                        </div>
                    </div>

                    <div id="editRepresentanteSection" style="display: none; background: #fff8e1; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffe082;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; color: #f57c00; font-size: 16px;"><i class="fas fa-user-tie"></i> Datos del Representante</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Representante</label>
                                <input type="text" name="representante_nombre" id="edit_rep_nombre" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Cédula Representante</label>
                                <input type="text" name="representante_cedula" id="edit_rep_cedula" class="form-control" maxlength="10" oninput="validarCedulaInput(this)" onblur="verificarCedula(this)">
                                <div class="error-feedback">Cédula no válida</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Celular Representante</label>
                                <div class="celular-input-wrapper" id="wrapper_edit_rep_celular">
                                    <span class="celular-prefix">+593</span>
                                    <input type="text" name="representante_celular" id="edit_rep_celular" class="form-control" placeholder="999999999" maxlength="9" oninput="validarCelularInput(this)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Representante</label>
                                <input type="email" name="representante_email" id="edit_rep_email" class="form-control" onblur="verificarEmail(this)">
                                <div class="error-feedback">Email no válido</div>
                            </div>
                        </div>
                    </div>

                    <div class="edit-categorias-section">
                        <h4><i class="fas fa-layer-group"></i> Categorías y Períodos del Grupo</h4>
                        <div id="editCategoriasResumenGroup" class="edit-categorias-list">
                            <div class="edit-categorias-empty">
                                <i class="fas fa-spinner fa-spin"></i> Cargando categorías...
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn-bulk" style="background:#e0e0e0; color:#333; border:none; border-radius:8px; padding:10px 20px; cursor:pointer;" onclick="cerrarEditModal()">Cancelar</button>
                        <button type="submit" class="btn-bulk" style="background:var(--color-grupo); color:white; border:none; border-radius:8px; padding:10px 20px; cursor:pointer;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <div class="modal-body" style="padding: 40px 30px;">
                <div style="width: 70px; height: 70px; background: #fee2e2; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: #dc2626;"></i>
                </div>
                <h3 style="margin: 0 0 15px; color: #1f2937; font-size: 20px;">¿Quitar Estudiante?</h3>
                <p style="color: #6b7280; margin-bottom: 10px;">
                    Está a punto de quitar a <strong id="deleteEstudianteNombre">este estudiante</strong>.
                </p>
                <p style="color: #ef4444; font-size: 13px; margin-bottom: 25px;">
                    <i class="fas fa-info-circle"></i> Esta acción quitará al estudiante de este grupo.
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button onclick="cerrarDeleteModal()" class="btn-bulk" style="background:#e0e0e0; color:#333; border:none; border-radius:8px; padding:12px 25px; cursor:pointer;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button onclick="confirmarEliminarEstudiante()" class="btn-bulk" style="background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; padding:12px 25px;">
                        <i class="fas fa-user-minus"></i> Sí, Quitar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial de Generaciones Original -->
    <div id="historyModal">
        <div class="history-content">
            <span class="history-close" onclick="cerrarHistorial()">&times;</span>
            <h3 style="margin-top:0; color:#333;">Historial de Generación</h3>
            <ul class="history-list" id="historyList"></ul>
            <button class="btn-bulk" onclick="cerrarHistorial()" style="width:100%; background:var(--color-grupo); color:white; border:none; border-radius:8px; padding:10px; cursor:pointer;">Cerrar</button>
        </div>
    </div>

    <!-- Modal Historial de Auditoria (Nuevo) -->
    <div id="modalHistorial" class="modal" onclick="cerrarModal(event, 'modalHistorial')">
        <div class="modal-content modal-lg" onclick="event.stopPropagation()" style="background: white; border-radius: 12px; width: 90%; max-width: 800px; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background-color: var(--color-grupo); padding: 20px 25px; margin: 0; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-history"></i> Historial de Cambios</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalHistorial')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">×</button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div id="historialContainer">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Referencias -->
    <div id="modalReferencias" class="modal" onclick="cerrarModal(event, 'modalReferencias')">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 520px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 20px 25px; margin: 0;">
                <h3 id="modalReferenciasTitle" style="color: white; margin: 0;"><i class="fas fa-address-book"></i> Referencias</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalReferencias')" style="background: rgba(255,255,255,0.2); color: white;">&times;</button>
            </div>
            <div class="modal-body" id="modalReferenciasBody" style="padding: 25px;">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <script>
        // Inject PHP variables
        const grupoId = <?= $grupo_id ?>;
        const grupoColor = '<?= htmlspecialchars($grupo['color']) ?>';
        const categoriasGrupo = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= $jsPath ?>/estudiantes/grupo_gestion.js?v=<?= time() ?>"></script>
</body>
</html>
