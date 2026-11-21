@extends('layouts.staff')

@section('title', 'Register My Face - SMARTDTR')

@section('content')
<x-page-header
    title="Register My Face"
    subtitle="Use this phone or laptop camera to register three blink-verified photos for the attendance kiosk."
/>

@if(! $personnel)
<div class="alert alert-error mb-6">
    <span>⚠️</span>
    <span>Your account is not linked to a personnel record yet. Ask HR to activate and link your account before you can register your face.</span>
</div>
@else
<div class="grid xl:grid-cols-[1fr_20rem] gap-6">
    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">{{ $personnel->name }}</h2>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $personnel->employee_number ?: 'No employee number' }} · {{ $personnel->role }}
                </p>
            </div>
            <span id="capture-counter" class="badge {{ $savedCount === 3 ? 'badge-approved' : 'badge-pending' }}">
                {{ $savedCount }} / 3 saved
            </span>
        </div>

        <div class="app-card-body">
            <form id="enrollment-form" data-face-form method="POST" action="{{ route('staff.faces.store') }}"
                data-face-redirect="{{ route('staff.faces') }}"
                enctype="multipart/form-data">
                @csrf
                <x-face-capture
                    :employee-name="$personnel->name"
                    :saved-count="$savedCount"
                    :error="session('error')"
                />
                <button type="submit" data-face-save class="btn-success w-full mt-5 py-3" disabled
                    data-waiting-label="Complete 3 Blink Captures"
                    data-ready-label="Save & Sync to Attendance Kiosk"
                    data-saving-label="Saving… checking photos">
                    Complete 3 Blink Captures
                </button>
            </form>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="app-card">
            <div class="app-card-header"><h2 class="app-card-title">How to register</h2></div>
            <div class="app-card-body space-y-4">
                @foreach([
                    ['1', 'Use your phone or laptop', 'Open the Employee Portal while connected to the school network.'],
                    ['2', 'Start the camera', 'Allow camera permission. Keep only your face in the oval.'],
                    ['3', 'Blink three times', 'Each photo needs a fresh blink. Then tap Save.'],
                ] as [$number, $title, $text])
                    <div class="flex gap-3">
                        <span class="w-7 h-7 shrink-0 rounded-full bg-primary-50 text-primary-700 flex items-center justify-center text-xs font-bold">{{ $number }}</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="alert alert-info">
            <span>📱</span>
            <span class="text-xs">Phones need a secure page for the camera (HTTPS), or use a school computer / laptop browser that can open the camera. Saving can take up to a minute while the system checks your photos.</span>
        </div>
    </aside>
</div>
@endif
@endsection
