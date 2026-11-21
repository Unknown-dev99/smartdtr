import {
    FaceLandmarker,
    FilesetResolver
} from 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/+esm';

export function bindFaceCapture(root) {
    const form = root.closest('form');
    const video = root.querySelector('[data-face-video]');
    const canvas = root.querySelector('[data-face-canvas]');
    const context = canvas.getContext('2d');
    const startButton = root.querySelector('[data-face-start]');
    const resetButton = root.querySelector('[data-face-reset]');
    const fileInput = root.querySelector('[data-face-files]');
    const statusText = root.querySelector('[data-face-status]');
    const detailText = root.querySelector('[data-face-detail]');
    const notice = root.querySelector('[data-face-notice]');
    const saveButton = form?.querySelector('[data-face-save]');
    const savedCount = Number(root.dataset.savedCount || 0);
    const employeeName = root.dataset.employeeName || 'you';

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
            ? 'text-emerald-300 font-semibold text-sm sm:text-base'
            : tone === 'warning'
                ? 'text-amber-300 font-semibold text-sm sm:text-base'
                : 'text-white font-semibold text-sm sm:text-base';
    };

    const showNotice = (message, tone = 'info') => {
        notice.className = `alert ${tone === 'error' ? 'alert-error' : 'alert-info'} mb-4`;
        notice.replaceChildren();
        const icon = document.createElement('span');
        icon.textContent = tone === 'error' ? '⚠️' : '📷';
        const text = document.createElement('span');
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
        const pageCounter = document.getElementById('capture-counter');
        const label = captures.length
            ? `${captures.length} / 3 captured`
            : `${savedCount} / 3 saved`;
        if (pageCounter) {
            pageCounter.textContent = label;
            pageCounter.className = `badge ${(captures.length || savedCount) === 3 ? 'badge-approved' : 'badge-pending'}`;
        }
        resetButton.disabled = captures.length === 0 || saving;
        if (saveButton) {
            saveButton.disabled = captures.length !== 3 || saving;
            if (saving) {
                saveButton.textContent = saveButton.dataset.savingLabel || 'Saving… checking photos';
            } else if (captures.length === 3) {
                saveButton.textContent = saveButton.dataset.readyLabel || 'Save face registration';
            } else {
                const remaining = 3 - captures.length;
                saveButton.textContent = saveButton.dataset.waitingLabel
                    || `Complete ${remaining} more blink capture${remaining === 1 ? '' : 's'}`;
            }
        }
    };

    const renderCapture = (index, blob) => {
        const slot = root.querySelector(`[data-capture-slot="${index + 1}"]`);
        const preview = slot.querySelector('[data-preview]');
        previewUrls[index] = URL.createObjectURL(blob);
        preview.src = previewUrls[index];
        preview.classList.remove('hidden');
        slot.querySelector('[data-placeholder]').classList.add('hidden');
        slot.querySelector('[data-check]').hidden = false;
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
            setStatus('Three captures complete', 'Review the previews, then save.', 'success');
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
                    'Keep your face clearly visible before blinking.',
                    'warning'
                );
            } else {
                const blink = scoresFor(result);
                if (!blink) {
                    setStatus('Checking eyes…', 'Look directly at the camera.');
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
        setStatus('Loading face detector…', 'Needs a short internet connection the first time.');
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
            showNotice('Phone cameras are blocked on plain http://192.168… addresses. Ask HR for the HTTPS link, or register on a school computer that can open the camera.', 'error');
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
                    ? 'This page needs internet once to load the blink detector. Then try again on the school network.'
                    : 'Allow camera permission, then tap Start Camera again.',
                'warning'
            );
            startButton.disabled = false;
        }
    });

    resetButton.addEventListener('click', () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
        captures = [];
        root.querySelectorAll('[data-capture-slot]').forEach((slot) => {
            slot.querySelector('[data-preview]').classList.add('hidden');
            slot.querySelector('[data-placeholder]').classList.remove('hidden');
            slot.querySelector('[data-check]').hidden = true;
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

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (saving || captures.length !== 3) {
            showNotice('Blink three times before continuing.', 'error');
            return;
        }

        saving = true;
        syncFiles();
        setStatus('Saving samples…', 'Please wait while photos are checked.', 'success');
        showNotice(`Saving three blink captures for ${employeeName}. Keep this page open.`, 'info');

        const body = new FormData(form);
        body.delete('images[]');
        captures.forEach((file, index) => {
            body.append('images[]', file, file.name || `blink_capture_${index + 1}.jpg`);
        });

        try {
            const response = await fetch(form.action, {
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

            window.location.href = payload.redirect || form.dataset.faceRedirect || window.location.href;
        } catch (error) {
            console.error(error);
            saving = false;
            syncFiles();
            const message = 'The save request failed. Stay on this page and try again.';
            setStatus('Save failed', message, 'warning');
            showNotice(message, 'error');
        }
    });

    window.addEventListener('beforeunload', () => {
        stream?.getTracks().forEach((track) => track.stop());
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
    });

    syncFiles();
}
