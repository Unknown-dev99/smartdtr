<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateDtrTemplate extends Command
{
    protected $signature = 'dtr:generate-template';

    protected $description = 'Generate starter DepEd CS Form No. 48 DTR Excel template';

    public function handle(): int
    {
        $dir = storage_path('app/templates');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $paths = [
            $dir.'/DTR-2025-2026.xlsx',
            $dir.'/CS_Form_48_DTR.xlsx',
        ];

        foreach ($paths as $path) {
            $this->createTemplate($path);
            $this->info("Created: {$path}");
        }

        return self::SUCCESS;
    }

    protected function createTemplate(string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DTR');

        $sheet->setCellValue('A1', 'Civil Service Form No. 48');
        $sheet->setCellValue('A2', 'DAILY TIME RECORD');
        $sheet->setCellValue('G1', 'Employee No.:');
        $sheet->setCellValue('H1', '');
        $sheet->setCellValue('C3', '(Name)');
        $sheet->setCellValue('C5', 'For the month of __________');
        $sheet->setCellValue('A6', 'Official hours of arrival and departure');
        $sheet->setCellValue('G6', '');
        $sheet->setCellValue('G7', '');

        $headers = ['Day', 'AM Arrival', 'AM Departure', 'PM Arrival', 'PM Departure', 'Undertime Hrs', 'Undertime Min'];
        $cols = ['C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i].'10', $header);
        }

        for ($day = 1; $day <= 31; $day++) {
            $row = 10 + $day;
            $sheet->setCellValue('C'.$row, $day);
        }

        $sheet->getStyle('A1:I50')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A10:I41')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }
}
