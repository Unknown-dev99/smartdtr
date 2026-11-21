<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;

class PersonalDataSheetService
{
    /** @var array<int, string> */
    public const EDUCATION_LEVELS = [
        'ELEMENTARY',
        'SECONDARY',
        'VOCATIONAL / TRADE COURSE',
        'COLLEGE',
        'GRADUATE STUDIES',
    ];

    public function __construct(protected PersonalDataSheetPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $row = 'nullable|string|max:255';

        return [
            'surname' => 'required|string|max:80',
            'first_name' => 'required|string|max:80',
            'middle_name' => 'nullable|string|max:80',
            'name_extension' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'place_of_birth' => $row,
            'sex' => 'nullable|in:male,female',
            'civil_status' => 'nullable|in:single,married,widowed,separated,others',
            'civil_status_other' => 'nullable|string|max:80',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'blood_type' => 'nullable|string|max:8',
            'umid' => 'nullable|string|max:30',
            'pagibig' => 'nullable|string|max:30',
            'philhealth' => 'nullable|string|max:30',
            'philsys' => 'nullable|string|max:30',
            'tin' => 'nullable|string|max:30',
            'agency_employee_no' => 'nullable|string|max:30',
            'citizenship' => 'nullable|in:filipino,dual',
            'dual_by' => 'nullable|in:birth,naturalization',
            'citizenship_country' => 'nullable|string|max:80',
            'residential' => 'nullable|array',
            'residential.*' => $row,
            'permanent' => 'nullable|array',
            'permanent.*' => $row,
            'telephone' => 'nullable|string|max:40',
            'mobile' => 'nullable|string|max:40',
            'email' => 'nullable|string|max:160',
            'spouse' => 'nullable|array',
            'spouse.*' => $row,
            'children' => 'nullable|array|max:30',
            'children.*.name' => $row,
            'children.*.birth_date' => 'nullable|date',
            'father' => 'nullable|array',
            'father.*' => 'nullable|string|max:80',
            'mother' => 'nullable|array',
            'mother.*' => 'nullable|string|max:80',
            'education' => 'nullable|array|max:20',
            'education.*.level' => $row,
            'education.*.school' => $row,
            'education.*.course' => $row,
            'education.*.from' => 'nullable|string|max:10',
            'education.*.to' => 'nullable|string|max:10',
            'education.*.units' => 'nullable|string|max:40',
            'education.*.year_graduated' => 'nullable|string|max:10',
            'education.*.honors' => $row,
            'eligibility' => 'nullable|array|max:30',
            'eligibility.*.career' => $row,
            'eligibility.*.rating' => 'nullable|string|max:20',
            'eligibility.*.exam_date' => 'nullable|date',
            'eligibility.*.place' => $row,
            'eligibility.*.license_no' => 'nullable|string|max:40',
            'eligibility.*.license_validity' => 'nullable|date',
            'work' => 'nullable|array|max:40',
            'work.*.from' => 'nullable|date',
            'work.*.to' => 'nullable|string|max:20',
            'work.*.position' => $row,
            'work.*.office' => $row,
            'work.*.salary' => 'nullable|string|max:40',
            'work.*.grade' => 'nullable|string|max:20',
            'work.*.status' => 'nullable|string|max:40',
            'work.*.government' => 'nullable|in:Y,N',
            'training' => 'nullable|array|max:40',
            'training.*.title' => $row,
            'training.*.from' => 'nullable|date',
            'training.*.to' => 'nullable|date',
            'training.*.hours' => 'nullable|string|max:10',
            'training.*.type' => 'nullable|string|max:40',
            'training.*.sponsor' => $row,
            'voluntary' => 'nullable|array|max:30',
            'voluntary.*.organization' => $row,
            'voluntary.*.from' => 'nullable|date',
            'voluntary.*.to' => 'nullable|date',
            'voluntary.*.hours' => 'nullable|string|max:10',
            'voluntary.*.position' => $row,
            'skills' => 'nullable|array|max:20',
            'skills.*' => $row,
            'recognitions' => 'nullable|array|max:20',
            'recognitions.*' => $row,
            'memberships' => 'nullable|array|max:20',
            'memberships.*' => $row,
            'q34a' => 'nullable|in:yes,no',
            'q34a_details' => $row,
            'q34b' => 'nullable|in:yes,no',
            'q34b_details' => $row,
            'q35a' => 'nullable|in:yes,no',
            'q35a_details' => $row,
            'q35b' => 'nullable|in:yes,no',
            'q35b_details' => $row,
            'q35b_date' => 'nullable|date',
            'q35b_status' => $row,
            'q36' => 'nullable|in:yes,no',
            'q36_details' => $row,
            'q37' => 'nullable|in:yes,no',
            'q37_details' => $row,
            'q38a' => 'nullable|in:yes,no',
            'q38a_details' => $row,
            'q38b' => 'nullable|in:yes,no',
            'q38b_details' => $row,
            'q39' => 'nullable|in:yes,no',
            'q39_details' => $row,
            'q40a' => 'nullable|in:yes,no',
            'q40a_details' => $row,
            'q40b' => 'nullable|in:yes,no',
            'q40b_id' => 'nullable|string|max:40',
            'q40c' => 'nullable|in:yes,no',
            'q40c_id' => 'nullable|string|max:40',
            'references' => 'nullable|array|max:3',
            'references.*.name' => $row,
            'references.*.address' => $row,
            'references.*.contact' => $row,
            'gov_id' => $row,
            'gov_id_no' => 'nullable|string|max:40',
            'gov_id_issuance' => $row,
            'date_accomplished' => 'nullable|date',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): PersonalDataSheet
    {
        $sheet = PersonalDataSheet::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'date_accomplished' => $data['date_accomplished'] ?: null,
                'details' => $this->details($data),
            ]
        );

        $this->storePdf($sheet);

        return $sheet;
    }

    public function render(PersonalDataSheet $sheet): string
    {
        return $this->pdf->render($sheet);
    }

    public function storePdf(PersonalDataSheet $sheet): Personnel201File
    {
        $path = '201_files/'.$sheet->personnel_id.'/personal_data_sheet/personal-data-sheet.pdf';
        $binary = $this->render($sheet);
        Storage::disk('local')->put($path, $binary);

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $sheet->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'personal_data_sheet',
                'original_name' => 'Personal Data Sheet CS Form 212.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'CS Form No. 212 Revised 2026',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $sheet->staff_user_id,
                'uploaded_by_name' => trim(($sheet->details['surname'] ?? '').', '.($sheet->details['first_name'] ?? '')),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?PersonalDataSheet $sheet = null): array
    {
        $blank = $this->blank($staff, $personnel);
        if (! $sheet) {
            return $blank;
        }

        $saved = $sheet->details ?? [];
        $merged = array_replace_recursive($blank, $saved);
        $merged['date_accomplished'] = $sheet->date_accomplished?->toDateString() ?? ($saved['date_accomplished'] ?? '');
        $merged['education'] = $this->educationRows($saved['education'] ?? []);
        foreach (['children', 'eligibility', 'work', 'training', 'voluntary', 'references'] as $key) {
            $rows = $saved[$key] ?? [];
            $merged[$key] = $rows !== [] ? $rows : $blank[$key];
        }
        foreach (['skills', 'recognitions', 'memberships'] as $key) {
            $rows = array_values(array_filter($saved[$key] ?? []));
            $merged[$key] = $rows !== [] ? $rows : [''];
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function blank(StaffUser $staff, Personnel $personnel): array
    {
        return [
            'surname' => strtoupper((string) $staff->last_name),
            'first_name' => strtoupper((string) $staff->first_name),
            'middle_name' => strtoupper((string) $staff->middle_name),
            'name_extension' => strtoupper((string) $staff->suffix),
            'birth_date' => '',
            'place_of_birth' => '',
            'sex' => '',
            'civil_status' => '',
            'civil_status_other' => '',
            'height' => '',
            'weight' => '',
            'blood_type' => '',
            'umid' => '',
            'pagibig' => '',
            'philhealth' => '',
            'philsys' => '',
            'tin' => '',
            'agency_employee_no' => (string) ($personnel->employee_number ?? $staff->employee_number ?? ''),
            'citizenship' => 'filipino',
            'dual_by' => '',
            'citizenship_country' => '',
            'residential' => $this->blankAddress(),
            'permanent' => $this->blankAddress(),
            'telephone' => '',
            'mobile' => (string) ($staff->phone ?? ''),
            'email' => (string) $staff->email,
            'spouse' => [
                'surname' => '', 'first_name' => '', 'middle_name' => '', 'extension' => '',
                'occupation' => '', 'employer' => '', 'business_address' => '', 'telephone' => '',
            ],
            'children' => [['name' => '', 'birth_date' => '']],
            'father' => ['surname' => '', 'first_name' => '', 'middle_name' => '', 'extension' => ''],
            'mother' => ['surname' => '', 'first_name' => '', 'middle_name' => ''],
            'education' => $this->educationRows([]),
            'eligibility' => [$this->blankEligibility()],
            'work' => [$this->blankWork()],
            'training' => [$this->blankTraining()],
            'voluntary' => [$this->blankVoluntary()],
            'skills' => [''],
            'recognitions' => [''],
            'memberships' => [''],
            'q34a' => '', 'q34a_details' => '',
            'q34b' => '', 'q34b_details' => '',
            'q35a' => '', 'q35a_details' => '',
            'q35b' => '', 'q35b_details' => '', 'q35b_date' => '', 'q35b_status' => '',
            'q36' => '', 'q36_details' => '',
            'q37' => '', 'q37_details' => '',
            'q38a' => '', 'q38a_details' => '',
            'q38b' => '', 'q38b_details' => '',
            'q39' => '', 'q39_details' => '',
            'q40a' => '', 'q40a_details' => '',
            'q40b' => '', 'q40b_id' => '',
            'q40c' => '', 'q40c_id' => '',
            'references' => [
                ['name' => '', 'address' => '', 'contact' => ''],
                ['name' => '', 'address' => '', 'contact' => ''],
                ['name' => '', 'address' => '', 'contact' => ''],
            ],
            'gov_id' => '',
            'gov_id_no' => '',
            'gov_id_issuance' => '',
            'date_accomplished' => now()->toDateString(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, string>>
     */
    public function educationRows(array $rows): array
    {
        $byLevel = [];
        $extra = [];
        foreach ($rows as $row) {
            $level = strtoupper(trim((string) ($row['level'] ?? '')));
            if (in_array($level, self::EDUCATION_LEVELS, true) && ! isset($byLevel[$level])) {
                $byLevel[$level] = $row;
            } elseif ($this->filled($row, ['level'])) {
                $extra[] = $row;
            }
        }

        $out = [];
        foreach (self::EDUCATION_LEVELS as $level) {
            $out[] = array_merge($this->blankEducation(), $byLevel[$level] ?? [], ['level' => $level]);
        }
        foreach ($extra as $row) {
            $out[] = array_merge($this->blankEducation(), $row);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function blankAddress(): array
    {
        return [
            'house' => '', 'street' => '', 'subdivision' => '', 'barangay' => '',
            'city' => '', 'province' => '', 'zip' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function blankEducation(): array
    {
        return [
            'level' => '', 'school' => '', 'course' => '', 'from' => '', 'to' => '',
            'units' => '', 'year_graduated' => '', 'honors' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function blankEligibility(): array
    {
        return ['career' => '', 'rating' => '', 'exam_date' => '', 'place' => '', 'license_no' => '', 'license_validity' => ''];
    }

    /**
     * @return array<string, string>
     */
    protected function blankWork(): array
    {
        return ['from' => '', 'to' => '', 'position' => '', 'office' => '', 'salary' => '', 'grade' => '', 'status' => '', 'government' => ''];
    }

    /**
     * @return array<string, string>
     */
    protected function blankTraining(): array
    {
        return ['title' => '', 'from' => '', 'to' => '', 'hours' => '', 'type' => '', 'sponsor' => ''];
    }

    /**
     * @return array<string, string>
     */
    protected function blankVoluntary(): array
    {
        return ['organization' => '', 'from' => '', 'to' => '', 'hours' => '', 'position' => ''];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function details(array $data): array
    {
        $details = $data;
        $details['children'] = $this->keep($data['children'] ?? [], ['name', 'birth_date']);
        $details['education'] = $this->educationRows($data['education'] ?? []);
        $details['eligibility'] = $this->keep($data['eligibility'] ?? [], ['career', 'rating', 'exam_date', 'place', 'license_no', 'license_validity']);
        $details['work'] = $this->keep($data['work'] ?? [], ['from', 'to', 'position', 'office', 'salary', 'grade', 'status', 'government']);
        $details['training'] = $this->keep($data['training'] ?? [], ['title', 'from', 'to', 'hours', 'type', 'sponsor']);
        $details['voluntary'] = $this->keep($data['voluntary'] ?? [], ['organization', 'from', 'to', 'hours', 'position']);
        $details['skills'] = array_values(array_filter(array_map('trim', $data['skills'] ?? [])));
        $details['recognitions'] = array_values(array_filter(array_map('trim', $data['recognitions'] ?? [])));
        $details['memberships'] = array_values(array_filter(array_map('trim', $data['memberships'] ?? [])));
        $details['references'] = $this->keep($data['references'] ?? [], ['name', 'address', 'contact']);

        return $details;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    protected function keep(array $rows, array $keys): array
    {
        $kept = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($this->filled($row, [])) {
                $kept[] = $row;
            }
        }

        return $kept;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $ignore
     */
    protected function filled(array $row, array $ignore): bool
    {
        foreach ($row as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }
}
