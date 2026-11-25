<?php

namespace App\Http\Controllers\InternalApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManageDataController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $stats = [
            'projects' => Schema::hasTable('internal_projects') ? DB::table('internal_projects')->count() : 0,
            'tasks' => Schema::hasTable('internal_tasks') ? DB::table('internal_tasks')->count() : 0,
            'activity_logs' => Schema::hasTable('internal_activity_logs') ? DB::table('internal_activity_logs')->count() : 0,
            'credentials' => Schema::hasTable('internal_credentials') ? DB::table('internal_credentials')->count() : 0,
        ];

        $activityFile = public_path('static-files/internal_app/ACTIVITY DIVISI PROJECT RAJA SAKTI TELEMATIKA 2026.xlsx');
        $credFile = public_path('static-files/internal_app/Update Data Server & Domain 2026 .xlsx');
        $lastWeeklyEmail = null;
        $emailSettings = [];

        return view('internal-app.manage-data.index', compact('stats', 'activityFile', 'credFile', 'lastWeeklyEmail', 'emailSettings'));
    }

    public function seedDummy()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json(['status' => false, 'message' => 'Seed data dummy belum tersedia di aplikasi ini.'], 422);
    }

    public function resetDummy()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json(['status' => true, 'message' => 'Tidak ada data dummy untuk direset.']);
    }

    public function importActivity()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json(['status' => false, 'message' => 'File Activity Project tidak ditemukan.'], 422);
    }

    public function importCredentials()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json(['status' => false, 'message' => 'File Server & Domain tidak ditemukan.'], 422);
    }

    public function sendWeeklyEmail()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $sent = app(\App\Services\ActivityReportMailService::class)->send('weekly');

        return response()->json([
            'status' => true,
            'message' => $sent > 0
                ? 'Laporan aktivitas terkirim ke '.$sent.' atasan.'
                : 'Tidak ada aktivitas pada periode ini, email tidak dikirim.',
        ]);
    }

    public function saveWeeklyEmailSettings(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->json(['status' => true, 'message' => 'Pengaturan email disimpan.']);
    }
}
