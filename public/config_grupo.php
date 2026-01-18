<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

// Obtener fuentes de la base de datos
$pdo = getConnection();
$fontsList = [];
try {
    $stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE activo = 1 ORDER BY categoria ASC, es_sistema DESC, nombre ASC");
    $fontsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla no existe, usar fuentes predeterminadas
    $fontsList = [
        ['nombre' => 'Roboto', 'nombre_archivo' => 'Roboto-Regular', 'archivo' => 'Roboto-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Open Sans', 'nombre_archivo' => 'OpenSans', 'archivo' => 'OpenSans.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Lato', 'nombre_archivo' => 'Lato-Regular', 'archivo' => 'Lato-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Montserrat', 'nombre_archivo' => 'Montserrat', 'archivo' => 'Montserrat.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Poppins', 'nombre_archivo' => 'Poppins-Regular', 'archivo' => 'Poppins-Regular.ttf', 'tipo' => 'ttf', 'categoria' => 'sans-serif'],
        ['nombre' => 'Playfair Display', 'nombre_archivo' => 'PlayfairDisplay', 'archivo' => 'PlayfairDisplay.ttf', 'tipo' => 'ttf', 'categoria' => 'serif'],
        ['nombre' => 'Motterdam', 'nombre_archivo' => 'Motterdam', 'archivo' => 'Motterdam.ttf', 'tipo' => 'ttf', 'categoria' => 'handwriting'],
    ];
}

// Función para formatear fecha con meses en español
function formatearFechaEjemplo($formato) {
    $mesesEspanol = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    
    $fecha = date($formato);
    return str_replace(array_keys($mesesEspanol), array_values($mesesEspanol), $fecha);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Plantilla del Grupo - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Cargar fuentes personalizadas dinámicamente */
        <?php 
        // Obtener la ruta base del proyecto
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $fontsPath = str_replace('/public', '', $basePath) . '/assets/fonts/';
        foreach ($fontsList as $index => $font): 
            $formato = $font['tipo'] === 'ttf' ? 'truetype' : ($font['tipo'] === 'otf' ? 'opentype' : $font['tipo']);
            $fontId = $font['id'] ?? $index + 1;
        ?>
        @font-face {
            font-family: 'CustomFont<?= $fontId ?>';
            src: url('<?= $fontsPath . htmlspecialchars($font['archivo']) ?>') format('<?= $formato ?>');
            font-display: swap;
        }
        <?php endforeach; ?>
        
        /* Estilos para selectores de fuente */
        .font-select {
            font-size: 16px;
        }
        <?php foreach ($fontsList as $index => $font): 
            $fontId = $font['id'] ?? $index + 1;
        ?>
        .font-select option[value="<?= htmlspecialchars($font['nombre_archivo']) ?>"] { font-family: 'CustomFont<?= $fontId ?>', sans-serif !important; }
        <?php endforeach; ?>
        
        /* Slider de plantillas */
        .templates-slider-container {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .templates-slider-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .templates-slider-header h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .templates-slider-header .template-count {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: normal;
        }
        
        .templates-slider-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .slider-arrow {
            width: 36px;
            height: 36px;
            border: none;
            background: #3498db;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .slider-arrow:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        .slider-arrow:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        
        .templates-slider {
            display: flex;
            gap: 12px;
            overflow: hidden;
            padding: 8px 4px;
            flex: 1;
            justify-content: center;
        }
        
        .template-item {
            flex: 0 0 auto;
            width: 180px;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            border: 3px solid transparent;
            transition: all 0.2s ease;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            display: none;
        }
        
        .template-item.visible {
            display: block;
        }
        
        .template-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #3498db;
        }
        
        .template-item.active {
            border-color: #27ae60;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .template-item.active::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 8px;
            right: 8px;
            background: #27ae60;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        .template-item .template-thumb {
            width: 100%;
            height: 110px;
            object-fit: cover;
            display: block;
        }
        
        .template-item .template-info {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .template-item:hover .template-info {
            opacity: 1;
        }
        
        .template-item .template-name {
            font-size: 12px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
            text-align: center;
            padding: 0 10px;
        }
        
        .template-item .template-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        
        .template-item .template-actions button {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            padding: 10px 14px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .template-item .template-actions .btn-select {
            color: #2ecc71;
        }
        
        .template-item .template-actions .btn-select:hover {
            background: rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }
        
        .template-item .template-actions .btn-delete {
            color: #e74c3c;
        }
        
        .template-item .template-actions .btn-delete:hover {
            background: rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        
        /* Botón de agregar plantilla en el slider */
        .template-add-btn {
            flex: 0 0 auto;
            width: 180px;
            height: 110px;
            border: 2px dashed #bdc3c7;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            color: #7f8c8d;
        }
        
        .template-add-btn:hover {
            border-color: #3498db;
            color: #3498db;
            background: #e8f4f8;
        }
        
        .template-add-btn i {
            font-size: 28px;
        }
        
        .template-add-btn span {
            font-size: 12px;
            font-weight: 500;
        }
        
        .templates-empty {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
        
        .templates-empty i {
            font-size: 40px;
            margin-bottom: 10px;
            color: #bdc3c7;
        }
        
        .templates-empty p {
            margin: 0;
            font-size: 13px;
        }
        
        /* Vista previa de firma */
        .firma-preview-container {
            margin-top: 10px;
        }
        
        .firma-preview-container:empty {
            display: none;
        }
        
        .firma-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        .firma-preview img {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
            background: repeating-conic-gradient(#ddd 0% 25%, white 0% 50%) 50% / 10px 10px;
            border-radius: 4px;
            padding: 5px;
        }
        
        .firma-preview .firma-info {
            flex: 1;
        }
        
        .firma-preview .firma-info .firma-filename {
            font-size: 13px;
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 4px;
            word-break: break-all;
        }
        
        .firma-preview .firma-info small {
            color: #7f8c8d;
            font-size: 11px;
        }
        
        .firma-preview .btn-remove-firma {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s;
        }
        
        .firma-preview .btn-remove-firma:hover {
            background: #c0392b;
        }
        
        .main-content {
            display: block !important;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
            margin-top: 20px;
            align-items: start;
        }
        
        .config-grid > div:last-child {
            position: sticky;
            top: 20px;
            width: 100%;
        }
        
        .config-grid > div:last-child .card {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        @media (max-width: 968px) {
            .config-grid {
                grid-template-columns: 1fr;
            }
            
            .config-grid > div:last-child {
                position: static;
            }
            
            .config-grid > div:last-child .card {
                max-height: none;
            }
        }
        
        .canvas-container {
            position: relative;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        #canvas {
            display: block;
            max-width: 100%;
            height: auto;
            cursor: crosshair;
        }
        
        .draggable {
            position: absolute;
            cursor: move;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            user-select: none;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Indicador de posición exacta */
        .draggable::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: #e74c3c;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0,0,0,0.5);
            z-index: 20;
        }
        
        /* Para nombre: punto en esquina superior izquierda (donde inicia el texto) */
        .draggable.nombre-marker::before {
            top: -4px;
            left: -4px;
        }
        
        /* Para QR y Firma: punto en el centro */
        .draggable.qr-marker::before,
        .draggable.firma-marker::before {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .draggable.nombre-marker {
            background: rgba(52, 152, 219, 0.9);
            color: white;
            min-width: 60px;
            white-space: nowrap;
        }
        
        .draggable.qr-marker {
            background: rgba(46, 204, 113, 0.9);
            color: white;
            min-width: 40px;
            min-height: 40px;
        }
        
        .draggable.firma-marker {
            background: rgba(155, 89, 182, 0.9);
            color: white;
            min-width: 50px;
            height: 40px;
        }
        
        .draggable.razon-marker {
            background: rgba(230, 126, 34, 0.85);
            color: white;
            min-width: 100px;
            min-height: 30px;
            padding: 4px 6px;
            padding-right: 14px; /* Espacio para el handle */
            white-space: pre-wrap;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            text-align: justify; /* Texto justificado */
            line-height: 1.3;
            font-size: 10px;
            overflow: hidden;
            resize: none;
            position: absolute;
            display: block !important;
            z-index: 15;
            box-sizing: border-box;
        }
        
        .draggable.razon-marker .razon-text {
            display: block;
            width: 100%;
            white-space: pre-wrap;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        
        /* Handle de redimensionamiento para razón */
        .draggable.razon-marker .resize-handle {
            position: absolute;
            right: 2px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 24px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e67e22;
            border-radius: 2px;
            cursor: ew-resize;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        
        .draggable.razon-marker .resize-handle:hover {
            opacity: 1;
            background: #e67e22;
        }
        
        .draggable.razon-marker .resize-handle::before {
            content: '⋮';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 8px;
            color: #e67e22;
        }
        
        .draggable.razon-marker .resize-handle:hover::before {
            color: #fff;
        }
        
        /* Para razón: punto en esquina superior izquierda (donde inicia el texto) */
        .draggable.razon-marker::before {
            top: -4px;
            left: -4px;
        }
        
        .draggable.fecha-marker {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            min-width: 60px;
            white-space: nowrap;
        }
        
        /* Para fecha: punto en esquina superior izquierda (donde inicia el texto) */
        .draggable.fecha-marker::before {
            top: -4px;
            left: -4px;
        }
        
        .draggable.active {
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            transform: scale(1.05);
        }
        
        .coordinates-info {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .coordinates-info {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .coord-item {
            text-align: center;
        }
        
        .coord-label {
            font-weight: bold;
            color: #7f8c8d;
            display: block;
            margin-bottom: 5px;
        }
        
        .coord-value {
            color: #2c3e50;
            font-family: monospace;
        }
        
        .checkbox-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f8f9fa;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            font-size: 13px;
        }
        
        .checkbox-item:hover {
            background: #e9ecef;
        }
        
        .checkbox-item:has(input:checked) {
            background: #e8f4f8;
            border-color: #3498db;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: normal !important;
            white-space: nowrap;
        }
        
        /* Tabs para propiedades de variables */
        .variable-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 0;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .variable-tab {
            padding: 10px 15px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #7f8c8d;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .variable-tab:hover {
            background: #e9ecef;
            color: #2c3e50;
        }
        
        .variable-tab.active {
            background: white;
            color: #3498db;
            border-color: #ecf0f1;
            border-bottom: 2px solid white;
            margin-bottom: -2px;
        }
        
        .variable-tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .variable-tab-content.active {
            display: block;
        }
        
        /* Ayuda de variables */
        .variables-help {
            margin-top: 15px;
            padding: 12px;
            background: #f0f7ff;
            border-radius: 8px;
            border-left: 3px solid #3498db;
        }
        
        .variables-help strong {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .variables-help i {
            color: #3498db;
        }
        
        .variables-list {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 6px 12px;
            font-size: 12px;
        }
        
        .variables-list code {
            background: #e8f4f8;
            padding: 2px 8px;
            border-radius: 4px;
            color: #2980b9;
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        .variables-list span {
            color: #7f8c8d;
        }
        
        /* Upload inline para plantilla */
        .template-upload-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .template-upload-inline:hover {
            border-color: #3498db;
            background: #e8f4f8;
        }
        
        .template-upload-inline .upload-icon {
            font-size: 24px;
        }
        
        .template-upload-inline .upload-text {
            flex: 1;
        }
        
        .template-upload-inline .upload-text p {
            margin: 0;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .template-upload-inline .upload-text small {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .template-upload-inline .current-template {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            background: #e8f4f8;
            border-radius: 5px;
            font-size: 12px;
            color: #2c3e50;
        }
        
        .template-upload-inline .current-template .remove-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            padding: 2px;
            font-size: 14px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            font-family: inherit;
            min-height: 100px;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .color-preview {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .color-preview input[type="color"] {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        /* Selector de formato de nombre */
        .formato-nombre-selector {
            display: flex;
            gap: 8px;
        }
        
        .formato-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 12px 8px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .formato-btn i {
            font-size: 18px;
            color: #7f8c8d;
        }
        
        .formato-btn span {
            font-size: 14px;
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .formato-btn:hover {
            border-color: #bdc3c7;
            background: #ecf0f1;
        }
        
        .formato-btn.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }
        
        .formato-btn.active i,
        .formato-btn.active span {
            color: #667eea;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .preview-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-group .btn-primary {
            grid-column: 1 / -1;
        }
        
        .btn-group button {
            flex: 1;
        }
        
        .grupo-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .grupo-icon-large {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .grupo-info h2 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .grupo-info p {
            margin: 0;
            color: #7f8c8d;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-area:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .file-upload-area.dragover {
            border-color: #2ecc71;
            background: #e8f8f5;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .current-file {
            background: #e8f4f8;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Preview del sticker personalizado */
        .sticker-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e9ecef;
        }
        
        .sticker-preview img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
            background: #fff;
            padding: 5px;
            border: 1px solid #ddd;
        }
        
        .sticker-info {
            flex: 1;
        }
        
        .sticker-filename {
            font-weight: 500;
            color: #333;
            word-break: break-all;
        }
        
        .sticker-info small {
            color: #6c757d;
        }
        
        .btn-remove-sticker {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-remove-sticker:hover {
            background: #c0392b;
        }
        
        .sticker-preview-container {
            min-height: 0;
        }

        /* Modal de previsualización */
        .preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .preview-modal.active {
            display: flex;
        }
        
        .preview-modal-content {
            background: white;
            border-radius: 15px;
            max-width: 95%;
            max-height: 95%;
            overflow: auto;
            position: relative;
        }
        
        .preview-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            border-radius: 15px 15px 0 0;
            z-index: 10;
        }
        
        .preview-modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .preview-modal-close {
            background: #e74c3c;
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-modal-body {
            padding: 20px;
            text-align: center;
        }
        
        .preview-modal-body img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .preview-loading {
            padding: 60px;
            text-align: center;
        }
        
        .preview-loading i {
            font-size: 48px;
            color: #3498db;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-preview {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }
        
        .btn-preview:hover {
            background: linear-gradient(135deg, #8e44ad, #7d3c98);
        }
        
        /* Estilos para el marcador de destacado */
        .draggable.destacado-marker {
            background: transparent;
            width: 60px;
            height: 60px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed rgba(241, 196, 15, 0.7);
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .draggable.destacado-marker img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Grid de stickers para selección */
        .stickers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .sticker-option {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.2s;
            background: white;
        }
        
        .sticker-option:hover {
            border-color: #f1c40f;
            background: #fffef0;
        }
        
        .sticker-option input[type="radio"] {
            display: none;
        }
        
        .sticker-option input[type="radio"]:checked + img {
            transform: scale(1.1);
        }
        
        .sticker-option:has(input:checked) {
            border-color: #f1c40f;
            background: #fffceb;
            box-shadow: 0 2px 8px rgba(241, 196, 15, 0.3);
        }
        
        .sticker-option img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            transition: transform 0.2s;
        }
        
        .destacado-info {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            background: #e8f4fd;
            border-radius: 8px;
            border: 1px solid #bee5eb;
            margin-bottom: 15px;
        }
        
        .destacado-info i {
            color: #17a2b8;
            font-size: 18px;
            margin-top: 2px;
        }
        
        .destacado-info p {
            margin: 0;
            color: #0c5460;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .sticker-preview-container {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .sticker-preview-container img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        
        /* Coordenadas para destacado */
        #coordDestacadoContainer {
            display: none;
        }
        
        #coordDestacadoContainer.visible {
            display: flex;
        }
    </style>
</head>
<body>
    <script>
        // Detectar el basePath correcto
        const basePath = window.location.pathname.includes('/public/') ? '../' : '/cce-certificados/';
        // Actualizar logo con basePath correcto
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.logo');
            if (logo) {
                logo.src = basePath + 'assets/logos/logo-cce.png';
            }
            
            // Mapeo de valores a nombres de fuente CSS (generado dinámicamente)
            const fontMap = {
                <?php foreach ($fontsList as $index => $font): 
                    $fontId = $font['id'] ?? $index + 1;
                ?>
                '<?= htmlspecialchars($font['nombre_archivo']) ?>': 'CustomFont<?= $fontId ?>',
                <?php endforeach; ?>
            };
            
            // Función para actualizar la fuente del select
            function updateSelectFont(select) {
                const fontFamily = fontMap[select.value];
                if (fontFamily) {
                    select.style.fontFamily = `'${fontFamily}', sans-serif`;
                }
            }
            
            // Aplicar a todos los selectores de fuente
            document.querySelectorAll('.font-select').forEach(select => {
                updateSelectFont(select);
                select.addEventListener('change', () => updateSelectFont(select));
            });
        });
    </script>
    <div class="container">
        <header>
            <img src="" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
            <h1>Configurar Plantilla del Grupo</h1>
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Inicio</a>
            </div>
        </header>

        <div class="main-content">
            <div class="grupo-header" id="grupoHeader">
                <div class="grupo-icon-large" id="grupoIcon">📁</div>
                <div class="grupo-info">
                    <h2 id="grupoNombre">Cargando...</h2>
                    <p id="grupoDescripcion"></p>
                </div>
            </div>

            <form id="configForm">
                <input type="hidden" id="grupo_id" name="grupo_id">
                <input type="hidden" id="variables_habilitadas" name="variables_habilitadas">
                <input type="hidden" id="posicion_nombre_x" name="posicion_nombre_x" value="400">
                <input type="hidden" id="posicion_nombre_y" name="posicion_nombre_y" value="300">
                <input type="hidden" id="posicion_razon_x" name="posicion_razon_x" value="400">
                <input type="hidden" id="posicion_razon_y" name="posicion_razon_y" value="360">
                <input type="hidden" id="ancho_razon" name="ancho_razon" value="600">
                <input type="hidden" id="lineas_razon" name="lineas_razon" value="1">
                <input type="hidden" id="posicion_qr_x" name="posicion_qr_x" value="920">
                <input type="hidden" id="posicion_qr_y" name="posicion_qr_y" value="419">
                <input type="hidden" id="posicion_firma_x" name="posicion_firma_x" value="800">
                <input type="hidden" id="posicion_firma_y" name="posicion_firma_y" value="850">
                <input type="hidden" id="posicion_fecha_x" name="posicion_fecha_x" value="400">
                <input type="hidden" id="posicion_fecha_y" name="posicion_fecha_y" value="420">
                <input type="hidden" id="posicion_destacado_x" name="posicion_destacado_x" value="50">
                <input type="hidden" id="posicion_destacado_y" name="posicion_destacado_y" value="50">
                
                <div class="config-grid">
                    <!-- Columna 1: Lienzo y Configuración -->
                    <div>
                        <div class="card">
                            <h3><i class="fas fa-palette"></i> Lienzo de Posicionamiento</h3>
                            
                            <!-- Slider de plantillas -->
                            <div class="templates-slider-container">
                                <div class="templates-slider-header">
                                    <h4>
                                        <i class="fas fa-images"></i> Plantillas del Grupo
                                        <span class="template-count" id="templateCount">0/6</span>
                                    </h4>
                                </div>
                                <div class="templates-slider-wrapper">
                                    <button type="button" class="slider-arrow" id="sliderPrev" disabled>
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="templates-slider" id="templatesSlider">
                                        <!-- Las plantillas se cargarán dinámicamente -->
                                        <div class="templates-empty" id="templatesEmpty">
                                            <i class="fas fa-image"></i>
                                            <p>No hay plantillas. Haz clic en + para agregar.</p>
                                        </div>
                                        <div class="template-add-btn" id="templateAddBtn">
                                            <i class="fas fa-plus"></i>
                                            <span>Agregar</span>
                                        </div>
                                    </div>
                                    <button type="button" class="slider-arrow" id="sliderNext" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="file" id="plantillaUpload" name="plantilla" accept="image/*" style="display: none;">
                            
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_nombre" value="nombre" checked>
                                    <label for="var_nombre"><i class="fas fa-user"></i> Nombre</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_razon" value="razon" checked>
                                    <label for="var_razon"><i class="fas fa-file-alt"></i> Razón</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_fecha" value="fecha" checked>
                                    <label for="var_fecha"><i class="fas fa-calendar"></i> Fecha</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_qr" value="qr" checked>
                                    <label for="var_qr"><i class="fas fa-qrcode"></i> QR</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_firma" value="firma" checked>
                                    <label for="var_firma"><i class="fas fa-signature"></i> Firma</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="var_destacado" value="destacado">
                                    <label for="var_destacado"><i class="fas fa-star" style="color: #f1c40f;"></i> Destacado</label>
                                </div>
                            </div>
                            
                            <div class="canvas-container" id="canvasContainer">
                                <img id="canvas" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1600' height='1131'%3E%3Crect width='1600' height='1131' fill='%23f8f9fa'/%3E%3C/svg%3E" alt="Plantilla">
                                <div class="draggable nombre-marker" id="nombreMarker" data-type="nombre" style="left: 100px; top: 80px;">
                                    <i class="fas fa-user"></i> NOMBRE
                                </div>
                                <div class="draggable razon-marker" id="razonMarker" data-type="razon" style="left: 100px; top: 120px; width: 300px; display: flex !important;">
                                    <span class="razon-text" id="razonMarkerText">Por su destacada participación...</span>
                                    <div class="resize-handle" id="razonResizeHandle"></div>
                                </div>
                                <div class="draggable fecha-marker" id="fechaMarker" data-type="fecha" style="left: 100px; top: 160px;">
                                    <i class="fas fa-calendar"></i> FECHA
                                </div>
                                <div class="draggable qr-marker" id="qrMarker" data-type="qr" style="left: 300px; top: 150px;">
                                    <i class="fas fa-qrcode"></i> QR
                                </div>
                                <div class="draggable firma-marker" id="firmaMarker" data-type="firma" style="left: 250px; top: 280px;">
                                    <i class="fas fa-signature"></i> FIRMA
                                </div>
                                <div class="draggable destacado-marker" id="destacadoMarker" data-type="destacado" style="left: 50px; top: 50px; width: 60px; height: 60px; display: none;">
                                    <img id="destacadoMarkerImg" src="/cce-certificados/assets/stickers/estrella.png" alt="Destacado">
                                </div>
                            </div>
                            
                            <div class="coordinates-info">
                                <div class="coord-item" id="coordNombreContainer">
                                    <span class="coord-label"><i class="fas fa-user"></i> Nombre</span>
                                    <span class="coord-value" id="coordNombre">X: 400, Y: 300</span>
                                </div>
                                <div class="coord-item" id="coordRazonContainer">
                                    <span class="coord-label"><i class="fas fa-file-alt"></i> Razón</span>
                                    <span class="coord-value" id="coordRazon">X: 400, Y: 360</span>
                                </div>
                                <div class="coord-item" id="coordFechaContainer">
                                    <span class="coord-label"><i class="fas fa-calendar"></i> Fecha</span>
                                    <span class="coord-value" id="coordFecha">X: 400, Y: 420</span>
                                </div>
                                <div class="coord-item" id="coordQrContainer">
                                    <span class="coord-label"><i class="fas fa-qrcode"></i> QR</span>
                                    <span class="coord-value" id="coordQr">X: 920, Y: 419</span>
                                </div>
                                <div class="coord-item" id="coordFirmaContainer">
                                    <span class="coord-label"><i class="fas fa-signature"></i> Firma</span>
                                    <span class="coord-value" id="coordFirma">X: 800, Y: 850</span>
                                </div>
                                <div class="coord-item" id="coordDestacadoContainer">
                                    <span class="coord-label"><i class="fas fa-star" style="color: #f1c40f;"></i> Destacado</span>
                                    <span class="coord-value" id="coordDestacado">X: 50, Y: 50</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna 2: Configuración Detallada -->
                    <div>
                        <div class="card">
                            <h3><i class="fas fa-cog"></i> Propiedades de Variables</h3>
                            
                            <div class="variable-tabs">
                                <button type="button" class="variable-tab active" data-tab="nombre" onclick="switchTab('nombre')">
                                    <i class="fas fa-user"></i> Nombre
                                </button>
                                <button type="button" class="variable-tab" data-tab="razon" onclick="switchTab('razon')">
                                    <i class="fas fa-file-alt"></i> Razón
                                </button>
                                <button type="button" class="variable-tab" data-tab="fecha" onclick="switchTab('fecha')">
                                    <i class="fas fa-calendar"></i> Fecha
                                </button>
                                <button type="button" class="variable-tab" data-tab="qr" onclick="switchTab('qr')">
                                    <i class="fas fa-qrcode"></i> QR
                                </button>
                                <button type="button" class="variable-tab" data-tab="firma" onclick="switchTab('firma')">
                                    <i class="fas fa-signature"></i> Firma
                                </button>
                                <button type="button" class="variable-tab" data-tab="destacado" onclick="switchTab('destacado')">
                                    <i class="fas fa-star" style="color: #f1c40f;"></i> Destacado
                                </button>
                            </div>
                            
                            <!-- Tab: Nombre -->
                            <div class="variable-tab-content active" id="tab-nombre">
                                <div class="form-group">
                                    <label for="fuente_nombre">Fuente</label>
                                    <select id="fuente_nombre" name="fuente_nombre" class="font-select">
                                        <?php 
                                        $categoryLabels = ['sans-serif' => 'Sans Serif', 'serif' => 'Serif', 'display' => 'Display', 'handwriting' => 'Manuscritas', 'monospace' => 'Monoespaciadas'];
                                        $currentCategory = '';
                                        foreach ($fontsList as $index => $font):
                                            $fontId = $font['id'] ?? $index + 1;
                                            if ($font['categoria'] !== $currentCategory):
                                                if ($currentCategory !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . ($categoryLabels[$font['categoria']] ?? $font['categoria']) . '">';
                                                $currentCategory = $font['categoria'];
                                            endif;
                                        ?>
                                        <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: 'CustomFont<?= $fontId ?>', sans-serif; font-size: 16px;"><?= htmlspecialchars($font['nombre']) ?></option>
                                        <?php endforeach; 
                                        if ($currentCategory !== '') echo '</optgroup>';
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Formato del Nombre</label>
                                    <div class="formato-nombre-selector">
                                        <button type="button" class="formato-btn active" data-formato="mayusculas" onclick="setFormatoNombre('mayusculas')" title="MAYÚSCULAS">
                                            <i class="fas fa-font"></i>
                                            <span>ABC</span>
                                        </button>
                                        <button type="button" class="formato-btn" data-formato="capitalizado" onclick="setFormatoNombre('capitalizado')" title="Capitalizado">
                                            <i class="fas fa-text-height"></i>
                                            <span>Abc</span>
                                        </button>
                                        <button type="button" class="formato-btn" data-formato="minusculas" onclick="setFormatoNombre('minusculas')" title="minúsculas">
                                            <i class="fas fa-text-width"></i>
                                            <span>abc</span>
                                        </button>
                                    </div>
                                    <input type="hidden" id="formato_nombre" name="formato_nombre" value="mayusculas">
                                </div>
                                
                                <div class="form-group">
                                    <label for="tamanio_fuente">Tamaño de Fuente (px)</label>
                                    <input type="number" id="tamanio_fuente" name="tamanio_fuente" 
                                           value="48" min="20" max="100">
                                </div>
                                
                                <div class="form-group">
                                    <label for="color_texto">Color del Texto</label>
                                    <div class="color-preview">
                                        <input type="color" id="color_texto" name="color_texto" value="#000000">
                                        <input type="text" id="color_texto_hex" value="#000000" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: QR -->
                            <div class="variable-tab-content" id="tab-qr">
                                <div class="form-group">
                                    <label for="tamanio_qr">Tamaño del QR (px)</label>
                                    <input type="number" id="tamanio_qr" name="tamanio_qr" 
                                           value="200" min="50" max="400">
                                    <small>Ancho y alto del código QR</small>
                                </div>
                            </div>
                            
                            <!-- Tab: Firma -->
                            <div class="variable-tab-content" id="tab-firma">
                                <div class="form-group">
                                    <label for="tamanio_firma">Tamaño de Firma (ancho en px)</label>
                                    <input type="number" id="tamanio_firma" name="tamanio_firma" 
                                           value="150" min="50" max="400">
                                    <small>El alto se ajustará proporcionalmente</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="firma_nombre">Nombre de quien firma</label>
                                    <input type="text" id="firma_nombre" name="firma_nombre" 
                                           placeholder="Ej: Dr. Juan Pérez">
                                </div>
                                
                                <div class="form-group">
                                    <label for="firma_cargo">Cargo</label>
                                    <input type="text" id="firma_cargo" name="firma_cargo" 
                                           placeholder="Ej: Director de la Casa de la Cultura">
                                </div>
                                
                                <div class="form-group">
                                    <label>Imagen de Firma (opcional)</label>
                                    <div class="file-upload-area" id="uploadFirmaArea">
                                        <div class="upload-icon"><i class="fas fa-signature"></i></div>
                                        <p>Subir imagen de firma</p>
                                        <small>Formato PNG transparente recomendado</small>
                                    </div>
                                    <input type="file" id="firma_imagen" name="firma_imagen" accept="image/*" style="display: none;">
                                    <div id="currentFirma" class="firma-preview-container"></div>
                                </div>
                            </div>
                            
                            <!-- Tab: Razón -->
                            <div class="variable-tab-content" id="tab-razon">
                                <div class="form-group">
                                    <label for="fuente_razon">Fuente</label>
                                    <select id="fuente_razon" name="fuente_razon" class="font-select">
                                        <?php 
                                        $currentCategory = '';
                                        foreach ($fontsList as $index => $font):
                                            $fontId = $font['id'] ?? $index + 1;
                                            if ($font['categoria'] !== $currentCategory):
                                                if ($currentCategory !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . ($categoryLabels[$font['categoria']] ?? $font['categoria']) . '">';
                                                $currentCategory = $font['categoria'];
                                            endif;
                                        ?>
                                        <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: 'CustomFont<?= $fontId ?>', sans-serif; font-size: 16px;"><?= htmlspecialchars($font['nombre']) ?></option>
                                        <?php endforeach; 
                                        if ($currentCategory !== '') echo '</optgroup>';
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="tamanio_razon">Tamaño de Fuente (px)</label>
                                    <input type="number" id="tamanio_razon" name="tamanio_razon" 
                                           value="24" min="10" max="60">
                                </div>
                                <div class="form-group">
                                    <label for="ancho_razon_input">Ancho del área de texto (px)</label>
                                    <input type="number" id="ancho_razon_input" name="ancho_razon_input" 
                                           value="600" min="200" max="1400">
                                    <small>Define dónde se produce el salto de línea. También puedes ajustarlo arrastrando el borde derecho del marcador en el lienzo.</small>
                                </div>
                                <div class="form-group">
                                    <label for="alineacion_razon">Alineación del texto</label>
                                    <select id="alineacion_razon" name="alineacion_razon" onchange="updateRazonMarkerAlignment()">
                                        <option value="left">Izquierda</option>
                                        <option value="center">Centro</option>
                                        <option value="right">Derecha</option>
                                        <option value="justified" selected>Justificado</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="color_razon">Color del Texto</label>
                                    <div class="color-preview">
                                        <input type="color" id="color_razon" name="color_razon" value="#333333">
                                        <input type="text" id="color_razon_hex" value="#333333" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="razon_defecto">Texto predeterminado</label>
                                    <textarea id="razon_defecto" name="razon_defecto" 
                                              style="min-height: 120px;" oninput="updateRazonMarkerText()">Por su destacada participación en el {grupo} de {categoria}.</textarea>
                                    <small>Este texto aparecerá automáticamente al generar certificados. El texto se muestra en el lienzo para previsualizar su posición.</small>
                                </div>
                                <div class="variables-help">
                                    <strong><i class="fas fa-info-circle"></i> Variables disponibles:</strong>
                                    <div class="variables-list">
                                        <code>{grupo}</code> <span>Nombre del grupo</span>
                                        <code>{categoria}</code> <span>Nombre de la categoría</span>
                                        <code>{nombre}</code> <span>Nombre del participante</span>
                                        <code>{fecha}</code> <span>Fecha del certificado</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Fecha -->
                            <div class="variable-tab-content" id="tab-fecha">
                                <div class="form-group">
                                    <label for="fuente_fecha">Fuente</label>
                                    <select id="fuente_fecha" name="fuente_fecha" class="font-select">
                                        <?php 
                                        $currentCategory = '';
                                        foreach ($fontsList as $index => $font):
                                            $fontId = $font['id'] ?? $index + 1;
                                            if ($font['categoria'] !== $currentCategory):
                                                if ($currentCategory !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . ($categoryLabels[$font['categoria']] ?? $font['categoria']) . '">';
                                                $currentCategory = $font['categoria'];
                                            endif;
                                        ?>
                                        <option value="<?= htmlspecialchars($font['nombre_archivo']) ?>" style="font-family: 'CustomFont<?= $fontId ?>', sans-serif; font-size: 16px;"><?= htmlspecialchars($font['nombre']) ?></option>
                                        <?php endforeach; 
                                        if ($currentCategory !== '') echo '</optgroup>';
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="tamanio_fecha">Tamaño de Fuente (px)</label>
                                    <input type="number" id="tamanio_fecha" name="tamanio_fecha" 
                                           value="20" min="10" max="60">
                                </div>
                                <div class="form-group">
                                    <label for="color_fecha">Color del Texto</label>
                                    <div class="color-preview">
                                        <input type="color" id="color_fecha" name="color_fecha" value="#333333">
                                        <input type="text" id="color_fecha_hex" value="#333333" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="formato_fecha">Formato de Fecha</label>
                                    <select id="formato_fecha" name="formato_fecha">
                                        <option value="d de F de Y"><?= formatearFechaEjemplo('d \d\e F \d\e Y') ?></option>
                                        <option value="d/m/Y"><?= date('d/m/Y') ?></option>
                                        <option value="d-m-Y"><?= date('d-m-Y') ?></option>
                                        <option value="F d, Y"><?= formatearFechaEjemplo('F d, Y') ?></option>
                                        <option value="d F Y"><?= formatearFechaEjemplo('d F Y') ?></option>
                                        <option value="Y-m-d"><?= date('Y-m-d') ?></option>
                                    </select>
                                    <small>Formato en que se mostrará la fecha en el certificado</small>
                                </div>
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="usar_fecha_especifica" name="usar_fecha_especifica" style="width: 18px; height: 18px;">
                                        Usar fecha específica
                                    </label>
                                    <small>Si no se marca, se usará la fecha actual</small>
                                </div>
                                <div class="form-group" id="fecha_especifica_group" style="display: none;">
                                    <label for="fecha_especifica">Fecha específica</label>
                                    <input type="date" id="fecha_especifica" name="fecha_especifica">
                                </div>
                            </div>
                            
                            <!-- Tab: Destacado -->
                            <div class="variable-tab-content" id="tab-destacado">
                                <div class="destacado-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>El sticker de "Destacado" aparecerá automáticamente en los certificados de estudiantes marcados como destacados.</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="destacado_tipo">Tipo de Sticker</label>
                                    <select id="destacado_tipo" name="destacado_tipo" onchange="toggleDestacadoOptions()">
                                        <option value="icono">Icono predeterminado</option>
                                        <option value="imagen">Imagen personalizada</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="destacado_icono_group">
                                    <label>Seleccionar Icono</label>
                                    <div class="stickers-grid" id="stickersGrid">
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="estrella" checked>
                                            <img src="/cce-certificados/assets/stickers/estrella.png" alt="Estrella" title="Estrella">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="medalla">
                                            <img src="/cce-certificados/assets/stickers/medalla.png" alt="Medalla" title="Medalla">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="trofeo">
                                            <img src="/cce-certificados/assets/stickers/trofeo.png" alt="Trofeo" title="Trofeo">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="corona">
                                            <img src="/cce-certificados/assets/stickers/corona.png" alt="Corona" title="Corona">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="laurel">
                                            <img src="/cce-certificados/assets/stickers/laurel.png" alt="Laurel" title="Laurel">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="sello">
                                            <img src="/cce-certificados/assets/stickers/sello.png" alt="Sello" title="Sello">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="insignia">
                                            <img src="/cce-certificados/assets/stickers/insignia.png" alt="Insignia" title="Insignia">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="cinta">
                                            <img src="/cce-certificados/assets/stickers/cinta.png" alt="Cinta" title="Cinta">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="lazo">
                                            <img src="/cce-certificados/assets/stickers/lazo.png" alt="Lazo" title="Lazo">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="lazo-insignia">
                                            <img src="/cce-certificados/assets/stickers/lazo-insignia.png" alt="Lazo Insignia" title="Lazo Insignia">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="capitan">
                                            <img src="/cce-certificados/assets/stickers/capitan.png" alt="Capitán" title="Capitán">
                                        </label>
                                        <label class="sticker-option">
                                            <input type="radio" name="destacado_icono" value="rango">
                                            <img src="/cce-certificados/assets/stickers/rango.png" alt="Rango" title="Rango">
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="destacado_imagen_group" style="display: none;">
                                    <label>Imagen Personalizada</label>
                                    <div class="file-upload-area" id="uploadDestacadoArea">
                                        <div class="upload-icon"><i class="fas fa-star"></i></div>
                                        <p>Subir imagen de sticker</p>
                                        <small>Formato PNG con fondo transparente recomendado</small>
                                    </div>
                                    <input type="file" id="destacado_imagen_input" name="destacado_imagen" accept="image/*" style="display: none;">
                                    <div id="currentDestacadoImg" class="sticker-preview-container"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tamanio_destacado">Tamaño del Sticker (px)</label>
                                    <input type="number" id="tamanio_destacado" name="tamanio_destacado" 
                                           value="100" min="30" max="300">
                                    <small>Ancho del sticker. El alto se ajustará proporcionalmente.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-preview" onclick="previewCertificate()"><i class="fas fa-eye"></i> Previsualizar</button>
                            <button type="button" class="btn" onclick="resetDefaults()"><i class="fas fa-undo"></i> Restablecer</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Previsualización -->
    <div class="preview-modal" id="previewModal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h3><i class="fas fa-eye"></i> Vista Previa del Certificado</h3>
                <button class="preview-modal-close" onclick="closePreviewModal()">✕</button>
            </div>
            <div class="preview-modal-body" id="previewBody">
                <div class="preview-loading">
                    <i class="fas fa-spinner"></i>
                    <p>Generando vista previa...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const iconEmojis = {
            workshop: '🛠️', course: '📚', trophy: '🏆', seminar: '🎓',
            award: '🏅', certificate: '📜', medal: '🥇', star: '⭐',
            rocket: '🚀', book: '📖'
        };
        
        // Mapeo de fuentes: nombre_archivo -> CustomFont{id}
        const fontMap = {
            <?php foreach ($fontsList as $index => $font): 
                $fontId = $font['id'] ?? $index + 1;
            ?>
            '<?= htmlspecialchars($font['nombre_archivo']) ?>': 'CustomFont<?= $fontId ?>',
            <?php endforeach; ?>
        };
        
        // Función para obtener el nombre CSS de la fuente
        function getCssFontFamily(nombreArchivo) {
            return fontMap[nombreArchivo] ? `'${fontMap[nombreArchivo]}', sans-serif` : 'sans-serif';
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const grupoId = urlParams.get('id');
        let grupoNombreActual = 'Nombre del Grupo'; // Se actualiza al cargar la config
        
        // Datos del primer estudiante del grupo (se cargan dinámicamente)
        let primerEstudianteNombre = 'Juan Pérez García';
        let primerEstudianteCategoria = 'Nombre de Categoría';
        
        if (!grupoId) {
            showNotification('Grupo no especificado', 'error');
            setTimeout(() => window.location.href = 'index.php', 1500);
        }
        
        document.getElementById('grupo_id').value = grupoId;
        
        // Función para cambiar de pestaña
        function switchTab(tabName) {
            // Desactivar todas las pestañas
            document.querySelectorAll('.variable-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.variable-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activar la pestaña seleccionada
            document.querySelector(`.variable-tab[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }
        
        // Función para establecer el formato del nombre
        function setFormatoNombre(formato) {
            // Actualizar botones
            document.querySelectorAll('.formato-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.formato-btn[data-formato="${formato}"]`).classList.add('active');
            
            // Actualizar campo oculto
            document.getElementById('formato_nombre').value = formato;
            
            // Actualizar el marcador en el canvas usando la función común
            updateNombreMarkerText();
        }
        
        // Variables para el drag & drop
        let isDragging = false;
        let currentMarker = null;
        let offsetX = 0;
        let offsetY = 0;
        let canvasScale = 1;
        
        // Dimensiones reales de la imagen cargada
        let imageRealWidth = 1600;
        let imageRealHeight = 1131;
        
        // Calcular escala del lienzo basándose en las dimensiones reales de la imagen
        function updateCanvasScale() {
            const canvas = document.getElementById('canvas');
            // Usar el ancho natural de la imagen (dimensiones reales)
            if (canvas.naturalWidth > 0) {
                imageRealWidth = canvas.naturalWidth;
                imageRealHeight = canvas.naturalHeight;
            }
            canvasScale = canvas.offsetWidth / imageRealWidth;
            console.log(`Canvas scale: ${canvasScale}, Real dimensions: ${imageRealWidth}x${imageRealHeight}`);
        }
        
        // Variables para resize del marcador razón
        let isResizing = false;
        let resizeStartX = 0;
        let resizeStartWidth = 0;
        
        // Inicializar drag & drop
        function initDragDrop() {
            const markers = document.querySelectorAll('.draggable');
            const container = document.getElementById('canvasContainer');
            
            markers.forEach(marker => {
                marker.addEventListener('mousedown', startDrag);
            });
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
            
            // Inicializar resize del marcador razón
            initRazonResize();
            
            // Posicionar marcadores inicialmente
            updateMarkerPositions();
        }
        
        // Actualizar texto del marcador nombre en el lienzo
        function updateNombreMarkerText() {
            const nombreMarker = document.getElementById('nombreMarker');
            if (nombreMarker) {
                // Obtener el formato actual
                const formatoActual = document.getElementById('formato_nombre')?.value || 'mayusculas';
                let nombreMostrar = primerEstudianteNombre || 'Juan Pérez García';
                
                switch(formatoActual) {
                    case 'mayusculas':
                        nombreMostrar = nombreMostrar.toUpperCase();
                        break;
                    case 'capitalizado':
                        nombreMostrar = nombreMostrar.split(' ').map(word => 
                            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                        ).join(' ');
                        break;
                    case 'minusculas':
                        nombreMostrar = nombreMostrar.toLowerCase();
                        break;
                }
                nombreMarker.innerHTML = `<i class="fas fa-user"></i> ${nombreMostrar}`;
            }
        }
        
        // Actualizar texto del marcador razón en el lienzo
        // Esta función actualiza el texto Y fuerza el recálculo de altura del marcador
        function updateRazonMarkerText() {
            const razonTextarea = document.getElementById('razon_defecto');
            const razonMarkerText = document.getElementById('razonMarkerText');
            const razonMarker = document.getElementById('razonMarker');
            
            if (razonTextarea && razonMarkerText && razonMarker) {
                let texto = razonTextarea.value.trim();
                
                // Reemplazar variables con valores del primer estudiante del grupo
                texto = texto.replace(/\{grupo\}/gi, grupoNombreActual);
                texto = texto.replace(/\{categoria\}/gi, primerEstudianteCategoria);
                texto = texto.replace(/\{nombre\}/gi, primerEstudianteNombre);
                
                // Formatear fecha usando el formato configurado
                const usarFechaEspecifica = document.getElementById('usar_fecha_especifica')?.checked || false;
                const fechaEspecifica = document.getElementById('fecha_especifica')?.value;
                const formatoFecha = document.getElementById('formato_fecha')?.value || 'd de F de Y';
                
                let fechaFormateada;
                if (usarFechaEspecifica && fechaEspecifica) {
                    fechaFormateada = formatearFechaJS(fechaEspecifica, formatoFecha);
                } else {
                    // Usar fecha actual
                    const hoy = new Date();
                    const fechaHoy = hoy.toISOString().split('T')[0];
                    fechaFormateada = formatearFechaJS(fechaHoy, formatoFecha);
                }
                texto = texto.replace(/\{fecha\}/gi, fechaFormateada);
                
                // Si el texto está vacío, mostrar placeholder
                if (!texto) {
                    texto = 'Escribe el texto de razón...';
                }
                
                // Actualizar el texto del marcador
                razonMarkerText.textContent = texto;
                
                // Aplicar alineación visual
                updateRazonMarkerAlignment();
                
                // Aplicar tamaño de fuente y line-height para cálculo correcto de altura
                const tamanioRazon = parseInt(document.getElementById('tamanio_razon')?.value) || 24;
                const fontSize = Math.max(8, tamanioRazon * canvasScale);
                razonMarker.style.fontSize = fontSize + 'px';
                razonMarker.style.lineHeight = '1.3';
                
                // Forzar recálculo de altura removiendo cualquier altura fija
                razonMarker.style.height = 'auto';
                
                // Aplicar fuente configurada
                const fuenteRazon = document.getElementById('fuente_razon')?.value || 'Roboto-Regular';
                const fontMapLocal = {
                    'Roboto-Regular': 'Roboto',
                    'OpenSans': 'OpenSans',
                    'Lato-Regular': 'Lato',
                    'Montserrat': 'Montserrat',
                    'Poppins-Regular': 'Poppins',
                    'PlayfairDisplay': 'PlayfairDisplay',
                    'Motterdam': 'Motterdam'
                };
                const fontName = fontMapLocal[fuenteRazon] || 'Roboto';
                razonMarker.style.fontFamily = `'${fontName}', sans-serif`;
                
                // Calcular número de líneas después de que el navegador renderice
                setTimeout(() => {
                    const lineHeightPx = fontSize * 1.3;
                    const markerHeight = razonMarker.offsetHeight;
                    const paddingVertical = 8; // padding top + bottom del CSS
                    const textHeight = markerHeight - paddingVertical;
                    const numLineas = Math.max(1, Math.round(textHeight / lineHeightPx));
                    document.getElementById('lineas_razon').value = numLineas;
                    console.log('Líneas calculadas en canvas:', numLineas, '(altura:', markerHeight, 'lineHeight:', lineHeightPx, ')');
                }, 50);
            }
        }
        
        // Actualizar alineación visual del marcador razón
        function updateRazonMarkerAlignment() {
            const razonMarker = document.getElementById('razonMarker');
            const alineacion = document.getElementById('alineacion_razon')?.value || 'justified';
            
            if (razonMarker) {
                // Mapear valores a CSS text-align
                const alignMap = {
                    'left': 'left',
                    'center': 'center',
                    'right': 'right',
                    'justified': 'justify'
                };
                razonMarker.style.textAlign = alignMap[alineacion] || 'justify';
                console.log('Alineación razón actualizada:', alineacion);
            }
        }
        
        // Actualizar ancho del marcador razón
        function updateRazonMarkerWidth(realWidth) {
            const razonMarker = document.getElementById('razonMarker');
            if (razonMarker) {
                // El ancho_razon representa el ancho real del texto en píxeles de la imagen
                // Aplicamos escala + padding fijo del marcador
                const paddingTotal = 20; // Padding fijo del marcador (no escala)
                const scaledWidth = (realWidth * canvasScale) + paddingTotal;
                razonMarker.style.width = scaledWidth + 'px';
                
                // Actualizar también los inputs
                document.getElementById('ancho_razon').value = realWidth;
                const anchoInput = document.getElementById('ancho_razon_input');
                if (anchoInput) anchoInput.value = realWidth;
            }
        }
        
        // Inicializar resize del marcador razón
        function initRazonResize() {
            const resizeHandle = document.getElementById('razonResizeHandle');
            const razonMarker = document.getElementById('razonMarker');
            const container = document.getElementById('canvasContainer');
            
            if (resizeHandle) {
                resizeHandle.addEventListener('mousedown', (e) => {
                    e.stopPropagation(); // Evitar que inicie el drag
                    isResizing = true;
                    resizeStartX = e.clientX;
                    resizeStartWidth = razonMarker.offsetWidth;
                    document.body.style.cursor = 'ew-resize';
                    e.preventDefault();
                });
            }
            
            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                
                const deltaX = e.clientX - resizeStartX;
                let newWidth = Math.max(100, resizeStartWidth + deltaX);
                
                // Limitar al ancho del canvas
                const containerWidth = container.offsetWidth;
                const markerLeft = razonMarker.offsetLeft;
                const maxWidth = containerWidth - markerLeft - 10;
                if (newWidth > maxWidth) {
                    newWidth = maxWidth;
                }
                
                razonMarker.style.width = newWidth + 'px';
                
                // Actualizar el valor real (sin escala), compensando el padding fijo (20px)
                const paddingTotal = 20;
                const realWidth = Math.round((newWidth - paddingTotal) / canvasScale);
                const clampedWidth = Math.max(100, realWidth);
                document.getElementById('ancho_razon').value = clampedWidth;
                document.getElementById('ancho_razon_input').value = clampedWidth;
                
                console.log('Resize razón:', {newWidth, realWidth: clampedWidth, canvasScale});
            });
            
            document.addEventListener('mouseup', () => {
                if (isResizing) {
                    isResizing = false;
                    document.body.style.cursor = '';
                }
            });
            
            // Sincronizar input con marcador
            const anchoInput = document.getElementById('ancho_razon_input');
            if (anchoInput) {
                anchoInput.addEventListener('input', () => {
                    const realWidth = parseInt(anchoInput.value) || 600;
                    // Compensar padding (4px izq + 16px der = 20px)
                    const paddingTotal = 20;
                    let scaledWidth = (realWidth * canvasScale) + paddingTotal;
                    
                    // Limitar al ancho del canvas
                    const containerWidth = container.offsetWidth;
                    const markerLeft = razonMarker.offsetLeft;
                    const maxWidth = containerWidth - markerLeft - 10;
                    if (scaledWidth > maxWidth) {
                        scaledWidth = maxWidth;
                    }
                    
                    razonMarker.style.width = scaledWidth + 'px';
                    document.getElementById('ancho_razon').value = realWidth;
                });
            }
        }
        
        function startDrag(e) {
            // No iniciar drag si estamos en el resize handle
            if (e.target.classList.contains('resize-handle')) return;
            
            isDragging = true;
            currentMarker = e.target.closest('.draggable');
            currentMarker.classList.add('active');
            
            const rect = currentMarker.getBoundingClientRect();
            const containerRect = document.getElementById('canvasContainer').getBoundingClientRect();
            
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            
            e.preventDefault();
        }
        
        function drag(e) {
            if (!isDragging || !currentMarker) return;
            
            const container = document.getElementById('canvasContainer');
            const containerRect = container.getBoundingClientRect();
            
            let x = e.clientX - containerRect.left - offsetX;
            let y = e.clientY - containerRect.top - offsetY;
            
            // Límites
            x = Math.max(-10, Math.min(x, containerRect.width - currentMarker.offsetWidth + 10));
            y = Math.max(-10, Math.min(y, containerRect.height - currentMarker.offsetHeight + 10));
            
            const type = currentMarker.dataset.type;
            
            currentMarker.style.left = x + 'px';
            currentMarker.style.top = y + 'px';
            
            // Calcular coordenadas reales basándose en el punto de anclaje
            let realX, realY;
            
            if (type === 'razon') {
                // Para razón: el marcador tiene padding CSS fijo (no escala con la imagen)
                // padding: 4px 6px; padding-right: 14px;
                // El texto empieza 6px a la derecha del borde izquierdo del marcador
                // y 4px abajo del borde superior
                // Estos valores son en píxeles de pantalla, no de imagen
                const paddingLeft = 6; // px de pantalla
                const paddingTop = 4;  // px de pantalla
                // La coordenada real es donde el marcador está + el padding (convertido a coordenadas de imagen)
                realX = Math.round((x + paddingLeft) / canvasScale);
                realY = Math.round((y + paddingTop) / canvasScale);
            } else if (type === 'nombre' || type === 'fecha') {
                // Para nombre y fecha: también tienen padding proporcional
                // El padding CSS es: padding = fontSize * 0.2 (mínimo 2px)
                // paddingLeft real = padding (calculado en updateMarkerSizes)
                const paddingLeftEstimado = 2; // Padding mínimo aproximado
                realX = Math.round((x + paddingLeftEstimado) / canvasScale);
                realY = Math.round(y / canvasScale);
            } else {
                // Para QR y Firma: el punto está en el centro del marcador
                const markerWidth = currentMarker.offsetWidth;
                const markerHeight = currentMarker.offsetHeight;
                realX = Math.round((x + markerWidth / 2) / canvasScale);
                realY = Math.round((y + markerHeight / 2) / canvasScale);
            }
            
            updateCoordinatesFromMarker(type, realX, realY);
        }
        
        function stopDrag() {
            if (currentMarker) {
                currentMarker.classList.remove('active');
            }
            isDragging = false;
            currentMarker = null;
        }
        
        function updateCoordinatesFromMarker(type, x, y) {
            if (type === 'nombre') {
                const posX = document.getElementById('posicion_nombre_x');
                const posY = document.getElementById('posicion_nombre_y');
                const coord = document.getElementById('coordNombre');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            } else if (type === 'razon') {
                const posX = document.getElementById('posicion_razon_x');
                const posY = document.getElementById('posicion_razon_y');
                const coord = document.getElementById('coordRazon');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            } else if (type === 'fecha') {
                const posX = document.getElementById('posicion_fecha_x');
                const posY = document.getElementById('posicion_fecha_y');
                const coord = document.getElementById('coordFecha');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            } else if (type === 'qr') {
                const posX = document.getElementById('posicion_qr_x');
                const posY = document.getElementById('posicion_qr_y');
                const coord = document.getElementById('coordQr');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            } else if (type === 'firma') {
                const posX = document.getElementById('posicion_firma_x');
                const posY = document.getElementById('posicion_firma_y');
                const coord = document.getElementById('coordFirma');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            } else if (type === 'destacado') {
                const posX = document.getElementById('posicion_destacado_x');
                const posY = document.getElementById('posicion_destacado_y');
                const coord = document.getElementById('coordDestacado');
                if (posX) posX.value = x;
                if (posY) posY.value = y;
                if (coord) coord.textContent = `X: ${x}, Y: ${y}`;
            }
        }
        
        function updateMarkerPositions() {
            updateCanvasScale();
            
            const canvas = document.getElementById('canvas');
            const canvasWidth = canvas.offsetWidth;
            const canvasHeight = canvas.offsetHeight;
            
            const posNombreX = document.getElementById('posicion_nombre_x');
            const posNombreY = document.getElementById('posicion_nombre_y');
            const posRazonX = document.getElementById('posicion_razon_x');
            const posRazonY = document.getElementById('posicion_razon_y');
            const posFechaX = document.getElementById('posicion_fecha_x');
            const posFechaY = document.getElementById('posicion_fecha_y');
            const posQrX = document.getElementById('posicion_qr_x');
            const posQrY = document.getElementById('posicion_qr_y');
            const posFirmaX = document.getElementById('posicion_firma_x');
            const posFirmaY = document.getElementById('posicion_firma_y');
            
            const nombreX = parseInt(posNombreX?.value) || 400;
            const nombreY = parseInt(posNombreY?.value) || 300;
            const razonX = parseInt(posRazonX?.value) || 400;
            const razonY = parseInt(posRazonY?.value) || 360;
            const fechaX = parseInt(posFechaX?.value) || 400;
            const fechaY = parseInt(posFechaY?.value) || 420;
            const qrX = parseInt(posQrX?.value) || 920;
            const qrY = parseInt(posQrY?.value) || 419;
            const firmaX = parseInt(posFirmaX?.value) || 800;
            const firmaY = parseInt(posFirmaY?.value) || 850;
            
            const nombreMarker = document.getElementById('nombreMarker');
            const razonMarker = document.getElementById('razonMarker');
            const fechaMarker = document.getElementById('fechaMarker');
            const qrMarker = document.getElementById('qrMarker');
            const firmaMarker = document.getElementById('firmaMarker');
            
            if (nombreMarker) {
                // Compensar padding-left del marcador (aprox 2px mínimo)
                const paddingLeftEstimado = 2;
                let left = (nombreX * canvasScale) - paddingLeftEstimado;
                let top = nombreY * canvasScale;
                // Asegurar que esté dentro del canvas
                left = Math.max(0, Math.min(left, canvasWidth - 20));
                top = Math.max(0, Math.min(top, canvasHeight - 20));
                nombreMarker.style.left = left + 'px';
                nombreMarker.style.top = top + 'px';
            }
            if (razonMarker) {
                // Compensar padding del marcador: paddingLeft=6px, paddingTop=4px
                const paddingLeftFijo = 6;
                const paddingTopFijo = 4;
                let left = (razonX * canvasScale) - paddingLeftFijo;
                let top = (razonY * canvasScale) - paddingTopFijo;
                
                // Aplicar ancho guardado (limitado al canvas)
                // El ancho_razon representa el ancho real del texto en la imagen
                const anchoRazon = parseInt(document.getElementById('ancho_razon')?.value) || 600;
                // Padding total del marcador: 4px izq + 6px der + 14px handle = ~24px, pero escalamos solo el contenido
                const paddingEscalado = 20; // Padding fijo para el marcador
                let markerWidth = (anchoRazon * canvasScale) + paddingEscalado;
                
                // Limitar el ancho para que no exceda el canvas
                const maxWidth = canvasWidth - left - 10;
                if (markerWidth > maxWidth && maxWidth > 50) {
                    markerWidth = maxWidth;
                }
                
                // Asegurar que esté dentro del canvas
                left = Math.max(0, Math.min(left, canvasWidth - 50));
                top = Math.max(0, Math.min(top, canvasHeight - 20));
                
                razonMarker.style.left = left + 'px';
                razonMarker.style.top = top + 'px';
                razonMarker.style.width = markerWidth + 'px';
                razonMarker.style.display = 'flex';
                
                console.log('Razón marker posicionado:', {left, top, markerWidth, anchoRazon, canvasScale});
            }
            if (fechaMarker) {
                // Compensar padding-left del marcador (aprox 2px mínimo)
                const paddingLeftEstimado = 2;
                let left = (fechaX * canvasScale) - paddingLeftEstimado;
                let top = fechaY * canvasScale;
                left = Math.max(0, Math.min(left, canvasWidth - 20));
                top = Math.max(0, Math.min(top, canvasHeight - 20));
                fechaMarker.style.left = left + 'px';
                fechaMarker.style.top = top + 'px';
            }
            if (qrMarker) {
                // QR se posiciona desde el centro
                const qrWidth = qrMarker.offsetWidth || 60;
                const qrHeight = qrMarker.offsetHeight || 60;
                let left = (qrX * canvasScale) - (qrWidth / 2);
                let top = (qrY * canvasScale) - (qrHeight / 2);
                // Asegurar que esté dentro del canvas
                left = Math.max(-10, Math.min(left, canvasWidth - qrWidth + 10));
                top = Math.max(-10, Math.min(top, canvasHeight - qrHeight + 10));
                qrMarker.style.left = left + 'px';
                qrMarker.style.top = top + 'px';
            }
            if (firmaMarker) {
                // Firma se posiciona desde el centro
                const firmaWidth = firmaMarker.offsetWidth || 80;
                const firmaHeight = firmaMarker.offsetHeight || 40;
                let left = (firmaX * canvasScale) - (firmaWidth / 2);
                let top = (firmaY * canvasScale) - (firmaHeight / 2);
                // Asegurar que esté dentro del canvas
                left = Math.max(-10, Math.min(left, canvasWidth - firmaWidth + 10));
                top = Math.max(-10, Math.min(top, canvasHeight - firmaHeight + 10));
                firmaMarker.style.left = left + 'px';
                firmaMarker.style.top = top + 'px';
            }
            
            // Posicionar marcador de destacado
            const destacadoMarker = document.getElementById('destacadoMarker');
            const destacadoX = parseInt(document.getElementById('posicion_destacado_x')?.value) || 50;
            const destacadoY = parseInt(document.getElementById('posicion_destacado_y')?.value) || 50;
            const tamanioDestacadoVal = parseInt(document.getElementById('tamanio_destacado')?.value) || 100;
            if (destacadoMarker) {
                // Usar el tamaño calculado, no offsetWidth/Height (que puede ser 0 si display:none)
                const destacadoSize = Math.max(20, tamanioDestacadoVal * canvasScale);
                let left = (destacadoX * canvasScale) - (destacadoSize / 2);
                let top = (destacadoY * canvasScale) - (destacadoSize / 2);
                left = Math.max(-10, Math.min(left, canvasWidth - destacadoSize + 10));
                top = Math.max(-10, Math.min(top, canvasHeight - destacadoSize + 10));
                destacadoMarker.style.left = left + 'px';
                destacadoMarker.style.top = top + 'px';
                // Aplicar también el tamaño
                destacadoMarker.style.width = destacadoSize + 'px';
                destacadoMarker.style.height = destacadoSize + 'px';
            }
            
            updateCoordinatesFromMarker('nombre', nombreX, nombreY);
            updateCoordinatesFromMarker('razon', razonX, razonY);
            updateCoordinatesFromMarker('fecha', fechaX, fechaY);
            updateCoordinatesFromMarker('qr', qrX, qrY);
            updateCoordinatesFromMarker('firma', firmaX, firmaY);
            updateCoordinatesFromMarker('destacado', destacadoX, destacadoY);
            
            // También actualizar tamaños de marcadores
            updateMarkerSizes();
        }
        
        // Función para actualizar el tamaño de los marcadores según los valores de los inputs
        function updateMarkerSizes() {
            const tamanioQr = parseInt(document.getElementById('tamanio_qr')?.value) || 200;
            const tamanioFirma = parseInt(document.getElementById('tamanio_firma')?.value) || 200;
            const tamanioFuente = parseInt(document.getElementById('tamanio_fuente')?.value) || 50;
            const tamanioRazon = parseInt(document.getElementById('tamanio_razon')?.value) || 24;
            const tamanioFecha = parseInt(document.getElementById('tamanio_fecha')?.value) || 20;
            
            const qrMarker = document.getElementById('qrMarker');
            const firmaMarker = document.getElementById('firmaMarker');
            const nombreMarker = document.getElementById('nombreMarker');
            const razonMarker = document.getElementById('razonMarker');
            const fechaMarker = document.getElementById('fechaMarker');
            
            // Aplicar tamaño escalado al marcador QR
            if (qrMarker) {
                const qrSize = Math.max(30, tamanioQr * canvasScale);
                qrMarker.style.width = qrSize + 'px';
                qrMarker.style.height = qrSize + 'px';
            }
            
            // Aplicar tamaño escalado al marcador Firma (mantener proporción 2:1)
            if (firmaMarker) {
                const firmaWidth = Math.max(40, tamanioFirma * canvasScale);
                const firmaHeight = Math.max(20, (tamanioFirma / 2) * canvasScale);
                firmaMarker.style.width = firmaWidth + 'px';
                firmaMarker.style.height = firmaHeight + 'px';
            }
            
            // Aplicar tamaño de fuente escalado al marcador Nombre
            if (nombreMarker) {
                const fontSize = Math.max(10, tamanioFuente * canvasScale * 0.8); // Factor 0.8 para mejor visualización
                nombreMarker.style.fontSize = fontSize + 'px';
                // Ajustar padding proporcionalmente
                const padding = Math.max(2, fontSize * 0.2);
                nombreMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
                // Aplicar fuente configurada
                const fuenteNombre = document.getElementById('fuente_nombre')?.value || 'Roboto-Regular';
                nombreMarker.style.fontFamily = getCssFontFamily(fuenteNombre);
            }
            
            // Aplicar tamaño de fuente escalado al marcador Razón
            if (razonMarker) {
                // Usar factor 1.0 para mejor coincidencia con la previsualización real
                const fontSize = Math.max(8, tamanioRazon * canvasScale);
                razonMarker.style.fontSize = fontSize + 'px';
                razonMarker.style.lineHeight = '1.3';
                // Padding consistente con CSS: 4px arriba/abajo, 6px izquierda, 14px derecha (para handle)
                razonMarker.style.paddingTop = '4px';
                razonMarker.style.paddingBottom = '4px';
                razonMarker.style.paddingLeft = '6px';
                razonMarker.style.paddingRight = '14px'; // Espacio para handle
                // Aplicar fuente configurada
                const fuenteRazon = document.getElementById('fuente_razon')?.value || 'Roboto-Regular';
                razonMarker.style.fontFamily = getCssFontFamily(fuenteRazon);
            }
            
            // Aplicar tamaño de fuente escalado al marcador Fecha
            if (fechaMarker) {
                const fontSize = Math.max(8, tamanioFecha * canvasScale * 0.8);
                fechaMarker.style.fontSize = fontSize + 'px';
                const padding = Math.max(2, fontSize * 0.2);
                fechaMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
                // Aplicar fuente configurada
                const fuenteFecha = document.getElementById('fuente_fecha')?.value || 'Roboto-Regular';
                fechaMarker.style.fontFamily = getCssFontFamily(fuenteFecha);
            }
            
            // Aplicar tamaño al marcador de Destacado
            const tamanioDestacado = parseInt(document.getElementById('tamanio_destacado')?.value) || 100;
            const destacadoMarker = document.getElementById('destacadoMarker');
            if (destacadoMarker) {
                const destacadoSize = Math.max(20, tamanioDestacado * canvasScale);
                destacadoMarker.style.width = destacadoSize + 'px';
                destacadoMarker.style.height = destacadoSize + 'px';
                console.log('Destacado marker size:', destacadoSize, 'px (tamanio:', tamanioDestacado, ', scale:', canvasScale, ')');
            }
        }
        
        // Inicializar eventos de cambio de tamaño
        function initSizeChangeEvents() {
            const tamanioQr = document.getElementById('tamanio_qr');
            const tamanioFirma = document.getElementById('tamanio_firma');
            const tamanioFuente = document.getElementById('tamanio_fuente');
            const tamanioRazon = document.getElementById('tamanio_razon');
            const tamanioFecha = document.getElementById('tamanio_fecha');
            
            if (tamanioQr) {
                tamanioQr.addEventListener('input', () => {
                    updateMarkerSizes();
                    updateMarkerPositions(); // Reposicionar después de cambiar tamaño
                });
            }
            if (tamanioFirma) {
                tamanioFirma.addEventListener('input', () => {
                    updateMarkerSizes();
                    updateMarkerPositions();
                });
            }
            if (tamanioFuente) {
                tamanioFuente.addEventListener('input', () => {
                    updateMarkerSizes();
                });
            }
            if (tamanioRazon) {
                tamanioRazon.addEventListener('input', () => {
                    updateMarkerSizes();
                });
            }
            if (tamanioFecha) {
                tamanioFecha.addEventListener('input', () => {
                    updateMarkerSizes();
                });
            }
            
            // Agregar listener para cambio de tamaño de destacado
            const tamanioDestacado = document.getElementById('tamanio_destacado');
            if (tamanioDestacado) {
                tamanioDestacado.addEventListener('input', () => {
                    updateMarkerSizes();
                    updateMarkerPositions();
                });
            }
            
            // Agregar listeners para cambio de fuente
            const fuenteNombre = document.getElementById('fuente_nombre');
            if (fuenteNombre) {
                fuenteNombre.addEventListener('change', () => {
                    updateMarkerSizes();
                });
            }
            
            const fuenteRazon = document.getElementById('fuente_razon');
            if (fuenteRazon) {
                fuenteRazon.addEventListener('change', () => {
                    updateMarkerSizes();
                });
            }
            
            const fuenteFecha = document.getElementById('fuente_fecha');
            if (fuenteFecha) {
                fuenteFecha.addEventListener('change', () => {
                    updateMarkerSizes();
                });
            }
            
            // Agregar listener para cambio de icono destacado
            document.querySelectorAll('input[name="destacado_icono"]').forEach(radio => {
                radio.addEventListener('change', updateDestacadoMarkerImage);
            });
        }
        
        // Función para actualizar la imagen del marcador de destacado
        function updateDestacadoMarkerImage() {
            const tipo = document.getElementById('destacado_tipo')?.value || 'icono';
            const markerImg = document.getElementById('destacadoMarkerImg');
            
            if (!markerImg) {
                console.log('DEBUG - No se encontró marcador de imagen destacado');
                return;
            }
            
            if (tipo === 'imagen') {
                // Usar imagen personalizada si existe
                const currentImg = document.querySelector('#currentDestacadoImg img');
                if (currentImg && currentImg.src) {
                    markerImg.src = currentImg.src;
                    console.log('DEBUG - Usando imagen personalizada:', currentImg.src);
                    return;
                }
            }
            
            // Usar icono predeterminado
            const iconoSeleccionado = document.querySelector('input[name="destacado_icono"]:checked');
            if (iconoSeleccionado) {
                const newSrc = '/cce-certificados/assets/stickers/' + iconoSeleccionado.value + '.png';
                markerImg.src = newSrc;
                console.log('DEBUG - Usando icono predeterminado:', newSrc);
            } else {
                console.log('DEBUG - No hay icono seleccionado');
            }
        }
        
        // Función para alternar entre opciones de sticker de destacado
        function toggleDestacadoOptions() {
            const tipo = document.getElementById('destacado_tipo')?.value || 'icono';
            const iconoGroup = document.getElementById('destacado_icono_group');
            const imagenGroup = document.getElementById('destacado_imagen_group');
            
            if (tipo === 'icono') {
                if (iconoGroup) iconoGroup.style.display = 'block';
                if (imagenGroup) imagenGroup.style.display = 'none';
                // Actualizar marcador con el icono seleccionado
                updateDestacadoMarkerImage();
            } else {
                if (iconoGroup) iconoGroup.style.display = 'none';
                if (imagenGroup) imagenGroup.style.display = 'block';
                // Si hay una imagen personalizada cargada, actualizar marcador
                const currentImg = document.querySelector('#currentDestacadoImg img');
                if (currentImg && currentImg.src) {
                    const markerImg = document.getElementById('destacadoMarkerImg');
                    if (markerImg) markerImg.src = currentImg.src;
                }
            }
        }
        
        // Función para mostrar/ocultar marcadores según variables habilitadas
        function updateMarkersVisibility() {
            const varNombre = document.getElementById('var_nombre');
            const varRazon = document.getElementById('var_razon');
            const varFecha = document.getElementById('var_fecha');
            const varQr = document.getElementById('var_qr');
            const varFirma = document.getElementById('var_firma');
            
            const nombreEnabled = varNombre?.checked ?? true;
            const razonEnabled = varRazon?.checked ?? true;
            const fechaEnabled = varFecha?.checked ?? true;
            const qrEnabled = varQr?.checked ?? true;
            const firmaEnabled = varFirma?.checked ?? true;
            const destacadoEnabled = document.getElementById('var_destacado')?.checked ?? false;
            
            const nombreMarker = document.getElementById('nombreMarker');
            const razonMarker = document.getElementById('razonMarker');
            const fechaMarker = document.getElementById('fechaMarker');
            const qrMarker = document.getElementById('qrMarker');
            const firmaMarker = document.getElementById('firmaMarker');
            const destacadoMarker = document.getElementById('destacadoMarker');
            
            const coordNombreContainer = document.getElementById('coordNombreContainer');
            const coordRazonContainer = document.getElementById('coordRazonContainer');
            const coordFechaContainer = document.getElementById('coordFechaContainer');
            const coordQrContainer = document.getElementById('coordQrContainer');
            const coordFirmaContainer = document.getElementById('coordFirmaContainer');
            const coordDestacadoContainer = document.getElementById('coordDestacadoContainer');
            
            if (nombreMarker) nombreMarker.style.setProperty('display', nombreEnabled ? 'flex' : 'none', 'important');
            if (razonMarker) razonMarker.style.setProperty('display', razonEnabled ? 'flex' : 'none', 'important');
            if (fechaMarker) fechaMarker.style.setProperty('display', fechaEnabled ? 'flex' : 'none', 'important');
            if (qrMarker) qrMarker.style.setProperty('display', qrEnabled ? 'flex' : 'none', 'important');
            if (firmaMarker) firmaMarker.style.setProperty('display', firmaEnabled ? 'flex' : 'none', 'important');
            if (destacadoMarker) destacadoMarker.style.setProperty('display', destacadoEnabled ? 'flex' : 'none', 'important');
            
            if (coordNombreContainer) coordNombreContainer.style.display = nombreEnabled ? 'block' : 'none';
            if (coordRazonContainer) coordRazonContainer.style.display = razonEnabled ? 'block' : 'none';
            if (coordFechaContainer) coordFechaContainer.style.display = fechaEnabled ? 'block' : 'none';
            if (coordQrContainer) coordQrContainer.style.display = qrEnabled ? 'block' : 'none';
            if (coordFirmaContainer) coordFirmaContainer.style.display = firmaEnabled ? 'block' : 'none';
            if (coordDestacadoContainer) coordDestacadoContainer.style.display = destacadoEnabled ? 'flex' : 'none';
        }
        
        // Inicializar eventos de checkboxes
        function initCheckboxEvents() {
            document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', updateMarkersVisibility);
            });
        }
        
        // Reajustar al cambiar tamaño de ventana
        window.addEventListener('resize', updateMarkerPositions);
        
        // Inicializar después de cargar la imagen
        document.getElementById('canvas').addEventListener('load', () => {
            updateMarkerPositions();
            initDragDrop();
            initRazonResize();
            updateMarkersVisibility();
            initCheckboxEvents();
            initSizeChangeEvents();
        });
        
        // También inicializar cuando el DOM esté listo (para imágenes SVG inline)
        document.addEventListener('DOMContentLoaded', () => {
            // Pequeño delay para asegurar que el layout esté calculado
            setTimeout(() => {
                updateMarkerPositions();
                initDragDrop();
                initRazonResize();
                updateMarkersVisibility();
                initCheckboxEvents();
                initSizeChangeEvents();
                actualizarTextoFechaMarker(); // Actualizar texto de fecha inicial
            }, 100);
        });
        
        // Sincronizar color picker - Nombre
        document.getElementById('color_texto').addEventListener('input', (e) => {
            document.getElementById('color_texto_hex').value = e.target.value;
        });
        document.getElementById('color_texto_hex').addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                document.getElementById('color_texto').value = e.target.value;
            }
        });
        
        // Sincronizar color picker - Razón
        document.getElementById('color_razon').addEventListener('input', (e) => {
            document.getElementById('color_razon_hex').value = e.target.value;
        });
        document.getElementById('color_razon_hex').addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                document.getElementById('color_razon').value = e.target.value;
            }
        });
        
        // Sincronizar color picker - Fecha
        document.getElementById('color_fecha').addEventListener('input', (e) => {
            document.getElementById('color_fecha_hex').value = e.target.value;
        });
        document.getElementById('color_fecha_hex').addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                document.getElementById('color_fecha').value = e.target.value;
            }
        });
        
        // Mostrar/ocultar campo de fecha específica
        document.getElementById('usar_fecha_especifica').addEventListener('change', (e) => {
            const fechaEspecificaGroup = document.getElementById('fecha_especifica_group');
            fechaEspecificaGroup.style.display = e.target.checked ? 'block' : 'none';
            actualizarTextoFechaMarker();
            updateRazonMarkerText(); // También actualizar razón si usa {fecha}
        });
        
        // Actualizar el texto del marcador de fecha cuando cambie el formato o la fecha
        document.getElementById('formato_fecha').addEventListener('change', () => {
            actualizarTextoFechaMarker();
            updateRazonMarkerText(); // También actualizar razón si usa {fecha}
        });
        document.getElementById('fecha_especifica').addEventListener('change', () => {
            actualizarTextoFechaMarker();
            updateRazonMarkerText(); // También actualizar razón si usa {fecha}
        });
        
        // Función para formatear fecha según el formato seleccionado
        function formatearFechaJS(fecha, formato) {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const d = new Date(fecha + 'T12:00:00'); // Agregar hora para evitar problemas de timezone
            const dia = d.getDate();
            const mes = d.getMonth();
            const anio = d.getFullYear();
            const diaStr = dia.toString().padStart(2, '0');
            const mesNum = (mes + 1).toString().padStart(2, '0');
            
            switch(formato) {
                case 'd de F de Y':
                    return `${dia} de ${meses[mes]} de ${anio}`;
                case 'd/m/Y':
                    return `${diaStr}/${mesNum}/${anio}`;
                case 'd-m-Y':
                    return `${diaStr}-${mesNum}-${anio}`;
                case 'F d, Y':
                    return `${meses[mes]} ${dia}, ${anio}`;
                case 'd F Y':
                    return `${dia} ${meses[mes]} ${anio}`;
                case 'Y-m-d':
                    return `${anio}-${mesNum}-${diaStr}`;
                default:
                    return `${dia} de ${meses[mes]} de ${anio}`;
            }
        }
        
        // Función para actualizar el texto del marcador de fecha
        function actualizarTextoFechaMarker() {
            const fechaMarker = document.getElementById('fechaMarker');
            if (!fechaMarker) return;
            
            const usarFechaEspecifica = document.getElementById('usar_fecha_especifica')?.checked || false;
            const fechaEspecifica = document.getElementById('fecha_especifica')?.value;
            const formato = document.getElementById('formato_fecha')?.value || 'd de F de Y';
            
            let fechaTexto;
            if (usarFechaEspecifica && fechaEspecifica) {
                fechaTexto = formatearFechaJS(fechaEspecifica, formato);
            } else {
                // Usar fecha actual
                const hoy = new Date();
                const fechaHoy = hoy.toISOString().split('T')[0];
                fechaTexto = formatearFechaJS(fechaHoy, formato);
            }
            
            fechaMarker.innerHTML = `<i class="fas fa-calendar"></i> ${fechaTexto}`;
        }
        
        // =====================================================
        // SLIDER DE PLANTILLAS CON NAVEGACIÓN
        // =====================================================
        let currentTemplates = [];
        let activeTemplateId = null;
        let sliderIndex = 0;
        const ITEMS_PER_VIEW = 3;
        const MAX_TEMPLATES = 6;
        
        // Cargar plantillas del grupo
        async function loadTemplatesSlider() {
            try {
                const response = await fetch(`api_grupo_plantillas.php?action=list&grupo_id=${grupoId}`);
                const data = await response.json();
                
                if (data.success) {
                    currentTemplates = data.plantillas;
                    sliderIndex = 0;
                    renderTemplatesSlider();
                }
            } catch (error) {
                console.error('Error cargando plantillas:', error);
            }
        }
        
        // Renderizar el slider de plantillas
        function renderTemplatesSlider() {
            const slider = document.getElementById('templatesSlider');
            const countBadge = document.getElementById('templateCount');
            
            // Limpiar slider
            slider.innerHTML = '';
            
            // Actualizar contador (X/6)
            countBadge.textContent = `${currentTemplates.length}/${MAX_TEMPLATES}`;
            
            if (currentTemplates.length === 0) {
                // Mostrar mensaje vacío y botón agregar
                slider.innerHTML = `
                    <div class="templates-empty">
                        <i class="fas fa-image"></i>
                        <p>No hay plantillas. Haz clic en + para agregar.</p>
                    </div>
                `;
                
                // Agregar botón de añadir
                const addBtnHtml = document.createElement('div');
                addBtnHtml.className = 'template-add-btn';
                addBtnHtml.id = 'templateAddBtn';
                addBtnHtml.innerHTML = `
                    <i class="fas fa-plus"></i>
                    <span>Agregar</span>
                `;
                addBtnHtml.addEventListener('click', () => {
                    document.getElementById('plantillaUpload').click();
                });
                slider.appendChild(addBtnHtml);
                
                updateSliderArrows();
                loadActiveTemplateToCanvas();
                return;
            }
            
            // Renderizar todas las plantillas
            currentTemplates.forEach((template, index) => {
                const isActive = template.es_activa == 1;
                if (isActive) activeTemplateId = template.id;
                
                const templatePath = `${basePath}uploads/grupos/${grupoId}/${template.archivo}`;
                
                const item = document.createElement('div');
                item.className = `template-item ${isActive ? 'active' : ''}`;
                    item.dataset.id = template.id;
                    item.dataset.index = index;
                    item.innerHTML = `
                        <img src="${templatePath}" alt="${template.nombre}" class="template-thumb" loading="lazy">
                        <div class="template-info">
                            <div class="template-name" title="${template.nombre}">${template.nombre}</div>
                            <div class="template-actions">
                                <button type="button" class="btn-select" onclick="event.stopPropagation(); selectTemplate(${template.id})" title="Usar esta plantilla">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button type="button" class="btn-delete" onclick="event.stopPropagation(); deleteTemplate(${template.id})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // Click en el item selecciona la plantilla
                    item.addEventListener('click', () => {
                        selectTemplate(template.id);
                    });
                    
                    slider.appendChild(item);
                });
            
            // Agregar botón de añadir al final
            const addBtnHtml = document.createElement('div');
            addBtnHtml.className = 'template-add-btn';
            addBtnHtml.id = 'templateAddBtn';
            addBtnHtml.innerHTML = `
                <i class="fas fa-plus"></i>
                <span>Agregar</span>
            `;
            addBtnHtml.addEventListener('click', () => {
                document.getElementById('plantillaUpload').click();
            });
            slider.appendChild(addBtnHtml);
            
            // Actualizar visibilidad de items
            updateSliderView();
            
            // Cargar la plantilla activa en el canvas
            loadActiveTemplateToCanvas();
        }
        
        // Actualizar qué items son visibles en el slider
        function updateSliderView() {
            const items = document.querySelectorAll('.template-item');
            const addBtn = document.getElementById('templateAddBtn');
            const canAddMore = currentTemplates.length < MAX_TEMPLATES;
            
            // Mostrar plantillas según el índice actual
            items.forEach((item, index) => {
                if (index >= sliderIndex && index < sliderIndex + ITEMS_PER_VIEW) {
                    item.classList.add('visible');
                } else {
                    item.classList.remove('visible');
                }
            });
            
            // El botón agregar: visible si podemos agregar más Y está en el rango visible
            if (addBtn) {
                if (canAddMore) {
                    // El botón agregar está en la posición currentTemplates.length (después de todas las plantillas)
                    const addBtnPosition = currentTemplates.length;
                    const isInView = addBtnPosition >= sliderIndex && addBtnPosition < sliderIndex + ITEMS_PER_VIEW;
                    addBtn.style.display = isInView ? 'flex' : 'none';
                } else {
                    addBtn.style.display = 'none';
                }
            }
            
            updateSliderArrows();
        }
        
        // Actualizar estado de las flechas
        function updateSliderArrows() {
            const prevBtn = document.getElementById('sliderPrev');
            const nextBtn = document.getElementById('sliderNext');
            
            const canAddMore = currentTemplates.length < MAX_TEMPLATES;
            // Total incluye el botón agregar si podemos agregar más
            const totalItems = canAddMore ? currentTemplates.length + 1 : currentTemplates.length;
            
            // Deshabilitar prev si estamos al inicio
            prevBtn.disabled = sliderIndex === 0;
            
            // Deshabilitar next si ya vemos todos los items
            nextBtn.disabled = sliderIndex + ITEMS_PER_VIEW >= totalItems;
        }
        
        // Navegar hacia atrás
        function sliderPrevious() {
            if (sliderIndex > 0) {
                sliderIndex--;
                updateSliderView();
            }
        }
        
        // Navegar hacia adelante
        function sliderNext() {
            const canAddMore = currentTemplates.length < MAX_TEMPLATES;
            const totalItems = canAddMore ? currentTemplates.length + 1 : currentTemplates.length;
            
            if (sliderIndex + ITEMS_PER_VIEW < totalItems) {
                sliderIndex++;
                updateSliderView();
            }
        }
        
        // Event listeners para flechas
        document.getElementById('sliderPrev').addEventListener('click', sliderPrevious);
        document.getElementById('sliderNext').addEventListener('click', sliderNext);
        
        // Cargar plantilla activa en el canvas
        async function loadActiveTemplateToCanvas() {
            const activeTemplate = currentTemplates.find(t => t.es_activa == 1);
            const canvas = document.getElementById('canvas');
            
            if (activeTemplate) {
                activeTemplateId = activeTemplate.id;
                const templatePath = `${basePath}uploads/grupos/${grupoId}/${activeTemplate.archivo}`;
                console.log('Cargando plantilla activa en canvas:', templatePath);
                canvas.src = templatePath;
                canvas.onerror = function() {
                    console.error('Error al cargar la plantilla:', templatePath);
                    this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3EError al cargar plantilla%3C/text%3E%3C/svg%3E';
                };
                canvas.onload = function() {
                    console.log('Plantilla cargada correctamente');
                    updateCanvasScale();
                    updateMarkerPositions();
                };
                
                // Cargar la configuración específica de esta plantilla
                await loadTemplateConfig(activeTemplate.id);
            } else {
                canvas.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1600" height="1131"%3E%3Crect width="1600" height="1131" fill="%23f8f9fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="24" fill="%23999"%3ESube una plantilla para comenzar%3C/text%3E%3C/svg%3E';
            }
        }
        
        // Cargar configuración de una plantilla específica
        async function loadTemplateConfig(templateId) {
            try {
                const response = await fetch(`api_grupo_plantillas.php?action=get_config&grupo_id=${grupoId}&plantilla_id=${templateId}`);
                const data = await response.json();
                
                if (data.success && data.config) {
                    const config = data.config;
                    
                    // Aplicar posiciones
                    document.getElementById('posicion_nombre_x').value = config.posicion_nombre_x || 400;
                    document.getElementById('posicion_nombre_y').value = config.posicion_nombre_y || 300;
                    document.getElementById('posicion_razon_x').value = config.posicion_razon_x || 400;
                    document.getElementById('posicion_razon_y').value = config.posicion_razon_y || 360;
                    document.getElementById('posicion_fecha_x').value = config.posicion_fecha_x || 400;
                    document.getElementById('posicion_fecha_y').value = config.posicion_fecha_y || 420;
                    document.getElementById('posicion_qr_x').value = config.posicion_qr_x || 920;
                    document.getElementById('posicion_qr_y').value = config.posicion_qr_y || 419;
                    document.getElementById('posicion_firma_x').value = config.posicion_firma_x || 800;
                    document.getElementById('posicion_firma_y').value = config.posicion_firma_y || 850;
                    
                    // Aplicar fuentes
                    const fuenteNombre = document.getElementById('fuente_nombre');
                    const fuenteRazon = document.getElementById('fuente_razon');
                    const fuenteFecha = document.getElementById('fuente_fecha');
                    if (fuenteNombre) fuenteNombre.value = config.fuente_nombre || 'Roboto-Regular';
                    if (fuenteRazon) fuenteRazon.value = config.fuente_razon || 'Roboto-Regular';
                    if (fuenteFecha) fuenteFecha.value = config.fuente_fecha || 'Roboto-Regular';
                    
                    // Aplicar tamaños
                    document.getElementById('tamanio_fuente').value = config.tamanio_fuente || 50;
                    document.getElementById('tamanio_razon').value = config.tamanio_razon || 24;
                    document.getElementById('tamanio_fecha').value = config.tamanio_fecha || 20;
                    document.getElementById('tamanio_qr').value = config.tamanio_qr || 200;
                    document.getElementById('tamanio_firma').value = config.tamanio_firma || 200;
                    
                    // Aplicar colores
                    document.getElementById('color_texto').value = config.color_texto || '#000000';
                    document.getElementById('color_texto_hex').value = config.color_texto || '#000000';
                    document.getElementById('color_razon').value = config.color_razon || '#333333';
                    document.getElementById('color_razon_hex').value = config.color_razon || '#333333';
                    document.getElementById('color_fecha').value = config.color_fecha || '#333333';
                    document.getElementById('color_fecha_hex').value = config.color_fecha || '#333333';
                    
                    // Aplicar razón y formato fecha
                    const razonDefecto = document.getElementById('razon_defecto');
                    const formatoFecha = document.getElementById('formato_fecha');
                    if (razonDefecto) razonDefecto.value = config.razon_defecto || '';
                    if (formatoFecha) formatoFecha.value = config.formato_fecha || 'd de F de Y';
                    
                    // Aplicar ancho de razón
                    const anchoRazon = config.ancho_razon || 600;
                    document.getElementById('ancho_razon').value = anchoRazon;
                    document.getElementById('ancho_razon_input').value = anchoRazon;
                    updateRazonMarkerWidth(anchoRazon);
                    
                    // Aplicar alineación de razón
                    const alineacionRazon = config.alineacion_razon || 'justified';
                    const selectAlineacion = document.getElementById('alineacion_razon');
                    if (selectAlineacion) selectAlineacion.value = alineacionRazon;
                    updateRazonMarkerAlignment();
                    
                    // Aplicar configuración de destacado
                    document.getElementById('posicion_destacado_x').value = config.destacado_posicion_x || 50;
                    document.getElementById('posicion_destacado_y').value = config.destacado_posicion_y || 50;
                    document.getElementById('tamanio_destacado').value = config.destacado_tamanio || 100;
                    
                    const destacadoTipo = document.getElementById('destacado_tipo');
                    if (destacadoTipo) destacadoTipo.value = config.destacado_tipo || 'icono';
                    toggleDestacadoOptions();
                    
                    // Seleccionar icono de destacado
                    if (config.destacado_icono) {
                        const iconoRadio = document.querySelector(`input[name="destacado_icono"][value="${config.destacado_icono}"]`);
                        if (iconoRadio) iconoRadio.checked = true;
                    }
                    updateDestacadoMarkerImage();
                    
                    // Mostrar imagen personalizada si existe
                    // Mostrar imagen personalizada si existe
                    if (config.destacado_imagen) {
                        showExistingDestacadoImg(config.destacado_imagen);
                    }
                    
                    // Aplicar variables habilitadas
                    if (config.variables_habilitadas) {
                        try {
                            const vars = JSON.parse(config.variables_habilitadas);
                            document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                                cb.checked = vars.includes(cb.value);
                            });
                        } catch (e) {
                            console.error('Error parsing variables:', e);
                        }
                    }
                    
                    // Actualizar marcadores en el canvas
                    // Primero actualizar tamaños, luego posiciones
                    updateMarkerSizes();
                    updateMarkerPositions();
                    updateMarkersVisibility();
                    updateRazonMarkerText();
                    updateDestacadoMarkerImage();
                    
                    console.log('DEBUG - Destacado cargado:', {
                        posX: config.destacado_posicion_x,
                        posY: config.destacado_posicion_y,
                        tamanio: config.destacado_tamanio,
                        icono: config.destacado_icono
                    });
                    
                    // Actualizar fuentes en los selectores
                    document.querySelectorAll('.font-select').forEach(select => {
                        const fontMap = {
                            'Roboto-Regular': 'Roboto',
                            'OpenSans': 'OpenSans',
                            'Lato-Regular': 'Lato',
                            'Montserrat': 'Montserrat',
                            'Poppins-Regular': 'Poppins',
                            'PlayfairDisplay': 'PlayfairDisplay',
                            'Motterdam': 'Motterdam'
                        };
                        const fontName = fontMap[select.value] || 'Roboto';
                        select.style.fontFamily = `'${fontName}', sans-serif`;
                    });
                    
                    console.log('Configuración de plantilla cargada:', templateId);
                }
            } catch (error) {
                console.error('Error al cargar configuración de plantilla:', error);
            }
        }
        
        // Guardar configuración de la plantilla activa
        async function saveTemplateConfig() {
            if (!activeTemplateId) {
                console.log('No hay plantilla activa para guardar configuración');
                return false;
            }
            
            try {
                // Recopilar variables habilitadas
                const variablesHabilitadas = [];
                document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
                    variablesHabilitadas.push(cb.value);
                });
                
                const formData = new FormData();
                formData.append('action', 'save_config');
                formData.append('grupo_id', grupoId);
                formData.append('plantilla_id', activeTemplateId);
                formData.append('posicion_nombre_x', document.getElementById('posicion_nombre_x').value);
                formData.append('posicion_nombre_y', document.getElementById('posicion_nombre_y').value);
                formData.append('posicion_razon_x', document.getElementById('posicion_razon_x').value);
                formData.append('posicion_razon_y', document.getElementById('posicion_razon_y').value);
                formData.append('posicion_fecha_x', document.getElementById('posicion_fecha_x').value);
                formData.append('posicion_fecha_y', document.getElementById('posicion_fecha_y').value);
                formData.append('posicion_qr_x', document.getElementById('posicion_qr_x').value);
                formData.append('posicion_qr_y', document.getElementById('posicion_qr_y').value);
                formData.append('posicion_firma_x', document.getElementById('posicion_firma_x').value);
                formData.append('posicion_firma_y', document.getElementById('posicion_firma_y').value);
                formData.append('fuente_nombre', document.getElementById('fuente_nombre')?.value || 'Roboto-Regular');
                formData.append('formato_nombre', document.getElementById('formato_nombre')?.value || 'mayusculas');
                formData.append('fuente_razon', document.getElementById('fuente_razon')?.value || 'Roboto-Regular');
                formData.append('fuente_fecha', document.getElementById('fuente_fecha')?.value || 'Roboto-Regular');
                formData.append('tamanio_fuente', document.getElementById('tamanio_fuente').value);
                formData.append('tamanio_razon', document.getElementById('tamanio_razon').value);
                formData.append('tamanio_fecha', document.getElementById('tamanio_fecha').value);
                formData.append('tamanio_qr', document.getElementById('tamanio_qr').value);
                formData.append('tamanio_firma', document.getElementById('tamanio_firma').value);
                formData.append('color_texto', document.getElementById('color_texto').value);
                formData.append('color_razon', document.getElementById('color_razon').value);
                formData.append('color_fecha', document.getElementById('color_fecha').value);
                formData.append('razon_defecto', document.getElementById('razon_defecto')?.value || '');
                formData.append('formato_fecha', document.getElementById('formato_fecha')?.value || 'd de F de Y');
                
                // Fecha específica
                if (document.getElementById('usar_fecha_especifica')?.checked) {
                    formData.append('usar_fecha_especifica', '1');
                }
                formData.append('fecha_especifica', document.getElementById('fecha_especifica')?.value || '');
                
                formData.append('ancho_razon', document.getElementById('ancho_razon').value);
                formData.append('lineas_razon', document.getElementById('lineas_razon')?.value || 0);
                formData.append('alineacion_razon', document.getElementById('alineacion_razon')?.value || 'justified');
                formData.append('variables_habilitadas', JSON.stringify(variablesHabilitadas));
                
                // Campos de destacado
                formData.append('posicion_destacado_x', document.getElementById('posicion_destacado_x').value);
                formData.append('posicion_destacado_y', document.getElementById('posicion_destacado_y').value);
                formData.append('tamanio_destacado', document.getElementById('tamanio_destacado')?.value || 100);
                formData.append('destacado_tipo', document.getElementById('destacado_tipo')?.value || 'icono');
                const iconoSeleccionado = document.querySelector('input[name="destacado_icono"]:checked');
                formData.append('destacado_icono', iconoSeleccionado?.value || 'estrella');
                
                // Enviar imagen personalizada si existe
                const destacadoImagenInput = document.getElementById('destacado_imagen_input');
                if (destacadoImagenInput && destacadoImagenInput.files.length > 0) {
                    formData.append('destacado_imagen_file', destacadoImagenInput.files[0]);
                }
                // También enviar el nombre de la imagen actual si existe
                const currentDestacadoImg = document.querySelector('#currentDestacadoImg img');
                if (currentDestacadoImg && !destacadoImagenInput?.files?.length) {
                    // Extraer el nombre del archivo de la URL
                    const imgSrc = currentDestacadoImg.src;
                    const filename = imgSrc.split('/').pop();
                    if (filename && !filename.startsWith('data:')) {
                        formData.append('destacado_imagen_actual', filename);
                    }
                }
                
                const response = await fetch('api_grupo_plantillas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('Configuración de plantilla guardada:', activeTemplateId);
                    return true;
                } else {
                    console.error('Error al guardar config plantilla:', data.message);
                    return false;
                }
            } catch (error) {
                console.error('Error al guardar configuración de plantilla:', error);
                return false;
            }
        }
        
        // Seleccionar una plantilla como activa
        async function selectTemplate(templateId) {
            try {
                const formData = new FormData();
                formData.append('action', 'set_active');
                formData.append('grupo_id', grupoId);
                formData.append('plantilla_id', templateId);
                
                const response = await fetch('api_grupo_plantillas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar estado local
                    currentTemplates.forEach(t => {
                        t.es_activa = (t.id == templateId) ? 1 : 0;
                    });
                    activeTemplateId = templateId;
                    
                    // Re-renderizar slider
                    renderTemplatesSlider();
                    
                    // Cargar la configuración específica de esta plantilla
                    await loadTemplateConfig(templateId);
                    
                    // Mostrar mensaje de éxito
                    showNotification('Plantilla seleccionada correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al seleccionar la plantilla', 'error');
            }
        }
        
        // Eliminar una plantilla
        async function deleteTemplate(templateId) {
            if (!confirm('¿Estás seguro de eliminar esta plantilla?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('grupo_id', grupoId);
                formData.append('plantilla_id', templateId);
                
                const response = await fetch('api_grupo_plantillas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Recargar slider
                    await loadTemplatesSlider();
                    showNotification('Plantilla eliminada', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al eliminar la plantilla', 'error');
            }
        }
        
        // Subir nueva plantilla
        async function uploadNewTemplate(file) {
            // Verificar que no se exceda el máximo
            if (currentTemplates.length >= MAX_TEMPLATES) {
                showNotification(`Máximo ${MAX_TEMPLATES} plantillas permitidas`, 'error');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'upload');
                formData.append('grupo_id', grupoId);
                formData.append('plantilla', file);
                formData.append('nombre', file.name.replace(/\.[^/.]+$/, ''));
                
                showNotification('Subiendo plantilla...', 'info');
                
                const response = await fetch('api_grupo_plantillas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Agregar al array local
                    currentTemplates.push(data.plantilla);
                    
                    // Activar la plantilla recién subida para que se muestre en el canvas
                    await selectTemplate(data.plantilla.id);
                    
                    showNotification('Plantilla subida correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al subir la plantilla', 'error');
            }
        }
        
        // Mostrar notificación temporal
        function showNotification(message, type = 'info') {
            // Remover notificación existente
            const existing = document.querySelector('.template-notification');
            if (existing) existing.remove();
            
            const colors = {
                success: '#27ae60',
                error: '#e74c3c',
                info: '#3498db'
            };
            
            const notification = document.createElement('div');
            notification.className = 'template-notification';
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Agregar estilos de animación
        const notificationStyles = document.createElement('style');
        notificationStyles.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(notificationStyles);
        
        // Manejo de archivos - Plantilla (nuevo sistema)
        const plantillaInput = document.getElementById('plantillaUpload');
        const templatesSlider = document.getElementById('templatesSlider');
        
        plantillaInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                uploadNewTemplate(e.target.files[0]);
                // Limpiar input para permitir subir el mismo archivo
                e.target.value = '';
            }
        });
        
        // Drag & Drop para el slider
        templatesSlider.addEventListener('dragover', (e) => {
            e.preventDefault();
            templatesSlider.style.background = '#e8f4f8';
            templatesSlider.style.borderColor = '#3498db';
        });
        
        templatesSlider.addEventListener('dragleave', () => {
            templatesSlider.style.background = '';
            templatesSlider.style.borderColor = '';
        });
        
        templatesSlider.addEventListener('drop', (e) => {
            e.preventDefault();
            templatesSlider.style.background = '';
            templatesSlider.style.borderColor = '';
            
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/')) {
                    uploadNewTemplate(file);
                } else {
                    showNotification('Por favor, arrastra una imagen válida', 'error');
                }
            }
        });
        
        // Event listener para el botón inicial de agregar plantilla
        const initialAddBtn = document.getElementById('templateAddBtn');
        if (initialAddBtn) {
            initialAddBtn.addEventListener('click', () => {
                plantillaInput.click();
            });
        }
        
        // Función para previsualizar nueva plantilla (ya no necesaria, pero la mantenemos para compatibilidad)
        function previewNewTemplate(file) {
            const canvas = document.getElementById('canvas');
            const reader = new FileReader();
            
            reader.onload = function(e) {
                canvas.src = e.target.result;
                canvas.onload = function() {
                    updateCanvasScale();
                    updateMarkerPositions();
                    updateMarkersVisibility();
                };
            };
            
            reader.readAsDataURL(file);
        }
        
        // Manejo de archivos - Firma
        const uploadFirmaArea = document.getElementById('uploadFirmaArea');
        const firmaInput = document.getElementById('firma_imagen');
        
        uploadFirmaArea.addEventListener('click', () => firmaInput.click());
        
        uploadFirmaArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadFirmaArea.classList.add('dragover');
        });
        
        uploadFirmaArea.addEventListener('dragleave', () => {
            uploadFirmaArea.classList.remove('dragover');
        });
        
        uploadFirmaArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadFirmaArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                firmaInput.files = e.dataTransfer.files;
                showFileName('firma', e.dataTransfer.files[0].name);
            }
        });
        
        firmaInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                showFirmaPreview(e.target.files[0]);
            }
        });
        
        function showFirmaPreview(file) {
            const container = document.getElementById('currentFirma');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML = `
                        <div class="firma-preview">
                            <img src="${e.target.result}" alt="Vista previa de firma">
                            <div class="firma-info">
                                <div class="firma-filename">${file.name}</div>
                                <small>Nueva imagen seleccionada</small>
                            </div>
                            <button type="button" class="btn-remove-firma" onclick="clearFile('firma')">
                                <i class="fas fa-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
        
        function showExistingFirma(filename) {
            const container = document.getElementById('currentFirma');
            // La firma se guarda en assets/firmas/
            const imagePath = `${basePath}assets/firmas/${filename}`;
            
            container.innerHTML = `
                <div class="firma-preview">
                    <img src="${imagePath}" alt="Firma actual" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2250%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22100%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2212%22 fill=%22%23999%22%3ENo encontrada%3C/text%3E%3C/svg%3E'">
                    <div class="firma-info">
                        <div class="firma-filename">${filename}</div>
                        <small>Imagen actual</small>
                    </div>
                    <button type="button" class="btn-remove-firma" onclick="clearFile('firma')">
                        <i class="fas fa-trash"></i> Quitar
                    </button>
                </div>
            `;
        }
        
        function showFileName(type, filename) {
            if (type === 'firma') {
                showExistingFirma(filename);
            }
        }
        
        function clearFile(type) {
            if (type === 'firma') {
                document.getElementById('firma_imagen').value = '';
                document.getElementById('currentFirma').innerHTML = '';
            } else if (type === 'destacado') {
                document.getElementById('destacado_imagen_input').value = '';
                document.getElementById('currentDestacadoImg').innerHTML = '';
            }
        }
        
        // Manejo de archivos - Imagen personalizada de Destacado
        const uploadDestacadoArea = document.getElementById('uploadDestacadoArea');
        const destacadoImagenInput = document.getElementById('destacado_imagen_input');
        
        if (uploadDestacadoArea && destacadoImagenInput) {
            uploadDestacadoArea.addEventListener('click', () => destacadoImagenInput.click());
            
            uploadDestacadoArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadDestacadoArea.classList.add('dragover');
            });
            
            uploadDestacadoArea.addEventListener('dragleave', () => {
                uploadDestacadoArea.classList.remove('dragover');
            });
            
            uploadDestacadoArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadDestacadoArea.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    destacadoImagenInput.files = e.dataTransfer.files;
                    showDestacadoPreview(e.dataTransfer.files[0]);
                }
            });
            
            destacadoImagenInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    showDestacadoPreview(e.target.files[0]);
                }
            });
        }
        
        function showDestacadoPreview(file) {
            const container = document.getElementById('currentDestacadoImg');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML = `
                        <div class="sticker-preview">
                            <img src="${e.target.result}" alt="Vista previa del sticker">
                            <div class="sticker-info">
                                <div class="sticker-filename">${file.name}</div>
                                <small>Nueva imagen seleccionada</small>
                            </div>
                            <button type="button" class="btn-remove-sticker" onclick="clearFile('destacado')">
                                <i class="fas fa-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                    // También actualizar el marcador en el canvas
                    updateDestacadoMarkerWithCustomImage(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
        
        function showExistingDestacadoImg(filename) {
            const container = document.getElementById('currentDestacadoImg');
            const imagePath = `${basePath}uploads/stickers/${filename}`;
            
            container.innerHTML = `
                <div class="sticker-preview">
                    <img src="${imagePath}" alt="Sticker actual">
                    <div class="sticker-info">
                        <div class="sticker-filename">${filename}</div>
                        <small>Imagen actual</small>
                    </div>
                    <button type="button" class="btn-remove-sticker" onclick="clearFile('destacado')">
                        <i class="fas fa-trash"></i> Quitar
                    </button>
                </div>
            `;
            // También actualizar el marcador en el canvas
            updateDestacadoMarkerWithCustomImage(imagePath);
        }
        
        function updateDestacadoMarkerWithCustomImage(imageSrc) {
            const markerImg = document.getElementById('destacadoMarkerImg');
            if (markerImg) {
                markerImg.src = imageSrc;
            }
        }

        async function loadGrupoConfig() {
            try {
                const response = await fetch(`api_grupo_config.php?action=get&id=${grupoId}`);
                const data = await response.json();
                
                if (data.success) {
                    const grupo = data.grupo;
                    
                    // Guardar nombre del grupo para usarlo en variables
                    grupoNombreActual = grupo.nombre || 'Nombre del Grupo';
                    
                    // Cargar datos del primer estudiante del grupo
                    try {
                        const respEstudiante = await fetch(`api_estudiantes.php?action=primer_estudiante&grupo_id=${grupoId}`);
                        const dataEstudiante = await respEstudiante.json();
                        if (dataEstudiante.success && dataEstudiante.estudiante) {
                            primerEstudianteNombre = dataEstudiante.estudiante.nombre_completo || 'Juan Pérez García';
                            primerEstudianteCategoria = dataEstudiante.estudiante.categoria_nombre || 'Nombre de Categoría';
                            console.log('Primer estudiante cargado:', primerEstudianteNombre, primerEstudianteCategoria);
                            // Actualizar marcadores del lienzo
                            updateNombreMarkerText();
                            updateRazonMarkerText();
                        }
                    } catch (e) {
                        console.log('No se pudo cargar el primer estudiante, usando valores por defecto');
                    }
                    
                    // Actualizar header
                    document.getElementById('grupoNombre').textContent = grupo.nombre;
                    document.getElementById('grupoDescripcion').textContent = grupo.descripcion || '';
                    const iconElem = document.getElementById('grupoIcon');
                    iconElem.textContent = grupo.icono || '📁';
                    iconElem.style.backgroundColor = grupo.color;
                    
                    // Cargar configuración - con validación de existencia
                    const setValueIfExists = (id, value) => {
                        const elem = document.getElementById(id);
                        if (elem) elem.value = value;
                    };
                    
                    setValueIfExists('razon_defecto', grupo.razon_defecto || '');
                    setValueIfExists('firma_nombre', grupo.firma_nombre || '');
                    setValueIfExists('firma_cargo', grupo.firma_cargo || '');
                    setValueIfExists('fuente_nombre', grupo.fuente_nombre || 'Roboto-Regular');
                    
                    // Restaurar formato del nombre
                    const formatoNombre = grupo.formato_nombre || 'mayusculas';
                    setValueIfExists('formato_nombre', formatoNombre);
                    setFormatoNombre(formatoNombre);
                    
                    setValueIfExists('tamanio_fuente', grupo.tamanio_fuente || 48);
                    setValueIfExists('color_texto', grupo.color_texto || '#000000');
                    setValueIfExists('color_texto_hex', grupo.color_texto || '#000000');
                    setValueIfExists('posicion_nombre_x', grupo.posicion_nombre_x || 400);
                    setValueIfExists('posicion_nombre_y', grupo.posicion_nombre_y || 300);
                    setValueIfExists('posicion_razon_x', grupo.posicion_razon_x || 400);
                    setValueIfExists('posicion_razon_y', grupo.posicion_razon_y || 360);
                    setValueIfExists('posicion_qr_x', grupo.posicion_qr_x || 920);
                    setValueIfExists('posicion_qr_y', grupo.posicion_qr_y || 419);
                    setValueIfExists('posicion_firma_x', grupo.posicion_firma_x || 800);
                    setValueIfExists('posicion_firma_y', grupo.posicion_firma_y || 850);
                    setValueIfExists('posicion_fecha_x', grupo.posicion_fecha_x || 400);
                    setValueIfExists('posicion_fecha_y', grupo.posicion_fecha_y || 420);
                    setValueIfExists('tamanio_qr', grupo.tamanio_qr || 200);
                    setValueIfExists('tamanio_firma', grupo.tamanio_firma || 150);
                    
                    // Configuración de Razón
                    setValueIfExists('fuente_razon', grupo.fuente_razon || 'Roboto-Regular');
                    setValueIfExists('tamanio_razon', grupo.tamanio_razon || 24);
                    setValueIfExists('color_razon', grupo.color_razon || '#333333');
                    setValueIfExists('color_razon_hex', grupo.color_razon || '#333333');
                    setValueIfExists('ancho_razon', grupo.ancho_razon || 600);
                    setValueIfExists('ancho_razon_input', grupo.ancho_razon || 600);
                    setValueIfExists('lineas_razon', grupo.lineas_razon || 0);
                    setValueIfExists('alineacion_razon', grupo.alineacion_razon || 'justified');
                    updateRazonMarkerAlignment();
                    
                    // Configuración de Fecha
                    setValueIfExists('fuente_fecha', grupo.fuente_fecha || 'Roboto-Regular');
                    setValueIfExists('formato_fecha', grupo.formato_fecha || 'd de F de Y');
                    setValueIfExists('tamanio_fecha', grupo.tamanio_fecha || 20);
                    setValueIfExists('color_fecha', grupo.color_fecha || '#333333');
                    setValueIfExists('color_fecha_hex', grupo.color_fecha || '#333333');
                    
                    // Configuración de fecha específica
                    const usarFechaEsp = document.getElementById('usar_fecha_especifica');
                    const fechaEspGroup = document.getElementById('fecha_especifica_group');
                    if (usarFechaEsp) {
                        usarFechaEsp.checked = grupo.usar_fecha_especifica == 1;
                        if (fechaEspGroup) {
                            fechaEspGroup.style.display = usarFechaEsp.checked ? 'block' : 'none';
                        }
                    }
                    setValueIfExists('fecha_especifica', grupo.fecha_especifica || '');
                    
                    // Cargar variables habilitadas
                    let variablesHabilitadas = ['nombre', 'razon', 'fecha', 'qr', 'firma'];
                    if (grupo.variables_habilitadas) {
                        try {
                            variablesHabilitadas = JSON.parse(grupo.variables_habilitadas);
                        } catch(e) {
                            console.error('Error parsing variables_habilitadas:', e);
                        }
                    }
                    
                    // Marcar checkboxes
                    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                        cb.checked = variablesHabilitadas.includes(cb.value);
                    });
                    
                    // Inicializar eventos de checkboxes
                    initCheckboxEvents();
                    
                    // Actualizar posiciones de marcadores
                    updateMarkerPositions();
                    
                    // Actualizar visibilidad de marcadores
                    updateMarkersVisibility();
                    
                    // Actualizar texto del marcador de razón
                    updateRazonMarkerText();
                    
                    // Actualizar ancho del marcador de razón
                    const anchoRazonValue = grupo.ancho_razon || 600;
                    updateRazonMarkerWidth(anchoRazonValue);
                    
                    // Actualizar texto del marcador de fecha
                    actualizarTextoFechaMarker();
                    
                    // Cargar slider de plantillas
                    await loadTemplatesSlider();
                    
                    // Mostrar imagen de firma si existe
                    if (grupo.firma_imagen) {
                        showExistingFirma(grupo.firma_imagen);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al cargar la configuración del grupo', 'error');
            }
        }
        
        function resetDefaults() {
            if (confirm('¿Restablecer a valores predeterminados?')) {
                const setValueIfExists = (id, value) => {
                    const elem = document.getElementById(id);
                    if (elem) elem.value = value;
                };
                
                setValueIfExists('fuente_nombre', 'Roboto-Regular');
                setValueIfExists('formato_nombre', 'mayusculas');
                setFormatoNombre('mayusculas');
                setValueIfExists('tamanio_fuente', 48);
                setValueIfExists('color_texto', '#000000');
                setValueIfExists('color_texto_hex', '#000000');
                setValueIfExists('posicion_nombre_x', 400);
                setValueIfExists('posicion_nombre_y', 300);
                setValueIfExists('posicion_razon_x', 400);
                setValueIfExists('posicion_razon_y', 360);
                setValueIfExists('posicion_qr_x', 920);
                setValueIfExists('posicion_qr_y', 419);
                setValueIfExists('posicion_firma_x', 800);
                setValueIfExists('posicion_firma_y', 850);
                setValueIfExists('posicion_fecha_x', 400);
                setValueIfExists('posicion_fecha_y', 420);
                setValueIfExists('tamanio_qr', 200);
                setValueIfExists('tamanio_firma', 150);
                
                // Configuración de Razón
                setValueIfExists('fuente_razon', 'Roboto-Regular');
                setValueIfExists('tamanio_razon', 24);
                setValueIfExists('color_razon', '#333333');
                setValueIfExists('color_razon_hex', '#333333');
                setValueIfExists('ancho_razon', 600);
                setValueIfExists('ancho_razon_input', 600);
                
                // Configuración de Fecha
                setValueIfExists('fuente_fecha', 'Roboto-Regular');
                setValueIfExists('formato_fecha', 'd de F de Y');
                setValueIfExists('tamanio_fecha', 20);
                setValueIfExists('color_fecha', '#333333');
                setValueIfExists('color_fecha_hex', '#333333');
                
                // Restablecer fecha específica
                const usarFechaEsp = document.getElementById('usar_fecha_especifica');
                const fechaEspGroup = document.getElementById('fecha_especifica_group');
                if (usarFechaEsp) usarFechaEsp.checked = false;
                if (fechaEspGroup) fechaEspGroup.style.display = 'none';
                setValueIfExists('fecha_especifica', '');
                
                // Restablecer checkboxes
                document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = true;
                });
                
                updateMarkerPositions();
                updateMarkersVisibility();
                actualizarTextoFechaMarker();
            }
        }
        
        document.getElementById('configForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Recopilar variables habilitadas
            const variablesHabilitadas = [];
            document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
                variablesHabilitadas.push(cb.value);
            });
            document.getElementById('variables_habilitadas').value = JSON.stringify(variablesHabilitadas);
            
            const formData = new FormData(e.target);
            formData.append('action', 'update_config');
            
            try {
                // Guardar configuración del grupo
                const response = await fetch('api_grupo_config.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // También guardar la configuración de la plantilla activa
                    if (activeTemplateId) {
                        await saveTemplateConfig();
                    }
                    
                    showNotification('Configuración guardada correctamente', 'success');
                    loadGrupoConfig();
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo guardar la configuración'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al guardar la configuración', 'error');
            }
        });
        
        // Sincronizar color picker con hex input
        document.getElementById('color_texto').addEventListener('input', (e) => {
            document.getElementById('color_texto_hex').value = e.target.value;
        });
        
        document.getElementById('color_texto_hex').addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                document.getElementById('color_texto').value = e.target.value;
            }
        });
        
        // Cargar configuración al iniciar
        loadGrupoConfig();
        
        // Inicializar drag & drop cuando se cargue todo
        if (document.getElementById('canvas').complete) {
            updateMarkerPositions();
            initDragDrop();
            initRazonResize();
            updateMarkersVisibility();
            initCheckboxEvents();
        }
        
        // Funciones de previsualización
        async function previewCertificate() {
            const modal = document.getElementById('previewModal');
            const body = document.getElementById('previewBody');
            
            // Mostrar modal con loading
            modal.classList.add('active');
            body.innerHTML = `
                <div class="preview-loading">
                    <i class="fas fa-spinner"></i>
                    <p>Generando vista previa...</p>
                </div>
            `;
            
            try {
                const formData = new FormData();
                formData.append('tipo', 'grupo');
                formData.append('id', grupoId);
                formData.append('use_form_data', '1'); // Indicar que use datos del formulario
                
                // Posiciones de elementos
                formData.append('posicion_nombre_x', document.getElementById('posicion_nombre_x')?.value || 400);
                formData.append('posicion_nombre_y', document.getElementById('posicion_nombre_y')?.value || 300);
                formData.append('posicion_razon_x', document.getElementById('posicion_razon_x')?.value || 400);
                formData.append('posicion_razon_y', document.getElementById('posicion_razon_y')?.value || 360);
                formData.append('posicion_fecha_x', document.getElementById('posicion_fecha_x')?.value || 400);
                formData.append('posicion_fecha_y', document.getElementById('posicion_fecha_y')?.value || 420);
                formData.append('posicion_qr_x', document.getElementById('posicion_qr_x')?.value || 920);
                formData.append('posicion_qr_y', document.getElementById('posicion_qr_y')?.value || 419);
                formData.append('posicion_firma_x', document.getElementById('posicion_firma_x')?.value || 800);
                formData.append('posicion_firma_y', document.getElementById('posicion_firma_y')?.value || 850);
                formData.append('posicion_destacado_x', document.getElementById('posicion_destacado_x')?.value || 50);
                formData.append('posicion_destacado_y', document.getElementById('posicion_destacado_y')?.value || 50);
                
                // Configuración de texto
                formData.append('fuente_nombre', document.getElementById('fuente_nombre')?.value || 'Roboto-Regular');
                formData.append('formato_nombre', document.getElementById('formato_nombre')?.value || 'mayusculas');
                formData.append('tamanio_fuente', document.getElementById('tamanio_fuente')?.value || 48);
                formData.append('color_texto', document.getElementById('color_texto')?.value || '#000000');
                formData.append('tamanio_qr', document.getElementById('tamanio_qr')?.value || 200);
                formData.append('tamanio_firma', document.getElementById('tamanio_firma')?.value || 150);
                
                // Configuración de Razón
                formData.append('razon_defecto', document.getElementById('razon_defecto')?.value || '');
                formData.append('fuente_razon', document.getElementById('fuente_razon')?.value || 'Roboto-Regular');
                formData.append('tamanio_razon', document.getElementById('tamanio_razon')?.value || 24);
                formData.append('color_razon', document.getElementById('color_razon')?.value || '#333333');
                formData.append('ancho_razon', document.getElementById('ancho_razon')?.value || 600);
                formData.append('lineas_razon', document.getElementById('lineas_razon')?.value || 0);
                formData.append('alineacion_razon', document.getElementById('alineacion_razon')?.value || 'justified');
                
                // Configuración de Fecha
                const formatoFechaValue = document.getElementById('formato_fecha')?.value || 'd de F de Y';
                console.log('DEBUG Preview Grupo - formato_fecha:', formatoFechaValue);
                formData.append('formato_fecha', formatoFechaValue);
                formData.append('fuente_fecha', document.getElementById('fuente_fecha')?.value || 'Roboto-Regular');
                formData.append('tamanio_fecha', document.getElementById('tamanio_fecha')?.value || 20);
                formData.append('color_fecha', document.getElementById('color_fecha')?.value || '#333333');
                
                // Configuración de Destacado
                formData.append('tamanio_destacado', document.getElementById('tamanio_destacado')?.value || 100);
                formData.append('destacado_tipo', document.getElementById('destacado_tipo')?.value || 'icono');
                const iconoDestacado = document.querySelector('input[name="destacado_icono"]:checked');
                formData.append('destacado_icono', iconoDestacado?.value || 'estrella');
                
                // Enviar imagen personalizada para preview si existe
                const destacadoTipo = document.getElementById('destacado_tipo')?.value || 'icono';
                if (destacadoTipo === 'imagen') {
                    const destacadoImagenInput = document.getElementById('destacado_imagen_input');
                    if (destacadoImagenInput && destacadoImagenInput.files.length > 0) {
                        // Nueva imagen seleccionada - enviar el archivo
                        formData.append('destacado_imagen_file', destacadoImagenInput.files[0]);
                    } else {
                        // Imagen ya guardada - enviar el nombre del archivo
                        const currentImg = document.querySelector('#currentDestacadoImg img');
                        if (currentImg && currentImg.src) {
                            const filename = currentImg.src.split('/').pop();
                            if (filename && !filename.startsWith('data:')) {
                                formData.append('destacado_imagen', filename);
                            }
                        }
                    }
                }
                
                // Debug destacado
                const destacadoImgInput = document.getElementById('destacado_imagen_input');
                const currentDestacadoImg = document.querySelector('#currentDestacadoImg img');
                console.log('DEBUG Preview - Destacado:', {
                    posX: document.getElementById('posicion_destacado_x')?.value,
                    posY: document.getElementById('posicion_destacado_y')?.value,
                    tamanio: document.getElementById('tamanio_destacado')?.value,
                    tipo: destacadoTipo,
                    icono: iconoDestacado?.value,
                    tieneArchivoNuevo: destacadoImgInput?.files?.length > 0,
                    imagenActual: currentDestacadoImg?.src?.split('/').pop()
                });
                
                // Variables habilitadas
                const variablesHabilitadas = [];
                document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
                    variablesHabilitadas.push(cb.value);
                });
                console.log('DEBUG Preview Grupo - variables habilitadas:', variablesHabilitadas);
                formData.append('variables_habilitadas', JSON.stringify(variablesHabilitadas));
                
                const response = await fetch('api_preview.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    body.innerHTML = `
                        <img src="${data.preview_url}" alt="Vista previa del certificado" style="max-width: 100%;">
                        <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
                            <i class="fas fa-info-circle"></i> 
                            Esta es una vista previa con datos de ejemplo
                        </p>
                    `;
                } else {
                    body.innerHTML = `
                        <div style="padding: 40px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                            <p style="margin-top: 15px;">${data.error || 'Error al generar la vista previa'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                body.innerHTML = `
                    <div style="padding: 40px; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                        <p style="margin-top: 15px;">Error de conexión al generar la vista previa</p>
                    </div>
                `;
            }
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').classList.remove('active');
        }
        
        // Cerrar modal con Escape o click fuera
        document.getElementById('previewModal').addEventListener('click', (e) => {
            if (e.target.id === 'previewModal') {
                closePreviewModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closePreviewModal();
            }
        });
    </script>
</body>
</html>
