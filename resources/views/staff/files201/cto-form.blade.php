@php
    $defaults = $ctoDefaults ?? [];
    $entries = old('entries', $defaults['entries'] ?? []);
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Compensatory Time Off (CTO)</h3>
            <p class="text-sm text-slate-500 mt-1">For non-teaching personnel. Record the dates and hours to be used, then save and print.</p>
        </div>
        @if(($ctoReady ?? false) && ($ctoPrintUrl ?? null))
            <a href="{{ $ctoPrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($ctoAction ?? null)
        <form method="POST" action="{{ $ctoAction }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="category" value="compensatory_time_off">
            <div class="max-w-xs">
                <label class="form-label">COC balance (hours)</label>
                <input type="text" name="balance_hours" value="{{ old('balance_hours', $defaults['balance_hours'] ?? '') }}" class="form-input" placeholder="8">
            </div>
            @foreach($entries as $index => $entry)
                <div class="grid md:grid-cols-4 gap-3 border border-slate-200 rounded-lg p-3 bg-white">
                    <div>
                        <label class="form-label">From</label>
                        <input type="date" name="entries[{{ $index }}][date_from]" value="{{ $entry['date_from'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <input type="date" name="entries[{{ $index }}][date_to]" value="{{ $entry['date_to'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Hours</label>
                        <input type="text" name="entries[{{ $index }}][hours]" value="{{ $entry['hours'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Purpose</label>
                        <input type="text" name="entries[{{ $index }}][purpose]" value="{{ $entry['purpose'] ?? '' }}" class="form-input">
                    </div>
                </div>
            @endforeach
            @error('entries')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn-primary">Save CTO</button>
        </form>
    @elseif(! ($ctoReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
