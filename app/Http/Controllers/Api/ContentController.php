<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function videos()
    {
        return response()->json([
            'items' => DB::table('internal_video_tutorials')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function dokumentasi()
    {
        return response()->json([
            'items' => DB::table('internal_dokumentasi')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function pengumuman()
    {
        return response()->json([
            'items' => DB::table('internal_announcements')
                ->whereNull('deleted_at')->where('is_active', true)
                ->orderByDesc('created_at')->get(),
        ]);
    }
}
