<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AjaxController extends Controller
{
    public function options(Request $request)
    {
        $type = (string) $request->input('type', '');
        $q = trim((string) $request->input('q', ''));
        $like = '%'.mb_strtolower($q).'%';
        $results = [];

        if ($type === 'users') {
            $rows = DB::table('users as u')
                ->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
                ->whereNull('u.deleted_at')
                ->where('u.is_active', true)
                ->when($q !== '', function ($w) use ($like) {
                    $w->where(function ($x) use ($like) {
                        $x->whereRaw('LOWER(u.user_name) LIKE ?', [$like])
                            ->orWhereRaw("LOWER(COALESCE(p.nick_name,'')) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(COALESCE(p.first_name,'')) LIKE ?", [$like]);
                    });
                })
                ->select('u.id', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as text"))
                ->orderBy('text')->limit(50)->get();
            $results = $rows->map(fn ($r) => ['id' => (int) $r->id, 'text' => $r->text])->all();
        } elseif ($type === 'projects') {
            $rows = DB::table('internal_projects')
                ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like]))
                ->orderBy('name')->limit(100)->get(['id', 'name as text']);
            $results = $rows->map(fn ($r) => ['id' => (int) $r->id, 'text' => $r->text])->all();
        } elseif ($type === 'roles') {
            $rows = DB::table('roles')
                ->whereNull('deleted_at')
                ->whereIn('slug', InternalAppInstallation::orgRoleSlugs())
                ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like]))
                ->orderBy('name')->get(['id', 'name as text']);
            $results = $rows->map(fn ($r) => ['id' => (int) $r->id, 'text' => $r->text])->all();
        } elseif ($type === 'offices') {
            $rows = DB::table('internal_offices')
                ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like]))
                ->orderBy('name')->get(['id', 'name as text']);
            $results = $rows->map(fn ($r) => ['id' => (int) $r->id, 'text' => $r->text])->all();
        } elseif ($type === 'shifts') {
            $rows = DB::table('internal_shifts')
                ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like]))
                ->orderBy('name')->get(['id', 'name as text']);
            $results = $rows->map(fn ($r) => ['id' => (int) $r->id, 'text' => $r->text])->all();
        }

        return response()->json(['results' => $results]);
    }
}
