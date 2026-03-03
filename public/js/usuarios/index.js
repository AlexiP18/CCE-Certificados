// ===== FUNCIÓN DE NOTIFICACIONES =====
function mostrarNotificacion(mensaje, tipo = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification notification-${tipo}`;

    const iconMap = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };

    notification.innerHTML = `
        <i class="fas ${iconMap[tipo] || iconMap.info}"></i>
        <span>${mensaje}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(notification);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Estado
let roles = [];
let usuarios = [];
let usuariosFiltrados = [];
let modoEdicion = false;

// Paginación
let currentPage = 1;
let perPage = 10;

// Variables globales (deben ser definidas en la vista antes de cargar este script)
// let puedeEditar = ...;
// let puedeEliminar = ...;
// let usuarioActualId = ...;
// let esSuperadmin = ...;
// let esAdmin = ...;

let adminCount = 0;
const maxAdmins = 3;

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarRoles();
    cargarUsuarios();

    // Event listeners para filtros
    document.getElementById('searchInput').addEventListener('input', filtrarUsuarios);
    document.getElementById('filterRol').addEventListener('change', filtrarUsuarios);
    document.getElementById('filterEstado').addEventListener('change', filtrarUsuarios);
    document.getElementById('perPage').addEventListener('change', (e) => {
        perPage = parseInt(e.target.value);
        currentPage = 1;
        renderizarUsuarios();
    });
});

// Cargar roles
async function cargarRoles() {
    try {
        const response = await fetch('../api/usuarios/index.php?action=roles');
        const data = await response.json();
        if (data.success) {
            roles = data.roles;
            adminCount = data.admin_count || 0;

            // Llenar select del modal
            const selectModal = document.getElementById('rol_id');
            selectModal.innerHTML = '<option value="">Seleccionar rol...</option>';
            roles.forEach(rol => {
                // Mostrar advertencia si es admin y ya hay 3
                let label = `${rol.nombre} - ${rol.descripcion || ''}`;
                let disabled = '';
                if (rol.nombre === 'administrador' && adminCount >= maxAdmins) {
                    label += ' (Límite alcanzado)';
                    disabled = 'disabled';
                }
                selectModal.innerHTML += `<option value="${rol.id}" ${disabled}>${label}</option>`;
            });

            // Llenar filtro de roles
            const filterRol = document.getElementById('filterRol');
            filterRol.innerHTML = '<option value="">Todos los roles</option>';
            roles.forEach(rol => {
                filterRol.innerHTML += `<option value="${rol.nombre}">${rol.nombre}</option>`;
            });

            // Mostrar contador de admins
            actualizarContadorAdmins();
        }
    } catch (error) {
        console.error('Error cargando roles:', error);
    }
}

// Actualizar contador de admins
function actualizarContadorAdmins() {
    const info = document.getElementById('admin-counter');
    if (info) {
        info.innerHTML = `<i class="fas fa-user-shield"></i> Administradores: ${adminCount}/${maxAdmins}`;
        info.className = adminCount >= maxAdmins ? 'admin-counter full' : 'admin-counter';
    }
}

// Cargar usuarios
async function cargarUsuarios() {
    try {
        const response = await fetch('../api/usuarios/index.php?action=list');
        const data = await response.json();

        if (data.success) {
            usuarios = data.usuarios;
            usuariosFiltrados = [...usuarios];
            adminCount = data.admin_count || 0;
            actualizarContadorAdmins();
            renderizarUsuarios();
        } else {
            mostrarNotificacion(data.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        mostrarNotificacion('Error al cargar usuarios', 'error');
    }
}

// Filtrar usuarios
function filtrarUsuarios() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filterRol = document.getElementById('filterRol').value.toLowerCase();
    const filterEstado = document.getElementById('filterEstado').value;

    usuariosFiltrados = usuarios.filter(u => {
        const matchSearch = !searchTerm ||
            u.username.toLowerCase().includes(searchTerm) ||
            u.nombre_completo.toLowerCase().includes(searchTerm) ||
            u.email.toLowerCase().includes(searchTerm);

        const matchRol = !filterRol || u.rol_nombre.toLowerCase() === filterRol;
        const matchEstado = filterEstado === '' || u.activo.toString() === filterEstado;

        return matchSearch && matchRol && matchEstado;
    });

    currentPage = 1;
    renderizarUsuarios();
}

// Renderizar tabla
function renderizarUsuarios() {
    const tbody = document.getElementById('usuarios-body');
    const totalUsers = usuariosFiltrados.length;
    const totalPages = Math.ceil(totalUsers / perPage);
    const startIndex = (currentPage - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, totalUsers);
    const usuariosPagina = usuariosFiltrados.slice(startIndex, endIndex);

    // Actualizar info
    document.getElementById('showingFrom').textContent = totalUsers > 0 ? startIndex + 1 : 0;
    document.getElementById('showingTo').textContent = endIndex;
    document.getElementById('totalUsers').textContent = totalUsers;

    if (usuariosPagina.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No se encontraron usuarios</h3>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </td>
            </tr>
        `;
        renderizarPaginacion(0);
        return;
    }

    tbody.innerHTML = usuariosPagina.map(u => {
        const rolBadgeClass = {
            'administrador': 'danger',
            'editor': 'warning',
            'operador': 'primary',
            'visualizador': 'secondary',
            'instructor': 'purple',
            'oficinista': 'warning'
        }[u.rol_nombre] || 'secondary';

        const rolIcon = {
            'administrador': 'fa-user-shield',
            'editor': 'fa-user-edit',
            'operador': 'fa-user-cog',
            'visualizador': 'fa-user',
            'instructor': 'fa-chalkboard-teacher',
            'oficinista': 'fa-user-tie'
        }[u.rol_nombre] || 'fa-user';

        const estadoBadge = u.activo == 1
            ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activo</span>'
            : '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inactivo</span>';

        // Badge de superadmin
        const superadminBadge = u.es_superadmin == 1
            ? '<span class="badge badge-purple" title="Superadministrador"><i class="fas fa-crown"></i></span>'
            : '';

        const ultimoAcceso = u.ultimo_acceso
            ? formatearFecha(u.ultimo_acceso)
            : '<span style="color: #95a5a6"><i class="fas fa-clock"></i> Nunca</span>';

        const iniciales = u.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

        let acciones = '';
        if (puedeEditar) {
            acciones += `<button class="btn-icon" onclick="editarUsuario(${u.id})" title="Editar"><i class="fas fa-edit"></i></button>`;
        }
        // No permitir eliminar superadmin a menos que sea superadmin
        const puedeEliminarEste = puedeEliminar && u.id != usuarioActualId &&
            (u.es_superadmin != 1 || esSuperadmin);
        if (puedeEliminarEste) {
            acciones += `<button class="btn-icon danger" onclick="eliminarUsuario(${u.id}, '${escapeHtml(u.username)}')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>`;
        }

        return `
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar">${iniciales}</div>
                        <div class="user-info-cell">
                            <strong>${escapeHtml(u.nombre_completo)} ${superadminBadge}</strong>
                            <span>@${escapeHtml(u.username)}</span>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(u.email)}</td>
                <td><span class="badge badge-${rolBadgeClass}"><i class="fas ${rolIcon}"></i> ${escapeHtml(u.rol_nombre)}</span></td>
                <td>${estadoBadge}</td>
                <td>${ultimoAcceso}</td>
                <td class="actions-cell">${acciones}</td>
            </tr>
        `;
    }).join('');

    renderizarPaginacion(totalPages);
}

// Renderizar paginación
function renderizarPaginacion(totalPages) {
    const container = document.getElementById('pagination');

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';

    // Botón anterior
    html += `<button class="pagination-btn" onclick="cambiarPagina(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
        <i class="fas fa-chevron-left"></i>
    </button>`;

    // Páginas
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="cambiarPagina(1)">1</button>`;
        if (startPage > 2) {
            html += `<span style="padding: 0 8px; color: #95a5a6;">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="cambiarPagina(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span style="padding: 0 8px; color: #95a5a6;">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="cambiarPagina(${totalPages})">${totalPages}</button>`;
    }

    // Botón siguiente
    html += `<button class="pagination-btn" onclick="cambiarPagina(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
        <i class="fas fa-chevron-right"></i>
    </button>`;

    container.innerHTML = html;
}

// Cambiar página
function cambiarPagina(page) {
    const totalPages = Math.ceil(usuariosFiltrados.length / perPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderizarUsuarios();
}

// Formatear fecha
function formatearFecha(fecha) {
    const d = new Date(fecha);
    return d.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Abrir modal nuevo usuario
function abrirModalNuevo() {
    modoEdicion = false;
    document.getElementById('modal-titulo-text').textContent = 'Nuevo Usuario';
    document.querySelector('.modal-title i').className = 'fas fa-user-plus';
    document.getElementById('usuario-id').value = '';
    document.getElementById('form-usuario').reset();
    document.getElementById('activo').checked = true;
    document.getElementById('es_superadmin').checked = false;
    document.getElementById('password').required = true;
    document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Contraseña *';
    document.getElementById('help-password').textContent = 'Mínimo 6 caracteres';
    document.getElementById('superadmin-group').style.display = 'none';
    document.getElementById('admin-limit-warning').style.display = 'none';

    // Refrescar opciones de rol
    cargarRoles();

    document.getElementById('modal-usuario').classList.add('active');
}

// Manejar cambio de rol
function onRolChange() {
    const rolId = document.getElementById('rol_id').value;
    const rolNombre = roles.find(r => r.id == rolId)?.nombre || '';

    // Mostrar opción de superadmin solo para administradores y si el usuario actual es superadmin
    if (rolNombre === 'administrador' && esSuperadmin) {
        document.getElementById('superadmin-group').style.display = 'block';
    } else {
        document.getElementById('superadmin-group').style.display = 'none';
        document.getElementById('es_superadmin').checked = false;
    }

    // Verificar límite de admins
    if (rolNombre === 'administrador' && adminCount >= maxAdmins && !modoEdicion) {
        document.getElementById('admin-limit-warning').style.display = 'block';
    } else {
        document.getElementById('admin-limit-warning').style.display = 'none';
    }
}

// Editar usuario
async function editarUsuario(id) {
    try {
        const response = await fetch(`../api/usuarios/index.php?action=get&id=${id}`);
        const data = await response.json();

        if (data.success) {
            modoEdicion = true;
            const u = data.usuario;

            document.getElementById('modal-titulo-text').textContent = 'Editar Usuario';
            document.querySelector('.modal-title i').className = 'fas fa-user-edit';
            document.getElementById('usuario-id').value = u.id;
            document.getElementById('username').value = u.username;
            document.getElementById('nombre_completo').value = u.nombre_completo;
            document.getElementById('email').value = u.email;
            document.getElementById('rol_id').value = u.rol_id;
            document.getElementById('activo').checked = u.activo == 1;
            document.getElementById('cedula').value = u.cedula || '';
            document.getElementById('telefono').value = u.telefono || '';
            document.getElementById('direccion').value = u.direccion || '';
            document.getElementById('es_superadmin').checked = u.es_superadmin == 1;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Nueva Contraseña (opcional)';
            document.getElementById('help-password').textContent = 'Dejar vacío para mantener la contraseña actual';

            // Mostrar opción superadmin si corresponde
            if (u.rol_nombre === 'administrador' && esSuperadmin) {
                document.getElementById('superadmin-group').style.display = 'block';
            } else {
                document.getElementById('superadmin-group').style.display = 'none';
            }

            document.getElementById('admin-limit-warning').style.display = 'none';

            document.getElementById('modal-usuario').classList.add('active');
        } else {
            mostrarNotificacion(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al cargar usuario', 'error');
    }
}

// Guardar usuario
async function guardarUsuario(event) {
    event.preventDefault();

    const id = document.getElementById('usuario-id').value;
    const datos = {
        action: id ? 'update' : 'create',
        id: id || undefined,
        username: document.getElementById('username').value.trim(),
        nombre_completo: document.getElementById('nombre_completo').value.trim(),
        email: document.getElementById('email').value.trim(),
        rol_id: document.getElementById('rol_id').value,
        activo: document.getElementById('activo').checked ? 1 : 0,
        password: document.getElementById('password').value,
        cedula: document.getElementById('cedula').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        direccion: document.getElementById('direccion').value.trim(),
        es_superadmin: document.getElementById('es_superadmin').checked ? 1 : 0
    };

    // No enviar password vacío en edición
    if (id && !datos.password) {
        delete datos.password;
    }

    try {
        const response = await fetch('../api/usuarios/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        const data = await response.json();

        if (data.success) {
            mostrarNotificacion(data.message, 'success');
            cerrarModal();
            cargarUsuarios();
            cargarRoles(); // Refrescar para actualizar contador de admins
        } else {
            mostrarNotificacion(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al guardar usuario', 'error');
    }
}

// Eliminar usuario
async function eliminarUsuario(id, username) {
    if (!confirm(`¿Estás seguro de eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    try {
        const response = await fetch('../api/usuarios/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await response.json();

        if (data.success) {
            mostrarNotificacion(data.message, 'success');
            cargarUsuarios();
        } else {
            mostrarNotificacion(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar usuario', 'error');
    }
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modal-usuario').classList.remove('active');
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Cerrar modal con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

// Cerrar modal al hacer clic fuera
document.getElementById('modal-usuario').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        cerrarModal();
    }
});
