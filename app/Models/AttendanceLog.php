<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'personnel_id',
        'date',
        'time',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
