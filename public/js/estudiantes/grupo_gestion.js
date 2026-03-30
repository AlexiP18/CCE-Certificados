/**
 * Logic for Group Student Management
 */

// Global Variables (Initialized in View)
// const estudiantesData = [];
// const grupoId = ...;
// const grupoColor = ...;

let estudiantesFiltrados = [];
let datosPaginados = [];
let estudiantesSeleccionados = new Set();
let selectedCerts = new Set();
let paginaActual = 1;
let porPagina = 20;
let totalPaginasGlobal = 1;
let currentStudentId = null;
let currentStudentIdForCerts = null;
let estudianteIdAEliminar = null;
let generacionLoteItems = [];
let generacionLoteSeleccion = new Set();
let generacionLoteFiltro = 'todos';
let generacionLoteBusqueda = '';
let generacionLotePeriodoFiltro = '';
let currentPageGenLote = 1;
let rowsPerPageGenLote = 10;
let totalPagesGenLote = 1;
let lastFilteredGenLote = [];
let filtroCategoriaAnterior = '';
let filtroPeriodoAnterior = '';

document.addEventListener('DOMContentLoaded', () => {
    actualizarModoFiltro();
    cargarEstudiantes();
    setupHorizontalScroll();
    actualizarEstadoBotonGenerarLote();

    const btnActivo = document.querySelector('.group-tab-btn.active') || document.querySelector('.group-tab-btn');
    if (btnActivo) {
        const tabInicial = btnActivo.dataset.target || 'tab-todos';
        switchGroupTab(tabInicial, btnActivo);
    }
});

function actualizarModoFiltro() {
    const toggle = document.getElementById('filterModoEstado');
    const lblAprobados = document.getElementById('estadoLabelAprobados');
    const lblCertificados = document.getElementById('estadoLabelCertificados');
    const esCertificados = toggle ? toggle.checked : false;

    if (lblAprobados) lblAprobados.classList.toggle('active', !esCertificados);
    if (lblCertificados) lblCertificados.classList.toggle('active', esCertificados);
}

function setupHorizontalScroll() {
    const tableWrapper = document.querySelector('.table-scroll-wrapper');
    if (tableWrapper) {
        tableWrapper.addEventListener('wheel', (evt) => {
            if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                evt.preventDefault();
                tableWrapper.scrollLeft += evt.deltaY;
            }
        }, { passive: false });
    }
}

function switchGroupTab(tabId, btn = null) {
    document.querySelectorAll('.group-tab-pane').forEach(pane => {
        pane.classList.remove('active');
        pane.style.display = 'none';
    });

    document.querySelectorAll('.group-tab-btn').forEach(tabBtn => {
        tabBtn.classList.remove('active');
    });

    const tabObjetivo = document.getElementById(tabId);
    if (tabObjetivo) {
        tabObjetivo.classList.add('active');
        tabObjetivo.style.display = 'block';
    }

    const btnObjetivo = btn || Array.from(document.querySelectorAll('.group-tab-btn'))
        .find(tabBtn => String(tabBtn.dataset.target || '') === String(tabId));
    if (btnObjetivo) {
        btnObjetivo.classList.add('active');
    }

    if (String(tabId).startsWith('tab-cat-')) {
        const categoriaId = String(tabId).replace('tab-cat-', '');
        const iframe = document.getElementById(`iframe-cat-${categoriaId}`);
        const loader = document.getElementById(`loader-cat-${categoriaId}`);
        if (!iframe) return;

        const srcActual = iframe.getAttribute('src');
        const srcPendiente = iframe.getAttribute('data-src');

        if (!srcActual && srcPendiente) {
            if (loader) loader.style.display = 'block';
            iframe.style.display = 'none';
            iframe.setAttribute('src', srcPendiente);
        } else {
            if (loader) loader.style.display = 'none';
            iframe.style.display = 'block';
        }
    }
}

// ========== PAGINATION ==========

function cambiarFilasPorPagina(val) {
    porPagina = parseInt(val);
    irPagina(1);
}

function irPagina(pag) {
    if (pag === 'last') pag = totalPaginasGlobal;
    if (pag < 1) pag = 1;
    if (pag > totalPaginasGlobal) pag = totalPaginasGlobal;

    paginaActual = pag;
    renderTabla();
}

function prevPagina() {
    irPagina(paginaActual - 1);
}

function nextPagina() {
    irPagina(paginaActual + 1);
}

function irPaginaManual(val) {
    irPagina(parseInt(val));
}

// ========== DATA LOADING ==========

async function cargarEstudiantes() {
    const loader = document.getElementById('loadingOverlay');
    if (loader) loader.style.display = 'flex';

    try {
        const response = await fetch(`../api/estudiantes/index.php?action=listar_grupo&grupo_id=${grupoId}`);
        const data = await response.json();

        if (data.success) {
            estudiantesData = data.estudiantes || [];
            estudiantesFiltrados = [...estudiantesData];

            // Update stats
            if (document.getElementById('totalEstudiantes'))
                document.getElementById('totalEstudiantes').textContent = estudiantesData.length;

            const menores = estudiantesData.filter(e => e.es_menor == 1).length;
            if (document.getElementById('totalMenores'))
                document.getElementById('totalMenores').textContent = menores;

            prepararJerarquia(estudiantesFiltrados);
        } else {
            mostrarError('Error al cargar estudiantes');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión');
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

function filtrarEstudiantes() {
    const busqueda = document.getElementById('searchInput').value.toLowerCase().trim();
    const categoriaId = document.getElementById('filterCategoria').value;
    const periodoId = document.getElementById('filterPeriodo').value;
    const modoCertificados = document.getElementById('filterModoEstado')?.checked || false;
    // const estado = document.getElementById('filterEstado').value; // Removed

    const cambioAmbito = String(categoriaId) !== String(filtroCategoriaAnterior)
        || String(periodoId) !== String(filtroPeriodoAnterior);
    if (cambioAmbito) {
        estudiantesSeleccionados.clear();
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }
    filtroCategoriaAnterior = String(categoriaId);
    filtroPeriodoAnterior = String(periodoId);

    estudiantesFiltrados = estudiantesData.filter(est => {
        let cumple = true;
        const categorias = Array.isArray(est.categorias) ? est.categorias : [];

        const categoriasCoincidentes = categorias.filter(c => {
            if (categoriaId && String(c.id) !== String(categoriaId)) return false;
            if (periodoId && String(c.periodo_id) !== String(periodoId)) return false;
            return true;
        });

        if (busqueda) {
            cumple = cumple && (
                (est.nombre && est.nombre.toLowerCase().includes(busqueda)) ||
                (est.cedula && est.cedula.includes(busqueda)) ||
                (est.categoria_nombre && est.categoria_nombre.toLowerCase().includes(busqueda)) ||
                (est.representante_nombre && est.representante_nombre.toLowerCase().includes(busqueda)) ||
                (est.representante_cedula && est.representante_cedula.includes(busqueda))
            );
        }

        if (categoriaId || periodoId) {
            cumple = cumple && categoriasCoincidentes.length > 0;
        }

        const categoriasParaEvaluar = (categoriaId || periodoId) ? categoriasCoincidentes : categorias;

        const tieneAprobado = categoriasParaEvaluar.some(c => Number(c.cert_aprobado) === 1);
        const tieneGenerado = categoriasParaEvaluar.some(c => Number(c.cert_generado) === 1);
        cumple = cumple && (modoCertificados ? tieneGenerado : tieneAprobado);

        return cumple;
    });

    paginaActual = 1;
    prepararJerarquia(estudiantesFiltrados);
    actualizarEstadoBotonGenerarLote();
}

// Prepara datosPaginados con jerarquía: representantes (reales o virtuales) con sus menores
function prepararJerarquia(lista) {
    estudiantesFiltrados = lista;
    datosPaginados = [];

    if (!lista || lista.length === 0) {
        renderTabla();
        return;
    }

    const idsEnLista = new Set(lista.map(e => parseInt(e.id)));

    // 1. Agrupar menores por representante
    const menoresPorRep = new Map();
    lista.forEach(est => {
        if (est.es_menor == 1 && est.representante_cedula) {
            const repId = est.representante_id ? parseInt(est.representante_id) : null;
            let key;
            if (repId && idsEnLista.has(repId)) {
                key = repId;                       // Representante es mayor en la lista
            } else {
                key = 'rep_' + est.representante_cedula;  // Representante virtual
            }
            if (!menoresPorRep.has(key)) menoresPorRep.set(key, []);
            menoresPorRep.get(key).push(est);
        }
    });

    const procesados = new Set();

    // 2. Estudiantes mayores (peuvent tener menores a cargo)
    lista.forEach(est => {
        if (est.es_menor == 1) return; // Los menores se renderizan bajo su representante

        const filaMayor = { tipo: 'estudiante', data: est, menores: [] };

        const repId = parseInt(est.id);
        if (menoresPorRep.has(repId)) {
            filaMayor.menores = menoresPorRep.get(repId);
            menoresPorRep.get(repId).forEach(m => procesados.add(m.id));
        }

        datosPaginados.push(filaMayor);
    });

    // 3. Representantes virtuales (menores cuyo representante NO está en la lista)
    lista.forEach(est => {
        if (est.es_menor == 1 && !procesados.has(est.id)) {
            const key = 'rep_' + est.representante_cedula;
            const yaAgregado = datosPaginados.find(f => f.tipo === 'representante_virtual' && f.cedula === est.representante_cedula);

            if (!yaAgregado && menoresPorRep.has(key)) {
                const grupoMenores = menoresPorRep.get(key);
                datosPaginados.push({
                    tipo: 'representante_virtual',
                    nombre: est.representante_nombre,
                    cedula: est.representante_cedula,
                    celular: est.representante_celular,
                    email: est.representante_email,
                    fecha_nacimiento: est.representante_fecha_nacimiento,
                    representante_id: est.representante_id,
                    menores: grupoMenores
                });
                grupoMenores.forEach(m => procesados.add(m.id));
            } else if (!yaAgregado) {
                // Menor huérfano sin representante claro: lo mostramos solo
                datosPaginados.push({ tipo: 'estudiante', data: est, menores: [] });
            }
        }
    });

    // 4. Ordenar alfabéticamente por nombre principal
    datosPaginados.sort((a, b) => {
        const nA = a.tipo === 'estudiante' ? (a.data.nombre || '') : (a.nombre || '');
        const nB = b.tipo === 'estudiante' ? (b.data.nombre || '') : (b.nombre || '');
        return nA.localeCompare(nB);
    });

    renderTabla();
}

// ========== RENDERING ==========

function renderTabla() {
    const tbody = document.getElementById('estudiantesBody');
    const footer = document.getElementById('paginationFooter');

    const totalItems = datosPaginados.length;
    let inicio = 0;
    let fin = totalItems;

    if (porPagina > 0) {
        totalPaginasGlobal = Math.ceil(totalItems / porPagina);
        if (totalPaginasGlobal < 1) totalPaginasGlobal = 1;
        if (paginaActual > totalPaginasGlobal) paginaActual = 1;

        inicio = (paginaActual - 1) * porPagina;
        fin = inicio + porPagina;
        if (fin > totalItems) fin = totalItems;
    } else {
        totalPaginasGlobal = 1;
    }

    const paginados = datosPaginados.slice(inicio, fin);

    if (document.getElementById('countBadge'))
        document.getElementById('countBadge').textContent = `${totalItems} estudiantes`;

    if (totalItems === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state" style="text-align:center; padding: 40px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #cbd5e0; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; color: #7f8c8d;">No se encontraron estudiantes</h3>
                    <p style="margin: 5px 0 0; color: #95a5a6;">Intenta con otros filtros de búsqueda</p>
                </td>
            </tr>
        `;
        if (footer) footer.style.display = 'none';
        return;
    }

    if (footer) footer.style.display = 'flex';

    // Update pagination controls
    if (document.getElementById('pagStart')) document.getElementById('pagStart').textContent = inicio + 1;
    if (document.getElementById('pagEnd')) document.getElementById('pagEnd').textContent = fin;
    if (document.getElementById('pagTotal')) document.getElementById('pagTotal').textContent = totalItems;
    if (document.getElementById('totalPages')) document.getElementById('totalPages').textContent = totalPaginasGlobal;
    if (document.getElementById('pageInput')) {
        document.getElementById('pageInput').value = paginaActual;
        document.getElementById('pageInput').max = totalPaginasGlobal;
    }

    document.getElementById('btnPageFirst').disabled = paginaActual === 1;
    document.getElementById('btnPagePrev').disabled = paginaActual === 1;
    document.getElementById('btnPageNext').disabled = paginaActual === totalPaginasGlobal;
    document.getElementById('btnPageLast').disabled = paginaActual === totalPaginasGlobal;

    const representantesCedulas = new Set();
    estudiantesData.forEach(e => {
        if (e.representante_cedula) representantesCedulas.add(e.representante_cedula);
    });

    let htmlContent = '';

    paginados.forEach(fila => {
        if (fila.tipo === 'estudiante') {
            const repUniqueId = 'est-' + fila.data.id;
            const hasMenores = fila.menores && fila.menores.length > 0;
            htmlContent += buildEstudianteRowHtml(fila.data, repUniqueId, hasMenores, representantesCedulas);
            if (hasMenores) {
                fila.menores.forEach(menor => {
                    htmlContent += buildMenorRowHtml(menor, repUniqueId);
                });
            }
        } else if (fila.tipo === 'representante_virtual') {
            const repUniqueId = 'virt-' + (fila.cedula || '').replace(/[^a-zA-Z0-9]/g, '_');
            htmlContent += buildRepresentanteVirtualRowHtml(fila, repUniqueId);
            fila.menores.forEach(menor => {
                htmlContent += buildMenorRowHtml(menor, repUniqueId);
            });
        }
    });


    tbody.innerHTML = htmlContent;
    sincronizarCheckboxSeleccionGeneral();
}

// ========== ROW BUILDER HELPERS ==========

function buildCeldasDatos(est) {
    // Age
    let edadHtml = '<span style="color: #cbd5e0;">-</span>';
    if (est.fecha_nacimiento) {
        const birthDate = new Date(est.fecha_nacimiento);
        const fechaDisplay = est.fecha_nacimiento.split('-').reverse().join('/');
        edadHtml = `<div>${fechaDisplay}</div><div class="badge badge-edad">${calcularEdad(est.fecha_nacimiento)} años</div>`;
    }

    // Historial + última actualización
    let fechaActualizacionHtml = '<small style="display:inline-block;color:#cbd5e0;white-space:nowrap;">-</small>';
    if (est.fecha_actualizacion) {
        const fAct = new Date(est.fecha_actualizacion);
        if (!Number.isNaN(fAct.getTime())) {
            const dia = String(fAct.getDate()).padStart(2, '0');
            const mes = String(fAct.getMonth() + 1).padStart(2, '0');
            const anio = fAct.getFullYear();
            const hora = String(fAct.getHours()).padStart(2, '0');
            const min = String(fAct.getMinutes()).padStart(2, '0');
            fechaActualizacionHtml = `<small style="display:inline-block;color:#6b7280;white-space:nowrap;">${dia}/${mes}/${anio} ${hora}:${min}</small>`;
        }
    }

    const historialHtml = `
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;">
            <button class="btn-icon btn-view" title="Ver Historial" onclick="abrirModalHistorial(${est.id})" style="margin: 0 auto;"><i class="fas fa-history"></i></button>
            ${fechaActualizacionHtml}
        </div>
    `;

    // Fecha Registro
    let fechaRegistroHtml = '<span style="color:#cbd5e0;">-</span>';
    if (est.fecha_creacion) {
        const f = new Date(est.fecha_creacion);
        const dia = String(f.getDate()).padStart(2,'0');
        const mes = String(f.getMonth()+1).padStart(2,'0');
        fechaRegistroHtml = `<div style="line-height:1.2;"><div style="font-weight:500;color:#4b5563;">${dia}/${mes}/${f.getFullYear()}</div><small style="color:#9ca3af;">Registro</small></div>`;
    }

    // Categorias
    let categoriasHtml = '<span style="color:#95a5a6;">-</span>';
    if (est.categorias && est.categorias.length > 0) {
        const catsGrouped = {};
        est.categorias.forEach(c => {
            if (!catsGrouped[c.nombre]) catsGrouped[c.nombre] = { ...c, periodos: [] };
            if (!catsGrouped[c.nombre].periodos.some(p => p.nombre === c.periodo)) {
                catsGrouped[c.nombre].periodos.push({ 
                    nombre: c.periodo || 'Sin período', 
                    fecha: c.fecha_inicio || '0000-00-00',
                    es_destacado: c.es_destacado == 1 || c.es_destacado === '1' || c.es_destacado === true
                });
            }
        });
        categoriasHtml = Object.values(catsGrouped).map(cat => {
            cat.periodos.sort((a,b) => a.fecha < b.fecha ? -1 : 1);
            
            const tieneDestacado = cat.periodos.some(p => p.es_destacado);
            const estrellaHdr = tieneDestacado ? '<i class="fas fa-star"></i>' : '';
            
            let tooltipHtml = '<div class="tooltip-content">';
            cat.periodos.forEach(p => {
                const estrellaPeriodo = p.es_destacado ? '<i class="fas fa-star tooltip-star"></i>' : '';
                tooltipHtml += `
                    <div class="tooltip-period-item">
                        <span>${escapeHtml(p.nombre)}</span>
                        ${estrellaPeriodo}
                    </div>`;
            });
            tooltipHtml += '</div>';

            return `
                <span class="badge badge-categoria" style="background:${cat.color}20;color:${cat.color};display:inline-flex;width:fit-content;margin-bottom:0px;">
                    ${cat.icono||'📁'} ${escapeHtml(cat.nombre||'Sin categoría')} ${estrellaHdr}
                    ${tooltipHtml}
                </span>`;
        }).join('<br>');
    }

    // Contacto
    const esMenor = est.es_menor == 1;
    let contactoHtml = '<div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start;">';
    if (est.celular) {
        contactoHtml += `<a href="https://wa.me/${formatPhoneLink(est.celular)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="max-width:100%;"><i class="fab fa-whatsapp"></i> ${formatPhoneDisplay(est.celular)}</a>`;
    } else if (esMenor && est.representante_celular) {
        contactoHtml += `<a href="https://wa.me/${formatPhoneLink(est.representante_celular)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="max-width:100%;"><i class="fas fa-user-tie"></i> ${formatPhoneDisplay(est.representante_celular)} (Rep)</a>`;
    }
    if (est.email) {
        contactoHtml += `<a href="mailto:${est.email}" class="contact-chip contact-chip-email" style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-envelope"></i> ${est.email}</a>`;
    } else if (esMenor && est.representante_email) {
        contactoHtml += `<a href="mailto:${est.representante_email}" class="contact-chip contact-chip-email" style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-user-tie"></i> ${est.representante_email} (Rep)</a>`;
    }
    if (!est.celular && !(esMenor && est.representante_celular) && !est.email && !(esMenor && est.representante_email)) {
        contactoHtml += '<span style="color:#cbd5e0;">-</span>';
    }
    contactoHtml += '</div>';

    return { edadHtml, fechaRegistroHtml, categoriasHtml, contactoHtml, historialHtml };
}

function buildEstudianteRowHtml(est, repUniqueId, hasMenores, representantesCedulas) {
    const iniciales = est.nombre ? est.nombre.split(' ').map(n=>n[0]).slice(0,2).join('').toUpperCase() : '?';
    const colorCat = est.categoria_color || '#95a5a6';
    const esMenor = est.es_menor == 1;
    const isSelected = estudiantesSeleccionados.has(String(est.id));
    const esRepresentante = representantesCedulas && est.cedula && representantesCedulas.has(est.cedula);

    let rowClass = isSelected ? 'row-selected' : '';
    if (esMenor) rowClass += ' estudiante-menor';
    if (esRepresentante || hasMenores) rowClass += ' estudiante-representante';

    const chevron = hasMenores
        ? `<i id="toggle-${repUniqueId}" class="fas fa-chevron-right" onclick="toggleMenores('${repUniqueId}')" style="cursor:pointer;margin-right:8px;color:#3498db;width:15px;text-align:center;display:inline-block;" title="Ver menores"></i>`
        : '';

    const { edadHtml, fechaRegistroHtml, categoriasHtml, contactoHtml, historialHtml } = buildCeldasDatos(est);

    return `
        <tr class="${rowClass.trim()}">
            <td class="sticky-col sticky-left-1">
                <input type="checkbox" class="select-checkbox" data-id="${est.id}"
                       onchange="toggleSelectStudent(${est.id})" ${isSelected ? 'checked' : ''}>
            </td>
            <td class="sticky-col sticky-left-2">
                <div class="student-name">
                    ${chevron}
                    <div class="student-avatar" style="background:linear-gradient(135deg,${colorCat}cc,${colorCat});">${iniciales}</div>
                    <div class="student-info">
                        <strong>
                            ${escapeHtml(est.nombre)}
                            ${parseInt(est.tiene_referencias || 0) > 0 ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${est.id})" title="Ver Referencias"></i>` : ''}
                        </strong>
                        ${esMenor ? '<span class="badge badge-menor"><i class="fas fa-child"></i> Menor de edad</span>' : ''}
                    </div>
                </div>
            </td>
            <td>${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color:#aaa">—</span>'}</td>
            <td>${edadHtml}</td>
            <td style="text-align:center;">${contactoHtml}</td>
            <td style="min-width:380px;"><div class="categorias-container">${categoriasHtml}</div></td>
            <td style="min-width:230px;">${fechaRegistroHtml}</td>
            <td style="text-align:center;">${historialHtml}</td>
            <td class="sticky-col sticky-right" style="text-align:right;">
                <div class="actions-cell" style="justify-content:flex-end;">
                    <button class="btn-icon btn-view" onclick="abrirModalGeneracionLote([${est.id}])" title="Generar Certificados" style="color:#00348a;background:#e8f0fe;"><i class="fas fa-certificate"></i></button>
                    <button class="btn-icon btn-edit" onclick="editarEstudiante(${est.id})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="eliminarEstudiante(${est.id})" title="Quitar"><i class="fas fa-user-minus"></i></button>
                </div>
            </td>
        </tr>`;
}

function buildMenorRowHtml(est, repUniqueId) {
    const iniciales = est.nombre ? est.nombre.split(' ').map(n=>n[0]).slice(0,2).join('').toUpperCase() : '?';
    const colorCat = est.categoria_color || '#95a5a6';
    const isSelected = estudiantesSeleccionados.has(String(est.id));

    const { edadHtml, fechaRegistroHtml, categoriasHtml, contactoHtml, historialHtml } = buildCeldasDatos(est);

    const rowClass = (isSelected ? 'row-selected ' : '') + `estudiante-menor menor-de-${repUniqueId}`;

    return `
        <tr class="${rowClass}" style="display:none;background:#f8fbff;">
            <td class="sticky-col sticky-left-1">
                <input type="checkbox" class="select-checkbox" data-id="${est.id}"
                       onchange="toggleSelectStudent(${est.id})" ${isSelected ? 'checked' : ''}>
            </td>
            <td class="sticky-col sticky-left-2">
                <div class="student-name student-indent">
                    <i class="fas fa-level-up-alt fa-rotate-90" style="color: #bdc3c7; margin-right: 8px; margin-left: 5px;"></i>
                    <div class="student-avatar" style="background:linear-gradient(135deg,${colorCat}cc,${colorCat});width:28px;height:28px;font-size:11px;">${iniciales}</div>
                    <div class="student-info">
                        <strong>
                            ${escapeHtml(est.nombre)}
                            ${parseInt(est.tiene_referencias || 0) > 0 ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${est.id})" title="Ver Referencias"></i>` : ''}
                        </strong>
                        <span class="badge badge-menor"><i class="fas fa-child"></i> Menor de edad</span>
                    </div>
                </div>
            </td>
            <td>${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color:#aaa">—</span>'}</td>
            <td>${edadHtml}</td>
            <td style="text-align:center;">${contactoHtml}</td>
            <td style="min-width:380px;"><div class="categorias-container">${categoriasHtml}</div></td>
            <td style="min-width:230px;">${fechaRegistroHtml}</td>
            <td style="text-align:center;">${historialHtml}</td>
            <td class="sticky-col sticky-right" style="text-align:right;">
                <div class="actions-cell" style="justify-content:flex-end;">
                    <button class="btn-icon btn-view" onclick="abrirModalGeneracionLote([${est.id}])" title="Generar Certificados" style="color:#00348a;background:#e8f0fe;"><i class="fas fa-certificate"></i></button>
                    <button class="btn-icon btn-edit" onclick="editarEstudiante(${est.id})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" onclick="eliminarEstudiante(${est.id})" title="Quitar"><i class="fas fa-user-minus"></i></button>
                </div>
            </td>
        </tr>`;
}

function buildRepresentanteVirtualRowHtml(fila, repUniqueId) {
    const hasMenores = fila.menores && fila.menores.length > 0;
    const chevron = hasMenores
        ? `<i id="toggle-${repUniqueId}" class="fas fa-chevron-right" onclick="toggleMenores('${repUniqueId}')" style="cursor:pointer;margin-right:8px;color:#3498db;width:15px;text-align:center;display:inline-block;" title="Ver menores (${fila.menores.length})"></i>`
        : '<span style="display:inline-block;width:23px;"></span>';

    let contactoHtml = '<div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start;">';
    if (fila.celular) {
        contactoHtml += `<a href="https://wa.me/${formatPhoneLink(fila.celular)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="max-width:100%;"><i class="fab fa-whatsapp"></i> ${formatPhoneDisplay(fila.celular)}</a>`;
    }
    if (fila.email) {
        contactoHtml += `<a href="mailto:${fila.email}" class="contact-chip contact-chip-email" style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-envelope"></i> ${fila.email}</a>`;
    }
    if (!fila.celular && !fila.email) contactoHtml += '<span style="color:#cbd5e0;">-</span>';
    contactoHtml += '</div>';

    const countBadge = hasMenores
        ? `<span style="background:#3498db22;color:#3498db;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:6px;font-weight:600;">${fila.menores.length} menor${fila.menores.length>1?'es':''}</span>`
        : '';

    let fechaNacimientoHtml = '<span style="color:#cbd5e0;">-</span>';
    if (fila.fecha_nacimiento) {
        fechaNacimientoHtml = `<div>${escapeHtml(fila.fecha_nacimiento)}</div><div class="badge badge-edad">${calcularEdad(fila.fecha_nacimiento)} años</div>`;
    }

    const accionesHtml = fila.representante_id
        ? `
            <div class="actions-cell" style="justify-content:flex-end;">
                <button class="btn-icon btn-edit" onclick="editarEstudiante(${fila.representante_id})" title="Editar Representante">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `
        : '';

    return `
        <tr class="estudiante-representante">
            <td class="sticky-col sticky-left-1"></td>
            <td class="sticky-col sticky-left-2">
                <div class="student-name">
                    ${chevron}
                    <div class="student-avatar" style="background:linear-gradient(135deg,#7f8c8dcc,#7f8c8d);width:32px;height:32px;font-size:12px;">
                        <i class="fas fa-user-tie" style="font-size:14px;"></i>
                    </div>
                    <div class="student-info">
                        <strong>
                            ${escapeHtml(fila.nombre || 'Sin nombre')}
                            ${(parseInt(fila.tiene_referencias || 0) > 0 && fila.representante_id) ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${fila.representante_id})" title="Ver Referencias"></i>` : ''}
                        </strong>
                        <span style="display:inline-flex;align-items:center;gap:4px;">
                            <span class="badge" style="background:#7f8c8d22;color:#7f8c8d;font-size:10px;"><i class="fas fa-user-tie"></i> Representante</span>
                            ${countBadge}
                        </span>
                    </div>
                </div>
            </td>
            <td>${fila.cedula ? `<span class="cedula-cell" style="opacity:0.85;">${escapeHtml(fila.cedula)}</span>` : '<span style="color:#aaa">—</span>'}</td>
            <td>${fechaNacimientoHtml}</td>
            <td style="text-align:center;">${contactoHtml}</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="sticky-col sticky-right" style="text-align:right;">${accionesHtml}</td>
        </tr>`;
}

function toggleMenores(repUniqueId) {
    const rows = document.querySelectorAll(`.menor-de-${repUniqueId}`);
    const toggleEl = document.getElementById(`toggle-${repUniqueId}`);
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
        toggleEl.classList.toggle('fa-chevron-down', !isHidden);
        toggleEl.classList.toggle('fa-chevron-right', isHidden);
    }
}

// ========== SELECTION & BULK ACTIONS ==========

function obtenerIdsFilasEnTablaActual() {
    return Array.from(
        document.querySelectorAll('#estudiantesBody .select-checkbox[data-id]')
    )
        .map(chk => String(chk.dataset.id || '').trim())
        .filter(Boolean);
}

function sincronizarCheckboxSeleccionGeneral() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (!selectAllCheckbox) return;

    const idsPagina = Array.from(new Set(obtenerIdsFilasEnTablaActual()));
    if (!idsPagina.length) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        return;
    }

    const seleccionadosPagina = idsPagina.filter(id => estudiantesSeleccionados.has(id)).length;
    selectAllCheckbox.checked = seleccionadosPagina > 0 && seleccionadosPagina === idsPagina.length;
    selectAllCheckbox.indeterminate = seleccionadosPagina > 0 && seleccionadosPagina < idsPagina.length;
}

function normalizarSeleccionVisible() {
    const idsVisiblesMarcados = Array.from(new Set(obtenerIdsSeleccionadosEnTabla()));
    estudiantesSeleccionados = new Set(idsVisiblesMarcados);
    return idsVisiblesMarcados;
}

function toggleSelectAll(checkbox) {
    const isChecked = checkbox.checked;
    const idsPagina = Array.from(new Set(obtenerIdsFilasEnTablaActual()));

    if (isChecked) {
        idsPagina.forEach(id => estudiantesSeleccionados.add(id));
    } else {
        idsPagina.forEach(id => estudiantesSeleccionados.delete(id));
    }
    renderTabla();
    updateBulkActionsBar();
}

function toggleSelectStudent(id) {
    const studentId = String(id);

    if (estudiantesSeleccionados.has(studentId)) {
        estudiantesSeleccionados.delete(studentId);
    } else {
        estudiantesSeleccionados.add(studentId);
    }

    sincronizarCheckboxSeleccionGeneral();

    // Update specific row class
    const checkbox = document.querySelector(`.select-checkbox[data-id="${studentId}"]`);
    if (checkbox) {
        const row = checkbox.closest('tr');
        if (row) row.classList.toggle('row-selected', estudiantesSeleccionados.has(studentId));
    }

    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const bar = document.getElementById('bulkActionsBar');
    const countText = document.getElementById('selectedCountText');
    const idsSeleccionados = normalizarSeleccionVisible();
    const count = idsSeleccionados.length;

    if (count > 0) {
        bar.classList.add('visible');
        bar.style.display = 'flex'; // Force display
        countText.textContent = `${count} seleccionado${count > 1 ? 's' : ''}`;
    } else {
        bar.classList.remove('visible');
        bar.style.display = 'none';
    }

    actualizarEstadoBotonGenerarLote();
}

function tieneFiltroCategoriaSeleccionado() {
    const categoriaId = document.getElementById('filterCategoria')?.value || '';
    return String(categoriaId).trim() !== '';
}

function actualizarEstadoBotonGenerarLote() {
    const btn = document.getElementById('btnGenerarLote');
    if (!btn) return;

    const habilitado = tieneFiltroCategoriaSeleccionado();
    btn.disabled = !habilitado;
    btn.style.opacity = habilitado ? '1' : '0.55';
    btn.style.cursor = habilitado ? 'pointer' : 'not-allowed';
    btn.title = habilitado
        ? 'Generar certificados para los estudiantes seleccionados'
        : 'Primero selecciona una categoría en el filtro';
}

function deseleccionarTodos() {
    estudiantesSeleccionados.clear();
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
    renderTabla();
    updateBulkActionsBar();
}

function obtenerItemsGenerables(estudianteIds) {
    const categoriaId = document.getElementById('filterCategoria')?.value || '';
    const periodoId = document.getElementById('filterPeriodo')?.value || '';
    const items = [];
    const seen = new Set();

    estudianteIds.forEach(estudianteId => {
        const est = estudiantesData.find(e => String(e.id) === String(estudianteId));
        if (!est || !Array.isArray(est.categorias)) return;

        est.categorias.forEach(cat => {
            if (categoriaId && String(cat.id) !== String(categoriaId)) return;
            if (periodoId && String(cat.periodo_id) !== String(periodoId)) return;

            const aprobado = Number(cat.cert_aprobado) === 1;
            const generado = Number(cat.cert_generado) === 1;
            if (!aprobado || generado) return;

            const key = `${est.id}_${cat.id}_${cat.periodo_id == null ? 'null' : cat.periodo_id}`;
            if (seen.has(key)) return;
            seen.add(key);

            items.push({
                key,
                estudiante_id: est.id,
                estudiante_nombre: est.nombre,
                estudiante_cedula: est.cedula || '',
                es_menor: Number(est.es_menor) === 1,
                representante_cedula: est.representante_cedula || '',
                categoria_id: cat.id,
                categoria_nombre: cat.nombre || 'Sin categoría',
                periodo_id: cat.periodo_id,
                periodo_nombre: cat.periodo || 'Sin período',
                periodo_fecha_inicio: cat.fecha_inicio || '',
                es_destacado: Number(cat.es_destacado) === 1
            });
        });
    });

    return items;
}

function getGenLotePeriodoKey(periodoId) {
    return periodoId == null || String(periodoId).trim() === '' ? 'null' : String(periodoId).trim();
}

function getGenLotePeriodoKeyFromItem(item) {
    return getGenLotePeriodoKey(item?.periodo_id);
}

function obtenerPeriodosGeneracionLote() {
    const map = new Map();

    generacionLoteItems.forEach(item => {
        const key = getGenLotePeriodoKeyFromItem(item);
        if (!map.has(key)) {
            map.set(key, {
                id: key,
                nombre: item.periodo_nombre || 'Sin período',
                fecha_inicio: item.periodo_fecha_inicio || '',
                total: 0
            });
        }
        map.get(key).total += 1;
    });

    return Array.from(map.values()).sort((a, b) => {
        const fa = String(a.fecha_inicio || '').trim();
        const fb = String(b.fecha_inicio || '').trim();
        const aHasDate = /^\d{4}-\d{2}-\d{2}$/.test(fa);
        const bHasDate = /^\d{4}-\d{2}-\d{2}$/.test(fb);

        if (aHasDate && bHasDate && fa !== fb) return fa < fb ? -1 : 1;
        if (aHasDate && !bHasDate) return -1;
        if (!aHasDate && bHasDate) return 1;

        const na = String(a.nombre || '');
        const nb = String(b.nombre || '');
        return na.localeCompare(nb, 'es', { sensitivity: 'base', numeric: true });
    });
}

function prepararFiltroPeriodoGeneracionLote(defaultPeriodo = '') {
    const container = document.getElementById('genLoteListTabsContainer');
    const select = document.getElementById('filterPeriodoGenLote');
    const periodos = obtenerPeriodosGeneracionLote();

    if (!periodos.length) {
        generacionLotePeriodoFiltro = '';
        if (container) container.innerHTML = '';
        if (select) {
            select.innerHTML = '<option value="">Sin períodos</option>';
            select.disabled = true;
        }
        return periodos;
    }

    const keys = new Set(periodos.map(p => String(p.id)));
    const sugerido = String(defaultPeriodo || '').trim();
    if (sugerido && keys.has(sugerido)) {
        generacionLotePeriodoFiltro = sugerido;
    }
    if (!keys.has(String(generacionLotePeriodoFiltro || ''))) {
        generacionLotePeriodoFiltro = String(periodos[0].id);
    }

    if (select) {
        select.disabled = false;
        select.innerHTML = periodos.map(periodo => `
            <option value="${escapeHtml(String(periodo.id))}">
                ${escapeHtml(periodo.nombre)}
            </option>
        `).join('');
        select.value = String(generacionLotePeriodoFiltro);
    }

    if (container) {
        const tabsHtml = periodos.map(periodo => {
            const isActive = String(periodo.id) === String(generacionLotePeriodoFiltro);
            return `
                <button
                    type="button"
                    class="gen-lote-period-slide ${isActive ? 'active' : ''}"
                    data-periodo-id="${escapeHtml(String(periodo.id))}"
                    onclick="cambiarPeriodoGeneracionLote(this.dataset.periodoId)"
                >
                    <span class="gen-lote-period-name">${escapeHtml(periodo.nombre)}</span>
                    <span class="gen-lote-period-count">${periodo.total}</span>
                </button>
            `;
        }).join('');

        container.innerHTML = `
            <div class="gen-lote-period-slides" role="tablist" aria-label="Períodos de generación">
                ${tabsHtml}
            </div>
        `;
    }

    return periodos;
}

function cambiarPeriodoGeneracionLote(periodoId) {
    const nextPeriodo = String(periodoId || '').trim();
    if (!nextPeriodo || nextPeriodo === String(generacionLotePeriodoFiltro || '')) return;

    generacionLotePeriodoFiltro = nextPeriodo;
    currentPageGenLote = 1;
    renderGeneracionLoteTabla();
}

function getGeneracionLoteFiltrados() {
    const q = (generacionLoteBusqueda || '').toLowerCase().trim();
    const periodoFiltro = String(generacionLotePeriodoFiltro || '').trim();

    return generacionLoteItems.filter(item => {
        if (periodoFiltro && getGenLotePeriodoKeyFromItem(item) !== periodoFiltro) {
            return false;
        }

        if (q) {
            const nombre = (item.estudiante_nombre || '').toLowerCase();
            const cedula = String(item.estudiante_cedula || '').toLowerCase();
            if (!nombre.includes(q) && !cedula.includes(q)) return false;
        }

        if (generacionLoteFiltro === 'representante') {
            return !!String(item.representante_cedula || '').trim();
        }
        if (generacionLoteFiltro === 'mayores') {
            return !item.es_menor;
        }
        if (generacionLoteFiltro === 'destacados') {
            return item.es_destacado;
        }
        return true;
    });
}

function updateBulkActionsBarGeneracionLote() {
    const bar = document.getElementById('bulkActionsGenLote');
    const count = document.getElementById('selectedCountGenLote');
    if (!bar || !count) return;

    const selected = generacionLoteSeleccion.size;
    if (selected > 0) {
        bar.style.display = 'flex';
        count.textContent = String(selected);
    } else {
        bar.style.display = 'none';
        count.textContent = '0';
    }
}

function actualizarResumenGeneracionLoteFooter() {
    const seleccionados = generacionLoteItems.filter(i => generacionLoteSeleccion.has(i.key));

    const totalEl = document.getElementById('genLoteCount');
    const destacadosEl = document.getElementById('genLoteDestacados');
    const menoresEl = document.getElementById('genLoteMenores');
    const representantesEl = document.getElementById('genLoteRepresentantes');

    if (totalEl) totalEl.textContent = String(seleccionados.length);
    if (destacadosEl) destacadosEl.textContent = String(seleccionados.filter(i => i.es_destacado).length);
    if (menoresEl) menoresEl.textContent = String(seleccionados.filter(i => i.es_menor).length);
    if (representantesEl) representantesEl.textContent = String(seleccionados.filter(i => String(i.representante_cedula || '').trim()).length);
}

function updatePaginacionGeneracionLote(totalItems, pageItemsCount, startIdx, endIdx) {
    const footer = document.getElementById('paginationFooterGenLote');
    const pagStart = document.getElementById('pagStartGenLote');
    const pagEnd = document.getElementById('pagEndGenLote');
    const pagTotal = document.getElementById('pagTotalGenLote');
    const totalPages = document.getElementById('totalPagesGenLote');
    const pageInput = document.getElementById('pageInputGenLote');
    const firstBtn = document.getElementById('btnPageFirstGenLote');
    const prevBtn = document.getElementById('btnPagePrevGenLote');
    const nextBtn = document.getElementById('btnPageNextGenLote');
    const lastBtn = document.getElementById('btnPageLastGenLote');

    if (!footer || !pagStart || !pagEnd || !pagTotal || !totalPages || !pageInput) return;

    if (!totalItems) {
        footer.style.display = 'none';
        return;
    }

    footer.style.display = 'flex';
    pagStart.textContent = String(startIdx);
    pagEnd.textContent = String(endIdx);
    pagTotal.textContent = String(totalItems);
    totalPages.textContent = String(totalPagesGenLote);
    pageInput.value = String(currentPageGenLote);

    if (firstBtn) firstBtn.disabled = currentPageGenLote <= 1;
    if (prevBtn) prevBtn.disabled = currentPageGenLote <= 1;
    if (nextBtn) nextBtn.disabled = currentPageGenLote >= totalPagesGenLote;
    if (lastBtn) lastBtn.disabled = currentPageGenLote >= totalPagesGenLote;
}

function cambiarFilasPorPaginaGeneracionLote(val) {
    rowsPerPageGenLote = parseInt(val, 10);
    currentPageGenLote = 1;
    renderGeneracionLoteTabla();
}

function irPaginaGeneracionLote(pag) {
    if (pag === 'last') pag = totalPagesGenLote;
    pag = parseInt(pag, 10);
    if (Number.isNaN(pag)) pag = 1;
    if (pag < 1) pag = 1;
    if (pag > totalPagesGenLote) pag = totalPagesGenLote;
    currentPageGenLote = pag;
    renderGeneracionLoteTabla();
}

function prevPaginaGeneracionLote() {
    irPaginaGeneracionLote(currentPageGenLote - 1);
}

function nextPaginaGeneracionLote() {
    irPaginaGeneracionLote(currentPageGenLote + 1);
}

function irPaginaManualGeneracionLote(val) {
    irPaginaGeneracionLote(parseInt(val, 10));
}

function renderGeneracionLoteTabla() {
    const body = document.getElementById('genLoteBody');
    const resumen = document.getElementById('genLoteResumenText');
    const selectAll = document.getElementById('genLoteSelectAll');
    if (!body || !resumen || !selectAll) return;

    const periodos = prepararFiltroPeriodoGeneracionLote();

    if (!generacionLoteItems.length) {
        body.innerHTML = '<tr><td colspan="6" class="gen-empty">No hay estudiantes aprobados pendientes de generación para la selección actual.</td></tr>';
        resumen.textContent = 'Sin elementos generables';
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updatePaginacionGeneracionLote(0, 0, 0, 0);
        updateBulkActionsBarGeneracionLote();
        actualizarResumenGeneracionLoteFooter();
        return;
    }

    const filtrados = getGeneracionLoteFiltrados();
    lastFilteredGenLote = filtrados;

    const periodoActivo = periodos.find(p => String(p.id) === String(generacionLotePeriodoFiltro));
    const periodoLabel = periodoActivo ? periodoActivo.nombre : 'Período seleccionado';

    if (!filtrados.length) {
        body.innerHTML = '<tr><td colspan="6" class="gen-empty">No hay resultados con el filtro actual.</td></tr>';
        resumen.textContent = `0 registro(s) en ${periodoLabel}`;
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updatePaginacionGeneracionLote(0, 0, 0, 0);
        updateBulkActionsBarGeneracionLote();
        actualizarResumenGeneracionLoteFooter();
        return;
    }

    totalPagesGenLote = rowsPerPageGenLote === -1 ? 1 : Math.max(1, Math.ceil(filtrados.length / rowsPerPageGenLote));
    if (currentPageGenLote > totalPagesGenLote) currentPageGenLote = totalPagesGenLote;

    const start = rowsPerPageGenLote === -1 ? 0 : (currentPageGenLote - 1) * rowsPerPageGenLote;
    const end = rowsPerPageGenLote === -1 ? filtrados.length : start + rowsPerPageGenLote;
    const pageItems = filtrados.slice(start, end);

    body.innerHTML = pageItems.map((item, idx) => `
        <tr>
            <td><input type="checkbox" class="select-checkbox" ${generacionLoteSeleccion.has(item.key) ? 'checked' : ''} onchange="toggleGeneracionLoteItem('${item.key}')"></td>
            <td style="text-align:center; color:#64748b; font-weight:600;">${start + idx + 1}</td>
            <td>
                <strong>${escapeHtml(item.estudiante_nombre)}${item.es_destacado ? ' <i class="fas fa-star" style="color:#f59e0b;"></i>' : ''}</strong>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">${escapeHtml(item.categoria_nombre)}</div>
            </td>
            <td>${escapeHtml(item.estudiante_cedula || '-')}</td>
            <td>
                <span class="badge badge-categoria" style="display:inline-flex; width:auto; margin:0; background:#e8f0fe; color:#1f4f9a;">
                    <i class="fas fa-calendar-alt"></i> ${escapeHtml(item.periodo_nombre || 'Sin período')}
                </span>
            </td>
            <td style="text-align:center;">
                <button class="btn-table-delete" onclick="quitarItemGeneracionLote('${item.key}')" title="Quitar del lote" style="background:#fce8e6; color:#d93025; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">
                    <i class="fas fa-user-minus"></i>
                </button>
            </td>
        </tr>
    `).join('');

    const total = generacionLoteItems.length;
    const selected = generacionLoteSeleccion.size;
    const selectedFiltrados = filtrados.filter(item => generacionLoteSeleccion.has(item.key)).length;
    resumen.textContent = `${filtrados.length} registro(s) en ${periodoLabel} · ${total} en total`;
    selectAll.checked = selectedFiltrados > 0 && selectedFiltrados === filtrados.length;
    selectAll.indeterminate = selectedFiltrados > 0 && selectedFiltrados < filtrados.length;
    updatePaginacionGeneracionLote(filtrados.length, pageItems.length, pageItems.length ? start + 1 : 0, pageItems.length ? start + pageItems.length : 0);
    updateBulkActionsBarGeneracionLote();
    actualizarResumenGeneracionLoteFooter();
}

function toggleGeneracionLoteAll(checked) {
    const filtrados = getGeneracionLoteFiltrados();
    if (checked) {
        filtrados.forEach(item => generacionLoteSeleccion.add(item.key));
    } else {
        filtrados.forEach(item => generacionLoteSeleccion.delete(item.key));
    }
    renderGeneracionLoteTabla();
    cargarPreviewPlantillasGeneracion();
}

function toggleGeneracionLoteItem(key) {
    if (generacionLoteSeleccion.has(key)) generacionLoteSeleccion.delete(key);
    else generacionLoteSeleccion.add(key);
    renderGeneracionLoteTabla();
    cargarPreviewPlantillasGeneracion();
}

function filtrarGeneracionLote() {
    generacionLoteBusqueda = document.getElementById('searchInputGenLote')?.value || '';
    currentPageGenLote = 1;
    renderGeneracionLoteTabla();
}

function toggleDropdownFiltroGenLote() {
    const dropdown = document.getElementById('dropdownFiltroGenLote');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'none' || !dropdown.style.display ? 'block' : 'none';
}

function aplicarFiltroGeneracionLote(tipo) {
    generacionLoteFiltro = tipo;

    const labels = {
        todos: 'Todos',
        representante: 'Con Representante',
        mayores: 'Mayores de edad',
        destacados: 'Destacados'
    };

    const texto = document.getElementById('filtroTextoGenLote');
    if (texto) texto.textContent = labels[tipo] || 'Todos';

    const dropdown = document.getElementById('dropdownFiltroGenLote');
    if (dropdown) {
        dropdown.querySelectorAll('a').forEach(a => a.classList.remove('active'));
        const activo = dropdown.querySelector(`a[onclick="aplicarFiltroGeneracionLote('${tipo}')"]`);
        if (activo) activo.classList.add('active');
        dropdown.style.display = 'none';
    }

    currentPageGenLote = 1;
    renderGeneracionLoteTabla();
}

function quitarSeleccionadosGeneracionLote() {
    if (!generacionLoteSeleccion.size) return;
    generacionLoteItems = generacionLoteItems.filter(i => !generacionLoteSeleccion.has(i.key));
    generacionLoteSeleccion.clear();
    renderGeneracionLoteTabla();
    cargarPreviewPlantillasGeneracion();
}

function quitarItemGeneracionLote(key) {
    generacionLoteItems = generacionLoteItems.filter(i => i.key !== key);
    generacionLoteSeleccion.delete(key);
    renderGeneracionLoteTabla();
    cargarPreviewPlantillasGeneracion();
}

function switchGenLoteTab(tabId, btnEl = null) {
    const tabs = document.querySelectorAll('.gen-lote-tab-content');
    tabs.forEach(tab => {
        tab.classList.remove('active');
        tab.style.display = 'none';
    });

    const target = document.getElementById(tabId);
    if (target) {
        target.classList.add('active');
        target.style.display = 'block';
    }

    const tabButtons = document.querySelectorAll('.gen-lote-tab');
    tabButtons.forEach(button => {
        button.classList.remove('active');
        button.style.color = '#6b7280';
        button.style.borderBottom = '3px solid transparent';
    });

    const activeBtn = btnEl || Array.from(tabButtons).find(button => {
        const onClick = button.getAttribute('onclick') || '';
        return onClick.includes(`'${tabId}'`) || onClick.includes(`"${tabId}"`);
    });

    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.color = 'var(--color-grupo)';
        activeBtn.style.borderBottom = '3px solid var(--color-grupo)';
    }

    if (tabId === 'gen-lote-tab-preview') {
        cargarPreviewPlantillasGeneracion();
    }
}

function switchGenPreviewCategoriaTab(tabId, btnEl = null) {
    const container = document.getElementById('genLotePreviewPlantillas');
    if (!container) return;

    container.querySelectorAll('.gen-preview-tab-btn').forEach(button => {
        button.classList.remove('active');
        button.style.background = '#f8fafc';
        button.style.color = '#475569';
        button.style.borderColor = '#e2e8f0';
    });

    container.querySelectorAll('.gen-preview-tab-pane').forEach(pane => {
        pane.classList.remove('active');
        pane.style.display = 'none';
    });

    const activePane = container.querySelector(`#${tabId}`);
    if (activePane) {
        activePane.classList.add('active');
        activePane.style.display = 'block';
    }

    const activeBtn = btnEl || container.querySelector(`.gen-preview-tab-btn[data-target="${tabId}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = 'var(--color-grupo)';
        activeBtn.style.color = '#ffffff';
        activeBtn.style.borderColor = 'var(--color-grupo)';
    }
}

function formatSnapshotDate(snapshotValue) {
    if (!snapshotValue) return 'Sin snapshot generado';
    const normalized = String(snapshotValue).replace(' ', 'T');
    const d = new Date(normalized);
    if (Number.isNaN(d.getTime())) return String(snapshotValue);

    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const min = String(d.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

function obtenerSnapshotGrupoDesdeConfig(grupoId, plantillaId = null) {
    if (!grupoId) return null;

    try {
        const plantillaKey = plantillaId || 'system';
        const candidates = [
            `cce_snapshot_grupo_${grupoId}_${plantillaKey}`,
            `cce_snapshot_grupo_${grupoId}_last`
        ];

        for (const key of candidates) {
            const raw = localStorage.getItem(key);
            if (!raw) continue;

            const parsed = JSON.parse(raw);
            if (!parsed || !parsed.snapshot_data_url) continue;

            return {
                url: parsed.snapshot_data_url,
                fecha_creacion: parsed.generado_en || null
            };
        }
    } catch (e) {
        console.warn('No se pudo leer snapshot local de configuración de grupo:', e);
    }

    return null;
}

async function generarPreviewPlantillaUrl(tipo, id, plantillaId = null) {
    if (!tipo || !id) return null;

    try {
        const formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('id', String(id));
        formData.append('use_form_data', '0');
        if (plantillaId) formData.append('plantilla_id', String(plantillaId));

        const resp = await fetch(`../api/preview/index.php?v=${Date.now()}_${Math.random().toString(36).slice(2, 8)}`, {
            method: 'POST',
            body: formData
        });
        const json = await resp.json();
        if (json?.success && json?.preview_url) {
            return `${json.preview_url}?v=${Date.now()}`;
        }
    } catch (e) {
        console.warn('No se pudo generar preview dinámico:', e);
    }

    return null;
}

async function cargarPreviewPlantillasGeneracion() {
    const container = document.getElementById('genLotePreviewPlantillas');
    if (!container) return;

    const categoriasMap = new Map();
    if (typeof categoriasGrupo !== 'undefined' && Array.isArray(categoriasGrupo)) {
        categoriasGrupo.forEach(cat => {
            const categoriaId = String(cat.id || '').trim();
            if (!categoriaId || categoriasMap.has(categoriaId)) return;
            categoriasMap.set(categoriaId, {
                categoria_id: categoriaId,
                categoria_nombre: cat.nombre || 'Sin categoría',
                usar_plantilla_propia: Number(cat.usar_plantilla_propia || 0),
                plantilla_archivo: cat.plantilla_archivo || ''
            });
        });
    }

    // Fallback por compatibilidad: si no existe el catálogo del grupo, usar categorías del lote.
    if (categoriasMap.size === 0) {
        generacionLoteItems.forEach(i => {
            const categoriaId = String(i.categoria_id || '').trim();
            if (!categoriaId || categoriasMap.has(categoriaId)) return;
            categoriasMap.set(categoriaId, {
                categoria_id: categoriaId,
                categoria_nombre: i.categoria_nombre || 'Sin categoría',
                usar_plantilla_propia: Number(i.usar_plantilla_propia || 0),
                plantilla_archivo: i.plantilla_archivo || ''
            });
        });
    }

    if (categoriasMap.size === 0) {
        container.innerHTML = '<div class="gen-empty">No hay categorías registradas para este grupo.</div>';
        return;
    }

    container.innerHTML = '<div class="gen-empty">Cargando plantillas...</div>';

    const categorias = Array.from(categoriasMap.values());
    const diagnosticos = await Promise.all(categorias.map(async c => {
        try {
            const url = `../api/certificados/generar.php?action=verificar_plantilla&grupo_id=${grupoId}&categoria_id=${c.categoria_id}`;
            const resp = await fetch(url);
            const json = await resp.json();
            return { categoria: c, data: json?.diagnostico || null };
        } catch (e) {
            return { categoria: c, data: null };
        }
    }));

    const diagBase = diagnosticos.find(({ data }) => !!data) || null;
    const grupoPlantillaId = diagBase?.data?.plantilla_grupo?.id || null;
    const grupoSnapshot = diagBase?.data?.snapshot_grupo || null;
    const grupoSnapshotLocal = obtenerSnapshotGrupoDesdeConfig(grupoId, grupoPlantillaId);
    const grupoSnapshotUrl = grupoSnapshot?.url ? `${grupoSnapshot.url}?v=${Date.now()}` : null;
    const grupoSnapshotLocalUrl = grupoSnapshotLocal?.url || null;
    const grupoDynamicPreviewUrl = await generarPreviewPlantillaUrl('grupo', grupoId, grupoPlantillaId);
    const grupoPreviewUrl = grupoSnapshotLocalUrl || grupoDynamicPreviewUrl || grupoSnapshotUrl;

    const categoriasConPlantilla = diagnosticos.filter(({ categoria, data }) => {
        const tieneDiagnostico = !!data?.plantilla_categoria?.archivo;
        const categoriaMarcaPropia = Number(categoria?.usar_plantilla_propia || 0) === 1;
        const categoriaArchivoPropio = String(categoria?.plantilla_archivo || '').trim() !== '';
        return tieneDiagnostico || categoriaMarcaPropia || categoriaArchivoPropio;
    });

    const categoriasTabs = await Promise.all(categoriasConPlantilla.map(async ({ categoria, data }) => {
        const snap = data?.snapshot_categoria || null;
        const snapshotUrl = snap?.url ? `${snap.url}?v=${Date.now()}` : null;
        const dynamicPreviewUrl = await generarPreviewPlantillaUrl('categoria', categoria.categoria_id, data?.plantilla_categoria?.id || null);
        const previewUrl = dynamicPreviewUrl || snapshotUrl;

        return {
            id: `gen-preview-pane-cat-${categoria.categoria_id}`,
            tipo: 'categoria',
            label: categoria.categoria_nombre,
            src: previewUrl,
            snapshotDate: snap?.fecha_creacion || null,
            hasSnapshot: !!snap
        };
    }));

    const previewTabs = [
        {
            id: 'gen-preview-pane-grupo',
            tipo: 'grupo',
            label: 'Grupo',
            src: grupoPreviewUrl,
            snapshotDate: grupoSnapshotLocal?.fecha_creacion || grupoSnapshot?.fecha_creacion || null,
            hasSnapshot: !!(grupoSnapshotLocal || grupoSnapshot)
        },
        ...categoriasTabs
    ];

    const renderThumb = (src, textoVacio) => src
        ? `<div class="gen-thumb-box"><img src="${src}" alt="Plantilla" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="gen-thumb-fallback" style="display:none;">No disponible</div></div>`
        : `<div class="gen-thumb-box gen-thumb-empty">${textoVacio}</div>`;

    const tabsHtml = previewTabs.map((tab, idx) => {
        const tabId = tab.id;
        const isActive = idx === 0;
        const labelHtml = tab.tipo === 'grupo'
            ? '<i class="fas fa-layer-group" style="margin-right: 6px;"></i>Grupo'
            : escapeHtml(tab.label);
        return `
            <button
                type="button"
                class="gen-preview-tab-btn ${isActive ? 'active' : ''}"
                data-target="${tabId}"
                onclick="switchGenPreviewCategoriaTab('${tabId}', this)"
                style="
                    border: 1px solid ${isActive ? 'var(--color-grupo)' : '#e2e8f0'};
                    background: ${isActive ? 'var(--color-grupo)' : '#f8fafc'};
                    color: ${isActive ? '#ffffff' : '#475569'};
                    border-radius: 8px;
                    padding: 8px 12px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    white-space: nowrap;
                "
            >
                ${labelHtml}
            </button>
        `;
    }).join('');

    const panesHtml = previewTabs.map((tab, idx) => {
        const tabId = tab.id;
        const isActive = idx === 0;
        const esGrupo = tab.tipo === 'grupo';
        const vacio = esGrupo ? 'Sin previsualización de grupo' : 'Sin previsualización de categoría';
        const snapshotText = `Último snapshot: ${formatSnapshotDate(tab.snapshotDate)}`;

        return `
            <div
                id="${tabId}"
                class="gen-preview-tab-pane ${isActive ? 'active' : ''}"
                style="display: ${isActive ? 'block' : 'none'};"
            >
                ${renderThumb(tab.src, vacio)}
                <p class="gen-muted" style="margin-top: 8px;">${escapeHtml(snapshotText)}</p>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="gen-preview-tabs" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
            ${tabsHtml}
        </div>
        <div class="gen-preview-panes">
            ${panesHtml}
        </div>
    `;
}

function obtenerIdsSeleccionadosEnTabla() {
    return Array.from(
        document.querySelectorAll('#estudiantesBody .select-checkbox[data-id]:checked')
    )
        .filter(chk => {
            const row = chk.closest('tr');
            return !!row && window.getComputedStyle(row).display !== 'none';
        })
        .map(chk => String(chk.dataset.id || '').trim())
        .filter(Boolean);
}

function abrirModalGeneracionSeleccionActual() {
    if (!tieneFiltroCategoriaSeleccionado()) {
        mostrarNotificacion('Debes seleccionar una categoría en el filtro para generar certificados', 'warning');
        return;
    }

    const idsMarcados = normalizarSeleccionVisible();
    if (!idsMarcados.length) {
        mostrarNotificacion('Selecciona al menos un estudiante para generar certificados', 'error');
        return;
    }
    abrirModalGeneracionLote(idsMarcados);
}

function abrirModalGeneracionLote(estudianteIds = null) {
    // Modo estricto: solo abrir con IDs explícitos (fila o selección actual).
    // Evita cualquier fallback que pueda terminar cargando todos.
    let idsBase = Array.isArray(estudianteIds)
        ? estudianteIds.map(id => String(id)).filter(Boolean)
        : [];
    idsBase = Array.from(new Set(idsBase));

    if (!idsBase.length) {
        mostrarNotificacion('Selecciona al menos un estudiante para generar certificados', 'error');
        return;
    }

    const idsPermitidos = new Set(idsBase.map(id => String(id)));
    generacionLoteItems = obtenerItemsGenerables(idsBase)
        .filter(item => idsPermitidos.has(String(item.estudiante_id)));
    generacionLotePeriodoFiltro = '';
    const periodoPreferido = String(document.getElementById('filterPeriodo')?.value || '').trim();
    prepararFiltroPeriodoGeneracionLote(periodoPreferido);

    if (generacionLotePeriodoFiltro) {
        generacionLoteSeleccion = new Set(
            generacionLoteItems
                .filter(item => getGenLotePeriodoKeyFromItem(item) === String(generacionLotePeriodoFiltro))
                .map(i => i.key)
        );
    } else {
        generacionLoteSeleccion = new Set(generacionLoteItems.map(i => i.key));
    }

    generacionLoteFiltro = 'todos';
    generacionLoteBusqueda = '';
    currentPageGenLote = 1;
    rowsPerPageGenLote = parseInt(document.getElementById('rowsPerPageGenLote')?.value || '10', 10);

    const search = document.getElementById('searchInputGenLote');
    if (search) search.value = '';

    const textoFiltro = document.getElementById('filtroTextoGenLote');
    if (textoFiltro) textoFiltro.textContent = 'Todos';

    const dropdown = document.getElementById('dropdownFiltroGenLote');
    if (dropdown) {
        dropdown.style.display = 'none';
        dropdown.querySelectorAll('a').forEach(a => a.classList.remove('active'));
        const first = dropdown.querySelector('a[onclick="aplicarFiltroGeneracionLote(\'todos\')"]');
        if (first) first.classList.add('active');
    }

    const modal = document.getElementById('modalGeneracionLote');
    if (modal) modal.classList.add('active');

    const listaBtn = document.querySelector('.gen-lote-tab[onclick*="gen-lote-tab-lista"]');
    switchGenLoteTab('gen-lote-tab-lista', listaBtn);
    renderGeneracionLoteTabla();
    cargarPreviewPlantillasGeneracion();
}

document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('dropdownFiltroGenLote');
    const btn = document.getElementById('btnFiltroGenLote');
    if (!dropdown || !btn) return;

    if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

async function confirmarGeneracionLote() {
    const btn = document.getElementById('btnConfirmGeneracionLote');
    const seleccionados = generacionLoteItems.filter(i => generacionLoteSeleccion.has(i.key));

    if (!seleccionados.length) {
        mostrarNotificacion('No hay elementos seleccionados para generar', 'error');
        return;
    }

    const fecha = new Date().toISOString().slice(0, 10);
    const lotes = new Map();

    seleccionados.forEach(item => {
        const key = `${item.categoria_id}_${item.periodo_id == null ? 'null' : item.periodo_id}`;
        if (!lotes.has(key)) {
            lotes.set(key, {
                categoria_id: item.categoria_id,
                periodo_id: item.periodo_id,
                estudiantes_ids: new Set()
            });
        }
        lotes.get(key).estudiantes_ids.add(item.estudiante_id);
    });

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    }

    let totalExitosos = 0;
    let totalErrores = 0;

    try {
        for (const lote of lotes.values()) {
            const payload = {
                action: 'generar_batch',
                grupo_id: grupoId,
                categoria_id: lote.categoria_id,
                periodo_id: lote.periodo_id,
                estudiantes_ids: Array.from(lote.estudiantes_ids),
                fecha
            };

            const response = await fetch('../api/certificados/generar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (!result.success) {
                totalErrores += Array.from(lote.estudiantes_ids).length;
                if (result.message) console.error('Error lote:', result.message);
                continue;
            }

            totalExitosos += Number(result.resumen?.exitosos || 0);
            totalErrores += Number(result.resumen?.errores || 0);
        }

        if (totalExitosos > 0) {
            mostrarNotificacion(`${totalExitosos} certificado(s) generado(s) correctamente`, 'success');
        }
        if (totalErrores > 0) {
            mostrarNotificacion(`${totalErrores} registro(s) no se pudieron generar`, 'warning');
        }
        if (totalExitosos === 0 && totalErrores === 0) {
            mostrarNotificacion('No se generaron certificados', 'info');
        }

        cerrarModal(null, 'modalGeneracionLote');
        deseleccionarTodos();
        await cargarEstudiantes();
    } catch (error) {
        console.error(error);
        mostrarNotificacion('Error al generar certificados por lote', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cogs"></i> Generar Certificados';
        }
    }
}

// ========== CERTIFICATES MODAL ==========

async function verCertificados(estudianteId) {
    currentStudentId = estudianteId;
    const estudiante = estudiantesData.find(e => e.id == estudianteId);

    const modal = document.getElementById('certificadosModal');
    const content = document.getElementById('certificadosContent');
    const title = document.getElementById('certificadosModalTitle');
    const footer = document.getElementById('certificadosModalFooter');

    title.innerHTML = `<i class="fas fa-certificate"></i> Certificados de ${escapeHtml(estudiante.nombre)}`;
    content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Cargando certificados...</div>';

    modal.classList.add('active');

    try {
        const response = await fetch(`../api/estudiantes/index.php?action=get_details&id=${estudianteId}&t=${new Date().getTime()}`);
        const data = await response.json();

        if (data.success && data.certificados) {
            if (data.certificados.length === 0) {
                footer.innerHTML = '';
                content.innerHTML = `
                    <div class="empty-state" style="padding: 30px;">
                        <i class="fas fa-certificate" style="font-size: 48px; color: #cbd5e0; margin-bottom: 15px;"></i>
                        <p>Este estudiante no tiene certificados generados.</p>
                    </div>
                `;
                return;
            }

            selectedCerts.clear();
            currentStudentIdForCerts = estudianteId;

            footer.innerHTML = `
                 <button id="btnDownloadCerts" onclick="manejarDescarga(${estudianteId})" class="btn btn-primary" style="background: #2ecc71; color: white; padding: 10px 20px; border-radius: 8px; border:none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-pdf"></i> Descargar Todos (PDF Unificado)
                </button>
            `;

            const certsByPeriod = {};
            const periodOrder = [];

            data.certificados.forEach(cert => {
                const pName = cert.periodo_nombre || 'OTROS';
                if (!certsByPeriod[pName]) {
                    certsByPeriod[pName] = [];
                    periodOrder.push(pName);
                }
                certsByPeriod[pName].push(cert);
            });

            let tabsHeaderHtml = '<div class="cert-tabs-container" id="certTabsHeader">';
            let tabsContentHtml = '<div id="certTabsContent">';

            periodOrder.forEach((pName, index) => {
                const isActive = index === 0 ? 'active' : '';
                tabsHeaderHtml += `<div class="cert-tab-btn ${isActive}" onclick="switchCertTab(${index})">${escapeHtml(pName)}</div>`;

                const certsHtml = certsByPeriod[pName].map(cert => {
                    const formatDate = (d) => {
                        if (!d) return '--/--/----';
                        try { return d.split(' ')[0].split('-').reverse().join('/'); } catch (e) { return d; }
                    };

                    let ultimaGen = formatDate(cert.fecha);
                    if (cert.fechas_generacion) {
                        try {
                            const fechas = JSON.parse(cert.fechas_generacion);
                            if (Array.isArray(fechas) && fechas.length > 0) ultimaGen = formatDate(fechas[fechas.length - 1]);
                        } catch (e) { }
                    }

                    const matriculaDate = data.estudiante.fecha_creacion ? formatDate(data.estudiante.fecha_creacion) : '--/--/----';
                    const genDate = formatDate(cert.fecha);

                    const catColor = cert.categoria_color || '#e67e22';
                    const iconClass = cert.categoria_icono || 'fas fa-certificate';

                    let iconHtml = '';
                    if (iconClass.indexOf('fa-') > -1) {
                        iconHtml = `<i class="${iconClass}" style="color: ${catColor}"></i>`;
                    } else {
                        iconHtml = `<span style="font-size: 38px; line-height: 1;">${iconClass}</span>`;
                    }

                    return `
                    <div class="cert-card-new" id="cert-card-${cert.codigo}">
                        <div class="cert-card-period">${escapeHtml(pName)}</div>
                        <div class="cert-card-icon-section">
                            <div class="cert-card-title-header">${escapeHtml(cert.categoria_nombre)}</div>
                            <div class="cert-card-icon-wrapper">${iconHtml}</div>
                        </div>
                        <div class="cert-card-center">
                            <div class="cert-card-details">
                                <div class="cert-detail-row">
                                    <div class="cert-detail-label">Fecha Matriculación</div>
                                    <div class="cert-detail-value"><i class="far fa-calendar-alt"></i> ${matriculaDate}</div>
                                </div>
                                <div class="cert-detail-row">
                                    <div class="cert-detail-label">Gen. Certificado</div>
                                    <div class="cert-detail-value"><i class="far fa-calendar-check"></i> ${genDate}</div>
                                </div>
                                <div class="cert-detail-row">
                                    <div class="cert-detail-label">Ultima Generación</div>
                                    <div class="cert-detail-value"><i class="fas fa-history"></i> ${ultimaGen}</div>
                                </div>
                            </div>
                            <div class="cert-code-footer">${cert.codigo}</div>
                        </div>
                        <div class="cert-card-actions">
                            <div class="cert-action-btn cert-select-check" onclick="toggleCertSelection(this, '${cert.codigo}')" title="Seleccionar">
                                <i class="${selectedCerts.has(cert.codigo) ? 'fas fa-check-square' : 'far fa-square'}"></i>
                            </div>
                            <div class="cert-action-btn" onclick="window.open('../api/certificados/index.php?action=descargar_certificado&certificado_id=${cert.id}&formato=pdf', '_blank')" title="Descargar">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="cert-action-btn" onclick='abrirHistorial(${JSON.stringify(cert.fechas_generacion || "[]")})' title="Historial">
                                <i class="fas fa-history"></i>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');

                tabsContentHtml += `<div class="cert-tab-pane ${isActive}" id="cert-pane-${index}"><div class="cert-list">${certsHtml}</div></div>`;
            });

            tabsHeaderHtml += '</div>';
            tabsContentHtml += '</div>';

            content.innerHTML = tabsHeaderHtml + tabsContentHtml;
        } else {
            content.innerHTML = '<div class="alert alert-danger" style="text-align:center; padding:20px;">Error al cargar certificados</div>';
        }
    } catch (error) {
        console.error(error);
        content.innerHTML = '<div class="alert alert-danger" style="text-align:center; padding:20px;">Error de conexión</div>';
    }
}

function cerrarCertificadosModal() {
    document.getElementById('certificadosModal').classList.remove('active');
}

function switchCertTab(index) {
    const headers = document.getElementById('certTabsHeader').children;
    for (let btn of headers) btn.classList.remove('active');
    headers[index].classList.add('active');

    const panes = document.getElementById('certTabsContent').children;
    for (let pane of panes) pane.classList.remove('active');
    panes[index].classList.add('active');
}

function toggleCertSelection(element, id) {
    if (typeof element !== 'object') {
        id = element;
        const card = document.getElementById('cert-card-' + id);
        if (card) element = card.querySelector('.cert-select-check');
        else return;
    }

    const icon = element.querySelector('i');
    const card = document.getElementById('cert-card-' + id);

    if (selectedCerts.has(id)) {
        selectedCerts.delete(id);
        if (card) card.classList.remove('selected');
        if (icon) icon.className = 'far fa-square';
    } else {
        selectedCerts.add(id);
        if (card) card.classList.add('selected');
        if (icon) icon.className = 'fas fa-check-square';
    }
    updateDownloadButton();
}

function updateDownloadButton() {
    const btn = document.getElementById('btnDownloadCerts');
    if (!btn) return;

    if (selectedCerts.size > 0) {
        btn.innerHTML = `<i class="fas fa-file-pdf"></i> Descargar Seleccionados (${selectedCerts.size})`;
    } else {
        btn.innerHTML = `<i class="fas fa-file-pdf"></i> Descargar Todos (PDF Unificado)`;
    }
}

function manejarDescarga(estudianteId) {
    if (selectedCerts.size > 0) {
        const codes = Array.from(selectedCerts).join(',');
        window.open(`../certificados/consolidado.php?estudiante_id=${estudianteId}&codigos=${codes}`, '_blank');
    } else {
        window.location.href = `../certificados/consolidado.php?estudiante_id=${estudianteId}`;
    }
}

// ========== EDIT & DELETE ==========

function formatearFechaModalGrupo(fechaRaw) {
    const raw = String(fechaRaw || '').trim();
    if (!raw || raw === '0000-00-00') return 's/f';

    const iso = raw.length >= 10 ? raw.substring(0, 10) : raw;
    const parts = iso.split('-');
    if (parts.length === 3) {
        const [y, m, d] = parts;
        if (y && m && d) return `${d}/${m}/${y}`;
    }
    return raw;
}

function renderIconoCategoriaModal(iconoRaw) {
    const icono = String(iconoRaw || '').trim();
    if (!icono) return '📁';
    if (icono.includes('fa-')) return `<i class="${escapeHtml(icono)}"></i>`;
    return escapeHtml(icono);
}

function agruparCategoriasModalGrupo(categoriasRaw) {
    const map = {};
    (Array.isArray(categoriasRaw) ? categoriasRaw : []).forEach(cat => {
        const key = String(cat.id || cat.nombre || '').trim().toLowerCase() || 'sin_categoria';
        if (!map[key]) {
            map[key] = {
                id: cat.id || null,
                nombre: cat.nombre || 'Sin categoría',
                icono: cat.icono || '📁',
                periodos: [],
                _periodosMap: {}
            };
        }

        const periodoNombre = cat.periodo || 'Sin período';
        const periodoKey = `${cat.periodo_id ?? 'null'}::${periodoNombre}`;
        const periodoExistente = map[key]._periodosMap[periodoKey];
        const esDestacado = Number(cat.es_destacado || 0) === 1;

        if (!periodoExistente) {
            const periodoObj = {
                nombre: periodoNombre,
                fecha_inicio: cat.fecha_inicio || '0000-00-00',
                fecha_matricula: cat.fecha_matricula || '',
                es_destacado: esDestacado
            };
            map[key].periodos.push(periodoObj);
            map[key]._periodosMap[periodoKey] = periodoObj;
        } else {
            if (esDestacado) periodoExistente.es_destacado = true;
            if (!periodoExistente.fecha_matricula && cat.fecha_matricula) {
                periodoExistente.fecha_matricula = cat.fecha_matricula;
            }
            if ((!periodoExistente.fecha_inicio || periodoExistente.fecha_inicio === '0000-00-00') && cat.fecha_inicio) {
                periodoExistente.fecha_inicio = cat.fecha_inicio;
            }
        }
    });

    return Object.values(map).map(cat => {
        cat.periodos.sort((a, b) => {
            const fa = String(a.fecha_inicio || '0000-00-00');
            const fb = String(b.fecha_inicio || '0000-00-00');
            if (fa === fb) return String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' });
            return fa < fb ? -1 : 1;
        });
        delete cat._periodosMap;
        return cat;
    }).sort((a, b) => String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' }));
}

function renderResumenCategoriasModalGrupo(categoriasRaw) {
    const container = document.getElementById('editCategoriasResumenGroup');
    if (!container) return;

    const categorias = agruparCategoriasModalGrupo(categoriasRaw);
    if (!categorias.length) {
        container.innerHTML = '<div class="edit-categorias-empty"><i class="fas fa-folder-open"></i> Este estudiante no tiene categorías registradas en este grupo.</div>';
        return;
    }

    const buildPeriodoChip = (p) => `
        <span class="edit-periodo-chip${p.es_destacado ? ' destacado' : ''}">
            <i class="far fa-calendar-alt"></i> ${escapeHtml(p.nombre || 'Sin período')}
            <span class="edit-periodo-reg">Reg: ${escapeHtml(formatearFechaModalGrupo(p.fecha_matricula || ''))}</span>
            ${p.es_destacado ? '<i class="fas fa-star" style="color:#f59e0b;"></i>' : ''}
        </span>
    `;

    const html = categorias.map(cat => {
        const periodos = Array.isArray(cat.periodos) ? cat.periodos : [];
        const primerPeriodo = periodos[0] || { nombre: 'Sin período', fecha_matricula: '', es_destacado: false };
        const periodosAdicionales = periodos.slice(1);
        const mostrarDespliegue = periodosAdicionales.length > 0;

        return `
            <div class="edit-cat-item">
                <div class="edit-cat-main">
                    <div class="edit-cat-main-left">
                        <div class="edit-cat-header">
                            <span>${renderIconoCategoriaModal(cat.icono)}</span>
                            <span>${escapeHtml(cat.nombre)}</span>
                        </div>
                    </div>
                    <div class="edit-cat-main-right">
                        ${buildPeriodoChip(primerPeriodo)}
                        ${mostrarDespliegue ? `
                            <details class="edit-periodos-dropdown">
                                <summary class="edit-periodos-summary" title="Ver períodos adicionales">
                                    <i class="fas fa-chevron-down edit-periodos-chevron"></i>
                                </summary>
                                <div class="edit-periodos-content">
                                    ${periodosAdicionales.map(buildPeriodoChip).join('')}
                                </div>
                            </details>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = html;
}

async function editarEstudiante(id) {
    let est = estudiantesData.find(e => String(e.id) === String(id));

    if (!est) {
        try {
            const response = await fetch(`../api/estudiantes/index.php?action=get&id=${encodeURIComponent(id)}`);
            const data = await response.json();

            if (!response.ok || !data.success || !data.estudiante) {
                throw new Error(data.message || 'No se pudo cargar la información del representante');
            }

            est = data.estudiante;
        } catch (error) {
            console.error(error);
            mostrarError(error.message || 'No se pudo abrir el formulario de edición');
            return;
        }
    }

    document.getElementById('edit_estudiante_id').value = est.id;
    document.getElementById('edit_nombre').value = est.nombre;
    document.getElementById('edit_cedula').value = est.cedula || '';

    let celular = est.celular || '';
    if (celular.startsWith('+593')) celular = celular.substring(4);
    else if (celular.startsWith('0')) celular = celular.substring(1);
    document.getElementById('edit_celular').value = celular;

    document.getElementById('edit_email').value = est.email || '';
    document.getElementById('edit_fecha_nacimiento').value = est.fecha_nacimiento || '';
    document.getElementById('edit_destacado').checked = Number(est.destacado ?? est.es_destacado ?? 0) === 1;

    toggleEditRepresentante();

    // Fill representative data even if not visible yet
    document.getElementById('edit_rep_nombre').value = est.representante_nombre || '';
    document.getElementById('edit_rep_cedula').value = est.representante_cedula || '';

    let repCelular = est.representante_celular || '';
    if (repCelular.startsWith('+593')) repCelular = repCelular.substring(4);
    else if (repCelular.startsWith('0')) repCelular = repCelular.substring(1);
    document.getElementById('edit_rep_celular').value = repCelular;

    document.getElementById('edit_rep_email').value = est.representante_email || '';
    renderResumenCategoriasModalGrupo(est.categorias || []);

    document.getElementById('editEstudianteModal').classList.add('active');
}

function cerrarEditModal() {
    document.getElementById('editEstudianteModal').classList.remove('active');
}

function eliminarEstudiante(id) {
    estudianteIdAEliminar = id;
    const est = estudiantesData.find(e => e.id == id);
    document.getElementById('deleteEstudianteNombre').textContent = est ? est.nombre : 'este estudiante';
    document.getElementById('deleteConfirmModal').classList.add('active');
}

function cerrarDeleteModal() {
    estudianteIdAEliminar = null;
    document.getElementById('deleteConfirmModal').classList.remove('active');
}

async function confirmarEliminarEstudiante() {
    if (!estudianteIdAEliminar) return;

    try {
        const response = await fetch('../api/estudiantes/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'quitar_de_grupo', id: estudianteIdAEliminar, grupo_id: grupoId })
        });
        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Estudiante quitado del grupo', 'success');
            cargarEstudiantes();
        } else {
            mostrarError(result.message || 'Error al eliminar');
        }
    } catch (error) {
        console.error(error);
        mostrarError('Error de conexión');
    } finally {
        cerrarDeleteModal();
    }
}

async function guardarEdicionEstudiante(e) {
    e.preventDefault();
    const form = document.getElementById('editEstudianteForm');
    const data = new FormData(form);
    const jsonData = Object.fromEntries(data.entries());

    jsonData.es_menor = toggleEditRepresentante() ? 1 : 0;
    jsonData.destacado = document.getElementById('edit_destacado').checked ? 1 : 0;

    const celularElem = document.getElementById('edit_celular');
    if (celularElem.value.trim()) {
        jsonData.celular = '+593' + celularElem.value.trim();
    }

    const repCelularElem = document.getElementById('edit_rep_celular');
    if (repCelularElem.value.trim()) {
        jsonData.representante_celular = '+593' + repCelularElem.value.trim();
    }

    if (jsonData.cedula && !validarCedulaEcuatoriana(jsonData.cedula)) {
        alert('La cédula ingresada no es válida');
        return;
    }

    jsonData.action = 'update';

    try {
        const response = await fetch('../api/estudiantes/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(jsonData)
        });
        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Estudiante actualizado correctamente', 'success');
            cerrarEditModal();
            cargarEstudiantes();
        } else {
            mostrarError(result.message || 'Error al actualizar');
        }
    } catch (error) {
        console.error(error);
        mostrarError('Error de conexión');
    }
}

// ========== HELPERS ==========

function toggleEditRepresentante() {
    const fechaVal = document.getElementById('edit_fecha_nacimiento').value;
    let esMenor = false;

    if (fechaVal) {
        const hoy = new Date();
        const cumple = new Date(fechaVal);
        let edad = hoy.getFullYear() - cumple.getFullYear();
        const m = hoy.getMonth() - cumple.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < cumple.getDate())) {
            edad--;
        }
        if (edad < 18) esMenor = true;
    }

    const repSection = document.getElementById('editRepresentanteSection');
    repSection.style.display = esMenor ? 'block' : 'none';
    return esMenor;
}

function validarCedulaEcuatoriana(cedula) {
    if (!/^[0-9]{10}$/.test(cedula)) return false;
    const provincia = parseInt(cedula.substring(0, 2));
    if (provincia < 1 || provincia > 24) return false;
    const tercerDigito = parseInt(cedula.charAt(2));
    if (tercerDigito > 6) return false;
    const coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    let suma = 0;
    for (let i = 0; i < 9; i++) { let val = parseInt(cedula.charAt(i)) * coef[i]; if (val > 9) val -= 9; suma += val; }
    const digito = (10 - (suma % 10)) % 10;
    return digito === parseInt(cedula.charAt(9));
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const notif = document.createElement('div');
    notif.className = `notification ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : tipo === 'error' ? 'times' : 'info'}-circle"></i> ${mensaje}`;
    document.body.appendChild(notif);

    setTimeout(() => notif.remove(), 4000);
}

function mostrarError(mensaje) {
    alert(mensaje);
}

async function verReferencias(estudianteId) {
    const modal = document.getElementById('modalReferencias');
    const body = document.getElementById('modalReferenciasBody');
    const title = document.getElementById('modalReferenciasTitle');

    if (!modal || !body || !title) return;

    body.innerHTML = `
        <div style="text-align:center; padding:24px; color:#7f8c8d;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i>
            <p style="margin:0;">Cargando referencias...</p>
        </div>
    `;
    modal.classList.add('active');

    try {
        const response = await fetch(`../api/categorias/estudiantes.php?action=listar_referencias&estudiante_id=${encodeURIComponent(estudianteId)}`);
        const data = await response.json();

        title.innerHTML = `<i class="fas fa-address-book"></i> Referencias de ${escapeHtml(data.estudiante_nombre || 'Estudiante')}`;

        if (!data.success || !Array.isArray(data.referencias) || data.referencias.length === 0) {
            body.innerHTML = `
                <div style="text-align:center; padding:30px; color:#95a5a6;">
                    <i class="fas fa-user-slash" style="font-size:48px; margin-bottom:15px; opacity:0.3;"></i>
                    <p style="font-size:15px; margin:0;">No se han registrado referencias para este estudiante.</p>
                </div>
            `;
            return;
        }

        let html = '<div style="display:flex; flex-direction:column; gap:10px;">';
        data.referencias.forEach((ref, index) => {
            const telefonoRaw = String(ref.telefono || '').trim();
            const telefonoHtml = telefonoRaw
                ? `<a href="https://wa.me/${formatPhoneLink(telefonoRaw)}" target="_blank" class="contact-chip contact-chip-whatsapp"><i class="fab fa-whatsapp"></i> ${escapeHtml(formatPhoneDisplay(telefonoRaw))}</a>`
                : '<span style="color:#bdc3c7; font-size:12px;">Sin teléfono</span>';

            const relacionHtml = ref.relacion
                ? `<span class="badge" style="background:#eef2ff; color:#4f46e5; font-size:11px; border:1px solid #dbeafe;">${escapeHtml(ref.relacion)}</span>`
                : '';

            html += `
                <div style="display:flex; gap:12px; align-items:flex-start; border:1px solid #e8ecf0; border-radius:12px; padding:10px 12px; background:#fff;">
                    <div style="width:28px; height:28px; border-radius:50%; background:#eef2ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;">${index + 1}</div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; color:#334155; margin-bottom:6px;">${escapeHtml(ref.nombre || 'Sin nombre')}</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            ${telefonoHtml}
                            ${relacionHtml}
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        body.innerHTML = html;
    } catch (error) {
        console.error(error);
        body.innerHTML = `
            <div style="text-align:center; padding:24px; color:#e74c3c;">
                <i class="fas fa-exclamation-triangle" style="font-size:24px; margin-bottom:8px;"></i>
                <p style="margin:0;">Error al cargar las referencias.</p>
            </div>
        `;
    }
}

function formatPhoneDisplay(num) {
    if (!num) return '';
    let clean = num.replace(/\D/g, '');
    if (clean.startsWith('593')) clean = clean.substring(3);
    if (!clean.startsWith('0')) clean = '0' + clean;
    return clean;
}

function formatPhoneLink(num) {
    if (!num) return '';
    let clean = num.replace(/\D/g, '');
    if (clean.startsWith('0')) clean = clean.substring(1);
    if (!clean.startsWith('593')) clean = '593' + clean;
    return clean;
}

function validarCedulaInput(input) { input.value = input.value.replace(/[^0-9]/g, ''); }
function validarCelularInput(input) { input.value = input.value.replace(/[^0-9]/g, ''); }

function verificarCedula(input) {
    const cedula = input.value.trim();
    if (cedula.length > 0) {
        if (!validarCedulaEcuatoriana(cedula)) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
            if (input.nextElementSibling) input.nextElementSibling.style.display = 'block';
        } else {
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            if (input.nextElementSibling) input.nextElementSibling.style.display = 'none';
        }
    } else {
        input.classList.remove('input-error', 'input-valid');
        if (input.nextElementSibling) input.nextElementSibling.style.display = 'none';
    }
}

function verificarEmail(input) {
    const email = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length > 0) {
        if (!emailRegex.test(email)) {
            input.classList.add('input-error');
            if (input.nextElementSibling) input.nextElementSibling.style.display = 'block';
        } else {
            input.classList.remove('input-error');
            if (input.nextElementSibling) input.nextElementSibling.style.display = 'none';
        }
    } else {
        input.classList.remove('input-error');
        if (input.nextElementSibling) input.nextElementSibling.style.display = 'none';
    }
}

function abrirHistorial(fechasStr) {
    const list = document.getElementById('historyList');
    list.innerHTML = '';

    try {
        const fechas = typeof fechasStr === 'string' ? JSON.parse(fechasStr) : fechasStr;
        if (!fechas || fechas.length === 0) {
            list.innerHTML = '<li class="history-item">Sin historial registrado</li>';
        } else {
            const fechasRev = Array.isArray(fechas) ? [...fechas].reverse() : [];
            fechasRev.forEach(f => {
                let displayDate = f;
                try { displayDate = f.split(' ')[0].split('-').reverse().join('/'); } catch (e) { }
                list.innerHTML += `
                    <li class="history-item">
                        <span>Generado</span>
                        <strong>${displayDate}</strong>
                    </li>`;
            });
        }
    } catch (e) {
        console.error(e);
        list.innerHTML = '<li class="history-item">Error al leer historial</li>';
    }
    document.getElementById('historyModal').classList.add('active');
}

function cerrarHistorial() {
    document.getElementById('historyModal').classList.remove('active');
}

function exportarEstudiantes() {
    if (estudiantesFiltrados.length === 0) {
        mostrarNotificacion('No hay estudiantes para exportar', 'error');
        return;
    }
    const searchInput = document.getElementById('searchInput').value.trim();
    const categoriaId = document.getElementById('filterCategoria').value;
    const periodoId = document.getElementById('filterPeriodo').value;

    let url = `exportar_excel.php?grupo=${grupoId}`;
    if (searchInput) url += `&busqueda=${encodeURIComponent(searchInput)}`;
    if (categoriaId) url += `&categoria=${encodeURIComponent(categoriaId)}`;
    if (periodoId) url += `&periodo=${encodeURIComponent(periodoId)}`;

    window.open(url, '_blank');
}

function exportarPDF() {
    if (estudiantesFiltrados.length === 0) {
        mostrarNotificacion('No hay estudiantes para exportar', 'error');
        return;
    }
    const searchInput = document.getElementById('searchInput').value.trim();
    const categoriaId = document.getElementById('filterCategoria').value;
    const periodoId = document.getElementById('filterPeriodo').value;

    let url = `exportar_pdf.php?grupo=${grupoId}`;
    if (searchInput) url += `&busqueda=${encodeURIComponent(searchInput)}`;
    if (categoriaId) url += `&categoria=${encodeURIComponent(categoriaId)}`;
    if (periodoId) url += `&periodo=${encodeURIComponent(periodoId)}`;

    window.open(url, '_blank');
}

function construirUrlDescargaCertificado(codigo, tipo = 'pdf', inline = false) {
    if (!codigo) return '';

    const tipoNorm = String(tipo || 'pdf').toLowerCase();
    const action = (tipoNorm === 'imagen' || tipoNorm === 'png' || tipoNorm === 'image')
        ? 'descargar_imagen'
        : 'descargar_pdf';

    const params = new URLSearchParams();
    params.set('action', action);
    params.set('codigo', String(codigo));
    params.set('t', String(Date.now()));
    if (inline) params.set('inline', '1');

    return `../api/certificados/generar.php?${params.toString()}`;
}

function formatearFechaModalCertPadre(fechaRaw) {
    if (!fechaRaw) return '--';
    const f = new Date(fechaRaw);
    if (Number.isNaN(f.getTime())) return String(fechaRaw);
    const dd = String(f.getDate()).padStart(2, '0');
    const mm = String(f.getMonth() + 1).padStart(2, '0');
    const yyyy = f.getFullYear();
    const hh = String(f.getHours()).padStart(2, '0');
    const mi = String(f.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
}

function cargarImagenModalCertPadre(imageEl, src, loaderEl = null) {
    return new Promise(resolve => {
        if (!imageEl || !src) {
            resolve(false);
            return;
        }

        imageEl.onload = () => {
            if (loaderEl) loaderEl.style.display = 'none';
            imageEl.style.display = 'block';
            resolve(true);
        };
        imageEl.onerror = () => resolve(false);
        imageEl.src = src;
    });
}

async function intentarFallbackPreviewBase64Padre(codigo, imageEl, loaderEl = null, loaderTextEl = null) {
    try {
        const resp = await fetch(`../api/certificados/index.php?action=get_image&code=${encodeURIComponent(codigo)}&t=${Date.now()}`);
        const json = await resp.json();
        if (json?.success && json?.image) {
            return await cargarImagenModalCertPadre(imageEl, json.image, loaderEl);
        }
        if (loaderTextEl && json?.message) loaderTextEl.textContent = json.message;
    } catch (e) {
        console.warn('Fallback preview padre falló:', e);
    }
    return false;
}

async function intentarPreviewDesdePlantillaPadre(cert = {}, imageEl, loaderEl = null, loaderTextEl = null) {
    const categoriaId = Number(cert.categoria_id || 0);
    if (!categoriaId) return false;

    try {
        const formData = new FormData();
        formData.append('tipo', 'categoria');
        formData.append('id', String(categoriaId));
        formData.append('use_form_data', '0');
        if (cert.estudiante_id) formData.append('estudiante_id', String(cert.estudiante_id));
        if (cert.fecha) formData.append('fecha_certificado', String(cert.fecha));
        if (cert.codigo) formData.append('codigo_certificado', String(cert.codigo));

        const resp = await fetch(`../api/preview/index.php?v=${Date.now()}_${Math.random().toString(36).slice(2, 8)}`, {
            method: 'POST',
            body: formData
        });
        const json = await resp.json();

        if (json?.success && json?.preview_url) {
            return await cargarImagenModalCertPadre(
                imageEl,
                `${json.preview_url}?v=${Date.now()}`,
                loaderEl
            );
        }

        if (loaderTextEl && json?.message) loaderTextEl.textContent = json.message;
    } catch (e) {
        console.warn('Preview por plantilla (padre) falló:', e);
    }

    return false;
}

async function abrirModalInfoCertificadoPadre(cert = {}) {
    const codigo = String(cert.codigo || '').trim();
    if (!codigo) {
        mostrarNotificacion('No se encontró el código del certificado', 'warning');
        return;
    }

    const modal = document.getElementById('modalInfoCertificado');
    if (!modal) return;

    const elNombre = document.getElementById('infoCertNombre');
    const elCedula = document.getElementById('infoCertCedula');
    const elCodigo = document.getElementById('infoCertCodigo');
    const elFecha = document.getElementById('infoCertFecha');
    const elCategoria = document.getElementById('infoCertCategoria');
    const elQR = document.getElementById('infoCertQR');
    const elDestacado = document.getElementById('infoCertDestacadoBadge');
    const elLoader = document.getElementById('infoCertPreviewLoader');
    const elLoaderText = document.getElementById('infoCertPreviewLoaderText');
    const elPreview = document.getElementById('infoCertPreviewImg');

    if (elNombre) elNombre.textContent = cert.nombre || '—';
    if (elCedula) elCedula.textContent = cert.cedula || '—';
    if (elCodigo) elCodigo.textContent = codigo;
    if (elFecha) elFecha.textContent = formatearFechaModalCertPadre(cert.fecha || '');
    if (elCategoria) elCategoria.textContent = cert.categoria || 'Sin categoría';

    if (elQR) {
        elQR.src = `../api/certificados/qr.php?codigo=${encodeURIComponent(codigo)}&t=${Date.now()}`;
    }

    if (elDestacado) {
        elDestacado.style.display = cert.es_destacado ? 'block' : 'none';
    }

    if (elLoaderText) elLoaderText.textContent = 'Cargando previsualización...';
    if (elLoader) elLoader.style.display = 'flex';
    if (elPreview) {
        elPreview.style.display = 'none';
        let ok = await intentarPreviewDesdePlantillaPadre(cert, elPreview, elLoader, elLoaderText);
        if (!ok) {
            if (elLoaderText) elLoaderText.textContent = 'Intentando carga por certificado...';
            ok = await cargarImagenModalCertPadre(elPreview, construirUrlDescargaCertificado(codigo, 'imagen', true), elLoader);
        }
        if (!ok) {
            if (elLoaderText) elLoaderText.textContent = 'Intentando carga alternativa...';
            ok = await intentarFallbackPreviewBase64Padre(codigo, elPreview, elLoader, elLoaderText);
        }
        if (!ok && elLoaderText) {
            elLoaderText.textContent = 'No se pudo cargar la previsualización';
        }
    }

    modal.classList.add('active');
}

window.addEventListener('message', function (event) {
    if (event.origin && event.origin !== window.location.origin) return;
    const payload = event.data || {};
    if (payload.type !== 'cce:open-cert-modal' || !payload.certificado) return;
    abrirModalInfoCertificadoPadre(payload.certificado);
});

function descargarDesdeInfoModalPadre(tipo) {
    const codigoEl = document.getElementById('infoCertCodigo');
    const codigo = codigoEl ? String(codigoEl.textContent || '').trim() : '';

    if (!codigo || codigo === '...') {
        mostrarNotificacion('No se encontró el código del certificado para descargar', 'warning');
        return;
    }

    const url = construirUrlDescargaCertificado(codigo, tipo, false);
    if (!url) return;
    window.open(url, '_blank');
}

// Modal Listeners
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarEditModal();
        cerrarCertificadosModal();
        cerrarDeleteModal();
        cerrarHistorial();
        cerrarModal(null, 'modalHistorial');
    }
});

function cerrarModal(event = null, modalId = null) {
    if (event && event.target !== event.currentTarget) return;

    if (modalId) {
        const m = document.getElementById(modalId);
        if (m) {
            m.classList.remove('active');
        }
        return;
    }

    document.querySelectorAll('.modal').forEach(m => {
        m.classList.remove('active');
    });
}

function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return '-';
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const m = hoy.getMonth() - nacimiento.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    return edad;
}

function abrirModalHistorial(estudianteId) {
    const modal = document.getElementById('modalHistorial');
    const container = document.getElementById('historialContainer');

    if (!modal || !container) return;

    // Mostrar modal con spinner
    modal.classList.add('active');
    container.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <i class="fas fa-spinner fa-spin fa-3x" style="margin-bottom: 15px;"></i>
            <p>Cargando el historial del estudiante...</p>
        </div>
    `;

    // Fetch al endpoint
    fetch(`../api/categorias/estudiantes.php?action=historial_auditoria&estudiante_id=${estudianteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historial && data.historial.length > 0) {
                let html = '';
                data.historial.forEach(item => {
                    const fechaObj = new Date(item.fecha);
                    const fechaFmt = fechaObj.toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });

                    // Traducir acción
                    let accionLetra = item.accion;
                    let iconColor = '#3498db';
                    let iconClass = 'fa-info-circle';
                    
                    if (accionLetra === 'creacion') {
                        iconColor = '#2ecc71'; iconClass = 'fa-plus-circle'; accionLetra = 'Creación';
                    } else if (accionLetra === 'actualizacion') {
                        iconColor = '#f39c12'; iconClass = 'fa-edit'; accionLetra = 'Actualización';
                    } else if (accionLetra === 'eliminacion') {
                        iconColor = '#e74c3c'; iconClass = 'fa-trash-alt'; accionLetra = 'Eliminación';
                    } else if (accionLetra === 'cambio_categoria') {
                        iconColor = '#9b59b6'; iconClass = 'fa-exchange-alt'; accionLetra = 'Cambio Categ.';
                    }

                    const usuario = item.usuario_nombre || 'Sistema / Desconocido';
                    
                    let detallesStr = '-';
                    if (item.detalles) {
                        try {
                            const parsed = JSON.parse(item.detalles);
                            const lines = [];
                            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                                for (const key in parsed) {
                                    if (!Object.prototype.hasOwnProperty.call(parsed, key)) continue;
                                    const value = parsed[key];

                                    if (value && typeof value === 'object' && !Array.isArray(value) && ('old' in value || 'new' in value)) {
                                        const oldVal = value.old == null || value.old === '' ? 'vacío' : escapeHtml(String(value.old));
                                        const newVal = value.new == null || value.new === '' ? 'vacío' : escapeHtml(String(value.new));
                                        lines.push(`<strong>${escapeHtml(key)}:</strong> ${oldVal} <i class="fas fa-arrow-right" style="opacity:.6;"></i> ${newVal}`);
                                    } else if (Array.isArray(value)) {
                                        const arrText = value.map(v => escapeHtml(String(v))).join(', ');
                                        lines.push(`<strong>${escapeHtml(key)}:</strong> ${arrText || '[]'}`);
                                    } else if (value && typeof value === 'object') {
                                        lines.push(`<strong>${escapeHtml(key)}:</strong> ${escapeHtml(JSON.stringify(value))}`);
                                    } else {
                                        lines.push(`<strong>${escapeHtml(key)}:</strong> ${value == null || value === '' ? 'vacío' : escapeHtml(String(value))}`);
                                    }
                                }
                                detallesStr = lines.length ? lines.join('<br>') : '-';
                            } else {
                                detallesStr = escapeHtml(String(parsed));
                            }
                        } catch (e) {
                            detallesStr = escapeHtml(String(item.detalles));
                        }
                    }

                    html += `
                        <div class="audit-item" style="border-left-color: ${iconColor};">
                            <div class="audit-header">
                                <span><i class="fas ${iconClass}" style="color:${iconColor}; margin-right:5px;"></i> ${fechaFmt}</span>
                                <span style="color: #4b5563;"><i class="fas fa-user-circle"></i> ${usuario}</span>
                            </div>
                            <div class="audit-action">${accionLetra}</div>
                            <p class="audit-details" style="margin-top: 5px;">${detallesStr}</p>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #6b7280;">
                        <i class="fas fa-history fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No se encontraron registros en el historial de este estudiante.</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 15px;"></i>
                    <p>Error al cargar el historial.</p>
                </div>
            `;
        });
}

const detalleModal = document.getElementById('detalleModal');
if (detalleModal) detalleModal.addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });
