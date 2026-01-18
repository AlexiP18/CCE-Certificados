<?php
/**
 * Endpoint para previsualizar datos de archivo Excel/CSV antes de generar certificados
 */
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al cargar el archivo']);
    exit;
}

$file = $_FILES['file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$grupo_id = $_POST['grupo_id'] ?? 0;

if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Solo se soportan archivos CSV (.csv) y Excel (.xlsx, .xls)'
    ]);
    exit;
}

try {
    $pdo = getConnection();
    $rows = [];
    $headers = [];
    
    if ($fileExtension === 'csv') {
        // Leer CSV
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ',');
            
            // Limpiar headers de BOM
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (count($data) === count($headers)) {
                    $row = array_combine($headers, $data);
                    // Solo agregar si tiene nombre
                    if (!empty(trim($row['nombre'] ?? ''))) {
                        $rows[] = $row;
                    }
                }
            }
            fclose($handle);
        }
    } else {
        // Leer Excel
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        // Leer encabezados
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $value = $worksheet->getCell($columnLetter . '1')->getValue();
            if ($value !== null) {
                $headers[] = trim($value);
            }
        }
        
        // Leer datos
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 1; $col <= count($headers); $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                $rowData[$headers[$col - 1]] = $cellValue;
                if (!empty($cellValue)) $hasData = true;
            }
            
            // Solo agregar si tiene nombre
            if ($hasData && !empty(trim($rowData['nombre'] ?? ''))) {
                $rows[] = $rowData;
            }
        }
    }
    
    // Validar que hay columna nombre
    if (!in_array('nombre', $headers)) {
        echo json_encode([
            'success' => false,
            'message' => 'El archivo debe tener una columna "nombre"'
        ]);
        exit;
    }
    
    if (empty($rows)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos válidos en el archivo'
        ]);
        exit;
    }
    
    // Verificar estudiantes existentes por cédula
    $estudiantes_existentes = [];
    $cedulas = array_filter(array_column($rows, 'cedula'));
    
    if (!empty($cedulas)) {
        $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
        $stmt = $pdo->prepare("SELECT id, cedula, nombre, celular, email FROM estudiantes WHERE cedula IN ($placeholders)");
        $stmt->execute($cedulas);
        while ($est = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $estudiantes_existentes[$est['cedula']] = $est;
        }
    }
    
    // Obtener categorías del grupo
    $categorias = [];
    if ($grupo_id) {
        $stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE grupo_id = ? AND activo = 1");
        $stmt->execute([$grupo_id]);
        while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categorias[$cat['id']] = $cat['nombre'];
        }
    }
    
    // Procesar cada fila para la previsualización
    $preview_data = [];
    $nuevos_estudiantes = 0;
    $estudiantes_vinculados = 0;
    
    foreach ($rows as $index => $row) {
        $cedula = trim($row['cedula'] ?? '');
        $nombre = trim($row['nombre'] ?? '');
        $celular = trim($row['celular'] ?? '');
        $email = trim($row['email'] ?? '');
        $razon = trim($row['razon'] ?? '');
        $fecha = trim($row['fecha'] ?? '');
        $categoria = trim($row['categoria'] ?? '');
        
        // Verificar si el estudiante existe
        $estudiante_existente = null;
        $es_nuevo = true;
        
        if (!empty($cedula) && isset($estudiantes_existentes[$cedula])) {
            $estudiante_existente = $estudiantes_existentes[$cedula];
            $es_nuevo = false;
            $estudiantes_vinculados++;
        } else if (!empty($cedula) || !empty($celular) || !empty($email)) {
            $nuevos_estudiantes++;
        }
        
        $preview_data[] = [
            'fila' => $index + 1,
            'nombre' => $nombre,
            'cedula' => $cedula,
            'celular' => $celular,
            'email' => $email,
            'razon' => $razon,
            'fecha' => $fecha ?: date('Y-m-d'),
            'categoria' => !empty($categoria) && isset($categorias[$categoria]) ? $categorias[$categoria] : '',
            'categoria_id' => $categoria,
            'es_nuevo' => $es_nuevo,
            'estudiante_existente' => $estudiante_existente
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($preview_data),
        'nuevos_estudiantes' => $nuevos_estudiantes,
        'estudiantes_vinculados' => $estudiantes_vinculados,
        'headers' => $headers,
        'estudiantes' => $preview_data,
        'categorias' => $categorias
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar archivo: ' . $e->getMessage()
    ]);
}
