<?php
// Vista: grupos/detalle.php
// La lógica de obtención de datos ya ha sido manejada por el Controlador
// Las variables disponibles son: $grupo, $periodos, $categorias_por_periodo, etc.

// Función helper para la vista (mantener para compatibilidad con las llamadas en el HTML)
if (!function_exists('generarNombrePeriodo')) {
    function generarNombrePeriodo($fecha_inicio, $fecha_fin) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        
        $mes_inicio = (int)$inicio->format('n');
        $mes_fin = (int)$fin->format('n');
        $anio_inicio = $inicio->format('Y');
        $anio_fin = $fin->format('Y');
        $dia_inicio = (int)$inicio->format('j');
        $dia_fin = (int)$fin->format('j');
        
        if ($mes_inicio === $mes_fin && $anio_inicio === $anio_fin) {
            return $meses[$mes_inicio] . " $dia_inicio-$dia_fin, $anio_inicio";
        }
        if ($anio_inicio === $anio_fin) {
            return $meses[$mes_inicio] . " - " . $meses[$mes_fin] . " $anio_inicio";
        }
        return $meses[$mes_inicio] . " $anio_inicio - " . $meses[$mes_fin] . " $anio_fin";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($grupo['nombre']) ?> - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">

    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/grupos/detalle.css">
    <style>
        /* Dynamic CSS that might have been left inline or specific overrides */
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-nav" style="display: flex; justify-content: space-between; align-items: center; padding: 5px 0;">
            <a href="<?= BASE_URL ?>/dashboard/index.php" class="breadcrumb-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
            
            <!-- User Profile Badge -->
            <div class="user-profile-badge">
                <div class="user-avatar">
                    <?= strtoupper(substr($usuario['nombre_completo'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($usuario['nombre_completo']) ?></span>
                </div>
                <a href="auth/logout.php" class="btn-logout" title="Cerrar Sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <!-- Header del Grupo -->
        <div class="grupo-header" style="background: <?= $grupo['color'] ?>;">
            <div class="grupo-header-content">
                <div class="grupo-header-left">
                    <div class="grupo-icon-large"><?= htmlspecialchars($grupo['icono']) ?></div>
                    <div class="grupo-info">
                        <h1><?= htmlspecialchars($grupo['nombre']) ?></h1>
                        <p><?= htmlspecialchars($grupo['descripcion']) ?: 'Sin descripción' ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="<?= BASE_URL ?>/estudiantes/grupo.php?id=<?= $grupo_id ?>" class="btn-header">
                        <i class="fas fa-users"></i> Estudiantes del Grupo
                    </a>
                    <a href="<?= BASE_URL ?>/grupos/config.php?id=<?= $grupo_id ?>" class="btn-header">
                        <i class="fas fa-cog"></i> Configurar
                    </a>
                    <button onclick="editarGrupo()" class="btn-header btn-header-icon" title="Editar Grupo">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="eliminarGrupo()" class="btn-header btn-header-icon" style="background: rgba(231, 76, 60, 0.3); border-color: rgba(231, 76, 60, 0.4);" title="Eliminar Grupo">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Categorías -->
        <div class="categorias-container">
            <div class="section-title">
                <h2><i class="fas fa-folder-open"></i> Períodos y Categorías</h2>
            </div>
            
            <!-- Información del año y Filtros -->
            <div style="margin-bottom: 20px; padding: 12px 20px; background: #e8f5e9; border-radius: 10px; border-left: 4px solid #4caf50; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6c757d;">
                    <i class="fas fa-info-circle" style="color: #4caf50;"></i>
                    <span id="resumenPeriodosText">
                        Mostrando <strong><?= count($periodos) ?></strong> período(s) del año <strong><?= $anio_seleccionado ?></strong>
                    </span>
                </div>
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <!-- Toggle En Curso -->
                    <button id="btnFiltroVigente" onclick="toggleFiltroVigente()" class="btn-filtro-periodo" style="border: 1px solid #4caf50; background: white; color: #4caf50; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> En curso
                    </button>
                    
                    <!-- Selector de Mes -->
                    <select id="filtroMesPeriodo" onchange="filtrarPeriodos()" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; color: #495057; outline: none; cursor: pointer;">
                        <option value="">Todos los meses</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>

                    <!-- Selector de Año -->
                    <select id="anioSelect" onchange="cambiarAnio(this.value)" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; color: #495057; outline: none; cursor: pointer;">
                        <?php foreach ($anios_disponibles as $anio): ?>
                            <option value="<?= $anio ?>" <?= $anio == $anio_seleccionado ? 'selected' : '' ?>>
                                <?= $anio ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Tabs de Períodos -->
            <?php if (count($periodos) > 0): ?>
                <!-- Contenedor Principal: Sidebar + Contenido -->
                <div class="periodo-tabs-container">
                    <!-- SIDEBAR: Períodos -->
                    <div class="periodo-tabs-nav">
                        <!-- Botón Nuevo Período -->
                        <button onclick="abrirModalPeriodo()" class="btn-nuevo-periodo-sidebar">
                            <i class="fas fa-plus"></i> Nuevo Período
                        </button>
                        
                        <?php foreach ($periodos as $idx => $periodo): 
                            $fecha_hoy = date('Y-m-d');
                            $es_periodo_actual = ($fecha_hoy >= $periodo['fecha_inicio'] && $fecha_hoy <= $periodo['fecha_fin']);
                            
                            // Determinar si es el tab activo
                            $is_active = false;
                            if ($periodo_seleccionado_id) {
                                $is_active = ($periodo['id'] == $periodo_seleccionado_id);
                            } else {
                                $is_active = ($idx === 0);
                            }
                        ?>
                        <div class="periodo-tab <?= $is_active ? 'active' : '' ?> <?= $es_periodo_actual ? 'periodo-vigente' : '' ?>" 
                             data-periodo-id="<?= $periodo['id'] ?>"
                             data-vigente="<?= $es_periodo_actual ? '1' : '0' ?>"
                             data-start="<?= $periodo['fecha_inicio'] ?>"
                             data-end="<?= $periodo['fecha_fin'] ?>"
                             onclick="cambiarTab(<?= $periodo['id'] ?>)">
                            <?php if ($es_periodo_actual): ?>
                                <div class="badge-vigente" title="Período en curso">
                                    <i class="fas fa-circle"></i> En curso
                                </div>
                            <?php endif; ?>
                            <div class="tab-icon" style="background: <?= htmlspecialchars($periodo['color'] ?? '#3498db') ?>;">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="tab-info">
                                <div class="tab-title"><?= htmlspecialchars($periodo['nombre']) ?></div>
                                <div class="tab-subtitle">
                                    <?= date('d/m', strtotime($periodo['fecha_inicio'])) ?> - <?= date('d/m', strtotime($periodo['fecha_fin'])) ?>
                                </div>
                                <div class="tab-stats">
                                    <span class="tab-badge">
                                        <i class="fas fa-folder"></i> <?= count($categorias_por_periodo[$periodo['id']] ?? []) ?>
                                    </span>
                                    <span class="tab-badge">
                                        <i class="fas fa-users"></i> <?= array_sum(array_column($categorias_por_periodo[$periodo['id']] ?? [], 'total_estudiantes')) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tab-actions" onclick="event.stopPropagation();">

                                <button onclick="eliminarPeriodo(<?= $periodo['id'] ?>, '<?= htmlspecialchars(addslashes($periodo['nombre'])) ?>')" class="btn-tab-action btn-danger" title="Eliminar período">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Mensaje de Sin Resultados -->
                        <div id="mensajeSinResultadosPeriodo" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 30px 15px; text-align: center; color: #95a5a6;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 50%; margin-bottom: 10px;">
                                <i class="fas fa-search fa-2x" style="opacity: 0.5; color: #bdc3c7;"></i>
                            </div>
                            <span style="font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #7f8c8d;">Sin resultados</span>
                            <span style="font-size: 12px; opacity: 0.8; color: #95a5a6;">Prueba otros filtros</span>
                        </div>
                    </div>
                    
                    <!-- CONTENIDO: Categorías -->
                    <div class="periodo-content-area" id="periodosContentArea">
                        <?php foreach ($periodos as $idx => $periodo): 
                            // Recalcular activo para el contenido
                            $is_active = false;
                            if ($periodo_seleccionado_id) {
                                $is_active = ($periodo['id'] == $periodo_seleccionado_id);
                            } else {
                                $is_active = ($idx === 0);
                            }
                        ?>
                        <div class="periodo-tab-content <?= $is_active ? 'active' : '' ?>" id="tab-content-<?= $periodo['id'] ?>" data-periodo-id="<?= $periodo['id'] ?>">
                            <!-- Botón Nueva Categoría -->
                            <button onclick="openModal(<?= $periodo['id'] ?>)" class="btn-nueva-categoria-header">
                                <i class="fas fa-plus"></i> Nueva Categoría
                            </button>
                            
                            <div class="categorias-rows">
                                <?php if (count($categorias_por_periodo[$periodo['id']] ?? []) > 0): ?>
                                    <?php foreach ($categorias_por_periodo[$periodo['id']] as $categoria): ?>
                                    <div class="categoria-row">
                                        <?php $catColor = $categoria['color'] ?? '#3498db'; ?>
                                        <div class="categoria-icon" style="background: linear-gradient(135deg, <?= htmlspecialchars($catColor) ?>cc, <?= htmlspecialchars($catColor) ?>);"><?= htmlspecialchars($categoria['icono'] ?? '📁') ?></div>
                                        <div class="categoria-info">
                                            <h3><?= htmlspecialchars($categoria['nombre']) ?></h3>
                                            <p class="categoria-descripcion"><?= htmlspecialchars($categoria['descripcion']) ?: 'Sin descripción' ?></p>
                                            <p class="categoria-instructor">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                                <?php if (!empty($categoria['instructor_nombre'])): ?>
                                                    <?= htmlspecialchars($categoria['instructor_nombre']) ?>
                                                    <?php if (!empty($categoria['instructor_cedula'])): ?>
                                                        | CI: <?= htmlspecialchars($categoria['instructor_cedula']) ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    Sin instructor asignado
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="categoria-stats">
                                            <div class="stat-item">
                                                <i class="fas fa-users"></i>
                                                <span class="stat-number"><?= $categoria['total_estudiantes'] ?></span>
                                                <span>Est.</span>
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-check-circle"></i>
                                                <span class="stat-number"><?= $categoria['total_aprobados'] ?></span>
                                                <span>Aprobados</span>
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-certificate"></i>
                                                <span class="stat-number"><?= $categoria['total_certificados'] ?></span>
                                                <span>Cert.</span>
                                            </div>
                                        </div>
                                        <div class="categoria-actions">
                                            <a href="<?= BASE_URL ?>/categorias/gestion.php?categoria_id=<?= $categoria['id'] ?>&periodo_id=<?= $periodo['id'] ?>" class="btn-icon btn-certificados" title="Certificados">
                                                <i class="fas fa-certificate"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/categorias/config.php?id=<?= $categoria['id'] ?>" 
                                               class="btn-icon btn-config"
                                               title="Configurar Plantilla">
                                                <i class="fas fa-cog"></i>
                                            </a>
                                            <button onclick="editarCategoria(<?= $categoria['id'] ?>)" class="btn-icon btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="eliminarCategoria(<?= $categoria['id'] ?>, '<?= htmlspecialchars($categoria['nombre']) ?>')" 
                                                    class="btn-icon btn-delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="categoria-row add-categoria-row" onclick="openModal(<?= $periodo['id'] ?>)">
                                        <span class="add-icon"><i class="fas fa-folder-plus"></i></span>
                                        <span class="add-text">Este período aún no tiene categorías. ¡Crea la primera!</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #7f8c8d; background: #f8f9fa; border-radius: 12px;">
                    <div style="font-size: 80px; margin-bottom: 20px; opacity: 0.3;"><i class="fas fa-calendar-plus"></i></div>
                    <h3 style="margin: 0 0 10px 0; color: #2c3e50;">No hay períodos para el año <?= $anio_seleccionado ?></h3>
                    <p>Crea un nuevo período para comenzar a gestionar las categorías</p>
                    <button onclick="abrirModalPeriodo()" 
                            style="margin-top: 20px; padding: 12px 24px; background: #9b59b6; color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-plus"></i> Crear Período
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para editar grupo - REFACTORIZADO -->
    <div id="grupoModal" class="modal">
        <div class="modal-content" style="max-width: 500px; padding: 0; display: flex; flex-direction: column; max-height: 85vh; overflow: hidden; border-radius: 16px;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= $grupo['color'] ?>dd 0%, <?= $grupo['color'] ?> 100%); padding: 20px 30px; flex-shrink: 0;">
                <h2 style="color: white !important; text-shadow: 0 1px 2px rgba(0,0,0,0.2); margin: 0;"><i class="fas fa-edit"></i> Editar Grupo</h2>
                <button class="btn-close" onclick="closeGrupoModal()" style="color: white !important; opacity: 0.9; font-size: 32px; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">×</button>
            </div>
            
            <form id="grupoForm" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
                <input type="hidden" name="id" value="<?= $grupo_id ?>">
                
                <div class="modal-body-scroll" style="padding: 25px 30px; overflow-y: auto; flex: 1;">
                    <div class="form-group">
                        <label for="grupo_nombre" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                            <i class="fas fa-tag"></i> Nombre del Grupo *
                        </label>
                        <input type="text" id="grupo_nombre" name="nombre" required 
                               value="<?= htmlspecialchars($grupo['nombre']) ?>"
                               placeholder="Ej: Talleres, Cursos, Concursos"
                               style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px; transition: border-color 0.3s;">
                    </div>
                    
                    <div class="form-group">
                        <label for="grupo_descripcion" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                            <i class="fas fa-align-left"></i> Descripción
                            <span id="grupoCharCounter" style="float: right; font-size: 12px; color: #95a5a6;">0 / 250</span>
                        </label>
                        <textarea id="grupo_descripcion" name="descripcion" 
                                  placeholder="Describe este grupo..."
                                  maxlength="250"
                                  oninput="updateCharCounterGrupo(this)"
                                  onpaste="handlePasteGrupo(event)"
                                  style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 14px; min-height: 100px; resize: vertical;"><?= htmlspecialchars($grupo['descripcion']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;"><i class="fas fa-icons"></i> Icono *</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef;">
                            <input type="hidden" id="grupo_icono" name="icono" value="<?= htmlspecialchars($grupo['icono']) ?>">
                            <div class="icon-selector" id="grupoIconSelector" style="justify-content: center;">
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
                                <button type="button" class="icon-option-custom-grupo" onclick="toggleEmojiPickerGrupo()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="emojiPickerContainerGrupo" style="display: none; margin-top: 15px; position: relative; z-index: 100;">
                                <emoji-picker id="emojiPickerGrupo" style="width: 100%; --num-columns: 8;"></emoji-picker>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;"><i class="fas fa-palette"></i> Color del Tema</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef;">
                            <input type="hidden" id="grupo_color" name="color" value="<?= htmlspecialchars($grupo['color']) ?>">
                            <div class="color-preview">
                                <div class="color-presets" style="justify-content: center; margin-bottom: 15px;">
                                    <div class="color-preset" data-color="#3498db" style="background: #3498db;" onclick="selectPresetColorGrupo(this, '#3498db')" title="Azul"></div>
                                    <div class="color-preset" data-color="#e74c3c" style="background: #e74c3c;" onclick="selectPresetColorGrupo(this, '#e74c3c')" title="Rojo"></div>
                                    <div class="color-preset" data-color="#2ecc71" style="background: #2ecc71;" onclick="selectPresetColorGrupo(this, '#2ecc71')" title="Verde"></div>
                                    <div class="color-preset" data-color="#f39c12" style="background: #f39c12;" onclick="selectPresetColorGrupo(this, '#f39c12')" title="Naranja"></div>
                                    <div class="color-preset" data-color="#9b59b6" style="background: #9b59b6;" onclick="selectPresetColorGrupo(this, '#9b59b6')" title="Púrpura"></div>
                                    <div class="color-preset" data-color="#1abc9c" style="background: #1abc9c;" onclick="selectPresetColorGrupo(this, '#1abc9c')" title="Turquesa"></div>
                                    <div class="color-preset" data-color="#34495e" style="background: #34495e;" onclick="selectPresetColorGrupo(this, '#34495e')" title="Gris Oscuro"></div>
                                    <div class="color-preset" data-color="#e67e22" style="background: #e67e22;" onclick="selectPresetColorGrupo(this, '#e67e22')" title="Zanahoria"></div>
                                    <div class="color-preset" data-color="#16a085" style="background: #16a085;" onclick="selectPresetColorGrupo(this, '#16a085')" title="Verde Azulado"></div>
                                    <div class="color-preset" data-color="#c0392b" style="background: #c0392b;" onclick="selectPresetColorGrupo(this, '#c0392b')" title="Rojo Oscuro"></div>
                                    <div class="color-preset" data-color="#8e44ad" style="background: #8e44ad;" onclick="selectPresetColorGrupo(this, '#8e44ad')" title="Púrpura Oscuro"></div>
                                    <div class="color-preset" data-color="#2980b9" style="background: #2980b9;" onclick="selectPresetColorGrupo(this, '#2980b9')" title="Azul Oscuro"></div>
                                </div>
                                <div class="color-custom" style="justify-content: center; padding-top: 10px; border-top: 1px dashed #ced4da;">
                                    <label for="grupo_customColor" style="font-size: 13px; color: #6c757d;">Personalizado:</label>
                                    <input type="color" id="grupo_customColor" value="<?= htmlspecialchars($grupo['color']) ?>" onchange="selectCustomColorGrupo(this.value)" style="cursor: pointer; height: 35px; width: 60px; padding: 0; border: none; background: none;">
                                    <span id="grupoColorHex" style="font-family: monospace; font-weight: 600; color: #7f8c8d; background: white; padding: 4px 8px; border-radius: 4px; border: 1px solid #dee2e6;"><?= htmlspecialchars($grupo['color']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary-modal" onclick="closeGrupoModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal" style="background: linear-gradient(135deg, <?= $grupo['color'] ?> 0%, <?= $grupo['color'] ?>dd 100%); border: none;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar grupo -->
    <div id="confirmarEliminarGrupoModal" class="modal">
        <div class="modal-content" style="max-width: 450px; padding: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 30px; text-align: center;">
                <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 22px; font-weight: 700;">¿Eliminar Grupo?</h3>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 15px;">Esta acción es irreversible</p>
            </div>
            
            <div style="padding: 30px; text-align: center; background: white;">
                <div style="background: #fff5f5; border-left: 4px solid #e74c3c; padding: 15px; text-align: left; border-radius: 4px; margin-bottom: 20px;">
                    <p style="color: #c0392b; font-size: 14px; margin: 0; line-height: 1.5;">
                        <strong style="display: block; margin-bottom: 5px;"><i class="fas fa-info-circle"></i> Atención:</strong>
                        Se eliminarán todos los <strong>períodos</strong>, <strong>categorías</strong> y <strong>certificados</strong> asociados a este grupo.
                    </p>
                </div>
                
                <p style="color: #2c3e50; font-size: 16px; margin: 0 0 5px 0;">Vas a eliminar:</p>
                <div style="font-size: 20px; font-weight: 700; color: #2c3e50; margin-bottom: 25px; padding: 10px; background: #f8f9fa; border-radius: 8px; border: 1px dashed #cbd5e0;">
                    <?= htmlspecialchars($grupo['nombre']) ?>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="button" class="btn btn-secondary-modal" onclick="cerrarEliminarGrupoModal()" style="flex: 1;">
                        Cancelar
                    </button>
                    <button type="button" id="btnConfirmarEliminarGrupo" class="btn" style="background: #e74c3c; color: white; flex: 1; border: none; font-weight: 600; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.3);" onclick="confirmarEliminarGrupo()">
                        <i class="fas fa-trash-alt"></i> Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar categoría -->
    <div id="categoriaModal" class="modal">
        <div class="modal-content" style="max-width: 500px; padding: 0; display: flex; flex-direction: column; max-height: 85vh; overflow: hidden; border-radius: 16px;">
            <div class="modal-header" style="background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?>dd 0%, <?= htmlspecialchars($grupo['color']) ?> 100%); padding: 20px 30px; flex-shrink: 0;">
                <h2 id="modalTitle" style="color: white !important; text-shadow: 0 1px 2px rgba(0,0,0,0.2); margin: 0; font-size: 22px;"><i class="fas fa-plus-circle"></i> Nueva Categoría</h2>
                <button class="btn-close" onclick="closeModal()" style="color: white !important; opacity: 0.9; font-size: 32px; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">×</button>
            </div>
            
            <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 0;">
                <!-- Tabs -->
                <div id="categoriaTabs" class="categoria-tabs" style="margin: 0; padding: 15px 20px 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    <button type="button" class="categoria-tab active" onclick="switchCategoriaTab('nueva')" style="margin-bottom: -1px; border-radius: 8px 8px 0 0; padding: 10px 15px;">
                        <i class="fas fa-plus"></i> Crear Nueva
                    </button>
                    <button type="button" class="categoria-tab" onclick="switchCategoriaTab('copiar')" style="margin-bottom: -1px; border-radius: 8px 8px 0 0; padding: 10px 15px;">
                        <i class="fas fa-file-import"></i> Importar
                    </button>
                </div>
                
                <div style="padding: 25px 30px;">
                    <!-- Contenido: Crear Nueva Categoría -->
                    <div id="tabNuevaCategoria" class="categoria-tab-content active">
                        <form id="categoriaForm">
                            <input type="hidden" id="categoria_id" name="id">
                            <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
                            <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">
                            
                            <div class="form-group">
                                <label for="nombre" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                                    <i class="fas fa-tag"></i> Nombre de la Categoría *
                                </label>
                                <input type="text" id="nombre" name="nombre" required placeholder="Ej: Primer Parcial"
                                    style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px; transition: border-color 0.3s;">
                            </div>

                            <div class="form-group">
                                <label for="descripcion" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block; display: flex; justify-content: space-between;">
                                    <span><i class="fas fa-align-left"></i> Descripción</span>
                                    <span id="categoriaCharCounter" style="font-size: 12px; color: #95a5a6; font-weight: normal;">0 / 200</span>
                                </label>
                                <textarea id="descripcion" name="descripcion" placeholder="Opcional: Describe de qué trata esta categoría..." maxlength="200"
                                    oninput="updateCharCounter(this, 'categoriaCharCounter')"
                                    style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px; transition: border-color 0.3s; min-height: 80px; resize: vertical; font-family: inherit;"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="instructor_search" style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                                    <i class="fas fa-chalkboard-teacher"></i> Instructor a Cargo
                                </label>
                                <input type="hidden" id="instructor_id" name="instructor_id" value="">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="instructor_search" list="instructoresList" autocomplete="off"
                                        placeholder="Buscar por nombre o cédula"
                                        style="flex: 1; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 15px; transition: border-color 0.3s;">
                                    <button type="button" onclick="clearInstructorSearch()"
                                        style="padding: 10px 12px; border: 2px solid #e9ecef; background: #fff; border-radius: 10px; cursor: pointer; color: #7f8c8d; font-size: 13px; font-weight: 600; white-space: nowrap;"
                                        title="Quitar instructor asignado">
                                        Limpiar
                                    </button>
                                </div>
                                <div id="instructorSuggestions"
                                     style="display: none; margin-top: 6px; border: 1px solid #e9ecef; border-radius: 10px; background: #fff; max-height: 220px; overflow-y: auto; box-shadow: 0 6px 18px rgba(0,0,0,0.08);"></div>
                                <datalist id="instructoresList">
                                    <?php foreach ($instructores as $instructor): ?>
                                    <?php $cedulaInst = trim((string)($instructor['cedula'] ?? '')); ?>
                                    <?php $labelInst = $instructor['nombre_completo'] . ($cedulaInst !== '' ? ' | CI: ' . $cedulaInst : ''); ?>
                                    <option value="<?= htmlspecialchars($labelInst) ?>" data-id="<?= (int)$instructor['id'] ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <small style="display: block; margin-top: 6px; color: #7f8c8d; font-size: 12px;">Escribe nombre o cédula y selecciona una opción. Deja vacío para sin asignar.</small>
                            </div>
                            
                            <div class="form-group">
                                <label style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 8px; display: block;">
                                    <i class="fas fa-icons"></i> Icono
                                </label>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef;">
                                    <input type="hidden" id="icono" name="icono" value="📚">
                                    <div class="icon-selector" style="justify-content: center;">
                                        <div class="icon-option-grupo icon-option selected" data-icon="📚">📚</div>
                                        <div class="icon-option-grupo icon-option" data-icon="📝">📝</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🔬">🔬</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🎨">🎨</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🏅">🏅</div>
                                        <div class="icon-option-grupo icon-option" data-icon="💻">💻</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🧮">🧮</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🎵">🎵</div>
                                        <div class="icon-option-grupo icon-option" data-icon="🌍">🌍</div>
                                        
                                        <!-- Botón para abrir picker completo -->
                                        <button type="button" class="icon-option-custom-grupo icon-option-custom" onclick="toggleEmojiPickerCat()" title="Más iconos...">
                                            <i class="fas fa-search"></i> <span>Más iconos...</span>
                                        </button>
                                    </div>
                                    <!-- Emoji Picker Container -->
                                    <div id="emojiPickerContainerCat" style="display: none; margin-top: 15px; position: relative; z-index: 100;">
                                        <emoji-picker id="emojiPickerCat" style="width: 100%; --num-columns: 8;"></emoji-picker>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" id="color" name="color" value="<?= htmlspecialchars($grupo['color']) ?>">
                        </form>
                    </div>
                    
                    <!-- Contenido: Copiar de otro período -->
                    <div id="tabCopiarCategoria" class="categoria-tab-content">
                        <div class="form-group">
                            <label style="margin-bottom: 10px; display: block; color: #2c3e50; font-weight: 600;">Seleccionar Periodo</label>
                            
                             <div class="periodo-tabs-container" style="display: flex; flex-direction: row; gap: 0; height: 350px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                                <!-- Navegación de Tabs (Sidebar Vertical) -->
                                <div class="periodo-tabs-nav" style="display: flex; flex-direction: column; width: 115px; overflow-y: auto; overflow-x: hidden; background: #f8f9fa; border-right: 1px solid #dee2e6; flex-shrink: 0;">
                                    <?php foreach ($periodos as $p): ?>
                                    <div class="periodo-modal-tab periodo-import-tab" 
                                         id="periodo-import-<?= $p['id'] ?>"
                                         data-id="<?= $p['id'] ?>"
                                         onclick="seleccionarPeriodoImportacion(<?= $p['id'] ?>)"
                                         style="padding: 12px 10px; cursor: pointer; border-left: 3px solid transparent; border-bottom: 1px solid #e9ecef; color: #7f8c8d; font-weight: 500; font-size: 12px; display: flex; flex-direction: row; align-items: center; text-align: left; gap: 8px; background: transparent; transition: all 0.2s; width: 100%; box-sizing: border-box;">
                                        <i class="fas fa-calendar" style="font-size: 14px; color: <?= htmlspecialchars($p['color'] ?? '#95a5a6') ?>; flex-shrink: 0;"></i>
                                        <span style="line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;"><?= htmlspecialchars($p['nombre']) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            
                                <!-- Contenido del Tab (Lista de categorías) -->
                                <div id="categoriasOtroPeriodoContainer" style="display: none; flex: 1; overflow-y: auto; padding: 20px; background: #fff;">
                                    <input type="hidden" id="periodoOrigen" value="">
                                    
                                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #dee2e6;">
                                        <label class="checkbox-label" style="font-weight: 600; color: #2c3e50; font-size: 14px; display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer;">
                                            <input type="checkbox" id="selectAllCategoriasOtro" onchange="toggleSelectAllCategoriasOtro()" style="width: 16px; height: 16px;">
                                            <i class="fas fa-tasks" style="color: #3498db;"></i> Todo
                                        </label>
                                        
                                        <label class="checkbox-label" style="font-size: 13px; color: #7f8c8d; display: flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;">
                                            <input type="checkbox" id="copiarEstudiantes" checked style="width: 14px; height: 14px;">
                                            <i class="fas fa-user-friends" style="color: #27ae60;"></i> Copiar estudiantes
                                        </label>
                                    </div>
                                    
                                    <div id="listaCategoriasOtroPeriodo" class="categorias-lista-copiar" style="margin-top: 10px;">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                </div>
                                
                                <!-- Mensaje inicial de "Selecciona un periodo" -->
                                <div id="mensajeSeleccionPeriodo" style="display: flex; flex: 1; height: 100%; align-items: center; justify-content: center; color: #95a5a6; border-left: 1px dashed #dee2e6; margin-top: 0; flex-direction: column; gap: 15px;">
                                    <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-arrow-left" style="font-size: 24px; color: #bdc3c7;"></i>
                                    </div>
                                    <div style="font-weight: 500;">Seleccione un periodo</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                <button type="button" class="btn btn-secondary-modal" onclick="closeModal()">Cancelar</button>
                
                <!-- Botón para Guardar Nueva -->
                <!-- Botón para Guardar Nueva -->
                <button type="submit" form="categoriaForm" id="btnGuardarCategoria" class="btn btn-primary-modal" style="background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?> 0%, <?= htmlspecialchars($grupo['color']) ?>dd 100%); border: none;">
                    <span id="btnTextGuardarCategoria">Crear Categoría</span>
                </button>
                
                <!-- Botón para Copiar (Importar) -->
                <button type="button" class="btn btn-primary-modal" onclick="copiarCategoriasSeleccionadas()" id="btnCopiarCategorias" style="background: linear-gradient(135deg, <?= htmlspecialchars($grupo['color']) ?> 0%, <?= htmlspecialchars($grupo['color']) ?>dd 100%); border: none; display: none;" disabled>
                    <i class="fas fa-file-import"></i> Importar Categorías
                </button>
            </div>
        </div>
    </div>









    <!-- Modal para Crear/Editar Período -->
    <div id="periodoModal" class="modal">
        <div class="modal-content" style="max-width: 500px; padding: 0; display: flex; flex-direction: column; max-height: 85vh; overflow: hidden; border-radius: 16px;">
            <div id="periodoModalHeader" class="modal-header" style="background: linear-gradient(135deg, <?= $grupo['color'] ?? '#3498db' ?> 0%, <?= $grupo['color'] ?? '#3498db' ?>dd 100%); padding: 20px 30px; flex-shrink: 0;">
                <h2 id="periodoModalTitle" style="color: white !important; text-shadow: 0 1px 2px rgba(0,0,0,0.2); margin: 0;"><i class="fas fa-calendar-plus"></i> Nuevo Período</h2>
                <button class="btn-close" onclick="cerrarModalPeriodo()" style="color: white !important; opacity: 0.9; font-size: 32px; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">×</button>
            </div>
            <form id="periodoForm" onsubmit="guardarPeriodo(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
                <div class="periodo-modal-body" style="padding: 25px 30px; overflow-y: auto; flex: 1;">
                        <div class="periodo-form-row">
                            <div class="periodo-form-group">
                                <label><i class="fas fa-calendar-day"></i> Fecha Inicio</label>
                                <input type="date" id="periodoFechaInicio" onchange="actualizarPreviewPeriodo()">
                            </div>
                            <div class="periodo-form-group">
                                <label><i class="fas fa-calendar-check"></i> Fecha Fin</label>
                                <input type="date" id="periodoFechaFin" onchange="actualizarPreviewPeriodo()">
                            </div>
                        </div>
                        
                        <!-- Preview del nombre generado -->
                        <div class="periodo-preview">
                            <div class="periodo-preview-label">
                                <i class="fas fa-magic"></i> Nombre del período:
                            </div>
                            <div class="periodo-preview-nombre" id="periodoPreviewNombre">
                                Selecciona las fechas...
                            </div>
                        </div>
                    
                        <!-- Seleccionar categorías e importar estudiantes (Diseño Tabbed) -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                            <label style="font-weight: 600; color: #2c3e50; font-size: 14px; display: block; margin-bottom: 10px;">
                                <i class="fas fa-layer-group"></i> Importar categorías
                                <small style="display:block; font-weight:normal; color:#7f8c8d; font-size:12px; margin-top:2px;">
                                    Marca la casilla para importar estudiantes de un periodo anterior. Si la desmarcas, la categoría iniciará vacía.
                                </small>
                            </label>
                            
                            <!-- Navegación de Tabs (Horizontal) -->
                            <div class="periodo-modal-tabs-nav" id="periodoTabsNav">
                                <!-- Se llena dinámicamente -->
                                <div style="text-align: center; width: 100%; padding: 10px; color: #7f8c8d; font-size: 13px;">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando categorías...
                                </div>
                            </div>
                            
                            <!-- Contenido de Tabs (Con scroll vertical) -->
                            <div class="periodo-tabs-content" id="periodoTabsContent" style="height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-top: none; padding: 15px; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                </div>
                
                <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary-modal" onclick="cerrarModalPeriodo()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal" style="background: <?= $grupo['color'] ?? '#9b59b6' ?>;">
                        <i class="fas fa-save"></i> Guardar Período
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar período -->
    <div id="confirmarEliminarPeriodoModal" class="modal">
        <div class="modal-content" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Eliminar Período</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de eliminar el período:
                </p>
                <p id="confirmarEliminarPeriodoNombre" style="color: #e74c3c; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre del período -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> Esto NO eliminará las categorías ni estudiantes, solo la asociación con este período.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary-modal" onclick="cerrarConfirmarEliminarPeriodo()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarEliminarPeriodo" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="confirmarEliminarPeriodo()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar categoría -->
    <div id="confirmarEliminarCategoriaModal" class="modal">
        <div class="modal-content" style="max-width: 420px; padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px 30px; text-align: center;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: white;"></i>
                </div>
                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600;">Eliminar Categoría</h3>
            </div>
            <div style="padding: 25px 30px; text-align: center;">
                <p style="color: #2c3e50; font-size: 15px; margin: 0 0 10px 0;">
                    ¿Estás seguro de eliminar la categoría:
                </p>
                <p id="confirmarEliminarCategoriaNombre" style="color: #e74c3c; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                    <!-- Nombre de la categoría -->
                </p>
                <p style="color: #7f8c8d; font-size: 13px; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> Los certificados no se eliminarán, pero perderán la asociación con esta categoría.
                </p>
            </div>
            <div style="padding: 20px 30px; background: #f8f9fa; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary-modal" onclick="cerrarConfirmarEliminarCategoria()" style="min-width: 100px;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarEliminarCategoria" class="btn" style="background: #e74c3c; color: white; min-width: 100px;" onclick="confirmarEliminarCategoria()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const GRUPO_ID = <?= $grupo['id'] ?>;
        const GRUPO_NOMBRE = '<?= addslashes($grupo['nombre']) ?>';
        const GRUPO_COLOR = '<?= $grupo['color'] ?>';
        const GRUPO_ICONO = '<?= $grupo['icono'] ?>';
        const GRUPO_DESCRIPCION = '<?= addslashes($grupo['descripcion']) ?>';
        const GRUPO_PERIODOS = <?= json_encode(array_values($periodos ?? [])) ?>;
        const INSTRUCTORES_CATEGORIA = <?= json_encode(array_values(array_map(function ($i) {
            return [
                'id' => (int)$i['id'],
                'nombre_completo' => (string)$i['nombre_completo'],
                'cedula' => (string)($i['cedula'] ?? '')
            ];
        }, $instructores ?? []))) ?>;
        const ANIO_ACTUAL = <?= $anio_seleccionado ?>;
        const CURRENT_DATE = '<?= date('Y-m-d') ?>';
    </script>
    <script src="<?= BASE_URL ?>/js/grupos/detalle.js"></script>
</body>
</html>
