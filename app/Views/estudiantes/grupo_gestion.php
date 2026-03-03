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
                    <button type="button" id="btnRegenerarCerts" onclick="regenerarCertificadosSeleccionados()" class="btn-bulk" style="background: white; color: var(--color-grupo); border:none; border-radius:8px; padding:10px 15px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-sync-alt"></i> Regenerar Certificados
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
                            <th>Fecha Matriculación</th>
                            <th>Categoría</th>
                            <th>Fecha Nac. (Edad)</th>
                            <th>Contacto</th>
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

    <!-- Modal Historial -->
    <div id="historyModal">
        <div class="history-content">
            <span class="history-close" onclick="cerrarHistorial()">&times;</span>
            <h3 style="margin-top:0; color:#333;">Historial de Generación</h3>
            <ul class="history-list" id="historyList"></ul>
            <button class="btn-bulk" onclick="cerrarHistorial()" style="width:100%; background:var(--color-grupo); color:white; border:none; border-radius:8px; padding:10px; cursor:pointer;">Cerrar</button>
        </div>
    </div>

    <script>
        // Inject PHP variables
        const grupoId = <?= $grupo_id ?>;
        const grupoColor = '<?= htmlspecialchars($grupo['color']) ?>';
    </script>
    <script src="<?= $jsPath ?>/estudiantes/grupo_gestion.js?v=<?= time() ?>"></script>
</body>
</html>
