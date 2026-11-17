<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Support\Feature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    protected array $protected = ['super-admin', 'member'];

    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index()
    {
        abort_unless($this->isAdmin(), 403);
        $roles = DB::table('roles')->whereNull('deleted_at')->orderBy('name')->paginate(15);
        $counts = DB::table('users')
            ->select('role_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('role_id')
            ->groupBy('role_id')->pluck('total', 'role_id');
        $features = Feature::groups();

        return view('internal-app.roles.index', compact('roles', 'counts', 'features'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:roles,slug',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = ($v['slug'] ?? null) ?: Str::slug($v['name']);
        if (DB::table('roles')->where('slug', $slug)->exists()) {
            return response()->json(['status' => false, 'message' => 'Slug role sudah digunakan.'], 422);
        }

        DB::table('roles')->insert([
            'name' => $v['name'],
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
            'is_super_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Role berhasil ditambahkan.']);
    }

    public function edit($id)
    {
        abort_unless($this->isAdmin(), 403);
        $role = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($role, 404);

        return response()->json([
            'id' => (int) $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'is_active' => (bool) $role->is_active,
            'protected' => in_array($role->slug, $this->protected, true),
        ]);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        $role = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($role, 404);

        $v = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:roles,slug,'.$id,
            'is_active' => 'nullable|boolean',
        ]);

        $slug = $role->slug;
        if (! in_array($role->slug, $this->protected, true)) {
            $slug = ($v['slug'] ?? null) ?: Str::slug($v['name']);
            if (DB::table('roles')->where('slug', $slug)->where('id', '<>', $id)->exists()) {
                return response()->json(['status' => false, 'message' => 'Slug role sudah digunakan.'], 422);
            }
        }

        DB::table('roles')->where('id', $id)->update([
            'name' => $v['name'],
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Role berhasil diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        $role = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($role, 404);

        if (in_array($role->slug, $this->protected, true) || $role->is_super_admin) {
            return response()->json(['status' => false, 'message' => 'Role bawaan tidak dapat dihapus.'], 422);
        }
        if (DB::table('users')->where('role_id', $id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Role masih dipakai pengguna.'], 422);
        }

        DB::table('permissions')->where('first_big_integer_key', $id)->where('model', 'feature')->delete();
        DB::table('roles')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Role dihapus.']);
    }

    public function permissionsData($id)
    {
        abort_unless($this->isAdmin(), 403);
        $role = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($role, 404);

        $rows = DB::table('permissions')
            ->where('first_big_integer_key', $id)
            ->where('model', 'feature')
            ->where('code', 'like', 'internalapp.%')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->code][$row->action] = true;
        }

        return response()->json(['status' => true, 'permissions' => $map]);
    }

    public function permissionsUpdate(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        $role = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($role, 404);

        $request->validate(['permissions' => 'nullable|array']);

        $allowed = [];
        foreach (Feature::groups() as $group) {
            foreach ($group['items'] as $item) {
                $allowed[$item['id']] = $item['actions'];
            }
        }

        DB::beginTransaction();
        try {
            DB::table('permissions')
                ->where('first_big_integer_key', $id)
                ->where('model', 'feature')
                ->where('code', 'like', 'internalapp.%')
                ->delete();

            foreach ((array) $request->input('permissions', []) as $code => $actions) {
                if (! isset($allowed[$code])) {
                    continue;
                }
                foreach ((array) $actions as $action => $value) {
                    if (! $value || ! in_array($action, $allowed[$code], true)) {
                        continue;
                    }
                    DB::table('permissions')->insert([
                        'first_string_key' => $role->slug ?? '',
                        'first_big_integer_key' => $id,
                        'code' => $code,
                        'action' => $action,
                        'model' => 'feature',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => false, 'message' => 'Gagal menyimpan hak akses: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => true, 'message' => 'Hak akses role disimpan.']);
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
