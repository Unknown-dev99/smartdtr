@extends('layouts.admin')

@section('title', 'Travel Orders - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Travel Order Queue</h1>
    <p class="text-slate-500 text-sm">Review Travel Authority for Official Local Travel — opens official Word form for printing</p>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Order No.</th>
                    <th>Staff</th>
                    <th>Inclusive Dates</th>
                    <th>Destination & Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="font-mono text-xs">{{ $order->orderNumber() }}</td>
                    <td>
                        <span class="font-medium">{{ $order->staffUser->name }}</span><br>
                        <span class="text-xs text-slate-400">{{ $order->position_designation }}</span>
                    </td>
                    <td class="text-sm">{{ $order->inclusiveDatesLabel() }}</td>
                    <td class="max-w-xs text-sm">
                        <span class="font-medium">{{ $order->destination }}</span>
                        <span class="text-xs text-slate-400">({{ $order->travelTypeLabel() }})</span><br>
                        <span class="text-slate-500">{{ Str::limit($order->purpose, 80) }}</span>
                        @if($order->host_of_activity)
                        <br><span class="text-xs text-slate-400">Host: {{ $order->host_of_activity }}</span>
                        @endif
                        @if($order->fund_source)
                        <br><span class="text-xs text-slate-400">Fund: {{ $order->fund_source }}</span>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                    <td>
                        @if($order->status === 'pending')
                        <div class="flex flex-col gap-2 min-w-[180px]">
                            <form method="POST" action="{{ route('admin.hr.travel-orders.approve', $order) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Optional note" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-success text-xs w-full py-1">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.hr.travel-orders.reject', $order) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Rejection reason" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-danger text-xs w-full py-1">Reject</button>
                            </form>
                        </div>
                        @else
                        <div class="text-xs text-slate-400 space-y-1">
                            <div>{{ $order->reviewer?->username ?? '—' }}<br>{{ $order->reviewed_at?->format('M d, Y g:i A') }}</div>
                            @if($order->status === 'approved')
                            <a href="{{ route('admin.hr.travel-orders.print', $order) }}" class="btn-secondary btn-sm inline-block">Open in Word & Print</a>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No travel order requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(session('auto_download_word'))
<iframe src="{{ session('auto_download_word') }}" class="hidden" title="Download Travel Order Word"></iframe>
@endif
@endsection
