@props([
    'categories',
    'filesByCategory',
    'uploadAction',
    'downloadRoute',
    'destroyRoute',
    'routeParams' => [],
    'canManage' => true,
    'printUrl' => null,
    'clearanceRequests' => null,
    'clearanceDefaults' => [],
    'clearanceAction' => null,
    'clearancePrintRoute' => null,
    'workExperienceDefaults' => [],
    'workExperienceAction' => null,
    'workExperiencePrintUrl' => null,
    'workExperienceReady' => false,
    'positionDescriptionDefaults' => [],
    'positionDescriptionAction' => null,
    'positionDescriptionPrintUrl' => null,
    'positionDescriptionReady' => false,
    'medicalCertificateDefaults' => [],
    'medicalCertificateAction' => null,
    'medicalCertificatePrintUrl' => null,
    'medicalCertificateReady' => false,
    'personalDataSheetDefaults' => [],
    'personalDataSheetAction' => null,
    'personalDataSheetPrintUrl' => null,
    'personalDataSheetReady' => false,
    'serviceRecordDefaults' => [],
    'serviceRecordAction' => null,
    'serviceRecordPrintUrl' => null,
    'serviceRecordReady' => false,
    'ctoDefaults' => [],
    'ctoAction' => null,
    'ctoPrintUrl' => null,
    'ctoReady' => false,
])

<div class="space-y-4">
    @if($canManage)
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="font-semibold">Upload scanned document</h2>
        </div>
        <div class="app-card-body">
            <form method="POST" action="{{ $uploadAction }}" enctype="multipart/form-data" class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                @csrf
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="form-label">Category</label>
                    <select name="category" required class="form-select">
                        <option value="">Select category…</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Scanned file</label>
                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,image/*" class="form-input !py-2">
                </div>
                <div>
                    <label class="form-label">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500" class="form-input" placeholder="e.g. 2024 SALN">
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-xs text-slate-500 mb-3">Upload a scan or photo of the paper document: PDF, JPG, or PNG. Word files are also accepted. Max 10 MB.</p>
                    <button type="submit" class="btn-primary">Upload scanned document</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @php
        $totalFiles = collect($filesByCategory)->flatten()->count();
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-primary-900">Repository</h2>
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm text-slate-500">{{ $totalFiles }} document{{ $totalFiles === 1 ? '' : 's' }}</span>
            @if($printUrl && $totalFiles > 0)
                <a href="{{ $printUrl }}" target="_blank" class="btn-primary btn-sm">Print 201 File (PDF)</a>
            @endif
        </div>
    </div>

    @php
        $openCategory = old('category') ?: '';
        if ($openCategory === '' && request()->filled('clearance_year')) {
            $openCategory = 'clearance_property_money_csc7';
        }
        if ($openCategory === '' && request()->filled('work_experience')) {
            $openCategory = 'work_experience_sheet';
        }
        if ($openCategory === '' && request()->filled('position_description')) {
            $openCategory = 'position_description_forms';
        }
        if ($openCategory === '' && request()->filled('medical_certificate')) {
            $openCategory = 'medical_certificate_csc211';
        }
        if ($openCategory === '' && request()->filled('personal_data_sheet')) {
            $openCategory = 'personal_data_sheet';
        }
        if ($openCategory === '' && request()->filled('service_record')) {
            $openCategory = 'updated_service_record';
        }
        if ($openCategory === '' && request()->filled('cto')) {
            $openCategory = 'compensatory_time_off';
        }
        if ($openCategory === '' && request()->filled('open') && array_key_exists((string) request('open'), $categories)) {
            $openCategory = (string) request('open');
        }
        if ($openCategory === '' && array_key_exists('compensatory_time_off', $categories)) {
            $openCategory = 'updated_service_record';
        }
    @endphp
    <div class="space-y-3" x-data="{ open: @js($openCategory) }">
        @foreach($categories as $key => $label)
            @php($catFiles = $filesByCategory->get($key, collect()))
            <div class="app-card overflow-hidden">
                <button
                    type="button"
                    class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left hover:bg-slate-50/80 transition"
                    @click="open = open === '{{ $key }}' ? '' : '{{ $key }}'"
                >
                    <div class="min-w-0">
                        <div class="font-medium text-slate-800">{{ $label }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $catFiles->count() }} file{{ $catFiles->count() === 1 ? '' : 's' }}</div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': open === '{{ $key }}' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open === '{{ $key }}'" x-cloak class="border-t border-slate-100">
                    @if($key === 'clearance_property_money_csc7')
                        @include('staff.files201.clearance-form')
                    @endif
                    @if($key === 'work_experience_sheet')
                        @include('staff.files201.work-experience-form')
                    @endif
                    @if($key === 'position_description_forms')
                        @include('staff.files201.position-description-form')
                    @endif
                    @if($key === 'medical_certificate_csc211')
                        @include('staff.files201.medical-certificate-form')
                    @endif
                    @if($key === 'personal_data_sheet')
                        @include('staff.files201.personal-data-sheet-form')
                    @endif
                    @if($key === 'updated_service_record' && ($serviceRecordAction || $serviceRecordReady || array_key_exists('compensatory_time_off', $categories)))
                        @include('staff.files201.service-record-form')
                    @endif
                    @if($key === 'compensatory_time_off')
                        @include('staff.files201.cto-form')
                    @endif
                    @if($canManage)
                        <form method="POST" action="{{ $uploadAction }}" enctype="multipart/form-data" class="px-5 py-4 border-b border-slate-100 bg-white">
                            @csrf
                            <input type="hidden" name="category" value="{{ $key }}">
                            <p class="text-sm font-medium text-slate-800">Upload scanned copy</p>
                            <p class="text-xs text-slate-500 mt-1 mb-3">Attach a scanned PDF or a photo of this document. Max 10 MB.</p>
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="min-w-[16rem] flex-1">
                                    <label class="form-label">Scanned file</label>
                                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,image/*" class="form-input !py-2">
                                </div>
                                <div class="min-w-[12rem] flex-1">
                                    <label class="form-label">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <input type="text" name="notes" value="{{ old('category') === $key ? old('notes') : '' }}" maxlength="500" class="form-input" placeholder="e.g. signed scan">
                                </div>
                                <button type="submit" class="btn-secondary">Upload scan</button>
                            </div>
                        </form>
                    @endif
                    @if($catFiles->isEmpty() && ! in_array($key, ['clearance_property_money_csc7', 'work_experience_sheet', 'position_description_forms', 'medical_certificate_csc211', 'personal_data_sheet', 'compensatory_time_off'], true) && ! ($key === 'updated_service_record' && ($serviceRecordAction || $serviceRecordReady || array_key_exists('compensatory_time_off', $categories))))
                        <p class="px-5 py-6 text-sm text-slate-400 text-center">No documents in this category yet.</p>
                    @elseif(! $catFiles->isEmpty())
                        <div class="overflow-x-auto">
                            <table class="app-table">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Size</th>
                                        <th>Uploaded</th>
                                        <th>By</th>
                                        <th>Notes</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catFiles as $file)
                                    <tr>
                                        <td class="font-medium text-sm max-w-xs truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</td>
                                        <td class="text-xs text-slate-500 whitespace-nowrap">{{ $file->humanSize() }}</td>
                                        <td class="text-xs text-slate-500 whitespace-nowrap">{{ $file->created_at->format('M d, Y g:i A') }}</td>
                                        <td class="text-xs text-slate-500">{{ $file->uploaded_by_name ?: '—' }}</td>
                                        <td class="text-sm text-slate-600 max-w-[12rem] truncate">{{ $file->notes ?: '—' }}</td>
                                        <td class="whitespace-nowrap space-x-2 text-right">
                                            <a href="{{ route($downloadRoute, array_merge($routeParams, ['file' => $file])) }}" class="btn-secondary btn-sm">Download</a>
                                            @if($canManage)
                                            <form method="POST" action="{{ route($destroyRoute, array_merge($routeParams, ['file' => $file])) }}" class="inline" onsubmit="return confirm('Remove this document from the 201 File?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn-sm">Remove</button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
