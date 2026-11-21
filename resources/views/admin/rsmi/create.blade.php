@extends('layouts.admin')

@section('title', 'New RSMI - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">New RSMI Report</h1>
    <p class="text-slate-500 text-sm">Appendix 64 — Report of Supplies and Materials Issued</p>
</div>

<div class="app-card max-w-3xl">
    <div class="app-card-body">
        <form method="POST" action="{{ route('admin.rsmi.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Entity Name</label>
                <input type="text" name="entity_name" value="{{ old('entity_name', 'Isabela National High School') }}" required class="form-input">
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Fund Cluster</label>
                    <input type="text" name="fund_cluster" value="{{ old('fund_cluster') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Serial No.</label>
                    <input type="text" name="serial_no" value="{{ old('serial_no') }}" class="form-input" placeholder="e.g. 2026-08-0001">
                </div>
            </div>
            <div>
                <label class="form-label">Report Month / Date</label>
                <input type="date" name="report_date" value="{{ old('report_date', now()->toDateString()) }}" required class="form-input">
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Supply / Property Custodian</label>
                    <input type="text" name="supply_custodian" value="{{ old('supply_custodian', $signatories['supply_custodian']) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Designated Accounting Staff</label>
                    <input type="text" name="accounting_staff" value="{{ old('accounting_staff', $signatories['accounting_staff']) }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Create Report</button>
                <a href="{{ route('admin.rsmi.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
