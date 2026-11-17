<?php

namespace App\Http\Controllers\InternalApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function index()
    {
        abort_unless($this->isAdmin(), 403);

        $items = DB::table('internal_announcements')->whereNull('deleted_at')
            ->orderByDesc('created_at')->get();

        return view('internal-app.pengumuman.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $data = $this->validateData($request);

        DB::table('internal_announcements')->insert([
            'code' => $data['code'] ?? null,
            'slug' => $data['slug'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'pdf_path' => $this->storePdf($request),
            'is_active' => $request->boolean('is_active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Pengumuman ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_announcements')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);

        $data = $this->validateData($request);
        $payload = [
            'code' => $data['code'] ?? null,
            'slug' => $data['slug'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ];

        if ($request->hasFile('pdf')) {
            $this->deletePdf($row->pdf_path);
            $payload['pdf_path'] = $this->storePdf($request);
        }

        DB::table('internal_announcements')->where('id', $id)->update($payload);

        return response()->json(['status' => true, 'message' => 'Pengumuman diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_announcements')->where('id', $id)->first();
        abort_unless($row, 404);

        $this->deletePdf($row->pdf_path);
        DB::table('internal_announcements')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Pengumuman dihapus.']);
    }

    public function toggle($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_announcements')->where('id', $id)->first();
        abort_unless($row, 404);

        DB::table('internal_announcements')->where('id', $id)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Status diubah.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:200'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function storePdf(Request $request): ?string
    {
        if (! $request->hasFile('pdf')) {
            return null;
        }

        $dir = public_path('storages/internal-app/pengumuman');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $request->file('pdf');
        $name = date('YmdHis').'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
        $file->move($dir, $name);

        return '/storages/internal-app/pengumuman/'.$name;
    }

    private function deletePdf(?string $path): void
    {
        if (! $path) {
            return;
        }

        $full = public_path(ltrim($path, '/'));
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
