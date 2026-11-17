<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class GuideController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function absensi(Request $request)
    {
        return $this->render('absensi', $request);
    }

    public function project(Request $request)
    {
        return $this->render('project_management', $request);
    }

    protected function render(string $module, Request $request)
    {
        $isAdmin = $this->isAdmin();
        $q = trim((string) $request->input('q', ''));

        $guides = DB::table('internal_guides')->where('module_key', $module)->where('is_active', true)
            ->where(function ($w) use ($isAdmin) {
                $w->where('role_scope', 'all');
                if ($isAdmin) {
                    $w->orWhere('role_scope', 'admin');
                }
            })
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.mb_strtolower($q).'%';
                $w->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(title) LIKE ?', [$like])->orWhereRaw('LOWER(content) LIKE ?', [$like]);
                });
            })
            ->orderBy('sort')->get();

        $faqs = DB::table('internal_faqs')->where('module_key', $module)->where('is_active', true)
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.mb_strtolower($q).'%';
                $w->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(question) LIKE ?', [$like])->orWhereRaw('LOWER(answer) LIKE ?', [$like]);
                });
            })
            ->orderBy('sort')->get();

        $steps = DB::table('internal_guide_steps')->where('module_key', $module)->where('is_active', true)
            ->orderBy('sort')->get();

        return view('internal-app.guide.index', compact('module', 'guides', 'faqs', 'steps', 'q', 'isAdmin'));
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
