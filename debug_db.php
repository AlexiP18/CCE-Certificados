<?php
require_once __DIR__ . '/config/database.php';
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT variables_habilitadas FROM grupos WHERE id=73");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "VARIABLES_HABILITADAS: " . ($result['variables_habilitadas'] ?? 'NULL');
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
