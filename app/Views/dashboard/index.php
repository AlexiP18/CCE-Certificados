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
    <style>
        :root {
            --site-primary: <?= htmlspecialchars($siteConfig['primary_color']) ?>;
            --site-secondary: <?= htmlspecialchars($siteConfig['secondary_color']) ?>;
        }

        .top-nav .nav-link.active,
        .btn-admin,
        .hero-section {
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
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <?php if (!empty($siteConfig['logo_nav_url'])): ?>
            <img src="<?= htmlspecialchars($siteConfig['logo_nav_url']) ?>" alt="Logo" class="nav-logo-image" onerror="this.style.display='none'">
            <?php endif; ?>
            <i class="fas fa-graduation-cap"></i>
            <span><?= htmlspecialchars($siteConfig['site_name']) ?></span>
        </div>
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>/dashboard/index.php" class="nav-link active"><i class="fas fa-home"></i> Inicio</a></li>
            <?php if (puede('estudiantes', 'ver')): ?>
            <li><a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <?php endif; ?>
            <?php if (puede('plantillas', 'ver') || esAdmin()): ?>
            <li><a href="<?= $basePath ?>/admin/fuentes.php" class="nav-link"><i class="fas fa-font"></i> Fuentes</a></li>
            <?php endif; ?>
            <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <?php if (puede('usuarios', 'ver')): ?>
            <li><a href="<?= $basePath ?>/usuarios/index.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <?php endif; ?>
            <?php if (esAdmin()): ?>
            <li><a href="<?= $basePath ?>/configuracion/index.php" class="nav-link"><i class="fas fa-sliders-h"></i> Configuracion</a></li>
            <?php endif; ?>
            <li class="nav-user">
                <a href="<?= $basePath ?>/perfil/index.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="<?= $basePath ?>/auth/logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container main-content">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($siteConfig['site_name']) ?></h1>
            <p><?= htmlspecialchars($siteConfig['institution_name']) ?></p>
        </div>

        <!-- Sección de Grupos -->
        <div class="section-header">
            <h2><i class="fas fa-folder"></i> Grupos de Certificados</h2>
            
            <div class="periodo-selector" style="margin-left: auto; margin-right: 15px;">
                <label for="periodoFilter"><i class="fas fa-filter"></i> Filtrar por:</label>
                <select id="periodoFilter" onchange="filterGroupsByPeriod(this.value)">
                    <option value="all">Todos los períodos</option>
                    <?php
                    // Agrupar visualmente por año
                    $currentYear = '';
                    foreach ($periodos as $periodo):
                        $year = date('Y', strtotime($periodo['fecha_inicio']));
                        if ($year !== $currentYear):
                            if ($currentYear !== '') echo '</optgroup>';
                            echo '<optgroup label="Año ' . $year . '">';
                            $currentYear = $year;
                        endif;
                    ?>
                        <option value="<?= htmlspecialchars($periodo['nombre']) ?>">
                            <?= htmlspecialchars($periodo['nombre']) ?>
                        </option>
                    <?php endforeach; 
                    if ($currentYear !== '') echo '</optgroup>';
                    ?>
                </select>
            </div>

            <?php if (puede('grupos', 'crear')): ?>
            <button onclick="openCreateGrupoModal()" class="btn-admin">
                <i class="fas fa-plus"></i> Crear Grupo
            </button>
            <?php endif; ?>
        </div>

        <div class="grupos-grid">
            <?php if (count($grupos) > 0): ?>
                <?php foreach ($grupos as $grupo): 
                    $periodoNombre = $grupo['periodo_nombre'] ?? '';
                ?>
                    <a href="<?= $basePath ?>/grupos/detalle.php?id=<?= $grupo['id'] ?>" class="grupo-card" 
                       style="--grupo-color: <?= $grupo['color'] ?>"
                       data-periodo="<?= htmlspecialchars($periodoNombre) ?>">
                        <div class="grupo-header">
                            <div class="grupo-icono" style="background: linear-gradient(135deg, <?= $grupo['color'] ?>, <?= $grupo['color'] ?>dd);"><?= htmlspecialchars($grupo['icono']) ?></div>
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
        <div class="modal-content" style="max-width: 500px; padding: 0; display: flex; flex-direction: column; max-height: 85vh; overflow: hidden; border-radius: 16px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #3498dbdd 0%, #3498db 100%); padding: 20px 30px; flex-shrink: 0;">
                <h2 style="color: white !important; text-shadow: 0 1px 2px rgba(0,0,0,0.2); margin: 0;"><i class="fas fa-plus-circle"></i> Nuevo Grupo</h2>
                <button class="close-modal" onclick="closeCreateGrupoModal()" style="color: white !important; opacity: 0.9; font-size: 32px; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">×</button>
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
                            <div class="icon-selector" style="justify-content: center;">
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
                                <div class="icon-option-custom-grupo" onclick="toggleEmojiPicker()">
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
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background: white; color: #2c3e50; border-bottom: 1px solid #e0e0e0;">
                <h2 style="color: #2c3e50;"><i class="fas fa-calendar-alt" style="color: #9b59b6;"></i> Asignar Períodos</h2>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i> El grupo <strong id="periodoGrupoNombre"></strong> ha sido creado exitosamente.
                </p>
                
                <p style="color: #7f8c8d; margin-bottom: 20px; font-size: 14px;">
                    <i class="fas fa-info-circle" style="color: #3498db;"></i> Selecciona los períodos que deseas asignar a este grupo:
                </p>
                
                <input type="hidden" id="periodoGrupoId">
                
                <div id="periodosListContainer" style="max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 10px; padding: 10px;">
                    <?php foreach ($periodos as $periodo): ?>
                    <label class="periodo-item" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; margin-bottom: 5px;">
                        <input type="checkbox" class="periodo-checkbox" value="<?= $periodo['id'] ?>" style="width: 18px; height: 18px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($periodo['nombre']) ?></div>
                            <div style="font-size: 12px; color: #7f8c8d;">
                                <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="modal-actions" style="margin-top: 25px; display: flex; gap: 10px; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary-modal" onclick="closePeriodosModal()">
                        Omitir (sin períodos)
                    </button>
                    <button type="button" class="btn btn-primary-modal" onclick="savePeriodosSelection()">
                        <i class="fas fa-check"></i> Asignar Períodos
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Variables Globales para JS -->
    <script>
        window.basePath = "<?= $basePath ?>";
        window.hayPeriodosReales = <?= $hayPeriodosReales ? 'true' : 'false' ?>;
        
        // Función simple para filtrar
        function filterGroupsByPeriod(periodoName) {
            const cards = document.querySelectorAll('.grupo-card[data-periodo]');
            
            cards.forEach(card => {
                if (periodoName === 'all') {
                    card.style.display = 'flex';
                } else {
                    const cardPeriod = card.getAttribute('data-periodo');
                    // Usar includes para manejar múltiples periodos
                    if (cardPeriod && cardPeriod.includes(periodoName)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
    </script>
    <script src="<?= $jsPath ?>/dashboard/index.js"></script>
</body>
</html>
