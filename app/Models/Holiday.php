<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'type', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public static function isNonWorkingDay(string $date): bool
    {
        return static::whereDate('date', $date)->exists();
    }
}
