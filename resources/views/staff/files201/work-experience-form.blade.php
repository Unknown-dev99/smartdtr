@php
    $defaults = $workExperienceDefaults ?? [];
    $entries = old('entries', $defaults['entries'] ?? [[
        'duration' => '',
        'position' => '',
        'office_unit' => '',
        'supervisor' => '',
        'agency' => '',
        'accomplishments' => '',
        'duties' => '',
    ]]);
    $blank = [
        'duration' => '',
        'position' => '',
        'office_unit' => '',
        'supervisor' => '',
        'agency' => \App\Services\WorkExperienceSheetService::AGENCY,
        'accomplishments' => '',
        'duties' => '',
    ];
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Work Experience Sheet</h3>
            <p class="text-sm text-slate-500 mt-1">Instructions: 1. Include only the work experiences relevant to the position being applied for. 2. The duration should include start and finish dates, if known, month in abbreviated form, if known, and year in full. For the current position, use the word Present, e.g., 1998-Present. Work experience should be listed starting with the most recent/present employment.</p>
        </div>
        @if(($workExperienceReady ?? false) && ($workExperiencePrintUrl ?? null))
            <a href="{{ $workExperiencePrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($workExperienceAction ?? null)
        <form method="POST" action="{{ $workExperienceAction }}" class="mt-4 space-y-4"
            x-data="{ rows: @js(array_values($entries)), blank: @js($blank) }">
            @csrf
            <input type="hidden" name="category" value="work_experience_sheet">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Printed name*</label>
                    <input type="text" name="printed_name" required value="{{ old('printed_name', $defaults['printed_name'] ?? '') }}" class="form-input">
                    @error('printed_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Date*</label>
                    <input type="date" name="sheet_date" required value="{{ old('sheet_date', $defaults['sheet_date'] ?? '') }}" class="form-input">
                    @error('sheet_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <template x-for="(row, index) in rows" :key="index">
                <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800" x-text="'Experience ' + (index + 1)"></p>
                        <button type="button" class="btn-secondary btn-sm" x-show="rows.length > 1" @click="rows.splice(index, 1)">Remove</button>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Duration*</label>
                            <input type="text" :name="`entries[${index}][duration]`" x-model="row.duration" required class="form-input" placeholder="February 2011 – Present">
                        </div>
                        <div>
                            <label class="form-label">Position*</label>
                            <input type="text" :name="`entries[${index}][position]`" x-model="row.position" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Name of Office/Unit*</label>
                            <input type="text" :name="`entries[${index}][office_unit]`" x-model="row.office_unit" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Immediate Supervisor*</label>
                            <input type="text" :name="`entries[${index}][supervisor]`" x-model="row.supervisor" required class="form-input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Name of Agency/Organization and Location*</label>
                            <input type="text" :name="`entries[${index}][agency]`" x-model="row.agency" required class="form-input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">List of Accomplishments and Contributions (if any)</label>
                            <textarea :name="`entries[${index}][accomplishments]`" x-model="row.accomplishments" rows="3" class="form-input" placeholder="One accomplishment per line"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Summary of Actual Duties*</label>
                            <textarea :name="`entries[${index}][duties]`" x-model="row.duties" rows="3" required class="form-input"></textarea>
                        </div>
                    </div>
                </div>
            </template>
            @error('entries')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @foreach($errors->get('entries.*') as $messages)
                @foreach($messages as $message)
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @endforeach
            @endforeach

            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-secondary" @click="rows.push({...blank})">Add another experience</button>
                <button type="submit" class="btn-primary">Save work experience sheet</button>
            </div>
        </form>
    @elseif(! ($workExperienceReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
