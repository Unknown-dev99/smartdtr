<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalDataSheet extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'date_accomplished',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'date_accomplished' => 'date',
            'details' => 'array',
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
