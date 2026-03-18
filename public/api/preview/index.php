<?php
/**
 * API para generar vista previa de certificado
 * Soporta preview para grupos y categorías
 * Puede usar la configuración actual del formulario (sin guardar) o la de la BD
 */
// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);



require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

// Verificar autenticación
// Verificar autenticación
Auth::requireAuth();

use Intervention\Image\ImageManagerStatic as Image;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Configurar driver GD
Image::configure(['driver' => 'gd']);

header('Content-Type: application/json');

$projectRoot = dirname(dirname(dirname(__DIR__)));
$publicRoot = $projectRoot . '/public';

function isReadableImageFile($path) {
    return !empty($path)
        && is_file($path)
        && is_readable($path)
        && @getimagesize($path) !== false;
}

/**
 * Resolver ruta de archivo buscando en root y public
 * @param string $relativePath Ruta relativa (ej: 'uploads/grupos/73/plantilla.png')
 * @return string|null Ruta absoluta resuelta o null si no se encuentra
 */
function resolveAssetPath($relativePath) {
    global $projectRoot, $publicRoot;
    $candidates = [
        $projectRoot . '/' . $relativePath,
        $publicRoot . '/' . $relativePath,
    ];
    foreach ($candidates as $c) {
        $real = realpath($c);
        if ($real && is_file($real)) {
            return $real;
        }
    }
    return null;
}

/**
 * Formatear fecha según el formato especificado con meses en español
 * Convierte formatos amigables a formato PHP date() y traduce al español
 */
function formatearFechaPreview($fecha, $formato) {
    $timestamp = strtotime($fecha);
    
    if ($timestamp === false) {
        return $fecha;
    }
    
    // Escapar palabras literales comunes antes de procesar
    // "de" debe convertirse a "\d\e" para que PHP no lo interprete
    $formatoEscapado = $formato;
    $formatoEscapado = str_replace(' de ', ' \d\e ', $formatoEscapado);
    $formatoEscapado = str_replace(' del ', ' \d\e\l ', $formatoEscapado);
    
    $mesesEspanol = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    
    $mesesAbrev = [
        'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr',
        'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
        'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'
    ];
    
    $fechaFormateada = date($formatoEscapado, $timestamp);
    $fechaFormateada = str_replace(array_keys($mesesEspanol), array_values($mesesEspanol), $fechaFormateada);
    $fechaFormateada = str_replace(array_keys($mesesAbrev), array_values($mesesAbrev), $fechaFormateada);
    
    return $fechaFormateada;
}

/**
 * Dividir texto en líneas según ancho máximo
 * Retorna array con información de cada línea incluyendo su ancho real
 * Nota: Se aplica un factor de tolerancia para compensar diferencias
 * entre las métricas de fuentes del navegador y PHP/GD
 * @param string $text Texto a dividir
 * @param string $fontPath Ruta a la fuente TTF
 * @param int $fontSize Tamaño de fuente
 * @param int $maxWidth Ancho máximo en píxeles
 * @param int $lineasEsperadas Número de líneas esperadas desde el canvas (0=auto)
 */
/**
 * Convert CSS pixel font size to GD point size.
 * Matches Intervention Image's internal getPointSize() formula exactly:
 *   intval(ceil($size * 0.75))
 * This ensures that text measurement (imagettfbbox) produces widths
 * consistent with Intervention's rendering (imagettftext).
 *
 * IMPORTANT: Only use this for DIRECT GD calls (imagettfbbox in getTextWidth).
 * Do NOT use for $img->text() — Intervention already converts internally.
 */
function cssPxToGdPt($cssPx) {
    return intval(ceil($cssPx * 0.75));
}

function wordwrapText($text, $fontPath, $fontSize, $maxWidth, $lineasEsperadas = 0) {
    $words = explode(' ', $text);
    $totalWords = count($words);
    
    // Si se especifica número de líneas y hay suficientes palabras, distribuir equilibradamente
    if ($lineasEsperadas > 0 && $totalWords >= $lineasEsperadas) {
        error_log("WordWrap: Modo líneas fijas - lineasEsperadas=$lineasEsperadas, totalWords=$totalWords");
        
        // Distribuir palabras equilibradamente entre las líneas
        $wordsPerLine = ceil($totalWords / $lineasEsperadas);
        $lines = [];
        
        for ($i = 0; $i < $lineasEsperadas; $i++) {
            $start = $i * $wordsPerLine;
            $lineWords = array_slice($words, $start, $wordsPerLine);
            if (!empty($lineWords)) {
                $lines[] = implode(' ', $lineWords);
            }
        }
        
        // Si quedaron menos líneas de las esperadas, redistribuir
        if (count($lines) < $lineasEsperadas && count($lines) > 0) {
            // El texto es muy corto, devolver las líneas que tenemos
            error_log("WordWrap: Texto corto, devolviendo " . count($lines) . " líneas");
        }
        
        error_log("WordWrap: Distribución equilibrada - " . count($lines) . " líneas generadas");
        foreach ($lines as $idx => $line) {
            error_log("WordWrap: Línea $idx: $line");
        }
        
        return $lines;
    }
    
    // Modo estándar: usar ancho máximo para dividir
    $lines = [];
    $currentLine = '';
    
    // Factor de tolerancia - usar 1.0 para que el wrap coincida exactamente con el lienzo CSS
    $toleranceFactor = 1.0;
    $effectiveMaxWidth = $maxWidth * $toleranceFactor;
    error_log("WordWrap: modo estándar, effectiveMaxWidth=$effectiveMaxWidth");
    
    foreach ($words as $word) {
        $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
        
        // Calcular ancho del texto
        $textWidth = getTextWidth($testLine, $fontPath, $fontSize);
        
        if ($textWidth > $effectiveMaxWidth && $currentLine !== '') {
            error_log("WordWrap: BREAK at width=$textWidth > $effectiveMaxWidth, line='" . $currentLine . "'");
            $lines[] = $currentLine;
            $currentLine = $word;
        } else {
            $currentLine = $testLine;
        }
    }
    
    if ($currentLine !== '') {
        error_log("WordWrap: LAST line='" . $currentLine . "', width=" . getTextWidth($currentLine, $fontPath, $fontSize));
        $lines[] = $currentLine;
    }
    
    error_log("WordWrap: Total lines=" . count($lines));
    
    return $lines;
}

/**
 * Calcular el ancho de un texto
 */
function getTextWidth($text, $fontPath, $fontSize) {
    if ($fontPath && file_exists($fontPath)) {
        // Convert CSS px to GD pt for accurate measurement
        $bbox = @imagettfbbox(cssPxToGdPt($fontSize), 0, $fontPath, $text);
        if ($bbox !== false) {
            return abs($bbox[4] - $bbox[0]);
        }
    }
    return strlen($text) * $fontSize * 0.6;
}

/**
 * Dibujar texto con alineación configurable usando TCPDF + ImageMagick
 * TCPDF genera PDF con la alineación especificada, ImageMagick lo convierte a imagen
 * @param string $alignment - Alineación: 'left', 'center', 'right', 'justified'
 */
function drawJustifiedText($img, $lines, $x, $y, $maxWidth, $fontPath, $fontSize, $color, $lineHeight, $isLastLineLeft = true, $alignment = 'left') {
    $currentY = $y;
    foreach ($lines as $line) {
        // Calcular X según alineación
        $drawX = $x;
        $drawAlign = 'left';
        
        if ($alignment === 'center') {
            $drawX = $x + ($maxWidth / 2);
            $drawAlign = 'center';
        } elseif ($alignment === 'right') {
            $drawX = $x + $maxWidth;
            $drawAlign = 'right';
        }
        
        // Pass CSS px directly — Intervention's getPointSize() converts internally
        $img->text($line, (int)$drawX, (int)$currentY, function($font) use ($fontPath, $fontSize, $color, $drawAlign) {
            if ($fontPath && file_exists($fontPath)) {
                $font->file($fontPath);
            }
            $font->size($fontSize);
            $font->color($color);
            $font->align($drawAlign);
            $font->valign('top');
        });
        $currentY += $lineHeight;
    }
    
    return $currentY;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $tipo = $_POST['tipo'] ?? 'grupo'; // grupo o categoria
    $id = intval($_POST['id'] ?? 0);
    $useFormData = isset($_POST['use_form_data']) && $_POST['use_form_data'] === '1';
    
    error_log("API Preview - POST data: " . json_encode($_POST));
    error_log("API Preview - useFormData: " . ($useFormData ? 'true' : 'false'));
    error_log("API Preview - formato_fecha POST: " . ($_POST['formato_fecha'] ?? 'NOT SET'));
    
    if (!$id) {
        throw new Exception("ID no especificado");
    }
    
    $certConfig = [];
    $plantillaPath = '';
    $firmaPath = '';

    // Conexión DB
    $pdo = getConnection();
    
    if ($tipo === 'categoria') {
        // Cargar configuración de categoría junto con la del grupo
        $stmt = $pdo->prepare("
            SELECT c.*, g.plantilla as grupo_plantilla, g.firma_imagen as grupo_firma_imagen,
                   g.id as grupo_id, g.fuente_nombre as grupo_fuente_nombre, 
                   g.formato_nombre as grupo_formato_nombre,
                   g.tamanio_fuente as grupo_tamanio_fuente, g.color_texto as grupo_color_texto,
                   g.posicion_nombre_x as grupo_pos_nombre_x, g.posicion_nombre_y as grupo_pos_nombre_y,
                   g.posicion_qr_x as grupo_pos_qr_x, g.posicion_qr_y as grupo_pos_qr_y,
                   g.posicion_firma_x as grupo_pos_firma_x, g.posicion_firma_y as grupo_pos_firma_y,
                   g.posicion_razon_x as grupo_pos_razon_x, g.posicion_razon_y as grupo_pos_razon_y,
                   g.posicion_fecha_x as grupo_pos_fecha_x, g.posicion_fecha_y as grupo_pos_fecha_y,
                   g.tamanio_qr as grupo_tamanio_qr, g.tamanio_firma as grupo_tamanio_firma,
                   g.variables_habilitadas as grupo_variables_habilitadas,
                   g.razon_defecto as grupo_razon_defecto, g.fuente_razon as grupo_fuente_razon,
                   g.tamanio_razon as grupo_tamanio_razon, g.color_razon as grupo_color_razon,
                   g.ancho_razon as grupo_ancho_razon,
                   g.formato_fecha as grupo_formato_fecha, g.fuente_fecha as grupo_fuente_fecha,
                   g.tamanio_fecha as grupo_tamanio_fecha, g.color_fecha as grupo_color_fecha,
                   g.firma_nombre as grupo_firma_nombre, g.firma_cargo as grupo_firma_cargo,
                   g.posicion_destacado_x as grupo_pos_destacado_x, g.posicion_destacado_y as grupo_pos_destacado_y,
                   g.tamanio_destacado as grupo_tamanio_destacado, g.destacado_tipo as grupo_destacado_tipo,
                   g.destacado_icono as grupo_destacado_icono, g.destacado_imagen as grupo_destacado_imagen,
                   g.destacado_habilitado as grupo_destacado_habilitado
            FROM categorias c
            INNER JOIN grupos g ON c.grupo_id = g.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cat) {
            throw new Exception("Categoría no encontrada");
        }
        
        // Determinar si usar configuración propia de la categoría o del grupo
        $usarConfigPropia = $cat['usar_plantilla_propia'] == 1;
        
        // Determinar plantilla a usar
        $plantillaIdPost = $_POST['plantilla_id'] ?? null;

        if ($usarConfigPropia) {
            // Buscar plantilla especificada o activa en categoria_plantillas
            if ($plantillaIdPost && $plantillaIdPost !== 'null' && $plantillaIdPost !== 'system') {
                $stmtPlantilla = $pdo->prepare("SELECT archivo FROM categoria_plantillas WHERE id = ? LIMIT 1");
                $stmtPlantilla->execute([intval($plantillaIdPost)]);
            } else {
                $stmtPlantilla = $pdo->prepare("SELECT archivo FROM categoria_plantillas WHERE categoria_id = ? AND es_activa = 1 LIMIT 1");
                $stmtPlantilla->execute([$id]);
            }
            $plantillaActiva = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
            
            if ($plantillaActiva) {
                $plantillaPath = resolveAssetPath('uploads/categorias/' . $id . '/' . $plantillaActiva['archivo']);
            } elseif (!empty($cat['plantilla_archivo'])) {
                // Fallback a la plantilla antigua de categoría si existe
                $plantillaPath = resolveAssetPath('assets/templates/' . $cat['plantilla_archivo']);
            }
        }
        
        // Si no usa plantilla propia o no encontró, buscar del grupo
        if (empty($plantillaPath) || !file_exists($plantillaPath)) {
            // Buscar plantilla especificada o activa del grupo
            if ($plantillaIdPost && $plantillaIdPost !== 'null' && $plantillaIdPost !== 'system') {
                $stmtGrupoPlantilla = $pdo->prepare("SELECT archivo, alineacion_razon, destacado_habilitado, destacado_tipo, destacado_icono, destacado_imagen, destacado_posicion_x, destacado_posicion_y, destacado_tamanio FROM grupo_plantillas WHERE id = ? LIMIT 1");
                $stmtGrupoPlantilla->execute([intval($plantillaIdPost)]);
            } else {
                $stmtGrupoPlantilla = $pdo->prepare("SELECT archivo, alineacion_razon, destacado_habilitado, destacado_tipo, destacado_icono, destacado_imagen, destacado_posicion_x, destacado_posicion_y, destacado_tamanio FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
                $stmtGrupoPlantilla->execute([$cat['grupo_id']]);
            }
            $grupoPlantillaActiva = $stmtGrupoPlantilla->fetch(PDO::FETCH_ASSOC);
            
            if ($grupoPlantillaActiva) {
                $plantillaPath = resolveAssetPath('uploads/grupos/' . $cat['grupo_id'] . '/' . $grupoPlantillaActiva['archivo']);
                // Guardar configuración de destacado y lineas_razon
                $destacadoConfig = [
                    'destacado_habilitado' => $grupoPlantillaActiva['destacado_habilitado'] ?? 0,
                    'destacado_tipo' => $grupoPlantillaActiva['destacado_tipo'] ?? 'icono',
                    'destacado_icono' => $grupoPlantillaActiva['destacado_icono'] ?? 'estrella',
                    'destacado_imagen' => $grupoPlantillaActiva['destacado_imagen'] ?? null,
                    'destacado_posicion_x' => $grupoPlantillaActiva['destacado_posicion_x'] ?? 50,
                    'destacado_posicion_y' => $grupoPlantillaActiva['destacado_posicion_y'] ?? 50,
                    'destacado_tamanio' => $grupoPlantillaActiva['destacado_tamanio'] ?? 100
                ];
            } elseif (!empty($cat['grupo_plantilla'])) {
                // Fallback a la plantilla antigua del grupo
                $plantillaPath = resolveAssetPath('assets/templates/' . $cat['grupo_plantilla']);
            }
        }
        
        // Determinar firma
        // 1. Verificar si viene archivo adjunto (prioridad alta)
        if (isset($_FILES['firma_imagen_file']) && $_FILES['firma_imagen_file']['error'] === UPLOAD_ERR_OK) {
            $firmaPath = $_FILES['firma_imagen_file']['tmp_name'];
        } 
        // 2. Verificar si viene nombre de archivo en POST
        elseif (!empty($_POST['firma_imagen'])) {
            $firmaPath = resolveAssetPath('assets/firmas/' . basename($_POST['firma_imagen']));
        }
        // 3. Si no, usar configuración de BD
        elseif ($usarConfigPropia && !empty($cat['plantilla_archivo_firma'])) {
            $firmaPath = dirname(dirname(dirname(__DIR__))) . '/public/uploads/categorias/' . $id . '/' . $cat['plantilla_archivo_firma'];
            if (!file_exists($firmaPath)) {
                $firmaPath = dirname(dirname(dirname(__DIR__))) . '/public/assets/firmas/' . $cat['plantilla_archivo_firma'];
            }
        } elseif (!empty($cat['grupo_firma_imagen'])) {
            $firmaPath = dirname(dirname(dirname(__DIR__))) . '/public/assets/firmas/' . $cat['grupo_firma_imagen'];
        }
        
        // Debug firma path
        $debugFirmaPath = $firmaPath;
        $debugFirmaExists = file_exists($firmaPath) ? 'YES' : 'NO';

        // Construir configuración según si usa plantilla propia o del grupo
        if ($usarConfigPropia) {
            $certConfig = [
                'fuente_nombre' => $cat['plantilla_fuente'] ?? 'Roboto-Regular',
                'tamanio_fuente' => $cat['plantilla_tamanio_fuente'] ?? 48,
                'color_texto' => $cat['plantilla_color_texto'] ?? '#000000',
                'posicion_nombre_x' => $cat['plantilla_pos_nombre_x'] ?? 400,
                'posicion_nombre_y' => $cat['plantilla_pos_nombre_y'] ?? 300,
                'posicion_qr_x' => $cat['plantilla_pos_qr_x'] ?? 920,
                'posicion_qr_y' => $cat['plantilla_pos_qr_y'] ?? 419,
                'posicion_firma_x' => $cat['plantilla_pos_firma_x'] ?? 800,
                'posicion_firma_y' => $cat['plantilla_pos_firma_y'] ?? 850,
                'posicion_razon_x' => $cat['plantilla_pos_razon_x'] ?? 400,
                'posicion_razon_y' => $cat['plantilla_pos_razon_y'] ?? 360,
                'posicion_fecha_x' => $cat['plantilla_pos_fecha_x'] ?? 400,
                'posicion_fecha_y' => $cat['plantilla_pos_fecha_y'] ?? 420,
                'tamanio_qr' => $cat['plantilla_tamanio_qr'] ?? 200,
                'tamanio_firma' => $cat['plantilla_tamanio_firma'] ?? 150,
                'variables_habilitadas' => $cat['plantilla_variables_habilitadas'] ?? '["nombre","qr","firma"]',
                'razon_defecto' => $cat['plantilla_razon_defecto'] ?? 'Por su destacada participación',
                'fuente_razon' => $cat['plantilla_fuente_razon'] ?? 'Roboto-Regular',
                'tamanio_razon' => $cat['plantilla_tamanio_razon'] ?? 24,
                'color_razon' => $cat['plantilla_color_razon'] ?? '#333333',
                'ancho_razon' => $cat['plantilla_ancho_razon'] ?? 600,
                'formato_fecha' => $cat['plantilla_formato_fecha'] ?? 'd de F de Y',
                'fecha_especifica' => $cat['plantilla_fecha_especifica'] ?? null,
                'fuente_fecha' => $cat['plantilla_fuente_fecha'] ?? 'Roboto-Regular',
                'tamanio_fecha' => $cat['plantilla_tamanio_fecha'] ?? 20,
                'color_fecha' => $cat['plantilla_color_fecha'] ?? '#333333',
                // Destacado
                'destacado_posicion_x' => $cat['plantilla_pos_destacado_x'] ?? 50,
                'destacado_posicion_y' => $cat['plantilla_pos_destacado_y'] ?? 50,
                'destacado_tamanio' => $cat['plantilla_tamanio_destacado'] ?? 100,
                'destacado_tipo' => $cat['plantilla_destacado_tipo'] ?? 'icono',
                'destacado_icono' => $cat['plantilla_destacado_icono'] ?? 'estrella',
                'destacado_imagen' => $cat['plantilla_destacado_imagen'] ?? null
            ];
        } else {
            // Usar configuración del grupo
            $certConfig = [
                'fuente_nombre' => $cat['grupo_fuente_nombre'] ?? 'Roboto-Regular',
                'formato_nombre' => $cat['grupo_formato_nombre'] ?? 'mayusculas',
                'tamanio_fuente' => $cat['grupo_tamanio_fuente'] ?? 48,
                'color_texto' => $cat['grupo_color_texto'] ?? '#000000',
                'posicion_nombre_x' => $cat['grupo_pos_nombre_x'] ?? 400,
                'posicion_nombre_y' => $cat['grupo_pos_nombre_y'] ?? 300,
                'posicion_qr_x' => $cat['grupo_pos_qr_x'] ?? 920,
                'posicion_qr_y' => $cat['grupo_pos_qr_y'] ?? 419,
                'posicion_firma_x' => $cat['grupo_pos_firma_x'] ?? 800,
                'posicion_firma_y' => $cat['grupo_pos_firma_y'] ?? 850,
                'posicion_razon_x' => $cat['grupo_pos_razon_x'] ?? 400,
                'posicion_razon_y' => $cat['grupo_pos_razon_y'] ?? 360,
                'posicion_fecha_x' => $cat['grupo_pos_fecha_x'] ?? 400,
                'posicion_fecha_y' => $cat['grupo_pos_fecha_y'] ?? 420,
                'tamanio_qr' => $cat['grupo_tamanio_qr'] ?? 200,
                'tamanio_firma' => $cat['grupo_tamanio_firma'] ?? 150,
                'variables_habilitadas' => $cat['grupo_variables_habilitadas'] ?? '["nombre","qr","firma"]',
                'razon_defecto' => $cat['grupo_razon_defecto'] ?? 'Por su destacada participación',
                'fuente_razon' => $cat['grupo_fuente_razon'] ?? 'Roboto-Regular',
                'tamanio_razon' => $cat['grupo_tamanio_razon'] ?? 24,
                'color_razon' => $cat['grupo_color_razon'] ?? '#333333',
                'ancho_razon' => $cat['grupo_ancho_razon'] ?? 600,
                'formato_fecha' => $cat['grupo_formato_fecha'] ?? 'd de F de Y',
                'fuente_fecha' => $cat['grupo_fuente_fecha'] ?? 'Roboto-Regular',
                'tamanio_fecha' => $cat['grupo_tamanio_fecha'] ?? 20,
                'color_fecha' => $cat['grupo_color_fecha'] ?? '#333333',
                'firma_nombre' => $cat['grupo_firma_nombre'] ?? '',
                'firma_cargo' => $cat['grupo_firma_cargo'] ?? '',
                // Destacado (usar valores del grupo si existen en la consulta, o defaults)
                'destacado_posicion_x' => $cat['grupo_pos_destacado_x'] ?? 50,
                'destacado_posicion_y' => $cat['grupo_pos_destacado_y'] ?? 50,
                'destacado_tamanio' => $cat['grupo_tamanio_destacado'] ?? 100,
                'destacado_tipo' => $cat['grupo_destacado_tipo'] ?? 'icono',
                'destacado_icono' => $cat['grupo_destacado_icono'] ?? 'estrella',
                'destacado_imagen' => $cat['grupo_destacado_imagen'] ?? null
            ];
        }
        
        // Agregar configuración de destacado si está disponible
        if (isset($destacadoConfig)) {
            $certConfig = array_merge($certConfig, $destacadoConfig);
        }
        
        // Si use_form_data está activo, sobrescribir con valores del formulario
        if ($useFormData) {
            if (isset($_POST['posicion_nombre_x'])) $certConfig['posicion_nombre_x'] = (int)round((float)$_POST['posicion_nombre_x']);
            if (isset($_POST['posicion_nombre_y'])) $certConfig['posicion_nombre_y'] = (int)round((float)$_POST['posicion_nombre_y']);
            if (isset($_POST['posicion_razon_x'])) $certConfig['posicion_razon_x'] = (int)round((float)$_POST['posicion_razon_x']);
            if (isset($_POST['posicion_razon_y'])) $certConfig['posicion_razon_y'] = (int)round((float)$_POST['posicion_razon_y']);
            if (isset($_POST['posicion_fecha_x'])) $certConfig['posicion_fecha_x'] = (int)round((float)$_POST['posicion_fecha_x']);
            if (isset($_POST['posicion_fecha_y'])) $certConfig['posicion_fecha_y'] = (int)round((float)$_POST['posicion_fecha_y']);
            if (isset($_POST['posicion_qr_x'])) $certConfig['posicion_qr_x'] = (int)round((float)$_POST['posicion_qr_x']);
            if (isset($_POST['posicion_qr_y'])) $certConfig['posicion_qr_y'] = (int)round((float)$_POST['posicion_qr_y']);
            if (isset($_POST['posicion_firma_x'])) $certConfig['posicion_firma_x'] = (int)round((float)$_POST['posicion_firma_x']);
            if (isset($_POST['posicion_firma_y'])) $certConfig['posicion_firma_y'] = (int)round((float)$_POST['posicion_firma_y']);
            if (isset($_POST['fuente_nombre'])) $certConfig['fuente_nombre'] = $_POST['fuente_nombre'];
            if (isset($_POST['formato_nombre'])) $certConfig['formato_nombre'] = $_POST['formato_nombre'];
            if (isset($_POST['tamanio_fuente'])) $certConfig['tamanio_fuente'] = intval($_POST['tamanio_fuente']);
            if (isset($_POST['color_texto'])) $certConfig['color_texto'] = $_POST['color_texto'];
            if (isset($_POST['tamanio_qr'])) $certConfig['tamanio_qr'] = intval($_POST['tamanio_qr']);
            if (isset($_POST['tamanio_firma'])) $certConfig['tamanio_firma'] = intval($_POST['tamanio_firma']);
            if (isset($_POST['variables_habilitadas'])) $certConfig['variables_habilitadas'] = $_POST['variables_habilitadas'];
            // Razón
            if (isset($_POST['razon_defecto'])) $certConfig['razon_defecto'] = $_POST['razon_defecto'];
            if (isset($_POST['fuente_razon'])) $certConfig['fuente_razon'] = $_POST['fuente_razon'];
            if (isset($_POST['tamanio_razon'])) $certConfig['tamanio_razon'] = intval($_POST['tamanio_razon']);
            if (isset($_POST['color_razon'])) $certConfig['color_razon'] = $_POST['color_razon'];
            if (isset($_POST['ancho_razon'])) $certConfig['ancho_razon'] = intval($_POST['ancho_razon']);
            if (isset($_POST['lineas_razon'])) $certConfig['lineas_razon'] = intval($_POST['lineas_razon']);
            if (isset($_POST['alineacion_razon'])) $certConfig['alineacion_razon'] = $_POST['alineacion_razon'];
            // Fecha
            if (isset($_POST['formato_fecha'])) $certConfig['formato_fecha'] = $_POST['formato_fecha'];
            if (isset($_POST['fuente_fecha'])) $certConfig['fuente_fecha'] = $_POST['fuente_fecha'];
            if (isset($_POST['tamanio_fecha'])) $certConfig['tamanio_fecha'] = intval($_POST['tamanio_fecha']);
            if (isset($_POST['color_fecha'])) $certConfig['color_fecha'] = $_POST['color_fecha'];
        }
        
    } else {
        // Cargar configuración de grupo
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$grupo) {
            throw new Exception("Grupo no encontrado");
        }
        
        // Buscar plantilla especificada o activa
        $plantillaIdPost = $_POST['plantilla_id'] ?? null;
        $plantillaActiva = null;
        
        if ($plantillaIdPost && $plantillaIdPost !== 'null' && $plantillaIdPost !== 'system') {
            $stmtPlantilla = $pdo->prepare("SELECT archivo, alineacion_razon FROM grupo_plantillas WHERE id = ? LIMIT 1");
            $stmtPlantilla->execute([intval($plantillaIdPost)]);
            $plantillaActiva = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmtPlantilla = $pdo->prepare("SELECT archivo, alineacion_razon FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
            $stmtPlantilla->execute([$id]);
            $plantillaActiva = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
        }
        
        $alineacionRazonFromPlantilla = 'justified';
        if ($plantillaActiva) {
            $plantillaPath = resolveAssetPath('uploads/grupos/' . $id . '/' . $plantillaActiva['archivo']);
            $alineacionRazonFromPlantilla = $plantillaActiva['alineacion_razon'] ?? 'justified';
        } elseif (!empty($grupo['plantilla'])) {
            // Fallback a la plantilla antigua si existe
            $plantillaPath = resolveAssetPath('assets/templates/' . $grupo['plantilla']);
        }
        
        if (!empty($grupo['firma_imagen'])) {
            $firmaPath = resolveAssetPath('assets/firmas/' . $grupo['firma_imagen']);
        }
        
        $certConfig = [
            'fuente_nombre' => $grupo['fuente_nombre'] ?? 'Roboto-Regular',
            'formato_nombre' => $grupo['formato_nombre'] ?? 'mayusculas',
            'tamanio_fuente' => $grupo['tamanio_fuente'] ?? 48,
            'color_texto' => $grupo['color_texto'] ?? '#000000',
            'posicion_nombre_x' => $grupo['posicion_nombre_x'] ?? 400,
            'posicion_nombre_y' => $grupo['posicion_nombre_y'] ?? 300,
            'posicion_qr_x' => $grupo['posicion_qr_x'] ?? 920,
            'posicion_qr_y' => $grupo['posicion_qr_y'] ?? 419,
            'posicion_firma_x' => $grupo['posicion_firma_x'] ?? 800,
            'posicion_firma_y' => $grupo['posicion_firma_y'] ?? 850,
            'posicion_razon_x' => $grupo['posicion_razon_x'] ?? 400,
            'posicion_razon_y' => $grupo['posicion_razon_y'] ?? 360,
            'posicion_fecha_x' => $grupo['posicion_fecha_x'] ?? 400,
            'posicion_fecha_y' => $grupo['posicion_fecha_y'] ?? 420,
            'tamanio_qr' => $grupo['tamanio_qr'] ?? 200,
            'tamanio_firma' => $grupo['tamanio_firma'] ?? 150,
            'firma_imagen' => $grupo['firma_imagen'] ?? null,
            'variables_habilitadas' => $grupo['variables_habilitadas'] ?? '["nombre","qr","firma"]',
            // Configuración de Razón (valores por defecto para grupos)
            'razon_defecto' => $grupo['razon_defecto'] ?? 'Por su destacada participación',
            'fuente_razon' => $grupo['fuente_razon'] ?? 'Roboto-Regular',
            'tamanio_razon' => $grupo['tamanio_razon'] ?? 24,
            'color_razon' => $grupo['color_razon'] ?? '#333333',
            'ancho_razon' => $grupo['ancho_razon'] ?? 600,
            'alineacion_razon' => $alineacionRazonFromPlantilla,
            // Configuración de Fecha (valores por defecto para grupos)
            'formato_fecha' => $grupo['formato_fecha'] ?? 'd de F de Y',
            'fecha_especifica' => null,
            'fuente_fecha' => $grupo['fuente_fecha'] ?? 'Roboto-Regular',
            'tamanio_fecha' => $grupo['tamanio_fecha'] ?? 20,
            'color_fecha' => $grupo['color_fecha'] ?? '#333333',
            // Destacado
            'destacado_posicion_x' => $grupo['posicion_destacado_x'] ?? 50,
            'destacado_posicion_y' => $grupo['posicion_destacado_y'] ?? 50,
            'destacado_tamanio' => $grupo['tamanio_destacado'] ?? 100,
            'destacado_tipo' => $grupo['destacado_tipo'] ?? 'icono',
            'destacado_icono' => $grupo['destacado_icono'] ?? 'estrella',
            'destacado_imagen' => $grupo['destacado_imagen'] ?? null,
            'destacado_habilitado' => $grupo['destacado_habilitado'] ?? 0
        ];
        
        // Si use_form_data está activo, sobrescribir con valores del formulario (para grupos)
        if ($useFormData) {
            if (isset($_POST['posicion_nombre_x'])) $certConfig['posicion_nombre_x'] = (int)round((float)$_POST['posicion_nombre_x']);
            if (isset($_POST['posicion_nombre_y'])) $certConfig['posicion_nombre_y'] = (int)round((float)$_POST['posicion_nombre_y']);
            if (isset($_POST['posicion_razon_x'])) $certConfig['posicion_razon_x'] = (int)round((float)$_POST['posicion_razon_x']);
            if (isset($_POST['posicion_razon_y'])) $certConfig['posicion_razon_y'] = (int)round((float)$_POST['posicion_razon_y']);
            if (isset($_POST['posicion_fecha_x'])) $certConfig['posicion_fecha_x'] = (int)round((float)$_POST['posicion_fecha_x']);
            if (isset($_POST['posicion_fecha_y'])) $certConfig['posicion_fecha_y'] = (int)round((float)$_POST['posicion_fecha_y']);
            if (isset($_POST['posicion_qr_x'])) $certConfig['posicion_qr_x'] = (int)round((float)$_POST['posicion_qr_x']);
            if (isset($_POST['posicion_qr_y'])) $certConfig['posicion_qr_y'] = (int)round((float)$_POST['posicion_qr_y']);
            if (isset($_POST['posicion_firma_x'])) $certConfig['posicion_firma_x'] = (int)round((float)$_POST['posicion_firma_x']);
            if (isset($_POST['posicion_firma_y'])) $certConfig['posicion_firma_y'] = (int)round((float)$_POST['posicion_firma_y']);
            if (isset($_POST['fuente_nombre'])) $certConfig['fuente_nombre'] = $_POST['fuente_nombre'];
            if (isset($_POST['formato_nombre'])) $certConfig['formato_nombre'] = $_POST['formato_nombre'];
            if (isset($_POST['tamanio_fuente'])) $certConfig['tamanio_fuente'] = intval($_POST['tamanio_fuente']);
            if (isset($_POST['color_texto'])) $certConfig['color_texto'] = $_POST['color_texto'];
            if (isset($_POST['tamanio_qr'])) $certConfig['tamanio_qr'] = intval($_POST['tamanio_qr']);
            if (isset($_POST['tamanio_firma'])) $certConfig['tamanio_firma'] = intval($_POST['tamanio_firma']);
            if (isset($_POST['variables_habilitadas'])) $certConfig['variables_habilitadas'] = $_POST['variables_habilitadas'];
            // Razón
            if (isset($_POST['razon_defecto'])) $certConfig['razon_defecto'] = $_POST['razon_defecto'];
            if (isset($_POST['fuente_razon'])) $certConfig['fuente_razon'] = $_POST['fuente_razon'];
            if (isset($_POST['tamanio_razon'])) $certConfig['tamanio_razon'] = intval($_POST['tamanio_razon']);
            if (isset($_POST['color_razon'])) $certConfig['color_razon'] = $_POST['color_razon'];
            if (isset($_POST['ancho_razon'])) $certConfig['ancho_razon'] = intval($_POST['ancho_razon']);
            if (isset($_POST['lineas_razon'])) $certConfig['lineas_razon'] = intval($_POST['lineas_razon']);
            if (isset($_POST['alineacion_razon'])) $certConfig['alineacion_razon'] = $_POST['alineacion_razon'];
            // Fecha
            if (isset($_POST['formato_fecha'])) $certConfig['formato_fecha'] = $_POST['formato_fecha'];
            if (isset($_POST['fuente_fecha'])) $certConfig['fuente_fecha'] = $_POST['fuente_fecha'];
            if (isset($_POST['tamanio_fecha'])) $certConfig['tamanio_fecha'] = intval($_POST['tamanio_fecha']);
            if (isset($_POST['color_fecha'])) $certConfig['color_fecha'] = $_POST['color_fecha'];
            
            // Capture firma_imagen from POST if exists (for existing signatures)
            if (!empty($_POST['firma_imagen'])) $certConfig['firma_imagen'] = $_POST['firma_imagen'];
            // Destacado
            if (isset($_POST['posicion_destacado_x'])) $certConfig['posicion_destacado_x'] = (int)round((float)$_POST['posicion_destacado_x']);
            if (isset($_POST['posicion_destacado_y'])) $certConfig['posicion_destacado_y'] = (int)round((float)$_POST['posicion_destacado_y']);
            if (isset($_POST['destacado_tamanio'])) $certConfig['destacado_tamanio'] = intval($_POST['destacado_tamanio']);
            if (isset($_POST['destacado_tipo'])) $certConfig['destacado_tipo'] = $_POST['destacado_tipo'];
            if (isset($_POST['destacado_icono'])) $certConfig['destacado_icono'] = $_POST['destacado_icono'];
        }
    }
    
    // Verificar plantilla
    error_log("API Preview - plantillaPath: " . $plantillaPath);
    error_log("API Preview - file_exists: " . (file_exists($plantillaPath) ? 'true' : 'false'));
    
    if (!isReadableImageFile($plantillaPath)) {
        // Intentar usar plantilla por defecto en ambas estructuras
        $defaultCandidates = [
            $projectRoot . '/assets/templates/default_template.png',
            $publicRoot . '/assets/templates/default_template.png'
        ];

        $plantillaPath = '';
        foreach ($defaultCandidates as $candidatePath) {
            if (isReadableImageFile($candidatePath)) {
                $plantillaPath = $candidatePath;
                break;
            }
        }

        if (!isReadableImageFile($plantillaPath)) {
            throw new Exception('No hay plantilla válida para previsualización (archivo inexistente, no legible o no es imagen).');
        }
    }
    
    $plantillaRealPath = realpath($plantillaPath) ?: $plantillaPath;
    error_log('API Preview - plantilla final: ' . $plantillaRealPath);
    error_log('API Preview - plantilla is_file: ' . (is_file($plantillaRealPath) ? 'true' : 'false'));
    error_log('API Preview - plantilla readable: ' . (is_readable($plantillaRealPath) ? 'true' : 'false'));

    if (!isReadableImageFile($plantillaRealPath)) {
        throw new Exception('Plantilla inválida para abrir: ' . $plantillaRealPath);
    }

    // Cargar plantilla y forzar TrueColor (creando un lienzo nuevo)
    try {
        $source = Image::make($plantillaRealPath);
    } catch (Exception $e) {
        throw new Exception('Error abriendo plantilla: ' . $plantillaRealPath . ' | ' . $e->getMessage());
    }
    $img = Image::canvas($source->width(), $source->height());
    $img->insert($source);
    
    // --- ESTANDARIZACIÓN DE RESOLUCIÓN ---
    // Igual que Certificate.php: forzamos la plantilla a 1600x1131 para que
    // las coordenadas del frontend se usen directamente sin escalado.
    $TARGET_WIDTH = 1600;
    $TARGET_HEIGHT = 1131;
    $img->resize($TARGET_WIDTH, $TARGET_HEIGHT);
    
    // Variables habilitadas
    $variablesHabilitadas = json_decode($certConfig['variables_habilitadas'], true) ?: ['nombre', 'qr', 'firma'];
    
    // Obtener datos del primer estudiante del grupo (si existe)
    // Aplicar formato según configuración al nombre por defecto
    $formatoNombre = $certConfig['formato_nombre'] ?? 'mayusculas';
    $nombreBase = 'Juan Carlos Pérez García';
    
    switch ($formatoNombre) {
        case 'mayusculas':
            $nombreEjemplo = mb_strtoupper($nombreBase, 'UTF-8');
            break;
        case 'capitalizado':
            $nombreEjemplo = mb_convert_case($nombreBase, MB_CASE_TITLE, 'UTF-8');
            break;
        case 'minusculas':
            $nombreEjemplo = mb_strtolower($nombreBase, 'UTF-8');
            break;
        default:
            $nombreEjemplo = mb_strtoupper($nombreBase, 'UTF-8');
    }
    
    $categoriaEjemplo = 'Categoría de Ejemplo';
    
    // Determinar el grupo_id según el tipo
    $grupoIdParaEstudiante = null;
    if ($tipo === 'grupo') {
        $grupoIdParaEstudiante = $id;
    } elseif ($tipo === 'categoria' && isset($cat['grupo_id'])) {
        $grupoIdParaEstudiante = $cat['grupo_id'];
    }
    
    if ($grupoIdParaEstudiante) {
        // Buscar el primer estudiante del grupo (usando tabla intermedia categoria_estudiantes)
        $stmtEstudiante = $pdo->prepare("
            SELECT e.nombre, c.nombre as categoria_nombre
            FROM estudiantes e
            INNER JOIN categoria_estudiantes ce ON e.id = ce.estudiante_id
            INNER JOIN categorias c ON ce.categoria_id = c.id
            WHERE c.grupo_id = ?
            ORDER BY e.id ASC
            LIMIT 1
        ");
        $stmtEstudiante->execute([$grupoIdParaEstudiante]);
        $primerEstudiante = $stmtEstudiante->fetch(PDO::FETCH_ASSOC);
        
        if ($primerEstudiante) {
            // Aplicar formato de nombre según configuración
            $formatoNombre = $certConfig['formato_nombre'] ?? 'mayusculas';
            $nombreFormateado = trim($primerEstudiante['nombre']);
            
            switch ($formatoNombre) {
                case 'mayusculas':
                    $nombreFormateado = mb_strtoupper($nombreFormateado, 'UTF-8');
                    break;
                case 'capitalizado':
                    $nombreFormateado = mb_convert_case($nombreFormateado, MB_CASE_TITLE, 'UTF-8');
                    break;
                case 'minusculas':
                    $nombreFormateado = mb_strtolower($nombreFormateado, 'UTF-8');
                    break;
                default:
                    $nombreFormateado = mb_strtoupper($nombreFormateado, 'UTF-8');
            }
            
            $nombreEjemplo = $nombreFormateado;
            $categoriaEjemplo = $primerEstudiante['categoria_nombre'];
            error_log("API Preview - Usando datos del primer estudiante: $nombreEjemplo (formato: $formatoNombre), categoría: $categoriaEjemplo");
        } else {
            error_log("API Preview - No hay estudiantes en el grupo, usando datos por defecto");
        }
    }
    
    $razonEjemplo = 'Por su destacada participación en el taller de ejemplo';
    $fechaEjemplo = date('d/m/Y');
    $codigoEjemplo = 'CCE-PREVIEW01';
    
    // Función helper para obtener ruta de fuente (mejorada para buscar en BD)
    // Función helper para obtener ruta de fuente (mejorada para buscar en BD)
    // Intentar primero en root/assets/fonts, luego en public/assets/fonts
    $fontsDir = dirname(dirname(dirname(__DIR__))) . '/assets/fonts/';
    if (!is_dir($fontsDir)) {
        $fontsDir = dirname(dirname(__DIR__)) . '/assets/fonts/';
    }
    $getFontPath = function($fontName) use ($fontsDir, $pdo) {
        $normalizePath = function($path) {
            $real = realpath($path);
            return $real ? $real : $path;
        };

        if (empty($fontName)) {
            $defaultFont = $fontsDir . 'Roboto-Regular.ttf';
            return file_exists($defaultFont) ? $normalizePath($defaultFont) : null;
        }
        
        // Manejar fuentes de Google (prefijo "google:")
        if (strpos($fontName, 'google:') === 0) {
            // Extraer el nombre real de la fuente (ej: "google:Open+Sans" -> "Open Sans")
            $googleFontName = str_replace('+', ' ', substr($fontName, 7));
            
            // Buscar en BD por nombre
            $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$googleFontName]);
            $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fuente && !empty($fuente['archivo'])) {
                $fontPath = $fontsDir . $fuente['archivo'];
                if (file_exists($fontPath)) {
                    return $normalizePath($fontPath);
                }
            }
            
            // También intentar sin espacios como nombre_archivo
            $fontNameNoSpaces = str_replace(' ', '', $googleFontName);
            $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre_archivo LIKE ? AND activo = 1 LIMIT 1");
            $stmt->execute(['%' . $fontNameNoSpaces . '%']);
            $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fuente && !empty($fuente['archivo'])) {
                $fontPath = $fontsDir . $fuente['archivo'];
                if (file_exists($fontPath)) {
                    return $normalizePath($fontPath);
                }
            }
        }
        
        // Primero buscar en la carpeta de fuentes directamente
        $fontBasePath = $fontsDir . $fontName;
        // Check extensions (case insensitive on Windows but good for Linux)
        foreach (['.ttf', '.otf', '.TTF', '.OTF', ''] as $ext) {
            if (file_exists($fontBasePath . $ext)) {
                return $normalizePath($fontBasePath . $ext);
            }
        }
        
        // Si no existe como archivo, buscar en la BD por nombre_archivo
        $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre_archivo = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fuente && !empty($fuente['archivo'])) {
            $fontPath = $fontsDir . $fuente['archivo'];
            if (file_exists($fontPath)) {
                return $normalizePath($fontPath);
            }
        }
        
        // También buscar por nombre
        $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fuente && !empty($fuente['archivo'])) {
            $fontPath = $fontsDir . $fuente['archivo'];
            if (file_exists($fontPath)) {
                return $normalizePath($fontPath);
            }
        }
        
        // Fallback a Roboto
        $defaultFont = $fontsDir . 'Roboto-Regular.ttf';
        return file_exists($defaultFont) ? $normalizePath($defaultFont) : null;
    };
    
    // Obtener rutas de fuentes para cada variable
    $fontPathNombre = $getFontPath($certConfig['fuente_nombre'] ?? 'Roboto-Regular');
    $fontPathRazon = $getFontPath($certConfig['fuente_razon'] ?? 'Roboto-Regular');
    $fontPathFecha = $getFontPath($certConfig['fuente_fecha'] ?? 'Roboto-Regular');
    
    // Resolver ruta de firma
    $firmaPath = null;
    
    // 1. Check for uploaded file first (preview of new signature)
    if (isset($_FILES['firma_imagen_file']) && $_FILES['firma_imagen_file']['error'] === UPLOAD_ERR_OK) {
        $firmaPath = $_FILES['firma_imagen_file']['tmp_name'];
    } elseif (isset($_FILES['firma_imagen']) && $_FILES['firma_imagen']['error'] === UPLOAD_ERR_OK) {
        $firmaPath = $_FILES['firma_imagen']['tmp_name'];
    }
    // 2. Use existing configuration
    elseif (!empty($certConfig['firma_imagen'])) {
        $firmaVal = $certConfig['firma_imagen'];
        // Si ya incluye un subdirectorio (uploads/firmas/ o assets/firmas/) usar directo
        if (strpos($firmaVal, '/') !== false) {
            $firmaPath = resolveAssetPath($firmaVal);
        } else {
            // Nombre de archivo solo — buscar en uploads/firmas/ primero, luego assets/firmas/
            $firmaPath = resolveAssetPath('uploads/firmas/' . $firmaVal)
                      ?? resolveAssetPath('assets/firmas/' . $firmaVal);
        }
    }
    // 3. Fallback: convention-based path grupo_{id}_firma.png
    if (!$firmaPath && $tipo === 'grupo') {
        $conventionName = 'grupo_' . $id . '_firma.png';
        $firmaPath = resolveAssetPath('assets/firmas/' . $conventionName)
                  ?? resolveAssetPath('uploads/firmas/' . $conventionName);
    }
    
    // Helper function for text with fallback
    $drawTextWithFallback = function($text, $x, $y, $fontPath, $size, $color, $align = 'left') use ($img) {
        try {
            // Check if we can use TrueType font
            if (function_exists('imagettftext') && !empty($fontPath) && file_exists($fontPath)) {
                // Pass CSS px directly — Intervention's getPointSize() converts internally
                $img->text($text, $x, $y, function($font) use ($fontPath, $size, $color, $align) {
                    $font->file($fontPath);
                    $font->size($size);
                    $font->color($color);
                    $font->align($align);
                    $font->valign('top');
                });
                return true;
            }
        } catch (Exception $e) {
            error_log("API Preview - Font Error (Fallback to internal): " . $e->getMessage());
        }
        
        // Fallback to internal font (1-5)
        $internalSize = 1;
        if ($size >= 12) $internalSize = 2;
        if ($size >= 14) $internalSize = 3;
        if ($size >= 16) $internalSize = 4;
        if ($size >= 20) $internalSize = 5;
        
        $img->text($text, $x, $y, function($font) use ($internalSize, $color, $align) {
            $font->size($internalSize);
            $font->color($color);
            $font->align($align);
            $font->valign('top');
        });
        return false;
    };

    // Agregar NOMBRE (using fallback)
    if (in_array('nombre', $variablesHabilitadas)) {
        $nombreX = (int)$certConfig['posicion_nombre_x'];
        $nombreY = (int)$certConfig['posicion_nombre_y'];
        $nombreSize = (int)$certConfig['tamanio_fuente'];
        error_log("=== PREVIEW NOMBRE ===");
        error_log("posicion_nombre_x=$nombreX (raw POST=" . ($_POST['posicion_nombre_x'] ?? 'N/A') . ")");
        error_log("posicion_nombre_y=$nombreY (raw POST=" . ($_POST['posicion_nombre_y'] ?? 'N/A') . ")");
        error_log("tamanio_fuente=$nombreSize (Intervention pt=" . intval(ceil($nombreSize * 0.75)) . ")");
        error_log("fuente_nombre=" . ($certConfig['fuente_nombre'] ?? 'NULL'));
        error_log("fontPathNombre=" . ($fontPathNombre ?? 'NULL'));
        error_log("fontExists=" . (($fontPathNombre && file_exists($fontPathNombre)) ? 'YES' : 'NO'));
        error_log("nombreEjemplo=$nombreEjemplo");
        error_log("Imagen: {$img->width()}x{$img->height()}");
        
        $drawTextWithFallback(
            $nombreEjemplo, 
            $nombreX, 
            $nombreY, 
            $fontPathNombre, 
            $nombreSize, 
            $certConfig['color_texto'],
            'left'
        );
    }
    
    // Agregar RAZÓN
    if (in_array('razon', $variablesHabilitadas)) {
        $razonTexto = !empty($certConfig['razon_defecto']) ? $certConfig['razon_defecto'] : $razonEjemplo;
        
        // Obtener nombres para reemplazo
        $nombreGrupo = '';
        $nombreCategoria = '';
        
        if ($tipo === 'categoria') {
            $stmtInfo = $pdo->prepare("
                SELECT c.nombre as categoria_nombre, g.nombre as grupo_nombre 
                FROM categorias c 
                INNER JOIN grupos g ON c.grupo_id = g.id 
                WHERE c.id = ?
            ");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                $nombreGrupo = $info['grupo_nombre'];
                $nombreCategoria = $info['categoria_nombre'];
            }
        } else {
            $stmtInfo = $pdo->prepare("SELECT nombre FROM grupos WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                $nombreGrupo = $info['nombre'];
            }
            $nombreCategoria = $categoriaEjemplo;
        }
        
        // Fecha formateada para variable {fecha} en razón
        $fechaBaseRazon = !empty($certConfig['fecha_especifica']) ? $certConfig['fecha_especifica'] : date('Y-m-d');
        $formatoFechaRazon = $certConfig['formato_fecha'] ?? 'd de F de Y';
        $fechaFormateadaRazon = formatearFechaPreview($fechaBaseRazon, $formatoFechaRazon);
        
        // Reemplazar variables
        $razonTexto = preg_replace('/\{grupo\}/i', $nombreGrupo, $razonTexto);
        $razonTexto = preg_replace('/\{categoria\}/i', $nombreCategoria, $razonTexto);
        $razonTexto = preg_replace('/\{nombre\}/i', $nombreEjemplo, $razonTexto);
        $razonTexto = preg_replace('/\{fecha\}/i', $fechaFormateadaRazon, $razonTexto);
        
        $tamanioRazon = (int)($certConfig['tamanio_razon'] ?? 24);
        $anchoRazon = (int)($certConfig['ancho_razon'] ?? 600);
        $colorRazon = $certConfig['color_razon'] ?? '#333333';
        $lineasRazon = (int)($certConfig['lineas_razon'] ?? 0);
        
        error_log("=== PREVIEW RAZÓN ===");
        error_log("posicion_razon_x=" . ($certConfig['posicion_razon_x'] ?? 'NULL'));
        error_log("posicion_razon_y=" . ($certConfig['posicion_razon_y'] ?? 'NULL'));
        error_log("ancho_razon=$anchoRazon");
        error_log("tamanio_razon=$tamanioRazon");
        error_log("alineacion=" . ($certConfig['alineacion_razon'] ?? 'NULL'));
        error_log("lineas_razon=$lineasRazon");
        error_log("fuente_razon=" . ($certConfig['fuente_razon'] ?? 'NULL'));
        error_log("fontPathRazon=" . ($fontPathRazon ?? 'NULL'));
        error_log("Imagen: {$img->width()}x{$img->height()}");
        
        // Intentar usar fuente real para cálculo
        $lineas = [];
        if (function_exists('imagettfbbox') && !empty($fontPathRazon) && file_exists($fontPathRazon)) {
            $lineas = wordwrapText($razonTexto, $fontPathRazon, $tamanioRazon, $anchoRazon, $lineasRazon);
        } else {
             // Fallback wordwrap
             $charsPerLine = floor($anchoRazon / ($tamanioRazon * 0.5));
             $wrapped = wordwrap($razonTexto, $charsPerLine, "\n", true);
             $lineas = explode("\n", $wrapped);
        }

        $yCurrent = (int)$certConfig['posicion_razon_y'];
        $lineHeight = (int)($tamanioRazon * 1.3);
        $alineacion = $certConfig['alineacion_razon'] ?? 'left';
        
        // Usar drawJustifiedText que maneja alineación dentro del ancho configurado
        $xDraw = (int)$certConfig['posicion_razon_x'];
        drawJustifiedText(
            $img,
            $lineas,
            $xDraw,
            $yCurrent,
            $anchoRazon,
            $fontPathRazon,
            $tamanioRazon,
            $colorRazon,
            $lineHeight,
            true,
            $alineacion
        );
    }
    
    // Agregar FECHA
    if (in_array('fecha', $variablesHabilitadas)) {
         $fechaBase = !empty($certConfig['fecha_especifica']) ? $certConfig['fecha_especifica'] : date('Y-m-d');
         $formatoFecha = $certConfig['formato_fecha'] ?? 'd de F de Y';
         $fechaFormateada = formatearFechaPreview($fechaBase, $formatoFecha);
         
         $drawTextWithFallback(
            $fechaFormateada, 
            (int)$certConfig['posicion_fecha_x'], 
            (int)$certConfig['posicion_fecha_y'], 
            $fontPathFecha, 
            (int)($certConfig['tamanio_fecha'] ?? 20), 
            $certConfig['color_fecha'] ?? '#333333',
            'left'
        );
    }
    
    // Agregar QR real
    if (in_array('qr', $variablesHabilitadas)) {
        $qrSize = (int)$certConfig['tamanio_qr'];
        $qrX = (int)$certConfig['posicion_qr_x'];
        $qrY = (int)$certConfig['posicion_qr_y'];
        
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'imageBase64' => false,
        ]);
        
        $qrcode = new QRCode($options);
        // Usar public/uploads (dirname(dirname(__DIR__)) . '/public/uploads/')
        // Pero uploads esta en root segun otro codigo?
        // En config.php: uploadsDir = $publicAssetsDir . 'templates/' (assets/templates)
        // PERO aqui usamos una carpeta temp para preview QR.
        // Usemos public/uploads para consistencia si existe, o uploads en root.
        // El codigo anterior usaba dirname(dirname(dirname(__DIR__))) . '/uploads/'; (ROOT/uploads)
        $uploadsDir = dirname(dirname(dirname(__DIR__))) . '/uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $qrTempPath = $uploadsDir . 'preview_qr_' . time() . '.png';
        $qrcode->render('https://ejemplo.com/verify?code=' . $codigoEjemplo, $qrTempPath);
        
        if (file_exists($qrTempPath)) {
            try {
                $qrImg = Image::make($qrTempPath);
                $qrImg->resize($qrSize, $qrSize);
                // Coordenadas = top-left del cuadro (igual que el lienzo frontend)
                $img->insert($qrImg, 'top-left', $qrX, $qrY);
            } catch (Exception $e) {
                error_log("Error al insertar QR en preview: " . $e->getMessage());
            }
            @unlink($qrTempPath);
        }
    }
    
    // Agregar FIRMA (firmaPath ya fue resuelto arriba)
    if (in_array('firma', $variablesHabilitadas) && !empty($firmaPath) && file_exists($firmaPath)) {
        try {
            $firmaImg = Image::make($firmaPath);
            $firmaWidth = (int)$certConfig['tamanio_firma'];
            
            $firmaImg->resize($firmaWidth, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // Coordenadas = top-left del cuadro (igual que el lienzo frontend)
            $firmaX = (int)$certConfig['posicion_firma_x'];
            $firmaY = (int)$certConfig['posicion_firma_y'];
            
            $img->insert($firmaImg, 'top-left', $firmaX, $firmaY);
        } catch (Exception $e) {
            error_log("Error al insertar firma: " . $e->getMessage());
        }
    } elseif (in_array('firma', $variablesHabilitadas)) {
        // Marcador de firma si no hay imagen (coordenadas = top-left)
        $firmaX = (int)$certConfig['posicion_firma_x'];
        $firmaY = (int)$certConfig['posicion_firma_y'];
        $firmaWidth = (int)$certConfig['tamanio_firma'];
        $firmaHeight = (int)($firmaWidth * 0.4);
        
        $img->rectangle(
            $firmaX, 
            $firmaY, 
            $firmaX + $firmaWidth, 
            $firmaY + $firmaHeight, 
            function($draw) {
                $draw->background('rgba(155, 89, 182, 0.3)');
                $draw->border(2, '#9b59b6');
            }
        );
        
        // Center text within the rectangle
        $img->text('✍️ FIRMA', $firmaX + $firmaWidth/2, $firmaY + $firmaHeight/2, function($font) {
            $font->size(24);
            $font->color('#9b59b6');
            $font->align('center');
            $font->valign('middle');
        });
    }
    
    // Agregar sticker de destacado
    if (in_array('destacado', $variablesHabilitadas) || 
        (isset($certConfig['destacado_habilitado']) && $certConfig['destacado_habilitado'])) {
        
        $destacadoPosX = intval($certConfig['posicion_destacado_x'] ?? ($certConfig['destacado_posicion_x'] ?? 50));
        $destacadoPosY = intval($certConfig['posicion_destacado_y'] ?? ($certConfig['destacado_posicion_y'] ?? 50));
        $destacadoTamanio = intval($certConfig['destacado_tamanio'] ?? 100);
        $destacadoTipo = $certConfig['destacado_tipo'] ?? 'icono';
        $destacadoIcono = $certConfig['destacado_icono'] ?? 'estrella';
        $destacadoImagen = $certConfig['destacado_imagen'] ?? null;
        
        $stickerPath = null;
        
        if ($destacadoTipo === 'imagen') {
            if (isset($_FILES['destacado_imagen_file']) && $_FILES['destacado_imagen_file']['error'] === UPLOAD_ERR_OK) {
                $stickerPath = $_FILES['destacado_imagen_file']['tmp_name'];
            } elseif (!empty($destacadoImagen)) {
                // Usar imagen ya guardada
                $stickerPath = resolveAssetPath('uploads/stickers/' . $destacadoImagen);
            }
        }
        
        if (!$stickerPath || !file_exists($stickerPath)) {
            // Buscar icono en assets/stickers
            $stickerPath = resolveAssetPath('assets/stickers/' . $destacadoIcono . '.png');
        }
        
        if ($stickerPath && file_exists($stickerPath)) {
            try {
                $stickerImg = Image::make($stickerPath);
                // Forzar cuadrado igual que el lienzo frontend (tamanio x tamanio)
                $stickerImg->fit($destacadoTamanio, $destacadoTamanio);
                
                // Coordenadas = top-left del cuadro (igual que el lienzo frontend)
                $img->insert($stickerImg, 'top-left', $destacadoPosX, $destacadoPosY);
            } catch (Exception $e) {
                error_log("Error al agregar sticker en preview: " . $e->getMessage());
            }
        } else {
            // Dibujar marcador fallback
            $img->text('⭐ DESTACADO', $destacadoPosX, $destacadoPosY, function($font) {
                $font->size(16);
                $font->color('#f1c40f');
                $font->align('center');
                $font->valign('middle');
            });
        }
    }
    
    // Guardar vista previa temporal
    $previewFilename = 'preview_' . $tipo . '_' . $id . '_' . time() . '.png';
    // Usar ruta (ROOT/uploads)
    $uploadsDir = dirname(dirname(dirname(__DIR__))) . '/uploads/';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    $previewPath = $uploadsDir . $previewFilename;
    $img->save($previewPath, 90);
    
    // Limpiar vistas previas antiguas
    foreach (glob($uploadsDir . 'preview_*.png') as $file) {
        if (filemtime($file) < time() - 600) {
            @unlink($file);
        }
    }
    

    $response = [
        'success' => true,
        'preview_url' => '/cce-certificados/uploads/' . $previewFilename,
        'config' => $certConfig
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
