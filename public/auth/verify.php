<?php
/**
 * Verificación de Certificados - CCE Certificados
 * Permite a cualquier usuario verificar la autenticidad de un certificado
 */
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

use CCE\Certificate;

// Variables para la vista
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';

// Lógica de búsqueda
$codigo = $_GET['code'] ?? $_POST['code'] ?? '';
$busquedaRealizada = !empty($codigo);
$cert = null;

if ($busquedaRealizada) {
    try {
        $certificate = new Certificate($pdo);
        $cert = $certificate->getByCodigo($codigo);

        // Registrar verificación si se encontró
        if ($cert) {
            $certificate->registrarVerificacion(
                $cert['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            );
        }
    } catch (Exception $e) {
        // En caso de error de conexión o base de datos, podríamos registrar el error
        // Por ahora, simplemente no mostramos el certificado
        error_log("Error verificando certificado: " . $e->getMessage());
    }
}

// Cargar la vista
require_once '../../app/Views/verify/index.php';
