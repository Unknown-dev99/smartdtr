<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkExperienceSheet extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'sheet_date',
        'printed_name',
        'entries',
    ];

    protected function casts(): array
    {
        return [
            'sheet_date' => 'date',
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
