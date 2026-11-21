<?php

namespace App\Models;

use App\Services\File201Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Personnel201File extends Model
{
    protected $table = 'personnel_201_files';

    protected $fillable = [
        'personnel_id',
        'category',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'notes',
        'uploaded_by_type',
        'uploaded_by_id',
        'uploaded_by_name',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function categoryLabel(): string
    {
        return app(File201Service::class)->label((string) $this->category);
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->size_bytes);
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->stored_path);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk('local')->exists($this->stored_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (Personnel201File $file) {
            if ($file->stored_path && Storage::disk('local')->exists($file->stored_path)) {
                Storage::disk('local')->delete($file->stored_path);
            }
        });
    }
}
