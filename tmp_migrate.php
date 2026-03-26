<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

try {
    $pdo->exec("ALTER TABLE certificados ADD COLUMN periodo_id INT(11) NULL DEFAULT NULL AFTER categoria_id");
    echo "Columna periodo_id añadida correctamente.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
