<?php

namespace App\Services;

use App\Models\LeaveCard;
use App\Models\LeaveRequest;
use App\Models\Personnel;
use Carbon\Carbon;

class LeaveCardService
{
    public const ANNUAL_CREDIT = 15.0;

    public const MONTHLY_CREDIT = 1.25;

    /**
     * Twelve months earn 15 vacation leave and 15 sick leave.
     * Each month of service, including the entrance month, earns 1.25.
     */
    public function creditForDays(int $days): float
    {
        if ($days >= 30) {
            return self::MONTHLY_CREDIT;
        }

        if ($days <= 0) {
            return 0.0;
        }

        return round($days * self::MONTHLY_CREDIT / 30, 3);
    }

    public static function formatCredit(float $value): string
    {
        $text = number_format($value, 3, '.', '');
        $text = rtrim(rtrim($text, '0'), '.');

        return $text === '' ? '0' : $text;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function months(Personnel $personnel, LeaveCard $card, ?Carbon $through = null): array
    {
        if (! $card->entrance_to_duty) {
            return [];
        }

        $entrance = $card->entrance_to_duty->copy()->startOfDay();
        $end = ($through ?? now()->endOfYear())->copy()->endOfMonth()->startOfDay();
        if ($end->lt($entrance)) {
            return [];
        }

        $leaves = LeaveRequest::query()
            ->where('personnel_id', $personnel->id)
            ->where('status', 'approved')
            ->whereDate('date_to', '>=', $entrance->toDateString())
            ->get();

        $adjustments = $card->adjustments ?? [];
        $rows = [];
        $vlBalance = 0.0;
        $slBalance = 0.0;
        $cursor = $entrance->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $isEntranceMonth = $cursor->isSameMonth($entrance);
            $earned = self::MONTHLY_CREDIT;

            $vlWithPay = 0.0;
            $slWithPay = 0.0;
            $notes = [];
            $monthStart = $cursor->copy()->startOfMonth()->startOfDay();
            $monthEnd = $cursor->copy()->endOfMonth()->startOfDay();

            foreach ($leaves as $leave) {
                $from = $leave->date_from->copy()->startOfDay()->max($monthStart);
                $to = $leave->date_to->copy()->startOfDay()->min($monthEnd);
                if ($from->gt($to)) {
                    continue;
                }

                $days = (int) $from->diffInDays($to) + 1;
                $bucket = $leave->balanceBucket();
                if ($bucket === 'Sick') {
                    $slWithPay += $days;
                } elseif ($bucket === 'Leave') {
                    $vlWithPay += $days;
                }
                $notes[] = $this->particular($leave->leave_type);
            }

            $autoParticulars = implode(', ', array_values(array_unique(array_filter($notes))));
            $manual = $adjustments[$key] ?? [];
            $vlWithout = (float) ($manual['vl_without_pay'] ?? 0);
            $slWithout = (float) ($manual['sl_without_pay'] ?? 0);
            if (! empty($manual['particulars'])) {
                $notes[] = (string) $manual['particulars'];
            }
            $remarks = trim((string) ($manual['remarks'] ?? ''));

            $vlBalance = round($vlBalance + $earned - $vlWithPay, 3);
            $slBalance = round($slBalance + $earned - $slWithPay, 3);

            $rows[] = [
                'key' => $key,
                'period' => $this->periodLabel($cursor),
                'year' => (int) $cursor->year,
                'from' => $isEntranceMonth ? $entrance->copy() : $monthStart->copy(),
                'to' => $monthEnd->copy(),
                'earned' => $earned,
                'vl_with_pay' => $vlWithPay,
                'vl_without_pay' => $vlWithout,
                'vl_balance' => $vlBalance,
                'sl_with_pay' => $slWithPay,
                'sl_without_pay' => $slWithout,
                'sl_balance' => $slBalance,
                'auto_particulars' => $autoParticulars,
                'particulars' => implode(', ', array_values(array_unique(array_filter($notes)))),
                'remarks' => $remarks,
            ];

            $cursor->addMonth();
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $months
     * @return list<array<string, mixed>>
     */
    public function yearlySummary(array $months): array
    {
        $groups = [];
        foreach ($months as $month) {
            $year = $month['year'];
            if (! isset($groups[$year])) {
                $groups[$year] = [
                    'from' => $month['from']->copy(),
                    'to' => $month['to']->copy(),
                    'vl_earned' => 0.0,
                    'vl_used' => 0.0,
                    'vl_balance' => 0.0,
                    'sl_earned' => 0.0,
                    'sl_used' => 0.0,
                    'sl_balance' => 0.0,
                    'remarks' => [],
                ];
            }

            $groups[$year]['to'] = $month['to']->copy();
            $groups[$year]['months'] = ($groups[$year]['months'] ?? 0) + 1;
            $groups[$year]['vl_earned'] = round($groups[$year]['vl_earned'] + $month['earned'], 3);
            $groups[$year]['sl_earned'] = round($groups[$year]['sl_earned'] + $month['earned'], 3);
            $groups[$year]['vl_used'] = round($groups[$year]['vl_used'] + $month['vl_with_pay'], 3);
            $groups[$year]['sl_used'] = round($groups[$year]['sl_used'] + $month['sl_with_pay'], 3);
            $groups[$year]['vl_balance'] = $month['vl_balance'];
            $groups[$year]['sl_balance'] = $month['sl_balance'];
            if ($month['remarks'] !== '') {
                $groups[$year]['remarks'][] = $month['remarks'];
            }
            if (str_contains($month['particulars'], 'FL')) {
                $groups[$year]['remarks'][] = 'FL '.$year;
            }
        }

        return array_map(function (array $year) {
            if (($year['months'] ?? 0) === 12) {
                $year['vl_earned'] = self::ANNUAL_CREDIT;
                $year['sl_earned'] = self::ANNUAL_CREDIT;
            }
            $year['remarks'] = implode(', ', array_values(array_unique($year['remarks'])));
            unset($year['months']);

            return $year;
        }, array_values($groups));
    }

    /**
     * @return array{vl: float, sl: float}
     */
    public function balanceAsOf(Personnel $personnel, LeaveCard $card, Carbon $asOf): array
    {
        $months = $this->months($personnel, $card, $asOf->copy()->endOfMonth());
        $last = $months === [] ? null : $months[array_key_last($months)];

        return [
            'vl' => (float) ($last['vl_balance'] ?? 0),
            'sl' => (float) ($last['sl_balance'] ?? 0),
        ];
    }

    private function periodLabel(Carbon $month): string
    {
        $names = [
            1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APRIL', 5 => 'MAY', 6 => 'JUNE',
            7 => 'JULY', 8 => 'AUG', 9 => 'SEPT', 10 => 'OCT', 11 => 'NOV', 12 => 'DEC',
        ];

        return $names[(int) $month->month].' '.$month->year;
    }

    private function particular(string $type): string
    {
        $normalized = strtolower($type);

        if (str_contains($normalized, 'forced')) {
            return 'FL';
        }
        if (str_contains($normalized, 'sick')) {
            return 'SL';
        }
        if (str_contains($normalized, 'vacation')) {
            return 'VL';
        }
        if (str_contains($normalized, 'privilege')) {
            return 'SPL';
        }

        return $type;
    }
}
