// Estado de la aplicación
let currentPage = 1;
let perPage = 20;
let totalItems = 0;
let totalPagesGlobal = 1;
let currentFilters = {
    search: '',
    grupo_id: '',
    categoria_id: ''
};

let estudiantesData = []; // Global store for students
let estudiantesSeleccionados = [];
let categoriasPorGrupo = {};

// Helper functions for dropdowns
function toggleDropdown(id, event) {
    event.stopPropagation();
    const dropdown = document.getElementById(id);
    const trigger = event.currentTarget;

    if (dropdown) {
        // Close all others
        document.querySelectorAll('.grupo-dropdown.active').forEach(d => {
            if (d.id !== id) {
                d.classList.remove('active');
                d.style.position = ''; // Reset
            }
        });

        if (dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
            dropdown.style.position = ''; /* Reset inline styles */
        } else {
            dropdown.classList.add('active');

            // Fixed Positioning Logic to escape overflow
            const rect = trigger.getBoundingClientRect();

            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 2) + 'px'; // Small gap
            dropdown.style.left = rect.left + 'px';
            dropdown.style.minWidth = '200px';
            dropdown.style.maxWidth = 'calc(100vw - 20px)';
            dropdown.style.zIndex = '9999';

            // Boundary enforcement (optional, simplistic)
            const dropdownRect = dropdown.getBoundingClientRect();
            if (dropdownRect.right > window.innerWidth) {
                dropdown.style.left = (window.innerWidth - dropdownRect.width - 10) + 'px';
            }
            const leftActual = parseFloat(dropdown.style.left || '0');
            if (leftActual < 10) {
                dropdown.style.left = '10px';
            }
        }
    }
}

function hideDropdown(id) {
    const dropdown = document.getElementById(id);
    if (dropdown) {
        dropdown.classList.remove('active');
    }
}

function toggleMenoresIndex(repId) {
    const rows = document.querySelectorAll(`.menor-de-${repId}`);
    if (!rows.length) return;

    const toggleEl = document.getElementById(`toggle-${repId}`);
    const mostrar = Array.from(rows).some(row => row.style.display === 'none');

    rows.forEach(row => {
        row.style.display = mostrar ? 'table-row' : 'none';
    });

    if (toggleEl) {
        toggleEl.classList.toggle('fa-chevron-right', !mostrar);
        toggleEl.classList.toggle('fa-chevron-down', mostrar);
    }
}

function normalizarTelefonoLocalEC(raw) {
    const clean = String(raw || '').replace(/\D/g, '');
    if (!clean) return '';
    if (clean.startsWith('593')) return clean.substring(3).slice(0, 9);
    if (clean.startsWith('0')) return clean.substring(1).slice(0, 9);
    return clean.slice(0, 9);
}

function construirTelefonoApiEC(raw) {
    const local = normalizarTelefonoLocalEC(raw);
    return local ? `+593${local}` : '';
}

function calcularEdadDesdeFecha(fechaRaw) {
    if (!fechaRaw) return null;
    const fecha = new Date(fechaRaw);
    if (Number.isNaN(fecha.getTime())) return null;

    const hoy = new Date();
    let edad = hoy.getFullYear() - fecha.getFullYear();
    const m = hoy.getMonth() - fecha.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < fecha.getDate())) {
        edad--;
    }
    return edad;
}

function esIconoFontAwesome(valor) {
    return typeof valor === 'string' && valor.includes('fa-');
}

function renderBadgeIcon(iconoRaw, iconoFallback = '📁') {
    const icono = (iconoRaw || '').toString().trim();
    if (!icono) return iconoFallback;
    if (esIconoFontAwesome(icono)) return `<i class="${icono}"></i>`;
    return escapeHtml(icono);
}

function renderCategoriaIcono(iconoRaw) {
    return renderBadgeIcon(iconoRaw, '📁');
}

function renderGrupoIcono(iconoRaw) {
    return renderBadgeIcon(iconoRaw, '👥');
}

function formatearTelefonoReferencia(telefonoRaw) {
    const clean = String(telefonoRaw || '').replace(/\D/g, '');
    if (!clean) return '';
    if (clean.startsWith('593')) return '0' + clean.substring(3);
    if (clean.startsWith('0')) return clean;
    return '0' + clean;
}

function generarLinkWhatsAppReferencia(telefonoRaw, nombreReferencia = '', relacion = '', estudianteNombre = 'Estudiante') {
    const clean = String(telefonoRaw || '').replace(/\D/g, '');
    if (!clean) return '#';

    let numero = clean;
    if (numero.startsWith('0')) numero = numero.substring(1);
    if (!numero.startsWith('593')) numero = `593${numero}`;

    const mensaje = `Hola 👋 le saluda Casa de la Cultura - Núcleo Tungurahua 🎭. Usted es ${nombreReferencia || 'referencia'}${relacion ? ` y ${relacion}` : ''} del estudiante ${estudianteNombre}. ¿Me podría confirmar esta información, por favor? 🙏`;
    return `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`;
}

function formatearFechaMatriculaBadge(fechaRaw) {
    const raw = String(fechaRaw || '').trim();
    if (!raw || raw === '0000-00-00') return 's/f';

    const iso = raw.length >= 10 ? raw.substring(0, 10) : raw;
    const parts = iso.split('-');
    if (parts.length === 3) {
        const [y, m, d] = parts;
        if (y && m && d) return `${d}/${m}/${y}`;
    }

    const dateObj = new Date(raw);
    if (!Number.isNaN(dateObj.getTime())) {
        const d = String(dateObj.getDate()).padStart(2, '0');
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const y = dateObj.getFullYear();
        return `${d}/${m}/${y}`;
    }

    return 's/f';
}

function agruparCategoriasConPeriodos(categorias) {
    const mapa = {};

    (categorias || []).forEach(cat => {
        const key = String(cat.id || cat.nombre || 'sin_categoria');
        if (!mapa[key]) {
            mapa[key] = {
                id: cat.id,
                nombre: cat.nombre || 'Sin categoría',
                icono: cat.icono || '📁',
                fecha_matricula: '',
                periodos: []
            };
        }

        const periodoNombre = cat.periodo || 'Sin período';
        const fechaInicio = cat.fecha_inicio || '0000-00-00';
        const fechaMatricula = cat.fecha_matricula || '';
        const esDestacado = cat.es_destacado === 1 || cat.es_destacado === '1' || cat.es_destacado === true;

        if (fechaMatricula) {
            if (!mapa[key].fecha_matricula || mapa[key].fecha_matricula < fechaMatricula) {
                mapa[key].fecha_matricula = fechaMatricula;
            }
        }

        const periodoExistente = mapa[key].periodos.find(p => p.nombre === periodoNombre);
        if (periodoExistente) {
            if (esDestacado) periodoExistente.es_destacado = true;
            if ((!periodoExistente.fecha || periodoExistente.fecha === '0000-00-00') && fechaInicio) {
                periodoExistente.fecha = fechaInicio;
            }
            if (fechaMatricula) {
                if (!periodoExistente.fecha_matricula || periodoExistente.fecha_matricula > fechaMatricula) {
                    periodoExistente.fecha_matricula = fechaMatricula;
                }
            }
            return;
        }

        mapa[key].periodos.push({
            nombre: periodoNombre,
            fecha: fechaInicio,
            fecha_matricula: fechaMatricula,
            es_destacado: esDestacado
        });
    });

    return Object.values(mapa).map(cat => {
        cat.periodos.sort((a, b) => {
            const fechaA = a.fecha || '0000-00-00';
            const fechaB = b.fecha || '0000-00-00';
            if (fechaA === fechaB) {
                return String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' });
            }
            return fechaA < fechaB ? -1 : 1;
        });
        return cat;
    }).sort((a, b) => String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' }));
}

function buildCategoriasDropdownHtml(categorias) {
    const categoriasAgrupadas = agruparCategoriasConPeriodos(categorias);

    if (!categoriasAgrupadas.length) {
        return '<div class="categoria-empty">Sin categorías</div>';
    }

    return categoriasAgrupadas.map(cat => {
        const iconoHtml = renderCategoriaIcono(cat.icono);
        const fechaMatricula = formatearFechaMatriculaBadge(cat.fecha_matricula);
        const tieneDestacado = cat.periodos.some(p => p.es_destacado);
        const estrellaHdr = tieneDestacado ? '<i class="fas fa-star categoria-star"></i>' : '';

        let tooltipHtml = '<div class="tooltip-content">';
        cat.periodos.forEach(periodo => {
            const fechaPeriodo = formatearFechaMatriculaBadge(periodo.fecha_matricula || '');
            tooltipHtml += `
                <div class="tooltip-period-item">
                    <span class="tooltip-period-main">
                        <span class="tooltip-period-name">${escapeHtml(periodo.nombre)}</span>
                        <span class="tooltip-period-date"><i class="far fa-calendar-check"></i> Reg: ${escapeHtml(fechaPeriodo)}</span>
                    </span>
                    ${periodo.es_destacado ? '<i class="fas fa-star tooltip-star"></i>' : ''}
                </div>
            `;
        });
        tooltipHtml += '</div>';

        return `
            <div class="categoria-item">
                <span class="categoria-item-main">
                    <span class="categoria-item-main-left">
                        <span class="categoria-item-icon">${iconoHtml}</span>
                        <span class="categoria-item-name">${escapeHtml(cat.nombre)}</span>
                        ${estrellaHdr}
                    </span>
                    <span class="categoria-item-meta">
                        <i class="far fa-calendar-check"></i>
                        ${escapeHtml(fechaMatricula)}
                    </span>
                </span>
                ${tooltipHtml}
            </div>
        `;
    }).join('');
}

// Close dropdowns on scroll to prevent detached floating elements
window.addEventListener('scroll', function () {
    document.querySelectorAll('.grupo-dropdown.active').forEach(d => {
        d.classList.remove('active');
    });
}, true); // Capture phase to catch table scrolls too

// Close dropdowns when clicking outside
document.addEventListener('click', function (event) {
    document.querySelectorAll('.grupo-dropdown.active').forEach(d => {
        d.classList.remove('active');
    });
});

// Inicializar
document.addEventListener('DOMContentLoaded', function () {
    // Aplicar filtros iniciales si vienen por URL (window.initialGrupoId defined in View)
    if (window.initialGrupoId) {
        const grupoFilter = document.getElementById('grupoFilter');
        if (grupoFilter) {
            grupoFilter.value = window.initialGrupoId;
            currentFilters.grupo_id = window.initialGrupoId;
            filterCategoriasByGrupo(window.initialGrupoId);
        }
    }
    if (window.initialCategoriaId) {
        const catFilter = document.getElementById('categoriaFilter');
        if (catFilter) {
            catFilter.value = window.initialCategoriaId;
            currentFilters.categoria_id = window.initialCategoriaId;
        }
    }

    actualizarBotonCertificados();
    loadEstudiantes();
    setupHorizontalScroll();

    // Búsqueda en tiempo real
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = this.value;
                currentPage = 1;
                loadEstudiantes();
            }, 300);
        });
    }

    // Filtrar categorías por grupo
    const grupoFilter = document.getElementById('grupoFilter');
    if (grupoFilter) {
        grupoFilter.addEventListener('change', function () {
            filterCategoriasByGrupo(this.value);
            currentFilters.grupo_id = this.value;
            currentPage = 1;
            loadEstudiantes();
            actualizarBotonCertificados();
        });
    }

    const categoriaFilter = document.getElementById('categoriaFilter');
    if (categoriaFilter) {
        categoriaFilter.addEventListener('change', function () {
            currentFilters.categoria_id = this.value;
            currentPage = 1;
            loadEstudiantes();
            actualizarBotonCertificados();
        });
    }

    // Validar formulario certificados
    const certGrupo = document.getElementById('certGrupo');
    const certCategoria = document.getElementById('certCategoria');
    const certFecha = document.getElementById('certFecha');

    function validarFormularioCertificado() {
        const btnGenerar = document.getElementById('btnGenerarCertificados');
        const grupoValido = certGrupo && certGrupo.value;
        const categoriaValida = certCategoria && certCategoria.value;
        const fechaValida = certFecha && certFecha.value;

        if (btnGenerar) {
            btnGenerar.disabled = !(grupoValido && categoriaValida && fechaValida && estudiantesSeleccionados.length > 0);
        }
    }

    if (certCategoria) {
        certCategoria.addEventListener('change', validarFormularioCertificado);
    }
    if (certFecha) {
        certFecha.addEventListener('change', validarFormularioCertificado);
    }
});

// Filtrar opciones de categoría según el grupo seleccionado
function filterCategoriasByGrupo(grupoId) {
    const categoriaSelect = document.getElementById('categoriaFilter');
    if (!categoriaSelect) return;
    const options = categoriaSelect.querySelectorAll('option');

    options.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
        } else if (!grupoId || option.dataset.grupo === grupoId) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });

    // Resetear selección si la categoría actual no corresponde al grupo
    if (grupoId && categoriaSelect.value) {
        const selectedOption = categoriaSelect.querySelector(`option[value="${categoriaSelect.value}"]`);
        if (selectedOption && selectedOption.dataset.grupo !== grupoId) {
            categoriaSelect.value = '';
            currentFilters.categoria_id = '';
        }
    }
}

async function loadEstudiantes() {
    const tableBody = document.getElementById('estudiantesTableBody');
    tableBody.innerHTML = `
                <tr>
                    <td colspan="12">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p>Cargando estudiantes...</p>
                        </div>
                    </td>
                </tr>
            `;

    try {
        const offset = (currentPage - 1) * perPage;
        const params = new URLSearchParams({
            action: 'list_with_details',
            limit: perPage,
            offset: offset,
            search: currentFilters.search,
            grupo_id: currentFilters.grupo_id,
            categoria_id: currentFilters.categoria_id
        });

        const response = await fetch(`../api/estudiantes/index.php?${params}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        estudiantesData = data.estudiantes || []; // Populate global store

        totalItems = data.total;
        updatePagination();
        renderEstudiantes(data.estudiantes);

    } catch (error) {
        console.error('Error:', error);
        tableBody.innerHTML = `
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error al cargar los datos</h3>
                                <p>${error.message}</p>
                            </div>
                        </td>
                    </tr>
                `;
    }
}

function renderEstudiantes(estudiantes) {
    const tableBody = document.getElementById('estudiantesTableBody');

    if (!estudiantes || estudiantes.length === 0) {
        tableBody.innerHTML = `
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h3>No se encontraron estudiantes</h3>
                                <p>Intenta con otros filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                `;
        return;
    }

    const representantesEnPagina = new Set(
        estudiantes
            .filter(e => Number(e.es_menor) !== 1)
            .map(e => String(e.id))
    );

    const menoresPorRepresentante = new Map();
    estudiantes.forEach(e => {
        if (Number(e.es_menor) !== 1 || !e.representante_id) return;
        const repId = String(e.representante_id);
        if (!representantesEnPagina.has(repId)) return;
        menoresPorRepresentante.set(repId, (menoresPorRepresentante.get(repId) || 0) + 1);
    });

    tableBody.innerHTML = estudiantes.map(est => {
        // Parse Groups and Categories
        let gruposMap = {};

        if (est.enrollment_data) {
            const enrollments = est.enrollment_data.split('||');
            enrollments.forEach(enroll => {
                // Data format:
                // GrupoID##GrupoNombre##GrupoColor##GrupoIcono##CatID##CatNombre##CatIcono##Periodo##EsDestacado##PeriodoFechaInicio##FechaMatricula
                const parts = enroll.split('##');
                if (parts.length >= 8) {
                    const gId = parts[0];
                    const gNombre = parts[1];
                    const gColor = parts[2] || '#ccc';
                    const gIcono = parts[3] || '👥';
                    const cId = parts[4];
                    const cNombre = parts[5];
                    const cIcono = parts[6] || '📁';
                    const cPeriodo = parts[7] || 'Sin período';
                    const cEsDestacado = parts[8] ?? '0';
                    const cPeriodoFecha = parts[9] || '0000-00-00';
                    const cFechaMatricula = parts[10] || '';

                    if (!gruposMap[gId]) {
                        gruposMap[gId] = {
                            id: gId,
                            nombre: gNombre,
                            color: gColor,
                            icono: gIcono,
                            categorias: []
                        };
                    }

                    const keyCategoriaPeriodo = `${cId}::${cPeriodo}`;
                    if (!gruposMap[gId].categorias.some(c => `${c.id}::${c.periodo}` === keyCategoriaPeriodo)) {
                        gruposMap[gId].categorias.push({
                            id: cId,
                            nombre: cNombre,
                            icono: cIcono,
                            periodo: cPeriodo,
                            fecha_inicio: cPeriodoFecha,
                            fecha_matricula: cFechaMatricula,
                            es_destacado: cEsDestacado === '1'
                        });
                    }
                }
            });
        }

        // Build Groups HTML
        let gruposHtml = '';
        if (Object.keys(gruposMap).length > 0) {
            Object.values(gruposMap)
                .sort((a, b) => String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' }))
                .forEach(grupo => {
                // Dropdown Content
                const categoriasList = buildCategoriasDropdownHtml(grupo.categorias);
                const grupoIcono = renderGrupoIcono(grupo.icono);

                const dropdownId = `dropdown-${est.id}-${grupo.id}`;

                gruposHtml += `
                            <div class="grupo-container" onmouseleave="hideDropdown('${dropdownId}')">
                                <span class="badge badge-grupo-interactive" 
                                      style="background: ${grupo.color}20; color: ${grupo.color}; border: 1px solid ${grupo.color}40;"
                                      onclick="toggleDropdown('${dropdownId}', event)">
                                    <span class="grupo-badge-icon">${grupoIcono}</span>
                                    <span class="grupo-badge-name">${escapeHtml(grupo.nombre)}</span>
                                </span>
                                <div id="${dropdownId}" class="grupo-dropdown">
                                    <div style="font-size: 11px; font-weight: 600; color: #7f8c8d; margin-bottom: 4px; padding-left: 4px;">CATEGORÍAS:</div>
                                    <div class="categorias-dropdown-list">
                                        ${categoriasList}
                                    </div>
                                </div>
                            </div>
                        `;
                });
            // Wrap in scroll container
            gruposHtml = `<div class="groups-badge-list">${gruposHtml}</div>`;
        } else {
            gruposHtml = '<span style="color: #aaa;">—</span>';
        }

        // Build Contact HTML
        let contactoHtml = '<div class="contact-badges">';

        // WhatsApp
        if (est.celular) {
            const cleanPhone = est.celular.replace(/\D/g, ''); // Remove non-digits
            if (cleanPhone.length >= 7) { // Basic validation
                contactoHtml += `
                            <a href="https://wa.me/${cleanPhone}" target="_blank" class="contact-badge btn-whatsapp" title="WhatsApp: ${escapeHtml(est.celular)}">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        `;
            }
        }

        // Email
        if (est.email) {
            contactoHtml += `
                        <a href="mailto:${escapeHtml(est.email)}" class="contact-badge btn-email" title="Email: ${escapeHtml(est.email)}">
                            <i class="fas fa-envelope"></i>
                        </a>
                    `;
        }

        if (!est.celular && !est.email) {
            contactoHtml += '<span style="color: #aaa; font-size: 12px;">—</span>';
        }

        contactoHtml += '</div>';

        // Reconstruct simple CSV strings for data attributes (compatibility)
        let gruposStr = Object.values(gruposMap).map(g => g.nombre).join(', ');
        let categoriasSet = new Set();
        Object.values(gruposMap).forEach(g => {
            g.categorias.forEach(c => categoriasSet.add(c.nombre));
        });
        const categoriasStr = Array.from(categoriasSet).join(', ');

        // Hierarchy Visuals
        const isMenor = est.es_menor == 1;
        const isRepresentante = Number(est.num_hijos || 0) > 0;
        const repIdStr = est.representante_id ? String(est.representante_id) : '';
        const menorDeRepresentanteVisible = isMenor && repIdStr && representantesEnPagina.has(repIdStr);
        const hasMenoresEnPagina = !isMenor && menoresPorRepresentante.has(String(est.id));

        let prefijoNombre = '<span style="display:inline-block; width: 23px;"></span>';
        let rowStyle = '';

        if (isMenor) {
            prefijoNombre = '<span style="color: #bdc3c7; margin-right: 8px; margin-left: 15px;"><i class="fas fa-level-up-alt fa-rotate-90"></i></span>';
            rowStyle = 'background-color: #fef9e7;'; // Light yellow for minors
        } else if (isRepresentante) {
            rowStyle = 'background-color: #eaf2f8;'; // Light blue for representatives
        }

        if (!isMenor && isRepresentante) {
            const repUniqueId = `rep-${est.id}`;
            if (hasMenoresEnPagina) {
                prefijoNombre = `<i id="toggle-${repUniqueId}" class="fas fa-chevron-right" onclick="toggleMenoresIndex('${repUniqueId}')" style="cursor: pointer; margin-right: 8px; color: #3498db; width: 15px; text-align: center; display: inline-block;" title="Ver menores"></i>`;
            } else {
                prefijoNombre = '<i class="fas fa-chevron-right" style="cursor: not-allowed; margin-right: 8px; color: #95a5a6; opacity: .55; width: 15px; text-align: center; display: inline-block;" title="No hay menores visibles con los filtros/página actual"></i>';
            }
        }

        let badgesHtml = '';
        if (isRepresentante) {
            badgesHtml += '<span class="badge" title="Representante" style="background: #3498db; color: white; font-size: 11px; margin-left: 5px; padding: 5px 8px;"><i class="fas fa-user-tie"></i></span>';
        }
        if (isMenor) {
            badgesHtml += '<span class="badge" title="Menor" style="background: #f1c40f; color: white; font-size: 11px; margin-left: 5px; padding: 5px 8px;"><i class="fas fa-child"></i></span>';
        }

        const tieneReferencias = parseInt(est.tiene_referencias || 0, 10) > 0;

        // Cálculo de Edad
        let edadHtml = '<span style="color: #aaa;">—</span>';
        if (est.fecha_nacimiento) {
            const birthDate = new Date(est.fecha_nacimiento);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            // Formato fecha: d/m/Y
            const fechaDisplay = est.fecha_nacimiento.split('-').reverse().join('/');
            edadHtml = `
                        <div style="line-height: 1.2;">
                            <div style="font-size: 13px; color: #5a6c7d;"><i class="fas fa-calendar-alt" style="color: #a0aec0; margin-right: 5px;"></i>${fechaDisplay}</div>
                            <small style="color: #7f8c8d; font-weight: 600;">(${age} años)</small>
                        </div>
                    `;
        }

        const rowClasses = menorDeRepresentanteVisible ? `menor-de-rep-${repIdStr}` : '';
        const rowInlineStyle = `${rowStyle}${menorDeRepresentanteVisible ? 'display:none;' : ''}`;
        const btnEditarHtml = isMenor
            ? `<button class="btn-icon btn-edit" disabled title="Edición no disponible para menores" style="opacity:.45; cursor:not-allowed;"><i class="fas fa-edit"></i></button>`
            : `<button class="btn-icon btn-edit" onclick="editarEstudiante(${est.id})" title="Editar Estudiante"><i class="fas fa-edit"></i></button>`;

        return `
                <tr data-estudiante-id="${est.id}" class="${rowClasses}" style="${rowInlineStyle}">
                    <td class="checkbox-cell sticky-col sticky-left-1">
                        <input type="checkbox" class="select-checkbox estudiante-checkbox" 
                               value="${est.id}" 
                               data-nombre="${escapeHtml(est.nombre)}"
                               data-cedula="${escapeHtml(est.cedula || '')}"
                               data-grupos="${escapeHtml(gruposStr)}"
                               data-categorias="${escapeHtml(categoriasStr)}"
                               onchange="actualizarSeleccion()">
                    </td>
                    <td class="nombre-cell sticky-col sticky-left-2">
                        ${prefijoNombre}
                        ${est.destacado == 1 ? '<i class="fas fa-star" style="color: #f39c12; margin-right: 5px;"></i>' : ''}
                        ${escapeHtml(est.nombre)}
                        ${tieneReferencias ? `<i class="fas fa-address-book icon-ref" onclick="verReferencias(${est.id})" title="Ver Referencias"></i>` : ''}
                        ${badgesHtml}
                    </td>
                    <td>
                        ${est.cedula ? `<span class="cedula-cell">${escapeHtml(est.cedula)}</span>` : '<span style="color: #aaa;">—</span>'}
                    </td>
                    <td>${edadHtml}</td>
                    <td>${contactoHtml}</td>
                    <td>${gruposHtml}</td>
                    <td>
                        ${est.activo == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-error" style="background: #e74c3c; color: white;">Inactivo</span>'}
                    </td>
                    <td>
                        <span style="font-size: 12px; color: #5a6c7d;">${est.fecha_creacion || '-'}</span>
                    </td>
                    <td>
                        <span style="font-size: 12px; color: #5a6c7d;">${est.fecha_actualizacion || '-'}</span>
                    </td>
                    <td>
                        <span class="badge ${est.total_certificados > 0 ? 'badge-categoria' : ''}" style="${est.total_certificados == 0 ? 'background: #f0f0f0; color: #999;' : ''}">
                            ${est.total_certificados || 0} certificado(s)
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-icon btn-view" onclick="abrirModalHistorial(${est.id})" title="Ver Historial" style="margin: 0 auto;">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                    <td class="sticky-col sticky-right">
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="verInfoGrupos(${est.id})" title="Ver Grupos y Categorías" style="color:#5e35b1; background:#ede7f6;">
                                <i class="fas fa-layer-group"></i>
                            </button>
                            <button class="btn-icon btn-certificate" onclick="viewEstudiante(${est.id})" title="Visualizar Certificados">
                                <i class="fas fa-certificate"></i>
                            </button>
                            ${btnEditarHtml}
                            <button class="btn-icon btn-delete" onclick="deleteEstudiante(${est.id}, '${escapeHtml(est.nombre)}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');

    // Resetear el checkbox "seleccionar todos"
    document.getElementById('selectAll').checked = false;
}

function updatePagination() {
    totalPagesGlobal = Math.ceil(totalItems / perPage) || 1;

    // Update counts badge
    const badge = document.getElementById('countBadge');
    if (badge) badge.textContent = `${totalItems} estudiantes`;

    // Update footer info
    const start = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, totalItems);

    document.getElementById('pagStart').textContent = start;
    document.getElementById('pagEnd').textContent = end;
    document.getElementById('pagTotal').textContent = totalItems;

    // Update pages inputs
    const pageInput = document.getElementById('pageInput');
    pageInput.value = currentPage;
    pageInput.max = totalPagesGlobal;

    document.getElementById('totalPages').textContent = totalPagesGlobal;

    // Validate buttons
    document.getElementById('btnPageFirst').disabled = currentPage <= 1;
    document.getElementById('btnPagePrev').disabled = currentPage <= 1;
    document.getElementById('btnPageNext').disabled = currentPage >= totalPagesGlobal;
    document.getElementById('btnPageLast').disabled = currentPage >= totalPagesGlobal;
}

// New Pagination Functions
function cambiarFilasPorPagina(val) {
    perPage = parseInt(val);
    // If -1 (all), set a very high number
    if (perPage === -1) perPage = 100000;
    irPagina(1);
}

function irPagina(pag) {
    if (pag === 'last') pag = totalPagesGlobal;
    pag = parseInt(pag);

    if (isNaN(pag) || pag < 1) pag = 1;
    if (pag > totalPagesGlobal) pag = totalPagesGlobal;

    currentPage = pag;
    loadEstudiantes();
}

function prevPagina() {
    irPagina(currentPage - 1);
}

function nextPagina() {
    irPagina(currentPage + 1);
}

function irPaginaManual(val) {
    irPagina(val);
}

function applyFilters() {
    currentFilters.search = document.getElementById('searchInput').value;
    currentFilters.grupo_id = document.getElementById('grupoFilter').value;
    currentFilters.categoria_id = document.getElementById('categoriaFilter').value;
    currentPage = 1;
    actualizarBotonCertificados();
    loadEstudiantes();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('grupoFilter').value = '';
    document.getElementById('categoriaFilter').value = '';
    currentFilters = { search: '', grupo_id: '', categoria_id: '' };

    // Mostrar todas las categorías
    filterCategoriasByGrupo('');

    currentPage = 1;
    actualizarBotonCertificados();
    loadEstudiantes();
}

async function viewEstudiante(id) {
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Cargando...</p></div>';
    document.getElementById('modalOverlay').classList.add('active');

    try {
        const response = await fetch(`../api/estudiantes/index.php?action=get_details&id=${id}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        const est = data.estudiante;
        const certificados = data.certificados || [];

        // Group certificates by Group
        const gruposCert = {};

        certificados.forEach(cert => {
            // Default group if null
            const gId = cert.grupo_id || '999999';
            const gNombre = cert.grupo_nombre || 'Sin Grupo';

            if (!gruposCert[gId]) {
                gruposCert[gId] = {
                    nombre: gNombre,
                    certificados: []
                };
            }
            gruposCert[gId].certificados.push(cert);
        });

        // Generate Tabs HTML
        let tabsHtml = '<div class="tabs-nav">';
        let contentHtml = '';

        const grupoKeys = Object.keys(gruposCert);
        const hasCerts = grupoKeys.length > 0;

        if (hasCerts) {
            grupoKeys.forEach((gId, index) => {
                const activeClass = index === 0 ? 'active' : '';
                const grupo = gruposCert[gId];

                tabsHtml += `
                            <button class="tab-btn ${activeClass}" onclick="switchModalTab('tab-${gId}')">
                                ${escapeHtml(grupo.nombre)} <span class="badge badge-light" style="font-size: 0.8em;">${grupo.certificados.length}</span>
                            </button>
                        `;

                // Generate Cards
                const cardsHtml = grupo.certificados.map(cert => {
                    const iconColor = cert.categoria_color || '#e67e22';
                    const iconClass = cert.categoria_icono || 'fas fa-certificate';

                    let iconHtml = '';
                    if (iconClass.indexOf('fa-') > -1) {
                        iconHtml = `<i class="${iconClass}" style="color: ${iconColor}; font-size: 30px;"></i>`;
                    } else {
                        iconHtml = `<span style="font-size: 30px; font-weight: bold; color: ${iconColor};">${iconClass}</span>`;
                    }

                    return `
                                <div class="cert-card">
                                    <div class="cert-icon-area">
                                        ${iconHtml}
                                    </div>
                                    <div class="cert-info-area">
                                        <div class="cert-title">
                                            ${escapeHtml(cert.categoria_nombre || 'CERTIFICADO')}
                                        </div>
                                        <div class="cert-dates-row">
                                            <div class="cert-date-col">
                                                <div class="cert-date-label">Generado</div>
                                                <div class="cert-date-value">${formatDate(cert.fecha)}</div>
                                            </div>
                                        </div>
                                        <div class="cert-code">${escapeHtml(cert.codigo)}</div>
                                    </div>
                                    <div class="cert-actions-col">
                                        <a href="../certificados/descargar.php?codigo=${cert.codigo}" target="_blank" class="cert-action-btn" title="Descargar PDF">
                                            <i class="fas fa-arrow-down"></i>
                                        </a>
                                    </div>
                                </div>
                            `;
                }).join('');

                contentHtml += `
                            <div id="tab-${gId}" class="tab-content ${activeClass}">
                                ${cardsHtml}
                            </div>
                        `;
            });
        }

        tabsHtml += '</div>';

        // If no certs
        if (!hasCerts) {
            tabsHtml = '';
            contentHtml = '<div style="padding: 20px; text-align: center; color: #999;">Este estudiante aún no tiene certificados.</div>';
        }

        modalContent.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <div class="detail-row" style="border: none; padding: 5px 0;">
                            <div class="detail-label">Nombre:</div>
                            <div class="detail-value" style="font-weight: 600;">${escapeHtml(est.nombre)}</div>
                        </div>
                        <div class="detail-row" style="border: none; padding: 5px 0;">
                            <div class="detail-label">Cédula:</div>
                            <div class="detail-value">${est.cedula || '—'}</div>
                        </div>
                        <div class="detail-row" style="border: none; padding: 5px 0;">
                            <div class="detail-label">Contacto:</div>
                            <div class="detail-value">${est.celular || est.email || '—'}</div>
                        </div>
                        <div class="detail-row" style="border: none; padding: 5px 0;">
                            <div class="detail-label">Registrado:</div>
                            <div class="detail-value">${formatDate(est.fecha_creacion)}</div>
                        </div>
                    </div>
                    
                    <div class="certificados-list">
                        <h4 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-folder-open"></i> Certificados por Grupo</h4>
                        ${tabsHtml}
                        ${contentHtml}
                    </div>
                `;

    } catch (error) {
        modalContent.innerHTML = `<p style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> ${error.message}</p>`;
    }
}

function sanitizarCedulaEdit(input) {
    if (!input) return;
    input.value = String(input.value || '').replace(/\D/g, '').slice(0, 10);
}

function sanitizarCelularEdit(input) {
    if (!input) return;
    input.value = String(input.value || '').replace(/\D/g, '').slice(0, 9);
}

function esMenorPorFechaNacimiento(fechaRaw) {
    const edad = calcularEdadDesdeFecha(fechaRaw);
    return edad !== null && edad < 18;
}

function formatearFechaHoraSimple(fechaRaw) {
    const raw = String(fechaRaw || '').trim();
    if (!raw || raw === '0000-00-00') return '-';
    const [fechaPart, horaPart] = raw.split(' ');
    const fechaFmt = formatDate(fechaPart);
    if (horaPart) {
        return `${fechaFmt} ${horaPart.substring(0, 5)}`;
    }
    return fechaFmt;
}

function buildPeriodoBadgeEdit(periodo) {
    if (!periodo) {
        return '<span class="edit-pill edit-pill-periodo"><i class="far fa-calendar-alt"></i> Sin período</span>';
    }
    return `
        <span class="edit-pill edit-pill-periodo${periodo.es_destacado ? ' edit-pill-periodo-destacado' : ''}">
            <i class="far fa-calendar-alt"></i> ${escapeHtml(periodo.nombre || 'Sin período')}
            <span style="opacity:.85; margin-left:4px;">Reg: ${escapeHtml(formatearFechaMatriculaBadge(periodo.fecha_matricula || ''))}</span>
            ${periodo.es_destacado ? '<i class="fas fa-star edit-period-star" title="Destacado"></i>' : ''}
        </span>
    `;
}

function buildPeriodoBadgeInfo(periodo) {
    if (!periodo) {
        return '<span class="badge" style="background: #e8f0fe; color: #1967d2; font-size: 0.82em; border: 1px solid #d2e3fc; padding: 4px 8px;"><i class="far fa-calendar-alt"></i> Sin período</span>';
    }
    return `
        <span class="badge" style="background:${periodo.es_destacado ? '#fffbeb' : '#e8f0fe'}; color:${periodo.es_destacado ? '#92400e' : '#1967d2'}; font-size: 0.82em; border: 1px solid ${periodo.es_destacado ? '#fde68a' : '#d2e3fc'}; padding: 4px 8px;">
            <i class="far fa-calendar-alt"></i> ${escapeHtml(periodo.nombre || 'Sin período')}
            <span style="opacity:.85; margin-left:4px;">Reg: ${escapeHtml(formatearFechaMatriculaBadge(periodo.fecha_matricula || ''))}</span>
            ${periodo.es_destacado ? '<i class="fas fa-star" style="margin-left:5px; color:#f59e0b;"></i>' : ''}
        </span>
    `;
}

function parseEnrollmentDataForEditModal(enrollmentRaw) {
    const gruposMap = {};
    const raw = String(enrollmentRaw || '').trim();
    if (!raw) return [];

    raw.split('||').forEach(enroll => {
        if (!enroll) return;
        const parts = enroll.split('##');
        if (parts.length < 8) return;

        const gId = parts[0] || '0';
        const gNombre = parts[1] || 'Sin grupo';
        const gColor = parts[2] || '#94a3b8';
        const gIcono = parts[3] || '👥';
        const cId = parts[4] || '';
        const cNombre = parts[5] || 'Sin categoría';
        const cIcono = parts[6] || '📁';
        const cPeriodo = parts[7] || 'Sin período';
        const cEsDestacado = (parts[8] || '0') === '1';
        const cPeriodoFecha = parts[9] || '0000-00-00';
        const cFechaMatricula = parts[10] || '';

        if (!gruposMap[gId]) {
            gruposMap[gId] = {
                id: gId,
                nombre: gNombre,
                color: gColor,
                icono: gIcono,
                categoriasMap: {}
            };
        }

        const categoriaKey = String(cId || cNombre || '').trim().toLowerCase() || `cat_${gId}`;
        if (!gruposMap[gId].categoriasMap[categoriaKey]) {
            gruposMap[gId].categoriasMap[categoriaKey] = {
                id: cId,
                nombre: cNombre,
                icono: cIcono,
                fecha_matricula: '',
                periodos: [],
                _periodosMap: {}
            };
        }

        const categoriaRef = gruposMap[gId].categoriasMap[categoriaKey];

        if (cFechaMatricula) {
            if (!categoriaRef.fecha_matricula || cFechaMatricula < categoriaRef.fecha_matricula) {
                categoriaRef.fecha_matricula = cFechaMatricula;
            }
        }

        const periodoKey = `${cPeriodo}::${cPeriodoFecha}`;
        if (!categoriaRef._periodosMap[periodoKey]) {
            const periodoObj = {
                nombre: cPeriodo,
                fecha_inicio: cPeriodoFecha,
                fecha_matricula: cFechaMatricula || '',
                es_destacado: cEsDestacado
            };
            categoriaRef.periodos.push(periodoObj);
            categoriaRef._periodosMap[periodoKey] = periodoObj;
        } else if (cEsDestacado) {
            categoriaRef._periodosMap[periodoKey].es_destacado = true;
        }

        if (categoriaRef._periodosMap[periodoKey]) {
            const periodoExistente = categoriaRef._periodosMap[periodoKey];
            if (cFechaMatricula) {
                if (!periodoExistente.fecha_matricula || cFechaMatricula < periodoExistente.fecha_matricula) {
                    periodoExistente.fecha_matricula = cFechaMatricula;
                }
            }
        }
    });

    return Object.values(gruposMap)
        .map(g => {
            const categorias = Object.values(g.categoriasMap).map(cat => {
                cat.periodos.sort((a, b) => {
                    const fechaA = String(a.fecha_inicio || '0000-00-00');
                    const fechaB = String(b.fecha_inicio || '0000-00-00');
                    if (fechaA === fechaB) {
                        return String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' });
                    }
                    return fechaA < fechaB ? -1 : 1;
                });
                delete cat._periodosMap;
                return cat;
            });

            categorias.sort((a, b) => String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' }));

            return {
                id: g.id,
                nombre: g.nombre,
                color: g.color,
                icono: g.icono,
                categorias
            };
        })
        .sort((a, b) => String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' }));
}

function renderResumenInscripcionEditModal(enrollmentRaw) {
    const container = document.getElementById('editEnrollmentResumen');
    if (!container) return;

    const grupos = parseEnrollmentDataForEditModal(enrollmentRaw || '');
    if (!grupos.length) {
        container.innerHTML = `
            <div class="empty-state" style="padding: 18px 0;">
                <i class="fas fa-layer-group"></i>
                <p>Este estudiante no tiene grupos/categorías registrados.</p>
            </div>
        `;
        return;
    }

    const html = grupos.map(grupo => {
        const iconoGrupo = renderGrupoIcono(grupo.icono);
        const categoriasHtml = grupo.categorias.map(cat => {
            const iconoCat = renderCategoriaIcono(cat.icono);
            const periodos = cat.periodos || [];
            const primerPeriodo = periodos[0] || null;
            const periodosAdicionales = periodos.slice(1);
            const primerBadge = buildPeriodoBadgeEdit(primerPeriodo);
            const periodosAdicionalesHtml = periodosAdicionales.map(buildPeriodoBadgeEdit).join('');
            const mostrarDespliegue = periodosAdicionales.length > 0;

            return `
                <div class="edit-enrollment-cat">
                    <div class="edit-enrollment-cat-main">
                        <span class="edit-enrollment-cat-main-left">
                            <span>${iconoCat}</span>
                            <span class="edit-enrollment-cat-name">${escapeHtml(cat.nombre)}</span>
                        </span>
                        <span class="edit-enrollment-cat-main-right">
                            ${primerBadge}
                            ${mostrarDespliegue ? `
                                <details class="periodos-inline-dropdown">
                                    <summary class="periodos-inline-summary" title="Ver períodos adicionales">
                                        <i class="fas fa-chevron-down periodos-dropdown-chevron"></i>
                                    </summary>
                                    <div class="periodos-inline-content">
                                        ${periodosAdicionalesHtml}
                                    </div>
                                </details>
                            ` : ''}
                        </span>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="edit-enrollment-group">
                <div class="edit-enrollment-group-header" style="color:${escapeHtml(grupo.color)};">
                    <span>${iconoGrupo}</span>
                    <span>${escapeHtml(grupo.nombre)}</span>
                </div>
                ${categoriasHtml}
            </div>
        `;
    }).join('');

    container.innerHTML = html;
}

function bloquearFormularioEdicion(esBloqueado) {
    const form = document.getElementById('editEstudianteForm');
    const warning = document.getElementById('editMinorWarning');
    const btnGuardar = document.getElementById('btnGuardarEdicionEstudiante');
    if (!form) return;

    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type === 'hidden') return;
        el.disabled = esBloqueado;
    });

    if (warning) warning.style.display = esBloqueado ? 'flex' : 'none';
    if (btnGuardar) btnGuardar.disabled = esBloqueado;
}

async function editarEstudiante(id) {
    const overlay = document.getElementById('modalEditarOverlay');
    const form = document.getElementById('editEstudianteForm');
    if (!overlay || !form) return;

    overlay.classList.add('active');
    form.reset();
    document.getElementById('edit_estudiante_id').value = String(id);
    bloquearFormularioEdicion(false);
    renderResumenInscripcionEditModal('');

    const btnGuardar = document.getElementById('btnGuardarEdicionEstudiante');
    const estado = document.getElementById('editModalEstado');
    let bloqueoPorMenor = false;
    if (btnGuardar) btnGuardar.disabled = true;
    if (estado) estado.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando datos del estudiante...';

    try {
        const response = await fetch(`../api/estudiantes/index.php?action=get_details&id=${encodeURIComponent(id)}`);
        const data = await response.json();
        if (!response.ok || !data.success || !data.estudiante) {
            throw new Error(data.message || 'No se pudo cargar la información del estudiante');
        }

        const est = data.estudiante;
        const estListado = estudiantesData.find(e => String(e.id) === String(est.id || id));

        document.getElementById('edit_estudiante_id').value = est.id || id;
        document.getElementById('edit_es_menor_actual').value = Number(est.es_menor || 0) === 1 ? '1' : '0';
        document.getElementById('edit_nombre').value = est.nombre || '';
        document.getElementById('edit_cedula').value = est.cedula || '';
        document.getElementById('edit_celular').value = normalizarTelefonoLocalEC(est.celular || '');
        document.getElementById('edit_email').value = est.email || '';
        document.getElementById('edit_fecha_nacimiento').value = est.fecha_nacimiento || '';

        const metaRegistro = document.getElementById('editMetaRegistro');
        const metaActualizacion = document.getElementById('editMetaActualizacion');
        if (metaRegistro) metaRegistro.textContent = formatearFechaHoraSimple(est.fecha_creacion);
        if (metaActualizacion) metaActualizacion.textContent = formatearFechaHoraSimple(est.fecha_actualizacion);

        const enrollmentRaw = (est && est.enrollment_data) || (estListado && estListado.enrollment_data) || '';
        renderResumenInscripcionEditModal(enrollmentRaw);

        const esMenorInicial = Number(est.es_menor || 0) === 1 || esMenorPorFechaNacimiento(est.fecha_nacimiento || '');
        bloqueoPorMenor = esMenorInicial;
        bloquearFormularioEdicion(esMenorInicial);

        if (estado) estado.textContent = '';
    } catch (error) {
        if (estado) estado.textContent = '';
        alert(error.message || 'No se pudo abrir el formulario de edición');
        cerrarModalEditar();
    } finally {
        if (btnGuardar) btnGuardar.disabled = bloqueoPorMenor;
    }
}

function cerrarModalEditar(event) {
    if (!event || event.target.id === 'modalEditarOverlay') {
        const overlay = document.getElementById('modalEditarOverlay');
        if (overlay) overlay.classList.remove('active');
    }
}

async function guardarEdicionEstudiante(event) {
    event.preventDefault();

    const id = (document.getElementById('edit_estudiante_id') || {}).value;
    const nombre = (document.getElementById('edit_nombre') || {}).value || '';
    if (!id || !String(nombre).trim()) {
        alert('El nombre del estudiante es obligatorio');
        return;
    }
    const esMenorActual = String((document.getElementById('edit_es_menor_actual') || {}).value || '0') === '1';
    if (esMenorActual || esMenorPorFechaNacimiento((document.getElementById('edit_fecha_nacimiento') || {}).value || '')) {
        alert('La edición de estudiantes menores de edad no está habilitada en este modal.');
        return;
    }

    const payload = {
        action: 'update',
        id: String(id).trim(),
        nombre: String(nombre).trim(),
        cedula: String((document.getElementById('edit_cedula') || {}).value || '').trim(),
        celular: construirTelefonoApiEC((document.getElementById('edit_celular') || {}).value || ''),
        email: String((document.getElementById('edit_email') || {}).value || '').trim(),
        fecha_nacimiento: String((document.getElementById('edit_fecha_nacimiento') || {}).value || '').trim(),
        es_menor: 0
    };

    const btnGuardar = document.getElementById('btnGuardarEdicionEstudiante');
    const estado = document.getElementById('editModalEstado');

    try {
        if (btnGuardar) btnGuardar.disabled = true;
        if (estado) estado.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando cambios...';

        const response = await fetch('../api/estudiantes/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo actualizar el estudiante');
        }

        cerrarModalEditar();
        await loadEstudiantes();
    } catch (error) {
        alert(error.message || 'Error al guardar cambios');
    } finally {
        if (btnGuardar) btnGuardar.disabled = false;
        if (estado) estado.textContent = '';
    }
}

// Better Switcher
window.switchModalTab = function (tabId) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(t => t.classList.remove('active'));

    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');

    // Buttons
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(b => {
        if (b.getAttribute('onclick').includes(tabId)) {
            b.classList.add('active');
        } else {
            b.classList.remove('active');
        }
    });
};

function closeModal(event) {
    if (!event || event.target.id === 'modalOverlay') {
        const overlay = document.getElementById('modalOverlay');
        if (overlay) overlay.classList.remove('active');
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closeModal();
    cerrarModalCertificado();
    cerrarModalEditar();
    cerrarModalHistorial();
    cerrarModalReferencias();
    cerrarInfoGruposModal();
});

function cerrarModalHistorial(event) {
    if (!event || event.target.id === 'modalHistorialOverlay') {
        const overlay = document.getElementById('modalHistorialOverlay');
        if (overlay) overlay.classList.remove('active');
    }
}

function cerrarModalReferencias(event) {
    if (!event || event.target.id === 'modalReferenciasOverlay') {
        const overlay = document.getElementById('modalReferenciasOverlay');
        if (overlay) overlay.classList.remove('active');
    }
}

function obtenerMetaAccionAuditoria(accion) {
    switch (accion) {
    case 'creacion':
        return { texto: 'Creación', color: '#2ecc71', icono: 'fa-plus-circle' };
    case 'actualizacion':
        return { texto: 'Actualización', color: '#f39c12', icono: 'fa-edit' };
    case 'eliminacion':
        return { texto: 'Eliminación', color: '#e74c3c', icono: 'fa-trash-alt' };
    case 'cambio_categoria':
        return { texto: 'Cambio Categoría', color: '#9b59b6', icono: 'fa-exchange-alt' };
    default:
        return { texto: 'Acción', color: '#3498db', icono: 'fa-info-circle' };
    }
}

function formatearFechaAuditoria(fechaRaw) {
    if (!fechaRaw) return '-';
    const fechaObj = new Date(String(fechaRaw).replace(' ', 'T'));
    if (isNaN(fechaObj.getTime())) return escapeHtml(String(fechaRaw));
    return fechaObj.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function renderizarDetallesAuditoria(detallesRaw) {
    if (!detallesRaw) return '-';

    try {
        const parsed = typeof detallesRaw === 'string' ? JSON.parse(detallesRaw) : detallesRaw;

        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            const lines = [];

            Object.keys(parsed).forEach(key => {
                const value = parsed[key];
                const keySafe = escapeHtml(String(key));

                if (value && typeof value === 'object' && !Array.isArray(value) && ('old' in value || 'new' in value)) {
                    const oldVal = value.old == null || value.old === '' ? 'vacío' : escapeHtml(String(value.old));
                    const newVal = value.new == null || value.new === '' ? 'vacío' : escapeHtml(String(value.new));
                    lines.push(`<strong>${keySafe}:</strong> ${oldVal} <i class="fas fa-arrow-right" style="opacity:.6;"></i> ${newVal}`);
                    return;
                }

                if (Array.isArray(value)) {
                    const arrText = value.map(v => escapeHtml(String(v))).join(', ');
                    lines.push(`<strong>${keySafe}:</strong> ${arrText || '[]'}`);
                    return;
                }

                if (value && typeof value === 'object') {
                    lines.push(`<strong>${keySafe}:</strong> ${escapeHtml(JSON.stringify(value))}`);
                    return;
                }

                lines.push(`<strong>${keySafe}:</strong> ${value == null || value === '' ? 'vacío' : escapeHtml(String(value))}`);
            });

            return lines.length ? lines.join('<br>') : '-';
        }

        if (Array.isArray(parsed)) {
            return escapeHtml(parsed.map(v => String(v)).join(', '));
        }

        return escapeHtml(String(parsed));
    } catch (_error) {
        return escapeHtml(String(detallesRaw));
    }
}

async function abrirModalHistorial(estudianteId) {
    const overlay = document.getElementById('modalHistorialOverlay');
    const container = document.getElementById('historialContainer');
    if (!overlay || !container) return;

    overlay.classList.add('active');
    container.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Cargando historial del estudiante...</p>
        </div>
    `;

    try {
        const response = await fetch(`../api/categorias/estudiantes.php?action=historial_auditoria&estudiante_id=${encodeURIComponent(estudianteId)}`);
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo cargar el historial');
        }

        if (!Array.isArray(data.historial) || data.historial.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 30px 20px;">
                    <i class="fas fa-history"></i>
                    <h3>Sin historial</h3>
                    <p>No se encontraron registros para este estudiante.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.historial.map(item => {
            const accionMeta = obtenerMetaAccionAuditoria(item.accion);
            const fechaFmt = formatearFechaAuditoria(item.fecha);
            const usuario = escapeHtml(item.usuario_nombre || 'Sistema / Desconocido');
            const detalles = renderizarDetallesAuditoria(item.detalles);

            return `
                <div class="audit-item" style="border-left-color: ${accionMeta.color};">
                    <div class="audit-header">
                        <span><i class="fas ${accionMeta.icono}" style="color:${accionMeta.color}; margin-right: 6px;"></i>${fechaFmt}</span>
                        <span style="color: #4b5563;"><i class="fas fa-user-circle" style="margin-right: 5px;"></i>${usuario}</span>
                    </div>
                    <div class="audit-action">${accionMeta.texto}</div>
                    <p class="audit-details">${detalles}</p>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error cargando historial:', error);
        container.innerHTML = `
            <div class="empty-state" style="padding: 30px 20px;">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <h3>Error al cargar</h3>
                <p>${escapeHtml(error.message || 'No fue posible cargar el historial.')}</p>
            </div>
        `;
    }
}

async function verReferencias(estudianteId) {
    const overlay = document.getElementById('modalReferenciasOverlay');
    const title = document.getElementById('modalReferenciasTitle');
    const body = document.getElementById('modalReferenciasBody');
    if (!overlay || !title || !body) return;

    overlay.classList.add('active');
    title.innerHTML = '<i class="fas fa-address-book"></i> Referencias';
    body.innerHTML = `
        <div style="text-align:center; padding:20px; color:#7f8c8d;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
            <p>Cargando referencias...</p>
        </div>
    `;

    try {
        const response = await fetch(`../api/categorias/estudiantes.php?action=listar_referencias&estudiante_id=${encodeURIComponent(estudianteId)}`);
        const data = await response.json();

        title.innerHTML = `<i class="fas fa-address-book"></i> Referencias de ${escapeHtml(data.estudiante_nombre || 'Estudiante')}`;

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudieron cargar las referencias');
        }

        if (!Array.isArray(data.referencias) || data.referencias.length === 0) {
            body.innerHTML = `
                <div style="text-align:center; padding:30px; color:#95a5a6;">
                    <i class="fas fa-user-slash" style="font-size:48px; margin-bottom:15px; opacity:0.3;"></i>
                    <p style="font-size:15px; margin:0;">No se han registrado referencias para este estudiante.</p>
                </div>
            `;
            return;
        }

        let html = '<div class="ref-modal-list">';
        data.referencias.forEach((ref, i) => {
            const telefonoDisplay = formatearTelefonoReferencia(ref.telefono);
            const telefonoLink = telefonoDisplay
                ? `<a href="${generarLinkWhatsAppReferencia(ref.telefono, ref.nombre, ref.relacion, data.estudiante_nombre || 'Estudiante')}" target="_blank" class="ref-phone-link"><i class="fab fa-whatsapp"></i> ${escapeHtml(telefonoDisplay)}</a>`
                : '<span class="ref-phone-empty">Sin teléfono</span>';

            html += `
                <div class="ref-modal-card">
                    <div class="ref-modal-number">${i + 1}</div>
                    <div class="ref-modal-info">
                        <div class="ref-modal-name">${escapeHtml(ref.nombre || 'Referencia')}</div>
                        <div class="ref-modal-details">
                            ${telefonoLink}
                            ${ref.relacion ? `<span class="ref-relacion-badge">${escapeHtml(ref.relacion)}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        body.innerHTML = html;
    } catch (error) {
        body.innerHTML = `
            <div style="text-align:center; padding:20px; color:#e74c3c;">
                <i class="fas fa-exclamation-triangle" style="font-size:24px;"></i>
                <p>${escapeHtml(error.message || 'Error al cargar las referencias')}</p>
            </div>
        `;
    }
}

async function deleteEstudiante(id, nombre) {
    if (!confirm(`¿Estás seguro de eliminar al estudiante "${nombre}"?\n\nEsta acción eliminará también sus certificados asociados.`)) {
        return;
    }

    try {
        const response = await fetch('../api/estudiantes/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        alert('Estudiante eliminado correctamente');
        loadEstudiantes();

    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Mostrar/ocultar botón de certificados según categoría seleccionada
function actualizarBotonCertificados() {
    const btn = document.getElementById('btnVerCertificados');
    if (!btn) return;
    if (currentFilters.categoria_id) {
        btn.style.display = 'inline-flex';
        btn.dataset.categoriaId = currentFilters.categoria_id;
        btn.dataset.grupoId = currentFilters.grupo_id || '';
    } else {
        btn.style.display = 'none';
    }
}

function irACertificados() {
    const btn = document.getElementById('btnVerCertificados');
    if (!btn || !btn.dataset.categoriaId) return;
    const params = new URLSearchParams();
    params.set('categoria', btn.dataset.categoriaId);
    if (btn.dataset.grupoId) params.set('grupo', btn.dataset.grupoId);
    window.location.href = `../certificados/visualizar.php?${params.toString()}`;
}

function exportarLista(tipo) {
    if (!currentFilters.grupo_id) {
        alert('Por favor, seleccione un grupo primero para exportar la lista.');
        return;
    }

    const params = new URLSearchParams();
    params.set('grupo', currentFilters.grupo_id);
    if (currentFilters.categoria_id) params.set('categoria', currentFilters.categoria_id);
    if (currentFilters.search) params.set('busqueda', currentFilters.search);

    // Periodo?? The script checks for 'periodo', but we don't have a global period filter in the UI currently?
    // We can assume it exports all periods unless we add a specific filter.

    let url = '';
    if (tipo === 'excel') {
        url = `exportar_excel.php?${params.toString()}`;
    } else if (tipo === 'pdf') {
        url = `exportar_pdf.php?${params.toString()}`;
    }

    if (url) window.open(url, '_blank');
}

function toggleExportMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('exportMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

// Close export menu when clicking outside
document.addEventListener('click', function (event) {
    const menu = document.getElementById('exportMenu');
    if (menu && menu.style.display === 'block') {
        menu.style.display = 'none';
    }
});

// ====== GESTIÓN DE CERTIFICADOS ======

// Toggle seleccionar todos
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.estudiante-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    actualizarSeleccion();
}

// Actualizar lista de seleccionados
function actualizarSeleccion() {
    const checkboxes = document.querySelectorAll('.estudiante-checkbox:checked');
    estudiantesSeleccionados = Array.from(checkboxes).map(cb => ({
        id: cb.value,
        nombre: cb.dataset.nombre,
        cedula: cb.dataset.cedula,
        grupos: cb.dataset.grupos,
        categorias: cb.dataset.categorias
    }));

    const count = estudiantesSeleccionados.length;
    document.getElementById('selectedCount').textContent = count;

    // Mostrar/ocultar barra de acciones
    const bulkBar = document.getElementById('bulkActionsBar');
    if (count > 0) {
        bulkBar.classList.add('active');
    } else {
        bulkBar.classList.remove('active');
    }

    // Actualizar estado del checkbox "seleccionar todos"
    const allCheckboxes = document.querySelectorAll('.estudiante-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (allCheckboxes.length > 0) {
        selectAll.checked = checkboxes.length === allCheckboxes.length;
        selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
    }
}

function setupHorizontalScroll() {
    const tableWrapper = document.querySelector('.table-inner-scroll') || document.querySelector('.table-scroll-wrapper');
    if (tableWrapper) {
        tableWrapper.addEventListener('wheel', (evt) => {
            // Si hay contenido para scrollear horizontalmente
            if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                evt.preventDefault();
                tableWrapper.scrollLeft += evt.deltaY || evt.deltaX;
            }
        }, { passive: false });
    }
}

// Cancelar selección
function cancelarSeleccion() {
    document.querySelectorAll('.estudiante-checkbox').forEach(cb => {
        cb.checked = false;
    });
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.checked = false;
    estudiantesSeleccionados = [];
    const bulkBar = document.getElementById('bulkActionsBar');
    if (bulkBar) bulkBar.classList.remove('active');
}

// Abrir modal para certificado individual
function abrirModalCertificadoIndividual(estudianteId, nombre) {
    // Buscar el checkbox de este estudiante para obtener sus datos
    const checkbox = document.querySelector(`.estudiante-checkbox[value="${estudianteId}"]`);
    if (checkbox) {
        estudiantesSeleccionados = [{
            id: estudianteId,
            nombre: nombre,
            cedula: checkbox.dataset.cedula,
            grupos: checkbox.dataset.grupos,
            categorias: checkbox.dataset.categorias
        }];
    } else {
        estudiantesSeleccionados = [{
            id: estudianteId,
            nombre: nombre,
            cedula: '',
            grupos: '',
            categorias: ''
        }];
    }
    abrirModalGenerarCertificados();
}

// Abrir modal de generación de certificados
function abrirModalGenerarCertificados() {
    if (estudiantesSeleccionados.length === 0) {
        alert('Selecciona al menos un estudiante');
        return;
    }

    // Renderizar lista de estudiantes seleccionados
    const listContainer = document.getElementById('selectedStudentsList');
    listContainer.innerHTML = estudiantesSeleccionados.map(est => `
                <div class="selected-student-item" data-id="${est.id}">
                    <div>
                        <span class="student-name">${escapeHtml(est.nombre)}</span>
                        ${est.cedula ? `<span class="student-info"> - ${escapeHtml(est.cedula)}</span>` : ''}
                    </div>
                    <button type="button" class="btn-remove-student" onclick="removerEstudianteDelModal(${est.id})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');

    // Actualizar contador
    const cantCert = document.getElementById('cantidadCertificados');
    if (cantCert) cantCert.textContent = estudiantesSeleccionados.length;

    // Resetear formulario
    const cGrupo = document.getElementById('certGrupo');
    if (cGrupo) cGrupo.value = '';

    const cCat = document.getElementById('certCategoria');
    if (cCat) {
        cCat.value = '';
        cCat.disabled = true;
        cCat.innerHTML = '<option value="">Primero seleccione un grupo</option>';
    }

    const cRazon = document.getElementById('certRazon');
    if (cRazon) cRazon.value = '';

    const btnGen = document.getElementById('btnGenerarCertificados');
    if (btnGen) btnGen.disabled = true;

    // Mostrar modal
    const overlay = document.getElementById('modalCertificadoOverlay');
    if (overlay) overlay.classList.add('active');
}

// Remover estudiante del modal
function removerEstudianteDelModal(estudianteId) {
    estudiantesSeleccionados = estudiantesSeleccionados.filter(e => e.id != estudianteId);

    // También desmarcar el checkbox si está visible
    const checkbox = document.querySelector(`.estudiante-checkbox[value="${estudianteId}"]`);
    if (checkbox) checkbox.checked = false;

    if (estudiantesSeleccionados.length === 0) {
        cerrarModalCertificado();
        actualizarSeleccion();
        return;
    }

    // Re-renderizar lista
    const listContainer = document.getElementById('selectedStudentsList');
    listContainer.innerHTML = estudiantesSeleccionados.map(est => `
                <div class="selected-student-item" data-id="${est.id}">
                    <div>
                        <span class="student-name">${escapeHtml(est.nombre)}</span>
                        ${est.cedula ? `<span class="student-info"> - ${escapeHtml(est.cedula)}</span>` : ''}
                    </div>
                    <button type="button" class="btn-remove-student" onclick="removerEstudianteDelModal(${est.id})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');

    const cantCert = document.getElementById('cantidadCertificados');
    if (cantCert) cantCert.textContent = estudiantesSeleccionados.length;

    actualizarSeleccion();
}

// Cerrar modal de certificados
function cerrarModalCertificado(event) {
    if (!event || event.target.id === 'modalCertificadoOverlay') {
        const overlay = document.getElementById('modalCertificadoOverlay');
        if (overlay) overlay.classList.remove('active');
    }
}

// Cargar categorías por grupo
async function cargarCategoriasGrupo(grupoId) {
    const selectCategoria = document.getElementById('certCategoria');
    const btnGenerar = document.getElementById('btnGenerarCertificados');

    if (!grupoId) {
        selectCategoria.disabled = true;
        selectCategoria.innerHTML = '<option value="">Primero seleccione un grupo</option>';
        btnGenerar.disabled = true;
        return;
    }

    selectCategoria.disabled = true;
    selectCategoria.innerHTML = '<option value="">Cargando categorías...</option>';

    try {
        // Cargar categorías del grupo seleccionado
        const response = await fetch(`../api/categorias/index.php?action=listar&grupo_id=${grupoId}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Error al cargar categorías');
        }

        if (data.categorias && data.categorias.length > 0) {
            selectCategoria.innerHTML = '<option value="">Seleccione una categoría</option>' +
                data.categorias.map(cat =>
                    `<option value="${cat.id}">${escapeHtml(cat.nombre)}</option>`
                ).join('');
            selectCategoria.disabled = false;
        } else {
            selectCategoria.innerHTML = '<option value="">No hay categorías en este grupo</option>';
        }

        btnGenerar.disabled = true;

    } catch (error) {
        console.error('Error:', error);
        selectCategoria.innerHTML = '<option value="">Error al cargar</option>';
    }
}

// Generar certificados
async function generarCertificados() {
    const grupoId = document.getElementById('certGrupo').value;
    const categoriaId = document.getElementById('certCategoria').value;
    const fecha = document.getElementById('certFecha').value;
    const razon = document.getElementById('certRazon').value.trim();

    if (!grupoId || !categoriaId || !fecha) {
        alert('Por favor complete todos los campos requeridos');
        return;
    }

    if (estudiantesSeleccionados.length === 0) {
        alert('No hay estudiantes seleccionados');
        return;
    }

    const btnGenerar = document.getElementById('btnGenerarCertificados');
    const textoOriginal = btnGenerar.innerHTML;
    btnGenerar.disabled = true;
    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    try {
        const response = await fetch('../api/certificados/generar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generar_batch',
                grupo_id: grupoId,
                categoria_id: categoriaId,
                estudiantes_ids: estudiantesSeleccionados.map(e => e.id),
                fecha: fecha,
                razon: razon
            })
        });

        const data = await response.json();

        if (!data.success) {
            if (data.error_type === 'PLANTILLA_NO_CONFIGURADA') {
                alert(`⚠️ ${data.message}\n\n${data.detalle}`);
            } else {
                throw new Error(data.message || 'Error al generar certificados');
            }
            return;
        }

        // Mostrar resumen
        const exitosos = data.exitosos || 0;
        const errores = data.errores || 0;

        let mensaje = `✅ Se generaron ${exitosos} certificado(s) exitosamente.`;
        if (errores > 0) {
            mensaje += `\n⚠️ ${errores} certificado(s) no pudieron generarse.`;
        }

        alert(mensaje);

        // Cerrar modal y refrescar
        cerrarModalCertificado();
        cancelarSeleccion();
        loadEstudiantes();

    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    } finally {
        btnGenerar.disabled = false;
        btnGenerar.innerHTML = textoOriginal;
    }
}

function verInfoGrupos(estudianteId) {
    const estudiante = estudiantesData.find(e => e.id == estudianteId);
    if (!estudiante) return;

    const content = document.getElementById('infoGruposContent');

    let html = '';

    const grupos = parseEnrollmentDataForEditModal(estudiante.enrollment_data || '');
    if (grupos.length) {
        let tabsHtml = '<div class="modal-tabs-nav">';
        let contentHtml = '';

        grupos.forEach((grupo, index) => {
            const isActive = index === 0 ? 'active' : '';
            const tabId = `info-grupo-tab-${grupo.id}`;
            const gIconHTML = renderGrupoIcono(grupo.icono);

            tabsHtml += `
                        <button class="tab-btn ${isActive}" onclick="switchInfoTab('${tabId}')">
                             <span style="color:${escapeHtml(grupo.color)};">${gIconHTML}</span> ${escapeHtml(grupo.nombre)}
                        </button>
                    `;

            contentHtml += `
                        <div id="${tabId}" class="tab-content ${isActive}">
                            <div style="padding: 10px 0;">
                                ${grupo.categorias.map(cat => {
                const cIconHTML = renderCategoriaIcono(cat.icono);
                const periodos = cat.periodos || [];
                const primerPeriodo = periodos[0] || null;
                const periodosAdicionales = periodos.slice(1);
                const primerBadge = buildPeriodoBadgeInfo(primerPeriodo);
                const periodosAdicionalesHtml = periodosAdicionales.map(buildPeriodoBadgeInfo).join('');
                const mostrarDespliegue = periodosAdicionales.length > 0;

                return `
                                    <div style="margin-bottom: 8px; font-size: 0.95em; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed #eee; padding-bottom: 8px; gap: 10px;">
                                        <span style="display: inline-flex; align-items: center; gap: 8px; min-width:0; flex:1;">
                                            <span style="width: 20px; text-align: center;">${cIconHTML}</span>
                                            <span style="color: #2c3e50; font-weight: 500;">${escapeHtml(cat.nombre)}</span>
                                        </span>
                                        <span style="display:inline-flex; align-items:center; gap:6px; justify-content:flex-end; margin-left:auto;">
                                            ${primerBadge}
                                            ${mostrarDespliegue ? `
                                                <details class="periodos-inline-dropdown periodos-inline-dropdown-compact">
                                                    <summary class="periodos-inline-summary periodos-inline-summary-compact" title="Ver períodos adicionales">
                                                        <i class="fas fa-chevron-down periodos-dropdown-chevron"></i>
                                                    </summary>
                                                    <div class="periodos-inline-content periodos-inline-content-compact">
                                                        ${periodosAdicionalesHtml}
                                                    </div>
                                                </details>
                                            ` : ''}
                                        </span>
                                    </div>
                                    `;
            }).join('')}
                            </div>
                        </div>
                    `;
        });

        tabsHtml += '</div>';
        html = tabsHtml + contentHtml;
    } else {
        html = `
                    <div class="empty-state" style="padding: 30px 0;">
                        <i class="fas fa-layer-group" style="font-size: 40px; color: #cbd5e0; margin-bottom: 10px;"></i>
                        <p style="color: #718096;">Este estudiante no está inscrito en ningún grupo.</p>
                    </div>
                `;
    }

    content.innerHTML = html;
    document.getElementById('infoGruposModal').classList.add('active');
}

function switchInfoTab(tabId) {
    // Remove active from all tabs and contents in the modal
    const modalContent = document.getElementById('infoGruposContent');
    modalContent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    modalContent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    // Activate selected
    document.getElementById(tabId).classList.add('active');

    // Find button that triggered it
    const buttons = modalContent.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        if (btn.getAttribute('onclick').includes(tabId)) {
            btn.classList.add('active');
        }
    });
}

function cerrarInfoGruposModal() {
    document.getElementById('infoGruposModal').classList.remove('active');
}
