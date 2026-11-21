@extends('layouts.staff')

@section('title', 'Leave Requests - SMARTDTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Application for Leave</h1>
    <p class="text-slate-500 text-sm">Civil Service Form No. 6 — submit for HR approval, then download Excel or PDF to print</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="app-card lg:col-span-1">
        <div class="app-card-header"><h2 class="font-semibold">New Application</h2></div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('staff.leave.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Office / Department</label>
                    <input type="text" name="office_department" value="{{ old('office_department', $staff->department ?: 'Isabela National High School') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Position</label>
                    <input type="text" name="position" value="{{ old('position', $staff->department ?: $staff->roleLabel()) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Salary <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="salary" value="{{ old('salary') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Type of Leave</label>
                    @if($staff->isNonTeaching())
                        <p class="text-xs text-primary-800 mb-1">Non-teaching staff can choose Compensatory Time Off (CTO). It does not deduct vacation or sick leave.</p>
                    @endif
                    <select name="leave_type" required class="form-select">
                        @foreach(\App\Models\LeaveRequest::leaveTypesFor($staff) as $type)
                        <option value="{{ $type }}" @selected(old('leave_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3" data-leave-max-days="{{ \App\Models\LeaveRequest::LONG_LEAVE_MAX_DAYS }}">
                    <div>
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" id="leave-date-from" value="{{ old('date_from') }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" id="leave-date-to" value="{{ old('date_to') }}" required class="form-input">
                    </div>
                </div>
                <p id="leave-day-count" class="text-xs text-slate-500 -mt-2">Maximum {{ \App\Models\LeaveRequest::LONG_LEAVE_MAX_DAYS }} days for the 60 Days Above form.</p>
                @error('date_to')
                    <p class="text-xs text-red-600 -mt-2">{{ $message }}</p>
                @enderror
                <div>
                    <label class="form-label">Details of Leave</label>
                    <input type="text" name="leave_details" value="{{ old('leave_details') }}" class="form-input" placeholder="Within PH location / illness / purpose">
                </div>
                <div>
                    <label class="form-label">Reason</label>
                    <textarea name="reason" rows="3" required class="form-input" placeholder="Explain your request...">{{ old('reason') }}</textarea>
                </div>
                <div>
                    <label class="form-label">Commutation</label>
                    <select name="commutation" class="form-select">
                        <option value="not_requested" @selected(old('commutation', 'not_requested') === 'not_requested')>Not Requested</option>
                        <option value="requested" @selected(old('commutation') === 'requested')>Requested</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full">Submit Application</button>
            </form>
        </div>
    </div>

    <div class="app-card lg:col-span-2">
        <div class="app-card-header"><h2 class="font-semibold">My Applications</h2></div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>HR Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td class="text-sm">{{ $req->leave_type }}</td>
                        <td>{{ $req->date_from->format('M d') }} – {{ $req->date_to->format('M d, Y') }}</td>
                        <td>{{ $req->dayCount() }}</td>
                        <td><span class="badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
                        <td class="text-slate-500 text-xs">{{ $req->admin_notes ?? '—' }}</td>
                        <td>
                            @if($req->status === 'approved')
                            <div class="flex flex-col gap-1 min-w-[7.5rem]">
                                <a href="{{ route('staff.leave.print', $req) }}" class="btn-secondary btn-sm text-center">Excel</a>
                                <a href="{{ route('staff.leave.pdf', $req) }}" class="btn-danger btn-sm text-center">PDF</a>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">No requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const from = document.getElementById('leave-date-from');
    const to = document.getElementById('leave-date-to');
    const hint = document.getElementById('leave-day-count');
    const maxDays = Number(from?.closest('[data-leave-max-days]')?.dataset.leaveMaxDays || 105);

    if (!from || !to) {
        return;
    }

    const addDays = (value, days) => {
        const date = new Date(value + 'T00:00:00');
        date.setDate(date.getDate() + days);
        return date.toISOString().slice(0, 10);
    };

    const inclusiveDays = (start, end) => {
        const a = new Date(start + 'T00:00:00');
        const b = new Date(end + 'T00:00:00');
        return Math.round((b - a) / 86400000) + 1;
    };

    const applyLimit = () => {
        if (!from.value) {
            to.removeAttribute('max');
            if (hint) {
                hint.textContent = 'Maximum ' + maxDays + ' days for the 60 Days Above form.';
            }
            return;
        }

        const latest = addDays(from.value, maxDays - 1);
        to.min = from.value;
        to.max = latest;

        if (to.value && to.value > latest) {
            to.value = latest;
        }

        if (to.value && to.value < from.value) {
            to.value = from.value;
        }

        if (hint && to.value) {
            const days = inclusiveDays(from.value, to.value);
            hint.textContent = days + ' day(s) selected. Maximum ' + maxDays + ' days — the To date cannot go beyond ' + latest + '.';
        } else if (hint) {
            hint.textContent = 'Maximum ' + maxDays + ' days. To date is locked after ' + latest + '.';
        }
    };

    from.addEventListener('change', applyLimit);
    from.addEventListener('input', applyLimit);
    to.addEventListener('change', applyLimit);
    to.addEventListener('input', applyLimit);
    applyLimit();
})();
</script>
@endpush
