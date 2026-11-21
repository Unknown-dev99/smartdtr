<?php

namespace App\Services;

use App\Models\CompensatoryTimeOff;
use FPDF;

class CompensatoryTimeOffPdf
{
    public function render(CompensatoryTimeOff $record): string
    {
        $record->loadMissing('personnel');
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetCompression(false);
        $pdf->SetAutoPageBreak(true, 16);
        $fonts = resource_path('fonts');
        $pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $pdf->SetMargins(12, 14, 12);
        $pdf->SetTitle('Compensatory Time Off');
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        foreach ([
            'Republic of the Philippines',
            'Department of Education',
            'Region II - Schools Division of the City of Ilagan',
            'Isabela National High School',
        ] as $line) {
            $pdf->Cell(0, 5, $this->text($line), 0, 1, 'C');
        }
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 13);
        $pdf->Cell(0, 8, $this->text('COMPENSATORY TIME-OFF'), 0, 1, 'C');
        $pdf->Ln(2);

        $person = $record->personnel;
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(38, 6, 'Name', 0, 0);
        $pdf->Cell(0, 6, $this->text($person?->name ?: 'Employee'), 0, 1);
        $pdf->Cell(38, 6, 'Position', 0, 0);
        $pdf->Cell(0, 6, $this->text((string) ($person?->role ?: '')), 0, 1);
        $pdf->Cell(38, 6, 'COC balance', 0, 0);
        $pdf->Cell(0, 6, $this->text((string) ($record->balance_hours ?: '')), 0, 1);
        $pdf->Ln(3);

        $widths = [32, 32, 24, 98];
        $pdf->SetFillColor(226, 226, 226);
        foreach (['From', 'To', 'Hours', 'Purpose'] as $index => $label) {
            $pdf->Cell($widths[$index], 7, $label, 1, 0, 'L', true);
        }
        $pdf->Ln();
        foreach ($record->entries as $entry) {
            $cells = [
                (string) ($entry['date_from'] ?? ''),
                (string) ($entry['date_to'] ?? ''),
                (string) ($entry['hours'] ?? ''),
                (string) ($entry['purpose'] ?? ''),
            ];
            foreach ($cells as $index => $cell) {
                $pdf->Cell($widths[$index], 7, $this->text($this->clip($cell, $index === 3 ? 55 : 16)), 1, 0);
            }
            $pdf->Ln();
        }

        $pdf->Ln(12);
        $pdf->Cell(0, 5, $this->text('Requested by:'), 0, 1);
        $pdf->Ln(12);
        $pdf->Cell(80, 5, '________________________________', 0, 1);
        $pdf->Cell(80, 5, $this->text($person?->name ?: ''), 0, 1);
        $pdf->Ln(4);
        $pdf->Cell(0, 5, $this->text('Date prepared: '.($record->prepared_on?->format('F j, Y') ?: '')), 0, 1);

        return $pdf->Output('S');
    }

    protected function clip(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length - 1).'.';
    }

    protected function text(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
