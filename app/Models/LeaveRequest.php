<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const LONG_LEAVE_MIN_DAYS = 60;

    public const LONG_LEAVE_MAX_DAYS = 105;

    /**
     * Replacement for corrupted/ghosted `leave_requests` InnoDB table.
     * Keep using this name until MySQL dictionary for `leave_requests` is purged.
     */
    protected $table = 'leave_requests_data';

    protected $fillable = [
        'staff_user_id',
        'personnel_id',
        'office_department',
        'position',
        'salary',
        'leave_type',
        'date_from',
        'date_to',
        'reason',
        'leave_details',
        'commutation',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    public static function cscLeaveTypes(): array
    {
        return [
            'Vacation Leave',
            'Mandatory/Forced Leave',
            'Sick Leave',
            'Maternity Leave',
            'Paternity Leave',
            'Special Privilege Leave',
            'Solo Parent Leave',
            'Study Leave',
            '10-Day VAWC Leave',
            'Rehabilitation Privilege',
            'Special Leave Benefits for Women',
            'Special Emergency (Calamity) Leave',
            'Adoption Leave',
            'Others',
        ];
    }

    public const CTO = 'Compensatory Time Off (CTO)';

    public const STATUS_DIVISION = 'division_pending';

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending school approval',
            self::STATUS_DIVISION => 'Pending Division Office approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'badge-approved',
            'rejected' => 'badge-rejected',
            default => 'badge-pending',
        };
    }

    public function awaitsDecision(): bool
    {
        return in_array($this->status, ['pending', self::STATUS_DIVISION], true);
    }

    /**
     * @return list<string>
     */
    public static function leaveTypesFor(?StaffUser $staff): array
    {
        $types = self::cscLeaveTypes();

        if ($staff?->isNonTeaching()) {
            $types[] = self::CTO;
        }

        return $types;
    }

    public function balanceBucket(): string
    {
        $type = strtolower($this->leave_type);

        if (str_contains($type, 'sick')) {
            return 'Sick';
        }

        if (str_contains($type, 'emergency') || str_contains($type, 'maternity') || str_contains($type, 'paternity')
            || str_contains($type, 'study') || str_contains($type, 'vawc') || str_contains($type, 'adoption')
            || str_contains($type, 'rehabilitation') || str_contains($type, 'solo') || $type === 'other' || $type === 'others'
            || $type === 'travel'
            || str_contains($type, 'compensatory') || str_contains($type, 'cto')) {
            return 'Other';
        }

        return 'Leave';
    }

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    public function dayCount(): int
    {
        return $this->date_from->diffInDays($this->date_to) + 1;
    }

    public static function inclusiveDayCount(string $from, string $to): int
    {
        return (int) \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1;
    }
}
