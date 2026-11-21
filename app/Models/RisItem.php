<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RisItem extends Model
{
    protected $fillable = [
        'ris_slip_id',
        'line_no',
        'stock_no',
        'unit',
        'description',
        'quantity',
        'stock_available',
        'issue_quantity',
        'unit_cost',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'issue_quantity' => 'float',
            'unit_cost' => 'float',
        ];
    }

    public function slip(): BelongsTo
    {
        return $this->belongsTo(RisSlip::class, 'ris_slip_id');
    }

    public function rsmiItem(): HasOne
    {
        return $this->hasOne(RsmiItem::class, 'ris_item_id');
    }

    /**
     * Qty that should appear on RSMI when this line is issued.
     */
    public function issuedQuantity(): float
    {
        if ($this->issue_quantity !== null) {
            return max(0, (float) $this->issue_quantity);
        }

        if (strtolower((string) $this->stock_available) === 'no') {
            return 0;
        }

        return max(0, (float) $this->quantity);
    }

    public function isIssued(): bool
    {
        return $this->issuedQuantity() > 0;
    }
}
