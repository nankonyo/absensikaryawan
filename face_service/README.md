# Face Verification Service

Service verifikasi wajah server-side (InsightFace + ONNX Runtime), berjalan
internal di `127.0.0.1:9997`. Client hanya melakukan deteksi wajah + auto-shot;
keputusan identitas dilakukan service ini terhadap foto profil user.

## Menjalankan dengan Docker

```bash
cd face_service
docker compose build
docker compose up -d
curl http://127.0.0.1:9997/health
```

Container memakai model InsightFace `buffalo_s` dan me-mount model dari host:

```
/home/www/.insightface:/root/.insightface:ro
```

Jika model belum ada di host, jalankan service sekali tanpa mount read-only
(atau siapkan volume) agar model diunduh otomatis.

## Endpoint

- `GET /health`
- `POST /verify`
  ```json
  {
    "image_base64": "...",
    "reference_base64": "...",
    "threshold": 0.40
  }
  ```
  Response: `match`, `similarity`, `threshold`, `elapsed_ms`.

## Konfigurasi Laravel

```env
FACE_SERVICE_URL=http://127.0.0.1:9997
FACE_SERVICE_THRESHOLD=0.40
FACE_SERVICE_TIMEOUT=5
```

Port 9997 sengaja hanya listen di localhost; jangan diekspos ke publik.
