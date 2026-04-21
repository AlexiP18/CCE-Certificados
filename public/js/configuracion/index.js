function showAlert(message, type = 'success') {
    const box = document.getElementById('alertBox');
    box.className = `alert-box ${type}`;
    box.textContent = message;
    box.style.display = 'block';
    setTimeout(() => {
        box.style.display = 'none';
    }, 4500);
}

function setImagePreview(id, src) {
    const img = document.getElementById(id);
    if (!img) return;

    if (src) {
        img.src = src;
        img.style.display = 'block';
    } else {
        img.removeAttribute('src');
        img.style.display = 'none';
    }
}

function bindPreview(fileInputId, previewId) {
    const input = document.getElementById(fileInputId);
    if (!input) return;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        setImagePreview(previewId, url);
    });
}

async function loadSettings() {
    try {
        const res = await fetch('../api/configuracion/index.php?action=get');
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message || 'No se pudo cargar configuracion');
        }

        const s = data.settings;
        document.getElementById('site_name').value = s.site_name || '';
        document.getElementById('institution_name').value = s.institution_name || '';
        document.getElementById('primary_color').value = s.primary_color || '#667eea';
        document.getElementById('secondary_color').value = s.secondary_color || '#764ba2';

        const basePath = (window.basePath || '..').replace(/\/$/, '');
        setImagePreview('preview_logo_nav', s.logo_nav ? `${basePath}/${s.logo_nav}` : '');
        setImagePreview('preview_logo_header', s.logo_header ? `${basePath}/${s.logo_header}` : '');
        setImagePreview('preview_favicon', s.favicon ? `${basePath}/${s.favicon}` : '');
    } catch (error) {
        showAlert(error.message || 'Error cargando configuracion', 'error');
    }
}

async function saveSettings(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('action', 'save');
    formData.append('site_name', document.getElementById('site_name').value.trim());
    formData.append('institution_name', document.getElementById('institution_name').value.trim());
    formData.append('primary_color', document.getElementById('primary_color').value);
    formData.append('secondary_color', document.getElementById('secondary_color').value);

    const logoNav = document.getElementById('logo_nav_file');
    if (logoNav && logoNav.files && logoNav.files[0]) {
        formData.append('logo_nav_file', logoNav.files[0]);
    }

    const logoHeader = document.getElementById('logo_header_file');
    if (logoHeader && logoHeader.files && logoHeader.files[0]) {
        formData.append('logo_header_file', logoHeader.files[0]);
    }

    const favicon = document.getElementById('favicon_file');
    if (favicon && favicon.files && favicon.files[0]) {
        formData.append('favicon_file', favicon.files[0]);
    }

    try {
        const response = await fetch('../api/configuracion/index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'No se pudo guardar la configuracion');
        }

        showAlert(data.message || 'Configuracion guardada', 'success');
        await loadSettings();
    } catch (error) {
        showAlert(error.message || 'Error guardando configuracion', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    bindPreview('logo_nav_file', 'preview_logo_nav');
    bindPreview('logo_header_file', 'preview_logo_header');
    bindPreview('favicon_file', 'preview_favicon');
    document.getElementById('configForm').addEventListener('submit', saveSettings);
    loadSettings();
});
