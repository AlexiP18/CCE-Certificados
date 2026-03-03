<?php
require_once '../../includes/Auth.php';
require_once '../../config/database.php';

// Verificar autenticación
Auth::requireAuth();
$usuario = Auth::user();

// Configuración de rutas
$basePath = '..';
$cssPath = '../css';
$jsPath = '../js';

try {
    $pdo = getConnection();
    
    // Obtener grupos para el filtro
    $stmt = $pdo->query("SELECT id, nombre, color FROM grupos ORDER BY nombre");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para el filtro
    $stmt = $pdo->query("SELECT c.id, c.nombre, c.grupo_id, g.nombre as grupo_nombre 
                         FROM categorias c 
                         LEFT JOIN grupos g ON c.grupo_id = g.id 
                         ORDER BY g.nombre, c.nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $grupos = [];
    $categorias = [];
    error_log("Error en estudiantes.php: " . $e->getMessage());
}

// Helper functions from Auth.php are available globally

// Cargar la vista
require_once '../../app/Views/estudiantes/index.php';
?>
