<?php
/**
 * Administración de Fuentes para Certificados
 * Permite subir, visualizar y gestionar fuentes personalizadas
 */
require_once '../includes/Auth.php';
require_once '../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

$pageTitle = 'Administrar Fuentes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
            background-color: #f8f9fa;
        }
        
        /* ===== CONTENIDO PRINCIPAL ===== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-primary-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* Estilos para la cuadrícula de fuentes */
        .font-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .font-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .font-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .font-preview {
            padding: 30px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            text-align: center;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .font-preview-text {
            font-size: 32px;
            color: #2c3e50;
        }
        
        .font-info {
            padding: 15px;
        }
        
        .font-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .font-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .font-meta span {
            margin-right: 12px;
        }
        
        .font-actions {
            display: flex;
            gap: 8px;
        }
        
        .font-actions .btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #f0f2f5;
            border: none;
            color: #667eea;
        }
        
        .btn-edit:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-delete {
            background: #fef2f2;
            border: none;
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            color: white;
        }
        
        .badge-system {
            background-color: #10b981;
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .badge-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .badge-google {
            background: linear-gradient(135deg, #4285f4, #34a853);
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-inactive {
            background-color: #dc2626;
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        /* Category filters */
        .category-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 10px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            background: white;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
        }
        
        .category-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .category-btn.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        /* Modal styles */
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
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-box {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: all 0.3s;
        }
        
        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .modal-close:hover {
            opacity: 1;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 15px 25px 25px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Upload zone */
        .upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f9fafb;
            margin-bottom: 20px;
            display: block;
        }
        
        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .upload-zone i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .upload-zone h5 {
            color: #374151;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        
        .upload-zone p {
            color: #9ca3af;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #9ca3af;
            font-size: 12px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h5 {
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #9ca3af;
        }
        
        /* Loading state */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Notificaciones */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Navegación Superior -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="estudiantes.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="admin_fuentes.php" class="nav-link active"><i class="fas fa-font"></i> Fuentes</a></li>
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
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h1 class="page-title">
                    <i class="fas fa-font"></i> <?php echo $pageTitle; ?>
                </h1>
                <button class="btn-primary-gradient" onclick="openUploadModal()">
                    <i class="fas fa-cloud-upload-alt"></i> Subir Nueva Fuente
                </button>
            </div>
        </div>
        
        <!-- Filtros por categoría -->
        <div class="category-filters">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th-large"></i> Todas
            </button>
            <button class="category-btn" data-category="sans-serif">
                Sans Serif
            </button>
            <button class="category-btn" data-category="serif">
                Serif
            </button>
            <button class="category-btn" data-category="display">
                Display
            </button>
            <button class="category-btn" data-category="handwriting">
                Manuscritas
            </button>
            <button class="category-btn" data-category="monospace">
                Monoespaciadas
            </button>
        </div>
        
        <!-- Contenedor de fuentes -->
        <div id="fonts-container">
            <div class="loading-state">
                <div class="spinner"></div>
                <p style="color: #6b7280;">Cargando fuentes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Subida -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Subir Nueva Fuente</h3>
                <button class="modal-close" onclick="closeModal('uploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <label class="upload-zone" id="dropZone">
                        <input type="file" name="archivo" id="fileInput" accept=".ttf,.otf,.woff,.woff2" hidden>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5 id="dropZoneText">Arrastra un archivo aquí o haz clic para seleccionar</h5>
                        <p>Formatos: TTF, OTF, WOFF, WOFF2</p>
                    </label>
                    
                    <div class="form-group">
                        <label for="fontName">Nombre de la fuente *</label>
                        <input type="text" id="fontName" name="nombre" required placeholder="Ej: Arial Bold">
                        <small>Este nombre se mostrará en los selectores de fuente</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fontCategory">Categoría</label>
                        <select id="fontCategory" name="categoria">
                            <option value="sans-serif">Sans Serif</option>
                            <option value="serif">Serif</option>
                            <option value="display">Display</option>
                            <option value="handwriting">Manuscritas</option>
                            <option value="monospace">Monoespaciadas</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnUpload" onclick="uploadFont()">
                    <i class="fas fa-upload"></i> Subir Fuente
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edición -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Fuente</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editFontId" name="id">
                    
                    <div class="form-group">
                        <label for="editFontName">Nombre de la fuente</label>
                        <input type="text" id="editFontName" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFontCategory">Categoría</label>
                        <select id="editFontCategory" name="categoria">
                            <option value="sans-serif">Sans Serif</option>
                            <option value="serif">Serif</option>
                            <option value="display">Display</option>
                            <option value="handwriting">Manuscritas</option>
                            <option value="monospace">Monoespaciadas</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="editFontActive" name="activo" value="1" checked>
                        <label for="editFontActive">Fuente activa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit" onclick="saveEdit()">
                    <i class="fas fa-check"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <h3><i class="fas fa-trash-alt"></i> Eliminar Fuente</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la fuente <strong id="deleteFontName"></strong>?</p>
                <p style="color: #dc2626; font-size: 14px; margin-bottom: 0;">
                    <i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.
                </p>
                <input type="hidden" id="deleteFontId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete" onclick="deleteFont()">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Estado global
        let fonts = [];
        let currentFilter = 'all';
        
        // Cargar fuentes al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadFonts();
            setupDragDrop();
            setupEventListeners();
        });
        
        // Cargar lista de fuentes
        async function loadFonts() {
            try {
                const response = await fetch('api_fuentes.php?action=list&solo_activas=0');
                const data = await response.json();
                
                if (data.success) {
                    fonts = data.fuentes;
                    
                    // Debug: mostrar fuentes cargadas
                    console.log('Fuentes cargadas:', fonts.length);
                    fonts.forEach(f => console.log(`- ${f.nombre}: ${f.archivo}`));
                    
                    await loadFontStyles();
                    renderFonts();
                } else {
                    showNotification(data.message || 'Error al cargar fuentes', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión al cargar fuentes', 'error');
            }
        }
        
        // Cargar estilos CSS de las fuentes
        async function loadFontStyles() {
            let styleElement = document.getElementById('font-styles');
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = 'font-styles';
                document.head.appendChild(styleElement);
            }
            
            // Obtener la ruta base del proyecto
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const fontsPath = basePath.replace('/public', '') + '/assets/fonts/';
            
            let css = '';
            let googleFonts = [];
            
            fonts.forEach(font => {
                // Verificar si es una fuente de Google
                if (font.archivo && font.archivo.startsWith('google:')) {
                    const fontName = font.archivo.replace('google:', '');
                    googleFonts.push(fontName);
                    // Crear clase CSS para aplicar la fuente de Google
                    css += `.preview-font-${font.id} { font-family: "${font.nombre}", Arial, sans-serif !important; }\n`;
                } else {
                    // Fuente local
                    let format = 'truetype';
                    switch(font.tipo.toLowerCase()) {
                        case 'ttf': format = 'truetype'; break;
                        case 'otf': format = 'opentype'; break;
                        case 'woff': format = 'woff'; break;
                        case 'woff2': format = 'woff2'; break;
                    }
                    
                    const fontFamilyName = `CustomFont${font.id}`;
                    
                    css += `
@font-face { 
    font-family: "${fontFamilyName}"; 
    src: url("${fontsPath}${font.archivo}") format("${format}");
    font-display: swap;
    font-weight: normal;
    font-style: normal;
}
`;
                    css += `.preview-font-${font.id} { font-family: "${fontFamilyName}", Arial, sans-serif !important; }\n`;
                }
            });
            
            // Cargar Google Fonts si hay alguna
            if (googleFonts.length > 0) {
                const googleLink = document.getElementById('google-fonts-link');
                if (googleLink) googleLink.remove();
                
                const link = document.createElement('link');
                link.id = 'google-fonts-link';
                link.rel = 'stylesheet';
                link.href = 'https://fonts.googleapis.com/css2?family=' + googleFonts.join('&family=') + '&display=swap';
                document.head.appendChild(link);
            }
            
            styleElement.textContent = css;
            
            // Esperar a que el navegador procese los estilos
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Intentar precargar las fuentes
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }
        }
        
        // Renderizar cuadrícula de fuentes
        function renderFonts() {
            const container = document.getElementById('fonts-container');
            
            // Filtrar fuentes
            let filtered = currentFilter === 'all' 
                ? fonts 
                : fonts.filter(f => f.categoria === currentFilter);
            
            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-font"></i>
                        <h5>No hay fuentes ${currentFilter !== 'all' ? 'en esta categoría' : 'disponibles'}</h5>
                        <p>Sube una nueva fuente para comenzar</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="font-grid">
                    ${filtered.map(font => renderFontCard(font)).join('')}
                </div>
            `;
            
            // Dar tiempo para que las fuentes carguen y re-renderizar
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(() => {
                    // Forzar un repaint de los elementos de preview
                    document.querySelectorAll('.font-preview-text').forEach(el => {
                        el.style.opacity = '0.99';
                        setTimeout(() => { el.style.opacity = '1'; }, 10);
                    });
                });
            }
        }
        
        // Renderizar tarjeta de fuente individual
        function renderFontCard(font) {
            const categoryLabels = {
                'sans-serif': 'Sans Serif',
                'serif': 'Serif',
                'display': 'Display',
                'handwriting': 'Manuscrita',
                'monospace': 'Monoespaciada'
            };
            
            // Detectar si es fuente de Google (ya sea por el archivo original o por el nombre del archivo descargado)
            const isGoogleFont = font.archivo && (font.archivo.startsWith('google:') || font.archivo.startsWith('google_'));
            
            let badge;
            if (isGoogleFont) {
                badge = '<span class="badge-google"><i class="fab fa-google"></i> Google</span>';
            } else if (font.es_sistema == 1) {
                badge = '<span class="badge-system">Sistema</span>';
            } else {
                badge = '<span class="badge-custom">Personalizada</span>';
            }
            
            const inactiveBadge = font.activo != 1 
                ? '<span class="badge-inactive">Inactiva</span>' 
                : '';
            
            // Solo permitir eliminar fuentes personalizadas (no Google ni sistema)
            const deleteBtn = (font.es_sistema != 1 && !isGoogleFont)
                ? `<button class="btn btn-delete" onclick="openDeleteModal(${font.id}, '${font.nombre.replace(/'/g, "\\'")}')">
                       <i class="fas fa-trash-alt"></i>
                   </button>` 
                : '';
            
            // Tipo de fuente para mostrar
            const tipoDisplay = isGoogleFont ? 'Web Font' : font.tipo.toUpperCase();
            
            return `
                <div class="font-card ${font.activo != 1 ? 'opacity-50' : ''}" data-category="${font.categoria}" data-font-id="${font.id}">
                    <div class="font-preview">
                        <span class="font-preview-text preview-font-${font.id}">
                            Aa Bb Cc 123
                        </span>
                    </div>
                    <div class="font-info">
                        <div class="font-name">
                            ${escapeHtml(font.nombre)} ${badge} ${inactiveBadge}
                        </div>
                        <div class="font-meta">
                            <span><i class="fas fa-tag"></i> ${categoryLabels[font.categoria] || font.categoria}</span>
                            <span><i class="fas fa-${isGoogleFont ? 'cloud' : 'file'}"></i> ${tipoDisplay}</span>
                        </div>
                        <div class="font-actions">
                            <button class="btn btn-edit" onclick="openEditModal(${font.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            ${deleteBtn}
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Función para escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Configurar drag & drop
        function setupDragDrop() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const dropZoneText = document.getElementById('dropZoneText');
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('drag-over');
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('drag-over');
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('drag-over');
                
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileDisplay(e.dataTransfer.files[0]);
                }
            });
            
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    updateFileDisplay(fileInput.files[0]);
                }
            });
            
            function updateFileDisplay(file) {
                dropZoneText.textContent = file.name;
                
                // Sugerir nombre basado en el archivo
                const fontName = document.getElementById('fontName');
                if (!fontName.value) {
                    const name = file.name.replace(/\.[^/.]+$/, '')
                        .replace(/[-_]/g, ' ')
                        .replace(/([a-z])([A-Z])/g, '$1 $2');
                    fontName.value = name;
                }
            }
        }
        
        // Configurar event listeners
        function setupEventListeners() {
            // Filtros de categoría
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.category;
                    renderFonts();
                });
            });
            
            // Cerrar modales al hacer clic fuera
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeModal(modal.id);
                    }
                });
            });
        }
        
        // Funciones de modal
        function openUploadModal() {
            document.getElementById('uploadForm').reset();
            document.getElementById('dropZoneText').textContent = 'Arrastra un archivo aquí o haz clic para seleccionar';
            document.getElementById('uploadModal').classList.add('active');
        }
        
        function openEditModal(fontId) {
            const font = fonts.find(f => f.id == fontId);
            if (!font) return;
            
            document.getElementById('editFontId').value = font.id;
            document.getElementById('editFontName').value = font.nombre;
            document.getElementById('editFontCategory').value = font.categoria;
            document.getElementById('editFontActive').checked = font.activo == 1;
            document.getElementById('editFontActive').disabled = font.es_sistema == 1;
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function openDeleteModal(fontId, fontName) {
            document.getElementById('deleteFontId').value = fontId;
            document.getElementById('deleteFontName').textContent = fontName;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Subir fuente
        async function uploadFont() {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);
            formData.append('action', 'upload');
            
            const fileInput = document.getElementById('fileInput');
            if (!fileInput.files.length) {
                showNotification('Por favor selecciona un archivo de fuente', 'error');
                return;
            }
            
            const fontName = document.getElementById('fontName').value.trim();
            if (!fontName) {
                showNotification('Por favor ingresa un nombre para la fuente', 'error');
                return;
            }
            
            const btn = document.getElementById('btnUpload');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
            
            try {
                const response = await fetch('api_fuentes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('uploadModal');
                    loadFonts();
                } else {
                    showNotification(data.message || 'Error al subir la fuente', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        // Guardar edición
        async function saveEdit() {
            const formData = new FormData(document.getElementById('editForm'));
            formData.append('action', 'update');
            formData.append('activo', document.getElementById('editFontActive').checked ? '1' : '0');
            
            const btn = document.getElementById('btnSaveEdit');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            try {
                const response = await fetch('api_fuentes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('editModal');
                    loadFonts();
                } else {
                    showNotification(data.message || 'Error al actualizar la fuente', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        // Eliminar fuente
        async function deleteFont() {
            const fontId = document.getElementById('deleteFontId').value;
            
            const btn = document.getElementById('btnConfirmDelete');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', fontId);
                
                const response = await fetch('api_fuentes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('deleteModal');
                    loadFonts();
                } else {
                    showNotification(data.message || 'Error al eliminar la fuente', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        // Mostrar notificación
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
