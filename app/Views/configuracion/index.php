<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion General - <?= htmlspecialchars($siteConfig['site_name']) ?></title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/configuracion/index.css">
    <style>
        :root {
            --site-primary: <?= htmlspecialchars($siteConfig['primary_color']) ?>;
            --site-secondary: <?= htmlspecialchars($siteConfig['secondary_color']) ?>;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-logo">
            <?php if (!empty($siteConfig['logo_nav_url'])): ?>
                <img src="<?= htmlspecialchars($siteConfig['logo_nav_url']) ?>" alt="Logo" class="nav-logo-image" onerror="this.style.display='none'">
            <?php endif; ?>
            <i class="fas fa-graduation-cap"></i>
            <span><?= htmlspecialchars($siteConfig['site_name']) ?></span>
        </div>
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>/dashboard/index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <li><a href="<?= $basePath ?>/usuarios/index.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <li><a href="<?= $basePath ?>/configuracion/index.php" class="nav-link active"><i class="fas fa-sliders-h"></i> Configuracion</a></li>
            <li class="nav-user">
                <a href="<?= $basePath ?>/perfil/dashboard/index.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sesion"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <div class="hero-section">
            <h1><i class="fas fa-sliders-h"></i> Configuracion General</h1>
            <p>Personaliza nombre del sistema, identidad institucional, colores y logos</p>
        </div>

        <div id="alertBox" class="alert-box" style="display:none;"></div>

        <form id="configForm" class="config-card" enctype="multipart/form-data">
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="site_name"><i class="fas fa-heading"></i> Nombre del Sistema *</label>
                    <input type="text" id="site_name" name="site_name" required>
                </div>
                <div class="form-group">
                    <label for="institution_name"><i class="fas fa-building"></i> Nombre Institucional *</label>
                    <input type="text" id="institution_name" name="institution_name" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="primary_color"><i class="fas fa-palette"></i> Color Primario</label>
                    <input type="color" id="primary_color" name="primary_color">
                </div>
                <div class="form-group">
                    <label for="secondary_color"><i class="fas fa-fill-drip"></i> Color Secundario</label>
                    <input type="color" id="secondary_color" name="secondary_color">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="logo_nav_file"><i class="fas fa-image"></i> Logo del Menu</label>
                    <input type="file" id="logo_nav_file" name="logo_nav_file" accept=".png,.jpg,.jpeg,.webp,.svg">
                    <small>Se muestra junto al nombre del sistema.</small>
                    <img id="preview_logo_nav" class="logo-preview" alt="Preview logo menu">
                </div>
                <div class="form-group">
                    <label for="logo_header_file"><i class="fas fa-landmark"></i> Logo Institucional</label>
                    <input type="file" id="logo_header_file" name="logo_header_file" accept=".png,.jpg,.jpeg,.webp,.svg">
                    <small>Se usa en cabeceras institucionales.</small>
                    <img id="preview_logo_header" class="logo-preview" alt="Preview logo institucional">
                </div>
            </div>

            <div class="form-group">
                <label for="favicon_file"><i class="fas fa-globe"></i> Favicon del Sistema</label>
                <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,.jpg,.jpeg,.webp,.svg">
                <small>Se muestra en la pestaña del navegador. Recomendado: 32x32 o 48x48.</small>
                <img id="preview_favicon" class="logo-preview" alt="Preview favicon" style="max-width: 64px; max-height: 64px;">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Configuracion
                </button>
            </div>
        </form>
    </div>

    <script>
        window.basePath = <?= json_encode($basePath) ?>;
        window.initialSiteSettings = <?= json_encode($siteConfig) ?>;
    </script>
    <script src="<?= $jsPath ?>/configuracion/index.js"></script>
</body>
</html>
