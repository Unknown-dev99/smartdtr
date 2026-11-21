@extends('layouts.admin')

@section('title', 'New RIS - SMART-DTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">New Requisition and Issue Slip</h1>
    <p class="text-slate-500 text-sm">Appendix 63 — fill header first, then add stock items</p>
</div>

<div class="app-card max-w-4xl">
    <div class="app-card-body">
        <form method="POST" action="{{ route('admin.ris.store') }}" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Entity Name</label>
                    <input type="text" name="entity_name" value="{{ old('entity_name', 'INHS-SHS') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Fund Cluster</label>
                    <input type="text" name="fund_cluster" value="{{ old('fund_cluster', 'MOOE -SHS') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Division</label>
                    <input type="text" name="division" value="{{ old('division') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Office</label>
                    <input type="text" name="office" value="{{ old('office') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Responsibility Center Code</label>
                    <input type="text" name="responsibility_center_code" value="{{ old('responsibility_center_code') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">RIS No.</label>
                    <input type="text" name="ris_no" value="{{ old('ris_no') }}" class="form-input" placeholder="Auto if blank">
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="ris_date" value="{{ old('ris_date', now()->toDateString()) }}" required class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose') }}" class="form-input" placeholder="e.g. For INHS-SHS students & teachers">
            </div>
            <div>
                <label class="form-label">Purpose line 2</label>
                <input type="text" name="purpose_line2" value="{{ old('purpose_line2') }}" class="form-input" placeholder="e.g. GRADE 12 — HUMSS ADVISERS">
            </div>
            <div>
                <label class="form-label">Purpose line 3</label>
                <textarea name="purpose_line3" rows="2" class="form-input" placeholder="Names / recipients">{{ old('purpose_line3') }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Requested by</label>
                    <input type="text" name="requested_by" value="{{ old('requested_by') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Requested designation</label>
                    <input type="text" name="requested_designation" value="{{ old('requested_designation') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Approved by</label>
                    <input type="text" name="approved_by" value="{{ old('approved_by', $signatories['approved_name']) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Approved designation</label>
                    <input type="text" name="approved_designation" value="{{ old('approved_designation', $signatories['approved_title']) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Issued by</label>
                    <input type="text" name="issued_by" value="{{ old('issued_by', $signatories['issued_name']) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Issued designation</label>
                    <input type="text" name="issued_designation" value="{{ old('issued_designation', $signatories['issued_title']) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Received by</label>
                    <input type="text" name="received_by" value="{{ old('received_by') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Received designation</label>
                    <input type="text" name="received_designation" value="{{ old('received_designation') }}" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Create RIS</button>
                <a href="{{ route('admin.ris.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
