<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

// Obtener información completa del usuario
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT u.*, r.nombre as rol_nombre, r.descripcion as rol_descripcion
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    WHERE u.id = ?
");
$stmt->execute([$usuario['id']]);
$usuarioCompleto = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si es superadmin
$esSuperAdmin = isset($usuarioCompleto['es_superadmin']) && $usuarioCompleto['es_superadmin'] == 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            color: #2c3e50;
            padding-top: 80px;
        }

        /* Header estándar */
        .top-nav {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .nav-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            gap: 8px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            color: #5a6c7d;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: #f0f2f5;
            color: #667eea;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .nav-link i {
            font-size: 16px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #e5e7eb;
        }

        .logout-link {
            padding: 8px 12px !important;
            color: #dc2626 !important;
        }

        .logout-link:hover {
            background: #fee2e2 !important;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero-section h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            flex-shrink: 0;
        }

        .profile-info h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .profile-info .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .role-badge.superadmin {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }

        .role-badge.instructor {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }

        .role-badge.oficinista {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .role-badge.visitante {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .section-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
            color: #2c3e50;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background: white;
        }

        .form-group input:disabled {
            background: #e9ecef;
            color: #95a5a6;
            cursor: not-allowed;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #95a5a6;
            font-size: 0.85rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #7f8c8d;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #2c3e50;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
        }

        .password-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
        }

        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .password-requirements h4 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
        }

        .password-requirements li {
            padding: 5px 0;
            color: #7f8c8d;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements li i {
            color: #e74c3c;
        }

        .password-requirements li.valid i {
            color: #27ae60;
        }

        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 25px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 20px;
            background: transparent;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
        }

        .tab-btn:hover {
            color: #2c3e50;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 10px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            flex-shrink: 0;
        }

        .activity-info {
            flex: 1;
        }

        .activity-info h4 {
            margin-bottom: 3px;
            font-size: 0.95rem;
            color: #2c3e50;
        }

        .activity-info p {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin: 0;
        }

        .activity-time {
            color: #95a5a6;
            font-size: 0.8rem;
        }

        /* Modal de contraseña */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            color: #667eea;
        }

        .modal-close {
            background: none;
            border: none;
            color: #95a5a6;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        .input-group {
            position: relative;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
        }

        .input-group .toggle-password:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header Estándar -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <?php if (puede('estudiantes', 'ver')): ?>
            <li><a href="estudiantes.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <?php endif; ?>
            <?php if (puede('plantillas', 'ver') || esAdmin()): ?>
            <li><a href="admin_fuentes.php" class="nav-link"><i class="fas fa-font"></i> Fuentes</a></li>
            <?php endif; ?>
            <li><a href="verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <?php if (puede('usuarios', 'ver')): ?>
            <li><a href="usuarios.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <?php endif; ?>
            <li class="nav-user">
                <a href="mi_perfil.php" class="nav-link active" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

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
        // Variables globales
        const datosOriginales = {
            nombre_completo: '<?= addslashes($usuarioCompleto['nombre_completo']) ?>',
            email: '<?= addslashes($usuarioCompleto['email']) ?>'
        };

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
                const response = await fetch('api_perfil.php', {
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
                const response = await fetch('api_perfil.php', {
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
                const response = await fetch('api_perfil.php?action=actividad');
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
            if (diff < 3600000) return `Hace ${Math.floor(diff/60000)} min`;
            if (diff < 86400000) return `Hace ${Math.floor(diff/3600000)} horas`;
            if (diff < 604800000) return `Hace ${Math.floor(diff/86400000)} días`;
            
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    </script>
</body>
</html>
