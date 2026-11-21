<?php

namespace App\Services;

use App\Models\LeaveCard;
use App\Models\Personnel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LeaveCardWorkbook
{
    public function __construct(
        protected LeaveCardService $leaveCards
    ) {}

    public function store(Personnel $personnel, LeaveCard $card): string
    {
        $months = $this->leaveCards->months($personnel, $card);
        $years = $this->leaveCards->yearlySummary($months);

        $book = new Spreadsheet;
        $this->monthlySheet($book->getActiveSheet(), $personnel, $card, $months);
        $this->yearlySheet($book->createSheet(), $personnel, $card, $years);
        $book->setActiveSheetIndex(0);

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/leave-card-'.$personnel->id.'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    /**
     * @param  list<array<string, mixed>>  $months
     */
    private function monthlySheet(Worksheet $sheet, Personnel $personnel, LeaveCard $card, array $months): void
    {
        $sheet->setTitle('General LC');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        foreach ([
            'A' => 16.8, 'B' => 21, 'C' => 11.4, 'D' => 14, 'E' => 11.4,
            'F' => 14, 'G' => 11.4, 'H' => 14, 'I' => 11.4, 'J' => 14, 'K' => 28,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', "EMPLOYEE'S LEAVE CARDS");
        $this->title($sheet, 'A1');

        $sheet->setCellValue('A3', 'Name');
        $sheet->mergeCells('B3:D3');
        $sheet->setCellValue('B3', $personnel->name);
        $sheet->setCellValue('E3', 'Civil Status');
        $sheet->mergeCells('F3:H3');
        $sheet->setCellValue('F3', $card->field('civil_status'));
        $sheet->setCellValue('I3', 'GSIS Policy No.');
        $sheet->setCellValue('K3', $card->field('gsis_policy_no'));

        $sheet->setCellValue('A4', 'Position');
        $sheet->mergeCells('B4:D4');
        $sheet->setCellValue('B4', $personnel->role);
        $sheet->setCellValue('E4', 'Entrance to Duty');
        $sheet->mergeCells('F4:H4');
        $sheet->setCellValue('F4', $card->entrance_to_duty?->format('F j, Y') ?? '');
        $sheet->setCellValue('I4', 'TIN No.');
        $sheet->setCellValue('K4', $card->field('tin'));

        $sheet->setCellValue('A5', 'Status');
        $sheet->mergeCells('B5:D5');
        $sheet->setCellValue('B5', strtoupper((string) $card->field('employment_status')));
        $sheet->setCellValue('E5', 'Unit:');
        $sheet->mergeCells('G5:H5');
        $sheet->setCellValue('G5', $card->field('unit'));
        $sheet->setCellValue('I5', 'National Reference Card No.');
        $sheet->setCellValue('K5', $card->field('national_reference_no'));
        $sheet->getStyle('A3:K5')->getFont()->setBold(true);

        $row = 7;
        $previousVl = null;
        $previousSl = null;
        $blocks = $months === [] ? [[]] : array_chunk($months, 16);

        foreach ($blocks as $block) {
            $sheet->mergeCells("A{$row}:K{$row}");
            $sheet->setCellValue("A{$row}", "EMPLOYEE'S LEAVE CARDS");
            $this->title($sheet, "A{$row}");
            $row += 2;

            $head = $row;
            $sub = $row + 1;
            $sheet->mergeCells("A{$head}:A{$sub}");
            $sheet->mergeCells("B{$head}:B{$sub}");
            $sheet->mergeCells("C{$head}:F{$head}");
            $sheet->mergeCells("G{$head}:J{$head}");
            $sheet->mergeCells("K{$head}:K{$sub}");
            $sheet->setCellValue("A{$head}", 'PERIOD');
            $sheet->setCellValue("B{$head}", 'PARTICULARS');
            $sheet->setCellValue("C{$head}", 'VACATION LEAVE');
            $sheet->setCellValue("G{$head}", 'SICK LEAVE');
            $sheet->setCellValue("K{$head}", 'REMARKS');
            foreach (['C', 'G'] as $start) {
                $labels = ['EARNED', 'Absence Undertime W/ Pay', 'BALANCE', 'Absence Undertime W/o Pay'];
                foreach ($labels as $index => $label) {
                    $column = chr(ord($start) + $index);
                    $sheet->setCellValue("{$column}{$sub}", $label);
                }
            }
            $sheet->getStyle("A{$head}:K{$sub}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
            $sheet->getRowDimension($sub)->setRowHeight(32);

            $row = $sub + 1;
            $startData = $row;
            $lineCount = $block === [] ? 16 : count($block);
            for ($line = 0; $line < $lineCount; $line++) {
                $month = $block[$line] ?? null;
                if (is_array($month)) {
                    $sheet->setCellValue("A{$row}", $month['period']);
                    $sheet->setCellValue("B{$row}", $month['particulars']);
                    $sheet->setCellValue("C{$row}", $month['earned']);
                    $sheet->setCellValue("G{$row}", $month['earned']);
                    $this->days($sheet, "D{$row}", $month['vl_with_pay']);
                    $this->days($sheet, "F{$row}", $month['vl_without_pay']);
                    $this->days($sheet, "H{$row}", $month['sl_with_pay']);
                    $this->days($sheet, "J{$row}", $month['sl_without_pay']);
                    $sheet->setCellValue("K{$row}", $month['remarks']);
                }
                $sheet->setCellValue("E{$row}", $previousVl ? "={$previousVl}+C{$row}-D{$row}" : "=C{$row}-D{$row}");
                $sheet->setCellValue("I{$row}", $previousSl ? "={$previousSl}+G{$row}-H{$row}" : "=G{$row}-H{$row}");
                $previousVl = "E{$row}";
                $previousSl = "I{$row}";
                $row++;
            }
            $end = $row - 1;
            $sheet->getStyle("C{$startData}:J{$end}")->getNumberFormat()->setFormatCode('0.000');
            $sheet->getStyle("A{$head}:K{$end}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("C{$startData}:J{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('A1:K'.$sheet->getHighestRow())->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    }

    /**
     * @param  list<array<string, mixed>>  $years
     */
    private function yearlySheet(Worksheet $sheet, Personnel $personnel, LeaveCard $card, array $years): void
    {
        $sheet->setTitle('Abstract Summary (Yearly)');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(1);

        foreach (['A' => 16, 'B' => 16, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 12, 'I' => 22] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'RECORD OF LEAVE CREDITS');
        $this->title($sheet, 'A1');

        $sheet->setCellValue('A3', 'Name:');
        $sheet->mergeCells('B3:C3');
        $sheet->setCellValue('B3', $personnel->name);
        $sheet->setCellValue('G3', 'Position:');
        $sheet->setCellValue('H3', $personnel->role);

        $sheet->setCellValue('A4', 'Name of Agency:');
        $sheet->mergeCells('C4:E4');
        $sheet->setCellValue('C4', 'Schools Division of the City of Ilagan');
        $sheet->setCellValue('G4', 'District/Station:');
        $sheet->setCellValue('I4', 'Isabela National High School');

        $sheet->setCellValue('A5', 'First Day of Government Service:');
        $sheet->mergeCells('D5:E5');
        $sheet->setCellValue('D5', $card->entrance_to_duty ? strtoupper($card->entrance_to_duty->format('F j, Y')) : '');
        $sheet->setCellValue('G5', 'Last Day of Government Service:');

        $sheet->mergeCells('A9:B9');
        $sheet->mergeCells('C9:E9');
        $sheet->mergeCells('F9:H9');
        $sheet->mergeCells('I9:I10');
        $sheet->setCellValue('A9', 'PERIOD');
        $sheet->setCellValue('C9', 'VACATION LEAVE');
        $sheet->setCellValue('F9', 'SICK LEAVE');
        $sheet->setCellValue('I9', 'REMARKS');
        foreach (['A' => 'FROM', 'B' => 'TO', 'C' => 'EARNED', 'D' => 'USED', 'E' => 'BALANCE', 'F' => 'EARNED', 'G' => 'USED', 'H' => 'BALANCE'] as $column => $label) {
            $sheet->setCellValue($column.'10', $label);
        }
        $sheet->getStyle('A9:I10')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $row = 11;
        $previousVl = null;
        $previousSl = null;
        foreach ($years as $year) {
            $sheet->setCellValue("A{$row}", $year['from']->format('m/d/Y'));
            $sheet->setCellValue("B{$row}", $year['to']->format('m/d/Y'));
            $sheet->setCellValue("C{$row}", $year['vl_earned']);
            $sheet->setCellValue("F{$row}", $year['sl_earned']);
            $this->days($sheet, "D{$row}", $year['vl_used']);
            $this->days($sheet, "G{$row}", $year['sl_used']);
            $sheet->setCellValue("E{$row}", $previousVl ? "={$previousVl}+C{$row}-D{$row}" : "=C{$row}-D{$row}");
            $sheet->setCellValue("H{$row}", $previousSl ? "={$previousSl}+F{$row}-G{$row}" : "=F{$row}-G{$row}");
            $sheet->setCellValue("I{$row}", $year['remarks']);
            $previousVl = "E{$row}";
            $previousSl = "H{$row}";
            $row++;
        }
        $end = max(11, $row - 1);
        $sheet->getStyle("C11:H{$end}")->getNumberFormat()->setFormatCode('0.000');
        $sheet->getStyle("A11:I{$end}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("I11:I{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sign = $end + 3;
        $sheet->setCellValue("A{$sign}", 'Prepared by:');
        $sheet->setCellValue("F{$sign}", 'Approved:');
        $sheet->setCellValue('B'.($sign + 2), $card->field('prepared_by'));
        $sheet->setCellValue('F'.($sign + 2), $card->field('approved_by'));
        $sheet->setCellValue('A'.($sign + 3), $card->field('prepared_title'));
        $sheet->setCellValue('F'.($sign + 3), $card->field('approved_title'));
        $sheet->setCellValue('C'.($sign + 5), 'Noted:');
        $sheet->setCellValue('E'.($sign + 7), $card->field('noted_by'));
        $sheet->setCellValue('E'.($sign + 8), $card->field('noted_title'));
        $sheet->getStyle('B'.($sign + 2).':F'.($sign + 8))->getFont()->setBold(true);

        $sheet->getStyle('A1:I'.$sheet->getHighestRow())->getFont()->setName('Arial')->setSize(10);
    }

    private function title(Worksheet $sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function days(Worksheet $sheet, string $cell, float $value): void
    {
        if ($value > 0) {
            $sheet->setCellValue($cell, $value);
        }
    }
}
