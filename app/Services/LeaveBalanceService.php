<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveCard;
use App\Models\LeaveRequest;
use App\Models\Personnel;

class LeaveBalanceService
{
    public function ensureBalance(Personnel $personnel): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['personnel_id' => $personnel->id],
            ['vl_balance' => 15, 'sl_balance' => 15, 'vl_used' => 0, 'sl_used' => 0]
        );
    }

    public function deductForRequest(LeaveRequest $request): void
    {
        $personnelId = $request->personnel_id ?? $request->staffUser?->personnel_id;
        if (! $personnelId) {
            return;
        }

        $balance = $this->ensureBalance(Personnel::find($personnelId));
        $days = $request->dayCount();

        $bucket = method_exists($request, 'balanceBucket')
            ? $request->balanceBucket()
            : $request->leave_type;

        match ($bucket) {
            'Sick' => $balance->increment('sl_used', $days),
            'Other' => null,
            default => $balance->increment('vl_used', $days),
        };
    }

    /**
     * @return array{vl: float, sl: float}
     */
    public function remaining(Personnel $personnel): array
    {
        $card = LeaveCard::query()->where('personnel_id', $personnel->id)->first();

        if ($personnel->eligibleForLeaveCard() && $card?->entrance_to_duty) {
            return app(LeaveCardService::class)->balanceAsOf($personnel, $card, now());
        }

        $balance = $this->ensureBalance($personnel);

        return [
            'vl' => $balance->vlRemaining(),
            'sl' => $balance->slRemaining(),
        ];
    }

    public function hasSufficientBalance(Personnel $personnel, string $leaveType, float $days): bool
    {
        $credits = $this->remaining($personnel);
        $normalized = strtolower($leaveType);

        if (in_array($leaveType, ['Travel', 'Emergency', 'Other', 'Others'], true)
            || str_contains($normalized, 'emergency')
            || str_contains($normalized, 'maternity')
            || str_contains($normalized, 'paternity')
            || str_contains($normalized, 'study')
            || str_contains($normalized, 'vawc')
            || str_contains($normalized, 'adoption')
            || str_contains($normalized, 'rehabilitation')
            || str_contains($normalized, 'solo')
            || str_contains($normalized, 'compensatory')
            || str_contains($normalized, 'cto')) {
            return true;
        }

        if ($leaveType === 'Sick' || str_contains($normalized, 'sick')) {
            return $credits['sl'] >= $days;
        }

        return $credits['vl'] >= $days;
    }
}
