<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Plantilla de Categoría | CCE</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $cssPath ?>/grupos/config_new.css?v=<?= time() ?>">
    <style>
        /* Estilos dinámicos para fuentes personalizadas */
        <?php foreach ($fontsList as $index => $font): 
            $fontId = $font['id'] ?? $index + 1;
            $fontPath = $assetsPath . '/fonts/' . $font['archivo']; 
            $ext = strtolower(pathinfo($font['archivo'], PATHINFO_EXTENSION));
            $format = ($ext == 'otf') ? 'opentype' : 'truetype';
            $fontUrl = htmlspecialchars($fontPath) . '?v=' . time();
        ?>
        @font-face {
            font-family: 'CustomFont<?= $fontId ?>';
            src: url('<?= $fontUrl ?>') format('<?= $format ?>');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        <?php endforeach; ?>

        /* Toggle para usar plantilla del grupo */
        .toggle-plantilla-grupo {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .toggle-plantilla-grupo.active-grupo {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
        }
        .toggle-plantilla-grupo .toggle-switch-custom {
            position: relative;
            width: 50px;
            height: 26px;
            flex-shrink: 0;
        }
        .toggle-plantilla-grupo .toggle-switch-custom input { opacity: 0; width: 0; height: 0; }
        .toggle-plantilla-grupo .toggle-slider-custom {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: 0.3s; border-radius: 26px;
        }
        .toggle-plantilla-grupo .toggle-slider-custom:before {
            position: absolute; content: ""; height: 20px; width: 20px;
            left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%;
        }
        .toggle-plantilla-grupo .toggle-switch-custom input:checked + .toggle-slider-custom {
            background-color: #27ae60;
        }
        .toggle-plantilla-grupo .toggle-switch-custom input:checked + .toggle-slider-custom:before {
            transform: translateX(24px);
        }
        .toggle-info { flex: 1; }
        .toggle-info strong { display: block; color: #2c3e50; margin-bottom: 3px; font-size: 14px; }
        .toggle-info small { color: #7f8c8d; font-size: 12px; }

        /* Grupo plantilla info panel */
        .grupo-plantilla-info {
            padding: 30px;
            background: linear-gradient(135deg, #e8f4f8 0%, #d4edda 100%);
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }
        .grupo-plantilla-info i.fa-folder-open { font-size: 48px; color: #3498db; margin-bottom: 15px; display: block; }
        .grupo-plantilla-info p { color: #2c3e50; margin: 0; font-size: 16px; }
        .grupo-plantilla-info a { color: #3498db; text-decoration: none; font-weight: 500; }
        .grupo-plantilla-info a:hover { text-decoration: underline; }

        /* Ocultar contenido cuando hereda del grupo */
        #configContent.hidden { display: none !important; }

        /* Category breadcrumb */
        .category-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .category-breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .category-breadcrumb a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <script>
        // Global variables for initialization
        window.basePath = '<?= $basePath ?>/'; 
        window.serverCategoriaId = <?= json_encode($categoria['id'] ?? null) ?>;
        window.serverGrupoId = <?= json_encode($categoria['grupo_id'] ?? null) ?>;
        window.entityType = 'categoria';
        
        // Font map for JS
        window.fontMap = {
            <?php foreach ($fontsList as $index => $font): 
                $fontId = $font['id'] ?? $index + 1;
            ?>
            '<?= htmlspecialchars($font['nombre_archivo']) ?>': 'CustomFont<?= $fontId ?>',
            <?php endforeach; ?>
        };

        // Assets Paths
        window.assetsPath = '<?= $assetsPath ?>';
    </script>
    
    <div class="container config-container">
        <header class="config-header">
            <div class="header-titles">
                <img src="<?= $basePath ?>/assets/logos/logo-cce.png" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
                <div>
                    <h1>Configurador de Certificados</h1>
                    <div class="category-breadcrumb">
                        <a href="<?= $basePath ?>/grupos/config.php?id=<?= $categoria['grupo_id'] ?? '' ?>">
                            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($grupo['nombre'] ?? 'Grupo') ?>
                        </a>
                        <i class="fas fa-chevron-right"></i>
                        <span><i class="fas fa-folder"></i> <?= htmlspecialchars($categoria['nombre'] ?? 'Categoría') ?></span>
                    </div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="<?= $basePath ?>/grupos/config.php?id=<?= $categoria['grupo_id'] ?? '' ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver al Grupo</a>
            </div>
        </header>

        <!-- Toggle: Usar plantilla del grupo -->
        <div class="toggle-plantilla-grupo" id="togglePlantillaGrupo">
            <label class="toggle-switch-custom">
                <input type="checkbox" id="usarPlantillaGrupo">
                <span class="toggle-slider-custom"></span>
            </label>
            <div class="toggle-info">
                <strong>Usar plantilla del Grupo</strong>
                <small>Si activas esta opción, se heredará el diseño del grupo padre.</small>
            </div>
        </div>

        <!-- Mensaje cuando hereda del grupo -->
        <div id="grupoPlantillaInfo" class="grupo-plantilla-info" style="display: none;">
            <i class="fas fa-folder-open"></i>
            <p>Esta categoría está configurada para heredar la plantilla del grupo.</p>
            <p style="margin-top: 10px;">
                <a href="<?= $basePath ?>/grupos/config.php?id=<?= $categoria['grupo_id'] ?? '' ?>">
                    <i class="fas fa-cog"></i> Editar configuración del grupo
                </a>
            </p>
        </div>

        <!-- Contenido de configuración propia -->
        <div id="configContent">
            <!-- Template Selector Ribbon -->
            <div class="template-selector-ribbon">
                <div class="ribbon-header" style="margin-bottom: 5px;">
                    <h3><i class="fas fa-images"></i> Plantillas Disponibles</h3>
                    <span class="badge" id="templateCounter">Cargando...</span>
                </div>
                <p style="font-size: 0.85rem; color: #6c757d; margin-top: 0; margin-bottom: 12px;">
                    <i class="fas fa-info-circle" style="color: #17a2b8;"></i> <strong>Resolución recomendada: 1600 x 1131 píxeles</strong> (Formato horizontal A4). Usa este tamaño para que todo encaje perfecto.
                </p>
                <div class="ribbon-slider-wrapper">
                    <button class="slider-btn" id="sliderPrevBtn"><i class="fas fa-chevron-left"></i></button>
                    <div class="ribbon-slider" id="templateSliderContainer">
                        <!-- Templates injected via JS -->
                    </div>
                    <button class="slider-btn" id="sliderNextBtn"><i class="fas fa-chevron-right"></i></button>
                    
                    <div class="ribbon-actions">
                        <button class="btn btn-icon" id="btnUploadTemplate" title="Subir nueva plantilla"><i class="fas fa-upload"></i></button>
                        <input type="file" id="uploadTemplateInput" accept="image/*" style="display: none;">
                    </div>
                </div>
            </div>

            <div class="config-workspace">
                <!-- Left: Interactive Canvas -->
                <div class="workspace-canvas-area" id="canvasArea">
                    
                    <div class="variables-toolbar">
                        <label class="toggle-switch" title="Nombre"><i class="fas fa-user toggle-icon"></i><input type="checkbox" id="toggle_nombre" value="nombre" checked> <span class="slider round"></span></label>
                        <label class="toggle-switch" title="Razón"><i class="fas fa-file-alt toggle-icon"></i><input type="checkbox" id="toggle_razon" value="razon" checked> <span class="slider round"></span></label>
                        <label class="toggle-switch" title="Fecha"><i class="fas fa-calendar toggle-icon"></i><input type="checkbox" id="toggle_fecha" value="fecha" checked> <span class="slider round"></span></label>
                        <label class="toggle-switch" title="QR"><i class="fas fa-qrcode toggle-icon"></i><input type="checkbox" id="toggle_qr" value="qr" checked> <span class="slider round"></span></label>
                        <label class="toggle-switch" title="Firma"><i class="fas fa-signature toggle-icon"></i><input type="checkbox" id="toggle_firma" value="firma" checked> <span class="slider round"></span></label>
                        <label class="toggle-switch" title="Destacado"><i class="fas fa-star toggle-icon"></i><input type="checkbox" id="toggle_destacado" value="destacado"> <span class="slider round"></span></label>
                    </div>

                    <div class="canvas-wrapper" id="canvasWrapper" style="display: none; background: #fff; position: relative;">
                        <img id="templateBaseImage" src="" alt="Template Base" draggable="false" style="display: block; width: 100%; height: auto;">
                        
                        <!-- Dynamic Markers Wrapper -->
                        <div id="markersLayer"></div>
                    </div>

                    <!-- Empty State -->
                    <div class="empty-state-canvas" id="canvasEmptyState" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; margin: 20px 0;">
                        <i class="fas fa-images" style="font-size: 3rem; color: #adb5bd; margin-bottom: 15px;"></i>
                        <h3 style="color: #495057; margin-bottom: 10px;">Aún no tienes plantillas</h3>
                        <p style="color: #6c757d; margin-bottom: 20px; text-align: center; max-width: 400px;">Para comenzar a diseñar el certificado, sube una imagen base para tu plantilla usando el botón de la parte superior derecha.</p>
                    </div>
                    
                    <div class="canvas-footer">
                        <p class="help-text"><i class="fas fa-info-circle"></i> Arrastra los elementos en el lienzo para posicionarlos. Usa la barra lateral para ajustar estilos.</p>
                    </div>
                </div>

                <!-- Right Column: Sidebar Card + Actions Below -->
                <div class="sidebar-column" style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="workspace-sidebar" style="display: flex; flex-direction: column; overflow-y: auto;">
                        <div class="sidebar-tabs" id="sidebarTabs">
                            <!-- Tabs injected via JS -->
                        </div>
                        
                        <div class="sidebar-panels" id="sidebarPanels">
                            <!-- Panels injected via JS -->
                        </div>
                    </div>

                    <!-- Warning Notice -->
                    <div class="preview-notice" style="margin-bottom: 15px; padding: 12px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-exclamation-triangle" style="color: #856404; font-size: 18px;"></i>
                        <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.4;">
                            <strong>Importante:</strong> La previsualización se genera como captura del lienzo (plantilla + variables) para mantener el mismo posicionamiento visual.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="sidebar-actions-outside">
                        <button type="button" class="action-btn action-btn--preview" id="btnPreview">
                            <i class="fas fa-eye"></i>
                            <span>Previsualizar</span>
                        </button>
                        <button type="button" class="action-btn action-btn--save" id="btnSaveConfig">
                            <i class="fas fa-save"></i>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal preview-modal" id="previewModal">
        <div class="modal-header-floating">
            <h2><i class="fas fa-eye"></i> Previsualización Real</h2>
            <button class="close-modal" id="btnClosePreview">✕</button>
        </div>
        <div class="preview-modal-body" id="previewContainer">
            <!-- Preview injected here -->
        </div>
    </div>

    <div class="modal alert-modal" id="deleteModal">
        <div class="modal-content small alert-danger">
            <div class="modal-header">
                <h2>Eliminar Plantilla</h2>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-exclamation-triangle warning-icon big-icon" style="font-size: 3rem; color: #ff4757; margin-bottom: 1rem;"></i>
                <p>¿Estás seguro de que deseas eliminar esta plantilla?</p>
                <p class="text-muted">No podrás recuperarla.</p>
            </div>
            <div class="modal-footer split">
                <button class="btn btn-outline" id="btnCancelDelete">Cancelar</button>
                <button class="btn btn-danger" id="btnConfirmDelete">Sí, Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Hidden configuration templates for panels -->
    <template id="panelNombreTemplate">
        <div class="panel-group">
            <label>Fuente Tipográfica</label>
            <select class="settings-input font-selector" data-bind="fuente_nombre"></select>
        </div>
        <div class="panel-group">
            <label>Tamaño (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="tamanio_fuente" min="20" max="150" step="1">
        </div>
        <div class="panel-group">
            <label>Color</label>
            <div class="color-picker-wrap">
                <input type="color" class="settings-input color-picker" data-bind="color_texto">
                <input type="text" class="settings-input color-hex" data-bind="color_texto" pattern="^#[0-9A-Fa-f]{6}$">
            </div>
        </div>
        <div class="panel-group">
            <label>Formato (Mayúsculas/Minúsculas)</label>
            <div class="button-group segment-control" data-bind="formato_nombre">
                <button type="button" data-val="mayusculas">Aa→</button>
                <button type="button" data-val="capitalizado">Aa</button>
                <button type="button" data-val="minusculas">aa→</button>
            </div>
        </div>
    </template>

    <template id="panelRazonTemplate">
        <div class="panel-group">
            <label>Fuente Tipográfica</label>
            <select class="settings-input font-selector" data-bind="fuente_razon"></select>
        </div>
        <div class="panel-group">
            <label>Tamaño (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="tamanio_razon" min="10" max="60" step="1">
        </div>
        <div class="panel-group">
            <label>Color</label>
            <div class="color-picker-wrap">
                <input type="color" class="settings-input color-picker" data-bind="color_razon">
                <input type="text" class="settings-input color-hex" data-bind="color_razon" pattern="^#[0-9A-Fa-f]{6}$">
            </div>
        </div>
        <div class="panel-group">
            <label>Alineación</label>
            <div class="button-group segment-control" data-bind="alineacion_razon">
                <button type="button" data-val="left"><i class="fas fa-align-left"></i></button>
                <button type="button" data-val="center"><i class="fas fa-align-center"></i></button>
                <button type="button" data-val="right"><i class="fas fa-align-right"></i></button>
                <button type="button" data-val="justify"><i class="fas fa-align-justify"></i></button>
            </div>
        </div>
        <div class="panel-group">
            <label>Texto de Ejemplo</label>
            <textarea class="settings-input" data-bind="razon_defecto" rows="4"></textarea>
            <small>Usa variables como {nombre}, {fecha}, {categoría}. Este texto ayuda a comprobar la altura sobre el lienzo.</small>
        </div>
        <div class="panel-group">
            <label>Ancho Máximo (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="ancho_razon" min="200" max="1400" step="10">
            <small>También puedes redimensionarlo arrastrando desde el borde derecho en el lienzo.</small>
        </div>
    </template>

    <template id="panelFechaTemplate">
         <div class="panel-group">
            <label>Fuente Tipográfica</label>
            <select class="settings-input font-selector" data-bind="fuente_fecha"></select>
        </div>
        <div class="panel-group">
            <label>Tamaño (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="tamanio_fecha" min="10" max="60" step="1">
        </div>
        <div class="panel-group">
            <label>Color</label>
            <div class="color-picker-wrap">
                <input type="color" class="settings-input color-picker" data-bind="color_fecha">
                <input type="text" class="settings-input color-hex" data-bind="color_fecha" pattern="^#[0-9A-Fa-f]{6}$">
            </div>
        </div>
        <div class="panel-group">
            <label>Formato de Salida</label>
            <select class="settings-input" data-bind="formato_fecha">
                <option value="d de F de Y">24 de Noviembre de 2023</option>
                <option value="d/m/Y">24/11/2023</option>
                <option value="Y-m-d">2023-11-24</option>
                <option value="F d, Y">Noviembre 24, 2023</option>
            </select>
        </div>
    </template>

    <template id="panelQrTemplate">
        <div class="panel-group">
            <label>Tamaño o Escala (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="tamanio_qr" min="50" max="500" step="5">
        </div>
    </template>

    <template id="panelFirmaTemplate">
        <div class="panel-group">
            <label>Tamaño Ancho (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="tamanio_firma" min="50" max="400" step="5">
        </div>
        <div class="panel-group">
            <label>Sello/Firma Personalizada (<small>opcional</small>)</label>
            <div class="upload-dropzone" id="firmaDropzone">
                <i class="fas fa-upload"></i>
                <span>Haz clic para subir un PNG</span>
                <input type="file" id="firmaUploadDirect" accept="image/png, image/jpeg" style="display:none;">
            </div>
            <div id="firmaPreviewHolder" class="image-preview-holder"></div>
        </div>
    </template>

    <template id="panelDestacadoTemplate">
        <div class="panel-group">
            <label>Selección de Icono</label>
            <div class="icon-selector-grid">
               <!-- Injected via JS -->
            </div>
        </div>
        
        <div class="panel-group">
            <label>Escala (px) <span class="live-val"></span></label>
            <input type="range" class="settings-input" data-bind="destacado_tamanio" min="30" max="300" step="5">
        </div>
    </template>

    <!-- Raw Data for Templates -->
    <script id="fontData" type="application/json">
        <?= json_encode($fontsList) ?>
    </script>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="<?= $jsPath ?>/categorias/config_new.js?v=<?= time() ?>"></script>
</body>
</html>
