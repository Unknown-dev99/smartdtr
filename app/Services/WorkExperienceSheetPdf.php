<?php

namespace App\Services;

use App\Models\WorkExperienceSheet;
use FPDF;

class WorkExperienceSheetPdf
{
    protected FPDF $pdf;

    protected float $bodySize = 11;

    protected float $lead = 4.6;

    protected float $left = 12;

    protected float $right = 204;

    public function render(WorkExperienceSheet $sheet): string
    {
        $this->pdf = new FPDF('P', 'mm', [215.9, 330.2]);
        $this->pdf->SetCompression(false);
        $this->pdf->SetAutoPageBreak(false);
        $fonts = resource_path('fonts');
        $this->pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $this->pdf->AddFont('Arial', 'I', 'ariali.json', $fonts);
        $this->pdf->AddFont('ArialNarrow', '', 'arialn.json', $fonts);
        $this->pdf->AddFont('ArialNarrow', 'I', 'arialni.json', $fonts);
        $this->pdf->SetTitle($this->text('Work Experience Sheet'));

        [$this->bodySize, $this->lead, $top] = $this->fitOnOnePage($sheet);
        $this->pdf->SetMargins($this->left, $top, 215.9 - $this->right);
        $this->pdf->AddPage();
        $this->pdf->SetXY($this->left, $top);

        $topY = $top;
        $boxBottom = 330.2 - 12;
        $this->header();
        $this->instructions();
        $this->rule();
        $this->sampleLabel();
        foreach ($this->sampleEntries() as $entry) {
            $this->experience($entry);
            $this->rule();
        }
        foreach ($sheet->entries as $entry) {
            $this->experience($entry);
            $this->rule();
        }

        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Rect($this->left, $topY, $this->right - $this->left, $boxBottom - $topY);
        $this->signature($sheet, $boxBottom);

        return $this->pdf->Output('S');
    }

    /**
     * @return array{0:float,1:float,2:float}
     */
    protected function fitOnOnePage(WorkExperienceSheet $sheet): array
    {
        $width = ($this->right - $this->left) - 8;
        $pageHeight = 330.2;

        for ($size = 11.0; $size >= 7.5; $size -= 0.5) {
            $lead = $size * 0.42;
            $top = $size >= 10 ? 14.0 : 12.0;
            $needed = $this->estimateHeight($sheet, $size, $lead, $width);
            if ($top + $needed <= $pageHeight - 12) {
                return [$size, $lead, $top];
            }
        }

        return [7.5, 3.15, 12.0];
    }

    protected function estimateHeight(WorkExperienceSheet $sheet, float $size, float $lead, float $width): float
    {
        $this->pdf->SetFont('Arial', 'I', $size);
        $height = $lead + 3.2;
        $height += $this->wrappedLines($this->pdf, 'Instructions:  1. Include only the work experiences relevant to the position being applied for.', $width) * $lead;
        $height += $this->wrappedLines($this->pdf, '2. The duration should include start and finish dates, if known, month in abbreviated form, if known, and year in full. For the current position, use the word Present, e.g., 1998-Present. Work experience should be listed starting with the most recent/present employment.', $width) * $lead;
        $height += $lead;
        $height += $lead;

        $blocks = array_merge($this->sampleEntries(), $sheet->entries);
        $this->pdf->SetFont('Arial', '', $size);
        foreach ($blocks as $entry) {
            foreach ([
                'Duration: '.$entry['duration'],
                'Position: '.$entry['position'],
                'Name of Office/Unit: '.$entry['office_unit'],
                'Immediate Supervisor: '.$entry['supervisor'],
                'Name of Agency/Organization and Location: '.$entry['agency'],
                'List of Accomplishments and Contributions (if any)',
                'Summary of Actual Duties',
                (string) ($entry['duties'] ?? ''),
            ] as $line) {
                $height += $this->wrappedLines($this->pdf, $line, $width - 8) * $lead;
            }
            foreach ($this->items((string) ($entry['accomplishments'] ?? '')) as $item) {
                $height += $this->wrappedLines($this->pdf, $item, $width - 14) * $lead;
            }
            $height += $lead * 0.8;
        }

        $height += $lead * 5;

        return $height;
    }

    protected function header(): void
    {
        $y = $this->pdf->GetY();
        $width = $this->right - $this->left;
        $height = $this->lead + 2.4;
        $this->pdf->SetFillColor(150, 150, 150);
        $this->pdf->Rect($this->left, $y, $width, $height, 'F');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('ArialNarrow', 'I', max(8, $this->bodySize - 1));
        $this->pdf->SetXY($this->left + 2, $y);
        $this->pdf->Cell(78, $height, 'Attachment to CS Form No. 212', 0, 0, 'L');
        $this->pdf->SetFont('ArialNarrow', 'I', $this->bodySize + 1);
        $this->pdf->SetXY($this->left, $y);
        $this->pdf->Cell($width, $height, 'WORK EXPERIENCE SHEET', 0, 1, 'C');
        $this->pdf->SetLineWidth(0.25);
        $this->pdf->Line($this->left, $y + $height, $this->right, $y + $height);
    }

    protected function instructions(): void
    {
        $start = $this->pdf->GetY();
        $width = $this->right - $this->left;
        $inner = $width - 6;
        $this->pdf->SetFont('Arial', 'I', $this->bodySize);
        $first = 'Instructions:  1. Include only the work experiences relevant to the position being applied for.';
        $second = '2. The duration should include start and finish dates, if known, month in abbreviated form, if known, and year in full. For the current position, use the word Present, e.g., 1998-Present. Work experience should be listed starting with the most recent/present employment.';
        $block = ($this->wrappedLines($this->pdf, $first, $inner) + $this->wrappedLines($this->pdf, $second, $inner)) * $this->lead + 2;
        $this->pdf->SetFillColor(234, 234, 234);
        $this->pdf->Rect($this->left, $start, $width, $block, 'F');
        $this->pdf->SetXY($this->left + 3, $start + 1);
        $this->pdf->MultiCell($inner, $this->lead, $this->text($first), 0, 'L');
        $this->pdf->SetX($this->left + 3);
        $this->pdf->MultiCell($inner, $this->lead, $this->text($second), 0, 'L');
        $this->pdf->SetY($start + $block);
    }

    protected function sampleLabel(): void
    {
        $this->pdf->SetX($this->left + 2);
        $this->pdf->SetFont('ArialNarrow', '', max(8, $this->bodySize - 1));
        $this->pdf->Cell($this->right - $this->left - 4, $this->lead, $this->text('Sample: If applying to Supervising Administrative Officer'), 0, 1, 'L');
    }

    /**
     * @param  array<string, string>  $entry
     */
    protected function experience(array $entry): void
    {
        $this->pdf->SetFont('Arial', '', $this->bodySize);
        $this->bullet('Duration: '.$entry['duration'], 1);
        $this->bullet('Position: '.$entry['position'], 1);
        $this->bullet('Name of Office/Unit: '.$entry['office_unit'], 1);
        $this->bullet('Immediate Supervisor: '.$entry['supervisor'], 1);
        $this->bullet('Name of Agency/Organization and Location: '.$entry['agency'], 1);
        $this->bullet('List of Accomplishments and Contributions (if any)', 1);
        foreach ($this->items((string) ($entry['accomplishments'] ?? '')) as $item) {
            $this->bullet($item, 2);
        }
        $this->bullet('Summary of Actual Duties', 1);
        $duties = trim((string) ($entry['duties'] ?? ''));
        if ($duties !== '') {
            $this->bullet($duties, 2);
        }
    }

    protected function bullet(string $text, int $level): void
    {
        $indent = $level === 1 ? 4.0 : 10.0;
        $markWidth = 4.0;
        $textWidth = $this->right - $this->left - $indent - $markWidth - 3;
        $y = $this->pdf->GetY();
        $this->pdf->SetXY($this->left + $indent, $y);
        $this->pdf->SetFont('Arial', '', $level === 1 ? $this->bodySize : max(7.5, $this->bodySize - 0.5));
        $this->pdf->Cell($markWidth, $this->lead, $level === 1 ? chr(149) : 'o', 0, 0);
        $this->pdf->MultiCell($textWidth, $this->lead, $this->text($text), 0, 'L');
    }

    protected function rule(): void
    {
        $y = $this->pdf->GetY() + 0.6;
        $this->pdf->SetLineWidth(0.25);
        $this->pdf->Line($this->left, $y, $this->right, $y);
        $this->pdf->SetY($y);
    }

    protected function signature(WorkExperienceSheet $sheet, float $boxBottom): void
    {
        $width = $this->right - $this->left;
        $block = $this->lead * 4.6;
        $y = $boxBottom - $block - 4;
        $lineRight = $this->right - 6;
        $lineLeft = $lineRight - 58;
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->Line($lineLeft, $y, $lineRight, $y);
        $this->pdf->SetFont('Arial', '', $this->bodySize);
        $this->pdf->SetXY($this->left, $y + 0.6);
        $this->pdf->Cell($width - 6, $this->lead, $this->text($sheet->printed_name), 0, 1, 'R');
        $this->pdf->SetX($this->left);
        $this->pdf->Cell($width - 6, $this->lead, '(Signature over Printed Name', 0, 1, 'R');
        $this->pdf->SetX($this->left);
        $this->pdf->Cell($width - 6, $this->lead, 'of Employee/Applicant)', 0, 1, 'R');
        $date = $sheet->sheet_date?->format('F j, Y') ?? '';
        $dateWidth = max(32, $this->pdf->GetStringWidth($this->text($date !== '' ? $date : ' ')) + 2);
        $this->pdf->SetX($this->left);
        $this->pdf->Cell($width - 6 - $dateWidth, $this->lead, 'Date:', 0, 0, 'R');
        $this->pdf->Cell($dateWidth, $this->lead, $this->text($date), 'B', 1, 'C');
    }

    /**
     * @return array<int, string>
     */
    protected function items(string $text): array
    {
        $lines = [];
        foreach (preg_split("/\r\n|\r|\n/", $text) ?: [] as $item) {
            $item = trim($item);
            if ($item !== '') {
                $lines[] = $item;
            }
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function sampleEntries(): array
    {
        return [
            [
                'duration' => 'February 11, 2011 – present',
                'position' => 'Human Resource Management Officer III',
                'office_unit' => 'Finance and Administrative Service',
                'supervisor' => 'Maria Estrada',
                'agency' => 'Department of Human Resources, Metro Manila',
                'accomplishments' => "Developed recruitment plan\nDesigned training program for retirees under EO 366",
                'duties' => "Responsible for the management of the recruitment and selection process and the coordination of training activities of the Department; provides assistance in the management of the Division's programs and activities and performs other related functions.",
            ],
            [
                'duration' => 'January 2, 2002 – February 10, 2011',
                'position' => 'Administrative Officer III',
                'office_unit' => 'Finance and Administrative Division',
                'supervisor' => 'Celia Romano',
                'agency' => 'Department of Finance',
                'accomplishments' => '',
                'duties' => 'Responsible for performing administrative and technical tasks e.g., pre-screening of applicants, preparation of monthly report on accession and separation, report of appointments issued, preparation of minutes of meetings of various HR committees and monitoring of trainings conducted; responds to queries and performs other related functions.',
            ],
        ];
    }

    protected function wrappedLines(FPDF $pdf, string $text, float $width): int
    {
        $text = trim($this->text($text));
        if ($text === '') {
            return 1;
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = 1;
        $current = '';
        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current.' '.$word;
            if ($pdf->GetStringWidth($trial) > $width && $current !== '') {
                $lines++;
                $current = $word;
            } else {
                $current = $trial;
            }
        }

        return max(1, $lines);
    }

    protected function text(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
