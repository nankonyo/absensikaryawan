(function () {
const faceapi = window.faceapi;
if (!faceapi) { console.error('face-api CDN belum termuat'); return; }

const MODEL_URL = window.FACE_MODEL_URL || '/models';
let detectorPromise = null;
let recognitionPromise = null;
const referencePromises = new Map();

// Detektor wajah kecil (~190 KB) dimuat duluan supaya preview bisa langsung
// mendeteksi. Model pengenalan (~6,4 MB) dimuat di belakang sambil user
// memposisikan wajah.
function loadDetector() {
    if (!detectorPromise) {
        detectorPromise = faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    }

    return detectorPromise;
}

function loadRecognition() {
    if (!recognitionPromise) {
        recognitionPromise = Promise.all([
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
    }

    return recognitionPromise;
}

async function loadModels() {
    await Promise.all([loadDetector(), loadRecognition()]);
}

async function loadImage(src, maxSize = 640) {
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.src = src;
    await image.decode();

    const width = image.naturalWidth || image.width;
    const height = image.naturalHeight || image.height;
    const scale = Math.min(1, maxSize / Math.max(width, height));

    if (!scale || scale >= 1) {
        return image;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(width * scale));
    canvas.height = Math.max(1, Math.round(height * scale));
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

    return canvas;
}

function readCachedDescriptor(url) {
    try {
        const raw = localStorage.getItem('faceapi.descriptor:' + url);

        if (!raw) {
            return null;
        }

        const data = JSON.parse(raw);

        if (data && Array.isArray(data.descriptor) && data.descriptor.length === 128) {
            return new Float32Array(data.descriptor);
        }
    } catch (error) {
        // localStorage bisa disabled di WebView tertentu; abaikan.
    }

    return null;
}

function writeCachedDescriptor(url, descriptor) {
    try {
        localStorage.setItem('faceapi.descriptor:' + url, JSON.stringify({
            descriptor: Array.from(descriptor),
            at: Date.now(),
        }));
    } catch (error) {
        // abaikan
    }
}

async function descriptor(input) {
    await loadModels();

    // Beberapa foto (mis. avatar close-up) hanya terdeteksi pada ukuran tertentu.
    // Coba beberapa inputSize sebelum menyerah agar avatar tidak mudah gagal.
    for (const inputSize of [224, 320, 416, 608]) {
        const result = await faceapi
            .detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({ inputSize, scoreThreshold: 0.35 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (result?.descriptor) {
            return result.descriptor;
        }
    }

    return null;
}

/**
 * Compute the reference (avatar) descriptor once and reuse it for every capture.
 */
async function prepare(referenceUrl) {
    if (!referencePromises.has(referenceUrl)) {
        const promise = (async () => {
            const cached = readCachedDescriptor(referenceUrl);

            if (cached) {
                return cached;
            }

            await loadModels();
            const reference = await descriptor(await loadImage(referenceUrl));

            if (!reference) {
                throw new Error('Wajah tidak terdeteksi pada avatar. Minta admin unggah foto wajah yang jelas.');
            }

            writeCachedDescriptor(referenceUrl, reference);

            return reference;
        })();

        promise.catch(() => referencePromises.delete(referenceUrl));
        referencePromises.set(referenceUrl, promise);
    }

    return referencePromises.get(referenceUrl);
}

/**
 * Compare a live selfie against the user's avatar.
 * Score is a 0-100 similarity derived from the 128-D face descriptor distance.
 */
async function compare(referenceUrl, liveSource) {
    const [referenceDescriptor, liveDescriptor] = await Promise.all([
        prepare(referenceUrl),
        descriptor(liveSource),
    ]);

    if (!liveDescriptor) {
        throw new Error('Wajah tidak terdeteksi pada selfie. Pastikan wajah terlihat jelas dan pencahayaan cukup.');
    }

    const distance = faceapi.euclideanDistance(referenceDescriptor, liveDescriptor);
    const score = Math.round(Math.max(0, Math.min(100, (1 - (distance * distance) / 2) * 100)) * 100) / 100;

    return { score, distance };
}

/**
 * Detect a single face (dipakai untuk auto-capture saat wajah di lingkaran).
 */
async function detect(input) {
    await loadDetector();

    const options = (inputSize) => new faceapi.TinyFaceDetectorOptions({
        inputSize,
        scoreThreshold: 0.4,
    });

    // 320 cepat untuk wajah normal; 416 hanya fallback jika wajah kecil/jauh.
    let result = await faceapi.detectSingleFace(input, options(224));

    if (!result) {
        result = await faceapi.detectSingleFace(input, options(416));
    }

    return result ? { box: result.box, score: result.score } : null;
}

window.FaceMatch = { load: loadModels, loadDetector, loadRecognition, prepare, compare, detect };

})();
