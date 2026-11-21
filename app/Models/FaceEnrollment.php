<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FaceEnrollment extends Model
{
    protected $fillable = [
        'staff_user_id',
        'personnel_id',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    public function storageDir(): string
    {
        return (string) $this->id;
    }

    /**
     * @return list<string>
     */
    public function photoNames(): array
    {
        if (! Storage::disk('face_pending')->exists($this->storageDir())) {
            return [];
        }

        $names = [];
        foreach (Storage::disk('face_pending')->files($this->storageDir()) as $path) {
            $name = basename($path);
            if (preg_match('/\.(jpe?g|png)$/i', $name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function photoCount(): int
    {
        return count($this->photoNames());
    }
}
