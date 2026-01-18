<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use Intervention\Image\ImageManagerStatic as Image;

// Configurar driver GD
Image::configure(['driver' => 'gd']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener configuración actual
    $stmt = $pdo->query("SELECT * FROM configuracion_plantillas WHERE activa = 1 LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        throw new Exception("No hay plantilla activa configurada");
    }
    
    $templatePath = dirname(__DIR__) . '/assets/templates/' . $config['archivo_plantilla'];
    
    if (!file_exists($templatePath)) {
        throw new Exception("Plantilla no encontrada");
    }
    
    // Cargar plantilla
    $img = Image::make($templatePath);
    
    // Agregar texto de ejemplo
    $nombreEjemplo = $_POST['nombre'] ?? 'NOMBRE DE EJEMPLO';
    
    // Buscar fuente
    $fontPath = null;
    if (!empty($config['fuente_nombre'])) {
        $fontBasePath = dirname(__DIR__) . '/assets/fonts/' . $config['fuente_nombre'];
        if (file_exists($fontBasePath . '.ttf')) {
            $fontPath = $fontBasePath . '.ttf';
        } elseif (file_exists($fontBasePath . '.otf')) {
            $fontPath = $fontBasePath . '.otf';
        }
    }
    
    // Agregar texto
    $img->text($nombreEjemplo, 
        $config['posicion_nombre_x'], 
        $config['posicion_nombre_y'], 
        function($font) use ($fontPath, $config) {
            if ($fontPath && file_exists($fontPath)) {
                try {
                    $font->file($fontPath);
                } catch (Exception $e) {
                    // Usar fuente por defecto si falla
                }
            }
            $font->size($config['tamanio_fuente']);
            $font->color($config['color_texto']);
            $font->align('left');  // Alineación desde el inicio (izquierda)
            $font->valign('top');   // Alineación desde arriba
        }
    );
    
    // Agregar marcador QR (simulado)
    $qrSize = 150;
    $qrX = $config['posicion_qr_x'];
    $qrY = $config['posicion_qr_y'];
    
    // Dibujar un rectángulo verde para simular el QR
    // Las coordenadas guardadas son esquina superior izquierda del QR
    $qrTopLeftX = $qrX - $qrSize/2;
    $qrTopLeftY = $qrY - $qrSize/2;
    
    $img->rectangle($qrTopLeftX, $qrTopLeftY, 
                    $qrTopLeftX + $qrSize, $qrTopLeftY + $qrSize, 
                    function($draw) {
                        $draw->background('rgba(46, 204, 113, 0.5)');
                        $draw->border(3, '#27ae60');
                    });
    
    // Texto "QR" en el centro del rectángulo
    $img->text('QR', $qrX, $qrY, function($font) {
        $font->size(40);
        $font->color('#27ae60');
        $font->align('center');
        $font->valign('middle');
    });
    
    // Guardar vista previa temporal
    $previewFilename = 'preview_' . time() . '.png';
    $previewPath = dirname(__DIR__) . '/uploads/' . $previewFilename;
    $img->save($previewPath, 85);
    
    // Limpiar vistas previas antiguas (más de 1 hora)
    $uploadsDir = dirname(__DIR__) . '/uploads/';
    $files = glob($uploadsDir . 'preview_*.png');
    $now = time();
    foreach ($files as $file) {
        if ($now - filemtime($file) > 3600) { // 1 hora
            @unlink($file);
        }
    }
    
    echo json_encode([
        'success' => true,
        'preview' => '../uploads/' . $previewFilename
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en preview.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
