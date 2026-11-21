@props([
    'employeeName' => 'you',
    'savedCount' => 0,
    'error' => null,
])

<div
    data-face-capture
    data-saved-count="{{ (int) $savedCount }}"
    data-employee-name="{{ $employeeName }}"
>
    <div data-face-notice class="alert {{ $error ? 'alert-error' : 'alert-info' }} mb-4">
        <span>{{ $error ? '⚠️' : '📷' }}</span>
        <span>
            @if ($error)
                {{ $error }} Capture three blinks again, then save.
            @else
                Hold the camera in front of <strong>your</strong> face only. Blink three times to register for the attendance kiosk.
                @if ($savedCount === 3)
                    You already have 3 saved samples. Capture three new blinks only if you need to replace them.
                @endif
            @endif
        </span>
    </div>

    <div class="relative max-w-xl mx-auto overflow-hidden rounded-2xl bg-slate-950 aspect-[3/4] sm:aspect-[4/3] shadow-inner">
        <video data-face-video class="w-full h-full object-cover -scale-x-100" autoplay muted playsinline></video>
        <canvas data-face-canvas width="640" height="480" class="hidden"></canvas>

        <div class="absolute inset-x-0 top-0 p-3 bg-gradient-to-b from-slate-950/80 to-transparent text-center pointer-events-none">
            <p class="text-white font-semibold text-sm sm:text-base">Registering {{ $employeeName }}</p>
        </div>

        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="w-[62%] h-[58%] sm:w-[54%] sm:h-[76%] rounded-[48%] border-2 border-white/80 shadow-[0_0_0_999px_rgba(2,6,23,0.22)]"></div>
        </div>

        <div class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-slate-950/90 to-transparent text-center">
            <p data-face-status class="text-white font-semibold text-sm sm:text-base">Camera is not started</p>
            <p data-face-detail class="text-xs text-white/70 mt-1">Tap “Start Camera” when you are ready.</p>
        </div>
    </div>

    <div class="flex flex-wrap justify-center gap-3 mt-5">
        <button type="button" data-face-start class="btn-primary">Start Camera</button>
        <button type="button" data-face-reset class="btn-secondary" disabled>Retake All</button>
    </div>

    <input data-face-files type="file" name="images[]" accept="image/jpeg" multiple hidden>
    <input type="hidden" name="blink_verifications[]" value="1">
    <input type="hidden" name="blink_verifications[]" value="1">
    <input type="hidden" name="blink_verifications[]" value="1">

    <div class="grid grid-cols-3 gap-3 max-w-md mx-auto mt-6">
        @for($slot = 1; $slot <= 3; $slot++)
            <div class="aspect-[4/3] rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden relative" data-capture-slot="{{ $slot }}">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400" data-placeholder>
                    <span class="text-xl font-bold">{{ $slot }}</span>
                    <span class="text-[10px] mt-1">Blink</span>
                </div>
                <img alt="Capture {{ $slot }}" class="hidden w-full h-full object-cover -scale-x-100" data-preview>
                <span class="absolute top-2 right-2 badge badge-approved" data-check hidden>OK</span>
            </div>
        @endfor
    </div>
    @error('images')<p class="form-error mt-3">{{ $message }}</p>@enderror
    @error('images.*')<p class="form-error mt-3">{{ $message }}</p>@enderror
</div>

@once
    @push('scripts')
        <script type="module">
            import { bindFaceCapture } from '{{ asset('js/face-capture.js') }}';
            document.querySelectorAll('[data-face-capture]').forEach((root) => bindFaceCapture(root));
        </script>
    @endpush
@endonce
