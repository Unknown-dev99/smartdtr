<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveCard;
use App\Models\Personnel;
use App\Models\StaffUser;
use App\Services\LeaveCardService;
use App\Services\LeaveCardWorkbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaveCardController extends Controller
{
    public function __construct(
        protected LeaveCardService $leaveCards
    ) {}

    public function index(): View
    {
        $staffRoles = StaffUser::query()->pluck('role', 'personnel_id');
        $people = Personnel::query()
            ->with('leaveCard')
            ->orderBy('name')
            ->get()
            ->filter(fn (Personnel $person) => $person->eligibleForLeaveCard($staffRoles[$person->id] ?? null))
            ->values();

        return view('admin.leave-cards.index', [
            'people' => $people,
            'balances' => $people->mapWithKeys(function (Personnel $person) {
                $card = $person->leaveCard;
                if (! $card?->entrance_to_duty) {
                    return [$person->id => null];
                }

                return [$person->id => $this->leaveCards->balanceAsOf($person, $card, now())];
            }),
        ]);
    }

    public function show(Personnel $personnel): View
    {
        $card = $this->cardFor($personnel);
        $months = $this->leaveCards->months($personnel, $card);

        return view('admin.leave-cards.show', [
            'personnel' => $personnel,
            'card' => $card,
            'months' => $months,
            'years' => $this->leaveCards->yearlySummary($months),
            'asOf' => $card->entrance_to_duty
                ? $this->leaveCards->balanceAsOf($personnel, $card, now())
                : null,
        ]);
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $card = $this->cardFor($personnel);
        $validated = $request->validate([
            'entrance_to_duty' => 'required|date',
            'civil_status' => 'nullable|string|max:50',
            'employment_status' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:255',
            'gsis_policy_no' => 'nullable|string|max:80',
            'tin' => 'nullable|string|max:40',
            'national_reference_no' => 'nullable|string|max:80',
            'prepared_by' => 'nullable|string|max:255',
            'prepared_title' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'approved_title' => 'nullable|string|max:255',
            'noted_by' => 'nullable|string|max:255',
            'noted_title' => 'nullable|string|max:255',
            'months' => 'nullable|array',
            'months.*.vl_without_pay' => 'nullable|numeric|min:0|max:31',
            'months.*.sl_without_pay' => 'nullable|numeric|min:0|max:31',
            'months.*.particulars' => 'nullable|string|max:80',
            'months.*.remarks' => 'nullable|string|max:80',
        ]);

        $adjustments = [];
        foreach ($validated['months'] ?? [] as $key => $row) {
            if (! preg_match('/^\d{4}-\d{2}$/', (string) $key)) {
                continue;
            }
            $vl = (float) ($row['vl_without_pay'] ?? 0);
            $sl = (float) ($row['sl_without_pay'] ?? 0);
            $note = trim((string) ($row['particulars'] ?? ''));
            $remarks = trim((string) ($row['remarks'] ?? ''));
            if ($vl <= 0 && $sl <= 0 && $note === '' && $remarks === '') {
                continue;
            }
            $adjustments[$key] = [
                'vl_without_pay' => $vl,
                'sl_without_pay' => $sl,
                'particulars' => $note,
                'remarks' => $remarks,
            ];
        }

        $card->update([
            ...collect($validated)->except('months')->all(),
            'adjustments' => $adjustments,
        ]);

        return redirect()
            ->route('admin.leave-cards.show', $personnel)
            ->with('success', 'Leave card saved.');
    }

    public function excel(Personnel $personnel, LeaveCardWorkbook $workbook): BinaryFileResponse
    {
        $card = $this->cardFor($personnel);
        $safe = preg_replace('/[^A-Za-z0-9 _-]+/', '', $personnel->name) ?: 'employee';

        return response()->download(
            $workbook->store($personnel, $card),
            'Leave Card - '.$safe.'.xlsx'
        )->deleteFileAfterSend(true);
    }

    public function print(Personnel $personnel): View
    {
        $card = $this->cardFor($personnel);
        $months = $this->leaveCards->months($personnel, $card);

        return view('admin.leave-cards.print', [
            'personnel' => $personnel,
            'card' => $card,
            'months' => $months,
            'years' => $this->leaveCards->yearlySummary($months),
        ]);
    }

    private function cardFor(Personnel $personnel): LeaveCard
    {
        $role = StaffUser::query()->where('personnel_id', $personnel->id)->value('role');
        abort_unless($personnel->eligibleForLeaveCard($role), 404);

        return LeaveCard::query()->firstOrCreate(['personnel_id' => $personnel->id]);
    }
}
