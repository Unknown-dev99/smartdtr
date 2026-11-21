<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DocumentSignatory extends Model
{
    protected $fillable = ['key', 'name', 'title', 'effective_from', 'effective_until'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    /**
     * @return array<string, array{label:string,description:string,name:?string,title:?string}>
     */
    public static function definitions(): array
    {
        return [
            'dtr_verifier' => [
                'label' => 'DTR Verifier',
                'description' => 'CS Form 48 certification / verification.',
                'name' => config('smartdtr.dtr_verifier_name', 'MARIE ROSE P. RAMOS'),
                'title' => 'Authorized Officer',
            ],
            'school_head' => [
                'label' => 'School Head',
                'description' => 'Locator slips, travel orders, and leave recommendations.',
                'name' => config('smartdtr.locator_school_head', 'PRISCA ROMELA C. BUENAVENTURA'),
                'title' => 'School Head',
            ],
            'assistant_principal' => [
                'label' => 'Assistant Principal',
                'description' => 'RIS approval and other school-level papers.',
                'name' => config('smartdtr.ris_approved_by', 'PRISCA ROMELA C. BUENAVENTURA'),
                'title' => config('smartdtr.ris_approved_designation', 'ASSISTANT PRINCIPAL II'),
            ],
            'assistant_sds' => [
                'label' => 'Assistant Schools Division Superintendent',
                'description' => 'CS Form 6 leave approval / recommendation.',
                'name' => 'CHERYL R. RAMIRO',
                'title' => 'Assistant Schools Division Superintendent',
            ],
            'schools_division_superintendent' => [
                'label' => 'Schools Division Superintendent',
                'description' => 'Division-level leave and travel papers.',
                'name' => config('smartdtr.locator_sds_name', 'EDUARDO C. ESCORPISO, JR.'),
                'title' => config('smartdtr.locator_sds_title', 'Schools Division Superintendent'),
            ],
            'ris_issuer' => [
                'label' => 'RIS Issuing Officer',
                'description' => 'Issued-by block on Appendix 63 RIS.',
                'name' => config('smartdtr.ris_issued_by', 'JONALYN B. TUDDAO'),
                'title' => config('smartdtr.ris_issued_designation', 'AO II'),
            ],
            'supply_custodian' => [
                'label' => 'Supply / Property Custodian',
                'description' => 'Supply custodian signature on Appendix 64 RSMI.',
                'name' => config('smartdtr.rsmi_supply_custodian') ?: null,
                'title' => 'Supply / Property Custodian',
            ],
            'accounting_staff' => [
                'label' => 'Designated Accounting Staff',
                'description' => 'Accounting signature on Appendix 64 RSMI.',
                'name' => config('smartdtr.rsmi_accounting_staff') ?: null,
                'title' => 'Designated Accounting Staff',
            ],
        ];
    }

    public function scopeEffectiveOn(Builder $query, mixed $date = null): Builder
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date);
            });
    }

    public static function activeRecord(string $key, mixed $date = null): ?self
    {
        return static::query()
            ->where('key', $key)
            ->effectiveOn($date)
            ->orderByRaw('effective_from IS NULL ASC')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public static function resolve(string $key, string $field = 'name', mixed $date = null): ?string
    {
        $definition = static::definitions()[$key] ?? null;
        $fallback = $definition[$field] ?? null;

        try {
            if (! Schema::hasTable('document_signatories')) {
                return $fallback;
            }

            $value = static::activeRecord($key, $date)?->{$field};

            return filled($value) ? (string) $value : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
