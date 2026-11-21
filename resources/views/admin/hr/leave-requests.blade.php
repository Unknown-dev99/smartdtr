@extends('layouts.admin')

@section('title', 'Leave Requests - SMARTDTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Leave Request Queue</h1>
    <p class="text-slate-500 text-sm">CS Form No. 6 Application for Leave. Mark a request pending Division Office approval while the Schools Division Office is still reviewing it.</p>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>
                        <span class="font-medium">{{ $req->staffUser->name }}</span><br>
                        <span class="text-xs text-slate-400">{{ $req->position ?: $req->staffUser->roleLabel() }}</span>
                    </td>
                    <td class="text-sm">{{ $req->leave_type }}</td>
                    <td>{{ $req->date_from->format('M d') }} – {{ $req->date_to->format('M d, Y') }}<br><span class="text-xs text-slate-400">{{ $req->dayCount() }} day(s)</span></td>
                    <td class="max-w-xs text-sm">{{ Str::limit($req->reason, 80) }}</td>
                    <td><span class="badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
                    <td>
                        @if($req->awaitsDecision())
                        <div class="flex flex-col gap-2 min-w-[180px]">
                            <form method="POST" action="{{ route('admin.hr.leave.approve', $req) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Optional note" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-success text-xs w-full py-1">Approve</button>
                            </form>
                            @if($req->status === 'pending')
                            <form method="POST" action="{{ route('admin.hr.leave.division', $req) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Note for the employee" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-secondary text-xs w-full py-1">Pending Division Office</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.hr.leave.reject', $req) }}">
                                @csrf
                                <input type="text" name="admin_notes" placeholder="Rejection reason" class="form-input text-xs mb-1 py-1">
                                <button type="submit" class="btn-danger text-xs w-full py-1">Reject</button>
                            </form>
                        </div>
                        @else
                        <div class="text-xs text-slate-400 space-y-1">
                            <div>{{ $req->reviewer?->username ?? '—' }}<br>{{ $req->reviewed_at?->format('M d, Y') }}</div>
                            @if($req->status === 'approved')
                            <div class="flex flex-col gap-1 mt-1">
                                <a href="{{ route('admin.hr.leave.print', $req) }}" class="btn-secondary btn-sm inline-block text-center">Excel</a>
                                <a href="{{ route('admin.hr.leave.pdf', $req) }}" class="btn-danger btn-sm inline-block text-center">PDF</a>
                            </div>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No leave requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(session('auto_download_excel'))
<iframe src="{{ session('auto_download_excel') }}" class="hidden" title="Download Application for Leave"></iframe>
@endif
@endsection
