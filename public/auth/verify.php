<?php
/**
 * Verificación pública de estudiantes/certificados - CCE Certificados
 * Permite:
 * - Verificar un certificado por código
 * - Consultar historial académico por cédula
 */
require_once '../../vendor/autoload.php';
require_once '../../includes/Auth.php';
require_once '../../includes/SiteSettings.php';
require_once '../../config/database.php';

use CCE\Certificate;

// Variables para la vista
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';
$usuario = Auth::user();
$siteConfig = SiteSettings::toViewModel(SiteSettings::get($pdo), $basePath);

/**
 * Normaliza cédula para búsqueda exacta.
 */
function normalizarCedulaBusqueda($rawCedula): string {
    return preg_replace('/\s+/', '', trim((string)$rawCedula));
}

/**
 * Normaliza código de certificado.
 */
function normalizarCodigoBusqueda($rawCodigo): string {
    return strtoupper(preg_replace('/\s+/', '', trim((string)$rawCodigo)));
}

/**
 * Resuelve URL pública para archivos de uploads (root/public).
 */
function buildUploadsPublicUrlVerify(?string $archivo): ?string {
    $archivo = trim((string)$archivo);
    if ($archivo === '') return null;

    $projectRoot = dirname(dirname(__DIR__));
    $projectFolder = basename($projectRoot);

    $relative = str_replace('\\', '/', ltrim($archivo, '/'));
    if (strpos($relative, 'public/uploads/') === 0) {
        $relative = substr($relative, strlen('public/uploads/'));
    } elseif (strpos($relative, 'uploads/') === 0) {
        $relative = substr($relative, strlen('uploads/'));
    }
    $relative = ltrim($relative, '/');

    $rootCandidate = $projectRoot . '/uploads/' . $relative;
    if (is_file($rootCandidate)) {
        return '/' . $projectFolder . '/uploads/' . $relative;
    }

    $publicCandidate = $projectRoot . '/public/uploads/' . $relative;
    if (is_file($publicCandidate)) {
        return '/' . $projectFolder . '/public/uploads/' . $relative;
    }

    return null;
}

/**
 * Resuelve URL pública de un archivo dentro de /public.
 */
function buildPublicAssetUrlVerify(string $relativePath): ?string {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if ($relativePath === '') return null;

    $projectRoot = dirname(dirname(__DIR__));
    $projectFolder = basename($projectRoot);
    $publicCandidate = $projectRoot . '/public/' . $relativePath;
    if (is_file($publicCandidate)) {
        return '/' . $projectFolder . '/public/' . $relativePath;
    }

    $rootCandidate = $projectRoot . '/' . $relativePath;
    if (is_file($rootCandidate)) {
        return '/' . $projectFolder . '/' . $relativePath;
    }

    return null;
}

/**
 * Verifica si una columna existe en una tabla.
 */
function columnExistsVerify(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

/**
 * Verifica si una tabla existe.
 */
function tableExistsVerify(PDO $pdo, string $table): bool {
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $cache[$table] = (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

/**
 * Normaliza clave de icono destacado.
 */
function normalizeDestacadoIconKeyVerify(?string $iconKey): string {
    $icon = strtolower(trim((string)$iconKey));
    $alias = [
        'estrella_dorada' => 'estrella',
        'verificado' => 'sello'
    ];
    return $alias[$icon] ?? $icon;
}

/**
 * Catálogo oficial de iconos coleccionables (igual al configurador de grupos).
 */
function getDestacadoIconCatalogVerify(): array {
    return [
        ['key' => 'estrella', 'label' => 'Estrella'],
        ['key' => 'medalla', 'label' => 'Medalla'],
        ['key' => 'trofeo', 'label' => 'Trofeo'],
        ['key' => 'corona', 'label' => 'Corona'],
        ['key' => 'laurel', 'label' => 'Laurel'],
        ['key' => 'sello', 'label' => 'Sello'],
        ['key' => 'insignia', 'label' => 'Insignia'],
        ['key' => 'cinta', 'label' => 'Cinta'],
        ['key' => 'lazo', 'label' => 'Lazo'],
        ['key' => 'lazo-insignia', 'label' => 'Lazo Insignia'],
        ['key' => 'capitan', 'label' => 'Capitán'],
        ['key' => 'rango', 'label' => 'Rango']
    ];
}

/**
 * Mapea el nombre de sticker a su archivo público.
 */
function resolveStickerByIconVerify(?string $iconName): ?string {
    $icon = normalizeDestacadoIconKeyVerify($iconName);
    $map = [
        'estrella' => 'estrella.png',
        'estrella_dorada' => 'estrella.png',
        'medalla' => 'medalla.png',
        'cinta' => 'cinta.png',
        'lazo' => 'lazo.png',
        'lazo-insignia' => 'lazo-insignia.png',
        'laurel' => 'laurel.png',
        'trofeo' => 'trofeo.png',
        'corona' => 'corona.png',
        'insignia' => 'insignia.png',
        'rango' => 'rango.png',
        'capitan' => 'capitan.png',
        'sello' => 'sello.png',
        'verificado' => 'sello.png',
        'certificado' => 'certificado.png'
    ];

    $file = $map[$icon] ?? '';
    if ($file === '' && $icon !== '' && preg_match('/^[a-z0-9\-_]+\.(png|jpe?g|webp|gif)$/i', $icon)) {
        $file = $icon;
    }

    if ($file !== '') {
        $url = buildPublicAssetUrlVerify('assets/stickers/' . $file);
        if ($url !== null) {
            return $url;
        }
    }

    return buildPublicAssetUrlVerify('assets/stickers/insignia.png');
}

/**
 * Resuelve URL final para insignia destacada de categoría/grupo.
 */
function resolveDestacadoBadgeUrlVerify(
    ?string $tipo,
    ?string $icono,
    ?string $imagen,
    int $categoriaId = 0
): ?string {
    $tipo = strtolower(trim((string)$tipo));
    $imagen = trim((string)$imagen);

    if ($tipo === 'imagen' && $imagen !== '') {
        if (preg_match('/^https?:\/\//i', $imagen) || strpos($imagen, '/') === 0) {
            return $imagen;
        }

        $candidates = [
            $imagen,
            'categorias/' . $categoriaId . '/' . $imagen,
            'categorias/' . $categoriaId . '/destacados/' . $imagen,
            'categorias/' . $categoriaId . '/firmas/' . $imagen
        ];
        foreach ($candidates as $candidate) {
            $url = buildUploadsPublicUrlVerify($candidate);
            if ($url !== null) {
                return $url;
            }
        }
    }

    return resolveStickerByIconVerify($icono);
}

// Estado de búsqueda
$tipoBusqueda = trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? ''));
$codigo = normalizarCodigoBusqueda($_GET['code'] ?? $_POST['code'] ?? '');
$cedula = normalizarCedulaBusqueda($_GET['cedula'] ?? $_POST['cedula'] ?? '');

if ($tipoBusqueda !== 'certificado' && $tipoBusqueda !== 'estudiante') {
    if ($codigo !== '') {
        $tipoBusqueda = 'certificado';
    } elseif ($cedula !== '') {
        $tipoBusqueda = 'estudiante';
    }
}

$busquedaRealizada = ($tipoBusqueda === 'certificado' && $codigo !== '')
    || ($tipoBusqueda === 'estudiante' && $cedula !== '');

$cert = null;
$estudiante = null;
$historialAcademico = [];
$certificadosEstudiante = [];
$categoriasPublicas = [];
$gruposPublicos = [];
$insigniasColeccion = [
    'total' => 0, // total de tipos
    'ganadas' => 0, // tipos desbloqueados
    'pendientes' => 0, // tipos faltantes
    'total_acumuladas' => 0, // suma de insignias (puede repetir tipo)
    'items' => []
];
$resumenPublicoEstudiante = [
    'total_categorias' => 0,
    'total_periodos' => 0,
    'total_aprobados' => 0,
    'total_pendientes' => 0,
    'total_generados' => 0
];
$errorBusqueda = '';

if ($tipoBusqueda === 'certificado' && $codigo !== '') {
    try {
        $sql = "
            SELECT c.*,
                   e.cedula AS estudiante_cedula,
                   e.nombre AS estudiante_nombre,
                   cat.nombre AS categoria_nombre,
                   g.nombre AS grupo_nombre,
                   g.icono AS grupo_icono,
                   g.color AS grupo_color,
                   p.nombre AS periodo_nombre,
                   (
                       SELECT COUNT(*)
                       FROM verificaciones v
                       WHERE v.certificado_id = c.id
                   ) AS total_verificaciones
            FROM certificados c
            LEFT JOIN estudiantes e ON c.estudiante_id = e.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            LEFT JOIN grupos g ON c.grupo_id = g.id
            LEFT JOIN periodos p ON c.periodo_id = p.id
            WHERE c.codigo = ? AND c.estado = 'activo'
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($cert) {
            $cert['archivo_pdf_url'] = buildUploadsPublicUrlVerify($cert['archivo_pdf'] ?? null);
            $cert['archivo_imagen_url'] = buildUploadsPublicUrlVerify($cert['archivo_imagen'] ?? null);

            // Registrar verificación si se encontró.
            $certificate = new Certificate($pdo);
            $certificate->registrarVerificacion(
                (int)$cert['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            );
        }
    } catch (Exception $e) {
        $errorBusqueda = 'No se pudo completar la verificación del certificado.';
        error_log("Error verificando certificado por código: " . $e->getMessage());
    }
}

if ($tipoBusqueda === 'estudiante' && $cedula !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT e.*,
                   (
                       SELECT COUNT(*)
                       FROM certificados c
                       WHERE c.estudiante_id = e.id AND c.estado = 'activo'
                   ) AS total_certificados,
                   (
                       SELECT COUNT(*)
                       FROM categoria_estudiantes ce
                       WHERE ce.estudiante_id = e.id
                   ) AS total_matriculas
            FROM estudiantes e
            WHERE e.cedula = ? AND e.activo = 1
            ORDER BY e.id DESC
            LIMIT 1
        ");
        $stmt->execute([$cedula]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($estudiante) {
            $hasGpTable = tableExistsVerify($pdo, 'grupo_plantillas');
            $hasGpActiva = $hasGpTable && columnExistsVerify($pdo, 'grupo_plantillas', 'es_activa');
            $hasCatDestTipo = columnExistsVerify($pdo, 'categorias', 'plantilla_destacado_tipo');
            $hasCatDestIcono = columnExistsVerify($pdo, 'categorias', 'plantilla_destacado_icono');
            $hasCatDestImagen = columnExistsVerify($pdo, 'categorias', 'plantilla_destacado_imagen');
            $hasGroupDestTipo = columnExistsVerify($pdo, 'grupos', 'destacado_tipo');
            $hasGroupDestIcono = columnExistsVerify($pdo, 'grupos', 'destacado_icono');
            $hasGroupDestImagen = columnExistsVerify($pdo, 'grupos', 'destacado_imagen');
            $hasGpDestTipo = $hasGpTable && columnExistsVerify($pdo, 'grupo_plantillas', 'destacado_tipo');
            $hasGpDestIcono = $hasGpTable && columnExistsVerify($pdo, 'grupo_plantillas', 'destacado_icono');
            $hasGpDestImagen = $hasGpTable && columnExistsVerify($pdo, 'grupo_plantillas', 'destacado_imagen');

            $sqlCatDestTipo = $hasCatDestTipo
                ? "cat.plantilla_destacado_tipo AS categoria_destacado_tipo"
                : "'icono' AS categoria_destacado_tipo";
            $sqlCatDestIcono = $hasCatDestIcono
                ? "cat.plantilla_destacado_icono AS categoria_destacado_icono"
                : "NULL AS categoria_destacado_icono";
            $sqlCatDestImagen = $hasCatDestImagen
                ? "cat.plantilla_destacado_imagen AS categoria_destacado_imagen"
                : "NULL AS categoria_destacado_imagen";
            $sqlGroupDestTipo = $hasGpDestTipo
                ? "gp.destacado_tipo AS grupo_destacado_tipo"
                : ($hasGroupDestTipo ? "g.destacado_tipo AS grupo_destacado_tipo" : "'icono' AS grupo_destacado_tipo");
            $sqlGroupDestIcono = $hasGpDestIcono
                ? "gp.destacado_icono AS grupo_destacado_icono"
                : ($hasGroupDestIcono ? "g.destacado_icono AS grupo_destacado_icono" : "'estrella' AS grupo_destacado_icono");
            $sqlGroupDestImagen = $hasGpDestImagen
                ? "gp.destacado_imagen AS grupo_destacado_imagen"
                : ($hasGroupDestImagen ? "g.destacado_imagen AS grupo_destacado_imagen" : "NULL AS grupo_destacado_imagen");
            $sqlGroupJoin = '';
            if ($hasGpTable) {
                $sqlGroupJoin = $hasGpActiva
                    ? "LEFT JOIN grupo_plantillas gp ON gp.grupo_id = g.id AND gp.es_activa = 1"
                    : "LEFT JOIN grupo_plantillas gp ON gp.id = (
                        SELECT gp2.id
                        FROM grupo_plantillas gp2
                        WHERE gp2.grupo_id = g.id
                        ORDER BY gp2.id DESC
                        LIMIT 1
                    )";
            }

            // Historial académico por matrícula en categoría/período.
            $stmtHist = $pdo->prepare("
                SELECT ce.id,
                       ce.fecha_matricula,
                       ce.estado,
                       ce.es_destacado,
                       cat.id AS categoria_id,
                       cat.nombre AS categoria_nombre,
                       cat.icono AS categoria_icono,
                       {$sqlCatDestTipo},
                       {$sqlCatDestIcono},
                       {$sqlCatDestImagen},
                       g.id AS grupo_id,
                       g.nombre AS grupo_nombre,
                       g.icono AS grupo_icono,
                       g.color AS grupo_color,
                       {$sqlGroupDestTipo},
                       {$sqlGroupDestIcono},
                       {$sqlGroupDestImagen},
                       p.id AS periodo_id,
                       p.nombre AS periodo_nombre,
                       p.fecha_inicio AS periodo_fecha_inicio,
                       p.fecha_fin AS periodo_fecha_fin,
                       cert.id AS certificado_id,
                       cert.codigo AS certificado_codigo,
                       cert.fecha AS certificado_fecha,
                       cert.fecha_creacion AS certificado_fecha_creacion,
                       cert.archivo_pdf AS certificado_archivo_pdf,
                       cert.archivo_imagen AS certificado_archivo_imagen,
                       cert.aprobado AS certificado_aprobado
                FROM categoria_estudiantes ce
                INNER JOIN categorias cat ON ce.categoria_id = cat.id
                INNER JOIN grupos g ON cat.grupo_id = g.id
                {$sqlGroupJoin}
                LEFT JOIN periodos p ON ce.periodo_id = p.id
                LEFT JOIN certificados cert ON cert.id = (
                    SELECT c2.id
                    FROM certificados c2
                    WHERE c2.estudiante_id = ce.estudiante_id
                      AND c2.categoria_id = ce.categoria_id
                      AND (
                          (c2.periodo_id = ce.periodo_id)
                          OR (c2.periodo_id IS NULL AND ce.periodo_id IS NULL)
                      )
                    ORDER BY c2.fecha_creacion DESC, c2.id DESC
                    LIMIT 1
                )
                WHERE ce.estudiante_id = ?
                ORDER BY COALESCE(p.fecha_inicio, '1900-01-01') DESC,
                         ce.fecha_matricula DESC,
                         cat.nombre ASC
            ");
            $stmtHist->execute([(int)$estudiante['id']]);
            $historialAcademico = $stmtHist->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($historialAcademico as &$item) {
                $item['certificado_archivo_pdf_url'] = buildUploadsPublicUrlVerify($item['certificado_archivo_pdf'] ?? null);
                $item['certificado_archivo_imagen_url'] = buildUploadsPublicUrlVerify($item['certificado_archivo_imagen'] ?? null);
            }
            unset($item);

            // Lista consolidada de certificados emitidos al estudiante.
            $stmtCerts = $pdo->prepare("
                SELECT c.id,
                       c.codigo,
                       c.fecha,
                       c.fecha_creacion,
                       c.razon,
                       c.archivo_pdf,
                       c.archivo_imagen,
                       c.aprobado,
                       cat.nombre AS categoria_nombre,
                       g.nombre AS grupo_nombre,
                       p.nombre AS periodo_nombre
                FROM certificados c
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                LEFT JOIN grupos g ON c.grupo_id = g.id
                LEFT JOIN periodos p ON c.periodo_id = p.id
                WHERE c.estudiante_id = ? AND c.estado = 'activo'
                ORDER BY c.fecha_creacion DESC, c.id DESC
            ");
            $stmtCerts->execute([(int)$estudiante['id']]);
            $certificadosEstudiante = $stmtCerts->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($certificadosEstudiante as &$certItem) {
                $certItem['archivo_pdf_url'] = buildUploadsPublicUrlVerify($certItem['archivo_pdf'] ?? null);
                $certItem['archivo_imagen_url'] = buildUploadsPublicUrlVerify($certItem['archivo_imagen'] ?? null);
            }
            unset($certItem);

            // Estructura pública estilo modal de información por categorías/períodos.
            $tmpCategorias = [];
            foreach ($historialAcademico as $item) {
                $categoriaId = (int)($item['categoria_id'] ?? 0);
                $grupoId = (int)($item['grupo_id'] ?? 0);
                $grupoNombre = (string)($item['grupo_nombre'] ?? 'Sin grupo');
                $grupoKey = $grupoId > 0
                    ? ('g-' . $grupoId)
                    : ('g-' . substr(md5(strtolower(trim($grupoNombre))), 0, 10));
                $categoriaKey = $categoriaId > 0 ? (string)$categoriaId : 'cat-' . md5(json_encode([
                    $item['grupo_nombre'] ?? '',
                    $item['categoria_nombre'] ?? ''
                ]));

                if (!isset($tmpCategorias[$categoriaKey])) {
                    $tmpCategorias[$categoriaKey] = [
                        'key' => $categoriaKey,
                        'id' => $categoriaId,
                        'categoria_nombre' => (string)($item['categoria_nombre'] ?? 'Sin categoría'),
                        'categoria_icono' => (string)($item['categoria_icono'] ?? '📁'),
                        'categoria_destacado_tipo' => (string)($item['categoria_destacado_tipo'] ?? 'icono'),
                        'categoria_destacado_icono' => (string)($item['categoria_destacado_icono'] ?? ''),
                        'categoria_destacado_imagen' => (string)($item['categoria_destacado_imagen'] ?? ''),
                        'grupo_id' => $grupoId,
                        'grupo_key' => $grupoKey,
                        'grupo_nombre' => $grupoNombre,
                        'grupo_icono' => (string)($item['grupo_icono'] ?? '🏷️'),
                        'grupo_color' => (string)($item['grupo_color'] ?? '#94a3b8'),
                        'grupo_destacado_tipo' => (string)($item['grupo_destacado_tipo'] ?? 'icono'),
                        'grupo_destacado_icono' => (string)($item['grupo_destacado_icono'] ?? 'estrella'),
                        'grupo_destacado_imagen' => (string)($item['grupo_destacado_imagen'] ?? ''),
                        'periodos' => []
                    ];
                }

                $codigoCert = trim((string)($item['certificado_codigo'] ?? ''));
                $certAprobado = (int)($item['certificado_aprobado'] ?? 0) === 1;
                $certGenerado = $codigoCert !== '';
                $estadoCert = 'pendiente';
                if ($certAprobado) {
                    $estadoCert = 'aprobado';
                } elseif ($certGenerado) {
                    $estadoCert = 'generado';
                }

                $periodoUnique = implode('|', [
                    (string)($item['periodo_id'] ?? ''),
                    (string)($item['periodo_nombre'] ?? ''),
                    (string)($item['fecha_matricula'] ?? ''),
                    (string)($item['id'] ?? '')
                ]);

                $tmpCategorias[$categoriaKey]['periodos'][] = [
                    'periodo_unique' => $periodoUnique,
                    'periodo_id' => (int)($item['periodo_id'] ?? 0),
                    'periodo_nombre' => (string)($item['periodo_nombre'] ?? 'Sin período'),
                    'periodo_fecha_inicio' => (string)($item['periodo_fecha_inicio'] ?? ''),
                    'periodo_fecha_fin' => (string)($item['periodo_fecha_fin'] ?? ''),
                    'fecha_matricula' => (string)($item['fecha_matricula'] ?? ''),
                    'estado_matricula' => (string)($item['estado'] ?? 'activo'),
                    'es_destacado' => (int)($item['es_destacado'] ?? 0) === 1,
                    'certificado_codigo' => $codigoCert,
                    'certificado_aprobado' => $certAprobado,
                    'certificado_fecha' => (string)($item['certificado_fecha'] ?? ''),
                    'certificado_fecha_creacion' => (string)($item['certificado_fecha_creacion'] ?? ''),
                    'certificado_archivo_pdf_url' => $item['certificado_archivo_pdf_url'] ?? null,
                    'certificado_archivo_imagen_url' => $item['certificado_archivo_imagen_url'] ?? null,
                    'estado_certificado' => $estadoCert,
                    'verify_url' => $codigoCert !== '' ? ('verify.php?tipo=certificado&code=' . urlencode($codigoCert)) : '',
                    'qr_url' => $codigoCert !== '' ? ('../api/certificados/qr.php?codigo=' . urlencode($codigoCert)) : ''
                ];

                $resumenPublicoEstudiante['total_periodos']++;
                if ($estadoCert === 'aprobado') {
                    $resumenPublicoEstudiante['total_aprobados']++;
                } elseif ($estadoCert === 'pendiente') {
                    $resumenPublicoEstudiante['total_pendientes']++;
                } elseif ($estadoCert === 'generado') {
                    $resumenPublicoEstudiante['total_generados']++;
                }
            }

            // Escoger período activo inicial por categoría (preferencia: aprobado > generado > pendiente).
            foreach ($tmpCategorias as &$categoriaTmp) {
                $defaultIndex = 0;
                foreach ($categoriaTmp['periodos'] as $idx => $periodoTmp) {
                    if (($periodoTmp['estado_certificado'] ?? '') === 'aprobado') {
                        $defaultIndex = $idx;
                        break;
                    }
                    if (($periodoTmp['estado_certificado'] ?? '') === 'generado' && $defaultIndex === 0) {
                        $defaultIndex = $idx;
                    }
                }
                $categoriaTmp['default_period_index'] = $defaultIndex;
            }
            unset($categoriaTmp);

            $categoriasPublicas = array_values($tmpCategorias);
            usort($categoriasPublicas, static function (array $a, array $b): int {
                $grupoCmp = strcasecmp((string)($a['grupo_nombre'] ?? ''), (string)($b['grupo_nombre'] ?? ''));
                if ($grupoCmp !== 0) return $grupoCmp;
                return strcasecmp((string)($a['categoria_nombre'] ?? ''), (string)($b['categoria_nombre'] ?? ''));
            });

            $catalogoInsignias = getDestacadoIconCatalogVerify();
            $conteoInsignias = [];
            foreach ($catalogoInsignias as $insDef) {
                $key = (string)($insDef['key'] ?? '');
                if ($key !== '') {
                    $conteoInsignias[$key] = 0;
                }
            }

            foreach ($historialAcademico as $hist) {
                if ((int)($hist['es_destacado'] ?? 0) !== 1) {
                    continue;
                }

                $tipo = trim((string)($hist['categoria_destacado_tipo'] ?? ''));
                if ($tipo === '') {
                    $tipo = trim((string)($hist['grupo_destacado_tipo'] ?? 'icono'));
                }

                // Para coleccionables por ícono contamos solo configuraciones de tipo ícono.
                if (strtolower($tipo) !== 'icono') {
                    continue;
                }

                $icono = trim((string)($hist['categoria_destacado_icono'] ?? ''));
                if ($icono === '') {
                    $icono = trim((string)($hist['grupo_destacado_icono'] ?? 'estrella'));
                }
                $icono = normalizeDestacadoIconKeyVerify($icono);
                if ($icono === '') {
                    $icono = 'estrella';
                }

                if (!array_key_exists($icono, $conteoInsignias)) {
                    $conteoInsignias[$icono] = 0;
                }
                $conteoInsignias[$icono]++;
            }

            $insigniaItems = [];
            foreach ($catalogoInsignias as $insDef) {
                $iconKey = (string)($insDef['key'] ?? '');
                if ($iconKey === '') {
                    continue;
                }
                $cantidad = (int)($conteoInsignias[$iconKey] ?? 0);
                $insigniaItems[] = [
                    'icon_key' => $iconKey,
                    'icon_label' => (string)($insDef['label'] ?? ucfirst($iconKey)),
                    'icon_url' => resolveStickerByIconVerify($iconKey),
                    'cantidad' => $cantidad,
                    'ganada' => $cantidad > 0
                ];
            }

            usort($insigniaItems, static function (array $a, array $b): int {
                $ganadaCmp = ((int)$b['ganada']) <=> ((int)$a['ganada']);
                if ($ganadaCmp !== 0) return $ganadaCmp;
                $cantidadCmp = ((int)($b['cantidad'] ?? 0)) <=> ((int)($a['cantidad'] ?? 0));
                if ($cantidadCmp !== 0) return $cantidadCmp;
                return strcasecmp((string)($a['icon_label'] ?? ''), (string)($b['icon_label'] ?? ''));
            });

            $insigniasColeccion['items'] = $insigniaItems;
            $insigniasColeccion['total'] = count($insigniaItems);
            $insigniasColeccion['ganadas'] = count(array_filter($insigniaItems, static function (array $item): bool {
                return (int)($item['cantidad'] ?? 0) > 0;
            }));
            $insigniasColeccion['pendientes'] = max(0, $insigniasColeccion['total'] - $insigniasColeccion['ganadas']);
            $insigniasColeccion['total_acumuladas'] = (int)array_sum(array_map(static function (array $item): int {
                return (int)($item['cantidad'] ?? 0);
            }, $insigniaItems));

            $tmpGrupos = [];
            foreach ($categoriasPublicas as $cat) {
                $grupoKey = (string)($cat['grupo_key'] ?? '');
                if ($grupoKey === '' || isset($tmpGrupos[$grupoKey])) {
                    continue;
                }
                $tmpGrupos[$grupoKey] = [
                    'grupo_key' => $grupoKey,
                    'grupo_id' => (int)($cat['grupo_id'] ?? 0),
                    'grupo_nombre' => (string)($cat['grupo_nombre'] ?? 'Sin grupo'),
                    'grupo_icono' => (string)($cat['grupo_icono'] ?? '🏷️'),
                    'grupo_color' => (string)($cat['grupo_color'] ?? '#94a3b8')
                ];
            }
            $gruposPublicos = array_values($tmpGrupos);

            $resumenPublicoEstudiante['total_categorias'] = count($categoriasPublicas);
        }
    } catch (Exception $e) {
        $errorBusqueda = 'No se pudo consultar el historial del estudiante.';
        error_log("Error verificando estudiante por cédula: " . $e->getMessage());
    }
}

// Cargar la vista
require_once '../../app/Views/verify/index.php';
