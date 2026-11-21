<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $table = 'schedule';

    protected $fillable = [
        'personnel_id',
        'day_of_week',
        'start_time',
        'end_time',
        'subject',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
