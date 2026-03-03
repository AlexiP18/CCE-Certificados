// ========== COMPATIBILIDAD GLOBAL ==========
const grupoId = GRUPO_ID;

// ========== FUNCIÓN UTILIDAD PARA ESCAPAR HTML ==========
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== SISTEMA DE NOTIFICACIONES ==========
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.app-notification');
    if (existing) existing.remove();

    const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db'
    };

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    const notification = document.createElement('div');
    notification.className = 'app-notification';
    notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 99999;
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
                font-weight: 500;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
            `;
    notification.innerHTML = `
                <span style="font-size: 20px; font-weight: bold;">${icons[type]}</span>
                <span>${message}</span>
            `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Estilos de animación para notificaciones
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
    document.head.appendChild(style);
}

// ========== FUNCIONES DE PERÍODOS ==========

// Meses en español
const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

function generarNombrePeriodo(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 'Selecciona las fechas...';

    const inicio = new Date(fechaInicio + 'T00:00:00');
    const fin = new Date(fechaFin + 'T00:00:00');

    const mesInicio = inicio.getMonth();
    const mesFin = fin.getMonth();
    const anioInicio = inicio.getFullYear();
    const anioFin = fin.getFullYear();
    const diaInicio = inicio.getDate();
    const diaFin = fin.getDate();

    // Si es el mismo mes
    if (mesInicio === mesFin && anioInicio === anioFin) {
        return `${meses[mesInicio]} ${diaInicio}-${diaFin}, ${anioInicio}`;
    }

    // Si son meses diferentes del mismo año
    if (anioInicio === anioFin) {
        return `${meses[mesInicio]} - ${meses[mesFin]} ${anioInicio}`;
    }

    // Si son años diferentes
    return `${meses[mesInicio]} ${anioInicio} - ${meses[mesFin]} ${anioFin}`;
}

function actualizarPreviewPeriodo() {
    const fechaInicio = document.getElementById('periodoFechaInicio').value;
    const fechaFin = document.getElementById('periodoFechaFin').value;
    const nombre = generarNombrePeriodo(fechaInicio, fechaFin);
    document.getElementById('periodoPreviewNombre').textContent = nombre;
}

// Sincronizar fecha fin cuando se cambia fecha inicio
document.addEventListener('DOMContentLoaded', function () {
    const fechaInicioInput = document.getElementById('periodoFechaInicio');
    if (fechaInicioInput) {
        fechaInicioInput.addEventListener('change', function () {
            const fechaFinInput = document.getElementById('periodoFechaFin');
            if (fechaFinInput && this.value) {
                // Calcular fin de mes
                const parts = this.value.split('-');
                if (parts.length === 3) {
                    const year = parseInt(parts[0]);
                    const month = parseInt(parts[1]) - 1; // 0-indexed
                    // Dia 0 del siguiente mes es el ultimo dia del mes actual
                    const lastDay = new Date(year, month + 1, 0);

                    const y = lastDay.getFullYear();
                    const m = String(lastDay.getMonth() + 1).padStart(2, '0');
                    const d = String(lastDay.getDate()).padStart(2, '0');

                    fechaFinInput.value = `${y}-${m}-${d}`;
                    actualizarPreviewPeriodo();
                }
            }
        });
    }

    // Scroll horizontal con mouse wheel para los tabs
    const tabsNav = document.getElementById('periodoTabsNav');
    if (tabsNav) {
        tabsNav.addEventListener('wheel', (evt) => {
            evt.preventDefault();
            tabsNav.scrollLeft += evt.deltaY;
        });
    }
});

// Variable para almacenar info de categorías con sus periodos
let historialCategorias = [];

async function cargarCategoriasParaPeriodo() {
    const navContainer = document.getElementById('periodoTabsNav');
    const contentContainer = document.getElementById('periodoTabsContent');

    navContainer.innerHTML = '<div style="padding: 10px; color: #7f8c8d;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    contentContainer.innerHTML = '';

    try {
        // Usar la nueva acción historial_categorias
        const response = await fetch(`${BASE_URL}/api/periodos/index.php?action=historial_categorias&grupo_id=${grupoId}`);
        const data = await response.json();

        if (data.success && data.categorias.length > 0) {
            historialCategorias = data.categorias;
            renderPeriodoTabs(historialCategorias);
        } else {
            navContainer.innerHTML = '<div style="padding: 10px; color: #7f8c8d;">No hay categorías disponibles.</div>';
        }
    } catch (error) {
        console.error(error);
        navContainer.innerHTML = '<div style="padding: 10px; color: #e74c3c;">Error al cargar datos</div>';
    }
}

function renderPeriodoTabs(categorias) {
    const navContainer = document.getElementById('periodoTabsNav');
    const contentContainer = document.getElementById('periodoTabsContent');

    navContainer.innerHTML = '';
    contentContainer.innerHTML = '';

    categorias.forEach((cat, index) => {
        // Crear Tab Horizontal
        const tab = document.createElement('div');
        tab.className = `periodo-modal-tab ${index === 0 ? 'active' : ''}`;
        tab.onclick = (e) => {
            if (e.target.type !== 'checkbox') {
                switchPeriodoModalTab(index);
            }
        };

        // HTML del Tab Horizontal
        tab.innerHTML = `
                        <input type="checkbox" id="cat_import_${cat.id}" 
                               onchange="toggleCategoryContent(${index})"
                               style="cursor: pointer; margin-right: 5px;">
                        <span>${cat.icono} ${cat.nombre}</span>
                `;
        navContainer.appendChild(tab);

        // Crear Content
        const content = document.createElement('div');
        content.className = `periodo-modal-content ${index === 0 ? 'active' : ''}`;
        content.setAttribute('data-index', index);

        // Lista de periodos
        const listContainer = document.createElement('div');
        listContainer.className = 'import-options-list';

        let html = '';

        if (cat.historial && cat.historial.length > 0) {
            html += `<div style="font-size: 13px; color: #2c3e50; font-weight: 600; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #eee;">
                        Selecciona el periodo origen para ${cat.nombre}:
                    </div>`;

            cat.historial.forEach((p, pIndex) => {
                const count = p.total_estudiantes || 0;
                if (count > 0) {
                    // Seleccionar el primero por defecto (el más reciente)
                    const isSelected = pIndex === 0 ? 'checked' : '';
                    const selectClass = pIndex === 0 ? 'selected' : '';

                    html += `
                                <label class="import-option ${selectClass}" style="padding: 12px; margin-bottom: 8px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                                    <input type="radio" name="periodo_origen_${cat.id}" value="${p.id}" ${isSelected} onchange="updateImportSelection(this)">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #34495e;">${p.nombre}</div>
                                        <div style="font-size: 12px; color: #7f8c8d; margin-top: 2px;"><i class="fas fa-user-graduate"></i> ${count} estudiantes</div>
                                    </div>
                                    <i class="fas fa-arrow-right" style="color: #bdc3c7;"></i>
                                </label>
                            `;
                }
            });
        } else {
            // Sin estudiantes (Estilo base - Centrado en el área)
            html += `<div style="height: 100%; display: flex; flex-direction: row; align-items: center; justify-content: center; text-align: left; gap: 20px;">
                        <i class="fas fa-ban fa-3x" style="color: #dcdcdc;"></i>
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <div style="font-size: 18px; font-weight: 700; color: #95a5a6; line-height: 1.2;">Sin estudiantes</div>
                            <div style="font-size: 14px; color: #bdc3c7; margin-top: 5px;">Esta categoría iniciará vacía</div>
                        </div>
                    </div>`;
        }

        // Mensaje cuando está deshabilitado (Ninguna categoría seleccionada - Estilo de la imagen - Centrado en el área)
        html += `
                    <div class="disabled-message" style="display: none; height: 100%; flex-direction: row; align-items: center; justify-content: center; text-align: left; gap: 20px;">
                        <i class="fas fa-ban fa-3x" style="color: #dcdcdc;"></i>
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <div style="font-size: 18px; font-weight: 700; color: #95a5a6; line-height: 1.2;">Ninguna categoría seleccionada</div>
                            <div style="font-size: 14px; color: #bdc3c7; margin-top: 5px;">No se importarán estudiantes a esta categoría</div>
                        </div>
                    </div>
                `;

        listContainer.innerHTML = html;
        content.appendChild(listContainer);
        contentContainer.appendChild(content);

        // Ejecutar toggle para asegurar estado visual inicial correcto (desmarcado)
        toggleCategoryContent(index);
    });

    // CSS actualizado para Horizontal Tabs
    if (!document.getElementById('periodo-tabs-style')) {
        const style = document.createElement('style');
        style.id = 'periodo-tabs-style';
        style.textContent = `
                    .periodo-modal-tabs-nav {
                        width: 100% !important;
                        overflow: hidden !important;
                        display: flex !important;
                        flex-direction: row !important;
                        gap: 0 !important;
                    }
                    .periodo-modal-tab {
                        padding: 10px 8px;
                        cursor: pointer;
                        border-bottom: 2px solid transparent;
                        color: #7f8c8d;
                        font-weight: 500;
                        transition: all 0.2s;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                        flex: 1 1 0;
                        min-width: 0;
                        text-align: center;
                    }
                    .periodo-modal-tab span {
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        max-width: 100%;
                        font-size: 12px;
                    }
                    .periodo-modal-tab:hover {
                        background: rgba(0,0,0,0.02);
                        color: #34495e;
                    }
                    .periodo-modal-tab.active {
                        border-bottom-color: ${GRUPO_COLOR};
                        color: ${GRUPO_COLOR};
                        background: ${GRUPO_COLOR}1a;
                    }
                    .periodo-modal-content {
                        display: none;
                        padding: 15px 5px;
                    }
                    .periodo-modal-content.active {
                        display: block;
                        animation: fadeIn 0.3s;
                    }
                    .import-option {
                        transition: all 0.2s;
                        background: #fff;
                    }
                    .import-option:hover {
                        background: #f8f9fa;
                        border-color: #ddd;
                    }
                    .import-option.selected {
                        background: #f0f9ff !important;
                        border-color: #3498db !important;
                        box-shadow: 0 2px 4px rgba(52,152,219,0.1);
                    }

                    /* Custom Scrollbar for Tabs */
                    .periodo-tabs-nav::-webkit-scrollbar {
                        height: 6px;
                    }
`;
        document.head.appendChild(style);
    }







}

function switchPeriodoModalTab(index) {
    const nav = document.getElementById('periodoTabsNav');
    const contentContainer = document.getElementById('periodoTabsContent');

    if (!nav || !contentContainer) return;

    // Update Tabs
    Array.from(nav.children).forEach((t, i) => {
        if (i === index) t.classList.add('active');
        else t.classList.remove('active');
    });

    // Update Content
    Array.from(contentContainer.children).forEach((c, i) => {
        // Ensure we are toggling the content divs
        if (c.classList.contains('periodo-modal-content')) {
            if (c.getAttribute('data-index') == index) c.classList.add('active');
            else c.classList.remove('active');
        }
    });
}


function toggleCategoryContent(index) {
    const content = document.querySelector(`.periodo-modal-content[data-index="${index}"]`);
    if (!content) return;

    const catId = historialCategorias[index].id;
    const checkbox = document.getElementById(`cat_import_${catId}`);
    const tab = document.getElementById('periodoTabsNav').children[index];

    const list = content.querySelector('.import-options-list');
    const disabledMsg = list.querySelector('.disabled-message');

    if (checkbox.checked) {
        if (tab) tab.classList.add('checked-tab');
        Array.from(list.children).forEach(child => {
            if (!child.classList.contains('disabled-message')) child.style.display = 'block';
        });
        if (disabledMsg) disabledMsg.style.display = 'none';
    } else {
        if (tab) tab.classList.remove('checked-tab');
        Array.from(list.children).forEach(child => {
            if (!child.classList.contains('disabled-message')) child.style.display = 'none';
        });
        if (disabledMsg) disabledMsg.style.display = 'flex';
    }
}

function updateImportSelection(radio) {
    // Remover clase selected de todos los labels hermanos
    const container = radio.closest('.import-options-list');
    container.querySelectorAll('.import-option').forEach(lbl => lbl.classList.remove('selected'));

    // Añadir a este
    if (radio.checked) {
        radio.closest('.import-option').classList.add('selected');
    }
}

function togglePeriodoOrigenSelector() {
    // Deprecated function kept for safety
}

function abrirModalPeriodo() {
    document.getElementById('periodoModalTitle').innerHTML = '<i class="fas fa-calendar-plus"></i> Nuevo Período';
    const periodoForm = document.getElementById('periodoForm');
    const previewNombre = document.getElementById('periodoPreviewNombre');
    if (periodoForm) periodoForm.reset();
    if (previewNombre) previewNombre.textContent = 'Selecciona las fechas...';

    // Establecer fecha por defecto (primer día del próximo mes)
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

    const periodoFechaInicio = document.getElementById('periodoFechaInicio');
    const periodoFechaFin = document.getElementById('periodoFechaFin');
    if (periodoFechaInicio) periodoFechaInicio.value = primerDia.toISOString().split('T')[0];
    if (periodoFechaFin) periodoFechaFin.value = ultimoDia.toISOString().split('T')[0];
    actualizarPreviewPeriodo();

    cargarCategoriasParaPeriodo();
    const periodoModal = document.getElementById('periodoModal');
    if (periodoModal) periodoModal.classList.add('active');
}



async function guardarPeriodo(event) {
    event.preventDefault();

    const fechaInicio = document.getElementById('periodoFechaInicio').value;
    const fechaFin = document.getElementById('periodoFechaFin').value;

    if (!fechaInicio || !fechaFin) {
        showNotification('Las fechas son requeridas', 'warning');
        return;
    }

    if (new Date(fechaFin) < new Date(fechaInicio)) {
        showNotification('La fecha de fin debe ser posterior a la fecha de inicio', 'warning');
        return;
    }

    let nombre = generarNombrePeriodo(fechaInicio, fechaFin);
    const asignaciones = [];

    historialCategorias.forEach(cat => {
        const importCheckbox = document.getElementById(`cat_import_${cat.id}`);
        const importar = importCheckbox ? importCheckbox.checked : false;

        let origenId = null;
        if (importar) {
            const origenRadio = document.querySelector(`input[name="periodo_origen_${cat.id}"]:checked`);
            if (origenRadio) {
                origenId = origenRadio.value;
            }
            asignaciones.push({
                categoria_id: cat.id,
                periodo_origen_id: origenId
            });
        }
    });

    const formData = new FormData();
    formData.append('action', 'crear');
    formData.append('grupo_id', grupoId);
    formData.append('nombre', nombre);
    formData.append('fecha_inicio', fechaInicio);
    formData.append('fecha_fin', fechaFin);

    asignaciones.forEach((asig, index) => {
        formData.append(`asignaciones[${index}][categoria_id]`, asig.categoria_id);
        if (asig.periodo_origen_id) {
            formData.append(`asignaciones[${index}][periodo_origen_id]`, asig.periodo_origen_id);
        }
    });

    try {
        const response = await fetch(BASE_URL + '/api/periodos/index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            const count = data.copiados || 0;
            if (count > 0) {
                showNotification(`Período creado con ${count} estudiante(s) importado(s)`, 'success');
            } else {
                showNotification('Período creado correctamente', 'success');
            }

            cerrarModalPeriodo();

            setTimeout(() => {
                const anioNuevo = new Date(fechaInicio).getFullYear();
                const periodoHash = data.periodo_id ? `#periodo-${data.periodo_id}` : '';
                const nuevaUrl = `${BASE_URL}/grupos/detalle.php?id=${grupoId}&anio=${anioNuevo}${periodoHash}`;
                window.location.href = nuevaUrl;
            }, 500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error al guardar el período', 'error');
    }
}


function cerrarModalPeriodo() {
    document.getElementById('periodoModal').classList.remove('active');
}


// Variables para el modal de confirmación de eliminación de período
let periodoIdAEliminar = null;
let categoriaIdAEliminar = null;

function eliminarPeriodo(periodoId, nombre) {
    periodoIdAEliminar = periodoId;
    document.getElementById('confirmarEliminarPeriodoNombre').textContent = nombre;
    document.getElementById('confirmarEliminarPeriodoModal').classList.add('active');
}

function cerrarConfirmarEliminarPeriodo() {
    document.getElementById('confirmarEliminarPeriodoModal').classList.remove('active');
    periodoIdAEliminar = null;
}

async function confirmarEliminarPeriodo() {
    if (!periodoIdAEliminar) return;

    const formData = new FormData();
    formData.append('action', 'eliminar');
    formData.append('id', periodoIdAEliminar);
    formData.append('grupo_id', grupoId);

    try {
        const response = await fetch(BASE_URL + '/api/periodos/index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            cerrarConfirmarEliminarPeriodo();
            showNotification('Período eliminado correctamente', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showNotification('Error al eliminar el período', 'error');
    }
}




// ========== FUNCIONES DE TABS ==========

function cambiarTab(periodoId) {
    // Ocultar todos los tabs y remover active
    document.querySelectorAll('.periodo-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.periodo-tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Activar el tab seleccionado
    const tabSeleccionado = document.querySelector(`.periodo-tab[data-periodo-id="${periodoId}"]`);
    const contentSeleccionado = document.getElementById(`tab-content-${periodoId}`);

    if (tabSeleccionado && contentSeleccionado) {
        tabSeleccionado.classList.add('active');
        contentSeleccionado.classList.add('active');
    }

    // Actualizar URL sin recargar
    const params = new URLSearchParams(window.location.search);
    const anio = params.get('anio') || new Date().getFullYear();
    const newUrl = `${BASE_URL}/grupos/detalle.php?id=${grupoId}&anio=${anio}#periodo-${periodoId}`;
    history.pushState(null, '', newUrl);
}

// Función genérica para contador de caracteres
function updateCharCounter(input, counterId) {
    const count = input.value.length;
    const max = input.getAttribute('maxlength') || 200;
    const counter = document.getElementById(counterId);
    if (counter) {
        counter.textContent = `${count} / ${max}`;
        if (count >= max) {
            counter.style.color = '#e74c3c';
        } else if (count > max * 0.9) {
            counter.style.color = '#f39c12';
        } else {
            counter.style.color = '#95a5a6';
        }
    }
}

function expandirTodos() {
    // Ya no es necesario con tabs, pero mantenemos la función por compatibilidad
    showNotification('Todos los períodos están visibles en las pestañas', 'info');
}

// ========== FILTROS DE PERÍODOS ==========
let filtroVigenteActivo = false;
let filtroMes = '';

function toggleFiltroVigente() {
    filtroVigenteActivo = !filtroVigenteActivo;
    const btn = document.getElementById('btnFiltroVigente');

    if (filtroVigenteActivo) {
        btn.style.background = '#e8f5e9';
        btn.style.borderColor = '#4caf50';
        btn.innerHTML = '<i class="fas fa-check-circle" style="color: #4caf50;"></i> En curso';
    } else {
        btn.style.background = 'white';
        btn.style.borderColor = '#4caf50';
        btn.innerHTML = '<i class="fas fa-circle" style="font-size: 8px;"></i> En curso';
    }

    aplicarFiltros();
}

function filtrarPeriodos() {
    filtroMes = document.getElementById('filtroMesPeriodo').value;
    aplicarFiltros();
}

function aplicarFiltros() {
    const tabs = document.querySelectorAll('.periodo-tab');
    let visibles = 0;
    let primerVisibleId = null;
    let tabSeleccionadoVisible = false;
    const periodoSeleccionadoId = document.querySelector('.periodo-tab.active')?.dataset.periodoId;

    tabs.forEach(tab => {
        const esVigente = tab.dataset.vigente === '1';
        const fechaInicio = new Date(tab.dataset.start);
        const fechaFin = new Date(tab.dataset.end);

        // Filtro Vigente
        if (filtroVigenteActivo && !esVigente) {
            tab.style.display = 'none';
            return;
        }

        // Filtro Mes
        if (filtroMes) {
            const mesFiltro = parseInt(filtroMes) - 1; // JS months are 0-11
            const inicioMes = fechaInicio.getMonth();
            const finMes = fechaFin.getMonth();
            const inicioAnio = fechaInicio.getFullYear();
            const finAnio = fechaFin.getFullYear();

            // Check overlaps for the selected year (ANIO_ACTUAL is global from php)
            // Logic: Period starts before or in month AND ends after or in month
            // Simplified check: Check if the period covers the selected month in the current year
            // But periods can span months.

            // Construct a date for the selected month to compare
            // We assume the year is ANIO_ACTUAL because we are filtering periods displayed for that year

            // Check if the period overlaps with the selected month
            // Overlap condition: Start <= MonthEnd AND End >= MonthStart

            const monthStart = new Date(ANIO_ACTUAL, mesFiltro, 1);
            const monthEnd = new Date(ANIO_ACTUAL, mesFiltro + 1, 0); // Last day of month

            // Convert period dates to objects if not already (re-parsing to be safe or use existing)
            // tab.dataset.start is YYYY-MM-DD
            // We need to compare strict timestamps or just text?
            // Let's use the Date objects

            if (fechaInicio > monthEnd || fechaFin < monthStart) {
                tab.style.display = 'none';
                return;
            }
        }

        // Si pasa todos los filtros
        tab.style.display = 'flex';
        visibles++;
        if (!primerVisibleId) primerVisibleId = tab.dataset.periodoId;

        if (tab.dataset.periodoId == periodoSeleccionadoId) {
            tabSeleccionadoVisible = true;
        }
    });

    // Actualizar UI de "Sin resultados"
    const mensajeSinResultados = document.getElementById('mensajeSinResultadosPeriodo');
    if (mensajeSinResultados) {
        mensajeSinResultados.style.display = visibles === 0 ? 'flex' : 'none';
    }

    // Actualizar texto de resumen
    const resumenText = document.getElementById('resumenPeriodosText');
    if (resumenText) {
        let texto = `Mostrando <strong>${visibles}</strong> período(s)`;
        if (filtroMes) {
            const nombreMes = document.querySelector(`#filtroMesPeriodo option[value="${filtroMes}"]`).text;
            texto += ` en <strong>${nombreMes}</strong>`;
        }
        if (filtroVigenteActivo) {
            texto += ` (En curso)`;
        }
        texto += ` del año <strong>${ANIO_ACTUAL}</strong>`;
        resumenText.innerHTML = texto;
    }

    // Si la tab seleccionada ya no es visible, cambiar a la primera visible
    if (!tabSeleccionadoVisible && primerVisibleId) {
        cambiarTab(primerVisibleId);
    }
}

function cambiarAnio(anio) {
    window.location.href = `${BASE_URL}/grupos/detalle.php?id=${grupoId}&anio=${anio}`;
}

// Inicializar primer tab como activo
// Inicializar tab activo basado en hash o por defecto
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    let tabActivated = false;

    // Check if a hash is present in URL (e.g., #periodo-123)
    if (hash && hash.startsWith('#periodo-')) {
        const periodoId = hash.replace('#periodo-', '');
        const targetTab = document.querySelector(`.periodo-tab[data-periodo-id="${periodoId}"]`);
        if (targetTab) {
            cambiarTab(periodoId);
            tabActivated = true;
            // Scroll suave al tab
            targetTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    // Check if PHP already set an active tab (via periodo_id parameter)
    if (!tabActivated) {
        const existingActiveTab = document.querySelector('.periodo-tab.active');
        if (existingActiveTab) {
            // PHP already set the correct tab, don't override it
            tabActivated = true;
        }
    }

    // Only fallback to first tab if NOTHING is active
    if (!tabActivated) {
        const primerTab = document.querySelector('.periodo-tab');
        const primerContent = document.querySelector('.periodo-tab-content');

        if (primerTab) primerTab.classList.add('active');
        if (primerContent) primerContent.classList.add('active');
    }
});

// ========== FUNCIONES ORIGINALES ==========

// Selector de iconos para categorías
document.querySelectorAll('#categoriaModal .icon-option').forEach(option => {
    option.addEventListener('click', function () {
        document.querySelectorAll('#categoriaModal .icon-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.querySelector('#categoriaModal #icono').value = this.dataset.icon;
        // Ocultar picker si está abierto
        document.getElementById('emojiPickerContainerCat').style.display = 'none';

        // Resetear botón personalizado
        const customBtn = document.querySelector('#categoriaModal .icon-option-custom');
        if (customBtn) {
            customBtn.innerHTML = '<i class="fas fa-search"></i> <span>Más iconos...</span>';
            customBtn.classList.remove('selected');
        }
    });
});

// Toggle emoji picker para categorías
function toggleEmojiPickerCat() {
    const container = document.getElementById('emojiPickerContainerCat');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

// Configurar emoji picker para categorías cuando se carga
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('emojiPickerCat');
    if (picker) {
        picker.addEventListener('emoji-click', event => {
            const emoji = event.detail.unicode;
            // Deseleccionar opciones predefinidas
            document.querySelectorAll('#categoriaModal .icon-option').forEach(o => o.classList.remove('selected'));
            // Establecer el emoji seleccionado
            document.querySelector('#categoriaModal #icono').value = emoji;
            // Ocultar el picker
            document.getElementById('emojiPickerContainerCat').style.display = 'none';
            // Mostrar feedback visual permanente
            const customBtn = document.querySelector('#categoriaModal .icon-option-custom');
            customBtn.innerHTML = `<span style="font-size: 24px;">${emoji}</span><i class="fas fa-search" style="font-size: 10px; position: absolute; bottom: 2px; right: 2px; opacity: 0.6;"></i>`;
            customBtn.classList.add('selected');
        });
    }
});

// Selector de iconos para grupos
document.querySelectorAll('#grupoModal .icon-option-grupo').forEach(option => {
    option.addEventListener('click', function () {
        document.querySelectorAll('#grupoModal .icon-option-grupo').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('grupo_icono').value = this.dataset.icon;
        // Ocultar picker si está abierto
        document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
        // Resetear botón personalizado
        const customBtn = document.querySelector('.icon-option-custom-grupo');
        if (customBtn) {
            customBtn.innerHTML = '<i class="fas fa-search"></i>';
            customBtn.classList.remove('selected');
        }
    });
});

// Toggle emoji picker para grupos
function toggleEmojiPickerGrupo() {
    const container = document.getElementById('emojiPickerContainerGrupo');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

// Configurar emoji picker para grupos cuando se carga
document.addEventListener('DOMContentLoaded', function () {
    const pickerGrupo = document.getElementById('emojiPickerGrupo');
    if (pickerGrupo) {
        pickerGrupo.addEventListener('emoji-click', event => {
            const emoji = event.detail.unicode;
            // Deseleccionar opciones predefinidas
            document.querySelectorAll('#grupoModal .icon-option-grupo').forEach(o => o.classList.remove('selected'));
            // Establecer el emoji seleccionado
            document.getElementById('grupo_icono').value = emoji;
            // Ocultar el picker
            document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
            // Mostrar feedback visual permanente
            const customBtn = document.querySelector('.icon-option-custom-grupo');
            if (customBtn) {
                customBtn.innerHTML = `<span style="font-size: 24px;">${emoji}</span>`;
                customBtn.classList.add('selected');
            }
        });
    }
});

// Color picker para categorías
document.getElementById('color').addEventListener('input', function () {
    document.getElementById('colorHex').textContent = this.value;
});

// Funciones para contador de caracteres - Grupo
function updateCharCounterGrupo(textarea) {
    const count = textarea.value.length;
    const max = textarea.getAttribute('maxlength');
    const counter = document.getElementById('grupoCharCounter');
    counter.textContent = `${count} / ${max}`;

    if (count > max * 0.9) {
        counter.style.color = '#e74c3c';
    } else if (count > max * 0.7) {
        counter.style.color = '#f39c12';
    } else {
        counter.style.color = '#95a5a6';
    }
}

function handlePasteGrupo(event) {
    const textarea = event.target;
    const maxLength = parseInt(textarea.getAttribute('maxlength'));

    setTimeout(() => {
        if (textarea.value.length > maxLength) {
            textarea.value = textarea.value.substring(0, maxLength);
            updateCharCounterGrupo(textarea);
            alert(`El texto ha sido recortado al límite de ${maxLength} caracteres.`);
        }
    }, 0);
}

// Funciones para selector de color - Grupo
function selectPresetColorGrupo(element, color) {
    document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('grupo_color').value = color;
    document.getElementById('grupo_customColor').value = color;
    document.getElementById('grupoColorHex').textContent = color;
}

function selectCustomColorGrupo(color) {
    document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });
    document.getElementById('grupo_color').value = color;
    document.getElementById('grupoColorHex').textContent = color;
}





// Variable para período al crear categoría
let periodoParaCategoria = null;

// Función para cambiar entre tabs del modal de categoría
function switchCategoriaTab(tab) {
    // Actualizar tabs
    document.querySelectorAll('.categoria-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.categoria-tab-content').forEach(c => c.classList.remove('active'));

    const modalContent = document.querySelector('#categoriaModal .modal-content');

    if (tab === 'nueva') {
        document.querySelector('.categoria-tab:first-child').classList.add('active');
        document.getElementById('tabNuevaCategoria').classList.add('active');
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nueva Categoría';

        // RESTAURAR Ancho normal
        modalContent.style.maxWidth = '500px';

        // Botones footer
        document.getElementById('btnGuardarCategoria').style.display = 'block';
        document.getElementById('btnCopiarCategorias').style.display = 'none';
    } else {
        document.querySelector('.categoria-tab:last-child').classList.add('active');
        document.getElementById('tabCopiarCategoria').classList.add('active');
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-import"></i> Importar Categorías';

        // APLICAR Ancho amplio para importación
        modalContent.style.maxWidth = '850px';

        // Botones footer
        document.getElementById('btnGuardarCategoria').style.display = 'none';
        document.getElementById('btnCopiarCategorias').style.display = 'block';

        // Filtrar el período actual de los tabs
        document.querySelectorAll('.periodo-import-tab').forEach(tab => {
            if (tab.dataset.id == periodoParaCategoria) {
                tab.style.display = 'none';
            } else {
                tab.style.display = 'flex';
            }
        });
    }
}

// Seleccionar periodo para importar
async function seleccionarPeriodoImportacion(periodoId) {
    // UI Update - Vertical Sidebar Style
    document.querySelectorAll('.periodo-import-tab').forEach(t => {
        t.style.fontWeight = '500';
        t.style.color = '#7f8c8d';
        t.style.background = 'transparent';
        t.style.borderLeftColor = 'transparent';
        t.style.marginRight = '0';
        t.style.zIndex = '0';
        t.style.boxShadow = 'none';
    });

    const activeTab = document.getElementById('periodo-import-' + periodoId);
    if (activeTab) {
        activeTab.style.fontWeight = '700';
        activeTab.style.color = '#2c3e50';
        activeTab.style.background = '#ffffff';
        activeTab.style.borderLeftColor = GRUPO_COLOR; // Usar constante global
        activeTab.style.marginRight = '-1px'; // Connect with content
        activeTab.style.zIndex = '2';
        activeTab.style.boxShadow = '-2px 2px 5px rgba(0,0,0,0.05)';
    }

    // Show content container
    document.getElementById('mensajeSeleccionPeriodo').style.display = 'none';
    const container = document.getElementById('categoriasOtroPeriodoContainer');
    container.style.display = 'block';

    // Set value
    document.getElementById('periodoOrigen').value = periodoId;

    const lista = document.getElementById('listaCategoriasOtroPeriodo');
    lista.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    try {
        // Cargar categorías del periodo ORIGEN
        const response = await fetch(`${BASE_URL}/api/categorias/index.php?action=listar&grupo_id=${grupoId}&periodo_id=${periodoId}`);
        const data = await response.json();

        // Cargar categorías del periodo DESTINO (para verificar duplicados)
        const periodoDestino = periodoParaCategoria;
        const targetResponse = await fetch(`${BASE_URL}/api/categorias/index.php?action=listar&grupo_id=${grupoId}&periodo_id=${periodoDestino}`);
        const targetData = await targetResponse.json();

        // Crear Set de nombres existentes en el destino (normalizados a minúsculas)
        const existingNames = new Set(targetData.success ? targetData.categorias.map(c => c.nombre.trim().toLowerCase()) : []);

        if (data.success && data.categorias.length > 0) {
            let html = '';
            for (const cat of data.categorias) {
                // Obtener cantidad de estudiantes
                const estResponse = await fetch(`${BASE_URL}/api/categorias/estudiantes.php?action=list&categoria_id=${cat.id}&periodo_id=${periodoId}`);
                const estData = await estResponse.json();
                const cantEstudiantes = estData.success ? estData.estudiantes.length : 0;

                // Verificar si ya existe en el destino
                const isEstablished = existingNames.has(cat.nombre.trim().toLowerCase());

                // Estilos condicionales
                const opacity = isEstablished ? '0.6' : '1';
                const cursor = isEstablished ? 'not-allowed' : 'pointer';
                const bg = isEstablished ? '#f8f9fa' : 'white';

                html += `
                    <label class="categoria-copiar-item" data-id="${cat.id}" style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 10px; cursor: ${cursor}; transition: all 0.2s; background: ${bg}; opacity: ${opacity}; position: relative;">
                        <input type="checkbox" class="check-categoria-copiar" 
                               data-id="${cat.id}" 
                               data-nombre="${cat.nombre}"
                               data-icono="${cat.icono}"
                               data-descripcion="${cat.descripcion || ''}"
                               data-color="${cat.color}"
                               onchange="actualizarBotonCopiar()"
                               style="width: 20px; height: 20px; margin: 0;"
                               ${isEstablished ? 'disabled' : ''}>
                        
                        <span class="categoria-copiar-icono" style="font-size: 24px; width: 40px; text-align: center;">${cat.icono}</span>
                        
                        <div class="categoria-copiar-info" style="flex: 1;">
                            <div class="categoria-copiar-nombre" style="font-weight: 600; font-size: 16px; margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
                                ${cat.nombre}
                                ${isEstablished ? '<span style="font-size: 11px; background: #e9ecef; color: #7f8c8d; padding: 2px 8px; border-radius: 12px; font-weight: 500;">Ya establecida</span>' : ''}
                            </div>
                            ${cat.descripcion ? `<div class="categoria-copiar-desc" style="font-size: 13px; color: #7f8c8d; margin-bottom: 4px;">${cat.descripcion}</div>` : ''}
                            <div class="categoria-copiar-estudiantes" style="font-size: 12px; color: #3498db; font-weight: 500;">
                                <i class="fas fa-users"></i> ${cantEstudiantes} estudiante(s)
                            </div>
                        </div>
                    </label>
                `;
            }
            lista.innerHTML = html;
        } else {
            lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #7f8c8d;">No hay categorías en este período</div>';
        }
    } catch (error) {
        console.error(error);
        lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error al cargar categorías</div>';
    }

    document.getElementById('selectAllCategoriasOtro').checked = false;
    actualizarBotonCopiar();
}

// Toggle seleccionar todas las categorías
function toggleSelectAllCategoriasOtro() {
    const selectAll = document.getElementById('selectAllCategoriasOtro').checked;
    document.querySelectorAll('.check-categoria-copiar').forEach(cb => cb.checked = selectAll);
    actualizarBotonCopiar();
}

// Actualizar estado del botón copiar
function actualizarBotonCopiar() {
    const seleccionadas = document.querySelectorAll('.check-categoria-copiar:checked').length;
    document.getElementById('btnCopiarCategorias').disabled = seleccionadas === 0;
}

// Copiar categorías seleccionadas al período actual
async function copiarCategoriasSeleccionadas() {
    const checkboxes = document.querySelectorAll('.check-categoria-copiar:checked');
    if (checkboxes.length === 0) {
        showNotification('Selecciona al menos una categoría', 'warning');
        return;
    }

    const copiarEstudiantes = document.getElementById('copiarEstudiantes').checked;
    const periodoOrigen = document.getElementById('periodoOrigen').value;
    const periodoDestino = periodoParaCategoria;

    const btn = document.getElementById('btnCopiarCategorias');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copiando...';

    let exitosos = 0;
    let errores = 0;

    for (const cb of checkboxes) {
        try {
            // Crear la categoría en el nuevo período
            const formData = new FormData();
            formData.append('nombre', cb.dataset.nombre);
            formData.append('icono', cb.dataset.icono);
            formData.append('descripcion', cb.dataset.descripcion || '');
            formData.append('color', cb.dataset.color);
            formData.append('grupo_id', grupoId);
            formData.append('periodo_id', periodoDestino);
            formData.append('import_mode', '1');

            const response = await fetch(BASE_URL + '/api/categorias/index.php?action=create', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                exitosos++;

                // Si hay que copiar estudiantes
                if (copiarEstudiantes) {
                    const categoriaOrigenId = cb.dataset.id;
                    const categoriaDestinoId = data.id;

                    // Obtener estudiantes de la categoría origen
                    const estResponse = await fetch(`${BASE_URL}/api/categorias/estudiantes.php?action=list&categoria_id=${categoriaOrigenId}&periodo_id=${periodoOrigen}`);
                    const estData = await estResponse.json();

                    if (estData.success && estData.estudiantes.length > 0) {
                        // Matricular estudiantes en la nueva categoría
                        for (const est of estData.estudiantes) {
                            const matriculaForm = new FormData();
                            matriculaForm.append('action', 'matricular');
                            matriculaForm.append('categoria_id', categoriaDestinoId);
                            matriculaForm.append('estudiante_id', est.estudiante_id);
                            matriculaForm.append('periodo_id', periodoDestino);

                            await fetch(BASE_URL + '/api/categorias/estudiantes.php', {
                                method: 'POST',
                                body: matriculaForm
                            });
                        }
                    }
                }
            } else {
                errores++;
            }
        } catch (e) {
            console.error(e);
            errores++;
        }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-copy"></i> Copiar Categorías';

    if (exitosos > 0) {
        showNotification(`${exitosos} categoría(s) copiada(s) correctamente${copiarEstudiantes ? ' con sus estudiantes' : ''}`, 'success');
        closeModal();
        location.hash = 'periodo-' + periodoDestino;
        location.reload(); // Recargar para ver las nuevas categorías
    }

    if (errores > 0) {
        showNotification(`${errores} categoría(s) no pudieron copiarse`, 'error');
    }
}

// Modal de categorías
function openModal(periodoId = null) {
    // Verificar si hay períodos antes de abrir el modal
    // Usamos constante calculada en la vista
    const hasPeriodos = (typeof GRUPO_PERIODOS !== 'undefined' && GRUPO_PERIODOS.length > 0);

    if (!hasPeriodos && !periodoId) {
        showNotification('Es necesario establecer un período para este grupo antes de crear categorías', 'warning');
        return;
    }

    periodoParaCategoria = periodoId;

    // Mostrar tabs al crear nueva categoría
    document.getElementById('categoriaTabs').style.display = 'flex';

    // Resetear tabs al abrir
    switchCategoriaTab('nueva');

    // Resetear select de período origen (ahora tabs)
    document.querySelectorAll('.periodo-import-tab').forEach(t => {
        t.style.fontWeight = '500';
        t.style.color = '#7f8c8d';
        t.style.borderColor = 'transparent';
        t.style.backgroundColor = 'transparent';
    });
    document.getElementById('periodoOrigen').value = '';
    document.getElementById('categoriasOtroPeriodoContainer').style.display = 'none';
    document.getElementById('mensajeSeleccionPeriodo').style.display = 'flex';
    document.getElementById('selectAllCategoriasOtro').checked = false;
    document.getElementById('copiarEstudiantes').checked = true;

    document.getElementById('categoriaModal').classList.add('active');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nueva Categoría';
    document.getElementById('btnTextGuardarCategoria').textContent = 'Crear Categoría';
    document.getElementById('categoriaForm').reset();
    document.getElementById('categoria_id').value = '';
    document.querySelectorAll('#categoriaModal .icon-option').forEach(o => o.classList.remove('selected'));
    document.querySelector('#categoriaModal .icon-option[data-icon="📚"]').classList.add('selected');
    document.querySelector('#categoriaModal #icono').value = '📚';
    // Resetear contador
    updateCharCounter(document.getElementById('descripcion'), 'categoriaCharCounter');

    document.getElementById('emojiPickerContainerCat').style.display = 'none';
    // Resetear botón personalizado
    const customBtn = document.querySelector('#categoriaModal .icon-option-custom');
    if (customBtn) {
        customBtn.innerHTML = '<i class="fas fa-search"></i> <span>Más iconos...</span>';
        customBtn.classList.remove('selected');
    }



    // El color se hereda automáticamente del grupo
}

function closeModal() {
    document.getElementById('categoriaModal').classList.remove('active');
    document.getElementById('emojiPickerContainerCat').style.display = 'none';
}

// Modal de grupo
function editarGrupo() {
    const iconoActual = GRUPO_ICONO;

    // Resetear picker
    document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
    const customBtn = document.querySelector('.icon-option-custom-grupo');

    // Verificar si el icono actual está en las opciones predefinidas
    const iconoPredefinido = document.querySelector(`.icon-option-grupo[data-icon="${iconoActual}"]`);

    if (iconoPredefinido) {
        // Es un icono predefinido
        document.querySelectorAll('.icon-option-grupo').forEach(o => {
            o.classList.toggle('selected', o.dataset.icon === iconoActual);
        });
        if (customBtn) {
            customBtn.innerHTML = '<i class="fas fa-search"></i>';
            customBtn.classList.remove('selected');
        }
    } else {
        // Es un emoji personalizado
        document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
        if (customBtn) {
            customBtn.innerHTML = `<span style="font-size: 24px;">${iconoActual}</span>`;
            customBtn.classList.add('selected');
        }
    }

    // Reconfigurar event listeners para los iconos del grupo cada vez que se abre el modal
    document.querySelectorAll('.icon-option-grupo').forEach(option => {
        // Remover listeners antiguos clonando el elemento
        const newOption = option.cloneNode(true);
        option.parentNode.replaceChild(newOption, option);

        // Agregar nuevo listener
        newOption.addEventListener('click', function () {
            document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('grupo_icono').value = this.dataset.icon;
            // Ocultar picker si está abierto
            document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
            // Resetear botón personalizado
            if (customBtn) {
                customBtn.innerHTML = '<i class="fas fa-search"></i>';
                customBtn.classList.remove('selected');
            }
        });
    });

    // Actualizar contador de caracteres
    const descripcion = GRUPO_DESCRIPCION;
    const length = descripcion.length;
    document.getElementById('grupoCharCounter').textContent = `${length} / 250`;
    if (length > 200) {
        document.getElementById('grupoCharCounter').style.color = '#e74c3c';
    } else if (length > 150) {
        document.getElementById('grupoCharCounter').style.color = '#f39c12';
    } else {
        document.getElementById('grupoCharCounter').style.color = '#95a5a6';
    }

    // Seleccionar color preset
    const colorActual = GRUPO_COLOR;
    document.querySelectorAll('#grupoModal .color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });
    const colorPreset = document.querySelector(`#grupoModal .color-preset[data-color="${colorActual}"]`);
    if (colorPreset) {
        colorPreset.classList.add('selected');
    }
    document.getElementById('grupo_customColor').value = colorActual;
    document.getElementById('grupoColorHex').textContent = colorActual;

    document.getElementById('grupoModal').classList.add('active');
}

function closeGrupoModal() {
    document.getElementById('grupoModal').classList.remove('active');
    document.getElementById('emojiPickerContainerGrupo').style.display = 'none';
}

async function editarCategoria(id) {
    try {
        console.log('Editando categoría ID:', id);
        const response = await fetch(`${BASE_URL}/api/categorias/index.php?action=get&id=${id}`);
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Response text:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parseando JSON:', e);
            showNotification('Error: Respuesta inválida del servidor', 'error');
            return;
        }

        if (data.success) {
            const cat = data.categoria;

            // Ocultar tabs al editar (solo mostrar formulario)
            document.getElementById('categoriaTabs').style.display = 'none';
            document.getElementById('tabNuevaCategoria').classList.add('active');
            document.getElementById('tabCopiarCategoria').classList.remove('active');

            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Categoría';
            document.getElementById('btnTextGuardarCategoria').textContent = 'Guardar';
            document.getElementById('categoria_id').value = cat.id;
            document.getElementById('nombre').value = cat.nombre;
            document.getElementById('descripcion').value = cat.descripcion || '';
            // Actualizar contador
            updateCharCounter(document.getElementById('descripcion'), 'categoriaCharCounter');

            document.querySelector('#categoriaModal #icono').value = cat.icono;
            document.getElementById('color').value = cat.color;

            // Verificar si el icono actual está en las opciones predefinidas
            const iconoPredefinido = document.querySelector(`#categoriaModal .icon-option[data-icon="${cat.icono}"]`);
            const customBtn = document.querySelector('#categoriaModal .icon-option-custom');

            if (iconoPredefinido) {
                // Es un icono predefinido
                document.querySelectorAll('#categoriaModal .icon-option').forEach(o => {
                    o.classList.toggle('selected', o.dataset.icon === cat.icono);
                });
                if (customBtn) {
                    customBtn.innerHTML = '<i class="fas fa-search"></i> <span>Más iconos...</span>';
                    customBtn.classList.remove('selected');
                }
            } else {
                // Es un emoji personalizado
                document.querySelectorAll('#categoriaModal .icon-option').forEach(o => o.classList.remove('selected'));
                if (customBtn) {
                    customBtn.innerHTML = `<span style="font-size: 24px;">${cat.icono}</span><i class="fas fa-search" style="font-size: 10px; position: absolute; bottom: 2px; right: 2px; opacity: 0.6;"></i>`;
                    customBtn.classList.add('selected');
                }
            }


            // El color se hereda automáticamente del grupo

            document.getElementById('categoriaModal').classList.add('active');
        } else {
            alert('Error: ' + (data.message || 'No se pudo cargar la categoría'));
        }
    } catch (error) {
        console.error('Error en editarCategoria:', error);
        alert('Error al cargar la categoría: ' + error.message);
    }
}

function eliminarCategoria(id, nombre) {
    categoriaIdAEliminar = id;
    document.getElementById('confirmarEliminarCategoriaNombre').textContent = nombre;
    document.getElementById('confirmarEliminarCategoriaModal').classList.add('active');
}

function cerrarConfirmarEliminarCategoria() {
    document.getElementById('confirmarEliminarCategoriaModal').classList.remove('active');
    categoriaIdAEliminar = null;
}

function confirmarEliminarCategoria() {
    if (!categoriaIdAEliminar) return;

    const btn = document.getElementById('btnConfirmarEliminarCategoria');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

    fetch(BASE_URL + '/api/categorias/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: categoriaIdAEliminar })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Categoría eliminada correctamente', 'success');
                cerrarConfirmarEliminarCategoria();
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification('Error: ' + data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al procesar la solicitud', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function eliminarGrupo() {
    document.getElementById('confirmarEliminarGrupoModal').classList.add('active');
}

function cerrarEliminarGrupoModal() {
    document.getElementById('confirmarEliminarGrupoModal').classList.remove('active');
}

function confirmarEliminarGrupo() {
    const btn = document.getElementById('btnConfirmarEliminarGrupo');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

    fetch(BASE_URL + '/api/grupos/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: grupoId })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Grupo eliminado correctamente', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard/index.php';
                }, 1000);
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Sí, Eliminar';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt"></i> Sí, Eliminar';
        });
}

// Submit del formulario de grupo
document.getElementById('grupoForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'update');

    // Obtener el nuevo color
    const nuevoColor = formData.get('color');

    try {
        const response = await fetch(BASE_URL + '/api/grupos/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar el color del header del grupo
            const groupHeader = document.querySelector('.grupo-header');
            if (groupHeader) {
                groupHeader.style.background = `linear-gradient(135deg, ${nuevoColor}dd 0%, ${nuevoColor} 100%)`;
            }

            // Actualizar los cards de las categorías con el nuevo color del grupo
            document.querySelectorAll('.categoria-card:not(.add-categoria-card)').forEach(card => {
                card.style.setProperty('--categoria-color', nuevoColor);

                // Actualizar también el gradiente del icono
                const iconEl = card.querySelector('.categoria-icon');
                if (iconEl) {
                    iconEl.style.background = `linear-gradient(135deg, ${nuevoColor}cc, ${nuevoColor})`;
                }
            });

            // Cerrar modal
            closeGrupoModal();

            // Mostrar mensaje de éxito
            showNotification('Grupo actualizado correctamente', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error al guardar el grupo', 'error');
    }
});

// Submit del formulario de categoría
document.getElementById('categoriaForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', document.getElementById('categoria_id').value ? 'update' : 'create');

    // Agregar período si está definido
    if (periodoParaCategoria) {
        formData.append('periodo_id', periodoParaCategoria);
    }

    try {
        const response = await fetch(BASE_URL + '/api/categorias/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (periodoParaCategoria) {
                window.location.hash = 'periodo-' + periodoParaCategoria;
            }
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error al guardar la categoría', 'error');
    }
});

// Cerrar modal al hacer clic fuera
document.getElementById('categoriaModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('grupoModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeGrupoModal();
    }
});

// Habilitar scroll horizontal con rueda del mouse para los tabs de periodos
document.addEventListener('DOMContentLoaded', function () {
    const periodTabsNav = document.querySelector('.periodo-tabs-nav');
    if (periodTabsNav) {
        periodTabsNav.addEventListener('wheel', (evt) => {
            evt.preventDefault();
            periodTabsNav.scrollLeft += evt.deltaY;
        });
    }
});
