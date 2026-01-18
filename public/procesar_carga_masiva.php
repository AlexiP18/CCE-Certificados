<?php
// Capturar todos los errores y mostrarlos como JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Función para manejar errores fatales
function errorHandler($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Error PHP: $errstr en $errfile línea $errline"
    ]);
    exit;
}

set_error_handler('errorHandler');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Error fatal: {$error['message']} en {$error['file']} línea {$error['line']}"
        ]);
    }
});

require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/Certificate.php';

use CCE\Certificate;
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$grupo_id = $_POST['grupo_id'] ?? 0;
$categoria_default = $_POST['categoria_default'] ?? '';
$pdf_individual = isset($_POST['pdf_individual']) && $_POST['pdf_individual'] == '1';
$pdf_combined = isset($_POST['pdf_combined']) && $_POST['pdf_combined'] == '1';
$imagenes = isset($_POST['imagenes']) && $_POST['imagenes'] == '1';
$guardar_estudiantes = isset($_POST['guardar_estudiantes']) && $_POST['guardar_estudiantes'] == '1';
$registrar_estudiantes = isset($_POST['registrar_estudiantes']) && $_POST['registrar_estudiantes'] == '1';

// Verificar si vienen datos del formulario manual (JSON), previsualización editada, o archivo
$estudiantes_json = $_POST['estudiantes_json'] ?? null;
$preview_data = $_POST['preview_data'] ?? null;
$es_formulario = !empty($estudiantes_json);
$es_preview = !empty($preview_data);

if (!$es_formulario && !$es_preview) {
    // Modo archivo: validar que se subió un archivo
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error al cargar el archivo o datos']);
        exit;
    }

    $file = $_FILES['file'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Soportamos CSV y Excel
    if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Solo se soportan archivos CSV (.csv) y Excel (.xlsx, .xls)'
        ]);
        exit;
    }
}

try {
    $pdo = getConnection();
    
    // Leer datos según la fuente (formulario, previsualización o archivo)
    $rows = [];
    $estudiantes_ids = []; // Para guardar los IDs de estudiantes creados
    
    if ($es_preview) {
        // Modo previsualización: datos ya parseados y posiblemente editados
        $preview_estudiantes = json_decode($preview_data, true);
        if (!$preview_estudiantes || !is_array($preview_estudiantes)) {
            echo json_encode(['success' => false, 'message' => 'Datos de previsualización inválidos']);
            exit;
        }
        
        // Procesar estudiantes si está habilitado el registro
        if ($registrar_estudiantes) {
            // Verificar estudiantes existentes por cédula
            $estudiantes_existentes = [];
            $cedulas = array_filter(array_map(function($r) { return trim($r['cedula'] ?? ''); }, $preview_estudiantes));
            
            if (!empty($cedulas)) {
                $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
                $stmt = $pdo->prepare("SELECT id, cedula FROM estudiantes WHERE cedula IN ($placeholders)");
                $stmt->execute(array_values($cedulas));
                while ($est = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $estudiantes_existentes[$est['cedula']] = $est['id'];
                }
            }
            
            // Crear/vincular estudiantes
            foreach ($preview_estudiantes as $index => &$est) {
                $cedula = trim($est['cedula'] ?? '');
                $nombre = trim($est['nombre'] ?? '');
                $celular = trim($est['celular'] ?? '');
                $email = trim($est['email'] ?? '');
                
                if (!empty($cedula) && isset($estudiantes_existentes[$cedula])) {
                    // Estudiante existente
                    $est['estudiante_id'] = $estudiantes_existentes[$cedula];
                } else if (!empty($nombre) && (!empty($cedula) || !empty($celular) || !empty($email))) {
                    // Crear nuevo estudiante
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO estudiantes (cedula, nombre, celular, email, activo) 
                            VALUES (?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            !empty($cedula) ? $cedula : null,
                            $nombre,
                            !empty($celular) ? $celular : null,
                            !empty($email) ? $email : null
                        ]);
                        $est['estudiante_id'] = $pdo->lastInsertId();
                        
                        if (!empty($cedula)) {
                            $estudiantes_existentes[$cedula] = $est['estudiante_id'];
                        }
                    } catch (PDOException $e) {
                        if (!empty($cedula)) {
                            $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ?");
                            $stmt->execute([$cedula]);
                            $existente = $stmt->fetch();
                            if ($existente) {
                                $est['estudiante_id'] = $existente['id'];
                            }
                        }
                    }
                }
            }
            unset($est);
        }
        
        // Convertir a formato de filas para el procesador
        foreach ($preview_estudiantes as $est) {
            $rows[] = [
                'nombre' => $est['nombre'] ?? '',
                'razon' => $est['razon'] ?? '',
                'fecha' => $est['fecha'] ?? date('Y-m-d'),
                'categoria' => $est['categoria_id'] ?? $categoria_default,
                'estudiante_id' => $est['estudiante_id'] ?? null
            ];
        }
        
    } else if ($es_formulario) {
        // Modo formulario: parsear JSON
        $estudiantes = json_decode($estudiantes_json, true);
        if (!$estudiantes || !is_array($estudiantes)) {
            echo json_encode(['success' => false, 'message' => 'Datos de estudiantes inválidos']);
            exit;
        }
        
        // Guardar estudiantes en la base de datos si está habilitado
        if ($guardar_estudiantes) {
            foreach ($estudiantes as $index => $est) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO estudiantes (cedula, nombre, celular, email, fecha_nacimiento, destacado) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        !empty($est['cedula']) ? $est['cedula'] : null,
                        $est['nombre'],
                        !empty($est['celular']) ? $est['celular'] : null,
                        !empty($est['email']) ? $est['email'] : null,
                        !empty($est['fecha_nacimiento']) ? $est['fecha_nacimiento'] : null,
                        $est['destacado'] ?? 0
                    ]);
                    $estudiantes_ids[$index] = $pdo->lastInsertId();
                } catch (PDOException $e) {
                    // Ignorar errores de inserción de estudiantes, continuar
                    error_log("Error guardando estudiante: " . $e->getMessage());
                    $estudiantes_ids[$index] = null;
                }
            }
        }
        
        // Convertir a formato compatible con el proceso de certificados
        foreach ($estudiantes as $index => $est) {
            $rows[] = [
                'nombre' => $est['nombre'],
                'razon' => '', // Se usará la razón configurada en la categoría
                'fecha' => $est['fecha'] ?? date('Y-m-d'),
                'categoria' => $est['categoria_id'] ?? $categoria_default,
                'estudiante_id' => $estudiantes_ids[$index] ?? null
            ];
        }
    } else {
        // Modo archivo
        if ($fileExtension === 'csv') {
            // Leer CSV
            if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
                $headers = fgetcsv($handle, 1000, ',');
                
                // Solo 'nombre' es requerido, las demás columnas son opcionales
                // 'razon' si está vacía usará la configuración de plantilla
                // 'fecha' si está vacía usará la fecha actual
                // 'categoria' es opcional
                $requiredColumns = ['nombre'];
                foreach ($requiredColumns as $col) {
                    if (!in_array($col, $headers)) {
                        fclose($handle);
                        echo json_encode([
                            'success' => false,
                            'message' => "El archivo no tiene la columna requerida: $col"
                        ]);
                        exit;
                    }
                }
                
                // Leer datos
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if (count($data) === count($headers)) {
                        $row = array_combine($headers, $data);
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
            
            // Leer encabezados (primera fila)
            $headers = [];
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $value = $worksheet->getCell($columnLetter . '1')->getValue();
                if ($value !== null) {
                    $headers[] = trim($value);
                }
            }
            
            // Limpiar headers de BOM si existe
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            
            // Solo 'nombre' es requerido
            $requiredColumns = ['nombre'];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $headers)) {
                    echo json_encode([
                        'success' => false,
                        'message' => "El archivo no tiene la columna requerida: $col"
                    ]);
                    exit;
                }
            }
            
            // Leer datos (desde la fila 2)
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                $hasData = false;
                for ($col = 1; $col <= count($headers); $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                    $rowData[$headers[$col - 1]] = $cellValue;
                    if (!empty($cellValue)) $hasData = true;
                }
                if ($hasData && !empty(trim($rowData['nombre'] ?? ''))) {
                    $rows[] = $rowData;
                }
            }
        }
        
        // Procesar estudiantes del archivo si tiene datos de estudiantes
        $registrar_estudiantes = isset($_POST['registrar_estudiantes']) && $_POST['registrar_estudiantes'] == '1';
        
        if ($registrar_estudiantes) {
            // Verificar estudiantes existentes por cédula
            $estudiantes_existentes = [];
            $cedulas = array_filter(array_map(function($r) { return trim($r['cedula'] ?? ''); }, $rows));
            
            if (!empty($cedulas)) {
                $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
                $stmt = $pdo->prepare("SELECT id, cedula FROM estudiantes WHERE cedula IN ($placeholders)");
                $stmt->execute(array_values($cedulas));
                while ($est = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $estudiantes_existentes[$est['cedula']] = $est['id'];
                }
            }
            
            // Crear/vincular estudiantes
            foreach ($rows as $index => &$row) {
                $cedula = trim($row['cedula'] ?? '');
                $nombre = trim($row['nombre'] ?? '');
                $celular = trim($row['celular'] ?? '');
                $email = trim($row['email'] ?? '');
                
                if (!empty($cedula) && isset($estudiantes_existentes[$cedula])) {
                    // Estudiante existente
                    $row['estudiante_id'] = $estudiantes_existentes[$cedula];
                } else if (!empty($nombre)) {
                    // Crear nuevo estudiante si tiene datos adicionales o cédula
                    if (!empty($cedula) || !empty($celular) || !empty($email)) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO estudiantes (cedula, nombre, celular, email, activo) 
                                VALUES (?, ?, ?, ?, 1)
                            ");
                            $stmt->execute([
                                !empty($cedula) ? $cedula : null,
                                $nombre,
                                !empty($celular) ? $celular : null,
                                !empty($email) ? $email : null
                            ]);
                            $row['estudiante_id'] = $pdo->lastInsertId();
                            
                            // Agregar a existentes para evitar duplicados
                            if (!empty($cedula)) {
                                $estudiantes_existentes[$cedula] = $row['estudiante_id'];
                            }
                        } catch (PDOException $e) {
                            // Si falla por duplicado de cédula, buscar el existente
                            if (!empty($cedula)) {
                                $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ?");
                                $stmt->execute([$cedula]);
                                $existente = $stmt->fetch();
                                if ($existente) {
                                    $row['estudiante_id'] = $existente['id'];
                                }
                            }
                        }
                    }
                }
            }
            unset($row); // Romper referencia
        }
    }
    
    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'No hay datos para procesar']);
        exit;
    }
    
    // Generar certificados
    $success_count = 0;
    $error_count = 0;
    $generated_files = [
        'pdfs' => [],
        'images' => []
    ];
    
    $certificate = new Certificate($pdo);
    
    foreach ($rows as $index => $row) {
        try {
            $nombre = $row['nombre'] ?? '';
            $razon = $row['razon'] ?? '';
            $fecha = $row['fecha'] ?? date('Y-m-d');
            $categoria_id = !empty($row['categoria']) ? $row['categoria'] : $categoria_default;
            
            if (empty($nombre)) {
                $error_count++;
                continue;
            }
            
            // Validar que la categoría existe en el grupo
            if (!empty($categoria_id)) {
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND grupo_id = ?");
                $stmt->execute([$categoria_id, $grupo_id]);
                if (!$stmt->fetch()) {
                    // Si la categoría no existe, usar NULL
                    $categoria_id = null;
                }
            } else {
                $categoria_id = null;
            }
            
            // Generar código único
            $codigo = $certificate->generateCode();
            
            // Preparar datos para crear certificado
            $data = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'razon' => $razon,
                'fecha' => $fecha,
                'grupo_id' => $grupo_id,
                'categoria_id' => $categoria_id,
                'es_destacado' => isset($row['destacado']) ? (bool)$row['destacado'] : false
            ];
            
            // Generar certificado
            $result = $certificate->create($data);
            
            // Debug: registrar el resultado
            error_log("Resultado para {$nombre}: " . json_encode($result));
            
            if ($result && isset($result['success']) && $result['success']) {
                $success_count++;
                
                // Guardar relación estudiante-certificado
                $estudiante_id = $row['estudiante_id'] ?? null;
                if ($estudiante_id && isset($result['certificado_id'])) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO estudiante_certificados (estudiante_id, certificado_id, categoria_id, grupo_id, fecha_emision) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $estudiante_id,
                            $result['certificado_id'],
                            $categoria_id,
                            $grupo_id,
                            $fecha
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error guardando relación estudiante-certificado: " . $e->getMessage());
                    }
                }
                
                // Guardar rutas absolutas de archivos generados
                // archivo_pdf y archivo_imagen ya vienen con 'uploads/' al inicio
                $baseDir = dirname(__DIR__) . '/';
                if (!empty($result['archivo_pdf'])) {
                    $fullPdfPath = $baseDir . $result['archivo_pdf'];
                    error_log("Agregando PDF: $fullPdfPath - Existe: " . (file_exists($fullPdfPath) ? 'SI' : 'NO'));
                    $generated_files['pdfs'][] = $fullPdfPath;
                }
                if (!empty($result['archivo_imagen'])) {
                    $fullImgPath = $baseDir . $result['archivo_imagen'];
                    error_log("Agregando Imagen: $fullImgPath - Existe: " . (file_exists($fullImgPath) ? 'SI' : 'NO'));
                    $generated_files['images'][] = $fullImgPath;
                }
            } else {
                $error_count++;
                error_log("Error: Certificado no generado para {$nombre}. Resultado: " . json_encode($result));
            }
            
        } catch (Exception $e) {
            $error_count++;
            error_log("Error generando certificado para {$row['nombre']}: " . $e->getMessage());
        }
    }
    
    // Verificar si ZipArchive está disponible
    if (!class_exists('ZipArchive')) {
        echo json_encode([
            'success' => false,
            'message' => 'La extensión ZIP no está habilitada en PHP. Para habilitarla: 1) Abre C:\\xampp\\php\\php.ini, 2) Busca ";extension=zip" y quita el punto y coma, 3) Reinicia Apache'
        ]);
        exit;
    }
    
    // Crear archivos ZIP si se solicitaron
    $downloads = [];
    $timestamp = date('YmdHis');
    $zipDir = dirname(__DIR__) . '/uploads/temp/';
    
    // Debug: registrar estado de generación
    error_log("Total rows procesados: " . count($rows));
    error_log("Certificados exitosos: $success_count, Errores: $error_count");
    error_log("PDFs generados: " . count($generated_files['pdfs']));
    error_log("Imágenes generadas: " . count($generated_files['images']));
    error_log("Formato solicitado - PDF individual: " . ($pdf_individual ? 'SI' : 'NO') . ", PDF combinado: " . ($pdf_combined ? 'SI' : 'NO') . ", Imágenes: " . ($imagenes ? 'SI' : 'NO'));
    
    if (!file_exists($zipDir)) {
        mkdir($zipDir, 0777, true);
    }
    
    // ZIP de PDFs individuales
    if ($pdf_individual && !empty($generated_files['pdfs'])) {
        $zipName = "certificados_pdf_{$timestamp}.zip";
        $zipPath = $zipDir . $zipName;
        $zip = new ZipArchive();
        
        $openResult = $zip->open($zipPath, ZipArchive::CREATE);
        if ($openResult === TRUE) {
            $addedFiles = 0;
            foreach ($generated_files['pdfs'] as $pdf) {
                if (file_exists($pdf)) {
                    $zip->addFile($pdf, basename($pdf));
                    $addedFiles++;
                }
            }
            $zip->close();
            
            // Verificar que el ZIP se creó correctamente
            if (file_exists($zipPath) && $addedFiles > 0) {
                $downloads['pdf_individual'] = 'uploads/temp/' . $zipName;
            } else {
                error_log("ZIP no se creó o no tiene archivos. Path: $zipPath, Archivos agregados: $addedFiles");
            }
        } else {
            error_log("No se pudo abrir ZIP. Código de error: $openResult, Path: $zipPath");
        }
    }
    
    // PDF Combinado (un solo PDF con múltiples páginas usando las imágenes)
    if ($pdf_combined && !empty($generated_files['images'])) {
        require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
        
        $pdfCombinado = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdfCombinado->SetCreator('CCE Certificados');
        $pdfCombinado->SetAuthor('CCE');
        $pdfCombinado->SetTitle('Certificados Combinados');
        $pdfCombinado->setPrintHeader(false);
        $pdfCombinado->setPrintFooter(false);
        $pdfCombinado->SetMargins(0, 0, 0);
        $pdfCombinado->SetAutoPageBreak(false, 0);
        
        // Agregar cada imagen de certificado como una página
        foreach ($generated_files['images'] as $imgFile) {
            if (file_exists($imgFile)) {
                $pdfCombinado->AddPage();
                // Insertar imagen a tamaño completo A4 horizontal (297mm x 210mm)
                $pdfCombinado->Image($imgFile, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);
            }
        }
        
        $pdfCombinadoName = "certificados_combinados_{$timestamp}.pdf";
        $pdfCombinadoPath = $zipDir . $pdfCombinadoName;
        $pdfCombinado->Output($pdfCombinadoPath, 'F');
        
        if (file_exists($pdfCombinadoPath)) {
            $downloads['pdf_combined'] = 'uploads/temp/' . $pdfCombinadoName;
        }
    }
    
    // ZIP de Imágenes
    if ($imagenes && !empty($generated_files['images'])) {
        $zipName = "certificados_imagenes_{$timestamp}.zip";
        $zipPath = $zipDir . $zipName;
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $addedFiles = 0;
            foreach ($generated_files['images'] as $img) {
                if (file_exists($img)) {
                    $zip->addFile($img, basename($img));
                    $addedFiles++;
                }
            }
            $zip->close();
            
            if (file_exists($zipPath) && $addedFiles > 0) {
                $downloads['imagenes'] = 'uploads/temp/' . $zipName;
            }
        }
    }
    
    // Debug: registrar downloads
    error_log("Downloads generados: " . json_encode($downloads));
    
    echo json_encode([
        'success' => true,
        'total' => count($rows),
        'success_count' => $success_count,
        'error_count' => $error_count,
        'downloads' => $downloads
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el archivo: ' . $e->getMessage()
    ]);
}
