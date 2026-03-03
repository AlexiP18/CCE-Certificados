<?php
require 'config/database.php';
$stmt = getConnection()->query("SELECT id, nombre, variables_habilitadas FROM grupo_plantillas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
