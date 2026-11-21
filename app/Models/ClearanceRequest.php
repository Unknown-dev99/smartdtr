<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceRequest extends Model
{
    public const PURPOSE_PROMOTION = 'promotion';

    public const PURPOSE_RETIREMENT = 'retirement';

    public const PURPOSE_OTHERS = 'others';

    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'year',
        'request_date',
        'last_name',
        'first_name',
        'middle_initial',
        'home_address',
        'contact_no',
        'email',
        'present_position',
        'agency_name',
        'agency_address',
        'purpose',
        'purpose_other',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'request_date' => 'date',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class);
    }

    public function purposeLabel(): string
    {
        return match ($this->purpose) {
            self::PURPOSE_PROMOTION => 'Promotion',
            self::PURPOSE_RETIREMENT => 'Retirement',
            default => 'Others'.($this->purpose_other ? ': '.$this->purpose_other : ''),
        };
    }
}
