<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRecord extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'birth_date',
        'birth_place',
        'entries',
        'prepared_on',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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
