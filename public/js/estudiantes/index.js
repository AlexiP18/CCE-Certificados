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
            dropdown.style.zIndex = '9999';

            // Boundary enforcement (optional, simplistic)
            const dropdownRect = dropdown.getBoundingClientRect();
            if (dropdownRect.right > window.innerWidth) {
                dropdown.style.left = (window.innerWidth - dropdownRect.width - 10) + 'px';
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
                        <td colspan="13">
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
                        <td colspan="13">
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

    tableBody.innerHTML = estudiantes.map(est => {
        // Parse Groups and Categories
        let gruposMap = {};

        if (est.enrollment_data) {
            const enrollments = est.enrollment_data.split('||');
            enrollments.forEach(enroll => {
                // Data format: GrupoID##GrupoNombre##GrupoColor##Icono##CatID##CatNombre##CatIcono##Periodo
                const parts = enroll.split('##');
                if (parts.length >= 8) {
                    const gId = parts[0];
                    const gNombre = parts[1];
                    const gColor = parts[2] || '#ccc';
                    // parts[3] is Icono (unused here but good to know)
                    const cId = parts[4];
                    const cNombre = parts[5];
                    // parts[6] CatIcono, parts[7] Periodo

                    if (!gruposMap[gId]) {
                        gruposMap[gId] = {
                            id: gId,
                            nombre: gNombre,
                            color: gColor,
                            categorias: []
                        };
                    }
                    // Avoid duplicates (though redundant DISTINCT in query should handle it)
                    if (!gruposMap[gId].categorias.some(c => c.id === cId)) {
                        gruposMap[gId].categorias.push({ id: cId, nombre: cNombre });
                    }
                }
            });
        }

        // Build Groups HTML
        let gruposHtml = '';
        if (Object.keys(gruposMap).length > 0) {
            Object.values(gruposMap).forEach(grupo => {
                // Dropdown Content
                const categoriasList = grupo.categorias.map(c =>
                    `<div class="categoria-item">${escapeHtml(c.nombre)}</div>`
                ).join('');

                const dropdownId = `dropdown-${est.id}-${grupo.id}`;

                gruposHtml += `
                            <div class="grupo-container" onmouseleave="hideDropdown('${dropdownId}')">
                                <span class="badge badge-grupo-interactive" 
                                      style="background: ${grupo.color}20; color: ${grupo.color}; border: 1px solid ${grupo.color}40;"
                                      onclick="toggleDropdown('${dropdownId}', event)">
                                    ${escapeHtml(grupo.nombre)}
                                </span>
                                <div id="${dropdownId}" class="grupo-dropdown">
                                    <div style="font-size: 11px; font-weight: 600; color: #7f8c8d; margin-bottom: 4px; padding-left: 4px;">CATEGORÍAS:</div>
                                    ${categoriasList}
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
        let categoriasStr = [];
        Object.values(gruposMap).forEach(g => {
            g.categorias.forEach(c => categoriasStr.push(c.nombre));
        });
        categoriasStr = categoriasStr.join(', ');

        // Hierarchy Visuals
        const isMenor = est.es_menor == 1;
        const isRepresentante = est.num_hijos > 0;

        let namePrefix = '';
        let rowStyle = '';

        if (isMenor) {
            namePrefix = '<span style="color: #bdc3c7; margin-right: 8px; margin-left: 15px;"><i class="fas fa-level-up-alt fa-rotate-90"></i></span>';
            rowStyle = 'background-color: #fef9e7;'; // Light yellow for minors
        } else if (isRepresentante) {
            rowStyle = 'background-color: #eaf2f8;'; // Light blue for representatives
        }

        let badgesHtml = '';
        if (isRepresentante) {
            badgesHtml += '<span class="badge" style="background: #3498db; color: white; font-size: 10px; margin-left: 5px;">REPRESENTANTE</span>';
        }
        if (isMenor) {
            badgesHtml += '<span class="badge" style="background: #f1c40f; color: white; font-size: 10px; margin-left: 5px;">MENOR</span>';
        }

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

        return `
                <tr data-estudiante-id="${est.id}" style="${rowStyle}">
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
                        ${namePrefix}
                        ${est.destacado == 1 ? '<i class="fas fa-star" style="color: #f39c12; margin-right: 5px;"></i>' : ''}
                        ${escapeHtml(est.nombre)}
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
                    <td class="sticky-col sticky-right">
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="verInfoGrupos(${est.id})" title="Ver Grupos y Categorías" style="color:#5e35b1; background:#ede7f6;">
                                <i class="fas fa-layer-group"></i>
                            </button>
                            <button class="btn-icon btn-certificate" onclick="viewEstudiante(${est.id})" title="Visualizar Certificados">
                                <i class="fas fa-certificate"></i>
                            </button>
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
    const tableWrapper = document.querySelector('.table-scroll-wrapper');
    if (tableWrapper) {
        tableWrapper.addEventListener('wheel', (evt) => {
            // Si hay contenido para scrollear horizontalmente
            if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                evt.preventDefault();
                tableWrapper.scrollLeft += evt.deltaY;
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

    if (estudiante.enrollment_data) {
        // Group by Grupo
        const gruposMap = {};

        // Parse enrollment_data: "GrupoID##GrupoNombre##GrupoColor##CatID##CatNombre||..."
        const enrollments = estudiante.enrollment_data.split('||');

        enrollments.forEach(enroll => {
            if (!enroll) return;
            const parts = enroll.split('##');
            // Expected format: ID##Nombre##Color##Icono##CatID##CatNombre##CatIcono##Periodo
            if (parts.length >= 8) {
                const gId = parts[0];
                const gNombre = parts[1];
                const gColor = parts[2] || '#ccc';
                const gIcono = parts[3] || 'fas fa-users'; // Default Group Icon
                const cId = parts[4];
                const cNombre = parts[5];
                const cIcono = parts[6] || 'fas fa-layer-group'; // Default Category Icon
                const periodo = parts[7] || '-';

                if (!gruposMap[gId]) {
                    gruposMap[gId] = {
                        id: gId,
                        nombre: gNombre,
                        color: gColor,
                        icono: gIcono,
                        categorias: []
                    };
                }
                gruposMap[gId].categorias.push({
                    id: cId,
                    nombre: cNombre,
                    icono: cIcono,
                    periodo: periodo
                });
            }
        });

        // Generate Tabs and Content
        let tabsHtml = '<div class="modal-tabs-nav">';
        let contentHtml = '';

        Object.values(gruposMap).forEach((grupo, index) => {
            const isActive = index === 0 ? 'active' : '';
            const tabId = `grupo-tab-${grupo.id}`;

            // Tab Button
            const gIconHTML = grupo.icono.includes('fa-') ? `<i class="${grupo.icono}" style="color: ${grupo.color}"></i>` : `<span>${grupo.icono}</span>`;

            tabsHtml += `
                        <button class="tab-btn ${isActive}" onclick="switchInfoTab('${tabId}')">
                             ${gIconHTML} ${escapeHtml(grupo.nombre)}
                        </button>
                    `;

            // Tab Content
            contentHtml += `
                        <div id="${tabId}" class="tab-content ${isActive}">
                            <div style="padding: 10px 0;">
                                ${grupo.categorias.map(cat => {
                const cIconHTML = cat.icono.includes('fa-') ? `<i class="${cat.icono}" style="color: #667eea;"></i>` : `<span>${cat.icono}</span>`;
                return `
                                    <div style="margin-bottom: 8px; font-size: 0.95em; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eee; padding-bottom: 8px;">
                                        <span style="display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 20px; text-align: center;">${cIconHTML}</span>
                                            <span style="color: #2c3e50; font-weight: 500;">${escapeHtml(cat.nombre)}</span>
                                        </span>
                                        <span class="badge" style="background: #e8f0fe; color: #1967d2; font-size: 0.85em; border: 1px solid #d2e3fc; padding: 4px 8px;">
                                            <i class="far fa-calendar-alt"></i> ${cat.periodo}
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
