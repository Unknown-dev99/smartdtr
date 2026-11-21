<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RsmiReport extends Model
{
    protected $fillable = [
        'entity_name',
        'fund_cluster',
        'serial_no',
        'report_date',
        'supply_custodian',
        'accounting_staff',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RsmiItem::class)->orderBy('line_no')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function reportNumber(): string
    {
        return 'RSMI-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function monthLabel(): string
    {
        return $this->report_date?->format('F Y') ?? '';
    }

    public function totalAmount(): float
    {
        return (float) $this->items->sum(fn (RsmiItem $item) => $item->amount());
    }
}
