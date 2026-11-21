@extends('layouts.admin')

@section('title', 'Document Signatories - SMARTDTR')

@section('content')
<x-page-header
    title="Document Signatories"
    subtitle="Manage the officials printed on newly generated school forms and reports."
/>

<div class="alert alert-info mb-6">
    <span>📅</span>
    <span>Schedule a replacement now and it will activate automatically on the effective date. Existing RIS and RSMI records keep their original signatories.</span>
</div>

<div class="space-y-6">
    @foreach($definitions as $key => $definition)
        @php
            $current = $active[$key] ?? null;
            $entries = $schedules->get($key, collect());
            $currentName = $current?->name ?? $definition['name'];
            $currentTitle = $current?->title ?? $definition['title'];
        @endphp
        <section class="app-card overflow-hidden" data-signatory-card>
            <div class="app-card-header">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="app-card-title">{{ $definition['label'] }}</h2>
                        <span class="badge badge-active">Currently active</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ $definition['description'] }}</p>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-primary-900">{{ $currentName ?: 'Not assigned' }}</p>
                    <p class="text-xs text-slate-500">{{ $currentTitle }}</p>
                </div>
            </div>

            <div class="app-card-body">
                <div class="grid lg:grid-cols-[1fr_0.9fr] gap-6">
                    <form method="POST" action="{{ route('admin.settings.signatories.update') }}" class="space-y-4"
                        data-signatory-form
                        data-confirm="Save this signatory schedule? Please review the paper preview first."
                        data-confirm-button="Save Schedule">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="key" value="{{ $key }}">

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="{{ $key }}_name">New official’s full name</label>
                                <input
                                    id="{{ $key }}_name"
                                    name="name"
                                    type="text"
                                    maxlength="255"
                                    required
                                    value="{{ old('key') === $key ? old('name') : $currentName }}"
                                    placeholder="Enter official's full name"
                                    class="form-input"
                                    data-preview-name
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="{{ $key }}_title">Position / title</label>
                                <input
                                    id="{{ $key }}_title"
                                    name="title"
                                    type="text"
                                    maxlength="255"
                                    required
                                    value="{{ old('key') === $key ? old('title') : $currentTitle }}"
                                    placeholder="Enter official position"
                                    class="form-input"
                                    data-preview-title
                                >
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="{{ $key }}_from">Effective from</label>
                                <input
                                    id="{{ $key }}_from"
                                    name="effective_from"
                                    type="date"
                                    required
                                    value="{{ old('key') === $key ? old('effective_from') : now()->toDateString() }}"
                                    class="form-input"
                                    data-preview-date
                                >
                                <p class="text-xs text-slate-400 mt-1">Choose a future date to schedule next year’s official.</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="{{ $key }}_until">Effective until <span class="font-normal text-slate-400">(optional)</span></label>
                                <input
                                    id="{{ $key }}_until"
                                    name="effective_until"
                                    type="date"
                                    value="{{ old('key') === $key ? old('effective_until') : '' }}"
                                    class="form-input"
                                >
                                <p class="text-xs text-slate-400 mt-1">Useful for an OIC or temporary appointment.</p>
                            </div>
                        </div>

                        @if(old('key') === $key && $errors->any())
                            <div class="alert alert-error">
                                <span>Please correct the highlighted schedule details.</span>
                            </div>
                        @endif

                        <button type="submit" class="btn-primary">Previewed — Save Schedule</button>
                    </form>

                    <div>
                        <p class="form-label mb-2">Live document preview</p>
                        <div class="relative bg-slate-100 rounded-xl p-4 border border-slate-200">
                            <div class="bg-white border border-slate-300 shadow-sm min-h-56 px-6 py-5 flex flex-col text-center">
                                <div class="border-b border-slate-200 pb-3">
                                    <p class="text-[9px] tracking-[0.18em] text-slate-400 uppercase">Republic of the Philippines</p>
                                    <p class="text-xs font-bold text-primary-900 mt-1">ISABELA NATIONAL HIGH SCHOOL</p>
                                    <p class="text-[9px] text-slate-500">Official Document Preview</p>
                                </div>
                                <div class="flex-1 flex flex-col justify-end pb-2">
                                    <div class="w-48 max-w-full mx-auto border-b border-slate-700 mb-1"></div>
                                    <p class="text-sm font-bold text-slate-900 uppercase" data-preview-name-output>{{ $currentName ?: 'OFFICIAL NAME' }}</p>
                                    <p class="text-[10px] text-slate-500 uppercase mt-1" data-preview-title-output>{{ $currentTitle ?: 'POSITION / TITLE' }}</p>
                                </div>
                                <p class="text-[9px] text-slate-400 border-t border-dashed border-slate-200 pt-2">
                                    Applies starting <span class="font-semibold" data-preview-date-output>{{ now()->format('M d, Y') }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($entries->isNotEmpty())
                    <div class="mt-6 pt-5 border-t border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">Scheduled and previous appointments</h3>
                        <div class="space-y-2">
                            @foreach($entries as $entry)
                                @php
                                    $isFuture = $entry->effective_from->isFuture();
                                    $isActive = $current?->id === $entry->id;
                                @endphp
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-slate-200 px-4 py-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-sm text-slate-800">{{ $entry->name }}</span>
                                            @if($isFuture)
                                                <span class="badge badge-pending">Scheduled</span>
                                            @elseif($isActive)
                                                <span class="badge badge-active">Active</span>
                                            @else
                                                <span class="badge badge-offduty">Previous</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $entry->title }} ·
                                            {{ $entry->effective_from->format('M d, Y') }}
                                            @if($entry->effective_until) – {{ $entry->effective_until->format('M d, Y') }} @else onward @endif
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.settings.signatories.destroy', $entry) }}"
                                        data-confirm="Remove this scheduled signatory entry?"
                                        data-confirm-type="danger"
                                        data-confirm-button="Remove">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-secondary btn-sm">Remove</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>

<div class="app-card mt-6">
    <div class="app-card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-slate-700">Annual updates are automatic</p>
            <p class="text-xs text-slate-500 mt-1">The system activates each scheduled official on the chosen date—no yearly code changes needed.</p>
        </div>
        <form method="POST" action="{{ route('admin.settings.signatories.reset') }}"
            data-confirm="Remove all schedules and restore the original signatories?"
            data-confirm-type="danger"
            data-confirm-button="Restore Defaults">
            @csrf
            <button type="submit" class="btn-secondary btn-sm">Restore All Defaults</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-signatory-card]').forEach((card) => {
    const nameInput = card.querySelector('[data-preview-name]');
    const titleInput = card.querySelector('[data-preview-title]');
    const dateInput = card.querySelector('[data-preview-date]');
    const nameOutput = card.querySelector('[data-preview-name-output]');
    const titleOutput = card.querySelector('[data-preview-title-output]');
    const dateOutput = card.querySelector('[data-preview-date-output]');

    const updatePreview = () => {
        nameOutput.textContent = nameInput.value.trim() || 'OFFICIAL NAME';
        titleOutput.textContent = titleInput.value.trim() || 'POSITION / TITLE';
        if (dateInput.value) {
            const date = new Date(dateInput.value + 'T00:00:00');
            dateOutput.textContent = date.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        } else {
            dateOutput.textContent = 'SELECT A DATE';
        }
    };

    [nameInput, titleInput, dateInput].forEach((input) => input.addEventListener('input', updatePreview));
    updatePreview();
});
</script>
@endpush
@endsection
