<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteConfig['site_name']) ?> - Sistema de Certificados</title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Estilos Base -->
    <link rel="stylesheet" href="<?= $cssPath ?>/style.css">
    <!-- Estilos Específicos Dashboard -->
    <link rel="stylesheet" href="<?= $cssPath ?>/dashboard/index.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/header_theme.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
</head>
<body>
    <?php
    $activeNav = 'inicio';
    require __DIR__ . '/../components/top_nav.php';
    ?>

    <div class="container main-content">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">
                <img src="<?= $basePath ?>/assets/Logo%20CCE%20Rosa.svg" alt="Logo CCE Rosa" class="hero-page-logo" onerror="this.style.display='none'">
                <?= htmlspecialchars($siteConfig['site_name']) ?>
            </h1>
            <p><?= htmlspecialchars($siteConfig['institution_name']) ?></p>
        </div>

        <!-- Sección de Grupos -->
        <div class="section-header">
            <h2><i class="fas fa-folder"></i> Grupos de Certificados</h2>

            <?php if (puede('grupos', 'crear')): ?>
            <button onclick="openCreateGrupoModal()" class="btn-admin">
                <i class="fas fa-plus"></i> Crear Grupo
            </button>
            <?php endif; ?>
        </div>

        <div class="grupos-grid">
            <?php if (count($grupos) > 0): ?>
                <?php foreach ($grupos as $grupo): ?>
                    <a href="<?= $basePath ?>/grupos/detalle.php?id=<?= $grupo['id'] ?>" class="grupo-card" 
                       style="--grupo-color: <?= $grupo['color'] ?>">
                        <div class="grupo-header">
                            <div class="grupo-icono"><?= htmlspecialchars($grupo['icono']) ?></div>
                            <div class="grupo-title">
                                <h3><?= htmlspecialchars($grupo['nombre']) ?></h3>
                                <p><?= htmlspecialchars($grupo['descripcion']) ?: 'Sin descripción' ?></p>
                            </div>
                        </div>
                        
                        <div class="grupo-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?= $grupo['total_categorias'] ?></span>
                                <span class="stat-label">Categorías</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?= $grupo['total_estudiantes'] ?></span>
                                <span class="stat-label">Estudiantes</span>
                            </div>
                        </div>

                        <div class="grupo-fecha">
                            <i class="far fa-calendar-alt"></i>
                            Creado el <?= date('d/m/Y', strtotime($grupo['fecha_creacion'])) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if (puede('grupos', 'crear')): ?>
                <a href="javascript:void(0)" onclick="openCreateGrupoModal()" class="grupo-card add-grupo-card">
                    <div class="icon"><i class="fas fa-plus"></i></div>
                    <h3>Nuevo Grupo</h3>
                    <p>Crear un nuevo tipo de certificado</p>
                </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="icon"><i class="fas fa-folder-open"></i></div>
                    <h3>No hay grupos <?= $esInstructor ? 'asignados' : 'creados' ?></h3>
                    <p><?= $esInstructor ? 'No tienes grupos asignados. Contacta al administrador.' : 'Crea tu primer grupo de certificados para comenzar' ?></p>
                    <?php if (puede('grupos', 'crear')): ?>
                    <button onclick="openCreateGrupoModal()" class="btn-admin" style="margin-top: 20px; display: inline-block; border: none;">
                        + Crear Primer Grupo
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para crear grupo -->
    <div id="grupoModal" class="modal">
        <div class="modal-content modal-content-fixed" style="max-width: 500px;">
            <div class="modal-header modal-header-institutional">
                <h2><i class="fas fa-plus-circle"></i> Nuevo Grupo</h2>
                <button class="close-modal" onclick="closeCreateGrupoModal()">×</button>
            </div>
            
            <form id="grupoForm" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
                <div class="modal-body-scroll">
                    <div class="form-group">
                        <label for="nombre" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                            <i class="fas fa-tag"></i> Nombre del Grupo *
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Talleres, Cursos, Concursos"
                               style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px; transition: border-color 0.3s;">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                            <i class="fas fa-align-left"></i> Descripción
                            <span id="charCounter" style="float: right; font-size: 12px; color: #95a5a6;">0 / 250</span>
                        </label>
                        <textarea id="descripcion" name="descripcion" 
                                  placeholder="Describe este grupo..." 
                                  maxlength="250"
                                  oninput="updateCharCounter(this)"
                                  onpaste="handlePaste(event)"
                                  style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 14px; min-height: 100px; resize: vertical;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;"><i class="fas fa-icons"></i> Icono *</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef;">
                            <input type="hidden" id="icono" name="icono" value="📚">
                            <div class="icon-selector">
                                <div class="icon-option-grupo" data-icon="🎻">🎻</div>
                                <div class="icon-option-grupo" data-icon="🎹">🎹</div>
                                <div class="icon-option-grupo" data-icon="💃">💃</div>
                                <div class="icon-option-grupo" data-icon="🎺">🎺</div>
                                <div class="icon-option-grupo" data-icon="🥁">🥁</div>
                                <div class="icon-option-grupo" data-icon="🎸">🎸</div>
                                <div class="icon-option-grupo" data-icon="🎤">🎤</div>
                                <div class="icon-option-grupo" data-icon="🎭">🎭</div>
                                <div class="icon-option-grupo" data-icon="🎨">🎨</div>
                                <div class="icon-option-grupo" data-icon="📚">📚</div>
                                <div class="icon-option-grupo" data-icon="✍️">✍️</div>
                                <div class="icon-option-grupo" data-icon="🏆">🏆</div>
                                <div class="icon-option-custom-grupo" onclick="toggleEmojiPicker()" title="Buscar más íconos">
                                    <i class="fas fa-search"></i>
                                    <span>Buscar...</span>
                                </div>
                            </div>
                            <div id="emojiPickerContainer" style="display: none; margin-top: 10px; position: relative; z-index: 100;">
                                <emoji-picker id="emojiPicker" style="width: 100%; --num-columns: 8;"></emoji-picker>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;"><i class="fas fa-palette"></i> Color del Tema</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef;">
                            <input type="hidden" id="color" name="color" value="#3498db">
                            <div class="color-preview">
                                <div class="color-presets" style="justify-content: center; margin-bottom: 15px;">
                                    <div class="color-preset selected" data-color="#3498db" style="background: #3498db;" onclick="selectPresetColor(this, '#3498db')" title="Azul"></div>
                                    <div class="color-preset" data-color="#e74c3c" style="background: #e74c3c;" onclick="selectPresetColor(this, '#e74c3c')" title="Rojo"></div>
                                    <div class="color-preset" data-color="#2ecc71" style="background: #2ecc71;" onclick="selectPresetColor(this, '#2ecc71')" title="Verde"></div>
                                    <div class="color-preset" data-color="#f39c12" style="background: #f39c12;" onclick="selectPresetColor(this, '#f39c12')" title="Naranja"></div>
                                    <div class="color-preset" data-color="#9b59b6" style="background: #9b59b6;" onclick="selectPresetColor(this, '#9b59b6')" title="Púrpura"></div>
                                    <div class="color-preset" data-color="#1abc9c" style="background: #1abc9c;" onclick="selectPresetColor(this, '#1abc9c')" title="Turquesa"></div>
                                    <div class="color-preset" data-color="#34495e" style="background: #34495e;" onclick="selectPresetColor(this, '#34495e')" title="Gris Oscuro"></div>
                                    <div class="color-preset" data-color="#e67e22" style="background: #e67e22;" onclick="selectPresetColor(this, '#e67e22')" title="Zanahoria"></div>
                                    <div class="color-preset" data-color="#16a085" style="background: #16a085;" onclick="selectPresetColor(this, '#16a085')" title="Verde Azulado"></div>
                                    <div class="color-preset" data-color="#c0392b" style="background: #c0392b;" onclick="selectPresetColor(this, '#c0392b')" title="Rojo Oscuro"></div>
                                    <div class="color-preset" data-color="#8e44ad" style="background: #8e44ad;" onclick="selectPresetColor(this, '#8e44ad')" title="Púrpura Oscuro"></div>
                                    <div class="color-preset" data-color="#2980b9" style="background: #2980b9;" onclick="selectPresetColor(this, '#2980b9')" title="Azul Oscuro"></div>
                                </div>
                                <div class="color-custom" style="justify-content: center; padding-top: 10px; border-top: 1px dashed #ced4da;">
                                    <label for="customColor" style="font-size: 13px; color: #6c757d;">Personalizado:</label>
                                    <input type="color" id="customColor" value="#3498db" onchange="selectCustomColor(this.value)" style="cursor: pointer; height: 35px; width: 60px; padding: 0; border: none; background: none;">
                                    <span id="colorHex" style="font-family: monospace; font-weight: 600; color: #7f8c8d; background: white; padding: 4px 8px; border-radius: 4px; border: 1px solid #dee2e6;">#3498db</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-modal" onclick="closeCreateGrupoModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Crear Grupo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hayPeriodosReales): ?>
    <!-- Modal de Selección de Períodos -->
    <div id="periodosModal" class="modal">
        <div class="modal-content modal-content-fixed" style="max-width: 500px;">
            <div class="modal-header modal-header-institutional">
                <h2><i class="fas fa-calendar-alt"></i> Asignar Períodos</h2>
                <button class="close-modal" onclick="closePeriodosModal()">×</button>
            </div>
            <div class="modal-body-scroll periodos-modal-body">
                <p class="periodos-modal-message periodos-modal-message-success">
                    <i class="fas fa-check-circle"></i> El grupo <strong id="periodoGrupoNombre"></strong> ha sido creado exitosamente.
                </p>
                
                <p class="periodos-modal-message periodos-modal-message-info">
                    <i class="fas fa-info-circle"></i> Selecciona los períodos que deseas asignar a este grupo:
                </p>
                
                <input type="hidden" id="periodoGrupoId">
                
                <div id="periodosListContainer" class="periodos-list-container">
                    <?php foreach ($periodos as $periodo): ?>
                    <label class="periodo-item periodos-modal-item">
                        <input type="checkbox" class="periodo-checkbox" value="<?= $periodo['id'] ?>">
                        <div style="flex: 1;">
                            <div class="periodos-modal-item-title"><?= htmlspecialchars($periodo['nombre']) ?></div>
                            <div class="periodos-modal-item-subtitle">
                                <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer modal-footer-split">
                <button type="button" class="btn btn-secondary-modal" onclick="closePeriodosModal()">
                    Omitir (sin períodos)
                </button>
                <button type="button" class="btn btn-primary-modal" onclick="savePeriodosSelection()">
                    <i class="fas fa-check"></i> Asignar Períodos
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Variables Globales para JS -->
    <script>
        window.basePath = "<?= $basePath ?>";
        window.hayPeriodosReales = <?= $hayPeriodosReales ? 'true' : 'false' ?>;
    </script>
    <script src="<?= $jsPath ?>/dashboard/index.js"></script>
</body>
</html>
