<?php

namespace App\Services;

use App\Models\CompensatoryTimeOff;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompensatoryTimeOffService
{
    public function __construct(protected CompensatoryTimeOffPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'balance_hours' => 'nullable|string|max:40',
            'entries' => 'required|array|min:1|max:8',
            'entries.*.date_from' => 'nullable|date',
            'entries.*.date_to' => 'nullable|date',
            'entries.*.hours' => 'nullable|string|max:20',
            'entries.*.purpose' => 'nullable|string|max:255',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): CompensatoryTimeOff
    {
        if (! $personnel->isNonTeaching()) {
            throw ValidationException::withMessages([
                'entries' => 'Compensatory Time Off is for non-teaching personnel.',
            ]);
        }

        $entries = [];
        foreach ($data['entries'] as $entry) {
            $from = trim((string) ($entry['date_from'] ?? ''));
            $purpose = trim((string) ($entry['purpose'] ?? ''));
            if ($from === '' && $purpose === '') {
                continue;
            }
            $entries[] = [
                'date_from' => $from,
                'date_to' => trim((string) ($entry['date_to'] ?? '')),
                'hours' => trim((string) ($entry['hours'] ?? '')),
                'purpose' => $purpose,
            ];
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'entries' => 'Add at least one compensatory time-off line.',
            ]);
        }

        $record = CompensatoryTimeOff::query()->updateOrCreate(
            ['personnel_id' => $personnel->id],
            [
                'staff_user_id' => $staff?->id,
                'balance_hours' => trim((string) ($data['balance_hours'] ?? '')) ?: null,
                'entries' => $entries,
                'prepared_on' => now()->toDateString(),
            ]
        );

        $this->storePdf($record);

        return $record;
    }

    public function render(CompensatoryTimeOff $record): string
    {
        return $this->pdf->render($record);
    }

    public function storePdf(CompensatoryTimeOff $record): Personnel201File
    {
        $path = '201_files/'.$record->personnel_id.'/compensatory_time_off/compensatory-time-off.pdf';
        $binary = $this->render($record);
        Storage::disk('local')->put($path, $binary);
        $record->loadMissing('personnel');

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $record->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'compensatory_time_off',
                'original_name' => 'Compensatory Time Off.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'Compensatory Time Off (CTO)',
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $record->staff_user_id,
                'uploaded_by_name' => $record->personnel?->name,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?CompensatoryTimeOff $record = null): array
    {
        $entries = $record?->entries ?: [[
            'date_from' => '',
            'date_to' => '',
            'hours' => '',
            'purpose' => '',
        ]];
        $blank = ['date_from' => '', 'date_to' => '', 'hours' => '', 'purpose' => ''];
        while (count($entries) < 4) {
            $entries[] = $blank;
        }

        return [
            'balance_hours' => $record?->balance_hours ?? '',
            'entries' => array_slice($entries, 0, 8),
        ];
    }
}
