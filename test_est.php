<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'administrador';
$_SESSION['es_superadmin'] = true;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'listar_matriculados';
$_GET['categoria_id'] = '88';
$_GET['periodo_id'] = '112';

require_once 'public/api/categorias/estudiantes.php';
