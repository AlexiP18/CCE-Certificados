<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($siteConfig['site_name']) ?></title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/admin_fuentes/index.css">
    <style>
        :root {
            --site-primary: <?= htmlspecialchars($siteConfig['primary_color']) ?>;
            --site-secondary: <?= htmlspecialchars($siteConfig['secondary_color']) ?>;
        }

        .top-nav .nav-link.active,
        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--site-primary), var(--site-secondary)) !important;
        }

        .nav-logo-image {
            width: 34px;
            height: 34px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
            padding: 2px;
        }
    </style>
</head>
<body>
    <!-- Navegación Superior -->
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
            <li><a href="<?= $basePath ?>/admin/fuentes.php" class="nav-link active"><i class="fas fa-font"></i> Fuentes</a></li>
            <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <?php if (puede('usuarios', 'ver')): ?>
            <li><a href="<?= $basePath ?>/usuarios/index.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <?php endif; ?>
            <?php if (esAdmin()): ?>
            <li><a href="<?= $basePath ?>/configuracion/index.php" class="nav-link"><i class="fas fa-sliders-h"></i> Configuracion</a></li>
            <?php endif; ?>
            <li class="nav-user">
                <a href="<?= $basePath ?>/perfil/dashboard/index.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h1 class="page-title">
                    <i class="fas fa-font"></i> <?= $pageTitle ?>
                </h1>
                <button class="btn-primary-gradient" onclick="openUploadModal()">
                    <i class="fas fa-cloud-upload-alt"></i> Subir Nueva Fuente
                </button>
            </div>
        </div>
        
        <!-- Filtros por categoría -->
        <div class="category-filters">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th-large"></i> Todas
            </button>
            <button class="category-btn" data-category="sans-serif">
                Sans Serif
            </button>
            <button class="category-btn" data-category="serif">
                Serif
            </button>
            <button class="category-btn" data-category="display">
                Display
            </button>
            <button class="category-btn" data-category="handwriting">
                Manuscritas
            </button>
            <button class="category-btn" data-category="monospace">
                Monoespaciadas
            </button>
        </div>
        
        <!-- Contenedor de fuentes -->
        <div id="fonts-container">
            <div class="loading-state">
                <div class="spinner"></div>
                <p style="color: #6b7280;">Cargando fuentes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Subida -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Subir Nueva Fuente</h3>
                <button class="modal-close" onclick="closeModal('uploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <label class="upload-zone" id="dropZone">
                        <input type="file" name="archivo" id="fileInput" accept=".ttf,.otf,.woff,.woff2" hidden>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5 id="dropZoneText">Arrastra un archivo aquí o haz clic para seleccionar</h5>
                        <p>Formatos: TTF, OTF, WOFF, WOFF2</p>
                    </label>
                    
                    <div class="form-group">
                        <label for="fontName">Nombre de la fuente *</label>
                        <input type="text" id="fontName" name="nombre" required placeholder="Ej: Arial Bold">
                        <small>Este nombre se mostrará en los selectores de fuente</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fontCategory">Categoría</label>
                        <select id="fontCategory" name="categoria">
                            <option value="sans-serif">Sans Serif</option>
                            <option value="serif">Serif</option>
                            <option value="display">Display</option>
                            <option value="handwriting">Manuscritas</option>
                            <option value="monospace">Monoespaciadas</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnUpload" onclick="uploadFont()">
                    <i class="fas fa-upload"></i> Subir Fuente
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edición -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Fuente</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editFontId" name="id">
                    
                    <div class="form-group">
                        <label for="editFontName">Nombre de la fuente</label>
                        <input type="text" id="editFontName" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFontCategory">Categoría</label>
                        <select id="editFontCategory" name="categoria">
                            <option value="sans-serif">Sans Serif</option>
                            <option value="serif">Serif</option>
                            <option value="display">Display</option>
                            <option value="handwriting">Manuscritas</option>
                            <option value="monospace">Monoespaciadas</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="editFontActive" name="activo" value="1" checked>
                        <label for="editFontActive">Fuente activa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit" onclick="saveEdit()">
                    <i class="fas fa-check"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <h3><i class="fas fa-trash-alt"></i> Eliminar Fuente</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la fuente <strong id="deleteFontName"></strong>?</p>
                <p style="color: #dc2626; font-size: 14px; margin-bottom: 0;">
                    <i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.
                </p>
                <input type="hidden" id="deleteFontId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete" onclick="deleteFont()">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Variables globales
        const basePath = '<?= $basePath ?>';
    </script>
    <script src="<?= $jsPath ?>/admin_fuentes/index.js"></script>
</body>
</html>
