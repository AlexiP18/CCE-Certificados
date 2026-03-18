<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nombre']) ?> - Gestión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/estudiantes/gestion.css">
    <style>
        :root {
            --color-principal: <?= $color_principal ?>;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-top">
                <div class="header-title">
                    <span class="header-icon"><?= htmlspecialchars($categoria['icono'] ?: '📁') ?></span>
                    <div>
                        <h1><?= htmlspecialchars($categoria['nombre']) ?></h1>
                        <div class="header-subtitle">
                            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($categoria['grupo_nombre']) ?>
                            <?php if ($periodo_actual): ?>
                            &nbsp;|&nbsp; <i class="fas fa-calendar"></i> <?= htmlspecialchars($periodo_actual['nombre']) ?>
                            <?php endif; ?>
                            <?php if (!empty($categoria['instructor_nombre'])): ?>
                            &nbsp;|&nbsp; <i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($categoria['instructor_nombre']) ?><?php if (!empty($categoria['instructor_cedula'])): ?> | CI: <?= htmlspecialchars($categoria['instructor_cedula']) ?><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/grupos/detalle.php?id=<?= $grupo_id ?>&periodo_id=<?= $periodo_id ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Grupo
                </a>
            </div>
        </div>
        
        <!-- Selector de Período -->
        <?php if (count($periodos) > 1): ?>
        <div class="periodo-selector">
            <label><i class="fas fa-calendar-alt"></i> Período:</label>
            <select id="periodoSelect" onchange="cambiarPeriodo(this.value)">
                <?php foreach ($periodos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $periodo_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon estudiantes">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalEstudiantes">0</h4>
                    <p>Estudiantes matriculados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon menores" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-child"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalMenores">0</h4>
                    <p>Estudiantes menores de edad</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon certificados">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalCertificados">0</h4>
                    <p>Certificados generados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pendientes">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h4 id="totalPendientes">0</h4>
                    <p>Pendientes de certificado</p>
                </div>
            </div>
        </div>
        
        <!-- Panel único de estudiantes con certificados integrados -->
        <div class="content-panel">
            <!-- Acciones masivas -->
            <div class="bulk-actions" id="bulkActions">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><span id="selectedCount">0</span> estudiantes seleccionados</span>
                </div>
                <div class="bulk-buttons">
                    <button class="btn-bulk btn-bulk-primary" id="btnGenerarSeleccionados" onclick="generarCertificadosSeleccionados()" style="display: none;">
                        <i class="fas fa-check-double"></i> Aprobar / Desaprobar
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnDescargarPdfSeleccionados" onclick="descargarCertificadosSeleccionados('pdf')" style="display: none;">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnDescargarImgSeleccionados" onclick="descargarCertificadosSeleccionados('imagen')" style="display: none;">
                        <i class="fas fa-image"></i> Descargar Imagen
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnToggleDestacados" onclick="toggleDestacadosSeleccionados()" style="display: none; background: #f39c12; border-color: #f39c12;">
                        <i class="fas fa-star"></i> Marcar / Desmarcar
                    </button>
                    <button class="btn-bulk btn-bulk-primary" id="btnQuitarSeleccionados" onclick="confirmarQuitarSeleccionados()" style="display: none; background: #e74c3c; border-color: #e74c3c;">
                        <i class="fas fa-trash-alt"></i> Quitar
                    </button>
                    <button class="btn-bulk btn-bulk-cancel" onclick="cancelarSeleccion()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            <div class="panel-header">
                <h3 class="panel-title"><i class="fas fa-users"></i> Estudiantes y Certificados</h3>
                <div class="search-bar">
                    <input type="text" class="search-input" id="searchEstudiantes" placeholder="Buscar por nombre o cédula...">
                    
                    <!-- Dropdown filtro -->
                    <div class="dropdown-filter">
                        <button class="btn btn-outline" onclick="toggleDropdownFiltro()" id="btnFiltro">
                            <i class="fas fa-filter"></i> <span id="filtroTexto">Todos</span> <i class="fas fa-caret-down" style="margin-left: 5px;"></i>
                        </button>
                        <div class="dropdown-filter-menu" id="dropdownFiltro">
                            <a onclick="aplicarFiltro('todos')" class="active">
                                <i class="fas fa-users"></i> Todos
                            </a>
                            <a onclick="aplicarFiltro('representante')">
                                <i class="fas fa-user-tie"></i> Con Representante
                            </a>
                            <a onclick="aplicarFiltro('mayores')">
                                <i class="fas fa-user-graduate"></i> Mayores de Edad
                            </a>
                            <a onclick="aplicarFiltro('destacados')">
                                <i class="fas fa-star"></i> Destacados
                            </a>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline" onclick="descargarListaEstudiantes()" title="Descargar lista en PDF">
                        <i class="fas fa-file-pdf"></i> Descargar
                    </button>
                    <button class="btn btn-outline" onclick="abrirModalAgregar()">
                        <i class="fas fa-user-plus"></i> Agregar
                    </button>
                    <button class="btn btn-outline" id="btnHistorial" onclick="abrirModalHistorial()" style="display: none;">
                        <i class="fas fa-history"></i> Historial
                    </button>
                    <button class="btn btn-success" id="btnGenerarTodos" onclick="generarTodosCertificados()" style="display: none;">
                        <i class="fas fa-magic"></i> Generar Todos
                    </button>
                    <div class="dropdown" id="dropdownDescargasContainer" style="position: relative; display: none;">
                        <button class="btn btn-primary" onclick="toggleDropdownDescargas()" id="btnDescargas">
                            <i class="fas fa-download"></i> Descargar <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdownDescargas" style="display: none; position: absolute; right: 0; top: 100%; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); z-index: 100; min-width: 180px; margin-top: 5px;">
                            <button class="dropdown-item" onclick="descargarTodosCertificados('pdf')">
                                <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> Todos en PDF
                            </button>
                            <button class="dropdown-item" onclick="descargarTodosCertificados('imagen')">
                                <i class="fas fa-images" style="color: #3498db;"></i> Todos en Imágenes (ZIP)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" class="select-checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th class="col-nombre">Nombre</th>
                            <th>Cédula</th>
                            <th class="col-fecha">F. Nacimiento</th>
                            <th>Contacto</th>
                            <th>Certificado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEstudiantes">
                        <tr>
                            <td colspan="7">
                                <div class="loading">
                                    <i class="fas fa-spinner"></i>
                                    <p>Cargando...</p>
                                </div>
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
                        <select id="rowsPerPage" onchange="cambiarFilasPorPagina(this.value)">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
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
    
    <!-- Modal para agregar estudiante -->
    <div class="modal-overlay" id="modalAgregar">
        <div class="modal modal-lg" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-user-plus"></i> Agregar Estudiante</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalAgregar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <!-- Búsqueda de estudiante existente -->
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Buscar estudiante existente</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="text" id="buscarExistente" placeholder="Escribe nombre o cédula del estudiante..." style="width: 100%; padding-right: 35px;">
                        <button type="button" id="btnLimpiarBuscarExistente" onclick="limpiarBuscadorExistente()" style="display: none; position: absolute; right: 10px; background: none; border: none; color: #95a5a6; cursor: pointer; padding: 5px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="resultadosBusqueda" style="margin-top: 10px; max-height: 150px; overflow-y: auto;"></div>
                </div>
                
                <div style="display: flex; align-items: center; margin: 25px 0;">
                    <div style="flex: 1; border-top: 2px dashed #e8ecf0;"></div>
                    <span style="padding: 0 15px; color: #7f8c8d; font-size: 14px; white-space: nowrap;"><i class="fas fa-plus-circle"></i> O crear nuevo estudiante</span>
                    <div style="flex: 1; border-top: 2px dashed #e8ecf0;"></div>
                </div>
            
            <form id="formNuevoEstudiante" onsubmit="guardarNuevoEstudiante(event)">
                <!-- Toggle menor de edad -->
                <div class="toggle-menor">
                    <input type="checkbox" id="es_menor" onchange="toggleMenorEdad()">
                    <label for="es_menor"><i class="fas fa-child"></i> El/los estudiante(s) son menor(es) de edad</label>
                </div>
                
                <!-- SECCIÓN: Estudiante Mayor de Edad -->
                <div id="datosEstudianteMayor">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user"></i> Datos del Estudiante</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo *</label>
                                <input type="text" id="nuevo_nombre" name="nombre" placeholder="Nombre completo" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" id="nuevo_fecha_nacimiento" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>"
                                       onblur="verificarFechaNacimientoMayor(this)">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cédula * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(10 dígitos)</span></label>
                                <input type="text" id="nuevo_cedula" name="cedula" 
                                       placeholder="Ej: 1712345678" maxlength="10" pattern="[0-9]{10}" required
                                       oninput="validarCedulaInput(this)" onblur="verificarCedula(this)">
                                <small class="field-error" id="cedula_error"></small>
                            </div>
                            <div class="form-group">
                                <label>Celular * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(9 dígitos)</span></label>
                                <div class="celular-input-wrapper">
                                    <span class="celular-prefix">+593</span>
                                    <input type="text" id="nuevo_celular" name="celular" 
                                           placeholder="9XXXXXXXX" maxlength="9" pattern="[0-9]{9}" required
                                           oninput="validarCelularInput(this)" onblur="verificarCelular(this)">
                                </div>
                                <small class="field-error" id="celular_error"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico *</label>
                            <input type="email" id="nuevo_email" name="email" required
                                   placeholder="correo@ejemplo.com" onblur="verificarEmail(this)">
                            <small class="field-error" id="email_error"></small>
                        </div>
                        
                        <!-- Referencias Personales (Mayor) -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-address-book"></i> Referencias Personales <small>(Máximo 3)</small></div>
                            <div id="referencias_mayor_list" class="referencias-list"></div>
                            <button type="button" class="btn-add-referencia" onclick="agregarReferencia('mayor')">
                                <i class="fas fa-plus"></i> Agregar Referencia
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN: Menores de Edad con Representante -->
                <div id="datosRepresentante" style="display: none;">
                    <!-- Datos del Representante -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user-tie"></i> Datos del Representante</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre del Representante *</label>
                                <input type="text" id="representante_nombre" name="representante_nombre" placeholder="Nombre completo del representante">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" id="representante_fecha_nacimiento" name="representante_fecha_nacimiento" max="<?= date('Y-m-d') ?>"
                                       onblur="verificarEdadRepresentante(this)">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cédula del Representante * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(10 dígitos)</span></label>
                                <input type="text" id="representante_cedula" name="representante_cedula" 
                                       placeholder="Ej: 1712345678" maxlength="10" pattern="[0-9]{10}"
                                       oninput="validarCedulaInput(this)" onblur="verificarCedulaRepresentante(this)">
                                <small class="field-error" id="representante_cedula_error"></small>
                            </div>
                            <div class="form-group">
                                <label>Celular del Representante * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(9 dígitos)</span></label>
                                <div class="celular-input-wrapper">
                                    <span class="celular-prefix">+593</span>
                                    <input type="text" id="representante_celular" name="representante_celular" 
                                           placeholder="9XXXXXXXX" maxlength="9" pattern="[0-9]{9}"
                                           oninput="validarCelularInput(this)" onblur="verificarCelularRepresentante(this)">
                                </div>
                                <small class="field-error" id="representante_celular_error"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Correo del Representante</label>
                            <input type="email" id="representante_email" name="representante_email" 
                                   placeholder="correo@ejemplo.com" onblur="verificarEmailRepresentante(this)">
                            <small class="field-error" id="representante_email_error"></small>
                        </div>
                        
                        <!-- Referencias Personales (Representante) -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-address-book"></i> Referencias Personales <small>(Máximo 3)</small></div>
                            <div id="referencias_representante_list" class="referencias-list"></div>
                            <button type="button" class="btn-add-referencia" onclick="agregarReferencia('representante')">
                                <i class="fas fa-plus"></i> Agregar Referencia
                            </button>
                        </div>
                    </div>
                    
                    <!-- Lista de Menores -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-child"></i> Menores de Edad</div>
                        <div class="menores-list" id="listaMenores">
                            <!-- Primer menor (template) -->
                            <div class="menor-item" data-index="0">
                                <div class="menor-header">
                                    <span class="menor-numero"><i class="fas fa-child"></i> Menor #1</span>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nombre del Menor *</label>
                                        <input type="text" name="menores[0][nombre]" placeholder="Nombre completo del menor">
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha de Nacimiento *</label>
                                        <input type="date" name="menores[0][fecha_nacimiento]" max="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Cédula <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(opcional - 10 dígitos)</span></label>
                                        <input type="text" name="menores[0][cedula]" 
                                               placeholder="Ej: 1712345678" maxlength="10"
                                               oninput="validarCedulaInput(this)" onblur="verificarCedulaMenor(this)">
                                        <small class="field-error menor-cedula-error"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Celular <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(opcional - 9 dígitos)</span></label>
                                        <div class="celular-input-wrapper">
                                            <span class="celular-prefix">+593</span>
                                            <input type="text" name="menores[0][celular]" 
                                                   placeholder="9XXXXXXXX" maxlength="9"
                                                   oninput="validarCelularInput(this)" onblur="verificarCelularMenor(this)">
                                        </div>
                                        <small class="field-error menor-celular-error"></small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" name="menores[0][email]" 
                                           placeholder="correo@ejemplo.com" onblur="verificarEmailMenor(this)">
                                    <small class="field-error menor-email-error"></small>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-add-menor" onclick="agregarMenor()">
                            <i class="fas fa-plus-circle"></i> Agregar otro menor
                        </button>
                    </div>
                </div>
                
            </form>
            </div>
            
            <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; flex-shrink: 0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalAgregar')">Cancelar</button>
                <button type="submit" form="formNuevoEstudiante" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar y Matricular
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para aprobar certificado por lotes -->
    <div class="modal-overlay" id="modalAprobarLote" onclick="cerrarModal(event, 'modalAprobarLote')">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 1050px; width: 95%; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-check-double"></i> Aprobar / Desaprobar por Lote</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalAprobarLote')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalAprobarLoteContent" class="modal-body" style="padding: 25px; overflow-y: auto; overflow-x: auto; flex: 1;">
                <p style="color: #666; margin-bottom: 20px;">
                    Verifica la lista de estudiantes seleccionados para aprobación. Puedes quitar de la lista a los estudiantes que no desees aprobar en este lote.
                </p>
                
                <div class="bulk-actions" id="bulkActionsModal" style="display: none; padding: 12px 15px; background: #e8f0fe; border-radius: 8px; margin-bottom: 15px; align-items: center; justify-content: space-between; border: 1px solid #c2dbfe;">
                    <div class="bulk-info" style="color: #1967d2; font-weight: 500;">
                        <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                        <span><span id="selectedCountModal">0</span> estudiantes marcados</span>
                    </div>
                    <div class="bulk-buttons">
                        <button class="btn-bulk btn-bulk-cancel" onclick="quitarSeleccionadosDeLoteModal()" style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                            <i class="fas fa-user-times"></i> Quitar del lote
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <div class="search-input-wrapper" style="border: 1px solid #ccc; border-radius: 8px; overflow: hidden; display: flex; align-items: center; padding: 0 10px; background: white; flex: 1;">
                        <i class="fas fa-search" style="color: #7f8c8d;"></i>
                        <input type="text" id="searchInputAprobarLote" placeholder="Buscar por nombre o cédula..." style="border: none; padding: 10px; width: 100%; outline: none;" oninput="filtrarListaAprobarLote()">
                    </div>
                    
                    <div class="dropdown-filter" style="position: relative;">
                        <button class="btn btn-outline" onclick="toggleDropdownFiltroModal()" id="btnFiltroModal">
                            <i class="fas fa-filter"></i> <span id="filtroTextoModal">Todos</span> <i class="fas fa-caret-down" style="margin-left: 5px;"></i>
                        </button>
                        <div class="dropdown-filter-menu" id="dropdownFiltroModal" style="display: none; position: absolute; top: calc(100% + 5px); right: 0; left: auto; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; border: 1px solid #eee; min-width: 200px; z-index: 1000; overflow: hidden;">
                            <a onclick="aplicarFiltroModal('todos')" class="active" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-users" style="width: 20px; color: #3498db;"></i> Todos</a>
                            <a onclick="aplicarFiltroModal('representante')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-users" style="width: 20px; color: #2ecc71;"></i> Con Representante</a>
                            <a onclick="aplicarFiltroModal('mayores')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; border-bottom: 1px solid #eee; cursor: pointer;"><i class="fas fa-user-graduate" style="width: 20px; color: #9b59b6;"></i> Mayores de edad</a>
                            <a onclick="aplicarFiltroModal('destacados')" style="display: block; padding: 12px 15px; color: #2c3e50; text-decoration: none; cursor: pointer;"><i class="fas fa-star" style="width: 20px; color: #f1c40f;"></i> Destacados</a>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper" style="overflow-x: visible;">
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 12px; text-align: left; width: 40px;">
                                    <input type="checkbox" id="selectAllModal" onchange="toggleSelectAllModal(this)" style="cursor: pointer;">
                                </th>
                                <th style="padding: 12px; text-align: left; width: 50px;">N°</th>
                                <th style="padding: 12px; text-align: left;">Nombre</th>
                                <th style="padding: 12px; text-align: left;">Cédula</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaEstudiantesAprobarLote">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Footer Modal -->
                <div class="pagination-footer" id="paginationFooterModal" style="display: none; flex-wrap: nowrap !important; white-space: nowrap !important; justify-content: space-between; align-items: center; padding-bottom: 5px; min-width: 800px;">
                    <div class="pagination-info" style="white-space: nowrap !important; flex-shrink: 0; margin-right: 15px;">
                        Mostrando <span id="pagStartModal">0</span> - <span id="pagEndModal">0</span> de <span id="pagTotalModal">0</span> estudiantes
                    </div>
                    <div class="pagination-controls" style="flex-wrap: nowrap !important; white-space: nowrap !important; flex-shrink: 0; display: flex; align-items: center; gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <label style="margin: 0;">Filas por página:</label>
                            <select id="rowsPerPageModal" onchange="cambiarFilasPorPaginaModal(this.value)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="-1">Todas</option>
                            </select>
                        </div>
                        
                        <div class="pagination-buttons">
                            <button class="btn-page" id="btnPageFirstModal" onclick="irPaginaModal(1)" title="Primera"><i class="fas fa-angle-double-left"></i></button>
                            <button class="btn-page" id="btnPagePrevModal" onclick="prevPaginaModal()" title="Anterior"><i class="fas fa-angle-left"></i></button>
                            
                            <span class="pagination-current">
                                Página <input type="number" id="pageInputModal" value="1" min="1" onchange="irPaginaManualModal(this.value)"> de <span id="totalPagesModal">1</span>
                            </span>
                            
                            <button class="btn-page" id="btnPageNextModal" onclick="nextPaginaModal()" title="Siguiente"><i class="fas fa-angle-right"></i></button>
                            <button class="btn-page" id="btnPageLastModal" onclick="irPaginaModal('last')" title="Última"><i class="fas fa-angle-double-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer fijo (fuera del scroll) -->
            <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: #fff;">
                <div style="font-weight: bold; color: <?= $color_principal ?>; display: flex; align-items: center; gap: 15px; font-size: 14px;">
                    <div>Total a aprobar: <span id="aprobarLoteCount" style="font-size: 16px;">0</span></div>
                    <div style="color: #f39c12;" title="Seleccionados Destacados"><i class="fas fa-star"></i> <span id="aprobarLoteDestacados">0</span></div>
                    <div style="color: #9b59b6;" title="Seleccionados Menores de Edad"><i class="fas fa-child"></i> <span id="aprobarLoteMenores">0</span></div>
                    <div style="color: #3498db;" title="Seleccionados Representantes"><i class="fas fa-user-tie"></i> <span id="aprobarLoteRepresentantes">0</span></div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalAprobarLote')" style="padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn" id="btnConfirmarAprobarLote" onclick="confirmarAprobacionLote()" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); color: white; padding: 12px 24px; font-weight: 600;">
                        <i class="fas fa-check-double"></i> Aprobar / Desaprobar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar estudiante -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 id="editModalTitle" style="color: white; margin: 0;"><i class="fas fa-user-edit"></i> Editar Estudiante</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalEditar')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formEditarEstudiante" onsubmit="guardarEdicionEstudiante(event)" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                <input type="hidden" id="edit_estudiante_id">
                <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <input type="text" id="edit_nombre" required placeholder="Nombre completo del estudiante" style="flex: 1;">
                            <label class="star-toggle" id="editDestacadoToggle" title="Marcar como estudiante destacado">
                                <input type="checkbox" id="edit_destacado">
                                <i class="fas fa-star"></i>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" id="edit_fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label id="editCedulaLabel">Cédula * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(10 dígitos)</span></label>
                            <input type="text" id="edit_cedula" placeholder="Ej: 1712345678" maxlength="10" required oninput="validarCedulaInput(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label id="editCelularLabel">Celular * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(9 dígitos)</span></label>
                            <div class="celular-input-wrapper">
                                <span class="celular-prefix">+593</span>
                                <input type="text" id="edit_celular" placeholder="9XXXXXXXX" maxlength="9" required oninput="validarCelularInput(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label id="editEmailLabel">Correo Electrónico *</label>
                            <input type="email" id="edit_email" required placeholder="correo@ejemplo.com">
                        </div>
                    </div>
                    <!-- Referencias Personales (Editar) -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-address-book"></i> Referencias Personales <small>(Máximo 3)</small></div>
                        <div id="referencias_editar_list" class="referencias-list"></div>
                        <button type="button" class="btn-add-referencia" onclick="agregarReferencia('editar')">
                            <i class="fas fa-plus"></i> Agregar Referencia
                        </button>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; flex-shrink: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalEditar')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para previsualizar certificado -->
    <div class="modal-overlay" id="modalPreview" onclick="cerrarModal(event, 'modalPreview')">
        <div class="modal modal-xl" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-eye"></i> <span id="previewTitulo">Certificado</span></h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalPreview')" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div class="preview-container">
                    <!-- Imagen del certificado -->
                    <div class="preview-image-wrapper">
                        <div id="previewLoading" style="padding: 60px; color: #7f8c8d;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Cargando certificado...</p>
                        </div>
                        <img id="previewImage" class="preview-image" style="display: none;" alt="Certificado">
                    </div>
                    
                    <!-- Información del certificado -->
                    <div class="preview-info">
                        <div class="preview-badge-valid">
                            <i class="fas fa-check-circle"></i> Certificado Válido
                        </div>
                        
                        <div class="preview-info-card">
                            <h4><i class="fas fa-info-circle"></i> Información</h4>
                            <div class="preview-info-row">
                                <span class="label">Código</span>
                                <span class="value" id="previewCodigo">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Nombre</span>
                                <span class="value" id="previewNombre">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Fecha Certificación</span>
                                <span class="value" id="previewFecha">—</span>
                            </div>
                            <div class="preview-info-row">
                                <span class="label">Emitido el</span>
                                <span class="value" id="previewFechaCreacion">—</span>
                            </div>
                        </div>
                        
                        <div class="preview-razon" id="previewRazonContainer" style="display: none;">
                            <h4><i class="fas fa-quote-left"></i> Razón</h4>
                            <p id="previewRazon"></p>
                        </div>
                        
                        <div class="preview-downloads">
                            <button class="btn btn-secondary" id="btnPreviewDescargarImg" onclick="descargarDesdePreview('imagen')">
                                <i class="fas fa-image"></i> Imagen
                            </button>
                            <button class="btn btn-primary" id="btnPreviewDescargarPdf" onclick="descargarDesdePreview('pdf')">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Historial de Generaciones -->
    <div class="modal-overlay" id="modalHistorial" onclick="cerrarModal(event, 'modalHistorial')">
        <div class="modal modal-lg" onclick="event.stopPropagation()" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $color_principal ?>, <?= $color_principal ?>dd); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-history"></i> Historial de Generaciones</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalHistorial')" style="background: rgba(255,255,255,0.2); color: white;">×</button>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1;">
                <div id="historialContainer">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para quitar estudiante -->
    <div class="modal-overlay" id="modalConfirmarQuitar">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-user-times" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Quitar Estudiante</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de quitar a:
                </p>
                <p id="confirmarQuitarNombre" style="color: #e74c3c; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre del estudiante -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> El estudiante será quitado de esta categoría pero no será eliminado del sistema.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarConfirmarQuitar()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarQuitar" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="confirmarQuitarEstudiante()">
                    <i class="fas fa-user-times"></i> Quitar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmación para desaprobar estudiante -->
    <div class="modal-overlay" id="modalConfirmarDesaprobar">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-undo" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Deshacer Aprobación</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de deshacer la aprobación de:
                </p>
                <p id="confirmarDesaprobarNombre" style="color: #d35400; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre del estudiante -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> El estado del certificado volverá a "Pendiente". Puedes generarlo o aprobarlo nuevamente en cualquier momento.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal(null, 'modalConfirmarDesaprobar')" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarDesaprobar" class="btn" style="background: #e67e22; color: white; min-width: 100px;">
                    <i class="fas fa-undo"></i> Desaprobar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmación para quitar MÚLTIPLES estudiantes -->
    <div class="modal-overlay" id="modalConfirmarQuitarSeleccionados">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 450px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-users-slash" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Quitar Estudiantes</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de quitar a los <strong id="countSeleccionadosConfirm">0</strong> estudiantes seleccionados?
                </p>
                <div style="background: #fff3cd; color: #856404; font-size: 13px; padding: 12px; border-radius: 8px; border: 1px solid #ffeeba; margin-top: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> Esta acción los desvinculará de esta categoría pero no los eliminará permanentemente del sistema.
                </div>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="cerrarConfirmarQuitarSeleccionados()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarQuitarSel" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="quitarSeleccionados()">
                    <i class="fas fa-trash-alt"></i> Quitar estudiantes
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal: Ver Referencias -->
    <div class="modal-overlay" id="modalReferencias" onclick="cerrarModal(event, 'modalReferencias')">
        <div class="modal" onclick="event.stopPropagation()" style="max-width: 520px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 20px 25px; margin: 0; flex-shrink: 0;">
                <h3 id="modalReferenciasTitle" style="color: white; margin: 0;"><i class="fas fa-address-book"></i> Referencias</h3>
                <button class="modal-close" onclick="cerrarModal(null, 'modalReferencias')" style="background: rgba(255,255,255,0.2); color: white;">&times;</button>
            </div>
            <div id="modalReferenciasBody" style="padding: 25px;">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Configuración inicial desde PHP
        const categoriaId = <?= $categoria_id ?>;
        const grupoId = <?= $grupo_id ?>;
        const periodoId = <?= json_encode($periodo_id) ?>;
        const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;
        const categoriaNombre = <?= json_encode($categoria['nombre']) ?>;
        const grupoNombre = <?= json_encode($categoria['grupo_nombre']) ?>;
        let currentPreviewCode = null;
    </script>
    <script src="<?= BASE_URL ?>/js/estudiantes/gestion.js"></script>
</body>
</html>
