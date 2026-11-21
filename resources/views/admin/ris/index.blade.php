@extends('layouts.admin')

@section('title', 'RIS - SMART-DTR')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">Requisition and Issue Slip</h1>
        <p class="text-slate-500 text-sm">Appendix 63 (RIS) — issue stock here, then send to RSMI for the monthly report</p>
    </div>
    <a href="{{ route('admin.ris.create') }}" class="btn-primary">New RIS</a>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>RIS No.</th>
                    <th>Date</th>
                    <th>Office</th>
                    <th>Purpose</th>
                    <th>Items</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($slips as $slip)
                <tr>
                    <td class="font-mono text-xs">{{ $slip->displayRisNo() }}</td>
                    <td>{{ $slip->ris_date->format('M d, Y') }}</td>
                    <td>{{ $slip->office ?: '—' }}</td>
                    <td class="text-sm max-w-xs truncate">{{ $slip->purpose ?: '—' }}</td>
                    <td>{{ $slip->items_count }}</td>
                    <td class="space-x-2 whitespace-nowrap">
                        <a href="{{ route('admin.ris.show', $slip) }}" class="btn-secondary btn-sm">Open</a>
                        <a href="{{ route('admin.ris.export', $slip) }}" class="btn-success btn-sm">Open in Excel & Print</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No RIS records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
