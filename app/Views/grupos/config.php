<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Plantilla del Grupo | CCE</title>
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
    </style>
</head>
<body>
    <script>
        // Global variables for initialization
        window.basePath = '<?= $basePath ?>/'; 
        window.serverGrupoId = <?= json_encode($grupo['id'] ?? null) ?>;
        
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
                <h1>Configurador de Certificados</h1>
            </div>
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </header>

        <!-- Template Selector Ribbon -->
        <div class="template-selector-ribbon">
            <div class="ribbon-header">
                <h3><i class="fas fa-images"></i> Plantillas Disponibles</h3>
                <span class="badge" id="templateCounter">Cargando...</span>
            </div>
            <div class="ribbon-slider-wrapper">
                <button class="slider-btn" id="sliderPrevBtn"><i class="fas fa-chevron-left"></i></button>
                <div class="ribbon-slider" id="templateSliderContainer">
                    <!-- Templates injected via JS -->
                </div>
                <button class="slider-btn" id="sliderNextBtn"><i class="fas fa-chevron-right"></i></button>
                
                <div class="ribbon-actions">
                    <button class="btn btn-icon" id="btnUploadTemplate" title="Subir nueva plantilla separada"><i class="fas fa-upload"></i></button>
                    <button class="btn btn-icon btn-system" id="btnRestoreSystem" title="Restaurar plantilla predeterminada del sistema"><i class="fas fa-undo"></i></button>
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

                <div class="canvas-wrapper" id="canvasWrapper">
                    <img id="templateBaseImage" src="<?= $assetsPath ?>/templates/default_template.png" alt="Template Base" draggable="false">
                    
                    <!-- Dynamic Markers Wrapper -->
                    <div id="markersLayer"></div>
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
                        <strong>Importante:</strong> Mantente al pendiente de la previsualización. El posicionamiento y tamaño entre el lienzo y la previsualización pueden variar.
                    </p>
                </div>

                <!-- Action Buttons Outside the Card -->
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
    <script src="<?= $jsPath ?>/grupos/config_new.js?v=<?= time() ?>"></script>
</body>
</html>
