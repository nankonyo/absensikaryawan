import base64
import hashlib
import os
import time
from collections import OrderedDict
from typing import Optional

import cv2
import numpy as np
from fastapi import FastAPI, HTTPException
from insightface.app import FaceAnalysis
from pydantic import BaseModel

MODEL_NAME = os.getenv("FACE_MODEL", "buffalo_s")
DEFAULT_THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.45"))
DET_SIZE = int(os.getenv("FACE_DET_SIZE", "640"))
MAX_CACHE = int(os.getenv("FACE_CACHE", "512"))

app = FastAPI(title="Absensi Face Service", version="1.0.0")

face_app = FaceAnalysis(name=MODEL_NAME, providers=["CPUExecutionProvider"])
face_app.prepare(ctx_id=-1, det_size=(DET_SIZE, DET_SIZE))

reference_cache: "OrderedDict[str, np.ndarray]" = OrderedDict()


class VerifyRequest(BaseModel):
    image_base64: str
    reference_base64: str
    threshold: Optional[float] = None


def decode_image(data: str):
    if not data:
        raise HTTPException(status_code=400, detail="Gambar kosong")

    if data.startswith("data:"):
        data = data.split(",", 1)[1]

    try:
        raw = base64.b64decode(data)
    except Exception:
        raise HTTPException(status_code=400, detail="Base64 tidak valid")

    img = cv2.imdecode(np.frombuffer(raw, dtype=np.uint8), cv2.IMREAD_COLOR)

    if img is None:
        raise HTTPException(status_code=400, detail="Gambar tidak valid")

    return img, raw


def pad_image(img: np.ndarray, pct: float) -> np.ndarray:
    height, width = img.shape[:2]
    pad = int(max(height, width) * pct)

    return cv2.copyMakeBorder(
        img,
        pad,
        pad,
        pad,
        pad,
        cv2.BORDER_CONSTANT,
        value=(255, 255, 255),
    )


def pick_face(img: np.ndarray, label: str):
    faces = face_app.get(img, max_num=0)

    # Avatar close-up kadang tidak terdeteksi saat wajah memenuhi frame.
    # Tambahkan margin dan coba lagi sebelum menyerah.
    if not faces:
        faces = face_app.get(pad_image(img, 0.25), max_num=0)

    if not faces:
        faces = face_app.get(pad_image(img, 0.50), max_num=0)

    if not faces:
        raise HTTPException(status_code=422, detail=f"Wajah tidak terdeteksi pada {label}")

    faces.sort(
        key=lambda face: (face.bbox[2] - face.bbox[0]) * (face.bbox[3] - face.bbox[1]),
        reverse=True,
    )

    return faces[0]


def embedding(img: np.ndarray, label: str) -> np.ndarray:
    face = pick_face(img, label)
    return np.asarray(face.normed_embedding, dtype=np.float32)


@app.get("/health")
def health():
    return {
        "ok": True,
        "model": MODEL_NAME,
        "threshold": DEFAULT_THRESHOLD,
        "cache_size": len(reference_cache),
    }


@app.post("/verify")
def verify(req: VerifyRequest):
    started = time.time()

    live_img, _ = decode_image(req.image_base64)
    ref_img, ref_raw = decode_image(req.reference_base64)

    cache_key = hashlib.sha256(ref_raw).hexdigest()
    ref_embedding = reference_cache.get(cache_key)

    if ref_embedding is None:
        ref_embedding = embedding(ref_img, "foto profil")
        reference_cache[cache_key] = ref_embedding

        if len(reference_cache) > MAX_CACHE:
            reference_cache.popitem(last=False)
    else:
        reference_cache.move_to_end(cache_key)

    live_embedding = embedding(live_img, "foto absensi")
    similarity = float(np.dot(ref_embedding, live_embedding))
    threshold = req.threshold if req.threshold is not None else DEFAULT_THRESHOLD

    return {
        "match": similarity >= threshold,
        "similarity": round(similarity, 6),
        "threshold": threshold,
        "elapsed_ms": round((time.time() - started) * 1000),
    }
