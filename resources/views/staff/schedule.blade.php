@extends('layouts.staff')

@section('title', 'My Schedule - SMART-DTR')

@section('content')
<x-page-header title="My Schedule" subtitle="Save your duty week in one step. Use an override only for a special class or an off-duty date." />

@if(! $personnel)
<div class="alert alert-error mb-6">
    Your staff account is not linked to a personnel record yet. Please contact HR so you can manage your schedule.
</div>
@else
<p class="text-sm text-slate-500 mb-6">Managing schedule for <strong>{{ $personnel->name }}</strong></p>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">Save duty week</h2>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('staff.schedule.store') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="form-label">Days</label>
                    <p class="text-xs text-slate-400 mb-2">Monday–Friday is already checked. One save fills the whole week.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Services\ScheduleWeekService::WEEKDAY_LABELS as $value => $label)
                            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold cursor-pointer">
                                <input type="checkbox" name="days[]" value="{{ $value }}" class="rounded border-slate-300"
                                       @checked(in_array($value, \App\Services\ScheduleWeekService::DEFAULT_DAYS, true))>
                                {{ substr($label, 0, 3) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" required class="form-input" value="07:30">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" required class="form-input" value="17:00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject / Duty</label>
                    <input type="text" name="subject" required class="form-input" value="Regular duty">
                </div>
                <button type="submit" class="btn-primary w-full">Save week</button>
            </form>
        </div>
    </div>

    <div class="app-card" x-data="{ type: 'Class' }">
        <div class="app-card-header">
            <h2 class="app-card-title">Date Override</h2>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('staff.schedule.override') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Override Type</label>
                    <select name="override_type" x-model="type" required class="form-select">
                        <option value="Class">Special Class</option>
                        <option value="Off-duty">Off-duty</option>
                    </select>
                </div>
                <div x-show="type === 'Class'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Save Override</button>
            </form>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">My Weekly Schedules</h2>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                    <tr>
                        <td>{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$s->day_of_week] }}</td>
                        <td>{{ date('g:i A', strtotime($s->start_time)) }} – {{ date('g:i A', strtotime($s->end_time)) }}</td>
                        <td>{{ $s->subject }}</td>
                        <td>
                            <form method="POST" action="{{ route('staff.schedule.destroy', $s) }}" data-confirm="Remove this schedule entry?" data-confirm-button="Remove">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state-text py-8 text-center">No weekly schedule yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">My Overrides</h2>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overrides as $o)
                    <tr>
                        <td>{{ $o->date->format('M d, Y') }}</td>
                        <td><span class="badge {{ $o->override_type === 'Off-duty' ? 'badge-offduty' : 'badge-present' }}">{{ $o->override_type }}</span></td>
                        <td class="text-sm">
                            @if($o->override_type === 'Class')
                                {{ $o->subject }}
                                @if($o->start_time && $o->end_time)
                                    <span class="text-slate-400">({{ date('g:i A', strtotime($o->start_time)) }} – {{ date('g:i A', strtotime($o->end_time)) }})</span>
                                @endif
                            @else
                                Off-duty
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('staff.schedule.override.destroy', $o) }}" data-confirm="Remove this override?" data-confirm-button="Remove">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state-text py-8 text-center">No overrides yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
