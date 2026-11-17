<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index()
    {
        return $this->renderDashboard();
    }

    public function memberIndex()
    {
        return $this->renderDashboard();
    }

    protected function renderDashboard()
    {
        $videos = DB::table('internal_video_tutorials')->whereNull('deleted_at')->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
        $docs = DB::table('internal_dokumentasi')->whereNull('deleted_at')->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
        $announcements = DB::table('internal_announcements')->whereNull('deleted_at')->where('is_active', true)
            ->orderByDesc('created_at')->limit(8)->get();

        $isAdmin = $this->isAdmin();
        $projectStats = [
            'total' => DB::table('internal_projects')->count(),
            'tasks' => DB::table('internal_tasks')->count(),
            'activities' => DB::table('internal_activity_logs')->where('activity_date', '>=', date('Y-m-01'))->count(),
        ];

        return view('internal-app.dashboard.index', compact('videos', 'docs', 'announcements', 'isAdmin', 'projectStats'));
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
