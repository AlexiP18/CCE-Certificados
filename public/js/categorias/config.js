// Variables globales
let currentTemplates = [];
let activeTemplateId = null;
let sliderIndex = 0;
const ITEMS_PER_VIEW = 3;
const MAX_TEMPLATES = 6;
let isDragging = false;
let currentMarker = null;
let offsetX = 0;
let offsetY = 0;
let canvasScale = 1;
let imageRealWidth = 1600;
let imageRealHeight = 1131;
let isResizing = false;
let resizeStartX = 0;
let resizeStartWidth = 0;

// Variables de contexto (se inicializan desde el PHP)
let categoriaId = null;
let basePath = '';
let grupoNombreActual = 'Nombre del Grupo';
let categoriaNombreActual = 'Nombre de Categoría';
let grupoId = null;

document.addEventListener('DOMContentLoaded', function () {
    // Detectar el basePath correcto
    basePath = window.location.pathname.includes('/public/') ? '../' : '/cce-certificados/';

    // Obtener ID de categoría
    const urlParams = new URLSearchParams(window.location.search);
    categoriaId = urlParams.get('id');

    if (!categoriaId) {
        alert('Categoría no especificada');
        window.location.href = 'dashboard/index.php';
        return;
    }

    document.getElementById('categoria_id').value = categoriaId;

    // Actualizar logo
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.src = basePath + 'assets/logos/logo-cce.png';
    }

    // Inicializar todo
    loadCategoriaConfig();

    // Esperar a que el canvas cargue para inicializar drag & drop
    const canvas = document.getElementById('canvas');
    if (canvas.complete) {
        initAll();
    } else {
        canvas.addEventListener('load', initAll);
    }
    // Backup init
    setTimeout(initAll, 500);
});

function initAll() {
    updateMarkerPositions();
    initDragDrop();
    initRazonResize();
    updateMarkersVisibility();
    initCheckboxEvents();
    initSizeChangeEvents();
    initColorPickers();
    initFileUploads();
}

// =====================================================
// MANEJO DE FUENTES
// =====================================================

// Mapeo de valores a nombres de fuente CSS (se llenará dinámicamente o se usará fallback)
// Nota: El PHP generará un script inline con el fontMap específico si es necesario, 
// o podemos deducirlo de las opciones del select.
function updateSelectFont(select) {
    // Intentar obtener la familia de la opción seleccionada
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption) {
        select.style.fontFamily = selectedOption.style.fontFamily;
    }
}

// Aplicar a todos los selectores de fuente
function initFontSelectors() {
    document.querySelectorAll('.font-select').forEach(select => {
        updateSelectFont(select);
        select.addEventListener('change', () => {
            updateSelectFont(select);
            updateMarkerSizes(); // Actualizar canvas
        });
    });
}

// =====================================================
// CANVAS Y DRAG & DROP
// =====================================================

function updateCanvasScale() {
    const canvas = document.getElementById('canvas');
    if (canvas.naturalWidth > 0) {
        imageRealWidth = canvas.naturalWidth;
        imageRealHeight = canvas.naturalHeight;
    }
    canvasScale = canvas.offsetWidth / imageRealWidth;
    // console.log(`Canvas scale: ${canvasScale}, Real dimensions: ${imageRealWidth}x${imageRealHeight}`);
}

function initDragDrop() {
    const markers = document.querySelectorAll('.draggable');

    markers.forEach(marker => {
        marker.addEventListener('mousedown', startDrag);
    });

    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);
}

function startDrag(e) {
    if (e.target.classList.contains('resize-handle')) return;

    isDragging = true;
    currentMarker = e.target.closest('.draggable');
    currentMarker.classList.add('active');

    const rect = currentMarker.getBoundingClientRect();

    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;

    e.preventDefault();
}

function drag(e) {
    if (!isDragging || !currentMarker) return;

    const container = document.getElementById('canvasContainer');
    const containerRect = container.getBoundingClientRect();

    let x = e.clientX - containerRect.left - offsetX;
    let y = e.clientY - containerRect.top - offsetY;

    // Límites
    x = Math.max(-10, Math.min(x, containerRect.width - currentMarker.offsetWidth + 10));
    y = Math.max(-10, Math.min(y, containerRect.height - currentMarker.offsetHeight + 10));

    const type = currentMarker.dataset.type;

    currentMarker.style.left = x + 'px';
    currentMarker.style.top = y + 'px';

    // Calcular coordenadas reales
    let realX, realY;

    if (type === 'nombre' || type === 'razon' || type === 'fecha' || type === 'destacado') {
        realX = Math.round(x / canvasScale);
        realY = Math.round(y / canvasScale);
    } else {
        const markerWidth = currentMarker.offsetWidth;
        const markerHeight = currentMarker.offsetHeight;
        realX = Math.round((x + markerWidth / 2) / canvasScale);
        realY = Math.round((y + markerHeight / 2) / canvasScale);
    }

    updateCoordinatesFromMarker(type, realX, realY);
}

function stopDrag() {
    if (currentMarker) {
        currentMarker.classList.remove('active');
    }
    isDragging = false;
    currentMarker = null;
}

function updateCoordinatesFromMarker(type, x, y) {
    const posX = document.getElementById(`posicion_${type}_x`);
    const posY = document.getElementById(`posicion_${type}_y`);
    const coord = document.getElementById(`coord${type.charAt(0).toUpperCase() + type.slice(1)}`);

    if (posX) posX.value = x;
    if (posY) posY.value = y;
    if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
}

function updateMarkerPositions() {
    updateCanvasScale();

    const canvas = document.getElementById('canvas');
    const canvasWidth = canvas.offsetWidth;
    const canvasHeight = canvas.offsetHeight;

    const types = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];

    types.forEach(type => {
        const posX = document.getElementById(`posicion_${type}_x`);
        const posY = document.getElementById(`posicion_${type}_y`);
        const marker = document.getElementById(`${type}Marker`);

        if (posX && posY && marker) {
            const x = parseInt(posX.value) || 0;
            const y = parseInt(posY.value) || 0;

            let left, top;

            if (type === 'qr' || type === 'firma') {
                const markerWidth = marker.offsetWidth || (type === 'qr' ? 60 : 80);
                const markerHeight = marker.offsetHeight || (type === 'qr' ? 60 : 40);
                left = (x * canvasScale) - (markerWidth / 2);
                top = (y * canvasScale) - (markerHeight / 2);
            } else {
                left = x * canvasScale;
                top = y * canvasScale;
            }

            // Límites básicos
            left = Math.max(-20, Math.min(left, canvasWidth));
            top = Math.max(-20, Math.min(top, canvasHeight));

            marker.style.left = left + 'px';
            marker.style.top = top + 'px';

            if (type === 'destacado') marker.style.display = 'flex';
            if (type === 'razon') marker.style.display = 'flex';
        }
    });

    // Actualizar ancho razón específicamente
    const anchoRazon = parseInt(document.getElementById('ancho_razon')?.value) || 600;
    updateRazonMarkerWidth(anchoRazon);

    updateMarkerSizes();
}

// =====================================================
// RESIZE RAZÓN
// =====================================================

function initRazonResize() {
    const resizeHandle = document.getElementById('razonResizeHandle');
    const razonMarker = document.getElementById('razonMarker');
    const container = document.getElementById('canvasContainer');

    if (resizeHandle) {
        resizeHandle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            isResizing = true;
            resizeStartX = e.clientX;
            resizeStartWidth = razonMarker.offsetWidth;
            document.body.style.cursor = 'ew-resize';
            e.preventDefault();
        });
    }

    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;

        const deltaX = e.clientX - resizeStartX;
        let newWidth = Math.max(100, resizeStartWidth + deltaX);

        const containerWidth = container.offsetWidth;
        const markerLeft = razonMarker.offsetLeft;
        const maxWidth = containerWidth - markerLeft - 10;
        if (newWidth > maxWidth) newWidth = maxWidth;

        razonMarker.style.width = newWidth + 'px';

        const paddingTotal = 20;
        const realWidth = Math.round((newWidth - paddingTotal) / canvasScale);

        document.getElementById('ancho_razon').value = Math.max(100, realWidth);
        const input = document.getElementById('ancho_razon_input');
        if (input) input.value = Math.max(100, realWidth);
    });

    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            document.body.style.cursor = '';
        }
    });

    const anchoInput = document.getElementById('ancho_razon_input');
    if (anchoInput) {
        anchoInput.addEventListener('input', () => {
            const realWidth = parseInt(anchoInput.value) || 600;
            document.getElementById('ancho_razon').value = realWidth;
            updateRazonMarkerWidth(realWidth);
        });
    }
}

function updateRazonMarkerWidth(realWidth) {
    const razonMarker = document.getElementById('razonMarker');
    if (razonMarker) {
        const paddingTotal = 20;
        const scaledWidth = (realWidth * canvasScale) + paddingTotal;
        razonMarker.style.width = scaledWidth + 'px';
    }
}

function updateRazonMarkerText() {
    const razonTextarea = document.getElementById('razon_defecto');
    const razonMarkerText = document.getElementById('razonMarkerText');
    const razonMarker = document.getElementById('razonMarker');

    if (razonTextarea && razonMarkerText && razonMarker) {
        let texto = razonTextarea.value.trim();

        texto = texto.replace(/\{grupo\}/gi, grupoNombreActual);
        texto = texto.replace(/\{categoria\}/gi, categoriaNombreActual);
        texto = texto.replace(/\{nombre\}/gi, 'Juan Pérez García');

        const fechaActual = new Date();
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const fechaFormateada = `${fechaActual.getDate()} de ${meses[fechaActual.getMonth()]} de ${fechaActual.getFullYear()}`;
        texto = texto.replace(/\{fecha\}/gi, fechaFormateada);

        if (!texto) texto = 'Escribe el texto de razón...';

        razonMarkerText.textContent = texto;

        updateMarkerSizes(); // Aplica fuentes y tamaños

        // Alineación
        const alineacion = document.getElementById('alineacion_razon')?.value || 'justified';
        razonMarker.style.textAlign = alineacion;
        razonMarkerText.style.textAlign = alineacion;
    }
}

// =====================================================
// TAMAÑOS Y VISUALIZACIÓN
// =====================================================

function updateMarkerSizes() {
    const tamanioQr = parseInt(document.getElementById('tamanio_qr')?.value) || 200;
    const tamanioFirma = parseInt(document.getElementById('tamanio_firma')?.value) || 200;
    const tamanioFuente = parseInt(document.getElementById('tamanio_fuente')?.value) || 50;
    const tamanioRazon = parseInt(document.getElementById('tamanio_razon')?.value) || 24;
    const tamanioFecha = parseInt(document.getElementById('tamanio_fecha')?.value) || 20;
    const tamanioDestacado = parseInt(document.getElementById('tamanio_destacado')?.value) || 100;

    const qrMarker = document.getElementById('qrMarker');
    const firmaMarker = document.getElementById('firmaMarker');
    const nombreMarker = document.getElementById('nombreMarker');
    const razonMarker = document.getElementById('razonMarker');
    const fechaMarker = document.getElementById('fechaMarker');
    const destacadoMarker = document.getElementById('destacadoMarker');

    if (qrMarker) {
        const qrSize = Math.max(30, tamanioQr * canvasScale);
        qrMarker.style.width = qrSize + 'px';
        qrMarker.style.height = qrSize + 'px';
    }

    if (firmaMarker) {
        const firmaWidth = Math.max(40, tamanioFirma * canvasScale);
        const firmaHeight = Math.max(20, (tamanioFirma / 2) * canvasScale);
        firmaMarker.style.width = firmaWidth + 'px';
        firmaMarker.style.height = firmaHeight + 'px';
    }

    if (nombreMarker) {
        const fontSize = Math.max(10, tamanioFuente * canvasScale * 0.8);
        nombreMarker.style.fontSize = fontSize + 'px';
    }

    if (razonMarker) {
        const fontSize = Math.max(8, tamanioRazon * canvasScale);
        razonMarker.style.fontSize = fontSize + 'px';

        const fuenteRazon = document.getElementById('fuente_razon');
        if (fuenteRazon && fuenteRazon.options.length > 0) {
            const selected = fuenteRazon.options[fuenteRazon.selectedIndex];
            if (selected) razonMarker.style.fontFamily = selected.style.fontFamily;
        }
    }

    if (fechaMarker) {
        const fontSize = Math.max(8, tamanioFecha * canvasScale * 0.8);
        fechaMarker.style.fontSize = fontSize + 'px';
    }

    if (destacadoMarker) {
        const destacadoWidth = Math.max(20, tamanioDestacado * canvasScale);
        destacadoMarker.style.width = destacadoWidth + 'px';
        destacadoMarker.style.height = destacadoWidth + 'px';
    }
}

function initSizeChangeEvents() {
    ['tamanio_qr', 'tamanio_firma', 'tamanio_fuente', 'tamanio_razon', 'tamanio_fecha', 'tamanio_destacado'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => {
                updateMarkerSizes();
                updateMarkerPositions();
            });
        }
    });

    const fuenteRazon = document.getElementById('fuente_razon');
    if (fuenteRazon) {
        fuenteRazon.addEventListener('change', updateMarkerSizes);
    }
}

function updateMarkersVisibility() {
    const vars = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];

    vars.forEach(v => {
        const checkbox = document.getElementById(`var_${v}`);
        const marker = document.getElementById(`${v}Marker`);
        const coord = document.getElementById(`coord${v.charAt(0).toUpperCase() + v.slice(1)}Container`);

        const enabled = checkbox ? checkbox.checked : true;

        if (marker) marker.style.setProperty('display', enabled ? 'flex' : 'none', 'important');
        if (coord) coord.style.display = enabled ? 'block' : 'none';
    });
}

function initCheckboxEvents() {
    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateMarkersVisibility);
    });
}

// =====================================================
// UTILIDADES E INTERFAZ
// =====================================================

function switchTab(tabName) {
    document.querySelectorAll('.variable-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.variable-tab-content').forEach(content => content.classList.remove('active'));

    document.querySelector(`.variable-tab[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

function setFormatoNombre(formato) {
    document.getElementById('formato_nombre').value = formato;
    document.querySelectorAll('.formato-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.formato === formato);
    });
}

function setAlineacionRazon(align) {
    document.getElementById('alineacion_razon').value = align;
    document.querySelectorAll('.alineacion-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.align === align);
    });
    updateRazonMarkerText();
}

function toggleDestacadoOptions() {
    const tipo = document.getElementById('destacado_tipo').value;
    document.getElementById('destacado_icono_group').style.display = tipo === 'icono' ? 'block' : 'none';
    document.getElementById('destacado_imagen_group').style.display = tipo === 'imagen' ? 'block' : 'none';
    updateDestacadoSticker();
}

function updateDestacadoSticker(customImageSrc = null) {
    const marker = document.getElementById('destacadoMarker');
    if (!marker) return;

    const tipo = document.getElementById('destacado_tipo').value;
    let content = '';

    if (tipo === 'icono') {
        const selectedIcon = document.querySelector('input[name="destacado_icono"]:checked')?.value || 'estrella';
        content = `<img src="${basePath}assets/stickers/${selectedIcon}.png" alt="${selectedIcon}" style="width: 100%; height: 100%; object-fit: contain;">`;
    } else {
        const src = customImageSrc || document.querySelector('#currentDestacadoImg img')?.src;
        if (src) {
            content = `<img src="${src}" alt="Destacado" style="width: 100%; height: 100%; object-fit: contain;">`;
        } else {
            content = `<div style="width: 100%; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image"></i></div>`;
        }
    }
    marker.innerHTML = content;
}

function initColorPickers() {
    ['texto', 'razon', 'fecha'].forEach(type => {
        const colorInput = document.getElementById(`color_${type}`);
        const hexInput = document.getElementById(`color_${type}_hex`);

        if (colorInput && hexInput) {
            colorInput.addEventListener('input', e => hexInput.value = e.target.value);
            hexInput.addEventListener('input', e => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) colorInput.value = e.target.value;
            });
        }
    });
}

// =====================================================
// API CALLS & LOGIC
// =====================================================

function updateToggleState() {
    const toggle = document.getElementById('usarPlantillaGrupo');
    const configContent = document.getElementById('configContent');
    const info = document.getElementById('grupoPlantillaInfo');
    const hiddenInput = document.getElementById('usar_plantilla_propia');

    if (toggle.checked) {
        configContent.classList.add('hidden');
        info.style.display = 'block';
        hiddenInput.value = '0';
    } else {
        configContent.classList.remove('hidden');
        info.style.display = 'none';
        hiddenInput.value = '1';
    }
}

document.getElementById('usarPlantillaGrupo').addEventListener('change', async function () {
    updateToggleState();

    try {
        const formData = new FormData();
        formData.append('action', this.checked ? 'use_grupo' : 'use_propias');
        formData.append('categoria_id', categoriaId);

        const response = await fetch('../api/categorias/plantillas.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success && !this.checked) {
            await loadTemplatesSlider();
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

async function loadTemplatesSlider() {
    try {
        const response = await fetch(`../api/categorias/plantillas.php?action=list&categoria_id=${categoriaId}`);
        const data = await response.json();

        if (data.success) {
            currentTemplates = data.plantillas;
            renderTemplatesSlider();
        }
    } catch (error) {
        console.error('Error cargando plantillas:', error);
    }
}

function renderTemplatesSlider() {
    const slider = document.getElementById('templatesSlider');
    const countBadge = document.getElementById('templateCount');

    slider.innerHTML = '';
    countBadge.textContent = `${currentTemplates.length}/${MAX_TEMPLATES}`;

    if (currentTemplates.length === 0) {
        slider.innerHTML = `
            <div class="templates-empty">
                <i class="fas fa-image"></i>
                <p>No hay plantillas. Haz clic en + para agregar.</p>
            </div>
            <div class="template-add-btn" id="templateAddBtn">
                <i class="fas fa-plus"></i>
                <span>Agregar</span>
            </div>`;
    } else {
        currentTemplates.forEach((template, index) => {
            const isActive = template.es_activa == 1;
            if (isActive) activeTemplateId = template.id;

            const item = document.createElement('div');
            item.className = `template-item ${isActive ? 'active' : ''}`;
            item.innerHTML = `
                <img src="${basePath}uploads/categorias/${categoriaId}/${template.archivo}" class="template-thumb">
                <div class="template-info">
                    <div class="template-name">${template.nombre}</div>
                    <div class="template-actions">
                         <button type="button" class="btn-select" onclick="event.stopPropagation(); selectTemplate(${template.id})"><i class="fas fa-check-circle"></i></button>
                         <button type="button" class="btn-delete" onclick="event.stopPropagation(); deleteTemplate(${template.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            item.onclick = () => selectTemplate(template.id);
            slider.appendChild(item);
        });

        const addBtn = document.createElement('div');
        addBtn.className = 'template-add-btn';
        addBtn.id = 'templateAddBtn';
        addBtn.innerHTML = `<i class="fas fa-plus"></i><span>Agregar</span>`;
        if (currentTemplates.length < MAX_TEMPLATES) slider.appendChild(addBtn);
    }

    // Event listener para boton agregar
    const btn = document.getElementById('templateAddBtn');
    if (btn) btn.onclick = () => document.getElementById('plantillaUpload').click();

    loadActiveTemplateToCanvas();
}

async function selectTemplate(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'set_active');
        formData.append('categoria_id', categoriaId);
        formData.append('plantilla_id', id);

        const response = await fetch('../api/categorias/plantillas.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            currentTemplates.forEach(t => t.es_activa = (t.id == id) ? 1 : 0);
            activeTemplateId = id;
            renderTemplatesSlider();
            await loadTemplateConfig(id);
        }
    } catch (e) {
        console.error(e);
    }
}

async function deleteTemplate(id) {
    if (!confirm('¿Eliminar plantilla?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('categoria_id', categoriaId);
        formData.append('plantilla_id', id);

        const response = await fetch('../api/categorias/plantillas.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) await loadTemplatesSlider();
    } catch (e) {
        console.error(e);
    }
}

async function uploadNewTemplate(file) {
    try {
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('categoria_id', categoriaId);
        formData.append('plantilla', file);
        formData.append('nombre', file.name.replace(/\.[^/.]+$/, ''));

        const response = await fetch('../api/categorias/plantillas.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            currentTemplates.push(data.plantilla);
            await selectTemplate(data.plantilla.id);
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Error al subir');
    }
}

async function loadActiveTemplateToCanvas() {
    const active = currentTemplates.find(t => t.es_activa == 1);
    const canvas = document.getElementById('canvas');

    if (active) {
        canvas.src = `${basePath}uploads/categorias/${categoriaId}/${active.archivo}`;
        canvas.onload = () => {
            updateCanvasScale();
            updateMarkerPositions();
        };
        await loadTemplateConfig(active.id);
    } else {
        canvas.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3ESube una plantilla%3C/text%3E%3C/svg%3E';
    }
}

async function loadTemplateConfig(id) {
    try {
        const response = await fetch(`../api/categorias/plantillas.php?action=get_config&categoria_id=${categoriaId}&plantilla_id=${id}`);
        const data = await response.json();

        if (data.success && data.config) {
            const c = data.config;

            // Helper
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };

            setVal('posicion_nombre_x', c.posicion_nombre_x || 400);
            setVal('posicion_nombre_y', c.posicion_nombre_y || 300);
            setVal('posicion_razon_x', c.posicion_razon_x || 400);
            setVal('posicion_razon_y', c.posicion_razon_y || 360);
            setVal('posicion_fecha_x', c.posicion_fecha_x || 400);
            setVal('posicion_fecha_y', c.posicion_fecha_y || 420);
            setVal('posicion_qr_x', c.posicion_qr_x || 920);
            setVal('posicion_qr_y', c.posicion_qr_y || 419);
            setVal('posicion_firma_x', c.posicion_firma_x || 800);
            setVal('posicion_firma_y', c.posicion_firma_y || 850);
            setVal('posicion_destacado_x', c.posicion_destacado_x || 50);
            setVal('posicion_destacado_y', c.posicion_destacado_y || 50);

            setVal('tamanio_fuente', c.tamanio_fuente || 50);
            setVal('tamanio_razon', c.tamanio_razon || 24);
            setVal('tamanio_fecha', c.tamanio_fecha || 20);
            setVal('tamanio_qr', c.tamanio_qr || 200);
            setVal('tamanio_firma', c.tamanio_firma || 200);
            setVal('tamanio_destacado', c.tamanio_destacado || 100);

            setVal('fuente_nombre', c.fuente_nombre || 'Roboto-Regular');
            setVal('fuente_razon', c.fuente_razon || 'Roboto-Regular');
            setVal('fuente_fecha', c.fuente_fecha || 'Roboto-Regular');

            setVal('color_texto', c.color_texto || '#000000');
            setVal('color_texto_hex', c.color_texto || '#000000');
            setVal('color_razon', c.color_razon || '#333333');
            setVal('color_razon_hex', c.color_razon || '#333333');
            setVal('color_fecha', c.color_fecha || '#333333');
            setVal('color_fecha_hex', c.color_fecha || '#333333');

            setVal('razon_defecto', c.razon_defecto || '');
            setVal('ancho_razon', c.ancho_razon || 600);
            setVal('ancho_razon_input', c.ancho_razon || 600);

            setFormatoNombre(c.formato_nombre || 'mayusculas');
            setAlineacionRazon(c.alineacion_razon || 'justified');

            setVal('destacado_tipo', c.destacado_tipo || 'icono');
            const radio = document.querySelector(`input[name="destacado_icono"][value="${c.destacado_icono || 'estrella'}"]`);
            if (radio) radio.checked = true;

            if (c.variables_habilitadas) {
                const vars = JSON.parse(c.variables_habilitadas);
                document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = vars.includes(cb.value);
                });
            }

            updateMarkerPositions();
            updateMarkersVisibility();
            updateRazonMarkerText();
            toggleDestacadoOptions();
        }
    } catch (e) {
        console.error(e);
    }
}

async function saveTemplateConfig() {
    if (!activeTemplateId) return false;

    try {
        const formData = new FormData(document.getElementById('configForm'));
        formData.append('action', 'save_config');
        formData.append('plantilla_id', activeTemplateId);

        const response = await fetch('../api/categorias/plantillas.php', { method: 'POST', body: formData });
        return (await response.json()).success;
    } catch (e) {
        console.error(e);
        return false;
    }
}

async function loadCategoriaConfig() {
    try {
        const response = await fetch(`../api/categorias/config.php?action=get&id=${categoriaId}`);
        const data = await response.json();

        if (data.success) {
            const cat = data.categoria;
            categoriaNombreActual = cat.nombre;
            grupoNombreActual = cat.grupo_nombre;
            grupoId = cat.grupo_id;

            document.getElementById('CategoriaNombre').textContent = cat.nombre;
            document.getElementById('CategoriaDescripcion').textContent = cat.descripcion || '';
            const icon = document.getElementById('CategoriaIcon');
            icon.textContent = cat.icono || '📁';
            icon.style.backgroundColor = cat.color;
            document.getElementById('GrupoPadreNombre').textContent = cat.grupo_nombre || '-';

            const links = document.querySelectorAll('#linkConfigGrupo, #linkConfigGrupo2');
            links.forEach(l => l.href = `grupos/config.php?id=${grupoId}`);

            document.getElementById('usarPlantillaGrupo').checked = !(cat.usar_plantilla_propia == 1);
            updateToggleState();

            // Si no usa plantilla del grupo, cargar slider
            if (cat.usar_plantilla_propia == 1) {
                await loadTemplatesSlider();
            }
        }
    } catch (e) {
        console.error(e);
    }
}

function initFileUploads() {
    const pInput = document.getElementById('plantillaUpload');
    pInput.addEventListener('change', e => {
        if (e.target.files.length) {
            uploadNewTemplate(e.target.files[0]);
            e.target.value = '';
        }
    });

    // Firma
    const fInput = document.getElementById('firma_imagen');
    document.getElementById('uploadFirmaArea').addEventListener('click', () => fInput.click());
    fInput.addEventListener('change', e => {
        if (e.target.files.length) showFirmaPreview(e.target.files[0]);
    });
}

function showFirmaPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('currentFirma').innerHTML = `
            <div class="firma-preview">
                <img src="${e.target.result}">
                <div class="firma-info"><div class="firma-filename">${file.name}</div></div>
                <button type="button" class="btn-remove-firma" onclick="clearFile('firma')">Quitar</button>
            </div>
        `;
        document.getElementById('currentFirma').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearFile(type) {
    if (type === 'firma') {
        document.getElementById('firma_imagen').value = '';
        document.getElementById('currentFirma').innerHTML = '';
        document.getElementById('currentFirma').style.display = 'none';
    }
}

// Form submit
document.getElementById('configForm').addEventListener('submit', async e => {
    e.preventDefault();

    // Variables habilitadas
    const vars = [];
    document.querySelectorAll('.checkbox-item input:checked').forEach(cb => vars.push(cb.value));
    document.getElementById('variables_habilitadas').value = JSON.stringify(vars);

    const formData = new FormData(e.target);
    formData.append('action', 'update_config');

    try {
        const response = await fetch('../api/categorias/config.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            if (activeTemplateId) await saveTemplateConfig();
            alert('Configuración guardada');
            loadCategoriaConfig();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) {
        console.error(err);
        alert('Error al guardar');
    }
});
