<?php

namespace App\Http\Controllers\InternalApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoTutorialController extends Controller
{
    public function index()
    {
        abort_unless($this->isAdmin(), 403);

        $items = DB::table('internal_video_tutorials')->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('id')->get();

        return view('internal-app.video-tutorials.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $data = $this->validateData($request);
        $max = (int) DB::table('internal_video_tutorials')->max('sort_order');

        DB::table('internal_video_tutorials')->insert([
            'title' => $data['title'],
            'youtube_url' => $data['youtube_url'],
            'badge_text' => $data['badge_text'] ?? null,
            'badge_color' => $data['badge_color'] ?? 'bg-blue-600',
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $max + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Video tutorial ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        abort_unless(DB::table('internal_video_tutorials')->where('id', $id)->whereNull('deleted_at')->exists(), 404);

        $data = $this->validateData($request);

        DB::table('internal_video_tutorials')->where('id', $id)->update([
            'title' => $data['title'],
            'youtube_url' => $data['youtube_url'],
            'badge_text' => $data['badge_text'] ?? null,
            'badge_color' => $data['badge_color'] ?? 'bg-blue-600',
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Video tutorial diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        DB::table('internal_video_tutorials')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Video tutorial dihapus.']);
    }

    public function toggle($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_video_tutorials')->where('id', $id)->first();
        abort_unless($row, 404);

        DB::table('internal_video_tutorials')->where('id', $id)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Status diubah.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'youtube_url' => ['required', 'string', 'max:500'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
