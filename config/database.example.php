<?php
/**
 * Configuración de Base de Datos
 * IMPORTANTE: Renombrar a database.php y actualizar con tus credenciales
 */

// Configuración para ambiente LOCAL (XAMPP/WAMP)
$config = [
    'host' => 'localhost',
    'database' => 'cce_certificados',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Para producción en cPanel, actualiza estos valores:
// $config = [
//     'host' => 'localhost',
//     'database' => 'tunombre_cce',
//     'username' => 'tunombre_usuario',
//     'password' => 'tu_password_seguro',
//     'charset' => 'utf8mb4'
// ];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// URL base de la aplicación
define('BASE_URL', 'http://localhost/cce-certificados/public');
// Para producción: define('BASE_URL', 'https://tudominio.com');

return $pdo;
