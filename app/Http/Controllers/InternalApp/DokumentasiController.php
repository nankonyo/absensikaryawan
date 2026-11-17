<?php

namespace App\Http\Controllers\InternalApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DokumentasiController extends Controller
{
    public function index()
    {
        abort_unless($this->isAdmin(), 403);

        $items = DB::table('internal_dokumentasi')->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('id')->get();

        return view('internal-app.dokumentasi.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $data = $this->validateData($request);
        $max = (int) DB::table('internal_dokumentasi')->max('sort_order');
        $fileUrl = $this->storeFile($request) ?: ($data['file_url'] ?? null);

        DB::table('internal_dokumentasi')->insert([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_url' => $fileUrl,
            'icon_color' => $data['icon_color'] ?? 'emerald',
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $max + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Dokumentasi ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_dokumentasi')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);

        $data = $this->validateData($request);
        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'icon_color' => $data['icon_color'] ?? 'emerald',
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ];

        $newFile = $this->storeFile($request);
        if ($newFile) {
            $this->deleteFile($row->file_url);
            $payload['file_url'] = $newFile;
        } elseif (array_key_exists('file_url', $data)) {
            $payload['file_url'] = $data['file_url'];
        }

        DB::table('internal_dokumentasi')->where('id', $id)->update($payload);

        return response()->json(['status' => true, 'message' => 'Dokumentasi diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_dokumentasi')->where('id', $id)->first();
        if ($row) {
            $this->deleteFile($row->file_url);
        }

        DB::table('internal_dokumentasi')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Dokumentasi dihapus.']);
    }

    public function toggle($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_dokumentasi')->where('id', $id)->first();
        abort_unless($row, 404);

        DB::table('internal_dokumentasi')->where('id', $id)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Status diubah.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file_url' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
            'icon_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function storeFile(Request $request): ?string
    {
        if (! $request->hasFile('file')) {
            return null;
        }

        $dir = public_path('storages/internal-app/dokumentasi');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $request->file('file');
        $name = date('YmdHis').'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
        $file->move($dir, $name);

        return '/storages/internal-app/dokumentasi/'.$name;
    }

    private function deleteFile(?string $path): void
    {
        if (! $path || strpos($path, '/storages/internal-app/dokumentasi/') !== 0) {
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
