@extends('layouts.staff')

@section('title', 'Travel Order - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Travel Order</h1>
    <p class="text-slate-500 text-sm">Travel Authority for Official Local Travel (Within & Outside) — INHS-SHS. After HR approves a request, use Print on that row to download your copy.</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="app-card lg:col-span-1">
        <div class="app-card-header"><h2 class="font-semibold">New Travel Order</h2></div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('staff.travel-orders.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Position/Designation</label>
                    <input type="text" name="position_designation" value="{{ old('position_designation', $staff->department ?: $staff->roleLabel()) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Purpose of Travel</label>
                    <textarea name="purpose" rows="3" required class="form-input" placeholder="Must be supported by attachments...">{{ old('purpose') }}</textarea>
                </div>
                <div>
                    <label class="form-label">Host of Activity</label>
                    <input type="text" name="host_of_activity" value="{{ old('host_of_activity') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Travel Type</label>
                    <div class="flex flex-col gap-2 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="travel_type" value="within" {{ old('travel_type', 'within') === 'within' ? 'checked' : '' }} required>
                            Within
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="travel_type" value="outside" {{ old('travel_type') === 'outside' ? 'checked' : '' }}>
                            Outside
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Inclusive Date From</label>
                        <input type="date" name="date_from" value="{{ old('date_from', now()->toDateString()) }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Inclusive Date To</label>
                        <input type="date" name="date_to" value="{{ old('date_to', now()->toDateString()) }}" required class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" value="{{ old('destination') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Fund Source</label>
                    <input type="text" name="fund_source" value="{{ old('fund_source') }}" required class="form-input" placeholder="e.g. Local Funds, MOOE">
                </div>
                <div>
                    <label class="form-label">Supporting Attachment <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="form-input">
                </div>
                <button type="submit" class="btn-primary w-full">Submit for Approval</button>
            </form>
        </div>
    </div>

    <div class="app-card lg:col-span-2">
        <div class="app-card-header"><h2 class="font-semibold">My Travel Orders</h2></div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Inclusive Dates</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="font-mono text-xs">{{ $order->orderNumber() }}</td>
                        <td class="text-sm">{{ $order->inclusiveDatesLabel() }}</td>
                        <td>
                            <span class="font-medium">{{ $order->destination }}</span>
                            <span class="text-xs text-slate-400">({{ $order->travelTypeLabel() }})</span><br>
                            <span class="text-xs text-slate-400">{{ Str::limit($order->purpose, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                            @if($order->admin_notes)
                            <p class="text-xs text-slate-400 mt-1">{{ $order->admin_notes }}</p>
                            @endif
                        </td>
                        <td>
                            @if($order->status === 'approved')
                            <a href="{{ route('staff.travel-orders.print', $order) }}" class="btn-primary btn-sm">Print</a>
                            @else
                            <span class="text-xs text-slate-400">Print after approval</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">No travel orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
