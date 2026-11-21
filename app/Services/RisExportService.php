<?php

namespace App\Services;

use App\Models\RisSlip;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class RisExportService
{
    /** Item lines on each slip (template rows 12–28 before Purpose). */
    public const MAX_ITEM_ROWS = 17;

    /** Horizontal copy column offsets: A-H, J-Q, S-Z */
    protected array $copyOffsets = [0, 9, 18];

    /** Vertical duplicate of the three slips starts at this row. */
    protected int $secondBlockStart = 39;

    public function resolveTemplatePath(): string
    {
        foreach ([
            base_path('template for 2026-RIS (3).xls'),
            storage_path('app/templates/template for 2026-RIS (3).xls'),
            storage_path('app/templates/RIS-2026.xls'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('RIS Excel template not found. Place "template for 2026-RIS (3).xls" in the project root.');
    }

    public function generateExcel(RisSlip $slip): string
    {
        $slip->loadMissing('items');

        $spreadsheet = IOFactory::load($this->resolveTemplatePath());
        $sheet = $spreadsheet->getSheetByName('RIS') ?? $spreadsheet->getSheet(0);

        foreach ([1, $this->secondBlockStart] as $blockStart) {
            $this->clearBlock($sheet, $blockStart);
            $this->fillBlock($sheet, $slip, $blockStart);
        }

        $sheet->setTitle('RIS');
        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if ($spreadsheet->getSheet($i)->getTitle() !== 'RIS') {
                $spreadsheet->removeSheetByIndex($i);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        $sheet->getPageSetup()->setPrintArea('A1:Z72');
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = sprintf(
            'RIS_%s_%s.xlsx',
            $slip->ris_date->format('Y-m-d'),
            preg_replace('/[^A-Za-z0-9_\-]/', '_', $slip->displayRisNo())
        );
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function clearBlock(Worksheet $sheet, int $blockStart): void
    {
        $itemStart = $blockStart === 1 ? 12 : 48;
        $itemEnd = $blockStart === 1 ? 28 : 64;
        $purposeRow = $blockStart === 1 ? 29 : 65;

        foreach ($this->copyOffsets as $offset) {
            for ($row = $itemStart; $row <= $itemEnd; $row++) {
                foreach (range(1, 8) as $colIndex) {
                    $sheet->setCellValue($this->cell($offset, $colIndex, $row), null);
                }
            }

            // Purpose lines
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow), null);
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow + 1), null);
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow + 2), null);

            // Signature names / designations / dates
            $sigRow = $purposeRow + 5; // 34 / 70
            foreach ([3, 4, 6, 8] as $colIndex) {
                $sheet->setCellValue($this->cell($offset, $colIndex, $sigRow), null);
            }
            $sheet->setCellValue($this->cell($offset, 4, $sigRow + 1), null);
            $sheet->setCellValue($this->cell($offset, 6, $sigRow + 1), null);
            $sheet->setCellValue($this->cell($offset, 3, $sigRow + 1), null);
            $sheet->setCellValue($this->cell($offset, 8, $sigRow + 1), null);
            foreach ([3, 4, 6, 8] as $colIndex) {
                $sheet->setCellValue($this->cell($offset, $colIndex, $sigRow + 2), null);
            }
        }
    }

    protected function fillBlock(Worksheet $sheet, RisSlip $slip, int $blockStart): void
    {
        $headerEntityRow = $blockStart === 1 ? 6 : 42;
        $headerDivRow = $blockStart === 1 ? 8 : 44;
        $headerOfficeRow = $blockStart === 1 ? 9 : 45;
        $itemStart = $blockStart === 1 ? 12 : 48;
        $purposeRow = $blockStart === 1 ? 29 : 65;
        $sigRow = $purposeRow + 5;

        $entity = $slip->entity_name ?: 'INHS-SHS';
        $fund = $slip->fund_cluster ?: 'MOOE -SHS';
        $division = $slip->division ?: '';
        $office = $slip->office ?: '';
        $rcc = $slip->responsibility_center_code ?: '';
        $risNo = $slip->displayRisNo();

        $requested = $slip->requested_by ?: '';
        $approved = $slip->approved_by ?: config('smartdtr.ris_approved_by', 'PRISCA ROMELA C. BUENAVENTURA');
        $issued = $slip->issued_by ?: config('smartdtr.ris_issued_by', 'JONALYN B. TUDDAO');
        $received = $slip->received_by ?: $requested;
        $approvedDesig = $slip->approved_designation ?: config('smartdtr.ris_approved_designation', 'ASSISTANT PRINCIPAL II');
        $issuedDesig = $slip->issued_designation ?: config('smartdtr.ris_issued_designation', 'AO II');
        $requestedDesig = $slip->requested_designation ?: '';
        $receivedDesig = $slip->received_designation ?: $requestedDesig;
        $dateLabel = $slip->ris_date->format('m/d/Y');

        $items = $slip->items->take(self::MAX_ITEM_ROWS)->values();

        foreach ($this->copyOffsets as $offset) {
            $sheet->setCellValue($this->cell($offset, 1, $headerEntityRow), 'Entity Name : '.$entity);
            $sheet->setCellValue($this->cell($offset, 7, $headerEntityRow), 'Fund Cluster : ______________________');
            $sheet->setCellValue($this->cell($offset, 8, $headerEntityRow), $fund);

            $sheet->setCellValue(
                $this->cell($offset, 1, $headerDivRow),
                'Division : '.($division !== '' ? $division : '_______________________________________________')
            );
            $sheet->setCellValue(
                $this->cell($offset, 6, $headerDivRow),
                'Responsibility Center Code : '.($rcc !== '' ? $rcc : '______________________')
            );

            $sheet->setCellValue(
                $this->cell($offset, 1, $headerOfficeRow),
                'Office : '.($office !== '' ? $office : '________________________________________________')
            );
            $sheet->setCellValue(
                $this->cell($offset, 6, $headerOfficeRow),
                'RIS No. : '.$risNo
            );

            foreach ($items as $index => $item) {
                $row = $itemStart + $index;
                $sheet->setCellValue($this->cell($offset, 1, $row), $item->stock_no);
                $sheet->setCellValue($this->cell($offset, 2, $row), $item->unit);
                $sheet->setCellValue($this->cell($offset, 3, $row), $item->description);
                $sheet->setCellValue($this->cell($offset, 4, $row), $item->quantity > 0 ? $item->quantity : null);

                $available = strtolower((string) $item->stock_available);
                if ($available === 'yes') {
                    $sheet->setCellValue($this->cell($offset, 5, $row), '✓');
                } elseif ($available === 'no') {
                    $sheet->setCellValue($this->cell($offset, 6, $row), '✓');
                }

                $issueQty = $item->issue_quantity;
                if ($issueQty === null && $available !== 'no') {
                    $issueQty = $item->quantity;
                }
                $sheet->setCellValue($this->cell($offset, 7, $row), $issueQty);
                $sheet->setCellValue($this->cell($offset, 8, $row), $item->remarks);
            }

            $sheet->setCellValue($this->cell($offset, 1, $purposeRow), '   Purpose:');
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow), $slip->purpose);
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow + 1), $slip->purpose_line2);
            $sheet->setCellValue($this->cell($offset, 2, $purposeRow + 2), $slip->purpose_line3);

            $sheet->setCellValue($this->cell($offset, 3, $sigRow), $requested);
            $sheet->setCellValue($this->cell($offset, 4, $sigRow), $approved);
            $sheet->setCellValue($this->cell($offset, 6, $sigRow), $issued);
            $sheet->setCellValue($this->cell($offset, 8, $sigRow), $received);

            $sheet->setCellValue($this->cell($offset, 3, $sigRow + 1), $requestedDesig);
            $sheet->setCellValue($this->cell($offset, 4, $sigRow + 1), $approvedDesig);
            $sheet->setCellValue($this->cell($offset, 6, $sigRow + 1), $issuedDesig);
            $sheet->setCellValue($this->cell($offset, 8, $sigRow + 1), $receivedDesig);

            $sheet->setCellValue($this->cell($offset, 3, $sigRow + 2), $dateLabel);
            $sheet->setCellValue($this->cell($offset, 4, $sigRow + 2), $dateLabel);
            $sheet->setCellValue($this->cell($offset, 6, $sigRow + 2), $dateLabel);
            $sheet->setCellValue($this->cell($offset, 8, $sigRow + 2), $dateLabel);
        }
    }

    protected function cell(int $offset, int $colIndex, int $row): string
    {
        return Coordinate::stringFromColumnIndex($colIndex + $offset).$row;
    }
}
