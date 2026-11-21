<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionDescriptionForm extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'employee_name',
        'employee_date',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'employee_date' => 'date',
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
