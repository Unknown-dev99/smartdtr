<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RsmiItem extends Model
{
    protected $fillable = [
        'rsmi_report_id',
        'ris_item_id',
        'line_no',
        'ris_no',
        'responsibility_center',
        'stock_no',
        'item',
        'unit',
        'quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'float',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(RsmiReport::class, 'rsmi_report_id');
    }

    public function risItem(): BelongsTo
    {
        return $this->belongsTo(RisItem::class, 'ris_item_id');
    }

    public function amount(): float
    {
        return round(((float) $this->quantity) * ((float) $this->unit_cost), 2);
    }
}
