<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

$stmt = $pdo->query("SELECT * FROM estudiantes_auditoria ORDER BY id DESC LIMIT 5");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Auditoria:\n";
print_r($res);
