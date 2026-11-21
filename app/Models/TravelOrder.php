<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrder extends Model
{
    protected $fillable = [
        'staff_user_id',
        'personnel_id',
        'position_designation',
        'permanent_station',
        'purpose',
        'host_of_activity',
        'date_from',
        'date_to',
        'destination',
        'fund_source',
        'travel_type',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function orderNumber(): string
    {
        return 'TO-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function inclusiveDatesLabel(): string
    {
        $from = $this->date_from;
        $to = $this->date_to ?? $this->date_from;

        if (! $from) {
            return '—';
        }

        if ($to && $to->ne($from)) {
            if ($from->format('Y-m') === $to->format('Y-m')) {
                return $from->format('F j').'–'.$to->format('j, Y');
            }

            return $from->format('F j, Y').' – '.$to->format('F j, Y');
        }

        return $from->format('F j, Y');
    }

    public function travelTypeLabel(): string
    {
        return match ($this->travel_type) {
            'outside' => 'Outside',
            default => 'Within',
        };
    }
}
