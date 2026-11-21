@extends('layouts.admin')

@section('title', $personnel->name.' — 201 File')

@section('main_class', 'page-shell-wide')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary-900">201 File — {{ $personnel->name }}</h1>
        <p class="text-slate-500 text-sm">
            {{ $personnel->employee_number ? 'Employee No. '.$personnel->employee_number.' · ' : '' }}
            {{ ucfirst($personnel->role ?: 'Personnel') }}
        </p>
    </div>
    <a href="{{ route('admin.files201.index') }}" class="btn-secondary">Back to list</a>
</div>

@include('admin.files201.partials.add-category')

<x-file-201-repository
    :categories="$categories"
    :files-by-category="$filesByCategory"
    :upload-action="route('admin.files201.store', $personnel)"
    download-route="admin.files201.download"
    destroy-route="admin.files201.destroy"
    :route-params="['personnel' => $personnel]"
    :print-url="route('admin.files201.print', $personnel)"
    :clearance-requests="$clearanceRequests"
    clearance-print-route="admin.files201.clearance.print"
    :work-experience-ready="$workExperienceSheet !== null"
    :work-experience-print-url="$workExperienceSheet ? route('admin.files201.work-experience.print', $personnel) : null"
    :position-description-ready="$positionDescriptionForm !== null"
    :position-description-print-url="$positionDescriptionForm ? route('admin.files201.position-description.print', $personnel) : null"
    :medical-certificate-ready="$medicalCertificate !== null"
    :medical-certificate-print-url="$medicalCertificate ? route('admin.files201.medical-certificate.print', $personnel) : null"
    :personal-data-sheet-ready="$personalDataSheet !== null"
    :personal-data-sheet-print-url="$personalDataSheet ? route('admin.files201.personal-data-sheet.print', $personnel) : null"
    :service-record-ready="$serviceRecordReady"
    :service-record-print-url="$serviceRecordReady ? route('admin.files201.service-record.print', $personnel) : null"
    :cto-ready="$ctoReady"
    :cto-print-url="$ctoReady ? route('admin.files201.cto.print', $personnel) : null"
    :can-manage="auth('admin')->user()->canWrite()"
/>
@endsection
