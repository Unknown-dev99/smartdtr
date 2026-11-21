@extends('layouts.admin')

@section('title', 'Leave Card - '.$personnel->name)

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">Employee's Leave Card</h1>
        <p class="text-slate-500 text-sm">{{ $personnel->name }} · {{ $personnel->role }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.leave-cards.index') }}" class="btn-secondary">Back</a>
        @if($card->entrance_to_duty)
        <a href="{{ route('admin.leave-cards.print', $personnel) }}" class="btn-secondary" target="_blank">Print</a>
        <a href="{{ route('admin.leave-cards.excel', $personnel) }}" class="btn-primary">Excel</a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.leave-cards.update', $personnel) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="app-card">
        <div class="app-card-body grid md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Entrance to duty</label>
                <input type="date" name="entrance_to_duty" value="{{ old('entrance_to_duty', $card->entrance_to_duty?->toDateString()) }}" required class="form-input">
                <p class="text-xs text-slate-400 mt-1">Each month earns 1.25. Twelve months earn 15 vacation leave and 15 sick leave.</p>
            </div>
            <div>
                <label class="form-label">Civil status</label>
                <input type="text" name="civil_status" value="{{ old('civil_status', $card->civil_status) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Status</label>
                <input type="text" name="employment_status" value="{{ old('employment_status', $card->field('employment_status')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Unit</label>
                <input type="text" name="unit" value="{{ old('unit', $card->field('unit')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">GSIS Policy No.</label>
                <input type="text" name="gsis_policy_no" value="{{ old('gsis_policy_no', $card->gsis_policy_no) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $card->tin) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">National Reference Card No.</label>
                <input type="text" name="national_reference_no" value="{{ old('national_reference_no', $card->national_reference_no) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Prepared by</label>
                <input type="text" name="prepared_by" value="{{ old('prepared_by', $card->field('prepared_by')) }}" class="form-input">
                <input type="text" name="prepared_title" value="{{ old('prepared_title', $card->field('prepared_title')) }}" class="form-input mt-1">
            </div>
            <div>
                <label class="form-label">Approved by</label>
                <input type="text" name="approved_by" value="{{ old('approved_by', $card->field('approved_by')) }}" class="form-input">
                <input type="text" name="approved_title" value="{{ old('approved_title', $card->field('approved_title')) }}" class="form-input mt-1">
            </div>
            <div>
                <label class="form-label">Noted by</label>
                <input type="text" name="noted_by" value="{{ old('noted_by', $card->field('noted_by')) }}" class="form-input">
                <input type="text" name="noted_title" value="{{ old('noted_title', $card->field('noted_title')) }}" class="form-input mt-1">
            </div>
        </div>
    </div>

    @if($card->entrance_to_duty)
    <div class="app-card">
        <div class="app-card-header flex justify-between gap-3">
            <h2 class="font-semibold">Monthly credits</h2>
            @if($asOf)
            <p class="text-sm text-slate-500">As of {{ now()->format('F Y') }}: VL {{ \App\Services\LeaveCardService::formatCredit($asOf['vl']) }} · SL {{ \App\Services\LeaveCardService::formatCredit($asOf['sl']) }}</p>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="app-table text-sm">
                <thead>
                    <tr>
                        <th rowspan="2">PERIOD</th>
                        <th rowspan="2">PARTICULARS</th>
                        <th colspan="4">VACATION LEAVE</th>
                        <th colspan="4">SICK LEAVE</th>
                        <th rowspan="2">REMARKS</th>
                    </tr>
                    <tr>
                        <th>EARNED</th>
                        <th>Absence Undertime W/ Pay</th>
                        <th>BALANCE</th>
                        <th>Absence Undertime W/o Pay</th>
                        <th>EARNED</th>
                        <th>Absence Undertime W/ Pay</th>
                        <th>BALANCE</th>
                        <th>Absence Undertime W/o Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($months as $row)
                    <tr>
                        <td class="whitespace-nowrap">{{ $row['period'] }}</td>
                        <td>
                            @if($row['auto_particulars'] !== '')
                            <div class="text-xs font-medium mb-1">{{ $row['auto_particulars'] }}</div>
                            @endif
                            <input type="text" name="months[{{ $row['key'] }}][particulars]" value="{{ old('months.'.$row['key'].'.particulars', $card->adjustments[$row['key']]['particulars'] ?? '') }}" class="form-input text-xs py-1" placeholder="Extra note">
                        </td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($row['earned']) }}</td>
                        <td>{{ $row['vl_with_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['vl_with_pay']) : '' }}</td>
                        <td class="font-medium">{{ \App\Services\LeaveCardService::formatCredit($row['vl_balance']) }}</td>
                        <td><input type="number" step="0.001" min="0" max="31" name="months[{{ $row['key'] }}][vl_without_pay]" value="{{ old('months.'.$row['key'].'.vl_without_pay', $row['vl_without_pay'] ?: '') }}" class="form-input text-xs py-1 w-20"></td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($row['earned']) }}</td>
                        <td>{{ $row['sl_with_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['sl_with_pay']) : '' }}</td>
                        <td class="font-medium">{{ \App\Services\LeaveCardService::formatCredit($row['sl_balance']) }}</td>
                        <td><input type="number" step="0.001" min="0" max="31" name="months[{{ $row['key'] }}][sl_without_pay]" value="{{ old('months.'.$row['key'].'.sl_without_pay', $row['sl_without_pay'] ?: '') }}" class="form-input text-xs py-1 w-20"></td>
                        <td><input type="text" name="months[{{ $row['key'] }}][remarks]" value="{{ old('months.'.$row['key'].'.remarks', $card->adjustments[$row['key']]['remarks'] ?? '') }}" class="form-input text-xs py-1 w-28"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 px-4 py-3">Approved vacation leave and forced leave are deducted from vacation leave. Approved sick leave is deducted from sick leave. Days without pay are recorded and are not subtracted from the balance.</p>
    </div>

    <div class="app-card">
        <div class="app-card-header"><h2 class="font-semibold">RECORD OF LEAVE CREDITS</h2></div>
        <div class="overflow-x-auto">
            <table class="app-table text-sm">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>VL earned</th>
                        <th>VL used</th>
                        <th>VL balance</th>
                        <th>SL earned</th>
                        <th>SL used</th>
                        <th>SL balance</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($years as $year)
                    <tr>
                        <td>{{ $year['from']->format('m/d/Y') }}</td>
                        <td>{{ $year['to']->format('m/d/Y') }}</td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($year['vl_earned']) }}</td>
                        <td>{{ $year['vl_used'] > 0 ? \App\Services\LeaveCardService::formatCredit($year['vl_used']) : '' }}</td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($year['vl_balance']) }}</td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($year['sl_earned']) }}</td>
                        <td>{{ $year['sl_used'] > 0 ? \App\Services\LeaveCardService::formatCredit($year['sl_used']) : '' }}</td>
                        <td>{{ \App\Services\LeaveCardService::formatCredit($year['sl_balance']) }}</td>
                        <td>{{ $year['remarks'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <button type="submit" class="btn-primary">Save leave card</button>
</form>
@endsection
