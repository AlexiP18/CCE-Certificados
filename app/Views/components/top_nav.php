<?php
$activeNav = $activeNav ?? '';
$usuarioNav = $usuario ?? null;

if (!is_array($usuarioNav) && class_exists('Auth')) {
    $usuarioNav = Auth::user();
}

$isAuthenticated = is_array($usuarioNav) && !empty($usuarioNav['id']);
$canEstudiantes = $isAuthenticated && (!function_exists('puede') || puede('estudiantes', 'ver'));
$canFuentes = $isAuthenticated && ((!function_exists('puede') || puede('plantillas', 'ver')) || (function_exists('esAdmin') && esAdmin()));
$canUsuarios = $isAuthenticated && (!function_exists('puede') || puede('usuarios', 'ver'));
$canConfiguracion = $isAuthenticated && (!function_exists('esAdmin') || esAdmin());
$userDisplayName = trim((string)($usuarioNav['nombre_completo'] ?? ($usuarioNav['nombre'] ?? 'Usuario')));
$logoNavUrl = $basePath . '/assets/Logo%20CCE%20Rosa.svg';
?>
<nav class="top-nav">
    <div class="nav-logo">
        <img src="<?= htmlspecialchars($logoNavUrl) ?>" alt="Logo CCE Rosa" class="nav-logo-image" onerror="this.style.display='none'">
        <span><?= htmlspecialchars($siteConfig['site_name']) ?></span>
    </div>

    <ul class="nav-menu">
        <?php if ($isAuthenticated): ?>
        <li><a href="<?= $basePath ?>/dashboard/index.php" class="nav-link <?= $activeNav === 'inicio' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a></li>
        <?php if ($canEstudiantes): ?>
        <li><a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link <?= $activeNav === 'estudiantes' ? 'active' : '' ?>"><i class="fas fa-users"></i> Estudiantes</a></li>
        <?php endif; ?>
        <?php if ($canFuentes): ?>
        <li><a href="<?= $basePath ?>/admin/fuentes.php" class="nav-link <?= $activeNav === 'fuentes' ? 'active' : '' ?>"><i class="fas fa-font"></i> Fuentes</a></li>
        <?php endif; ?>
        <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link <?= $activeNav === 'verify' ? 'active' : '' ?>"><i class="fas fa-search"></i> Verificar</a></li>
        <?php if ($canUsuarios): ?>
        <li><a href="<?= $basePath ?>/usuarios/index.php" class="nav-link <?= $activeNav === 'usuarios' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Usuarios</a></li>
        <?php endif; ?>
        <?php if ($canConfiguracion): ?>
        <li><a href="<?= $basePath ?>/configuracion/index.php" class="nav-link <?= $activeNav === 'configuracion' ? 'active' : '' ?>"><i class="fas fa-sliders-h"></i> Configuracion</a></li>
        <?php endif; ?>
        <li class="nav-user">
            <a href="<?= $basePath ?>/perfil/index.php" class="nav-link <?= $activeNav === 'perfil' ? 'active' : '' ?>" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($userDisplayName) ?></a>
            <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
        </li>
        <?php else: ?>
        <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link <?= $activeNav === 'verify' ? 'active' : '' ?>"><i class="fas fa-search"></i> Verificar</a></li>
        <li><a href="<?= $basePath ?>/auth/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Ingresar</a></li>
        <?php endif; ?>
    </ul>
</nav>
