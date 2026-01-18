<?php
require_once '../config/database.php';

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
    <title>Configurar Plantilla de la Categoria - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Cargar fuentes personalizadas */
        @font-face {
            font-family: 'Roboto';
            src: url('../assets/fonts/Roboto-Regular.ttf') format('truetype');
        }
        @font-face {
            font-family: 'OpenSans';
            src: url('../assets/fonts/OpenSans.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Lato';
            src: url('../assets/fonts/Lato-Regular.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('../assets/fonts/Montserrat.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonts/Poppins-Regular.ttf') format('truetype');
        }
        @font-face {
            font-family: 'PlayfairDisplay';
            src: url('../assets/fonts/PlayfairDisplay.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Motterdam';
            src: url('../assets/fonts/Motterdam.ttf') format('truetype');
        }
        
        /* Estilos para selectores de fuente */
        .font-select {
            font-size: 16px;
        }
        .font-select option[value="Roboto-Regular"] { font-family: 'Roboto', sans-serif; }
        .font-select option[value="OpenSans"] { font-family: 'OpenSans', sans-serif; }
        .font-select option[value="Lato-Regular"] { font-family: 'Lato', sans-serif; }
        .font-select option[value="Montserrat"] { font-family: 'Montserrat', sans-serif; }
        .font-select option[value="Poppins-Regular"] { font-family: 'Poppins', sans-serif; }
        .font-select option[value="PlayfairDisplay"] { font-family: 'PlayfairDisplay', serif; }
        .font-select option[value="Motterdam"] { font-family: 'Motterdam', cursive; }
        
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
            padding: 6px 10px;
            padding-right: 18px; /* Espacio para el handle */
            white-space: pre-wrap;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            text-align: justify;
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
        
        .Categoria-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .Categoria-icon-large {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .Categoria-info h2 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .Categoria-info p {
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
        
        /* Toggle para usar plantilla del grupo */
        .toggle-plantilla-grupo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            flex-shrink: 0;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background-color: #27ae60;
        }
        
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        .toggle-info {
            flex: 1;
        }
        
        .toggle-info strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .toggle-info small {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        /* Ocultar contenido de plantilla propia cuando se usa la del grupo */
        #configContent.hidden {
            display: none !important;
        }
        
        .grupo-plantilla-info {
            padding: 20px;
            background: #e8f4f8;
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }
        
        .grupo-plantilla-info i {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .grupo-plantilla-info p {
            color: #2c3e50;
            margin: 0;
        }
        
        .grupo-plantilla-info a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .grupo-plantilla-info a:hover {
            text-decoration: underline;
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
            
            // Mapeo de valores a nombres de fuente CSS
            const fontMap = {
                'Roboto-Regular': 'Roboto',
                'OpenSans': 'OpenSans',
                'Lato-Regular': 'Lato',
                'Montserrat': 'Montserrat',
                'Poppins-Regular': 'Poppins',
                'PlayfairDisplay': 'PlayfairDisplay',
                'Motterdam': 'Motterdam'
            };
            
            // Función para actualizar la fuente del select
            function updateSelectFont(select) {
                const fontName = fontMap[select.value] || 'Roboto';
                select.style.fontFamily = `'${fontName}', sans-serif`;
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
            <h1>Configurar Plantilla de la Categoria</h1>
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Inicio</a>
            </div>
        </header>

        <div class="main-content">
            <div class="Categoria-header" id="CategoriaHeader">
                <div class="Categoria-icon-large" id="CategoriaIcon">📁</div>
                <div class="Categoria-info">
                    <h2 id="CategoriaNombre">Cargando...</h2>
                    <p id="CategoriaDescripcion"></p>
                    <small id="GrupoPadre" style="color: #7f8c8d;"><i class="fas fa-folder"></i> Grupo: <span id="GrupoPadreNombre">-</span></small>
                </div>
            </div>

            <!-- Toggle para usar plantilla del grupo -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="toggle-plantilla-grupo">
                    <label class="toggle-switch">
                        <input type="checkbox" id="usarPlantillaGrupo" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="toggle-info">
                        <strong>Usar plantilla del grupo</strong>
                        <small>Cuando está activado, esta categoría usará la configuración de plantilla del grupo padre</small>
                    </div>
                </div>
            </div>

            <form id="configForm">
                <input type="hidden" id="categoria_id" name="categoria_id">
                <input type="hidden" id="usar_plantilla_propia" name="usar_plantilla_propia" value="0">
                <input type="hidden" id="variables_habilitadas" name="variables_habilitadas">
                <input type="hidden" id="posicion_nombre_x" name="posicion_nombre_x" value="400">
                <input type="hidden" id="posicion_nombre_y" name="posicion_nombre_y" value="300">
                <input type="hidden" id="posicion_razon_x" name="posicion_razon_x" value="400">
                <input type="hidden" id="posicion_razon_y" name="posicion_razon_y" value="360">
                <input type="hidden" id="ancho_razon" name="ancho_razon" value="600">
                <input type="hidden" id="posicion_qr_x" name="posicion_qr_x" value="920">
                <input type="hidden" id="posicion_qr_y" name="posicion_qr_y" value="419">
                <input type="hidden" id="posicion_firma_x" name="posicion_firma_x" value="800">
                <input type="hidden" id="posicion_firma_y" name="posicion_firma_y" value="850">
                <input type="hidden" id="posicion_fecha_x" name="posicion_fecha_x" value="400">
                <input type="hidden" id="posicion_fecha_y" name="posicion_fecha_y" value="420">
                
                <!-- Mensaje cuando se usa plantilla del grupo -->
                <div id="grupoPlantillaInfo" class="grupo-plantilla-info">
                    <i class="fas fa-folder-open"></i>
                    <p>Esta categoría está usando la plantilla del grupo padre.</p>
                    <p style="margin-top: 10px;">
                        <a href="#" id="linkConfigGrupo">
                            <i class="fas fa-cog"></i> Ver configuración del grupo
                        </a>
                    </p>
                </div>
                
                <!-- Contenido de configuración propia (se oculta cuando usa plantilla del grupo) -->
                <div id="configContent" class="hidden">
                <div class="config-grid">
                    <!-- Columna 1: Lienzo y Configuración -->
                    <div>
                        <div class="card">
                            <h3><i class="fas fa-palette"></i> Lienzo de Posicionamiento</h3>
                            
                            <!-- Slider de plantillas -->
                            <div class="templates-slider-container">
                                <div class="templates-slider-header">
                                    <h4>
                                        <i class="fas fa-images"></i> Plantillas de la Categoria
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
                            </div>
                            
                            <!-- Tab: Nombre -->
                            <div class="variable-tab-content active" id="tab-nombre">
                                <div class="form-group">
                                    <label for="fuente_nombre">Fuente</label>
                                    <select id="fuente_nombre" name="fuente_nombre" class="font-select">
                                        <option value="Roboto-Regular">Roboto</option>
                                        <option value="OpenSans">Open Sans</option>
                                        <option value="Lato-Regular">Lato</option>
                                        <option value="Montserrat">Montserrat</option>
                                        <option value="Poppins-Regular">Poppins</option>
                                        <option value="PlayfairDisplay">Playfair Display</option>
                                        <option value="Motterdam">Motterdam</option>
                                    </select>
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
                                        <option value="Roboto-Regular">Roboto</option>
                                        <option value="OpenSans">Open Sans</option>
                                        <option value="Lato-Regular">Lato</option>
                                        <option value="Montserrat">Montserrat</option>
                                        <option value="Poppins-Regular">Poppins</option>
                                        <option value="PlayfairDisplay">Playfair Display</option>
                                        <option value="Motterdam">Motterdam</option>
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
                                    <label for="color_razon">Color del Texto</label>
                                    <div class="color-preview">
                                        <input type="color" id="color_razon" name="color_razon" value="#333333">
                                        <input type="text" id="color_razon_hex" value="#333333" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="razon_defecto">Texto predeterminado</label>
                                    <textarea id="razon_defecto" name="razon_defecto" 
                                              style="min-height: 120px;" oninput="updateRazonMarkerText()">Por su destacada participación en el {Categoria} de {categoria}.</textarea>
                                    <small>Este texto aparecerá automáticamente al generar certificados. El texto se muestra en el lienzo para previsualizar su posición.</small>
                                </div>
                                <div class="variables-help">
                                    <strong><i class="fas fa-info-circle"></i> Variables disponibles:</strong>
                                    <div class="variables-list">
                                        <code>{Categoria}</code> <span>Nombre de la Categoria</span>
                                        <code>{categoria}</code> <span>Nombre de la Categoria</span>
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
                                        <option value="Roboto-Regular">Roboto</option>
                                        <option value="OpenSans">Open Sans</option>
                                        <option value="Lato-Regular">Lato</option>
                                        <option value="Montserrat">Montserrat</option>
                                        <option value="Poppins-Regular">Poppins</option>
                                        <option value="PlayfairDisplay">Playfair Display</option>
                                        <option value="Motterdam">Motterdam</option>
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
                        </div>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-preview" onclick="previewCertificate()"><i class="fas fa-eye"></i> Previsualizar</button>
                            <button type="button" class="btn" onclick="resetDefaults()"><i class="fas fa-undo"></i> Restablecer</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración</button>
                        </div>
                    </div>
                </div>
                </div><!-- fin configContent -->
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
        
        const urlParams = new URLSearchParams(window.location.search);
        const categoriaId = urlParams.get('id');
        
        if (!categoriaId) {
            alert('Categoria no especificado');
            window.location.href = 'index.php';
        }
        
        document.getElementById('categoria_id').value = categoriaId;
        
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
        
        // Actualizar texto del marcador razón en el lienzo
        function updateRazonMarkerText() {
            const razonTextarea = document.getElementById('razon_defecto');
            const razonMarkerText = document.getElementById('razonMarkerText');
            
            if (razonTextarea && razonMarkerText) {
                let texto = razonTextarea.value.trim();
                
                // Reemplazar variables con valores de ejemplo para previsualización
                texto = texto.replace(/\{Categoria\}/gi, 'Nombre de la Categoria');
                texto = texto.replace(/\{categoria\}/gi, 'Categoria');
                texto = texto.replace(/\{nombre\}/gi, 'Juan Pérez');
                texto = texto.replace(/\{fecha\}/gi, '2 de Diciembre de 2025');
                
                // Si el texto está vacío, mostrar placeholder
                if (!texto) {
                    texto = 'Escribe el texto de razón...';
                }
                
                razonMarkerText.textContent = texto;
            }
        }
        
        // Actualizar ancho del marcador razón
        function updateRazonMarkerWidth(realWidth) {
            const razonMarker = document.getElementById('razonMarker');
            if (razonMarker) {
                // Compensar el padding interno (4px izq + 16px der = 20px escalado)
                // El ancho del texto interno debe ser realWidth * scale
                // Entonces el ancho total del marcador debe ser mayor para compensar padding
                const paddingTotal = 20; // Espacio para padding izq/der (4 + 16)
                const scaledWidth = (realWidth * canvasScale) + paddingTotal;
                razonMarker.style.width = scaledWidth + 'px';
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
                
                // Actualizar el valor real (sin escala), compensando el padding (20px)
                const paddingTotal = 20;
                const realWidth = Math.round((newWidth - paddingTotal) / canvasScale);
                document.getElementById('ancho_razon').value = Math.max(100, realWidth);
                document.getElementById('ancho_razon_input').value = Math.max(100, realWidth);
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
            
            if (type === 'nombre' || type === 'razon' || type === 'fecha') {
                // Para nombre, razón y fecha: el punto está en la esquina superior izquierda del marcador
                // La posición CSS del marcador ya es la posición real
                realX = Math.round(x / canvasScale);
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
                let left = nombreX * canvasScale;
                let top = nombreY * canvasScale;
                // Asegurar que esté dentro del canvas
                left = Math.max(0, Math.min(left, canvasWidth - 20));
                top = Math.max(0, Math.min(top, canvasHeight - 20));
                nombreMarker.style.left = left + 'px';
                nombreMarker.style.top = top + 'px';
            }
            if (razonMarker) {
                let left = razonX * canvasScale;
                let top = razonY * canvasScale;
                
                // Aplicar ancho guardado (limitado al canvas)
                const anchoRazon = parseInt(document.getElementById('ancho_razon')?.value) || 600;
                let markerWidth = anchoRazon * canvasScale;
                
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
                
                console.log('Razón marker posicionado:', {left, top, markerWidth, canvasWidth, canvasHeight});
            }
            if (fechaMarker) {
                let left = fechaX * canvasScale;
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
            
            updateCoordinatesFromMarker('nombre', nombreX, nombreY);
            updateCoordinatesFromMarker('razon', razonX, razonY);
            updateCoordinatesFromMarker('fecha', fechaX, fechaY);
            updateCoordinatesFromMarker('qr', qrX, qrY);
            updateCoordinatesFromMarker('firma', firmaX, firmaY);
            
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
            }
            
            // Aplicar tamaño de fuente escalado al marcador Razón
            if (razonMarker) {
                // Usar factor 1.0 para mejor coincidencia con la previsualización real
                const fontSize = Math.max(8, tamanioRazon * canvasScale);
                razonMarker.style.fontSize = fontSize + 'px';
                razonMarker.style.lineHeight = '1.3';
                // Padding mínimo para no afectar mucho el ancho del texto
                razonMarker.style.paddingTop = '2px';
                razonMarker.style.paddingBottom = '2px';
                razonMarker.style.paddingLeft = '4px';
                razonMarker.style.paddingRight = '16px'; // Espacio para handle
                
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
            }
            
            // Aplicar tamaño de fuente escalado al marcador Fecha
            if (fechaMarker) {
                const fontSize = Math.max(8, tamanioFecha * canvasScale * 0.8);
                fechaMarker.style.fontSize = fontSize + 'px';
                const padding = Math.max(2, fontSize * 0.2);
                fechaMarker.style.padding = padding + 'px ' + (padding * 2) + 'px';
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
            
            // Agregar listener para cambio de fuente de razón
            const fuenteRazon = document.getElementById('fuente_razon');
            if (fuenteRazon) {
                fuenteRazon.addEventListener('change', () => {
                    updateMarkerSizes();
                });
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
            
            const nombreMarker = document.getElementById('nombreMarker');
            const razonMarker = document.getElementById('razonMarker');
            const fechaMarker = document.getElementById('fechaMarker');
            const qrMarker = document.getElementById('qrMarker');
            const firmaMarker = document.getElementById('firmaMarker');
            
            const coordNombreContainer = document.getElementById('coordNombreContainer');
            const coordRazonContainer = document.getElementById('coordRazonContainer');
            const coordFechaContainer = document.getElementById('coordFechaContainer');
            const coordQrContainer = document.getElementById('coordQrContainer');
            const coordFirmaContainer = document.getElementById('coordFirmaContainer');
            
            if (nombreMarker) nombreMarker.style.setProperty('display', nombreEnabled ? 'flex' : 'none', 'important');
            if (razonMarker) razonMarker.style.setProperty('display', razonEnabled ? 'flex' : 'none', 'important');
            if (fechaMarker) fechaMarker.style.setProperty('display', fechaEnabled ? 'flex' : 'none', 'important');
            if (qrMarker) qrMarker.style.setProperty('display', qrEnabled ? 'flex' : 'none', 'important');
            if (firmaMarker) firmaMarker.style.setProperty('display', firmaEnabled ? 'flex' : 'none', 'important');
            
            if (coordNombreContainer) coordNombreContainer.style.display = nombreEnabled ? 'block' : 'none';
            if (coordRazonContainer) coordRazonContainer.style.display = razonEnabled ? 'block' : 'none';
            if (coordFechaContainer) coordFechaContainer.style.display = fechaEnabled ? 'block' : 'none';
            if (coordQrContainer) coordQrContainer.style.display = qrEnabled ? 'block' : 'none';
            if (coordFirmaContainer) coordFirmaContainer.style.display = firmaEnabled ? 'block' : 'none';
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
        });
        
        // =====================================================
        // SLIDER DE PLANTILLAS CON NAVEGACIÓN
        // =====================================================
        let currentTemplates = [];
        let activeTemplateId = null;
        let sliderIndex = 0;
        const ITEMS_PER_VIEW = 3;
        const MAX_TEMPLATES = 6;
        
        // Cargar plantillas de la Categoria
        async function loadTemplatesSlider() {
            try {
                const response = await fetch(`api_categoria_plantillas.php?action=list&categoria_id=${categoriaId}`);
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
                
                const templatePath = `${basePath}uploads/categorias/${categoriaId}/${template.archivo}`;
                
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
                const templatePath = `${basePath}uploads/categorias/${categoriaId}/${activeTemplate.archivo}`;
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
                const response = await fetch(`api_categoria_plantillas.php?action=get_config&categoria_id=${categoriaId}&plantilla_id=${templateId}`);
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
                    updateMarkerPositions();
                    updateMarkerSizes();
                    updateMarkersVisibility();
                    updateRazonMarkerText();
                    
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
                formData.append('categoria_id', categoriaId);
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
                formData.append('ancho_razon', document.getElementById('ancho_razon').value);
                formData.append('variables_habilitadas', JSON.stringify(variablesHabilitadas));
                
                const response = await fetch('api_categoria_plantillas.php', {
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
                formData.append('categoria_id', categoriaId);
                formData.append('plantilla_id', templateId);
                
                const response = await fetch('api_categoria_plantillas.php', {
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
                formData.append('categoria_id', categoriaId);
                formData.append('plantilla_id', templateId);
                
                const response = await fetch('api_categoria_plantillas.php', {
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
                formData.append('categoria_id', categoriaId);
                formData.append('plantilla', file);
                formData.append('nombre', file.name.replace(/\.[^/.]+$/, ''));
                
                showNotification('Subiendo plantilla...', 'info');
                
                const response = await fetch('api_categoria_plantillas.php', {
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
            }
        }
        
        async function loadCategoriaConfig() {
            try {
                const response = await fetch(`api_categoria_config.php?action=get&id=${categoriaId}`);
                const data = await response.json();
                
                if (data.success) {
                    const Categoria = data.categoria;
                    
                    // Actualizar header
                    document.getElementById('CategoriaNombre').textContent = Categoria.nombre;
                    document.getElementById('CategoriaDescripcion').textContent = Categoria.descripcion || '';
                    const iconElem = document.getElementById('CategoriaIcon');
                    iconElem.textContent = Categoria.icono || '📁';
                    iconElem.style.backgroundColor = Categoria.color;
                    
                    // Mostrar info del grupo padre
                    document.getElementById('GrupoPadreNombre').textContent = Categoria.grupo_nombre || '-';
                    grupoId = Categoria.grupo_id;
                    
                    // Actualizar link al grupo
                    const linkConfigGrupo = document.getElementById('linkConfigGrupo');
                    if (linkConfigGrupo && grupoId) {
                        linkConfigGrupo.href = `config_grupo.php?id=${grupoId}`;
                    }
                    
                    // Establecer estado del toggle según usar_plantilla_propia
                    const usarPlantillaPropia = Categoria.usar_plantilla_propia == 1;
                    toggleUsarGrupo.checked = !usarPlantillaPropia;
                    updateToggleState();
                    
                    // Cargar configuración - con validación de existencia
                    const setValueIfExists = (id, value) => {
                        const elem = document.getElementById(id);
                        if (elem) elem.value = value;
                    };
                    
                    setValueIfExists('razon_defecto', Categoria.plantilla_razon_defecto || Categoria.razon_defecto || '');
                    setValueIfExists('firma_nombre', Categoria.plantilla_firma_nombre || Categoria.firma_nombre || '');
                    setValueIfExists('firma_cargo', Categoria.plantilla_firma_cargo || Categoria.firma_cargo || '');
                    setValueIfExists('fuente_nombre', Categoria.plantilla_fuente || Categoria.fuente_nombre || 'Roboto-Regular');
                    setValueIfExists('tamanio_fuente', Categoria.plantilla_tamanio_fuente || Categoria.tamanio_fuente || 48);
                    setValueIfExists('color_texto', Categoria.plantilla_color_texto || Categoria.color_texto || '#000000');
                    setValueIfExists('color_texto_hex', Categoria.plantilla_color_texto || Categoria.color_texto || '#000000');
                    setValueIfExists('posicion_nombre_x', Categoria.plantilla_pos_nombre_x || Categoria.posicion_nombre_x || 400);
                    setValueIfExists('posicion_nombre_y', Categoria.plantilla_pos_nombre_y || Categoria.posicion_nombre_y || 300);
                    setValueIfExists('posicion_razon_x', Categoria.plantilla_pos_razon_x || Categoria.posicion_razon_x || 400);
                    setValueIfExists('posicion_razon_y', Categoria.plantilla_pos_razon_y || Categoria.posicion_razon_y || 360);
                    setValueIfExists('posicion_qr_x', Categoria.plantilla_pos_qr_x || Categoria.posicion_qr_x || 920);
                    setValueIfExists('posicion_qr_y', Categoria.plantilla_pos_qr_y || Categoria.posicion_qr_y || 419);
                    setValueIfExists('posicion_firma_x', Categoria.plantilla_pos_firma_x || Categoria.posicion_firma_x || 800);
                    setValueIfExists('posicion_firma_y', Categoria.plantilla_pos_firma_y || Categoria.posicion_firma_y || 850);
                    setValueIfExists('posicion_fecha_x', Categoria.plantilla_pos_fecha_x || Categoria.posicion_fecha_x || 400);
                    setValueIfExists('posicion_fecha_y', Categoria.plantilla_pos_fecha_y || Categoria.posicion_fecha_y || 420);
                    setValueIfExists('tamanio_qr', Categoria.plantilla_tamanio_qr || Categoria.tamanio_qr || 200);
                    setValueIfExists('tamanio_firma', Categoria.plantilla_tamanio_firma || Categoria.tamanio_firma || 150);
                    
                    // Configuración de Razón
                    setValueIfExists('fuente_razon', Categoria.plantilla_fuente_razon || Categoria.fuente_razon || 'Roboto-Regular');
                    setValueIfExists('tamanio_razon', Categoria.plantilla_tamanio_razon || Categoria.tamanio_razon || 24);
                    setValueIfExists('color_razon', Categoria.plantilla_color_razon || Categoria.color_razon || '#333333');
                    setValueIfExists('color_razon_hex', Categoria.plantilla_color_razon || Categoria.color_razon || '#333333');
                    setValueIfExists('ancho_razon', Categoria.ancho_razon || 600);
                    setValueIfExists('ancho_razon_input', Categoria.ancho_razon || 600);
                    
                    // Configuración de Fecha
                    setValueIfExists('fuente_fecha', Categoria.plantilla_fuente_fecha || Categoria.fuente_fecha || 'Roboto-Regular');
                    setValueIfExists('formato_fecha', Categoria.plantilla_formato_fecha || Categoria.formato_fecha || 'd de F de Y');
                    setValueIfExists('tamanio_fecha', Categoria.plantilla_tamanio_fecha || Categoria.tamanio_fecha || 20);
                    setValueIfExists('color_fecha', Categoria.plantilla_color_fecha || Categoria.color_fecha || '#333333');
                    setValueIfExists('color_fecha_hex', Categoria.plantilla_color_fecha || Categoria.color_fecha || '#333333');
                    
                    // Cargar variables habilitadas
                    let variablesHabilitadas = ['nombre', 'razon', 'fecha', 'qr', 'firma'];
                    if (Categoria.plantilla_variables_habilitadas || Categoria.variables_habilitadas) {
                        try {
                            variablesHabilitadas = JSON.parse(Categoria.plantilla_variables_habilitadas || Categoria.variables_habilitadas);
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
                    const anchoRazonValue = Categoria.ancho_razon || 600;
                    updateRazonMarkerWidth(anchoRazonValue);
                    
                    // Cargar slider de plantillas
                    await loadTemplatesSlider();
                    
                    // Mostrar imagen de firma si existe
                    if (Categoria.plantilla_archivo_firma || Categoria.firma_imagen) {
                        showExistingFirma(Categoria.plantilla_archivo_firma || Categoria.firma_imagen);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar la configuración de la categoria');
            }
        }
        
        function resetDefaults() {
            if (confirm('¿Restablecer a valores predeterminados?')) {
                const setValueIfExists = (id, value) => {
                    const elem = document.getElementById(id);
                    if (elem) elem.value = value;
                };
                
                setValueIfExists('fuente_nombre', 'Roboto-Regular');
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
                
                // Restablecer checkboxes
                document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = true;
                });
                
                updateMarkerPositions();
                updateMarkersVisibility();
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
                // Guardar configuración de la Categoria
                const response = await fetch('api_categoria_config.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // También guardar la configuración de la plantilla activa
                    if (activeTemplateId) {
                        await saveTemplateConfig();
                    }
                    
                    alert('Configuración guardada correctamente');
                    loadCategoriaConfig();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la configuración'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar la configuración');
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
        loadCategoriaConfig();
        
        // Variables para guardar info del grupo
        let grupoId = null;
        
        // Manejar toggle de usar plantilla del grupo
        const toggleUsarGrupo = document.getElementById('usarPlantillaGrupo');
        const configContent = document.getElementById('configContent');
        const grupoPlantillaInfo = document.getElementById('grupoPlantillaInfo');
        const usarPlantillaPropiaInput = document.getElementById('usar_plantilla_propia');
        
        function updateToggleState() {
            const usarGrupo = toggleUsarGrupo.checked;
            if (usarGrupo) {
                // Usar plantilla del grupo
                configContent.classList.add('hidden');
                grupoPlantillaInfo.style.display = 'block';
                usarPlantillaPropiaInput.value = '0';
            } else {
                // Usar plantillas propias
                configContent.classList.remove('hidden');
                grupoPlantillaInfo.style.display = 'none';
                usarPlantillaPropiaInput.value = '1';
            }
        }
        
        toggleUsarGrupo.addEventListener('change', async function() {
            updateToggleState();
            
            // Guardar el cambio en el servidor
            try {
                const formData = new FormData();
                formData.append('action', this.checked ? 'use_grupo' : 'use_propias');
                formData.append('categoria_id', categoriaId);
                
                const response = await fetch('api_categoria_plantillas.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    console.log(this.checked ? 'Cambiado a usar plantilla del grupo' : 'Cambiado a usar plantillas propias');
                    // Recargar plantillas si cambiamos a propias
                    if (!this.checked) {
                        await loadTemplatesSlider();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
        
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
                formData.append('tipo', 'Categoria');
                formData.append('id', categoriaId);
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
                
                // Configuración de texto
                formData.append('fuente_nombre', document.getElementById('fuente_nombre')?.value || 'Roboto-Regular');
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
                
                // Configuración de Fecha
                const formatoFechaValue = document.getElementById('formato_fecha')?.value || 'd de F de Y';
                console.log('DEBUG Preview Categoria - formato_fecha:', formatoFechaValue);
                formData.append('formato_fecha', formatoFechaValue);
                formData.append('fuente_fecha', document.getElementById('fuente_fecha')?.value || 'Roboto-Regular');
                formData.append('tamanio_fecha', document.getElementById('tamanio_fecha')?.value || 20);
                formData.append('color_fecha', document.getElementById('color_fecha')?.value || '#333333');
                
                // Variables habilitadas
                const variablesHabilitadas = [];
                document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(cb => {
                    variablesHabilitadas.push(cb.value);
                });
                console.log('DEBUG Preview Categoria - variables habilitadas:', variablesHabilitadas);
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





