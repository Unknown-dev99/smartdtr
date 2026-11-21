<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Personnel extends Model
{
    protected $table = 'personnel';

    protected $fillable = [
        'name',
        'role',
        'employee_number',
        'official_hours_regular',
        'official_hours_saturday',
        'face_dataset_dir',
        'is_active',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isNonTeaching(): bool
    {
        $role = strtoupper(trim((string) $this->role));

        if ($role !== '' && preg_match('/^(TCH|MT|SST|TEACHER|SUBSTITUTE)\b/', $role)) {
            return false;
        }

        if ($role !== '' && preg_match('/^(ADAS|AO|ASSP|REGISTRAR)\b/', $role)) {
            return true;
        }

        return StaffUser::query()
            ->where('personnel_id', $this->id)
            ->where('role', 'non_teaching')
            ->exists();
    }

    /**
     * Employee's Leave Card is for administrative staff. Teaching and utility staff are excluded.
     */
    public function eligibleForLeaveCard(?string $staffRole = null): bool
    {
        $role = strtoupper(trim((string) $this->role));

        if ($role !== '' && (preg_match('/\bTEACHER\b/', $role) || preg_match('/^(TCH|MT|SST|SUBSTITUTE)\b/', $role))) {
            return false;
        }

        if ($role !== '' && preg_match('/^(UTILITY|CLEAN)/', $role)) {
            return false;
        }

        if ($staffRole === null) {
            $staffRole = StaffUser::query()->where('personnel_id', $this->id)->value('role');
        }

        return ! in_array($staffRole, ['teacher', 'cleaning_staff'], true);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leaveRecords(): HasMany
    {
        return $this->hasMany(LeaveRecord::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleOverrides(): HasMany
    {
        return $this->hasMany(ScheduleOverride::class);
    }

    public function dailyStatuses(): HasMany
    {
        return $this->hasMany(PersonnelDailyStatus::class);
    }

    public function leaveBalance(): HasOne
    {
        return $this->hasOne(LeaveBalance::class);
    }

    public function leaveCard(): HasOne
    {
        return $this->hasOne(LeaveCard::class);
    }

    public function files201(): HasMany
    {
        return $this->hasMany(Personnel201File::class);
    }

    public function clearanceRequests(): HasMany
    {
        return $this->hasMany(ClearanceRequest::class);
    }

    public function workExperienceSheet(): HasOne
    {
        return $this->hasOne(WorkExperienceSheet::class);
    }

    public function positionDescriptionForm(): HasOne
    {
        return $this->hasOne(PositionDescriptionForm::class);
    }

    public function medicalCertificate(): HasOne
    {
        return $this->hasOne(MedicalCertificate::class);
    }

    public function personalDataSheet(): HasOne
    {
        return $this->hasOne(PersonalDataSheet::class);
    }

    public function serviceRecord(): HasOne
    {
        return $this->hasOne(ServiceRecord::class);
    }

    public function compensatoryTimeOff(): HasOne
    {
        return $this->hasOne(CompensatoryTimeOff::class);
    }

    /**
     * @return array{am_in:string,am_out:string,pm_in:string,pm_out:string}
     */
    public function regularHoursParts(): array
    {
        return self::parseRegularHours($this->official_hours_regular);
    }

    /**
     * @return array{in:string,out:string}
     */
    public function saturdayHoursParts(): array
    {
        return self::parseSaturdayHours($this->official_hours_saturday);
    }

    /**
     * @return array{am_in:string,am_out:string,pm_in:string,pm_out:string}
     */
    public static function parseRegularHours(?string $value): array
    {
        $defaults = ['am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00'];
        $times = self::extractClocks($value);

        if (count($times) < 4) {
            return $defaults;
        }

        return [
            'am_in' => $times[0],
            'am_out' => $times[1],
            'pm_in' => $times[2],
            'pm_out' => $times[3],
        ];
    }

    /**
     * @return array{in:string,out:string}
     */
    public static function parseSaturdayHours(?string $value): array
    {
        $defaults = ['in' => '08:00', 'out' => '12:00'];
        $times = self::extractClocks($value);

        if (count($times) < 2) {
            return $defaults;
        }

        return ['in' => $times[0], 'out' => $times[1]];
    }

    public static function composeRegularHours(?string $amIn, ?string $amOut, ?string $pmIn, ?string $pmOut): ?string
    {
        $amIn = self::normalizeClock($amIn);
        $amOut = self::normalizeClock($amOut);
        $pmIn = self::normalizeClock($pmIn);
        $pmOut = self::normalizeClock($pmOut);

        if (! $amIn && ! $amOut && ! $pmIn && ! $pmOut) {
            return null;
        }

        return self::clockToLabel($amIn).' – '.self::clockToLabel($amOut, noonAsNn: true)
            .' & '.self::clockToLabel($pmIn).' – '.self::clockToLabel($pmOut);
    }

    public static function composeSaturdayHours(?string $in, ?string $out): ?string
    {
        $in = self::normalizeClock($in);
        $out = self::normalizeClock($out);

        if (! $in && ! $out) {
            return null;
        }

        return self::clockToLabel($in).' – '.self::clockToLabel($out);
    }

    public static function normalizeClock(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $match)) {
            return sprintf('%02d:%s', (int) $match[1], $match[2]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function extractClocks(?string $value): array
    {
        if (! $value) {
            return [];
        }

        preg_match_all('/(\d{1,2}):(\d{2})\s*(AM|PM|NN)/i', $value, $matches, PREG_SET_ORDER);

        return array_values(array_filter(array_map(
            fn (array $match) => self::labelToClock($match[0]),
            $matches
        )));
    }

    public static function clockToLabel(?string $hhmm, bool $noonAsNn = false): string
    {
        if (! $hhmm || ! preg_match('/^(\d{1,2}):(\d{2})/', $hhmm, $match)) {
            return '';
        }

        $hour = (int) $match[1];
        $minute = $match[2];

        if ($noonAsNn && $hour === 12 && $minute === '00') {
            return '12:00 NN';
        }

        $ampm = $hour >= 12 ? 'PM' : 'AM';
        $hour12 = $hour % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return $hour12.':'.$minute.' '.$ampm;
    }

    public static function labelToClock(string $label): ?string
    {
        $label = trim(str_ireplace('NN', 'PM', $label));
        if (! preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $label, $match)) {
            return null;
        }

        $hour = (int) $match[1];
        $minute = $match[2];
        $ampm = strtoupper($match[3]);

        if ($ampm === 'AM') {
            $hour = $hour === 12 ? 0 : $hour;
        } elseif ($hour !== 12) {
            $hour += 12;
        }

        return sprintf('%02d:%s', $hour, $minute);
    }

    /**
     * @return array{official_hours_regular:?string,official_hours_saturday:?string}
     */
    public static function hoursFromRequest(\Illuminate\Http\Request $request): array
    {
        return [
            'official_hours_regular' => self::composeRegularHours(
                $request->input('regular_am_in'),
                $request->input('regular_am_out'),
                $request->input('regular_pm_in'),
                $request->input('regular_pm_out'),
            ),
            'official_hours_saturday' => self::composeSaturdayHours(
                $request->input('saturday_in'),
                $request->input('saturday_out'),
            ),
        ];
    }

    public function faceSampleCount(): int
    {
        $dir = $this->face_dataset_dir ?: storage_path('app/known_faces/'.$this->id);

        if (! is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($path) && preg_match('/\.(jpe?g|png)$/i', $name)) {
                $count++;
            }
        }

        return $count;
    }
}
