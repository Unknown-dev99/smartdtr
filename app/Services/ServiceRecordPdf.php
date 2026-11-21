<?php

namespace App\Services;

use App\Models\ServiceRecord;
use FPDF;

class ServiceRecordPdf
{
    public function render(ServiceRecord $record): string
    {
        $record->loadMissing('personnel');
        $pdf = $this->document();
        $pdf->AddPage();
        $this->heading($pdf, 'SERVICE RECORD');

        $person = $record->personnel;
        $name = $person?->name ?: 'Employee';
        $this->line($pdf, 'Name', $name);
        $this->line($pdf, 'Employee number', (string) ($person?->employee_number ?: ''));
        $birth = $record->birth_date?->format('F j, Y') ?: '';
        $this->line($pdf, 'Date of birth', trim($birth.($record->birth_place ? '  /  '.$record->birth_place : '')));

        $pdf->Ln(3);
        $widths = [24, 24, 36, 22, 24, 40, 16];
        $headers = ['From', 'To', 'Designation', 'Status', 'Salary', 'Station / Office', 'L/V Abs'];
        $this->row($pdf, $headers, $widths, true);
        foreach ($record->entries as $entry) {
            $this->row($pdf, [
                (string) ($entry['date_from'] ?? ''),
                (string) ($entry['date_to'] ?? ''),
                (string) ($entry['designation'] ?? ''),
                (string) ($entry['status'] ?? ''),
                (string) ($entry['salary'] ?? ''),
                (string) ($entry['station'] ?? ''),
                (string) ($entry['lwop'] ?? ''),
            ], $widths, false);
        }

        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, $this->text('Certified correct:'), 0, 1);
        $pdf->Ln(12);
        $pdf->Cell(80, 5, $this->text('________________________________'), 0, 1);
        $pdf->Cell(80, 5, $this->text('Human Resource Management Officer'), 0, 1);
        $pdf->Ln(4);
        $pdf->Cell(0, 5, $this->text('Date prepared: '.($record->prepared_on?->format('F j, Y') ?: '')), 0, 1);

        return $pdf->Output('S');
    }

    protected function document(): FPDF
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetCompression(false);
        $pdf->SetAutoPageBreak(true, 16);
        $fonts = resource_path('fonts');
        $pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $pdf->SetMargins(12, 14, 12);
        $pdf->SetTitle($this->text('Service Record'));

        return $pdf;
    }

    protected function heading(FPDF $pdf, string $title): void
    {
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
        $pdf->SetFont('Arial', '', 14);
        $pdf->Cell(0, 8, $this->text($title), 0, 1, 'C');
        $pdf->Ln(2);
    }

    protected function line(FPDF $pdf, string $label, string $value): void
    {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(38, 6, $this->text($label), 0, 0);
        $pdf->Cell(0, 6, $this->text($value), 0, 1);
    }

    /**
     * @param  list<string>  $cells
     * @param  list<int>  $widths
     */
    protected function row(FPDF $pdf, array $cells, array $widths, bool $header): void
    {
        $pdf->SetFont('Arial', '', $header ? 8 : 8);
        $pdf->SetFillColor($header ? 226 : 255, $header ? 226 : 255, $header ? 226 : 255);
        foreach ($cells as $index => $cell) {
            $pdf->Cell($widths[$index], 7, $this->text($this->clip($cell, $header ? 18 : 28)), 1, 0, 'L', $header);
        }
        $pdf->Ln();
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
