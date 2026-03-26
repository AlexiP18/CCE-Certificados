// Estado global
let fonts = [];
let currentFilter = 'all';

// Variables globales necesarias (definidas en la vista):
// const basePath = '...';

// Cargar fuentes al iniciar
document.addEventListener('DOMContentLoaded', function () {
    loadFonts();
    setupDragDrop();
    setupEventListeners();
});

// Cargar lista de fuentes
async function loadFonts() {
    try {
        const response = await fetch('../api/fuentes/index.php?action=list&solo_activas=0');
        const data = await response.json();

        if (data.success) {
            fonts = data.fuentes;

            // Debug: mostrar fuentes cargadas
            console.log('Fuentes cargadas:', fonts.length);
            fonts.forEach(f => console.log(`- ${f.nombre}: ${f.archivo}`));

            await loadFontStyles();
            renderFonts();
        } else {
            showNotification(data.message || 'Error al cargar fuentes', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al cargar fuentes', 'error');
    }
}

// Cargar estilos CSS de las fuentes
async function loadFontStyles() {
    let styleElement = document.getElementById('font-styles');
    if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = 'font-styles';
        document.head.appendChild(styleElement);
    }

    // Usar basePath definido globalmente o fallback a la lógica anterior si no existe
    const projectBasePath = typeof basePath !== 'undefined'
        ? basePath
        : window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

    // Si basePath es relativo (./ o ../), necesitamos resolverlo o asumirlo relativo al HTML
    // Para simplificar, asumimos que si viene de PHP es correcto, sino calculamos.
    // La lógica original asumía estructura: /public/admin/fuentes.php -> /assets/fonts/

    // Ajuste para rutas relativas o absolutas:
    // Si estamos en /public/, los assets suelen estar en ../assets/ o en /assets/ si el root apunta a public
    // La lógica original: fontsPath = basePath.replace('/public', '') + '/assets/fonts/';

    let fontsPath;
    if (typeof basePath !== 'undefined') {
        // Si basePath es "..", entonces estamos en public/admin/ -> public/
        // Queremos ir a assets/fonts/ que esta en root/assets/fonts/
        // ../../assets/fonts/ funciona si ejecutamos desde public/admin/

        if (basePath === '..') {
            fontsPath = '../../assets/fonts/';
        } else if (basePath.includes('/public')) {
            fontsPath = basePath.replace('/public', '') + '/assets/fonts/';
        } else {
            fontsPath = basePath + '/../assets/fonts/';
        }
    } else {
        fontsPath = '../../assets/fonts/';
    }

    let css = '';
    let googleFonts = [];

    fonts.forEach(font => {
        // Verificar si es una fuente de Google
        if (font.archivo && font.archivo.startsWith('google:')) {
            const fontName = font.archivo.replace('google:', '');
            googleFonts.push(fontName);
            // Crear clase CSS para aplicar la fuente de Google
            css += `.preview-font-${font.id} { font-family: "${font.nombre}", Arial, sans-serif !important; }\n`;
        } else {
            // Fuente local
            let format = 'truetype';
            switch (font.tipo.toLowerCase()) {
                case 'ttf': format = 'truetype'; break;
                case 'otf': format = 'opentype'; break;
                case 'woff': format = 'woff'; break;
                case 'woff2': format = 'woff2'; break;
            }

            const fontFamilyName = `CustomFont${font.id}`;

            css += `
@font-face { 
    font-family: "${fontFamilyName}"; 
    src: url("${fontsPath}${font.archivo}") format("${format}");
    font-display: swap;
    font-weight: normal;
    font-style: normal;
}
`;
            css += `.preview-font-${font.id} { font-family: "${fontFamilyName}", Arial, sans-serif !important; }\n`;
        }
    });

    // Cargar Google Fonts si hay alguna
    if (googleFonts.length > 0) {
        const googleLink = document.getElementById('google-fonts-link');
        if (googleLink) googleLink.remove();

        const link = document.createElement('link');
        link.id = 'google-fonts-link';
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + googleFonts.join('&family=') + '&display=swap';
        document.head.appendChild(link);
    }

    styleElement.textContent = css;

    // Esperar a que el navegador procese los estilos
    await new Promise(resolve => setTimeout(resolve, 100));

    // Intentar precargar las fuentes
    if (document.fonts && document.fonts.ready) {
        await document.fonts.ready;
    }
}

// Renderizar cuadrícula de fuentes
function renderFonts() {
    const container = document.getElementById('fonts-container');

    // Filtrar fuentes
    let filtered = currentFilter === 'all'
        ? fonts
        : fonts.filter(f => f.categoria === currentFilter);

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-font"></i>
                <h5>No hay fuentes ${currentFilter !== 'all' ? 'en esta categoría' : 'disponibles'}</h5>
                <p>Sube una nueva fuente para comenzar</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="font-grid">
            ${filtered.map(font => renderFontCard(font)).join('')}
        </div>
    `;

    // Dar tiempo para que las fuentes carguen y re-renderizar
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => {
            // Forzar un repaint de los elementos de preview
            document.querySelectorAll('.font-preview-text').forEach(el => {
                el.style.opacity = '0.99';
                setTimeout(() => { el.style.opacity = '1'; }, 10);
            });
        });
    }
}

// Renderizar tarjeta de fuente individual
function renderFontCard(font) {
    const categoryLabels = {
        'sans-serif': 'Sans Serif',
        'serif': 'Serif',
        'display': 'Display',
        'handwriting': 'Manuscrita',
        'monospace': 'Monoespaciada'
    };

    // Detectar si es fuente de Google (ya sea por el archivo original o por el nombre del archivo descargado)
    const isGoogleFont = font.archivo && (font.archivo.startsWith('google:') || font.archivo.startsWith('google_'));

    let badge;
    if (isGoogleFont) {
        badge = '<span class="badge-google"><i class="fab fa-google"></i> Google</span>';
    } else if (font.es_sistema == 1) {
        badge = '<span class="badge-system">Sistema</span>';
    } else {
        badge = '<span class="badge-custom">Personalizada</span>';
    }

    const inactiveBadge = font.activo != 1
        ? '<span class="badge-inactive">Inactiva</span>'
        : '';

    // Solo permitir eliminar fuentes personalizadas (no Google ni sistema)
    const deleteBtn = (font.es_sistema != 1 && !isGoogleFont)
        ? `<button class="btn btn-delete" onclick="openDeleteModal(${font.id}, '${font.nombre.replace(/'/g, "\\'")}')">
               <i class="fas fa-trash-alt"></i>
           </button>`
        : '';

    // Tipo de fuente para mostrar
    const tipoDisplay = isGoogleFont ? 'Web Font' : font.tipo.toUpperCase();

    return `
        <div class="font-card ${font.activo != 1 ? 'opacity-50' : ''}" data-category="${font.categoria}" data-font-id="${font.id}">
            <div class="font-preview">
                <span class="font-preview-text preview-font-${font.id}">
                    Aa Bb Cc 123
                </span>
            </div>
            <div class="font-info">
                <div class="font-name">
                    ${escapeHtml(font.nombre)} ${badge} ${inactiveBadge}
                </div>
                <div class="font-meta">
                    <span><i class="fas fa-tag"></i> ${categoryLabels[font.categoria] || font.categoria}</span>
                    <span><i class="fas fa-${isGoogleFont ? 'cloud' : 'file'}"></i> ${tipoDisplay}</span>
                </div>
                <div class="font-actions">
                    <button class="btn btn-edit" onclick="openEditModal(${font.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    ${deleteBtn}
                </div>
            </div>
        </div>
    `;
}

// Función para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Configurar drag & drop
function setupDragDrop() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const dropZoneText = document.getElementById('dropZoneText');

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');

        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFileDisplay(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            updateFileDisplay(fileInput.files[0]);
        }
    });

    function updateFileDisplay(file) {
        dropZoneText.textContent = file.name;

        // Sugerir nombre basado en el archivo
        const fontName = document.getElementById('fontName');
        if (!fontName.value) {
            const name = file.name.replace(/\.[^/.]+$/, '')
                .replace(/[-_]/g, ' ')
                .replace(/([a-z])([A-Z])/g, '$1 $2');
            fontName.value = name;
        }
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros de categoría
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.category;
            renderFonts();
        });
    });

    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

// Funciones de modal
function openUploadModal() {
    document.getElementById('uploadForm').reset();
    document.getElementById('dropZoneText').textContent = 'Arrastra un archivo aquí o haz clic para seleccionar';
    document.getElementById('uploadModal').classList.add('active');
}

function openEditModal(fontId) {
    const font = fonts.find(f => f.id == fontId);
    if (!font) return;

    document.getElementById('editFontId').value = font.id;
    document.getElementById('editFontName').value = font.nombre;
    document.getElementById('editFontCategory').value = font.categoria;
    document.getElementById('editFontActive').checked = font.activo == 1;
    document.getElementById('editFontActive').disabled = font.es_sistema == 1;

    document.getElementById('editModal').classList.add('active');
}

function openDeleteModal(fontId, fontName) {
    document.getElementById('deleteFontId').value = fontId;
    document.getElementById('deleteFontName').textContent = fontName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Subir fuente
async function uploadFont() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);
    formData.append('action', 'upload');

    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files.length) {
        showNotification('Por favor selecciona un archivo de fuente', 'error');
        return;
    }

    const fontName = document.getElementById('fontName').value.trim();
    if (!fontName) {
        showNotification('Por favor ingresa un nombre para la fuente', 'error');
        return;
    }

    const btn = document.getElementById('btnUpload');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';

    try {
        const response = await fetch('../api/fuentes/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('uploadModal');
            loadFonts();
        } else {
            showNotification(data.message || 'Error al subir la fuente', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Guardar edición
async function saveEdit() {
    const formData = new FormData(document.getElementById('editForm'));
    formData.append('action', 'update');
    formData.append('activo', document.getElementById('editFontActive').checked ? '1' : '0');

    const btn = document.getElementById('btnSaveEdit');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        const response = await fetch('../api/fuentes/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('editModal');
            loadFonts();
        } else {
            showNotification(data.message || 'Error al actualizar la fuente', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Eliminar fuente
async function deleteFont() {
    const fontId = document.getElementById('deleteFontId').value;

    const btn = document.getElementById('btnConfirmDelete');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', fontId);

        const response = await fetch('../api/fuentes/index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('deleteModal');
            loadFonts();
        } else {
            showNotification(data.message || 'Error al eliminar la fuente', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Mostrar notificación
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
