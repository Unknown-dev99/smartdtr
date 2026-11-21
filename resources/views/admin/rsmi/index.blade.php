@extends('layouts.admin')

@section('title', 'RSMI - SMART-DTR')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">Report of Supplies and Materials Issued</h1>
        <p class="text-slate-500 text-sm">Appendix 64 (RSMI) — filled from issued RIS items (Appendix 63), then printed via Excel</p>
    </div>
    <a href="{{ route('admin.rsmi.create') }}" class="btn-primary">New RSMI Report</a>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Report No.</th>
                    <th>Serial No.</th>
                    <th>Period</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td class="font-mono text-xs">{{ $report->reportNumber() }}</td>
                    <td>{{ $report->serial_no ?: '—' }}</td>
                    <td>{{ $report->monthLabel() }}</td>
                    <td>{{ $report->items_count }}</td>
                    <td>₱{{ number_format($report->totalAmount(), 2) }}</td>
                    <td class="space-x-2 whitespace-nowrap">
                        <a href="{{ route('admin.rsmi.show', $report) }}" class="btn-secondary btn-sm">Open</a>
                        <a href="{{ route('admin.rsmi.export', $report) }}" class="btn-success btn-sm">Open in Excel & Print</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No RSMI reports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
