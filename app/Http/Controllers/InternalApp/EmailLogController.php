<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class EmailLogController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $q = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));

        $query = DB::table('internal_email_logs')->orderByDesc('id');
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw("LOWER(COALESCE(recipient_email,'')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(recipient_name,'')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(subject,'')) LIKE ?", [$like]);
            });
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $items = $query->paginate(30)->withQueryString();
        $counts = [
            'sent' => DB::table('internal_email_logs')->where('status', 'sent')->count(),
            'failed' => DB::table('internal_email_logs')->where('status', 'failed')->count(),
            'total' => DB::table('internal_email_logs')->count(),
        ];

        return view('internal-app.email-logs.index', compact('items', 'counts', 'q', 'status'));
    }

    public function show($id)
    {
        abort_unless($this->isAdmin(), 403);
        $row = DB::table('internal_email_logs')->find($id);
        abort_unless($row, 404);

        return response()->json([
            'id' => (int) $row->id,
            'recipient_name' => $row->recipient_name,
            'recipient_email' => $row->recipient_email,
            'subject' => $row->subject,
            'status' => $row->status,
            'error' => $row->error,
            'sent_at' => $row->sent_at,
            'period_from' => $row->period_from,
            'period_to' => $row->period_to,
            'body' => $row->body,
        ]);
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
