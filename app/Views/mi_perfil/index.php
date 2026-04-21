<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= htmlspecialchars($siteConfig['site_name']) ?></title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/mi_perfil/index.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/header_theme.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/institutional_theme.css">
</head>
<body>
    <?php
    $activeNav = 'perfil';
    require __DIR__ . '/../components/top_nav.php';
    ?>

    <div class="container">
        <div class="hero-section">
            <h1><i class="fas fa-user-cog"></i> Mi Perfil</h1>
            <p>Gestiona tu información personal y configuración de cuenta</p>
        </div>

        <div id="alertContainer"></div>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($usuarioCompleto['nombre_completo']) ?></h2>
                    <p style="color: #7f8c8d;">@<?= htmlspecialchars($usuarioCompleto['username']) ?></p>
                    <?php
                    $rolClass = 'visitante';
                    $rolIcon = 'fa-user';
                    if ($esSuperAdmin) {
                        $rolClass = 'superadmin';
                        $rolIcon = 'fa-crown';
                    } elseif ($usuarioCompleto['rol_nombre'] === 'Administrador') {
                        $rolClass = 'admin';
                        $rolIcon = 'fa-user-shield';
                    } elseif ($usuarioCompleto['rol_nombre'] === 'Instructor') {
                        $rolClass = 'instructor';
                        $rolIcon = 'fa-chalkboard-teacher';
                    } elseif ($usuarioCompleto['rol_nombre'] === 'Oficinista') {
                        $rolClass = 'oficinista';
                        $rolIcon = 'fa-user-tie';
                    }
                    ?>
                    <span class="role-badge <?= $rolClass ?>">
                        <i class="fas <?= $rolIcon ?>"></i>
                        <?= $esSuperAdmin ? 'Super Administrador' : htmlspecialchars($usuarioCompleto['rol_nombre']) ?>
                    </span>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="info"><i class="fas fa-info-circle"></i> Información</button>
                <button class="tab-btn" data-tab="security"><i class="fas fa-shield-alt"></i> Seguridad</button>
                <button class="tab-btn" data-tab="activity"><i class="fas fa-history"></i> Actividad</button>
            </div>

            <!-- Tab: Información -->
            <div class="tab-content active" id="tab-info">
                <h3 class="section-title"><i class="fas fa-edit"></i> Información Personal</h3>
                
                <form id="formPerfil">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre_completo">Nombre Completo</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" 
                                   value="<?= htmlspecialchars($usuarioCompleto['nombre_completo']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Usuario</label>
                            <input type="text" id="username" name="username" 
                                   value="<?= htmlspecialchars($usuarioCompleto['username']) ?>" disabled>
                            <small>El nombre de usuario no se puede cambiar</small>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($usuarioCompleto['email']) ?>" required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Restablecer
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>

                <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Información de la Cuenta</h3>
                    
                    <div class="info-row">
                        <span class="info-label">Rol</span>
                        <span class="info-value"><?= $esSuperAdmin ? 'Super Administrador' : htmlspecialchars($usuarioCompleto['rol_nombre']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado</span>
                        <span class="info-value" style="color: <?= $usuarioCompleto['activo'] ? '#2ecc71' : '#ff6b6b' ?>">
                            <?= $usuarioCompleto['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha de Registro</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($usuarioCompleto['fecha_creacion'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Último Acceso</span>
                        <span class="info-value">
                            <?= $usuarioCompleto['ultimo_acceso'] 
                                ? date('d/m/Y H:i', strtotime($usuarioCompleto['ultimo_acceso'])) 
                                : 'Nunca' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab: Seguridad -->
            <div class="tab-content" id="tab-security">
                <h3 class="section-title"><i class="fas fa-key"></i> Cambiar Contraseña</h3>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Para mayor seguridad, te recomendamos cambiar tu contraseña periódicamente.
                </div>

                <form id="formPassword">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="password_actual">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" id="password_actual" name="password_actual" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password_actual')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="password_nuevo">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password_nuevo" name="password_nuevo" 
                                   required minlength="8" oninput="validarPassword()">
                            <button type="button" class="toggle-password" onclick="togglePassword('password_nuevo')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="password_confirmar">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmar" name="password_confirmar" 
                                   required minlength="8" oninput="validarPassword()">
                            <button type="button" class="toggle-password" onclick="togglePassword('password_confirmar')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <h4><i class="fas fa-check-circle"></i> Requisitos de la contraseña:</h4>
                        <ul>
                            <li id="req-length"><i class="fas fa-times"></i> Mínimo 8 caracteres</li>
                            <li id="req-upper"><i class="fas fa-times"></i> Al menos una letra mayúscula</li>
                            <li id="req-lower"><i class="fas fa-times"></i> Al menos una letra minúscula</li>
                            <li id="req-number"><i class="fas fa-times"></i> Al menos un número</li>
                            <li id="req-match"><i class="fas fa-times"></i> Las contraseñas coinciden</li>
                        </ul>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" id="btnCambiarPassword" disabled>
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Actividad -->
            <div class="tab-content" id="tab-activity">
                <h3 class="section-title"><i class="fas fa-history"></i> Actividad Reciente</h3>
                
                <div class="activity-list" id="activityList">
                    <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top: 15px;">Cargando actividad...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal cambio de contraseña exitoso -->
    <div class="modal-overlay" id="modalExito">
        <div class="modal">
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #2ecc71; margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 10px;">¡Contraseña Actualizada!</h3>
                <p style="color: rgba(255,255,255,0.7); margin-bottom: 25px;">
                    Tu contraseña ha sido cambiada exitosamente.
                </p>
                <button class="btn btn-primary" onclick="cerrarModalExito()">
                    <i class="fas fa-check"></i> Entendido
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales inyectadas desde PHP
        const datosOriginales = {
            nombre_completo: '<?= addslashes($usuarioCompleto['nombre_completo']) ?>',
            email: '<?= addslashes($usuarioCompleto['email']) ?>'
        };
    </script>
    <script src="<?= $basePath ?>/js/mi_perfil/index.js"></script>
</body>
</html>
