<?php
/**
 * Script para agregar fuentes preestablecidas usando Google Fonts
 * Estas fuentes están disponibles en línea y no requieren archivos locales
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

echo "=== Agregando fuentes preestablecidas ===\n";

// Fuentes preestablecidas con Google Fonts
$fuentesPreestablecidas = [
    // Sans Serif
    ['nombre' => 'Roboto', 'nombre_archivo' => 'google:Roboto', 'archivo' => 'google:Roboto', 'tipo' => 'ttf', 'categoria' => 'sans-serif', 'es_sistema' => 1],
    ['nombre' => 'Open Sans', 'nombre_archivo' => 'google:Open+Sans', 'archivo' => 'google:Open+Sans', 'tipo' => 'ttf', 'categoria' => 'sans-serif', 'es_sistema' => 1],
    ['nombre' => 'Montserrat', 'nombre_archivo' => 'google:Montserrat', 'archivo' => 'google:Montserrat', 'tipo' => 'ttf', 'categoria' => 'sans-serif', 'es_sistema' => 1],
    ['nombre' => 'Lato', 'nombre_archivo' => 'google:Lato', 'archivo' => 'google:Lato', 'tipo' => 'ttf', 'categoria' => 'sans-serif', 'es_sistema' => 1],
    ['nombre' => 'Poppins', 'nombre_archivo' => 'google:Poppins', 'archivo' => 'google:Poppins', 'tipo' => 'ttf', 'categoria' => 'sans-serif', 'es_sistema' => 1],
    
    // Serif
    ['nombre' => 'Playfair Display', 'nombre_archivo' => 'google:Playfair+Display', 'archivo' => 'google:Playfair+Display', 'tipo' => 'ttf', 'categoria' => 'serif', 'es_sistema' => 1],
    ['nombre' => 'Merriweather', 'nombre_archivo' => 'google:Merriweather', 'archivo' => 'google:Merriweather', 'tipo' => 'ttf', 'categoria' => 'serif', 'es_sistema' => 1],
    ['nombre' => 'Libre Baskerville', 'nombre_archivo' => 'google:Libre+Baskerville', 'archivo' => 'google:Libre+Baskerville', 'tipo' => 'ttf', 'categoria' => 'serif', 'es_sistema' => 1],
    ['nombre' => 'PT Serif', 'nombre_archivo' => 'google:PT+Serif', 'archivo' => 'google:PT+Serif', 'tipo' => 'ttf', 'categoria' => 'serif', 'es_sistema' => 1],
    ['nombre' => 'Lora', 'nombre_archivo' => 'google:Lora', 'archivo' => 'google:Lora', 'tipo' => 'ttf', 'categoria' => 'serif', 'es_sistema' => 1],
    
    // Display
    ['nombre' => 'Oswald', 'nombre_archivo' => 'google:Oswald', 'archivo' => 'google:Oswald', 'tipo' => 'ttf', 'categoria' => 'display', 'es_sistema' => 1],
    ['nombre' => 'Bebas Neue', 'nombre_archivo' => 'google:Bebas+Neue', 'archivo' => 'google:Bebas+Neue', 'tipo' => 'ttf', 'categoria' => 'display', 'es_sistema' => 1],
    ['nombre' => 'Abril Fatface', 'nombre_archivo' => 'google:Abril+Fatface', 'archivo' => 'google:Abril+Fatface', 'tipo' => 'ttf', 'categoria' => 'display', 'es_sistema' => 1],
    ['nombre' => 'Cinzel', 'nombre_archivo' => 'google:Cinzel', 'archivo' => 'google:Cinzel', 'tipo' => 'ttf', 'categoria' => 'display', 'es_sistema' => 1],
    ['nombre' => 'Righteous', 'nombre_archivo' => 'google:Righteous', 'archivo' => 'google:Righteous', 'tipo' => 'ttf', 'categoria' => 'display', 'es_sistema' => 1],
    
    // Handwriting (Manuscritas)
    ['nombre' => 'Dancing Script', 'nombre_archivo' => 'google:Dancing+Script', 'archivo' => 'google:Dancing+Script', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    ['nombre' => 'Pacifico', 'nombre_archivo' => 'google:Pacifico', 'archivo' => 'google:Pacifico', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    ['nombre' => 'Great Vibes', 'nombre_archivo' => 'google:Great+Vibes', 'archivo' => 'google:Great+Vibes', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    ['nombre' => 'Sacramento', 'nombre_archivo' => 'google:Sacramento', 'archivo' => 'google:Sacramento', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    ['nombre' => 'Satisfy', 'nombre_archivo' => 'google:Satisfy', 'archivo' => 'google:Satisfy', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    ['nombre' => 'Allura', 'nombre_archivo' => 'google:Allura', 'archivo' => 'google:Allura', 'tipo' => 'ttf', 'categoria' => 'handwriting', 'es_sistema' => 1],
    
    // Monospace
    ['nombre' => 'Roboto Mono', 'nombre_archivo' => 'google:Roboto+Mono', 'archivo' => 'google:Roboto+Mono', 'tipo' => 'ttf', 'categoria' => 'monospace', 'es_sistema' => 1],
    ['nombre' => 'Source Code Pro', 'nombre_archivo' => 'google:Source+Code+Pro', 'archivo' => 'google:Source+Code+Pro', 'tipo' => 'ttf', 'categoria' => 'monospace', 'es_sistema' => 1],
];

$stmt = $pdo->prepare("
    INSERT INTO fuentes_personalizadas (nombre, nombre_archivo, archivo, tipo, categoria, es_sistema, activo) 
    VALUES (?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), categoria = VALUES(categoria)
");

$agregadas = 0;
foreach ($fuentesPreestablecidas as $fuente) {
    try {
        $stmt->execute([
            $fuente['nombre'],
            $fuente['nombre_archivo'],
            $fuente['archivo'],
            $fuente['tipo'],
            $fuente['categoria'],
            $fuente['es_sistema']
        ]);
        $agregadas++;
        echo "✓ Agregada: {$fuente['nombre']} ({$fuente['categoria']})\n";
    } catch (PDOException $e) {
        echo "✗ Error al agregar {$fuente['nombre']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Fuentes agregadas/actualizadas: $agregadas\n";

// Verificar total
$stmt = $pdo->query("SELECT COUNT(*) FROM fuentes_personalizadas");
echo "Total de fuentes en BD: " . $stmt->fetchColumn() . "\n";
