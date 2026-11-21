@extends('layouts.admin')

@section('title', '201 Files - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">201 Files</h1>
    <p class="text-slate-500 text-sm">Personnel document repository — appointments, PDS, SALN, clearances, and related records</p>
</div>

@include('admin.files201.partials.add-category')

<div x-data="{ q: @js($q) }">
<form class="filter-bar" @submit.prevent>
    <div class="flex-1 min-w-[200px]">
        <label class="form-label" for="personnel-search">Search personnel</label>
        <input id="personnel-search" type="search" x-model="q" class="form-input" placeholder="Name or employee number" autocomplete="off">
    </div>
</form>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Employee No.</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnel as $person)
                <tr
                    data-search="{{ strtolower(trim($person->name.' '.($person->employee_number ?? '').' '.preg_replace('/\D/', '', (string) $person->employee_number).' '.($person->role ?? '').' '.($person->is_active ? 'active' : 'inactive'))) }}"
                    x-show="!q.trim() || $el.dataset.search.includes(q.trim().toLowerCase())"
                >
                    <td class="font-medium">{{ $person->name }}</td>
                    <td class="font-mono text-xs">{{ $person->employee_number ?: '—' }}</td>
                    <td class="text-sm capitalize">{{ $person->role ?: '—' }}</td>
                    <td>
                        @if($person->is_active)
                            <span class="text-emerald-600 text-xs font-semibold">Active</span>
                        @else
                            <span class="text-slate-400 text-xs font-semibold">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $person->files201_count }}</td>
                    <td>
                        <a href="{{ route('admin.files201.show', $person) }}" class="btn-secondary btn-sm">Open 201 File</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-slate-400 py-8">No personnel found.</td></tr>
                @endforelse
                @if($personnel->isNotEmpty())
                <tr x-show="q.trim() !== '' && ![...$root.querySelectorAll('[data-search]')].some(row => row.dataset.search.includes(q.trim().toLowerCase()))">
                    <td colspan="6" class="text-center text-slate-400 py-8">No personnel found.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
