<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignatory;
use App\Models\RisItem;
use App\Models\RisSlip;
use App\Services\AuditLogService;
use App\Services\RisExportService;
use App\Services\RisToRsmiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RisController extends Controller
{
    public function __construct(
        protected RisExportService $exportService,
        protected RisToRsmiService $risToRsmi,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $slips = RisSlip::withCount('items')
            ->latest('ris_date')
            ->latest('id')
            ->get();

        return view('admin.ris.index', compact('slips'));
    }

    public function create(): View
    {
        $signatories = [
            'approved_name' => DocumentSignatory::resolve('assistant_principal'),
            'approved_title' => DocumentSignatory::resolve('assistant_principal', 'title'),
            'issued_name' => DocumentSignatory::resolve('ris_issuer'),
            'issued_title' => DocumentSignatory::resolve('ris_issuer', 'title'),
        ];

        return view('admin.ris.create', compact('signatories'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $this->validatedHeader($request);
        $slip = RisSlip::create([
            ...$validated,
            'created_by' => auth('admin')->id(),
        ]);

        $this->auditLog->log('ris.created', "Created RIS {$slip->displayRisNo()}", $slip);

        return redirect()
            ->route('admin.ris.show', $slip)
            ->with('success', 'RIS created. Add requisition items below.');
    }

    public function show(RisSlip $ris): View
    {
        $ris->load(['items.rsmiItem', 'creator']);

        return view('admin.ris.show', ['slip' => $ris]);
    }

    public function update(Request $request, RisSlip $ris): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $ris->update($this->validatedHeader($request));
        $this->auditLog->log('ris.updated', "Updated RIS {$ris->displayRisNo()}", $ris);

        return back()->with('success', 'RIS header updated.');
    }

    public function destroy(RisSlip $ris): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $number = $ris->displayRisNo();
        $ris->delete();
        $this->auditLog->log('ris.deleted', "Deleted RIS {$number}");

        return redirect()->route('admin.ris.index')->with('success', 'RIS deleted.');
    }

    public function storeItem(Request $request, RisSlip $ris): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.stock_no' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.stock_available' => 'nullable|in:yes,no',
            'items.*.issue_quantity' => 'nullable|numeric|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:255',
        ]);

        $rows = collect($validated['items'])
            ->filter(fn ($row) => filled(trim((string) ($row['description'] ?? ''))))
            ->values();

        if ($rows->isEmpty()) {
            return back()->with('error', 'Enter at least one item description (or pick a stock code).')->withInput();
        }

        $remaining = RisExportService::MAX_ITEM_ROWS - $ris->items()->count();
        if ($remaining <= 0) {
            return back()->with('error', 'This RIS sheet is full (max '.RisExportService::MAX_ITEM_ROWS.' lines).');
        }

        if ($rows->count() > $remaining) {
            return back()->with('error', "Only {$remaining} more line(s) can fit on this RIS sheet.")->withInput();
        }

        $lineNo = ((int) $ris->items()->max('line_no')) + 1;
        foreach ($rows as $row) {
            $ris->items()->create([
                'line_no' => $lineNo++,
                'stock_no' => $row['stock_no'] ?? null,
                'unit' => $row['unit'] ?? null,
                'description' => $row['description'],
                'quantity' => $row['quantity'] ?? 0,
                'stock_available' => $row['stock_available'] ?? null,
                'issue_quantity' => $row['issue_quantity'] ?? null,
                'unit_cost' => $row['unit_cost'] ?? 0,
                'remarks' => $row['remarks'] ?? null,
            ]);
        }

        $count = $rows->count();

        return back()->with('success', $count === 1 ? 'Item added to RIS.' : "{$count} items added to RIS.");
    }

    public function destroyItem(RisSlip $ris, RisItem $item): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        abort_unless($item->ris_slip_id === $ris->id, 404);
        $item->delete();

        return back()->with('success', 'Item removed.');
    }

    /**
     * Push this RIS's issued lines into the month's RSMI (create report if needed).
     */
    public function sendToRsmi(RisSlip $ris): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $ris->load('items');
        $issued = $ris->items->filter(fn (RisItem $item) => $item->isIssued());
        if ($issued->isEmpty()) {
            return back()->with('error', 'No issued quantities on this RIS. Set Issue Qty (or Qty with Available = Yes) first.');
        }

        $report = $this->risToRsmi->findOrCreateReportForDate(
            $ris->ris_date->copy(),
            auth('admin')->id(),
            $ris
        );

        $result = $this->risToRsmi->importIntoReport($report, $issued->pluck('id')->all(), [$ris->id]);

        if ($result['imported'] === 0) {
            return redirect()
                ->route('admin.rsmi.show', $report)
                ->with('error', 'Issued items were already on RSMI '.$report->reportNumber().', or the sheet is full.');
        }

        $this->auditLog->log(
            'ris.sent_to_rsmi',
            "Sent {$result['imported']} item(s) from {$ris->displayRisNo()} to {$report->reportNumber()}",
            $ris
        );

        return redirect()
            ->route('admin.rsmi.show', $report)
            ->with('success', $result['imported'].' issued item(s) from '.$ris->displayRisNo().' saved to RSMI '.$report->reportNumber().'.');
    }

    public function export(RisSlip $ris): BinaryFileResponse
    {
        $path = $this->exportService->generateExcel($ris);
        $filename = basename($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedHeader(Request $request): array
    {
        return $request->validate([
            'entity_name' => 'required|string|max:255',
            'fund_cluster' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'office' => 'nullable|string|max:255',
            'responsibility_center_code' => 'nullable|string|max:255',
            'ris_no' => 'nullable|string|max:100',
            'ris_date' => 'required|date',
            'purpose' => 'nullable|string|max:500',
            'purpose_line2' => 'nullable|string|max:500',
            'purpose_line3' => 'nullable|string|max:1000',
            'requested_by' => 'nullable|string|max:255',
            'requested_designation' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'approved_designation' => 'nullable|string|max:255',
            'issued_by' => 'nullable|string|max:255',
            'issued_designation' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'received_designation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}
