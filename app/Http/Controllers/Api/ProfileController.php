<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['user' => AuthController::userPayload($request->user())]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json([
            'message' => 'Profil diperbarui.',
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }
}
