<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleOverride extends Model
{
    protected $fillable = [
        'personnel_id',
        'date',
        'override_type',
        'start_time',
        'end_time',
        'subject',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
