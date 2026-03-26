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
const PASSWORD_REGEX = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,128}$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const LIMITES_CAMPOS = {
    username: 50,
    nombre_completo: 255,
    email: 255,
    cedula: 10,
    telefono: 10,
    direccion: 500,
    instructor_codigo_postal: 6,
    instructor_maps: 255,
    instructor_especialidad: 255,
    instructor_titulo: 255,
    instructor_institucion: 255,
    instructor_certificaciones: 2000,
    instructor_biografia: 2000,
    password: 128
};

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarRoles();
    cargarUsuarios();
    configurarCampoCelular();
    configurarCampoCedula();
    configurarCampoCodigoPostal();

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

function configurarCampoCelular() {
    const telefonoInput = document.getElementById('telefono');
    if (!telefonoInput) return;

    telefonoInput.addEventListener('input', () => {
        const digits = (telefonoInput.value || '').replace(/\D/g, '').slice(0, 10);
        telefonoInput.value = digits;
    });
}

function configurarCampoCedula() {
    const cedulaInput = document.getElementById('cedula');
    if (!cedulaInput) return;

    cedulaInput.addEventListener('input', () => {
        const digits = (cedulaInput.value || '').replace(/\D/g, '').slice(0, 10);
        cedulaInput.value = digits;
    });
}

function configurarCampoCodigoPostal() {
    const codigoPostalInput = document.getElementById('instructor_codigo_postal');
    if (!codigoPostalInput) return;

    codigoPostalInput.addEventListener('input', () => {
        const digits = (codigoPostalInput.value || '').replace(/\D/g, '').slice(0, 6);
        codigoPostalInput.value = digits;
    });
}

function esCedulaEcuatorianaValida(cedula) {
    if (!/^\d{10}$/.test(cedula)) return false;

    const provincia = Number(cedula.slice(0, 2));
    const tercerDigito = Number(cedula[2]);
    if (provincia < 1 || provincia > 24 || tercerDigito > 5) {
        return false;
    }

    const coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    let suma = 0;
    for (let i = 0; i < 9; i++) {
        let valor = Number(cedula[i]) * coeficientes[i];
        if (valor >= 10) valor -= 9;
        suma += valor;
    }

    const verificadorEsperado = (10 - (suma % 10)) % 10;
    return verificadorEsperado === Number(cedula[9]);
}

function esUrlHttpValida(url) {
    if (!url) return true;
    try {
        const parsed = new URL(url);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

function excedeLongitud(valor, maximo) {
    return (valor || '').trim().length > maximo;
}

function construirFotoUsuarioUrl(foto) {
    const raw = (foto || '').trim();
    if (!raw) return '';

    if (/^https?:\/\//i.test(raw)) {
        return raw;
    }

    // Mantener rutas relativas ya normalizadas
    if (/^\.\.\//.test(raw) || /^\//.test(raw)) {
        return raw;
    }

    // Guardado estándar en API: "usuarios/<archivo>"
    if (/^usuarios\//i.test(raw)) {
        return `../../uploads/${raw}`;
    }

    // Formato legacy: solo nombre de archivo, asumir carpeta uploads/usuarios
    if (!raw.includes('/')) {
        return `../../uploads/usuarios/${raw}`;
    }

    // Cualquier otra ruta relativa se resuelve desde uploads raíz
    return `../../uploads/${raw.replace(/^\/*/, '')}`;
}

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
        const fotoUrl = construirFotoUsuarioUrl(u.foto || '');
        const avatarHtml = fotoUrl
            ? `<div class="user-avatar user-avatar-photo"><img src="${encodeURI(fotoUrl)}" alt="Foto de ${escapeHtml(u.nombre_completo)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><span class="user-avatar-fallback" style="display:none;">${iniciales}</span></div>`
            : `<div class="user-avatar">${iniciales}</div>`;

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
                        ${avatarHtml}
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

function limpiarCamposInstructor() {
    const ids = [
        'instructor_especialidad',
        'instructor_experiencia',
        'instructor_titulo',
        'instructor_institucion',
        'instructor_anio_titulo',
        'instructor_certificaciones',
        'instructor_biografia'
    ];

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const certFile = document.getElementById('certificado_titulo_file');
    if (certFile) certFile.value = '';
}

function parseDireccionInstructor(direccion) {
    const raw = (direccion || '').trim();
    const result = { direccionBase: raw, codigoPostal: '', mapsUrl: '' };

    if (!raw) return result;

    const cpMatch = raw.match(/\[CP:([^\]]*)\]/i);
    const mapsMatch = raw.match(/\[MAPS:([^\]]*)\]/i);

    if (cpMatch) result.codigoPostal = cpMatch[1].trim();
    if (mapsMatch) result.mapsUrl = mapsMatch[1].trim();

    result.direccionBase = raw
        .replace(/\s*\[CP:[^\]]*\]/ig, '')
        .replace(/\s*\[MAPS:[^\]]*\]/ig, '')
        .trim();

    return result;
}

function construirDireccionConMeta(direccionBase, codigoPostal, mapsUrl) {
    const base = (direccionBase || '').trim();
    const cp = (codigoPostal || '').trim();
    const maps = (mapsUrl || '').trim();

    let salida = base;
    if (cp) salida += `${salida ? ' ' : ''}[CP:${cp}]`;
    if (maps) salida += `${salida ? ' ' : ''}[MAPS:${maps}]`;
    return salida;
}

function obtenerRolNombreSeleccionado() {
    const rolId = document.getElementById('rol_id').value;
    return roles.find(r => r.id == rolId)?.nombre || '';
}

function toggleInstructorFields(rolNombre) {
    const block = document.getElementById('instructor-fields');
    if (!block) return;
    block.style.display = rolNombre === 'instructor' ? 'block' : 'none';
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
    document.getElementById('password_confirm').required = true;
    document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Contraseña *';
    document.getElementById('help-password').textContent = 'Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo';
    document.getElementById('label-password-confirm').innerHTML = '<i class="fas fa-lock"></i> Confirmar Contraseña *';
    document.getElementById('help-password-confirm').textContent = 'Debe coincidir con la contraseña';
    document.getElementById('superadmin-group').style.display = 'none';
    document.getElementById('admin-limit-warning').style.display = 'none';
    toggleInstructorFields('');
    limpiarCamposInstructor();

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

    toggleInstructorFields(rolNombre);
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
            const metaDireccion = parseDireccionInstructor(u.direccion || '');
            document.getElementById('cedula').value = u.cedula || '';
            document.getElementById('telefono').value = u.telefono || '';
            document.getElementById('direccion').value = metaDireccion.direccionBase || '';
            document.getElementById('es_superadmin').checked = u.es_superadmin == 1;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('password_confirm').value = '';
            document.getElementById('password_confirm').required = false;
            document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Nueva Contraseña (opcional)';
            document.getElementById('help-password').textContent = 'Si cambias la contraseña, usa mínimo 8 caracteres con mayúscula, minúscula, número y símbolo';
            document.getElementById('label-password-confirm').innerHTML = '<i class="fas fa-lock"></i> Confirmar Nueva Contraseña';
            document.getElementById('help-password-confirm').textContent = 'Si cambias la contraseña, debes confirmarla';

            document.getElementById('instructor_codigo_postal').value = metaDireccion.codigoPostal || '';
            document.getElementById('instructor_maps').value = metaDireccion.mapsUrl || '';

            toggleInstructorFields(u.rol_nombre || '');

            if (u.rol_nombre === 'instructor') {
                const p = u.perfil_instructor || {};
                document.getElementById('instructor_especialidad').value = p.especialidad || '';
                document.getElementById('instructor_experiencia').value = p.experiencia_anios || '';
                document.getElementById('instructor_titulo').value = p.titulo_academico || '';
                document.getElementById('instructor_institucion').value = p.institucion_titulo || '';
                document.getElementById('instructor_anio_titulo').value = p.anio_titulo || '';

                let certs = p.certificaciones || '';
                if (Array.isArray(certs)) certs = certs.join('\n');
                if (typeof certs === 'string' && certs.trim().startsWith('[')) {
                    try {
                        const parsed = JSON.parse(certs);
                        certs = Array.isArray(parsed) ? parsed.join('\n') : certs;
                    } catch (_) {}
                }
                document.getElementById('instructor_certificaciones').value = certs || '';
                document.getElementById('instructor_biografia').value = p.biografia || '';
            } else {
                limpiarCamposInstructor();
            }

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
    const rolNombre = obtenerRolNombreSeleccionado();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const email = document.getElementById('email').value.trim();
    const cedula = document.getElementById('cedula').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const mapsUrl = document.getElementById('instructor_maps').value.trim();

    const username = document.getElementById('username').value.trim();
    const nombreCompleto = document.getElementById('nombre_completo').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const codigoPostal = document.getElementById('instructor_codigo_postal').value.trim();
    const especialidad = document.getElementById('instructor_especialidad').value.trim();
    const titulo = document.getElementById('instructor_titulo').value.trim();
    const institucion = document.getElementById('instructor_institucion').value.trim();
    const certificaciones = document.getElementById('instructor_certificaciones').value.trim();
    const biografia = document.getElementById('instructor_biografia').value.trim();

    if (!id && !password) {
        mostrarNotificacion('La contraseña es obligatoria para crear un usuario', 'error');
        return;
    }

    if (!EMAIL_REGEX.test(email)) {
        mostrarNotificacion('El email ingresado no es válido', 'error');
        return;
    }

    if (cedula && !esCedulaEcuatorianaValida(cedula)) {
        mostrarNotificacion('La cédula debe tener 10 dígitos y ser una cédula ecuatoriana válida', 'error');
        return;
    }

    if (password || passwordConfirm) {
        if (!PASSWORD_REGEX.test(password)) {
            mostrarNotificacion('La contraseña debe tener mínimo 8 caracteres, incluir mayúscula, minúscula, número y símbolo', 'error');
            return;
        }
        if (password !== passwordConfirm) {
            mostrarNotificacion('La confirmación de contraseña no coincide', 'error');
            return;
        }
    }

    if (telefono && !/^09\d{8}$/.test(telefono)) {
        mostrarNotificacion('El celular debe tener formato ecuatoriano: 10 dígitos y comenzar con 09', 'error');
        return;
    }

    if (!esUrlHttpValida(mapsUrl)) {
        mostrarNotificacion('El enlace de Google Maps debe ser una URL válida con http o https', 'error');
        return;
    }

    if (codigoPostal && !/^\d{1,6}$/.test(codigoPostal)) {
        mostrarNotificacion('El código postal solo puede contener hasta 6 dígitos', 'error');
        return;
    }

    if (excedeLongitud(username, LIMITES_CAMPOS.username) ||
        excedeLongitud(nombreCompleto, LIMITES_CAMPOS.nombre_completo) ||
        excedeLongitud(email, LIMITES_CAMPOS.email) ||
        excedeLongitud(cedula, LIMITES_CAMPOS.cedula) ||
        excedeLongitud(telefono, LIMITES_CAMPOS.telefono) ||
        excedeLongitud(direccion, LIMITES_CAMPOS.direccion) ||
        excedeLongitud(codigoPostal, LIMITES_CAMPOS.instructor_codigo_postal) ||
        excedeLongitud(mapsUrl, LIMITES_CAMPOS.instructor_maps) ||
        excedeLongitud(especialidad, LIMITES_CAMPOS.instructor_especialidad) ||
        excedeLongitud(titulo, LIMITES_CAMPOS.instructor_titulo) ||
        excedeLongitud(institucion, LIMITES_CAMPOS.instructor_institucion) ||
        excedeLongitud(certificaciones, LIMITES_CAMPOS.instructor_certificaciones) ||
        excedeLongitud(biografia, LIMITES_CAMPOS.instructor_biografia) ||
        password.length > LIMITES_CAMPOS.password ||
        passwordConfirm.length > LIMITES_CAMPOS.password) {
        mostrarNotificacion('Uno o más campos superan la longitud permitida', 'error');
        return;
    }

    const direccionConMeta = construirDireccionConMeta(
        direccion,
        codigoPostal,
        mapsUrl
    );

    const formData = new FormData();
    formData.append('action', id ? 'update' : 'create');
    if (id) formData.append('id', id);
    formData.append('username', username);
    formData.append('nombre_completo', nombreCompleto);
    formData.append('email', email);
    formData.append('rol_id', document.getElementById('rol_id').value);
    formData.append('activo', document.getElementById('activo').checked ? '1' : '0');
    formData.append('password', password);
    formData.append('cedula', cedula);
    formData.append('telefono', telefono);
    formData.append('direccion', direccionConMeta);
    formData.append('es_superadmin', document.getElementById('es_superadmin').checked ? '1' : '0');

    const fotoFileInput = document.getElementById('foto_file');
    if (fotoFileInput && fotoFileInput.files && fotoFileInput.files[0]) {
        formData.append('foto_file', fotoFileInput.files[0]);
    }

    // No enviar password vacío en edición (multipart)
    if (id && !document.getElementById('password').value) {
        formData.delete('password');
    }

    try {
        const response = await fetch('../api/usuarios/index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            const usuarioId = id || data.id;

            if (rolNombre === 'instructor' && usuarioId) {
                const certsRaw = document.getElementById('instructor_certificaciones').value || '';
                const certsList = certsRaw
                    .split('\n')
                    .map(v => v.trim())
                    .filter(Boolean);

                const payloadInstructor = new FormData();
                payloadInstructor.append('action', 'actualizar_perfil_instructor');
                payloadInstructor.append('usuario_id', String(Number(usuarioId)));
                payloadInstructor.append('especialidad', document.getElementById('instructor_especialidad').value.trim());
                payloadInstructor.append('titulo_academico', document.getElementById('instructor_titulo').value.trim());
                payloadInstructor.append('institucion_titulo', document.getElementById('instructor_institucion').value.trim());
                payloadInstructor.append('anio_titulo', document.getElementById('instructor_anio_titulo').value);
                payloadInstructor.append('experiencia_anios', document.getElementById('instructor_experiencia').value);
                payloadInstructor.append('biografia', document.getElementById('instructor_biografia').value.trim());
                payloadInstructor.append('certificaciones', JSON.stringify(certsList));

                const certFileInput = document.getElementById('certificado_titulo_file');
                if (certFileInput && certFileInput.files && certFileInput.files[0]) {
                    payloadInstructor.append('certificado_titulo_file', certFileInput.files[0]);
                }

                const respPerfil = await fetch('../api/usuarios/index.php', {
                    method: 'POST',
                    body: payloadInstructor
                });
                const dataPerfil = await respPerfil.json();

                if (!dataPerfil.success) {
                    mostrarNotificacion(dataPerfil.message || 'Usuario guardado, pero falló perfil instructor', 'warning');
                }
            }

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

// El modal solo se cierra por acciones explícitas de UI (botón X o Cancelar)
