@extends('layouts.staff')

@section('title', 'My 201 File - SMART-DTR')

@section('main_class', 'page-shell-wide')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">My 201 File</h1>
    <p class="text-slate-500 text-sm">Upload scanned copies of your personnel documents (appointments, PDS, SALN, service record, CTO, and clearances)</p>
    @if($personnel?->isNonTeaching())
        <p class="text-sm text-primary-800 mt-2">Non-teaching records include your Updated Service Record and Compensatory Time Off (CTO).</p>
    @endif
</div>

@if(! $personnel)
    <div class="app-card">
        <div class="app-card-body text-center py-10">
            <p class="text-slate-600 font-medium mb-2">Your account is not linked to a personnel record yet.</p>
            <p class="text-sm text-slate-500">Please contact HR so they can link your staff account. Your 201 File repository will appear here after linking.</p>
        </div>
    </div>
@else
    <x-file-201-repository
        :categories="$categories"
        :files-by-category="$filesByCategory"
        :upload-action="route('staff.files201.store')"
        download-route="staff.files201.download"
        destroy-route="staff.files201.destroy"
        :print-url="route('staff.files201.print')"
        :clearance-requests="$clearanceRequests"
        :clearance-defaults="$clearanceDefaults"
        :clearance-action="route('staff.files201.clearance.store')"
        clearance-print-route="staff.files201.clearance.print"
        :work-experience-defaults="$workExperienceDefaults"
        :work-experience-action="route('staff.files201.work-experience.store')"
        :work-experience-print-url="route('staff.files201.work-experience.print')"
        :work-experience-ready="$workExperienceReady"
        :position-description-defaults="$positionDescriptionDefaults"
        :position-description-action="route('staff.files201.position-description.store')"
        :position-description-print-url="route('staff.files201.position-description.print')"
        :position-description-ready="$positionDescriptionReady"
        :medical-certificate-defaults="$medicalCertificateDefaults"
        :medical-certificate-action="route('staff.files201.medical-certificate.store')"
        :medical-certificate-print-url="route('staff.files201.medical-certificate.print')"
        :medical-certificate-ready="$medicalCertificateReady"
        :personal-data-sheet-defaults="$personalDataSheetDefaults"
        :personal-data-sheet-action="route('staff.files201.personal-data-sheet.store')"
        :personal-data-sheet-print-url="route('staff.files201.personal-data-sheet.print')"
        :personal-data-sheet-ready="$personalDataSheetReady"
        :service-record-defaults="$serviceRecordDefaults"
        :service-record-action="$personnel->isNonTeaching() ? route('staff.files201.service-record.store') : null"
        :service-record-print-url="route('staff.files201.service-record.print')"
        :service-record-ready="$serviceRecordReady"
        :cto-defaults="$ctoDefaults"
        :cto-action="$personnel->isNonTeaching() ? route('staff.files201.cto.store') : null"
        :cto-print-url="route('staff.files201.cto.print')"
        :cto-ready="$ctoReady"
        :can-manage="true"
    />
@endif
@endsection
