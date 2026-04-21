<?php
/**
 * Script para verificar y mostrar la tabla de fuentes
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

echo "=== Estructura de la tabla fuentes_personalizadas ===\n";
$stmt = $pdo->query("DESCRIBE fuentes_personalizadas");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "- {$col['Field']}: {$col['Type']}\n";
}

echo "\n=== Fuentes existentes ===\n";
$stmt = $pdo->query("SELECT * FROM fuentes_personalizadas");
$fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($fuentes)) {
    echo "No hay fuentes en la base de datos.\n";
} else {
    foreach ($fuentes as $f) {
        echo "ID: {$f['id']} | Nombre: {$f['nombre']} | Archivo: {$f['archivo']} | Sistema: {$f['es_sistema']} | Activo: {$f['activo']}\n";
    }
}

echo "\nTotal: " . count($fuentes) . " fuentes\n";
