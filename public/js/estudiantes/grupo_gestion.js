/**
 * Logic for Group Student Management
 */

// Global Variables (Initialized in View)
// const estudiantesData = [];
// const grupoId = ...;
// const grupoColor = ...;

let estudiantesFiltrados = [];
let estudiantesSeleccionados = new Set();
let selectedCerts = new Set();
let paginaActual = 1;
let porPagina = 20;
let totalPaginasGlobal = 1;
let currentStudentId = null;
let currentStudentIdForCerts = null;
let estudianteIdAEliminar = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarEstudiantes();
    setupHorizontalScroll();
});

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

            // Apply initial hierarchical sort
            estudiantesFiltrados = aplicarOrdenJerarquico(estudiantesFiltrados);

            renderTabla();
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
    // const estado = document.getElementById('filterEstado').value; // Removed

    estudiantesFiltrados = estudiantesData.filter(est => {
        let cumple = true;

        if (busqueda) {
            cumple = cumple && (
                (est.nombre && est.nombre.toLowerCase().includes(busqueda)) ||
                (est.cedula && est.cedula.includes(busqueda)) ||
                (est.categoria_nombre && est.categoria_nombre.toLowerCase().includes(busqueda)) ||
                (est.representante_nombre && est.representante_nombre.toLowerCase().includes(busqueda)) ||
                (est.representante_cedula && est.representante_cedula.includes(busqueda))
            );
        }

        if (categoriaId) {
            const tieneCategoria = est.categorias && est.categorias.some(c => c.id == categoriaId);
            cumple = cumple && tieneCategoria;
        }

        if (periodoId) {
            const tienePeriodo = est.categorias && est.categorias.some(c => c.periodo_id == periodoId);
            cumple = cumple && tienePeriodo;
        }

        return cumple;
    });

    estudiantesFiltrados = aplicarOrdenJerarquico(estudiantesFiltrados);
    paginaActual = 1;
    renderTabla();
}

function aplicarOrdenJerarquico(lista) {
    const mapPorCedula = new Map();
    lista.forEach(est => {
        if (est.cedula) mapPorCedula.set(est.cedula, est);
    });

    const hijosConPadreEnLista = new Set();
    const mapPadreHijos = new Map();

    lista.forEach(est => {
        if (est.es_menor == 1 && est.representante_cedula && mapPorCedula.has(est.representante_cedula)) {
            hijosConPadreEnLista.add(est.id);
            const repCed = est.representante_cedula;
            if (!mapPadreHijos.has(repCed)) {
                mapPadreHijos.set(repCed, []);
            }
            mapPadreHijos.get(repCed).push(est);
        }
    });

    const nuevaLista = [];
    lista.forEach(est => {
        if (hijosConPadreEnLista.has(est.id)) return;

        nuevaLista.push(est);

        if (est.cedula && mapPadreHijos.has(est.cedula)) {
            const hijos = mapPadreHijos.get(est.cedula);
            hijos.forEach(hijo => {
                hijo.is_hierarchical_child = true;
                nuevaLista.push(hijo);
            });
        }
    });

    return nuevaLista;
}

// ========== RENDERING ==========

function renderTabla() {
    const tbody = document.getElementById('estudiantesBody');
    const footer = document.getElementById('paginationFooter');

    const totalItems = estudiantesFiltrados.length;
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

    const paginados = estudiantesFiltrados.slice(inicio, fin);

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

    tbody.innerHTML = paginados.map((est, idx) => {
        const iniciales = est.nombre ? est.nombre.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : '?';
        const colorCat = est.categoria_color || '#95a5a6';
        const esMenor = est.es_menor == 1;
        const isSelected = estudiantesSeleccionados.has(est.id);

        let rowClass = isSelected ? 'row-selected' : '';
        if (esMenor) rowClass += ' estudiante-menor';

        if (est.cedula && representantesCedulas.has(est.cedula)) {
            rowClass += ' estudiante-representante';
        }

        let nameClass = 'student-name';
        if (est.is_hierarchical_child) {
            nameClass += ' student-indent';
        }

        // Age Calculation
        let edadHtml = '<span style="color: #cbd5e0;">-</span>';
        if (est.fecha_nacimiento) {
            const birthDate = new Date(est.fecha_nacimiento);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            const fechaDisplay = est.fecha_nacimiento.split('-').reverse().join('/');
            edadHtml = `
                <div style="line-height: 1.2;">
                    <div>${fechaDisplay}</div>
                    <small style="color: #7f8c8d; font-weight: 600;">(${age} años)</small>
                </div>
            `;
        }

        // Simplified Contact Column (Icons only, no fancy chips)
        // Similar to standard simplified layout in gestion_categoria
        // Chip Contacto Unificado (Original Style: Chips with text)
        let contactoHtml = '<div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">';

        let hasPhone = false;
        let hasEmail = false;

        // 1. WhatsApp
        if (est.celular) {
            contactoHtml += `<a href="https://wa.me/${formatPhoneLink(est.celular)}" target="_blank" class="contact-chip contact-chip-whatsapp" style="max-width: 100%;"><i class="fab fa-whatsapp"></i> ${formatPhoneDisplay(est.celular)}</a>`;
            hasPhone = true;
        } else if (esMenor && est.representante_celular) {
            contactoHtml += `<a href="https://wa.me/${formatPhoneLink(est.representante_celular)}" target="_blank" class="contact-chip contact-chip-whatsapp" title="Representante" style="max-width: 100%;"><i class="fas fa-user-tie"></i> ${formatPhoneDisplay(est.representante_celular)} (Rep)</a>`;
            hasPhone = true;
        }

        // 2. Email
        if (est.email) {
            contactoHtml += `<a href="mailto:${est.email}" class="contact-chip contact-chip-email" style="max-width: 100%; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><i class="fas fa-envelope"></i> ${est.email}</a>`;
            hasEmail = true;
        } else if (esMenor && est.representante_email) {
            contactoHtml += `<a href="mailto:${est.representante_email}" class="contact-chip contact-chip-email" title="Representante" style="max-width: 100%; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><i class="fas fa-user-tie"></i> ${est.representante_email} (Rep)</a>`;
            hasEmail = true;
        }

        if (!hasPhone && !hasEmail) {
            contactoHtml += '<span style="color: #cbd5e0;">-</span>';
        }

        contactoHtml += '</div>';

        // Date registration
        let fechaRegistroHtml = '<span style="color: #cbd5e0;">-</span>';
        if (est.fecha_creacion) {
            const fechaObj = new Date(est.fecha_creacion);
            const dia = String(fechaObj.getDate()).padStart(2, '0');
            const mes = String(fechaObj.getMonth() + 1).padStart(2, '0');
            const anio = fechaObj.getFullYear();
            fechaRegistroHtml = `<div style="line-height: 1.2;">
                        <div style="font-weight: 500; color: #4b5563;">${dia}/${mes}/${anio}</div>
                        <small style="color: #9ca3af;">Registro</small>
                    </div>`;
        }

        // Categories Badge (retained as it's useful info, but simplified if needed)
        let categoriasHtml = '<span style="color:#95a5a6;">-</span>';
        if (est.categorias && est.categorias.length > 0) {
            const catsGrouped = {};
            est.categorias.forEach(c => {
                const key = c.nombre;
                if (!catsGrouped[key]) {
                    catsGrouped[key] = { ...c, periodos: [] };
                }
                const exists = catsGrouped[key].periodos.some(p => p.nombre === c.periodo);
                if (!exists) {
                    catsGrouped[key].periodos.push({
                        nombre: c.periodo,
                        fecha: c.fecha_inicio || '0000-00-00'
                    });
                }
            });
            categoriasHtml = Object.values(catsGrouped).map(cat => {
                // simple sort
                cat.periodos.sort((a, b) => (a.fecha < b.fecha ? -1 : 1));
                const periodosStr = cat.periodos.map(p => p.nombre).join('\n');
                return `
                    <span class="badge badge-categoria" 
                          style="background: ${cat.color}20; color: ${cat.color}; display: inline-flex; width: fit-content; cursor: help; margin-bottom: 2px;"
                          title="Períodos:&#10;${periodosStr}">
                        ${cat.icono || '📁'} ${escapeHtml(cat.nombre || 'Sin categoría')}
                    </span>
                `;
            }).join('<br>');
        }

        return `
            <tr class="${rowClass}">
                <td class="sticky-col sticky-left-1">
                    <input type="checkbox" class="select-checkbox" data-id="${est.id}" 
                           onchange="toggleSelectStudent(${est.id})" ${isSelected ? 'checked' : ''}>
                </td>
                <td class="sticky-col sticky-left-2">
                    <div class="${nameClass}">
                        <div class="student-avatar" style="background: linear-gradient(135deg, ${colorCat}cc, ${colorCat});">${iniciales}</div>
                        <div class="student-info">
                            <strong>${escapeHtml(est.nombre)}</strong>
                            ${esMenor ? '<span class="badge badge-menor"><i class="fas fa-child"></i> Menor de edad</span>' : ''}
                        </div>
                    </div>
                </td>
                <td>${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color:#aaa">—</span>'}</td>
                <td>${fechaRegistroHtml}</td>
                <td>
                    <div class="category-scroll-container">${categoriasHtml}</div>
                </td>
                <td>${edadHtml}</td>
                <td style="text-align: center;">${contactoHtml}</td>
                <td class="sticky-col sticky-right" style="text-align: right;">
                    <div class="actions-cell" style="justify-content: flex-end;">
                        <button class="btn-icon btn-view" onclick="verCertificados(${est.id})" title="Ver Certificados" style="color:#00348a;background:#e8f0fe;">
                            <i class="fas fa-certificate"></i>
                        </button>
                        <button class="btn-icon btn-edit" onclick="editarEstudiante(${est.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" onclick="eliminarEstudiante(${est.id})" title="Quitar">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ========== SELECTION & BULK ACTIONS ==========

function toggleSelectAll(checkbox) {
    const isChecked = checkbox.checked;
    if (isChecked) {
        estudiantesFiltrados.forEach(est => estudiantesSeleccionados.add(est.id));
    } else {
        estudiantesSeleccionados.clear();
    }
    renderTabla();
    updateBulkActionsBar();
}

function toggleSelectStudent(id) {
    if (estudiantesSeleccionados.has(id)) {
        estudiantesSeleccionados.delete(id);
    } else {
        estudiantesSeleccionados.add(id);
    }

    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        const todosSeleccionados = estudiantesFiltrados.length > 0 && estudiantesFiltrados.every(est => estudiantesSeleccionados.has(est.id));
        const algunosSeleccionados = estudiantesFiltrados.some(est => estudiantesSeleccionados.has(est.id));
        selectAllCheckbox.checked = todosSeleccionados;
        selectAllCheckbox.indeterminate = algunosSeleccionados && !todosSeleccionados;
    }

    // Update specific row class
    const checkbox = document.querySelector(`.select-checkbox[data-id="${id}"]`);
    if (checkbox) {
        const row = checkbox.closest('tr');
        if (row) row.classList.toggle('row-selected', estudiantesSeleccionados.has(id));
    }

    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const bar = document.getElementById('bulkActionsBar');
    const countText = document.getElementById('selectedCountText');
    const count = estudiantesSeleccionados.size;

    if (count > 0) {
        bar.classList.add('visible');
        bar.style.display = 'flex'; // Force display
        countText.textContent = `${count} seleccionado${count > 1 ? 's' : ''}`;
    } else {
        bar.classList.remove('visible');
        bar.style.display = 'none';
    }
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

async function regenerarCertificadosSeleccionados() {
    const count = estudiantesSeleccionados.size;

    if (count === 0) {
        mostrarNotificacion('No hay estudiantes seleccionados', 'error');
        return;
    }

    const confirmar = confirm(`¿Desea regenerar los certificados de ${count} estudiante${count > 1 ? 's' : ''} seleccionado${count > 1 ? 's' : ''}?\n\nEsto sobrescribirá los certificados existentes.`);
    if (!confirmar) return;

    const estudianteIds = Array.from(estudiantesSeleccionados);
    const btnRegenerar = document.getElementById('btnRegenerarCerts');
    const originalText = btnRegenerar ? btnRegenerar.innerHTML : 'Regenerar';

    if (btnRegenerar) {
        btnRegenerar.disabled = true;
        btnRegenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regenerando...';
    }

    let exitosos = 0;
    let errores = 0;

    try {
        for (const estudianteId of estudianteIds) {
            try {
                const response = await fetch('../api/certificados/generar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'regenerar_estudiante',
                        grupo_id: grupoId,
                        estudiante_id: estudianteId,
                        forzar_regenerar: true
                    })
                });

                const result = await response.json();
                if (result.success) exitosos++;
                else {
                    errores++;
                    console.error(`Error regenerando certificado para estudiante ${estudianteId}:`, result.error);
                }
            } catch (err) {
                errores++;
                console.error(`Error en petición para estudiante ${estudianteId}:`, err);
            }
        }

        if (errores === 0) {
            mostrarNotificacion(`✅ ${exitosos} certificado${exitosos > 1 ? 's' : ''} regenerado${exitosos > 1 ? 's' : ''} exitosamente`, 'success');
        } else if (exitosos > 0) {
            mostrarNotificacion(`⚠️ ${exitosos} regenerado${exitosos > 1 ? 's' : ''}, ${errores} error${errores > 1 ? 'es' : ''}`, 'warning');
        } else {
            mostrarNotificacion(`❌ Error al regenerar certificados`, 'error');
        }

        deseleccionarTodos();

    } catch (error) {
        console.error('Error general:', error);
        mostrarNotificacion('Error al procesar la regeneración', 'error');
    } finally {
        if (btnRegenerar) {
            btnRegenerar.disabled = false;
            btnRegenerar.innerHTML = originalText;
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

function editarEstudiante(id) {
    const est = estudiantesData.find(e => e.id == id);
    if (!est) return;

    document.getElementById('edit_estudiante_id').value = est.id;
    document.getElementById('edit_nombre').value = est.nombre;
    document.getElementById('edit_cedula').value = est.cedula || '';

    let celular = est.celular || '';
    if (celular.startsWith('+593')) celular = celular.substring(4);
    else if (celular.startsWith('0')) celular = celular.substring(1);
    document.getElementById('edit_celular').value = celular;

    document.getElementById('edit_email').value = est.email || '';
    document.getElementById('edit_fecha_nacimiento').value = est.fecha_nacimiento || '';
    document.getElementById('edit_destacado').checked = est.destacado == 1;

    toggleEditRepresentante();

    // Fill representative data even if not visible yet
    document.getElementById('edit_rep_nombre').value = est.representante_nombre || '';
    document.getElementById('edit_rep_cedula').value = est.representante_cedula || '';

    let repCelular = est.representante_celular || '';
    if (repCelular.startsWith('+593')) repCelular = repCelular.substring(4);
    else if (repCelular.startsWith('0')) repCelular = repCelular.substring(1);
    document.getElementById('edit_rep_celular').value = repCelular;

    document.getElementById('edit_rep_email').value = est.representante_email || '';

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

// Modal Listeners
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarEditModal();
        cerrarCertificadosModal();
        cerrarDeleteModal();
        cerrarHistorial();
    }
});

const detalleModal = document.getElementById('detalleModal');
if (detalleModal) detalleModal.addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });
