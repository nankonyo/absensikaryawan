<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function videos(Request $request)
    {
        abort_unless($request->user()?->canFeature('internalapp.content.video-tutorials'), 403, 'Akses tidak diizinkan.');

        return response()->json([
            'items' => DB::table('internal_video_tutorials')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function dokumentasi(Request $request)
    {
        abort_unless($request->user()?->canFeature('internalapp.content.dokumentasi'), 403, 'Akses tidak diizinkan.');

        return response()->json([
            'items' => DB::table('internal_dokumentasi')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function pengumuman(Request $request)
    {
        abort_unless($request->user()?->canFeature('internalapp.content.pengumuman'), 403, 'Akses tidak diizinkan.');

        return response()->json([
            'items' => DB::table('internal_announcements')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderByDesc('created_at')->get(),
        ]);
    }
}
