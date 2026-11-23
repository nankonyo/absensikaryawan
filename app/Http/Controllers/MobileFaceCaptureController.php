<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

class MobileFaceCaptureController extends Controller
{
    /**
     * Issue a short-lived signed URL for the mobile face capture screen.
     */
    public function url(Request $request)
    {
        $user = $request->user();
        $avatar = $this->avatarUrl($user);
        $faces = app(FaceRecognitionService::class);
        $enforced = $faces->isEnabled() && (bool) Setting::get('mobile_face_recognition_enabled', false);

        if (! $avatar) {
            return response()->json([
                'url' => null,
                'reason' => 'no_avatar',
                'enforced' => $enforced,
            ]);
        }

        $mode = $request->query('mode') === 'out' ? 'out' : 'in';
        $warmup = $request->boolean('warmup');

        return response()->json([
            'url' => URL::temporarySignedRoute('mobile.face-capture', now()->addMinutes(10), [
                'user' => $user->id,
                'mode' => $mode,
                'warmup' => $warmup ? 1 : 0,
            ]),
            'threshold' => $faces->getThreshold(),
            'enforced' => $enforced,
        ]);
    }

    /**
     * Camera + face-api capture screen embedded in the mobile WebView.
     * The signed URL is the only authentication required: the result is sent
     * back to the native app, which then calls the authenticated API.
     */
    public function show(Request $request, User $user, FaceRecognitionService $faces)
    {
        $avatar = $this->avatarUrl($user);

        abort_if(! $avatar, 404);

        return view('mobile.face-capture', [
            'avatarUrl' => $avatar,
            'threshold' => $faces->getThreshold(),
            'mode' => $request->query('mode') === 'out' ? 'out' : 'in',
            'warmup' => $request->boolean('warmup'),
        ]);
    }

    private function avatarUrl(User $user): ?string
    {
        if (! $user->avatar) {
            return null;
        }

        $path = Storage::disk('public')->url($user->avatar);

        return str_starts_with($path, 'http') ? $path : url($path);
    }
}
