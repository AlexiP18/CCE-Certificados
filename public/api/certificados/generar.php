<?php
/**
 * API para generar certificados en batch
 * Los certificados se generan al vuelo sin guardar archivos físicos permanentes
 * Solo se guarda la metadata de generación
 */
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/Certificate.php';

// Verificar autenticación
Auth::requireAuth();

// Usuario actual
$usuario = Auth::user();
// Nota: La generación del certificado ES la aprobación en sí misma
// Por lo tanto siempre se crea con estado='activo'

$pdo = getConnection();

/**
 * Normaliza el valor de archivo_imagen a una URL pública en /uploads.
 * Acepta formatos guardados como "cert_xxx.png" o "uploads/cert_xxx.png".
 */
function buildUploadsPublicUrl($archivoImagen) {
    if (empty($archivoImagen)) return null;
    $archivoImagen = trim((string)$archivoImagen);
    if ($archivoImagen === '') return null;

    if (strpos($archivoImagen, 'uploads/') === 0) {
        $archivoImagen = substr($archivoImagen, strlen('uploads/'));
    }

    return '/CCE-Certificados/uploads/' . ltrim($archivoImagen, '/');
}

/**
 * Compatibilidad: en esquemas nuevos existe aprobacion explicita.
 * Si las columnas no existen aun, este update se ignora sin romper flujo.
 */
function marcarCertificadoAprobado(PDO $pdo, $certificadoId): void {
    if (!$certificadoId) return;
    try {
        $stmt = $pdo->prepare("UPDATE certificados SET aprobado = 1, requiere_aprobacion = 0, fecha_aprobacion = NOW() WHERE id = ?");
        $stmt->execute([$certificadoId]);
    } catch (Throwable $e) {
        // No-op para mantener compatibilidad con instalaciones sin columnas de aprobacion.
    }
}

// Inicializar $data
$data = [];

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Si es POST con JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $json = file_get_contents('php://input');
    if ($json) {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Error al parsear JSON: ' . json_last_error_msg()
            ]);
            exit;
        }
        if ($data && isset($data['action'])) {
            $action = $data['action'];
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST con form data
    $data = $_POST;
}

try {
    // Establecer JSON header por defecto (se sobrescribirá para descargas)
    if (!in_array($action, ['descargar_pdf', 'descargar_imagen'])) {
        header('Content-Type: application/json');
    }
    
    switch ($action) {
        case 'verificar_plantilla':
            // Verificar si hay plantilla configurada para un grupo
            $grupo_id = $data['grupo_id'] ?? $_GET['grupo_id'] ?? 0;
            $categoria_id = $data['categoria_id'] ?? $_GET['categoria_id'] ?? 0;

            $existeGrupoPlantillas = false;
            $existeCategoriaPlantillas = false;
            try {
                $pdo->query("SELECT 1 FROM grupo_plantillas LIMIT 1");
                $existeGrupoPlantillas = true;
            } catch (Exception $e) {
                $existeGrupoPlantillas = false;
            }
            try {
                $pdo->query("SELECT 1 FROM categoria_plantillas LIMIT 1");
                $existeCategoriaPlantillas = true;
            } catch (Exception $e) {
                $existeCategoriaPlantillas = false;
            }
            
            $resultado = [
                'grupo_id' => $grupo_id,
                'categoria_id' => $categoria_id,
                'plantilla_global' => null,
                'plantilla_grupo' => null,
                'plantilla_categoria' => null,
                'snapshot_grupo' => null,
                'snapshot_categoria' => null
            ];
            
            // Verificar plantilla global (Table removed - Skipping global check)
            $resultado['plantilla_global'] = null;
            
            // Verificar plantilla de grupo
            if ($grupo_id) {
                if ($existeGrupoPlantillas) {
                    $stmt = $pdo->prepare("SELECT id, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
                    $stmt->execute([$grupo_id]);
                    $resultado['plantilla_grupo'] = $stmt->fetch(PDO::FETCH_ASSOC);

                    // También ver todas las plantillas del grupo
                    $stmt = $pdo->prepare("SELECT id, archivo, es_activa FROM grupo_plantillas WHERE grupo_id = ?");
                    $stmt->execute([$grupo_id]);
                    $resultado['todas_plantillas_grupo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? LIMIT 1");
                    $stmt->execute([$grupo_id]);
                    $grupoLegacy = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $archivoLegacyGrupo = trim((string)($grupoLegacy['plantilla'] ?? ''));
                    if ($archivoLegacyGrupo !== '') {
                        $resultado['plantilla_grupo'] = [
                            'id' => null,
                            'archivo' => $archivoLegacyGrupo,
                            'es_activa' => 1,
                            'origen' => 'legacy_grupo'
                        ];
                    }
                    $resultado['todas_plantillas_grupo'] = $resultado['plantilla_grupo'] ? [$resultado['plantilla_grupo']] : [];
                }

                // Último certificado generado en el grupo (snapshot)
                $stmt = $pdo->prepare("
                    SELECT codigo, archivo_imagen, fecha_creacion
                    FROM certificados
                    WHERE grupo_id = ?
                      AND archivo_imagen IS NOT NULL
                      AND archivo_imagen <> ''
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $stmt->execute([$grupo_id]);
                $snapGrupo = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($snapGrupo) {
                    $resultado['snapshot_grupo'] = [
                        'codigo' => $snapGrupo['codigo'] ?? null,
                        'fecha_creacion' => $snapGrupo['fecha_creacion'] ?? null,
                        'url' => buildUploadsPublicUrl($snapGrupo['archivo_imagen'] ?? null)
                    ];
                }
            }
            
            // Verificar plantilla de categoría
            if ($categoria_id) {
                if ($existeCategoriaPlantillas) {
                    $stmt = $pdo->prepare("SELECT id, archivo, es_activa FROM categoria_plantillas WHERE categoria_id = ? AND es_activa = 1 LIMIT 1");
                    $stmt->execute([$categoria_id]);
                    $resultado['plantilla_categoria'] = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                // Compatibilidad legacy: algunas categorías usan plantilla_archivo en tabla categorias
                // y no necesariamente tienen registro activo en categoria_plantillas.
                if (!$resultado['plantilla_categoria']) {
                    // IMPORTANTE: usar SELECT * para no romper en instalaciones donde
                    // no existen columnas legacy específicas.
                    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? LIMIT 1");
                    $stmt->execute([$categoria_id]);
                    $catLegacy = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                    $usaPropiaLegacy = isset($catLegacy['usar_plantilla_propia'])
                        ? ((int)$catLegacy['usar_plantilla_propia'] === 1)
                        : true; // Si no existe la columna, asumimos legacy activa si hay archivo.
                    $archivoLegacy = trim((string)($catLegacy['plantilla_archivo'] ?? ''));

                    if ($archivoLegacy !== '' && $usaPropiaLegacy) {
                        $resultado['plantilla_categoria'] = [
                            'id' => null,
                            'archivo' => $archivoLegacy,
                            'es_activa' => 1,
                            'origen' => 'legacy_categoria'
                        ];
                    }
                }

                // Último certificado generado de la categoría (snapshot)
                if ($grupo_id) {
                    $stmt = $pdo->prepare("
                        SELECT codigo, archivo_imagen, fecha_creacion
                        FROM certificados
                        WHERE grupo_id = ?
                          AND categoria_id = ?
                          AND archivo_imagen IS NOT NULL
                          AND archivo_imagen <> ''
                        ORDER BY id DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$grupo_id, $categoria_id]);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT codigo, archivo_imagen, fecha_creacion
                        FROM certificados
                        WHERE categoria_id = ?
                          AND archivo_imagen IS NOT NULL
                          AND archivo_imagen <> ''
                        ORDER BY id DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$categoria_id]);
                }

                $snapCategoria = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($snapCategoria) {
                    $resultado['snapshot_categoria'] = [
                        'codigo' => $snapCategoria['codigo'] ?? null,
                        'fecha_creacion' => $snapCategoria['fecha_creacion'] ?? null,
                        'url' => buildUploadsPublicUrl($snapCategoria['archivo_imagen'] ?? null)
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'diagnostico' => $resultado
            ]);
            break;
            
        case 'desaprobar':
            // Eliminar solo el registro de aprobación (desaprobar)
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $estudiante_id = $_POST['estudiante_id'] ?? 0;
            $grupo_id = $_POST['grupo_id'] ?? 0;
            $periodo_id = $_POST['periodo_id'] ?? null;
            
            if (empty($categoria_id) || empty($estudiante_id)) {
                throw new Exception('Faltan datos para desaprobar');
            }
            
            // Obtener el nombre del estudiante porque los certificados guardan el nombre 
            $stmt = $pdo->prepare("SELECT nombre FROM estudiantes WHERE id = ?");
            $stmt->execute([$estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            $sql = "DELETE FROM certificados WHERE categoria_id = ? AND nombre = ? AND periodo_id <=> ?";
            $params = [$categoria_id, $estudiante['nombre'], $periodo_id];
            if (!empty($grupo_id)) {
                $sql .= " AND grupo_id = ?";
                $params[] = $grupo_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante desaprobado exitosamente'
            ]);
            break;
            
        case 'obtener_estudiantes':
            // Obtener estudiantes matriculados en una categoría para generar certificados
            $categoria_id = $data['categoria_id'] ?? $_GET['categoria_id'] ?? 0;
            $periodo_id = $data['periodo_id'] ?? $_GET['periodo_id'] ?? null;
            
            if (empty($categoria_id)) {
                throw new Exception('Categoría no especificada');
            }
            
            // Obtener info de la categoría
            $stmt = $pdo->prepare("
                SELECT c.*, g.nombre as grupo_nombre, g.color as grupo_color, g.icono as grupo_icono
                FROM categorias c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE c.id = ?
            ");
            $stmt->execute([$categoria_id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                throw new Exception('Categoría no encontrada');
            }
            
            // Obtener estudiantes matriculados
            $sql = "
                SELECT 
                    e.*,
                    ce.id as matricula_id,
                    ce.fecha_matricula,
                    ce.estado as estado_matricula,
                    cert.id as certificado_id,
                    cert.codigo as certificado_codigo,
                    cert.fecha_creacion as certificado_fecha,
                    cert.fechas_generacion as certificado_fechas_generacion
                FROM categoria_estudiantes ce
                JOIN estudiantes e ON ce.estudiante_id = e.id
                LEFT JOIN certificados cert ON cert.categoria_id = ce.categoria_id 
                    AND cert.nombre = e.nombre 
                    AND cert.grupo_id = ?
                    AND cert.periodo_id <=> ?
                WHERE ce.categoria_id = ?
                AND ce.estado = 'activo'
            ";
            
            $params = [$categoria['grupo_id'], $periodo_id, $categoria_id];
            
            if ($periodo_id) {
                $sql .= " AND ce.periodo_id = ?";
                $params[] = $periodo_id;
            } else {
                $sql .= " AND ce.periodo_id IS NULL";
            }
            
            $sql .= " ORDER BY e.nombre ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'categoria' => $categoria,
                'estudiantes' => $estudiantes,
                'total' => count($estudiantes)
            ]);
            break;
            
        case 'toggle_aprobacion_batch':
            $categoria_id = $data['categoria_id'] ?? 0;
            $periodo_id = $data['periodo_id'] ?? null;
            $grupo_id = $data['grupo_id'] ?? 0;
            $estudiantes_ids = $data['estudiantes_ids'] ?? [];
            $fecha = $data['fecha'] ?? date('Y-m-d');
            $estadoCertificado = 'activo';
            
            if (empty($categoria_id) || empty($estudiantes_ids)) {
                throw new Exception('Faltan datos para procesar (Categoría o Estudiantes)');
            }
            
            // Obtener info de categoría
            $stmt = $pdo->prepare("SELECT c.*, g.id as grupo_id, g.nombre as grupo_nombre FROM categorias c JOIN grupos g ON c.grupo_id = g.id WHERE c.id = ?");
            $stmt->execute([$categoria_id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$categoria) throw new Exception('Categoría no encontrada');
            
            $placeholders = str_repeat('?,', count($estudiantes_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id IN ($placeholders)");
            $stmt->execute($estudiantes_ids);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $certificate = new \CCE\Certificate($pdo);
            
            $aprobados = 0;
            $desaprobados = 0;
            $errores = 0;
            
            foreach ($estudiantes as $estudiante) {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM certificados WHERE categoria_id = ? AND nombre = ? AND grupo_id = ? AND periodo_id <=> ?");
                    $stmt->execute([$categoria_id, $estudiante['nombre'], $categoria['grupo_id'], $periodo_id]);
                    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existente) {
                        // Ya existe -> Desaprobar
                        $stmtDel = $pdo->prepare("DELETE FROM certificados WHERE id = ?");
                        $stmtDel->execute([$existente['id']]);
                        $desaprobados++;
                    } else {
                        // No existe -> Aprobar (insertar en BD)
                        $codigo = $certificate->generateCode();
                        $fechasGeneracion = json_encode([date('Y-m-d H:i:s')]);
                        
                        $stmtIns = $pdo->prepare("
                            INSERT INTO certificados (codigo, nombre, razon, fecha, grupo_id, categoria_id, periodo_id, estudiante_id, fechas_generacion, estado)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtIns->execute([
                            $codigo,
                            $estudiante['nombre'],
                            'Por su participación',
                            $fecha,
                            $categoria['grupo_id'],
                            $categoria_id,
                            $periodo_id,
                            $estudiante['id'],
                            $fechasGeneracion,
                            $estadoCertificado
                        ]);
                        marcarCertificadoAprobado($pdo, $pdo->lastInsertId());
                        $aprobados++;
                    }
                } catch (Exception $e) {
                    $errores++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'resumen' => [
                    'aprobados' => $aprobados,
                    'desaprobados' => $desaprobados,
                    'errores' => $errores
                ]
            ]);
            break;
            
        case 'generar_batch':
            // Generar certificados para múltiples estudiantes
            $categoria_id = $data['categoria_id'] ?? 0;
            $periodo_id = $data['periodo_id'] ?? null;
            $grupo_id = $data['grupo_id'] ?? 0;
            $estudiantes_ids = $data['estudiantes_ids'] ?? [];
            $estudiantes_data = $data['estudiantes_data'] ?? []; // Info adicional (es_destacado, etc.)
            $fecha = $data['fecha'] ?? date('Y-m-d');
            $razon = $data['razon'] ?? null;
            $guardar_archivos = $data['guardar_archivos'] ?? false; // Por defecto NO guardar archivos
            $skip_template_check = $data['skip_template_check'] ?? false;
            $estadoCertificado = 'activo'; // La generación ES la aprobación
            
            if (empty($categoria_id)) {
                throw new Exception('Falta el ID de categoría');
            }
            if (empty($estudiantes_ids)) {
                throw new Exception('No se han seleccionado estudiantes');
            }
            
            // Obtener info de categoría
            $stmt = $pdo->prepare("
                SELECT c.*, g.id as grupo_id, g.nombre as grupo_nombre
                FROM categorias c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE c.id = ?
            ");
            $stmt->execute([$categoria_id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                throw new Exception('Categoría no encontrada');
            }
            
            // ====== VALIDACIÓN PREVIA DE PLANTILLA ======
            // Verificar si existe una plantilla configurada antes de intentar generar
            $tieneTemplate = false;
            $detalleValidacion = [];
            
            // 1. Verificar plantilla global (Obsolete, table removed)
            // No longer checking global template
            
            // 2. Verificar plantilla del grupo (slider con es_activa=1)
            $stmtGrupo = $pdo->prepare("SELECT id, archivo FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
            $stmtGrupo->execute([$categoria['grupo_id']]);
            $plantillaGrupo = $stmtGrupo->fetch();
            if ($plantillaGrupo && !empty($plantillaGrupo['archivo'])) {
                // Verificar que el archivo exista físicamente
                $rutaPlantilla = dirname(dirname(__DIR__)) . '/uploads/grupos/' . $categoria['grupo_id'] . '/' . $plantillaGrupo['archivo'];
                if (file_exists($rutaPlantilla)) {
                    $tieneTemplate = true;
                    $detalleValidacion[] = 'grupo';
                }
            }
            
            // 3. Verificar plantilla de categoría (si usa plantilla propia)
            if ($categoria['usar_plantilla_propia'] == 1 && !empty($categoria['plantilla_archivo'])) {
                $rutaCatPlantilla = dirname(__DIR__) . '/uploads/categorias/' . $categoria['plantilla_archivo'];
                if (file_exists($rutaCatPlantilla)) {
                    $tieneTemplate = true;
                    $detalleValidacion[] = 'categoria';
                }
            }
            
            // Si no hay plantilla, devolver error específico ANTES de intentar generar
            if (!$tieneTemplate && !$skip_template_check) {
                echo json_encode([
                    'success' => false,
                    'error_type' => 'PLANTILLA_NO_CONFIGURADA',
                    'message' => 'No hay plantilla configurada para este grupo. Debe configurar una plantilla antes de generar certificados.',
                    'detalle' => 'Vaya a la pestaña "Plantillas" del grupo y configure al menos una plantilla activa.',
                    'grupo_id' => $categoria['grupo_id'],
                    'grupo_nombre' => $categoria['grupo_nombre']
                ]);
                exit;
            }
            // ====== FIN VALIDACIÓN PREVIA ======
            
            // Obtener estudiantes
            $placeholders = str_repeat('?,', count($estudiantes_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id IN ($placeholders)");
            $stmt->execute($estudiantes_ids);
            $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $certificate = new \CCE\Certificate($pdo);
            $resultados = [];
            $exitosos = 0;
            $errores = 0;
            $errorComun = null; // Para detectar errores repetitivos
            
            foreach ($estudiantes as $estudiante) {
                try {
                    // Verificar si ya tiene certificado para ESE periodo
                    $stmt = $pdo->prepare("
                        SELECT id, codigo, fechas_generacion FROM certificados 
                        WHERE categoria_id = ? AND nombre = ? AND grupo_id = ? AND periodo_id <=> ?
                    ");
                    $stmt->execute([$categoria_id, $estudiante['nombre'], $categoria['grupo_id'], $periodo_id]);
                    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existente) {
                        // Regenerar el certificado y actualizar historial de fechas
                        $resultRegen = $certificate->regenerate($existente['codigo']);
                        
                        if ($resultRegen['success']) {
                            $resultados[] = [
                                'estudiante_id' => $estudiante['id'],
                                'nombre' => $estudiante['nombre'],
                                'success' => true,
                                'codigo' => $existente['codigo'],
                                'ya_existia' => true,
                                'regenerado' => true
                            ];
                        } else {
                            // Si falla la regeneración, al menos devolver el existente
                            $resultados[] = [
                                'estudiante_id' => $estudiante['id'],
                                'nombre' => $estudiante['nombre'],
                                'success' => true,
                                'codigo' => $existente['codigo'],
                                'ya_existia' => true,
                                'regenerado' => false,
                                'nota' => $resultRegen['error'] ?? 'No se pudo regenerar'
                            ];
                        }
                        $exitosos++;
                        continue;
                    }
                    
                    // Preparar datos para generar (la razón se tomará de la plantilla)
                    $dataCert = [
                        'nombre' => $estudiante['nombre'],
                        'fecha' => $fecha,
                        'grupo_id' => $categoria['grupo_id'],
                        'categoria_id' => $categoria_id,
                        'periodo_id' => $periodo_id,
                        'estado' => $estadoCertificado,
                        'es_destacado' => isset($estudiante['destacado']) ? (bool)$estudiante['destacado'] : false
                    ];
                    // Solo agregar razón si se envió explícitamente (para casos especiales)
                    if (!empty($razon)) {
                        $dataCert['razon'] = $razon;
                    }
                    
                    // Si solo estamos aprobando sin plantilla, insertar directo en BD
                    if ($skip_template_check) {
                        $codigo = $certificate->generateCode();
                        $fechasGeneracion = json_encode([date('Y-m-d H:i:s')]);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO certificados (codigo, nombre, razon, fecha, grupo_id, categoria_id, periodo_id, estudiante_id, fechas_generacion, estado)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $codigo,
                            $estudiante['nombre'],
                            $dataCert['razon'] ?? 'Por su participación',
                            $fecha,
                            $categoria['grupo_id'],
                            $categoria_id,
                            $periodo_id,
                            $estudiante['id'],
                            $fechasGeneracion,
                            $estadoCertificado
                        ]);
                        marcarCertificadoAprobado($pdo, $pdo->lastInsertId());
                        
                        $resultados[] = [
                            'estudiante_id' => $estudiante['id'],
                            'nombre' => $estudiante['nombre'],
                            'success' => true,
                            'codigo' => $codigo,
                            'ya_existia' => false
                        ];
                        $exitosos++;
                    } else {
                        // Generar certificado físico (PDF e Imagen)
                        $result = $certificate->create($dataCert);
                        
                        if ($result['success']) {
                            $resultados[] = [
                                'estudiante_id' => $estudiante['id'],
                                'nombre' => $estudiante['nombre'],
                                'success' => true,
                                'codigo' => $result['codigo'],
                                'ya_existia' => false
                            ];
                            $exitosos++;
                        } else {
                            $resultados[] = [
                                'estudiante_id' => $estudiante['id'],
                                'nombre' => $estudiante['nombre'],
                                'success' => false,
                                'error' => $result['error']
                            ];
                            $errores++;
                        }
                    }
                } catch (Exception $e) {
                    $msgError = $e->getMessage();
                    $resultados[] = [
                        'estudiante_id' => $estudiante['id'],
                        'nombre' => $estudiante['nombre'],
                        'success' => false,
                        'error' => $msgError
                    ];
                    $errores++;
                    
                    // Detectar errores comunes para mostrar mensaje más claro
                    if (!$errorComun && (
                        strpos($msgError, 'plantilla') !== false ||
                        strpos($msgError, 'template') !== false ||
                        strpos($msgError, 'No hay plantilla') !== false
                    )) {
                        $errorComun = 'PLANTILLA_NO_CONFIGURADA';
                    }
                }
            }
            
            // Analizar errores para dar mejor feedback
            $erroresPorTipo = [];
            foreach ($resultados as $r) {
                if (!$r['success'] && isset($r['error'])) {
                    $tipoError = $r['error'];
                    if (!isset($erroresPorTipo[$tipoError])) {
                        $erroresPorTipo[$tipoError] = 0;
                    }
                    $erroresPorTipo[$tipoError]++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'resultados' => $resultados,
                'resumen' => [
                    'total' => count($estudiantes_ids),
                    'exitosos' => $exitosos,
                    'errores' => $errores,
                    'error_comun' => $errorComun,
                    'errores_por_tipo' => $erroresPorTipo
                ]
            ]);
            break;
        
        case 'regenerar_estudiante':
            // Regenerar todos los certificados de un estudiante específico
            $estudiante_id = $data['estudiante_id'] ?? 0;
            $grupo_id = $data['grupo_id'] ?? 0;
            $forzar = $data['forzar_regenerar'] ?? true;
            
            if (empty($estudiante_id)) {
                throw new Exception('Falta el ID del estudiante');
            }
            if (empty($grupo_id)) {
                throw new Exception('Falta el ID del grupo');
            }
            
            // Obtener info del estudiante
            $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
            $stmt->execute([$estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            // Obtener todos los certificados del estudiante en este grupo
            $stmt = $pdo->prepare("
                SELECT c.id, c.codigo, c.nombre, c.categoria_id 
                FROM certificados c
                WHERE c.grupo_id = ? AND c.nombre = ?
            ");
            $stmt->execute([$grupo_id, $estudiante['nombre']]);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($certificados)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Este estudiante no tiene certificados generados en este grupo'
                ]);
                exit;
            }
            
            $certificate = new \CCE\Certificate($pdo);
            $resultados = [];
            $exitosos = 0;
            $errores = 0;
            
            foreach ($certificados as $cert) {
                try {
                    $resultRegen = $certificate->regenerate($cert['codigo']);
                    
                    if ($resultRegen['success']) {
                        $resultados[] = [
                            'codigo' => $cert['codigo'],
                            'success' => true,
                            'regenerado' => true
                        ];
                        $exitosos++;
                    } else {
                        $resultados[] = [
                            'codigo' => $cert['codigo'],
                            'success' => false,
                            'error' => $resultRegen['error'] ?? 'Error desconocido'
                        ];
                        $errores++;
                    }
                } catch (Exception $e) {
                    $resultados[] = [
                        'codigo' => $cert['codigo'],
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    $errores++;
                }
            }
            
            echo json_encode([
                'success' => $errores === 0,
                'estudiante' => $estudiante['nombre'],
                'total' => count($certificados),
                'exitosos' => $exitosos,
                'errores' => $errores,
                'resultados' => $resultados
            ]);
            break;
        
        case 'regenerar':
            // Regenerar un certificado específico por código
            $codigo = $data['codigo'] ?? '';
            $razon_regeneracion = $data['razon_regeneracion'] ?? '';
            
            if (empty($codigo)) {
                throw new Exception('Código de certificado no proporcionado');
            }
            
            $certificate = new \CCE\Certificate($pdo);
            $result = $certificate->regenerate($codigo, $razon_regeneracion);
            
            echo json_encode($result);
            break;
            
        case 'descargar_certificado':
            // Generar y descargar un certificado individual al vuelo
            $certificado_id = $data['certificado_id'] ?? $_GET['certificado_id'] ?? 0;
            $formato = $data['formato'] ?? $_GET['formato'] ?? 'pdf';
            
            if (empty($certificado_id)) {
                throw new Exception('Certificado no especificado');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE id = ?");
            $stmt->execute([$certificado_id]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                throw new Exception('Certificado no encontrado');
            }
            
            // Devolver la ruta del archivo
            $archivo = $formato === 'pdf' ? $cert['archivo_pdf'] : $cert['archivo_imagen'];
            
            echo json_encode([
                'success' => true,
                'archivo' => 'uploads/' . $archivo,
                'codigo' => $cert['codigo'],
                'nombre' => $cert['nombre']
            ]);
            break;
            
        case 'descargar_batch':
            // Crear ZIP con múltiples certificados
            $certificados_ids = $data['certificados_ids'] ?? [];
            $formato = $data['formato'] ?? 'pdf';
            
            if (empty($certificados_ids)) {
                throw new Exception('No hay certificados seleccionados');
            }
            
            $placeholders = str_repeat('?,', count($certificados_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE id IN ($placeholders)");
            $stmt->execute($certificados_ids);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear archivo ZIP temporal
            $zipName = 'certificados_' . date('Y-m-d_His') . '.zip';
            $zipPath = '../uploads/temp/' . $zipName;
            
            // Crear directorio temp si no existe
            if (!is_dir('../uploads/temp')) {
                mkdir('../uploads/temp', 0777, true);
            }
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('No se pudo crear el archivo ZIP');
            }
            
            foreach ($certificados as $cert) {
                $archivo = $formato === 'pdf' ? $cert['archivo_pdf'] : $cert['archivo_imagen'];
                $rutaArchivo = '../uploads/' . $archivo;
                
                if (file_exists($rutaArchivo)) {
                    $nombreArchivo = $cert['codigo'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $cert['nombre']);
                    $nombreArchivo .= $formato === 'pdf' ? '.pdf' : '.png';
                    $zip->addFile($rutaArchivo, $nombreArchivo);
                }
            }
            
            $zip->close();
            
            echo json_encode([
                'success' => true,
                'archivo' => 'uploads/temp/' . $zipName,
                'nombre' => $zipName,
                'total' => count($certificados)
            ]);
            break;
            
        case 'obtener_info_generacion':
            // Obtener resumen de generación para la categoría
            $categoria_id = $data['categoria_id'] ?? $_GET['categoria_id'] ?? 0;
            $periodo_id = $data['periodo_id'] ?? $_GET['periodo_id'] ?? null;
            
            if (empty($categoria_id)) {
                throw new Exception('Categoría no especificada');
            }
            
            // Contar estudiantes matriculados
            $sql = "SELECT COUNT(*) as total FROM categoria_estudiantes WHERE categoria_id = ? AND estado = 'activo'";
            $params = [$categoria_id];
            
            if ($periodo_id) {
                $sql .= " AND periodo_id = ?";
                $params[] = $periodo_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $totalEstudiantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener grupo_id de la categoría
            $stmt = $pdo->prepare("SELECT grupo_id FROM categorias WHERE id = ?");
            $stmt->execute([$categoria_id]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Contar certificados generados
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM certificados WHERE categoria_id = ? AND grupo_id = ?");
            $stmt->execute([$categoria_id, $cat['grupo_id']]);
            $totalCertificados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'total_estudiantes' => $totalEstudiantes,
                'certificados_generados' => $totalCertificados,
                'pendientes' => max(0, $totalEstudiantes - $totalCertificados)
            ]);
            break;
            
        case 'descargar_pdf_consolidado':
            // Generar un PDF con todos los certificados en páginas separadas
            $codigos = $data['codigos'] ?? [];
            
            if (empty($codigos)) {
                throw new Exception('No hay certificados para descargar');
            }
            
            // Obtener información de certificados
            $placeholders = str_repeat('?,', count($codigos) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo IN ($placeholders)");
            $stmt->execute($codigos);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($certificados)) {
                throw new Exception('No se encontraron certificados');
            }
            
            // Ruta base absoluta
            $baseDir = dirname(__DIR__);
            $uploadsDir = $baseDir . '/uploads';
            $tempDir = $uploadsDir . '/temp';
            
            // Crear directorio temp si no existe
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Crear PDF consolidado
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('CCE Certificados');
            $pdf->SetAuthor('Casa de la Cultura');
            $pdf->SetTitle('Certificados');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            
            $certificate = new \CCE\Certificate($pdo);
            $agregados = 0;
            
            foreach ($certificados as $cert) {
                // Buscar imagen existente
                $imagePath = $uploadsDir . '/' . $cert['archivo_imagen'];
                
                // Si no existe, intentar regenerar el certificado
                if (!file_exists($imagePath)) {
                    error_log("Imagen no encontrada: $imagePath, regenerando...");
                    
                    // Regenerar certificado al vuelo
                    $result = $certificate->regenerate($cert['codigo']);
                    if ($result['success'] && isset($result['imagen_path'])) {
                        $imagePath = $result['imagen_path'];
                    }
                }
                
                if (file_exists($imagePath)) {
                    $pdf->AddPage();
                    $pdf->Image($imagePath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);
                    $agregados++;
                } else {
                    error_log("No se pudo encontrar/generar imagen para certificado: " . $cert['codigo']);
                }
            }
            
            if ($agregados === 0) {
                throw new Exception('No se pudieron generar los certificados. Verifique la configuración de plantilla.');
            }
            
            // Guardar PDF temporalmente
            $pdfName = 'certificados_' . date('Y-m-d_His') . '.pdf';
            $pdfPath = $tempDir . '/' . $pdfName;
            $pdf->Output($pdfPath, 'F');
            
            // Leer el archivo y convertir a base64 para descarga directa
            $pdfContent = file_get_contents($pdfPath);
            $pdfBase64 = base64_encode($pdfContent);
            
            echo json_encode([
                'success' => true,
                'archivo' => 'uploads/temp/' . $pdfName,
                'nombre' => $pdfName,
                'total' => $agregados,
                'pdf_base64' => $pdfBase64
            ]);
            break;
            
        case 'descargar_zip':
            // Crear ZIP con certificados por códigos
            $codigos = $data['codigos'] ?? [];
            $formato = $data['formato'] ?? 'pdf';
            
            if (empty($codigos)) {
                throw new Exception('No hay certificados seleccionados');
            }
            
            // Obtener información de certificados
            $placeholders = str_repeat('?,', count($codigos) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo IN ($placeholders)");
            $stmt->execute($codigos);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($certificados)) {
                throw new Exception('No se encontraron certificados');
            }
            
            // Ruta base absoluta
            $baseDir = dirname(__DIR__);
            $uploadsDir = $baseDir . '/uploads';
            $tempDir = $uploadsDir . '/temp';
            
            // Crear directorio temp si no existe
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Crear archivo ZIP
            $zipName = 'certificados_' . ($formato === 'pdf' ? 'PDF' : 'IMG') . '_' . date('Y-m-d_His') . '.zip';
            $zipPath = $tempDir . '/' . $zipName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('No se pudo crear el archivo ZIP');
            }
            
            $archivosAgregados = 0;
            $certificate = new \CCE\Certificate($pdo);
            
            foreach ($certificados as $cert) {
                $archivo = $formato === 'pdf' ? $cert['archivo_pdf'] : $cert['archivo_imagen'];
                $rutaArchivo = $uploadsDir . '/' . $archivo;
                
                // Si no existe, intentar regenerar
                if (!file_exists($rutaArchivo)) {
                    $result = $certificate->regenerate($cert['codigo']);
                    if ($result['success'] && isset($result['imagen_path'])) {
                        if ($formato === 'pdf') {
                            // El PDF se genera junto con la imagen
                            $rutaArchivo = str_replace('.png', '.pdf', $result['imagen_path']);
                        } else {
                            $rutaArchivo = $result['imagen_path'];
                        }
                    }
                }
                
                if (file_exists($rutaArchivo)) {
                    $nombreArchivo = $cert['codigo'] . '_' . preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/u', '', $cert['nombre']);
                    $nombreArchivo = preg_replace('/\s+/', '_', $nombreArchivo);
                    $nombreArchivo .= $formato === 'pdf' ? '.pdf' : '.png';
                    $zip->addFile($rutaArchivo, $nombreArchivo);
                    $archivosAgregados++;
                }
            }
            
            $zip->close();
            
            if ($archivosAgregados === 0) {
                unlink($zipPath);
                throw new Exception('No se encontraron archivos para agregar al ZIP');
            }
            
            // Leer el ZIP y convertir a base64
            $zipContent = file_get_contents($zipPath);
            $zipBase64 = base64_encode($zipContent);
            
            echo json_encode([
                'success' => true,
                'archivo' => 'uploads/temp/' . $zipName,
                'nombre' => $zipName,
                'total' => $archivosAgregados,
                'zip_base64' => $zipBase64
            ]);
            break;
            
        case 'descargar_imagenes_zip':
            // Crear ZIP con imágenes de certificados por códigos
            $codigos = $data['codigos'] ?? [];
            
            if (empty($codigos)) {
                throw new Exception('No hay certificados seleccionados');
            }
            
            // Obtener información de certificados
            $placeholders = str_repeat('?,', count($codigos) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo IN ($placeholders)");
            $stmt->execute($codigos);
            $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($certificados)) {
                throw new Exception('No se encontraron certificados');
            }
            
            // Ruta base absoluta
            $baseDir = dirname(__DIR__);
            $uploadsDir = $baseDir . '/uploads';
            $tempDir = $uploadsDir . '/temp';
            
            // Crear directorio temp si no existe
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Crear archivo ZIP
            $zipName = 'certificados_imagenes_' . date('Y-m-d_His') . '.zip';
            $zipPath = $tempDir . '/' . $zipName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('No se pudo crear el archivo ZIP');
            }
            
            $archivosAgregados = 0;
            $certificate = new \CCE\Certificate($pdo);
            
            foreach ($certificados as $cert) {
                $rutaArchivo = $uploadsDir . '/' . $cert['archivo_imagen'];
                
                // Si no existe, intentar regenerar
                if (!file_exists($rutaArchivo)) {
                    $result = $certificate->regenerate($cert['codigo']);
                    if ($result['success'] && isset($result['imagen_path'])) {
                        $rutaArchivo = $result['imagen_path'];
                    }
                }
                
                if (file_exists($rutaArchivo)) {
                    $nombreArchivo = $cert['codigo'] . '_' . preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/u', '', $cert['nombre']);
                    $nombreArchivo = preg_replace('/\s+/', '_', $nombreArchivo);
                    $nombreArchivo .= '.png';
                    $zip->addFile($rutaArchivo, $nombreArchivo);
                    $archivosAgregados++;
                }
            }
            
            $zip->close();
            
            if ($archivosAgregados === 0) {
                unlink($zipPath);
                throw new Exception('No se encontraron imágenes para agregar al ZIP');
            }
            
            echo json_encode([
                'success' => true,
                'archivo' => 'uploads/temp/' . $zipName,
                'nombre' => $zipName,
                'total' => $archivosAgregados
            ]);
            break;
        
        case 'descargar_pdf':
            // Descargar PDF de un certificado por código
            $codigo = $data['codigo'] ?? $_GET['codigo'] ?? '';
            
            if (empty($codigo)) {
                throw new Exception('Código de certificado no proporcionado');
            }
            
            // Obtener certificado
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                throw new Exception('Certificado no encontrado');
            }
            
            $certificate = new \CCE\Certificate($pdo);
            
            // Verificar si existe el archivo PDF
            $pdfPath = __DIR__ . '/../uploads/' . ($cert['archivo_pdf'] ?? '');
            
            if (!$cert['archivo_pdf'] || !file_exists($pdfPath)) {
                // Regenerar el certificado
                $result = $certificate->regenerate($codigo);
                if ($result['success'] && isset($result['pdf_path'])) {
                    $pdfPath = $result['pdf_path'];
                } else {
                    throw new Exception('No se pudo generar el PDF');
                }
            }
            
            // Enviar archivo
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $codigo . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        
        case 'descargar_imagen':
            // Descargar imagen de un certificado por código
            $codigo = $data['codigo'] ?? $_GET['codigo'] ?? '';
            
            if (empty($codigo)) {
                throw new Exception('Código de certificado no proporcionado');
            }
            
            // Obtener certificado
            $stmt = $pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                throw new Exception('Certificado no encontrado');
            }
            
            $certificate = new \CCE\Certificate($pdo);
            
            // Verificar si existe el archivo de imagen
            $imgPath = __DIR__ . '/../uploads/' . ($cert['archivo_imagen'] ?? '');
            
            if (!$cert['archivo_imagen'] || !file_exists($imgPath)) {
                // Regenerar el certificado
                $result = $certificate->regenerate($codigo);
                if ($result['success'] && isset($result['imagen_path'])) {
                    $imgPath = $result['imagen_path'];
                } else {
                    throw new Exception('No se pudo generar la imagen');
                }
            }
            
            // Enviar archivo
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $codigo . '.png"');
            header('Content-Length: ' . filesize($imgPath));
            readfile($imgPath);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
