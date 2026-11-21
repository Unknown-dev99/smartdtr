<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\PositionDescriptionForm;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;

class PositionDescriptionService
{
    public const DEPARTMENT = 'Department of Education';

    public const BUREAU = 'Region II';

    public const DIVISION = 'Schools Division of the City of Ilagan';

    public const WORKSTATION = 'Isabela National High School';

    public function __construct(protected PositionDescriptionPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $frequency = 'nullable|in:occasional,frequent';

        return [
            'position_title' => 'required|string|max:160',
            'item_number' => 'required|string|max:80',
            'salary_grade' => 'nullable|string|max:40',
            'lg_unit' => 'nullable|in:province,city,municipality',
            'lg_class' => 'nullable|in:1st,2nd,3rd,4th,5th,6th,special',
            'department' => 'required|string|max:160',
            'bureau' => 'required|string|max:160',
            'division' => 'required|string|max:160',
            'workstation' => 'required|string|max:160',
            'present_approp' => 'nullable|string|max:80',
            'previous_approp' => 'nullable|string|max:80',
            'salary_authorized' => 'nullable|string|max:80',
            'other_compensation' => 'nullable|string|max:160',
            'immediate_supervisor' => 'nullable|string|max:160',
            'next_supervisor' => 'nullable|string|max:160',
            'supervised' => 'nullable|array|max:7',
            'supervised.*.title' => 'nullable|string|max:160',
            'supervised.*.item' => 'nullable|string|max:80',
            'machines' => 'nullable|string|max:1000',
            'contacts.internal.executive' => $frequency,
            'contacts.internal.supervisors' => $frequency,
            'contacts.internal.non_supervisors' => $frequency,
            'contacts.internal.staff' => $frequency,
            'contacts.external.general_public' => $frequency,
            'contacts.external.other_agencies' => $frequency,
            'contacts.external.others' => $frequency,
            'others_specify' => 'nullable|string|max:160',
            'work_office' => 'nullable|boolean',
            'work_field' => 'nullable|boolean',
            'work_other' => 'nullable|boolean',
            'work_other_specify' => 'nullable|string|max:160',
            'unit_function' => 'nullable|string|max:2000',
            'job_summary' => 'nullable|string|max:4000',
            'education' => 'nullable|string|max:500',
            'experience' => 'nullable|string|max:500',
            'training' => 'nullable|string|max:500',
            'eligibility' => 'nullable|string|max:500',
            'core_competencies' => 'nullable|string|max:2000',
            'core_level' => 'nullable|string|max:160',
            'leadership_competencies' => 'nullable|string|max:2000',
            'leadership_level' => 'nullable|string|max:160',
            'duties' => 'required|array|min:1|max:12',
            'duties.*.percent' => 'required|string|max:20',
            'duties.*.duty' => 'required|string|max:500',
            'duties_level' => 'nullable|string|max:160',
            'employee_name' => 'required|string|max:160',
            'employee_date' => 'required|date',
            'supervisor_name' => 'nullable|string|max:160',
            'supervisor_date' => 'nullable|date',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): PositionDescriptionForm
    {
        $form = PositionDescriptionForm::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'employee_name' => trim((string) $data['employee_name']),
                'employee_date' => $data['employee_date'],
                'details' => $this->details($data),
            ]
        );

        $this->storePdf($form);

        return $form;
    }

    public function render(PositionDescriptionForm $form): string
    {
        return $this->pdf->render($form);
    }

    public function storePdf(PositionDescriptionForm $form): Personnel201File
    {
        $path = '201_files/'.$form->personnel_id.'/position_description_forms/position-description-form.pdf';
        $binary = $this->render($form);
        Storage::disk('local')->put($path, $binary);

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $form->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'position_description_forms',
                'original_name' => 'Position Description Form.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'DBM-CSC Form No. 1',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $form->staff_user_id,
                'uploaded_by_name' => $form->employee_name,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?PositionDescriptionForm $form = null): array
    {
        $blank = $this->blank($staff, $personnel);
        if (! $form) {
            return $blank;
        }

        return array_replace_recursive($blank, $form->details ?? [], [
            'employee_name' => $form->employee_name,
            'employee_date' => $form->employee_date?->toDateString() ?? $blank['employee_date'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function blank(StaffUser $staff, Personnel $personnel): array
    {
        return [
            'position_title' => (string) $personnel->role,
            'item_number' => '',
            'salary_grade' => '',
            'lg_unit' => '',
            'lg_class' => '',
            'department' => self::DEPARTMENT,
            'bureau' => self::BUREAU,
            'division' => self::DIVISION,
            'workstation' => self::WORKSTATION,
            'present_approp' => '',
            'previous_approp' => '',
            'salary_authorized' => '',
            'other_compensation' => '',
            'immediate_supervisor' => '',
            'next_supervisor' => '',
            'supervised' => [['title' => '', 'item' => '']],
            'machines' => '',
            'contacts' => [
                'internal' => [
                    'executive' => '',
                    'supervisors' => '',
                    'non_supervisors' => '',
                    'staff' => '',
                ],
                'external' => [
                    'general_public' => '',
                    'other_agencies' => '',
                    'others' => '',
                ],
            ],
            'others_specify' => '',
            'work_office' => false,
            'work_field' => false,
            'work_other' => false,
            'work_other_specify' => '',
            'unit_function' => '',
            'job_summary' => '',
            'education' => '',
            'experience' => '',
            'training' => '',
            'eligibility' => '',
            'core_competencies' => '',
            'core_level' => '',
            'leadership_competencies' => '',
            'leadership_level' => '',
            'duties' => [['percent' => '', 'duty' => '']],
            'duties_level' => '',
            'employee_name' => $staff->officialLetterName() ?: $staff->name,
            'employee_date' => now()->toDateString(),
            'supervisor_name' => '',
            'supervisor_date' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function details(array $data): array
    {
        $supervised = [];
        foreach ($data['supervised'] ?? [] as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $item = trim((string) ($row['item'] ?? ''));
            if ($title === '' && $item === '') {
                continue;
            }
            $supervised[] = ['title' => $title, 'item' => $item];
        }

        $duties = [];
        foreach ($data['duties'] as $row) {
            $duties[] = [
                'percent' => trim((string) $row['percent']),
                'duty' => trim((string) $row['duty']),
            ];
        }

        $contacts = $data['contacts'] ?? [];

        return [
            'position_title' => trim((string) $data['position_title']),
            'item_number' => trim((string) $data['item_number']),
            'salary_grade' => trim((string) ($data['salary_grade'] ?? '')),
            'lg_unit' => (string) ($data['lg_unit'] ?? ''),
            'lg_class' => (string) ($data['lg_class'] ?? ''),
            'department' => trim((string) $data['department']),
            'bureau' => trim((string) $data['bureau']),
            'division' => trim((string) $data['division']),
            'workstation' => trim((string) $data['workstation']),
            'present_approp' => trim((string) ($data['present_approp'] ?? '')),
            'previous_approp' => trim((string) ($data['previous_approp'] ?? '')),
            'salary_authorized' => trim((string) ($data['salary_authorized'] ?? '')),
            'other_compensation' => trim((string) ($data['other_compensation'] ?? '')),
            'immediate_supervisor' => trim((string) ($data['immediate_supervisor'] ?? '')),
            'next_supervisor' => trim((string) ($data['next_supervisor'] ?? '')),
            'supervised' => $supervised,
            'machines' => trim((string) ($data['machines'] ?? '')),
            'contacts' => [
                'internal' => [
                    'executive' => (string) ($contacts['internal']['executive'] ?? ''),
                    'supervisors' => (string) ($contacts['internal']['supervisors'] ?? ''),
                    'non_supervisors' => (string) ($contacts['internal']['non_supervisors'] ?? ''),
                    'staff' => (string) ($contacts['internal']['staff'] ?? ''),
                ],
                'external' => [
                    'general_public' => (string) ($contacts['external']['general_public'] ?? ''),
                    'other_agencies' => (string) ($contacts['external']['other_agencies'] ?? ''),
                    'others' => (string) ($contacts['external']['others'] ?? ''),
                ],
            ],
            'others_specify' => trim((string) ($data['others_specify'] ?? '')),
            'work_office' => (bool) ($data['work_office'] ?? false),
            'work_field' => (bool) ($data['work_field'] ?? false),
            'work_other' => (bool) ($data['work_other'] ?? false),
            'work_other_specify' => trim((string) ($data['work_other_specify'] ?? '')),
            'unit_function' => trim((string) ($data['unit_function'] ?? '')),
            'job_summary' => trim((string) ($data['job_summary'] ?? '')),
            'education' => trim((string) ($data['education'] ?? '')),
            'experience' => trim((string) ($data['experience'] ?? '')),
            'training' => trim((string) ($data['training'] ?? '')),
            'eligibility' => trim((string) ($data['eligibility'] ?? '')),
            'core_competencies' => trim((string) ($data['core_competencies'] ?? '')),
            'core_level' => trim((string) ($data['core_level'] ?? '')),
            'leadership_competencies' => trim((string) ($data['leadership_competencies'] ?? '')),
            'leadership_level' => trim((string) ($data['leadership_level'] ?? '')),
            'duties' => $duties,
            'duties_level' => trim((string) ($data['duties_level'] ?? '')),
            'supervisor_name' => trim((string) ($data['supervisor_name'] ?? '')),
            'supervisor_date' => (string) ($data['supervisor_date'] ?? ''),
        ];
    }
}
