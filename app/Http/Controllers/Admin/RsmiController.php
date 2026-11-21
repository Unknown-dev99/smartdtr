<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignatory;
use App\Models\RsmiItem;
use App\Models\RsmiReport;
use App\Services\AuditLogService;
use App\Services\RisToRsmiService;
use App\Services\RsmiExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RsmiController extends Controller
{
    public function __construct(
        protected RsmiExportService $exportService,
        protected RisToRsmiService $risToRsmi,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $reports = RsmiReport::with(['items'])
            ->withCount('items')
            ->latest('report_date')
            ->latest('id')
            ->get();

        return view('admin.rsmi.index', compact('reports'));
    }

    public function create(): View
    {
        $signatories = [
            'supply_custodian' => DocumentSignatory::resolve('supply_custodian'),
            'accounting_staff' => DocumentSignatory::resolve('accounting_staff'),
        ];

        return view('admin.rsmi.create', compact('signatories'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'fund_cluster' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:100',
            'report_date' => 'required|date',
            'supply_custodian' => 'nullable|string|max:255',
            'accounting_staff' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $report = RsmiReport::create([
            ...$validated,
            'created_by' => auth('admin')->id(),
        ]);

        $this->auditLog->log('rsmi.created', "Created RSMI {$report->reportNumber()}", $report);

        return redirect()
            ->route('admin.rsmi.show', $report)
            ->with('success', 'RSMI report created. Add issued items below.');
    }

    public function show(RsmiReport $rsmiReport): View
    {
        $rsmiReport->load(['items', 'creator']);
        $pendingRisItems = $this->risToRsmi->pendingIssuedItems($rsmiReport);
        $risSlips = $this->risToRsmi->slipsForReportMonth($rsmiReport);

        return view('admin.rsmi.show', [
            'report' => $rsmiReport,
            'pendingRisItems' => $pendingRisItems,
            'risSlips' => $risSlips,
        ]);
    }

    public function update(Request $request, RsmiReport $rsmiReport): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'fund_cluster' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:100',
            'report_date' => 'required|date',
            'supply_custodian' => 'nullable|string|max:255',
            'accounting_staff' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rsmiReport->update($validated);
        $this->auditLog->log('rsmi.updated', "Updated RSMI {$rsmiReport->reportNumber()}", $rsmiReport);

        return back()->with('success', 'RSMI header updated.');
    }

    public function destroy(RsmiReport $rsmiReport): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $number = $rsmiReport->reportNumber();
        $rsmiReport->delete();
        $this->auditLog->log('rsmi.deleted', "Deleted RSMI {$number}");

        return redirect()->route('admin.rsmi.index')->with('success', 'RSMI report deleted.');
    }

    public function storeItem(Request $request, RsmiReport $rsmiReport): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.ris_no' => 'nullable|string|max:100',
            'items.*.responsibility_center' => 'nullable|string|max:255',
            'items.*.stock_no' => 'nullable|string|max:100',
            'items.*.item' => 'nullable|string|max:255',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $rows = collect($validated['items'])
            ->filter(fn ($row) => filled(trim((string) ($row['item'] ?? ''))))
            ->values();

        if ($rows->isEmpty()) {
            return back()->with('error', 'Enter at least one item name in the table.')->withInput();
        }

        $remaining = RsmiExportService::MAX_ITEM_ROWS - $rsmiReport->items()->count();
        if ($remaining <= 0) {
            return back()->with('error', 'This RSMI sheet is full (max '.RsmiExportService::MAX_ITEM_ROWS.' items).');
        }

        if ($rows->count() > $remaining) {
            return back()->with('error', "Only {$remaining} more item(s) can fit on this RSMI sheet.")->withInput();
        }

        $lineNo = ((int) $rsmiReport->items()->max('line_no')) + 1;
        foreach ($rows as $row) {
            $rsmiReport->items()->create([
                'line_no' => $lineNo++,
                'ris_no' => $row['ris_no'] ?? null,
                'responsibility_center' => $row['responsibility_center'] ?? null,
                'stock_no' => $row['stock_no'] ?? null,
                'item' => $row['item'],
                'unit' => $row['unit'] ?? null,
                'quantity' => $row['quantity'] ?? 0,
                'unit_cost' => $row['unit_cost'] ?? 0,
            ]);
        }

        $count = $rows->count();

        return back()->with('success', $count === 1 ? 'Item added to RSMI.' : "{$count} items added to RSMI.");
    }

    public function destroyItem(RsmiReport $rsmiReport, RsmiItem $item): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        abort_unless($item->rsmi_report_id === $rsmiReport->id, 404);
        $item->delete();

        return back()->with('success', 'Item removed.');
    }

    /**
     * Pull issued RIS lines (same month) into this RSMI report.
     */
    public function importFromRis(Request $request, RsmiReport $rsmiReport): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'ris_item_ids' => 'nullable|array',
            'ris_item_ids.*' => 'integer|exists:ris_items,id',
            'ris_slip_ids' => 'nullable|array',
            'ris_slip_ids.*' => 'integer|exists:ris_slips,id',
            'import_all' => 'nullable|boolean',
        ]);

        $risItemIds = $validated['import_all'] ?? false
            ? null
            : ($validated['ris_item_ids'] ?? []);

        if ($risItemIds !== null && $risItemIds === []) {
            return back()->with('error', 'Select at least one issued RIS item to import.');
        }

        $result = $this->risToRsmi->importIntoReport(
            $rsmiReport,
            $risItemIds,
            $validated['ris_slip_ids'] ?? null
        );

        if ($result['imported'] === 0) {
            return back()->with('error', 'No new issued RIS items to import for '.$rsmiReport->monthLabel().'.');
        }

        $msg = $result['imported'].' RIS item(s) added to RSMI.';
        if ($result['skipped'] > 0) {
            $msg .= ' '.$result['skipped'].' skipped (sheet full or already imported).';
        }

        $this->auditLog->log(
            'rsmi.imported_from_ris',
            "Imported {$result['imported']} RIS item(s) into {$rsmiReport->reportNumber()}",
            $rsmiReport
        );

        return back()->with('success', $msg);
    }

    public function export(RsmiReport $rsmiReport): BinaryFileResponse
    {
        $path = $this->exportService->generateExcel($rsmiReport);
        $filename = basename($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
