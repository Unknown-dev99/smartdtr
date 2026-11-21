<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RisSlip extends Model
{
    protected $fillable = [
        'entity_name',
        'fund_cluster',
        'division',
        'office',
        'responsibility_center_code',
        'ris_no',
        'ris_date',
        'purpose',
        'purpose_line2',
        'purpose_line3',
        'requested_by',
        'requested_designation',
        'approved_by',
        'approved_designation',
        'issued_by',
        'issued_designation',
        'received_by',
        'received_designation',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ris_date' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RisItem::class)->orderBy('line_no')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function slipNumber(): string
    {
        return 'RIS-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function displayRisNo(): string
    {
        return $this->ris_no ?: $this->slipNumber();
    }
}
