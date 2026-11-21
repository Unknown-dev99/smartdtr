@extends('layouts.staff')

@section('title', 'Locator Slip - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Locator Slip (SHS)</h1>
    <p class="text-slate-500 text-sm">Official form based on LOCATOR - SHS — Permanent Station: Isabela National High School</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="app-card lg:col-span-1">
        <div class="app-card-header"><h2 class="font-semibold">New Locator Request</h2></div>
        <div class="app-card-body">
            <form method="POST" action="{{ route('staff.locator.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Position/Designation</label>
                    <input type="text" name="position_designation" value="{{ old('position_designation', $staff->department ?: $staff->roleLabel()) }}" required class="form-input" placeholder="e.g. Teacher II, AO II">
                </div>
                <div>
                    <label class="form-label">Purpose of Travel</label>
                    <textarea name="purpose" rows="3" required class="form-input" placeholder="Must be supported by attachments...">{{ old('purpose') }}</textarea>
                </div>
                <div>
                    <label class="form-label">Please Check</label>
                    <div class="flex flex-col gap-2 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="nature" value="Official Business" {{ old('nature', 'Official Business') === 'Official Business' ? 'checked' : '' }} required>
                            Official Business
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="nature" value="Official Time" {{ old('nature') === 'Official Time' ? 'checked' : '' }}>
                            Official Time
                        </label>
                    </div>
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="form-input">
                    <input type="hidden" name="date_to" value="{{ old('date_to', old('date', now()->toDateString())) }}">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Time Out</label>
                        <input type="time" name="time_out" value="{{ old('time_out') }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Expected Return</label>
                        <input type="time" name="time_in_expected" value="{{ old('time_in_expected') }}" required class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" value="{{ old('destination') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Supporting Attachment <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="form-input">
                    <p class="text-xs text-slate-400 mt-1">PDF, image, or Word — max 5MB</p>
                </div>
                <button type="submit" class="btn-primary w-full">Submit for Approval</button>
            </form>
        </div>
    </div>

    <div class="app-card lg:col-span-2">
        <div class="app-card-header"><h2 class="font-semibold">My Locator Slip Requests</h2></div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Date and Time</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td class="font-mono text-xs">{{ $req->slipNumber() }}</td>
                        <td class="text-sm">
                            {{ $req->date->format('M d, Y') }}<br>
                            <span class="text-xs text-slate-500">{{ $req->formattedTimeOut() }} – {{ $req->formattedTimeInExpected() }}</span>
                        </td>
                        <td>
                            <span class="font-medium">{{ $req->destination }}</span><br>
                            <span class="text-xs text-slate-400">{{ Str::limit($req->purpose, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
                            @if($req->admin_notes)
                            <p class="text-xs text-slate-400 mt-1">{{ $req->admin_notes }}</p>
                            @endif
                        </td>
                        <td>
                            @if($req->status === 'approved')
                            <a href="{{ route('staff.locator.print', $req) }}" class="btn-secondary btn-sm">Open in Word & Print</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">No requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
