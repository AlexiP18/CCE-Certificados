<?php
require_once '../../includes/Auth.php';
require_once '../../includes/SiteSettings.php';
require_once '../../config/database.php';

Auth::requireAuth();

if (!Auth::isAdmin()) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$usuario = Auth::user();
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';

$siteConfig = SiteSettings::toViewModel(SiteSettings::get($pdo), $basePath);

require_once '../../app/Views/configuracion/index.php';
