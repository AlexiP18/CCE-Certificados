<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Estudiantes / Certificados - <?= htmlspecialchars($siteConfig['site_name']) ?></title>
    <?php if (!empty($siteConfig['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteConfig['favicon_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/verify/index.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/css/header_theme.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/institutional_theme.css">
</head>
<body>
    <?php
    $activeNav = 'verify';
    require __DIR__ . '/../components/top_nav.php';
    ?>

    <div class="container">
        <header>
            <img src="<?= htmlspecialchars(!empty($siteConfig['logo_header_url']) ? $siteConfig['logo_header_url'] : ($basePath . '/../assets/logos/logo-cce.png')) ?>" alt="Logo institucional" class="logo" onerror="this.style.display='none'">
            <h1>Verificación Estudiantes / Certificados</h1>
            <p class="subtitle">Consulta historial académico por cédula o verifica certificados por código.</p>
        </header>

        <section class="search-grid">
            <article class="search-card">
                <h2><i class="fas fa-id-card"></i> Verificar Estudiante</h2>
                <p>Ingresa la cédula para ver su historial académico y certificados emitidos.</p>
                <form class="search-form" method="GET" action="verify.php">
                    <input type="hidden" name="tipo" value="estudiante">
                    <input type="text"
                           name="cedula"
                           placeholder="Ej: 1755559711"
                           value="<?= htmlspecialchars($cedula ?? '') ?>"
                           required>
                    <button type="submit">
                        <i class="fas fa-user-check"></i> Buscar
                    </button>
                </form>
            </article>

            <article class="search-card">
                <h2><i class="fas fa-certificate"></i> Verificar Certificado</h2>
                <p>Ingresa el código del certificado para confirmar su autenticidad.</p>
                <form class="search-form" method="GET" action="verify.php">
                    <input type="hidden" name="tipo" value="certificado">
                    <input type="text"
                           name="code"
                           placeholder="Ej: CCE-A1B2C3D4"
                           value="<?= htmlspecialchars($codigo ?? '') ?>"
                           required
                           pattern="[A-Za-z0-9\-]+"
                           title="Solo letras, números y guiones">
                    <button type="submit">
                        <i class="fas fa-search"></i> Verificar
                    </button>
                </form>
            </article>
        </section>

        <?php if (!empty($errorBusqueda)): ?>
        <div class="card verify-card error">
            <div class="verify-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <h2>Error de Consulta</h2>
            <p><?= htmlspecialchars($errorBusqueda) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($busquedaRealizada && $tipoBusqueda === 'certificado'): ?>
            <?php if ($cert): ?>
            <div class="card verify-card success">
                <div class="verify-icon">✓</div>
                <h2>Certificado Válido</h2>

                <div class="cert-info">
                    <div class="info-row">
                        <span class="label">Código:</span>
                        <span class="value value-mono"><?= htmlspecialchars($cert['codigo']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Estudiante:</span>
                        <span class="value"><?= htmlspecialchars(($cert['estudiante_nombre'] ?? '') !== '' ? (string)$cert['estudiante_nombre'] : (string)($cert['nombre'] ?? 'No registrado')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Cédula:</span>
                        <span class="value"><?= htmlspecialchars($cert['estudiante_cedula'] ?: 'No registrada') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Grupo / Categoría:</span>
                        <span class="value">
                            <?= htmlspecialchars(trim(($cert['grupo_nombre'] ?? 'Sin grupo') . ' / ' . ($cert['categoria_nombre'] ?? 'Sin categoría'))) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Período:</span>
                        <span class="value"><?= htmlspecialchars($cert['periodo_nombre'] ?: 'Sin período') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Fecha del certificado:</span>
                        <span class="value"><?= !empty($cert['fecha']) ? date('d/m/Y', strtotime($cert['fecha'])) : '—' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Emitido el:</span>
                        <span class="value"><?= !empty($cert['fecha_creacion']) ? date('d/m/Y H:i', strtotime($cert['fecha_creacion'])) : '—' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Verificaciones:</span>
                        <span class="value"><?= (int)($cert['total_verificaciones'] ?? 0) ?></span>
                    </div>
                </div>

                <div class="cert-downloads">
                    <h3>Archivos del Certificado</h3>
                    <div class="download-buttons">
                        <?php if (!empty($cert['archivo_imagen_url'])): ?>
                        <a href="<?= htmlspecialchars($cert['archivo_imagen_url']) ?>" class="btn btn-secondary" target="_blank" rel="noopener">
                            <i class="fas fa-image"></i> Ver Imagen
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($cert['archivo_pdf_url'])): ?>
                        <a href="<?= htmlspecialchars($cert['archivo_pdf_url']) ?>" class="btn btn-primary" target="_blank" rel="noopener">
                            <i class="fas fa-file-pdf"></i> Ver PDF
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($cert['archivo_imagen_url']) && empty($cert['archivo_pdf_url'])): ?>
                    <p class="muted-note">Este certificado no tiene archivos públicos disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card verify-card error">
                <div class="verify-icon"><i class="fas fa-times-circle"></i></div>
                <h2>Certificado No Encontrado</h2>
                <p>El código ingresado no corresponde a un certificado activo.</p>
                <p class="code-searched">Código consultado: <strong><?= htmlspecialchars($codigo) ?></strong></p>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($busquedaRealizada && $tipoBusqueda === 'estudiante'): ?>
            <?php if ($estudiante): ?>
            <section class="card student-card student-modal-public">
                <div class="info-estudiante-head">
                    <div class="info-estudiante-head-main">
                        <h2><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($estudiante['nombre']) ?></h2>
                        <p><i class="fas fa-id-card"></i> Cédula: <?= htmlspecialchars($estudiante['cedula']) ?></p>
                    </div>
                    <div class="info-estudiante-head-stats">
                        <span class="info-stat-chip info-stat-chip-categorias"><i class="fas fa-folder-open"></i> Categorías: <?= (int)($resumenPublicoEstudiante['total_categorias'] ?? 0) ?></span>
                        <span class="info-stat-chip info-stat-chip-periodos"><i class="fas fa-calendar"></i> Períodos: <?= (int)($resumenPublicoEstudiante['total_periodos'] ?? 0) ?></span>
                        <span class="info-stat-chip info-stat-chip-aprobados"><i class="fas fa-check-circle"></i> Aprobados: <?= (int)($resumenPublicoEstudiante['total_aprobados'] ?? 0) ?></span>
                        <span class="info-stat-chip info-stat-chip-generados"><i class="fas fa-certificate"></i> Generados: <?= (int)($resumenPublicoEstudiante['total_generados'] ?? 0) ?></span>
                        <span class="info-stat-chip info-stat-chip-pendientes"><i class="fas fa-hourglass-half"></i> Pendientes: <?= (int)($resumenPublicoEstudiante['total_pendientes'] ?? 0) ?></span>
                    </div>
                </div>

                <div class="verify-info-main-layout">
                    <div id="verifyInfoGroupFilters" class="verify-info-group-filters">
                        <button type="button" class="verify-info-group-chip active" data-group-key="general">
                            <span><i class="fas fa-layer-group"></i></span>
                            <span>General</span>
                        </button>
                        <?php foreach ($gruposPublicos as $grp): ?>
                        <button type="button"
                                class="verify-info-group-chip"
                                data-group-key="<?= htmlspecialchars((string)$grp['grupo_key']) ?>"
                                style="--group-color: <?= htmlspecialchars((string)($grp['grupo_color'] ?? '#0f766e')) ?>;">
                            <span><?= htmlspecialchars((string)($grp['grupo_icono'] ?? '🏷️')) ?></span>
                            <span><?= htmlspecialchars((string)($grp['grupo_nombre'] ?? 'Sin grupo')) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="verify-info-main-content">
                        <div id="verifyStudentTabs" class="info-estudiante-tabs verify-student-tabs">
                            <?php foreach ($categoriasPublicas as $idx => $cat): ?>
                            <button type="button"
                                    class="info-estudiante-tab verify-student-tab"
                                    data-target="verify-slide-cat-<?= $idx ?>"
                                    data-group-key="<?= htmlspecialchars((string)($cat['grupo_key'] ?? '')) ?>">
                                <span><?= htmlspecialchars((string)($cat['categoria_icono'] ?? '📁')) ?></span>
                                <span><?= htmlspecialchars((string)($cat['categoria_nombre'] ?? 'Sin categoría')) ?></span>
                                <span class="info-estudiante-tab-count"><?= count($cat['periodos'] ?? []) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div id="verifyStudentSlides" class="info-estudiante-slides verify-student-slides">
                            <section class="info-estudiante-slide verify-student-slide active" data-slide-id="verify-slide-general" data-group-key="general">
                                <div class="info-general-grid">
                                    <article class="info-general-card info-general-card-main">
                                        <h5><i class="fas fa-circle-info"></i> Datos Públicos del Estudiante</h5>
                                        <div class="info-general-stack">
                                            <div class="info-general-row">
                                                <span>Nombre</span>
                                                <div class="info-general-row-value-wrap"><strong><?= htmlspecialchars((string)($estudiante['nombre'] ?? '—')) ?></strong></div>
                                            </div>
                                            <div class="info-general-row">
                                                <span>Cédula</span>
                                                <div class="info-general-row-value-wrap"><strong><?= htmlspecialchars((string)($estudiante['cedula'] ?? '—')) ?></strong></div>
                                            </div>
                                            <div class="info-general-row">
                                                <span>Matrículas registradas</span>
                                                <div class="info-general-row-value-wrap"><strong><?= (int)($estudiante['total_matriculas'] ?? 0) ?></strong></div>
                                            </div>
                                            <div class="info-general-row">
                                                <span>Certificados emitidos</span>
                                                <div class="info-general-row-value-wrap"><strong><?= (int)($estudiante['total_certificados'] ?? 0) ?></strong></div>
                                            </div>
                                        </div>
                                        <p class="verify-public-note">
                                            <i class="fas fa-shield-alt"></i> Esta consulta pública muestra únicamente información académica de verificación.
                                        </p>
                                    </article>
                                </div>

                                <section class="verify-insignias-card">
                                    <div class="verify-insignias-header">
                                        <h5><i class="fas fa-award"></i> Insignias Coleccionables</h5>
                                        <span class="verify-insignias-counter" title="Tipos desbloqueados">
                                            <?= (int)($insigniasColeccion['ganadas'] ?? 0) ?>/<?= (int)($insigniasColeccion['total'] ?? 0) ?>
                                        </span>
                                    </div>
                                    <p class="verify-insignias-note">
                                        <i class="fas fa-star"></i>
                                        Tipos desbloqueados: <?= (int)($insigniasColeccion['ganadas'] ?? 0) ?>.
                                        Faltantes: <?= (int)($insigniasColeccion['pendientes'] ?? 0) ?>.
                                        Total acumuladas: <?= (int)($insigniasColeccion['total_acumuladas'] ?? 0) ?>.
                                    </p>

                                    <?php if (!empty($insigniasColeccion['items'])): ?>
                                    <div class="verify-insignias-grid">
                                        <?php foreach ($insigniasColeccion['items'] as $insignia): ?>
                                        <?php $esGanada = !empty($insignia['ganada']); ?>
                                        <article class="verify-insignia-item <?= $esGanada ? 'is-earned' : 'is-locked' ?>">
                                            <div class="verify-insignia-icon-wrap">
                                                <?php if (!empty($insignia['icon_url'])): ?>
                                                <img src="<?= htmlspecialchars((string)$insignia['icon_url']) ?>" alt="Insignia <?= htmlspecialchars((string)($insignia['icon_label'] ?? '')) ?>">
                                                <?php else: ?>
                                                <i class="fas fa-award"></i>
                                                <?php endif; ?>
                                                <?php if (!$esGanada): ?>
                                                <span class="verify-insignia-lock"><i class="fas fa-lock"></i></span>
                                                <?php else: ?>
                                                <span class="verify-insignia-count"><?= (int)($insignia['cantidad'] ?? 0) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="verify-insignia-meta">
                                                <strong><?= htmlspecialchars((string)($insignia['icon_label'] ?? 'Insignia')) ?></strong>
                                                <span>Coleccionable destacado</span>
                                                <?php if ($esGanada): ?>
                                                <small>Obtenida <?= (int)($insignia['cantidad'] ?? 0) ?> vez/veces</small>
                                                <?php else: ?>
                                                <small>Insignia aún no desbloqueada</small>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="muted-note">No hay insignias disponibles para este estudiante todavía.</p>
                                    <?php endif; ?>
                                </section>

                                <h3 class="section-title"><i class="fas fa-certificate"></i> Certificados Emitidos</h3>
                                <?php if (!empty($certificadosEstudiante)): ?>
                                <div class="issued-certs-grid">
                                    <?php foreach ($certificadosEstudiante as $certItem): ?>
                                    <article class="issued-cert-card">
                                        <div class="issued-cert-head">
                                            <a class="cert-link" href="verify.php?tipo=certificado&code=<?= urlencode((string)$certItem['codigo']) ?>">
                                                <?= htmlspecialchars((string)$certItem['codigo']) ?>
                                            </a>
                                            <span class="issued-cert-date">
                                                <?= !empty($certItem['fecha']) ? date('d/m/Y', strtotime($certItem['fecha'])) : '—' ?>
                                            </span>
                                        </div>
                                        <p><strong>Grupo:</strong> <?= htmlspecialchars($certItem['grupo_nombre'] ?: 'Sin grupo') ?></p>
                                        <p><strong>Categoría:</strong> <?= htmlspecialchars($certItem['categoria_nombre'] ?: 'Sin categoría') ?></p>
                                        <p><strong>Período:</strong> <?= htmlspecialchars($certItem['periodo_nombre'] ?: 'Sin período') ?></p>
                                        <div class="issued-cert-actions">
                                            <?php if (!empty($certItem['archivo_imagen_url'])): ?>
                                            <a href="<?= htmlspecialchars($certItem['archivo_imagen_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary">
                                                <i class="fas fa-image"></i> Imagen
                                            </a>
                                            <?php endif; ?>
                                            <?php if (!empty($certItem['archivo_pdf_url'])): ?>
                                            <a href="<?= htmlspecialchars($certItem['archivo_pdf_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="muted-note">Este estudiante todavía no tiene certificados emitidos.</p>
                                <?php endif; ?>
                            </section>

                            <?php foreach ($categoriasPublicas as $catIndex => $cat): ?>
                            <section class="info-estudiante-slide verify-student-slide verify-category-slide"
                                     data-slide-id="verify-slide-cat-<?= $catIndex ?>"
                                     data-cat-id="<?= $catIndex ?>"
                                     data-group-key="<?= htmlspecialchars((string)($cat['grupo_key'] ?? '')) ?>">
                                <div class="verify-period-filters-bar">
                                    <div class="verify-period-badges-title"><i class="fas fa-calendar-day"></i> Filtros de Período</div>
                                    <div class="info-periodo-badges-wrap verify-period-badges-wrap verify-period-badges-wrap-inline">
                                        <?php if (!empty($cat['periodos'])): ?>
                                            <?php foreach (($cat['periodos'] ?? []) as $pIdx => $periodo): ?>
                                            <?php $activePeriodo = $pIdx === (int)($cat['default_period_index'] ?? 0); ?>
                                            <button type="button"
                                                    class="info-periodo-badge verify-period-badge <?= $activePeriodo ? 'active' : '' ?>"
                                                    data-target-detail="verify-cat-<?= $catIndex ?>-periodo-<?= $pIdx ?>"
                                                    data-cat-id="<?= $catIndex ?>">
                                                <i class="far fa-calendar-alt"></i>
                                                <span><?= htmlspecialchars((string)($periodo['periodo_nombre'] ?? 'Sin período')) ?></span>
                                                <?php if (!empty($periodo['es_destacado'])): ?><i class="fas fa-star info-periodo-badge-star"></i><?php endif; ?>
                                                <?php if (!empty($periodo['certificado_codigo'])): ?><i class="fas fa-certificate info-periodo-badge-cert"></i><?php endif; ?>
                                            </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="muted-note">No hay períodos registrados para esta categoría.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="verify-period-detail-col">
                                        <?php if (!empty($cat['periodos'])): ?>
                                        <?php foreach (($cat['periodos'] ?? []) as $pIdx => $periodo): ?>
                                        <?php
                                            $activePeriodo = $pIdx === (int)($cat['default_period_index'] ?? 0);
                                            $estadoCert = (string)($periodo['estado_certificado'] ?? 'pendiente');
                                            $estadoLabel = $estadoCert === 'aprobado'
                                                ? 'Aprobado'
                                                : ($estadoCert === 'generado' ? 'Generado' : 'Pendiente');
                                            $estadoClass = $estadoCert === 'aprobado'
                                                ? 'is-aprobado'
                                                : ($estadoCert === 'generado' ? 'is-generado' : 'is-pendiente');
                                        ?>
                                        <article class="info-periodo-card verify-period-detail <?= $activePeriodo ? 'active' : '' ?>" id="verify-cat-<?= $catIndex ?>-periodo-<?= $pIdx ?>">
                                            <div class="info-periodo-layout">
                                                <div class="info-periodo-layout-left">
                                                    <div class="info-periodo-stack">
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Período</span>
                                                            <strong class="info-periodo-row-value"><?= htmlspecialchars((string)($periodo['periodo_nombre'] ?? 'Sin período')) ?></strong>
                                                        </div>
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Matrícula</span>
                                                            <strong class="info-periodo-row-value"><?= !empty($periodo['fecha_matricula']) ? date('d/m/Y', strtotime((string)$periodo['fecha_matricula'])) : '—' ?></strong>
                                                        </div>
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Estado de matrícula</span>
                                                            <strong class="info-periodo-row-value"><?= htmlspecialchars(ucfirst((string)($periodo['estado_matricula'] ?? 'activo'))) ?></strong>
                                                        </div>
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Estado de certificado</span>
                                                            <strong class="info-periodo-row-value"><span class="info-cert-status <?= $estadoClass ?>"><?= $estadoLabel ?></span></strong>
                                                        </div>
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Código</span>
                                                            <strong class="info-periodo-row-value"><?= htmlspecialchars((string)($periodo['certificado_codigo'] ?: 'Sin certificado')) ?></strong>
                                                        </div>
                                                        <div class="info-periodo-row">
                                                            <span class="info-periodo-row-label">Fecha de certificado</span>
                                                            <strong class="info-periodo-row-value"><?= !empty($periodo['certificado_fecha']) ? date('d/m/Y', strtotime((string)$periodo['certificado_fecha'])) : '—' ?></strong>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($periodo['certificado_codigo'])): ?>
                                                    <div class="info-periodo-cert-actions">
                                                        <a class="btn btn-secondary" href="<?= htmlspecialchars((string)$periodo['verify_url']) ?>">
                                                            <i class="fas fa-shield-alt"></i> Verificar Código
                                                        </a>
                                                        <?php if (!empty($periodo['certificado_archivo_imagen_url'])): ?>
                                                        <a class="btn btn-secondary" href="<?= htmlspecialchars((string)$periodo['certificado_archivo_imagen_url']) ?>" target="_blank" rel="noopener">
                                                            <i class="fas fa-image"></i> Imagen
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php if (!empty($periodo['certificado_archivo_pdf_url'])): ?>
                                                        <a class="btn btn-primary" href="<?= htmlspecialchars((string)$periodo['certificado_archivo_pdf_url']) ?>" target="_blank" rel="noopener">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="info-periodo-layout-right">
                                                    <?php if (!empty($periodo['certificado_codigo'])): ?>
                                                    <div class="info-periodo-qr-wrap">
                                                        <div class="info-periodo-qr-box">
                                                            <img src="<?= htmlspecialchars((string)$periodo['qr_url']) ?>" alt="QR de verificación">
                                                        </div>
                                                        <span class="info-periodo-qr-label"><i class="fas fa-qrcode"></i> QR de Verificación</span>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="verify-period-empty">
                                                        <i class="fas fa-eye-slash"></i> Aún no hay certificado emitido para este período.
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </article>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="verify-period-empty">
                                            <i class="fas fa-calendar-times"></i> No se encontraron períodos para esta categoría.
                                        </div>
                                        <?php endif; ?>
                                </div>
                            </section>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php else: ?>
            <div class="card verify-card error">
                <div class="verify-icon"><i class="fas fa-user-times"></i></div>
                <h2>Estudiante No Encontrado</h2>
                <p>No existe un estudiante activo registrado con esa cédula.</p>
                <p class="code-searched">Cédula consultada: <strong><?= htmlspecialchars($cedula) ?></strong></p>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <footer>
            <p>&copy; <?= date('Y') ?> Casa de la Cultura CCE. Todos los derechos reservados.</p>
        </footer>
    </div>

    <?php if ($busquedaRealizada && $tipoBusqueda === 'estudiante' && $estudiante): ?>
    <script>
        (function () {
            const groupsContainer = document.getElementById('verifyInfoGroupFilters');
            const tabsContainer = document.getElementById('verifyStudentTabs');
            const slidesContainer = document.getElementById('verifyStudentSlides');
            if (!groupsContainer || !tabsContainer || !slidesContainer) return;

            let currentGroupKey = 'general';

            const getVisibleCategoryTabs = () => Array.from(
                tabsContainer.querySelectorAll('.verify-student-tab[data-group-key]')
            ).filter((tab) => !tab.classList.contains('is-hidden'));

            const applyGroupFilter = (groupKey) => {
                currentGroupKey = groupKey || 'general';
                const isGeneralMode = currentGroupKey === 'general';

                groupsContainer.querySelectorAll('.verify-info-group-chip').forEach((chip) => {
                    chip.classList.toggle('active', chip.dataset.groupKey === currentGroupKey);
                });

                tabsContainer.querySelectorAll('.verify-student-tab').forEach((tab) => {
                    const tabGroupKey = tab.dataset.groupKey || '';
                    const shouldShow = !isGeneralMode && tabGroupKey === currentGroupKey;
                    tab.classList.toggle('is-hidden', !shouldShow);
                });
                tabsContainer.classList.toggle('is-hidden', isGeneralMode);

                slidesContainer.querySelectorAll('.verify-student-slide').forEach((slide) => {
                    const slideGroupKey = slide.dataset.groupKey || '';
                    const isGeneralSlide = slideGroupKey === 'general';
                    const shouldShow = isGeneralMode ? isGeneralSlide : slideGroupKey === currentGroupKey;
                    slide.classList.toggle('is-hidden', !shouldShow);
                });

                if (isGeneralMode) {
                    switchSlide('verify-slide-general');
                    return;
                }

                const currentActiveTab = tabsContainer.querySelector('.verify-student-tab.active:not(.is-hidden)');
                if (currentActiveTab) {
                    switchSlide(currentActiveTab.dataset.target || '');
                    return;
                }

                const fallbackTab = getVisibleCategoryTabs()[0];
                if (fallbackTab) {
                    switchSlide(fallbackTab.dataset.target || '');
                }
            };

            const switchSlide = (targetId) => {
                tabsContainer.querySelectorAll('.verify-student-tab').forEach((tab) => {
                    tab.classList.toggle('active', tab.dataset.target === targetId);
                });
                slidesContainer.querySelectorAll('.verify-student-slide').forEach((slide) => {
                    slide.classList.toggle('active', slide.dataset.slideId === targetId);
                });
            };

            groupsContainer.addEventListener('click', (event) => {
                const chip = event.target.closest('.verify-info-group-chip');
                if (!chip) return;
                applyGroupFilter(chip.dataset.groupKey || 'all');
            });

            tabsContainer.addEventListener('click', (event) => {
                const btn = event.target.closest('.verify-student-tab');
                if (!btn || btn.classList.contains('is-hidden')) return;
                switchSlide(btn.dataset.target || '');
            });

            slidesContainer.querySelectorAll('.verify-category-slide').forEach((catSlide) => {
                catSlide.addEventListener('click', (event) => {
                    const badge = event.target.closest('.verify-period-badge');
                    if (!badge) return;

                    const catId = badge.dataset.catId || '';
                    const targetDetail = badge.dataset.targetDetail || '';
                    if (!catId || !targetDetail) return;

                    catSlide.querySelectorAll(`.verify-period-badge[data-cat-id="${catId}"]`).forEach((el) => {
                        el.classList.remove('active');
                    });
                    badge.classList.add('active');

                    catSlide.querySelectorAll('.verify-period-detail').forEach((detail) => {
                        detail.classList.remove('active');
                    });
                    const selectedDetail = document.getElementById(targetDetail);
                    if (selectedDetail && catSlide.contains(selectedDetail)) {
                        selectedDetail.classList.add('active');
                    }
                });
            });

            applyGroupFilter('general');
        })();
    </script>
    <?php endif; ?>
</body>
</html>
