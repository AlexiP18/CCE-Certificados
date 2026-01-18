<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$tipo = $_GET['tipo'] ?? 'csv';

if ($tipo === 'excel') {
    // Generar plantilla Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Certificados');
    
    // Configurar encabezados - incluye datos de estudiantes
    $headers = ['nombre', 'cedula', 'celular', 'email', 'razon', 'fecha', 'categoria'];
    $sheet->fromArray($headers, null, 'A1');
    
    // Estilo para encabezados
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '2F5496']
            ]
        ]
    ];
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);
    
    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(30);  // nombre
    $sheet->getColumnDimension('B')->setWidth(15);  // cedula
    $sheet->getColumnDimension('C')->setWidth(15);  // celular
    $sheet->getColumnDimension('D')->setWidth(25);  // email
    $sheet->getColumnDimension('E')->setWidth(50);  // razon
    $sheet->getColumnDimension('F')->setWidth(12);  // fecha
    $sheet->getColumnDimension('G')->setWidth(12);  // categoria
    
    // Datos de ejemplo
    $ejemplos = [
        ['Juan Pérez García', '1712345678', '+593987654321', 'juan@email.com', '', '2025-12-06', ''],
        ['María López Rodríguez', '1798765432', '+593912345678', 'maria@email.com', '', '', ''],
        ['Carlos Martínez Silva', '', '', '', '', '', '']
    ];
    $sheet->fromArray($ejemplos, null, 'A2');
    
    // Estilo para datos de ejemplo
    $dataStyle = [
        'font' => ['italic' => true, 'color' => ['rgb' => '808080']],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D9D9D9']
            ]
        ]
    ];
    $sheet->getStyle('A2:G4')->applyFromArray($dataStyle);
    
    // Agregar notas explicativas
    $sheet->setCellValue('A6', 'INSTRUCCIONES:');
    $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4472C4'));
    
    $notas = [
        '• nombre (OBLIGATORIO): Nombre completo del estudiante para el certificado.',
        '• cedula (opcional): Número de cédula/identificación. Si existe, se vinculará al estudiante existente.',
        '• celular (opcional): Número de teléfono del estudiante.',
        '• email (opcional): Correo electrónico del estudiante.',
        '• razon (opcional): Razón del certificado. Si está vacía, se usa la configurada en la plantilla.',
        '• fecha (opcional): Fecha del certificado (formato: YYYY-MM-DD). Si está vacía, se usa la fecha actual.',
        '• categoria (opcional): ID de la categoría. Si está vacía, se usa la categoría seleccionada.',
        '',
        'IMPORTANTE: Los estudiantes nuevos se registrarán automáticamente en el sistema si no existen.'
    ];
    
    foreach ($notas as $i => $nota) {
        $row = 7 + $i;
        $sheet->setCellValue("A$row", $nota);
        $sheet->mergeCells("A$row:G$row");
        if ($i < 7) {
            $sheet->getStyle("A$row")->getFont()->setSize(10);
        } else {
            $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('C00000'));
        }
    }
    
    // Configurar descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="plantilla_certificados.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} else {
    // Descargar plantilla CSV con campos de estudiante
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="plantilla_certificados.csv"');
    header('Cache-Control: max-age=0');
    
    // BOM para UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, ['nombre', 'cedula', 'celular', 'email', 'razon', 'fecha', 'categoria']);
    
    // Ejemplos
    fputcsv($output, ['Juan Pérez García', '1712345678', '+593987654321', 'juan@email.com', '', '2025-12-06', '']);
    fputcsv($output, ['María López Rodríguez', '1798765432', '+593912345678', 'maria@email.com', '', '', '']);
    fputcsv($output, ['Carlos Martínez Silva', '', '', '', '', '', '']);
    
    fclose($output);
    exit;
}
