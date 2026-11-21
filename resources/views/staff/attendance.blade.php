@extends('layouts.staff')

@section('title', 'My Attendance - SMARTDTR')

@section('content')
<div class="mb-6 flex flex-wrap justify-between items-end gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">My Attendance</h1>
        <p class="text-slate-500 text-sm">Daily time records from face scan device</p>
    </div>
    <form method="GET" class="flex gap-2 items-end">
        <div>
            <label class="form-label">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">View</button>
    </form>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        @if(!$staff->personnel_id)
            <p class="p-8 text-center text-slate-400">Your account is not linked to a personnel record yet. Contact HR.</p>
        @elseif($attendance->isEmpty() && $logs->isEmpty())
            <p class="p-8 text-center text-slate-400">No attendance records for this month.</p>
        @else
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Scans</th>
                    </tr>
                </thead>
                <tbody>
                    @php $days = \Carbon\Carbon::parse($month.'-01')->daysInMonth; @endphp
                    @for($d = 1; $d <= $days; $d++)
                        @php
                            $dateKey = \Carbon\Carbon::parse($month.'-01')->day($d)->format('Y-m-d');
                            $record = $attendance->get($dateKey);
                            $dayLogs = $logs->get($dateKey, collect());
                        @endphp
                        @if($record || $dayLogs->isNotEmpty())
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($dateKey)->format('M d, Y (D)') }}</td>
                            <td>
                                @if($record)
                                    <span class="badge {{ statusBadgeClass($record->status) }}">{{ $record->status }}</span>
                                @else — @endif
                            </td>
                            <td>{{ $record?->time_in ? date('g:i A', strtotime($record->time_in)) : '—' }}</td>
                            <td>{{ $record?->time_out ? date('g:i A', strtotime($record->time_out)) : '—' }}</td>
                            <td class="text-xs text-slate-500">
                                @foreach($dayLogs as $log)
                                    {{ date('g:i A', strtotime($log->time)) }}@if(!$loop->last), @endif
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    @endfor
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
