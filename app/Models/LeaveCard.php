<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCard extends Model
{
    public const DEFAULTS = [
        'employment_status' => 'Permanent',
        'unit' => 'SDO Ilagan - Isabela NHS',
        'prepared_by' => 'MARIFEL B. DALIT',
        'prepared_title' => 'Administrative Aide IV',
        'approved_by' => 'MARK CLIFF D. MADDARA',
        'approved_title' => 'Administrative Officer IV',
        'noted_by' => 'MARIE ROSE P. RAMOS',
        'noted_title' => 'Secondary School Principal IV',
    ];

    protected $fillable = [
        'personnel_id',
        'entrance_to_duty',
        'civil_status',
        'employment_status',
        'unit',
        'gsis_policy_no',
        'tin',
        'national_reference_no',
        'prepared_by',
        'prepared_title',
        'approved_by',
        'approved_title',
        'noted_by',
        'noted_title',
        'adjustments',
    ];

    protected function casts(): array
    {
        return [
            'entrance_to_duty' => 'date',
            'adjustments' => 'array',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function field(string $key): string
    {
        $value = $this->{$key};

        if ($value === null || $value === '') {
            return (string) (self::DEFAULTS[$key] ?? '');
        }

        return (string) $value;
    }
}
