<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCertificate extends Model
{
    protected $fillable = [
        'personnel_id',
        'staff_user_id',
        'appointee_name',
        'details',
    ];

    protected function casts(): array
    {
        return [
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
