<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/usuarios/index.css">
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>/dashboard/index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <li><a href="<?= $basePath ?>/usuarios/index.php" class="nav-link active"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <li class="nav-user">
                <a href="<?= $basePath ?>/perfil/dashboard/index.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
            <p>Administra los usuarios y permisos del sistema</p>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h2><i class="fas fa-user-friends"></i> Usuarios del Sistema</h2>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span id="admin-counter" class="admin-counter"><i class="fas fa-user-shield"></i> Administradores: 0/3</span>
                <?php if ($puede_crear): ?>
                <button class="btn-admin" onclick="abrirModalNuevo()">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            <!-- Table Header (Filtros) -->
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, usuario o email...">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Rol:</label>
                    <select id="filterRol">
                        <option value="">Todos los roles</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-toggle-on"></i> Estado:</label>
                    <select id="filterEstado">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            
            <!-- Table -->
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuarios-body">
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Cargando usuarios...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Table Footer (Paginación) -->
            <div class="table-footer">
                <div class="table-info">
                    Mostrando <span id="showingFrom">0</span> - <span id="showingTo">0</span> de <span id="totalUsers">0</span> usuarios
                </div>
                <div class="per-page-select">
                    <label>Mostrar:</label>
                    <select id="perPage">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="pagination" id="pagination">
                    <!-- Se genera dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuario -->
    <div class="modal" id="modal-usuario">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-plus"></i> <span id="modal-titulo-text">Nuevo Usuario</span></h3>
                <button class="btn-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form id="form-usuario" onsubmit="guardarUsuario(event)">
                <input type="hidden" id="usuario-id">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-at"></i> Nombre de Usuario *</label>
                    <input type="text" id="username" required minlength="3" autocomplete="off" placeholder="usuario123">
                </div>
                
                <div class="form-group">
                    <label for="nombre_completo"><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" id="nombre_completo" required placeholder="Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="email" required placeholder="correo@ejemplo.com">
                </div>
                
                <div class="form-group">
                    <label for="rol_id"><i class="fas fa-user-tag"></i> Rol *</label>
                    <select id="rol_id" required onchange="onRolChange()">
                        <option value="">Seleccionar rol...</option>
                    </select>
                    <p class="help-text" id="admin-limit-warning" style="color: #dc2626; display: none;">
                        <i class="fas fa-exclamation-triangle"></i> Límite de 3 administradores alcanzado
                    </p>
                </div>
                
                <div class="form-group" id="superadmin-group" style="display: none;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="es_superadmin">
                        <label for="es_superadmin"><i class="fas fa-crown"></i> Superadministrador</label>
                    </div>
                    <p class="help-text">El superadministrador puede gestionar a otros administradores</p>
                </div>
                
                <div class="form-group">
                    <label for="cedula"><i class="fas fa-id-card"></i> Cédula/DNI</label>
                    <input type="text" id="cedula" placeholder="Número de identificación">
                </div>
                
                <div class="form-group">
                    <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" id="telefono" placeholder="+58 412 1234567">
                </div>
                
                <div class="form-group">
                    <label for="direccion"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" id="direccion" placeholder="Dirección completa">
                </div>
                
                <div class="form-group">
                    <label for="password" id="label-password"><i class="fas fa-lock"></i> Contraseña *</label>
                    <div class="password-toggle">
                        <input type="password" id="password" minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                        <button type="button" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                    </div>
                    <p class="help-text" id="help-password">Mínimo 6 caracteres</p>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" checked>
                        <label for="activo">Usuario activo</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="notification-container"></div>


    <script>
        // Permisos y variables globales
        const puedeEditar = <?= $puede_editar ? 'true' : 'false' ?>;
        const puedeEliminar = <?= $puede_eliminar ? 'true' : 'false' ?>;
        const usuarioActualId = <?= json_encode($_SESSION['usuario_id']) ?>;
        const esSuperadmin = <?= $es_superadmin ? 'true' : 'false' ?>;
        const esAdmin = <?= $es_admin ? 'true' : 'false' ?>;
    </script>
    <script src="<?= $jsPath ?>/usuarios/index.js"></script>
</body>
</html>
