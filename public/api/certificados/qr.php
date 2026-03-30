<?php
require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Opcional: verificar auth
Auth::requireAuth();

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    http_response_code(400);
    exit('Código no proporcionado');
}

$url = BASE_URL . "/certificados/buscar.php?codigo=" . $codigo;

$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'   => QRCode::ECC_L,
    'scale'      => 5,
    'imageBase64'=> false // Return raw PNG data, not base64!
]);

$qrcode = new QRCode($options);

header('Content-Type: image/png');
echo $qrcode->render($url);
exit();
