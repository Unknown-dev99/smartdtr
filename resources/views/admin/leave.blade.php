@extends('layouts.admin')

@section('title', 'Leave Management - SMARTDTR')

@section('content')
<x-page-header title="Leave Management" subtitle="Assign leave or official travel to personnel" />

<div class="grid lg:grid-cols-3 gap-6">
    <div class="app-card lg:col-span-1">
        <div class="app-card-header">
            <h2 class="app-card-title">Assign Leave / Travel</h2>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('admin.leave.store') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="form-label">Personnel</label>
                    <select name="personnel_id" required class="form-select">
                        @foreach($personnel as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="leave_type" required class="form-select">
                        <option value="Leave">Leave</option>
                        <option value="Travel">Travel</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" rows="2" class="form-textarea"></textarea>
                </div>
                <button type="submit" class="btn-primary w-full">Save</button>
            </form>
        </div>
    </div>

    <div class="app-card lg:col-span-2">
        <div class="app-card-header">
            <h2 class="app-card-title">Leave Records</h2>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Personnel</th>
                        <th>Type</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $record->date->format('M d, Y') }}</td>
                        <td class="font-semibold text-slate-900">{{ $record->personnel->name }}</td>
                        <td>
                            <span class="badge {{ statusBadgeClass($record->leave_type === 'Travel' ? 'Official Travel' : 'On Leave') }}">
                                {{ $record->leave_type === 'Travel' ? 'Official Travel' : 'On Leave' }}
                            </span>
                        </td>
                        <td class="text-slate-500">{{ $record->reason ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state-text py-8 text-center">No leave records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
