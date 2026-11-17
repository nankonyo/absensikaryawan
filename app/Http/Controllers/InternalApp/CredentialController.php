<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Services\InternalAppImportService;
use App\Services\InternalAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CredentialController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $q = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('category', ''));

        $query = DB::table('internal_credentials')->orderByRaw('expired_date IS NULL, expired_date ASC')->orderBy('name');
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(vendor, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(ip_address, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(username, '')) LIKE ?", [$like]);
            });
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $items = $query->paginate(30)->withQueryString();
        $categories = DB::table('internal_credentials')->distinct()->orderBy('category')->pluck('category');

        $soon = now()->addDays(30)->toDateString();
        $expiring = DB::table('internal_credentials')->whereNotNull('expired_date')->where('expired_date', '<=', $soon)->count();
        $expired = DB::table('internal_credentials')->whereNotNull('expired_date')->where('expired_date', '<', now()->toDateString())->count();

        // Pemberitahuan ke bell admin untuk credential yang expired / akan expired.
        $userId = InternalAppService::userId();
        if ($userId > 0) {
            $soonItems = DB::table('internal_credentials')
                ->whereNotNull('expired_date')->where('expired_date', '<=', $soon)
                ->orderBy('expired_date')->limit(50)->get();
            foreach ($soonItems as $cred) {
                $name = $cred->name ?: $cred->vendor ?: ('Credential #'.$cred->id);
                $title = 'Credential Expired: '.$name;
                $exists = DB::table('internal_notifications')->where('user_id', $userId)->where('title', $title)->exists();
                if (! $exists) {
                    $isExpired = $cred->expired_date < now()->toDateString();
                    \App\Services\InternalAppNotifier::notify(
                        $userId,
                        $title,
                        ($isExpired ? 'Sudah expired' : 'Akan expired').' pada '.$cred->expired_date.' — '.$name,
                        $isExpired ? 'error' : 'warning',
                        route('internal.servers.index')
                    );
                }
            }
        }

        return view('internal-app.credentials.index', compact('items', 'categories', 'q', 'category', 'expiring', 'expired'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $v = $this->validateData($request, false);
        DB::table('internal_credentials')->insert($this->payload($v, false));

        return response()->json(['status' => true, 'message' => 'Credential ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        abort_unless(DB::table('internal_credentials')->where('id', $id)->exists(), 404);
        $v = $this->validateData($request, true);
        DB::table('internal_credentials')->where('id', $id)->update($this->payload($v, true));

        return response()->json(['status' => true, 'message' => 'Credential diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        DB::table('internal_credentials')->where('id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Credential dihapus.']);
    }

    public function password($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_credentials')->find($id);
        abort_unless($row, 404);
        $password = null;
        if (! empty($row->password)) {
            try {
                $password = Crypt::decryptString($row->password);
            } catch (\Throwable $e) {
                $password = $row->password;
            }
        }

        return response()->json(['status' => true, 'password' => $password]);
    }

    public function import()
    {
        abort_unless($this->isAdmin(), 403);
        $path = public_path('static-files/internal_app/Update Data Server & Domain 2026 .xlsx');
        if (! is_file($path)) {
            return response()->json(['status' => false, 'message' => 'File Update Data Server & Domain tidak ditemukan.'], 422);
        }
        try {
            $count = InternalAppImportService::importCredentials($path, true);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'Gagal import: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => true, 'message' => $count.' credential berhasil diimpor dari Excel.']);
    }

    protected function validateData(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'category' => 'required|string|max:40',
            'group_name' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:150',
            'name' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:500',
            'username' => 'nullable|string|max:255',
            'password' => ($isUpdate ? 'nullable' : 'nullable').'|string|max:2000',
            'email' => 'nullable|email|max:255',
            'expired_date' => 'nullable|date',
            'status' => 'nullable|string|max:40',
            'notes' => 'nullable|string',
        ]);
    }

    protected function payload(array $v, bool $isUpdate): array
    {
        $data = [
            'category' => $v['category'],
            'group_name' => $v['group_name'] ?? null,
            'vendor' => $v['vendor'] ?? null,
            'name' => $v['name'] ?? null,
            'ip_address' => $v['ip_address'] ?? null,
            'url' => $v['url'] ?? null,
            'username' => $v['username'] ?? null,
            'email' => $v['email'] ?? null,
            'expired_date' => $v['expired_date'] ?? null,
            'status' => $v['status'] ?? null,
            'notes' => $v['notes'] ?? null,
            'updated_at' => now(),
        ];
        if (! empty($v['password'])) {
            $data['password'] = Crypt::encryptString($v['password']);
        }
        if (! $isUpdate) {
            $data['created_at'] = now();
        }

        return $data;
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
