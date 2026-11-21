@extends('layouts.admin')

@section('title', 'Attendance Correction - SMARTDTR')

@section('content')
<x-page-header title="Manual Attendance Correction" subtitle="Fix time records with required reason — logged in audit trail" />

<form method="GET" class="filter-bar">
    <div class="form-group">
        <label class="form-label">Personnel</label>
        <select name="personnel_id" class="form-select" onchange="this.form.submit()">
            <option value="">Select employee...</option>
            @foreach($personnel as $p)
            <option value="{{ $p->id }}" @selected($selectedId == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ $date }}" class="form-input w-auto">
    </div>
    <button type="submit" class="btn-primary">Load</button>
</form>

@if($selectedId)
<div class="app-card max-w-2xl">
    <div class="app-card-header"><h2 class="app-card-title">Correct Record</h2></div>
    <div class="app-card-body">
        @if($attendance)
        <p class="text-sm text-slate-500 mb-4">Current: In {{ $attendance->time_in ? date('g:i A', strtotime($attendance->time_in)) : '—' }} · Out {{ $attendance->time_out ? date('g:i A', strtotime($attendance->time_out)) : '—' }} · Status <span class="badge {{ statusBadgeClass($attendance->status) }}">{{ $attendance->status }}</span></p>
        @else
        <p class="text-sm text-slate-500 mb-4">No attendance record for this date. A new one will be created.</p>
        @endif

        <form method="POST" action="{{ route('admin.attendance.correction.store') }}" class="space-y-4"
            data-confirm="Save this manual attendance correction? This will be logged in the audit trail."
            data-confirm-button="Save Correction">
            @csrf
            <input type="hidden" name="personnel_id" value="{{ $selectedId }}">
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Time In</label>
                    <input type="time" name="time_in" value="{{ $attendance?->time_in ? date('H:i', strtotime($attendance->time_in)) : '' }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Time Out</label>
                    <input type="time" name="time_out" value="{{ $attendance?->time_out ? date('H:i', strtotime($attendance->time_out)) : '' }}" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reason (required)</label>
                <textarea name="reason" rows="3" required class="form-textarea" placeholder="e.g. Face scan failed, verified by HR"></textarea>
            </div>
            <button type="submit" class="btn-primary">Save Correction</button>
        </form>
    </div>
</div>
@endif
@endsection
