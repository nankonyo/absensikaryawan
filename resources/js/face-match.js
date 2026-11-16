import * as faceapi from '@vladmandic/face-api';

const MODEL_URL = '/models';
let modelsPromise = null;
const referencePromises = new Map();

async function loadModels() {
    if (!modelsPromise) {
        modelsPromise = Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
    }

    return modelsPromise;
}

async function loadImage(src) {
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.src = src;
    await image.decode();

    return image;
}

async function descriptor(input) {
    const result = await faceapi
        .detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 }))
        .withFaceLandmarks()
        .withFaceDescriptor();

    return result?.descriptor ?? null;
}

/**
 * Compute the reference (avatar) descriptor once and reuse it for every capture.
 */
async function prepare(referenceUrl) {
    if (!referencePromises.has(referenceUrl)) {
        const promise = (async () => {
            await loadModels();
            const reference = await descriptor(await loadImage(referenceUrl));

            if (!reference) {
                throw new Error('Wajah tidak terdeteksi pada avatar. Minta admin unggah foto wajah yang jelas.');
            }

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
    await loadModels();

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

window.FaceMatch = { load: loadModels, prepare, compare };
