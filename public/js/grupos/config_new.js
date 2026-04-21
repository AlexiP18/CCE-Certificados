/**
 * Configurador de Certificados (Refactorizado)
 * Arquitectura basada en Estado y Componentes
 */

// ==========================================
// 1. ESTADO GLOBAL DE LA APLICACIÓN
// ==========================================
function getDefaultConfig() {
    return {
        variables_habilitadas: ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'],

        // --- Nombre ---
        fuente_nombre: 'Roboto-Bold',
        tamanio_fuente: 48,
        color_texto: '#000000',
        formato_nombre: 'mayusculas',
        posicion_nombre_x: -1,
        posicion_nombre_y: -1,

        // --- Razón ---
        fuente_razon: 'Roboto-Regular',
        tamanio_razon: 24,
        color_razon: '#333333',
        alineacion_razon: 'center',
        ancho_razon: -1,
        razon_defecto: 'Por su destacada participación en el {grupo} de {categoria}.',
        posicion_razon_x: -1,
        posicion_razon_y: -1,

        // --- Fecha ---
        fuente_fecha: 'Roboto-Regular',
        tamanio_fecha: 20,
        color_fecha: '#333333',
        formato_fecha: 'd de F de Y',
        usar_fecha_especifica: false,
        fecha_especifica: '',
        posicion_fecha_x: -1,
        posicion_fecha_y: -1,

        // --- QR ---
        tamanio_qr: 150,
        posicion_qr_x: -1,
        posicion_qr_y: -1,

        // --- Firma ---
        tamanio_firma: 150,
        firma_nombre: '',
        firma_cargo: '',
        posicion_firma_x: -1,
        posicion_firma_y: -1,
        firma_imagen: '',

        // --- Destacado ---
        destacado_tipo: 'icono',
        destacado_icono: 'estrella',
        destacado_tamanio: 150,
        posicion_destacado_x: -1,
        posicion_destacado_y: -1,
        destacado_imagen: ''
    };
}

const State = {
    // Datos del Grupo y Contexto
    grupoId: window.serverGrupoId,
    basePath: window.basePath,
    assetsPath: window.assetsPath,
    fontMap: window.fontMap || {},

    // Plantillas
    templates: [],
    currentTemplateId: null, // Si es el template del sistema es null o 'system'

    // Datos Mock (o reales) para preview
    mockData: {
        grupo: 'Nombre del Grupo',
        categoria: 'Categoría de Ejemplo',
        nombre: 'Juan Pérez García'
    },

    // Configuración activa (se sincroniza con Base de Datos)
    config: getDefaultConfig(),

    // Formularios Pendientes
    pendingFiles: {
        firma: null,
        destacado: null
    },

    // UI State local (no se guarda en BD)
    ui: {
        activeTab: 'nombre',
        canvasScale: 1,
        imageRealWidth: 1600,
        imageRealHeight: 1131
    }
};

// ==========================================
// 2. INICIALIZACIÓN
// ==========================================
document.addEventListener('DOMContentLoaded', async () => {
    console.log("🚀 Iniciando Configurador v2");

    initUI();
    await fetchConfig();
});

function initUI() {
    // Construir Tabs
    buildSidebarTabs();

    // Binding general de inputs manuales
    bindInputs();

    // Listeners de UI puros (Modales, Switches)
    bindUIEvents();

    // Drag & Drop
    bindDropzones();
}

function bindUIEvents() {
    // Modal Listeners
    const btnClosePreview = document.getElementById('btnClosePreview');
    if (btnClosePreview) btnClosePreview.addEventListener('click', () => {
        document.getElementById('previewModal').classList.remove('active');
    });

    const btnCancelDelete = document.getElementById('btnCancelDelete');
    if (btnCancelDelete) btnCancelDelete.addEventListener('click', () => {
        document.getElementById('deleteModal').classList.remove('active');
    });

    // Note: btnConfirmDelete listener is bound dynamically in promptDeleteTemplate(id)

    // Action Listeners
    const btnSaveConfig = document.getElementById('btnSaveConfig');
    if (btnSaveConfig) btnSaveConfig.addEventListener('click', saveConfig);

    const btnPreview = document.getElementById('btnPreview');
    if (btnPreview) btnPreview.addEventListener('click', previewCertificate);

    const btnUploadTemplate = document.getElementById('btnUploadTemplate');
    if (btnUploadTemplate) btnUploadTemplate.addEventListener('click', () => {
        document.getElementById('uploadTemplateInput').click();
    });

    const uploadTemplateInput = document.getElementById('uploadTemplateInput');
    if (uploadTemplateInput) uploadTemplateInput.addEventListener('change', uploadTemplate);
}

// ==========================================
// 3. COMUNICACIÓN CON API
// ==========================================
async function fetchConfig() {
    try {
        let url = `${State.basePath}api/grupos/plantillas.php?action=get_config&grupo_id=${State.grupoId}`;
        if (State.currentTemplateId) {
            url += `&plantilla_id=${State.currentTemplateId}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            // Cargar Plantillas
            State.templates = data.plantillas || [];

            // Cargar Configuración Actual
            if (data.config) {
                mergeConfig(data.config);
            }

            // Cargar Data del Grupo para Previews
            if (data.grupo) {
                State.mockData.grupo = data.grupo.nombre || 'Nombre del Grupo';
                State.mockData.categoria = data.grupo.categoria_nombre || 'Categoría de Ejemplo';
            }
            if (data.estudiante) {
                State.mockData.nombre = data.estudiante.nombre_completo || 'Juan Pérez García';
            }

            // Render Initial State
            renderAll();

            // Configurar interact.js despues de renderizar marcadores
            initInteractJS();
        } else {
            notifyError('Error al cargar la configuración');
        }
    } catch (e) {
        console.error(e);
        notifyError('Error de red al cargar');
    }
}

function mergeConfig(dbConfig) {
    // Reset to defaults first so unconfigured variables perfectly center
    State.config = getDefaultConfig();

    // Combinar con config por defecto para evitar undefined
    for (let key in dbConfig) {
        if (dbConfig[key] !== null && dbConfig[key] !== '') {

            // Reemplazo estricto de coordenadas que llegan "falsas" (DB Default)
            if (key.startsWith('posicion_') || key.startsWith('tamanio_')) {
                let parsedVal = parseFloat(dbConfig[key]);

                // Evitar sobreescribir los Default Centers de Javascript con los valores 0 o 100 
                // que pone la Base de Datos automáticamente al crear una nueva plantilla
                if (parsedVal === 0 || parsedVal === 100 || isNaN(parsedVal)) {
                    continue; // Skip this key, keep getDefaultConfig() center
                }

                State.config[key] = parsedVal;
            } else if (key === 'ancho_razon') {
                let parsedVal = parseFloat(dbConfig[key]);
                if (parsedVal === 0 || isNaN(parsedVal)) continue;
                State.config[key] = parsedVal;
            } else if (key === 'variables_habilitadas') {
                try {
                    State.config[key] = JSON.parse(dbConfig[key]) || [];
                } catch (e) {
                    State.config[key] = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];
                }
            } else {
                State.config[key] = dbConfig[key];
            }
        }
    }

    // Determinacion Plantilla Activa
    State.currentTemplateId = dbConfig.plantilla_id || null;
}

// ==========================================
// 4. RENDERIZACIÓN DE LA INTERFAZ
// ==========================================
function renderAll() {
    renderRibbon();
    renderToggles();
    renderSidebar();
    renderSidebarTabVisibility();
    renderTemplateImage();
    renderMarkers();
    updateContextValues();
}

// Renderizar la cinta de plantillas
function renderRibbon() {
    const container = document.getElementById('templateSliderContainer');
    if (!container) return;

    container.innerHTML = '';

    const customTemplates = State.templates.filter(tpl => tpl.archivo && tpl.archivo.trim() !== '');

    document.getElementById('templateCounter').textContent = `${customTemplates.length}/5`;

    // Plantillas Subidas
    customTemplates.forEach(tpl => {
        const thumb = document.createElement('div');
        const isActive = State.currentTemplateId == tpl.id;
        thumb.className = `template-thumb ${isActive ? 'active' : ''}`;
        thumb.innerHTML = `
            <img src="${State.basePath}uploads/grupos/${State.grupoId}/${tpl.archivo}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
            <div style="position:absolute; top:2px; right:2px; font-size:14px; cursor:pointer;" onclick="promptDeleteTemplate(${tpl.id}, event)">
                <i class="fas fa-times-circle" style="color:red; background:white; border-radius:50%;"></i>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:white; font-size:10px; text-align:center; padding:2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">Pers(${tpl.id})</div>
        `;
        thumb.onclick = () => selectTemplate(tpl.id);
        container.appendChild(thumb);
    });

    // Slots Vacíos hasta 5
    const MAX_TEMPLATES = 5;
    const emptySlotsCount = MAX_TEMPLATES - customTemplates.length;
    
    for (let i = 0; i < emptySlotsCount; i++) {
        const emptySlot = document.createElement('div');
        emptySlot.className = 'template-thumb-empty';
        emptySlot.innerHTML = `
            <i class="fas fa-image" style="font-size: 24px; opacity: 0.3; margin-bottom: 5px;"></i>
            <span style="opacity: 0.6;">Vacío</span>
        `;
        container.appendChild(emptySlot);
    }

    // Activar o desactivar botón de subir nueva plantilla
    const btnUpload = document.getElementById('btnUploadTemplate');
    if (btnUpload) {
        btnUpload.disabled = emptySlotsCount <= 0;
        if (emptySlotsCount <= 0) {
            btnUpload.style.opacity = '0.5';
            btnUpload.style.cursor = 'not-allowed';
            btnUpload.title = 'Límite de 5 plantillas alcanzado';
        } else {
            btnUpload.style.opacity = '1';
            btnUpload.style.cursor = 'pointer';
            btnUpload.title = 'Subir nueva plantilla separada';
        }
    }
}

// Renderizar switches superiores
function renderToggles() {
    const vars = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];
    vars.forEach(v => {
        const el = document.getElementById(`toggle_${v}`);
        if (el) {
            el.checked = State.config.variables_habilitadas.includes(v);

            // Listener de cambio
            el.onchange = (e) => {
                if (e.target.checked) {
                    if (!State.config.variables_habilitadas.includes(v)) State.config.variables_habilitadas.push(v);
                } else {
                    State.config.variables_habilitadas = State.config.variables_habilitadas.filter(item => item !== v);
                }
                renderMarkersVisibility();
                renderSidebarTabVisibility();
            };
        }
    });
}

// Construir pestañas y paneles desde templates
function buildSidebarTabs() {
    const tabsContainer = document.getElementById('sidebarTabs');
    const panelsContainer = document.getElementById('sidebarPanels');

    const tabs = [
        { id: 'nombre', icon: 'fa-user', label: 'Nombre' },
        { id: 'razon', icon: 'fa-file-alt', label: 'Razón' },
        { id: 'fecha', icon: 'fa-calendar', label: 'Fecha' },
        { id: 'qr', icon: 'fa-qrcode', label: 'QR' },
        { id: 'firma', icon: 'fa-signature', label: 'Firma' },
        { id: 'destacado', icon: 'fa-star', label: 'Destacado' },
    ];

    tabsContainer.innerHTML = '';
    panelsContainer.innerHTML = '';

    tabs.forEach(tab => {
        // Tab
        const t = document.createElement('div');
        t.className = `sidebar-tab ${State.ui.activeTab === tab.id ? 'active' : ''}`;
        t.innerHTML = `<i class="fas ${tab.icon}"></i> ${tab.label}`;
        t.onclick = () => switchSidebarTab(tab.id);
        tabsContainer.appendChild(t);

        // Panel
        const p = document.createElement('div');
        p.className = `panel-content ${State.ui.activeTab === tab.id ? 'active' : ''}`;
        p.id = `panel-${tab.id}`;

        const template = document.getElementById(`panel${tab.id.charAt(0).toUpperCase() + tab.id.slice(1)}Template`);
        if (template) {
            p.appendChild(template.content.cloneNode(true));
        }
        panelsContainer.appendChild(p);
    });
}

function switchSidebarTab(tabId) {
    State.ui.activeTab = tabId;
    Array.from(document.getElementById('sidebarTabs').children).forEach((t, idx) => {
        const id = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'][idx];
        t.classList.toggle('active', id === tabId);
    });

    Array.from(document.getElementById('sidebarPanels').children).forEach(p => {
        p.classList.toggle('active', p.id === `panel-${tabId}`);
    });

    if (tabId === 'destacado') {
        renderDestacadoIconGrid();
    }
    if (tabId === 'firma') {
        renderFirmaPreview();
    }
    if (tabId === 'fecha') {
        updateFechaFormatLabels();
    }
}

function renderSidebarTabVisibility() {
    const tabsContainer = document.getElementById('sidebarTabs');
    if (!tabsContainer) return;
    
    const tabs = Array.from(tabsContainer.children);
    const varNames = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];

    tabs.forEach((t, idx) => {
        const id = varNames[idx];
        const isEnabled = State.config.variables_habilitadas.includes(id);
        
        // Mantener la pestaña visible, pero indicando visualmente si está bloqueada
        t.style.opacity = isEnabled ? '1' : '0.5';
        
        // Bloquear el panel de configuración respectivo
        const panel = document.getElementById(`panel-${id}`);
        if (panel) {
            panel.style.opacity = isEnabled ? '1' : '0.5';
            panel.style.pointerEvents = isEnabled ? 'auto' : 'none';
        }
    });
}

// Llenar datos a la Sidebar
function renderSidebar() {
    // Font Selectors
    document.querySelectorAll('.font-selector').forEach(sel => {
        if (sel.options.length === 0) fillFontSelector(sel);
    });
    updateFechaFormatLabels();

    // Inputs Data Binding (Value to UI)
    document.querySelectorAll('[data-bind]').forEach(el => {
        const key = el.getAttribute('data-bind');
        const val = State.config[key];

        if (el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            if (el.type !== 'file' && val !== undefined) {
                el.value = val;
            }
            if (el.type === 'checkbox') {
                el.checked = val;
            }
        }

        // Elementos especiales (Segment Controls)
        if (el.classList.contains('segment-control')) {
            el.querySelectorAll('button').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-val') === val);
            });
        }
    });

    // Live Vals
    document.querySelectorAll('input[type="range"]').forEach(el => {
        const liveVal = el.parentElement.querySelector('.live-val');
        if (liveVal) liveVal.textContent = el.value;
    });

    // Destacado Icon Grid
    renderDestacadoIconGrid();

    // Firma Preview
    renderFirmaPreview();
}

function fillFontSelector(sel) {
    // Using raw data injected
    const rawFonts = document.getElementById('fontData').textContent;
    try {
        const fontsList = JSON.parse(rawFonts);
        let currentCategory = '';

        fontsList.forEach((f, i) => {
            if (f.categoria !== currentCategory) {
                const grp = document.createElement('optgroup');
                grp.label = f.categoria;
                sel.appendChild(grp);
                currentCategory = f.categoria;
            }

            const opt = document.createElement('option');
            opt.value = f.nombre_archivo;
            opt.textContent = f.nombre;
            // Get font id to apply real font visually
            const fp = State.fontMap[f.nombre_archivo] || '';
            opt.style.fontFamily = `"${fp}", sans-serif`;

            sel.querySelector(`optgroup[label="${f.categoria}"]`).appendChild(opt);
        });
    } catch (e) { }
}

function bindDropzones() {
    ['firma', 'destacado'].forEach(type => {
        const dropzone = document.getElementById(`${type}Dropzone`);
        const input = document.getElementById(`${type}UploadDirect`);
        const holder = document.getElementById(`${type}PreviewHolder`);

        if (!dropzone || !input || !holder) return;

        dropzone.addEventListener('click', () => input.click());

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            State.pendingFiles[type] = file;

            // Preview
            const reader = new FileReader();
            reader.onload = (re) => {
                holder.innerHTML = `<img src="${re.target.result}" style="max-height:80px; margin-top:10px; border:1px solid #ccc; border-radius:4px;">`;
                holder.style.display = 'block';
                // Trigger Visual Update if needed
                if (type === 'destacado') {
                    // Force the config to know we want to render the local image
                    // This is tricky without uploading first, so we use a data URL temporarily
                    State.config[`${type}_imagen`] = re.target.result;
                } else if (type === 'firma') {
                    State.config[`${type}_imagen`] = re.target.result;
                }
                renderMarkers();
            };
            reader.readAsDataURL(file);
        });
    });
}


// Listeners bi-direccionales UI <-> State
function bindInputs() {
    // Escuchar cambios en inputs que tienen data-bind
    document.getElementById('sidebarPanels').addEventListener('input', (e) => {
        const el = e.target;
        const bindKey = el.getAttribute('data-bind');
        if (!bindKey) return;

        let val = el.type === 'checkbox' ? el.checked : el.value;

        // Transformar si es number
        if (el.type === 'range' || el.type === 'number') {
            val = parseFloat(val);
            const liveVal = el.parentElement.querySelector('.live-val');
            if (liveVal) liveVal.textContent = val;
        }

        // Actualizar State
        State.config[bindKey] = val;

        // Sincronizaciones Especiales (Hex - Color picker)
        if (el.classList.contains('color-picker')) {
            const hexInput = el.nextElementSibling;
            if (hexInput) hexInput.value = val;
        } else if (el.classList.contains('color-hex')) {
            const pickerInput = el.previousElementSibling;
            if (pickerInput) pickerInput.value = val;
        }

        // Re-render markers (eficiente)
        renderMarkers();
    });

    // Segment Controls y Dropdowns especiales
    document.getElementById('sidebarPanels').addEventListener('click', (e) => {
        const btn = e.target.closest('.segment-control button');
        if (btn) {
            const container = btn.closest('.segment-control');
            const bindKey = container.getAttribute('data-bind');
            const val = btn.getAttribute('data-val');

            State.config[bindKey] = val;

            container.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            renderMarkers();
        }
    });

}

// Expone para el scope global ya q se llama por onchange en el HTML renderizado dinamicamente
window.toggleDestacadoOptions = function () {
    toggleDestacadoCondition();
};

function toggleDestacadoCondition() {
    const val = document.querySelector('[data-bind="destacado_tipo"]')?.value || State.config.destacado_tipo;
    State.config.destacado_tipo = val; // Sync in case it lagged
    document.querySelectorAll('.condition-grupo').forEach(el => {
        el.classList.remove('active');
        if (el.getAttribute('data-condition') === `destacado_tipo:${val}`) {
            el.classList.add('active');
        }
    });
    renderMarkers();
}

function renderFirmaPreview() {
    const holder = document.getElementById('firmaPreviewHolder');
    if (!holder) return;

    const saved = State.config.firma_imagen;
    if (saved) {
        let uri = saved;
        if (!uri.startsWith('data:')) uri = State.basePath + uri;
        holder.innerHTML = `<img src="${uri}" style="max-height:80px; margin-top:10px; border:1px solid #ccc; border-radius:4px; display:block;">`;
        holder.style.display = 'block';
    } else {
        holder.innerHTML = '';
        holder.style.display = 'none';
    }
}

function renderDestacadoIconGrid() {
    const grid = document.querySelector('.icon-selector-grid');
    if (!grid) return;
    if (grid.querySelectorAll('.sticker-option').length > 0) {
        // Solo actualizar selección si ya está construido
        grid.querySelectorAll('input[type="radio"]').forEach(input => {
            input.checked = input.value === State.config.destacado_icono;
        });
        return;
    }

    grid.innerHTML = '';

    const stickers = ['estrella', 'medalla', 'trofeo', 'corona', 'laurel', 'sello', 'insignia', 'cinta', 'lazo', 'lazo-insignia', 'capitan', 'rango'];

    stickers.forEach(s => {
        const label = document.createElement('label');
        label.className = 'sticker-option';

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'destacado_icono_radio';
        input.value = s;
        if (State.config.destacado_icono === s) input.checked = true;

        input.onchange = (e) => {
            State.config.destacado_icono = e.target.value;
            renderMarkers();
        };

        const img = document.createElement('img');
        img.src = `${State.assetsPath}/stickers/${s}.png`;
        img.title = s;

        label.appendChild(input);
        label.appendChild(img);
        grid.appendChild(label);
    });
}

function renderTemplateImage() {
    const customTemplates = State.templates.filter(tpl => tpl.archivo && tpl.archivo.trim() !== '');

    if (customTemplates.length === 0) {
        document.getElementById('canvasWrapper').style.display = 'none';
        document.getElementById('canvasEmptyState').style.display = 'flex';
        document.getElementById('btnSaveConfig').disabled = true;
        document.getElementById('btnPreview').disabled = true;
        return; // Sale temprano sin cargar imagen
    } else {
        document.getElementById('canvasWrapper').style.display = 'block';
        document.getElementById('canvasEmptyState').style.display = 'none';
        document.getElementById('btnSaveConfig').disabled = false;
        document.getElementById('btnPreview').disabled = false;

        // Auto-seleccionar la primera plantilla si no hay ninguna activa o si la activa era la del sistema fantasma
        if (!State.currentTemplateId || !customTemplates.find(t => t.id == State.currentTemplateId)) {
            State.currentTemplateId = customTemplates[0].id;
        }
    }

    const imgEl = document.getElementById('templateBaseImage');
    const tpl = customTemplates.find(t => t.id == State.currentTemplateId);
    
    if (!tpl) return;
    
    const path = `${State.basePath}uploads/grupos/${State.grupoId}/${tpl.archivo}`;

    imgEl.src = path;

    imgEl.onload = () => {
        // --- ESTANDARIZACIÓN DE RESOLUCIÓN ---
        // Forzamos el Editor a operar sobre un lienzo maestro de 1600x1131
        // Desvinculandolo permanentemente de la resolución nativa de la imagen de fondo.
        State.ui.imageRealWidth = 1600;
        State.ui.imageRealHeight = 1131;

        // Recalcular escala canvas VS real
        const canvasWrapper = document.getElementById('canvasWrapper');
        if (canvasWrapper) {
            syncCanvasScaleAndLayer();

            // Observador de redimensión: recalcula escala al hacer zoom o resize
            if (State.ui.resizeObserver) {
                State.ui.resizeObserver.disconnect();
            }
            State.ui.resizeObserver = new ResizeObserver(() => {
                const previousScale = State.ui.canvasScale;
                syncCanvasScaleAndLayer();
                if (Math.abs(State.ui.canvasScale - previousScale) > 0.001) {
                    renderMarkers();
                }
            });
            State.ui.resizeObserver.observe(canvasWrapper);
        }
        renderMarkers();
    };
}

function syncCanvasScaleAndLayer() {
    const canvasWrapper = document.getElementById('canvasWrapper');
    const layer = document.getElementById('markersLayer');
    if (!canvasWrapper || !layer || !State.ui.imageRealWidth || !State.ui.imageRealHeight) return;

    // clientWidth evita incluir el borde del wrapper y alinea exactamente capa e imagen.
    const wrapperWidth = canvasWrapper.clientWidth;
    State.ui.canvasScale = wrapperWidth > 0 ? (wrapperWidth / State.ui.imageRealWidth) : 1;

    // La capa siempre vive en coordenadas maestras 1600x1131.
    layer.style.width = `${State.ui.imageRealWidth}px`;
    layer.style.height = `${State.ui.imageRealHeight}px`;
    layer.style.transformOrigin = 'top left';
    layer.style.transform = `scale(${State.ui.canvasScale})`;
}


// Renderizar los marcadores en el lienzo
function renderMarkers() {
    const layer = document.getElementById('markersLayer');
    if (!layer) return;
    layer.innerHTML = '';
    syncCanvasScaleAndLayer();

    // --- AUTO CENTERING LOGIC ---
    // Si la configuracion es nueva (-1), calculamos el centro exacto en base al tamaño REAL de esta imagen especifica
    const rw = State.ui.imageRealWidth;
    const rh = State.ui.imageRealHeight;

    if (State.config.ancho_razon === -1 || State.config.ancho_razon === 1200) {
        State.config.ancho_razon = rw * 0.75; // 75% del ancho de la plantilla
    }

    const yMap = {
        'nombre': rh * 0.35,
        'razon': rh * 0.45,
        'fecha': rh * 0.60,
        'qr': rh * 0.75,
        'firma': rh * 0.80,
        'destacado': rh * 0.75
    };

    const varsList = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];
    varsList.forEach(vid => {
        if (State.config[`posicion_${vid}_x`] === -1 || State.config[`posicion_${vid}_x`] === 800 || State.config[`posicion_${vid}_x`] === 200 || State.config[`posicion_${vid}_x`] === 725 || State.config[`posicion_${vid}_x`] === 350 || State.config[`posicion_${vid}_x`] === 1100) {

            // Distribuir elementos horizontalmente si no tienen posicion
            if (vid === 'razon') {
                State.config.posicion_razon_x = (rw - State.config.ancho_razon) / 2;
            } else if (vid === 'qr') {
                State.config.posicion_qr_x = (rw / 2) - (rw * 0.15) - (State.config.tamanio_qr / 2); // Un poco a la izquierda
            } else if (vid === 'destacado') {
                State.config.posicion_destacado_x = (rw / 2) + (rw * 0.15) - (State.config.destacado_tamanio / 2); // Un poco a la derecha
            } else if (vid === 'firma') {
                State.config.posicion_firma_x = (rw / 2) - (State.config.tamanio_firma / 2);
            } else {
                // Heuristica basica para textos (asume ~300px normalizados)
                State.config[`posicion_${vid}_x`] = (rw / 2) - 150;
            }
        }

        // Re-asignar Y si es el default quemado viejo o -1
        if (State.config[`posicion_${vid}_y`] === -1 || State.config[`posicion_${vid}_y`] === 350 || State.config[`posicion_${vid}_y`] === 450 || State.config[`posicion_${vid}_y`] === 600 || State.config[`posicion_${vid}_y`] === 700 || State.config[`posicion_${vid}_y`] === 800) {

            let baseY = yMap[vid] || (rh / 2);

            // Ajuste vertical (Top-Left Origin vs Center Visual)
            if (vid === 'qr') {
                baseY -= (State.config.tamanio_qr / 2);
            } else if (vid === 'firma') {
                baseY -= (State.config.tamanio_firma / 2);
            } else if (vid === 'destacado') {
                baseY -= (State.config.destacado_tamanio / 2);
            } else if (vid === 'nombre' || vid === 'razon' || vid === 'fecha') {
                // Heurística de altura de texto para centrar
                let fontSize = State.config[`tamanio_${vid === 'nombre' ? 'fuente' : vid}`] || 24;
                baseY -= (fontSize / 2);
            }

            State.config[`posicion_${vid}_y`] = baseY;
        }
    });

    // Nombres de Variables
    const vars = [
        { id: 'nombre', html: `<span class="marker-label"><i class="fas fa-user"></i></span>${formatText(State.mockData.nombre, State.config.formato_nombre)}` },
        { id: 'razon', html: `<span class="marker-label"><i class="fas fa-file-alt"></i></span><span class="razon-text">${formatRazonText()}</span>` },
        { id: 'fecha', html: `<span class="marker-label"><i class="fas fa-calendar"></i></span><span>${formatFechaText()}</span>` },
        { id: 'qr', html: `<span class="marker-label"><i class="fas fa-qrcode"></i></span><i class="fas fa-qrcode" style="font-size:${State.config.tamanio_qr}px; line-height:1; display:block;"></i>` },
        { id: 'firma', html: `<span class="marker-label"><i class="fas fa-signature"></i></span>${getFirmaHtml()}` },
        { id: 'destacado', html: `<span class="marker-label"><i class="fas fa-star"></i></span>${getDestacadoHtml()}` }
    ];

    vars.forEach(v => {
        if (!State.config.variables_habilitadas.includes(v.id)) return; // No renderizar si no está habilitada

        const m = document.createElement('div');
        m.id = `${v.id}Marker`;
        m.className = `marker type-${v.id}`;
        m.setAttribute('data-id', v.id);

        // Base Pos
        m.style.left = `${State.config[`posicion_${v.id}_x`]}px`;
        m.style.top = `${State.config[`posicion_${v.id}_y`]}px`;

        // Modificaciones Estilos Especificos (Fuente, Tamaño, Color, Ancho)
        if (v.id === 'nombre' || v.id === 'razon' || v.id === 'fecha') {
            const fontReq = State.config[`fuente_${v.id}`];
            m.style.fontFamily = `"${State.fontMap[fontReq] || 'sans-serif'}", sans-serif`;

            const pxSize = State.config[`tamanio_${v.id === 'nombre' ? 'fuente' : v.id}`];
            m.style.fontSize = `${pxSize}px`;

            m.style.color = State.config[`color_${v.id === 'nombre' ? 'texto' : v.id}`];
        }

        if (v.id === 'razon') {
            m.style.width = `${State.config.ancho_razon}px`;
            m.style.textAlign = State.config.alineacion_razon;
            m.style.lineHeight = '1.3';

            // Extra resize handle visual for razon
            v.html += `<div class="resize-handle" style="position:absolute; right:-5px; top:0; bottom:0; width:10px; cursor:ew-resize; background:rgba(0,0,0,0.1); border-right:2px solid var(--primary-color);"></div>`;
        }

        if (v.id === 'qr') {
            m.style.width = `${State.config.tamanio_qr}px`;
            m.style.height = `${State.config.tamanio_qr}px`;
            m.style.display = 'flex';
            m.style.alignItems = 'center';
            m.style.justifyContent = 'center';
        }

        if (v.id === 'firma') {
            m.style.display = 'flex';
            m.style.alignItems = 'center';
            m.style.justifyContent = 'center';

            const saved = State.config.firma_imagen;
            if (saved) {
                m.style.width = `${State.config.tamanio_firma}px`;
                m.style.height = 'auto'; // Altura determinada por ratio real de la imagen
                m.style.overflow = 'visible';
            } else {
                m.style.width = 'auto';
            }
        }

        if (v.id === 'destacado') {
            m.style.width = `${State.config.destacado_tamanio}px`;
            m.style.height = `${State.config.destacado_tamanio}px`;
            m.style.display = 'flex';
            m.style.alignItems = 'center';
            m.style.justifyContent = 'center';
        }

        m.innerHTML = `<div class="marker-content">${v.html}</div>`;
        layer.appendChild(m);
    });

    // Attach fallback handlers for firma images that may 404
    layer.querySelectorAll('.type-firma img').forEach(img => {
        img.addEventListener('error', function () {
            const iconSize = State.config.tamanio_firma * 0.5;
            this.outerHTML = '<i class="fas fa-signature" style="font-size:' + iconSize + 'px; line-height:1; display:block; color:#333;"></i>';
        });
    });

}

// Visibilidad en vivo al tocar switches
function renderMarkersVisibility() {
    renderMarkers(); // Simplemente renderiza según lista actual
}

// Helpers Formateo
function formatText(t, format) {
    if (!t) return '';
    if (format === 'mayusculas') return t.toUpperCase();
    if (format === 'minusculas') return t.toLowerCase();
    // Capitalizado
    return t.toLowerCase().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function formatRazonText() {
    let t = State.config.razon_defecto || '';
    t = t.replace(/\{grupo\}/g, State.mockData.grupo)
        .replace(/\{categoria\}/g, State.mockData.categoria)
        .replace(/\{nombre\}/g, State.mockData.nombre)
        .replace(/\{fecha\}/g, formatFechaText());
    return t || 'Escribe texto predeterminado...';
}

/**
 * Replica la lógica de formatearFecha() de Certificate.php en el frontend:
 * 1. Escapa palabras literales " de " / " del " igual que el backend.
 * 2. Sustituye tokens PHP (d, j, m, n, Y, F) en un solo pase regex.
 * 3. Restaura las palabras literales escapadas.
 */
function phpDateFormat(fmt, date) {
    const mesesEs = [
        'Enero','Febrero','Marzo','Abril','Mayo','Junio',
        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];
    date = date || new Date();
    const d  = date.getDate();
    const m  = date.getMonth();
    const Y  = date.getFullYear();
    const pad2 = n => String(n).padStart(2, '0');

    // Paso 1: pre-escapar palabras literales con placeholders opacos
    let f = fmt
        .replace(/ del /g, '\x02')
        .replace(/ de /g,  '\x01');

    // Paso 2: sustituir tokens en un solo pase
    const tokens = { d: pad2(d), j: String(d), m: pad2(m + 1), n: String(m + 1), Y: String(Y), F: mesesEs[m] };
    f = f.replace(/[djmnYF]/g, tok => tokens[tok] !== undefined ? tokens[tok] : tok);

    // Paso 3: restaurar los literales
    f = f.replace(/\x01/g, ' de ').replace(/\x02/g, ' del ');

    return f;
}

function formatFechaText(fmt) {
    return phpDateFormat(fmt || State.config.formato_fecha);
}

function updateFechaFormatLabels() {
    const sel = document.querySelector('[data-bind="formato_fecha"]');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        opt.textContent = phpDateFormat(opt.value);
    });
}

function getFirmaHtml() {
    const saved = State.config.firma_imagen;
    if (saved) {
        let uri = saved;
        if (!uri.startsWith('data:')) uri = State.basePath + uri;
        return `<img src="${uri}" style="width:100%; height:auto; display:block; object-fit:contain;">`;
    }
    // Ícono default cuando no hay imagen guardada
    return `<i class="fas fa-signature" style="font-size:${State.config.tamanio_firma * 0.5}px; line-height:1; display:block; color:#333;"></i>`;
}

function getDestacadoHtml() {
    const ic = State.config.destacado_icono || 'estrella';
    return `<img src="${State.assetsPath}/stickers/${ic}.png" style="width:100%; height:100%; object-fit:contain;">`;
}

// ==========================================
// 5. LÓGICA DE INTERACCION DRAG / RESIZE (interact.js)
// ==========================================
function initInteractJS() {
    // Draggable genérico para todos
    interact('.marker').draggable({
        ignoreFrom: '.resize-handle',
        listeners: {
            start(event) {
                event.target.classList.add('interact-dragging');
            },
            move(event) {
                const target = event.target;
                const id = target.getAttribute('data-id');
                const s = State.ui.canvasScale;

                // Actualizar DB State en vivo
                State.config[`posicion_${id}_x`] += event.dx / s;
                State.config[`posicion_${id}_y`] += event.dy / s;

                // Actualizar UI Style
                target.style.left = `${State.config[`posicion_${id}_x`]}px`;
                target.style.top = `${State.config[`posicion_${id}_y`]}px`;

                updateContextValues();
            },
            end(event) {
                event.target.classList.remove('interact-dragging');
                // Snap to valid boundaries logic if needed
            }
        },
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: false
            })
        ]
    });

    // Resizable específico para la Razón
    interact('.marker.type-razon').resizable({
        edges: { left: false, right: '.resize-handle', bottom: false, top: false },
        listeners: {
            move: function (event) {
                let { x, y } = event.target.dataset;
                const s = State.ui.canvasScale;
                const target = event.target;

                // Evitar negativos
                let newWidthVal = (event.rect.width / s);
                if (newWidthVal < 100) newWidthVal = 100;

                State.config.ancho_razon = Math.round(newWidthVal);

                // Update UI visually
                target.style.width = `${State.config.ancho_razon}px`;

                // Update Sidebar slider
                const input = document.querySelector('[data-bind="ancho_razon"]');
                if (input) {
                    input.value = State.config.ancho_razon;
                    const lv = input.parentElement.querySelector('.live-val');
                    if (lv) lv.textContent = State.config.ancho_razon;
                }
            }
        },
        modifiers: [
            interact.modifiers.restrictSize({
                min: { width: 100 }
            })
        ]
    });
}

function updateContextValues() {
    // Update simple visually the coordinates blocks
    ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'].forEach(v => {
        const el = document.getElementById(`coord${v.charAt(0).toUpperCase() + v.slice(1)}`);
        if (el) {
            el.textContent = `X: ${Math.round(State.config[`posicion_${v}_x`])}, Y: ${Math.round(State.config[`posicion_${v}_y`])}`;
        }
    });
}

// ==========================================
// 6. ACCIONES: GUARDAR, PREVIEW, TEMPLATES
// ==========================================
async function saveConfig() {
    const btn = document.getElementById('btnSaveConfig');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('grupo_id', State.grupoId);
        formData.append('plantilla_id', State.currentTemplateId || '');

        // Adjuntar config base
        const excludedKeys = ['id', 'grupo_id', 'plantilla_id', 'archivo', 'es_activa', 'fecha_creacion', 'orden', 'nombre'];
        Object.keys(State.config).forEach(k => {
            if (excludedKeys.includes(k)) return;

            if (k === 'variables_habilitadas') {
                formData.append(k, JSON.stringify(State.config[k]));
            } else if (!k.includes('imagen')) { // Images handled separately
                formData.append(k, State.config[k]);
            }
        });

        // Adjuntar archivos pendientes si hay
        if (State.pendingFiles.firma) {
            formData.append('firma_imagen', State.pendingFiles.firma);
        }
        if (State.pendingFiles.destacado) {
            formData.append('destacado_imagen', State.pendingFiles.destacado);
        }

        const res = await fetch(`${State.basePath}api/grupos/plantillas.php?action=save_config`, {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            if (data.plantilla_id) {
                State.currentTemplateId = data.plantilla_id;
            }

            // Limpiar colas de subida
            State.pendingFiles.firma = null;
            State.pendingFiles.destacado = null;

            // Re-fetch config to get true URLs for custom images
            await fetchConfig();

            notifySuccess('Configuración guardada correctamente');
        } else {
            notifyError(data.message || data.error || 'Error al guardar');
        }
    } catch (e) {
        console.error(e);
        notifyError('Error de red');
    } finally {
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        btn.disabled = false;
    }
}

async function uploadTemplate(e) {
    const file = e.target.files[0];
    if (!file) return;

    const customTemplates = State.templates.filter(tpl => tpl.archivo && tpl.archivo.trim() !== '');
    if (customTemplates.length >= 5) {
        notifyError('Límite alcanzado: Ya tienes 5 plantillas guardadas.');
        return;
    }

    // Quick validate
    if (!file.type.match('image.*')) {
        notifyError('El archivo debe ser una imagen');
        return;
    }

    const formData = new FormData();
    formData.append('plantilla', file);
    formData.append('grupo_id', State.grupoId);

    try {
        const res = await fetch(`${State.basePath}api/grupos/plantillas.php?action=upload`, {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            State.currentTemplateId = data.plantilla_id; // Forzar que esta comience a ser la activa
            await fetchConfig();
            notifySuccess('Plantilla subida y seleccionada con éxito');
        } else {
            notifyError(data.message || data.error || 'Error al subir plantilla');
        }
    } catch (err) {
        console.error(err);
        notifyError('Error de servidor al subir plantilla');
    }
}

async function selectTemplate(id) {
    // Evitar recargar si ya es la activa
    if (State.currentTemplateId == id) return;

    // Guardar ID temporalmente para que fetchConfig sepa qué pedir
    State.currentTemplateId = id;

    // El fetchConfig se encarga de re-dibujar la UI (renderAll) con las variables de esta plantilla en particular
    notifySuccess('Cargando configuración de la plantilla...');
    await fetchConfig();
}

window.promptDeleteTemplate = function (id, evt) {
    evt.stopPropagation(); // Evitar seleccionar
    document.getElementById('deleteModal').classList.add('active');

    // Bind action a ese id
    document.getElementById('btnConfirmDelete').onclick = () => deleteActiveTemplate(id);
};

async function deleteActiveTemplate(id) {
    document.getElementById('deleteModal').classList.remove('active');

    try {
        const res = await fetch(`${State.basePath}api/grupos/plantillas.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `plantilla_id=${id}&grupo_id=${State.grupoId}`
        });
        const data = await res.json();

        if (data.success) {
            if (State.currentTemplateId == id) {
                State.currentTemplateId = null; // Volver al sistema si borré la activa
            }
            await fetchConfig();
            notifySuccess('Plantilla eliminada');
        } else {
            notifyError(data.error || 'Error al eliminar');
        }
    } catch (e) {
        notifyError('Fallo en la red');
    }
}

function restoreSystemTemplate() {
    selectTemplate(null);
}

function guardarSnapshotPreviewGrupo(dataUrl) {
    if (!dataUrl) return;

    try {
        const plantillaId = State.currentTemplateId || 'system';
        const payload = {
            grupo_id: Number(State.grupoId) || null,
            plantilla_id: plantillaId,
            snapshot_data_url: dataUrl,
            generado_en: new Date().toISOString()
        };

        localStorage.setItem(`cce_snapshot_grupo_${State.grupoId}_${plantillaId}`, JSON.stringify(payload));
        localStorage.setItem(`cce_snapshot_grupo_${State.grupoId}_last`, JSON.stringify(payload));
    } catch (e) {
        console.warn('No se pudo guardar snapshot de preview en localStorage:', e);
    }
}

async function previewCertificate() {
    const modal = document.getElementById('previewModal');
    const container = document.getElementById('previewContainer');

    modal.classList.add('active');
    container.innerHTML = `<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Generando previsualización...</p></div>`;

    // Modo 1 (preferido): captura exacta del lienzo (plantilla + capa de variables)
    // Esto evita diferencias visuales entre lo que se acomoda y lo que se previsualiza.
    try {
        const canvasWrapper = document.getElementById('canvasWrapper');
        if (window.html2canvas && canvasWrapper && canvasWrapper.style.display !== 'none') {
            const capture = await window.html2canvas(canvasWrapper, {
                backgroundColor: '#ffffff',
                useCORS: true,
                allowTaint: true,
                scale: 2,
                logging: false
            });

            const previewDataUrl = capture.toDataURL('image/png');
            guardarSnapshotPreviewGrupo(previewDataUrl);
            container.innerHTML = `<img src="${previewDataUrl}" style="width:100%; height:auto; border-radius:4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">`;
            return;
        }
    } catch (captureErr) {
        console.warn('Fallback a preview backend por error en captura del lienzo:', captureErr);
    }

    // Modo 2 (fallback): render backend tradicional
    const timestamp = Date.now();
    const formData = new FormData();
    formData.append('tipo', 'grupo');
    formData.append('id', State.grupoId);
    formData.append('use_form_data', '1');
    formData.append('plantilla_id', State.currentTemplateId || 'system');

    // Mapear todas las configuraciones actuales
    const excludedKeys = ['id', 'grupo_id', 'plantilla_id', 'archivo', 'es_activa', 'fecha_creacion', 'orden', 'nombre'];
    Object.keys(State.config).forEach(key => {
        if (excludedKeys.includes(key)) return;

        let value = State.config[key];
        if (Array.isArray(value)) value = JSON.stringify(value);
        if (typeof value === 'boolean') value = value ? '1' : '0';
        formData.append(key, value);
    });

    // Adjuntar archivos pendientes si hay (para previsualizar nueva firma/sticker)
    if (State.pendingFiles.firma) {
        formData.append('firma_imagen_file', State.pendingFiles.firma);
    }
    if (State.pendingFiles.destacado) {
        formData.append('destacado_imagen_file', State.pendingFiles.destacado);
    }

    fetch(`${State.basePath}api/preview/index.php?v=${timestamp}`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && (data.preview_url || data.preview_data_url)) {
                // No cache para la imagen cuando viene por URL.
                const finalUrl = data.preview_url
                    ? (data.preview_url + '?v=' + Date.now())
                    : data.preview_data_url;
                container.innerHTML = `<img src="${finalUrl}" style="width:100%; height:auto; border-radius:4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">`;
            } else {
                throw new Error(data.message || 'Error en respuesta');
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = `<div style="text-align:center; padding:50px; color:red;"><i class="fas fa-exclamation-triangle fa-3x"></i><p>Error al generar la previsualización.</p><p>${err.message || 'Intente nuevamente.'}</p></div>`;
        });
}

// ==========================================
// 7. UTILIDADES NOTIFICACIONES (Mocked for legacy compatibility)
// ==========================================
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

function notifySuccess(msg) {
    showNotification(msg, 'success');
}
function notifyError(msg) {
    showNotification(msg, 'error');
}
