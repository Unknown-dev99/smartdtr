@extends('layouts.admin')

@section('title', 'Schedule - SMARTDTR')

@section('content')
<x-page-header title="Schedule Management" subtitle="Save a whole duty week in one step. Attendance uses these hours for Present, Late, Absent, and Off-duty." />

<div class="app-card mb-6" x-data="{ copyTo: 'unscheduled' }">
    <div class="app-card-header">
        <h2 class="app-card-title">Fast school week</h2>
    </div>
    <div class="app-card-body grid lg:grid-cols-2 gap-8">
        <div>
            <p class="text-sm text-slate-500 mb-4">
                {{ $unscheduledCount }} employee{{ $unscheduledCount === 1 ? '' : 's' }} still {{ $unscheduledCount === 1 ? 'has' : 'have' }} no weekly schedule.
                One click applies <strong>Monday–Friday, 7:30 AM–5:00 PM, Regular duty</strong>. People who already have a week are left unchanged.
            </p>
            <form method="POST" action="{{ route('admin.schedule.apply-default') }}"
                  data-confirm="Apply Mon–Fri 7:30 AM–5:00 PM Regular duty to every employee who has no schedule yet?"
                  data-confirm-button="Apply">
                @csrf
                <button type="submit" class="btn-primary" @disabled($unscheduledCount === 0)>
                    Apply school week to {{ $unscheduledCount }} unscheduled {{ $unscheduledCount === 1 ? 'employee' : 'employees' }}
                </button>
            </form>
        </div>
        <div>
            <p class="text-sm text-slate-500 mb-4">Copy one person’s saved week onto others (same days and times).</p>
            <form method="POST" action="{{ route('admin.schedule.copy') }}" class="space-y-3">
                @csrf
                <div class="form-group">
                    <label class="form-label">Copy from</label>
                    <select name="source_personnel_id" required class="form-select" @disabled($personnelWithSchedules->isEmpty())>
                        @forelse($personnelWithSchedules as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @empty
                            <option value="">Save a week first</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Copy to</label>
                    <select name="copy_to" x-model="copyTo" class="form-select">
                        <option value="unscheduled">Everyone with no schedule yet</option>
                        <option value="selected">Selected employees below</option>
                    </select>
                </div>
                <div x-show="copyTo === 'selected'" x-cloak class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 p-3 space-y-1">
                    @foreach($personnel as $p)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="personnel_ids[]" value="{{ $p->id }}" class="rounded border-slate-300">
                            {{ $p->name }}
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn-secondary" @disabled($personnelWithSchedules->isEmpty())>Copy week</button>
            </form>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="app-card" x-data>
        <div class="app-card-header">
            <h2 class="app-card-title">Save a duty week</h2>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('admin.schedule.store') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <label class="form-label mb-0">Employees</label>
                        <button type="button" class="text-xs font-semibold text-blue-700"
                                @click="$refs.people.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = true)">
                            Select all
                        </button>
                    </div>
                    <div x-ref="people" class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 p-3 space-y-1">
                        @foreach($personnel as $p)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="personnel_ids[]" value="{{ $p->id }}" class="rounded border-slate-300">
                                {{ $p->name }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Check one or many. Saving replaces those weekdays for the selected people.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Days</label>
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
            <form method="POST" action="{{ route('admin.schedule.override') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="form-label">Personnel</label>
                    <select name="personnel_id" required class="form-select">
                        @foreach($personnel as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
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
            <h2 class="app-card-title">Weekly Schedules</h2>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                    <tr>
                        <td class="font-semibold text-slate-900">{{ $s->personnel->name }}</td>
                        <td>{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$s->day_of_week] }}</td>
                        <td>{{ date('g:i A', strtotime($s->start_time)) }} – {{ date('g:i A', strtotime($s->end_time)) }}</td>
                        <td>{{ $s->subject }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state-text py-8 text-center">No schedules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">Recent Overrides</h2>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Personnel</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overrides as $o)
                    <tr>
                        <td>{{ $o->date->format('M d, Y') }}</td>
                        <td class="font-semibold text-slate-900">{{ $o->personnel->name }}</td>
                        <td><span class="badge {{ $o->override_type === 'Off-duty' ? 'badge-offduty' : 'badge-present' }}">{{ $o->override_type }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-state-text py-8 text-center">No overrides yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
