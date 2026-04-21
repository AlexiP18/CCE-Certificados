<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getConnection();
    $stmt = $conn->query("SELECT id, variables_habilitadas FROM grupo_plantillas");
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actualizadas = 0;
    foreach ($plantillas as $p) {
        $vars = [];
        if (!empty($p['variables_habilitadas'])) {
            $decoded = json_decode($p['variables_habilitadas'], true);
            if (is_array($decoded)) {
                $vars = $decoded;
            }
        }
        
        $changed = false;
        foreach (['nombre', 'razon', 'fecha', 'qr', 'firma', 'destacado'] as $v) {
            if (!in_array($v, $vars)) {
                $vars[] = $v;
                $changed = true;
            }
        }
        
        if ($changed) {
            $upd = $conn->prepare("UPDATE grupo_plantillas SET variables_habilitadas = ? WHERE id = ?");
            $upd->execute([json_encode($vars), $p['id']]);
            $actualizadas++;
        }
    }
    echo "Migracion completada. Plantillas actualizadas: " . $actualizadas;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
