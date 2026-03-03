/**
 * Gestión de Estudiantes y Certificados
 * 
 * Variables globales requeridas (definidas en la vista):
 * - categoriaId
 * - grupoId
 * - periodoId
 * - esAdmin
 * - currentPreviewCode
 */

let estudiantes = [];
let certificados = [];
let seleccionados = [];
let contadorMenores = 1;
let filtroActual = 'todos'; // todos, representante, mayores
let rowsPerPage = 25;
let currentPage = 1;
let totalPages = 1;
let estudiantesFiltrados = [];
let datosPaginados = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function () {
    cargarDatos();
    toggleMenorEdad(false);
    resetearListaMenores();

    // Búsqueda en tiempo real
    let timeout;
    document.getElementById('searchEstudiantes').addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => filtrarEstudiantes(this.value), 300);
    });

    const searchCert = document.getElementById('searchCertificados');
    if (searchCert) {
        searchCert.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => filtrarCertificados(this.value), 300);
        });
    }

    // Búsqueda de estudiante existente
    document.getElementById('buscarExistente').addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => buscarEstudianteExistente(this.value), 300);
    });

    // Cerrar dropdowns al hacer click fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown-filter')) {
            document.getElementById('dropdownFiltro').classList.remove('show');
        }
        if (!e.target.closest('#dropdownDescargasContainer')) {
            document.getElementById('dropdownDescargas').style.display = 'none';
        }
        if (!e.target.closest('.btn-dropdown-wrapper')) {
            document.querySelectorAll('.btn-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Cerrar modales con tecla ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(overlay => {
                overlay.classList.remove('active');
            });
        }
    });
});

// Cambiar período
function cambiarPeriodo(nuevoPeriodo) {
    periodoId = nuevoPeriodo;
    const url = new URL(window.location);
    url.searchParams.set('periodo_id', nuevoPeriodo);
    window.history.pushState({}, '', url);
    cargarDatos();
}

// Cambiar tab
function cambiarTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
}

// Cargar datos
async function cargarDatos() {
    await cargarEstudiantes();
    if (document.getElementById('listaCertificados')) {
        await cargarCertificados(); // Si existiera funcion separada
    }
    actualizarStats();
}

// Cargar estudiantes
async function cargarEstudiantes() {
    const tbody = document.getElementById('tablaEstudiantes');
    tbody.innerHTML = '<tr><td colspan="7"><div class="loading"><i class="fas fa-spinner"></i><p>Cargando...</p></div></td></tr>';

    try {
        const params = new URLSearchParams({
            action: 'listar_matriculados',
            categoria_id: categoriaId
        });
        if (periodoId) params.append('periodo_id', periodoId);

        const response = await fetch(`../api/categorias/estudiantes.php?${params}`);
        const data = await response.json();

        if (data.success) {
            estudiantes = data.estudiantes || [];
            renderEstudiantes(estudiantes);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error al cargar</h3><p>' + error.message + '</p></div></td></tr>';
    }
}

// Cambiar filas por página
function cambiarFilasPorPagina(val) {
    rowsPerPage = parseInt(val);
    irPagina(1);
}

// Navegación de página
function irPagina(pag) {
    if (pag === 'last') pag = totalPages;
    if (pag < 1) pag = 1;
    if (pag > totalPages) pag = totalPages;

    currentPage = pag;
    renderPage();
}

function prevPagina() {
    irPagina(currentPage - 1);
}

function nextPagina() {
    irPagina(currentPage + 1);
}

function irPaginaManual(val) {
    irPagina(parseInt(val));
}

// Preparar datos para paginación (agrupa representantes con sus menores)
function prepararDatosPaginacion(lista) {
    estudiantesFiltrados = lista;
    datosPaginados = [];

    if (!lista || lista.length === 0) {
        renderPage();
        return;
    }

    // Mapa de representantes para acceso rápido
    const idsEnLista = new Set(lista.map(e => parseInt(e.id)));

    // 1. Identificar menores y agruparlos con su representante
    const menoresPorRep = new Map(); // RepID -> [Menores] (o RepCedula -> [Menores])

    lista.forEach(est => {
        if (est.es_menor == 1 && est.representante_cedula) {
            const repId = est.representante_id ? parseInt(est.representante_id) : null;
            let key = null;

            if (repId && idsEnLista.has(repId)) {
                key = repId; // Representante es un estudiante mayor en la lista
            } else {
                key = 'rep_' + est.representante_cedula; // Representante virtual
            }

            if (!menoresPorRep.has(key)) {
                menoresPorRep.set(key, []);
            }
            menoresPorRep.get(key).push(est);
        }
    });

    // 2. Construir lista plana de objetos a renderizar (Filas principales)
    const procesados = new Set(); // Para evitar duplicar menores ya asignados

    // A. Estudiantes Mayores en la lista (que pueden ser representantes o alumnos normales)
    lista.forEach(est => {
        if (est.es_menor == 1) return; // Saltamos menores, se renderizan con su rep

        // Crear objeto fila para el estudiante mayor
        const filaMayor = {
            tipo: 'estudiante',
            data: est,
            menores: []
        };

        // Buscar si tiene menores a cargo en esta lista
        const repId = parseInt(est.id);
        if (menoresPorRep.has(repId)) {
            filaMayor.menores = menoresPorRep.get(repId);
            menoresPorRep.get(repId).forEach(m => procesados.add(m.id));
        }

        datosPaginados.push(filaMayor);
    });

    // B. Representantes virtuales (Menores cuyo representante no está como estudiante en la lista actual)
    // Recorremos los menores que no han sido procesados
    lista.forEach(est => {
        if (est.es_menor == 1 && !procesados.has(est.id)) {
            // Verificar si formamos ya un grupo para su representante virtual
            const key = 'rep_' + est.representante_cedula;

            // Si ya agregamos el grupo de este representante virtual, saltamos
            const yaAgregado = datosPaginados.find(f => f.tipo === 'representante_virtual' && f.cedula === est.representante_cedula);

            if (!yaAgregado && menoresPorRep.has(key)) {
                const grupoMenores = menoresPorRep.get(key);
                // Crear fila virtual de representante
                const filaVirtual = {
                    tipo: 'representante_virtual',
                    nombre: est.representante_nombre,
                    cedula: est.representante_cedula,
                    celular: est.representante_celular,
                    email: est.representante_email,
                    fecha_nacimiento: est.representante_fecha_nacimiento,
                    representante_id: est.representante_id,
                    menores: grupoMenores
                };
                datosPaginados.push(filaVirtual);
                grupoMenores.forEach(m => procesados.add(m.id));
            } else if (!yaAgregado) {
                // Caso huérfano (raro): menor sin representante claro en el grupo
                // Lo tratamos como estudiante individual
                datosPaginados.push({
                    tipo: 'estudiante',
                    data: est,
                    menores: []
                });
            }
        }
    });

    // Ordenar alfabéticamente por nombre del principal
    datosPaginados.sort((a, b) => {
        const nombreA = a.tipo === 'estudiante' ? a.data.nombre : a.nombre;
        const nombreB = b.tipo === 'estudiante' ? b.data.nombre : b.nombre;
        return nombreA.localeCompare(nombreB);
    });

    renderPage();
}

// Renderizar página actual
function renderPage() {
    const tbody = document.getElementById('tablaEstudiantes');
    const totalItems = datosPaginados.length;

    // Actualizar controles
    document.getElementById('paginationFooter').style.display = totalItems > 0 ? 'flex' : 'none';

    if (rowsPerPage === -1) {
        totalPages = 1;
        currentPage = 1;
    } else {
        totalPages = Math.ceil(totalItems / rowsPerPage);
    }

    if (currentPage > totalPages) currentPage = totalPages || 1;

    document.getElementById('pagTotal').textContent = totalItems;
    document.getElementById('totalPages').textContent = totalPages;
    document.getElementById('pageInput').value = currentPage;
    document.getElementById('pageInput').max = totalPages;

    document.getElementById('btnPagePrev').disabled = currentPage === 1;
    document.getElementById('btnPageFirst').disabled = currentPage === 1;
    document.getElementById('btnPageNext').disabled = currentPage === totalPages;
    document.getElementById('btnPageLast').disabled = currentPage === totalPages;

    // Calcular slice
    let start = 0;
    let end = totalItems;

    if (rowsPerPage !== -1) {
        start = (currentPage - 1) * rowsPerPage;
        end = Math.min(start + rowsPerPage, totalItems);
    }

    document.getElementById('pagStart').textContent = totalItems > 0 ? start + 1 : 0;
    document.getElementById('pagEnd').textContent = end;

    tbody.innerHTML = '';

    if (totalItems === 0) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><h3>No hay estudiantes</h3><p>No se encontraron estudiantes con los filtros actuales</p></div></td></tr>';
        return;
    }

    const itemsToShow = datosPaginados.slice(start, end);

    itemsToShow.forEach(fila => {
        if (fila.tipo === 'estudiante') {
            const hasMenores = fila.menores && fila.menores.length > 0;
            const repUniqueId = 'est-' + fila.data.id;

            renderFilaEstudiante(tbody, fila.data, hasMenores ? 'estudiante-representante' : '', hasMenores, repUniqueId);

            if (hasMenores) {
                fila.menores.forEach(menor => {
                    renderFilaMenor(tbody, menor, repUniqueId);
                });
            }
        } else if (fila.tipo === 'representante_virtual') {
            const repUniqueId = 'virt-' + fila.cedula;
            renderFilaRepresentanteVirtual(tbody, fila, repUniqueId);
            fila.menores.forEach(menor => {
                renderFilaMenor(tbody, menor, repUniqueId);
            });
        }
    });
}


function toggleMenores(repId) {
    const rows = document.querySelectorAll(`.menor-de-${repId}`);
    const toggleEl = document.getElementById(`toggle-${repId}`);
    let isHidden = false;

    rows.forEach(row => {
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            isHidden = false;
        } else {
            row.style.display = 'none';
            isHidden = true;
        }
    });

    if (toggleEl) {
        // Rotar icono
        if (isHidden) {
            toggleEl.classList.remove('fa-chevron-down');
            toggleEl.classList.add('fa-chevron-right');
        } else {
            toggleEl.classList.remove('fa-chevron-right');
            toggleEl.classList.add('fa-chevron-down');
        }
    }
}

function renderFilaEstudiante(tbody, est, rowClass = '', hasMenores = false, repUniqueId = '') {
    const tr = document.createElement('tr');
    if (rowClass) tr.className = rowClass;

    const checked = seleccionados.includes(est.id);
    const hasCert = est.certificado_codigo ? true : false;

    let certStatus = '<span class="cert-status cert-none">Sin certificado</span>';
    let tooltip = '';

    if (hasCert) {
        certStatus = `<span class="cert-status cert-generated"><i class="fas fa-check-circle"></i> Generado</span>`;
        if (est.certificado_creado_at) {
            const date = new Date(est.certificado_creado_at);
            tooltip = `Generado el ${date.toLocaleDateString()} a las ${date.toLocaleTimeString()}`;
        }
    }

    const tooltipAttr = tooltip ? `class="cert-tooltip-trigger" data-tooltip="${tooltip}"` : '';

    // Acciones simplificadas
    let accionesHtml = `
        <div class="action-buttons">
            <button class="btn-icon btn-secondary" onclick="abrirModalEditar(${est.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon btn-danger" onclick="confirmarQuitar('${est.id}', '${est.nombre.replace(/'/g, "\\'")}', ${est.es_menor})" title="Quitar">
                <i class="fas fa-user-times"></i>
            </button>
            ${hasCert ? `
            <button class="btn-icon btn-view" title="Ver Certificado" onclick="previsualizarCertificado('${est.certificado_codigo}')">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-icon btn-download" title="Descargar PDF" onclick="descargarCertificado('${est.certificado_codigo}', 'pdf')">
                <i class="fas fa-file-pdf"></i>
            </button>
            <button class="btn-icon btn-secondary" title="Descargar Imagen" onclick="descargarCertificado('${est.certificado_codigo}', 'imagen')">
                <i class="fas fa-image"></i>
            </button>
            ` : `
            <button class="btn-icon btn-certificate" title="Generar Certificado" onclick="abrirModalGenerar([${est.id}])">
                <i class="fas fa-plus"></i>
            </button>
            `}
        </div>
    `;

    tr.innerHTML = `
        <td class="checkbox-cell">
            <input type="checkbox" class="select-checkbox" value="${est.id}" 
                   onchange="toggleSeleccion('${est.id}')" ${checked ? 'checked' : ''}>
        </td>
        <td class="nombre-cell">
            ${hasMenores ? `
                <i id="toggle-${repUniqueId}" class="fas fa-chevron-right" onclick="toggleMenores('${repUniqueId}')" style="cursor: pointer; margin-right: 8px; color: #3498db; width: 15px; text-align: center; display: inline-block;"></i>
            ` : '<span style="display:inline-block; width: 23px;"></span>'}
            ${est.nombre}
            ${parseInt(est.tiene_referencias) > 0 ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${est.id})" title="Ver Referencias"></i>` : ''}
            ${est.es_destacado == 1 ? '<i class="fas fa-star" style="color: #f1c40f; margin-left: 5px;" title="Estudiante destacado"></i>' : ''}
        </td>
        <td>${est.cedula ? '<span class="cedula-cell">' + est.cedula + '</span>' : '<span style="color:#ccc">--</span>'}</td>
        <td class="fecha-cell">
            ${est.fecha_nacimiento ?
            `<div>${est.fecha_nacimiento}</div>
                 <div class="badge badge-edad">${calcularEdad(est.fecha_nacimiento)} años</div>`
            : '<span style="color:#ccc">--</span>'}
        </td>
        <td>
            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start; max-width: 100%;">
                ${est.celular ? `<a href="${generarLinkWhatsApp(est.celular, est.nombre)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fab fa-whatsapp"></i> 0${est.celular}</a>` : ''}
                ${est.email ? `<a href="mailto:${est.email}" class="contact-chip contact-chip-email" style="max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${est.email}"><i class="fas fa-envelope"></i> ${est.email}</a>` : ''}
            </div>
        </td>
        <td ${tooltipAttr}>${certStatus} ${tooltip ? '<i class="fas fa-info-circle cert-info-icon"></i>' : ''}</td>
        <td>${accionesHtml}</td>
    `;

    tbody.appendChild(tr);
}

function renderFilaMenor(tbody, menor, repUniqueId = '') {
    const tr = document.createElement('tr');
    tr.className = `estudiante-menor ${repUniqueId ? 'menor-de-' + repUniqueId : ''}`;
    // Por defecto ocultos si pertenecen a un grupo
    if (repUniqueId) {
        tr.style.display = 'none';
        tr.style.backgroundColor = '#f8f9fa'; // Fondo ligeramente diferente para diferenciar
    }

    const checked = seleccionados.includes(menor.id);
    const hasCert = menor.certificado_codigo ? true : false;

    let certStatus = '<span class="cert-status cert-none">Sin certificado</span>';
    let tooltip = '';

    if (hasCert) {
        certStatus = `<span class="cert-status cert-generated"><i class="fas fa-check-circle"></i> Generado</span>`;
        if (menor.certificado_creado_at) {
            const date = new Date(menor.certificado_creado_at);
            tooltip = `Generado el ${date.toLocaleDateString()} a las ${date.toLocaleTimeString()}`;
        }
    }

    const tooltipAttr = tooltip ? `class="cert-tooltip-trigger" data-tooltip="${tooltip}"` : '';

    // Acciones simplificadas
    let accionesHtml = `
        <div class="action-buttons">
            <button class="btn-icon btn-secondary" onclick="abrirModalEditar(${menor.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon btn-danger" onclick="confirmarQuitar('${menor.id}', '${menor.nombre.replace(/'/g, "\\'")}', 1)" title="Quitar">
                <i class="fas fa-user-times"></i>
            </button>
            ${hasCert ? `
            <button class="btn-icon btn-view" title="Ver Certificado" onclick="previsualizarCertificado('${menor.certificado_codigo}')">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-icon btn-download" title="Descargar PDF" onclick="descargarCertificado('${menor.certificado_codigo}', 'pdf')">
                <i class="fas fa-file-pdf"></i>
            </button>
            <button class="btn-icon btn-secondary" title="Descargar Imagen" onclick="descargarCertificado('${menor.certificado_codigo}', 'imagen')">
                <i class="fas fa-image"></i>
            </button>
            ` : `
            <button class="btn-icon btn-certificate" title="Generar Certificado" onclick="abrirModalGenerar([${menor.id}])">
                <i class="fas fa-plus"></i>
            </button>
            `}
        </div>
    `;

    tr.innerHTML = `
        <td class="checkbox-cell">
            <input type="checkbox" class="select-checkbox" value="${menor.id}" 
                   onchange="toggleSeleccion('${menor.id}')" ${checked ? 'checked' : ''}>
        </td>
        <td class="nombre-cell" style="padding-left: 30px;">
            <i class="fas fa-level-up-alt fa-rotate-90" style="color: #bdc3c7; margin-right: 8px;"></i>
            ${menor.nombre}
            ${parseInt(menor.tiene_referencias) > 0 ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${menor.id})" title="Ver Referencias"></i>` : ''}
            <span class="badge badge-info shadow-none" style="margin-left: 5px; font-size: 10px;">Menor</span>
            ${menor.es_destacado == 1 ? '<i class="fas fa-star" style="color: #f1c40f; margin-left: 5px;" title="Estudiante destacado"></i>' : ''}
        </td>
        <td>${menor.cedula ? '<span class="cedula-cell">' + menor.cedula + '</span>' : '<span style="color:#ccc">--</span>'}</td>
        <td class="fecha-cell">
            ${menor.fecha_nacimiento ?
            `<div>${menor.fecha_nacimiento}</div>
                 <div class="badge badge-edad">${calcularEdad(menor.fecha_nacimiento)} años</div>`
            : '<span style="color:#ccc">--</span>'}
        </td>
        <td>
            <!-- Contacto desde representante -->
            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start; max-width: 100%;">
                ${menor.representante_celular ? `<a href="${generarLinkWhatsApp(menor.representante_celular, menor.nombre)}" target="_blank" class="contact-chip contact-chip-whatsapp" title="Representante: ${menor.representante_celular}" style="max-width: 100%; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-user-tie"></i> 0${menor.representante_celular}</a>` : ''}
                ${menor.representante_email ? `<a href="mailto:${menor.representante_email}" class="contact-chip contact-chip-email" title="Representante: ${menor.representante_email}" style="max-width: 100%; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-user-tie"></i> ${menor.representante_email}</a>` : ''}
                 ${!menor.representante_celular && !menor.representante_email ? '<span style="color: #95a5a6; font-size: 12px; font-style: italic;">(Sin contacto)</span>' : ''}
            </div>
        </td>
        <td ${tooltipAttr}>${certStatus} ${tooltip ? '<i class="fas fa-info-circle cert-info-icon"></i>' : ''}</td>
        <td>${accionesHtml}</td>
    `;

    tbody.appendChild(tr);
}

function renderFilaRepresentanteVirtual(tbody, fila, repUniqueId = '') {
    const tr = document.createElement('tr');
    tr.className = 'representante-virtual';

    // Acciones: botón de editar si tiene ID
    let accionesHtml = '';
    if (fila.representante_id) {
        accionesHtml = `
            <div class="action-buttons">
                <button class="btn-icon btn-secondary" onclick="abrirModalEditar(${fila.representante_id})" title="Editar Representante">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;
    }

    tr.innerHTML = `
        <td></td>
        <td class="nombre-cell" style="color: #7f8c8d !important;">
            <i id="toggle-${repUniqueId}" class="fas fa-chevron-right" onclick="toggleMenores('${repUniqueId}')" style="cursor: pointer; margin-right: 8px; color: #3498db; width: 15px; text-align: center; display: inline-block;"></i>
            ${fila.nombre} 
            <span class="badge badge-secondary" style="margin-left: 5px;">Representante</span>
        </td>
        <td>${fila.cedula ? '<span class="cedula-cell">' + fila.cedula + '</span>' : ''}</td>
        <td class="fecha-cell">
            ${fila.fecha_nacimiento ?
            `<div>${fila.fecha_nacimiento}</div>
                 <div class="badge badge-edad">${calcularEdad(fila.fecha_nacimiento)} a\u00f1os</div>`
            : '<span style="color:#ccc">--</span>'}
        </td>
        <td>
            ${fila.celular ? `<a href="${generarLinkWhatsApp(fila.celular, fila.nombre)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="opacity: 0.8"><i class="fab fa-whatsapp"></i> 0${fila.celular}</a>` : ''}
            ${fila.email ? `<a href="mailto:${fila.email}" class="contact-chip contact-chip-email" style="opacity: 0.8"><i class="fas fa-envelope"></i> ${fila.email}</a>` : ''}
        </td>
        <td><small style="color: #95a5a6;">Representante de ${fila.menores.length} estudiantes</small></td>
        <td>${accionesHtml}</td>
    `;

    tbody.appendChild(tr);
}

function toggleAccionesFila(event, id) {
    // Cerrar otros menús primero
    document.querySelectorAll('.btn-dropdown-menu.show').forEach(m => {
        if (m.id !== `acciones-${id}`) m.classList.remove('show');
    });

    const menu = document.getElementById(`acciones-${id}`);
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Actualizar stats
function actualizarStats() {
    document.getElementById('totalEstudiantes').textContent = estudiantes.length;

    const totalMenoresVal = estudiantes.filter(e => e.es_menor == 1).length;
    document.getElementById('totalMenores').textContent = totalMenoresVal;

    const generados = estudiantes.filter(e => e.certificado_codigo).length;
    document.getElementById('totalCertificados').textContent = generados;

    document.getElementById('totalPendientes').textContent = estudiantes.length - generados;
}

// Filtrar Estudiantes (Búsqueda local)
function filtrarEstudiantes(busqueda) {
    if (!busqueda) {
        // Restaurar filtro de categoría actual (todos, rep, mayores)
        aplicarFiltro(filtroActual);
        return;
    }

    busqueda = busqueda.toLowerCase();

    // Filtrar sobre la lista completa de estudiantes
    let filtrados = estudiantes.filter(e =>
        e.nombre.toLowerCase().includes(busqueda) ||
        (e.cedula && e.cedula.includes(busqueda)) ||
        (e.representante_nombre && e.representante_nombre.toLowerCase().includes(busqueda))
    );

    // Aplicar lógica de paginación sobre los resultados de búsqueda
    prepararDatosPaginacion(filtrados);
    irPagina(1);

    // Actualizar texto filtro visualmente para indicar que hay búsqueda
    const filtroTexto = document.getElementById('filtroTexto');
    if (filtroTexto.textContent !== 'Resultados búsqueda') {
        filtroTexto.dataset.original = filtroTexto.textContent;
        filtroTexto.textContent = 'Resultados búsqueda';
    }
}

// Aplicar filtros de categoría
function renderEstudiantes(lista) {
    aplicarFiltro(filtroActual);
}

function toggleDropdownFiltro() {
    document.getElementById('dropdownFiltro').classList.toggle('show');
}

function aplicarFiltro(tipo) {
    filtroActual = tipo;

    // Actualizar UI
    document.querySelectorAll('.dropdown-filter-menu a').forEach(a => a.classList.remove('active'));

    let texto = 'Todos';
    let listaFiltrada = [...estudiantes];

    if (tipo === 'todos') {
        document.querySelector('.dropdown-filter-menu a:nth-child(1)').classList.add('active');
        texto = 'Todos';
    } else if (tipo === 'representante') {
        document.querySelector('.dropdown-filter-menu a:nth-child(2)').classList.add('active');
        texto = 'Con Representante';

        // Lógica mejorada: Incluir también a los representantes (adultos) de los menores listados
        // Paso 1: Obtener IDs de representantes de los menores
        const repIds = new Set();
        estudiantes.forEach(e => {
            if (e.es_menor == 1 && e.representante_id) {
                repIds.add(parseInt(e.representante_id));
            }
        });

        listaFiltrada = estudiantes.filter(e =>
            e.es_menor == 1 ||
            e.representante_id ||
            repIds.has(parseInt(e.id))
        );
    } else if (tipo === 'mayores') {
        document.querySelector('.dropdown-filter-menu a:nth-child(3)').classList.add('active');
        texto = 'Mayores de Edad';
        listaFiltrada = estudiantes.filter(e => e.es_menor == 0);
    } else if (tipo === 'destacados') {
        document.querySelector('.dropdown-filter-menu a:nth-child(4)').classList.add('active');
        texto = 'Destacados';
        listaFiltrada = estudiantes.filter(e => e.es_destacado == 1);
    }

    document.getElementById('filtroTexto').textContent = texto;
    document.getElementById('dropdownFiltro').classList.remove('show');

    // Preparar y paginar
    prepararDatosPaginacion(listaFiltrada);
    irPagina(1);

    // Limpiar búsqueda si existe
    if (document.getElementById('searchEstudiantes').value) {
        document.getElementById('searchEstudiantes').value = '';
    }
}

// Selección
function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
    seleccionados = [];

    if (isChecked) {
        // Seleccionar TODOS los de la PÁGINA ACTUAL
        // O mejor: seleccionar todos los visibles (paginados)
        // La implementación estándar suele ser seleccionar todos los filtrados
        // Pero para UI, a veces solo la página. Vamos a seleccionar TODOS los filtrados.
        estudiantesFiltrados.forEach(e => {
            seleccionados.push(e.id);
        });
    }

    actualizarContadorSeleccionados();
    renderPage(); // Re-render para actualizar checkboxes
}

function toggleSeleccion(id) {
    id = String(id); // asegurar string
    const index = seleccionados.indexOf(id);

    if (index === -1) {
        seleccionados.push(id);
    } else {
        seleccionados.splice(index, 1);
    }

    actualizarContadorSeleccionados();

    // Actualizar estado checkbox selectAll
    const totalVisibles = estudiantesFiltrados.length;
    document.getElementById('selectAll').checked = totalVisibles > 0 && seleccionados.length === totalVisibles;
}

function actualizarContadorSeleccionados() {
    const count = seleccionados.length;
    document.getElementById('selectedCount').textContent = count;

    const bulkActions = document.getElementById('bulkActions');

    if (count > 0) {
        bulkActions.classList.add('active');

        // Verificar estados para mostrar botones
        // 1. Si alguno NO tiene certificado -> Mostrar Generar
        // 2. Si alguno TIENE certificado -> Mostrar Regenerar y Descargar

        let haySinCert = false;
        let hayConCert = false;

        // Buscar en la lista de estudiantes los seleccionados
        seleccionados.forEach(id => {
            const est = estudiantes.find(e => e.id == id);
            if (est) {
                if (est.certificado_codigo) hayConCert = true;
                else haySinCert = true;
            }
        });

        document.getElementById('btnGenerarSeleccionados').style.display = haySinCert ? 'inline-flex' : 'none';
        document.getElementById('btnRegenerarSeleccionados').style.display = hayConCert ? 'inline-flex' : 'none';
        document.getElementById('btnDescargarPdfSeleccionados').style.display = hayConCert ? 'inline-flex' : 'none';
        document.getElementById('btnDescargarImgSeleccionados').style.display = hayConCert ? 'inline-flex' : 'none';
        document.getElementById('btnDestacadosSeleccionados').style.display = 'inline-flex';
        document.getElementById('btnQuitarDestacadosSeleccionados').style.display = 'inline-flex';
        document.getElementById('btnQuitarSeleccionados').style.display = 'inline-flex';

    } else {
        bulkActions.classList.remove('active');
    }
}

function cancelarSeleccion() {
    seleccionados = [];
    document.getElementById('selectAll').checked = false;
    actualizarContadorSeleccionados();
    renderPage();
}

async function establecerDestacadosSeleccionados() {
    if (seleccionados.length === 0) return;

    const formData = new FormData();
    formData.append('action', 'establecer_destacados');
    formData.append('ids', JSON.stringify(seleccionados));

    try {
        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'Estudiantes marcados como destacados', 'success');
            // Actualizar datos locales
            seleccionados.forEach(id => {
                const est = estudiantes.find(e => e.id == id);
                if (est) est.es_destacado = 1;
            });
            cancelarSeleccion();
            renderPage();
        } else {
            showNotification(data.message || 'Error al establecer destacados', 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error de conexión', 'error');
    }
}

async function quitarDestacadosSeleccionados() {
    if (seleccionados.length === 0) return;

    const formData = new FormData();
    formData.append('action', 'quitar_destacados');
    formData.append('ids', JSON.stringify(seleccionados));

    try {
        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'Se quitó el estado destacado', 'success');
            seleccionados.forEach(id => {
                const est = estudiantes.find(e => e.id == id);
                if (est) est.es_destacado = 0;
            });
            cancelarSeleccion();
            renderPage();
        } else {
            showNotification(data.message || 'Error al quitar destacados', 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error de conexión', 'error');
    }
}

// --- MODALES ---

function abrirModalAgregar() {
    document.getElementById('formNuevoEstudiante').reset();
    resetearListaMenores();
    toggleMenorEdad(false); // Resetear estado

    // Limpiar clases de validación (bordes verdes/rojos)
    const modal = document.getElementById('modalAgregar');
    modal.querySelectorAll('.input-valid, .input-error').forEach(el => {
        el.classList.remove('input-valid', 'input-error');
    });

    // Limpiar textos de error
    modal.querySelectorAll('.field-error').forEach(el => {
        el.textContent = '';
    });

    // Limpiar referencias (mayor y representante)
    const refMayorList = document.getElementById('referencias_mayor_list');
    if (refMayorList) refMayorList.innerHTML = '';
    const refRepList = document.getElementById('referencias_representante_list');
    if (refRepList) refRepList.innerHTML = '';

    // Mostrar botones de agregar referencia
    modal.querySelectorAll('.btn-add-referencia').forEach(btn => {
        btn.style.display = '';
    });

    // Limpiar búsqueda existente
    const buscar = document.getElementById('buscarExistente');
    if (buscar) buscar.value = '';
    const resultados = document.getElementById('resultadosBusqueda');
    if (resultados) resultados.innerHTML = '';

    modal.classList.add('active');
}

function cerrarModal(event, id) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById(id).classList.remove('active');
}

// Toggle Menor de edad
function toggleMenorEdad(forceState = null) {
    const checkbox = document.getElementById('es_menor');
    const isMenor = forceState !== null ? forceState : checkbox.checked;

    if (forceState !== null) checkbox.checked = isMenor;

    const seccionMayor = document.getElementById('datosEstudianteMayor');
    const seccionRepresentante = document.getElementById('datosRepresentante');

    // Inputs requeridos dinámicamente
    const inputsMayor = ['nuevo_nombre', 'nuevo_cedula', 'nuevo_celular', 'nuevo_email'];
    const inputsRep = ['representante_nombre', 'representante_cedula', 'representante_celular'];

    if (isMenor) {
        seccionMayor.style.display = 'none';
        seccionRepresentante.style.display = 'block';

        inputsMayor.forEach(id => document.getElementById(id).required = false);
        inputsRep.forEach(id => document.getElementById(id).required = true);

        // Reset mayor inputs
        inputsMayor.forEach(id => document.getElementById(id).value = '');
    } else {
        seccionMayor.style.display = 'block';
        seccionRepresentante.style.display = 'none';

        inputsMayor.forEach(id => document.getElementById(id).required = true);
        inputsRep.forEach(id => document.getElementById(id).required = false);
    }
}

// Gestión lista menores
function resetearListaMenores() {
    const container = document.getElementById('listaMenores');
    const template = container.querySelector('.menor-item').cloneNode(true);

    // Limpiar valores del template
    template.querySelectorAll('input').forEach(input => input.value = '');

    container.innerHTML = '';
    container.appendChild(template);
    contadorMenores = 1;
    actualizarIndicesMenores();
}

function agregarMenor() {
    const container = document.getElementById('listaMenores');
    const items = container.querySelectorAll('.menor-item');

    if (items.length >= 10) {
        showNotification('Máximo 10 menores permitidos por representante', 'warning');
        return;
    }

    const template = container.querySelector('.menor-item').cloneNode(true);

    // Limpiar valores y clases de validación
    template.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.classList.remove('input-valid', 'input-error');
    });
    template.querySelectorAll('.field-error').forEach(el => el.textContent = '');

    // Agregar botón eliminar si no es el primero
    if (!template.querySelector('.btn-remove-menor')) {
        const header = template.querySelector('.menor-header');
        const btnRemove = document.createElement('button');
        btnRemove.type = 'button';
        btnRemove.className = 'btn-remove-menor';
        btnRemove.innerHTML = '<i class="fas fa-trash"></i> Quitar';
        btnRemove.onclick = function () { eliminarMenor(this); };
        header.appendChild(btnRemove);
    }

    container.appendChild(template);
    actualizarIndicesMenores();

    // Ocultar botón si se llegó al máximo (10)
    if (container.querySelectorAll('.menor-item').length >= 10) {
        document.querySelector('.btn-add-menor').style.display = 'none';
        showNotification('Se alcanzó el máximo de 10 menores', 'info');
    }
}

function eliminarMenor(btn) {
    btn.closest('.menor-item').remove();
    actualizarIndicesMenores();

    // Mostrar botón si hay menos de 10
    const container = document.getElementById('listaMenores');
    if (container.querySelectorAll('.menor-item').length < 10) {
        const btnAdd = document.querySelector('.btn-add-menor');
        if (btnAdd) btnAdd.style.display = '';
    }
}

function actualizarIndicesMenores() {
    const items = document.querySelectorAll('.menor-item');
    items.forEach((item, index) => {
        item.dataset.index = index;
        item.querySelector('.menor-numero').innerHTML = `<i class="fas fa-child"></i> Menor #${index + 1}`;

        // Actualizar nombres de inputs para array
        item.querySelectorAll('input, select').forEach(input => {
            const name = input.name;
            if (name) {
                const newName = name.replace(/menores\[\d+\]/, `menores[${index}]`);
                input.name = newName;
            }
        });
    });
}

// Validaciones
function validarCedulaInput(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length > 10) input.value = input.value.slice(0, 10);
}

function validarCelularInput(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length > 9) input.value = input.value.slice(0, 9);
}

async function verificarCedula(input) {
    const valor = input.value;
    const errorSpan = document.getElementById('cedula_error');

    if (valor.length > 0 && valor.length < 10) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'La cédula debe tener 10 dígitos';
    } else if (valor.length === 10) {
        // Validar algoritmo
        if (!validarCedulaEcuatoriana(valor)) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
            if (errorSpan) errorSpan.textContent = 'Cédula inválida';
            return;
        }

        // Verificar duplicado en backend
        try {
            const response = await fetch(`../api/categorias/estudiantes.php?action=verificar_cedula&cedula=${valor}`);
            const data = await response.json();
            if (data.existe && !data.es_solo_representante) {
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorSpan) errorSpan.textContent = `Cédula ya registrada: ${data.estudiante.nombre}`;
            } else {
                input.classList.remove('input-error');
                input.classList.add('input-valid');
                if (errorSpan) errorSpan.textContent = '';
            }
        } catch (e) {
            console.error('Error verificando cédula:', e);
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (errorSpan) errorSpan.textContent = '';
        }
    } else {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
    }
}

function verificarCelular(input) {
    const valor = input.value;
    const errorSpan = document.getElementById('celular_error');

    if (valor.length > 0 && valor.length < 9) {
        input.parentNode.classList.add('input-error');
        errorSpan.textContent = 'El celular debe tener 9 dígitos';
    } else if (valor.length === 9) {
        input.parentNode.classList.remove('input-error');
        input.parentNode.classList.add('input-valid');
        errorSpan.textContent = '';
    } else {
        input.parentNode.classList.remove('input-error', 'input-valid');
        errorSpan.textContent = '';
    }
}

// Validaciones Representante
async function verificarCedulaRepresentante(input) {
    const errorSpan = document.getElementById('representante_cedula_error');
    const valor = input.value;

    if (valor.length > 0 && valor.length < 10) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'Cédula incompleta';
    } else if (valor.length === 10) {
        // Validar algoritmo
        if (!validarCedulaEcuatoriana(valor)) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
            if (errorSpan) errorSpan.textContent = 'Cédula inválida';
            return;
        }

        // Verificar duplicado en backend
        try {
            const response = await fetch(`../api/categorias/estudiantes.php?action=verificar_cedula&cedula=${valor}&para_representante=1`);
            const data = await response.json();

            if (data.existe) {
                // Si ya existe (sea estudiante o solo representante), advertir
                input.classList.add('input-error');
                input.classList.remove('input-valid');
                if (errorSpan) errorSpan.textContent = `Representante ya registrado: ${data.estudiante.nombre}`;
            } else {
                input.classList.remove('input-error');
                input.classList.add('input-valid');
                if (errorSpan) errorSpan.textContent = '';
            }
        } catch (e) {
            console.error('Error verificando cédula rep:', e);
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (errorSpan) errorSpan.textContent = '';
        }
    } else {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
    }
}

async function verificarEmail(input) {
    const valor = input.value;
    const errorSpan = document.getElementById('email_error');
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (valor.length > 0) {
        if (!regex.test(valor)) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
            if (errorSpan) errorSpan.textContent = 'Email inválido';
        } else {
            // Verificar en backend
            try {
                // Si estamos editando, excluir el propio ID (no implementado en el UI de agregar, pero útil para futuro)
                // Para el formulario de agregar, no hay ID que excluir.
                const response = await fetch(`../api/categorias/estudiantes.php?action=verificar_email&email=${encodeURIComponent(valor)}`);
                const data = await response.json();

                if (data.existe) {
                    input.classList.add('input-error');
                    input.classList.remove('input-valid');
                    if (errorSpan) errorSpan.textContent = `Email ya registrado: ${data.estudiante.nombre}`;
                } else {
                    input.classList.remove('input-error');
                    input.classList.add('input-valid');
                    if (errorSpan) errorSpan.textContent = '';
                }
            } catch (error) {
                console.error('Error verificando email:', error);
            }
        }
    } else {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
    }
}

function verificarEmailRepresentante(input) {
    const valor = input.value;
    const errorSpan = document.getElementById('representante_email_error');
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (valor.length > 0 && !regex.test(valor)) {
        input.classList.add('input-error');
        if (errorSpan) errorSpan.textContent = 'Email inválido';
    } else {
        input.classList.remove('input-error');
        if (errorSpan) errorSpan.textContent = '';
    }
}

function verificarEmailMenor(input) {
    const valor = input.value;
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (valor.length > 0 && !regex.test(valor)) {
        input.classList.add('input-error');
    } else {
        input.classList.remove('input-error');
    }
}

function verificarCelularRepresentante(input) {
    const valor = input.value;
    const errorSpan = document.getElementById('representante_celular_error');

    if (valor.length > 0 && valor.length < 9) {
        input.classList.add('input-error');
        if (errorSpan) errorSpan.textContent = 'Celular incompleto';
    } else {
        input.classList.remove('input-error');
        if (errorSpan) errorSpan.textContent = '';
    }
}


function validarCedulaEcuatoriana(cedula) {
    if (cedula.length !== 10) return false;

    const digitoRegion = parseInt(cedula.substring(0, 2));
    if (digitoRegion < 1 || digitoRegion > 24) return false;

    const tercerDigito = parseInt(cedula.substring(2, 3));
    if (tercerDigito >= 6) return false;

    const coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    const verificador = parseInt(cedula.substring(9, 10));
    let suma = 0;

    for (let i = 0; i < 9; i++) {
        let valor = parseInt(cedula.substring(i, i + 1)) * coeficientes[i];
        if (valor >= 10) valor -= 9;
        suma += valor;
    }

    const total = Math.ceil(suma / 10) * 10;
    let digitoCalculado = total - suma;
    if (digitoCalculado === 10) digitoCalculado = 0;

    return digitoCalculado === verificador;
}


function verificarFechaNacimientoMayor(input) {
    const valor = input.value;
    if (!valor) return;

    const hoy = new Date();
    const cumple = new Date(valor);
    let edad = hoy.getFullYear() - cumple.getFullYear();
    const m = hoy.getMonth() - cumple.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
        edad--;
    }

    if (edad < 18) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        showNotification('Debe ser mayor de edad (18+)', 'warning');
    } else {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
    }
}

function verificarEdadRepresentante(input) {
    const valor = input.value;
    if (!valor) return;

    const hoy = new Date();
    const cumple = new Date(valor);
    let edad = hoy.getFullYear() - cumple.getFullYear();
    const m = hoy.getMonth() - cumple.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
        edad--;
    }

    if (edad < 18) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        showNotification('El representante debe ser mayor de edad (18+)', 'warning');
    } else {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
    }
}

function agregarReferencia(tipo, data = null) {
    let containerId, prefix;

    if (tipo === 'editar') {
        containerId = 'referencias_editar_list';
        prefix = 'referencias_editar';
    } else if (tipo === 'mayor') {
        containerId = 'referencias_mayor_list';
        prefix = 'referencias_mayor';
    } else {
        containerId = 'referencias_representante_list';
        prefix = 'referencias_representante';
    }

    const contenedor = document.getElementById(containerId);
    if (!contenedor) return;

    const items = contenedor.querySelectorAll('.referencia-item');
    if (items.length >= 3) {
        showNotification('Máximo 3 referencias permitidas', 'warning');
        return;
    }

    const index = items.length;
    const item = document.createElement('div');
    item.className = 'referencia-item';

    const nombreVal = data ? data.nombre : '';
    const telefonoVal = data ? data.telefono : '';
    const relacionVal = data ? data.relacion : '';

    item.innerHTML = `
        <input type="text" name="${prefix}[${index}][nombre]" class="form-control" placeholder="Nombre completo" value="${nombreVal}" required>
        <div class="celular-input-wrapper ref-celular-wrapper">
            <span class="celular-prefix">+593</span>
            <input type="text" name="${prefix}[${index}][telefono]" placeholder="9XXXXXXXX" maxlength="9"
                   value="${telefonoVal}" oninput="validarCelularInput(this)" required>
        </div>
        <input type="text" name="${prefix}[${index}][relacion]" class="form-control" placeholder="Relación *" value="${relacionVal}" required>
        <button type="button" class="btn-ref-delete" onclick="eliminarReferencia(this)"><i class="fas fa-trash-alt"></i></button>
    `;
    contenedor.appendChild(item);

    // Ocultar botón si se llegó al máximo (3)
    if (contenedor.querySelectorAll('.referencia-item').length >= 3) {
        const btnAdd = contenedor.parentElement.querySelector('.btn-add-referencia');
        if (btnAdd) btnAdd.style.display = 'none';
    }
}

function eliminarReferencia(btn) {
    const item = btn.closest('.referencia-item');
    const contenedor = item.parentElement;
    item.remove();

    // Mostrar botón de agregar si hay menos de 3
    if (contenedor.querySelectorAll('.referencia-item').length < 3) {
        const btnAdd = contenedor.parentElement.querySelector('.btn-add-referencia');
        if (btnAdd) btnAdd.style.display = '';
    }
}

async function verReferencias(estudianteId) {
    const body = document.getElementById('modalReferenciasBody');
    const title = document.getElementById('modalReferenciasTitle');
    body.innerHTML = '<div style="text-align:center; padding:20px; color:#7f8c8d;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p>Cargando referencias...</p></div>';
    document.getElementById('modalReferencias').classList.add('active');

    try {
        const resp = await fetch(`../api/categorias/estudiantes.php?action=listar_referencias&estudiante_id=${estudianteId}`);
        const data = await resp.json();

        title.innerHTML = `<i class="fas fa-address-book"></i> Referencias de ${data.estudiante_nombre || 'Estudiante'}`;

        if (!data.success || !data.referencias || data.referencias.length === 0) {
            body.innerHTML = `
                <div style="text-align:center; padding:30px; color:#95a5a6;">
                    <i class="fas fa-user-slash" style="font-size:48px; margin-bottom:15px; opacity:0.3;"></i>
                    <p style="font-size:15px; margin:0;">No se han registrado referencias para este estudiante.</p>
                </div>`;
            return;
        }

        let html = '<div class="ref-modal-list">';
        data.referencias.forEach((ref, i) => {
            const celLink = ref.telefono ? `<a href="${generarLinkWhatsAppReferencia(ref.telefono, ref.nombre, ref.relacion, data.estudiante_nombre || 'Estudiante')}" target="_blank" class="contact-chip contact-chip-whatsapp"><i class="fab fa-whatsapp"></i> 0${ref.telefono}</a>` : '<span style="color:#bdc3c7; font-size:12px;">Sin teléfono</span>';
            html += `
                <div class="ref-modal-card">
                    <div class="ref-modal-number">${i + 1}</div>
                    <div class="ref-modal-info">
                        <div class="ref-modal-name">${ref.nombre}</div>
                        <div class="ref-modal-details">
                            ${celLink}
                            ${ref.relacion ? `<span class="badge badge-secondary">${ref.relacion}</span>` : ''}
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        body.innerHTML = html;
    } catch (e) {
        body.innerHTML = '<div style="text-align:center; padding:20px; color:#e74c3c;"><i class="fas fa-exclamation-triangle" style="font-size:24px;"></i><p>Error al cargar las referencias</p></div>';
    }
}

function generarLinkWhatsApp(celular, nombreEstudiante) {
    if (!celular) return '#';
    const numero = '593' + celular.replace(/^0+/, ''); // 593XXXXX
    const mensaje = `Hola ${nombreEstudiante} del ${grupoNombre} de ${categoriaNombre} de la Casa de la Cultura de Tungurahua 🎭`;
    return `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`;
}

function generarLinkWhatsAppReferencia(celular, nombreRef, relacion, nombreEstudiante) {
    if (!celular) return '#';
    const numero = '593' + celular.replace(/^0+/, '');
    const mensaje = `Hola 👋 le saluda Casa de la Cultura - Núcleo Tungurahua 🎭. Usted es ${nombreRef} y ${relacion || 'familiar'} del estudiante ${nombreEstudiante}. Me podría confirmar esto, por favor? 🙏`;
    return `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`;
}
/* Fin de funciones JS */

function verificarCedulaMenor(input) {
    const valor = input.value;
    const errorSpan = input.closest('.form-group')?.querySelector('.field-error');

    if (valor.length === 0) {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
        return;
    }

    if (valor.length < 10) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'La cédula debe tener 10 dígitos';
        return;
    }

    // Validar algoritmo ecuatoriano
    if (!validarCedulaEcuatoriana(valor)) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'Cédula inválida';
        return;
    }

    // Verificar duplicado con representante
    const repCedula = document.getElementById('representante_cedula')?.value;
    if (repCedula && valor === repCedula) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'Cédula igual a la del representante';
        return;
    }

    // Verificar duplicado con otros menores
    const allCedulaInputs = document.querySelectorAll('#listaMenores .menor-item input[name*="[cedula]"]');
    let duplicado = false;
    allCedulaInputs.forEach(otherInput => {
        if (otherInput !== input && otherInput.value === valor && valor.length === 10) {
            duplicado = true;
        }
    });

    if (duplicado) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        if (errorSpan) errorSpan.textContent = 'Cédula ya usada en otro menor';
        return;
    }

    input.classList.remove('input-error');
    input.classList.add('input-valid');
    if (errorSpan) errorSpan.textContent = '';
}

function verificarCelularMenor(input) {
    const valor = input.value;
    const errorSpan = input.closest('.form-group')?.querySelector('.field-error');

    if (valor.length === 0) {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
        return;
    }

    if (valor.length < 9) {
        input.classList.add('input-error');
        if (errorSpan) errorSpan.textContent = 'Celular incompleto (9 dígitos)';
    } else {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
        if (errorSpan) errorSpan.textContent = '';
    }
}

function verificarEmailMenor(input) {
    const valor = input.value;
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const errorSpan = input.closest('.form-group')?.querySelector('.field-error');

    if (valor.length === 0) {
        input.classList.remove('input-error', 'input-valid');
        if (errorSpan) errorSpan.textContent = '';
        return;
    }

    if (!regex.test(valor)) {
        input.classList.add('input-error');
        if (errorSpan) errorSpan.textContent = 'Email inválido';
    } else {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
        if (errorSpan) errorSpan.textContent = '';
    }
}

// Búsqueda existente
async function buscarEstudianteExistente(query) {
    const resultadosDiv = document.getElementById('resultadosBusqueda');
    resultadosDiv.innerHTML = '';

    if (query.length < 3) return;

    resultadosDiv.innerHTML = '<div style="padding: 10px; color: #7f8c8d;"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

    try {
        let url = `../api/categorias/estudiantes.php?action=search&q=${encodeURIComponent(query)}&categoria_id=${categoriaId}`;
        if (periodoId) url += `&periodo_id=${periodoId}`;

        const response = await fetch(url);
        const data = await response.json();

        resultadosDiv.innerHTML = '';

        if (data.success && data.estudiantes && data.estudiantes.length > 0) {
            data.estudiantes.forEach(est => {
                const div = document.createElement('div');
                div.className = `resultado-estudiante ${est.ya_matriculado ? 'ya-matriculado' : ''}`;

                let tipo = '';
                if (est.es_solo_representante == 1) {
                    tipo = '<br><span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-size: 10px; padding: 2px 6px;">Representante</span>';
                }

                let info = `<div><strong>${est.nombre}</strong><br><small>${est.cedula || 'Sin cédula'}</small>${tipo}</div>`;
                let action = '';

                if (est.ya_matriculado) {
                    action = '<span class="badge badge-warning">Ya matriculado</span>';
                } else if (est.es_solo_representante == 1) {
                    action = `<button type="button" class="btn btn-sm btn-outline" style="border-color: #3498db; color: #3498db;" onclick="seleccionarEstudianteExistente(${est.id}, '${est.nombre.replace(/'/g, "\\'")}', true)">Matricular</button>`;
                } else {
                    action = `<button type="button" class="btn btn-sm btn-outline" onclick="seleccionarEstudianteExistente(${est.id}, '${est.nombre.replace(/'/g, "\\'")}', false)">Seleccionar</button>`;
                }

                div.innerHTML = info + action;
                resultadosDiv.appendChild(div);
            });
        } else {
            resultadosDiv.innerHTML = '<div style="padding: 10px; color: #7f8c8d;">No se encontraron estudiantes</div>';
        }
    } catch (error) {
        console.error(error);
        resultadosDiv.innerHTML = '<div style="padding: 10px; color: #e74c3c;">Error en búsqueda</div>';
    }
}

function seleccionarEstudianteExistente(id, nombre, esRepresentante = false) {
    matricularExistente(id, esRepresentante);
}

async function matricularExistente(estudianteId, convertirRepresentante = false) {
    try {
        const formData = new FormData();
        formData.append('action', 'matricular'); // Acción correcta
        formData.append('categoria_id', categoriaId);
        formData.append('estudiante_id', estudianteId);
        if (periodoId) formData.append('periodo_id', periodoId);
        if (convertirRepresentante) formData.append('convertir_representante', '1');

        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Estudiante matriculado correctamente', 'success');
            cerrarModal(null, 'modalAgregar');
            cargarDatos();
        } else {
            showNotification(data.message || 'Error al matricular', 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error de conexión', 'error');
    }
}

// Guardar Nuevo Estudiante
async function guardarNuevoEstudiante(event) {
    event.preventDefault();

    const form = event.target;
    const esMenor = document.getElementById('es_menor').checked;

    const submitBtn = document.querySelector(`button[form="${form.id}"]`);
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        let response;

        if (esMenor) {
            // --- MODO MENORES: Enviar JSON ---
            const repNombre = document.getElementById('representante_nombre').value.trim();
            const repCedula = document.getElementById('representante_cedula').value.trim();
            const repCelular = document.getElementById('representante_celular').value.trim();
            const repEmail = document.getElementById('representante_email').value.trim();
            const repFechaNac = document.getElementById('representante_fecha_nacimiento').value;

            if (!repNombre || !repCedula) {
                showNotification('Nombre y cédula del representante son obligatorios', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            // Validar edad representante
            if (repFechaNac) {
                const hoy = new Date();
                const cumple = new Date(repFechaNac);
                let edad = hoy.getFullYear() - cumple.getFullYear();
                const m = hoy.getMonth() - cumple.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
                    edad--;
                }
                if (edad < 18) {
                    showNotification('El representante debe ser mayor de edad', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    return;
                }
            }

            // Recopilar datos de menores desde el DOM
            const menoresItems = document.querySelectorAll('.menor-item');
            if (menoresItems.length === 0) {
                showNotification('Debe agregar al menos un menor', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            const menoresData = [];
            menoresItems.forEach(item => {
                const inputs = item.querySelectorAll('input');
                const menor = {};
                inputs.forEach(input => {
                    // Extraer nombre del campo: menores[0][nombre] -> nombre
                    const match = input.name.match(/menores\[\d+\]\[(\w+)\]/);
                    if (match) {
                        menor[match[1]] = input.value.trim();
                    }
                });
                if (menor.nombre) {
                    menoresData.push(menor);
                }
            });

            if (menoresData.length === 0) {
                showNotification('Debe completar al menos un menor con nombre', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            const jsonPayload = {
                action: 'crear_menores_con_representante',
                categoria_id: categoriaId,
                periodo_id: periodoId,
                representante: {
                    nombre: repNombre,
                    cedula: repCedula,
                    celular: repCelular,
                    email: repEmail,
                    fecha_nacimiento: repFechaNac,
                    referencias: [] // Inicializar
                },
                menores: menoresData
            };

            // Recopilar referencias del representante
            document.querySelectorAll('#referencias_representante_list .referencia-item').forEach(item => {
                const nombreInput = item.querySelector('input[name*="[nombre]"]');
                const telefonoInput = item.querySelector('input[name*="[telefono]"]');
                const relacionInput = item.querySelector('input[name*="[relacion]"]');

                if (nombreInput && nombreInput.value.trim()) {
                    jsonPayload.representante.referencias.push({
                        nombre: nombreInput.value.trim(),
                        telefono: telefonoInput ? telefonoInput.value.trim() : '',
                        relacion: relacionInput ? relacionInput.value.trim() : ''
                    });
                }
            });

            response = await fetch('../api/categorias/estudiantes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonPayload)
            });

        } else {
            // --- MODO MAYOR: Enviar FormData ---
            const formData = new FormData(form);
            formData.append('action', 'crear_y_matricular');
            formData.append('categoria_id', categoriaId);
            if (periodoId) formData.append('periodo_id', periodoId);

            const nombreInput = document.getElementById('nuevo_nombre');
            const cedulaInput = document.getElementById('nuevo_cedula');
            const celularInput = document.getElementById('nuevo_celular');
            const emailInput = document.getElementById('nuevo_email');
            const fechaInput = document.getElementById('nuevo_fecha_nacimiento');

            const nombre = nombreInput?.value.trim();
            const cedula = cedulaInput?.value.trim();
            const celular = celularInput?.value.trim();
            const email = emailInput?.value.trim();
            const fecha = fechaInput?.value;

            let isValid = true;

            if (!nombre) {
                showNotification('Nombre del estudiante es obligatorio', 'error');
                isValid = false;
            }

            if (!cedula || cedula.length !== 10 || cedulaInput.classList.contains('input-error')) {
                cedulaInput.classList.add('input-error');
                showNotification('Cédula inválida o incompleta', 'error');
                isValid = false;
            } else {
                cedulaInput.classList.remove('input-error');
                cedulaInput.classList.add('input-valid');
            }

            if (!celular || celular.length !== 9 || celularInput.parentNode.classList.contains('input-error')) {
                celularInput.parentNode.classList.add('input-error');
                showNotification('Celular incompleto', 'error');
                isValid = false;
            } else {
                celularInput.parentNode.classList.remove('input-error');
                celularInput.parentNode.classList.add('input-valid');
            }

            if (!email || emailInput.classList.contains('input-error')) {
                emailInput.classList.add('input-error');
                showNotification('Correo electrónico inválido', 'error');
                isValid = false;
            }

            // Validar mayor de edad
            if (fecha) {
                const hoy = new Date();
                const cumple = new Date(fecha);
                let edad = hoy.getFullYear() - cumple.getFullYear();
                const m = hoy.getMonth() - cumple.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
                    edad--;
                }
                if (edad < 18) {
                    fechaInput.classList.add('input-error');
                    showNotification('El estudiante debe ser mayor de edad (18+). Si es menor, marque la casilla "El estudiante es menor de edad".', 'error');
                    isValid = false;
                } else {
                    fechaInput.classList.remove('input-error');
                    fechaInput.classList.add('input-valid');
                }
            }

            if (!isValid) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            response = await fetch('../api/categorias/estudiantes.php', {
                method: 'POST',
                body: formData
            });
        }

        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'Estudiante(s) creado(s) correctamente', 'success');
            cerrarModal(null, 'modalAgregar');
            cargarDatos();
        } else {
            showNotification(data.message || 'Error al guardar', 'error');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

    } catch (error) {
        console.error(error);
        showNotification('Error de conexión', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// --- EDICIÓN ---
let estudianteEditandoId = null;

async function abrirModalEditar(id) {
    estudianteEditandoId = id;
    let estudiante = estudiantes.find(e => e.id == id);

    // Si no está en la lista local (ej: representante), obtener del backend
    if (!estudiante) {
        try {
            const resp = await fetch(`../api/categorias/estudiantes.php?action=obtener_estudiante&id=${id}`);
            const data = await resp.json();
            if (data.success && data.estudiante) {
                estudiante = data.estudiante;
            } else {
                showNotification('No se pudo cargar los datos del estudiante', 'error');
                return;
            }
        } catch (e) {
            console.error('Error obteniendo estudiante:', e);
            showNotification('Error de conexión', 'error');
            return;
        }
    }

    document.getElementById('edit_estudiante_id').value = id;
    document.getElementById('edit_nombre').value = estudiante.nombre;
    document.getElementById('edit_fecha_nacimiento').value = estudiante.fecha_nacimiento || '';
    document.getElementById('edit_cedula').value = estudiante.cedula || '';
    document.getElementById('edit_celular').value = estudiante.celular || '';
    document.getElementById('edit_email').value = estudiante.email || '';
    document.getElementById('edit_destacado').checked = estudiante.es_destacado == 1;

    // --- ADAPTAR MODAL (Estudiante vs Representante) ---
    const isRep = estudiante.es_solo_representante == 1;

    // Título
    document.getElementById('editModalTitle').innerHTML = isRep
        ? '<i class="fas fa-user-tie"></i> Editar Representante'
        : '<i class="fas fa-user-edit"></i> Editar Estudiante';

    // Toggle Destacado (Ocultar si es rep)
    const starToggle = document.getElementById('editDestacadoToggle');
    if (starToggle) starToggle.style.display = isRep ? 'none' : 'flex';

    // Campos obligatorios/opcionales (Ahora siempre obligatorios)
    const cedulaInput = document.getElementById('edit_cedula');
    const celularInput = document.getElementById('edit_celular');
    const emailInput = document.getElementById('edit_email');

    // Labels (Siempre obligatorios)
    document.getElementById('editCedulaLabel').innerHTML = 'Cédula * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(10 dígitos)</span>';
    document.getElementById('editCelularLabel').innerHTML = 'Celular * <span style="color:#95a5a6; font-size: 85%; font-weight: normal;">(9 dígitos)</span>';
    document.getElementById('editEmailLabel').innerHTML = 'Correo Electrónico *';

    // Required attributes (Siempre true)
    cedulaInput.required = true;
    celularInput.required = true;
    emailInput.required = true;

    // Validación visual (limpiar estados previos)
    cedulaInput.classList.remove('input-error', 'input-valid');
    celularInput.parentNode.classList.remove('input-error', 'input-valid');
    emailInput.classList.remove('input-error', 'input-valid');


    // Cargar referencias existentes
    const refList = document.getElementById('referencias_editar_list');
    if (refList) {
        refList.innerHTML = '';
        try {
            const resp = await fetch(`../api/categorias/estudiantes.php?action=listar_referencias&estudiante_id=${id}`);
            const data = await resp.json();
            if (data.success && data.referencias) {
                data.referencias.forEach(ref => {
                    agregarReferencia('editar', ref);
                });
            }
        } catch (e) {
            console.error('Error cargando referencias:', e);
        }
    }

    document.getElementById('modalEditar').classList.add('active');
}

async function guardarEdicionEstudiante(event) {
    event.preventDefault();

    const id = document.getElementById('edit_estudiante_id').value;
    const formData = new FormData();
    formData.append('action', 'editar_estudiante');
    formData.append('id', id);
    formData.append('nombre', document.getElementById('edit_nombre').value);
    formData.append('fecha_nacimiento', document.getElementById('edit_fecha_nacimiento').value);
    formData.append('cedula', document.getElementById('edit_cedula').value);
    formData.append('celular', document.getElementById('edit_celular').value);
    formData.append('email', document.getElementById('edit_email').value);
    formData.append('es_destacado', document.getElementById('edit_destacado').checked ? 1 : 0);

    // Recopilar referencias editadas
    const referencias = [];
    document.querySelectorAll('#referencias_editar_list .referencia-item').forEach(item => {
        const nombreInput = item.querySelector('input[name*="[nombre]"]');
        const telefonoInput = item.querySelector('input[name*="[telefono]"]');
        const relacionInput = item.querySelector('input[name*="[relacion]"]');

        if (nombreInput && nombreInput.value.trim()) {
            referencias.push({
                nombre: nombreInput.value.trim(),
                telefono: telefonoInput ? telefonoInput.value.trim() : '',
                relacion: relacionInput ? relacionInput.value.trim() : ''
            });
        }
    });
    formData.append('referencias', JSON.stringify(referencias));

    try {
        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Estudiante actualizado', 'success');
            cerrarModal(null, 'modalEditar');
            cargarDatos();
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Error al actualizar', 'error');
    }
}

// --- QUITAR ESTUDIANTE ---
let idQuitar = null;

function confirmarQuitar(id, nombre, esMenor) {
    idQuitar = id;
    document.getElementById('confirmarQuitarNombre').textContent = nombre;

    const modal = document.getElementById('modalConfirmarQuitar');
    modal.classList.add('active');
}

function cerrarConfirmarQuitar() {
    idQuitar = null;
    document.getElementById('modalConfirmarQuitar').classList.remove('active');
}

async function confirmarQuitarEstudiante() {
    if (!idQuitar) return;

    try {
        const formData = new FormData();
        formData.append('action', 'quitar_estudiante');
        formData.append('categoria_id', categoriaId);
        formData.append('estudiante_id', idQuitar);
        if (periodoId) formData.append('periodo_id', periodoId);

        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Estudiante quitado de la categoría', 'success');
            cerrarConfirmarQuitar();
            cargarDatos();
        } else {
            showNotification(data.message || 'Error al quitar', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// --- GENERACIÓN DE CERTIFICADOS ---
let idsParaGenerar = [];

function abrirModalGenerar(ids) {
    idsParaGenerar = ids;
    if (!ids || ids.length === 0) return;

    const count = ids.length;
    document.getElementById('generarCount').textContent = count;

    const lista = document.getElementById('listaEstudiantesGenerar');
    lista.innerHTML = '';

    // Mostrar nombres
    ids.forEach(id => {
        const est = estudiantes.find(e => e.id == id);
        if (est) {
            const div = document.createElement('div');
            div.style.padding = '5px 0';
            div.style.borderBottom = '1px solid #eee';
            div.innerHTML = `<i class="fas fa-check" style="color: #27ae60; margin-right: 8px;"></i> ${est.nombre}`;
            lista.appendChild(div);
        }
    });

    // Cargar preview
    cargarPreview(ids[0], 'generar'); // Usar el primero para preview

    document.getElementById('modalGenerar').classList.add('active');
}

async function cargarPreview(estudianteId, modo) {
    // modo: 'generar' o 'regenerar'
    const prefix = modo; // IDs prefix

    const imgId = prefix + 'PreviewImage';
    const loadingId = prefix + 'PreviewLoading';
    const containerId = prefix + 'PreviewContainer';

    document.getElementById(loadingId).style.display = 'block';
    document.getElementById(imgId).style.display = 'none';

    // Si no hay plantilla configurada, mostrar alerta en modo generar
    if (modo === 'generar') {
        // Consultar API para verificar plantilla
        // Por simplificación, intentamos generar el preview directamente
    }

    try {
        const params = new URLSearchParams({
            id: estudianteId,
            categoria_id: categoriaId,
            periodo_id: periodoId || '',
            preview: 1,
            t: new Date().getTime()
        });

        const url = `generar_certificado.php?${params}`;

        const img = document.getElementById(imgId);
        img.onload = function () {
            document.getElementById(loadingId).style.display = 'none';
            img.style.display = 'block';
        };
        img.onerror = function () {
            document.getElementById(loadingId).innerHTML = '<p style="color:#e74c3c">Error al cargar preview. Verifique configuración de plantilla.</p>';
            // Mostrar alerta de plantilla si es error
            if (modo === 'generar') {
                document.getElementById('generarPlantillaAlerta').style.display = 'block';
                document.getElementById('linkConfigPlantilla').href = `grupos/detalle.php?id=${grupoId}&tab=plantilla`;
                document.getElementById('btnConfirmarGenerar').disabled = true;
                document.getElementById('btnConfirmarGenerar').style.opacity = '0.5';
            }
        };
        img.src = url;

    } catch (error) {
        console.error(error);
    }
}

async function confirmarGeneracion() {
    if (idsParaGenerar.length === 0) return;

    const btn = document.getElementById('btnConfirmarGenerar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    let exito = 0;
    let errores = 0;
    const fecha = document.getElementById('fechaCertificado').value;

    // Procesar en lotes o uno por uno
    for (const id of idsParaGenerar) {
        try {
            const formData = new FormData();
            formData.append('estudiante_id', id);
            formData.append('categoria_id', categoriaId);
            if (periodoId) formData.append('periodo_id', periodoId);
            formData.append('fecha_emision', fecha);

            const response = await fetch('generar_certificado.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) exito++;
            else errores++;

        } catch (e) {
            errores++;
        }
    }

    cerrarModal(null, 'modalGenerar');

    if (exito > 0) {
        showNotification(`Se generaron ${exito} certificados correctamente`, 'success');
        if (errores > 0) showNotification(`Hubo ${errores} errores`, 'warning');
        cargarDatos();
        // Limpiar selección
        seleccionados = [];
        actualizarContadorSeleccionados();
    } else {
        showNotification('No se pudieron generar los certificados', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-certificate"></i> Generar Certificados';
}

function generarCertificadosSeleccionados() {
    abrirModalGenerar(seleccionados);
}

function generarTodosCertificados() {
    // Filtrar los que NO tienen certificado
    const sinCertificado = estudiantes.filter(e => !e.certificado_codigo).map(e => e.id);
    if (sinCertificado.length > 0) {
        abrirModalGenerar(sinCertificado);
    } else {
        showNotification('Todos los estudiantes ya tienen certificado', 'info');
    }
}

// --- VISUALIZACIÓN Y DESCARGA ---

function previsualizarCertificado(codigo) {
    if (!codigo) return;

    currentPreviewCode = codigo;
    const modal = document.getElementById('modalPreview');
    const img = document.getElementById('previewImage');
    const loading = document.getElementById('previewLoading');

    loading.style.display = 'block';
    img.style.display = 'none';

    // Obtener info del certificado
    fetch(`../api/certificados/index.php?action=info&codigo=${codigo}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const info = data.certificado;

                document.getElementById('previewCodigo').textContent = info.codigo;
                document.getElementById('previewNombre').textContent = info.estudiante_nombre;
                document.getElementById('previewFecha').textContent = info.fecha_emision;
                document.getElementById('previewFechaCreacion').textContent = info.created_at;

                if (info.razon_regeneracion) {
                    document.getElementById('previewRazonContainer').style.display = 'block';
                    document.getElementById('previewRazon').textContent = info.razon_regeneracion;
                } else {
                    document.getElementById('previewRazonContainer').style.display = 'none';
                }

                // Cargar imagen
                const urlImg = `ver_certificado.php?codigo=${codigo}&tipo=imagen&t=${new Date().getTime()}`;
                img.onload = () => {
                    loading.style.display = 'none';
                    img.style.display = 'block';
                };
                img.src = urlImg;

                modal.classList.add('active');
            } else {
                showNotification('No se encontró información del certificado', 'error');
            }
        })
        .catch(err => {
            showNotification('Error al cargar información', 'error');
        });
}

function descargarCertificado(codigo, tipo) {
    window.open(`ver_certificado.php?codigo=${codigo}&tipo=${tipo}&descargar=1`, '_blank');
}

function descargarDesdePreview(tipo) {
    if (currentPreviewCode) {
        descargarCertificado(currentPreviewCode, tipo);
    }
}

function descargarCertificadosSeleccionados(tipo) {
    // Descarga masiva no implementada en detail, solo individual en loop o zip
    // Por simplicidad, descargamos uno por uno con delay o mostramos aviso
    if (seleccionados.length > 5) {
        if (!confirm(`Va a descargar ${seleccionados.length} archivos. ¿Desea continuar?`)) return;
    }

    seleccionados.forEach((id, index) => {
        const est = estudiantes.find(e => e.id == id);
        if (est && est.certificado_codigo) {
            setTimeout(() => {
                descargarCertificado(est.certificado_codigo, tipo);
            }, index * 1000);
        }
    });
}

function descargarTodosCertificados(tipo) {
    const conCertificado = estudiantes.filter(e => e.certificado_codigo);
    if (conCertificado.length === 0) {
        showNotification('No hay certificados para descargar', 'warning');
        return;
    }

    if (confirm(`Se descargarán ${conCertificado.length} certificados. Esto puede tardar unos momentos.`)) {
        // Idealmente endpoint ZIP
        conCertificado.forEach((est, index) => {
            setTimeout(() => {
                descargarCertificado(est.certificado_codigo, tipo);
            }, index * 800);
        });
    }
}

// --- UTILIDADES ---

function showNotification(msg, type = 'info') {
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;

    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';

    notif.innerHTML = `<i class="fas fa-${icon}"></i> ${msg}`;

    document.body.appendChild(notif);

    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transform = 'translateY(-20px)';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Función auxiliar para calcular edad
function calcularEdad(fecha) {
    if (!fecha) return '';
    const hoy = new Date();
    const cumpleanos = new Date(fecha);
    let edad = hoy.getFullYear() - cumpleanos.getFullYear();
    const m = hoy.getMonth() - cumpleanos.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < cumpleanos.getDate())) {
        edad--;
    }
    return edad;
}

// Drodown descarga
function toggleDropdownDescargas() {
    const menu = document.getElementById('dropdownDescargas');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}





function descargarListaEstudiantes() {
    if (!categoriaId) return;
    const url = `../api/categorias/exportar_pdf.php?categoria_id=${categoriaId}&periodo_id=${periodoId || ''}`;
    window.open(url, '_blank');
}

/* Fin de funciones JS */

// --- ACCIONES MASIVAS: QUITAR ---

function confirmarQuitarSeleccionados() {
    if (seleccionados.length === 0) return;

    const modal = document.getElementById('modalConfirmarQuitarSeleccionados');
    const countSpan = document.getElementById('countSeleccionadosConfirm');

    if (modal && countSpan) {
        countSpan.textContent = seleccionados.length;
        modal.classList.add('active');
    }
}

function cerrarConfirmarQuitarSeleccionados() {
    const modal = document.getElementById('modalConfirmarQuitarSeleccionados');
    if (modal) {
        modal.classList.remove('active');
    }
}

async function quitarSeleccionados() {
    // Ya no necesitamos confirm() nativo porque estamos en el modal

    const btn = document.getElementById('btnConfirmarQuitarSel');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    const formData = new FormData();
    formData.append('action', 'quitar_multiple');
    formData.append('ids', JSON.stringify(seleccionados));
    formData.append('categoria_id', categoriaId);

    try {
        const response = await fetch('../api/categorias/estudiantes.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            // Limpiar selección
            seleccionados = [];
            document.getElementById('selectAll').checked = false;
            actualizarContadorSeleccionados();
            cerrarConfirmarQuitarSeleccionados();
            cargarDatos(); // Recargar tabla
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error al procesar la solicitud', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}
