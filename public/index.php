<?php
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();
$esInstructor = Auth::isInstructor();

$pdo = getConnection();

// Obtener todos los grupos activos con conteo de categorías y certificados
// Si es instructor, solo mostrar sus grupos asignados
if ($esInstructor) {
    $gruposIds = Auth::getGruposAsignados();
    if (!empty($gruposIds)) {
        $placeholders = implode(',', array_fill(0, count($gruposIds), '?'));
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   COUNT(DISTINCT c.id) as total_categorias,
                   COUNT(DISTINCT cert.id) as total_certificados
            FROM grupos g
            LEFT JOIN categorias c ON g.id = c.grupo_id AND c.activo = 1
            LEFT JOIN certificados cert ON g.id = cert.grupo_id
            WHERE g.activo = 1 AND g.id IN ($placeholders)
            GROUP BY g.id
            ORDER BY g.fecha_creacion DESC
        ");
        $stmt->execute($gruposIds);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $grupos = [];
    }
} else {
    $stmt = $pdo->query("
        SELECT g.*, 
               COUNT(DISTINCT c.id) as total_categorias,
               COUNT(DISTINCT cert.id) as total_certificados
        FROM grupos g
        LEFT JOIN categorias c ON g.id = c.grupo_id AND c.activo = 1
        LEFT JOIN certificados cert ON g.id = cert.grupo_id
        WHERE g.activo = 1
        GROUP BY g.id
        ORDER BY g.fecha_creacion DESC
    ");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener todos los períodos activos (agrupados por nombre y fechas para evitar duplicados)
// EXCLUIR periodos que sean años completos (01/01 a 31/12 del mismo año con nombre "Año XXXX")
$stmt = $pdo->query("
    SELECT MIN(id) as id, nombre, fecha_inicio, fecha_fin, MIN(color) as color, 
           MIN(descripcion) as descripcion, COUNT(*) as grupos_count
    FROM periodos 
    WHERE activo = 1 
    AND NOT (
        nombre LIKE 'Año %' 
        AND DATE_FORMAT(fecha_inicio, '%m-%d') = '01-01'
        AND DATE_FORMAT(fecha_fin, '%m-%d') = '12-31'
        AND YEAR(fecha_inicio) = YEAR(fecha_fin)
    )
    GROUP BY nombre, fecha_inicio, fecha_fin
    ORDER BY fecha_inicio DESC
");
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// El año solo es un filtro, NO se crea como periodo automáticamente
// Los periodos deben ser creados manualmente por el usuario
$anioActual = date('Y');
$hayPeriodosReales = count($periodos) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCE - Sistema de Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        /* ===== MENÚ DE NAVEGACIÓN FIJO ===== */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            color: #2c3e50;
        }
        
        .nav-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-menu {
            display: flex;
            gap: 8px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            color: #5a6c7d;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f0f2f5;
            color: #667eea;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .nav-link i {
            font-size: 16px;
        }
        
        /* Usuario en el menú */
        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #e5e7eb;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #f0f2f5;
            border-radius: 20px;
            font-size: 13px;
            color: #374151;
            font-weight: 500;
        }
        
        .logout-link {
            padding: 8px 12px !important;
            color: #dc2626 !important;
        }
        
        .logout-link:hover {
            background: #fee2e2 !important;
        }
        
        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-user {
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                padding-top: 10px;
                border-top: 1px solid #e5e7eb;
            }
        }
        
        /* Espacio para el menú fijo */
        body {
            padding-top: 80px;
        }
        
        .hero-section {
            text-align: center;
            padding: 60px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            margin-bottom: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .hero-section h1 {
            margin: 0 0 15px 0;
            font-size: 48px;
        }
        
        .hero-section p {
            margin: 0;
            font-size: 18px;
            opacity: 0.95;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .section-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 32px;
        }

        .periodo-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .periodo-selector label {
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }

        .periodo-selector select {
            padding: 8px 16px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            color: #495057;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 250px;
        }

        .periodo-selector select:hover {
            border-color: #3498db;
        }

        .periodo-selector select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .periodo-info {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-admin {
            padding: 12px 24px;
            background: #e67e22;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-admin:hover {
            background: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.4);
            cursor: pointer;
        }
        
        .grupos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        @media (max-width: 768px) {
            .grupos-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .grupo-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            border-top: 5px solid var(--grupo-color);
            height: 280px;
        }
        
        .grupo-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .grupo-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
            flex: 1;
            min-height: 0;
        }
        
        .grupo-icono {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--grupo-color), var(--grupo-color)dd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        
        .grupo-title {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .grupo-title h3 {
            margin: 0;
            font-size: 22px;
            color: #2c3e50;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .grupo-title p {
            margin: 0;
            font-size: 14px;
            color: #7f8c8d;
            line-height: 1.5;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .grupo-stats {
            display: flex;
            gap: 20px;
            padding: 15px 0 0 0;
            border-top: 1px solid #ecf0f1;
            margin-top: auto;
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
            display: block;
        }
        
        .grupo-fecha {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .add-grupo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 250px;
            text-align: center;
            border: 3px dashed rgba(255,255,255,0.3);
        }
        
        .add-grupo-card:hover {
            border-color: rgba(255,255,255,0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .add-grupo-card .icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .add-grupo-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .add-grupo-card p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .main-content {
            display: block !important;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .section-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .close-modal:hover {
            color: #2c3e50;
        }
        
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 10px 0;
        }
        
        .icon-option {
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .icon-option:hover {
            border-color: #3498db;
            background: #ecf0f1;
        }
        
        .icon-option.selected {
            border-color: #3498db;
            background: #e8f4f8;
        }
        
        .color-picker-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .color-picker-group input[type="color"] {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            font-family: inherit;
        }
        
        .grupo-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .grupo-icono {
            width: 80px;
            height: 80px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--grupo-color), var(--grupo-color)dd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        
        .grupo-title h3 {
            margin: 0 0 8px 0;
            font-size: 24px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .grupo-title p {
            margin: 0;
            font-size: 14px;
            color: #7f8c8d;
            line-height: 1.5;
        }
        
        .grupo-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 15px 0;
            border-top: 1px solid #ecf0f1;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: bold;
            color: var(--grupo-color);
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
            display: block;
        }
        
        .add-grupo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            text-align: center;
            border: 3px dashed rgba(255,255,255,0.3);
            border-top: 3px dashed rgba(255,255,255,0.3);
        }
        
        .add-grupo-card:hover {
            border-color: rgba(255,255,255,0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .add-grupo-card .icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .add-grupo-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .add-grupo-card p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .btn-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.2s;
        }
        
        .btn-close:hover {
            color: #2c3e50;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .icon-option {
            font-size: 32px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        
        .icon-option:hover,
        .icon-option.selected {
            border-color: #3498db;
            background: #e8f4f8;
            transform: scale(1.1);
        }
        
        .icon-option-custom {
            grid-column: span 2;
            padding: 12px;
            border: 2px dashed #3498db;
            border-radius: 10px;
            cursor: pointer;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            font-size: 14px;
            color: #3498db;
            font-weight: 500;
        }
        
        .icon-option-custom:hover {
            background: #e8f4f8;
            transform: scale(1.05);
        }
        
        .icon-option-custom i {
            font-size: 18px;
        }
        
        #emojiPickerContainer {
            background: white;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        emoji-picker {
            width: 100%;
            height: 350px;
            --border-radius: 10px;
        }
        
        .color-preview {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .color-presets {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }
        
        .color-preset {
            width: 100%;
            height: 45px;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
            position: relative;
        }
        
        .color-preset:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .color-preset.selected {
            border-color: #2c3e50;
            box-shadow: 0 0 0 2px white, 0 0 0 4px #2c3e50;
        }
        
        .color-preset.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 20px;
            font-weight: bold;
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }
        
        .color-custom {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-top: 10px;
            border-top: 2px solid #ecf0f1;
        }
        
        .color-custom input[type="color"] {
            width: 70px;
            height: 50px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .color-custom label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary-modal {
            background: #3498db;
            color: white;
        }
        
        .btn-primary-modal:hover {
            background: #2980b9;
        }
        
        .btn-secondary-modal {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary-modal:hover {
            background: #d5dbdb;
        }
    </style>
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Inicio</a></li>
            <?php if (puede('estudiantes', 'ver')): ?>
            <li><a href="estudiantes.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <?php endif; ?>
            <?php if (puede('plantillas', 'ver') || esAdmin()): ?>
            <li><a href="admin_fuentes.php" class="nav-link"><i class="fas fa-font"></i> Fuentes</a></li>
            <?php endif; ?>
            <li><a href="verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <?php if (puede('usuarios', 'ver')): ?>
            <li><a href="usuarios.php" class="nav-link"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <?php endif; ?>
            <li class="nav-user">
                <a href="mi_perfil.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-graduation-cap"></i> Sistema de Certificados</h1>
            <p>Casa de la Cultura CCE</p>
        </div>

        <!-- Sección de Grupos -->
        <div class="section-header">
            <h2><i class="fas fa-folder"></i> Grupos de Certificados</h2>
            <?php if (puede('grupos', 'crear')): ?>
            <button onclick="openCreateGrupoModal()" class="btn-admin">
                <i class="fas fa-plus"></i> Crear Grupo
            </button>
            <?php endif; ?>
        </div>

        <div class="grupos-grid">
            <?php if (count($grupos) > 0): ?>
                <?php foreach ($grupos as $grupo): ?>
                    <a href="grupo_detalle.php?id=<?= $grupo['id'] ?>" class="grupo-card" style="--grupo-color: <?= $grupo['color'] ?>">
                        <div class="grupo-header">
                            <div class="grupo-icono" style="background: linear-gradient(135deg, <?= $grupo['color'] ?>, <?= $grupo['color'] ?>dd);"><?= htmlspecialchars($grupo['icono']) ?></div>
                            <div class="grupo-title">
                                <h3><?= htmlspecialchars($grupo['nombre']) ?></h3>
                                <p><?= htmlspecialchars($grupo['descripcion']) ?: 'Sin descripción' ?></p>
                            </div>
                        </div>
                        
                        <div class="grupo-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?= $grupo['total_categorias'] ?></span>
                                <span class="stat-label">Categorías</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?= $grupo['total_certificados'] ?></span>
                                <span class="stat-label">Certificados</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if (puede('grupos', 'crear')): ?>
                <a href="javascript:void(0)" onclick="openCreateGrupoModal()" class="grupo-card add-grupo-card">
                    <div class="icon"><i class="fas fa-plus"></i></div>
                    <h3>Nuevo Grupo</h3>
                    <p>Crear un nuevo tipo de certificado</p>
                </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="icon"><i class="fas fa-folder-open"></i></div>
                    <h3>No hay grupos <?= $esInstructor ? 'asignados' : 'creados' ?></h3>
                    <p><?= $esInstructor ? 'No tienes grupos asignados. Contacta al administrador.' : 'Crea tu primer grupo de certificados para comenzar' ?></p>
                    <?php if (puede('grupos', 'crear')): ?>
                    <button onclick="openCreateGrupoModal()" class="btn-admin" style="margin-top: 20px; display: inline-block; border: none;">
                        + Crear Primer Grupo
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para crear grupo -->
    <div id="grupoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nuevo Grupo</h2>
                <button class="btn-close" onclick="closeCreateGrupoModal()">×</button>
            </div>
            <form id="grupoForm">
                <div class="form-group">
                    <label for="nombre">Nombre del Grupo *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           placeholder="Ej: Talleres, Cursos, Concursos">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">
                        Descripción
                        <span id="charCounter" style="float: right; font-size: 12px; color: #95a5a6;">0 / 250</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Describe este grupo..." 
                              maxlength="250"
                              oninput="updateCharCounter(this)"
                              onpaste="handlePaste(event)"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Icono *</label>
                    <input type="hidden" id="icono" name="icono" value="📚">
                    <div class="icon-selector">
                        <div class="icon-option" data-icon="🎻">🎻</div>
                        <div class="icon-option" data-icon="🎹">🎹</div>
                        <div class="icon-option" data-icon="💃">💃</div>
                        <div class="icon-option" data-icon="🎺">🎺</div>
                        <div class="icon-option" data-icon="🥁">🥁</div>
                        <div class="icon-option" data-icon="🎸">🎸</div>
                        <div class="icon-option" data-icon="🎤">🎤</div>
                        <div class="icon-option" data-icon="🎭">🎭</div>
                        <div class="icon-option" data-icon="🎨">🎨</div>
                        <div class="icon-option" data-icon="📚">📚</div>
                        <div class="icon-option" data-icon="✍️">✍️</div>
                        <div class="icon-option" data-icon="🏆">🏆</div>
                        <button type="button" class="icon-option-custom" onclick="toggleEmojiPicker()">
                            <i class="fas fa-search"></i>
                            <span>Buscar más...</span>
                        </button>
                    </div>
                    <div id="emojiPickerContainer" style="display: none; margin-top: 10px; position: relative;">
                        <emoji-picker id="emojiPicker"></emoji-picker>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Color</label>
                    <input type="hidden" id="color" name="color" value="#3498db">
                    <div class="color-preview">
                        <div class="color-presets">
                            <div class="color-preset selected" data-color="#3498db" style="background: #3498db;" onclick="selectPresetColor(this, '#3498db')" title="Azul"></div>
                            <div class="color-preset" data-color="#e74c3c" style="background: #e74c3c;" onclick="selectPresetColor(this, '#e74c3c')" title="Rojo"></div>
                            <div class="color-preset" data-color="#2ecc71" style="background: #2ecc71;" onclick="selectPresetColor(this, '#2ecc71')" title="Verde"></div>
                            <div class="color-preset" data-color="#f39c12" style="background: #f39c12;" onclick="selectPresetColor(this, '#f39c12')" title="Naranja"></div>
                            <div class="color-preset" data-color="#9b59b6" style="background: #9b59b6;" onclick="selectPresetColor(this, '#9b59b6')" title="Púrpura"></div>
                            <div class="color-preset" data-color="#1abc9c" style="background: #1abc9c;" onclick="selectPresetColor(this, '#1abc9c')" title="Turquesa"></div>
                            <div class="color-preset" data-color="#34495e" style="background: #34495e;" onclick="selectPresetColor(this, '#34495e')" title="Gris Oscuro"></div>
                            <div class="color-preset" data-color="#e67e22" style="background: #e67e22;" onclick="selectPresetColor(this, '#e67e22')" title="Zanahoria"></div>
                            <div class="color-preset" data-color="#16a085" style="background: #16a085;" onclick="selectPresetColor(this, '#16a085')" title="Verde Azulado"></div>
                            <div class="color-preset" data-color="#c0392b" style="background: #c0392b;" onclick="selectPresetColor(this, '#c0392b')" title="Rojo Oscuro"></div>
                            <div class="color-preset" data-color="#8e44ad" style="background: #8e44ad;" onclick="selectPresetColor(this, '#8e44ad')" title="Púrpura Oscuro"></div>
                            <div class="color-preset" data-color="#2980b9" style="background: #2980b9;" onclick="selectPresetColor(this, '#2980b9')" title="Azul Oscuro"></div>
                        </div>
                        <div class="color-custom">
                            <label for="customColor">Personalizado:</label>
                            <input type="color" id="customColor" value="#3498db" onchange="selectCustomColor(this.value)">
                            <span id="colorHex" style="font-family: monospace; font-weight: 600; color: #7f8c8d;">#3498db</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary-modal" onclick="closeCreateGrupoModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modal">Crear Grupo</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hayPeriodosReales): ?>
    <!-- Modal de Selección de Períodos -->
    <div id="periodosModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background: white; color: #2c3e50; border-bottom: 1px solid #e0e0e0;">
                <h2 style="color: #2c3e50;"><i class="fas fa-calendar-alt" style="color: #9b59b6;"></i> Asignar Períodos</h2>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i> El grupo <strong id="periodoGrupoNombre"></strong> ha sido creado exitosamente.
                </p>
                
                <p style="color: #7f8c8d; margin-bottom: 20px; font-size: 14px;">
                    <i class="fas fa-info-circle" style="color: #3498db;"></i> Selecciona los períodos que deseas asignar a este grupo:
                </p>
                
                <input type="hidden" id="periodoGrupoId">
                
                <div id="periodosListContainer" style="max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 10px; padding: 10px;">
                    <?php foreach ($periodos as $periodo): ?>
                    <label class="periodo-item" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; margin-bottom: 5px;">
                        <input type="checkbox" class="periodo-checkbox" value="<?= $periodo['id'] ?>" style="width: 18px; height: 18px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($periodo['nombre']) ?></div>
                            <div style="font-size: 12px; color: #7f8c8d;">
                                <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="modal-actions" style="margin-top: 25px; display: flex; gap: 10px; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary-modal" onclick="closePeriodosModal()">
                        Omitir (sin períodos)
                    </button>
                    <button type="button" class="btn btn-primary-modal" onclick="savePeriodosSelection()">
                        <i class="fas fa-check"></i> Asignar Períodos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .periodo-item:hover {
            background: #f0f7ff;
        }
        .periodo-item input:checked + div {
            color: #3498db;
        }
        #periodosListContainer::-webkit-scrollbar {
            width: 6px;
        }
        #periodosListContainer::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        #periodosListContainer::-webkit-scrollbar-thumb {
            background: #c0c0c0;
            border-radius: 3px;
        }
        #periodosListContainer::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
    </style>
    <?php endif; ?>

    <script>
        // Selector de iconos
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('icono').value = this.dataset.icon;
                // Ocultar picker si está abierto
                document.getElementById('emojiPickerContainer').style.display = 'none';
            });
        });
        
        // Funciones para contador de caracteres
        function updateCharCounter(textarea) {
            const count = textarea.value.length;
            const max = textarea.getAttribute('maxlength');
            const counter = document.getElementById('charCounter');
            counter.textContent = `${count} / ${max}`;
            
            // Cambiar color según proximidad al límite
            if (count > max * 0.9) {
                counter.style.color = '#e74c3c';
            } else if (count > max * 0.7) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = '#95a5a6';
            }
        }
        
        function handlePaste(event) {
            const textarea = event.target;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            
            // Obtener el texto pegado
            setTimeout(() => {
                if (textarea.value.length > maxLength) {
                    textarea.value = textarea.value.substring(0, maxLength);
                    updateCharCounter(textarea);
                    alert(`El texto ha sido recortado al límite de ${maxLength} caracteres.`);
                }
            }, 0);
        }
        
        // Funciones para selector de color
        function selectPresetColor(element, color) {
            // Remover selección de todos los presets
            document.querySelectorAll('.color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            
            // Seleccionar el preset clickeado
            element.classList.add('selected');
            
            // Actualizar el input hidden y el color personalizado
            document.getElementById('color').value = color;
            document.getElementById('customColor').value = color;
            document.getElementById('colorHex').textContent = color;
        }
        
        function selectCustomColor(color) {
            // Remover selección de todos los presets
            document.querySelectorAll('.color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            
            // Actualizar valores
            document.getElementById('color').value = color;
            document.getElementById('colorHex').textContent = color;
        }
        
        // Toggle emoji picker
        function toggleEmojiPicker() {
            const container = document.getElementById('emojiPickerContainer');
            container.style.display = container.style.display === 'none' ? 'block' : 'none';
        }
        
        // Configurar emoji picker cuando se carga
        document.addEventListener('DOMContentLoaded', function() {
            const picker = document.getElementById('emojiPicker');
            if (picker) {
                picker.addEventListener('emoji-click', event => {
                    const emoji = event.detail.unicode;
                    // Deseleccionar opciones predefinidas
                    document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                    // Establecer el emoji seleccionado
                    document.getElementById('icono').value = emoji;
                    // Ocultar el picker
                    document.getElementById('emojiPickerContainer').style.display = 'none';
                    // Mostrar feedback visual permanente
                    const customBtn = document.querySelector('.icon-option-custom');
                    customBtn.innerHTML = `<span style="font-size: 24px;">${emoji}</span><span>Cambiar...</span>`;
                    customBtn.classList.add('selected');
                });
            }
        });
        
        function openCreateGrupoModal() {
            document.getElementById('grupoModal').classList.add('active');
            document.getElementById('grupoForm').reset();
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            document.querySelector('.icon-option[data-icon="📚"]').classList.add('selected');
            document.getElementById('icono').value = '📚';
            document.getElementById('emojiPickerContainer').style.display = 'none';
            // Resetear botón personalizado
            const customBtn = document.querySelector('.icon-option-custom');
            customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar más...</span>';
            customBtn.classList.remove('selected');
            
            // Resetear contador de caracteres
            document.getElementById('charCounter').textContent = '0 / 250';
            document.getElementById('charCounter').style.color = '#95a5a6';
            
            // Resetear selector de color al azul por defecto
            document.querySelectorAll('.color-preset').forEach(preset => {
                preset.classList.remove('selected');
            });
            document.querySelector('.color-preset[data-color="#3498db"]').classList.add('selected');
            document.getElementById('color').value = '#3498db';
            document.getElementById('customColor').value = '#3498db';
            document.getElementById('colorHex').textContent = '#3498db';
        }
        
        function closeCreateGrupoModal() {
            document.getElementById('grupoModal').classList.remove('active');
            document.getElementById('emojiPickerContainer').style.display = 'none';
        }
        
        document.getElementById('grupoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            // Verificar que el icono esté establecido
            const iconoValue = document.getElementById('icono').value;
            if (!iconoValue) {
                alert('Por favor selecciona un icono');
                return;
            }
            
            try {
                const response = await fetch('api_grupos.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error del servidor:', errorText);
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    closeCreateGrupoModal();
                    // Solo mostrar modal de períodos si existen periodos reales
                    <?php if ($hayPeriodosReales): ?>
                    openPeriodosModal(data.grupo_id, formData.get('nombre'));
                    <?php else: ?>
                    // No hay periodos reales, redirigir directamente al grupo
                    window.location.href = 'grupo_detalle.php?id=' + data.grupo_id;
                    <?php endif; ?>
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error completo:', error);
                alert('Error al crear el grupo: ' + error.message);
            }
        });
        
        <?php if ($hayPeriodosReales): ?>
        // Funciones para el modal de períodos
        function openPeriodosModal(grupoId, grupoNombre) {
            document.getElementById('periodoGrupoId').value = grupoId;
            document.getElementById('periodoGrupoNombre').textContent = grupoNombre;
            
            // Desmarcar todos los checkboxes de períodos
            document.querySelectorAll('.periodo-checkbox').forEach(cb => {
                cb.checked = false;
            });
            document.getElementById('periodosModal').classList.add('active');
        }
        
        function closePeriodosModal() {
            const grupoId = document.getElementById('periodoGrupoId').value;
            document.getElementById('periodosModal').classList.remove('active');
            // Redirigir al grupo sin asignar periodos
            window.location.href = 'grupo_detalle.php?id=' + grupoId;
        }
        
        async function savePeriodosSelection() {
            const grupoId = document.getElementById('periodoGrupoId').value;
            const selectedPeriodos = [];
            
            // Obtener períodos seleccionados
            document.querySelectorAll('.periodo-checkbox:checked').forEach(cb => {
                selectedPeriodos.push(cb.value);
            });
            
            if (selectedPeriodos.length === 0) {
                // Si no hay períodos seleccionados, solo cerrar y redirigir
                closePeriodosModal();
                return;
            }
            
            try {
                const response = await fetch('api_grupos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'assign_periodos',
                        grupo_id: grupoId,
                        periodos: selectedPeriodos
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'grupo_detalle.php?id=' + grupoId;
                } else {
                    alert('Error al asignar períodos: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al asignar períodos');
            }
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('periodosModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePeriodosModal();
            }
        });
        <?php endif; ?>
        
        // Cerrar modal de grupo al hacer clic fuera
        document.getElementById('grupoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateGrupoModal();
            }
        });
    </script>

</body>
</html>