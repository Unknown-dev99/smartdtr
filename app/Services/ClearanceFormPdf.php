<?php

namespace App\Services;

use App\Models\ClearanceRequest;
use FPDF;

class ClearanceFormPdf
{
    protected FPDF $pdf;

    protected float $y = 14;

    public function render(ClearanceRequest $clearance): string
    {
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetCompression(false);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetTitle($this->text('Request for Clearance '.$clearance->year));
        $this->pdf->AddPage();
        $this->y = 14;

        $this->front($clearance);
        $this->privacyNotice();

        return $this->pdf->Output('S');
    }

    protected function front(ClearanceRequest $clearance): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 13);
        $this->center('CIVIL SERVICE COMMISSION', 7);
        $this->pdf->SetFont('Helvetica', 'B', 16);
        $this->center('Request for Clearance', 9);

        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(90, 5, '*All fields are required', 0, 0, 'L');
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(28, 5, '*Date:', 0, 0, 'R');
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(36, 5, $clearance->request_date?->format('m/d/Y') ?? '', 'B', 0, 'C');
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(32, 5, ' (mm/dd/yyyy)', 0, 1);
        $this->y += 8;

        $this->label("Requestor's Name*");
        $this->pdf->SetFont('Helvetica', '', 11);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(70, 7, $this->text($clearance->last_name), 'B', 0, 'C');
        $this->pdf->Cell(4, 7, '', 0, 0);
        $this->pdf->Cell(84, 7, $this->text($clearance->first_name), 'B', 0, 'C');
        $this->pdf->Cell(4, 7, '', 0, 0);
        $this->pdf->Cell(24, 7, $this->text((string) $clearance->middle_initial), 'B', 1, 'C');
        $this->y += 7;
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(70, 4, 'Lastname', 0, 0, 'C');
        $this->pdf->Cell(4, 4, '', 0, 0);
        $this->pdf->Cell(84, 4, 'Firstname', 0, 0, 'C');
        $this->pdf->Cell(4, 4, '', 0, 0);
        $this->pdf->Cell(24, 4, 'M.I', 0, 1, 'C');
        $this->y += 6;

        $this->field('Home Address*', $clearance->home_address);
        $this->pair('Contact No.:*', $clearance->contact_no, 'Email Address:*', $clearance->email);
        $this->field('Present Position:*', $clearance->present_position);
        $this->field('Name of Agency:*', $clearance->agency_name);
        $this->field('Address of Agency:*', $clearance->agency_address);

        $this->pdf->SetXY(12, $this->y);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->MultiCell(186, 4.5, $this->text('I respectfully request your good office to issue a clearance in my favour which shall be used for the purpose/s indicated below:'), 0, 'L');
        $this->y = $this->pdf->GetY() + 1;
        $this->pdf->SetFont('Helvetica', 'I', 8);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(186, 4, '(pls. put an "X")', 0, 1);
        $this->y += 6;

        $this->purposeMark(12, 'Promotion:', $clearance->purpose === ClearanceRequest::PURPOSE_PROMOTION);
        $this->purposeMark(62, 'Retirement:', $clearance->purpose === ClearanceRequest::PURPOSE_RETIREMENT);
        $this->pdf->SetXY(112, $this->y);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(38, 5, 'Others (pls. specify):', 0, 0);
        $other = $clearance->purpose === ClearanceRequest::PURPOSE_OTHERS ? (string) $clearance->purpose_other : '';
        $this->pdf->Cell(48, 5, $this->text($other), 'B', 1);
        $this->y += 12;

        $this->pdf->SetFillColor(192, 192, 192);
        $this->pdf->Rect(12, $this->y, 186, 6, 'F');
        $this->pdf->SetXY(14, $this->y);
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(182, 6, 'To be filled up by CSC Personnel', 0, 1);
        $this->y += 9;

        $this->blankLine('Verified by:');
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->MultiCell(186, 4.5, 'Payment for Clearance for No Pending Administrative Case - P100.00', 0, 'L');
        $this->y = $this->pdf->GetY() + 3;

        $this->pdf->SetXY(12, $this->y);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(38, 6, 'Cashier / O.R. No.:', 0, 0);
        $this->pdf->Cell(50, 6, '', 'B', 0);
        $this->pdf->Cell(16, 6, 'Date:', 0, 0, 'R');
        $this->pdf->Cell(40, 6, '', 'B', 1);
        $this->y += 10;
        $this->blankLine('Issued By:');

        $this->pdf->SetFont('Helvetica', 'I', 8);
        $this->pdf->SetXY(12, $this->y + 2);
        $this->pdf->Cell(186, 5, 'Note: see the Data Privacy Notice at the back', 0, 1);
    }

    protected function privacyNotice(): void
    {
        $this->pdf->AddPage();
        $this->pdf->SetXY(14, 16);
        $this->pdf->SetFont('Helvetica', 'B', 13);
        $this->pdf->Cell(182, 7, 'DATA PRIVACY NOTICE', 0, 1, 'C');
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Helvetica', '', 10);
        $paragraphs = [
            'In compliance with Republic Act No. 10173 otherwise known as Data Privacy Act of 2012, the CSC provides this privacy notice to inform you about the collection and processing of your personal information through this form.',
            'Purpose of Data Collection',
            'The personal information collected will be used for documentation purposes. We ensure that your data is processed fairly and lawfully.',
            'Data Retention',
            'Your personal data will be retained only for as long as necessary to fulfill the purposes for which it was collected or as required by law.',
            'Your Rights',
            'You have the right to:',
            '- Be informed about how your data is being used.',
            '- Access your personal information.',
            '- Rectify any inaccuracies in your data.',
            '- Request the deletion of your personal information under certain conditions.',
            'Data Security',
            'We implement appropriate security measures to protect your personal data from unauthorized access, disclosure, or misuse.',
            'Contact Information',
            'For inquiries regarding this privacy notice or to exercise your rights, please contact our Data Protection Officer at dpo@csc.gov.ph.',
            'By providing your personal information, you consent to its collection and processing as described in this notice.',
        ];
        $headings = [
            'Purpose of Data Collection',
            'Data Retention',
            'Your Rights',
            'Data Security',
            'Contact Information',
        ];

        foreach ($paragraphs as $paragraph) {
            $this->pdf->SetX(14);
            if (in_array($paragraph, $headings, true)) {
                $this->pdf->Ln(2);
                $this->pdf->SetFont('Helvetica', 'B', 10);
            } else {
                $this->pdf->SetFont('Helvetica', '', 10);
            }
            $this->pdf->MultiCell(182, 5, $this->text($paragraph), 0, 'L');
        }
    }

    protected function center(string $text, float $height): void
    {
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(186, $height, $this->text($text), 0, 1, 'C');
        $this->y += $height;
    }

    protected function label(string $text): void
    {
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(186, 5, $this->text($text), 0, 1);
        $this->y += 5;
    }

    protected function field(string $label, string $value): void
    {
        $this->label($label);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->SetFont('Helvetica', '', 11);
        $this->pdf->Cell(186, 7, $this->text($value), 'B', 1);
        $this->y += 9;
    }

    protected function pair(string $leftLabel, string $leftValue, string $rightLabel, string $rightValue): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(90, 5, $this->text($leftLabel), 0, 0);
        $this->pdf->Cell(96, 5, $this->text($rightLabel), 0, 1);
        $this->y += 5;
        $this->pdf->SetFont('Helvetica', '', 11);
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->Cell(88, 7, $this->text($leftValue), 'B', 0);
        $this->pdf->Cell(4, 7, '', 0, 0);
        $this->pdf->Cell(94, 7, $this->text($rightValue), 'B', 1);
        $this->y += 9;
    }

    protected function purposeMark(float $x, string $label, bool $checked): void
    {
        $this->pdf->Rect($x, $this->y, 4.5, 4.5);
        if ($checked) {
            $this->pdf->SetXY($x, $this->y);
            $this->pdf->SetFont('Helvetica', 'B', 9);
            $this->pdf->Cell(4.5, 4.5, 'X', 0, 0, 'C');
        }
        $this->pdf->SetXY($x + 6, $this->y);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(40, 4.5, $label, 0, 0);
    }

    protected function blankLine(string $label): void
    {
        $this->pdf->SetXY(12, $this->y);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(28, 6, $label, 0, 0);
        $this->pdf->Cell(90, 6, '', 'B', 1);
        $this->y += 10;
    }

    protected function text(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', '', $value) ?? '') : $converted;
    }
}
