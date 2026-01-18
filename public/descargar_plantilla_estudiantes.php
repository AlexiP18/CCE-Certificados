<?php
/**
 * Genera y descarga la plantilla Excel para carga masiva de estudiantes
 * Soporta estudiantes mayores y menores de edad con representante legal
 */

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

// Crear nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Estudiantes');

// Definir encabezados
$headers = [
    'nombre', 
    'fecha_nacimiento',
    'cedula', 
    'celular', 
    'email',
    'es_menor',
    'representante_nombre',
    'representante_cedula',
    'representante_celular',
    'representante_email',
    'representante_fecha_nacimiento'
];

$headerDescriptions = [
    'Nombre completo del estudiante (OBLIGATORIO)',
    'Fecha de nacimiento (AAAA-MM-DD) - OBLIGATORIO',
    'Cédula - 10 dígitos (OBLIGATORIO para mayores)',
    'Celular - 9 dígitos sin +593 (OBLIGATORIO para mayores)',
    'Correo electrónico (opcional)',
    'Escribir SI si es menor de edad',
    'Nombre del representante legal (obligatorio si es menor)',
    'Cédula del representante - 10 dígitos (obligatorio si es menor)',
    'Celular del representante - 9 dígitos sin +593 (obligatorio si es menor)',
    'Email del representante (opcional)',
    'Fecha de nacimiento del representante (opcional)'
];

// Escribir encabezados
foreach ($headers as $col => $header) {
    $colLetter = chr(65 + $col); // A, B, C, D, E, F, G, H, I, J, K
    $sheet->setCellValue($colLetter . '1', $header);
    
    // Agregar comentario con descripción
    $sheet->getComment($colLetter . '1')->getText()->createTextRun($headerDescriptions[$col]);
}

// Estilo para encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2C3E50']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Estilo diferente para columnas de representante (F-J)
$representanteHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '8E44AD']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];
$sheet->getStyle('F1:K1')->applyFromArray($representanteHeaderStyle);

// Agregar filas de ejemplo
$ejemplos = [
    // Mayor de edad
    ['Juan Pérez García', '1990-05-15', '1712345678', '991234567', 'juan.perez@ejemplo.com', '', '', '', '', '', ''],
    // Mayor de edad
    ['María López Sánchez', '1985-08-20', '0912345678', '987654321', '', '', '', '', '', '', ''],
    // Menor de edad con representante
    ['Pedro Martínez', '2015-03-10', '', '', '', 'SI', 'Ana Martínez Ruiz', '0912345678', '998765432', 'ana.martinez@ejemplo.com', '1980-06-15'],
    // Menor de edad con mismo representante (hermana)
    ['Lucía Martínez', '2018-07-22', '', '', '', 'SI', 'Ana Martínez Ruiz', '0912345678', '998765432', 'ana.martinez@ejemplo.com', '1980-06-15'],
    // Menor de edad con otro representante
    ['Sofía Gómez', '2017-11-25', '', '', '', 'SI', 'Carlos Gómez Pérez', '1798765432', '991122334', '', ''],
];

$row = 2;
foreach ($ejemplos as $ejemplo) {
    $col = 0;
    foreach ($ejemplo as $valor) {
        $colLetter = chr(65 + $col);
        $sheet->setCellValue($colLetter . $row, $valor);
        $col++;
    }
    $row++;
}

// Estilo para filas de ejemplo (mayores de edad)
$exampleStyleMayor = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8F8F5']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'DEE2E6']
        ]
    ],
    'font' => [
        'color' => ['rgb' => '27AE60'],
        'italic' => true
    ]
];

// Estilo para filas de ejemplo (menores de edad)
$exampleStyleMenor = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F5EEF8']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'DEE2E6']
        ]
    ],
    'font' => [
        'color' => ['rgb' => '8E44AD'],
        'italic' => true
    ]
];

$sheet->getStyle('A2:K3')->applyFromArray($exampleStyleMayor);
$sheet->getStyle('A4:K6')->applyFromArray($exampleStyleMenor);

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(30); // nombre
$sheet->getColumnDimension('B')->setWidth(16); // fecha_nacimiento
$sheet->getColumnDimension('C')->setWidth(14); // cedula
$sheet->getColumnDimension('D')->setWidth(14); // celular
$sheet->getColumnDimension('E')->setWidth(28); // email
$sheet->getColumnDimension('F')->setWidth(12); // es_menor
$sheet->getColumnDimension('G')->setWidth(28); // representante_nombre
$sheet->getColumnDimension('H')->setWidth(18); // representante_cedula
$sheet->getColumnDimension('I')->setWidth(18); // representante_celular
$sheet->getColumnDimension('J')->setWidth(28); // representante_email
$sheet->getColumnDimension('K')->setWidth(24); // representante_fecha_nacimiento

// Agregar instrucciones en una segunda hoja
$instructionsSheet = $spreadsheet->createSheet();
$instructionsSheet->setTitle('Instrucciones');

$instructions = [
    ['INSTRUCCIONES PARA LLENAR LA PLANTILLA'],
    [''],
    ['COLUMNAS PRINCIPALES (A-E) - DATOS DEL ESTUDIANTE:'],
    ['- nombre: Nombre completo del estudiante (OBLIGATORIO)'],
    ['- fecha_nacimiento: Formato AAAA-MM-DD (ej: 2010-05-15). OBLIGATORIO.'],
    ['- cedula: 10 dígitos numéricos (OBLIGATORIO para mayores de edad)'],
    ['- celular: 9 dígitos sin el código +593 (ej: 991234567) - OBLIGATORIO para mayores'],
    ['- email: Correo electrónico válido (opcional)'],
    [''],
    ['COLUMNAS DE REPRESENTANTE LEGAL (F-K) - SOLO PARA MENORES:'],
    ['- es_menor: Escribir "SI" si el estudiante es menor de edad'],
    ['- representante_nombre: Nombre completo del representante legal (OBLIGATORIO si es menor)'],
    ['- representante_cedula: Cédula del representante 10 dígitos (OBLIGATORIO si es menor)'],
    ['- representante_celular: Celular del representante 9 dígitos sin +593 (OBLIGATORIO si es menor)'],
    ['- representante_email: Email del representante (opcional)'],
    ['- representante_fecha_nacimiento: Fecha de nacimiento del representante AAAA-MM-DD (opcional)'],
    [''],
    ['NOTAS IMPORTANTES:'],
    ['- Las filas en VERDE son ejemplos de mayores de edad'],
    ['- Las filas en MORADO son ejemplos de menores de edad con representante'],
    ['- Para MENORES: llenar nombre, fecha_nacimiento y todos los datos del representante'],
    ['- Para MAYORES: llenar nombre, fecha_nacimiento, cedula, celular (email opcional)'],
    ['- Los ejemplos pueden ser eliminados o reemplazados'],
    ['- No modifique los encabezados de la primera fila'],
    [''],
    ['MÚLTIPLES MENORES CON MISMO REPRESENTANTE:'],
    ['- Un representante puede tener varios menores a su cargo'],
    ['- Simplemente repita los mismos datos del representante en cada fila del menor'],
    ['- Vea el ejemplo de filas 4 y 5: Pedro y Lucía Martínez tienen el mismo representante']
];

$row = 1;
foreach ($instructions as $line) {
    $instructionsSheet->setCellValue('A' . $row, $line[0]);
    $row++;
}

// Estilo para título de instrucciones
$instructionsSheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => '2C3E50']
    ]
]);

// Estilos para secciones
$instructionsSheet->getStyle('A3')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '27AE60']]
]);
$instructionsSheet->getStyle('A10')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '8E44AD']]
]);
$instructionsSheet->getStyle('A17')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'E74C3C']]
]);

$instructionsSheet->getColumnDimension('A')->setWidth(70);

// Volver a la primera hoja
$spreadsheet->setActiveSheetIndex(0);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_estudiantes.xlsx"');
header('Cache-Control: max-age=0');

// Escribir archivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
