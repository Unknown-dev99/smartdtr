<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public $timestamps = false;

    protected $table = 'attendance';

    protected $fillable = [
        'personnel_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'updated_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public static function forPersonnelOnDate(int $personnelId, string $date, bool $lock = false): ?self
    {
        $query = static::query()
            ->where('personnel_id', $personnelId)
            ->whereDate('date', $date);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * SQLite stores date casts as datetime midnight, so exact-date updateOrCreate misses the row.
     *
     * @param  array<string, mixed>  $values
     */
    public static function updateOrCreateForDate(int $personnelId, string $date, array $values): self
    {
        $row = static::forPersonnelOnDate($personnelId, $date, lock: true);

        if ($row) {
            $row->fill($values);
            $row->save();

            return $row;
        }

        return static::create(array_merge($values, [
            'personnel_id' => $personnelId,
            'date' => $date,
        ]));
    }
}
