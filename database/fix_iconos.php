<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

// Mapeo específico por nombre de grupo
$nombreMapping = [
    'Talleres' => '🛠️',
    'Cursos' => '📚',
    'Concursos' => '🏆',
    'Seminarios' => '🎓',
    'Reconocimientos' => '🏅',
    'Otros' => '⭐'
];

echo "Actualizando iconos de grupos...\n\n";

// Primero intentar por nombre
foreach ($nombreMapping as $nombre => $emoji) {
    $update = $pdo->prepare("UPDATE grupos SET icono = ? WHERE nombre = ?");
    $resultado = $update->execute([$emoji, $nombre]);
    if ($update->rowCount() > 0) {
        echo "✓ {$nombre}: → '{$emoji}'\n";
    }
}

echo "\n¡Actualización completada!\n\n";

// Mostrar resultado
$stmt = $pdo->query("SELECT id, nombre, icono FROM grupos ORDER BY id");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Grupos actualizados:\n";
echo str_repeat("-", 50) . "\n";
foreach ($grupos as $grupo) {
    echo "{$grupo['id']} | {$grupo['nombre']} | {$grupo['icono']}\n";
}
?>
