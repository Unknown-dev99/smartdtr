@php
    $defaults = $medicalCertificateDefaults ?? [];
    $selectedExams = old('exams', $defaults['exams'] ?? []);
    $fitness = old('fitness', $defaults['fitness'] ?? '');
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Medical Certificate</h3>
            <p class="text-sm text-slate-500 mt-1">CS Form No. 211, Revised 2025. For the proposed appointee and the licensed physician. Attach the listed examination results to the printed form.</p>
        </div>
        @if(($medicalCertificateReady ?? false) && ($medicalCertificatePrintUrl ?? null))
            <a href="{{ $medicalCertificatePrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($medicalCertificateAction ?? null)
        <form method="POST" action="{{ $medicalCertificateAction }}" class="mt-4 space-y-5">
            @csrf
            <input type="hidden" name="category" value="medical_certificate_csc211">

            <fieldset class="space-y-2">
                <legend class="form-label">Attached examination results</legend>
                <p class="text-xs text-slate-500">Mark the pre-employment results attached to this certificate.</p>
                <div class="grid sm:grid-cols-2 gap-2 text-sm text-slate-700">
                    @foreach(\App\Services\MedicalCertificateService::EXAMS as $key => $label)
                        <label class="inline-flex items-start gap-2">
                            <input type="checkbox" name="exams[]" value="{{ $key }}" class="mt-1" @checked(in_array($key, $selectedExams, true))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <p class="text-sm font-semibold text-slate-800">For the proposed appointee</p>
                <div class="grid md:grid-cols-2 gap-4 mt-3">
                    <div class="md:col-span-2">
                        <label class="form-label">Name (Last Name, First Name, Name Extension and Middle Name)*</label>
                        <input type="text" name="appointee_name" required value="{{ old('appointee_name', $defaults['appointee_name'] ?? '') }}" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Address*</label>
                        <input type="text" name="address" required value="{{ old('address', $defaults['address'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Age*</label>
                        <input type="text" name="age" required value="{{ old('age', $defaults['age'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Sex*</label>
                        <input type="text" name="sex" required value="{{ old('sex', $defaults['sex'] ?? '') }}" class="form-input" placeholder="Male / Female">
                    </div>
                    <div>
                        <label class="form-label">Civil status*</label>
                        <input type="text" name="civil_status" required value="{{ old('civil_status', $defaults['civil_status'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Proposed position*</label>
                        <input type="text" name="proposed_position" required value="{{ old('proposed_position', $defaults['proposed_position'] ?? '') }}" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Agency / address*</label>
                        <input type="text" name="agency_address" required value="{{ old('agency_address', $defaults['agency_address'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-800">For the licensed government or private physician</p>
                <div class="mt-3 space-y-3">
                    <fieldset>
                        <legend class="form-label">Physically and medically</legend>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <label class="inline-flex items-center gap-2"><input type="radio" name="fitness" value="" @checked($fitness === '')> Not yet marked</label>
                            <label class="inline-flex items-center gap-2"><input type="radio" name="fitness" value="fit" @checked($fitness === 'fit')> FIT</label>
                            <label class="inline-flex items-center gap-2"><input type="radio" name="fitness" value="unfit" @checked($fitness === 'unfit')> UNFIT</label>
                        </div>
                    </fieldset>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Signature over printed name of physician</label>
                            <input type="text" name="physician_name" value="{{ old('physician_name', $defaults['physician_name'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Other information about the proposed appointee</label>
                            <input type="text" name="other_information" value="{{ old('other_information', $defaults['other_information'] ?? '') }}" class="form-input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Agency / affiliation of the physician</label>
                            <input type="text" name="physician_agency" value="{{ old('physician_agency', $defaults['physician_agency'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">License no.</label>
                            <input type="text" name="license_no" value="{{ old('license_no', $defaults['license_no'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Height (m), bare foot</label>
                            <input type="text" name="height" value="{{ old('height', $defaults['height'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Weight (kg), stripped</label>
                            <input type="text" name="weight" value="{{ old('weight', $defaults['weight'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Blood type</label>
                            <input type="text" name="blood_type" value="{{ old('blood_type', $defaults['blood_type'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Official designation</label>
                            <input type="text" name="designation" value="{{ old('designation', $defaults['designation'] ?? '') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Date examined</label>
                            <input type="date" name="date_examined" value="{{ old('date_examined', $defaults['date_examined'] ?? '') }}" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            @foreach($errors->all() as $message)
                <p class="text-xs text-red-600">{{ $message }}</p>
            @endforeach

            <button type="submit" class="btn-primary">Save medical certificate</button>
        </form>
    @elseif(! ($medicalCertificateReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
