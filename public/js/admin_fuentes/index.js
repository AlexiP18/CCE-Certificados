// Estado global
let fonts = [];
let currentFilter = 'all';
let currentSearchTerm = '';
let uploadSource = 'local';
const defaultGoogleFamilies = [
    'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Nunito', 'Inter',
    'Playfair Display', 'Merriweather', 'Lora', 'Libre Baskerville', 'PT Serif',
    'Oswald', 'Bebas Neue', 'Abril Fatface', 'Cinzel', 'Righteous', 'Anton',
    'Dancing Script', 'Pacifico', 'Great Vibes', 'Sacramento', 'Satisfy', 'Allura',
    'Caveat', 'Kaushan Script', 'Roboto Mono', 'Source Code Pro', 'Fira Code',
    'Inconsolata', 'Raleway', 'Work Sans', 'DM Sans', 'Quicksand', 'Manrope',
    'M PLUS Rounded 1c', 'Archivo', 'Barlow', 'Muli', 'Noto Sans', 'Noto Serif'
];
const categoryLabels = {
    'all': 'Todas las categorías',
    'sans-serif': 'Sans Serif',
    'serif': 'Serif',
    'display': 'Display',
    'handwriting': 'Manuscritas',
    'monospace': 'Monoespaciadas'
};

// Variables globales necesarias (definidas en la vista):
// const basePath = '...';

// Cargar fuentes al iniciar
document.addEventListener('DOMContentLoaded', function () {
    buildGoogleFontAutocomplete();
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

            buildGoogleFontAutocomplete();
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
    const normalizedSearch = currentSearchTerm.trim().toLowerCase();

    // Filtrar fuentes
    let filtered = currentFilter === 'all'
        ? fonts
        : fonts.filter(f => f.categoria === currentFilter);

    if (normalizedSearch !== '') {
        filtered = filtered.filter(f => String(f.nombre || '').toLowerCase().includes(normalizedSearch));
    }

    updateHeaderStats(filtered.length);

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-font"></i>
                <h5>No se encontraron fuentes</h5>
                <p>Intenta con otro filtro o cambia el texto de búsqueda.</p>
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

function updateHeaderStats(totalFiltered) {
    const totalEl = document.getElementById('fontsCountBubble');
    if (totalEl) totalEl.textContent = String(totalFiltered);
}

// Renderizar tarjeta de fuente individual
function renderFontCard(font) {
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
    const canDelete = (font.es_sistema != 1 && !isGoogleFont);
    const deleteBtn = canDelete
        ? `<button class="btn btn-delete" onclick="openDeleteModal(${font.id}, '${font.nombre.replace(/'/g, "\\'")}')">
               <i class="fas fa-trash-alt"></i> Eliminar
           </button>`
        : `<button class="btn btn-delete btn-disabled" disabled title="Esta fuente no se puede eliminar">
               <i class="fas fa-lock"></i> No disponible
           </button>`;

    // Tipo de fuente para mostrar
    const tipoDisplay = isGoogleFont ? 'Web Font' : font.tipo.toUpperCase();

    return `
        <article class="font-card ${font.activo != 1 ? 'font-card-inactive' : ''}" data-category="${font.categoria}" data-font-id="${font.id}">
            <header class="font-card-header">
                <div class="font-preview">
                    <span class="font-preview-text preview-font-${font.id}">
                        Aa Bb Cc 123
                    </span>
                </div>
            </header>
            <div class="font-card-body">
                <div class="font-name-row">
                    <h4 class="font-name-title">${escapeHtml(font.nombre)}</h4>
                    <div class="font-badges">
                        ${badge}
                        ${inactiveBadge}
                    </div>
                </div>
                <div class="font-meta">
                    <span><i class="fas fa-tag"></i> ${categoryLabels[font.categoria] || font.categoria}</span>
                    <span><i class="fas fa-${isGoogleFont ? 'cloud' : 'file'}"></i> ${tipoDisplay}</span>
                </div>
                <div class="font-tags">
                    <span class="font-tag"><i class="fas fa-a"></i> Familia tipográfica</span>
                    <span class="font-tag"><i class="fas fa-palette"></i> ${categoryLabels[font.categoria] || font.categoria}</span>
                    <span class="font-tag"><i class="fas fa-check-circle"></i> ${font.activo == 1 ? 'Activa' : 'Inactiva'}</span>
                </div>
            </div>
            <footer class="font-card-footer">
                <div class="font-actions">
                    <button class="btn btn-edit" onclick="openEditModal(${font.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    ${deleteBtn}
                </div>
            </footer>
        </article>
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

function extractGoogleFamilyFromRecord(font) {
    if (!font) return '';
    if (font.archivo && font.archivo.startsWith('google:')) {
        return font.archivo.replace('google:', '').replace(/\+/g, ' ').trim();
    }
    if (font.nombre && String(font.nombre).trim() !== '') {
        return String(font.nombre).trim();
    }
    return '';
}

function buildGoogleFontAutocomplete() {
    const datalist = document.getElementById('googleFontFamilySuggestions');
    if (!datalist) return;

    const fromRecords = fonts
        .filter((font) => font.archivo && (font.archivo.startsWith('google:') || font.archivo.startsWith('google_')))
        .map((font) => extractGoogleFamilyFromRecord(font))
        .filter(Boolean);

    const merged = [...defaultGoogleFamilies, ...fromRecords];
    const normalizedSet = new Map();

    merged.forEach((name) => {
        const clean = String(name).replace(/\s+/g, ' ').trim();
        if (!clean) return;
        normalizedSet.set(clean.toLowerCase(), clean);
    });

    datalist.innerHTML = Array.from(normalizedSet.values())
        .sort((a, b) => a.localeCompare(b, 'es'))
        .map((family) => `<option value="${escapeHtml(family)}"></option>`)
        .join('');
}

function normalizeGoogleFamily(rawValue) {
    const raw = String(rawValue || '').trim();
    if (!raw) return '';

    let candidate = raw;

    if (raw.includes('fonts.google.com') && raw.includes('/specimen/')) {
        const specimenPart = raw.split('/specimen/')[1] || '';
        candidate = specimenPart.split(/[?#]/)[0] || '';
    } else if (raw.includes('family=')) {
        const familyPart = raw.split('family=')[1] || '';
        candidate = familyPart.split('&')[0] || '';
        candidate = candidate.split(':')[0] || candidate;
    }

    try {
        candidate = decodeURIComponent(candidate);
    } catch (error) {
        // Mantener valor original si no viene correctamente encoded
    }
    candidate = candidate.replace(/\+/g, ' ');
    candidate = candidate.replace(/\s+/g, ' ').trim();
    return candidate;
}

function setUploadSource(source) {
    uploadSource = source === 'google' ? 'google' : 'local';

    const localFields = document.getElementById('localUploadFields');
    const googleFields = document.getElementById('googleUploadFields');
    const uploadBtn = document.getElementById('btnUpload');
    const fileInput = document.getElementById('fileInput');
    const dropZoneText = document.getElementById('dropZoneText');

    if (localFields) localFields.classList.toggle('upload-source-fields-hidden', uploadSource !== 'local');
    if (googleFields) googleFields.classList.toggle('upload-source-fields-hidden', uploadSource !== 'google');

    if (uploadSource === 'google') {
        if (fileInput) fileInput.value = '';
        if (dropZoneText) {
            dropZoneText.textContent = 'Arrastra un archivo aquí o haz clic para seleccionar';
        }
        if (uploadBtn) {
            uploadBtn.innerHTML = '<i class="fab fa-google"></i> Agregar Fuente Google';
        }
    } else if (uploadBtn) {
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Subir Fuente';
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

    const searchInput = document.getElementById('fontSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            currentSearchTerm = event.target.value || '';
            renderFonts();
        });
    }

    document.querySelectorAll('input[name="uploadSource"]').forEach((radio) => {
        radio.addEventListener('change', (event) => {
            setUploadSource(event.target.value);
        });
    });

    const googleFamilyInput = document.getElementById('googleFontFamily');
    const fontNameInput = document.getElementById('fontName');
    if (googleFamilyInput && fontNameInput) {
        googleFamilyInput.addEventListener('change', () => {
            const normalizedFamily = normalizeGoogleFamily(googleFamilyInput.value);
            if (normalizedFamily) {
                googleFamilyInput.value = normalizedFamily;
                if (!fontNameInput.value.trim()) {
                    fontNameInput.value = normalizedFamily;
                }
            }
        });

        googleFamilyInput.addEventListener('blur', () => {
            const normalizedFamily = normalizeGoogleFamily(googleFamilyInput.value);
            googleFamilyInput.value = normalizedFamily;
            if (normalizedFamily && !fontNameInput.value.trim()) {
                fontNameInput.value = normalizedFamily;
            }
        });
    }

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
    const sourceLocal = document.querySelector('input[name="uploadSource"][value="local"]');
    if (sourceLocal) sourceLocal.checked = true;
    setUploadSource('local');
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
    const formData = new FormData();
    const source = uploadSource;
    const category = document.getElementById('fontCategory').value;
    const googleFamilyInput = document.getElementById('googleFontFamily');
    const fileInput = document.getElementById('fileInput');
    const fontNameInput = document.getElementById('fontName');
    const baseFontName = fontNameInput.value.trim();

    if (source === 'local') {
        if (!fileInput.files.length) {
            showNotification('Por favor selecciona un archivo de fuente', 'error');
            return;
        }
        if (!baseFontName) {
            showNotification('Por favor ingresa un nombre para la fuente', 'error');
            return;
        }

        formData.append('action', 'upload');
        formData.append('archivo', fileInput.files[0]);
        formData.append('nombre', baseFontName);
        formData.append('categoria', category);
    } else {
        const googleFamily = normalizeGoogleFamily(googleFamilyInput ? googleFamilyInput.value : '');
        if (!googleFamily) {
            showNotification('Ingresa una familia válida de Google Fonts', 'error');
            return;
        }

        if (googleFamilyInput) googleFamilyInput.value = googleFamily;

        const finalFontName = baseFontName || googleFamily;
        if (!finalFontName) {
            showNotification('Por favor ingresa un nombre para la fuente', 'error');
            return;
        }

        if (!baseFontName) fontNameInput.value = finalFontName;

        formData.append('action', 'add_google');
        formData.append('nombre', finalFontName);
        formData.append('google_family', googleFamily);
        formData.append('categoria', category);
    }

    const btn = document.getElementById('btnUpload');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = source === 'google'
        ? '<i class="fas fa-spinner fa-spin"></i> Agregando...'
        : '<i class="fas fa-spinner fa-spin"></i> Subiendo...';

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
            showNotification(data.message || 'Error al registrar la fuente', 'error');
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
