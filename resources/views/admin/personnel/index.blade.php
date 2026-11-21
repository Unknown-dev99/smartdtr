@extends('layouts.admin')

@section('title', 'Personnel - SMARTDTR')

@section('content')
<x-page-header title="Personnel Enrollment" subtitle="Manage personnel and face recognition samples for Pi device" />

<div class="app-card" x-data="{ q: '' }">
    <div class="app-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="app-card-title">Enrolled Personnel</h2>
            <label class="relative w-full sm:w-72">
                <span class="sr-only">Search personnel</span>
                <input
                    type="search"
                    x-model="q"
                    placeholder="Search name, role, ID…"
                    class="form-input w-full"
                    style="padding-left: 2.25rem"
                    autocomplete="off"
                >
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </label>
        </div>
    </div>
    <div class="table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Faces</th>
                    <th class="w-64">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($personnel as $person)
                <tr
                    class="person-row {{ !$person->is_active ? 'opacity-50' : '' }}"
                    data-search="{{ strtolower(trim($person->id.' '.$person->name.' '.$person->role.' '.($person->employee_number ?? '').' '.($person->is_active ? 'active' : 'inactive'))) }}"
                    x-show="!q.trim() || $el.dataset.search.includes(q.trim().toLowerCase())"
                >
                    <td class="font-mono text-slate-400">#{{ $person->id }}</td>
                    <td class="font-semibold text-slate-900">
                        {{ $person->name }}
                        @if(!$person->is_active)<span class="badge badge-rejected ml-1">Inactive</span>@endif
                    </td>
                    <td>{{ $person->role }}</td>
                    <td>
                        @if($person->is_active)
                        <span class="badge badge-active">Active</span>
                        @else
                        <span class="badge badge-rejected">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $person->faceSampleCount() >= 3 ? 'badge-approved' : 'badge-pending' }}">
                            {{ $person->faceSampleCount() }} / 3
                        </span>
                    </td>
                    <td>
                        @if(auth('admin')->user()->canWrite())
                        <div class="action-toolbar">
                            @if(adminCan('hr') && $person->is_active)
                            <a href="{{ route('admin.hr.faces.enroll', $person) }}" class="btn-primary btn-sm">
                                {{ $person->faceSampleCount() ? 'Re-enroll Face' : 'Enroll Face' }}
                            </a>
                            @endif
                            <a href="{{ route('admin.personnel.edit', $person) }}" class="btn-secondary btn-sm">Edit</a>
                            @if($person->is_active)
                            <form method="POST" action="{{ route('admin.personnel.deactivate', $person) }}" class="action-form"
                                data-confirm="Deactivate {{ $person->name }}? They will no longer appear in active lists."
                                data-confirm-type="danger" data-confirm-button="Deactivate">
                                @csrf
                                <button type="submit" class="btn-danger btn-sm">Deactivate</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('admin.personnel.activate', $person) }}" class="action-form"
                                data-confirm="Reactivate {{ $person->name }}?"
                                data-confirm-button="Activate">
                                @csrf
                                <button type="submit" class="btn-success btn-sm">Activate</button>
                            </form>
                            @endif
                        </div>
                        @else
                        <span class="text-slate-400 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr x-show="q.trim() && Array.from($el.parentElement.querySelectorAll('.person-row')).every(row => row.style.display === 'none')">
                    <td colspan="6" class="empty-state-text py-8 text-center">No personnel match “<span x-text="q.trim()"></span>”.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="alert alert-info mt-4">
    <span>📷</span>
    <span>Only an admin can enroll faces. The employee must complete three separate blink-verified camera captures before their samples sync to the Raspberry Pi.</span>
</div>
@endsection
