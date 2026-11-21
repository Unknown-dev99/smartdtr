@extends('layouts.admin')

@section('title', 'Locator Slips - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Locator Slip Queue</h1>
    <p class="text-slate-500 text-sm">Review Locator Slip (SHS) requests — opens official Word form for printing</p>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Slip No.</th>
                    <th>Staff</th>
                    <th>Date / Time</th>
                    <th>Destination & Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="font-mono text-xs">{{ $req->slipNumber() }}</td>
                    <td>
                        <span class="font-medium">{{ $req->staffUser->name }}</span><br>
                        <span class="text-xs text-slate-400">{{ $req->staffUser->roleLabel() }}</span>
                        @if($req->contact_number)
                        <br><span class="text-xs text-slate-400">{{ $req->contact_number }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $req->date->format('M d, Y') }}<br>
                        <span class="text-xs text-slate-500">{{ $req->formattedTimeOut() }} – {{ $req->formattedTimeInExpected() }}</span>
                    </td>
                    <td class="max-w-xs text-sm">
                        <span class="font-medium">{{ $req->destination }}</span><br>
                        <span class="text-xs text-slate-400">{{ $req->inclusiveDatesLabel() }}</span><br>
                        <span class="text-slate-500">{{ Str::limit($req->purpose, 80) }}</span>
                        @if($req->host_of_activity)
                        <br><span class="text-xs text-slate-400">Host: {{ $req->host_of_activity }}</span>
                        @endif
                        @if($req->attachment_path)
                        <br><span class="text-xs text-primary-600">Has supporting attachment</span>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    <td>
                        @if($req->status === 'pending')
                        <div class="flex flex-col gap-2 min-w-[180px]">
                            <form method="POST" action="{{ route('admin.hr.locator.approve', $req) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Optional note" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-success text-xs w-full py-1">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.hr.locator.reject', $req) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Rejection reason" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-danger text-xs w-full py-1">Reject</button>
                            </form>
                        </div>
                        @else
                        <div class="text-xs text-slate-400 space-y-1">
                            <div>{{ $req->reviewer?->username ?? '—' }}<br>{{ $req->reviewed_at?->format('M d, Y g:i A') }}</div>
                            @if($req->status === 'approved')
                            <a href="{{ route('admin.hr.locator.print', $req) }}" class="btn-secondary btn-sm inline-block">Open in Word & Print</a>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No locator slip requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(session('auto_download_word'))
<iframe src="{{ session('auto_download_word') }}" class="hidden" title="Download Travel Authority Word"></iframe>
@endif
@endsection
