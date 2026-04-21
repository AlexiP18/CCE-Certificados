<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();
$stmt = $pdo->query("DESCRIBE certificados");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columnas en certificados:\n";
foreach($cols as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
