@extends('layouts.admin')

@section('title', 'Holidays - SMARTDTR')

@section('content')
<x-page-header title="Holiday Calendar" subtitle="DepEd holidays, suspensions, and non-working days" />

<div class="grid lg:grid-cols-3 gap-6">
    @if(auth('admin')->user()->canWrite())
    <div class="app-card lg:col-span-1">
        <div class="app-card-header"><h2 class="app-card-title">Add Date</h2></div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('admin.holidays.store') }}" class="space-y-4"
                data-confirm="Add this non-working date to the calendar?"
                data-confirm-button="Add">
                @csrf
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" required class="form-input" placeholder="Independence Day">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="holiday">Holiday</option>
                        <option value="suspension">Suspension</option>
                        <option value="non_working">Non-Working Day</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full">Add Holiday</button>
            </form>
        </div>
    </div>
    @endif

    <div class="app-card {{ auth('admin')->user()->canWrite() ? 'lg:col-span-2' : 'lg:col-span-3' }}">
        <div class="app-card-header"><h2 class="app-card-title">Calendar Entries</h2></div>
        <div class="table-wrap">
            <table class="app-table">
                <thead><tr><th>Date</th><th>Name</th><th>Type</th>@if(auth('admin')->user()->canWrite())<th></th>@endif</tr></thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holiday->date->format('M d, Y (D)') }}</td>
                        <td class="font-semibold">{{ $holiday->name }}</td>
                        <td><span class="badge badge-travel">{{ ucfirst(str_replace('_', ' ', $holiday->type)) }}</span></td>
                        @if(auth('admin')->user()->canWrite())
                        <td>
                            <form method="POST" action="{{ route('admin.holidays.destroy', $holiday) }}"
                                data-confirm="Remove {{ $holiday->name }} from the calendar?"
                                data-confirm-type="danger" data-confirm-button="Remove">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state-text py-8 text-center">No holidays configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
