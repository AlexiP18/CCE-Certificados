<?php
require_once dirname(__DIR__) . '/config/database.php';

echo "=== Tabla estudiantes ===\n";
$stmt = $pdo->query('DESCRIBE estudiantes');
while($r = $stmt->fetch()) {
    echo $r['Field'] . PHP_EOL;
}

echo "\n=== Tabla certificados ===\n";
$stmt = $pdo->query('DESCRIBE certificados');
while($r = $stmt->fetch()) {
    echo $r['Field'] . PHP_EOL;
}
