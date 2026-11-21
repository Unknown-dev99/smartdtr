<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleWeekService
{
    public const WEEKDAY_LABELS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public const DEFAULT_DAYS = [1, 2, 3, 4, 5];

    public const DEFAULT_START = '07:30:00';

    public const DEFAULT_END = '17:00:00';

    public const DEFAULT_SUBJECT = 'Regular duty';

    /**
     * Replace the selected weekdays with one duty row each.
     *
     * @param  list<int>  $personnelIds
     * @param  list<int>  $days
     */
    public function assignWeek(
        array $personnelIds,
        array $days,
        string $startTime,
        string $endTime,
        string $subject
    ): int {
        $personnelIds = $this->uniqueIds($personnelIds);
        $days = $this->uniqueDays($days);
        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);
        $subject = trim($subject) !== '' ? trim($subject) : self::DEFAULT_SUBJECT;

        if ($personnelIds === [] || $days === []) {
            return 0;
        }

        if (strtotime('2000-01-01 '.$end) <= strtotime('2000-01-01 '.$start)) {
            throw new InvalidArgumentException('End time must be after start time.');
        }

        $saved = 0;

        DB::transaction(function () use ($personnelIds, $days, $start, $end, $subject, &$saved) {
            foreach ($personnelIds as $personnelId) {
                foreach ($days as $day) {
                    $this->replaceDay($personnelId, $day, $start, $end, $subject);
                    $saved++;
                }
            }
        });

        return $saved;
    }

    public function applySchoolDefaultToUnscheduled(): int
    {
        return $this->assignWeek(
            $this->unscheduledPersonnelIds(),
            self::DEFAULT_DAYS,
            self::DEFAULT_START,
            self::DEFAULT_END,
            self::DEFAULT_SUBJECT
        );
    }

    /**
     * @param  list<int>  $targetIds
     */
    public function copyWeek(int $sourcePersonnelId, array $targetIds): int
    {
        $rows = Schedule::query()
            ->where('personnel_id', $sourcePersonnelId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time', 'subject']);

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('The source employee has no weekly schedule to copy.');
        }

        $targetIds = array_values(array_filter(
            $this->uniqueIds($targetIds),
            fn (int $id) => $id !== $sourcePersonnelId
        ));

        if ($targetIds === []) {
            return 0;
        }

        $saved = 0;

        DB::transaction(function () use ($rows, $targetIds, &$saved) {
            foreach ($targetIds as $personnelId) {
                foreach ($rows as $row) {
                    $this->replaceDay(
                        $personnelId,
                        (int) $row->day_of_week,
                        (string) $row->start_time,
                        (string) $row->end_time,
                        (string) $row->subject
                    );
                    $saved++;
                }
            }
        });

        return $saved;
    }

    /**
     * @return list<int>
     */
    public function unscheduledPersonnelIds(): array
    {
        return Personnel::query()
            ->active()
            ->whereDoesntHave('schedules')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function replaceDay(int $personnelId, int $dayOfWeek, string $start, string $end, string $subject): Schedule
    {
        Schedule::query()
            ->where('personnel_id', $personnelId)
            ->where('day_of_week', $dayOfWeek)
            ->delete();

        return Schedule::create([
            'personnel_id' => $personnelId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $start,
            'end_time' => $end,
            'subject' => $subject,
        ]);
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    protected function uniqueIds(array $ids): array
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }

        return array_values($clean);
    }

    /**
     * @param  list<int|string>  $days
     * @return list<int>
     */
    protected function uniqueDays(array $days): array
    {
        $clean = [];
        foreach ($days as $day) {
            $day = (int) $day;
            if ($day >= 0 && $day <= 6) {
                $clean[$day] = $day;
            }
        }

        return array_values($clean);
    }

    protected function normalizeTime(string $time): string
    {
        $stamp = strtotime($time);
        if ($stamp === false) {
            throw new InvalidArgumentException('Invalid time.');
        }

        return date('H:i:s', $stamp);
    }
}
