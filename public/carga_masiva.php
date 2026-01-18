<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$grupo_id = $_GET['grupo'] ?? 0;

if (empty($grupo_id)) {
    header('Location: index.php');
    exit;
}

$pdo = getConnection();

// Obtener información del grupo
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: index.php');
    exit;
}

// Obtener categorías del grupo
$stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE grupo_id = ? AND activo = 1 ORDER BY nombre");
$stmt->execute([$grupo_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Masiva - <?= htmlspecialchars($grupo['nombre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header-section {
            background: linear-gradient(135deg, <?= $grupo['color'] ?>dd 0%, <?= $grupo['color'] ?> 100%);
            color: white;
            padding: 30px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-icon {
            font-size: 48px;
            background: rgba(255,255,255,0.2);
            width: 80px;
            height: 80px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-info h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
        }
        
        .header-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .btn-back {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-area {
            border: 3px dashed #bdc3c7;
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .upload-area:hover {
            border-color: <?= $grupo['color'] ?>;
            background: <?= $grupo['color'] ?>10;
        }
        
        .upload-area.dragover {
            border-color: <?= $grupo['color'] ?>;
            background: <?= $grupo['color'] ?>20;
        }
        
        .upload-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .upload-text {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            font-size: 13px;
            color: #95a5a6;
        }
        
        .file-input {
            display: none;
        }
        
        .selected-file {
            margin-top: 15px;
            padding: 15px;
            background: #ecf0f1;
            border-radius: 8px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .selected-file.active {
            display: flex;
        }
        
        .file-name {
            flex: 1;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: <?= $grupo['color'] ?>;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .checkbox-item:hover {
            background: #ecf0f1;
        }
        
        .checkbox-item input[type="checkbox"],
        .checkbox-item input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin: 0;
        }
        
        .checkbox-item label {
            cursor: pointer;
            margin: 0;
            user-select: none;
            flex: 1;
        }
        
        .btn-download-template {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        
        .btn-download-template:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
            font-size: 16px;
        }
        
        .info-box ul {
            margin: 0;
            padding-left: 20px;
            color: #424242;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .btn-generate {
            width: 100%;
            padding: 15px;
            background: <?= $grupo['color'] ?>;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 20px;
        }
        
        .btn-generate:hover:not(:disabled) {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-generate:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progress-section {
            display: none;
            margin-top: 30px;
        }
        
        .progress-section.active {
            display: block;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background: <?= $grupo['color'] ?>;
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .progress-info {
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .results-section {
            display: none;
            margin-top: 30px;
        }
        
        .results-section.active {
            display: block;
        }
        
        .results-summary {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .results-summary h3 {
            margin: 0 0 15px 0;
            color: #155724;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .download-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .btn-download {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-download-pdf-individual {
            background: #e74c3c;
            color: white;
        }
        
        .btn-download-pdf-combined {
            background: #c0392b;
            color: white;
        }
        
        .btn-download-img-individual {
            background: #9b59b6;
            color: white;
        }
        
        .btn-download-img-combined {
            background: #8e44ad;
            color: white;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* ===== TABS SYSTEM ===== */
        .tabs-container {
            margin-bottom: 25px;
        }
        
        .tabs-header {
            display: flex;
            gap: 5px;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 12px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px 25px;
            border: none;
            background: transparent;
            color: #7f8c8d;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .tab-btn.active {
            background: <?= $grupo['color'] ?>;
            color: white;
            box-shadow: 0 4px 15px <?= $grupo['color'] ?>40;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* ===== FORM TAB STYLES ===== */
        .form-header-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            .form-header-options {
                grid-template-columns: 1fr;
            }
        }
        
        .estudiantes-table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .estudiantes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .estudiantes-table th {
            background: <?= $grupo['color'] ?>15;
            color: #2c3e50;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .estudiantes-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }
        
        .estudiantes-table tr:hover {
            background: #f8f9fa;
        }
        
        .estudiantes-table input[type="text"],
        .estudiantes-table input[type="date"],
        .estudiantes-table input[type="tel"],
        .estudiantes-table select {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .estudiantes-table input:focus,
        .estudiantes-table select:focus {
            outline: none;
            border-color: <?= $grupo['color'] ?>;
        }
        
        .estudiantes-table input[type="text"].nombre-input {
            min-width: 180px;
        }
        
        .estudiantes-table input[type="text"].cedula-input {
            min-width: 100px;
        }
        
        .estudiantes-table input[type="tel"].celular-input {
            min-width: 130px;
        }
        
        .estudiantes-table select.categoria-select {
            min-width: 140px;
        }
        
        .estudiantes-table input[type="date"] {
            min-width: 130px;
        }
        
        .email-input {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e8ecf0;
            border-radius: 6px;
            font-size: 13px;
            min-width: 160px;
        }
        
        .email-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .star-checkbox {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .star-checkbox input {
            display: none;
        }
        
        .star-checkbox .star-label {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: #ccc;
            background: transparent;
            border: 2px solid #e8ecf0;
            transition: all 0.3s ease;
        }
        
        .star-checkbox .star-label:hover {
            border-color: #f39c12;
            color: #f39c12;
        }
        
        .star-checkbox .star-label.active,
        .star-checkbox input:checked + .star-label {
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
        }
        
        .btn-remove-row {
            background: #e74c3c;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-remove-row:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        /* Botones de acción pequeños para la tabla de previsualización */
        .btn-action {
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin: 0 2px;
        }
        
        .btn-edit-small {
            background: #3498db;
            color: white;
        }
        
        .btn-edit-small:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        .btn-delete-small {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete-small:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        /* Modal para edición */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #e74c3c;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-body .form-group {
            margin-bottom: 15px;
        }
        
        .modal-body .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #34495e;
            font-size: 13px;
        }
        
        .modal-body .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .modal-body .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px 25px;
            border-top: 1px solid #ecf0f1;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }
        
        .modal-footer .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modal-footer .btn-secondary {
            background: #ecf0f1;
            color: #7f8c8d;
        }
        
        .modal-footer .btn-secondary:hover {
            background: #bdc3c7;
        }
        
        .modal-footer .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .modal-footer .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-add-row {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 15px;
        }
        
        .btn-add-row:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .celular-wrapper {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .celular-prefix {
            background: #ecf0f1;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 13px;
            color: #7f8c8d;
            white-space: nowrap;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .form-actions .btn-generate {
            flex: 1;
            min-width: 200px;
            margin-top: 0;
        }
        
        .empty-table-message {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }
        
        .empty-table-message i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .estudiantes-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: <?= $grupo['color'] ?>15;
            color: <?= $grupo['color'] ?>;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-left">
                <div class="header-icon"><?= htmlspecialchars($grupo['icono']) ?></div>
                <div class="header-info">
                    <h1><i class="fas fa-upload"></i> Generación Masiva de Certificados</h1>
                    <p><?= htmlspecialchars($grupo['nombre']) ?></p>
                </div>
            </div>
            <a href="grupo_detalle.php?id=<?= $grupo_id ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Sistema de Pestañas -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab('archivo')">
                    <i class="fas fa-file-excel"></i> Archivo Excel/CSV
                </button>
                <button class="tab-btn" onclick="switchTab('formulario')">
                    <i class="fas fa-edit"></i> Formulario Manual
                </button>
            </div>
        </div>

        <!-- TAB 1: Archivo Excel/CSV -->
        <div class="tab-content active" id="tab-archivo">
            <div class="content-grid">
            <!-- Columna 1: Carga de archivo -->
            <div class="card">
                <h2><i class="fas fa-folder-open"></i> Cargar Archivo Excel/CSV</h2>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <a href="descargar_plantilla.php?tipo=excel" class="btn-download-template" style="flex: 1;">
                        <i class="fas fa-file-excel"></i> Descargar Plantilla Excel
                    </a>
                    <a href="descargar_plantilla.php?tipo=csv" class="btn-download-template" style="flex: 1;">
                        <i class="fas fa-file-csv"></i> Descargar Plantilla CSV
                    </a>
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-clipboard"></i> Formato del archivo</h4>
                    <ul>
                        <li><strong>nombre:</strong> Nombre completo (OBLIGATORIO)</li>
                        <li><strong>cedula:</strong> Cédula/ID del estudiante</li>
                        <li><strong>celular:</strong> Teléfono del estudiante</li>
                        <li><strong>email:</strong> Correo electrónico</li>
                        <li><strong>razon:</strong> Razón del certificado (opcional)</li>
                        <li><strong>fecha:</strong> Fecha de emisión (AAAA-MM-DD)</li>
                        <li><strong>categoria:</strong> ID de la categoría (opcional)</li>
                    </ul>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        <i class="fas fa-info-circle"></i> Los estudiantes nuevos se registrarán automáticamente.
                    </p>
                </div>
                
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📤</div>
                    <div class="upload-text">Arrastra tu archivo aquí o haz clic para seleccionar</div>
                    <div class="upload-hint">Formatos soportados: .csv, .xlsx, .xls</div>
                </div>
                
                <input type="file" id="fileInput" class="file-input" accept=".csv,.xlsx,.xls">
                
                <div class="selected-file" id="selectedFile">
                    <span class="file-name" id="fileName"></span>
                    <button class="btn-remove" onclick="removeFile()">✕ Quitar</button>
                </div>
            </div>
            
            <!-- Columna 2: Opciones -->
            <div class="card">
                <h2><i class="fas fa-cog"></i> Opciones de Generación</h2>
                
                <div class="form-group">
                    <label for="categoria_default">Categoría por Defecto</label>
                    <select id="categoria_default">
                        <option value="">Usar la especificada en cada fila</option>
                        <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user-plus"></i> Registro de Estudiantes</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="registrar_estudiantes" checked>
                            <label for="registrar_estudiantes">Registrar estudiantes nuevos automáticamente</label>
                        </div>
                    </div>
                    <small style="color: #7f8c8d; display: block; margin-top: 8px;">
                        Los estudiantes con cédula existente se vincularán automáticamente.
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Formato de Descarga</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" name="formato_descarga" id="opt_pdf_individual" value="pdf_individual" checked>
                            <label for="opt_pdf_individual">📄 PDFs Individuales (ZIP con múltiples archivos)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" name="formato_descarga" id="opt_pdf_combined" value="pdf_combined">
                            <label for="opt_pdf_combined">📚 PDF Combinado (Un solo archivo con todas las páginas)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" name="formato_descarga" id="opt_imagenes" value="imagenes">
                            <label for="opt_imagenes">🖼️ Imágenes (ZIP con múltiples archivos)</label>
                        </div>
                    </div>
                </div>
                
                <button class="btn-generate" id="btnGenerate" disabled onclick="generateCertificates()">
                    ✨ Generar Certificados
                </button>
            </div>
        </div>
        
        <!-- Sección de Previsualización (debajo de las dos columnas) -->
        <div id="previewSection" class="card" style="display: none; margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">
                    <i class="fas fa-eye"></i> Previsualización de Datos
                </h2>
                <div style="display: flex; gap: 10px;">
                    <span class="preview-badge" style="background: #27ae60; color: white; padding: 5px 12px; border-radius: 15px; font-size: 13px;">
                        <i class="fas fa-users"></i> <span id="previewTotal">0</span> registros
                    </span>
                    <span class="preview-badge" style="background: #3498db; color: white; padding: 5px 12px; border-radius: 15px; font-size: 13px;">
                        <i class="fas fa-user-plus"></i> <span id="previewNuevos">0</span> nuevos
                    </span>
                    <span class="preview-badge" style="background: #9b59b6; color: white; padding: 5px 12px; border-radius: 15px; font-size: 13px;">
                        <i class="fas fa-link"></i> <span id="previewVinculados">0</span> existentes
                    </span>
                </div>
            </div>
            
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 10px;">
                <table class="estudiantes-table" id="previewTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Celular</th>
                            <th>Email</th>
                            <th>Razón</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th style="width: 70px;">Estado</th>
                            <th style="width: 90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody">
                    </tbody>
                </table>
            </div>
            
            <div id="previewSummary" style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; font-size: 14px; color: #555;"></div>
        </div>
        </div><!-- Fin TAB 1 -->

        <!-- TAB 2: Formulario Manual -->
        <div class="tab-content" id="tab-formulario">
            <div class="card">
                <h2><i class="fas fa-edit"></i> Ingreso Manual de Estudiantes</h2>
                
                <!-- Opciones generales del formulario -->
                <div class="form-header-options">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="form_fecha_general"><i class="fas fa-calendar-alt"></i> Fecha para todos los certificados</label>
                        <input type="date" id="form_fecha_general" style="width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="form_categoria_general"><i class="fas fa-folder"></i> Categoría por defecto</label>
                        <select id="form_categoria_general" style="width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Seleccionar en cada fila --</option>
                            <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Contador de estudiantes -->
                <div class="estudiantes-count">
                    <i class="fas fa-users"></i>
                    <span id="countEstudiantes">0</span> estudiantes agregados
                </div>
                
                <!-- Tabla de estudiantes -->
                <div class="estudiantes-table-container">
                    <table class="estudiantes-table" id="tablaEstudiantes">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Nombre Completo *</th>
                                <th>Cédula</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <th>Categoría</th>
                                <th>Fecha Nac.</th>
                                <th style="width: 60px;"><i class="fas fa-star" title="Destacado"></i></th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEstudiantes">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                    <div class="empty-table-message" id="emptyMessage">
                        <i class="fas fa-user-plus"></i>
                        <p>No hay estudiantes agregados. Haz clic en el botón de abajo para agregar.</p>
                    </div>
                </div>
                
                <button class="btn-add-row" onclick="agregarEstudiante()">
                    <i class="fas fa-plus"></i> Agregar Estudiante
                </button>
                
                <!-- Opciones de formato y botón generar -->
                <div class="form-group" style="margin-top: 25px;">
                    <label>Formato de Descarga</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" name="formato_form" id="form_opt_pdf_individual" value="pdf_individual" checked>
                            <label for="form_opt_pdf_individual">📄 PDFs Individuales (ZIP con múltiples archivos)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" name="formato_form" id="form_opt_pdf_combined" value="pdf_combined">
                            <label for="form_opt_pdf_combined">📚 PDF Combinado (Un solo archivo con todas las páginas)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" name="formato_form" id="form_opt_imagenes" value="imagenes">
                            <label for="form_opt_imagenes">🖼️ Imágenes (ZIP con múltiples archivos)</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button class="btn-generate" id="btnGenerateForm" disabled onclick="generateFromForm()">
                        ✨ Generar Certificados
                    </button>
                </div>
            </div>
        </div><!-- Fin TAB 2 -->
        
        <!-- Sección de Progreso -->
        <div class="card progress-section" id="progressSection">
            <h2>⏳ Generando Certificados...</h2>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            <div class="progress-info" id="progressInfo">
                Procesando certificados...
            </div>
        </div>
        
        <!-- Sección de Resultados -->
        <div class="card results-section" id="resultsSection">
            <div class="results-summary">
                <h3>✅ Certificados Generados Exitosamente</h3>
                <div class="summary-stats">
                    <div class="stat-item">
                        <div class="stat-value" id="statTotal">0</div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statSuccess">0</div>
                        <div class="stat-label">Exitosos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statErrors">0</div>
                        <div class="stat-label">Errores</div>
                    </div>
                </div>
            </div>
            
            <h3><i class="fas fa-download"></i> Descargar Certificados</h3>
            <div class="download-buttons" id="downloadButtons">
                <!-- Se llenarán dinámicamente -->
            </div>
        </div>
    </div>

    <script>
        // URL base del proyecto
        const BASE_URL = '<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/../';
        
        // ===== SISTEMA DE PESTAÑAS =====
        function switchTab(tab) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar tab seleccionado
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
        
        // ===== TAB ARCHIVO: Variables y funciones =====
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const selectedFile = document.getElementById('selectedFile');
        const fileName = document.getElementById('fileName');
        const btnGenerate = document.getElementById('btnGenerate');
        let currentFile = null;
        
        // Eventos de drag & drop
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(e.target.files[0]);
            }
        });
        
        // Variable para almacenar datos de previsualización
        let previewData = [];
        
        async function handleFile(file) {
            const validExtensions = ['csv', 'xlsx', 'xls'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!validExtensions.includes(fileExtension)) {
                alert('❌ Formato de archivo no válido. Use .csv, .xlsx o .xls');
                return;
            }
            
            currentFile = file;
            fileName.textContent = file.name;
            selectedFile.classList.add('active');
            
            // Cargar previsualización automáticamente
            await loadPreview(file);
        }
        
        async function loadPreview(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('grupo_id', <?= $grupo_id ?>);
            
            // Mostrar indicador de carga
            const previewSection = document.getElementById('previewSection');
            const previewTableBody = document.getElementById('previewTableBody');
            
            previewSection.style.display = 'block';
            previewTableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
            
            try {
                const response = await fetch('previsualizar_carga.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.estudiantes && data.estudiantes.length > 0) {
                    previewData = data.estudiantes;
                    renderPreviewTable();
                    btnGenerate.disabled = false;
                    
                } else {
                    previewTableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 30px; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> No se encontraron datos válidos en el archivo</td></tr>';
                    btnGenerate.disabled = true;
                    previewData = [];
                    document.getElementById('previewSummary').innerHTML = '';
                    document.getElementById('previewTotal').textContent = '0';
                    document.getElementById('previewNuevos').textContent = '0';
                    document.getElementById('previewVinculados').textContent = '0';
                }
            } catch (error) {
                console.error('Error en previsualización:', error);
                previewTableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 30px; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Error al cargar el archivo</td></tr>';
                btnGenerate.disabled = true;
                previewData = [];
                document.getElementById('previewSummary').innerHTML = '';
            }
        }
        
        function renderPreviewTable() {
            const previewTableBody = document.getElementById('previewTableBody');
            
            // Calcular contadores
            const nuevos = previewData.filter(e => !e.estudiante_existente && e.cedula && e.cedula.trim() !== '').length;
            const vinculados = previewData.filter(e => e.estudiante_existente).length;
            const sinNombre = previewData.filter(e => !e.nombre || e.nombre.trim() === '').length;
            
            // Actualizar contadores
            document.getElementById('previewTotal').textContent = previewData.length;
            document.getElementById('previewNuevos').textContent = nuevos;
            document.getElementById('previewVinculados').textContent = vinculados;
            
            // Generar filas de la tabla
            let html = '';
            previewData.forEach((est, index) => {
                const hasError = !est.nombre || est.nombre.trim() === '';
                const rowClass = hasError ? 'style="background-color: #ffe6e6;"' : '';
                
                // Determinar estado/icono
                let estadoIcon = '';
                if (est.estudiante_existente) {
                    estadoIcon = '<span title="Estudiante existente - se vinculará"><i class="fas fa-link" style="color: #9b59b6;"></i></span>';
                } else if (est.cedula && est.cedula.trim() !== '') {
                    estadoIcon = '<span title="Nuevo estudiante - se registrará"><i class="fas fa-user-plus" style="color: #27ae60;"></i></span>';
                } else {
                    estadoIcon = '<span title="Solo certificado (sin registro)"><i class="fas fa-file-alt" style="color: #3498db;"></i></span>';
                }
                
                html += `
                    <tr id="preview-row-${index}" ${rowClass}>
                        <td style="text-align: center; font-weight: 600; color: #7f8c8d;">${index + 1}</td>
                        <td>${est.nombre || '<span style="color:#e74c3c">⚠️ Sin nombre</span>'}</td>
                        <td>${est.cedula || '<span style="color:#95a5a6">-</span>'}</td>
                        <td>${est.celular || '<span style="color:#95a5a6">-</span>'}</td>
                        <td>${est.email || '<span style="color:#95a5a6">-</span>'}</td>
                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${est.razon || ''}">${est.razon || '<span style="color:#95a5a6">-</span>'}</td>
                        <td>${est.categoria || '<span style="color:#f39c12">Por defecto</span>'}</td>
                        <td>${est.fecha || '<span style="color:#95a5a6">-</span>'}</td>
                        <td style="text-align: center;">${estadoIcon}</td>
                        <td style="text-align: center;">
                            <button class="btn-action btn-edit-small" onclick="editPreviewRow(${index})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-delete-small" onclick="deletePreviewRow(${index})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            previewTableBody.innerHTML = html;
            
            // Mostrar resumen
            let summaryHtml = `<strong>📊 Resumen:</strong> ${previewData.length} registros totales`;
            summaryHtml += ` | <i class="fas fa-user-plus" style="color: #27ae60;"></i> ${nuevos} estudiantes nuevos`;
            summaryHtml += ` | <i class="fas fa-link" style="color: #9b59b6;"></i> ${vinculados} existentes`;
            if (sinNombre > 0) summaryHtml += ` | <span style="color:#e74c3c">⚠️ ${sinNombre} sin nombre (se omitirán)</span>`;
            
            document.getElementById('previewSummary').innerHTML = summaryHtml;
            
            // Deshabilitar botón si no hay datos
            btnGenerate.disabled = previewData.length === 0;
        }
        
        function editPreviewRow(index) {
            const est = previewData[index];
            
            // Crear modal de edición
            const modalHtml = `
                <div id="editPreviewModal" class="modal-overlay" style="display: flex;">
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header">
                            <h3><i class="fas fa-edit"></i> Editar Registro #${index + 1}</h3>
                            <button class="modal-close" onclick="closeEditPreviewModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nombre <span style="color:#e74c3c">*</span></label>
                                <input type="text" id="edit_nombre" value="${est.nombre || ''}" class="form-control">
                            </div>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Cédula</label>
                                    <input type="text" id="edit_cedula" value="${est.cedula || ''}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Celular</label>
                                    <input type="text" id="edit_celular" value="${est.celular || ''}" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="edit_email" value="${est.email || ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Razón</label>
                                <input type="text" id="edit_razon" value="${est.razon || ''}" class="form-control">
                            </div>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select id="edit_categoria" class="form-control">
                                        <option value="">Por defecto</option>
                                        <?php foreach($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fecha</label>
                                    <input type="date" id="edit_fecha" value="${est.fecha || ''}" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="closeEditPreviewModal()">Cancelar</button>
                            <button class="btn btn-primary" onclick="savePreviewEdit(${index})">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Insertar modal
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Seleccionar categoría actual si existe
            if (est.categoria_id) {
                document.getElementById('edit_categoria').value = est.categoria_id;
            }
        }
        
        function closeEditPreviewModal() {
            const modal = document.getElementById('editPreviewModal');
            if (modal) modal.remove();
        }
        
        function savePreviewEdit(index) {
            const nombre = document.getElementById('edit_nombre').value.trim();
            
            if (!nombre) {
                alert('El nombre es obligatorio');
                return;
            }
            
            // Actualizar datos
            previewData[index].nombre = nombre;
            previewData[index].cedula = document.getElementById('edit_cedula').value.trim();
            previewData[index].celular = document.getElementById('edit_celular').value.trim();
            previewData[index].email = document.getElementById('edit_email').value.trim();
            previewData[index].razon = document.getElementById('edit_razon').value.trim();
            previewData[index].fecha = document.getElementById('edit_fecha').value;
            
            const categoriaSelect = document.getElementById('edit_categoria');
            previewData[index].categoria_id = categoriaSelect.value;
            previewData[index].categoria = categoriaSelect.value ? categoriaSelect.options[categoriaSelect.selectedIndex].text : '';
            
            // Si cambió la cédula, resetear el estado de estudiante existente
            previewData[index].estudiante_existente = null;
            
            closeEditPreviewModal();
            renderPreviewTable();
        }
        
        function deletePreviewRow(index) {
            if (confirm(`¿Eliminar el registro #${index + 1} (${previewData[index].nombre || 'Sin nombre'})?`)) {
                previewData.splice(index, 1);
                renderPreviewTable();
                
                if (previewData.length === 0) {
                    document.getElementById('previewSection').style.display = 'none';
                    btnGenerate.disabled = true;
                }
            }
        }
        
        function removeFile() {
            currentFile = null;
            fileInput.value = '';
            selectedFile.classList.remove('active');
            btnGenerate.disabled = true;
            
            // Ocultar previsualización y limpiar datos
            document.getElementById('previewSection').style.display = 'none';
            document.getElementById('previewTableBody').innerHTML = '';
            document.getElementById('previewSummary').innerHTML = '';
            document.getElementById('previewTotal').textContent = '0';
            document.getElementById('previewNuevos').textContent = '0';
            document.getElementById('previewVinculados').textContent = '0';
            previewData = [];
        }
        
        async function generateCertificates() {
            if (previewData.length === 0) {
                alert('No hay datos para procesar');
                return;
            }
            
            // Filtrar registros sin nombre
            const datosValidos = previewData.filter(e => e.nombre && e.nombre.trim() !== '');
            if (datosValidos.length === 0) {
                alert('No hay registros válidos para procesar');
                return;
            }
            
            const formData = new FormData();
            // Enviar los datos de previsualización como JSON (ya pueden estar editados)
            formData.append('preview_data', JSON.stringify(datosValidos));
            formData.append('grupo_id', <?= $grupo_id ?>);
            formData.append('categoria_default', document.getElementById('categoria_default').value);
            formData.append('registrar_estudiantes', document.getElementById('registrar_estudiantes').checked ? '1' : '0');
            
            // Obtener formato seleccionado (radio button)
            const formatoSeleccionado = document.querySelector('input[name="formato_descarga"]:checked').value;
            formData.append('pdf_individual', formatoSeleccionado === 'pdf_individual' ? '1' : '0');
            formData.append('pdf_combined', formatoSeleccionado === 'pdf_combined' ? '1' : '0');
            formData.append('imagenes', formatoSeleccionado === 'imagenes' ? '1' : '0');
            
            // Mostrar progreso
            document.getElementById('progressSection').classList.add('active');
            const progressFill = document.querySelector('.progress-fill');
            const progressInfo = document.querySelector('.progress-info');
            btnGenerate.disabled = true;
            
            // Función para animar el progreso
            function updateProgress(percent, message) {
                progressFill.style.width = percent + '%';
                progressFill.textContent = percent + '%';
                progressInfo.textContent = message;
            }
            
            try {
                // Animación realista del progreso
                updateProgress(0, 'Iniciando...');
                await new Promise(resolve => setTimeout(resolve, 200));
                
                updateProgress(10, 'Leyendo archivo...');
                await new Promise(resolve => setTimeout(resolve, 300));
                
                updateProgress(20, 'Validando datos...');
                await new Promise(resolve => setTimeout(resolve, 400));
                
                // Iniciar petición al servidor
                const fetchPromise = fetch('procesar_carga_masiva.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Simular progreso mientras el servidor procesa
                updateProgress(30, 'Generando certificados...');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                updateProgress(50, 'Procesando imágenes...');
                await new Promise(resolve => setTimeout(resolve, 600));
                
                updateProgress(70, 'Creando documentos PDF...');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                updateProgress(85, 'Preparando archivos de descarga...');
                
                const response = await fetchPromise;
                
                updateProgress(95, 'Finalizando...');
                
                const responseText = await response.text();
                console.log('Respuesta del servidor:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    console.error('Respuesta recibida:', responseText);
                    throw new Error('El servidor devolvió una respuesta inválida. Revisa la consola para más detalles.');
                }
                
                // Completar progreso
                progressFill.style.width = '100%';
                progressFill.textContent = '100%';
                progressInfo.textContent = '¡Proceso completado!';
                
                if (data.success) {
                    // Actualizar estadísticas
                    document.getElementById('statTotal').textContent = data.total;
                    document.getElementById('statSuccess').textContent = data.success_count;
                    document.getElementById('statErrors').textContent = data.error_count;
                    
                    // Generar botones de descarga
                    const downloadButtons = document.getElementById('downloadButtons');
                    downloadButtons.innerHTML = '';
                    
                    console.log('Downloads recibidos:', data.downloads);
                    
                    if (data.downloads && Object.keys(data.downloads).length > 0) {
                        Object.entries(data.downloads).forEach(([key, url]) => {
                            const button = document.createElement('button');
                            button.className = 'btn-download btn-download-' + key.replace('_', '-');
                            
                            let icon = '📥';
                            let label = key;
                            if (key === 'pdf_individual') { icon = '📄'; label = 'Descargar PDFs Individuales (ZIP)'; }
                            if (key === 'pdf_combined') { icon = '📚'; label = 'Descargar PDF Combinado'; }
                            if (key === 'imagenes') { icon = '🖼️'; label = 'Descargar Imágenes (ZIP)'; }
                            
                            button.innerHTML = `${icon} ${label}`;
                            // Construir URL absoluta desde la base del proyecto
                            const downloadUrl = BASE_URL + url;
                            button.onclick = () => window.location.href = downloadUrl;
                            downloadButtons.appendChild(button);
                        });
                    } else {
                        downloadButtons.innerHTML = '<p style="color: #e74c3c;">⚠️ No se generaron archivos de descarga. Verifica que hayas seleccionado al menos una opción de formato.</p>';
                    }
                    
                    // Limpiar archivo cargado
                    removeFile();
                    
                    // Ocultar progreso y mostrar resultados
                    document.getElementById('progressSection').classList.remove('active');
                    document.getElementById('resultsSection').classList.add('active');
                } else {
                    alert('❌ Error: ' + data.message);
                    // Resetear progreso
                    progressFill.style.width = '0%';
                    progressFill.textContent = '';
                    document.getElementById('progressSection').classList.remove('active');
                    btnGenerate.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al procesar el archivo');
                // Resetear progreso
                progressFill.style.width = '0%';
                progressFill.textContent = '';
                document.getElementById('progressSection').classList.remove('active');
                btnGenerate.disabled = false;
            }
        }
        
        // ===== TAB FORMULARIO: Variables y funciones =====
        let estudiantesData = [];
        let estudianteId = 0;
        const categoriasJson = <?= json_encode($categorias) ?>;
        
        function actualizarContadorEstudiantes() {
            const count = estudiantesData.length;
            document.getElementById('countEstudiantes').textContent = count;
            document.getElementById('emptyMessage').style.display = count > 0 ? 'none' : 'block';
            document.getElementById('btnGenerateForm').disabled = count === 0;
        }
        
        function generarSelectCategorias(selectedId = '') {
            const categoriaGeneral = document.getElementById('form_categoria_general').value;
            let html = '<option value="">-- Seleccionar --</option>';
            categoriasJson.forEach(cat => {
                const selected = (selectedId || categoriaGeneral) == cat.id ? 'selected' : '';
                html += `<option value="${cat.id}" ${selected}>${cat.nombre}</option>`;
            });
            return html;
        }
        
        function agregarEstudiante(datos = null) {
            estudianteId++;
            const id = estudianteId;
            const estudiante = datos || {
                id: id,
                nombre: '',
                cedula: '',
                celular: '',
                email: '',
                categoria_id: document.getElementById('form_categoria_general').value,
                fecha_nacimiento: '',
                destacado: false
            };
            
            if (!datos) {
                estudiantesData.push(estudiante);
            }
            
            const tbody = document.getElementById('tbodyEstudiantes');
            const tr = document.createElement('tr');
            tr.id = 'row-' + id;
            tr.innerHTML = `
                <td style="text-align: center; font-weight: 600; color: #7f8c8d;">${estudiantesData.length}</td>
                <td>
                    <input type="text" class="nombre-input" placeholder="Nombre completo" 
                           value="${estudiante.nombre}" 
                           onchange="actualizarEstudiante(${id}, 'nombre', this.value)" required>
                </td>
                <td>
                    <input type="text" class="cedula-input" placeholder="Sin guiones" 
                           value="${estudiante.cedula}"
                           onchange="actualizarEstudiante(${id}, 'cedula', this.value)">
                </td>
                <td>
                    <div class="celular-wrapper">
                        <span class="celular-prefix">+593</span>
                        <input type="tel" class="celular-input" placeholder="9XXXXXXXX" 
                               value="${estudiante.celular}"
                               onchange="actualizarEstudiante(${id}, 'celular', this.value)"
                               maxlength="10" pattern="[0-9]{9,10}">
                    </div>
                </td>
                <td>
                    <input type="email" class="email-input" placeholder="correo@ejemplo.com" 
                           value="${estudiante.email || ''}"
                           onchange="actualizarEstudiante(${id}, 'email', this.value)">
                </td>
                <td>
                    <select class="categoria-select" onchange="actualizarEstudiante(${id}, 'categoria_id', this.value)">
                        ${generarSelectCategorias(estudiante.categoria_id)}
                    </select>
                </td>
                <td>
                    <input type="date" value="${estudiante.fecha_nacimiento}"
                           onchange="actualizarEstudiante(${id}, 'fecha_nacimiento', this.value)">
                </td>
                <td>
                    <div class="star-checkbox" onclick="toggleDestacado(${id})">
                        <input type="checkbox" id="star-${id}" ${estudiante.destacado ? 'checked' : ''}
                               onchange="actualizarEstudiante(${id}, 'destacado', this.checked)">
                        <label for="star-${id}" class="star-label ${estudiante.destacado ? 'active' : ''}">
                            <i class="fas fa-star"></i>
                        </label>
                    </div>
                </td>
                <td>
                    <button class="btn-remove-row" onclick="eliminarEstudiante(${id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            actualizarContadorEstudiantes();
            
            // Focus en el campo nombre
            tr.querySelector('.nombre-input').focus();
        }
        
        function actualizarEstudiante(id, campo, valor) {
            const estudiante = estudiantesData.find(e => e.id === id);
            if (estudiante) {
                estudiante[campo] = valor;
            }
        }
        
        function toggleDestacado(id) {
            const checkbox = document.getElementById('star-' + id);
            const label = checkbox.nextElementSibling;
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
            
            actualizarEstudiante(id, 'destacado', checkbox.checked);
        }
        
        function eliminarEstudiante(id) {
            const index = estudiantesData.findIndex(e => e.id === id);
            if (index > -1) {
                estudiantesData.splice(index, 1);
                document.getElementById('row-' + id).remove();
                renumerarFilas();
                actualizarContadorEstudiantes();
            }
        }
        
        function renumerarFilas() {
            const rows = document.querySelectorAll('#tbodyEstudiantes tr');
            rows.forEach((row, index) => {
                row.querySelector('td:first-child').textContent = index + 1;
            });
        }
        
        // Actualizar categorías cuando cambia la categoría general
        document.getElementById('form_categoria_general').addEventListener('change', function() {
            const value = this.value;
            if (value) {
                document.querySelectorAll('.categoria-select').forEach(select => {
                    if (!select.value) {
                        select.value = value;
                        const id = parseInt(select.closest('tr').id.replace('row-', ''));
                        actualizarEstudiante(id, 'categoria_id', value);
                    }
                });
            }
        });
        
        // Generar certificados desde formulario
        async function generateFromForm() {
            // Validar que hay estudiantes
            if (estudiantesData.length === 0) {
                alert('❌ Agrega al menos un estudiante');
                return;
            }
            
            // Validar que todos tengan nombre
            const sinNombre = estudiantesData.filter(e => !e.nombre.trim());
            if (sinNombre.length > 0) {
                alert('❌ Todos los estudiantes deben tener nombre');
                return;
            }
            
            // Obtener fecha general
            const fechaGeneral = document.getElementById('form_fecha_general').value;
            
            // Preparar datos
            const datos = estudiantesData.map(e => ({
                nombre: e.nombre.trim(),
                cedula: e.cedula.trim(),
                celular: e.celular ? '+593' + e.celular.trim() : '',
                email: e.email ? e.email.trim() : '',
                categoria_id: e.categoria_id || document.getElementById('form_categoria_general').value,
                fecha_nacimiento: e.fecha_nacimiento,
                destacado: e.destacado ? 1 : 0,
                fecha: fechaGeneral
            }));
            
            // Obtener formato
            const formatoSeleccionado = document.querySelector('input[name="formato_form"]:checked').value;
            
            const formData = new FormData();
            formData.append('estudiantes_json', JSON.stringify(datos));
            formData.append('grupo_id', <?= $grupo_id ?>);
            formData.append('categoria_default', document.getElementById('form_categoria_general').value);
            formData.append('pdf_individual', formatoSeleccionado === 'pdf_individual' ? '1' : '0');
            formData.append('pdf_combined', formatoSeleccionado === 'pdf_combined' ? '1' : '0');
            formData.append('imagenes', formatoSeleccionado === 'imagenes' ? '1' : '0');
            formData.append('guardar_estudiantes', '1'); // Flag para guardar en BD
            
            console.log('Enviando datos:', {
                estudiantes: datos,
                grupo_id: <?= $grupo_id ?>,
                categoria_default: document.getElementById('form_categoria_general').value,
                formato: formatoSeleccionado
            });
            
            // Mostrar progreso
            document.getElementById('progressSection').classList.add('active');
            const progressFill = document.querySelector('.progress-fill');
            const progressInfo = document.querySelector('.progress-info');
            document.getElementById('btnGenerateForm').disabled = true;
            
            function updateProgress(percent, message) {
                progressFill.style.width = percent + '%';
                progressFill.textContent = percent + '%';
                progressInfo.textContent = message;
            }
            
            try {
                updateProgress(0, 'Iniciando...');
                await new Promise(resolve => setTimeout(resolve, 200));
                
                updateProgress(20, 'Preparando datos de estudiantes...');
                await new Promise(resolve => setTimeout(resolve, 300));
                
                const fetchPromise = fetch('procesar_carga_masiva.php', {
                    method: 'POST',
                    body: formData
                });
                
                updateProgress(40, 'Generando certificados...');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                updateProgress(60, 'Procesando...');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                updateProgress(80, 'Preparando descargas...');
                
                const response = await fetchPromise;
                const responseText = await response.text();
                console.log('Respuesta:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Error JSON:', e);
                    throw new Error('Respuesta inválida del servidor');
                }
                
                updateProgress(100, '¡Completado!');
                
                if (data.success) {
                    console.log('Respuesta exitosa:', data);
                    console.log('Downloads:', data.downloads);
                    
                    document.getElementById('statTotal').textContent = data.total;
                    document.getElementById('statSuccess').textContent = data.success_count;
                    document.getElementById('statErrors').textContent = data.error_count;
                    
                    const downloadButtons = document.getElementById('downloadButtons');
                    downloadButtons.innerHTML = '';
                    
                    if (data.downloads && Object.keys(data.downloads).length > 0) {
                        Object.entries(data.downloads).forEach(([key, url]) => {
                            const button = document.createElement('button');
                            button.className = 'btn-download btn-download-' + key.replace('_', '-');
                            
                            let icon = '📥';
                            let label = key;
                            if (key === 'pdf_individual') { icon = '📄'; label = 'Descargar PDFs Individuales (ZIP)'; }
                            if (key === 'pdf_combined') { icon = '📚'; label = 'Descargar PDF Combinado'; }
                            if (key === 'imagenes') { icon = '🖼️'; label = 'Descargar Imágenes (ZIP)'; }
                            
                            button.innerHTML = `${icon} ${label}`;
                            button.onclick = () => window.location.href = BASE_URL + url;
                            downloadButtons.appendChild(button);
                        });
                    } else {
                        // Si no hay downloads pero hubo éxitos, mostrar mensaje
                        if (data.success_count > 0) {
                            downloadButtons.innerHTML = '<p style="color: #e74c3c;">⚠️ Se generaron ' + data.success_count + ' certificados pero no se pudieron crear los archivos de descarga.</p>';
                        } else {
                            downloadButtons.innerHTML = '<p style="color: #e74c3c;">⚠️ No se generaron certificados. Verifica los datos ingresados.</p>';
                        }
                    }
                    
                    // Limpiar formulario
                    estudiantesData = [];
                    estudianteId = 0;
                    document.getElementById('tbodyEstudiantes').innerHTML = '';
                    actualizarContadorEstudiantes();
                    
                    document.getElementById('progressSection').classList.remove('active');
                    document.getElementById('resultsSection').classList.add('active');
                } else {
                    alert('❌ Error: ' + data.message);
                    progressFill.style.width = '0%';
                    document.getElementById('progressSection').classList.remove('active');
                    document.getElementById('btnGenerateForm').disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al procesar');
                progressFill.style.width = '0%';
                document.getElementById('progressSection').classList.remove('active');
                document.getElementById('btnGenerateForm').disabled = false;
            }
        }
        
        // Inicializar con fecha de hoy
        document.getElementById('form_fecha_general').valueAsDate = new Date();
    </script>
</body>
</html>
