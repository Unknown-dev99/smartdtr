<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\ServiceRecord;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceRecordService
{
    public const STATION = 'Isabela National High School';

    public function __construct(protected ServiceRecordPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:160',
            'entries' => 'required|array|min:1|max:8',
            'entries.*.date_from' => 'nullable|string|max:40',
            'entries.*.date_to' => 'nullable|string|max:40',
            'entries.*.designation' => 'nullable|string|max:120',
            'entries.*.status' => 'nullable|string|max:40',
            'entries.*.salary' => 'nullable|string|max:40',
            'entries.*.station' => 'nullable|string|max:160',
            'entries.*.lwop' => 'nullable|string|max:40',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): ServiceRecord
    {
        $this->guard($personnel);

        $entries = [];
        foreach ($data['entries'] as $entry) {
            $designation = trim((string) ($entry['designation'] ?? ''));
            $station = trim((string) ($entry['station'] ?? ''));
            if ($designation === '' && $station === '') {
                continue;
            }
            $entries[] = [
                'date_from' => trim((string) ($entry['date_from'] ?? '')),
                'date_to' => trim((string) ($entry['date_to'] ?? '')),
                'designation' => $designation,
                'status' => trim((string) ($entry['status'] ?? '')),
                'salary' => trim((string) ($entry['salary'] ?? '')),
                'station' => $station,
                'lwop' => trim((string) ($entry['lwop'] ?? '')),
            ];
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'entries' => 'Add at least one service record line.',
            ]);
        }

        $record = ServiceRecord::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'birth_date' => ($data['birth_date'] ?? null) ?: null,
                'birth_place' => trim((string) ($data['birth_place'] ?? '')) ?: null,
                'entries' => $entries,
                'prepared_on' => now()->toDateString(),
            ]
        );

        $this->storePdf($record);

        return $record;
    }

    public function render(ServiceRecord $record): string
    {
        return $this->pdf->render($record);
    }

    public function storePdf(ServiceRecord $record): Personnel201File
    {
        $path = '201_files/'.$record->personnel_id.'/updated_service_record/service-record.pdf';
        $binary = $this->render($record);
        Storage::disk('local')->put($path, $binary);
        $record->loadMissing('personnel');

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $record->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'updated_service_record',
                'original_name' => 'Service Record.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'Updated Service Record',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $record->staff_user_id,
                'uploaded_by_name' => $record->personnel?->name,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?ServiceRecord $record = null): array
    {
        $entries = $record?->entries ?: [[
            'date_from' => '',
            'date_to' => 'Present',
            'designation' => (string) ($personnel->role ?: $staff->roleLabel()),
            'status' => '',
            'salary' => '',
            'station' => self::STATION,
            'lwop' => '',
        ]];

        return [
            'birth_date' => $record?->birth_date?->toDateString() ?? '',
            'birth_place' => $record?->birth_place ?? '',
            'entries' => $this->pad($entries),
        ];
    }

    /**
     * @param  list<array<string, string>>  $entries
     * @return list<array<string, string>>
     */
    protected function pad(array $entries): array
    {
        $blank = [
            'date_from' => '',
            'date_to' => '',
            'designation' => '',
            'status' => '',
            'salary' => '',
            'station' => '',
            'lwop' => '',
        ];
        while (count($entries) < 4) {
            $entries[] = $blank;
        }

        return array_slice($entries, 0, 8);
    }

    protected function guard(Personnel $personnel): void
    {
        if (! $personnel->isNonTeaching()) {
            throw ValidationException::withMessages([
                'entries' => 'The service record form is for non-teaching personnel.',
            ]);
        }
    }
}
