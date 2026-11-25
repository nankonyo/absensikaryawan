<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Models\Attendance;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index()
    {
        $this->ensureCanAttend();

        $user = auth()->user();
        $today = Carbon::today();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $canCheckIn = !$todayAttendance || !$todayAttendance->check_in;
        $canCheckOut = !$todayAttendance || !$todayAttendance->check_out;

        $isDayOff = Holiday::isDayOff($today);
        $dayOffName = $isDayOff ? Holiday::dayOffName($today) : '';

        return view('karyawan.attendance.index', compact(
            'todayAttendance',
            'canCheckIn',
            'canCheckOut',
            'isDayOff',
            'dayOffName'
        ));
    }

    public function checkInForm()
    {
        $this->ensureCanAttend();

        return view('karyawan.attendance.check-in');
    }

    public function checkIn(Request $request)
    {
        $this->ensureCanAttend();

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo_data' => 'required|string', // Base64 encoded image from camera
            'face_score' => 'nullable|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'attendance_reason_id' => 'nullable|exists:attendance_reasons,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->attendanceService->checkIn(auth()->user(), $request->all());

        if ($result['success']) {
            return redirect()
                ->route('karyawan.attendance.index')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    public function checkOutForm()
    {
        $this->ensureCanAttend();

        $user = auth()->user();
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($todayAttendance && $todayAttendance->check_out) {
            return redirect()
                ->route('karyawan.attendance.index')
                ->with('error', __('ui.already_checked_out'));
        }

        return view('karyawan.attendance.check-out', compact('todayAttendance'));
    }

    public function checkOut(Request $request)
    {
        $this->ensureCanAttend();

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo_data' => 'required|string', // Base64 encoded image from camera
            'face_score' => 'nullable|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'attendance_reason_id' => 'nullable|exists:attendance_reasons,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->attendanceService->checkOut(auth()->user(), $request->all());

        if ($result['success']) {
            return redirect()
                ->route('karyawan.attendance.index')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    public function history(Request $request)
    {
        $this->ensureCanAttend();

        $user = auth()->user();
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $status = $request->query('status');
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $attendances = $this->attendanceService->getUserAttendanceHistory($user, $month, $perPage, $status);

        return view('karyawan.attendance.history', compact('attendances', 'month', 'status', 'perPage'));
    }

    public function show(Attendance $attendance)
    {
        $this->ensureCanAttend();

        abort_unless($attendance->user_id === auth()->id(), 403);

        $attendance->load('user');

        return view('karyawan.attendance.show', compact('attendance'));
    }

    /**
     * Attendance is only available while the user still has a superior.
     */
    private function ensureCanAttend(): void
    {
        abort_unless(auth()->user()->hasSupervisor(), 403, __('ui.attendance_no_supervisor'));
    }
}
