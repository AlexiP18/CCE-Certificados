<?php
/**
 * Script para descargar fuentes TTF de Google Fonts
 * y almacenarlas localmente para uso en certificados
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();
$fontsDir = dirname(__DIR__) . '/assets/fonts/';

// Asegurar que el directorio existe
if (!is_dir($fontsDir)) {
    mkdir($fontsDir, 0755, true);
}

echo "=== Descargando fuentes de Google Fonts ===\n\n";

// Obtener fuentes de Google de la BD
$stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE archivo LIKE 'google:%' AND activo = 1");
$fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$descargadas = 0;
$errores = 0;

foreach ($fuentes as $fuente) {
    $fontName = str_replace('google:', '', $fuente['archivo']);
    $fontName = str_replace('+', ' ', $fontName);
    
    // Nombre del archivo local
    $localFileName = 'google_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fontName) . '.ttf';
    $localPath = $fontsDir . $localFileName;
    
    // Si ya existe, saltar
    if (file_exists($localPath)) {
        echo "⏭ {$fuente['nombre']}: Ya existe\n";
        
        // Actualizar la BD con el archivo local
        $stmt2 = $pdo->prepare("UPDATE fuentes_personalizadas SET archivo = ? WHERE id = ?");
        $stmt2->execute([$localFileName, $fuente['id']]);
        
        $descargadas++;
        continue;
    }
    
    echo "⏳ Descargando: {$fuente['nombre']}... ";
    
    // Intentar descargar usando google-webfonts-helper API
    $fontSlug = strtolower(str_replace(' ', '-', $fontName));
    
    // Descargar info de la fuente
    $apiUrl = "https://gwfh.mranftl.com/api/fonts/{$fontSlug}";
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0\r\n",
            'timeout' => 30
        ]
    ]);
    
    $fontInfo = @file_get_contents($apiUrl, false, $context);
    
    if ($fontInfo) {
        $data = json_decode($fontInfo, true);
        
        if ($data && isset($data['variants'])) {
            // Buscar variant regular (400)
            $regularVariant = null;
            foreach ($data['variants'] as $variant) {
                if ($variant['fontWeight'] == '400' && $variant['fontStyle'] == 'normal') {
                    $regularVariant = $variant;
                    break;
                }
            }
            
            // Si no hay regular, usar el primer variant
            if (!$regularVariant && !empty($data['variants'])) {
                $regularVariant = $data['variants'][0];
            }
            
            if ($regularVariant && isset($regularVariant['ttf'])) {
                // Descargar el archivo TTF
                $ttfUrl = $regularVariant['ttf'];
                $ttfData = @file_get_contents($ttfUrl, false, $context);
                
                if ($ttfData) {
                    file_put_contents($localPath, $ttfData);
                    
                    // Actualizar la BD con el archivo local
                    $stmt2 = $pdo->prepare("UPDATE fuentes_personalizadas SET archivo = ? WHERE id = ?");
                    $stmt2->execute([$localFileName, $fuente['id']]);
                    
                    echo "✓ Descargada\n";
                    $descargadas++;
                    continue;
                }
            }
        }
    }
    
    echo "✗ Error\n";
    $errores++;
}

echo "\n=== Resumen ===\n";
echo "Fuentes descargadas: $descargadas\n";
echo "Errores: $errores\n";

// Listar archivos en el directorio
echo "\n=== Archivos en assets/fonts/ ===\n";
$files = glob($fontsDir . '*.{ttf,otf,woff,woff2}', GLOB_BRACE);
foreach ($files as $file) {
    $size = round(filesize($file) / 1024, 2);
    echo "- " . basename($file) . " ({$size} KB)\n";
}
