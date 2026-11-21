@extends('layouts.admin')

@section('title', 'Camera Face Enrollment - SMARTDTR')

@section('content')
<x-page-header
    title="Camera Face Enrollment"
    subtitle="Register {{ $personnel->name }} using three separate blink-verified captures."
>
    <x-slot:actions>
        <a href="{{ route('admin.hr.faces') }}" class="btn-secondary">Back to Face Enrollment</a>
    </x-slot:actions>
</x-page-header>

<div class="grid xl:grid-cols-[1fr_22rem] gap-6">
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
            <div id="camera-notice" class="alert {{ session('error') ? 'alert-error' : 'alert-info' }} mb-4">
                <span>{{ session('error') ? '⚠️' : '📷' }}</span>
                <span id="camera-notice-text">
                    @if (session('error'))
                        {{ session('error') }} The three blink photos were not kept — capture them again, then save.
                    @else
                        The person in this camera must be <strong>{{ $personnel->name }}</strong>.
                        If you blink, the photos are saved as {{ $personnel->name }} — not as you.
                        Keep only that employee in view.
                        @if ($savedCount === 3)
                            This employee already has 3 saved samples. Capture three new blinks only if you need to replace them.
                        @endif
                    @endif
                </span>
            </div>

            <div class="relative max-w-3xl mx-auto overflow-hidden rounded-2xl bg-slate-950 aspect-[4/3] shadow-inner">
                <video id="camera-video" class="w-full h-full object-cover -scale-x-100" autoplay muted playsinline></video>
                <canvas id="capture-canvas" width="640" height="480" class="hidden"></canvas>

                <div class="absolute inset-x-0 top-0 p-3 bg-gradient-to-b from-slate-950/80 to-transparent text-center pointer-events-none">
                    <p class="text-white font-semibold">Enrolling {{ $personnel->name }}</p>
                </div>

                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-[54%] h-[76%] rounded-[48%] border-2 border-white/80 shadow-[0_0_0_999px_rgba(2,6,23,0.22)]"></div>
                </div>

                <div class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-slate-950/90 to-transparent text-center">
                    <p id="camera-status" class="text-white font-semibold">Camera is not started</p>
                    <p id="camera-detail" class="text-xs text-white/70 mt-1">Select “Start Camera” when the employee is ready.</p>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 mt-5">
                <button type="button" id="start-camera" class="btn-primary">Start Camera</button>
                <button type="button" id="reset-captures" class="btn-secondary" disabled>Retake All</button>
            </div>

            <form id="enrollment-form" method="POST" action="{{ route('admin.hr.faces.store', $personnel) }}"
                enctype="multipart/form-data" class="mt-6">
                @csrf
                <input id="captured-images" type="file" name="images[]" accept="image/jpeg" multiple hidden>
                <input type="hidden" name="blink_verifications[]" value="1">
                <input type="hidden" name="blink_verifications[]" value="1">
                <input type="hidden" name="blink_verifications[]" value="1">

                <div class="grid grid-cols-3 gap-3 max-w-2xl mx-auto">
                    @for($slot = 1; $slot <= 3; $slot++)
                        <div class="aspect-[4/3] rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden relative" data-capture-slot="{{ $slot }}">
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400" data-placeholder>
                                <span class="text-xl font-bold">{{ $slot }}</span>
                                <span class="text-[10px] mt-1">Blink capture</span>
                            </div>
                            <img alt="Capture {{ $slot }}" class="hidden w-full h-full object-cover -scale-x-100" data-preview>
                            <span class="hidden absolute top-2 right-2 badge badge-approved" data-check>Verified</span>
                        </div>
                    @endfor
                </div>

                <button type="submit" id="save-enrollment" class="btn-success w-full mt-5 py-3" disabled>
                    Complete 3 Blink Captures
                </button>
            </form>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="app-card">
            <div class="app-card-header"><h2 class="app-card-title">Enrollment Steps</h2></div>
            <div class="app-card-body space-y-4">
                @foreach([
                    ['1', 'Face the camera', 'Remove masks and glasses. Keep only one person in view.'],
                    ['2', 'Blink naturally', 'Open eyes, close both eyes, then open them again.'],
                    ['3', 'Repeat three times', 'The system resets after each photo, so each capture needs a new blink.'],
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
            <span>💻</span>
            <span class="text-xs">Use this laptop’s built-in camera through localhost. Saving can take up to a minute while the CNN face detector checks lighting, sharpness, and whether this is a new person.</span>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script type="module">
import {
    FaceLandmarker,
    FilesetResolver
} from 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/+esm';

const video = document.getElementById('camera-video');
const canvas = document.getElementById('capture-canvas');
const context = canvas.getContext('2d');
const startButton = document.getElementById('start-camera');
const resetButton = document.getElementById('reset-captures');
const saveButton = document.getElementById('save-enrollment');
const enrollmentForm = document.getElementById('enrollment-form');
const fileInput = document.getElementById('captured-images');
const statusText = document.getElementById('camera-status');
const detailText = document.getElementById('camera-detail');
const counter = document.getElementById('capture-counter');
const notice = document.getElementById('camera-notice');
const savedCount = {{ (int) $savedCount }};
const facesListUrl = @json(route('admin.hr.faces'));
const employeeName = @json($personnel->name);

let landmarker = null;
let stream = null;
let running = false;
let captureInProgress = false;
let saving = false;
let phase = 'open';
let openFrames = 0;
let closedFrames = 0;
let reopenedFrames = 0;
let captures = [];
let previewUrls = [];
let lastVideoTime = -1;

const setStatus = (title, detail, tone = 'normal') => {
    statusText.textContent = title;
    detailText.textContent = detail;
    statusText.className = tone === 'success'
        ? 'text-emerald-300 font-semibold'
        : tone === 'warning'
            ? 'text-amber-300 font-semibold'
            : 'text-white font-semibold';
};

const showNotice = (message, tone = 'info') => {
    notice.className = `alert ${tone === 'error' ? 'alert-error' : 'alert-info'} mb-4`;
    notice.replaceChildren();
    const icon = document.createElement('span');
    icon.textContent = tone === 'error' ? '⚠️' : '📷';
    const text = document.createElement('span');
    text.id = 'camera-notice-text';
    text.textContent = message;
    notice.append(icon, text);
};

const resetBlink = () => {
    phase = 'open';
    openFrames = 0;
    closedFrames = 0;
    reopenedFrames = 0;
};

const syncFiles = () => {
    const transfer = new DataTransfer();
    captures.forEach((file) => transfer.items.add(file));
    fileInput.files = transfer.files;
    const displayCount = captures.length || savedCount;
    counter.textContent = captures.length
        ? `${captures.length} / 3 captured`
        : `${savedCount} / 3 saved`;
    counter.className = `badge ${displayCount === 3 ? 'badge-approved' : 'badge-pending'}`;
    resetButton.disabled = captures.length === 0 || saving;
    saveButton.disabled = captures.length !== 3 || saving;
    if (saving) {
        saveButton.textContent = 'Saving… Python is checking the photos';
    } else {
        saveButton.textContent = captures.length === 3
            ? 'Save 3 Samples & Sync to Raspberry Pi'
            : `Complete ${3 - captures.length} More Blink Capture${3 - captures.length === 1 ? '' : 's'}`;
    }
};

const renderCapture = (index, blob) => {
    const slot = document.querySelector(`[data-capture-slot="${index + 1}"]`);
    const preview = slot.querySelector('[data-preview]');
    previewUrls[index] = URL.createObjectURL(blob);
    preview.src = previewUrls[index];
    preview.classList.remove('hidden');
    slot.querySelector('[data-placeholder]').classList.add('hidden');
    slot.querySelector('[data-check]').classList.remove('hidden');
    slot.classList.remove('border-dashed', 'border-slate-300');
    slot.classList.add('border-emerald-400');
};

const captureFrame = async () => {
    if (captureInProgress || captures.length >= 3) return;
    captureInProgress = true;

    const sourceWidth = video.videoWidth;
    const sourceHeight = video.videoHeight;
    const targetRatio = canvas.width / canvas.height;
    const sourceRatio = sourceWidth / sourceHeight;
    let sx = 0;
    let sy = 0;
    let sw = sourceWidth;
    let sh = sourceHeight;

    if (sourceRatio > targetRatio) {
        sw = sourceHeight * targetRatio;
        sx = (sourceWidth - sw) / 2;
    } else {
        sh = sourceWidth / targetRatio;
        sy = (sourceHeight - sh) / 2;
    }

    context.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
    if (!blob) {
        setStatus('Capture failed', 'Please try the blink again.', 'warning');
        captureInProgress = false;
        resetBlink();
        return;
    }

    const index = captures.length;
    captures.push(new File([blob], `blink_capture_${index + 1}.jpg`, { type: 'image/jpeg' }));
    renderCapture(index, blob);
    syncFiles();

    if (captures.length === 3) {
        setStatus('Three captures complete', 'Review the previews, then save and sync.', 'success');
        running = false;
    } else {
        setStatus(`Capture ${captures.length} verified`, 'Hold still—prepare for a new blink.', 'success');
        await new Promise((resolve) => setTimeout(resolve, 1200));
        resetBlink();
        setStatus('Eyes open', `Blink again for capture ${captures.length + 1}.`);
    }
    captureInProgress = false;
};

const scoresFor = (result) => {
    if (!result.faceBlendshapes?.length) return null;
    const categories = result.faceBlendshapes[0].categories;
    const score = (name) => categories.find((item) => item.categoryName === name)?.score ?? 0;
    return { left: score('eyeBlinkLeft'), right: score('eyeBlinkRight') };
};

const processFrame = async () => {
    if (!running || !landmarker || video.readyState < 2) {
        if (running) requestAnimationFrame(processFrame);
        return;
    }

    if (lastVideoTime !== video.currentTime) {
        lastVideoTime = video.currentTime;
        const result = landmarker.detectForVideo(video, performance.now());

        if (result.faceLandmarks.length !== 1) {
            resetBlink();
            setStatus(
                result.faceLandmarks.length > 1 ? 'Only one person allowed' : 'Position face inside the guide',
                'The face must remain clearly visible before blinking.',
                'warning'
            );
        } else {
            const blink = scoresFor(result);
            if (!blink) {
                setStatus('Checking eyes…', 'Keep looking directly at the camera.');
            } else if (phase === 'open') {
                if (blink.left < 0.32 && blink.right < 0.32) openFrames++;
                else openFrames = 0;
                setStatus('Eyes open', `Ready for capture ${captures.length + 1}—blink once (close, then open).`);
                if (openFrames >= 3) phase = 'closed';
            } else if (phase === 'closed') {
                if (blink.left > 0.42 && blink.right > 0.42) {
                    closedFrames++;
                    setStatus('Eyes closed', 'Open your eyes to finish this capture.', 'success');
                    if (closedFrames >= 1) phase = 'reopened';
                }
            } else if (phase === 'reopened') {
                if (blink.left < 0.32 && blink.right < 0.32) reopenedFrames++;
                else reopenedFrames = 0;
                if (reopenedFrames >= 2) await captureFrame();
            }
        }
    }

    if (running) requestAnimationFrame(processFrame);
};

const initializeDetector = async () => {
    if (landmarker) return;
    setStatus('Loading face detector…', 'This may take a few seconds the first time. Stay on this laptop (localhost).');
    const vision = await FilesetResolver.forVisionTasks(
        'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
    );
    const model = 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task';
    const options = {
        runningMode: 'VIDEO',
        numFaces: 2,
        outputFaceBlendshapes: true,
    };
    try {
        landmarker = await FaceLandmarker.createFromOptions(vision, {
            ...options,
            baseOptions: { modelAssetPath: model, delegate: 'GPU' },
        });
    } catch (gpuError) {
        console.warn('GPU face detector failed, using CPU', gpuError);
        landmarker = await FaceLandmarker.createFromOptions(vision, {
            ...options,
            baseOptions: { modelAssetPath: model, delegate: 'CPU' },
        });
    }
};

startButton.addEventListener('click', async () => {
    if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(location.hostname)) {
        notice.className = 'alert alert-error mb-4';
        notice.innerHTML = '<span>⚠️</span><span>Camera access is blocked on an unsecured network address. Open this page through HTTPS, or use the laptop running the system through localhost.</span>';
        return;
    }

    startButton.disabled = true;
    try {
        await initializeDetector();
        if (!stream) {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
            video.srcObject = stream;
            await video.play();
        }
        running = captures.length < 3;
        resetBlink();
        setStatus('Eyes open', `Blink for capture ${captures.length + 1}.`);
        requestAnimationFrame(processFrame);
        startButton.textContent = 'Camera Running';
    } catch (error) {
        console.error(error);
        const offline = String(error?.message || error).toLowerCase().includes('failed to fetch')
            || String(error?.message || error).toLowerCase().includes('network');
        setStatus(
            offline ? 'No internet for the face detector' : 'Camera unavailable',
            offline
                ? 'This page needs a short internet connection the first time to load the blink detector. Then blink on this laptop through localhost.'
                : 'Allow camera permission. Open Admin through http://localhost/INHS%20SMART-DTR/ — not a 192.168 address.',
            'warning'
        );
        startButton.disabled = false;
    }
});

resetButton.addEventListener('click', () => {
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls = [];
    captures = [];
    document.querySelectorAll('[data-capture-slot]').forEach((slot) => {
        slot.querySelector('[data-preview]').classList.add('hidden');
        slot.querySelector('[data-placeholder]').classList.remove('hidden');
        slot.querySelector('[data-check]').classList.add('hidden');
        slot.classList.add('border-dashed', 'border-slate-300');
        slot.classList.remove('border-emerald-400');
    });
    syncFiles();
    resetBlink();
    running = Boolean(stream);
    if (running) {
        setStatus('Eyes open', 'Blink for capture 1.');
        requestAnimationFrame(processFrame);
    }
});

enrollmentForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (saving || captures.length !== 3) {
        return;
    }

    saving = true;
    syncFiles();
    setStatus('Saving samples…', 'Please wait. Python is checking lighting, sharpness, and identity.', 'success');
    showNotice(`Saving three blink captures for ${employeeName}. This can take up to a minute — keep this tab open.`, 'info');

    const body = new FormData();
    const token = enrollmentForm.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        body.append('_token', token);
    }
    captures.forEach((file, index) => {
        body.append('images[]', file, file.name || `blink_capture_${index + 1}.jpg`);
    });
    for (let i = 0; i < 3; i++) {
        body.append('blink_verifications[]', '1');
    }

    try {
        const response = await fetch(enrollmentForm.action, {
            method: 'POST',
            body,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const fromErrors = payload.errors
                ? Object.values(payload.errors).flat().find(Boolean)
                : null;
            const message = payload.message || fromErrors || 'The photos could not be saved. Capture three blinks again and retry.';
            saving = false;
            syncFiles();
            setStatus('Save failed', message, 'warning');
            showNotice(message, 'error');
            return;
        }

        window.location.href = payload.redirect || facesListUrl;
    } catch (error) {
        console.error(error);
        saving = false;
        syncFiles();
        const message = 'The save request failed. Stay on localhost and try again without closing this tab.';
        setStatus('Save failed', message, 'warning');
        showNotice(message, 'error');
    }
});

window.addEventListener('beforeunload', () => {
    stream?.getTracks().forEach((track) => track.stop());
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
});
</script>
@endpush
