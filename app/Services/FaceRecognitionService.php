<?php

namespace App\Services;

use App\Models\Setting;

class FaceRecognitionService
{
    /**
     * Face matching runs in the browser (face-api.js) against the user's avatar.
     * The server only enforces the similarity score and threshold.
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('face_recognition_enabled', true);
    }

    /**
     * Get the minimum similarity score (0-100).
     */
    public function getThreshold(): int
    {
        return (int) Setting::get('face_recognition_threshold', 80);
    }
}
