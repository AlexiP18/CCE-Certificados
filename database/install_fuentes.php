<?php
/**
 * Script para instalar la tabla de fuentes personalizadas
 * Ejecutar desde la línea de comandos: php install_fuentes.php
 */
require_once dirname(__DIR__) . '/config/database.php';

$pdo = getConnection();

echo "Instalando tabla de fuentes personalizadas...\n";

try {
    // Crear la tabla
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fuentes_personalizadas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL COMMENT 'Nombre para mostrar',
            nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo sin extensión',
            archivo VARCHAR(255) NOT NULL COMMENT 'Nombre completo del archivo con extensión',
            tipo ENUM('ttf', 'otf', 'woff', 'woff2') DEFAULT 'ttf',
            categoria ENUM('sans-serif', 'serif', 'display', 'handwriting', 'monospace') DEFAULT 'sans-serif',
            activo TINYINT(1) DEFAULT 1,
            es_sistema TINYINT(1) DEFAULT 0 COMMENT 'Si es una fuente del sistema (no se puede eliminar)',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nombre_archivo (nombre_archivo),
            INDEX idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Tabla creada correctamente.\n";
    
    // Insertar fuentes predeterminadas
    $fuentes = [
        ['Roboto', 'Roboto-Regular', 'Roboto-Regular.ttf', 'ttf', 'sans-serif', 1],
        ['Open Sans', 'OpenSans', 'OpenSans.ttf', 'ttf', 'sans-serif', 1],
        ['Lato', 'Lato-Regular', 'Lato-Regular.ttf', 'ttf', 'sans-serif', 1],
        ['Montserrat', 'Montserrat', 'Montserrat.ttf', 'ttf', 'sans-serif', 1],
        ['Poppins', 'Poppins-Regular', 'Poppins-Regular.ttf', 'ttf', 'sans-serif', 1],
        ['Playfair Display', 'PlayfairDisplay', 'PlayfairDisplay.ttf', 'ttf', 'serif', 1],
        ['Motterdam', 'Motterdam', 'Motterdam.ttf', 'ttf', 'handwriting', 1],
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO fuentes_personalizadas (nombre, nombre_archivo, archivo, tipo, categoria, es_sistema) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $insertadas = 0;
    foreach ($fuentes as $fuente) {
        $stmt->execute($fuente);
        if ($stmt->rowCount() > 0) {
            $insertadas++;
        }
    }
    
    echo "{$insertadas} fuentes insertadas.\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM fuentes_personalizadas");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de fuentes en la tabla: {$total}\n";
    
    echo "\n✅ Instalación completada correctamente.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
