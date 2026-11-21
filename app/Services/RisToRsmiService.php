<?php

namespace App\Services;

use App\Models\DocumentSignatory;
use App\Models\RisItem;
use App\Models\RisSlip;
use App\Models\RsmiItem;
use App\Models\RsmiReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RisToRsmiService
{
    /**
     * Issued qty used for RSMI: issue_quantity when set; else quantity when stock is not "no".
     */
    public function issuedQuantity(RisItem $item): float
    {
        if ($item->issue_quantity !== null) {
            return max(0, (float) $item->issue_quantity);
        }

        if (strtolower((string) $item->stock_available) === 'no') {
            return 0;
        }

        return max(0, (float) $item->quantity);
    }

    /**
     * RIS slips in the same calendar month as the RSMI report date.
     *
     * @return Collection<int, RisSlip>
     */
    public function slipsForReportMonth(RsmiReport $report): Collection
    {
        $start = $report->report_date->copy()->startOfMonth()->toDateString();
        $end = $report->report_date->copy()->endOfMonth()->toDateString();

        return RisSlip::query()
            ->with(['items' => fn ($q) => $q->orderBy('line_no')->orderBy('id')])
            ->whereBetween('ris_date', [$start, $end])
            ->orderBy('ris_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Issued RIS lines not yet linked to any RSMI item.
     *
     * @return Collection<int, RisItem>
     */
    public function pendingIssuedItems(RsmiReport $report, ?array $risSlipIds = null): Collection
    {
        $alreadyImported = RsmiItem::query()
            ->whereNotNull('ris_item_id')
            ->pluck('ris_item_id')
            ->all();

        $slips = $this->slipsForReportMonth($report);
        if ($risSlipIds !== null) {
            $ids = array_map('intval', $risSlipIds);
            $slips = $slips->whereIn('id', $ids);
        }

        $pending = collect();
        foreach ($slips as $slip) {
            foreach ($slip->items as $item) {
                if (in_array($item->id, $alreadyImported, true)) {
                    continue;
                }
                if ($this->issuedQuantity($item) <= 0) {
                    continue;
                }
                $item->setRelation('slip', $slip);
                $pending->push($item);
            }
        }

        return $pending->values();
    }

    /**
     * Import issued RIS lines into an RSMI report.
     *
     * @param  list<int>|null  $risItemIds  When null, import all pending for the report month.
     * @return array{imported:int, skipped:int}
     */
    public function importIntoReport(RsmiReport $report, ?array $risItemIds = null, ?array $risSlipIds = null): array
    {
        $pending = $this->pendingIssuedItems($report, $risSlipIds);

        if ($risItemIds !== null) {
            $wanted = array_map('intval', $risItemIds);
            $pending = $pending->whereIn('id', $wanted)->values();
        }

        $remaining = RsmiExportService::MAX_ITEM_ROWS - $report->items()->count();
        if ($remaining <= 0) {
            return ['imported' => 0, 'skipped' => $pending->count()];
        }

        $toImport = $pending->take($remaining);
        $skipped = $pending->count() - $toImport->count();
        $imported = 0;

        DB::transaction(function () use ($report, $toImport, &$imported) {
            $lineNo = ((int) $report->items()->max('line_no')) + 1;

            foreach ($toImport as $item) {
                /** @var RisItem $item */
                $slip = $item->slip ?? $item->slip()->first();
                if (! $slip) {
                    continue;
                }

                // Skip if another concurrent import already linked this RIS line.
                if (RsmiItem::where('ris_item_id', $item->id)->exists()) {
                    continue;
                }

                $report->items()->create([
                    'line_no' => $lineNo++,
                    'ris_item_id' => $item->id,
                    'ris_no' => $slip->displayRisNo(),
                    'responsibility_center' => $slip->responsibility_center_code,
                    'stock_no' => $item->stock_no,
                    'item' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $this->issuedQuantity($item),
                    'unit_cost' => (float) ($item->unit_cost ?? 0),
                ]);
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Find or create an RSMI report for the calendar month of a RIS date.
     */
    public function findOrCreateReportForDate(Carbon $date, ?int $createdBy = null, ?RisSlip $fromSlip = null): RsmiReport
    {
        $start = $date->copy()->startOfMonth()->toDateString();
        $end = $date->copy()->endOfMonth()->toDateString();

        $existing = RsmiReport::query()
            ->whereBetween('report_date', [$start, $end])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return RsmiReport::create([
            'entity_name' => $fromSlip?->entity_name ?: 'Isabela National High School',
            'fund_cluster' => $fromSlip?->fund_cluster,
            'serial_no' => null,
            'report_date' => $date->copy()->endOfMonth()->toDateString(),
            'supply_custodian' => DocumentSignatory::resolve('supply_custodian'),
            'accounting_staff' => DocumentSignatory::resolve('accounting_staff'),
            'notes' => 'Auto-created from RIS issuances for '.$date->format('F Y'),
            'created_by' => $createdBy,
        ]);
    }
}
