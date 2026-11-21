@extends('layouts.staff')

@section('title', 'Staff Dashboard - SMARTDTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Welcome, {{ $staff->name }}</h1>
    <p class="text-slate-500 text-sm">{{ $staff->roleLabel() }} @if($staff->department) • {{ $staff->department }} @endif</p>
</div>

<div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card stat-card-blue">
        <p class="text-xs text-slate-500 uppercase">Today's Status</p>
        <p class="text-xl font-bold mt-1">{{ $todayStatus['status'] ?? 'No record' }}</p>
    </div>
    <div class="stat-card stat-card-green">
        <p class="text-xs text-slate-500 uppercase">Present (Month)</p>
        <p class="text-2xl font-bold mt-1">{{ $monthStats['present'] }}</p>
    </div>
    <div class="stat-card stat-card-orange">
        <p class="text-xs text-slate-500 uppercase">Late (Month)</p>
        <p class="text-2xl font-bold mt-1">{{ $monthStats['late'] }}</p>
    </div>
    <div class="stat-card stat-card-red">
        <p class="text-xs text-slate-500 uppercase">{{ $staff->isAttendanceOnly() ? 'Absent (Month)' : 'Pending Leave' }}</p>
        <p class="text-2xl font-bold mt-1">{{ $staff->isAttendanceOnly() ? $monthStats['absent'] : $pendingRequests }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="font-semibold text-primary-900">Quick Actions</h2>
        </div>
        <div class="app-card-body flex flex-wrap gap-3">
            @unless($staff->isAttendanceOnly())
            <a href="{{ route('staff.leave') }}" class="btn-primary">Request Leave</a>
            @endunless
            <a href="{{ route('staff.faces') }}" class="{{ $staff->isAttendanceOnly() ? 'btn-primary' : 'btn-secondary' }}">Register My Face</a>
            <a href="{{ route('staff.attendance') }}" class="btn-secondary">View Attendance</a>
            <a href="{{ route('staff.profile') }}" class="btn-secondary">My Profile</a>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <h2 class="font-semibold text-primary-900">Recent Scans</h2>
        </div>
        <div class="overflow-x-auto">
            @if($recentLogs->isEmpty())
                <p class="p-6 text-slate-400 text-sm">No attendance scans yet. @if(!$staff->personnel_id) Your account is not yet linked to a personnel record. @endif</p>
            @else
                <table class="app-table">
                    <thead><tr><th>Date</th><th>Time</th></tr></thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                        <tr>
                            <td>{{ $log->date->format('M d, Y') }}</td>
                            <td>{{ date('g:i A', strtotime($log->time)) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
