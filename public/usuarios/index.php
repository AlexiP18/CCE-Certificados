<?php
/**
 * Gestión de Usuarios - CCE Certificados
 */

require_once '../../includes/Auth.php';
Auth::requireAuth();
Auth::requirePermission('usuarios', 'ver');

// Variables para la vista
$usuario = Auth::user();
$puede_crear = puede('usuarios', 'crear');
$puede_editar = puede('usuarios', 'editar');
$puede_eliminar = puede('usuarios', 'eliminar');
$es_superadmin = esSuperAdmin();
$es_admin = esAdmin();

// Definir rutas para assets
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';

// Cargar la vista
require_once '../../app/Views/usuarios/index.php';
