<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Face Capture</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0;
            height: 100%;
            background: #000;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }
        #stage { position: fixed; inset: 0; overflow: hidden; background: #000; }
        #video, #preview {
            position: absolute;
            left: 50%;
            top: 50%;
            width: min(82vw, 62vh);
            height: min(82vw, 62vh);
            transform: translate(-50%, -50%);
            object-fit: cover;
            border-radius: 50%;
            background: #000;
        }
        #preview { display: none; }
        .mask {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .circle {
            position: relative;
            width: min(82vw, 62vh);
            aspect-ratio: 1;
            border-radius: 9999px;
            border: 4px solid {{ $mode === 'out' ? '#DC2626' : '#16A34A' }};
            box-shadow: 0 0 0 9999px #000;
            transition: transform .2s ease;
        }
        .circle.detected { transform: scale(1.05); }
        .badge {
            position: absolute;
            top: calc(14px + env(safe-area-inset-top));
            right: 14px;
            z-index: 10;
            padding: 6px 12px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
        }
        .cancel {
            position: absolute;
            top: calc(12px + env(safe-area-inset-top));
            left: 14px;
            z-index: 20;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            font-size: 24px;
            line-height: 1;
            font-family: inherit;
        }
        .status {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: calc(30px + env(safe-area-inset-bottom));
            z-index: 10;
            margin: 0;
            text-align: center;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
        }
        .status strong { color: #86efac; font-weight: 700; }
        .status.error { color: #fca5a5; }
        .progress-ring {
            position: absolute;
            inset: -12px;
            z-index: 10;
            display: none;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: var(--accent, #16A34A);
            border-right-color: var(--accent, #16A34A);
            animation: ring-spin 1s linear infinite;
            pointer-events: none;
        }
        .progress-ring.show { display: block; }
        @keyframes ring-spin { to { transform: rotate(360deg); } }
        .activate {
            position: absolute;
            inset: 0;
            z-index: 15;
            display: none;
            border: 0;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            font-family: inherit;
            font-size: 16px;
            font-weight: 700;
        }

    </style>
</head>
<body>
<div id="stage">
    <video id="video" autoplay playsinline muted></video>
    <img id="preview" alt="">
    <button type="button" class="activate" id="activate">Ketuk untuk mengaktifkan kamera</button>

    <span class="badge">{{ $mode === 'out' ? 'Keluar' : 'Masuk' }}</span>
    <button type="button" class="cancel" id="cancel" aria-label="Batal">&times;</button>

    <div class="mask">
        <div class="circle" id="circle">
            <div class="progress-ring" id="progressRing"></div>
        </div>
    </div>

    <p class="status" id="status">Menyiapkan kamera…</p>
</div>

<script>
    window.FACE_MODEL_URL = @json(asset('models'));
    window.FACE_CAPTURE = {
        avatarUrl: @json($avatarUrl),
        threshold: @json($threshold),
        mode: @json($mode),
        warmup: @json($warmup),
    };
</script>
<script defer src="{{ asset('cdn/face-api.min.js') }}?v={{ @filemtime(public_path('cdn/face-api.min.js')) }}"></script>
<script defer src="{{ asset('cdn/face-match.js') }}?v={{ @filemtime(public_path('cdn/face-match.js')) }}"></script>
<script>
(function () {
    var cfg = window.FACE_CAPTURE || {};
    var video = document.getElementById('video');
    var preview = document.getElementById('preview');
    var circle = document.getElementById('circle');
    var status = document.getElementById('status');
    var progressRing = document.getElementById('progressRing');
    var activate = document.getElementById('activate');

    function fitGuide() {
        // Lingkaran tetap besar; yang di-zoom-out adalah isi video (contain).
        var size = Math.min(window.innerWidth * 0.82, window.innerHeight * 0.62);
        var videoSize = size + 8;

        circle.style.width = size + 'px';
        circle.style.height = size + 'px';
        video.style.width = videoSize + 'px';
        video.style.height = videoSize + 'px';
        preview.style.width = videoSize + 'px';
        preview.style.height = videoSize + 'px';
    }

    window.addEventListener('resize', fitGuide);

    var stream = null;
    var captured = false;
    var busy = false;
    var stopped = false;
    var faceSeen = 0;
    var detectTimer = null;
    var lastFaceBox = null;
    var sessionActive = false;

    var copy = {
        guide: 'Posisikan wajah di lingkaran',
        detected: '<strong>Wajah terdeteksi, tahan sebentar…</strong>',
        verified: '<strong>Wajah terverifikasi</strong>',
    };

    function post(type, payload) {
        var data = Object.assign({ type: type }, payload || {});
        window.__FACE_CAPTURE_LAST__ = data;

        try {
            if (window.ReactNativeWebView && window.ReactNativeWebView.postMessage) {
                window.ReactNativeWebView.postMessage(JSON.stringify(data));
            } else {
                console.log('FACE_CAPTURE', data.type, data.message || '');
            }
        } catch (error) {
            console.error('postMessage gagal', error);
        }
    }

    function setStatus(html, isError) {
        status.innerHTML = html;
        status.className = isError ? 'status error' : 'status';
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
    }

    function stopDetection() {
        if (detectTimer) {
            clearTimeout(detectTimer);
            detectTimer = null;
        }
    }

    function fail(message, fatal) {
        stopDetection();
        setStatus(message, true);
        post('error', { message: message, fatal: !!fatal });

        if (fatal) {
            progressRing.classList.remove('show');
            status.style.display = '';
        }
    }

    async function playVideo() {
        try {
            video.muted = true;
            await video.play();
            activate.style.display = 'none';

            return true;
        } catch (error) {
            // WebView terkadang menolak autoplay sampai ada gesture pengguna.
            activate.style.display = 'block';

            return false;
        }
    }

    function cameraErrorMessage(error) {
        if (!error) {
            return 'Gagal mengakses kamera.';
        }

        if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
            return 'Izin kamera ditolak. Buka Pengaturan aplikasi dan izinkan akses kamera.';
        }

        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            return 'Kamera tidak ditemukan pada perangkat ini.';
        }

        return 'Gagal mengakses kamera: ' + (error.message || error.name || 'tidak diketahui');
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            fail('Kamera tidak tersedia di perangkat ini.', true);

            return false;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 720 },
                    height: { ideal: 1280 },
                    aspectRatio: 9 / 16,
                },
                audio: false,
            });

            video.srcObject = stream;
            video.addEventListener('loadedmetadata', fitGuide, { once: true });
            fitGuide();

            var playing = await playVideo();

            if (!playing) {
                setStatus('Ketuk layar untuk mengaktifkan kamera.');

                return false;
            }

            setStatus(copy.guide);
            post('ready');

            return true;
        } catch (error) {
            fail(cameraErrorMessage(error), true);

            return false;
        }
    }

    function drawFrame() {
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');
        var videoWidth = video.videoWidth;
        var videoHeight = video.videoHeight;

        if (lastFaceBox) {
            var faceSize = Math.max(lastFaceBox.width, lastFaceBox.height);
            var cropSize = Math.min(Math.max(faceSize * 1.9, 120), videoWidth, videoHeight);
            var faceCenterX = lastFaceBox.x + lastFaceBox.width / 2;
            var faceCenterY = lastFaceBox.y + lastFaceBox.height / 2;
            var faceX = Math.min(Math.max(0, faceCenterX - cropSize / 2), videoWidth - cropSize);
            var faceY = Math.min(Math.max(0, faceCenterY - cropSize / 2), videoHeight - cropSize);

            canvas.width = 800;
            canvas.height = 800;
            context.drawImage(video, faceX, faceY, cropSize, cropSize, 0, 0, canvas.width, canvas.height);

            return canvas.toDataURL('image/jpeg', 0.85);
        }

        var size = Math.min(videoWidth, videoHeight);
        var sourceX = (videoWidth - size) / 2;
        var sourceY = (videoHeight - size) / 2;

        canvas.width = 800;
        canvas.height = 800;
        context.drawImage(video, sourceX, sourceY, size, size, 0, 0, canvas.width, canvas.height);

        return canvas.toDataURL('image/jpeg', 0.85);
    }

    function withTimeout(promise, ms, message) {
        return new Promise(function (resolve, reject) {
            var timer = setTimeout(function () {
                reject(new Error(message));
            }, ms);

            promise.then(function (value) {
                clearTimeout(timer);
                resolve(value);
            }, function (error) {
                clearTimeout(timer);
                reject(error);
            });
        });
    }

    async function capture() {
        if (busy || captured) {
            return;
        }

        busy = true;
        captured = true;
        stopDetection();
        progressRing.classList.add('show');
        status.style.display = 'none';

        var photoData = drawFrame();
        preview.src = photoData;
        preview.style.display = 'block';
        video.style.display = 'none';
        stopCamera();

        // Deteksi wajah & auto-shot dilakukan di client; verifikasi identitas
        // dilakukan server-side (InsightFace) agar tidak bisa dimanipulasi.
        post('captured', { photo_data: photoData });
    }

    async function retry(message) {
        captured = false;
        busy = false;
        faceSeen = 0;
        progressRing.classList.remove('show');
        status.style.display = '';
        preview.style.display = 'none';
        video.style.display = 'block';
        setStatus(message, true);

        if (!stream) {
            var started = await startCamera();

            if (!started) {
                return;
            }
        }

        setStatus(message, true);
        setTimeout(function () {
            startDetection();
        }, 1200);
    }

    async function startDetection() {
        stopDetection();

        var ready = false;
        var startedAt = Date.now();

        while (!stopped && !captured && !(window.FaceMatch && window.faceapi) && Date.now() - startedAt < 12000) {
            await new Promise(function (resolve) { setTimeout(resolve, 120); });
        }

        if (stopped || captured) {
            return;
        }

        if (!window.FaceMatch || !window.faceapi) {
            fail('Library deteksi wajah gagal dimuat.', true);

            return;
        }

        // Siapkan descriptor avatar + model pengenalan sambil kamera tampil,
        // supaya saat capture verifikasi tidak menunggu lama.
        setStatus('Memuat detektor wajah…');

        try {
            await window.FaceMatch.loadDetector();
        } catch (error) {
            fail('Model deteksi wajah gagal dimuat.', true);

            return;
        }

        if (stopped || captured) {
            return;
        }

        setStatus(copy.guide);

        var detect = async function () {
            if (stopped || captured || busy) {
                return;
            }

            if (!video.videoWidth || video.readyState < 2) {
                detectTimer = setTimeout(detect, 250);

                return;
            }

            var face = null;

            try {
                face = await window.FaceMatch.detect(video);
            } catch (error) {
                face = null;
            }

            if (stopped || captured || busy) {
                return;
            }

            var width = video.videoWidth;
            var height = video.videoHeight;
            var centered = face
                && Math.abs((face.box.x + face.box.width / 2) - width / 2) < width * 0.32
                && Math.abs((face.box.y + face.box.height / 2) - height / 2) < height * 0.32
                && face.box.width > width * 0.10;

            if (centered) {
                faceSeen += 1;
                lastFaceBox = {
                    x: face.box.x,
                    y: face.box.y,
                    width: face.box.width,
                    height: face.box.height,
                };
                circle.classList.add('detected');
                setStatus(copy.detected);
            } else {
                faceSeen = 0;
                circle.classList.remove('detected');
                setStatus(copy.guide);
            }

            if (faceSeen >= 1) {
                stopDetection();
                capture();

                return;
            }

            detectTimer = setTimeout(detect, 220);
        };

        detectTimer = setTimeout(detect, 300);
    }

    activate.addEventListener('click', async function () {
        activate.style.display = 'none';
        var playing = await playVideo();

        if (playing) {
            setStatus(copy.guide);
            startDetection();
        }
    });

    async function warmupEngine() {
        if (!cfg.avatarUrl) {
            fail('Avatar wajah belum diatur. Hubungi admin.', true);

            return;
        }

        setStatus('Menyiapkan verifikasi wajah…');

        var startedAt = Date.now();

        while (!sessionActive && Date.now() - startedAt < 15000 && !(window.FaceMatch && window.faceapi)) {
            await new Promise(function (resolve) { setTimeout(resolve, 120); });
        }

        if (!window.FaceMatch || !window.faceapi) {
            fail('Library deteksi wajah gagal dimuat.', true);

            return;
        }

        try {
            await window.FaceMatch.loadDetector();
        } catch (error) {
            // startSession akan menampilkan error yang spesifik.
        }

        if (!sessionActive) {
            setStatus('Kamera siap');
        }

        post('ready');
    }

    async function startSession(mode) {
        sessionActive = true;
        stopped = false;
        captured = false;
        busy = false;
        faceSeen = 0;
        lastFaceBox = null;
        progressRing.classList.remove('show');
        status.style.display = '';
        preview.style.display = 'none';
        video.style.display = 'block';
        circle.classList.remove('detected');
        activate.style.display = 'none';
        document.querySelector('.badge').textContent = mode === 'out' ? 'Keluar' : 'Masuk';
        circle.style.borderColor = mode === 'out' ? '#DC2626' : '#16A34A';
        circle.style.setProperty('--accent', mode === 'out' ? '#DC2626' : '#16A34A');
        setStatus('Menyiapkan kamera…');

        var started = await startCamera();

        if (started) {
            startDetection();
        }
    }

    window.__faceCaptureStart = function (mode) {
        startSession(mode === 'out' ? 'out' : 'in');
    };

    window.__faceCaptureStop = function () {
        sessionActive = false;
        stopped = true;
        stopDetection();
        stopCamera();
        captured = false;
        busy = false;
        faceSeen = 0;
        lastFaceBox = null;
        progressRing.classList.remove('show');
        status.style.display = '';
        preview.style.display = 'none';
        video.style.display = 'block';
        circle.classList.remove('detected');
        activate.style.display = 'none';
        setStatus('Kamera siap');
    };

    document.getElementById('cancel').addEventListener('click', function () {
        sessionActive = false;
        stopped = true;
        stopDetection();
        stopCamera();
        post('cancel');
    });

    window.addEventListener('pagehide', function () {
        stopDetection();
        stopCamera();
    });

    post('loaded');

    (async function init() {
        if (!cfg.avatarUrl) {
            fail('Avatar wajah belum diatur. Hubungi admin.', true);

            return;
        }

        if (cfg.warmup) {
            warmupEngine();

            return;
        }

        startSession(cfg.mode || 'in');
    })();
})();
</script>
</body>
</html>
