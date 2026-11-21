<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function __construct(
        protected DailyStatusService $dailyStatusService
    ) {}

    /**
     * @param  array{personnel_id:int,date?:string,time?:string,date_override?:string,blink_detected:bool,face_confidence:float}  $data
     * @return array{success:bool,message?:string,data?:array}
     */
    public function processScan(array $data): array
    {
        if ($data['blink_detected'] !== true) {
            Log::warning('Scan rejected: blink not detected', $data);

            return ['success' => false, 'message' => 'Blink not detected', 'status' => 403];
        }

        $minConfidence = config('smartdtr.face_confidence_min');
        if (($data['face_confidence'] ?? 0) < $minConfidence) {
            Log::warning('Scan rejected: low face confidence', $data);

            return ['success' => false, 'message' => 'Face confidence below minimum threshold', 'status' => 422];
        }

        if (! Personnel::whereKey($data['personnel_id'])->exists()) {
            return ['success' => false, 'message' => 'Personnel not found', 'status' => 404];
        }

        $date = isset($data['date_override']) && $data['date_override']
            ? Carbon::parse($data['date_override'])->toDateString()
            : (isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : now()->toDateString());

        $time = isset($data['time'])
            ? Carbon::parse($data['time'])->format('H:i:s')
            : now()->format('H:i:s');

        return DB::transaction(function () use ($data, $date, $time) {
            $personnelId = (int) $data['personnel_id'];
            $dedupeMinutes = (int) config('smartdtr.scan_dedupe_minutes', 3);

            $attendance = Attendance::forPersonnelOnDate($personnelId, $date, lock: true);

            $alreadyAttended = $attendance && $this->isWithinDedupeWindow(
                $date,
                $this->latestPunchTime($attendance),
                $time,
                $dedupeMinutes
            );

            AttendanceLog::create([
                'personnel_id' => $personnelId,
                'date' => $date,
                'time' => $time,
                'created_at' => now(),
            ]);

            if (! $alreadyAttended) {
                if (! $attendance) {
                    Attendance::create([
                        'personnel_id' => $personnelId,
                        'date' => $date,
                        'time_in' => $time,
                        'time_out' => $time,
                        'status' => 'Unknown',
                        'updated_at' => now(),
                    ]);
                } else {
                    $timeIn = $attendance->time_in;
                    $timeOut = $attendance->time_out;

                    if (! $timeIn || $time < $timeIn) {
                        $timeIn = $time;
                    }

                    if (! $timeOut || $time > $timeOut) {
                        $timeOut = $time;
                    }

                    $attendance->update([
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'updated_at' => now(),
                    ]);
                }
            }

            $statusResult = $this->dailyStatusService->computeAndPersist($personnelId, $date);

            if ($alreadyAttended) {
                return [
                    'success' => true,
                    'message' => 'Already attended!',
                    'data' => array_merge($statusResult, [
                        'already_attended' => true,
                        'status' => 'Already attended!',
                    ]),
                ];
            }

            return [
                'success' => true,
                'message' => 'Scan recorded',
                'data' => array_merge($statusResult, [
                    'already_attended' => false,
                ]),
            ];
        });
    }

    protected function latestPunchTime(Attendance $attendance): ?string
    {
        $timeIn = $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i:s') : null;
        $timeOut = $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i:s') : null;

        if ($timeIn && $timeOut) {
            return $timeOut >= $timeIn ? $timeOut : $timeIn;
        }

        return $timeOut ?: $timeIn;
    }

    protected function isWithinDedupeWindow(string $date, ?string $existingTime, string $newTime, int $dedupeMinutes): bool
    {
        if (! $existingTime) {
            return false;
        }

        $existing = Carbon::parse($date.' '.$existingTime);
        $incoming = Carbon::parse($date.' '.$newTime);

        return abs($existing->diffInMinutes($incoming, false)) <= $dedupeMinutes;
    }

    /**
     * Dedupe scans within configured minutes window.
     *
     * @param  iterable<\App\Models\AttendanceLog>  $logs
     * @return array<int, string>
     */
    public static function dedupeScanTimes(iterable $logs): array
    {
        $times = [];
        foreach ($logs as $log) {
            $times[] = Carbon::parse($log->time)->format('H:i:s');
        }

        sort($times);

        $deduped = [];
        $dedupeMinutes = (int) config('smartdtr.scan_dedupe_minutes', 3);

        foreach ($times as $time) {
            if ($deduped === []) {
                $deduped[] = $time;
                continue;
            }

            $last = Carbon::parse('2000-01-01 '.$deduped[count($deduped) - 1]);
            $current = Carbon::parse('2000-01-01 '.$time);

            if (abs($last->diffInMinutes($current, false)) > $dedupeMinutes) {
                $deduped[] = $time;
            } else {
                $deduped[count($deduped) - 1] = $time;
            }
        }

        return $deduped;
    }

    /**
     * Replace a day's official times and scan logs so DTR export uses the correction.
     *
     * @return array{status:string,time_in:?string,time_out:?string}
     */
    public function applyManualCorrection(int $personnelId, string $date, ?string $timeIn, ?string $timeOut): array
    {
        $date = Carbon::parse($date)->toDateString();
        $timeIn = $timeIn ? Carbon::parse($timeIn)->format('H:i:s') : null;
        $timeOut = $timeOut ? Carbon::parse($timeOut)->format('H:i:s') : null;

        return DB::transaction(function () use ($personnelId, $date, $timeIn, $timeOut) {
            Attendance::updateOrCreateForDate($personnelId, $date, [
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'updated_at' => now(),
            ]);

            AttendanceLog::where('personnel_id', $personnelId)
                ->whereDate('date', $date)
                ->delete();

            $punches = [];
            if ($timeIn) {
                $punches[] = $timeIn;
            }
            if ($timeOut && $timeOut !== $timeIn) {
                $punches[] = $timeOut;
            }

            foreach ($punches as $time) {
                AttendanceLog::create([
                    'personnel_id' => $personnelId,
                    'date' => $date,
                    'time' => $time,
                    'created_at' => now(),
                ]);
            }

            return $this->dailyStatusService->computeAndPersist($personnelId, $date);
        });
    }
}
