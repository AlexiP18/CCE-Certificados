<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Categoría | CCE Certificados</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/categorias/config.css">
    
    <!-- Cargar estilos de fuentes personalizadas -->
    <style>
        <?php foreach ($fontsList as $index => $font): 
            $formato = $font['tipo'] === 'ttf' ? 'truetype' : ($font['tipo'] === 'otf' ? 'opentype' : $font['tipo']);
            $fontId = $font['id'] ?? $index + 1;
        ?>
        @font-face {
            font-family: 'CustomFont<?= $fontId ?>';
            src: url('<?= $fontsPath . htmlspecialchars($font['archivo']) ?>') format('<?= $formato ?>');
            font-display: swap;
        }
        <?php endforeach; ?>
    </style>
</head>
<body>

    <!-- Header simplificado -->
    <header class="main-header">
        <div class="header-content">
            <div class="logo-container">
                <img src="<?= BASE_URL ?>/../assets/logos/logo-cce.png" alt="CCE Logo" class="logo">
                <div class="brand-name">
                    <h1>CCE Certificados</h1>
                    <p>Sistema de Gestión</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($_SESSION['usuario_rol'] ?? 'Admin'); ?></span>
                    </div>
                </div>
                <!-- Botones de acción rápida -->
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-icon" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?= BASE_URL ?>/dashboard/index.php">
                            <i class="fas fa-home"></i> <span>Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/estudiantes/index.php">
                            <i class="fas fa-user-graduate"></i> <span>Estudiantes</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/grupos/config.php?id=<?= $grupoId ?>">
                             <i class="fas fa-layer-group"></i> <span>Volver a Grupo</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class="fas fa-cog"></i> <span>Configurar Categoría</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> CCE Certificados</p>
            </div>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            
            <div class="grupo-header">
                <div class="grupo-icon-large" id="CategoriaIcon" style="background-color: #3498db; color: white;">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="grupo-info">
                    <h2 id="CategoriaNombre">Cargando...</h2>
                    <p id="CategoriaDescripcion">Cargando detalles de la categoría...</p>
                    <div class="grupo-padre-link">
                        <i class="fas fa-level-up-alt"></i> Grupo: <span id="GrupoPadreNombre">-</span>
                        <a href="<?= BASE_URL ?>/grupos/config.php?id=<?= $grupoId ?>" id="linkConfigGrupo" style="margin-left: 10px; font-size: 12px;">
                            <i class="fas fa-cog"></i> Ir a configuración del grupo
                        </a>
                    </div>
                </div>
                <div style="margin-left: auto;">
                    <a href="javascript:history.back()" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Toggle para usar plantilla del grupo -->
            <div class="toggle-plantilla-grupo">
                <label class="toggle-switch">
                    <input type="checkbox" id="usarPlantillaGrupo" checked>
                    <span class="toggle-slider"></span>
                </label>
                <div class="toggle-info">
                    <strong>Usar plantilla del Grupo</strong>
                    <small>Si activas esta opción, se usará el diseño, fondo y configuración del grupo padre.</small>
                </div>
            </div>

            <!-- Mensaje cuando se usa plantilla del grupo -->
            <div id="grupoPlantillaInfo" class="grupo-plantilla-info" style="display: none;">
                <i class="fas fa-folder-open"></i>
                <p>Esta categoría está configurada para heredar la plantilla del grupo.</p>
                <p style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/grupos/config.php?id=<?= $grupoId ?>" id="linkConfigGrupo2">
                        Haz clic aquí para editar la configuración del grupo
                    </a>
                </p>
            </div>

            <!-- Contenido de configuración propia -->
            <div id="configContent">
                
                <!-- Slider de Plantillas -->
                <div class="templates-slider-container">
                    <div class="templates-slider-header">
                        <h4><i class="fas fa-images"></i> Plantillas de Fondo</h4>
                        <span class="template-count" id="templateCount">0/6</span>
                    </div>
                    
                    <div class="templates-slider-wrapper">
                        <button class="slider-arrow prev" id="sliderPrev" disabled><i class="fas fa-chevron-left"></i></button>
                        
                        <div class="templates-slider" id="templatesSlider">
                            <!-- Los items se cargarán dinámicamente -->
                            <div class="template-item loading-skeleton"></div>
                        </div>
                        
                        <button class="slider-arrow next" id="sliderNext" disabled><i class="fas fa-chevron-right"></i></button>
                    </div>
                    
                    <input type="file" id="plantillaUpload" accept="image/*" style="display: none;">
                </div>

                <div class="config-grid">
                    <!-- Columna Izquierda: Lienzo -->
                    <div>
                        <div class="card">
                            <div class="canvas-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0; border: none; padding: 0;">Diseño del Certificado</h3>
                                <div class="canvas-actions">
                                    <button type="button" class="btn-secondary btn-sm" onclick="updateMarkerPositions()">
                                        <i class="fas fa-sync"></i> Recargar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="canvas-container" id="canvasContainer">
                                <img id="canvas" src="<?= BASE_URL ?>/../assets/certificates/default.png" alt="Lienzo del certificado">
                                
                                <div id="nombreMarker" class="draggable nombre-marker" data-type="nombre" style="left: 400px; top: 300px;">
                                    <span id="nombreMarkerText">Nombre del Estudiante</span>
                                </div>
                                <div id="razonMarker" class="draggable razon-marker" data-type="razon" style="left: 400px; top: 360px;">
                                    <span id="razonMarkerText">Por haber completado satisfactoriamente...</span>
                                    <div class="resize-handle" id="razonResizeHandle"></div>
                                </div>
                                <div id="fechaMarker" class="draggable fecha-marker" data-type="fecha" style="left: 400px; top: 420px;">
                                    <span id="fechaMarkerText">20 de Octubre de 2023</span>
                                </div>
                                <div id="qrMarker" class="draggable qr-marker" data-type="qr" style="left: 920px; top: 419px;">
                                    <i class="fas fa-qrcode" style="font-size: 24px;"></i>
                                </div>
                                <div id="firmaMarker" class="draggable firma-marker" data-type="firma" style="left: 800px; top: 850px;">
                                    <span>Firma</span>
                                </div>
                                <div id="destacadoMarker" class="draggable destacado-marker" data-type="destacado" style="left: 50px; top: 50px; display: none;">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            
                            <div class="coordinates-info">
                                <div class="coord-item" id="coordNombreContainer">
                                    <span class="coord-label">Nombre</span>
                                    <span class="coord-value" id="coordNombre">X: 0, Y: 0</span>
                                </div>
                                <div class="coord-item" id="coordRazonContainer">
                                    <span class="coord-label">Razón</span>
                                    <span class="coord-value" id="coordRazon">X: 0, Y: 0</span>
                                </div>
                                <div class="coord-item" id="coordFechaContainer">
                                    <span class="coord-label">Fecha</span>
                                    <span class="coord-value" id="coordFecha">X: 0, Y: 0</span>
                                </div>
                                <div class="coord-item" id="coordQrContainer">
                                    <span class="coord-label">QR</span>
                                    <span class="coord-value" id="coordQr">X: 0, Y: 0</span>
                                </div>
                                <div class="coord-item" id="coordFirmaContainer">
                                    <span class="coord-label">Firma</span>
                                    <span class="coord-value" id="coordFirma">X: 0, Y: 0</span>
                                </div>
                                <div class="coord-item" id="coordDestacadoContainer" style="display: none;">
                                    <span class="coord-label">Destacado</span>
                                    <span class="coord-value" id="coordDestacado">X: 0, Y: 0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Variables -->
                        <div class="card" style="margin-top: 20px;">
                            <h3>Variables Habilitadas</h3>
                            <div class="checkbox-group" style="flex-wrap: wrap;">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_nombre" value="nombre" checked>
                                    <label for="var_nombre">Nombre del Estudiante</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_razon" value="razon" checked>
                                    <label for="var_razon">Razón/Descripción</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_fecha" value="fecha" checked>
                                    <label for="var_fecha">Fecha</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_qr" value="qr" checked>
                                    <label for="var_qr">Código QR</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_firma" value="firma" checked>
                                    <label for="var_firma">Firma Digital</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_destacado" value="destacado">
                                    <label for="var_destacado">Elemento Destacado</label>
                                </div>
                            </div>
                            
                            <div class="variables-help">
                                <strong><i class="fas fa-info-circle"></i> Variables disponibles para el texto de Razón:</strong>
                                <div class="variables-list">
                                    <code>{nombre}</code> <span>Nombre del estudiante</span>
                                    <code>{grupo}</code> <span>Nombre del grupo/curso</span>
                                    <code>{categoria}</code> <span>Nombre de la categoría</span>
                                    <code>{fecha}</code> <span>Fecha de certificación</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Configuración -->
                    <div>
                        <div class="card">
                            <h3>Configuración de Elementos</h3>
                            
                            <form id="configForm" action="config_grupo_save.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="categoria_id" id="categoria_id" value="">
                                <input type="hidden" name="variables_habilitadas" id="variables_habilitadas" value="">
                                <input type="hidden" name="usar_plantilla_propia" id="usar_plantilla_propia" value="1">
                                
                                <!-- Tabs de navegación -->
                                <div class="variable-tabs">
                                    <div class="variable-tab active" data-tab="general" onclick="switchTab('general')">
                                        <i class="fas fa-sliders-h"></i> General
                                    </div>
                                    <div class="variable-tab" data-tab="texto" onclick="switchTab('texto')">
                                        <i class="fas fa-font"></i> Texto
                                    </div>
                                    <div class="variable-tab" data-tab="firma" onclick="switchTab('firma')">
                                        <i class="fas fa-signature"></i> Firma
                                    </div>
                                    <div class="variable-tab" data-tab="destacado" onclick="switchTab('destacado')">
                                        <i class="fas fa-star"></i> Destacado
                                    </div>
                                </div>
                                
                                <!-- Contenido Tab General -->
                                <div id="tab-general" class="variable-tab-content active">
                                    <!-- Inputs ocultos de posiciones -->
                                    <input type="hidden" name="posicion_nombre_x" id="posicion_nombre_x">
                                    <input type="hidden" name="posicion_nombre_y" id="posicion_nombre_y">
                                    <input type="hidden" name="posicion_razon_x" id="posicion_razon_x">
                                    <input type="hidden" name="posicion_razon_y" id="posicion_razon_y">
                                    <input type="hidden" name="posicion_fecha_x" id="posicion_fecha_x">
                                    <input type="hidden" name="posicion_fecha_y" id="posicion_fecha_y">
                                    <input type="hidden" name="posicion_qr_x" id="posicion_qr_x">
                                    <input type="hidden" name="posicion_qr_y" id="posicion_qr_y">
                                    <input type="hidden" name="posicion_firma_x" id="posicion_firma_x">
                                    <input type="hidden" name="posicion_firma_y" id="posicion_firma_y">
                                    <input type="hidden" name="posicion_destacado_x" id="posicion_destacado_x">
                                    <input type="hidden" name="posicion_destacado_y" id="posicion_destacado_y">
                                    <input type="hidden" name="ancho_razon" id="ancho_razon">

                                    <div class="form-group">
                                        <label>Tamaño del QR (px)</label>
                                        <input type="range" class="form-range" id="tamanio_qr" name="tamanio_qr" min="50" max="500" value="200">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Fecha Específica (Opcional)</label>
                                        <div class="checkbox-item" style="margin-bottom: 10px;">
                                            <input type="checkbox" id="usar_fecha_especifica" name="usar_fecha_especifica">
                                            <label for="usar_fecha_especifica">Usar fecha fija para todos</label>
                                        </div>
                                        <div id="fecha_especifica_group" style="display: none;">
                                            <input type="date" name="fecha_especifica" id="fecha_especifica" class="form-control">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Formato de Fecha</label>
                                        <select name="formato_fecha" id="formato_fecha" class="form-control">
                                            <option value="d de F de Y">20 de Octubre de 2023</option>
                                            <option value="d/m/Y">20/10/2023</option>
                                            <option value="Y-m-d">2023-10-20</option>
                                            <option value="F Y">Octubre 2023</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Contenido Tab Texto -->
                                <div id="tab-texto" class="variable-tab-content">
                                    <div class="form-group">
                                        <label>Nombre del Estudiante</label>
                                        <input type="hidden" name="formato_nombre" id="formato_nombre" value="mayusculas">
                                        <div class="formato-nombre-selector">
                                            <div class="formato-btn active" data-formato="mayusculas" onclick="setFormatoNombre('mayusculas')">
                                                <i class="fas fa-font" style="text-transform: uppercase;"></i>
                                                <span>MAYÚS</span>
                                            </div>
                                            <div class="formato-btn" data-formato="capitalizar" onclick="setFormatoNombre('capitalizar')">
                                                <i class="fas fa-font" style="text-transform: capitalize;"></i>
                                                <span>Capital</span>
                                            </div>
                                            <div class="formato-btn" data-formato="original" onclick="setFormatoNombre('original')">
                                                <i class="fas fa-font"></i>
                                                <span>Original</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Fuente del Nombre</label>
                                        <select name="fuente_nombre" id="fuente_nombre" class="form-control font-select">
                                            <?php foreach ($fontsList as $font): ?>
                                            <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: '<?= htmlspecialchars($font['nombre']) ?>', sans-serif;">
                                                <?= htmlspecialchars($font['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Tamaño Fuente Nombre</label>
                                        <input type="number" name="tamanio_fuente" id="tamanio_fuente" class="form-control" value="50">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Color Nombre</label>
                                        <div class="color-preview">
                                            <input type="color" id="color_texto" value="#000000">
                                            <input type="text" id="color_texto_hex" name="color_texto" value="#000000" class="form-control" style="width: 100px;">
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group">
                                        <label>Texto de Razón</label>
                                        <textarea name="razon_defecto" id="razon_defecto" class="form-control" oninput="updateRazonMarkerText()"></textarea>
                                        <small>Variables: {nombre}, {grupo}, {categoria}, {fecha}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Fuente Razón</label>
                                        <select name="fuente_razon" id="fuente_razon" class="form-control font-select">
                                            <?php foreach ($fontsList as $font): ?>
                                            <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: '<?= htmlspecialchars($font['nombre']) ?>', sans-serif;">
                                                <?= htmlspecialchars($font['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Alineación Razón</label>
                                        <input type="hidden" name="alineacion_razon" id="alineacion_razon" value="justified">
                                        <div class="alineacion-selector">
                                            <div class="alineacion-btn" data-align="left" onclick="setAlineacionRazon('left')" title="Izquierda">
                                                <i class="fas fa-align-left"></i>
                                            </div>
                                            <div class="alineacion-btn" data-align="center" onclick="setAlineacionRazon('center')" title="Centro">
                                                <i class="fas fa-align-center"></i>
                                            </div>
                                            <div class="alineacion-btn" data-align="right" onclick="setAlineacionRazon('right')" title="Derecha">
                                                <i class="fas fa-align-right"></i>
                                            </div>
                                            <div class="alineacion-btn active" data-align="justified" onclick="setAlineacionRazon('justified')" title="Justificado">
                                                <i class="fas fa-align-justify"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Tamaño Fuente Razón</label>
                                        <input type="number" name="tamanio_razon" id="tamanio_razon" class="form-control" value="24">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Ancho del Bloque (px)</label>
                                        <input type="number" id="ancho_razon_input" class="form-control" value="600">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Color Razón</label>
                                        <div class="color-preview">
                                            <input type="color" id="color_razon" value="#333333">
                                            <input type="text" id="color_razon_hex" name="color_razon" value="#333333" class="form-control" style="width: 100px;">
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group">
                                        <label>Fuente Fecha</label>
                                        <select name="fuente_fecha" id="fuente_fecha" class="form-control font-select">
                                            <?php foreach ($fontsList as $font): ?>
                                            <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: '<?= htmlspecialchars($font['nombre']) ?>', sans-serif;">
                                                <?= htmlspecialchars($font['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Tamaño Fuente Fecha</label>
                                        <input type="number" name="tamanio_fecha" id="tamanio_fecha" class="form-control" value="20">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Color Fecha</label>
                                        <div class="color-preview">
                                            <input type="color" id="color_fecha" value="#333333">
                                            <input type="text" id="color_fecha_hex" name="color_fecha" value="#333333" class="form-control" style="width: 100px;">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contenido Tab Firma -->
                                <div id="tab-firma" class="variable-tab-content">
                                    <div class="form-group">
                                        <label>Tamaño de Firma (px)</label>
                                        <input type="range" class="form-range" id="tamanio_firma" name="tamanio_firma" min="50" max="400" value="200">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Imagen de Firma</label>
                                        <div class="file-upload-area" id="uploadFirmaArea">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <p>Arrastra tu firma aquí o haz clic</p>
                                            <small>PNG con fondo transparente recomendado</small>
                                        </div>
                                        <input type="file" name="firma_imagen" id="firma_imagen" accept="image/png, image/jpeg" style="display: none;">
                                        <div id="currentFirma" class="firma-preview-container"></div>
                                    </div>
                                </div>
                                
                                <!-- Contenido Tab Destacado -->
                                <div id="tab-destacado" class="variable-tab-content">
                                    <div class="info-card">
                                        <i class="fas fa-lightbulb"></i>
                                        <p>Usa este elemento para agregar un sello, sticker o logo adicional al certificado.</p>
                                    </div>
                                
                                    <div class="form-group">
                                        <label>Tipo de Elemento</label>
                                        <select name="destacado_tipo" id="destacado_tipo" class="form-control" onchange="toggleDestacadoOptions()">
                                            <option value="icono">Sticker / Icono</option>
                                            <option value="imagen">Imagen Personalizada</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" id="destacado_icono_group">
                                        <label>Seleccionar Sticker</label>
                                        <div class="stickers-grid">
                                            <label class="sticker-option">
                                                <input type="radio" name="destacado_icono" value="estrella" checked>
                                                <img src="<?= BASE_URL ?>/../assets/stickers/estrella.png" alt="Estrella">
                                            </label>
                                            <label class="sticker-option">
                                                <input type="radio" name="destacado_icono" value="medalla">
                                                <img src="<?= BASE_URL ?>/../assets/stickers/medalla.png" alt="Medalla">
                                            </label>
                                            <label class="sticker-option">
                                                <input type="radio" name="destacado_icono" value="cinta">
                                                <img src="<?= BASE_URL ?>/../assets/stickers/cinta.png" alt="Cinta">
                                            </label>
                                            <label class="sticker-option">
                                                <input type="radio" name="destacado_icono" value="verificado">
                                                <img src="<?= BASE_URL ?>/../assets/stickers/verificado.png" alt="Verificado">
                                            </label>
                                            <!-- Más stickers aquí -->
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" id="destacado_imagen_group" style="display: none;">
                                        <label>Subir Imagen</label>
                                        <input type="file" name="destacado_imagen" id="destacado_imagen_input" class="form-control" accept="image/*">
                                        <div id="currentDestacadoImg" style="margin-top: 10px; text-align: center;"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Tamaño (px)</label>
                                        <input type="range" class="form-range" id="tamanio_destacado" name="tamanio_destacado" min="20" max="300" value="100">
                                    </div>
                                </div>
                                
                                <div class="btn-group">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                    <button type="button" class="btn-secondary" onclick="previewCertificate()">
                                        <i class="fas fa-eye"></i> Previsualizar
                                    </button>
                                    <button type="button" class="btn-danger" onclick="resetDefaults()">
                                        <i class="fas fa-undo"></i> Restaurar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </main>
    </div>

    <!-- Modal de Previsualización -->
    <div class="preview-modal" id="previewModal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h3>Vista Previa del Certificado</h3>
                <button class="preview-modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="preview-modal-body" id="previewBody">
                <!-- Aquí se cargará la imagen -->
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/categorias/config.js"></script>
</body>
</html>
