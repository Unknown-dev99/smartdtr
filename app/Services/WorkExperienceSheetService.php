<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use App\Models\WorkExperienceSheet;
use Illuminate\Support\Facades\Storage;

class WorkExperienceSheetService
{
    public const AGENCY = 'Isabela National High School, Claravall St., San Vicente, City of Ilagan, Isabela';

    public function __construct(protected WorkExperienceSheetPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sheet_date' => 'required|date',
            'printed_name' => 'required|string|max:160',
            'entries' => 'required|array|min:1|max:15',
            'entries.*.duration' => 'required|string|max:120',
            'entries.*.position' => 'required|string|max:160',
            'entries.*.office_unit' => 'required|string|max:160',
            'entries.*.supervisor' => 'required|string|max:160',
            'entries.*.agency' => 'required|string|max:255',
            'entries.*.accomplishments' => 'nullable|string|max:2000',
            'entries.*.duties' => 'required|string|max:3000',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): WorkExperienceSheet
    {
        $entries = [];
        foreach ($data['entries'] as $entry) {
            $entries[] = [
                'duration' => trim((string) $entry['duration']),
                'position' => trim((string) $entry['position']),
                'office_unit' => trim((string) $entry['office_unit']),
                'supervisor' => trim((string) $entry['supervisor']),
                'agency' => trim((string) $entry['agency']),
                'accomplishments' => trim((string) ($entry['accomplishments'] ?? '')),
                'duties' => trim((string) $entry['duties']),
            ];
        }

        $sheet = WorkExperienceSheet::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'sheet_date' => $data['sheet_date'],
                'printed_name' => trim((string) $data['printed_name']),
                'entries' => $entries,
            ]
        );

        $this->storePdf($sheet);

        return $sheet;
    }

    public function render(WorkExperienceSheet $sheet): string
    {
        return $this->pdf->render($sheet);
    }

    public function storePdf(WorkExperienceSheet $sheet): Personnel201File
    {
        $path = '201_files/'.$sheet->personnel_id.'/work_experience_sheet/work-experience-sheet.pdf';
        $binary = $this->render($sheet);
        Storage::disk('local')->put($path, $binary);

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $sheet->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'work_experience_sheet',
                'original_name' => 'Work Experience Sheet.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'CS Form No. 212 attachment',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $sheet->staff_user_id,
                'uploaded_by_name' => $sheet->printed_name,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?WorkExperienceSheet $sheet = null): array
    {
        if ($sheet) {
            return [
                'sheet_date' => $sheet->sheet_date?->toDateString() ?? now()->toDateString(),
                'printed_name' => $sheet->printed_name,
                'entries' => $sheet->entries ?: [$this->blankEntry($staff, $personnel)],
            ];
        }

        return [
            'sheet_date' => now()->toDateString(),
            'printed_name' => $staff->officialLetterName() ?: $staff->name,
            'entries' => [$this->blankEntry($staff, $personnel)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function blankEntry(StaffUser $staff, Personnel $personnel): array
    {
        return [
            'duration' => '',
            'position' => (string) $personnel->role,
            'office_unit' => (string) ($staff->department ?: ''),
            'supervisor' => '',
            'agency' => self::AGENCY,
            'accomplishments' => '',
            'duties' => '',
        ];
    }
}
