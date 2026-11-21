<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelDailyStatus extends Model
{
    public $timestamps = false;

    protected $table = 'personnel_daily_status';

    protected $fillable = [
        'personnel_id',
        'date',
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

    /**
     * @param  array<string, mixed>  $values
     */
    public static function updateOrCreateForDate(int $personnelId, string $date, array $values): self
    {
        $row = static::query()
            ->where('personnel_id', $personnelId)
            ->whereDate('date', $date)
            ->first();

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
