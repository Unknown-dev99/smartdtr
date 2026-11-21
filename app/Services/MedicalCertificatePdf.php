<?php

namespace App\Services;

use App\Models\MedicalCertificate;
use FPDF;

class MedicalCertificatePdf
{
    protected FPDF $pdf;

    protected float $left = 14;

    protected float $right = 196;

    public function render(MedicalCertificate $certificate): string
    {
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetCompression(false);
        $this->pdf->SetAutoPageBreak(false);
        $fonts = resource_path('fonts');
        $this->pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $this->pdf->AddFont('Arial', 'I', 'ariali.json', $fonts);
        $this->pdf->SetTitle($this->text('Medical Certificate'));
        $this->pdf->AddPage();

        $d = $certificate->details ?? [];
        $exams = $d['exams'] ?? [];
        $this->heading();
        $this->rule(36);
        $this->instructions($exams);
        $this->rule(108);
        $this->appointee($certificate, $d);
        $this->rule(176);
        $this->physician($d);
        $this->rule(282);

        return $this->pdf->Output('S');
    }

    protected function heading(): void
    {
        $this->pdf->SetXY($this->left, 12);
        $this->pdf->SetFont('Arial', '', 10);
        $this->heavy($this->left, 12, 50, 5, 'CS Form No. 211', 'L');
        $this->pdf->SetFont('Arial', 'I', 9);
        $this->pdf->SetXY($this->left, 17);
        $this->pdf->Cell(50, 4, 'Revised 2025', 0, 0, 'L');
        $this->heavy($this->left, 24, $this->width(), 6, 'MEDICAL CERTIFICATE', 'C');
        $this->pdf->SetFont('Arial', 'I', 9);
        $this->pdf->SetXY($this->left, 30);
        $this->pdf->Cell($this->width(), 4, '(For Employment)', 0, 0, 'C');
    }

    /**
     * @param  array<int, string>  $exams
     */
    protected function instructions(array $exams): void
    {
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetXY($this->left, 39);
        $this->pdf->Cell($this->width(), 5, 'I N S T R U C T I O N S', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 8.5);
        $x = $this->left + 8;
        $w = $this->width() - 16;
        $this->pdf->SetXY($x, 46);
        $this->pdf->MultiCell($w, 4, $this->text('a. This medical certificate should be accomplished by a licensed government or private physician.'), 0, 'L');
        $this->pdf->SetX($x);
        $this->pdf->MultiCell($w, 4, $this->text('b. Attach this certificate to original appointment, transfer, and reemployment.'), 0, 'L');
        $this->pdf->SetX($x);
        $this->pdf->MultiCell($w, 4, $this->text('c. The results of the following pre-employment medical/physical/psychological examinations'), 0, 'L');
        $this->pdf->SetX($x + 4);
        $this->pdf->MultiCell($w - 4, 4, $this->text('must be attached to this form:'), 0, 'L');
        $y = 68;
        foreach (MedicalCertificateService::EXAMS as $key => $label) {
            $this->boxMark($x + 10, $y, in_array($key, $exams, true));
            $this->pdf->SetXY($x + 15, $y - 0.4);
            $this->pdf->Cell($w - 18, 4, $this->text($label), 0, 0);
            $y += 5.2;
        }
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function appointee(MedicalCertificate $certificate, array $d): void
    {
        $this->pdf->SetFont('Arial', '', 9.5);
        $this->pdf->SetXY($this->left, 110);
        $this->pdf->Cell($this->width(), 5, 'F O R   T H E   P R O P O S E D   A P P O I N T E E', 0, 0, 'C');

        $top = 117;
        $rightW = 68;
        $leftW = $this->width() - $rightW;
        $nameH = 18;
        $addressH = 16;
        $demoH = 16;
        $this->labeled($this->left, $top, $leftW, $nameH, 'NAME (Last Name, First Name, Name Extension (if any) and Middle Name)', $certificate->appointee_name);
        $this->labeled($this->left + $leftW, $top, $rightW, $nameH + $addressH, 'AGENCY / ADDRESS', (string) ($d['agency_address'] ?? ''));
        $this->labeled($this->left, $top + $nameH, $leftW, $addressH, 'ADDRESS', (string) ($d['address'] ?? ''));

        $demoY = $top + $nameH + $addressH;
        $ageW = 28;
        $sexW = 24;
        $civilW = $leftW - $ageW - $sexW;
        $this->splitLabel($this->left, $demoY, $ageW, $demoH, 'AGE', (string) ($d['age'] ?? ''));
        $this->splitLabel($this->left + $ageW, $demoY, $sexW, $demoH, 'SEX', (string) ($d['sex'] ?? ''));
        $this->splitLabel($this->left + $ageW + $sexW, $demoY, $civilW, $demoH, 'CIVIL STATUS', (string) ($d['civil_status'] ?? ''));
        $this->splitLabel($this->left + $leftW, $demoY, $rightW, $demoH, 'PROPOSED POSITION', (string) ($d['proposed_position'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function physician(array $d): void
    {
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY($this->left, 178);
        $this->pdf->Cell($this->width(), 5, 'F O R   T H E   L I C E N S E D   G O V E R N M E N T   O R   P R I V A T E   P H Y S I C I A N', 0, 0, 'C');

        $top = 185;
        $rightW = 62;
        $leftW = $this->width() - $rightW;
        $certH = 16;
        $signH = 22;
        $agencyH = 14;
        $vitalH = 18;
        $designationH = 18;

        $this->pdf->Rect($this->left, $top, $this->width(), $certH);
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetXY($this->left + 2, $top + 1.6);
        $this->pdf->Cell($this->width() - 4, 4, $this->text('I hereby certify that I have reviewed and evaluated the attached examination results, personally examined the'), 0, 1, 'C');
        $line = 'above named individual and found him/her to be physically and medically ';
        $this->pdf->SetFont('Arial', 'I', 8);
        $lineW = $this->pdf->GetStringWidth($this->text($line));
        $fitW = 18;
        $unfitW = 22;
        $tailW = $this->pdf->GetStringWidth('for employment.');
        $rowW = $lineW + 4 + $fitW + 4 + $unfitW + $tailW;
        $x = $this->left + (($this->width() - $rowW) / 2);
        $markY = $top + 7.2;
        $this->pdf->SetXY($x, $markY - 0.4);
        $this->pdf->Cell($lineW, 4, $this->text($line), 0, 0, 'L');
        $x += $lineW;
        $this->boxMark($x, $markY, ($d['fitness'] ?? '') === 'fit');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($x + 3.6, $markY - 0.5);
        $this->pdf->Cell(8, 4, 'FIT', 0, 0, 'L');
        $this->pdf->SetXY($x + 3.75, $markY - 0.5);
        $this->pdf->Cell(8, 4, 'FIT', 0, 0, 'L');
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetXY($x + 12, $markY - 0.4);
        $this->pdf->Cell(4, 4, '/', 0, 0, 'C');
        $this->boxMark($x + 16.5, $markY, ($d['fitness'] ?? '') === 'unfit');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($x + 20.2, $markY - 0.5);
        $this->pdf->Cell(14, 4, 'UNFIT', 0, 0, 'L');
        $this->pdf->SetXY($x + 20.35, $markY - 0.5);
        $this->pdf->Cell(14, 4, 'UNFIT', 0, 0, 'L');
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetXY($x + 34, $markY - 0.4);
        $this->pdf->Cell($tailW + 2, 4, 'for employment.', 0, 0, 'L');

        $signY = $top + $certH;
        $this->labeled($this->left, $signY, $leftW, $signH, 'SIGNATURE over PRINTED NAME of Licensed Government or Private Physician:', (string) ($d['physician_name'] ?? ''));
        $this->labeled($this->left + $leftW, $signY, $rightW, $signH + $agencyH, 'OTHER INFORMATION ABOUT THE PROPOSED APPOINTEE', (string) ($d['other_information'] ?? ''), true);
        $this->splitLabel($this->left, $signY + $signH, $leftW, $agencyH, 'AGENCY/Affiliation of the Licensed Government or Private Physician:', (string) ($d['physician_agency'] ?? ''));

        $vitalY = $signY + $signH + $agencyH;
        $heightW = 28;
        $weightW = 28;
        $bloodW = 26;
        $licenseW = $this->width() - $heightW - $weightW - $bloodW;
        $this->splitLabel($this->left, $vitalY, $licenseW, $vitalH, 'LICENSE NO.', (string) ($d['license_no'] ?? ''), 7);
        $this->stacked($this->left + $licenseW, $vitalY, $heightW, $vitalH, 'HEIGHT (M)', 'Bare Foot', (string) ($d['height'] ?? ''));
        $this->stacked($this->left + $licenseW + $heightW, $vitalY, $weightW, $vitalH, 'WEIGHT (KG)', 'Stripped', (string) ($d['weight'] ?? ''));
        $this->stacked($this->left + $licenseW + $heightW + $weightW, $vitalY, $bloodW, $vitalH, 'BLOOD', 'TYPE', (string) ($d['blood_type'] ?? ''));

        $date = (string) ($d['date_examined'] ?? '');
        $dateText = $date !== '' ? date('F j, Y', strtotime($date)) : '';
        $this->splitLabel($this->left, $vitalY + $vitalH, $licenseW, $designationH, 'OFFICIAL DESIGNATION', (string) ($d['designation'] ?? ''));
        $this->splitLabel($this->left + $licenseW, $vitalY + $vitalH, $heightW + $weightW + $bloodW, $designationH, 'DATE EXAMINED', $dateText);
    }

    protected function labeled(float $x, float $y, float $w, float $h, string $label, string $value, bool $centerLabel = false): void
    {
        $this->pdf->Rect($x, $y, $w, $h);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($x + 1, $y + 0.8);
        $this->pdf->MultiCell($w - 2, 3, $this->text($label), 0, $centerLabel ? 'C' : 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY($x + 1.4, $y + ($centerLabel ? 8 : 6));
        $this->pdf->MultiCell($w - 2.8, 3.8, $this->text($value), 0, 'L');
    }

    protected function splitLabel(float $x, float $y, float $w, float $h, string $label, string $value, float $ruleAt = 5): void
    {
        $this->pdf->Rect($x, $y, $w, $h);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($x + 1, $y + 0.7);
        $this->pdf->Cell($w - 2, 3.2, $this->text($label), 0, 0, 'L');
        $this->pdf->Line($x, $y + $ruleAt, $x + $w, $y + $ruleAt);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY($x + 1.2, $y + $ruleAt + 1.2);
        $this->pdf->MultiCell($w - 2.4, 3.8, $this->text($value), 0, 'L');
    }

    protected function stacked(float $x, float $y, float $w, float $h, string $top, string $bottom, string $value): void
    {
        $this->pdf->Rect($x, $y, $w, $h);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($x, $y + 0.6);
        $this->pdf->Cell($w, 3, $this->text($top), 0, 0, 'C');
        $this->pdf->SetXY($x, $y + 3.4);
        $this->pdf->Cell($w, 3, $this->text($bottom), 0, 0, 'C');
        $this->pdf->Line($x, $y + 7, $x + $w, $y + 7);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY($x + 0.6, $y + 8.2);
        $this->pdf->Cell($w - 1.2, 4, $this->text($value), 0, 0, 'C');
    }

    protected function boxMark(float $x, float $y, bool $on): void
    {
        $this->pdf->Rect($x, $y, 3.2, 3.2);
        if ($on) {
            $this->pdf->Line($x + 0.4, $y + 0.4, $x + 2.8, $y + 2.8);
            $this->pdf->Line($x + 2.8, $y + 0.4, $x + 0.4, $y + 2.8);
        }
    }

    protected function heavy(float $x, float $y, float $w, float $h, string $text, string $align): void
    {
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($w, $h, $this->text($text), 0, 0, $align);
        $this->pdf->SetXY($x + 0.18, $y);
        $this->pdf->Cell($w, $h, $this->text($text), 0, 0, $align);
    }

    protected function rule(float $y): void
    {
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line($this->left, $y, $this->right, $y);
        $this->pdf->SetLineWidth(0.2);
    }

    protected function width(): float
    {
        return $this->right - $this->left;
    }

    protected function text(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
