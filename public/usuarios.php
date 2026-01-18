<?php
/**
 * Gestión de Usuarios - CCE Certificados
 */

require_once '../includes/Auth.php';
Auth::requireAuth();
Auth::requirePermission('usuarios', 'ver');

$usuario = Auth::user();
$puede_crear = puede('usuarios', 'crear');
$puede_editar = puede('usuarios', 'editar');
$puede_eliminar = puede('usuarios', 'eliminar');
$es_superadmin = esSuperAdmin();
$es_admin = esAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - CCE Certificados</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* ===== HERO SECTION ===== */
        .hero-section {
            text-align: center;
            padding: 50px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .hero-section h1 {
            margin: 0 0 15px 0;
            font-size: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .hero-section p {
            margin: 0;
            font-size: 16px;
            opacity: 0.95;
        }
        
        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-header h2 i {
            color: #667eea;
        }
        
        .btn-admin {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* ===== TABLE CONTAINER ===== */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        /* ===== TABLE HEADER (FILTROS) ===== */
        .table-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #5a6c7d;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            color: #5a6c7d;
            background: white;
            cursor: pointer;
            min-width: 150px;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* ===== TABLE ===== */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tbody tr {
            transition: background 0.2s;
        }
        
        .users-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .users-table td {
            font-size: 14px;
            color: #374151;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-info-cell strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .user-info-cell span {
            font-size: 12px;
            color: #95a5a6;
        }
        
        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .badge-purple {
            background: #ede9fe;
            color: #5b21b6;
        }
        
        /* ===== ADMIN COUNTER ===== */
        .admin-counter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .admin-counter.full {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* ===== NOTIFICACIONES ===== */
        #notification-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 3000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }
        
        .notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            transition: opacity 0.3s;
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
        
        .notification i {
            font-size: 20px;
        }
        
        .notification span {
            flex: 1;
            font-size: 14px;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        .notification-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .notification-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .notification-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        .notification-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        /* ===== ACTIONS ===== */
        .actions-cell {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: #f0f2f5;
            color: #5a6c7d;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-icon.danger:hover {
            background: #dc2626;
            color: white;
        }
        
        /* ===== TABLE FOOTER (PAGINACIÓN) ===== */
        .table-footer {
            padding: 15px 25px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-info {
            font-size: 14px;
            color: #6b7280;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .pagination-btn {
            padding: 8px 14px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #5a6c7d;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover:not(:disabled) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .pagination-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .per-page-select {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .per-page-select label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .per-page-select select {
            padding: 6px 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
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
            padding: 30px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-title {
            margin: 0;
            font-size: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-title i {
            color: #667eea;
        }
        
        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
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
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 45px;
        }
        
        .password-toggle button {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #95a5a6;
            font-size: 16px;
        }
        
        .password-toggle button:hover {
            color: #667eea;
        }
        
        .help-text {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f2f5;
            color: #5a6c7d;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group select {
                flex: 1;
            }
        }
        
        @media (max-width: 768px) {
            .users-table th,
            .users-table td {
                padding: 12px 15px;
            }
            
            .table-footer {
                flex-direction: column;
                text-align: center;
            }
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
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="estudiantes.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="verify.php" class="nav-link"><i class="fas fa-search"></i> Verificar</a></li>
            <li><a href="usuarios.php" class="nav-link active"><i class="fas fa-user-cog"></i> Usuarios</a></li>
            <li class="nav-user">
                <a href="mi_perfil.php" class="nav-link" title="Mi Perfil"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($usuario['nombre_completo']) ?></a>
                <a href="logout.php" class="nav-link logout-link" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
            <p>Administra los usuarios y permisos del sistema</p>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h2><i class="fas fa-user-friends"></i> Usuarios del Sistema</h2>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span id="admin-counter" class="admin-counter"><i class="fas fa-user-shield"></i> Administradores: 0/3</span>
                <?php if ($puede_crear): ?>
                <button class="btn-admin" onclick="abrirModalNuevo()">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            <!-- Table Header (Filtros) -->
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, usuario o email...">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Rol:</label>
                    <select id="filterRol">
                        <option value="">Todos los roles</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-toggle-on"></i> Estado:</label>
                    <select id="filterEstado">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            
            <!-- Table -->
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuarios-body">
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Cargando usuarios...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Table Footer (Paginación) -->
            <div class="table-footer">
                <div class="table-info">
                    Mostrando <span id="showingFrom">0</span> - <span id="showingTo">0</span> de <span id="totalUsers">0</span> usuarios
                </div>
                <div class="per-page-select">
                    <label>Mostrar:</label>
                    <select id="perPage">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="pagination" id="pagination">
                    <!-- Se genera dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuario -->
    <div class="modal" id="modal-usuario">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-plus"></i> <span id="modal-titulo-text">Nuevo Usuario</span></h3>
                <button class="btn-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form id="form-usuario" onsubmit="guardarUsuario(event)">
                <input type="hidden" id="usuario-id">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-at"></i> Nombre de Usuario *</label>
                    <input type="text" id="username" required minlength="3" autocomplete="off" placeholder="usuario123">
                </div>
                
                <div class="form-group">
                    <label for="nombre_completo"><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" id="nombre_completo" required placeholder="Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="email" required placeholder="correo@ejemplo.com">
                </div>
                
                <div class="form-group">
                    <label for="rol_id"><i class="fas fa-user-tag"></i> Rol *</label>
                    <select id="rol_id" required onchange="onRolChange()">
                        <option value="">Seleccionar rol...</option>
                    </select>
                    <p class="help-text" id="admin-limit-warning" style="color: #dc2626; display: none;">
                        <i class="fas fa-exclamation-triangle"></i> Límite de 3 administradores alcanzado
                    </p>
                </div>
                
                <div class="form-group" id="superadmin-group" style="display: none;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="es_superadmin">
                        <label for="es_superadmin"><i class="fas fa-crown"></i> Superadministrador</label>
                    </div>
                    <p class="help-text">El superadministrador puede gestionar a otros administradores</p>
                </div>
                
                <div class="form-group">
                    <label for="cedula"><i class="fas fa-id-card"></i> Cédula/DNI</label>
                    <input type="text" id="cedula" placeholder="Número de identificación">
                </div>
                
                <div class="form-group">
                    <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" id="telefono" placeholder="+58 412 1234567">
                </div>
                
                <div class="form-group">
                    <label for="direccion"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" id="direccion" placeholder="Dirección completa">
                </div>
                
                <div class="form-group">
                    <label for="password" id="label-password"><i class="fas fa-lock"></i> Contraseña *</label>
                    <div class="password-toggle">
                        <input type="password" id="password" minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                        <button type="button" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                    </div>
                    <p class="help-text" id="help-password">Mínimo 6 caracteres</p>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" checked>
                        <label for="activo">Usuario activo</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="notification-container"></div>

    <script src="js/app.js"></script>
    <script>
        // ===== FUNCIÓN DE NOTIFICACIONES =====
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const container = document.getElementById('notification-container');
            if (!container) return;
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${tipo}`;
            
            const iconMap = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            notification.innerHTML = `
                <i class="fas ${iconMap[tipo] || iconMap.info}"></i>
                <span>${mensaje}</span>
                <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            container.appendChild(notification);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Estado
        let roles = [];
        let usuarios = [];
        let usuariosFiltrados = [];
        let modoEdicion = false;
        
        // Paginación
        let currentPage = 1;
        let perPage = 10;
        
        // Permisos
        const puedeEditar = <?php echo $puede_editar ? 'true' : 'false'; ?>;
        const puedeEliminar = <?php echo $puede_eliminar ? 'true' : 'false'; ?>;
        const usuarioActualId = <?php echo $_SESSION['usuario_id']; ?>;
        const esSuperadmin = <?php echo $es_superadmin ? 'true' : 'false'; ?>;
        const esAdmin = <?php echo $es_admin ? 'true' : 'false'; ?>;
        let adminCount = 0;
        const maxAdmins = 3;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', () => {
            cargarRoles();
            cargarUsuarios();
            
            // Event listeners para filtros
            document.getElementById('searchInput').addEventListener('input', filtrarUsuarios);
            document.getElementById('filterRol').addEventListener('change', filtrarUsuarios);
            document.getElementById('filterEstado').addEventListener('change', filtrarUsuarios);
            document.getElementById('perPage').addEventListener('change', (e) => {
                perPage = parseInt(e.target.value);
                currentPage = 1;
                renderizarUsuarios();
            });
        });
        
        // Cargar roles
        async function cargarRoles() {
            try {
                const response = await fetch('api_usuarios.php?action=roles');
                const data = await response.json();
                if (data.success) {
                    roles = data.roles;
                    adminCount = data.admin_count || 0;
                    
                    // Llenar select del modal
                    const selectModal = document.getElementById('rol_id');
                    selectModal.innerHTML = '<option value="">Seleccionar rol...</option>';
                    roles.forEach(rol => {
                        // Mostrar advertencia si es admin y ya hay 3
                        let label = `${rol.nombre} - ${rol.descripcion || ''}`;
                        let disabled = '';
                        if (rol.nombre === 'administrador' && adminCount >= maxAdmins) {
                            label += ' (Límite alcanzado)';
                            disabled = 'disabled';
                        }
                        selectModal.innerHTML += `<option value="${rol.id}" ${disabled}>${label}</option>`;
                    });
                    
                    // Llenar filtro de roles
                    const filterRol = document.getElementById('filterRol');
                    filterRol.innerHTML = '<option value="">Todos los roles</option>';
                    roles.forEach(rol => {
                        filterRol.innerHTML += `<option value="${rol.nombre}">${rol.nombre}</option>`;
                    });
                    
                    // Mostrar contador de admins
                    actualizarContadorAdmins();
                }
            } catch (error) {
                console.error('Error cargando roles:', error);
            }
        }
        
        // Actualizar contador de admins
        function actualizarContadorAdmins() {
            const info = document.getElementById('admin-counter');
            if (info) {
                info.innerHTML = `<i class="fas fa-user-shield"></i> Administradores: ${adminCount}/${maxAdmins}`;
                info.className = adminCount >= maxAdmins ? 'admin-counter full' : 'admin-counter';
            }
        }
        
        // Cargar usuarios
        async function cargarUsuarios() {
            try {
                const response = await fetch('api_usuarios.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    usuarios = data.usuarios;
                    usuariosFiltrados = [...usuarios];
                    adminCount = data.admin_count || 0;
                    actualizarContadorAdmins();
                    renderizarUsuarios();
                } else {
                    mostrarNotificacion(data.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando usuarios:', error);
                mostrarNotificacion('Error al cargar usuarios', 'error');
            }
        }
        
        // Filtrar usuarios
        function filtrarUsuarios() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterRol = document.getElementById('filterRol').value.toLowerCase();
            const filterEstado = document.getElementById('filterEstado').value;
            
            usuariosFiltrados = usuarios.filter(u => {
                const matchSearch = !searchTerm || 
                    u.username.toLowerCase().includes(searchTerm) ||
                    u.nombre_completo.toLowerCase().includes(searchTerm) ||
                    u.email.toLowerCase().includes(searchTerm);
                
                const matchRol = !filterRol || u.rol_nombre.toLowerCase() === filterRol;
                const matchEstado = filterEstado === '' || u.activo.toString() === filterEstado;
                
                return matchSearch && matchRol && matchEstado;
            });
            
            currentPage = 1;
            renderizarUsuarios();
        }
        
        // Renderizar tabla
        function renderizarUsuarios() {
            const tbody = document.getElementById('usuarios-body');
            const totalUsers = usuariosFiltrados.length;
            const totalPages = Math.ceil(totalUsers / perPage);
            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, totalUsers);
            const usuariosPagina = usuariosFiltrados.slice(startIndex, endIndex);
            
            // Actualizar info
            document.getElementById('showingFrom').textContent = totalUsers > 0 ? startIndex + 1 : 0;
            document.getElementById('showingTo').textContent = endIndex;
            document.getElementById('totalUsers').textContent = totalUsers;
            
            if (usuariosPagina.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <h3>No se encontraron usuarios</h3>
                            <p>Intenta ajustar los filtros de búsqueda</p>
                        </td>
                    </tr>
                `;
                renderizarPaginacion(0);
                return;
            }
            
            tbody.innerHTML = usuariosPagina.map(u => {
                const rolBadgeClass = {
                    'administrador': 'danger',
                    'editor': 'warning',
                    'operador': 'primary',
                    'visualizador': 'secondary',
                    'instructor': 'purple',
                    'oficinista': 'warning'
                }[u.rol_nombre] || 'secondary';
                
                const rolIcon = {
                    'administrador': 'fa-user-shield',
                    'editor': 'fa-user-edit',
                    'operador': 'fa-user-cog',
                    'visualizador': 'fa-user',
                    'instructor': 'fa-chalkboard-teacher',
                    'oficinista': 'fa-user-tie'
                }[u.rol_nombre] || 'fa-user';
                
                const estadoBadge = u.activo == 1 
                    ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activo</span>'
                    : '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inactivo</span>';
                
                // Badge de superadmin
                const superadminBadge = u.es_superadmin == 1 
                    ? '<span class="badge badge-purple" title="Superadministrador"><i class="fas fa-crown"></i></span>' 
                    : '';
                
                const ultimoAcceso = u.ultimo_acceso 
                    ? formatearFecha(u.ultimo_acceso)
                    : '<span style="color: #95a5a6"><i class="fas fa-clock"></i> Nunca</span>';
                
                const iniciales = u.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                
                let acciones = '';
                if (puedeEditar) {
                    acciones += `<button class="btn-icon" onclick="editarUsuario(${u.id})" title="Editar"><i class="fas fa-edit"></i></button>`;
                }
                // No permitir eliminar superadmin a menos que sea superadmin
                const puedeEliminarEste = puedeEliminar && u.id != usuarioActualId && 
                    (u.es_superadmin != 1 || esSuperadmin);
                if (puedeEliminarEste) {
                    acciones += `<button class="btn-icon danger" onclick="eliminarUsuario(${u.id}, '${escapeHtml(u.username)}')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>`;
                }
                
                return `
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar">${iniciales}</div>
                                <div class="user-info-cell">
                                    <strong>${escapeHtml(u.nombre_completo)} ${superadminBadge}</strong>
                                    <span>@${escapeHtml(u.username)}</span>
                                </div>
                            </div>
                        </td>
                        <td>${escapeHtml(u.email)}</td>
                        <td><span class="badge badge-${rolBadgeClass}"><i class="fas ${rolIcon}"></i> ${escapeHtml(u.rol_nombre)}</span></td>
                        <td>${estadoBadge}</td>
                        <td>${ultimoAcceso}</td>
                        <td class="actions-cell">${acciones}</td>
                    </tr>
                `;
            }).join('');
            
            renderizarPaginacion(totalPages);
        }
        
        // Renderizar paginación
        function renderizarPaginacion(totalPages) {
            const container = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // Botón anterior
            html += `<button class="pagination-btn" onclick="cambiarPagina(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>`;
            
            // Páginas
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            if (endPage - startPage + 1 < maxVisible) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            if (startPage > 1) {
                html += `<button class="pagination-btn" onclick="cambiarPagina(1)">1</button>`;
                if (startPage > 2) {
                    html += `<span style="padding: 0 8px; color: #95a5a6;">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="cambiarPagina(${i})">${i}</button>`;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span style="padding: 0 8px; color: #95a5a6;">...</span>`;
                }
                html += `<button class="pagination-btn" onclick="cambiarPagina(${totalPages})">${totalPages}</button>`;
            }
            
            // Botón siguiente
            html += `<button class="pagination-btn" onclick="cambiarPagina(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>`;
            
            container.innerHTML = html;
        }
        
        // Cambiar página
        function cambiarPagina(page) {
            const totalPages = Math.ceil(usuariosFiltrados.length / perPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderizarUsuarios();
        }
        
        // Formatear fecha
        function formatearFecha(fecha) {
            const d = new Date(fecha);
            return d.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Abrir modal nuevo usuario
        function abrirModalNuevo() {
            modoEdicion = false;
            document.getElementById('modal-titulo-text').textContent = 'Nuevo Usuario';
            document.querySelector('.modal-title i').className = 'fas fa-user-plus';
            document.getElementById('usuario-id').value = '';
            document.getElementById('form-usuario').reset();
            document.getElementById('activo').checked = true;
            document.getElementById('es_superadmin').checked = false;
            document.getElementById('password').required = true;
            document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Contraseña *';
            document.getElementById('help-password').textContent = 'Mínimo 6 caracteres';
            document.getElementById('superadmin-group').style.display = 'none';
            document.getElementById('admin-limit-warning').style.display = 'none';
            
            // Refrescar opciones de rol
            cargarRoles();
            
            document.getElementById('modal-usuario').classList.add('active');
        }
        
        // Manejar cambio de rol
        function onRolChange() {
            const rolId = document.getElementById('rol_id').value;
            const rolNombre = roles.find(r => r.id == rolId)?.nombre || '';
            
            // Mostrar opción de superadmin solo para administradores y si el usuario actual es superadmin
            if (rolNombre === 'administrador' && esSuperadmin) {
                document.getElementById('superadmin-group').style.display = 'block';
            } else {
                document.getElementById('superadmin-group').style.display = 'none';
                document.getElementById('es_superadmin').checked = false;
            }
            
            // Verificar límite de admins
            if (rolNombre === 'administrador' && adminCount >= maxAdmins && !modoEdicion) {
                document.getElementById('admin-limit-warning').style.display = 'block';
            } else {
                document.getElementById('admin-limit-warning').style.display = 'none';
            }
        }
        
        // Editar usuario
        async function editarUsuario(id) {
            try {
                const response = await fetch(`api_usuarios.php?action=get&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    modoEdicion = true;
                    const u = data.usuario;
                    
                    document.getElementById('modal-titulo-text').textContent = 'Editar Usuario';
                    document.querySelector('.modal-title i').className = 'fas fa-user-edit';
                    document.getElementById('usuario-id').value = u.id;
                    document.getElementById('username').value = u.username;
                    document.getElementById('nombre_completo').value = u.nombre_completo;
                    document.getElementById('email').value = u.email;
                    document.getElementById('rol_id').value = u.rol_id;
                    document.getElementById('activo').checked = u.activo == 1;
                    document.getElementById('cedula').value = u.cedula || '';
                    document.getElementById('telefono').value = u.telefono || '';
                    document.getElementById('direccion').value = u.direccion || '';
                    document.getElementById('es_superadmin').checked = u.es_superadmin == 1;
                    document.getElementById('password').value = '';
                    document.getElementById('password').required = false;
                    document.getElementById('label-password').innerHTML = '<i class="fas fa-lock"></i> Nueva Contraseña (opcional)';
                    document.getElementById('help-password').textContent = 'Dejar vacío para mantener la contraseña actual';
                    
                    // Mostrar opción superadmin si corresponde
                    if (u.rol_nombre === 'administrador' && esSuperadmin) {
                        document.getElementById('superadmin-group').style.display = 'block';
                    } else {
                        document.getElementById('superadmin-group').style.display = 'none';
                    }
                    
                    document.getElementById('admin-limit-warning').style.display = 'none';
                    
                    document.getElementById('modal-usuario').classList.add('active');
                } else {
                    mostrarNotificacion(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error al cargar usuario', 'error');
            }
        }
        
        // Guardar usuario
        async function guardarUsuario(event) {
            event.preventDefault();
            
            const id = document.getElementById('usuario-id').value;
            const datos = {
                action: id ? 'update' : 'create',
                id: id || undefined,
                username: document.getElementById('username').value.trim(),
                nombre_completo: document.getElementById('nombre_completo').value.trim(),
                email: document.getElementById('email').value.trim(),
                rol_id: document.getElementById('rol_id').value,
                activo: document.getElementById('activo').checked ? 1 : 0,
                password: document.getElementById('password').value,
                cedula: document.getElementById('cedula').value.trim(),
                telefono: document.getElementById('telefono').value.trim(),
                direccion: document.getElementById('direccion').value.trim(),
                es_superadmin: document.getElementById('es_superadmin').checked ? 1 : 0
            };
            
            // No enviar password vacío en edición
            if (id && !datos.password) {
                delete datos.password;
            }
            
            try {
                const response = await fetch('api_usuarios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });
                const data = await response.json();
                
                if (data.success) {
                    mostrarNotificacion(data.message, 'success');
                    cerrarModal();
                    cargarUsuarios();
                    cargarRoles(); // Refrescar para actualizar contador de admins
                } else {
                    mostrarNotificacion(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error al guardar usuario', 'error');
            }
        }
        
        // Eliminar usuario
        async function eliminarUsuario(id, username) {
            if (!confirm(`¿Estás seguro de eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            try {
                const response = await fetch('api_usuarios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                const data = await response.json();
                
                if (data.success) {
                    mostrarNotificacion(data.message, 'success');
                    cargarUsuarios();
                } else {
                    mostrarNotificacion(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error al eliminar usuario', 'error');
            }
        }
        
        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modal-usuario').classList.remove('active');
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-usuario').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
