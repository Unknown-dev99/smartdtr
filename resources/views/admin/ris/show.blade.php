@extends('layouts.admin')

@section('title', $slip->displayRisNo().' - RIS')

@section('main_class', 'page-shell-wide')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">{{ $slip->displayRisNo() }}</h1>
        <p class="text-slate-500 text-sm">{{ $slip->ris_date->format('F d, Y') }} — {{ $slip->entity_name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.ris.send-to-rsmi', $slip) }}" onsubmit="return confirm('Send issued items from this RIS to the RSMI for {{ $slip->ris_date->format('F Y') }}?')">
            @csrf
            <button type="submit" class="btn-primary">Save issued items to RSMI</button>
        </form>
        <a href="{{ route('admin.ris.export', $slip) }}" class="btn-success">Open in Excel & Print</a>
        <a href="{{ route('admin.ris.index') }}" class="btn-secondary">Back to list</a>
    </div>
</div>

<div class="ris-layout">
    {{-- Left: header --}}
    <div class="app-card">
        <div class="app-card-header"><h2 class="font-semibold">RIS Header</h2></div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('admin.ris.update', $slip) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="form-label">Entity Name</label>
                    <input type="text" name="entity_name" value="{{ old('entity_name', $slip->entity_name) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Fund Cluster</label>
                    <input type="text" name="fund_cluster" value="{{ old('fund_cluster', $slip->fund_cluster) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Division</label>
                    <input type="text" name="division" value="{{ old('division', $slip->division) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Office</label>
                    <input type="text" name="office" value="{{ old('office', $slip->office) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Responsibility Center Code</label>
                    <input type="text" name="responsibility_center_code" value="{{ old('responsibility_center_code', $slip->responsibility_center_code) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">RIS No.</label>
                    <input type="text" name="ris_no" value="{{ old('ris_no', $slip->ris_no) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="ris_date" value="{{ old('ris_date', $slip->ris_date->toDateString()) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose" value="{{ old('purpose', $slip->purpose) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Purpose line 2</label>
                    <input type="text" name="purpose_line2" value="{{ old('purpose_line2', $slip->purpose_line2) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Purpose line 3</label>
                    <textarea name="purpose_line3" rows="2" class="form-input">{{ old('purpose_line3', $slip->purpose_line3) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Requested by</label>
                    <input type="text" name="requested_by" value="{{ old('requested_by', $slip->requested_by) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Requested designation</label>
                    <input type="text" name="requested_designation" value="{{ old('requested_designation', $slip->requested_designation) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Approved by</label>
                    <input type="text" name="approved_by" value="{{ old('approved_by', $slip->approved_by) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Approved designation</label>
                    <input type="text" name="approved_designation" value="{{ old('approved_designation', $slip->approved_designation) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Issued by</label>
                    <input type="text" name="issued_by" value="{{ old('issued_by', $slip->issued_by) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Issued designation</label>
                    <input type="text" name="issued_designation" value="{{ old('issued_designation', $slip->issued_designation) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Received by</label>
                    <input type="text" name="received_by" value="{{ old('received_by', $slip->received_by) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Received designation</label>
                    <input type="text" name="received_designation" value="{{ old('received_designation', $slip->received_designation) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-input">{{ old('notes', $slip->notes) }}</textarea>
                </div>
                <button type="submit" class="btn-primary w-full">Save Header</button>
            </form>

            <form method="POST" action="{{ route('admin.ris.destroy', $slip) }}" class="mt-4" data-confirm="Delete this RIS?" data-confirm-button="Delete">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger w-full">Delete RIS</button>
            </form>
        </div>
    </div>

    {{-- Right: fills the open space with saved + add-item tables --}}
    <div class="ris-layout-side space-y-6">
        <div class="app-card">
            <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold">Add Items</h2>
                    <p class="text-xs text-slate-500 mt-1">Pick a stock code — fills description. Tab across the row for fast entry.</p>
                </div>
                <span class="text-xs text-slate-500">Max {{ \App\Services\RisExportService::MAX_ITEM_ROWS }} lines</span>
            </div>
            <div class="app-card-body" x-data="risItemTable(@js(config('smartdtr.supply_items', [])), @js(old('items', [])))">
                <form method="POST" action="{{ route('admin.ris.items.store', $slip) }}">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="app-table w-full">
                            <thead>
                                <tr>
                                    <th>Stock code</th>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Available</th>
                                    <th>Issue Qty</th>
                                    <th>Unit Cost</th>
                                    <th>Remarks</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in rows" :key="index">
                                    <tr>
                                        <td class="min-w-[9rem]">
                                            <select class="form-select !py-1.5 !text-sm" x-model="row.stock_no" @change="pickItem(index, row.stock_no)">
                                                <option value="">— code —</option>
                                                <template x-for="opt in catalogOptions" :key="opt.code">
                                                    <option :value="opt.code" x-text="opt.label"></option>
                                                </template>
                                            </select>
                                            <input type="hidden" :name="'items['+index+'][stock_no]'" :value="row.stock_no">
                                        </td>
                                        <td class="w-20">
                                            <input type="text" :name="'items['+index+'][unit]'" x-model="row.unit" class="form-input !py-1.5 !text-sm" placeholder="pc">
                                        </td>
                                        <td class="min-w-[10rem]">
                                            <input type="text" :name="'items['+index+'][description]'" x-model="row.description" class="form-input !py-1.5 !text-sm" placeholder="Item name">
                                        </td>
                                        <td class="w-20">
                                            <input type="number" step="0.01" min="0" :name="'items['+index+'][quantity]'" x-model="row.quantity" class="form-input form-input-num !py-1.5 !text-sm">
                                        </td>
                                        <td class="w-24">
                                            <select :name="'items['+index+'][stock_available]'" x-model="row.stock_available" class="form-select !py-1.5 !text-sm">
                                                <option value="">—</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                            </select>
                                        </td>
                                        <td class="w-24">
                                            <input type="number" step="0.01" min="0" :name="'items['+index+'][issue_quantity]'" x-model="row.issue_quantity" class="form-input form-input-num !py-1.5 !text-sm" placeholder="auto">
                                        </td>
                                        <td class="w-24">
                                            <input type="number" step="0.01" min="0" :name="'items['+index+'][unit_cost]'" x-model="row.unit_cost" class="form-input form-input-num !py-1.5 !text-sm">
                                        </td>
                                        <td class="min-w-[7rem]">
                                            <input type="text" :name="'items['+index+'][remarks]'" x-model="row.remarks" class="form-input !py-1.5 !text-sm">
                                        </td>
                                        <td class="text-center w-10">
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

        <div class="app-card">
            <div class="app-card-header">
                <h2 class="font-semibold">Saved Requisition Items</h2>
                <p class="text-xs text-slate-500 mt-1">Issued quantities (Issue Qty / available stock) are what get saved to RSMI.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table w-full">
                    <thead>
                        <tr>
                            <th>Stock No.</th>
                            <th>Unit</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Available</th>
                            <th>Issue Qty</th>
                            <th>Unit Cost</th>
                            <th>Remarks</th>
                            <th>RSMI</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slip->items as $item)
                        <tr>
                            <td class="text-xs">{{ $item->stock_no ?: '—' }}</td>
                            <td>{{ $item->unit ?: '—' }}</td>
                            <td class="text-sm font-medium">{{ $item->description }}</td>
                            <td>{{ $item->quantity !== null ? number_format($item->quantity, 2) : '—' }}</td>
                            <td class="uppercase text-xs">{{ $item->stock_available ?: '—' }}</td>
                            <td>{{ $item->issue_quantity !== null ? number_format($item->issue_quantity, 2) : '—' }}</td>
                            <td>₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="text-sm">{{ $item->remarks ?: '—' }}</td>
                            <td class="text-xs">
                                @if($item->rsmiItem)
                                    <span class="text-emerald-700 font-medium">On RSMI</span>
                                @elseif($item->isIssued())
                                    <span class="text-amber-700">Pending</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.ris.items.destroy', [$slip, $item]) }}" data-confirm="Remove this item?" data-confirm-button="Remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-slate-400 py-6">No items saved yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function risItemTable(catalog, oldRows) {
    const blank = () => ({
        stock_no: '', unit: '', description: '', quantity: 1,
        stock_available: 'yes', issue_quantity: '', unit_cost: 0, remarks: ''
    });
    const catalogOptions = Object.keys(catalog || {}).map(code => ({
        code: String(code),
        label: code + ' — ' + catalog[code],
    }));
    let rows;
    if (Array.isArray(oldRows) && oldRows.length) {
        rows = oldRows.map(r => ({
            stock_no: r.stock_no ?? '',
            unit: r.unit ?? '',
            description: r.description ?? '',
            quantity: r.quantity ?? 1,
            stock_available: r.stock_available ?? 'yes',
            issue_quantity: r.issue_quantity ?? '',
            unit_cost: r.unit_cost ?? 0,
            remarks: r.remarks ?? '',
        }));
    } else {
        rows = [blank(), blank(), blank(), blank(), blank()];
    }
    return {
        catalog,
        catalogOptions,
        rows,
        addRow() { this.rows.push(blank()); },
        removeRow(index) {
            if (this.rows.length <= 1) return;
            this.rows.splice(index, 1);
        },
        pickItem(index, code) {
            this.rows[index].stock_no = code || '';
            if (code && this.catalog[code]) {
                this.rows[index].description = this.catalog[code];
            }
        },
    };
}
</script>
@endpush
@endsection
