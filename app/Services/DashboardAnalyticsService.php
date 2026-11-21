<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\PersonnelDailyStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function __construct(
        protected DailyStatusService $dailyStatusService
    ) {}

    /**
     * @return array<int, array{label:string,present:int,late:int,absent:int}>
     */
    public function monthlyTrend(int $days = 14): array
    {
        $trend = [];
        $start = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $counts = PersonnelDailyStatus::whereDate('date', $date)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $trend[] = [
                'label' => Carbon::parse($date)->format('M d'),
                'present' => (int) ($counts['Present'] ?? 0),
                'late' => (int) ($counts['Late'] ?? 0),
                'absent' => (int) ($counts['Absent'] ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @return array<string, int>
     */
    public function monthSummary(): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $counts = PersonnelDailyStatus::whereBetween('date', [$start, $end])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'Present' => (int) ($counts['Present'] ?? 0),
            'Late' => (int) ($counts['Late'] ?? 0),
            'Absent' => (int) ($counts['Absent'] ?? 0),
            'On Leave' => (int) ($counts['On Leave'] ?? 0),
            'Official Business' => (int) ($counts['Official Business'] ?? 0),
        ];
    }

    /**
     * Statuses on the school attendance report.
     *
     * @var list<string>
     */
    public const OFFICE_STATUSES = [
        'Present',
        'Late',
        'Absent',
        'On Leave',
        'Official Travel',
        'Official Business',
        'Off-duty',
    ];

    /**
     * @var array<string, string>
     */
    public const OFFICE_COLORS = [
        'Present' => '#2E7D32',
        'Late' => '#FBC02D',
        'Absent' => '#C62828',
        'On Leave' => '#0D47A1',
        'Official Travel' => '#6A1B9A',
        'Official Business' => '#0277BD',
        'Off-duty' => '#546E7A',
    ];

    /**
     * @return array<string, mixed>
     */
    public function officeReport(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $personnel = Personnel::active()->orderBy('name')->get(['id', 'name', 'role']);
        $ids = $personnel->pluck('id');

        $rows = $ids->isEmpty()
            ? collect()
            : PersonnelDailyStatus::query()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('personnel_id', $ids)
                ->get(['personnel_id', 'date', 'status']);

        $summary = array_fill_keys(self::OFFICE_STATUSES, 0);
        $byPerson = [];
        $byDay = [];

        foreach ($personnel as $person) {
            $byPerson[$person->id] = array_fill_keys(self::OFFICE_STATUSES, 0);
        }

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $byDay[$date->toDateString()] = array_fill_keys(self::OFFICE_STATUSES, 0);
        }

        foreach ($rows as $row) {
            $status = (string) $row->status;
            if (! isset($summary[$status])) {
                continue;
            }
            $day = $row->date instanceof Carbon ? $row->date->toDateString() : Carbon::parse($row->date)->toDateString();
            $summary[$status]++;
            if (isset($byPerson[$row->personnel_id])) {
                $byPerson[$row->personnel_id][$status]++;
            }
            if (isset($byDay[$day])) {
                $byDay[$day][$status]++;
            }
        }

        $people = [];
        $groups = [
            'Teaching' => array_fill_keys(self::OFFICE_STATUSES, 0),
            'Non-teaching' => array_fill_keys(self::OFFICE_STATUSES, 0),
        ];

        foreach ($personnel as $person) {
            $group = $this->isTeachingRole($person->role) ? 'Teaching' : 'Non-teaching';
            $counts = $byPerson[$person->id];
            foreach (self::OFFICE_STATUSES as $status) {
                $groups[$group][$status] += $counts[$status];
            }
            $people[] = [
                'name' => $person->name,
                'role' => (string) ($person->role ?: ''),
                'group' => $group,
                'counts' => $counts,
                'late_absent' => $counts['Late'] + $counts['Absent'],
            ];
        }

        $trend = [];
        foreach ($byDay as $day => $counts) {
            $trend[] = [
                'label' => Carbon::parse($day)->format('M j'),
                'counts' => $counts,
            ];
        }

        return [
            'school' => 'Isabela National High School – Senior High School',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'from_label' => $start->format('F j, Y'),
            'to_label' => $end->format('F j, Y'),
            'statuses' => self::OFFICE_STATUSES,
            'colors' => self::OFFICE_COLORS,
            'summary' => $summary,
            'summary_total' => array_sum($summary),
            'trend' => $trend,
            'personnel' => $people,
            'groups' => $groups,
        ];
    }

    public function isTeachingRole(?string $role): bool
    {
        $role = strtoupper(trim((string) $role));

        return $role !== '' && (bool) preg_match('/^(TCH|MT|SST|TEACHER|SUBSTITUTE)\b/', $role);
    }
}
