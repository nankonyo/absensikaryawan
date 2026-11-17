<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrgController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index()
    {
        abort_unless($this->isAdmin(), 403);
        $nodes = DB::table('internal_org_structure')->orderBy('sort')->orderBy('id')->get();
        $byParent = $nodes->groupBy(fn ($n) => $n->parent_id === null ? 'root' : (string) $n->parent_id);
        $tree = $this->nest($byParent, 'root');

        $users = DB::table('users as u')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('internal_employee_settings as s', 's.user_id', '=', 'u.id')
            ->whereNull('u.deleted_at')
            ->select('u.id', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as nama"), 's.divisi')
            ->orderBy('nama')->get();

        $allNodes = $nodes;

        return view('internal-app.org.index', compact('tree', 'users', 'allNodes'));
    }

    public function syncDefaults()
    {
        abort_unless($this->isAdmin(), 403);
        $result = InternalAppInstallation::syncDefaultOrgStructure();

        return response()->json([
            'status' => true,
            'message' => 'Sync default struktur selesai: '.$result['created'].' node dibuat, '.$result['updated'].' node diperbarui (Direktur → General Manager → Project/IT, HRD, Akuntansi & Keuangan, Marketing, Umum & Operasional).',
            'created' => $result['created'],
            'updated' => $result['updated'],
        ]);
    }

    public function reorder(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $items = $request->input('items', []);
        if (! is_array($items)) {
            return response()->json(['status' => false, 'message' => 'Data tidak valid.'], 422);
        }

        $parents = [];
        $sorts = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $parentId = isset($item['parent_id']) && $item['parent_id'] !== '' ? (int) $item['parent_id'] : null;
            if ($parentId === $id) {
                $parentId = null;
            }
            $parents[$id] = $parentId;
            $sorts[$id] = (int) ($item['sort'] ?? 0);
        }

        // Cegah siklus (parent tidak boleh turunannya sendiri).
        foreach ($parents as $id => $parent) {
            $walk = $parent;
            $steps = 0;
            while ($walk !== null && $steps < 200) {
                if ($walk === $id) {
                    $parents[$id] = null;
                    break;
                }
                $walk = $parents[$walk] ?? null;
                $steps++;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($parents as $id => $parentId) {
                DB::table('internal_org_structure')->where('id', $id)->update([
                    'parent_id' => $parentId,
                    'sort' => $sorts[$id] ?? 0,
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => false, 'message' => 'Gagal menyimpan urutan: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => true, 'message' => 'Struktur organisasi diperbarui.']);
    }

    public function store(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:internal_org_structure,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'sort' => 'nullable|integer|min:0|max:9999',
        ]);
        DB::table('internal_org_structure')->insert([
            'name' => $v['name'],
            'parent_id' => $v['parent_id'] ?? null,
            'user_id' => $v['user_id'] ?? null,
            'sort' => (int) ($v['sort'] ?? 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        InternalAppInstallation::ensureOrgRole($v['name']);

        return response()->json(['status' => true, 'message' => 'Node struktur ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->isAdmin(), 403);
        $oldNode = DB::table('internal_org_structure')->where('id', $id)->first();
        abort_unless($oldNode, 404);
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:internal_org_structure,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'sort' => 'nullable|integer|min:0|max:9999',
        ]);
        if ((int) ($v['parent_id'] ?? 0) === (int) $id) {
            return response()->json(['status' => false, 'message' => 'Parent tidak boleh dirinya sendiri.'], 422);
        }
        DB::table('internal_org_structure')->where('id', $id)->update([
            'name' => $v['name'],
            'parent_id' => $v['parent_id'] ?? null,
            'user_id' => $v['user_id'] ?? null,
            'sort' => (int) ($v['sort'] ?? 0),
            'updated_at' => now(),
        ]);
        InternalAppInstallation::ensureOrgRole($v['name'], $oldNode->name);

        return response()->json(['status' => true, 'message' => 'Node struktur diperbarui.']);
    }

    public function destroy($id)
    {
        abort_unless($this->isAdmin(), 403);
        $node = DB::table('internal_org_structure')->where('id', $id)->first();
        abort_unless($node, 404);
        DB::table('internal_org_structure')->where('id', $id)->delete();

        $slug = \Illuminate\Support\Str::slug($node->name);
        $role = $slug ? DB::table('roles')->where('slug', $slug)->first() : null;
        if ($role && $role->slug !== 'super-admin' && ! DB::table('users')->where('role_id', $role->id)->exists()) {
            DB::table('roles')->where('id', $role->id)->delete();
        }

        return response()->json(['status' => true, 'message' => 'Node struktur dihapus.']);
    }

    protected function nest($byParent, $parentKey): array
    {
        $out = [];
        foreach (($byParent[$parentKey] ?? collect()) as $node) {
            $out[] = [
                'node' => $node,
                'children' => $this->nest($byParent, (string) $node->id),
            ];
        }

        return $out;
    }

    protected function flatten($byParent, $parentKey, int $depth): array
    {
        $out = [];
        foreach (($byParent[$parentKey] ?? collect()) as $node) {
            $out[] = ['node' => $node, 'depth' => $depth];
            $out = array_merge($out, $this->flatten($byParent, (string) $node->id, $depth + 1));
        }

        return $out;
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
