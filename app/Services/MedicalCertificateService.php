<?php

namespace App\Services;

use App\Models\MedicalCertificate;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;

class MedicalCertificateService
{
    public const AGENCY = 'Isabela National High School, Claravall St., San Vicente, City of Ilagan, Isabela';

    /** @var array<string, string> */
    public const EXAMS = [
        'blood_test' => 'Blood Test (CBC with Lipid Profile and Blood Glucose)',
        'urinalysis' => 'Urinalysis',
        'chest_xray' => 'Chest X-Ray',
        'drug_test' => 'Drug Test (2 Panel)',
        'ecg' => 'Electrocardiogram (ECG)',
        'psychological' => 'Psychological Examination',
        'neuro_psychiatric' => 'Neuro-Psychiatric Examination',
    ];

    public function __construct(protected MedicalCertificatePdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'appointee_name' => 'required|string|max:160',
            'address' => 'required|string|max:255',
            'age' => 'required|string|max:10',
            'sex' => 'required|string|max:20',
            'civil_status' => 'required|string|max:40',
            'agency_address' => 'required|string|max:255',
            'proposed_position' => 'required|string|max:160',
            'exams' => 'nullable|array',
            'exams.*' => 'in:'.implode(',', array_keys(self::EXAMS)),
            'fitness' => 'nullable|in:fit,unfit',
            'physician_name' => 'nullable|string|max:160',
            'other_information' => 'nullable|string|max:1000',
            'physician_agency' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:60',
            'height' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
            'blood_type' => 'nullable|string|max:10',
            'designation' => 'nullable|string|max:160',
            'date_examined' => 'nullable|date',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): MedicalCertificate
    {
        $certificate = MedicalCertificate::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'appointee_name' => trim((string) $data['appointee_name']),
                'details' => $this->details($data),
            ]
        );

        $this->storePdf($certificate);

        return $certificate;
    }

    public function render(MedicalCertificate $certificate): string
    {
        return $this->pdf->render($certificate);
    }

    public function storePdf(MedicalCertificate $certificate): Personnel201File
    {
        $path = '201_files/'.$certificate->personnel_id.'/medical_certificate_csc211/medical-certificate.pdf';
        $binary = $this->render($certificate);
        Storage::disk('local')->put($path, $binary);

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $certificate->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'medical_certificate_csc211',
                'original_name' => 'Medical Certificate CS Form 211.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'CS Form No. 211 Revised 2025',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $certificate->staff_user_id,
                'uploaded_by_name' => $certificate->appointee_name,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?MedicalCertificate $certificate = null): array
    {
        $blank = $this->blank($staff, $personnel);
        if (! $certificate) {
            return $blank;
        }

        return array_replace($blank, $certificate->details ?? [], [
            'appointee_name' => $certificate->appointee_name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function blank(StaffUser $staff, Personnel $personnel): array
    {
        $name = $staff->surnameFirstName();

        return [
            'appointee_name' => $name !== '' ? mb_strtoupper($name) : mb_strtoupper($staff->name),
            'address' => '',
            'age' => '',
            'sex' => '',
            'civil_status' => '',
            'agency_address' => self::AGENCY,
            'proposed_position' => (string) $personnel->role,
            'exams' => [],
            'fitness' => '',
            'physician_name' => '',
            'other_information' => '',
            'physician_agency' => '',
            'license_no' => '',
            'height' => '',
            'weight' => '',
            'blood_type' => '',
            'designation' => '',
            'date_examined' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function details(array $data): array
    {
        $exams = array_values(array_intersect(array_keys(self::EXAMS), $data['exams'] ?? []));

        return [
            'address' => trim((string) $data['address']),
            'age' => trim((string) $data['age']),
            'sex' => trim((string) $data['sex']),
            'civil_status' => trim((string) $data['civil_status']),
            'agency_address' => trim((string) $data['agency_address']),
            'proposed_position' => trim((string) $data['proposed_position']),
            'exams' => $exams,
            'fitness' => (string) ($data['fitness'] ?? ''),
            'physician_name' => trim((string) ($data['physician_name'] ?? '')),
            'other_information' => trim((string) ($data['other_information'] ?? '')),
            'physician_agency' => trim((string) ($data['physician_agency'] ?? '')),
            'license_no' => trim((string) ($data['license_no'] ?? '')),
            'height' => trim((string) ($data['height'] ?? '')),
            'weight' => trim((string) ($data['weight'] ?? '')),
            'blood_type' => trim((string) ($data['blood_type'] ?? '')),
            'designation' => trim((string) ($data['designation'] ?? '')),
            'date_examined' => (string) ($data['date_examined'] ?? ''),
        ];
    }
}
