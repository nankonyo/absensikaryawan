@props([
    'action',
    'accent' => 'green',
    'label' => '',
])

@php
    $ring = $accent === 'red' ? 'border-[#DC2626]' : 'border-[#16A34A]';
    $button = $accent === 'red' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700';
    $attendanceReasons = \App\Models\AttendanceReason::where('is_active', true)->orderBy('id')->get();
    $office = app(\App\Services\GpsValidationService::class)->getActiveOffice();
    $workMode = auth()->user()->work_mode;
    $officeData = $office ? [
        'latitude' => (float) $office->latitude,
        'longitude' => (float) $office->longitude,
        'radius' => (float) $office->radius,
    ] : null;
@endphp

<div class="fixed inset-0 z-[55] h-[100dvh] w-screen overflow-hidden bg-black" x-data="attendanceCamera()" x-init="init()">
    <div class="relative h-full w-full overflow-hidden">
        <!-- Camera full-screen: covers navbar & sidebar -->
        <div class="camera-frame absolute inset-0">
            <video x-ref="video" autoplay playsinline muted
                   x-show="!captured"
                   class="absolute left-1/2 top-1/2 h-[min(82vw,62vh)] w-[min(82vw,62vh)] -translate-x-1/2 -translate-y-1/2 rounded-full object-cover"></video>

            <img x-ref="preview" x-show="captured" x-cloak
                 class="absolute left-1/2 top-1/2 h-[min(82vw,62vh)] w-[min(82vw,62vh)] -translate-x-1/2 -translate-y-1/2 rounded-full object-cover" alt="{{ __('ui.selfie_photo') }}">

            <!-- Transparent badge: Masuk / Keluar -->
            <span class="absolute right-3 top-3 z-10 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                {{ $label }}
            </span>

            <!-- Face position guide: lingkaran tengah -->
            <div x-show="!captured || busy" x-cloak
                 class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div x-ref="guide" class="relative aspect-square w-[min(82vw,62vh)] rounded-full border-4 {{ $ring }} shadow-[0_0_0_9999px_#000] transition-transform duration-200"
                     :class="faceDetected ? 'scale-105' : ''">
                    <div x-show="busy" x-cloak
                         class="pointer-events-none absolute -inset-3 z-10 animate-spin rounded-full border-4 border-transparent {{ $accent === 'red' ? 'border-t-[#DC2626] border-r-[#DC2626]' : 'border-t-[#16A34A] border-r-[#16A34A]' }}"></div>
                </div>
            </div>

            <p x-show="!captured" x-cloak
               class="pointer-events-none absolute inset-x-0 bottom-10 text-center text-sm font-medium text-white drop-shadow">
                <span x-show="!faceDetected && !fallbackMode" x-cloak>{{ __('ui.face_auto_guide') }}</span>
                <span x-show="faceDetected" x-cloak class="font-semibold text-green-300">{{ __('ui.face_detected_hold') }}</span>
                <span x-show="fallbackMode" x-cloak>{{ __('ui.face_auto_fallback') }}</span>
            </p>

            <!-- Permission gate: camera/location (iOS Safari) -->
            <div x-show="cameraPermissionNeeded || locationPermissionNeeded" x-cloak
                 class="absolute inset-0 z-[45] flex flex-col items-center justify-center gap-4 bg-black/85 p-6 text-center text-white">
                <p class="text-base font-semibold">Izinkan akses untuk melanjutkan presensi</p>
                <p class="max-w-sm text-sm text-white/80">Aktifkan kamera dan lokasi. Jika prompt tidak muncul, buka Pengaturan iPhone → Safari → Kamera/Lokasi.</p>
                <button type="button" x-show="cameraPermissionNeeded" @click="requestCamera()"
                        class="w-full max-w-xs rounded-full {{ $button }} px-6 py-3 font-semibold text-white">
                    Izinkan Kamera
                </button>
                <button type="button" x-show="locationPermissionNeeded" @click="requestLocation()"
                        class="w-full max-w-xs rounded-full bg-slate-700 px-6 py-3 font-semibold text-white">
                    Izinkan Lokasi
                </button>
            </div>

            <!-- Outside-zone reason picker: hidden until the captured location is outside the radius -->
            <div x-show="needsReason" x-cloak class="absolute inset-0 z-40 flex flex-col bg-white p-5 text-gray-900">
                <div class="shrink-0">
                    <h2 class="text-lg font-bold">{{ __('ui.outside_zone_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('ui.outside_zone_reason_help') }}</p>
                </div>

                <div class="mt-4 min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-xl border border-gray-200">
                    @foreach($attendanceReasons as $reason)
                        <label class="flex cursor-pointer items-center gap-3 border-b border-gray-200 px-4 py-4 last:border-b-0">
                            <input type="radio" name="attendance_reason_choice" value="{{ $reason->id }}"
                                   x-model="selectedReason"
                                   class="h-4 w-4 border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium">{{ $reason->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 shrink-0">
                    <label for="attendance-reason-note" class="text-sm font-medium text-gray-700">
                        {{ __('ui.attendance_reason_note') }}
                    </label>
                    <textarea id="attendance-reason-note" rows="3" maxlength="500"
                              x-model="reasonNote"
                              placeholder="{{ __('ui.attendance_reason_note_placeholder') }}"
                              class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-green-500 focus:ring-green-500"></textarea>
                </div>

                <p x-show="reasonError" x-cloak class="mt-3 text-sm text-red-600">
                    {{ __('ui.attendance_reason_required') }}
                </p>

                <button type="button" @click="confirmReason()"
                        class="mt-4 w-full shrink-0 rounded-full {{ $button }} px-6 py-4 font-semibold text-white transition">
                    {{ __('ui.continue') }}
                </button>
            </div>

            <!-- Foto diambil otomatis saat wajah terdeteksi di lingkaran. -->
        </div>
    </div>

    <canvas x-ref="canvas" class="hidden"></canvas>

    <form x-ref="form" id="attendance-form" method="POST" action="{{ $action }}" class="hidden">
        @csrf
        <input type="hidden" name="latitude" x-ref="latitude">
        <input type="hidden" name="longitude" x-ref="longitude">
        <input type="hidden" name="address" x-ref="address">
        <input type="hidden" name="photo_data" x-ref="photoData">
        <input type="hidden" name="face_score" x-ref="faceScore">
        <input type="hidden" name="attendance_reason_id" x-ref="reason">
        <input type="hidden" name="notes" x-ref="notes">
    </form>
</div>

@if (config('app.asset_mode') !== 'cdn')
    @vite('resources/js/face-match.js')
@endif

<script>
    const FACE_API_SRC = @json(asset('cdn/face-api.min.js'));
    const FACE_MATCH_SRC = @json(asset('cdn/face-match.js'));
    const FACE_ENABLED = @json(app(\App\Services\FaceRecognitionService::class)->isEnabled());
    const FACE_AVATAR_URL = @json(auth()->user()->avatar_url);
    const FACE_THRESHOLD = {{ (int) app(\App\Services\FaceRecognitionService::class)->getThreshold() }};
    const OFFICE = @json($officeData);
    const WORK_MODE = @json($workMode);

    const MSG = {
        cameraError: @json(__('ui.camera_error', ['message' => '__MSG__'])),
        cameraInsecure: @json(__('ui.camera_insecure_context')),
        cameraUnsupported: @json(__('ui.camera_unsupported')),
        locationError: @json(__('ui.location_error', ['message' => '__MSG__'])),
        locationInsecure: @json(__('ui.location_insecure_context')),
        geolocationUnsupported: @json(__('ui.geolocation_unsupported')),
        processing: @json(__('ui.processing')),
        faceMatching: @json(__('ui.face_matching')),
        faceAvatarMissing: @json(__('ui.face_avatar_missing')),
        faceNotMatch: @json(__('ui.face_not_match')),
        faceLibraryFailed: @json(__('ui.face_library_failed')),
        errorTitle: @json(__('ui.error_title')),
    };

    function attendanceCamera() {
        return {
            busy: false,
            captured: false,
            stream: null,
            needsReason: false,
            selectedReason: '',
            reasonNote: '',
            reasonError: false,
            pendingPosition: null,
            lastPosition: null,
            lastFaceBox: null,
            faceDetected: false,
            faceSeen: 0,
            fallbackMode: false,
            detectTimer: null,
            detectGeneration: 0,
            previousHtmlOverflow: '',
            previousBodyOverflow: '',
            isDestroyed: false,
            cameraPermissionNeeded: false,
            locationPermissionNeeded: false,
            locationReady: false,

            async init() {
                // Kunci scroll: kamera menutup seluruh viewport, termasuk navbar.
                this.previousHtmlOverflow = document.documentElement.style.overflow;
                this.previousBodyOverflow = document.body.style.overflow;
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';

                // Warm up models + avatar descriptor and GPS while the user aims the camera.
                await this.startCamera();
                this.startPositionWatch();
                this.startFaceAutoCapture();

                this._onResize = () => this.fitGuide();
                window.addEventListener('resize', this._onResize);
            },

            destroy() {
                this.isDestroyed = true;

                if (this._onResize) {
                    window.removeEventListener('resize', this._onResize);
                }

                this.stopFaceAutoCapture();
                this.stopCamera();
                document.documentElement.style.overflow = this.previousHtmlOverflow || '';
                document.body.style.overflow = this.previousBodyOverflow || '';
            },

            async startCamera() {
                // Browsers only expose the camera (and geolocation) on a secure
                // context: HTTPS or localhost. Over plain HTTP navigator.mediaDevices
                // is undefined, which used to surface as a cryptic JS error.
                if (!this.cameraAvailable()) {
                    this.toast(window.isSecureContext ? MSG.cameraUnsupported : MSG.cameraInsecure);

                    return;
                }

                try {
                    const isPortrait = window.innerHeight >= window.innerWidth;
                    const videoConstraints = isPortrait
                        ? {
                            facingMode: 'user',
                            width: { ideal: 720 },
                            height: { ideal: 1280 },
                            aspectRatio: 9 / 16,
                        }
                        : {
                            facingMode: 'user',
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                            aspectRatio: 16 / 9,
                        };

                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: videoConstraints,
                        audio: false,
                    });
                    this.$refs.video.srcObject = this.stream;
                    this.$refs.video.addEventListener('loadedmetadata', () => this.fitGuide(), { once: true });
                    this.fitGuide();
                    await this.$refs.video.play().catch(() => {});
                    this.cameraPermissionNeeded = false;
                } catch (error) {
                    if (error && (error.name === 'NotAllowedError' || error.name === 'SecurityError')) {
                        this.cameraPermissionNeeded = true;
                    } else {
                        this.toast(MSG.cameraError.replace('__MSG__', error.message));
                    }
                }
            },

            async requestCamera() {
                this.cameraPermissionNeeded = false;
                await this.startCamera();
            },

            requestLocation() {
                if (!navigator.geolocation) {
                    this.toast(MSG.geolocationUnsupported);

                    return;
                }

                this.locationPermissionNeeded = false;
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.lastPosition = position;
                        this.locationReady = true;
                    },
                    (error) => {
                        if (error && error.code === 1) {
                            this.locationPermissionNeeded = true;
                        }
                        this.toast(MSG.locationError.replace('__MSG__', error.message));
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 10000 }
                );
            },

            cameraAvailable() {
                return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            },

            fitGuide() {
                const guide = this.$refs.guide;

                if (!guide) {
                    return;
                }

                // Lingkaran besar; video persegi di belakangnya memenuhi lingkaran.
                const size = Math.min(window.innerWidth * 0.82, window.innerHeight * 0.62);
                const videoSize = size + 8;
                guide.style.width = size + 'px';
                guide.style.height = size + 'px';

                if (this.$refs.video) {
                    this.$refs.video.style.width = videoSize + 'px';
                    this.$refs.video.style.height = videoSize + 'px';
                }

                if (this.$refs.preview) {
                    this.$refs.preview.style.width = videoSize + 'px';
                    this.$refs.preview.style.height = videoSize + 'px';
                }
            },

            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
            },

            stopFaceAutoCapture() {
                if (this.detectTimer) {
                    clearTimeout(this.detectTimer);
                    this.detectTimer = null;
                }
            },

            async startFaceAutoCapture() {
                this.stopFaceAutoCapture();

                const generation = ++this.detectGeneration;
                this.faceDetected = false;
                this.faceSeen = 0;
                this.fallbackMode = false;

                const stillCurrent = () => generation === this.detectGeneration
                    && !this.isDestroyed && !this.captured && !this.busy && !this.needsReason;

                // Tunggu library + model deteksi wajah siap.
                const startedAt = Date.now();
                while (stillCurrent() && !(window.FaceMatch && window.faceapi) && Date.now() - startedAt < 15000) {
                    await new Promise(resolve => setTimeout(resolve, 120));
                }

                if (!stillCurrent()) {
                    return;
                }

                if (!window.FaceMatch || !window.faceapi) {
                    // Library gagal dimuat: tetap ambil foto otomatis agar alur tidak macet.
                    this.fallbackMode = true;
                    this.detectTimer = setTimeout(() => {
                        if (stillCurrent()) {
                            this.capture();
                        }
                    }, 2500);

                    return;
                }

                try {
                    await window.FaceMatch.loadDetector();
                } catch (error) {
                    if (!stillCurrent()) {
                        return;
                    }

                    this.fallbackMode = true;
                    this.detectTimer = setTimeout(() => {
                        if (stillCurrent()) {
                            this.capture();
                        }
                    }, 2500);

                    return;
                }

                const detectStartedAt = Date.now();

                const detect = async () => {
                    if (!stillCurrent()) {
                        return;
                    }

                    const video = this.$refs.video;

                    if (!video || video.readyState < 2 || !video.videoWidth) {
                        this.detectTimer = setTimeout(detect, 250);

                        return;
                    }

                    let face = null;

                    try {
                        face = await window.FaceMatch.detect(video);
                    } catch (error) {
                        face = null;
                    }

                    if (!stillCurrent()) {
                        return;
                    }

                    const width = video.videoWidth;
                    const height = video.videoHeight;
                    const centered = face
                        && Math.abs((face.box.x + face.box.width / 2) - width / 2) < width * 0.32
                        && Math.abs((face.box.y + face.box.height / 2) - height / 2) < height * 0.32
                        && face.box.width > width * 0.10;

                    // Deteksi tidak kunjung menemukan wajah (kamera/lingkungan bermasalah):
                    // tetap lanjut ambil foto agar alur absensi tidak macet.
                    if (!centered && this.faceSeen === 0 && Date.now() - detectStartedAt > 15000) {
                        this.stopFaceAutoCapture();
                        this.fallbackMode = true;
                        this.detectTimer = setTimeout(() => {
                            if (stillCurrent()) {
                                this.capture();
                            }
                        }, 1200);

                        return;
                    }

                    if (centered) {
                        this.faceSeen += 1;
                        this.lastFaceBox = {
                            x: face.box.x,
                            y: face.box.y,
                            width: face.box.width,
                            height: face.box.height,
                        };
                        this.faceDetected = true;
                    } else {
                        this.faceSeen = 0;
                        this.faceDetected = false;
                    }

                    if (this.faceSeen >= 1) {
                        this.stopFaceAutoCapture();
                        this.capture();

                        return;
                    }

                    this.detectTimer = setTimeout(detect, 220);
                };

                this.detectTimer = setTimeout(detect, 300);
            },

            withTimeout(promise, ms, message) {
                return new Promise((resolve, reject) => {
                    const timer = setTimeout(() => reject(new Error(message)), ms);

                    promise.then(
                        (value) => {
                            clearTimeout(timer);
                            resolve(value);
                        },
                        (error) => {
                            clearTimeout(timer);
                            reject(error);
                        },
                    );
                });
            },

            async capture() {
                if (this.busy) {
                    return;
                }

                if (!this.stream) {
                    await this.startCamera();

                    if (!this.stream) {
                        return;
                    }
                }

                this.busy = true;
                this.captured = true;

                try {
                    this.drawFrame();
                    this.stopCamera();

                    // Auto-shot sudah dilakukan client; identitas diverifikasi
                    // server (InsightFace). Di sini cukup ambil lokasi.
                    const position = await this.withTimeout(
                        this.getPosition(),
                        5000,
                        'Proses absensi terlalu lama. Coba lagi.',
                    );

                    const distance = this.distanceToOffice(
                        position.coords.latitude,
                        position.coords.longitude
                    );

                    if (WORK_MODE === 'WFO' && OFFICE && distance !== null && distance > OFFICE.radius) {
                        this.pendingPosition = position;
                        this.busy = false;
                        this.needsReason = true;

                        return;
                    }

                    this.submitAttendance(position);
                } catch (error) {
                    this.busy = false;
                    this.captured = false;
                    this.toast(error.message);
                    await this.startCamera();

                    // Beri jeda singkat sebelum mencoba lagi agar pesan error tidak berulang terlalu cepat.
                    setTimeout(() => {
                        if (!this.isDestroyed && !this.busy && !this.captured) {
                            this.startFaceAutoCapture();
                        }
                    }, 1500);
                }
            },

            submitAttendance(position) {
                this.$refs.latitude.value = position.coords.latitude;
                this.$refs.longitude.value = position.coords.longitude;
                this.$refs.address.value = `${position.coords.latitude}, ${position.coords.longitude}`;
                this.$refs.form.submit();
            },

            confirmReason() {
                if (!this.selectedReason) {
                    this.reasonError = true;

                    return;
                }

                this.reasonError = false;
                this.$refs.reason.value = this.selectedReason;
                this.$refs.notes.value = this.reasonNote;
                this.submitAttendance(this.pendingPosition);
            },

            distanceToOffice(latitude, longitude) {
                if (!OFFICE) {
                    return null;
                }

                const earthRadius = 6371000;
                const latFrom = OFFICE.latitude * Math.PI / 180;
                const lonFrom = OFFICE.longitude * Math.PI / 180;
                const latTo = latitude * Math.PI / 180;
                const lonTo = longitude * Math.PI / 180;
                const latDelta = latTo - latFrom;
                const lonDelta = lonTo - lonFrom;
                const angle = 2 * Math.asin(Math.sqrt(
                    Math.pow(Math.sin(latDelta / 2), 2)
                    + Math.cos(latFrom) * Math.cos(latTo) * Math.pow(Math.sin(lonDelta / 2), 2)
                ));

                return angle * earthRadius;
            },

            drawFrame() {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                const context = canvas.getContext('2d');

                // Crop ke kotak wajah + margin supaya wajah utuh meski kamera
                // di-zoom-out / user memegang HP di jarak normal.
                if (this.lastFaceBox) {
                    const box = this.lastFaceBox;
                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;
                    const faceSize = Math.max(box.width, box.height);
                    const cropSize = Math.min(Math.max(faceSize * 1.9, 120), videoWidth, videoHeight);
                    const centerX = box.x + box.width / 2;
                    const centerY = box.y + box.height / 2;
                    const sourceX = Math.min(Math.max(0, centerX - cropSize / 2), videoWidth - cropSize);
                    const sourceY = Math.min(Math.max(0, centerY - cropSize / 2), videoHeight - cropSize);

                    canvas.width = 900;
                    canvas.height = 900;
                    context.drawImage(video, sourceX, sourceY, cropSize, cropSize, 0, 0, canvas.width, canvas.height);

                    this.$refs.photoData.value = canvas.toDataURL('image/jpeg', 0.85);
                    this.$refs.preview.src = this.$refs.photoData.value;

                    return;
                }

                // Fallback: center-crop the video to 3:4 portrait.
                const targetRatio = 3 / 4;
                let sourceWidth = video.videoWidth;
                let sourceHeight = video.videoHeight;

                if (sourceWidth / sourceHeight > targetRatio) {
                    sourceWidth = sourceHeight * targetRatio;
                } else {
                    sourceHeight = sourceWidth / targetRatio;
                }

                const sourceX = (video.videoWidth - sourceWidth) / 2;
                const sourceY = (video.videoHeight - sourceHeight) / 2;

                canvas.width = 900;
                canvas.height = 1200;
                context.drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, canvas.width, canvas.height);

                this.$refs.photoData.value = canvas.toDataURL('image/jpeg', 0.85);
                this.$refs.preview.src = this.$refs.photoData.value;
            },

            getPosition() {
                if (!navigator.geolocation) {
                    return Promise.reject(new Error(MSG.geolocationUnsupported));
                }

                if (!window.isSecureContext) {
                    return Promise.reject(new Error(MSG.locationInsecure));
                }

                // A recent fix from the background watch is good enough; skip the cold GPS wait.
                if (this.lastPosition && this.isUsableFix(this.lastPosition)) {
                    return Promise.resolve(this.lastPosition);
                }

                return new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.lastPosition = position;
                            resolve(position);
                        },
                        (error) => {
                            if (error && error.code === 1) {
                                this.locationPermissionNeeded = true;
                            }
                            reject(new Error(MSG.locationError.replace('__MSG__', error.message)));
                        },
                        { enableHighAccuracy: true, timeout: 20000, maximumAge: 10000 }
                    );
                });
            },

            startPositionWatch() {
                if (!navigator.geolocation || !window.isSecureContext) {
                    return;
                }

                navigator.geolocation.watchPosition(
                    (position) => {
                        if (!this.lastPosition || position.coords.accuracy <= this.lastPosition.coords.accuracy) {
                            this.lastPosition = position;
                        }
                        this.locationReady = true;
                        this.locationPermissionNeeded = false;
                    },
                    (error) => {
                        if (error && error.code === 1) {
                            this.locationPermissionNeeded = true;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 30000 }
                );
            },

            isUsableFix(position) {
                const age = Date.now() - (position.timestamp || 0);
                const maxAccuracy = OFFICE ? Math.min(50, Math.max(20, OFFICE.radius / 2)) : 50;

                return age < 60000 && position.coords.accuracy <= maxAccuracy;
            },

            async prepareFace() {
                const matcher = await this.getFaceMatcher();
                await matcher.prepare(FACE_AVATAR_URL);
            },

            async verifyFace() {
                if (!FACE_AVATAR_URL) {
                    throw new Error(MSG.faceAvatarMissing);
                }

                if (this.facePrepare) {
                    await this.facePrepare;
                }

                const matcher = await this.getFaceMatcher();
                await this.$refs.preview.decode().catch(() => {});

                const result = await matcher.compare(FACE_AVATAR_URL, this.$refs.preview);

                this.$refs.faceScore.value = result.score;

                if (result.score < FACE_THRESHOLD) {
                    throw new Error(MSG.faceNotMatch);
                }
            },

            loadFaceScript(src, allowCdnFallback = false) {
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.async = true;
                    script.src = src + (src.includes('?') ? '&' : '?') + 'v=' + Date.now();
                    script.onload = () => resolve();
                    script.onerror = () => {
                        if (!allowCdnFallback) {
                            reject(new Error('Gagal memuat ' + src));

                            return;
                        }

                        const fallback = document.createElement('script');
                        fallback.async = true;
                        fallback.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.15/dist/face-api.min.js';
                        fallback.onload = () => resolve();
                        fallback.onerror = () => reject(new Error('Gagal memuat library face recognition'));
                        document.head.appendChild(fallback);
                    };
                    document.head.appendChild(script);
                });
            },

            async getFaceMatcher() {
                const waitForMatcher = async (ms) => {
                    const startedAt = Date.now();

                    while (!window.FaceMatch && Date.now() - startedAt < ms) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }
                };

                await waitForMatcher(5000);

                // Script bisa gagal dimuat (jaringan/cache). Coba muat ulang
                // face-api & face-match sekali secara dinamis sebelum menyerah.
                if (!window.FaceMatch) {
                    try {
                        if (!window.faceapi) {
                            await this.loadFaceScript(FACE_API_SRC, true);
                        }

                        if (window.faceapi && !window.FaceMatch) {
                            await this.loadFaceScript(FACE_MATCH_SRC, false);
                        }
                    } catch (error) {
                        // lanjut ke pengecekan di bawah; pesan error tetap jelas
                    }

                    await waitForMatcher(10000);
                }

                if (!window.FaceMatch) {
                    throw new Error(MSG.faceLibraryFailed);
                }

                return window.FaceMatch;
            },

            toast(message) {
                if (window.showToast) {
                    window.showToast(message, 5000);

                    return;
                }

                alert(message);
            },
        };
    }
</script>
