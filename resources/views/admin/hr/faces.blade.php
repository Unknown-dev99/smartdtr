@extends('layouts.admin')

@section('title', 'Face Enrollment - SMARTDTR')

@section('content')
<x-page-header
    title="Admin Face Enrollment"
    subtitle="Register each employee using three live, blink-verified camera captures."
/>

<div class="alert alert-info mb-6">
    <span>📷</span>
    <span>HR can enroll an employee here, or the employee can self-register from <strong>Employee Portal → Register My Face</strong> on a phone or laptop. Each capture needs a blink. Saved samples sync to the Raspberry Pi.</span>
</div>

<div class="app-card" x-data="{ q: '' }">
    <div class="app-card-header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <h2 class="app-card-title">Active Personnel</h2>
            <input type="search" x-model="q" class="form-input sm:w-72" placeholder="Search employee…">
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Employee No.</th>
                    <th>Position</th>
                    <th>Face Samples</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnel as $person)
                <tr
                    data-search="{{ strtolower(trim($person->name.' '.$person->employee_number.' '.$person->role)) }}"
                    x-show="!q.trim() || $el.dataset.search.includes(q.trim().toLowerCase())"
                >
                    <td>
                        <span class="font-semibold text-slate-900">{{ $person->name }}</span>
                    </td>
                    <td class="font-mono text-sm">{{ $person->employee_number ?: '—' }}</td>
                    <td>{{ $person->role }}</td>
                    <td>
                        <span class="badge {{ $person->faceSampleCount() === 3 ? 'badge-approved' : 'badge-pending' }}">
                            {{ $person->faceSampleCount() }} / 3
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.hr.faces.enroll', $person) }}" class="{{ $person->faceSampleCount() ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                            {{ $person->faceSampleCount() ? 'Re-enroll Face' : 'Enroll Face' }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state-text py-8 text-center">No active personnel available for enrollment.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
