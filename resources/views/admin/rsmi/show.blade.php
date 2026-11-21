@extends('layouts.admin')

@section('title', $report->reportNumber().' - RSMI')

@section('main_class', 'page-shell-wide')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">{{ $report->reportNumber() }}</h1>
        <p class="text-slate-500 text-sm">{{ $report->monthLabel() }} — {{ $report->entity_name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.rsmi.export', $report) }}" class="btn-success">Open in Excel & Print</a>
        <a href="{{ route('admin.rsmi.index') }}" class="btn-secondary">Back to list</a>
    </div>
</div>

<div class="space-y-6">
    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Report Header</h2>
            <form method="POST" action="{{ route('admin.rsmi.destroy', $report) }}" onsubmit="return confirm('Delete this RSMI report?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">Delete Report</button>
            </form>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('admin.rsmi.update', $report) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 xl:col-span-1">
                        <label class="form-label">Entity Name</label>
                        <input type="text" name="entity_name" value="{{ old('entity_name', $report->entity_name) }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Fund Cluster</label>
                        <input type="text" name="fund_cluster" value="{{ old('fund_cluster', $report->fund_cluster) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Serial No.</label>
                        <input type="text" name="serial_no" value="{{ old('serial_no', $report->serial_no) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Report Date</label>
                        <input type="date" name="report_date" value="{{ old('report_date', $report->report_date->toDateString()) }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Supply Custodian</label>
                        <input type="text" name="supply_custodian" value="{{ old('supply_custodian', $report->supply_custodian) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Accounting Staff</label>
                        <input type="text" name="accounting_staff" value="{{ old('accounting_staff', $report->accounting_staff) }}" class="form-input">
                    </div>
                    <div class="sm:col-span-2 xl:col-span-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-input">{{ old('notes', $report->notes) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Save Header</button>
            </form>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header flex items-center justify-between">
            <h2 class="font-semibold">Issued Items</h2>
            <span class="text-sm text-slate-500">Total: ₱{{ number_format($report->totalAmount(), 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table w-full">
                <thead>
                    <tr>
                        <th>RIS No.</th>
                        <th>Resp. Center</th>
                        <th>Stock No.</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report->items as $item)
                    <tr>
                        <td class="text-xs">
                            {{ $item->ris_no ?: '—' }}
                            @if($item->ris_item_id)
                                <span class="ml-1 text-[10px] uppercase tracking-wide text-emerald-700">from RIS</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $item->responsibility_center ?: '—' }}</td>
                        <td class="text-xs">{{ $item->stock_no ?: '—' }}</td>
                        <td class="text-sm font-medium">{{ $item->item }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ number_format($item->quantity, 2) }}</td>
                        <td>₱{{ number_format($item->unit_cost, 2) }}</td>
                        <td>₱{{ number_format($item->amount(), 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.rsmi.items.destroy', [$report, $item]) }}" onsubmit="return confirm('Remove this item?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-slate-400 py-6">No items yet. Import from RIS below, or add manually.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <h2 class="font-semibold">Import from RIS ({{ $report->monthLabel() }})</h2>
            <p class="text-xs text-slate-500 mt-1">
                Pulls issued lines from RIS slips dated in this report month. Already-imported lines are skipped.
            </p>
        </div>
        <div class="app-card-body space-y-4">
            @if($pendingRisItems->isEmpty())
                <p class="text-sm text-slate-500">
                    @if($risSlips->isEmpty())
                        No RIS slips found for {{ $report->monthLabel() }}. Create a RIS first, then return here.
                    @else
                        All issued RIS items for {{ $report->monthLabel() }} are already on an RSMI (or have no issue quantity).
                    @endif
                </p>
            @else
                <form method="POST" action="{{ route('admin.rsmi.import-ris', $report) }}">
                    @csrf
                    <div class="overflow-x-auto mb-4">
                        <table class="app-table w-full">
                            <thead>
                                <tr>
                                    <th class="w-10">
                                        <input type="checkbox" checked
                                            onchange="document.querySelectorAll('.ris-import-cb').forEach(cb => cb.checked = this.checked)">
                                    </th>
                                    <th>RIS No.</th>
                                    <th>Date</th>
                                    <th>Stock No.</th>
                                    <th>Item</th>
                                    <th>Unit</th>
                                    <th>Issue Qty</th>
                                    <th>Unit Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingRisItems as $item)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="ris-import-cb" name="ris_item_ids[]" value="{{ $item->id }}" checked>
                                    </td>
                                    <td class="text-xs font-mono">{{ $item->slip?->displayRisNo() }}</td>
                                    <td class="text-xs">{{ $item->slip?->ris_date?->format('M d, Y') }}</td>
                                    <td class="text-xs">{{ $item->stock_no ?: '—' }}</td>
                                    <td class="text-sm font-medium">{{ $item->description }}</td>
                                    <td>{{ $item->unit ?: '—' }}</td>
                                    <td>{{ number_format($item->issuedQuantity(), 2) }}</td>
                                    <td>₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary">Import selected to RSMI</button>
                        <button type="submit" name="import_all" value="1" class="btn-secondary">Import all pending</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Add Items Manually</h2>
            <span class="text-xs text-slate-500">Optional — prefer Import from RIS when possible</span>
        </div>
        <div class="app-card-body" x-data="{
            catalog: @js(config('smartdtr.supply_items', [])),
            rows: @js(old('items', [
                ['ris_no' => '', 'responsibility_center' => '', 'stock_no' => '', 'item' => '', 'unit' => '', 'quantity' => 1, 'unit_cost' => 0],
                ['ris_no' => '', 'responsibility_center' => '', 'stock_no' => '', 'item' => '', 'unit' => '', 'quantity' => 1, 'unit_cost' => 0],
                ['ris_no' => '', 'responsibility_center' => '', 'stock_no' => '', 'item' => '', 'unit' => '', 'quantity' => 1, 'unit_cost' => 0],
            ])),
            addRow() {
                this.rows.push({ ris_no: '', responsibility_center: '', stock_no: '', item: '', unit: '', quantity: 1, unit_cost: 0 });
            },
            removeRow(index) {
                if (this.rows.length <= 1) return;
                this.rows.splice(index, 1);
            },
            pickItem(index, code) {
                this.rows[index].stock_no = code || '';
                if (code && this.catalog[code]) {
                    this.rows[index].item = this.catalog[code];
                }
            }
        }">
            <form method="POST" action="{{ route('admin.rsmi.items.store', $report) }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="app-table w-full table-fixed">
                        <colgroup>
                            <col class="w-[11%]">
                            <col class="w-[12%]">
                            <col class="w-[18%]">
                            <col>
                            <col class="w-[7%]">
                            <col class="w-[9%]">
                            <col class="w-[11%]">
                            <col class="w-12">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>RIS No.</th>
                                <th>Resp. Center</th>
                                <th>Stock code</th>
                                <th>Item</th>
                                <th>Unit</th>
                                <th>Qty</th>
                                <th>Unit Cost</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="index">
                                <tr>
                                    <td>
                                        <input type="text" :name="'items['+index+'][ris_no]'" x-model="row.ris_no" class="form-input !py-1.5 !text-sm">
                                    </td>
                                    <td>
                                        <input type="text" :name="'items['+index+'][responsibility_center]'" x-model="row.responsibility_center" class="form-input !py-1.5 !text-sm" placeholder="Supply / SHS">
                                    </td>
                                    <td>
                                        <select class="form-select !py-1.5 !text-sm" x-model="row.stock_no" @change="pickItem(index, row.stock_no)">
                                            <option value="">— code —</option>
                                            <template x-for="(name, code) in catalog" :key="code">
                                                <option :value="code" x-text="code + ' — ' + name"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="'items['+index+'][stock_no]'" :value="row.stock_no">
                                    </td>
                                    <td>
                                        <input type="text" :name="'items['+index+'][item]'" x-model="row.item" class="form-input !py-1.5 !text-sm" placeholder="Required">
                                    </td>
                                    <td>
                                        <input type="text" :name="'items['+index+'][unit]'" x-model="row.unit" class="form-input !py-1.5 !text-sm" placeholder="pc">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" :name="'items['+index+'][quantity]'" x-model="row.quantity" class="form-input form-input-num !py-1.5 !text-sm">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" :name="'items['+index+'][unit_cost]'" x-model="row.unit_cost" class="form-input form-input-num !py-1.5 !text-sm">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn-danger btn-sm" @click="removeRow(index)" :disabled="rows.length <= 1">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap gap-3 mt-4">
                    <button type="button" class="btn-secondary" @click="addRow()">+ Add row</button>
                    <button type="submit" class="btn-primary">Save Items</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
