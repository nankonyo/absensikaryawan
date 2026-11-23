<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceVerificationService
{
    /**
     * Verifikasi wajah di server (InsightFace service di 127.0.0.1:9997).
     * Skor dari client tidak dipakai untuk keputusan akhir.
     */
    public function verify(User $user, ?string $photoData): array
    {
        if (! $photoData) {
            return [
                'success' => false,
                'message' => 'Foto absensi tidak ditemukan. Ulangi pengambilan foto.',
            ];
        }

        if (! $user->avatar) {
            return [
                'success' => false,
                'message' => __('ui.avatar_not_uploaded'),
            ];
        }

        $avatarPath = storage_path('app/public/'.$user->avatar);

        if (! is_file($avatarPath)) {
            return [
                'success' => false,
                'message' => 'Foto profil tidak ditemukan. Hubungi admin.',
            ];
        }

        $reference = base64_encode(file_get_contents($avatarPath));
        $baseUrl = rtrim((string) config('services.face_service.url', 'http://127.0.0.1:9997'), '/');
        $threshold = (float) config('services.face_service.threshold', 0.45);
        $timeout = (int) config('services.face_service.timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->post($baseUrl.'/verify', [
                    'image_base64' => $photoData,
                    'reference_base64' => $reference,
                    'threshold' => $threshold,
                ]);
        } catch (\Throwable $error) {
            Log::warning('Face service tidak tersedia: '.$error->getMessage());

            return [
                'success' => false,
                'message' => 'Layanan verifikasi wajah tidak tersedia. Coba lagi.',
            ];
        }

        if ($response->failed()) {
            $detail = $response->json('detail');
            $message = is_string($detail) && $detail !== ''
                ? $detail
                : 'Verifikasi wajah gagal. Coba lagi.';

            return [
                'success' => false,
                'message' => $message,
            ];
        }

        $data = $response->json();
        $similarity = (float) ($data['similarity'] ?? 0);
        $score = (int) round(max(0, min(1, $similarity)) * 100);
        $matched = (bool) ($data['match'] ?? false);

        if (! $matched) {
            return [
                'success' => false,
                'score' => $score,
                'similarity' => $similarity,
                'message' => __('ui.face_service_mismatch'),
            ];
        }

        return [
            'success' => true,
            'score' => $score,
            'similarity' => $similarity,
        ];
    }
}
