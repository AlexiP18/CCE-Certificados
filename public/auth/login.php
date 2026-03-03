<?php
/**
 * Página de Login - CCE Certificados
 */

require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Si ya está autenticado, redirigir al inicio
if (Auth::check()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingresa usuario y contraseña';
    } else {
        $resultado = Auth::login($username, $password);
        
        if ($resultado['success']) {
            header('Location: ../dashboard/index.php');
            exit;
        } else {
            $error = $resultado['message'];
        }
    }
}

// Cargar la vista
require_once '../../app/Views/login/index.php';
