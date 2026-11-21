<?php

namespace App\Services;

use App\Models\ClearanceRequest;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClearanceRequestService
{
    public const AGENCY_NAME = 'Isabela National High School';

    public const AGENCY_ADDRESS = 'Claravall St., San Vicente, City of Ilagan, Isabela';

    public function __construct(protected ClearanceFormPdf $pdf) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100',
            'request_date' => 'required|date',
            'last_name' => 'required|string|max:120',
            'first_name' => 'required|string|max:120',
            'middle_initial' => 'nullable|string|max:10',
            'home_address' => 'required|string|max:255',
            'contact_no' => 'required|string|max:40',
            'email' => 'required|email|max:255',
            'present_position' => 'required|string|max:120',
            'agency_name' => 'required|string|max:160',
            'agency_address' => 'required|string|max:255',
            'purpose' => ['required', Rule::in([
                ClearanceRequest::PURPOSE_PROMOTION,
                ClearanceRequest::PURPOSE_RETIREMENT,
                ClearanceRequest::PURPOSE_OTHERS,
            ])],
            'purpose_other' => 'nullable|required_if:purpose,others|string|max:160',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Personnel $personnel, ?StaffUser $staff, array $data): ClearanceRequest
    {
        $clearance = ClearanceRequest::query()->updateOrCreate(
            [
                'personnel_id' => $personnel->id,
                'year' => (int) $data['year'],
            ],
            [
                'staff_user_id' => $staff?->id,
                'request_date' => $data['request_date'],
                'last_name' => trim((string) $data['last_name']),
                'first_name' => trim((string) $data['first_name']),
                'middle_initial' => trim((string) ($data['middle_initial'] ?? '')) ?: null,
                'home_address' => trim((string) $data['home_address']),
                'contact_no' => trim((string) $data['contact_no']),
                'email' => trim((string) $data['email']),
                'present_position' => trim((string) $data['present_position']),
                'agency_name' => trim((string) $data['agency_name']),
                'agency_address' => trim((string) $data['agency_address']),
                'purpose' => $data['purpose'],
                'purpose_other' => $data['purpose'] === ClearanceRequest::PURPOSE_OTHERS
                    ? trim((string) ($data['purpose_other'] ?? ''))
                    : null,
            ]
        );

        $this->storePdf($clearance);

        return $clearance;
    }

    public function render(ClearanceRequest $clearance): string
    {
        return $this->pdf->render($clearance);
    }

    public function storePdf(ClearanceRequest $clearance): Personnel201File
    {
        $path = '201_files/'.$clearance->personnel_id.'/clearance_property_money_csc7/request-for-clearance-'.$clearance->year.'.pdf';
        $binary = $this->render($clearance);
        Storage::disk('local')->put($path, $binary);

        return Personnel201File::query()->updateOrCreate(
            [
                'personnel_id' => $clearance->personnel_id,
                'stored_path' => $path,
            ],
            [
                'category' => 'clearance_property_money_csc7',
                'original_name' => 'Request for Clearance '.$clearance->year.'.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($binary),
                'notes' => 'Year '.$clearance->year,
                'uploaded_by_type' => 'staff',
                'uploaded_by_id' => $clearance->staff_user_id,
                'uploaded_by_name' => trim($clearance->first_name.' '.$clearance->last_name),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(StaffUser $staff, Personnel $personnel, ?ClearanceRequest $existing = null): array
    {
        $names = $staff->formNameParts();
        $middle = trim($names['middle']);
        if (strlen($middle) > 2) {
            $middle = substr($middle, 0, 1).'.';
        }

        return [
            'year' => $existing?->year ?? (int) now()->year,
            'request_date' => $existing?->request_date?->toDateString() ?? now()->toDateString(),
            'last_name' => $existing?->last_name ?? $names['last'],
            'first_name' => $existing?->first_name ?? $names['first'],
            'middle_initial' => $existing?->middle_initial ?? $middle,
            'home_address' => $existing?->home_address ?? '',
            'contact_no' => $existing?->contact_no ?? (string) $staff->phone,
            'email' => $existing?->email ?? (string) $staff->email,
            'present_position' => $existing?->present_position ?? (string) $personnel->role,
            'agency_name' => $existing?->agency_name ?? self::AGENCY_NAME,
            'agency_address' => $existing?->agency_address ?? self::AGENCY_ADDRESS,
            'purpose' => $existing?->purpose ?? ClearanceRequest::PURPOSE_PROMOTION,
            'purpose_other' => $existing?->purpose_other ?? '',
        ];
    }
}
