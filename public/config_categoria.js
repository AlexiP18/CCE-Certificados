// config_categoria.js - JavaScript para configuración de plantilla de categoría

// basePath ya está definido en el HTML (config_categoria.php)
console.log('basePath detectado:', basePath);
console.log('window.location.pathname:', window.location.pathname);

const iconEmojis = {
    workshop: '🛠️', course: '📚', trophy: '🏆', seminar: '🎓',
    award: '🏅', certificate: '📜', medal: '🥇', star: '⭐',
    rocket: '🚀', book: '📖'
};

// Mapeo de valores a nombres de fuente CSS
const fontMap = {
    'Roboto-Regular': 'Roboto',
    'OpenSans': 'OpenSans',
    'Lato-Regular': 'Lato',
    'Montserrat': 'Montserrat',
    'Poppins-Regular': 'Poppins',
    'PlayfairDisplay': 'PlayfairDisplay',
    'Motterdam': 'Motterdam'
};

// Función para actualizar la fuente del select
function updateSelectFont(select) {
    const fontName = fontMap[select.value] || 'Roboto';
    select.style.fontFamily = `'${fontName}', sans-serif`;
}

// Inicializar selectores de fuente cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.font-select').forEach(select => {
        updateSelectFont(select);
        select.addEventListener('change', () => updateSelectFont(select));
    });
});

// Función para cambiar de pestaña
function switchTab(tabName) {
    // Desactivar todas las pestañas
    document.querySelectorAll('.variable-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.variable-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Activar la pestaña seleccionada
    document.querySelector(`.variable-tab[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

const urlParams = new URLSearchParams(window.location.search);
const categoriaId = urlParams.get('id');

if (!categoriaId) {
    alert('Categoría no especificada');
    window.location.href = 'index.php';
}

document.getElementById('categoria_id').value = categoriaId;

// Variables para el drag & drop
let isDragging = false;
let currentMarker = null;
let offsetX = 0;
let offsetY = 0;
let canvasScale = 1;

// Dimensiones reales de la imagen cargada
let imageRealWidth = 1600;
let imageRealHeight = 1131;

// Calcular escala del lienzo basándose en las dimensiones reales de la imagen
function updateCanvasScale() {
    const canvas = document.getElementById('canvas');
    // Usar el ancho natural de la imagen (dimensiones reales)
    if (canvas.naturalWidth > 0) {
        imageRealWidth = canvas.naturalWidth;
        imageRealHeight = canvas.naturalHeight;
    }
    canvasScale = canvas.offsetWidth / imageRealWidth;
    console.log(`Canvas scale: ${canvasScale}, Real dimensions: ${imageRealWidth}x${imageRealHeight}`);
}

// Inicializar drag & drop
function initDragDrop() {
    const markers = document.querySelectorAll('.draggable');
    
    markers.forEach(marker => {
        marker.addEventListener('mousedown', startDrag);
    });
    
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);
    
    // Posicionar marcadores inicialmente
    updateMarkerPositions();
}

function startDrag(e) {
    isDragging = true;
    currentMarker = e.target.closest('.draggable');
    currentMarker.classList.add('active');
    
    const rect = currentMarker.getBoundingClientRect();
    const containerRect = document.getElementById('canvasContainer').getBoundingClientRect();
    
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
    
    // Límites (permitir un poco de margen negativo para el indicador)
    x = Math.max(-10, Math.min(x, containerRect.width - currentMarker.offsetWidth + 10));
    y = Math.max(-10, Math.min(y, containerRect.height - currentMarker.offsetHeight + 10));
    
    const type = currentMarker.dataset.type;
    
    currentMarker.style.left = x + 'px';
    currentMarker.style.top = y + 'px';
    
    // Calcular coordenadas reales basándose en el punto de anclaje
    let realX, realY;
    
    if (type === 'nombre' || type === 'razon' || type === 'fecha') {
        // Para nombre, razón y fecha: el punto está en la esquina superior izquierda del marcador
        realX = Math.round(x / canvasScale);
        realY = Math.round(y / canvasScale);
    } else {
        // Para QR y Firma: el punto está en el centro del marcador
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
    if (type === 'nombre') {
        const posX = document.getElementById('posicion_nombre_x');
        const posY = document.getElementById('posicion_nombre_y');
        const coord = document.getElementById('coordNombre');
        if (posX) posX.value = x;
        if (posY) posY.value = y;
        if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
    } else if (type === 'razon') {
        const posX = document.getElementById('posicion_razon_x');
        const posY = document.getElementById('posicion_razon_y');
        const coord = document.getElementById('coordRazon');
        if (posX) posX.value = x;
        if (posY) posY.value = y;
        if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
    } else if (type === 'fecha') {
        const posX = document.getElementById('posicion_fecha_x');
        const posY = document.getElementById('posicion_fecha_y');
        const coord = document.getElementById('coordFecha');
        if (posX) posX.value = x;
        if (posY) posY.value = y;
        if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
    } else if (type === 'qr') {
        const posX = document.getElementById('posicion_qr_x');
        const posY = document.getElementById('posicion_qr_y');
        const coord = document.getElementById('coordQr');
        if (posX) posX.value = x;
        if (posY) posY.value = y;
        if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
    } else if (type === 'firma') {
        const posX = document.getElementById('posicion_firma_x');
        const posY = document.getElementById('posicion_firma_y');
        const coord = document.getElementById('coordFirma');
        if (posX) posX.value = x;
        if (posY) posY.value = y;
        if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
    }
}

function updateMarkerPositions() {
    updateCanvasScale();
    
    const canvas = document.getElementById('canvas');
    const canvasWidth = canvas.offsetWidth;
    const canvasHeight = canvas.offsetHeight;
    
    const posNombreX = document.getElementById('posicion_nombre_x');
    const posNombreY = document.getElementById('posicion_nombre_y');
    const posRazonX = document.getElementById('posicion_razon_x');
    const posRazonY = document.getElementById('posicion_razon_y');
    const posFechaX = document.getElementById('posicion_fecha_x');
    const posFechaY = document.getElementById('posicion_fecha_y');
    const posQrX = document.getElementById('posicion_qr_x');
    const posQrY = document.getElementById('posicion_qr_y');
    const posFirmaX = document.getElementById('posicion_firma_x');
    const posFirmaY = document.getElementById('posicion_firma_y');
    
    // DEBUG: verificar que los elementos existen
    console.log('DEBUG updateMarkerPositions:');
    console.log('  posRazonX element:', posRazonX);
    console.log('  posRazonX value:', posRazonX?.value);
    console.log('  posRazonY element:', posRazonY);
    console.log('  posRazonY value:', posRazonY?.value);
    console.log('  posFechaX element:', posFechaX);
    console.log('  posFechaX value:', posFechaX?.value);
    console.log('  posFechaY element:', posFechaY);
    console.log('  posFechaY value:', posFechaY?.value);
    
    const nombreX = parseInt(posNombreX?.value) || 400;
    const nombreY = parseInt(posNombreY?.value) || 300;
    const razonX = parseInt(posRazonX?.value) || 400;
    const razonY = parseInt(posRazonY?.value) || 360;
    const fechaX = parseInt(posFechaX?.value) || 400;
    const fechaY = parseInt(posFechaY?.value) || 420;
    const qrX = parseInt(posQrX?.value) || 920;
    const qrY = parseInt(posQrY?.value) || 419;
    const firmaX = parseInt(posFirmaX?.value) || 800;
    const firmaY = parseInt(posFirmaY?.value) || 850;
    
    console.log('  Calculated positions:');
    console.log('    razonX:', razonX, 'razonY:', razonY);
    console.log('    fechaX:', fechaX, 'fechaY:', fechaY);
    console.log('  Canvas scale:', canvasScale);
    
    const nombreMarker = document.getElementById('nombreMarker');
    const razonMarker = document.getElementById('razonMarker');
    const fechaMarker = document.getElementById('fechaMarker');
    const qrMarker = document.getElementById('qrMarker');
    const firmaMarker = document.getElementById('firmaMarker');
    
    console.log('  Markers found:');
    console.log('    razonMarker:', razonMarker);
    console.log('    fechaMarker:', fechaMarker);
    
    if (nombreMarker) {
        let left = nombreX * canvasScale;
        let top = nombreY * canvasScale;
        // Asegurar que esté dentro del canvas
        left = Math.max(0, Math.min(left, canvasWidth - 20));
        top = Math.max(0, Math.min(top, canvasHeight - 20));
        nombreMarker.style.left = left + 'px';
        nombreMarker.style.top = top + 'px';
        console.log('  nombreMarker positioned at:', left, top);
    }
    if (razonMarker) {
        let left = razonX * canvasScale;
        let top = razonY * canvasScale;
        console.log('  razonMarker calculated left:', razonX, '*', canvasScale, '=', left);
        console.log('  razonMarker calculated top:', razonY, '*', canvasScale, '=', top);
        left = Math.max(0, Math.min(left, canvasWidth - 20));
        top = Math.max(0, Math.min(top, canvasHeight - 20));
        razonMarker.style.left = left + 'px';
        razonMarker.style.top = top + 'px';
        console.log('  razonMarker positioned at:', left, top);
    }
    if (fechaMarker) {
        let left = fechaX * canvasScale;
        let top = fechaY * canvasScale;
        console.log('  fechaMarker calculated left:', fechaX, '*', canvasScale, '=', left);
        console.log('  fechaMarker calculated top:', fechaY, '*', canvasScale, '=', top);
        left = Math.max(0, Math.min(left, canvasWidth - 20));
        top = Math.max(0, Math.min(top, canvasHeight - 20));
        fechaMarker.style.left = left + 'px';
        fechaMarker.style.top = top + 'px';
        console.log('  fechaMarker positioned at:', left, top);
    }
    if (qrMarker) {
        const qrWidth = qrMarker.offsetWidth || 60;
        const qrHeight = qrMarker.offsetHeight || 60;
        let left = (qrX * canvasScale) - (qrWidth / 2);
        let top = (qrY * canvasScale) - (qrHeight / 2);
        // Asegurar que esté dentro del canvas
        left = Math.max(-10, Math.min(left, canvasWidth - qrWidth + 10));
        top = Math.max(-10, Math.min(top, canvasHeight - qrHeight + 10));
        qrMarker.style.left = left + 'px';
        qrMarker.style.top = top + 'px';
    }
    if (firmaMarker) {
        const firmaWidth = firmaMarker.offsetWidth || 80;
        const firmaHeight = firmaMarker.offsetHeight || 40;
        let left = (firmaX * canvasScale) - (firmaWidth / 2);
        let top = (firmaY * canvasScale) - (firmaHeight / 2);
        // Asegurar que esté dentro del canvas
        left = Math.max(-10, Math.min(left, canvasWidth - firmaWidth + 10));
        top = Math.max(-10, Math.min(top, canvasHeight - firmaHeight + 10));
        firmaMarker.style.left = left + 'px';
        firmaMarker.style.top = top + 'px';
    }
    
    updateCoordinatesFromMarker('nombre', nombreX, nombreY);
    updateCoordinatesFromMarker('razon', razonX, razonY);
    updateCoordinatesFromMarker('fecha', fechaX, fechaY);
    updateCoordinatesFromMarker('qr', qrX, qrY);
    updateCoordinatesFromMarker('firma', firmaX, firmaY);
    
    // También actualizar tamaños de marcadores
    updateMarkerSizes();
}

// Función para actualizar el tamaño de los marcadores según los valores de los inputs
function updateMarkerSizes() {
    const tamanioQr = parseInt(document.getElementById('tamanio_qr')?.value) || 200;
    const tamanioFirma = parseInt(document.getElementById('tamanio_firma')?.value) || 200;
    const tamanioFuente = parseInt(document.getElementById('tamanio_fuente')?.value) || 50;
    const tamanioRazon = parseInt(document.getElementById('tamanio_razon')?.value) || 24;
    const tamanioFecha = parseInt(document.getElementById('tamanio_fecha')?.value) || 20;
    
    const qrMarker = document.getElementById('qrMarker');
    const firmaMarker = document.getElementById('firmaMarker');
    const nombreMarker = document.getElementById('nombreMarker');
    const razonMarker = document.getElementById('razonMarker');
    const fechaMarker = document.getElementById('fechaMarker');
    
    // Aplicar tamaño escalado al marcador QR
    if (qrMarker) {
        const qrSize = Math.max(30, tamanioQr * canvasScale);
        qrMarker.style.width = qrSize + 'px';
        qrMarker.style.height = qrSize + 'px';
    }
    
    // Aplicar tamaño escalado al marcador Firma (mantener proporción 2:1)
    if (firmaMarker) {
        const firmaWidth = Math.max(40, tamanioFirma * canvasScale);
        const firmaHeight = Math.max(20, (tamanioFirma / 2) * canvasScale);
        firmaMarker.style.width = firmaWidth + 'px';
        firmaMarker.style.height = firmaHeight + 'px';
    }
    
    // Aplicar tamaño de fuente escalado al marcador Nombre
    if (nombreMarker) {
        const fontSize = Math.max(10, tamanioFuente * canvasScale * 0.8); // Factor 0.8 para mejor visualización
        nombreMarker.style.fontSize = fontSize + 'px';
        // Ajustar padding proporcionalmente
        const padding = Math.max(2, fontSize * 0.2);
        nombreMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
    }
    
    // Aplicar tamaño de fuente escalado al marcador Razón
    if (razonMarker) {
        const fontSize = Math.max(8, tamanioRazon * canvasScale * 0.8);
        razonMarker.style.fontSize = fontSize + 'px';
        const padding = Math.max(2, fontSize * 0.2);
        razonMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
    }
    
    // Aplicar tamaño de fuente escalado al marcador Fecha
    if (fechaMarker) {
        const fontSize = Math.max(8, tamanioFecha * canvasScale * 0.8);
        fechaMarker.style.fontSize = fontSize + 'px';
        const padding = Math.max(2, fontSize * 0.2);
        fechaMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
    }
}

// Inicializar eventos de cambio de tamaño
function initSizeChangeEvents() {
    const tamanioQr = document.getElementById('tamanio_qr');
    const tamanioFirma = document.getElementById('tamanio_firma');
    const tamanioFuente = document.getElementById('tamanio_fuente');
    const tamanioRazon = document.getElementById('tamanio_razon');
    const tamanioFecha = document.getElementById('tamanio_fecha');
    
    if (tamanioQr) {
        tamanioQr.addEventListener('input', () => {
            updateMarkerSizes();
            updateMarkerPositions(); // Reposicionar después de cambiar tamaño
        });
    }
    if (tamanioFirma) {
        tamanioFirma.addEventListener('input', () => {
            updateMarkerSizes();
            updateMarkerPositions();
        });
    }
    if (tamanioFuente) {
        tamanioFuente.addEventListener('input', () => {
            updateMarkerSizes();
        });
    }
    if (tamanioRazon) {
        tamanioRazon.addEventListener('input', () => {
            updateMarkerSizes();
        });
    }
    if (tamanioFecha) {
        tamanioFecha.addEventListener('input', () => {
            updateMarkerSizes();
        });
    }
}

// Función para mostrar/ocultar marcadores según variables habilitadas
function updateMarkersVisibility() {
    const varNombre = document.getElementById('var_nombre');
    const varRazon = document.getElementById('var_razon');
    const varFecha = document.getElementById('var_fecha');
    const varQr = document.getElementById('var_qr');
    const varFirma = document.getElementById('var_firma');
    
    const nombreEnabled = varNombre?.checked ?? true;
    const razonEnabled = varRazon?.checked ?? true;
    const fechaEnabled = varFecha?.checked ?? true;
    const qrEnabled = varQr?.checked ?? true;
    const firmaEnabled = varFirma?.checked ?? true;
    
    const nombreMarker = document.getElementById('nombreMarker');
    const razonMarker = document.getElementById('razonMarker');
    const fechaMarker = document.getElementById('fechaMarker');
    const qrMarker = document.getElementById('qrMarker');
    const firmaMarker = document.getElementById('firmaMarker');
    
    const coordNombreContainer = document.getElementById('coordNombreContainer');
    const coordRazonContainer = document.getElementById('coordRazonContainer');
    const coordFechaContainer = document.getElementById('coordFechaContainer');
    const coordQrContainer = document.getElementById('coordQrContainer');
    const coordFirmaContainer = document.getElementById('coordFirmaContainer');
    
    if (nombreMarker) nombreMarker.style.setProperty('display', nombreEnabled ? 'flex' : 'none', 'important');
    if (razonMarker) razonMarker.style.setProperty('display', razonEnabled ? 'flex' : 'none', 'important');
    if (fechaMarker) fechaMarker.style.setProperty('display', fechaEnabled ? 'flex' : 'none', 'important');
    if (qrMarker) qrMarker.style.setProperty('display', qrEnabled ? 'flex' : 'none', 'important');
    if (firmaMarker) firmaMarker.style.setProperty('display', firmaEnabled ? 'flex' : 'none', 'important');
    
    if (coordNombreContainer) coordNombreContainer.style.display = nombreEnabled ? 'block' : 'none';
    if (coordRazonContainer) coordRazonContainer.style.display = razonEnabled ? 'block' : 'none';
    if (coordFechaContainer) coordFechaContainer.style.display = fechaEnabled ? 'block' : 'none';
    if (coordQrContainer) coordQrContainer.style.display = qrEnabled ? 'block' : 'none';
    if (coordFirmaContainer) coordFirmaContainer.style.display = firmaEnabled ? 'block' : 'none';
}

// Inicializar eventos de checkboxes
function initCheckboxEvents() {
    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateMarkersVisibility);
    });
}

// Reajustar al cambiar tamaño de ventana
window.addEventListener('resize', updateMarkerPositions);

// Toggle de configuración personalizada
document.getElementById('usar_plantilla_propia').addEventListener('change', function() {
    const configContent = document.getElementById('configContent');
    const saveButtonContainer = document.getElementById('saveButtonContainer');
    
    if (this.checked) {
        configContent.style.display = 'block';
        saveButtonContainer.style.display = 'none';
    } else {
        configContent.style.display = 'none';
        saveButtonContainer.style.display = 'block';
    }
});

// Inicializar después de cargar la imagen
document.getElementById('canvas').addEventListener('load', () => {
    updateMarkerPositions();
    initDragDrop();
    updateMarkersVisibility();
    initCheckboxEvents();
    initSizeChangeEvents();
});

// También inicializar cuando el DOM esté listo (para imágenes SVG inline)
document.addEventListener('DOMContentLoaded', () => {
    // Pequeño delay para asegurar que el layout esté calculado
    setTimeout(() => {
        updateMarkerPositions();
        initDragDrop();
        updateMarkersVisibility();
        initCheckboxEvents();
        initSizeChangeEvents();
    }, 100);
});

// Sincronizar color picker - Nombre
document.getElementById('color_texto').addEventListener('input', (e) => {
    document.getElementById('color_texto_hex').value = e.target.value;
});
document.getElementById('color_texto_hex').addEventListener('input', (e) => {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('color_texto').value = e.target.value;
    }
});

// Sincronizar color picker - Razón
document.getElementById('color_razon').addEventListener('input', (e) => {
    document.getElementById('color_razon_hex').value = e.target.value;
});
document.getElementById('color_razon_hex').addEventListener('input', (e) => {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('color_razon').value = e.target.value;
    }
});

// Sincronizar color picker - Fecha
document.getElementById('color_fecha').addEventListener('input', (e) => {
    document.getElementById('color_fecha_hex').value = e.target.value;
});
document.getElementById('color_fecha_hex').addEventListener('input', (e) => {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('color_fecha').value = e.target.value;
    }
});

// Mostrar/ocultar campo de fecha específica
document.getElementById('usar_fecha_especifica').addEventListener('change', (e) => {
    const fechaEspecificaGroup = document.getElementById('fecha_especifica_group');
    fechaEspecificaGroup.style.display = e.target.checked ? 'block' : 'none';
});

// Manejo de archivos - Plantilla
const uploadArea = document.getElementById('uploadArea');
const plantillaInput = document.getElementById('plantilla');

uploadArea.addEventListener('click', () => plantillaInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        plantillaInput.files = e.dataTransfer.files;
        showFileName('plantilla', e.dataTransfer.files[0].name);
    }
});

plantillaInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        showFileName('plantilla', e.target.files[0].name);
        // Mostrar vista previa de la nueva plantilla
        previewNewTemplate(e.target.files[0]);
    }
});

// Función para previsualizar nueva plantilla
function previewNewTemplate(file) {
    const canvas = document.getElementById('canvas');
    const reader = new FileReader();
    
    reader.onload = function(e) {
        canvas.src = e.target.result;
        // Cuando la imagen cargue, actualizar escala y marcadores
        canvas.onload = function() {
            console.log('Nueva plantilla cargada:', canvas.naturalWidth, 'x', canvas.naturalHeight);
            updateCanvasScale();
            updateMarkerPositions();
            updateMarkersVisibility();
        };
    };
    
    reader.readAsDataURL(file);
}

// Manejo de archivos - Firma
const uploadFirmaArea = document.getElementById('uploadFirmaArea');
const firmaInput = document.getElementById('firma_imagen');

uploadFirmaArea.addEventListener('click', () => firmaInput.click());

uploadFirmaArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadFirmaArea.classList.add('dragover');
});

uploadFirmaArea.addEventListener('dragleave', () => {
    uploadFirmaArea.classList.remove('dragover');
});

uploadFirmaArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadFirmaArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        firmaInput.files = e.dataTransfer.files;
        showFileName('firma', e.dataTransfer.files[0].name);
    }
});

firmaInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        showFileName('firma', e.target.files[0].name);
    }
});

function showFileName(type, filename) {
    const containerId = type === 'plantilla' ? 'currentPlantilla' : 'currentFirma';
    document.getElementById(containerId).innerHTML = `
        <div class="current-file">
            <span>📎 ${filename}</span>
            <button type="button" onclick="clearFile('${type}')" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">✕</button>
        </div>
    `;
}

function clearFile(type) {
    if (type === 'plantilla') {
        document.getElementById('plantilla').value = '';
        document.getElementById('currentPlantilla').innerHTML = '';
    } else {
        document.getElementById('firma_imagen').value = '';
        document.getElementById('currentFirma').innerHTML = '';
    }
}

async function loadCategoriaConfig() {
    console.log('Iniciando carga de configuración para categoría ID:', categoriaId);
    try {
        const response = await fetch(`api_categoria_config.php?action=get&id=${categoriaId}`);
        console.log('Respuesta recibida:', response.status);
        const data = await response.json();
        console.log('Datos parseados:', data);
        
        if (data.success) {
            const categoria = data.categoria;
            
            console.log('Datos de categoría:', categoria);
            console.log('Icono recibido:', categoria.icono);
            console.log('Color recibido:', categoria.color);
            
            // Actualizar header
            document.getElementById('categoriaNombre').textContent = categoria.nombre;
            document.getElementById('categoriaDescripcion').textContent = categoria.descripcion || '';
            document.getElementById('grupoNombre').textContent = `Grupo: ${categoria.grupo_nombre}`;
            const iconElem = document.getElementById('categoriaIcon');
            iconElem.textContent = categoria.icono || '📁';
            iconElem.style.backgroundColor = categoria.color || '#3498db';
            document.getElementById('categoriaHeader').style.setProperty('--categoria-color', categoria.color || '#3498db');
            
            // Verificar si usa plantilla propia
            const usaPlantillaPropia = categoria.usar_plantilla_propia == 1;
            document.getElementById('usar_plantilla_propia').checked = usaPlantillaPropia;
            
            if (usaPlantillaPropia) {
                document.getElementById('configContent').style.display = 'block';
                document.getElementById('saveButtonContainer').style.display = 'none';
                
                // Cargar configuración de la categoría
                const setValueIfExists = (id, value) => {
                    const elem = document.getElementById(id);
                    if (elem) elem.value = value;
                };
                
                setValueIfExists('firma_nombre', categoria.plantilla_firma_nombre || categoria.firma_nombre || '');
                setValueIfExists('firma_cargo', categoria.plantilla_firma_cargo || categoria.firma_cargo || '');
                setValueIfExists('fuente_nombre', categoria.plantilla_fuente || 'Roboto-Regular');
                setValueIfExists('tamanio_fuente', categoria.plantilla_tamanio_fuente || 48);
                setValueIfExists('color_texto', categoria.plantilla_color_texto || '#000000');
                setValueIfExists('color_texto_hex', categoria.plantilla_color_texto || '#000000');
                setValueIfExists('posicion_nombre_x', categoria.plantilla_pos_nombre_x || 400);
                setValueIfExists('posicion_nombre_y', categoria.plantilla_pos_nombre_y || 300);
                
                // DEBUG: Ver qué valores vienen del servidor
                console.log('DEBUG valores del servidor:');
                console.log('  plantilla_pos_razon_x:', categoria.plantilla_pos_razon_x);
                console.log('  plantilla_pos_razon_y:', categoria.plantilla_pos_razon_y);
                console.log('  plantilla_pos_fecha_x:', categoria.plantilla_pos_fecha_x);
                console.log('  plantilla_pos_fecha_y:', categoria.plantilla_pos_fecha_y);
                
                setValueIfExists('posicion_razon_x', categoria.plantilla_pos_razon_x || 400);
                setValueIfExists('posicion_razon_y', categoria.plantilla_pos_razon_y || 360);
                setValueIfExists('posicion_qr_x', categoria.plantilla_pos_qr_x || 920);
                setValueIfExists('posicion_qr_y', categoria.plantilla_pos_qr_y || 419);
                setValueIfExists('posicion_firma_x', categoria.plantilla_pos_firma_x || 800);
                setValueIfExists('posicion_firma_y', categoria.plantilla_pos_firma_y || 850);
                setValueIfExists('posicion_fecha_x', categoria.plantilla_pos_fecha_x || 400);
                setValueIfExists('posicion_fecha_y', categoria.plantilla_pos_fecha_y || 420);
                setValueIfExists('tamanio_qr', categoria.plantilla_tamanio_qr || 200);
                setValueIfExists('tamanio_firma', categoria.plantilla_tamanio_firma || 150);
                
                // Cargar configuración de Razón
                setValueIfExists('razon_defecto', categoria.plantilla_razon_defecto || 'Por su destacada participación en el {grupo} de {categoria}.');
                setValueIfExists('tamanio_razon', categoria.plantilla_tamanio_razon || 24);
                setValueIfExists('color_razon', categoria.plantilla_color_razon || '#333333');
                setValueIfExists('color_razon_hex', categoria.plantilla_color_razon || '#333333');
                
                // Cargar configuración de Fecha
                console.log('DEBUG Fecha - plantilla_formato_fecha del servidor:', categoria.plantilla_formato_fecha);
                setValueIfExists('formato_fecha', categoria.plantilla_formato_fecha || 'd de F de Y');
                console.log('DEBUG Fecha - valor establecido en select:', document.getElementById('formato_fecha')?.value);
                if (categoria.plantilla_fecha_especifica) {
                    document.getElementById('usar_fecha_especifica').checked = true;
                    setValueIfExists('fecha_especifica', categoria.plantilla_fecha_especifica);
                    document.getElementById('fecha_especifica_group').style.display = 'block';
                }
                setValueIfExists('tamanio_fecha', categoria.plantilla_tamanio_fecha || 20);
                setValueIfExists('color_fecha', categoria.plantilla_color_fecha || '#333333');
                setValueIfExists('color_fecha_hex', categoria.plantilla_color_fecha || '#333333');
                
                // Cargar variables habilitadas
                let variablesHabilitadas = ['nombre', 'qr', 'firma'];
                if (categoria.plantilla_variables_habilitadas) {
                    try {
                        variablesHabilitadas = JSON.parse(categoria.plantilla_variables_habilitadas);
                    } catch(e) {
                        console.error('Error parsing variables_habilitadas:', e);
                    }
                }
                
                // Marcar checkboxes
                document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = variablesHabilitadas.includes(cb.value);
                });
                
                // Inicializar eventos de checkboxes
                initCheckboxEvents();
                
                // Inicializar eventos de cambio de tamaño
                initSizeChangeEvents();
                
                // Actualizar posiciones de marcadores
                updateMarkerPositions();
                
                // Actualizar visibilidad de marcadores
                updateMarkersVisibility();
                
                // Cargar plantilla (propia o heredada del grupo)
                const canvas = document.getElementById('canvas');
                let plantillaArchivo = categoria.plantilla_archivo || categoria.plantilla_archivo_heredada;
                
                console.log('plantillaArchivo:', plantillaArchivo);
                console.log('categoria.plantilla_archivo:', categoria.plantilla_archivo);
                console.log('categoria.plantilla_archivo_heredada:', categoria.plantilla_archivo_heredada);
                console.log('basePath en este punto:', basePath);
                
                if (plantillaArchivo && plantillaArchivo.trim() !== '') {
                    const plantillaPath = `${basePath}assets/templates/${plantillaArchivo}`;
                    const esHeredada = !categoria.plantilla_archivo && categoria.plantilla_archivo_heredada;
                    console.log(esHeredada ? 'Cargando plantilla heredada del grupo:' : 'Cargando plantilla de categoría:', plantillaPath);
                    console.log('Ruta completa que se asignará al canvas:', plantillaPath);
                    
                    // Primero agregar el handler onload ANTES de cambiar src
                    canvas.onload = function() {
                        console.log('Imagen cargada correctamente, actualizando marcadores...');
                        // Pequeño delay para asegurar que el layout esté calculado
                        setTimeout(() => {
                            updateMarkerPositions();
                            updateMarkerSizes();
                        }, 50);
                    };
                    
                    canvas.src = plantillaPath;
                    canvas.onerror = function() {
                        console.error('Error al cargar la plantilla:', plantillaPath);
                        this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3EError al cargar plantilla%3C/text%3E%3C/svg%3E';
                    };
                    
                    // Mostrar información de la plantilla actual
                    if (categoria.plantilla_archivo) {
                        document.getElementById('currentPlantilla').innerHTML = `
                            <div class="current-file">
                                <span>📎 ${categoria.plantilla_archivo}</span>
                            </div>
                        `;
                    } else if (esHeredada) {
                        document.getElementById('currentPlantilla').innerHTML = `
                            <div class="current-file" style="background: #e8f4fd; border-color: #3498db;">
                                <span>📎 ${plantillaArchivo} <small style="color: #3498db;">(heredada del grupo)</small></span>
                            </div>
                        `;
                    }
                } else {
                    console.log('No hay plantilla definida');
                    canvas.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3ESube una plantilla para comenzar%3C/text%3E%3C/svg%3E';
                }
                
                // Mostrar firma (propia o heredada del grupo)
                let firmaArchivo = categoria.plantilla_archivo_firma || categoria.plantilla_archivo_firma_heredada;
                if (firmaArchivo) {
                    document.getElementById('currentFirma').innerHTML = `
                        <div class="current-file">
                            <span>📎 ${firmaArchivo}</span>
                        </div>
                    `;
                }
            } else {
                // No usa plantilla propia - heredar del grupo
                document.getElementById('configContent').style.display = 'none';
                document.getElementById('saveButtonContainer').style.display = 'block';
                
                // Mostrar plantilla heredada del grupo
                const canvas = document.getElementById('canvas');
                if (categoria.plantilla_archivo_heredada && categoria.plantilla_archivo_heredada.trim() !== '') {
                    const plantillaPath = `${basePath}assets/templates/${categoria.plantilla_archivo_heredada}`;
                    console.log('Mostrando plantilla heredada del grupo:', plantillaPath);
                    canvas.src = plantillaPath;
                    canvas.onerror = function() {
                        console.error('Error al cargar la plantilla del grupo:', plantillaPath);
                        this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3EHereda plantilla del grupo%3C/text%3E%3C/svg%3E';
                    };
                } else {
                    console.log('El grupo no tiene plantilla configurada');
                    canvas.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3EHereda configuraci%C3%B3n del grupo%3C/text%3E%3C/svg%3E';
                }
            }
        } else {
            console.error('API retornó error:', data.message);
        }
    } catch (error) {
        console.error('Error capturado:', error);
        console.error('Stack trace:', error.stack);
        alert('Error al cargar la configuración de la categoría: ' + error.message);
    }
}

function resetDefaults() {
    if (confirm('¿Restablecer a valores predeterminados?')) {
        const setValueIfExists = (id, value) => {
            const elem = document.getElementById(id);
            if (elem) elem.value = value;
        };
        
        setValueIfExists('fuente_nombre', 'Roboto-Regular');
        setValueIfExists('tamanio_fuente', 48);
        setValueIfExists('color_texto', '#000000');
        setValueIfExists('color_texto_hex', '#000000');
        setValueIfExists('posicion_nombre_x', 400);
        setValueIfExists('posicion_nombre_y', 300);
        setValueIfExists('posicion_razon_x', 400);
        setValueIfExists('posicion_razon_y', 360);
        setValueIfExists('posicion_qr_x', 920);
        setValueIfExists('posicion_qr_y', 419);
        setValueIfExists('posicion_firma_x', 800);
        setValueIfExists('posicion_firma_y', 850);
        setValueIfExists('posicion_fecha_x', 400);
        setValueIfExists('posicion_fecha_y', 420);
        setValueIfExists('tamanio_qr', 200);
        setValueIfExists('tamanio_firma', 150);
        
        // Restablecer checkboxes
        document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
            cb.checked = true;
        });
        
        updateMarkerPositions();
        updateMarkersVisibility();
    }
}

document.getElementById('configForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Recopilar variables habilitadas si se usa configuración personalizada
    const usaPlantillaPropia = document.getElementById('usar_plantilla_propia').checked;
    
    console.log('usaPlantillaPropia:', usaPlantillaPropia);
    
    if (usaPlantillaPropia) {
        const variablesHabilitadas = [];
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
            variablesHabilitadas.push(cb.value);
        });
        document.getElementById('variables_habilitadas').value = JSON.stringify(variablesHabilitadas);
    }
    
    const formData = new FormData(e.target);
    formData.append('action', 'update_config');
    
    // Debug: ver qué se está enviando
    console.log('FormData entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    try {
        const response = await fetch('api_categoria_config.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Response:', data);
        
        if (data.success) {
            alert('✅ Configuración guardada correctamente');
            loadCategoriaConfig();
        } else {
            alert('❌ Error: ' + (data.message || 'No se pudo guardar la configuración'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Error al guardar la configuración');
    }
});

// Cargar configuración al iniciar
loadCategoriaConfig();

// Inicializar drag & drop cuando se cargue todo
if (document.getElementById('canvas').complete) {
    updateMarkerPositions();
    initDragDrop();
    updateMarkersVisibility();
    initCheckboxEvents();
    initSizeChangeEvents();
}

// Funciones de previsualización
async function previewCertificate() {
    const modal = document.getElementById('previewModal');
    const body = document.getElementById('previewBody');
    
    // Mostrar modal con loading
    modal.classList.add('active');
    body.innerHTML = `
        <div class="preview-loading">
            <i class="fas fa-spinner"></i>
            <p>Generando vista previa...</p>
        </div>
    `;
    
    try {
        const formData = new FormData();
        formData.append('tipo', 'categoria');
        formData.append('id', categoriaId);
        formData.append('use_form_data', '1'); // Indicar que use datos del formulario
        
        // Enviar valores actuales del formulario para preview en tiempo real
        formData.append('posicion_nombre_x', document.getElementById('posicion_nombre_x')?.value || 400);
        formData.append('posicion_nombre_y', document.getElementById('posicion_nombre_y')?.value || 300);
        formData.append('posicion_razon_x', document.getElementById('posicion_razon_x')?.value || 400);
        formData.append('posicion_razon_y', document.getElementById('posicion_razon_y')?.value || 360);
        formData.append('posicion_fecha_x', document.getElementById('posicion_fecha_x')?.value || 400);
        formData.append('posicion_fecha_y', document.getElementById('posicion_fecha_y')?.value || 420);
        formData.append('posicion_qr_x', document.getElementById('posicion_qr_x')?.value || 920);
        formData.append('posicion_qr_y', document.getElementById('posicion_qr_y')?.value || 419);
        formData.append('posicion_firma_x', document.getElementById('posicion_firma_x')?.value || 800);
        formData.append('posicion_firma_y', document.getElementById('posicion_firma_y')?.value || 850);
        
        formData.append('fuente_nombre', document.getElementById('fuente_nombre')?.value || 'Roboto-Regular');
        formData.append('tamanio_fuente', document.getElementById('tamanio_fuente')?.value || 48);
        formData.append('color_texto', document.getElementById('color_texto')?.value || '#000000');
        formData.append('tamanio_qr', document.getElementById('tamanio_qr')?.value || 200);
        formData.append('tamanio_firma', document.getElementById('tamanio_firma')?.value || 150);
        
        // Configuración de Razón
        formData.append('razon_defecto', document.getElementById('razon_defecto')?.value || '');
        formData.append('fuente_razon', document.getElementById('fuente_razon')?.value || 'Roboto-Regular');
        formData.append('tamanio_razon', document.getElementById('tamanio_razon')?.value || 24);
        formData.append('color_razon', document.getElementById('color_razon')?.value || '#333333');
        
        // Configuración de Fecha
        const formatoFechaValue = document.getElementById('formato_fecha')?.value || 'd de F de Y';
        console.log('DEBUG Preview - formato_fecha antes de enviar:', formatoFechaValue);
        formData.append('formato_fecha', formatoFechaValue);
        formData.append('fuente_fecha', document.getElementById('fuente_fecha')?.value || 'Roboto-Regular');
        formData.append('tamanio_fecha', document.getElementById('tamanio_fecha')?.value || 20);
        formData.append('color_fecha', document.getElementById('color_fecha')?.value || '#333333');
        
        // Variables habilitadas
        const variablesHabilitadas = [];
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
            variablesHabilitadas.push(cb.value);
        });
        formData.append('variables_habilitadas', JSON.stringify(variablesHabilitadas));
        
        const response = await fetch('api_preview.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            body.innerHTML = `
                <img src="${data.preview_url}" alt="Vista previa del certificado" style="max-width: 100%;">
                <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Esta es una vista previa con datos de ejemplo
                </p>
            `;
        } else {
            body.innerHTML = `
                <div style="padding: 40px; color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                    <p style="margin-top: 15px;">${data.error || 'Error al generar la vista previa'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        body.innerHTML = `
            <div style="padding: 40px; color: #e74c3c;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                <p style="margin-top: 15px;">Error de conexión al generar la vista previa</p>
            </div>
        `;
    }
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('active');
}

// Cerrar modal con Escape o click fuera
document.getElementById('previewModal').addEventListener('click', (e) => {
    if (e.target.id === 'previewModal') {
        closePreviewModal();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closePreviewModal();
    }
});
