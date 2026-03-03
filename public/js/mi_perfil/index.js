// Variables globales - datosOriginales se inyecta desde PHP en la vista

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');

        // Cargar actividad cuando se selecciona esa tab
        if (btn.dataset.tab === 'activity') {
            cargarActividad();
        }
    });
});

// Guardar información personal
document.getElementById('formPerfil').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const datos = {
        action: 'actualizar_perfil',
        nombre_completo: formData.get('nombre_completo'),
        email: formData.get('email')
    };

    try {
        const response = await fetch('../api/perfil/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const result = await response.json();

        if (result.success) {
            mostrarAlerta('success', result.message || 'Perfil actualizado correctamente');
            datosOriginales.nombre_completo = datos.nombre_completo;
            datosOriginales.email = datos.email;

            // Actualizar nombre en el header
            document.querySelector('.profile-info h2').textContent = datos.nombre_completo;
        } else {
            mostrarAlerta('error', result.error || 'Error al actualizar el perfil');
        }
    } catch (error) {
        mostrarAlerta('error', 'Error de conexión');
    }
});

// Restablecer formulario
function resetForm() {
    document.getElementById('nombre_completo').value = datosOriginales.nombre_completo;
    document.getElementById('email').value = datosOriginales.email;
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validar contraseña
function validarPassword() {
    const password = document.getElementById('password_nuevo').value;
    const confirmar = document.getElementById('password_confirmar').value;

    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        match: password === confirmar && password.length > 0
    };

    Object.keys(requirements).forEach(req => {
        const el = document.getElementById('req-' + req);
        const icon = el.querySelector('i');

        if (requirements[req]) {
            el.classList.add('valid');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-check');
        } else {
            el.classList.remove('valid');
            icon.classList.remove('fa-check');
            icon.classList.add('fa-times');
        }
    });

    const allValid = Object.values(requirements).every(v => v);
    document.getElementById('btnCambiarPassword').disabled = !allValid;
}

// Cambiar contraseña
document.getElementById('formPassword').addEventListener('submit', async (e) => {
    e.preventDefault();

    const datos = {
        action: 'cambiar_password',
        password_actual: document.getElementById('password_actual').value,
        password_nuevo: document.getElementById('password_nuevo').value,
        password_confirmar: document.getElementById('password_confirmar').value
    };

    try {
        const response = await fetch('../api/perfil/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('modalExito').classList.add('active');
            document.getElementById('formPassword').reset();
            validarPassword();
        } else {
            mostrarAlerta('error', result.error || 'Error al cambiar la contraseña');
        }
    } catch (error) {
        mostrarAlerta('error', 'Error de conexión');
    }
});

function cerrarModalExito() {
    document.getElementById('modalExito').classList.remove('active');
}

// Cargar actividad reciente
async function cargarActividad() {
    try {
        const response = await fetch('../api/perfil/index.php?action=actividad');
        const result = await response.json();

        const container = document.getElementById('activityList');

        if (result.success && result.actividad && result.actividad.length > 0) {
            container.innerHTML = result.actividad.map(act => {
                let icon = 'fa-circle';
                if (act.accion.includes('login')) icon = 'fa-sign-in-alt';
                else if (act.accion.includes('logout')) icon = 'fa-sign-out-alt';
                else if (act.accion.includes('password')) icon = 'fa-key';
                else if (act.accion.includes('perfil')) icon = 'fa-user-edit';
                else if (act.accion.includes('certificado')) icon = 'fa-certificate';

                return `
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="activity-info">
                            <h4>${escapeHtml(act.accion)}</h4>
                            <p>${escapeHtml(act.descripcion || '')}</p>
                        </div>
                        <span class="activity-time">${formatearFecha(act.fecha_creacion)}</span>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                    <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px;"></i>
                    <p>No hay actividad reciente</p>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('activityList').innerHTML = `
            <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 15px; color: #ff6b6b;"></i>
                <p>Error al cargar la actividad</p>
            </div>
        `;
    }
}

// Mostrar alerta
function mostrarAlerta(tipo, mensaje) {
    const container = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo}`;
    alert.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${mensaje}
    `;

    container.innerHTML = '';
    container.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Utilidades
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    const ahora = new Date();
    const diff = ahora - d;

    if (diff < 60000) return 'Hace un momento';
    if (diff < 3600000) return `Hace ${Math.floor(diff / 60000)} min`;
    if (diff < 86400000) return `Hace ${Math.floor(diff / 3600000)} horas`;
    if (diff < 604800000) return `Hace ${Math.floor(diff / 86400000)} días`;

    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}
