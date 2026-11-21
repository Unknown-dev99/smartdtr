<?php

namespace App\Services;

use App\Models\RsmiReport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class RsmiExportService
{
    public const MAX_ITEM_ROWS = 63; // template rows 12–74

    public function resolveTemplatePath(): string
    {
        $candidates = [
            base_path('RSMI 2023 (1).xlsx'),
            storage_path('app/templates/RSMI.xlsx'),
            storage_path('app/templates/RSMI 2023 (1).xlsx'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('RSMI Excel template not found. Place "RSMI 2023 (1).xlsx" in the project root.');
    }

    public function generateExcel(RsmiReport $report): string
    {
        $report->loadMissing('items');

        $spreadsheet = IOFactory::load($this->resolveTemplatePath());
        $sheet = $this->pickLayoutSheet($spreadsheet);
        $this->clearItemRows($sheet);
        $this->fillHeader($sheet, $report);
        $this->fillItems($sheet, $report);
        $this->fillSignatures($sheet, $report);

        $title = strtoupper($report->report_date->format('M Y'));
        $sheet->setTitle(substr($title, 0, 31));

        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if ($spreadsheet->getSheet($i)->getTitle() !== $sheet->getTitle()) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getPageSetup()->setPrintArea('A1:H79');
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = sprintf(
            'RSMI_%s_%s.xlsx',
            $report->report_date->format('Y-m'),
            preg_replace('/[^A-Za-z0-9_\-]/', '_', $report->serial_no ?: $report->reportNumber())
        );
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function pickLayoutSheet($spreadsheet): Worksheet
    {
        // Prefer MAY layout from the official INHS workbook.
        return $spreadsheet->getSheetByName('MAY') ?? $spreadsheet->getSheet(0);
    }

    protected function clearItemRows(Worksheet $sheet): void
    {
        for ($row = 12; $row <= 74; $row++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
                $sheet->setCellValue($col.$row, null);
            }
            // Keep Amount formula if present; otherwise restore it.
            $formula = $sheet->getCell('H'.$row)->getValue();
            if (! is_string($formula) || ! str_starts_with($formula, '=')) {
                $sheet->setCellValue('H'.$row, '=F'.$row.'*G'.$row);
            }
        }
    }

    protected function fillHeader(Worksheet $sheet, RsmiReport $report): void
    {
        $entity = $report->entity_name ?: 'Isabela National High School';
        $sheet->setCellValue('A6', 'Entity Name: _'.$entity);
        $sheet->setCellValue('F6', 'Serial No. : '.($report->serial_no ?: $report->reportNumber()));
        $sheet->setCellValue('A7', 'Fund Cluster: '.($report->fund_cluster ?: '__________________________________'));
        $sheet->setCellValue('F7', 'Date: '.$report->report_date->format('F Y'));
    }

    protected function fillItems(Worksheet $sheet, RsmiReport $report): void
    {
        $items = $report->items->take(self::MAX_ITEM_ROWS)->values();

        foreach ($items as $index => $item) {
            $row = 12 + $index;
            $sheet->setCellValue('A'.$row, $item->ris_no);
            $sheet->setCellValue('B'.$row, $item->responsibility_center);
            $sheet->setCellValue('C'.$row, $item->stock_no);
            $sheet->setCellValue('D'.$row, $item->item);
            $sheet->setCellValue('E'.$row, $item->unit);
            $sheet->setCellValue('F'.$row, $item->quantity);
            $sheet->setCellValue('G'.$row, $item->unit_cost);
            $sheet->setCellValue('H'.$row, '=F'.$row.'*G'.$row);
        }
    }

    protected function fillSignatures(Worksheet $sheet, RsmiReport $report): void
    {
        $custodian = $report->supply_custodian
            ?: config('smartdtr.rsmi_supply_custodian', '');
        $accountant = $report->accounting_staff
            ?: config('smartdtr.rsmi_accounting_staff', '');

        if ($custodian !== '') {
            $sheet->setCellValue('A77', $custodian);
        }
        if ($accountant !== '') {
            $sheet->setCellValue('F77', $accountant);
        }

        $sheet->setCellValue('H77', $report->report_date->format('m/d/Y'));
    }
}
