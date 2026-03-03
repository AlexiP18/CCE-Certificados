<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estudiantes_referencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            estudiante_id INT NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            telefono VARCHAR(20),
            relacion VARCHAR(50),
            FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabla 'estudiantes_referencias' creada correctamente.\n";
} catch (PDOException $e) {
    echo "Error creando tabla: " . $e->getMessage() . "\n";
}
