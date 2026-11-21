@extends('layouts.staff')

@section('title', 'My Profile - SMARTDTR')

@section('content')
<x-page-header title="My Profile" subtitle="Your account and personnel information" />

<x-avatar-upload :user="$staff" guard="staff" />

<div class="app-card max-w-2xl">
    <div class="app-card-header">
        <h2 class="app-card-title">Account Details</h2>
    </div>
    <div class="app-card-body space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">Name</span><p class="font-medium">{{ $staff->name }}</p></div>
            <div><span class="text-slate-500">Email</span><p class="font-medium">{{ $staff->email }}</p></div>
            <div><span class="text-slate-500">Mobile</span><p class="font-medium">{{ $staff->phone ?? '—' }}</p></div>
            <div><span class="text-slate-500">Account Type</span><p class="font-medium">{{ $staff->roleLabel() }}</p></div>
            <div><span class="text-slate-500">Department</span><p class="font-medium">{{ $staff->department ?? '—' }}</p></div>
            <div><span class="text-slate-500">Employee No.</span><p class="font-medium">{{ $staff->employee_number ?? '—' }}</p></div>
            <div><span class="text-slate-500">Account Status</span><p><span class="badge badge-{{ $staff->status === 'active' ? 'active' : 'pending' }}">{{ ucfirst($staff->status) }}</span></p></div>
            <div><span class="text-slate-500">Personnel Link</span><p class="font-medium">{{ $staff->personnel ? '#'.$staff->personnel_id.' '.$staff->personnel->name : 'Not linked' }}</p></div>
            @if($leaveCredits)
            <div><span class="text-slate-500">VL Remaining</span><p class="font-medium">{{ \App\Services\LeaveCardService::formatCredit($leaveCredits['vl']) }} days</p></div>
            <div><span class="text-slate-500">SL Remaining</span><p class="font-medium">{{ \App\Services\LeaveCardService::formatCredit($leaveCredits['sl']) }} days</p></div>
            @endif
        </div>
    </div>
</div>
@endsection
