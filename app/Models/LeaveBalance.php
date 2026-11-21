<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'personnel_id', 'vl_balance', 'vl_used', 'sl_balance', 'sl_used',
    ];

    protected function casts(): array
    {
        return [
            'vl_balance' => 'float',
            'vl_used' => 'float',
            'sl_balance' => 'float',
            'sl_used' => 'float',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function vlRemaining(): float
    {
        return max(0, $this->vl_balance - $this->vl_used);
    }

    public function slRemaining(): float
    {
        return max(0, $this->sl_balance - $this->sl_used);
    }
}
