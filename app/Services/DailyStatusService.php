<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\LocatorRequest;
use App\Models\PersonnelDailyStatus;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\StaffUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyStatusService
{
    /**
     * @return array{status:string,time_in:?string,time_out:?string}
     */
    public function computeDailyStatus(int $personnelId, string $date): array
    {
        $dateCarbon = Carbon::parse($date);
        $attendance = Attendance::where('personnel_id', $personnelId)
            ->whereDate('date', $date)
            ->first();

        $timeIn = $attendance?->time_in ? Carbon::parse($attendance->time_in)->format('H:i:s') : null;
        $timeOut = $attendance?->time_out ? Carbon::parse($attendance->time_out)->format('H:i:s') : null;

        if ($this->approvedLocatorFor($personnelId, $date)) {
            return ['status' => 'Official Business', 'time_in' => $timeIn, 'time_out' => $timeOut];
        }

        if ($timeIn) {
            $override = ScheduleOverride::where('personnel_id', $personnelId)
                ->whereDate('date', $date)
                ->first();

            if ($override && $override->override_type === 'Off-duty') {
                return ['status' => 'Present', 'time_in' => $timeIn, 'time_out' => $timeOut];
            }

            if ($override && $override->override_type === 'Class' && $override->start_time) {
                $status = $this->applyLateThreshold($timeIn, $override->start_time);

                return ['status' => $status, 'time_in' => $timeIn, 'time_out' => $timeOut];
            }

            $dayOfWeek = $dateCarbon->dayOfWeek;
            $schedule = Schedule::where('personnel_id', $personnelId)
                ->where('day_of_week', $dayOfWeek)
                ->orderBy('start_time')
                ->first();

            if ($schedule) {
                $status = $this->applyLateThreshold($timeIn, $schedule->start_time);

                return ['status' => $status, 'time_in' => $timeIn, 'time_out' => $timeOut];
            }

            return ['status' => 'Present', 'time_in' => $timeIn, 'time_out' => $timeOut];
        }

        $leave = LeaveRecord::where('personnel_id', $personnelId)
            ->whereDate('date', $date)
            ->first();

        if ($leave) {
            $status = $leave->leave_type === 'Travel' ? 'Official Travel' : 'On Leave';

            return ['status' => $status, 'time_in' => null, 'time_out' => null];
        }

        $override = ScheduleOverride::where('personnel_id', $personnelId)
            ->whereDate('date', $date)
            ->first();

        if ($override) {
            if ($override->override_type === 'Off-duty') {
                return ['status' => 'Off-duty', 'time_in' => null, 'time_out' => null];
            }

            return ['status' => 'Absent', 'time_in' => null, 'time_out' => null];
        }

        $dayOfWeek = $dateCarbon->dayOfWeek;
        $hasSchedule = Schedule::where('personnel_id', $personnelId)
            ->where('day_of_week', $dayOfWeek)
            ->exists();

        if ($hasSchedule) {
            return ['status' => 'Absent', 'time_in' => null, 'time_out' => null];
        }

        return ['status' => 'Off-duty', 'time_in' => null, 'time_out' => null];
    }

    /**
     * @return array{status:string,time_in:?string,time_out:?string}
     */
    public function computeAndPersist(int $personnelId, string $date): array
    {
        $result = $this->computeDailyStatus($personnelId, $date);

        PersonnelDailyStatus::updateOrCreateForDate($personnelId, $date, [
            'status' => $result['status'],
            'updated_at' => now(),
        ]);

        Attendance::updateOrCreateForDate($personnelId, $date, [
            'time_in' => $result['time_in'],
            'time_out' => $result['time_out'],
            'status' => $result['status'],
            'updated_at' => now(),
        ]);

        $this->warnForLateStreak($personnelId, $date, $result['status']);

        return $result;
    }

    /**
     * Warn the employee once when they are Late on three straight work days.
     * Weekends, holidays, and Off-duty days do not break the streak.
     */
    protected function warnForLateStreak(int $personnelId, string $date, string $status): void
    {
        if ($status !== 'Late') {
            return;
        }

        try {
            $lateDates = $this->consecutiveLateDatesEndingOn($personnelId, $date);
            if (count($lateDates) < 3) {
                return;
            }

            $staffUsers = StaffUser::query()
                ->where('personnel_id', $personnelId)
                ->where('status', 'active')
                ->get();

            if ($staffUsers->isEmpty()) {
                return;
            }

            $key = 'late-streak:'.$personnelId.':'.$lateDates[0];
            $formatted = array_map(
                fn (string $day) => Carbon::parse($day)->format('M j, Y'),
                $lateDates
            );
            $count = count($lateDates);
            $message = 'You were late for '.$count.' straight work days: '.$this->joinWithAnd($formatted).'. This is a warning. Please arrive on time.';
            $notifications = app(NotificationService::class);

            foreach ($staffUsers as $staff) {
                $alreadySent = $staff->notifications()->where('data->key', $key)->exists();
                if ($alreadySent) {
                    continue;
                }

                $notifications->notifyStaff(
                    $staff,
                    'Late attendance warning',
                    $message,
                    route('staff.attendance'),
                    $key
                );
            }
        } catch (\Throwable $e) {
            Log::error('Late streak warning failed', [
                'personnel_id' => $personnelId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Late dates in the current streak, oldest first, ending on $date.
     *
     * @return array<int, string>
     */
    protected function consecutiveLateDatesEndingOn(int $personnelId, string $date): array
    {
        $end = Carbon::parse($date)->startOfDay();
        $limit = $end->copy()->subDays(120);

        $rows = PersonnelDailyStatus::query()
            ->where('personnel_id', $personnelId)
            ->whereDate('date', '>=', $limit->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (PersonnelDailyStatus $row) => $row->date->toDateString());

        $holidays = Holiday::query()
            ->whereDate('date', '>=', $limit->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())
            ->flip();

        $lateDates = [];
        $cursor = $end->copy();

        while ($cursor->greaterThanOrEqualTo($limit)) {
            $day = $cursor->toDateString();
            $status = $rows->get($day)?->status;

            if ($status === 'Late') {
                $lateDates[] = $day;
            } elseif ($this->skipsLateStreak($cursor, $status, $holidays->has($day))) {
                // A day the employee was not expected to work.
            } else {
                break;
            }

            $cursor->subDay();
        }

        return array_reverse($lateDates);
    }

    protected function skipsLateStreak(Carbon $day, ?string $status, bool $isHoliday): bool
    {
        if ($status === 'Late') {
            return false;
        }

        if ($isHoliday || $status === 'Off-duty') {
            return true;
        }

        return $status === null && $day->isWeekend();
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function joinWithAnd(array $items): string
    {
        $count = count($items);
        if ($count <= 1) {
            return $items[0] ?? '';
        }

        if ($count === 2) {
            return $items[0].' and '.$items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items).', and '.$last;
    }

    protected function approvedLocatorFor(int $personnelId, string $date): ?LocatorRequest
    {
        return LocatorRequest::query()
            ->approvedCovering($personnelId, $date)
            ->first();
    }

    protected function applyLateThreshold(string $timeIn, string $scheduledStart): string
    {
        $threshold = config('smartdtr.late_threshold_minutes', 10);
        $arrival = Carbon::parse('2000-01-01 '.$timeIn);
        $start = Carbon::parse('2000-01-01 '.$scheduledStart)->addMinutes($threshold);

        return $arrival->gt($start) ? 'Late' : 'Present';
    }
}
