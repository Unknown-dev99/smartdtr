<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompensatoryTimeOff extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'balance_hours',
        'entries',
        'prepared_on',
    ];

    protected function casts(): array
    {
        return [
            'prepared_on' => 'date',
            'entries' => 'array',
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
}
