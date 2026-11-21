@extends('layouts.admin')

@section('title', 'Attendance Logs - SMARTDTR')

@section('content')
<x-page-header title="Attendance Logs" subtitle="All scan events for selected date" />

<form method="GET" class="filter-bar">
    <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ $date }}" class="form-input w-auto">
    </div>
    <button type="submit" class="btn-primary">Filter</button>
</form>

<div class="app-card">
    <div class="table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Personnel ID</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="font-mono text-sm">{{ date('g:i:s A', strtotime($log->time)) }}</td>
                    <td class="font-mono text-slate-400">#{{ $log->personnel_id }}</td>
                    <td class="font-semibold text-slate-900">{{ $log->personnel->name }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="empty-state-text py-8 text-center">No logs for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
