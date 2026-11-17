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
    $officeData = $office ? [
        'latitude' => (float) $office->latitude,
        'longitude' => (float) $office->longitude,
        'radius' => (float) $office->radius,
    ] : null;
@endphp

<div class="-m-4 lg:-m-6 h-dvh overflow-hidden bg-black" x-data="attendanceCamera()" x-init="init()">
    <div class="flex h-full w-full items-center justify-center overflow-hidden">
        <!-- Camera: fills the phone screen, 3:4 portrait on larger screens -->
        <div class="camera-frame">
            <video x-ref="video" autoplay playsinline muted
                   x-show="!captured"
                   class="absolute inset-0 h-full w-full object-cover"></video>

            <img x-ref="preview" x-show="captured" x-cloak
                 class="absolute inset-0 h-full w-full object-cover" alt="{{ __('ui.selfie_photo') }}">

            <!-- Transparent badge: Masuk / Keluar -->
            <span class="absolute right-3 top-3 z-10 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                {{ $label }}
            </span>

            <!-- Face position guide: vertical oval -->
            <div x-show="!captured" x-cloak
                 class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="h-[42%] w-[52%] rounded-[50%] border-4 {{ $ring }} shadow-[0_0_0_9999px_rgba(0,0,0,0.45)]"></div>
            </div>

            <p x-show="!captured" x-cloak
               class="pointer-events-none absolute inset-x-0 bottom-28 text-center text-sm font-medium text-white drop-shadow">
                {{ __('ui.face_position_guide') }}
            </p>

            <!-- Loading spinner while getting location and matching face -->
            <div x-show="busy" x-cloak
                 class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-black/60 text-white">
                <svg class="h-12 w-12 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p x-text="busyLabel" class="text-sm font-medium"></p>
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

            <!-- Single action: pinned to the bottom of the camera preview -->
            <div class="absolute inset-x-0 bottom-0 z-30 p-4">
                <button type="button"
                        @click="capture()"
                        :disabled="busy"
                        class="mx-auto flex w-full max-w-sm items-center justify-center gap-2 rounded-full {{ $button }} px-6 py-4 font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ __('ui.capture_photo') }}
                </button>
            </div>
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

@if (config('app.asset_mode') === 'cdn')
    <script>window.FACE_MODEL_URL = "{{ asset('models') }}";</script>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.15/dist/face-api.min.js"></script>
    <script src="{{ asset('cdn/face-match.js') }}?v={{ @filemtime(public_path('cdn/face-match.js')) }}"></script>
@else
    @vite('resources/js/face-match.js')
@endif

<script>
    const FACE_ENABLED = @json(app(\App\Services\FaceRecognitionService::class)->isEnabled());
    const FACE_AVATAR_URL = @json(auth()->user()->avatar_url);
    const FACE_THRESHOLD = {{ (int) app(\App\Services\FaceRecognitionService::class)->getThreshold() }};
    const OFFICE = @json($officeData);

    const MSG = {
        cameraError: @json(__('ui.camera_error', ['message' => '__MSG__'])),
        locationError: @json(__('ui.location_error', ['message' => '__MSG__'])),
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
            busyLabel: '',
            captured: false,
            stream: null,
            needsReason: false,
            selectedReason: '',
            reasonNote: '',
            reasonError: false,
            pendingPosition: null,
            lastPosition: null,
            facePrepare: null,

            async init() {
                // Warm up models + avatar descriptor and GPS while the user aims the camera.
                if (FACE_ENABLED && FACE_AVATAR_URL) {
                    this.facePrepare = this.prepareFace();
                    this.facePrepare.catch(() => {});
                }

                await this.startCamera();
                this.startPositionWatch();
            },

            async startCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: { ideal: 1280 },
                            height: { ideal: 960 },
                            aspectRatio: 3 / 4,
                        },
                        audio: false,
                    });
                    this.$refs.video.srcObject = this.stream;
                    await this.$refs.video.play().catch(() => {});
                } catch (error) {
                    this.toast(MSG.cameraError.replace('__MSG__', error.message));
                }
            },

            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
            },

            async capture() {
                if (this.busy || !this.stream) {
                    return;
                }

                this.busy = true;
                this.busyLabel = MSG.processing;
                this.captured = true;

                try {
                    this.drawFrame();
                    this.stopCamera();

                    // Location lookup and face matching run together: wait for the slower one only.
                    const [position] = await Promise.all([
                        this.getPosition(),
                        FACE_ENABLED ? this.verifyFace() : Promise.resolve(),
                    ]);

                    const distance = this.distanceToOffice(
                        position.coords.latitude,
                        position.coords.longitude
                    );

                    if (OFFICE && distance !== null && distance > OFFICE.radius) {
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

                // Center-crop the video to 3:4 portrait to match what the user sees.
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
                        (error) => reject(new Error(MSG.locationError.replace('__MSG__', error.message))),
                        { enableHighAccuracy: true, timeout: 20000, maximumAge: 10000 }
                    );
                });
            },

            startPositionWatch() {
                if (!navigator.geolocation) {
                    return;
                }

                navigator.geolocation.watchPosition(
                    (position) => {
                        if (!this.lastPosition || position.coords.accuracy <= this.lastPosition.coords.accuracy) {
                            this.lastPosition = position;
                        }
                    },
                    () => {},
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

            async getFaceMatcher() {
                const startedAt = Date.now();

                while (!window.FaceMatch && Date.now() - startedAt < 15000) {
                    await new Promise(resolve => setTimeout(resolve, 100));
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
