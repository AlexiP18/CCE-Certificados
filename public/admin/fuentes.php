<?php
/**
 * Administración de Fuentes para Certificados
 * Permite subir, visualizar y gestionar fuentes personalizadas
 */
require_once '../../includes/Auth.php';
require_once '../../includes/SiteSettings.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

// Definir variables para la vista
$pageTitle = 'Administrar Fuentes';
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';
$siteConfig = SiteSettings::toViewModel(SiteSettings::get($pdo), $basePath);

// Cargar la vista
require_once '../../app/Views/admin_fuentes/index.php';
