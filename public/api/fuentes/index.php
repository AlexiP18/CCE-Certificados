<?php
/**
 * API para gestión de fuentes personalizadas
 * Permite subir, listar y eliminar fuentes para los certificados
 */

// Desactivar salida de errores HTML que rompen el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


require_once dirname(dirname(dirname(__DIR__))) . '/includes/Auth.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';

// Verificar autenticación
Auth::requireAuth();

header('Content-Type: application/json');

$pdo = getConnection();

// Directorio donde se guardan las fuentes (en root/assets/fonts)
// __DIR__ es public/api/fuentes -> subimos 3 niveles para llegar a root
$fontsDir = dirname(dirname(dirname(__DIR__))) . '/assets/fonts/';

// Crear directorio si no existe
if (!is_dir($fontsDir)) {
    mkdir($fontsDir, 0755, true);
}

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Listar todas las fuentes disponibles
            $soloActivas = isset($_GET['solo_activas']) ? (bool)$_GET['solo_activas'] : true;
            
            $sql = "SELECT * FROM fuentes_personalizadas";
            if ($soloActivas) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY es_sistema DESC, categoria ASC, nombre ASC";
            
            $stmt = $pdo->query($sql);
            $fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Verificar que los archivos existan
            foreach ($fuentes as &$fuente) {
                $fuente['archivo_existe'] = file_exists($fontsDir . $fuente['archivo']);
            }
            
            echo json_encode([
                'success' => true,
                'fuentes' => $fuentes,
                'total' => count($fuentes)
            ]);
            break;
            
        case 'get':
            // Obtener una fuente específica
            $id = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE id = ?");
            $stmt->execute([$id]);
            $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fuente) {
                throw new Exception('Fuente no encontrada');
            }
            
            $fuente['archivo_existe'] = file_exists($fontsDir . $fuente['archivo']);
            
            echo json_encode([
                'success' => true,
                'fuente' => $fuente
            ]);
            break;
            
        case 'upload':
            // Subir nueva fuente
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se ha subido ningún archivo o hubo un error en la subida');
            }
            
            $file = $_FILES['archivo'];
            $nombre = trim($_POST['nombre'] ?? '');
            $categoria = $_POST['categoria'] ?? 'sans-serif';
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la fuente es requerido');
            }
            
            // Validar extensión
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extensionesPermitidas = ['ttf', 'otf', 'woff', 'woff2'];
            
            if (!in_array($extension, $extensionesPermitidas)) {
                throw new Exception('Tipo de archivo no permitido. Solo se aceptan: ' . implode(', ', $extensionesPermitidas));
            }
            
            // Validar tipo MIME
            $mimeTypes = [
                'ttf' => ['font/ttf', 'font/sfnt', 'application/x-font-ttf', 'application/font-sfnt'],
                'otf' => ['font/otf', 'font/opentype', 'application/x-font-otf', 'application/vnd.ms-opentype'],
                'woff' => ['font/woff', 'application/font-woff'],
                'woff2' => ['font/woff2', 'application/font-woff2']
            ];
            
            // Generar nombre de archivo seguro
            $nombreArchivo = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $nombre));
            $archivoFinal = $nombreArchivo . '.' . $extension;
            
            // Verificar si ya existe una fuente con ese nombre de archivo
            $stmt = $pdo->prepare("SELECT id FROM fuentes_personalizadas WHERE nombre_archivo = ?");
            $stmt->execute([$nombreArchivo]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe una fuente con un nombre similar. Por favor, usa otro nombre.');
            }
            
            // Mover archivo
            $rutaDestino = $fontsDir . $archivoFinal;
            if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                throw new Exception('Error al guardar el archivo de fuente');
            }
            
            // Guardar en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO fuentes_personalizadas (nombre, nombre_archivo, archivo, tipo, categoria, es_sistema)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$nombre, $nombreArchivo, $archivoFinal, $extension, $categoria]);
            
            $fuenteId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Fuente subida correctamente',
                'fuente' => [
                    'id' => $fuenteId,
                    'nombre' => $nombre,
                    'nombre_archivo' => $nombreArchivo,
                    'archivo' => $archivoFinal,
                    'tipo' => $extension,
                    'categoria' => $categoria
                ]
            ]);
            break;
            
        case 'update':
            // Actualizar información de una fuente
            $id = $_POST['id'] ?? 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $categoria = $_POST['categoria'] ?? 'sans-serif';
            $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
            
            if (!$id) {
                throw new Exception('ID de fuente requerido');
            }
            
            // Verificar que la fuente existe
            $stmt = $pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE id = ?");
            $stmt->execute([$id]);
            $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fuente) {
                throw new Exception('Fuente no encontrada');
            }
            
            // No permitir desactivar fuentes del sistema
            if ($fuente['es_sistema'] && $activo == 0) {
                throw new Exception('No se pueden desactivar las fuentes del sistema');
            }
            
            $stmt = $pdo->prepare("
                UPDATE fuentes_personalizadas 
                SET nombre = ?, categoria = ?, activo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre ?: $fuente['nombre'], $categoria, $activo, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fuente actualizada correctamente'
            ]);
            break;
            
        case 'delete':
            // Eliminar fuente
            $id = $_POST['id'] ?? $_GET['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID de fuente requerido');
            }
            
            // Verificar que la fuente existe y no es del sistema
            $stmt = $pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE id = ?");
            $stmt->execute([$id]);
            $fuente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fuente) {
                throw new Exception('Fuente no encontrada');
            }
            
            if ($fuente['es_sistema']) {
                throw new Exception('No se pueden eliminar las fuentes del sistema');
            }
            
            // Eliminar archivo físico
            $rutaArchivo = $fontsDir . $fuente['archivo'];
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            
            // Eliminar de la base de datos
            $stmt = $pdo->prepare("DELETE FROM fuentes_personalizadas WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fuente eliminada correctamente'
            ]);
            break;
            
        case 'preview':
            // Obtener CSS para previsualizar fuentes
            $stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE activo = 1");
            $fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular la ruta correcta de las fuentes
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $fontsPath = str_replace('/public', '', $basePath) . '/assets/fonts/';
            
            $css = '';
            foreach ($fuentes as $fuente) {
                $formato = $fuente['tipo'] === 'ttf' ? 'truetype' : 
                          ($fuente['tipo'] === 'otf' ? 'opentype' : $fuente['tipo']);
                
                $css .= "@font-face {\n";
                $css .= "    font-family: 'CustomFont{$fuente['id']}';\n";
                $css .= "    src: url('{$fontsPath}{$fuente['archivo']}') format('{$formato}');\n";
                $css .= "    font-display: swap;\n";
                $css .= "}\n";
            }
            
            header('Content-Type: text/css');
            echo $css;
            exit;
            
        case 'get_css':
            // Obtener CSS inline para incluir en páginas
            $stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE activo = 1");
            $fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular la ruta correcta de las fuentes
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $fontsPath = str_replace('/public', '', $basePath) . '/assets/fonts/';
            
            $css = [];
            foreach ($fuentes as $fuente) {
                $formato = $fuente['tipo'] === 'ttf' ? 'truetype' : 
                          ($fuente['tipo'] === 'otf' ? 'opentype' : $fuente['tipo']);
                
                $css[] = [
                    'nombre' => $fuente['nombre'],
                    'nombre_archivo' => $fuente['nombre_archivo'],
                    'id' => $fuente['id'],
                    'css' => "@font-face { font-family: 'CustomFont{$fuente['id']}'; src: url('{$fontsPath}{$fuente['archivo']}') format('{$formato}'); font-display: swap; }"
                ];
            }
            
            echo json_encode([
                'success' => true,
                'fuentes' => $fuentes,
                'css' => $css,
                'fontsPath' => $fontsPath
            ]);
            break;
            
        case 'options':
            // Obtener opciones HTML para selectores de fuentes
            $stmt = $pdo->query("SELECT * FROM fuentes_personalizadas WHERE activo = 1 ORDER BY es_sistema DESC, categoria ASC, nombre ASC");
            $fuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $options = '';
            $currentCategory = '';
            
            foreach ($fuentes as $fuente) {
                if ($fuente['categoria'] !== $currentCategory) {
                    if ($currentCategory !== '') {
                        $options .= '</optgroup>';
                    }
                    $categoryLabels = [
                        'sans-serif' => 'Sans Serif',
                        'serif' => 'Serif',
                        'display' => 'Display',
                        'handwriting' => 'Manuscritas',
                        'monospace' => 'Monoespaciadas'
                    ];
                    $options .= '<optgroup label="' . ($categoryLabels[$fuente['categoria']] ?? $fuente['categoria']) . '">';
                    $currentCategory = $fuente['categoria'];
                }
                $options .= '<option value="' . htmlspecialchars($fuente['nombre_archivo']) . '">' . htmlspecialchars($fuente['nombre']) . '</option>';
            }
            if ($currentCategory !== '') {
                $options .= '</optgroup>';
            }
            
            echo json_encode([
                'success' => true,
                'options' => $options,
                'fuentes' => $fuentes
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
