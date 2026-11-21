<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocatorRequest extends Model
{
    protected $fillable = [
        'staff_user_id',
        'personnel_id',
        'position_designation',
        'date',
        'date_to',
        'time_out',
        'time_in_expected',
        'destination',
        'purpose',
        'host_of_activity',
        'fund_source',
        'nature',
        'accompanied_by',
        'contact_number',
        'remarks',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'actual_time_out',
        'actual_time_in',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'date_to' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function inclusiveDatesLabel(): string
    {
        $from = $this->date;
        $to = $this->date_to ?? $this->date;

        if (! $from) {
            return '—';
        }

        if ($to && $to->ne($from)) {
            if ($from->format('Y-m') === $to->format('Y-m')) {
                return $from->format('F j').'–'.$to->format('j, Y');
            }

            return $from->format('F j, Y').' – '.$to->format('F j, Y');
        }

        $label = $from->format('F j, Y');
        if ($this->time_out && $this->time_in_expected) {
            $label .= ' ('.$this->formattedTimeOut().' – '.$this->formattedTimeInExpected().')';
        }

        return $label;
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * @return list<string>
     */
    public function inclusiveDateStrings(): array
    {
        $from = $this->date?->copy();
        if (! $from) {
            return [];
        }

        $to = ($this->date_to ?? $this->date)->copy();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $dates = [];
        for ($day = $from; $day->lte($to); $day->addDay()) {
            $dates[] = $day->toDateString();
        }

        return $dates;
    }

    public function coversDate(string $date): bool
    {
        return in_array($date, $this->inclusiveDateStrings(), true);
    }

    public function scopeApprovedCovering(Builder $query, int $personnelId, string $from, ?string $to = null): Builder
    {
        $to ??= $from;

        return $query
            ->where('status', 'approved')
            ->where(function (Builder $query) use ($personnelId) {
                $query->where('personnel_id', $personnelId)
                    ->orWhereHas('staffUser', fn (Builder $staff) => $staff->where('personnel_id', $personnelId));
            })
            ->whereDate('date', '<=', $to)
            ->where(function (Builder $query) use ($from) {
                $query->whereDate('date_to', '>=', $from)
                    ->orWhere(function (Builder $query) use ($from) {
                        $query->whereNull('date_to')->whereDate('date', '>=', $from);
                    });
            });
    }

    public function formattedTimeOut(): string
    {
        return $this->formatTime($this->time_out);
    }

    public function formattedTimeInExpected(): string
    {
        return $this->formatTime($this->time_in_expected);
    }

    public function slipNumber(): string
    {
        return 'LS-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    protected function formatTime(?string $time): string
    {
        if (! $time) {
            return '—';
        }

        return date('g:i A', strtotime($time));
    }
}
