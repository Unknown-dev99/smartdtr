<?php

namespace App\Services;

use App\Models\PositionDescriptionForm;
use FPDF;

class PositionDescriptionPdf
{
    protected FPDF $pdf;

    protected float $left = 10;

    protected float $right = 206;

    public function render(PositionDescriptionForm $form): string
    {
        $this->pdf = new class extends FPDF
        {
            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetFont('Arial', 'I', 9);
                $this->Cell(0, 5, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
            }
        };
        $this->pdf->SetCompression(false);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AliasNbPages();
        $fonts = resource_path('fonts');
        $this->pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $this->pdf->AddFont('Arial', 'I', 'ariali.json', $fonts);
        $this->pdf->AddFont('Arial', 'B', 'arial.json', $fonts);
        $this->pdf->SetTitle($this->text('Position Description Form'));
        $this->pdf->AddPage('P', [215.9, 355.6]);

        $d = $form->details ?? [];
        $this->titleBlock();
        $this->valueRow('1. POSITION TITLE (as approved by authorized agency) with parenthetical title', (string) ($d['position_title'] ?? ''), 16);
        $this->splitRow(
            '2. ITEM NUMBER',
            (string) ($d['item_number'] ?? ''),
            '3. SALARY GRADE',
            (string) ($d['salary_grade'] ?? ''),
            14
        );
        $this->localGovernment($d);
        $this->splitRow(
            '5. DEPARTMENT, CORPORATION OR AGENCY / LOCAL GOVERNMENT',
            (string) ($d['department'] ?? ''),
            '6. BUREAU OR OFFICE',
            (string) ($d['bureau'] ?? ''),
            14
        );
        $this->splitRow(
            '7. DEPARTMENT / BRANCH / DIVISION',
            (string) ($d['division'] ?? ''),
            '8. WORKSTATION / PLACE OF WORK',
            (string) ($d['workstation'] ?? ''),
            14
        );
        $this->fourRow($d);
        $this->splitRow(
            '13. POSITION TITLE OF IMMEDIATE SUPERVISOR',
            (string) ($d['immediate_supervisor'] ?? ''),
            '14. POSITION TITLE OF NEXT HIGHER SUPERVISOR',
            (string) ($d['next_supervisor'] ?? ''),
            14
        );
        $this->supervised($d);
        $this->valueRow('16. MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK', (string) ($d['machines'] ?? ''), 16);
        $this->contacts($d);
        $this->workingCondition($d);
        $this->valueRow('19. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION', (string) ($d['unit_function'] ?? ''), 18);
        $this->valueRow('20. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION (Job Summary)', (string) ($d['job_summary'] ?? ''), 22);
        $this->qualifications($d);
        $this->duties($d);
        $this->acknowledgment($form, $d);

        return $this->pdf->Output('S');
    }

    protected function titleBlock(): void
    {
        $this->pdf->SetXY($this->left, 10);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell($this->width(), 4, 'Republic of the Philippines', 0, 1, 'C');
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell($this->width(), 6, 'POSITION DESCRIPTION FORM', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell($this->width(), 4, $this->text('DBM-CSC Form No. 1 (Revised Version No. 1, s. 2017)'), 0, 1, 'C');
        $this->pdf->Ln(1.5);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function localGovernment(array $d): void
    {
        $height = 22;
        $this->room($height);
        $y = $this->pdf->GetY();
        $this->pdf->Rect($this->left, $y, $this->width(), $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($this->width() - 2, 3.2, '4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS', 0, 1);
        $x = $this->left + 2;
        $rowY = $y + 6;
        foreach (['province' => 'Province', 'city' => 'City', 'municipality' => 'Municipality'] as $key => $label) {
            $x = $this->tick($x, $rowY, $label, ($d['lg_unit'] ?? '') === $key);
        }
        $x = $this->left + 2;
        $rowY = $y + 13;
        foreach (['1st' => '1st Class', '2nd' => '2nd Class', '3rd' => '3rd Class', '4th' => '4th Class', '5th' => '5th Class', '6th' => '6th Class', 'special' => 'Special'] as $key => $label) {
            $x = $this->tick($x, $rowY, $label, ($d['lg_class'] ?? '') === $key);
        }
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function fourRow(array $d): void
    {
        $height = 16;
        $this->room($height);
        $y = $this->pdf->GetY();
        $w = $this->width() / 4;
        $cells = [
            ['9. PRESENT APPROP ACT', (string) ($d['present_approp'] ?? '')],
            ['10. PREVIOUS APPROP ACT', (string) ($d['previous_approp'] ?? '')],
            ['11. SALARY AUTHORIZED', (string) ($d['salary_authorized'] ?? '')],
            ['12. OTHER COMPENSATION', (string) ($d['other_compensation'] ?? '')],
        ];
        foreach ($cells as $i => [$label, $value]) {
            $this->box($this->left + ($w * $i), $y, $w, $height, $label, $value);
        }
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function supervised(array $d): void
    {
        $rows = array_values($d['supervised'] ?? []);
        if ($rows === []) {
            $rows = [['title' => '', 'item' => '']];
        }
        $rowH = 7;
        $height = 12 + (count($rows) * $rowH);
        $this->room($height);
        $y = $this->pdf->GetY();
        $this->pdf->Rect($this->left, $y, $this->width(), $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($this->width() - 2, 3.2, '15. POSITION TITLE, AND ITEM OF THOSE DIRECTLY SUPERVISED', 0, 1);
        $this->pdf->SetX($this->left + 1.2);
        $this->pdf->Cell($this->width() - 2, 3.2, $this->text('(if more than seven (7) list only by their item numbers and titles)'), 0, 1);
        $half = $this->width() / 2;
        $headY = $y + 8;
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY($this->left, $headY);
        $this->pdf->Cell($half, 4, 'POSITION TITLE', 'T', 0, 'C');
        $this->pdf->Cell($half, 4, 'ITEM NUMBER', 'T', 1, 'C');
        $this->pdf->SetFont('Arial', '', 8);
        $lineY = $headY + 4;
        foreach ($rows as $row) {
            $this->pdf->Line($this->left, $lineY, $this->right, $lineY);
            $this->pdf->SetXY($this->left + 1.2, $lineY);
            $this->pdf->Cell($half - 2, $rowH, $this->text((string) ($row['title'] ?? '')), 0, 0);
            $this->pdf->Cell($half - 2, $rowH, $this->text((string) ($row['item'] ?? '')), 0, 0);
            $this->pdf->Line($this->left + $half, $lineY, $this->left + $half, $lineY + $rowH);
            $lineY += $rowH;
        }
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function contacts(array $d): void
    {
        $height = 36;
        $this->room($height);
        $y = $this->pdf->GetY();
        $w = $this->width();
        $this->pdf->Rect($this->left, $y, $w, $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($w - 2, 3.2, '17. CONTACTS / CLIENTS / STAKEHOLDERS', 0, 1);
        $mid = $this->left + ($w / 2);
        $this->pdf->Line($mid, $y + 5, $mid, $y + $height);
        $this->pdf->Line($this->left, $y + 5, $this->right, $y + 5);
        $this->headerPair($y + 5.4, '17a. Internal', '17b. External');
        $internal = $d['contacts']['internal'] ?? [];
        $external = $d['contacts']['external'] ?? [];
        $rows = [
            ['Executive / Managerial', (string) ($internal['executive'] ?? ''), 'General Public', (string) ($external['general_public'] ?? '')],
            ['Supervisors', (string) ($internal['supervisors'] ?? ''), 'Other Agencies', (string) ($external['other_agencies'] ?? '')],
            ['Non-Supervisors', (string) ($internal['non_supervisors'] ?? ''), 'Others (Please Specify): '.($d['others_specify'] ?? ''), (string) ($external['others'] ?? '')],
            ['Staff', (string) ($internal['staff'] ?? ''), '', ''],
        ];
        $rowY = $y + 12;
        foreach ($rows as $row) {
            $this->pdf->Line($this->left, $rowY, $this->right, $rowY);
            $this->contactSide($this->left + 1.5, $rowY + 1.2, $row[0], $row[1]);
            if ($row[2] !== '') {
                $this->contactSide($mid + 1.5, $rowY + 1.2, $row[2], $row[3]);
            }
            $rowY += 6;
        }
        $this->pdf->SetY($y + $height);
    }

    protected function headerPair(float $y, string $left, string $right): void
    {
        $half = $this->width() / 2;
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->SetXY($this->left + 1.2, $y);
        $this->pdf->Cell($half - 28, 4, $left, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell(14, 4, 'Occasional', 0, 0, 'C');
        $this->pdf->Cell(12, 4, 'Frequent', 0, 0, 'C');
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->SetXY($this->left + $half + 1.2, $y);
        $this->pdf->Cell($half - 28, 4, $right, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell(14, 4, 'Occasional', 0, 0, 'C');
        $this->pdf->Cell(12, 4, 'Frequent', 0, 0, 'C');
    }

    protected function contactSide(float $x, float $y, string $label, string $frequency): void
    {
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell(46, 4, $this->text($label), 0, 0);
        $this->mark($x + 50, $y + 0.4, $frequency === 'occasional');
        $this->mark($x + 64, $y + 0.4, $frequency === 'frequent');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function workingCondition(array $d): void
    {
        $height = 16;
        $this->room($height);
        $y = $this->pdf->GetY();
        $this->pdf->Rect($this->left, $y, $this->width(), $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($this->width() - 2, 3.2, '18. WORKING CONDITION', 0, 1);
        $this->tick($this->left + 2, $y + 6, 'Office Work', (bool) ($d['work_office'] ?? false));
        $x = $this->tick($this->left + 40, $y + 6, 'Field Work', (bool) ($d['work_field'] ?? false));
        $x = $this->tick($x + 6, $y + 6, 'Other/s (Please Specify)', (bool) ($d['work_other'] ?? false));
        $this->pdf->SetXY($x, $y + 5.6);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(70, 4, $this->text((string) ($d['work_other_specify'] ?? '')), 'B', 0);
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function qualifications(array $d): void
    {
        $this->sectionBar('21. QUALIFICATION STANDARDS');
        $height = 18;
        $this->room($height);
        $y = $this->pdf->GetY();
        $w = $this->width() / 4;
        $cells = [
            ['21a. Education', (string) ($d['education'] ?? '')],
            ['21b. Experience', (string) ($d['experience'] ?? '')],
            ['21c. Training', (string) ($d['training'] ?? '')],
            ['21d. Eligibility', (string) ($d['eligibility'] ?? '')],
        ];
        foreach ($cells as $i => [$label, $value]) {
            $this->box($this->left + ($w * $i), $y, $w, $height, $label, $value);
        }
        $this->pdf->SetY($y + $height);
        $this->competency('21e. Core Competencies', (string) ($d['core_competencies'] ?? ''), (string) ($d['core_level'] ?? ''));
        $this->competency('21f. Leadership Competencies', (string) ($d['leadership_competencies'] ?? ''), (string) ($d['leadership_level'] ?? ''));
    }

    protected function competency(string $label, string $value, string $level): void
    {
        $height = max(16, 8 + $this->wrapped($value, $this->width() - 48) * 3.6);
        $this->room($height);
        $y = $this->pdf->GetY();
        $levelW = 42;
        $this->box($this->left, $y, $this->width() - $levelW, $height, $label, $value);
        $this->box($this->right - $levelW, $y, $levelW, $height, 'Competency Level', $level);
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function duties(array $d): void
    {
        $rows = array_values($d['duties'] ?? []);
        if ($rows === []) {
            $rows = [['percent' => '', 'duty' => '']];
        }
        $rowH = 8;
        $body = max(24, count($rows) * $rowH);
        $height = 11 + $body;
        $this->room(min($height, 40));
        if ($this->pdf->GetY() + $height > 340) {
            $this->pdf->AddPage('P', [215.9, 355.6]);
        }
        $y = $this->pdf->GetY();
        $levelW = 42;
        $percentW = 32;
        $this->pdf->Rect($this->left, $y, $this->width(), $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($this->width() - $levelW - 2, 3.4, '22. STATEMENT OF DUTIES AND RESPONSIBILITIES (Technical Competencies)', 0, 0);
        $this->pdf->Cell($levelW, 3.4, 'Competency Level', 0, 1, 'C');
        $headY = $y + 6;
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->SetXY($this->left, $headY);
        $this->pdf->Cell($percentW, 5, 'Percentage of Working Time', 'T', 0, 'C');
        $this->pdf->Cell($this->width() - $percentW - $levelW, 5, '(State the duties and responsibilities here:)', 'T', 0, 'C');
        $this->pdf->Cell($levelW, 5, '', 'T', 1);
        $lineY = $headY + 5;
        $this->pdf->SetFont('Arial', '', 8);
        foreach ($rows as $row) {
            $this->pdf->Line($this->left, $lineY, $this->right - $levelW, $lineY);
            $this->pdf->SetXY($this->left + 1, $lineY);
            $this->pdf->Cell($percentW - 2, $rowH, $this->text((string) ($row['percent'] ?? '')), 0, 0, 'C');
            $this->pdf->Cell($this->width() - $percentW - $levelW - 2, $rowH, $this->text((string) ($row['duty'] ?? '')), 0, 0);
            $lineY += $rowH;
        }
        $this->pdf->Line($this->left + $percentW, $headY, $this->left + $percentW, $y + $height);
        $this->pdf->Line($this->right - $levelW, $y, $this->right - $levelW, $y + $height);
        $this->pdf->SetXY($this->right - $levelW + 1, $headY + 6);
        $this->pdf->MultiCell($levelW - 2, 3.6, $this->text((string) ($d['duties_level'] ?? '')), 0, 'C');
        $this->pdf->SetY($y + $height);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function acknowledgment(PositionDescriptionForm $form, array $d): void
    {
        $height = 42;
        $this->room($height);
        $y = $this->pdf->GetY();
        $this->pdf->Rect($this->left, $y, $this->width(), $height);
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($this->width() - 2, 3.2, '23. ACKNOWLEDGMENT AND ACCEPTANCE:', 0, 1);
        $this->pdf->SetX($this->left + 1.2);
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->MultiCell($this->width() - 2.4, 3.6, $this->text('I have received a copy of this position description. It has been discussed with me and I have freely chosen to comply with the performance and behavior/conduct expectations contained herein.'), 0, 'L');
        $half = $this->width() / 2;
        $signY = $y + 24;
        $this->signature($this->left + 8, $signY, $half - 16, $form->employee_name, $form->employee_date?->format('F j, Y') ?? '', "Employee's Name, Date and Signature");
        $supervisorDate = (string) ($d['supervisor_date'] ?? '');
        $supervisorWhen = $supervisorDate !== '' ? date('F j, Y', strtotime($supervisorDate)) : '';
        $this->signature($this->left + $half + 8, $signY, $half - 16, (string) ($d['supervisor_name'] ?? ''), $supervisorWhen, "Supervisor's Name, Date and Signature");
        $this->pdf->SetY($y + $height);
    }

    protected function signature(float $x, float $y, float $w, string $name, string $date, string $caption): void
    {
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($x, $y);
        $line = trim($name.($date !== '' ? ', '.$date : ''));
        $this->pdf->Cell($w, 4, $this->text($line), 'B', 1, 'C');
        $this->pdf->SetXY($x, $y + 4.5);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($w, 3.4, $this->text($caption), 0, 0, 'C');
    }

    protected function valueRow(string $label, string $value, float $minHeight): void
    {
        $height = max($minHeight, 8 + ($this->wrapped($value, $this->width() - 3) * 3.8));
        $this->room($height);
        $y = $this->pdf->GetY();
        $this->box($this->left, $y, $this->width(), $height, $label, $value);
        $this->pdf->SetY($y + $height);
    }

    protected function splitRow(string $leftLabel, string $leftValue, string $rightLabel, string $rightValue, float $height): void
    {
        $this->room($height);
        $y = $this->pdf->GetY();
        $half = $this->width() / 2;
        $this->box($this->left, $y, $half, $height, $leftLabel, $leftValue);
        $this->box($this->left + $half, $y, $half, $height, $rightLabel, $rightValue);
        $this->pdf->SetY($y + $height);
    }

    protected function sectionBar(string $label): void
    {
        $this->room(6);
        $y = $this->pdf->GetY();
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Rect($this->left, $y, $this->width(), 6, 'FD');
        $this->pdf->SetXY($this->left + 1.2, $y + 1);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell($this->width() - 2, 4, $this->text($label), 0, 1);
        $this->pdf->SetY($y + 6);
    }

    protected function box(float $x, float $y, float $w, float $h, string $label, string $value): void
    {
        $this->pdf->Rect($x, $y, $w, $h);
        $this->pdf->SetXY($x + 1.2, $y + 0.8);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->MultiCell($w - 2.4, 2.8, $this->text($label), 0, 'L');
        $this->pdf->SetXY($x + 1.2, $y + 6.2);
        $this->pdf->SetFont('Arial', '', 8.5);
        $this->pdf->MultiCell($w - 2.4, 3.6, $this->text($value), 0, 'L');
    }

    protected function tick(float $x, float $y, string $label, bool $on): float
    {
        $this->mark($x, $y, $on);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($x + 4, $y - 0.3);
        $this->pdf->Cell($this->pdf->GetStringWidth($this->text($label)) + 1.5, 3.6, $this->text($label), 0, 0);

        return $this->pdf->GetX() + 3;
    }

    protected function mark(float $x, float $y, bool $on): void
    {
        $this->pdf->Rect($x, $y, 3.1, 3.1);
        if ($on) {
            $this->pdf->Line($x + 0.4, $y + 0.4, $x + 2.7, $y + 2.7);
            $this->pdf->Line($x + 2.7, $y + 0.4, $x + 0.4, $y + 2.7);
        }
    }

    protected function room(float $height): void
    {
        if ($this->pdf->GetY() + $height > 342) {
            $this->pdf->AddPage('P', [215.9, 355.6]);
            $this->pdf->SetY(12);
        }
    }

    protected function width(): float
    {
        return $this->right - $this->left;
    }

    protected function wrapped(string $text, float $width): int
    {
        $this->pdf->SetFont('Arial', '', 8.5);
        $text = trim($this->text($text));
        if ($text === '') {
            return 1;
        }
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = 1;
        $current = '';
        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current.' '.$word;
            if ($this->pdf->GetStringWidth($trial) > $width && $current !== '') {
                $lines++;
                $current = $word;
            } else {
                $current = $trial;
            }
        }

        return $lines;
    }

    protected function text(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
