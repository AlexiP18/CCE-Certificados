<?php
/**
 * API para generar vista previa de certificado
 * Soporta preview para grupos y categorías
 * Puede usar la configuración actual del formulario (sin guardar) o la de la BD
 */
require_once '../includes/Auth.php';
require_once '../vendor/autoload.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();

use Intervention\Image\ImageManagerStatic as Image;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Configurar driver GD
Image::configure(['driver' => 'gd']);

header('Content-Type: application/json');

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
    
    // Factor de tolerancia estándar
    $toleranceFactor = 0.92;
    $effectiveMaxWidth = $maxWidth * $toleranceFactor;
    error_log("WordWrap: modo estándar, effectiveMaxWidth=$effectiveMaxWidth");
    
    foreach ($words as $word) {
        $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
        
        // Calcular ancho del texto
        $textWidth = getTextWidth($testLine, $fontPath, $fontSize);
        
        if ($textWidth > $effectiveMaxWidth && $currentLine !== '') {
            $lines[] = $currentLine;
            $currentLine = $word;
        } else {
            $currentLine = $testLine;
        }
    }
    
    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }
    
    return $lines;
}

/**
 * Calcular el ancho de un texto
 */
function getTextWidth($text, $fontPath, $fontSize) {
    if ($fontPath && file_exists($fontPath)) {
        $bbox = @imagettfbbox($fontSize, 0, $fontPath, $text);
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
function drawJustifiedText($img, $lines, $x, $y, $maxWidth, $fontPath, $fontSize, $color, $lineHeight, $isLastLineLeft = true, $alignment = 'justified') {
    // Unir las líneas en un solo texto
    $text = implode(' ', $lines);
    $text = trim($text);
    
    if (empty($text)) {
        return $y;
    }
    
    $numLines = count($lines);
    
    // Contar palabras promedio por línea para decidir si justificar
    $totalWords = count(explode(' ', $text));
    $avgWordsPerLine = $numLines > 0 ? $totalWords / $numLines : $totalWords;
    
    // Determinar alineación TCPDF según el parámetro
    // TCPDF usa: L=left, C=center, R=right, J=justified
    $tcpdfAlignment = 'L'; // Por defecto izquierda
    switch ($alignment) {
        case 'left':
            $tcpdfAlignment = 'L';
            break;
        case 'center':
            $tcpdfAlignment = 'C';
            break;
        case 'right':
            $tcpdfAlignment = 'R';
            break;
        case 'justified':
            // Solo justificar si hay suficientes palabras por línea
            $minWordsForJustify = 4;
            $tcpdfAlignment = ($avgWordsPerLine >= $minWordsForJustify) ? 'J' : 'L';
            break;
    }
    
    $shouldJustify = ($tcpdfAlignment === 'J');
    
    error_log("drawJustifiedText: alignment=$alignment, tcpdfAlignment=$tcpdfAlignment, avgWordsPerLine=$avgWordsPerLine, shouldJustify=" . ($shouldJustify ? 'true' : 'false'));
    
    // Crear PDF temporal con TCPDF
    $pdf = new TCPDF('L', 'px', array($maxWidth + 10, $numLines * $lineHeight + 50), true, 'UTF-8', false);
    $pdf->SetCreator('CCE');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    
    // Configurar fuente
    $fontName = 'helvetica';
    if ($fontPath && file_exists($fontPath)) {
        try {
            $fontName = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
            if (!$fontName) {
                $fontName = 'helvetica';
            }
        } catch (Exception $e) {
            $fontName = 'helvetica';
        }
    }
    
    // Convertir color hex a RGB
    $r = $g = $b = 0;
    if (preg_match('/^#?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$/i', $color, $matches)) {
        $r = hexdec($matches[1]);
        $g = hexdec($matches[2]);
        $b = hexdec($matches[3]);
    }
    
    $pdf->SetFont($fontName, '', $fontSize * 0.75);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY(0, 0);
    
    // La alineación principal ya está determinada en $tcpdfAlignment
    $mainAlign = $tcpdfAlignment;
    
    // Si usamos justificado y hay más de una línea, la última línea va a la izquierda
    // Para otras alineaciones (left, center, right), todas las líneas usan la misma alineación
    if ($isLastLineLeft && $numLines > 1 && $shouldJustify) {
        // Separar: líneas justificadas + última línea alineada a izquierda
        $linesJustified = array_slice($lines, 0, -1);
        $lastLine = end($lines);
        
        // Texto justificado (todas las líneas menos la última)
        $textJustified = implode(' ', $linesJustified);
        if (!empty($textJustified)) {
            $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $textJustified, 0, 'J', false, 1, 0, 0, true, 0, false, true, 0, 'T', false);
        }
        
        // Última línea alineada a la izquierda (usar GetY() para continuar desde donde terminó el texto anterior)
        if (!empty($lastLine)) {
            $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $lastLine, 0, 'L', false, 1, 0, $pdf->GetY(), true, 0, false, true, 0, 'T', false);
        }
    } else {
        // MultiCell con la alineación configurada (L, C, R, o J si hay suficientes palabras)
        $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $text, 0, $mainAlign, false, 1, 0, 0, true, 0, false, true, 0, 'T', false);
    }
    
    // Crear imagen del PDF
    $tempPdfPath = sys_get_temp_dir() . '/justify_' . uniqid() . '.pdf';
    $tempImgPath = sys_get_temp_dir() . '/justify_img_' . uniqid() . '.png';
    $pdf->Output($tempPdfPath, 'F');
    
    $converted = false;
    $imgHeight = $numLines * $lineHeight;
    
    // Determinar si debemos hacer trim completo o solo vertical
    // Para center y right, NO hacer trim horizontal para preservar la alineación
    $shouldTrimHorizontal = ($alignment === 'left' || $alignment === 'justified');
    
    // Intentar con extensión Imagick primero
    if (class_exists('Imagick')) {
        try {
            $imagick = new Imagick();
            // Usar 96 DPI para conversión pt->px (96 DPI es el estándar de pantalla)
            $imagick->setResolution(96, 96);
            $imagick->readImage($tempPdfPath . '[0]');
            $imagick->setImageFormat('png');
            $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            
            if ($shouldTrimHorizontal) {
                // Trim completo para left y justified
                $imagick->trimImage(0);
                $imagick->setImagePage(0, 0, 0, 0);
                
                // Escalar imagen para que el ancho coincida exactamente con maxWidth en píxeles
                $currentWidth = $imagick->getImageWidth();
                if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                    $scale = $maxWidth / $currentWidth;
                    $newHeight = (int)($imagick->getImageHeight() * $scale);
                    $imagick->resizeImage((int)$maxWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
                }
            } else {
                // Para center y right: NO hacer trim para preservar la alineación de TCPDF
                // TCPDF ya genera el texto centrado/derecha dentro del área maxWidth
                // Solo necesitamos escalar al ancho deseado sin recortar
                
                $currentWidth = $imagick->getImageWidth();
                $currentHeight = $imagick->getImageHeight();
                
                // Escalar al ancho deseado manteniendo proporciones
                if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                    $scale = $maxWidth / $currentWidth;
                    $newHeight = (int)($currentHeight * $scale);
                    $imagick->resizeImage((int)$maxWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
                }
            }
            
            $imagick->writeImage($tempImgPath);
            $imgHeight = $imagick->getImageHeight();
            $imagick->destroy();
            $converted = true;
        } catch (Exception $e) {
            error_log('Error Imagick extension: ' . $e->getMessage());
        }
    }
    
    // Fallback: usar ImageMagick desde línea de comandos
    if (!$converted) {
        // Para center/right, no usar -trim para preservar alineación
        $trimOption = $shouldTrimHorizontal ? '-trim' : '';
        
        // Intentar con 'magick' (ImageMagick 7+) - usar 96 DPI para conversión pt->px
        $cmd = sprintf(
            'magick -density 96 "%s[0]" %s -background transparent -alpha set "%s" 2>&1',
            $tempPdfPath,
            $trimOption,
            $tempImgPath
        );
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            // Intentar con 'convert' (ImageMagick 6)
            $cmd = sprintf(
                'convert -density 96 "%s[0]" %s -background transparent -alpha set "%s" 2>&1',
                $tempPdfPath,
                $trimOption,
                $tempImgPath
            );
            exec($cmd, $output, $returnCode);
        }
        
        if ($returnCode === 0 && file_exists($tempImgPath)) {
            $converted = true;
            // Obtener dimensiones y escalar si es necesario
            $imgInfo = @getimagesize($tempImgPath);
            if ($imgInfo) {
                $currentWidth = $imgInfo[0];
                $imgHeight = $imgInfo[1];
                
                // Escalar si el ancho no coincide
                if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                    $scale = $maxWidth / $currentWidth;
                    $newHeight = (int)($imgHeight * $scale);
                    $tempImgPath2 = sys_get_temp_dir() . '/justify_img2_' . uniqid() . '.png';
                    $cmd = sprintf(
                        'magick "%s" -resize %dx%d "%s" 2>&1',
                        $tempImgPath, (int)$maxWidth, $newHeight, $tempImgPath2
                    );
                    exec($cmd, $output2, $returnCode2);
                    if ($returnCode2 === 0 && file_exists($tempImgPath2)) {
                        @unlink($tempImgPath);
                        $tempImgPath = $tempImgPath2;
                        $imgHeight = $newHeight;
                    }
                }
            }
        } else {
            error_log('ImageMagick CLI error: ' . implode("\n", $output));
        }
    }
    
    // Insertar imagen en el certificado
    if ($converted && file_exists($tempImgPath) && filesize($tempImgPath) > 0) {
        $textImg = Image::make($tempImgPath);
        $img->insert($textImg, 'top-left', (int)$x, (int)$y);
        @unlink($tempImgPath);
        @unlink($tempPdfPath);
        return $y + $imgHeight;
    }
    
    @unlink($tempImgPath);
    @unlink($tempPdfPath);
    
    // Fallback final: dibujar línea por línea
    $currentY = $y;
    foreach ($lines as $line) {
        $img->text($line, $x, $currentY, function($font) use ($fontPath, $fontSize, $color) {
            if ($fontPath && file_exists($fontPath)) {
                $font->file($fontPath);
            }
            $font->size($fontSize);
            $font->color($color);
            $font->align('left');
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
    
    // Debug: Log para verificar qué datos se reciben
    error_log("API Preview - POST data: " . json_encode($_POST));
    error_log("API Preview - useFormData: " . ($useFormData ? 'true' : 'false'));
    error_log("API Preview - formato_fecha POST: " . ($_POST['formato_fecha'] ?? 'NOT SET'));
    
    if (!$id) {
        throw new Exception("ID no especificado");
    }
    
    $config = [];
    $plantillaPath = '';
    $firmaPath = '';
    
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
                   g.firma_nombre as grupo_firma_nombre, g.firma_cargo as grupo_firma_cargo
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
        if ($usarConfigPropia) {
            // Buscar plantilla activa en categoria_plantillas
            $stmtPlantilla = $pdo->prepare("SELECT archivo FROM categoria_plantillas WHERE categoria_id = ? AND es_activa = 1 LIMIT 1");
            $stmtPlantilla->execute([$id]);
            $plantillaActiva = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
            
            if ($plantillaActiva) {
                $plantillaPath = dirname(__DIR__) . '/uploads/categorias/' . $id . '/' . $plantillaActiva['archivo'];
            } elseif (!empty($cat['plantilla_archivo'])) {
                // Fallback a la plantilla antigua de categoría si existe
                $plantillaPath = dirname(__DIR__) . '/assets/templates/' . $cat['plantilla_archivo'];
            }
        }
        
        // Si no usa plantilla propia o no encontró, buscar del grupo
        if (empty($plantillaPath) || !file_exists($plantillaPath)) {
            // Buscar plantilla activa del grupo en grupo_plantillas (incluyendo config de destacado y lineas_razon)
            $stmtGrupoPlantilla = $pdo->prepare("SELECT archivo, destacado_habilitado, destacado_tipo, destacado_icono, destacado_imagen, destacado_posicion_x, destacado_posicion_y, destacado_tamanio, lineas_razon FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
            $stmtGrupoPlantilla->execute([$cat['grupo_id']]);
            $grupoPlantillaActiva = $stmtGrupoPlantilla->fetch(PDO::FETCH_ASSOC);
            
            if ($grupoPlantillaActiva) {
                $plantillaPath = dirname(__DIR__) . '/uploads/grupos/' . $cat['grupo_id'] . '/' . $grupoPlantillaActiva['archivo'];
                // Guardar configuración de destacado y lineas_razon
                $destacadoConfig = [
                    'destacado_habilitado' => $grupoPlantillaActiva['destacado_habilitado'] ?? 0,
                    'destacado_tipo' => $grupoPlantillaActiva['destacado_tipo'] ?? 'icono',
                    'destacado_icono' => $grupoPlantillaActiva['destacado_icono'] ?? 'estrella',
                    'destacado_imagen' => $grupoPlantillaActiva['destacado_imagen'] ?? null,
                    'destacado_posicion_x' => $grupoPlantillaActiva['destacado_posicion_x'] ?? 50,
                    'destacado_posicion_y' => $grupoPlantillaActiva['destacado_posicion_y'] ?? 50,
                    'destacado_tamanio' => $grupoPlantillaActiva['destacado_tamanio'] ?? 100,
                    'lineas_razon' => $grupoPlantillaActiva['lineas_razon'] ?? 0
                ];
            } elseif (!empty($cat['grupo_plantilla'])) {
                // Fallback a la plantilla antigua del grupo
                $plantillaPath = dirname(__DIR__) . '/assets/templates/' . $cat['grupo_plantilla'];
            }
        }
        
        // Determinar firma
        if ($usarConfigPropia && !empty($cat['plantilla_archivo_firma'])) {
            $firmaPath = dirname(__DIR__) . '/uploads/categorias/' . $id . '/' . $cat['plantilla_archivo_firma'];
            if (!file_exists($firmaPath)) {
                $firmaPath = dirname(__DIR__) . '/assets/firmas/' . $cat['plantilla_archivo_firma'];
            }
        } elseif (!empty($cat['grupo_firma_imagen'])) {
            $firmaPath = dirname(__DIR__) . '/assets/firmas/' . $cat['grupo_firma_imagen'];
        }
        
        // Construir configuración según si usa plantilla propia o del grupo
        if ($usarConfigPropia) {
            $config = [
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
                'color_fecha' => $cat['plantilla_color_fecha'] ?? '#333333'
            ];
        } else {
            // Usar configuración del grupo
            $config = [
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
                'lineas_razon' => 0, // Se llenará desde grupo_plantillas si está disponible
                'formato_fecha' => $cat['grupo_formato_fecha'] ?? 'd de F de Y',
                'fuente_fecha' => $cat['grupo_fuente_fecha'] ?? 'Roboto-Regular',
                'tamanio_fecha' => $cat['grupo_tamanio_fecha'] ?? 20,
                'color_fecha' => $cat['grupo_color_fecha'] ?? '#333333',
                'firma_nombre' => $cat['grupo_firma_nombre'] ?? '',
                'firma_cargo' => $cat['grupo_firma_cargo'] ?? ''
            ];
        }
        
        // Agregar configuración de destacado si está disponible
        if (isset($destacadoConfig)) {
            $config = array_merge($config, $destacadoConfig);
        }
        
        // Si use_form_data está activo, sobrescribir con valores del formulario
        if ($useFormData) {
            if (isset($_POST['posicion_nombre_x'])) $config['posicion_nombre_x'] = intval($_POST['posicion_nombre_x']);
            if (isset($_POST['posicion_nombre_y'])) $config['posicion_nombre_y'] = intval($_POST['posicion_nombre_y']);
            if (isset($_POST['posicion_razon_x'])) $config['posicion_razon_x'] = intval($_POST['posicion_razon_x']);
            if (isset($_POST['posicion_razon_y'])) $config['posicion_razon_y'] = intval($_POST['posicion_razon_y']);
            if (isset($_POST['posicion_fecha_x'])) $config['posicion_fecha_x'] = intval($_POST['posicion_fecha_x']);
            if (isset($_POST['posicion_fecha_y'])) $config['posicion_fecha_y'] = intval($_POST['posicion_fecha_y']);
            if (isset($_POST['posicion_qr_x'])) $config['posicion_qr_x'] = intval($_POST['posicion_qr_x']);
            if (isset($_POST['posicion_qr_y'])) $config['posicion_qr_y'] = intval($_POST['posicion_qr_y']);
            if (isset($_POST['posicion_firma_x'])) $config['posicion_firma_x'] = intval($_POST['posicion_firma_x']);
            if (isset($_POST['posicion_firma_y'])) $config['posicion_firma_y'] = intval($_POST['posicion_firma_y']);
            if (isset($_POST['fuente_nombre'])) $config['fuente_nombre'] = $_POST['fuente_nombre'];
            if (isset($_POST['formato_nombre'])) $config['formato_nombre'] = $_POST['formato_nombre'];
            if (isset($_POST['tamanio_fuente'])) $config['tamanio_fuente'] = intval($_POST['tamanio_fuente']);
            if (isset($_POST['color_texto'])) $config['color_texto'] = $_POST['color_texto'];
            if (isset($_POST['tamanio_qr'])) $config['tamanio_qr'] = intval($_POST['tamanio_qr']);
            if (isset($_POST['tamanio_firma'])) $config['tamanio_firma'] = intval($_POST['tamanio_firma']);
            if (isset($_POST['variables_habilitadas'])) $config['variables_habilitadas'] = $_POST['variables_habilitadas'];
            // Razón
            if (isset($_POST['razon_defecto'])) $config['razon_defecto'] = $_POST['razon_defecto'];
            if (isset($_POST['fuente_razon'])) $config['fuente_razon'] = $_POST['fuente_razon'];
            if (isset($_POST['tamanio_razon'])) $config['tamanio_razon'] = intval($_POST['tamanio_razon']);
            if (isset($_POST['color_razon'])) $config['color_razon'] = $_POST['color_razon'];
            if (isset($_POST['ancho_razon'])) $config['ancho_razon'] = intval($_POST['ancho_razon']);
            if (isset($_POST['lineas_razon'])) $config['lineas_razon'] = intval($_POST['lineas_razon']);
            if (isset($_POST['alineacion_razon'])) $config['alineacion_razon'] = $_POST['alineacion_razon'];
            // Fecha
            if (isset($_POST['formato_fecha'])) $config['formato_fecha'] = $_POST['formato_fecha'];
            if (isset($_POST['fuente_fecha'])) $config['fuente_fecha'] = $_POST['fuente_fecha'];
            if (isset($_POST['tamanio_fecha'])) $config['tamanio_fecha'] = intval($_POST['tamanio_fecha']);
            if (isset($_POST['color_fecha'])) $config['color_fecha'] = $_POST['color_fecha'];
        }
        
    } else {
        // Cargar configuración de grupo
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$grupo) {
            throw new Exception("Grupo no encontrado");
        }
        
        // Buscar plantilla activa en el slider de plantillas (incluyendo lineas_razon y alineacion_razon)
        $stmtPlantilla = $pdo->prepare("SELECT archivo, lineas_razon, alineacion_razon FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1");
        $stmtPlantilla->execute([$id]);
        $plantillaActiva = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
        
        $lineasRazonFromPlantilla = 0;
        $alineacionRazonFromPlantilla = 'justified';
        if ($plantillaActiva) {
            $plantillaPath = dirname(__DIR__) . '/uploads/grupos/' . $id . '/' . $plantillaActiva['archivo'];
            $lineasRazonFromPlantilla = $plantillaActiva['lineas_razon'] ?? 0;
            $alineacionRazonFromPlantilla = $plantillaActiva['alineacion_razon'] ?? 'justified';
        } elseif (!empty($grupo['plantilla'])) {
            // Fallback a la plantilla antigua si existe
            $plantillaPath = dirname(__DIR__) . '/assets/templates/' . $grupo['plantilla'];
        }
        
        if (!empty($grupo['firma_imagen'])) {
            $firmaPath = dirname(__DIR__) . '/assets/firmas/' . $grupo['firma_imagen'];
        }
        
        $config = [
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
            'variables_habilitadas' => $grupo['variables_habilitadas'] ?? '["nombre","qr","firma"]',
            // Configuración de Razón (valores por defecto para grupos)
            'razon_defecto' => $grupo['razon_defecto'] ?? 'Por su destacada participación',
            'fuente_razon' => $grupo['fuente_razon'] ?? 'Roboto-Regular',
            'tamanio_razon' => $grupo['tamanio_razon'] ?? 24,
            'color_razon' => $grupo['color_razon'] ?? '#333333',
            'ancho_razon' => $grupo['ancho_razon'] ?? 600,
            'lineas_razon' => $lineasRazonFromPlantilla,
            'alineacion_razon' => $alineacionRazonFromPlantilla,
            // Configuración de Fecha (valores por defecto para grupos)
            'formato_fecha' => $grupo['formato_fecha'] ?? 'd de F de Y',
            'fecha_especifica' => null,
            'fuente_fecha' => $grupo['fuente_fecha'] ?? 'Roboto-Regular',
            'tamanio_fecha' => $grupo['tamanio_fecha'] ?? 20,
            'color_fecha' => $grupo['color_fecha'] ?? '#333333'
        ];
        
        // Si use_form_data está activo, sobrescribir con valores del formulario (para grupos)
        if ($useFormData) {
            if (isset($_POST['posicion_nombre_x'])) $config['posicion_nombre_x'] = intval($_POST['posicion_nombre_x']);
            if (isset($_POST['posicion_nombre_y'])) $config['posicion_nombre_y'] = intval($_POST['posicion_nombre_y']);
            if (isset($_POST['posicion_razon_x'])) $config['posicion_razon_x'] = intval($_POST['posicion_razon_x']);
            if (isset($_POST['posicion_razon_y'])) $config['posicion_razon_y'] = intval($_POST['posicion_razon_y']);
            if (isset($_POST['posicion_fecha_x'])) $config['posicion_fecha_x'] = intval($_POST['posicion_fecha_x']);
            if (isset($_POST['posicion_fecha_y'])) $config['posicion_fecha_y'] = intval($_POST['posicion_fecha_y']);
            if (isset($_POST['posicion_qr_x'])) $config['posicion_qr_x'] = intval($_POST['posicion_qr_x']);
            if (isset($_POST['posicion_qr_y'])) $config['posicion_qr_y'] = intval($_POST['posicion_qr_y']);
            if (isset($_POST['posicion_firma_x'])) $config['posicion_firma_x'] = intval($_POST['posicion_firma_x']);
            if (isset($_POST['posicion_firma_y'])) $config['posicion_firma_y'] = intval($_POST['posicion_firma_y']);
            if (isset($_POST['fuente_nombre'])) $config['fuente_nombre'] = $_POST['fuente_nombre'];
            if (isset($_POST['formato_nombre'])) $config['formato_nombre'] = $_POST['formato_nombre'];
            if (isset($_POST['tamanio_fuente'])) $config['tamanio_fuente'] = intval($_POST['tamanio_fuente']);
            if (isset($_POST['color_texto'])) $config['color_texto'] = $_POST['color_texto'];
            if (isset($_POST['tamanio_qr'])) $config['tamanio_qr'] = intval($_POST['tamanio_qr']);
            if (isset($_POST['tamanio_firma'])) $config['tamanio_firma'] = intval($_POST['tamanio_firma']);
            if (isset($_POST['variables_habilitadas'])) $config['variables_habilitadas'] = $_POST['variables_habilitadas'];
            // Razón
            if (isset($_POST['razon_defecto'])) $config['razon_defecto'] = $_POST['razon_defecto'];
            if (isset($_POST['fuente_razon'])) $config['fuente_razon'] = $_POST['fuente_razon'];
            if (isset($_POST['tamanio_razon'])) $config['tamanio_razon'] = intval($_POST['tamanio_razon']);
            if (isset($_POST['color_razon'])) $config['color_razon'] = $_POST['color_razon'];
            if (isset($_POST['ancho_razon'])) $config['ancho_razon'] = intval($_POST['ancho_razon']);
            if (isset($_POST['lineas_razon'])) $config['lineas_razon'] = intval($_POST['lineas_razon']);
            if (isset($_POST['alineacion_razon'])) $config['alineacion_razon'] = $_POST['alineacion_razon'];
            // Fecha
            if (isset($_POST['formato_fecha'])) $config['formato_fecha'] = $_POST['formato_fecha'];
            if (isset($_POST['fuente_fecha'])) $config['fuente_fecha'] = $_POST['fuente_fecha'];
            if (isset($_POST['tamanio_fecha'])) $config['tamanio_fecha'] = intval($_POST['tamanio_fecha']);
            if (isset($_POST['color_fecha'])) $config['color_fecha'] = $_POST['color_fecha'];
        }
    }
    
    // Verificar plantilla
    error_log("API Preview - plantillaPath: " . $plantillaPath);
    error_log("API Preview - file_exists: " . (file_exists($plantillaPath) ? 'true' : 'false'));
    
    if (empty($plantillaPath) || !file_exists($plantillaPath)) {
        throw new Exception("No hay plantilla configurada o no se encuentra el archivo. Path: " . $plantillaPath);
    }
    
    // Cargar plantilla
    $img = Image::make($plantillaPath);
    
    // Variables habilitadas
    $variablesHabilitadas = json_decode($config['variables_habilitadas'], true) ?: ['nombre', 'qr', 'firma'];
    
    // Obtener datos del primer estudiante del grupo (si existe)
    // Aplicar formato según configuración al nombre por defecto
    $formatoNombre = $config['formato_nombre'] ?? 'mayusculas';
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
            $formatoNombre = $config['formato_nombre'] ?? 'mayusculas';
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
    $fontsDir = dirname(__DIR__) . '/assets/fonts/';
    $getFontPath = function($fontName) use ($fontsDir, $pdo) {
        if (empty($fontName)) {
            $defaultFont = $fontsDir . 'Roboto-Regular.ttf';
            return file_exists($defaultFont) ? $defaultFont : null;
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
                    return $fontPath;
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
                    return $fontPath;
                }
            }
        }
        
        // Primero buscar en la carpeta de fuentes directamente
        $fontBasePath = $fontsDir . $fontName;
        foreach (['.ttf', '.otf', '.TTF', '.OTF', ''] as $ext) {
            if (file_exists($fontBasePath . $ext)) {
                return $fontBasePath . $ext;
            }
        }
        
        // Si no existe como archivo, buscar en la BD por nombre_archivo
        $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre_archivo = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fuente && !empty($fuente['archivo'])) {
            $fontPath = $fontsDir . $fuente['archivo'];
            if (file_exists($fontPath)) {
                return $fontPath;
            }
        }
        
        // También buscar por nombre
        $stmt = $pdo->prepare("SELECT archivo FROM fuentes_personalizadas WHERE nombre = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fuente && !empty($fuente['archivo'])) {
            $fontPath = $fontsDir . $fuente['archivo'];
            if (file_exists($fontPath)) {
                return $fontPath;
            }
        }
        
        // Fallback a Roboto
        $defaultFont = $fontsDir . 'Roboto-Regular.ttf';
        return file_exists($defaultFont) ? $defaultFont : null;
    };
    
    // Obtener rutas de fuentes para cada variable
    $fontPathNombre = $getFontPath($config['fuente_nombre'] ?? 'Roboto-Regular');
    $fontPathRazon = $getFontPath($config['fuente_razon'] ?? 'Roboto-Regular');
    $fontPathFecha = $getFontPath($config['fuente_fecha'] ?? 'Roboto-Regular');
    
    // Agregar NOMBRE
    if (in_array('nombre', $variablesHabilitadas)) {
        $img->text($nombreEjemplo, 
            (int)$config['posicion_nombre_x'], 
            (int)$config['posicion_nombre_y'], 
            function($font) use ($fontPathNombre, $config) {
                if ($fontPathNombre && file_exists($fontPathNombre)) {
                    $font->file($fontPathNombre);
                }
                $font->size((int)$config['tamanio_fuente']);
                $font->color($config['color_texto']);
                $font->align('left');
                $font->valign('top');
            }
        );
    }
    
    // Agregar RAZÓN
    if (in_array('razon', $variablesHabilitadas)) {
        // Usar texto de razón predeterminado si está configurado
        $razonTexto = !empty($config['razon_defecto']) ? $config['razon_defecto'] : $razonEjemplo;
        
        // Reemplazar variables entre llaves con valores reales
        // Obtener nombres de grupo y categoría si es necesario
        $nombreGrupo = '';
        $nombreCategoria = '';
        
        if ($tipo === 'categoria') {
            // Para categoría, obtener ambos nombres
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
            // Para grupo, obtener solo el nombre del grupo
            $stmtInfo = $pdo->prepare("SELECT nombre FROM grupos WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                $nombreGrupo = $info['nombre'];
            }
            // Usar la categoría del primer estudiante si existe, sino usar placeholder
            $nombreCategoria = $categoriaEjemplo;
        }
        
        // Fecha formateada para la variable {fecha}
        $fechaBaseRazon = !empty($config['fecha_especifica']) ? $config['fecha_especifica'] : date('Y-m-d');
        $formatoFechaRazon = $config['formato_fecha'] ?? 'd de F de Y';
        $fechaFormateadaRazon = formatearFechaPreview($fechaBaseRazon, $formatoFechaRazon);
        
        // Reemplazar variables (case insensitive)
        $razonTexto = preg_replace('/\{grupo\}/i', $nombreGrupo, $razonTexto);
        $razonTexto = preg_replace('/\{categoria\}/i', $nombreCategoria, $razonTexto);
        $razonTexto = preg_replace('/\{nombre\}/i', $nombreEjemplo, $razonTexto);
        $razonTexto = preg_replace('/\{fecha\}/i', $fechaFormateadaRazon, $razonTexto);
        
        $tamanioRazon = (int)($config['tamanio_razon'] ?? 24);
        $colorRazon = $config['color_razon'] ?? '#333333';
        $anchoRazon = (int)($config['ancho_razon'] ?? 600);
        $lineasRazon = (int)($config['lineas_razon'] ?? 0);
        $alineacionRazon = $config['alineacion_razon'] ?? 'justified';
        
        error_log("API Preview - Razon config: ancho=$anchoRazon, lineas=$lineasRazon, tamanio=$tamanioRazon, alineacion=$alineacionRazon");
        error_log("API Preview - Razon texto: " . substr($razonTexto, 0, 100));
        
        // Dividir texto en líneas según ancho configurado (y líneas esperadas del canvas)
        $lineas = wordwrapText($razonTexto, $fontPathRazon, $tamanioRazon, $anchoRazon, $lineasRazon);
        
        error_log("API Preview - wordwrapText devolvió " . count($lineas) . " líneas");
        
        $lineHeight = (int)($tamanioRazon * 1.3);
        
        // Dibujar texto con alineación configurada
        drawJustifiedText(
            $img,
            $lineas,
            (int)$config['posicion_razon_x'],
            (int)$config['posicion_razon_y'],
            $anchoRazon,
            $fontPathRazon,
            $tamanioRazon,
            $colorRazon,
            $lineHeight,
            true, // última línea según alineación base si es justificado
            $alineacionRazon
        );
    }
    
    // Agregar FECHA
    if (in_array('fecha', $variablesHabilitadas)) {
        // Usar fecha específica si está configurada, sino la fecha actual
        $fechaBase = !empty($config['fecha_especifica']) ? $config['fecha_especifica'] : date('Y-m-d');
        $formatoFecha = $config['formato_fecha'] ?? 'd de F de Y';
        
        // Debug: Log del formato de fecha
        error_log("API Preview - formatoFecha final: " . $formatoFecha);
        error_log("API Preview - config['formato_fecha']: " . ($config['formato_fecha'] ?? 'NOT SET'));
        
        $fechaFormateada = formatearFechaPreview($fechaBase, $formatoFecha);
        
        error_log("API Preview - fechaFormateada: " . $fechaFormateada);
        
        $tamanioFecha = (int)($config['tamanio_fecha'] ?? 20);
        $colorFecha = $config['color_fecha'] ?? '#333333';
        
        $img->text($fechaFormateada, 
            (int)$config['posicion_fecha_x'], 
            (int)$config['posicion_fecha_y'], 
            function($font) use ($fontPathFecha, $tamanioFecha, $colorFecha) {
                if ($fontPathFecha && file_exists($fontPathFecha)) {
                    $font->file($fontPathFecha);
                }
                $font->size($tamanioFecha);
                $font->color($colorFecha);
                $font->align('left');
                $font->valign('top');
            }
        );
    }
    
    // Agregar QR real
    if (in_array('qr', $variablesHabilitadas)) {
        $qrSize = (int)$config['tamanio_qr'];
        $qrX = (int)$config['posicion_qr_x'];
        $qrY = (int)$config['posicion_qr_y'];
        
        // Generar QR real
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'imageBase64' => false,
        ]);
        
        $qrcode = new QRCode($options);
        $qrTempPath = dirname(__DIR__) . '/uploads/preview_qr_' . time() . '.png';
        $qrcode->render('https://ejemplo.com/verify?code=' . $codigoEjemplo, $qrTempPath);
        
        if (file_exists($qrTempPath)) {
            $qrImg = Image::make($qrTempPath);
            $qrImg->resize($qrSize, $qrSize);
            
            // Posición centrada
            $qrTopLeftX = $qrX - ($qrSize / 2);
            $qrTopLeftY = $qrY - ($qrSize / 2);
            
            $img->insert($qrImg, 'top-left', (int)$qrTopLeftX, (int)$qrTopLeftY);
            @unlink($qrTempPath);
        }
    }
    
    // Agregar FIRMA
    if (in_array('firma', $variablesHabilitadas) && !empty($firmaPath) && file_exists($firmaPath)) {
        $firmaImg = Image::make($firmaPath);
        $firmaWidth = (int)$config['tamanio_firma'];
        
        // Redimensionar manteniendo proporción
        $firmaImg->resize($firmaWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        
        // Posición centrada
        $firmaX = (int)$config['posicion_firma_x'] - ($firmaImg->width() / 2);
        $firmaY = (int)$config['posicion_firma_y'] - ($firmaImg->height() / 2);
        
        $img->insert($firmaImg, 'top-left', (int)$firmaX, (int)$firmaY);
    } elseif (in_array('firma', $variablesHabilitadas)) {
        // Si no hay imagen de firma, dibujar un marcador
        $firmaX = (int)$config['posicion_firma_x'];
        $firmaY = (int)$config['posicion_firma_y'];
        $firmaWidth = (int)$config['tamanio_firma'];
        $firmaHeight = (int)($firmaWidth * 0.4);
        
        $img->rectangle(
            $firmaX - $firmaWidth/2, 
            $firmaY - $firmaHeight/2, 
            $firmaX + $firmaWidth/2, 
            $firmaY + $firmaHeight/2, 
            function($draw) {
                $draw->background('rgba(155, 89, 182, 0.3)');
                $draw->border(2, '#9b59b6');
            }
        );
        
        $img->text('✍️ FIRMA', $firmaX, $firmaY, function($font) {
            $font->size(24);
            $font->color('#9b59b6');
            $font->align('center');
            $font->valign('middle');
        });
    }
    
    // Agregar sticker de destacado si está habilitado (para previsualización siempre se muestra si está en variables)
    if (in_array('destacado', $variablesHabilitadas) || 
        (isset($config['destacado_habilitado']) && $config['destacado_habilitado'])) {
        // Obtener configuración de destacado desde POST, config (BD) o valores por defecto
        $destacadoPosX = isset($_POST['posicion_destacado_x']) ? intval($_POST['posicion_destacado_x']) : 
                         (isset($config['destacado_posicion_x']) ? intval($config['destacado_posicion_x']) : 50);
        $destacadoPosY = isset($_POST['posicion_destacado_y']) ? intval($_POST['posicion_destacado_y']) : 
                         (isset($config['destacado_posicion_y']) ? intval($config['destacado_posicion_y']) : 50);
        $destacadoTamanio = isset($_POST['tamanio_destacado']) ? intval($_POST['tamanio_destacado']) : 
                           (isset($config['destacado_tamanio']) ? intval($config['destacado_tamanio']) : 100);
        $destacadoTipo = $_POST['destacado_tipo'] ?? ($config['destacado_tipo'] ?? 'icono');
        $destacadoIcono = $_POST['destacado_icono'] ?? ($config['destacado_icono'] ?? 'estrella');
        $destacadoImagen = $_POST['destacado_imagen'] ?? ($config['destacado_imagen'] ?? null);
        
        $stickerPath = null;
        
        // Determinar ruta del sticker
        if ($destacadoTipo === 'imagen') {
            // Primero verificar si se envió un archivo nuevo
            if (isset($_FILES['destacado_imagen_file']) && $_FILES['destacado_imagen_file']['error'] === UPLOAD_ERR_OK) {
                // Usar archivo temporal directamente para preview
                $stickerPath = $_FILES['destacado_imagen_file']['tmp_name'];
            } elseif (!empty($destacadoImagen)) {
                // Usar imagen ya guardada
                $stickerPath = dirname(__DIR__) . '/uploads/stickers/' . $destacadoImagen;
            }
        }
        
        // Si no hay imagen personalizada o no existe, usar icono predeterminado
        if (!$stickerPath || !file_exists($stickerPath)) {
            $stickerPath = dirname(__DIR__) . '/assets/stickers/' . $destacadoIcono . '.png';
        }
        
        if (file_exists($stickerPath)) {
            try {
                $stickerImg = Image::make($stickerPath);
                
                // Redimensionar manteniendo proporción
                $stickerImg->resize($destacadoTamanio, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                
                // Calcular posición centrada
                $stickerX = $destacadoPosX - ($stickerImg->width() / 2);
                $stickerY = $destacadoPosY - ($stickerImg->height() / 2);
                
                $img->insert($stickerImg, 'top-left', (int)$stickerX, (int)$stickerY);
            } catch (Exception $e) {
                error_log("Error al agregar sticker en preview: " . $e->getMessage());
            }
        } else {
            // Dibujar un marcador si no se encuentra el sticker
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
    $previewPath = dirname(__DIR__) . '/uploads/' . $previewFilename;
    $img->save($previewPath, 90);
    
    // Limpiar vistas previas antiguas (más de 10 minutos)
    $uploadsDir = dirname(__DIR__) . '/uploads/';
    foreach (glob($uploadsDir . 'preview_*.png') as $file) {
        if (filemtime($file) < time() - 600) {
            @unlink($file);
        }
    }
    
    // Construir URL correcta para el navegador
    $baseUrl = '/cce-certificados/uploads/';
    
    echo json_encode([
        'success' => true,
        'preview_url' => $baseUrl . $previewFilename,
        'config' => $config
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
