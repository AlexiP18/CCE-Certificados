const iconEmojis = {
    workshop: '🛠️', course: '📚', trophy: '🏆', seminar: '🎓',
    award: '🏅', certificate: '📜', medal: '🥇', star: '⭐',
    rocket: '🚀', book: '📖'
};

// Función para obtener el nombre CSS de la fuente
// fontMap debe ser definido externamente o en el window
function getCssFontFamily(nombreArchivo) {
    if (window.fontMap && window.fontMap[nombreArchivo]) {
        const family = window.fontMap[nombreArchivo];
        // console.log(`Applying font: ${nombreArchivo} -> ${family}`);
        return `"${family}", sans-serif`;
    }
    console.warn(`Font not found in map: ${nombreArchivo}`);
    return 'sans-serif';
}

const urlParams = new URLSearchParams(window.location.search);
// grupoId puede venir de window (inyectado por PHP) o de URL
const grupoId = (typeof serverGrupoId !== 'undefined' && serverGrupoId) ? serverGrupoId : urlParams.get('id');
let grupoNombreActual = 'Nombre del Grupo'; // Se actualiza al cargar la config

// Datos del primer estudiante del grupo (se cargan dinámicamente)
let primerEstudianteNombre = 'Juan Pérez García';
let primerEstudianteCategoria = 'Nombre de Categoría';

if (!grupoId) {
    showNotification('Grupo no especificado', 'error');
    setTimeout(() => window.location.href = 'dashboard/index.php', 1500);
}

const grupoIdInput = document.getElementById('grupo_id');
if (grupoIdInput) grupoIdInput.value = grupoId;

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

// Función para establecer el formato del nombre
function setFormatoNombre(formato) {
    // Actualizar botones
    document.querySelectorAll('.formato-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.querySelector(`.formato-btn[data-formato="${formato}"]`);
    if (activeBtn) activeBtn.classList.add('active');

    // Actualizar campo oculto
    const formatoInput = document.getElementById('formato_nombre');
    if (formatoInput) formatoInput.value = formato;

    // Actualizar el marcador en el canvas usando la función común
    updateNombreMarkerText();
}

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

// Variables para resize del marcador razón
let isResizing = false;
let resizeStartX = 0;
let resizeStartWidth = 0;

// Inicializar drag & drop
function initDragDrop() {
    const markers = document.querySelectorAll('.draggable');
    const container = document.getElementById('canvasContainer');

    markers.forEach(marker => {
        marker.addEventListener('mousedown', startDrag);
    });

    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);

    // Inicializar resize del marcador razón
    initRazonResize();

    // Posicionar marcadores inicialmente
    updateMarkerPositions();
}

// Actualizar texto del marcador nombre en el lienzo
function updateNombreMarkerText() {
    const nombreMarker = document.getElementById('nombreMarker');
    if (nombreMarker) {
        // Obtener el formato actual
        const formatoActual = document.getElementById('formato_nombre')?.value || 'mayusculas';
        let nombreMostrar = primerEstudianteNombre || 'Juan Pérez García';

        switch (formatoActual) {
            case 'mayusculas':
                nombreMostrar = nombreMostrar.toUpperCase();
                break;
            case 'capitalizado':
                nombreMostrar = nombreMostrar.split(' ').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                ).join(' ');
                break;
            case 'minusculas':
                nombreMostrar = nombreMostrar.toLowerCase();
                break;
        }
        nombreMarker.innerHTML = `<i class="fas fa-user"></i> ${nombreMostrar}`;
    }
}

// Actualizar texto del marcador razón en el lienzo
// Esta función actualiza el texto Y fuerza el recálculo de altura del marcador
function updateRazonMarkerText() {
    const razonTextarea = document.getElementById('razon_defecto');
    const razonMarkerText = document.getElementById('razonMarkerText');
    const razonMarker = document.getElementById('razonMarker');

    if (razonTextarea && razonMarkerText && razonMarker) {
        let texto = razonTextarea.value.trim();

        // Reemplazar variables con valores del primer estudiante del grupo
        texto = texto.replace(/\{grupo\}/gi, grupoNombreActual);
        texto = texto.replace(/\{categoria\}/gi, primerEstudianteCategoria);
        texto = texto.replace(/\{nombre\}/gi, primerEstudianteNombre);

        // Formatear fecha usando el formato configurado
        const usarFechaEspecifica = document.getElementById('usar_fecha_especifica')?.checked || false;
        const fechaEspecifica = document.getElementById('fecha_especifica')?.value;
        const formatoFecha = document.getElementById('formato_fecha')?.value || 'd de F de Y';

        let fechaFormateada;
        if (usarFechaEspecifica && fechaEspecifica) {
            fechaFormateada = formatearFechaJS(fechaEspecifica, formatoFecha);
        } else {
            // Usar fecha actual
            const hoy = new Date();
            const fechaHoy = hoy.toISOString().split('T')[0];
            fechaFormateada = formatearFechaJS(fechaHoy, formatoFecha);
        }
        texto = texto.replace(/\{fecha\}/gi, fechaFormateada);

        // Si el texto está vacío, mostrar placeholder
        if (!texto) {
            texto = 'Escribe el texto de razón...';
        }

        // Actualizar el texto del marcador
        razonMarkerText.textContent = texto;

        // Aplicar alineación visual
        updateRazonMarkerAlignment();

        // Aplicar tamaño de fuente y line-height para cálculo correcto de altura
        const tamanioRazon = parseInt(document.getElementById('tamanio_razon')?.value) || 24;
        const fontSize = Math.max(8, tamanioRazon * canvasScale);
        razonMarker.style.fontSize = fontSize + 'px';
        razonMarker.style.lineHeight = '1.3';

        // Forzar recálculo de altura removiendo cualquier altura fija
        razonMarker.style.height = 'auto';

        // Aplicar fuente configurada
        const fuenteRazon = document.getElementById('fuente_razon')?.value || 'Roboto-Regular';
        razonMarker.style.fontFamily = getCssFontFamily(fuenteRazon);

        // Calcular número de líneas después de que el navegador renderice
        setTimeout(() => {
            const lineHeightPx = fontSize * 1.3;
            const markerHeight = razonMarker.offsetHeight;
            const paddingVertical = 8; // padding top + bottom del CSS
            const textHeight = markerHeight - paddingVertical;
            const numLineas = Math.max(1, Math.round(textHeight / lineHeightPx));
            const lineasInput = document.getElementById('lineas_razon');
            if (lineasInput) lineasInput.value = numLineas;
            console.log('Líneas calculadas en canvas:', numLineas, '(altura:', markerHeight, 'lineHeight:', lineHeightPx, ')');
        }, 50);
    }
}

// Actualizar alineación visual del marcador razón
function updateRazonMarkerAlignment() {
    const razonMarker = document.getElementById('razonMarker');
    const alineacion = document.getElementById('alineacion_razon')?.value || 'justified';

    if (razonMarker) {
        // Mapear valores a CSS text-align
        const alignMap = {
            'left': 'left',
            'center': 'center',
            'right': 'right',
            'justified': 'justify'
        };
        razonMarker.style.textAlign = alignMap[alineacion] || 'justify';
        console.log('Alineación razón actualizada:', alineacion);
    }
}

// Actualizar ancho del marcador razón
function updateRazonMarkerWidth(realWidth) {
    const razonMarker = document.getElementById('razonMarker');
    if (razonMarker) {
        // El ancho_razon representa el ancho real del texto en píxeles de la imagen
        // Aplicamos escala + padding fijo del marcador
        const paddingTotal = 20; // Padding fijo del marcador (no escala)
        const scaledWidth = (realWidth * canvasScale) + paddingTotal;
        razonMarker.style.width = scaledWidth + 'px';

        // Actualizar también los inputs
        document.getElementById('ancho_razon').value = realWidth;
        const anchoInput = document.getElementById('ancho_razon_input');
        if (anchoInput) anchoInput.value = realWidth;
    }
}

// Inicializar resize del marcador razón
function initRazonResize() {
    const resizeHandle = document.getElementById('razonResizeHandle');
    const razonMarker = document.getElementById('razonMarker');
    const container = document.getElementById('canvasContainer');

    if (resizeHandle) {
        resizeHandle.addEventListener('mousedown', (e) => {
            e.stopPropagation(); // Evitar que inicie el drag
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

        // Limitar al ancho del canvas
        const containerWidth = container.offsetWidth;
        const markerLeft = razonMarker.offsetLeft;
        const maxWidth = containerWidth - markerLeft - 10;
        if (newWidth > maxWidth) {
            newWidth = maxWidth;
        }

        razonMarker.style.width = newWidth + 'px';

        // Actualizar el valor real (sin escala), compensando el padding fijo (20px)
        const paddingTotal = 20;
        const realWidth = Math.round((newWidth - paddingTotal) / canvasScale);
        const clampedWidth = Math.max(100, realWidth);
        document.getElementById('ancho_razon').value = clampedWidth;
        document.getElementById('ancho_razon_input').value = clampedWidth;

        console.log('Resize razón:', { newWidth, realWidth: clampedWidth, canvasScale });
    });

    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            document.body.style.cursor = '';
        }
    });

    // Sincronizar input con marcador
    const anchoInput = document.getElementById('ancho_razon_input');
    if (anchoInput) {
        anchoInput.addEventListener('input', () => {
            const realWidth = parseInt(anchoInput.value) || 600;
            // Compensar padding (4px izq + 16px der = 20px)
            const paddingTotal = 20;
            let scaledWidth = (realWidth * canvasScale) + paddingTotal;

            // Limitar al ancho del canvas
            const containerWidth = container.offsetWidth;
            const markerLeft = razonMarker.offsetLeft;
            const maxWidth = containerWidth - markerLeft - 10;
            if (scaledWidth > maxWidth) {
                scaledWidth = maxWidth;
            }

            razonMarker.style.width = scaledWidth + 'px';
            document.getElementById('ancho_razon').value = realWidth;
        });
    }
}

function startDrag(e) {
    // No iniciar drag si estamos en el resize handle
    if (e.target.classList.contains('resize-handle')) return;

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

    // Límites
    x = Math.max(-10, Math.min(x, containerRect.width - currentMarker.offsetWidth + 10));
    y = Math.max(-10, Math.min(y, containerRect.height - currentMarker.offsetHeight + 10));

    const type = currentMarker.dataset.type;

    currentMarker.style.left = x + 'px';
    currentMarker.style.top = y + 'px';

    // Calcular coordenadas reales basándose en el punto de anclaje
    let realX, realY;

    if (type === 'razon') {
        const paddingLeft = 6;
        const paddingTop = 4;
        realX = Math.round((x + paddingLeft) / canvasScale);
        realY = Math.round((y + paddingTop) / canvasScale);
    } else if (type === 'nombre' || type === 'fecha') {
        const paddingLeftEstimado = 2;
        realX = Math.round((x + paddingLeftEstimado) / canvasScale);
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
    // capitalize type
    const capType = type.charAt(0).toUpperCase() + type.slice(1);
    const coord = document.getElementById(`coord${capType}`);

    if (posX) posX.value = x;
    if (posY) posY.value = y;
    if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
}

function updateMarkerPositions() {
    updateCanvasScale();

    const canvas = document.getElementById('canvas');
    if (!canvas) return; // Si no hay canvas, salir

    const canvasWidth = canvas.offsetWidth;
    const canvasHeight = canvas.offsetHeight;

    // Lista de tipos de marcadores
    const types = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];

    types.forEach(type => {
        const posX = document.getElementById(`posicion_${type}_x`);
        const posY = document.getElementById(`posicion_${type}_y`);
        const marker = document.getElementById(`${type}Marker`);

        if (!marker || !posX || !posY) return;

        const x = parseInt(posX.value) || 0;
        const y = parseInt(posY.value) || 0;

        let cssLeft, cssTop;

        if (type === 'nombre' || type === 'fecha') {
            const paddingLeftEstimado = 2;
            cssLeft = (x * canvasScale) - paddingLeftEstimado;
            cssTop = y * canvasScale;
        } else if (type === 'razon') {
            const paddingLeftFijo = 6;
            const paddingTopFijo = 4;
            cssLeft = (x * canvasScale) - paddingLeftFijo;
            cssTop = (y * canvasScale) - paddingTopFijo;

            // Actualizar ancho
            const anchoRazon = parseInt(document.getElementById('ancho_razon')?.value) || 600;
            const paddingEscalado = 20;
            let markerWidth = (anchoRazon * canvasScale) + paddingEscalado;
            // Limitar ancho
            const maxWidth = canvasWidth - cssLeft - 10;
            if (markerWidth > maxWidth && maxWidth > 50) markerWidth = maxWidth;

            marker.style.width = markerWidth + 'px';
            marker.style.display = 'flex';
        } else {
            // QR, Firma, Destacado (anclaje central)
            // Para Destacado, calcular tamaño si es necesario
            let markerSize = 0;
            if (type === 'destacado') {
                const tamanioVal = parseInt(document.getElementById('tamanio_destacado')?.value) || 100;
                markerSize = Math.max(20, tamanioVal * canvasScale);
                marker.style.width = markerSize + 'px';
                marker.style.height = markerSize + 'px';
            }

            const markerWidth = type === 'destacado' ? markerSize : (marker.offsetWidth || 50);
            const markerHeight = type === 'destacado' ? markerSize : (marker.offsetHeight || 50);

            cssLeft = (x * canvasScale) - (markerWidth / 2);
            cssTop = (y * canvasScale) - (markerHeight / 2);
        }

        // Limits
        cssLeft = Math.max(-20, Math.min(cssLeft, canvasWidth + 20));
        cssTop = Math.max(-20, Math.min(cssTop, canvasHeight + 20));

        marker.style.left = cssLeft + 'px';
        marker.style.top = cssTop + 'px';

        // Actualizar coordenadas en texto
        updateCoordinatesFromMarker(type, x, y);
    });

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
        // Aplicar fuente configurada
        const fuenteNombre = document.getElementById('fuente_nombre')?.value || 'Roboto-Regular';
        nombreMarker.style.fontFamily = getCssFontFamily(fuenteNombre);
    }

    // Aplicar tamaño de fuente escalado al marcador Razón
    if (razonMarker) {
        // Usar factor 1.0 para mejor coincidencia con la previsualización real
        const fontSize = Math.max(8, tamanioRazon * canvasScale);
        razonMarker.style.fontSize = fontSize + 'px';
        razonMarker.style.lineHeight = '1.3';
        // Padding consistente con CSS: 4px arriba/abajo, 6px izquierda, 14px derecha (para handle)
        razonMarker.style.paddingTop = '4px';
        razonMarker.style.paddingBottom = '4px';
        razonMarker.style.paddingLeft = '6px';
        razonMarker.style.paddingRight = '14px'; // Espacio para handle
        // Aplicar fuente configurada
        const fuenteRazon = document.getElementById('fuente_razon')?.value || 'Roboto-Regular';
        razonMarker.style.fontFamily = getCssFontFamily(fuenteRazon);
    }

    // Aplicar tamaño de fuente escalado al marcador Fecha
    if (fechaMarker) {
        const fontSize = Math.max(8, tamanioFecha * canvasScale * 0.8);
        fechaMarker.style.fontSize = fontSize + 'px';
        const padding = Math.max(2, fontSize * 0.2);
        fechaMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
        // Aplicar fuente configurada
        const fuenteFecha = document.getElementById('fuente_fecha')?.value || 'Roboto-Regular';
        fechaMarker.style.fontFamily = getCssFontFamily(fuenteFecha);
    }

    // Aplicar tamaño al marcador de Destacado
    const tamanioDestacado = parseInt(document.getElementById('tamanio_destacado')?.value) || 100;
    const destacadoMarker = document.getElementById('destacadoMarker');
    if (destacadoMarker) {
        const destacadoSize = Math.max(20, tamanioDestacado * canvasScale);
        destacadoMarker.style.width = destacadoSize + 'px';
        destacadoMarker.style.height = destacadoSize + 'px';
        console.log('Destacado marker size:', destacadoSize, 'px (tamanio:', tamanioDestacado, ', scale:', canvasScale, ')');
    }
}

// Helper para actualizar display de sliders
function updateInputDisplay(input) {
    if (!input) return;
    const display = document.getElementById(input.id + '_val');
    if (display) {
        display.textContent = input.value + 'px';
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
        // Inicializar display
        updateInputDisplay(tamanioQr);
        tamanioQr.addEventListener('input', () => {
            updateInputDisplay(tamanioQr);
            updateMarkerSizes();
            updateMarkerPositions(); // Reposicionar después de cambiar tamaño
        });
    }
    if (tamanioFirma) {
        updateInputDisplay(tamanioFirma);
        tamanioFirma.addEventListener('input', () => {
            updateInputDisplay(tamanioFirma);
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

    // Agregar listener para cambio de tamaño de destacado
    const tamanioDestacado = document.getElementById('tamanio_destacado');
    if (tamanioDestacado) {
        updateInputDisplay(tamanioDestacado);
        tamanioDestacado.addEventListener('input', () => {
            updateInputDisplay(tamanioDestacado);
            updateMarkerSizes();
            updateMarkerPositions();
        });
    }

    // Agregar listeners para cambio de fuente
    const fuenteNombre = document.getElementById('fuente_nombre');
    if (fuenteNombre) {
        fuenteNombre.addEventListener('change', () => {
            updateMarkerSizes();
        });
    }

    const fuenteRazon = document.getElementById('fuente_razon');
    if (fuenteRazon) {
        fuenteRazon.addEventListener('change', () => {
            updateMarkerSizes();
        });
    }

    const fuenteFecha = document.getElementById('fuente_fecha');
    if (fuenteFecha) {
        fuenteFecha.addEventListener('change', () => {
            updateMarkerSizes();
        });
    }
}

// Inicializar eventos de checkboxes
function initCheckboxEvents() {
    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
            updateMarkersVisibility();
        });
    });
}

function updateMarkersVisibility() {
    const markers = {
        'nombre': document.getElementById('var_nombre'),
        'razon': document.getElementById('var_razon'),
        'fecha': document.getElementById('var_fecha'),
        'qr': document.getElementById('var_qr'),
        'firma': document.getElementById('var_firma'),
        'destacado': document.getElementById('var_destacado')
    };

    Object.keys(markers).forEach(key => {
        const checkbox = markers[key];
        const marker = document.getElementById(key + 'Marker');
        const tab = document.querySelector(`.variable-tab[data-tab="${key}"]`);

        if (checkbox && marker) {
            if (checkbox.checked) {
                marker.style.display = (key === 'razon') ? 'flex' : (key === 'nombre' || key === 'fecha' ? 'block' : 'flex');
                if (tab) tab.style.display = 'flex';
            } else {
                marker.style.display = 'none';
                if (tab) tab.style.display = 'none';
            }
        }
    });

    // Si la pestaña activa se oculta, cambiar a otra visible
    const activeTab = document.querySelector('.variable-tab.active');
    if (activeTab && activeTab.style.display === 'none') {
        const firstVisible = document.querySelector('.variable-tab:not([style*="display: none"])');
        if (firstVisible) {
            switchTab(firstVisible.dataset.tab);
        }
    }
}

// Función toggle para opciones de destacado
function toggleDestacadoOptions() {
    const tipo = document.getElementById('destacado_tipo')?.value || 'icono';
    const groupIcono = document.getElementById('destacado_icono_group');
    const groupImagen = document.getElementById('destacado_imagen_group');

    if (groupIcono) groupIcono.style.display = tipo === 'icono' ? 'block' : 'none';
    if (groupImagen) groupImagen.style.display = tipo === 'imagen' ? 'block' : 'none';

    // Actualizar previsualización en lienzo
    updateDestacadoMarkerImage();
}

// Función para actualizar la imagen del marcador de destacado
function updateDestacadoMarkerImage() {
    const tipo = document.getElementById('destacado_tipo')?.value || 'icono';
    const markerImg = document.getElementById('destacadoMarkerImg');

    if (!markerImg) return;

    if (tipo === 'imagen') {
        // Usar imagen personalizada si existe
        const currentImg = document.querySelector('#currentDestacadoImg img');
        if (currentImg && currentImg.src) {
            markerImg.src = currentImg.src;
            return;
        }
    }

    // Usar icono predeterminado
    const iconoSeleccionado = document.querySelector('input[name="destacado_icono"]:checked');
    if (iconoSeleccionado && iconoSeleccionado.value) {
        // Asumiendo que basePath y assets/stickers están configurados correctamente
        // Usamos basePath si está disponible, sino ruta relativa
        const basePathClean = (typeof basePath !== 'undefined') ? basePath : '/cce-certificados/';
        const newSrc = basePathClean + 'assets/stickers/' + iconoSeleccionado.value + '.png';
        markerImg.src = newSrc;
    }
}

// Listeners para cambio de icono destacado
document.querySelectorAll('input[name="destacado_icono"]').forEach(radio => {
    radio.addEventListener('change', updateDestacadoMarkerImage);
});

// Manejo de plantillas
let currentTemplates = [];
let activeTemplateId = null;
const MAX_TEMPLATES = 6;

// Cargar plantillas al iniciar
async function loadTemplatesSlider() {
    try {
        const response = await fetch(`../api/grupos/plantillas.php?action=list&grupo_id=${grupoId}`);
        const data = await response.json();

        if (data.success) {
            currentTemplates = data.plantillas;

            // Buscar activa
            const active = currentTemplates.find(t => t.es_activa == 1);
            if (active) {
                activeTemplateId = active.id;
            } else if (currentTemplates.length > 0) {
                // Si no hay activa pero hay plantillas, activar la primera (visualmente)
                // Opcional: activeTemplateId = currentTemplates[0].id;
            }

            await renderTemplatesSlider();
        }
    } catch (error) {
        console.error('Error cargando plantillas:', error);
    }
}

async function renderTemplatesSlider() {
    const slider = document.getElementById('templatesSlider');
    const emptyState = document.getElementById('templatesEmpty');
    const templateCount = document.getElementById('templateCount');
    const prevBtn = document.getElementById('sliderPrev');
    const nextBtn = document.getElementById('sliderNext');

    // Actualizar contador
    if (templateCount) templateCount.textContent = `${currentTemplates.length}/${MAX_TEMPLATES}`;

    // Limpiar slider (manteniendo el botón de agregar y empty state)
    const items = slider.querySelectorAll('.template-item');
    items.forEach(item => item.remove());

    if (currentTemplates.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        prevBtn.disabled = true;
        nextBtn.disabled = true;

        // Si no hay plantillas, mostrar canvas por defecto o limpiar
        const canvas = document.getElementById('canvas');
        // canvas.src = ''; // O placeholder
    } else {
        if (emptyState) emptyState.style.display = 'none';

        // Renderizar items
        currentTemplates.forEach(template => {
            const item = document.createElement('div');
            item.className = 'template-item' + (template.id == activeTemplateId ? ' active' : '');
            // Mostrar los primeros visiblemente (lógica de slider simplificada)
            item.classList.add('visible');

            // Construir ruta de imagen
            const basePathClean = (typeof basePath !== 'undefined') ? basePath : '/cce-certificados/';
            // Validar imagen fondo
            let imgPath = basePathClean + 'assets/templates/default_template.png';
            if (template.archivo) {
                imgPath = basePathClean + 'uploads/grupos/' + grupoId + '/' + template.archivo;
            }

            item.innerHTML = `
                <img src="${imgPath}" class="template-thumb" alt="Plantilla ${template.id}" onerror="this.src='${basePathClean}assets/templates/default_template.png'">
                <div class="template-info">
                    <div class="template-name">${template.nombre || 'Plantilla'}</div>
                    <div class="template-actions">
                        <button type="button" class="btn-select" onclick="selectTemplate(${template.id})" title="Usar esta plantilla">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn-delete" onclick="deleteTemplate(${template.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            // Insertar antes del botón de agregar
            const addBtn = document.getElementById('templateAddBtn');
            slider.insertBefore(item, addBtn);
        });

        // Si hay plantilla activa, cargarla en el canvas y ESPERAR
        const activeTemplate = currentTemplates.find(t => t.id == activeTemplateId);
        if (activeTemplate) {
            await loadTemplateImage(activeTemplate.archivo);
        } else if (currentTemplates.length > 0 && !activeTemplateId) {
            // Si no hay activa explícita, usar la primera
            await loadTemplateImage(currentTemplates[0].archivo);
        }

        // Habilitar/deshabilitar navegación (simplificado)
        prevBtn.disabled = false;
        nextBtn.disabled = false;
    }
}

function loadTemplateImage(filename) {
    return new Promise((resolve) => {
        const canvas = document.getElementById('canvas');
        const basePathClean = (typeof basePath !== 'undefined') ? basePath : '/cce-certificados/';

        if (filename) {
            canvas.src = basePathClean + 'uploads/grupos/' + grupoId + '/' + filename;
        } else {
            canvas.src = basePathClean + 'assets/templates/default_template.png';
        }

        canvas.onerror = function () {
            // Fallback si la imagen no existe
            if (this.src.indexOf('default_template.png') === -1) {
                this.src = basePathClean + 'assets/templates/default_template.png';
            }
        };

        canvas.onload = function () {
            updateCanvasScale();
            updateMarkerPositions();
            updateMarkersVisibility();
            resolve();
        };
    });
}

// Cargar configuración específica de una plantilla
async function loadTemplateConfig(templateId) {
    try {
        const response = await fetch(`../api/grupos/plantillas.php?action=get_config&plantilla_id=${templateId}`);
        const data = await response.json();

        if (data.success && data.config) {
            const config = data.config;

            // Aplicar configuración a los inputs
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el && val !== null && val !== undefined) el.value = val;
            };

            // Posiciones - usando valores dinámicos basados en la resolución real si es 0 (plantilla nueva)
            // Se asume que updateCanvasScale() ya se ejecutó al menos una vez al cargar la imagen,
            // por lo que imageRealWidth e imageRealHeight tienen valores. Fallback a 1600x1131 si no hay.
            const w = imageRealWidth || 1600;
            const h = imageRealHeight || 1131;

            setVal('posicion_nombre_x', parseInt(config.posicion_nombre_x) || Math.round(w / 2));
            setVal('posicion_nombre_y', parseInt(config.posicion_nombre_y) || Math.round(h * 0.45));
            setVal('posicion_razon_x', parseInt(config.posicion_razon_x) || Math.round(w / 2));
            setVal('posicion_razon_y', parseInt(config.posicion_razon_y) || Math.round(h * 0.55));
            setVal('posicion_fecha_x', parseInt(config.posicion_fecha_x) || Math.round(w / 2));
            setVal('posicion_fecha_y', parseInt(config.posicion_fecha_y) || Math.round(h * 0.70));
            setVal('posicion_qr_x', parseInt(config.posicion_qr_x) || Math.round(w * 0.85));
            setVal('posicion_qr_y', parseInt(config.posicion_qr_y) || Math.round(h * 0.85));
            setVal('posicion_firma_x', parseInt(config.posicion_firma_x) || Math.round(w / 2));
            setVal('posicion_firma_y', parseInt(config.posicion_firma_y) || Math.round(h * 0.85));
            setVal('posicion_destacado_x', parseInt(config.destacado_posicion_x) || Math.round(w * 0.15));
            setVal('posicion_destacado_y', parseInt(config.destacado_posicion_y) || Math.round(h * 0.15));

            // Cargar todas las otras configuraciones si no son null (respeta lo que esté en los inputs si son null)
            const stringFields = [
                'fuente_nombre', 'formato_nombre', 'fuente_razon', 'fuente_fecha',
                'color_texto', 'color_razon', 'color_fecha', 'razon_defecto',
                'formato_fecha', 'alineacion_razon', 'destacado_tipo', 'destacado_icono'
            ];
            const numberFields = [
                'tamanio_fuente', 'tamanio_razon', 'tamanio_fecha', 'tamanio_qr',
                'tamanio_firma', 'ancho_razon', 'lineas_razon', 'tamanio_destacado'
            ];

            stringFields.forEach(key => {
                if (config[key] !== null && config[key] !== undefined) setVal(key, config[key]);
            });

            numberFields.forEach(key => {
                if (config[key] !== null && config[key] !== undefined) setVal(key, parseInt(config[key]));
            });

            // Sincronizar UI especial (formatos, color_hex, sliders, etc.)
            const formatoNombre = document.getElementById('formato_nombre')?.value || 'mayusculas';
            setFormatoNombre(formatoNombre);

            // Colores hex
            ['texto', 'razon', 'fecha'].forEach(k => {
                const colorInput = document.getElementById(`color_${k}`);
                const hexInput = document.getElementById(`color_${k}_hex`);
                if (colorInput && hexInput) hexInput.value = colorInput.value;
            });

            // Cargar variables habilitadas (IMPORTANT FOR MARKER VISIBILITY)
            let variablesHabilitadas = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];
            if (config.variables_habilitadas) {
                try {
                    const savedVars = JSON.parse(config.variables_habilitadas);
                    if (Array.isArray(savedVars)) {
                        variablesHabilitadas = savedVars;
                        // Asegurar migración
                        if (!variablesHabilitadas.includes('fecha')) variablesHabilitadas.push('fecha');
                        if (!variablesHabilitadas.includes('destacado')) variablesHabilitadas.push('destacado');
                    }
                } catch (e) {
                    console.error('Error parsing variables_habilitadas:', e);
                }
            }

            // Marcar checkboxes
            document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                cb.checked = variablesHabilitadas.includes(cb.value);
            });

            // Actualizar vista
            const iconValue = config.destacado_icono || 'estrella';
            const iconRadio = document.querySelector(`input[name="destacado_icono"][value="${iconValue}"]`);
            if (iconRadio) iconRadio.checked = true;
            if (config.destacado_imagen) showExistingDestacadoImg(config.destacado_imagen);
            updateDestacadoMarkerImage();

            updateMarkerPositions();
            updateMarkersVisibility();
        }
    } catch (error) {
        console.error('Error cargando config de plantilla:', error);
    }
}

// Guardar configuración de la plantilla actual
async function saveTemplateConfig() {
    if (!activeTemplateId) return false;

    try {
        const form = document.getElementById('configForm');
        if (!form) return false;

        // Enviar todo el formulario como payload para la plantilla activa
        const formData = new FormData(form);
        formData.set('action', 'save_config');
        formData.set('plantilla_id', activeTemplateId);

        // Recopilar variables habilitadas y enviarlas
        const variablesHabilitadas = [];
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
            variablesHabilitadas.push(cb.value);
        });
        formData.set('variables_habilitadas', JSON.stringify(variablesHabilitadas));

        const response = await fetch('../api/grupos/plantillas.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            console.log('Configuración de plantilla guardada:', activeTemplateId);
            return true;
        } else {
            console.error('Error al guardar config plantilla:', data.message);
            return false;
        }
    } catch (error) {
        console.error('Error al guardar configuración de plantilla:', error);
        return false;
    }
}

// Seleccionar una plantilla como activa
async function selectTemplate(templateId) {
    try {
        const formData = new FormData();
        formData.append('action', 'set_active');
        formData.append('grupo_id', grupoId);
        formData.append('plantilla_id', templateId);

        const response = await fetch('../api/grupos/plantillas.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar estado local
            currentTemplates.forEach(t => {
                t.es_activa = (t.id == templateId) ? 1 : 0;
            });
            activeTemplateId = templateId;

            // Re-renderizar slider Y ESPERAR a que cargue la imagen
            await renderTemplatesSlider();

            // Cargar la configuración específica de esta plantilla
            await loadTemplateConfig(templateId);

            // Mostrar mensaje de éxito
            showNotification('Plantilla seleccionada correctamente', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al seleccionar la plantilla', 'error');
    }
}

// Agregar plantilla del sistema (por defecto)
async function addSystemTemplate() {
    try {
        const formData = new FormData();
        formData.append('action', 'add_default');
        formData.append('grupo_id', grupoId);

        const response = await fetch('../api/grupos/plantillas.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Plantilla del sistema restaurada', 'success');
            // Recargar todas las plantillas
            await loadTemplatesSlider();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al restaurar plantilla del sistema', 'error');
    }
}

// Abrir Modal de Eliminación de Plantilla
function deleteTemplate(templateId) {
    const modal = document.getElementById('deleteTemplateModal');
    const idInput = document.getElementById('deleteTemplateId');
    if (modal && idInput) {
        idInput.value = templateId;
        modal.classList.add('active');
    }
}

// Cerrar Modal de Eliminación de Plantilla
window.closeDeleteTemplateModal = function () {
    const modal = document.getElementById('deleteTemplateModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Confirmar Eliminación de Plantilla (Llamado desde el modal)
window.confirmDeleteTemplate = async function () {
    const idInput = document.getElementById('deleteTemplateId');
    const templateId = idInput ? idInput.value : null;

    if (!templateId) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('grupo_id', grupoId);
        formData.append('plantilla_id', templateId);

        const response = await fetch('../api/grupos/plantillas.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Recargar slider
            await loadTemplatesSlider();
            showNotification('Plantilla eliminada', 'success');
            closeDeleteTemplateModal();
        } else {
            showNotification('Error: ' + data.message, 'error');
            closeDeleteTemplateModal();
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al eliminar la plantilla', 'error');
        closeDeleteTemplateModal();
    }
}

// Subir nueva plantilla
async function uploadNewTemplate(file) {
    // Verificar que no se exceda el máximo
    if (currentTemplates.length >= MAX_TEMPLATES) {
        showNotification(`Máximo ${MAX_TEMPLATES} plantillas permitidas`, 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('grupo_id', grupoId);
        formData.append('plantilla', file);
        formData.append('nombre', file.name.replace(/\.[^/.]+$/, ''));

        showNotification('Subiendo plantilla...', 'info');

        const response = await fetch('../api/grupos/plantillas.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Agregar al array local
            currentTemplates.push(data.plantilla);

            // Activar la plantilla recién subida para que se muestre en el canvas
            await selectTemplate(data.plantilla.id);

            showNotification('Plantilla subida correctamente', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al subir la plantilla', 'error');
    }
}

// Mostrar notificación temporal
function showNotification(message, type = 'info') {
    // Remover notificación existente
    const existing = document.querySelector('.template-notification');
    if (existing) existing.remove();

    const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        info: '#3498db'
    };

    const notification = document.createElement('div');
    notification.className = 'template-notification';
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Auto-remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Agregar estilos de animación
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(notificationStyles);

// Manejo de archivos - Plantilla (nuevo sistema)
const plantillaInput = document.getElementById('plantillaUpload');
const templatesSlider = document.getElementById('templatesSlider');

if (plantillaInput) {
    plantillaInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            uploadNewTemplate(e.target.files[0]);
            // Limpiar input para permitir subir el mismo archivo
            e.target.value = '';
        }
    });
}

if (templatesSlider) {
    // Drag & Drop para el slider
    templatesSlider.addEventListener('dragover', (e) => {
        e.preventDefault();
        templatesSlider.style.background = '#e8f4f8';
        templatesSlider.style.borderColor = '#3498db';
    });

    templatesSlider.addEventListener('dragleave', () => {
        templatesSlider.style.background = '';
        templatesSlider.style.borderColor = '';
    });

    templatesSlider.addEventListener('drop', (e) => {
        e.preventDefault();
        templatesSlider.style.background = '';
        templatesSlider.style.borderColor = '';

        if (e.dataTransfer.files.length) {
            const file = e.dataTransfer.files[0];
            if (file.type.startsWith('image/')) {
                uploadNewTemplate(file);
            } else {
                showNotification('Por favor, arrastra una imagen válida', 'error');
            }
        }
    });
}

// Event listener para el botón inicial de agregar plantilla
const initialAddBtn = document.getElementById('templateAddBtn');
if (initialAddBtn) {
    initialAddBtn.addEventListener('click', () => {
        if (plantillaInput) plantillaInput.click();
    });
}

// Manejo de archivos - Firma
const uploadFirmaArea = document.getElementById('uploadFirmaArea');
const firmaInput = document.getElementById('firma_imagen');

if (uploadFirmaArea && firmaInput) {
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
            showFirmaPreview(e.target.files[0]);
            // Reset remove flag
            const removeInput = document.getElementById('remove_firma');
            if (removeInput) removeInput.value = '0';
        }
    });
}

function showFirmaPreview(file) {
    const container = document.getElementById('currentFirma');

    if (file && container) {
        const reader = new FileReader();
        reader.onload = function (e) {
            container.innerHTML = `
                <div class="firma-preview">
                    <img src="${e.target.result}" alt="Vista previa de firma">
                    <div class="firma-info">
                        <div class="firma-filename">${file.name}</div>
                        <small>Nueva imagen seleccionada</small>
                    </div>
                    <button type="button" class="btn-remove-firma" onclick="clearFile('firma')">
                        <i class="fas fa-trash"></i> Quitar
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

function showExistingFirma(filename) {
    if (!filename) return;
    const container = document.getElementById('currentFirma');
    // La firma se guarda en assets/firmas/
    const basePathClean = (typeof basePath !== 'undefined') ? basePath : '/cce-certificados/';
    const imagePath = `${basePathClean}assets/firmas/${filename}`;

    if (container) {
        const timestamp = new Date().getTime();
        container.innerHTML = `
            <div class="firma-preview">
                <img src="${imagePath}?t=${timestamp}" alt="Firma actual" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2250%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22100%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2212%22 fill=%22%23999%22%3ENo encontrada%3C/text%3E%3C/svg%3E'">
                <div class="firma-info">
                    <div class="firma-filename">${filename}</div>
                    <small>Imagen actual</small>
                </div>
                <button type="button" class="btn-remove-firma" onclick="clearFile('firma')">
                    <i class="fas fa-trash"></i> Quitar
                </button>
            </div>
        `;
    }
}

function showFileName(type, filename) {
    if (type === 'firma') {
        showExistingFirma(filename);
    }
}

function clearFile(type) {
    if (type === 'firma') {
        document.getElementById('firma_imagen').value = '';
        document.getElementById('currentFirma').innerHTML = '';
        // Set flag to remove on server
        const removeInput = document.getElementById('remove_firma');
        if (removeInput) removeInput.value = '1';
    } else if (type === 'destacado') {
        document.getElementById('destacado_imagen_input').value = '';
        document.getElementById('currentDestacadoImg').innerHTML = '';
        // Add similar logic for destacado if needed in future
    }
}

// Manejo de archivos - Imagen personalizada de Destacado
const uploadDestacadoArea = document.getElementById('uploadDestacadoArea');
const destacadoImagenInput = document.getElementById('destacado_imagen_input');

if (uploadDestacadoArea && destacadoImagenInput) {
    uploadDestacadoArea.addEventListener('click', () => destacadoImagenInput.click());

    uploadDestacadoArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadDestacadoArea.classList.add('dragover');
    });

    uploadDestacadoArea.addEventListener('dragleave', () => {
        uploadDestacadoArea.classList.remove('dragover');
    });

    uploadDestacadoArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadDestacadoArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            destacadoImagenInput.files = e.dataTransfer.files;
            showDestacadoPreview(e.dataTransfer.files[0]);
        }
    });

    destacadoImagenInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            showDestacadoPreview(e.target.files[0]);
        }
    });
}

function showDestacadoPreview(file) {
    const container = document.getElementById('currentDestacadoImg');

    if (file && container) {
        const reader = new FileReader();
        reader.onload = function (e) {
            container.innerHTML = `
                <div class="sticker-preview">
                    <img src="${e.target.result}" alt="Vista previa del sticker">
                    <div class="sticker-info">
                        <div class="sticker-filename">${file.name}</div>
                        <small>Nueva imagen seleccionada</small>
                    </div>
                    <button type="button" class="btn-remove-sticker" onclick="clearFile('destacado')">
                        <i class="fas fa-trash"></i> Quitar
                    </button>
                </div>
            `;
            // También actualizar el marcador en el canvas
            updateDestacadoMarkerWithCustomImage(e.target.result);
        };
        reader.readAsDataURL(file);
    }
}

function showExistingDestacadoImg(filename) {
    if (!filename) return;
    const container = document.getElementById('currentDestacadoImg');
    const basePathClean = (typeof basePath !== 'undefined') ? basePath : '/cce-certificados/';
    const imagePath = `${basePathClean}uploads/stickers/${filename}`;

    if (container) {
        container.innerHTML = `
            <div class="sticker-preview">
                <img src="${imagePath}" alt="Sticker actual">
                <div class="sticker-info">
                    <div class="sticker-filename">${filename}</div>
                    <small>Imagen actual</small>
                </div>
                <button type="button" class="btn-remove-sticker" onclick="clearFile('destacado')">
                    <i class="fas fa-trash"></i> Quitar
                </button>
            </div>
        `;
    }
    // También actualizar el marcador en el canvas
    updateDestacadoMarkerWithCustomImage(imagePath);
}

function updateDestacadoMarkerWithCustomImage(imageSrc) {
    const markerImg = document.getElementById('destacadoMarkerImg');
    if (markerImg) {
        markerImg.src = imageSrc;
    }
}

async function loadGrupoConfig() {
    try {
        const response = await fetch(`../api/grupos/config.php?action=get&id=${grupoId}`);
        const data = await response.json();

        if (data.success) {
            const grupo = data.grupo;

            // Guardar nombre del grupo para usarlo en variables
            grupoNombreActual = grupo.nombre || 'Nombre del Grupo';

            // Cargar datos del primer estudiante del grupo
            try {
                const respEstudiante = await fetch(`../api/estudiantes/index.php?action=primer_estudiante&grupo_id=${grupoId}`);
                const dataEstudiante = await respEstudiante.json();
                if (dataEstudiante.success && dataEstudiante.estudiante) {
                    primerEstudianteNombre = dataEstudiante.estudiante.nombre_completo || 'Juan Pérez García';
                    primerEstudianteCategoria = dataEstudiante.estudiante.categoria_nombre || 'Nombre de Categoría';
                    console.log('Primer estudiante cargado:', primerEstudianteNombre, primerEstudianteCategoria);
                    // Actualizar marcadores del lienzo
                    updateNombreMarkerText();
                    updateRazonMarkerText();
                }
            } catch (e) {
                console.log('No se pudo cargar el primer estudiante, usando valores por defecto');
            }

            // Actualizar header
            const grupoNombreElem = document.getElementById('grupoNombre');
            const grupoDescElem = document.getElementById('grupoDescripcion');
            const iconElem = document.getElementById('grupoIcon');

            if (grupoNombreElem) grupoNombreElem.textContent = grupo.nombre;
            if (grupoDescElem) grupoDescElem.textContent = grupo.descripcion || '';
            if (iconElem) {
                iconElem.textContent = grupo.icono || '📁';
                iconElem.style.backgroundColor = grupo.color;
            }

            // Cargar configuración - con validación de existencia
            const setValueIfExists = (id, value) => {
                const elem = document.getElementById(id);
                if (elem) elem.value = value;
            };

            setValueIfExists('razon_defecto', grupo.razon_defecto || '');
            setValueIfExists('firma_nombre', grupo.firma_nombre || '');
            setValueIfExists('firma_cargo', grupo.firma_cargo || '');
            setValueIfExists('fuente_nombre', grupo.fuente_nombre || 'Roboto-Regular');

            // Restaurar formato del nombre
            const formatoNombre = grupo.formato_nombre || 'mayusculas';
            setValueIfExists('formato_nombre', formatoNombre);
            setFormatoNombre(formatoNombre);

            setValueIfExists('tamanio_fuente', grupo.tamanio_fuente || 48);
            setValueIfExists('color_texto', grupo.color_texto || '#000000');
            setValueIfExists('color_texto_hex', grupo.color_texto || '#000000');
            setValueIfExists('posicion_nombre_x', grupo.posicion_nombre_x || 400);
            setValueIfExists('posicion_nombre_y', grupo.posicion_nombre_y || 300);
            setValueIfExists('posicion_razon_x', grupo.posicion_razon_x || 400);
            setValueIfExists('posicion_razon_y', grupo.posicion_razon_y || 360);
            setValueIfExists('posicion_qr_x', grupo.posicion_qr_x || 920);
            setValueIfExists('posicion_qr_y', grupo.posicion_qr_y || 419);
            setValueIfExists('posicion_firma_x', grupo.posicion_firma_x || 800);
            setValueIfExists('posicion_firma_y', grupo.posicion_firma_y || 850);
            setValueIfExists('posicion_fecha_x', grupo.posicion_fecha_x || 400);
            setValueIfExists('posicion_fecha_y', grupo.posicion_fecha_y || 420);
            setValueIfExists('tamanio_qr', grupo.tamanio_qr || 200);
            setValueIfExists('tamanio_firma', grupo.tamanio_firma || 150);

            // Configuración de Destacado (Fixed: Now loading correctly)
            setValueIfExists('posicion_destacado_x', grupo.posicion_destacado_x || 50);
            setValueIfExists('posicion_destacado_y', grupo.posicion_destacado_y || 50);
            setValueIfExists('tamanio_destacado', grupo.tamanio_destacado || 100);
            setValueIfExists('destacado_tipo', grupo.destacado_tipo || 'icono');

            // Set radio button for icon
            const iconValue = grupo.destacado_icono || 'estrella';
            const iconRadio = document.querySelector(`input[name="destacado_icono"][value="${iconValue}"]`);
            if (iconRadio) iconRadio.checked = true;

            // Load custom image if exists
            if (grupo.destacado_imagen) {
                showExistingDestacadoImg(grupo.destacado_imagen);
            }

            // Update marker appearance
            updateDestacadoMarkerImage();

            // Configuración de Razón
            setValueIfExists('fuente_razon', grupo.fuente_razon || 'Roboto-Regular');
            setValueIfExists('tamanio_razon', grupo.tamanio_razon || 24);
            setValueIfExists('color_razon', grupo.color_razon || '#333333');
            setValueIfExists('color_razon_hex', grupo.color_razon || '#333333');
            setValueIfExists('ancho_razon', grupo.ancho_razon || 600);
            setValueIfExists('ancho_razon_input', grupo.ancho_razon || 600);
            setValueIfExists('lineas_razon', grupo.lineas_razon || 0);
            setValueIfExists('alineacion_razon', grupo.alineacion_razon || 'justified');

            // Actualizar alineación
            const alineacionBtn = document.querySelector(`.alineacion-btn[data-align="${grupo.alineacion_razon || 'justified'}"]`);
            if (alineacionBtn) {
                document.querySelectorAll('.alineacion-btn').forEach(b => b.classList.remove('active'));
                alineacionBtn.classList.add('active');
            }
            updateRazonMarkerAlignment();

            // Configuración de Fecha
            setValueIfExists('fuente_fecha', grupo.fuente_fecha || 'Roboto-Regular');
            setValueIfExists('formato_fecha', grupo.formato_fecha || 'd de F de Y');
            setValueIfExists('tamanio_fecha', grupo.tamanio_fecha || 20);
            setValueIfExists('color_fecha', grupo.color_fecha || '#333333');
            setValueIfExists('color_fecha_hex', grupo.color_fecha || '#333333');

            // Configuración de fecha específica
            const usarFechaEsp = document.getElementById('usar_fecha_especifica');
            const fechaEspGroup = document.getElementById('fecha_especifica_group');
            if (usarFechaEsp) {
                usarFechaEsp.checked = grupo.usar_fecha_especifica == 1;
                if (fechaEspGroup) {
                    fechaEspGroup.style.display = usarFechaEsp.checked ? 'block' : 'none';
                }
            }
            setValueIfExists('fecha_especifica', grupo.fecha_especifica || '');

            // Cargar variables habilitadas
            let variablesHabilitadas = ['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'];
            if (grupo.variables_habilitadas) {
                try {
                    const savedVars = JSON.parse(grupo.variables_habilitadas);
                    if (Array.isArray(savedVars)) {
                        variablesHabilitadas = savedVars;
                        // Asegurar que fecha y destacado estén habilitados por defecto (migración)
                        if (!variablesHabilitadas.includes('fecha')) variablesHabilitadas.push('fecha');
                        if (!variablesHabilitadas.includes('destacado')) variablesHabilitadas.push('destacado');
                    }
                } catch (e) {
                    console.error('Error parsing variables_habilitadas:', e);
                }
            }

            // Marcar checkboxes
            document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                cb.checked = variablesHabilitadas.includes(cb.value);
            });

            // Inicializar eventos de checkboxes
            initCheckboxEvents();

            // Actualizar posiciones de marcadores
            updateMarkerPositions();

            // Actualizar visibilidad de marcadores
            updateMarkersVisibility();

            // Actualizar texto del marcador de razón
            updateRazonMarkerText();

            // Actualizar ancho del marcador de razón
            const anchoRazonValue = grupo.ancho_razon || 600;
            updateRazonMarkerWidth(anchoRazonValue);

            // Cargar slider de plantillas
            await loadTemplatesSlider();

            // Mostrar imagen de firma si existe
            if (grupo.firma_imagen) {
                showExistingFirma(grupo.firma_imagen);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al cargar la configuración del grupo', 'error');
    }
}

function resetDefaults() {
    if (confirm('¿Restablecer a valores predeterminados?')) {
        const setValueIfExists = (id, value) => {
            const elem = document.getElementById(id);
            if (elem) elem.value = value;
        };

        setValueIfExists('fuente_nombre', 'Roboto-Regular');
        setValueIfExists('formato_nombre', 'mayusculas');
        setFormatoNombre('mayusculas');
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

        // Configuración de Destacado
        setValueIfExists('tamanio_destacado', 100);
        setValueIfExists('destacado_tipo', 'icono');
        const iconoEstrella = document.querySelector('input[name="destacado_icono"][value="estrella"]');
        if (iconoEstrella) iconoEstrella.checked = true;
        updateDestacadoMarkerImage();

        // Configuración de Razón
        setValueIfExists('fuente_razon', 'Roboto-Regular');
        setValueIfExists('tamanio_razon', 24);
        setValueIfExists('color_razon', '#333333');
        setValueIfExists('color_razon_hex', '#333333');
        setValueIfExists('ancho_razon', 600);
        setValueIfExists('ancho_razon_input', 600);

        // Configuración de Fecha
        setValueIfExists('fuente_fecha', 'Roboto-Regular');
        setValueIfExists('formato_fecha', 'd de F de Y');
        setValueIfExists('tamanio_fecha', 20);
        setValueIfExists('color_fecha', '#333333');
        setValueIfExists('color_fecha_hex', '#333333');

        // Restablecer fecha específica
        const usarFechaEsp = document.getElementById('usar_fecha_especifica');
        const fechaEspGroup = document.getElementById('fecha_especifica_group');
        if (usarFechaEsp) usarFechaEsp.checked = false;
        if (fechaEspGroup) fechaEspGroup.style.display = 'none';
        setValueIfExists('fecha_especifica', '');

        // Restablecer checkboxes
        document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
            cb.checked = true;
        });

        updateMarkerPositions();
        updateMarkersVisibility();
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    // Sincronizar color picker con hex input
    document.getElementById('color_texto')?.addEventListener('input', (e) => {
        const hex = document.getElementById('color_texto_hex');
        if (hex) hex.value = e.target.value;
    });

    document.getElementById('color_texto_hex')?.addEventListener('input', (e) => {
        if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
            const pk = document.getElementById('color_texto');
            if (pk) pk.value = e.target.value;
        }
    });

    // Config Form Submit
    document.getElementById('configForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Recopilar variables habilitadas
        const variablesHabilitadas = [];
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
            variablesHabilitadas.push(cb.value);
        });
        document.getElementById('variables_habilitadas').value = JSON.stringify(variablesHabilitadas);

        const formData = new FormData(e.target);
        formData.append('action', 'update_config');

        try {
            // Guardar configuración del grupo
            const response = await fetch('../api/grupos/config.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // También guardar la configuración de la plantilla activa
                if (activeTemplateId) {
                    await saveTemplateConfig();
                }

                showNotification('Configuración guardada correctamente', 'success');
                loadGrupoConfig();
            } else {
                showNotification('Error: ' + (data.message || 'No se pudo guardar la configuración'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error al guardar la configuración', 'error');
        }
    });

    // Cargar configuración al iniciar
    if (grupoId) {
        loadGrupoConfig();
    }

    // Inicializar drag & drop cuando se cargue todo
    const canvas = document.getElementById('canvas');
    if (canvas) {
        if (canvas.complete) {
            updateMarkerPositions();
            initDragDrop();
            initRazonResize();
            updateMarkersVisibility();
            initCheckboxEvents();
            initSizeChangeEvents();
        } else {
            canvas.onload = () => {
                updateMarkerPositions();
                initDragDrop();
                initRazonResize();
                updateMarkersVisibility();
                initCheckboxEvents();
                initSizeChangeEvents();
            };
        }
    }

    // Toggle fecha específica
    const checkboxFecha = document.getElementById('usar_fecha_especifica');
    if (checkboxFecha) {
        checkboxFecha.addEventListener('change', (e) => {
            const group = document.getElementById('fecha_especifica_group');
            if (group) group.style.display = e.target.checked ? 'block' : 'none';
            updateRazonMarkerText(); // Actualizar preview
        });
    }

    // Inicializar flag de eliminación de firma
    const form = document.getElementById('configForm');
    if (form) {
        const inputRemove = document.createElement('input');
        inputRemove.type = 'hidden';
        inputRemove.name = 'remove_firma';
        inputRemove.id = 'remove_firma';
        inputRemove.value = '0';
        form.appendChild(inputRemove);

        // Interceptar envío para debug
        form.addEventListener('submit', (e) => {
            console.log('Enviando formulario, remove_firma:', document.getElementById('remove_firma').value);
        });
    }
});

// Función auxiliar para formatear fecha en JS similar a PHP
function formatearFechaJS(dateString, format) {
    if (!dateString) return '';

    const d = new Date(dateString + 'T12:00:00'); // Mediodía para evitar problemas de zona horaria

    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const mesesShort = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    const day = d.getDate();
    const monthIndex = d.getMonth();
    const year = d.getFullYear();

    let result = format;

    result = result.replace('d', day.toString().padStart(2, '0'));
    result = result.replace('j', day);
    result = result.replace('F', meses[monthIndex]);
    result = result.replace('M', mesesShort[monthIndex]);
    result = result.replace('m', (monthIndex + 1).toString().padStart(2, '0'));
    result = result.replace('n', monthIndex + 1);
    result = result.replace('Y', year);
    result = result.replace('y', year.toString().substr(-2));

    return result;
}

// Alineación
window.setAlineacionRazon = function (align) {
    const input = document.getElementById('alineacion_razon');
    if (input) input.value = align;

    document.querySelectorAll('.alineacion-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.align === align) btn.classList.add('active');
    });

    updateRazonMarkerAlignment();
}

// Preview Modal
window.previewCertificate = async function () {
    const modal = document.getElementById('previewModal');
    const body = document.getElementById('previewBody');

    if (!modal || !body) return;

    modal.classList.add('active');
    body.innerHTML = `
        <div class="preview-loading">
            <i class="fas fa-spinner"></i>
            <p>Generando vista previa...</p>
        </div>
    `;

    try {
        const formData = new FormData();
        formData.append('tipo', 'grupo');
        formData.append('id', grupoId);
        formData.append('use_form_data', '1');

        // Posiciones
        const getVal = (id) => document.getElementById(id)?.value;
        formData.append('posicion_nombre_x', getVal('posicion_nombre_x') || 400);
        formData.append('posicion_nombre_y', getVal('posicion_nombre_y') || 300);
        formData.append('posicion_razon_x', getVal('posicion_razon_x') || 400);
        formData.append('posicion_razon_y', getVal('posicion_razon_y') || 360);
        formData.append('posicion_fecha_x', getVal('posicion_fecha_x') || 400);
        formData.append('posicion_fecha_y', getVal('posicion_fecha_y') || 420);
        formData.append('posicion_qr_x', getVal('posicion_qr_x') || 920);
        formData.append('posicion_qr_y', getVal('posicion_qr_y') || 419);
        formData.append('posicion_firma_x', getVal('posicion_firma_x') || 800);
        formData.append('posicion_firma_y', getVal('posicion_firma_y') || 850);
        formData.append('posicion_destacado_x', getVal('posicion_destacado_x') || 50);
        formData.append('posicion_destacado_y', getVal('posicion_destacado_y') || 50);

        // Config texto
        formData.append('fuente_nombre', getVal('fuente_nombre') || 'Roboto-Regular');
        formData.append('formato_nombre', getVal('formato_nombre') || 'mayusculas');
        formData.append('tamanio_fuente', getVal('tamanio_fuente') || 48);
        formData.append('color_texto', getVal('color_texto') || '#000000');
        formData.append('tamanio_qr', getVal('tamanio_qr') || 200);
        formData.append('tamanio_firma', getVal('tamanio_firma') || 150);

        // Firma (Imagen)
        const firmaInput = document.getElementById('firma_imagen');
        if (firmaInput && firmaInput.files.length > 0) {
            formData.append('firma_imagen_file', firmaInput.files[0]);
        } else {
            // Si hay una imagen ya cargada (existente), enviar su nombre
            const currentFirmaImg = document.querySelector('#currentFirma img');
            if (currentFirmaImg && currentFirmaImg.src) {
                // Extraer nombre de archivo si es una imagen del servidor
                const src = currentFirmaImg.src;
                if (!src.startsWith('data:')) {
                    const filename = src.split('/').pop();
                    formData.append('firma_imagen', filename);
                }
            }
        }

        // Razón
        formData.append('razon_defecto', getVal('razon_defecto') || '');
        formData.append('fuente_razon', getVal('fuente_razon') || 'Roboto-Regular');
        formData.append('tamanio_razon', getVal('tamanio_razon') || 24);
        formData.append('color_razon', getVal('color_razon') || '#333333');
        formData.append('ancho_razon', getVal('ancho_razon') || 600);
        formData.append('lineas_razon', getVal('lineas_razon') || 0);
        formData.append('alineacion_razon', getVal('alineacion_razon') || 'justified');

        // Fecha
        const formatoFechaValue = getVal('formato_fecha') || 'd de F de Y';
        formData.append('formato_fecha', formatoFechaValue);
        formData.append('fuente_fecha', getVal('fuente_fecha') || 'Roboto-Regular');
        formData.append('tamanio_fecha', getVal('tamanio_fecha') || 20);
        formData.append('color_fecha', getVal('color_fecha') || '#333333');

        // Destacado
        formData.append('tamanio_destacado', getVal('tamanio_destacado') || 100);
        formData.append('destacado_tipo', getVal('destacado_tipo') || 'icono');
        const iconoDestacado = document.querySelector('input[name="destacado_icono"]:checked');
        formData.append('destacado_icono', iconoDestacado?.value || 'estrella');

        // Imagen personalizado para preview
        const destacadoTipo = getVal('destacado_tipo') || 'icono';
        if (destacadoTipo === 'imagen') {
            const destacadoImagenInput = document.getElementById('destacado_imagen_input');
            if (destacadoImagenInput && destacadoImagenInput.files.length > 0) {
                formData.append('destacado_imagen_file', destacadoImagenInput.files[0]);
            } else {
                const currentImg = document.querySelector('#currentDestacadoImg img');
                if (currentImg && currentImg.src) {
                    const filename = currentImg.src.split('/').pop();
                    if (filename && !filename.startsWith('data:')) {
                        formData.append('destacado_imagen', filename);
                    }
                }
            }
        }

        // Variables habilitadas
        const variablesHabilitadas = [];
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
            variablesHabilitadas.push(cb.value);
        });
        formData.append('variables_habilitadas', JSON.stringify(variablesHabilitadas));

        const response = await fetch('../api/preview/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && (data.preview_url || data.preview_data_url)) {
            const finalUrl = data.preview_url || data.preview_data_url;
            body.innerHTML = `
                <img src="${finalUrl}" alt="Vista previa del certificado" style="max-width: 100%;">
                <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Esta es una vista previa con datos de ejemplo
                </p>
                <div style="margin-top: 15px;">
                     <button class="btn" onclick="closePreviewModal()">Cerrar</button>
                </div>
            `;
        } else {
            body.innerHTML = `
                <div style="padding: 40px; color: #e74c3c;">
                    <i class="fas fa-exclamation-circle" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p>Error al generar la vista previa: ${data.message || data.error || 'Error desconocido'}</p>
                    <button class="btn" onclick="closePreviewModal()">Cerrar</button>
                </div>
            `;
        }
    } catch (e) {
        console.error(e);
        body.innerHTML = `
            <div style="padding: 40px; color: #e74c3c;">
                <p>Error de conexión al generar la vista previa</p>
                <button class="btn" onclick="closePreviewModal()">Cerrar</button>
            </div>
        `;
    }
}

window.closePreviewModal = function () {
    const modal = document.getElementById('previewModal');
    if (modal) modal.classList.remove('active');
}

// Force update on load to ensure fonts are applied
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        console.log('Forcing marker size and font update...');
        if (typeof initDragDrop === 'function') initDragDrop();
        if (typeof updateMarkerSizes === 'function') updateMarkerSizes();
        if (typeof updateMarkerPositions === 'function') updateMarkerPositions();
    }, 500);
});

// Debug: Log font map on load
console.log('Window Font Map:', window.fontMap);
if (window.fontMap) console.log('Font Map Keys:', Object.keys(window.fontMap));
