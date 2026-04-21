<?php
/**
 * Script para crear stickers de "Destacado" predeterminados
 * Genera imágenes PNG para usar como insignias en certificados
 */

$stickersDir = __DIR__ . '/../assets/stickers/';

// Verificar que GD está habilitado
if (!extension_loaded('gd')) {
    die("Error: La extensión GD no está habilitada\n");
}

// Crear directorio si no existe
if (!is_dir($stickersDir)) {
    mkdir($stickersDir, 0755, true);
}

/**
 * Crear sticker de estrella dorada
 */
function crearEstrellaDorada($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $gold = imagecolorallocate($img, 255, 193, 7);
    $goldDark = imagecolorallocate($img, 218, 165, 32);
    $goldLight = imagecolorallocate($img, 255, 215, 0);
    
    // Dibujar estrella de 5 puntas
    $cx = $size / 2;
    $cy = $size / 2;
    $outerRadius = $size * 0.45;
    $innerRadius = $size * 0.2;
    
    $points = [];
    for ($i = 0; $i < 10; $i++) {
        $radius = ($i % 2 == 0) ? $outerRadius : $innerRadius;
        $angle = deg2rad(-90 + $i * 36);
        $points[] = $cx + $radius * cos($angle);
        $points[] = $cy + $radius * sin($angle);
    }
    
    // Dibujar estrella con gradiente simulado
    imagefilledpolygon($img, $points, $gold);
    
    // Añadir brillo
    $brightPoints = [];
    for ($i = 0; $i < 10; $i++) {
        $radius = ($i % 2 == 0) ? $outerRadius * 0.8 : $innerRadius * 0.8;
        $angle = deg2rad(-90 + $i * 36);
        $brightPoints[] = $cx + $radius * cos($angle) - 5;
        $brightPoints[] = $cy + $radius * sin($angle) - 5;
    }
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: estrella_dorada.png\n";
}

/**
 * Crear sticker de medalla de oro
 */
function crearMedallaOro($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $gold = imagecolorallocate($img, 255, 193, 7);
    $goldDark = imagecolorallocate($img, 184, 134, 11);
    $red = imagecolorallocate($img, 220, 53, 69);
    $redDark = imagecolorallocate($img, 139, 0, 0);
    
    $cx = $size / 2;
    $cy = $size / 2;
    
    // Cinta (parte superior)
    $ribbonWidth = $size * 0.25;
    $ribbonLeft = [];
    $ribbonRight = [];
    
    // Cinta izquierda
    imagefilledpolygon($img, [
        $cx - 5, 0,
        $cx - $ribbonWidth, 0,
        $cx - $ribbonWidth * 0.7, $size * 0.45,
        $cx - 5, $size * 0.35
    ], 4, $red);
    
    // Cinta derecha
    imagefilledpolygon($img, [
        $cx + 5, 0,
        $cx + $ribbonWidth, 0,
        $cx + $ribbonWidth * 0.7, $size * 0.45,
        $cx + 5, $size * 0.35
    ], 4, $redDark);
    
    // Círculo principal (medalla)
    $medalRadius = $size * 0.35;
    $medalCy = $cy + $size * 0.1;
    imagefilledellipse($img, $cx, $medalCy, $medalRadius * 2, $medalRadius * 2, $goldDark);
    imagefilledellipse($img, $cx - 3, $medalCy - 3, $medalRadius * 1.8, $medalRadius * 1.8, $gold);
    
    // Número "1" en el centro
    $fontSize = 5;
    $text = "1";
    $textColor = imagecolorallocate($img, 139, 69, 19);
    $fontWidth = imagefontwidth($fontSize) * strlen($text);
    $fontHeight = imagefontheight($fontSize);
    imagestring($img, $fontSize, $cx - $fontWidth/2, $medalCy - $fontHeight/2, $text, $textColor);
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: medalla_oro.png\n";
}

/**
 * Crear sticker de trofeo
 */
function crearTrofeo($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $gold = imagecolorallocate($img, 255, 193, 7);
    $goldDark = imagecolorallocate($img, 184, 134, 11);
    $brown = imagecolorallocate($img, 101, 67, 33);
    
    $cx = $size / 2;
    
    // Copa del trofeo
    $copTop = $size * 0.15;
    $copBottom = $size * 0.55;
    $copWidthTop = $size * 0.35;
    $copWidthBottom = $size * 0.2;
    
    // Forma de la copa
    imagefilledpolygon($img, [
        $cx - $copWidthTop, $copTop,
        $cx + $copWidthTop, $copTop,
        $cx + $copWidthBottom, $copBottom,
        $cx - $copWidthBottom, $copBottom
    ], 4, $gold);
    
    // Asas laterales
    imagearc($img, $cx - $copWidthTop, $copTop + $size * 0.15, $size * 0.2, $size * 0.25, 90, 270, $goldDark);
    imagearc($img, $cx + $copWidthTop, $copTop + $size * 0.15, $size * 0.2, $size * 0.25, -90, 90, $goldDark);
    
    // Tallo
    imagefilledrectangle($img, $cx - $size * 0.05, $copBottom, $cx + $size * 0.05, $size * 0.75, $goldDark);
    
    // Base
    imagefilledellipse($img, $cx, $size * 0.8, $size * 0.4, $size * 0.1, $brown);
    imagefilledellipse($img, $cx, $size * 0.85, $size * 0.5, $size * 0.1, $brown);
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: trofeo.png\n";
}

/**
 * Crear sticker de sello de excelencia
 */
function crearSelloExcelencia($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $blue = imagecolorallocate($img, 52, 152, 219);
    $blueDark = imagecolorallocate($img, 41, 128, 185);
    $gold = imagecolorallocate($img, 255, 193, 7);
    $white = imagecolorallocate($img, 255, 255, 255);
    
    $cx = $size / 2;
    $cy = $size / 2;
    
    // Borde ondulado (simular sello)
    $outerRadius = $size * 0.45;
    $points = 24;
    $sealPoints = [];
    
    for ($i = 0; $i < $points * 2; $i++) {
        $radius = ($i % 2 == 0) ? $outerRadius : $outerRadius * 0.85;
        $angle = deg2rad($i * (360 / ($points * 2)));
        $sealPoints[] = $cx + $radius * cos($angle);
        $sealPoints[] = $cy + $radius * sin($angle);
    }
    
    imagefilledpolygon($img, $sealPoints, $points * 2, $blue);
    
    // Círculo interior
    imagefilledellipse($img, $cx, $cy, $outerRadius * 1.4, $outerRadius * 1.4, $blueDark);
    imagefilledellipse($img, $cx, $cy, $outerRadius * 1.2, $outerRadius * 1.2, $gold);
    imagefilledellipse($img, $cx, $cy, $outerRadius, $outerRadius, $blue);
    
    // Check mark
    $checkColor = $white;
    imagesetthickness($img, 4);
    imageline($img, $cx - 20, $cy, $cx - 5, $cy + 15, $checkColor);
    imageline($img, $cx - 5, $cy + 15, $cx + 25, $cy - 15, $checkColor);
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: sello_excelencia.png\n";
}

/**
 * Crear sticker de corona
 */
function crearCorona($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $gold = imagecolorallocate($img, 255, 193, 7);
    $goldDark = imagecolorallocate($img, 184, 134, 11);
    $red = imagecolorallocate($img, 220, 53, 69);
    
    $cx = $size / 2;
    
    // Base de la corona
    $baseTop = $size * 0.6;
    $baseBottom = $size * 0.8;
    $baseWidth = $size * 0.4;
    
    imagefilledrectangle($img, $cx - $baseWidth, $baseTop, $cx + $baseWidth, $baseBottom, $goldDark);
    
    // Parte superior de la corona (5 picos)
    $crownTop = $size * 0.15;
    $crownBottom = $baseTop;
    $peakWidth = $baseWidth * 2 / 5;
    
    // Dibujar los 5 picos
    $peakPositions = [
        $cx - $baseWidth,
        $cx - $baseWidth + $peakWidth,
        $cx,
        $cx + $baseWidth - $peakWidth,
        $cx + $baseWidth
    ];
    
    for ($i = 0; $i < 5; $i++) {
        $x = $peakPositions[$i];
        $peakTop = ($i == 0 || $i == 4) ? $crownTop + 20 : (($i == 2) ? $crownTop : $crownTop + 10);
        
        imagefilledpolygon($img, [
            $x - $peakWidth/2, $crownBottom,
            $x, $peakTop,
            $x + $peakWidth/2, $crownBottom
        ], 3, $gold);
        
        // Círculo decorativo en cada pico
        imagefilledellipse($img, $x, $peakTop + 10, 15, 15, $red);
    }
    
    // Banda decorativa
    imagefilledrectangle($img, $cx - $baseWidth, $baseTop, $cx + $baseWidth, $baseTop + 10, $red);
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: corona.png\n";
}

/**
 * Crear sticker de laurel
 */
function crearLaurel($path, $size = 200) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Fondo transparente
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colores
    $green = imagecolorallocate($img, 46, 125, 50);
    $greenDark = imagecolorallocate($img, 27, 94, 32);
    $gold = imagecolorallocate($img, 255, 193, 7);
    
    $cx = $size / 2;
    $cy = $size / 2;
    
    // Dibujar hojas de laurel (lado izquierdo)
    for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad(-30 - $i * 20);
        $leafX = $cx - 30 + 10 * cos($angle + 1.5);
        $leafY = $cy - 50 + $i * 20;
        
        imagefilledellipse($img, $leafX - 15, $leafY, 30, 15, $green);
    }
    
    // Dibujar hojas de laurel (lado derecho)
    for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad(30 + $i * 20);
        $leafX = $cx + 30 + 10 * cos($angle - 1.5);
        $leafY = $cy - 50 + $i * 20;
        
        imagefilledellipse($img, $leafX + 15, $leafY, 30, 15, $greenDark);
    }
    
    // Estrella central
    $starCx = $cx;
    $starCy = $cy - 20;
    $outerRadius = 25;
    $innerRadius = 10;
    
    $starPoints = [];
    for ($i = 0; $i < 10; $i++) {
        $radius = ($i % 2 == 0) ? $outerRadius : $innerRadius;
        $angle = deg2rad(-90 + $i * 36);
        $starPoints[] = $starCx + $radius * cos($angle);
        $starPoints[] = $starCy + $radius * sin($angle);
    }
    
    imagefilledpolygon($img, $starPoints, 10, $gold);
    
    // Lazo en la base
    imagefilledellipse($img, $cx, $cy + 60, 40, 20, $gold);
    
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "✓ Creado: laurel.png\n";
}

// Ejecutar creación de stickers
echo "=== Creando stickers de Destacado ===\n\n";

crearEstrellaDorada($stickersDir . 'estrella_dorada.png');
crearMedallaOro($stickersDir . 'medalla_oro.png');
crearTrofeo($stickersDir . 'trofeo.png');
crearSelloExcelencia($stickersDir . 'sello_excelencia.png');
crearCorona($stickersDir . 'corona.png');
crearLaurel($stickersDir . 'laurel.png');

echo "\n=== Stickers creados exitosamente ===\n";
echo "Ubicación: assets/stickers/\n";

// Listar archivos creados
echo "\nArchivos generados:\n";
foreach (glob($stickersDir . '*.png') as $file) {
    $size = round(filesize($file) / 1024, 2);
    echo "  - " . basename($file) . " ({$size} KB)\n";
}
