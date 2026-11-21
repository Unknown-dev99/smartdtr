@extends('layouts.admin')

@section('title', 'Audit Log - SMARTDTR')

@section('content')
<x-page-header title="Audit Log" subtitle="System activity and accountability trail" />

<div class="grid gap-4 sm:grid-cols-3 mb-6">
    <div class="app-card">
        <div class="app-card-body">
            <p class="text-xs uppercase tracking-wide text-slate-400">Emails delivered</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $emailStats['sent'] }}</p>
        </div>
    </div>
    <div class="app-card">
        <div class="app-card-body">
            <p class="text-xs uppercase tracking-wide text-slate-400">Emails failed</p>
            <p class="text-2xl font-bold {{ $emailStats['failed'] ? 'text-red-600' : 'text-slate-400' }}">{{ $emailStats['failed'] }}</p>
        </div>
    </div>
    <div class="app-card">
        <div class="app-card-body">
            <p class="text-xs uppercase tracking-wide text-slate-400">Emails skipped</p>
            <p class="text-2xl font-bold {{ $emailStats['skipped'] ? 'text-amber-600' : 'text-slate-400' }}">{{ $emailStats['skipped'] }}</p>
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.audit') }}" class="{{ $filter === 'all' ? 'btn-primary' : 'btn-secondary' }}">All activity</a>
    <a href="{{ route('admin.audit', ['filter' => 'email']) }}" class="{{ $filter === 'email' ? 'btn-primary' : 'btn-secondary' }}">Email activity only</a>
</div>

<div class="app-card">
    <div class="table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-xs whitespace-nowrap">{{ $log->created_at?->format('M d, Y g:i A') }}</td>
                    <td class="text-sm">{{ $log->actor_name ?? 'System' }} <span class="text-slate-400">({{ $log->actor_type }})</span></td>
                    <td>
                        @php
                            $badge = match ($log->action) {
                                'email.sent' => 'badge-approved',
                                'email.failed' => 'badge-rejected',
                                'email.skipped' => 'badge-pending',
                                default => 'badge-pending',
                            };
                        @endphp
                        <span class="badge {{ $badge }} font-mono text-xs">{{ $log->action }}</span>
                    </td>
                    <td class="text-sm text-slate-600">{{ $log->description ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-state-text py-8 text-center">
                    {{ $filter === 'email' ? 'No emails have been sent yet.' : 'No audit entries yet.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
