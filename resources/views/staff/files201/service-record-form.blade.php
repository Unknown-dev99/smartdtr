@php
    $defaults = $serviceRecordDefaults ?? [];
    $entries = old('entries', $defaults['entries'] ?? []);
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Service Record</h3>
            <p class="text-sm text-slate-500 mt-1">For non-teaching personnel. Fill in each appointment, then save and print.</p>
        </div>
        @if(($serviceRecordReady ?? false) && ($serviceRecordPrintUrl ?? null))
            <a href="{{ $serviceRecordPrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($serviceRecordAction ?? null)
        <form method="POST" action="{{ $serviceRecordAction }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="category" value="updated_service_record">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Date of birth</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $defaults['birth_date'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Place of birth</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $defaults['birth_place'] ?? '') }}" class="form-input">
                </div>
            </div>
            @foreach($entries as $index => $entry)
                <div class="grid md:grid-cols-4 gap-3 border border-slate-200 rounded-lg p-3 bg-white">
                    <div>
                        <label class="form-label">From</label>
                        <input type="text" name="entries[{{ $index }}][date_from]" value="{{ $entry['date_from'] ?? '' }}" class="form-input" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <input type="text" name="entries[{{ $index }}][date_to]" value="{{ $entry['date_to'] ?? '' }}" class="form-input" placeholder="Present">
                    </div>
                    <div>
                        <label class="form-label">Designation</label>
                        <input type="text" name="entries[{{ $index }}][designation]" value="{{ $entry['designation'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <input type="text" name="entries[{{ $index }}][status]" value="{{ $entry['status'] ?? '' }}" class="form-input" placeholder="Permanent">
                    </div>
                    <div>
                        <label class="form-label">Salary</label>
                        <input type="text" name="entries[{{ $index }}][salary]" value="{{ $entry['salary'] ?? '' }}" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Station / Office</label>
                        <input type="text" name="entries[{{ $index }}][station]" value="{{ $entry['station'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Leave without pay</label>
                        <input type="text" name="entries[{{ $index }}][lwop]" value="{{ $entry['lwop'] ?? '' }}" class="form-input">
                    </div>
                </div>
            @endforeach
            @error('entries')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn-primary">Save service record</button>
        </form>
    @elseif(! ($serviceRecordReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
