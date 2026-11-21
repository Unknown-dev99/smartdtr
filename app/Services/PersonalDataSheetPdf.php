<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use FPDF;

class PersonalDataSheetPdf
{
    protected FPDF $pdf;

    protected float $left = 8;

    protected float $right = 202;

    protected float $y = 8;

    public function render(PersonalDataSheet $sheet): string
    {
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetCompression(false);
        $this->pdf->SetAutoPageBreak(false);
        $fonts = resource_path('fonts');
        $this->pdf->AddFont('Arial', '', 'arial.json', $fonts);
        $this->pdf->AddFont('Arial', 'I', 'ariali.json', $fonts);
        $this->pdf->SetTitle($this->text('Personal Data Sheet'));

        $d = $sheet->details ?? [];
        $this->page1($sheet, $d);
        $this->page2($sheet, $d);
        $this->page3($sheet, $d);
        $this->page4($sheet, $d);
        $this->continuations($sheet, $d);

        return $this->pdf->Output('S');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function page1(PersonalDataSheet $sheet, array $d): void
    {
        $this->pdf->AddPage('P', 'A4');
        $this->right = 202;
        $this->y = 8;
        $this->pdf->SetFont('Arial', '', 9);
        $this->heavy($this->left, $this->y, 70, 4, 'CS Form No. 212', 'L');
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetXY($this->left, $this->y + 4);
        $this->pdf->Cell(70, 4, 'Revised 2026', 0, 0, 'L');
        $this->heavy($this->left, $this->y, $this->width(), 5, 'PERSONAL DATA SHEET', 'C');
        $this->y += 10;
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3, $this->text('WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.'), 1, 'C');
        $this->y = $this->pdf->GetY();
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3.2, $this->text('READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.'), 0, 'C');
        $this->y = $this->pdf->GetY();
        $this->pdf->SetX($this->left);
        $this->pdf->MultiCell($this->width(), 3.2, $this->text('Tick appropriate boxes and use separate sheet if necessary. Indicate N/A if not applicable. DO NOT ABBREVIATE.'), 0, 'L');
        $this->y = $this->pdf->GetY() + 1;

        $this->band('I. PERSONAL INFORMATION');
        $top = $this->y;
        $this->lineBox($this->left, $top, $this->width(), 5, '1. SURNAME', (string) ($d['surname'] ?? ''));
        $extW = 46;
        $this->lineBox($this->left, $top + 5, $this->width() - $extW, 5, '2. FIRST NAME', (string) ($d['first_name'] ?? ''));
        $this->lineBox($this->left + $this->width() - $extW, $top + 5, $extW, 5, 'NAME EXTENSION (JR., SR)', (string) ($d['name_extension'] ?? ''));
        $this->lineBox($this->left, $top + 10, $this->width(), 5, 'MIDDLE NAME', (string) ($d['middle_name'] ?? ''));
        $split = $top + 15;
        $leftW = 100;
        $this->identity($d, $split, $leftW);
        $this->citizenshipAndAddress($d, $split, $this->left + $leftW, $this->width() - $leftW);
        $this->y = max($this->y, $split + 72);

        $this->band('II. FAMILY BACKGROUND');
        $this->family($d);
        $this->band('III. EDUCATIONAL BACKGROUND');
        $this->educationTable(array_slice($d['education'] ?? [], 0, 5));
        $this->signLine($sheet, 'CS FORM 212 (Revised 2026), Page 1 of 4');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function page2(PersonalDataSheet $sheet, array $d): void
    {
        $this->pdf->AddPage('P', 'A4');
        $this->right = 202;
        $this->y = 8;
        $this->band('IV. CIVIL SERVICE ELIGIBILITY');
        $this->pdf->SetFont('Arial', '', 6);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3, $this->text('27. CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE / BARANGAY ELIGIBILITY'), 0, 'L');
        $this->y = $this->pdf->GetY();
        $rows = array_slice($d['eligibility'] ?? [], 0, 7);
        $this->grid(
            ['Career Service / RA 1080 / CES / CSEE', 'Rating', 'Date of Examination', 'Place of Examination', 'License No.', 'Valid Until'],
            [58, 18, 28, 42, 24, 24],
            $this->pad($rows, 7),
            fn ($row) => [
                (string) ($row['career'] ?? ''),
                (string) ($row['rating'] ?? ''),
                $this->dmy((string) ($row['exam_date'] ?? '')),
                (string) ($row['place'] ?? ''),
                (string) ($row['license_no'] ?? ''),
                $this->dmy((string) ($row['license_validity'] ?? '')),
            ]
        );
        $this->note('(Continue on a separate sheet if necessary)');

        $this->band('V. WORK EXPERIENCE');
        $this->pdf->SetFont('Arial', 'I', 6.5);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3, $this->text('(Include private employment. Start from your recent work) Description of duties should be indicated in the attached Work Experience Sheet.'), 0, 'L');
        $this->y = $this->pdf->GetY();
        $work = array_slice($d['work'] ?? [], 0, 12);
        $this->grid(
            ['From', 'To', 'Position Title', 'Department / Agency / Office / Company', 'Monthly Salary', 'Salary Grade', 'Status of Appointment', "Gov't Service"],
            [20, 20, 32, 40, 22, 18, 28, 14],
            $this->pad($work, 12),
            fn ($row) => [
                $this->dmy((string) ($row['from'] ?? '')),
                $this->present((string) ($row['to'] ?? '')),
                (string) ($row['position'] ?? ''),
                (string) ($row['office'] ?? ''),
                (string) ($row['salary'] ?? ''),
                (string) ($row['grade'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['government'] ?? ''),
            ],
            5.2
        );
        $this->note('(Continue on a separate sheet if necessary)');
        $this->signLine($sheet, 'CS FORM 212 (Revised 2026), Page 2 of 4');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function page3(PersonalDataSheet $sheet, array $d): void
    {
        $this->pdf->AddPage('P', [215.9, 355.6]);
        $this->right = 206;
        $this->y = 8;
        $this->band('VI. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED');
        $training = array_slice($d['training'] ?? [], 0, 12);
        $this->grid(
            ['Title of L&D / Training Program', 'From', 'To', 'Hours', 'Type of L&D', 'Conducted / Sponsored By'],
            [70, 22, 22, 16, 28, 40],
            $this->pad($training, 12),
            fn ($row) => [
                (string) ($row['title'] ?? ''),
                $this->dmy((string) ($row['from'] ?? '')),
                $this->dmy((string) ($row['to'] ?? '')),
                (string) ($row['hours'] ?? ''),
                (string) ($row['type'] ?? ''),
                (string) ($row['sponsor'] ?? ''),
            ]
        );
        $this->note('(Continue on a separate sheet if necessary)');

        $this->band('VII. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATIONS');
        $voluntary = array_slice($d['voluntary'] ?? [], 0, 6);
        $this->grid(
            ['Name & Address of Organization', 'From', 'To', 'Number of Hours', 'Position / Nature of Work'],
            [78, 22, 22, 28, 48],
            $this->pad($voluntary, 6),
            fn ($row) => [
                (string) ($row['organization'] ?? ''),
                $this->dmy((string) ($row['from'] ?? '')),
                $this->dmy((string) ($row['to'] ?? '')),
                (string) ($row['hours'] ?? ''),
                (string) ($row['position'] ?? ''),
            ]
        );
        $this->note('(Continue on a separate sheet if necessary)');

        $this->band('VIII. OTHER INFORMATION');
        $skills = array_pad(array_values($d['skills'] ?? []), 6, '');
        $recognitions = array_pad(array_values($d['recognitions'] ?? []), 6, '');
        $memberships = array_pad(array_values($d['memberships'] ?? []), 6, '');
        $other = [];
        for ($i = 0; $i < 6; $i++) {
            $other[] = [$skills[$i] ?? '', $recognitions[$i] ?? '', $memberships[$i] ?? ''];
        }
        $this->grid(
            ['31. Special Skills and Hobbies', '32. Non-Academic Distinctions / Recognition', '33. Membership in Association/Organization'],
            [66, 66, 66],
            $other,
            fn ($row) => $row
        );
        $this->note('(Continue on a separate sheet if necessary)');
        $this->signLine($sheet, 'CS FORM 212 (Revised 2026), Page 3 of 4');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function page4(PersonalDataSheet $sheet, array $d): void
    {
        $this->pdf->AddPage('P', [215.9, 355.6]);
        $this->right = 206;
        $this->y = 8;
        $this->question('34.', 'Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed,', null, null);
        $this->question('a.', 'within the third degree?', (string) ($d['q34a'] ?? ''), (string) ($d['q34a_details'] ?? ''));
        $this->question('b.', 'within the fourth degree (for Local Government Unit - Career Employees)?', (string) ($d['q34b'] ?? ''), (string) ($d['q34b_details'] ?? ''));
        $this->question('35.', 'a. Have you ever been found guilty of any administrative offense?', (string) ($d['q35a'] ?? ''), (string) ($d['q35a_details'] ?? ''));
        $extra = trim('Date Filed: '.$this->dmy((string) ($d['q35b_date'] ?? '')).'    Status of Case/s: '.(string) ($d['q35b_status'] ?? ''));
        $this->question('b.', 'Have you been criminally charged before any court?', (string) ($d['q35b'] ?? ''), trim((string) ($d['q35b_details'] ?? '')."\n".$extra));
        $this->question('36.', 'Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?', (string) ($d['q36'] ?? ''), (string) ($d['q36_details'] ?? ''));
        $this->question('37.', 'Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of contract, finished contract, AWOL, or phased out, in the public or private sector?', (string) ($d['q37'] ?? ''), (string) ($d['q37_details'] ?? ''));
        $this->question('38.', 'a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?', (string) ($d['q38a'] ?? ''), (string) ($d['q38a_details'] ?? ''));
        $this->question('b.', 'Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a candidate?', (string) ($d['q38b'] ?? ''), (string) ($d['q38b_details'] ?? ''));
        $this->question('39.', 'Have you acquired the status of an immigrant or permanent resident of another country?', (string) ($d['q39'] ?? ''), (string) ($d['q39_details'] ?? ''));
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3.2, $this->text('40. Pursuant to: (a) Indigenous Peoples\' Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861), please answer the following items:'), 0, 'L');
        $this->y = $this->pdf->GetY() + 1;
        $this->question('a.', 'Are you a member of any indigenous group?', (string) ($d['q40a'] ?? ''), (string) ($d['q40a_details'] ?? ''));
        $this->question('b.', 'Are you a person with disability?', (string) ($d['q40b'] ?? ''), (string) ($d['q40b_id'] ?? '') !== '' ? 'ID No: '.$d['q40b_id'] : '');
        $this->question('c.', 'Are you a solo parent?', (string) ($d['q40c'] ?? ''), (string) ($d['q40c_id'] ?? '') !== '' ? 'ID No: '.$d['q40c_id'] : '');

        $this->band('41. REFERENCES (Person not related by consanguinity or affinity to applicant/appointee)');
        $refs = array_pad(array_slice($d['references'] ?? [], 0, 3), 3, ['name' => '', 'address' => '', 'contact' => '']);
        $this->grid(
            ['Name', 'Office / Residential Address', 'Contact No. and/or Email'],
            [60, 80, 58],
            $refs,
            fn ($row) => [(string) ($row['name'] ?? ''), (string) ($row['address'] ?? ''), (string) ($row['contact'] ?? '')],
            6
        );

        $this->y += 2;
        $boxTop = $this->y;
        $photoW = 36;
        $textW = $this->width() - $photoW;
        $this->pdf->Rect($this->left, $boxTop, $textW, 28);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($this->left + 1.5, $boxTop + 1.5);
        $this->pdf->MultiCell($textW - 3, 3, $this->text('42. I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.'), 0, 'L');
        $this->pdf->Rect($this->left + $textW, $boxTop, $photoW, 28);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($this->left + $textW, $boxTop + 11);
        $this->pdf->Cell($photoW, 4, 'PHOTO', 0, 0, 'C');

        $this->y = $boxTop + 30;
        $idW = 110;
        $this->lineBox($this->left, $this->y, $idW, 8, 'Government Issued ID:', (string) ($d['gov_id'] ?? ''));
        $this->lineBox($this->left + $idW, $this->y, $this->width() - $idW, 16, 'Signature (Sign inside the box)', '');
        $this->lineBox($this->left, $this->y + 8, $idW, 8, 'ID/License/Passport No.:', (string) ($d['gov_id_no'] ?? ''));
        $this->lineBox($this->left, $this->y + 16, $idW, 8, 'Date/Place of Issuance:', (string) ($d['gov_id_issuance'] ?? ''));
        $this->lineBox($this->left + $idW, $this->y + 16, $this->width() - $idW, 8, 'Date Accomplished', $this->dmy($sheet->date_accomplished?->toDateString()));
        $this->pdf->Rect($this->left + $idW, $this->y + 24, $this->width() - $idW, 16);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($this->left + $idW, $this->y + 30);
        $this->pdf->Cell($this->width() - $idW, 4, 'Right Thumbmark', 0, 0, 'C');

        $this->y += 42;
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->MultiCell($this->width(), 3.2, $this->text('SUBSCRIBED AND SWORN to before me this ________________________, affiant exhibiting his/her validly issued government ID as indicated above.'), 0, 'L');
        $this->y = $this->pdf->GetY() + 8;
        $this->pdf->SetXY($this->left + 50, $this->y);
        $this->pdf->Cell(90, 4, '', 'T', 1, 'C');
        $this->pdf->SetX($this->left + 50);
        $this->pdf->Cell(90, 4, 'Person Administering Oath', 0, 1, 'C');
        $this->y = 346;
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->Cell($this->width(), 4, 'CS FORM 212 (Revised 2026), Page 4 of 4', 0, 0, 'R');
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function continuations(PersonalDataSheet $sheet, array $d): void
    {
        $children = array_slice($d['children'] ?? [], 7);
        if ($this->any($children)) {
            $this->continuation($sheet, 'FAMILY BACKGROUND (Continuation)', ['Name of Children', 'Date of Birth'], [140, 50], $children, fn ($row) => [
                (string) ($row['name'] ?? ''),
                $this->dmy((string) ($row['birth_date'] ?? '')),
            ]);
        }
        $education = array_slice($d['education'] ?? [], 5);
        if ($this->any($education)) {
            $this->continuation($sheet, 'EDUCATIONAL BACKGROUND (Continuation)', ['Level', 'School', 'Course', 'From', 'To', 'Year Graduated'], [32, 42, 42, 18, 18, 28], $education, fn ($row) => [
                (string) ($row['level'] ?? ''),
                (string) ($row['school'] ?? ''),
                (string) ($row['course'] ?? ''),
                (string) ($row['from'] ?? ''),
                (string) ($row['to'] ?? ''),
                (string) ($row['year_graduated'] ?? ''),
            ]);
        }
        $eligibility = array_slice($d['eligibility'] ?? [], 7);
        if ($this->any($eligibility)) {
            $this->continuation($sheet, 'CIVIL SERVICE ELIGIBILITY (Continuation)', ['Career Service', 'Rating', 'Date', 'Place', 'License No.'], [70, 22, 28, 40, 30], $eligibility, fn ($row) => [
                (string) ($row['career'] ?? ''),
                (string) ($row['rating'] ?? ''),
                $this->dmy((string) ($row['exam_date'] ?? '')),
                (string) ($row['place'] ?? ''),
                (string) ($row['license_no'] ?? ''),
            ]);
        }
        $work = array_slice($d['work'] ?? [], 12);
        if ($this->any($work)) {
            $this->continuation($sheet, 'WORK EXPERIENCE (Continuation)', ['From', 'To', 'Position Title', 'Office / Company', 'Status', "Gov't"], [24, 24, 46, 50, 28, 18], $work, fn ($row) => [
                $this->dmy((string) ($row['from'] ?? '')),
                $this->present((string) ($row['to'] ?? '')),
                (string) ($row['position'] ?? ''),
                (string) ($row['office'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['government'] ?? ''),
            ]);
        }
        $training = array_slice($d['training'] ?? [], 12);
        if ($this->any($training)) {
            $this->continuation($sheet, 'LEARNING AND DEVELOPMENT (Continuation)', ['Title', 'From', 'To', 'Hours', 'Conducted By'], [70, 24, 24, 20, 52], $training, fn ($row) => [
                (string) ($row['title'] ?? ''),
                $this->dmy((string) ($row['from'] ?? '')),
                $this->dmy((string) ($row['to'] ?? '')),
                (string) ($row['hours'] ?? ''),
                (string) ($row['sponsor'] ?? ''),
            ]);
        }
        $voluntary = array_slice($d['voluntary'] ?? [], 6);
        if ($this->any($voluntary)) {
            $this->continuation($sheet, 'VOLUNTARY WORK (Continuation)', ['Organization', 'From', 'To', 'Hours', 'Position'], [70, 24, 24, 22, 50], $voluntary, fn ($row) => [
                (string) ($row['organization'] ?? ''),
                $this->dmy((string) ($row['from'] ?? '')),
                $this->dmy((string) ($row['to'] ?? '')),
                (string) ($row['hours'] ?? ''),
                (string) ($row['position'] ?? ''),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function identity(array $d, float $top, float $width): void
    {
        $rows = [
            ['3. DATE OF BIRTH (dd/mm/yyyy)', $this->dmy((string) ($d['birth_date'] ?? ''))],
            ['4. PLACE OF BIRTH', (string) ($d['place_of_birth'] ?? '')],
        ];
        $y = $top;
        foreach ($rows as [$label, $value]) {
            $this->lineBox($this->left, $y, $width, 5, $label, $value);
            $y += 5;
        }
        $this->choiceRow($this->left, $y, $width, '5. SEX AT BIRTH', [
            'male' => 'Male',
            'female' => 'Female',
        ], (string) ($d['sex'] ?? ''));
        $y += 6;
        $this->pdf->Rect($this->left, $y, $width, 11);
        $this->pdf->SetFont('Arial', '', 5.5);
        $this->pdf->SetXY($this->left + 0.6, $y + 0.6);
        $this->pdf->Cell(24, 3, '6. CIVIL STATUS', 0, 0);
        $cursor = $this->left + 26;
        foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed'] as $value => $caption) {
            $this->mark($cursor, $y + 0.8, ($d['civil_status'] ?? '') === $value);
            $this->pdf->SetFont('Arial', '', 6);
            $this->pdf->SetXY($cursor + 3.3, $y + 0.5);
            $this->pdf->Cell(18, 3.2, $caption, 0, 0);
            $cursor += 22;
        }
        $cursor = $this->left + 26;
        foreach (['separated' => 'Separated', 'others' => 'Other/s'] as $value => $caption) {
            $this->mark($cursor, $y + 5.6, ($d['civil_status'] ?? '') === $value);
            $this->pdf->SetFont('Arial', '', 6);
            $this->pdf->SetXY($cursor + 3.3, $y + 5.3);
            $this->pdf->Cell(18, 3.2, $caption, 0, 0);
            $cursor += 24;
        }
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($cursor, $y + 5.2);
        $this->pdf->Cell($this->left + $width - $cursor - 1, 3.4, $this->text((string) ($d['civil_status_other'] ?? '')), 'B', 0);
        $y += 11;
        foreach ([
            ['7. HEIGHT (m)', (string) ($d['height'] ?? '')],
            ['8. WEIGHT (kg)', (string) ($d['weight'] ?? '')],
            ['9. BLOOD TYPE', (string) ($d['blood_type'] ?? '')],
            ['10. UMID ID NO.', (string) ($d['umid'] ?? '')],
            ['11. PAG-IBIG ID NO.', (string) ($d['pagibig'] ?? '')],
            ['12. PHILHEALTH NO.', (string) ($d['philhealth'] ?? '')],
            ['13. PhilSys Card Number (PCN)', (string) ($d['philsys'] ?? '')],
            ['14. TIN NO.', (string) ($d['tin'] ?? '')],
            ['15. AGENCY EMPLOYEE NO.', (string) ($d['agency_employee_no'] ?? '')],
        ] as [$label, $value]) {
            $this->lineBox($this->left, $y, $width, 5.4, $label, $value);
            $y += 5.4;
        }
        $this->y = $y;
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function citizenshipAndAddress(array $d, float $top, float $x, float $width): void
    {
        $y = $top;
        $this->pdf->Rect($x, $y, $width, 16);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($x + 1, $y + 0.6);
        $this->pdf->Cell($width - 2, 3, '16. CITIZENSHIP', 0, 1);
        $this->mark($x + 2, $y + 5, ($d['citizenship'] ?? '') === 'filipino');
        $this->pdf->SetXY($x + 6, $y + 4.6);
        $this->pdf->Cell(22, 3.5, 'Filipino', 0, 0);
        $this->mark($x + 28, $y + 5, ($d['citizenship'] ?? '') === 'dual');
        $this->pdf->SetXY($x + 32, $y + 4.6);
        $this->pdf->Cell(36, 3.5, 'Dual Citizenship', 0, 0);
        $this->mark($x + 2, $y + 10, ($d['dual_by'] ?? '') === 'birth');
        $this->pdf->SetXY($x + 6, $y + 9.6);
        $this->pdf->Cell(18, 3.5, 'by birth', 0, 0);
        $this->mark($x + 26, $y + 10, ($d['dual_by'] ?? '') === 'naturalization');
        $this->pdf->SetXY($x + 30, $y + 9.6);
        $this->pdf->Cell(28, 3.5, 'by naturalization', 0, 0);
        $this->pdf->SetXY($x + 58, $y + 9.6);
        $this->pdf->Cell($width - 60, 3.5, $this->text((string) ($d['citizenship_country'] ?? '')), 'B', 0);
        $y += 16;
        $y = $this->addressBlock($x, $y, $width, '17. RESIDENTIAL ADDRESS', $d['residential'] ?? []);
        $y = $this->addressBlock($x, $y, $width, '18. PERMANENT ADDRESS', $d['permanent'] ?? []);
        $this->lineBox($x, $y, $width, 5.2, '19. TELEPHONE NO.', (string) ($d['telephone'] ?? ''));
        $this->lineBox($x, $y + 5.2, $width, 5.2, '20. MOBILE NO.', (string) ($d['mobile'] ?? ''));
        $this->lineBox($x, $y + 10.4, $width, 5.2, '21. E-MAIL ADDRESS (if any)', (string) ($d['email'] ?? ''));
        $this->y = max($this->y, $y + 15.6);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    protected function addressBlock(float $x, float $y, float $width, string $title, array $address): float
    {
        $height = 22;
        $this->pdf->Rect($x, $y, $width, $height);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->SetXY($x + 1, $y + 0.4);
        $this->pdf->Cell($width - 2, 3.2, $this->text($title), 0, 1);
        $half = $width / 2;
        $lines = [
            ['House/Block/Lot No.', (string) ($address['house'] ?? ''), 'Street', (string) ($address['street'] ?? '')],
            ['Subdivision/Village', (string) ($address['subdivision'] ?? ''), 'Barangay', (string) ($address['barangay'] ?? '')],
            ['City/Municipality', (string) ($address['city'] ?? ''), 'Province', (string) ($address['province'] ?? '')],
        ];
        $lineY = $y + 3.8;
        foreach ($lines as [$leftLabel, $leftValue, $rightLabel, $rightValue]) {
            $this->addressPair($x + 0.8, $lineY, $half - 1.2, $leftLabel, $leftValue);
            $this->addressPair($x + $half, $lineY, $half - 1.2, $rightLabel, $rightValue);
            $lineY += 4.5;
        }
        $this->pdf->SetFont('Arial', '', 6);
        $this->pdf->SetXY($x + 1, $y + $height - 4.2);
        $this->pdf->Cell(16, 3.4, 'ZIP CODE', 0, 0);
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->Cell(28, 3.4, $this->text((string) ($address['zip'] ?? '')), 'B', 0);

        return $y + $height;
    }

    protected function addressPair(float $x, float $y, float $w, string $label, string $value): void
    {
        $this->pdf->SetFont('Arial', '', 5.5);
        $labelW = min($w * 0.62, $this->pdf->GetStringWidth($this->text($label)) + 1.2);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($labelW, 4, $this->text($label), 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($w - $labelW, 4, $this->text($value), 'B', 0);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    protected function family(array $d): void
    {
        $spouse = $d['spouse'] ?? [];
        $father = $d['father'] ?? [];
        $mother = $d['mother'] ?? [];
        $children = array_pad(array_slice($d['children'] ?? [], 0, 7), 7, ['name' => '', 'birth_date' => '']);
        $top = $this->y;
        $leftW = 100;
        $lines = [
            ['22. SPOUSE\'S SURNAME', (string) ($spouse['surname'] ?? '')],
            ['FIRST NAME / EXT. '.($spouse['extension'] ?? ''), (string) ($spouse['first_name'] ?? '')],
            ['MIDDLE NAME', (string) ($spouse['middle_name'] ?? '')],
            ['OCCUPATION', (string) ($spouse['occupation'] ?? '')],
            ['EMPLOYER/BUSINESS NAME', (string) ($spouse['employer'] ?? '')],
            ['BUSINESS ADDRESS', (string) ($spouse['business_address'] ?? '')],
            ['TELEPHONE NO.', (string) ($spouse['telephone'] ?? '')],
            ['24. FATHER\'S SURNAME', (string) ($father['surname'] ?? '')],
            ['FIRST NAME / EXT. '.($father['extension'] ?? ''), (string) ($father['first_name'] ?? '')],
            ['MIDDLE NAME', (string) ($father['middle_name'] ?? '')],
            ['25. MOTHER\'S MAIDEN NAME', ''],
            ['SURNAME', (string) ($mother['surname'] ?? '')],
            ['FIRST NAME', (string) ($mother['first_name'] ?? '')],
            ['MIDDLE NAME', (string) ($mother['middle_name'] ?? '')],
        ];
        $y = $top;
        foreach ($lines as [$label, $value]) {
            $this->lineBox($this->left, $y, $leftW, 4.6, $label, $value);
            $y += 4.6;
        }
        $this->pdf->Rect($this->left + $leftW, $top, $this->width() - $leftW, 6);
        $this->pdf->SetFont('Arial', '', 6);
        $this->pdf->SetXY($this->left + $leftW + 1, $top + 0.4);
        $this->pdf->Cell(($this->width() - $leftW) * 0.7, 5, '23. NAME OF CHILDREN (Write full name and list all)', 0, 0);
        $this->pdf->Cell(($this->width() - $leftW) * 0.3, 5, 'DATE OF BIRTH', 0, 0, 'C');
        $rowY = $top + 6;
        $rowH = ($y - $top - 6) / 7;
        foreach ($children as $child) {
            $this->pdf->Rect($this->left + $leftW, $rowY, $this->width() - $leftW, $rowH);
            $this->pdf->Line($this->left + $leftW + (($this->width() - $leftW) * 0.7), $rowY, $this->left + $leftW + (($this->width() - $leftW) * 0.7), $rowY + $rowH);
            $this->pdf->SetFont('Arial', '', 7);
            $this->pdf->SetXY($this->left + $leftW + 1, $rowY + 0.6);
            $this->pdf->Cell(($this->width() - $leftW) * 0.7 - 2, $rowH - 1, $this->text((string) ($child['name'] ?? '')), 0, 0);
            $this->pdf->Cell(($this->width() - $leftW) * 0.3 - 1, $rowH - 1, $this->text($this->dmy((string) ($child['birth_date'] ?? ''))), 0, 0, 'C');
            $rowY += $rowH;
        }
        $this->y = $y;
        $this->note('(Continue on a separate sheet if necessary)');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function educationTable(array $rows): void
    {
        $this->grid(
            ['26. Level', 'Name of School', 'Basic Education/Degree/Course', 'From', 'To', 'Units Earned', 'Year Graduated', 'Scholarship / Honors'],
            [28, 36, 40, 14, 14, 18, 18, 26],
            $this->pad($rows, 5),
            fn ($row) => [
                (string) ($row['level'] ?? ''),
                (string) ($row['school'] ?? ''),
                (string) ($row['course'] ?? ''),
                (string) ($row['from'] ?? ''),
                (string) ($row['to'] ?? ''),
                (string) ($row['units'] ?? ''),
                (string) ($row['year_graduated'] ?? ''),
                (string) ($row['honors'] ?? ''),
            ],
            6
        );
        $this->note('(Continue on a separate sheet if necessary)');
    }

    protected function question(string $number, string $prompt, ?string $answer, ?string $details): void
    {
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($this->left, $this->y);
        $w = $answer === null ? $this->width() : $this->width() - 36;
        $this->pdf->MultiCell($w, 3.2, $this->text(trim($number.' '.$prompt)), 0, 'L');
        $bottom = $this->pdf->GetY();
        if ($answer !== null) {
            $this->mark($this->right - 34, $this->y + 0.4, $answer === 'yes');
            $this->pdf->SetXY($this->right - 30, $this->y);
            $this->pdf->Cell(10, 4, 'YES', 0, 0);
            $this->mark($this->right - 18, $this->y + 0.4, $answer === 'no');
            $this->pdf->SetXY($this->right - 14, $this->y);
            $this->pdf->Cell(10, 4, 'NO', 0, 0);
        }
        $this->y = $bottom;
        if ($details !== null && trim($details) !== '') {
            $this->pdf->SetFont('Arial', 'I', 7);
            $this->pdf->SetXY($this->left + 8, $this->y);
            $this->pdf->MultiCell($this->width() - 10, 3.2, $this->text('If YES, give details: '.$details), 0, 'L');
            $this->y = $this->pdf->GetY();
        }
        $this->y += 1;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, float>  $widths
     * @param  array<int, mixed>  $rows
     * @param  callable(mixed): array<int, string>  $map
     */
    protected function grid(array $headers, array $widths, array $rows, callable $map, float $rowH = 5.5): void
    {
        $x = $this->left;
        $this->pdf->SetFont('Arial', '', 5.5);
        $headerH = 7;
        foreach ($headers as $i => $header) {
            $this->pdf->Rect($x, $this->y, $widths[$i], $headerH);
            $this->pdf->SetXY($x + 0.4, $this->y + 0.4);
            $this->pdf->MultiCell($widths[$i] - 0.8, 2.4, $this->text($header), 0, 'C');
            $x += $widths[$i];
        }
        $this->y += $headerH;
        $this->pdf->SetFont('Arial', '', 6.5);
        foreach ($rows as $row) {
            $values = $map($row);
            $x = $this->left;
            foreach ($widths as $i => $width) {
                $this->pdf->Rect($x, $this->y, $width, $rowH);
                $this->pdf->SetXY($x + 0.6, $this->y + 0.6);
                $this->pdf->Cell($width - 1.2, $rowH - 1.2, $this->text((string) ($values[$i] ?? '')), 0, 0, 'L');
                $x += $width;
            }
            $this->y += $rowH;
        }
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, float>  $widths
     * @param  array<int, mixed>  $rows
     * @param  callable(mixed): array<int, string>  $map
     */
    protected function continuation(PersonalDataSheet $sheet, string $title, array $headers, array $widths, array $rows, callable $map): void
    {
        $this->pdf->AddPage('P', 'A4');
        $this->right = 202;
        $this->y = 10;
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->Cell($this->width(), 5, $this->text($title), 0, 1, 'C');
        $this->y = 18;
        $this->grid($headers, $widths, $rows, $map);
        $this->signLine($sheet, 'CS FORM 212 (Revised 2026)');
    }

    protected function signLine(PersonalDataSheet $sheet, string $footer): void
    {
        $pageBottom = $this->pdf->GetPageHeight() - 12;
        $signY = $pageBottom - 6;
        $this->y = min(max($this->y + 2, $signY), $pageBottom);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->Cell(24, 4, 'SIGNATURE', 0, 0);
        $this->pdf->Cell(70, 4, '', 'B', 0);
        $this->pdf->Cell(16, 4, 'DATE', 0, 0, 'R');
        $this->pdf->Cell(36, 4, $this->text($this->dmy($sheet->date_accomplished?->toDateString())), 'B', 1, 'C');
        $this->pdf->SetFont('Arial', 'I', 7.5);
        $this->pdf->SetXY($this->left, $this->pdf->GetPageHeight() - 10);
        $this->pdf->Cell($this->width(), 4, $this->text($footer), 0, 0, 'R');
    }

    protected function band(string $label): void
    {
        $this->pdf->SetFillColor(226, 226, 226);
        $this->pdf->Rect($this->left, $this->y, $this->width(), 5, 'FD');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($this->left + 1, $this->y + 0.6);
        $this->pdf->Cell($this->width() - 2, 3.6, $this->text($label), 0, 0, 'L');
        $this->y += 5;
    }

    protected function lineBox(float $x, float $y, float $w, float $h, string $label, string $value): void
    {
        $this->pdf->Rect($x, $y, $w, $h);
        $this->pdf->SetFont('Arial', '', 5.5);
        $labelW = min($w * 0.46, $this->pdf->GetStringWidth($this->text($label)) + 2);
        $this->pdf->SetXY($x + 0.6, $y + 0.5);
        $this->pdf->Cell($labelW, $h - 1, $this->text($label), 0, 0);
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->Cell($w - $labelW - 1.4, $h - 1, $this->text($value), 0, 0);
    }

    /**
     * @param  array<string, string>  $options
     */
    protected function choiceRow(float $x, float $y, float $w, string $label, array $options, string $selected): void
    {
        $this->pdf->Rect($x, $y, $w, 6);
        $this->pdf->SetFont('Arial', '', 5.5);
        $this->pdf->SetXY($x + 0.6, $y + 1.2);
        $this->pdf->Cell(24, 3.5, $this->text($label), 0, 0);
        $cursor = $x + 26;
        foreach ($options as $value => $caption) {
            $this->mark($cursor, $y + 1.4, $selected === $value);
            $this->pdf->SetFont('Arial', '', 6);
            $this->pdf->SetXY($cursor + 3.4, $y + 1.2);
            $this->pdf->Cell(16, 3.5, $caption, 0, 0);
            $cursor += 18;
        }
    }

    protected function mark(float $x, float $y, bool $on): void
    {
        $this->pdf->Rect($x, $y, 2.8, 2.8);
        if ($on) {
            $this->pdf->Line($x + 0.4, $y + 0.4, $x + 2.4, $y + 2.4);
            $this->pdf->Line($x + 2.4, $y + 0.4, $x + 0.4, $y + 2.4);
        }
    }

    protected function note(string $text): void
    {
        $this->pdf->SetFont('Arial', 'I', 6);
        $this->pdf->SetXY($this->left, $this->y);
        $this->pdf->Cell($this->width(), 3.5, $this->text($text), 0, 1);
        $this->y += 3.5;
    }

    protected function heavy(float $x, float $y, float $w, float $h, string $text, string $align): void
    {
        $this->pdf->SetFont('Arial', '', 11);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($w, $h, $this->text($text), 0, 0, $align);
        $this->pdf->SetXY($x + 0.15, $y);
        $this->pdf->Cell($w, $h, $this->text($text), 0, 0, $align);
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, mixed>
     */
    protected function pad(array $rows, int $count): array
    {
        return array_pad(array_slice($rows, 0, $count), $count, []);
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    protected function any(array $rows): bool
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function dmy(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }
        $time = strtotime($value);

        return $time ? date('d/m/Y', $time) : $value;
    }

    protected function present(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }
        $time = strtotime($value);

        return $time ? date('d/m/Y', $time) : $value;
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
