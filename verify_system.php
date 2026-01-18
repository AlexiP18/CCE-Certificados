<?php
/**
 * Script de verificación del sistema
 * Ejecuta este script para verificar que todo está configurado correctamente
 */

echo "╔════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN DEL SISTEMA DE CERTIFICADOS  ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Verificar PHP
echo "1. Verificando PHP...\n";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4', '>=')) {
    echo "   ✓ PHP $phpVersion instalado\n";
    $success[] = "PHP versión OK";
} else {
    echo "   ✗ PHP $phpVersion (se requiere 7.4 o superior)\n";
    $errors[] = "Versión de PHP insuficiente";
}

// 2. Verificar extensiones
echo "\n2. Verificando extensiones PHP...\n";
$requiredExtensions = ['gd', 'pdo_mysql', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ Extensión $ext cargada\n";
        $success[] = "Extensión $ext OK";
    } else {
        echo "   ✗ Extensión $ext NO encontrada\n";
        $errors[] = "Falta extensión $ext";
    }
}

// 3. Verificar directorios
echo "\n3. Verificando directorios...\n";
$requiredDirs = [
    'vendor' => __DIR__ . '/vendor',
    'uploads' => __DIR__ . '/uploads',
    'assets/templates' => __DIR__ . '/assets/templates',
    'assets/fonts' => __DIR__ . '/assets/fonts',
    'config' => __DIR__ . '/config',
];

foreach ($requiredDirs as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path) || $name !== 'uploads') {
            echo "   ✓ Directorio $name existe\n";
            $success[] = "Directorio $name OK";
        } else {
            echo "   ⚠ Directorio $name existe pero no es escribible\n";
            $warnings[] = "Directorio $name no escribible";
        }
    } else {
        echo "   ✗ Directorio $name NO encontrado\n";
        $errors[] = "Falta directorio $name";
    }
}

// 4. Verificar archivos de configuración
echo "\n4. Verificando configuración...\n";
if (file_exists(__DIR__ . '/config/database.php')) {
    echo "   ✓ Archivo database.php existe\n";
    $success[] = "Configuración de BD OK";
    
    // Intentar conectar
    try {
        require_once __DIR__ . '/config/database.php';
        echo "   ✓ Conexión a base de datos exitosa\n";
        $success[] = "Conexión BD OK";
        
        // Verificar tablas
        $tables = ['certificados', 'configuracion_plantillas', 'verificaciones'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ Tabla $table existe\n";
                $success[] = "Tabla $table OK";
            } else {
                echo "   ✗ Tabla $table NO encontrada\n";
                $errors[] = "Falta tabla $table";
            }
        }
    } catch (Exception $e) {
        echo "   ✗ Error de conexión: " . $e->getMessage() . "\n";
        $errors[] = "Error de conexión BD";
    }
} else {
    echo "   ✗ Archivo database.php NO encontrado\n";
    $errors[] = "Falta configuración BD";
}

// 5. Verificar plantilla
echo "\n5. Verificando plantilla...\n";
$templateFiles = glob(__DIR__ . '/assets/templates/*.{png,jpg}', GLOB_BRACE);
if (count($templateFiles) > 0) {
    echo "   ✓ " . count($templateFiles) . " archivo(s) de plantilla encontrado(s)\n";
    $success[] = "Plantillas encontradas";
} else {
    echo "   ⚠ No se encontraron plantillas PNG/JPG\n";
    $warnings[] = "Sin plantillas";
}

// 6. Verificar fuentes
echo "\n6. Verificando fuentes...\n";
$fontFiles = glob(__DIR__ . '/assets/fonts/*.{ttf,otf}', GLOB_BRACE);
if (count($fontFiles) > 0) {
    echo "   ✓ " . count($fontFiles) . " archivo(s) de fuente encontrado(s)\n";
    $success[] = "Fuentes encontradas";
} else {
    echo "   ⚠ No se encontraron fuentes TTF/OTF (se usará fuente del sistema)\n";
    $warnings[] = "Sin fuentes personalizadas";
}

// 7. Verificar archivos principales
echo "\n7. Verificando archivos del sistema...\n";
$requiredFiles = [
    'public/index.php',
    'public/generate.php',
    'public/config.php',
    'public/save_config.php',
    'public/preview.php',
    'public/verify.php',
    'includes/Certificate.php',
];

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "   ✓ $file\n";
        $success[] = "$file OK";
    } else {
        echo "   ✗ $file NO encontrado\n";
        $errors[] = "Falta $file";
    }
}

// Resumen
echo "\n╔════════════════════════════════════════════╗\n";
echo "║               RESUMEN                      ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

echo "✓ Éxitos: " . count($success) . "\n";
echo "⚠ Advertencias: " . count($warnings) . "\n";
echo "✗ Errores: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORES CRÍTICOS:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
    echo "\nEl sistema NO está listo para funcionar.\n";
    exit(1);
} elseif (count($warnings) > 0) {
    echo "⚠ ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
    echo "\nEl sistema está funcional pero revisa las advertencias.\n";
    exit(0);
} else {
    echo "✅ ¡Todo está configurado correctamente!\n";
    echo "\nAccede al sistema en:\n";
    echo "→ http://localhost/cce-certificados/public/\n\n";
    exit(0);
}
