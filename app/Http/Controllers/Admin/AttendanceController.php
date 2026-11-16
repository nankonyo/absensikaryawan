<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $month = (string) $request->query('month', now()->format('Y-m'));
        $status = $request->query('status');
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $query = Attendance::with('user.department');

        // Search by employee name or employee ID
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filter by month and year
        if ($month !== '' && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            $query->whereYear('date', $matches[1])->whereMonth('date', $matches[2]);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

        return view('admin.attendances.index', [
            'attendances' => $attendances,
            'search' => $search,
            'month' => $month,
            'status' => $status,
            'perPage' => $perPage,
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('user');
        return view('admin.attendances.show', compact('attendance'));
    }
}
