<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

Route::get('updating-schema', [App\Http\Controllers\SynchController::class, 'index'])
    ->name('updating-schema.index');

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isAtasan()) {
            return redirect()->route('atasan.dashboard');
        } else {
            return redirect()->route('karyawan.dashboard');
        }
    })->name('dashboard');
});

// User management: admin/direksi and Sekretariat level 1-3.
Route::middleware(['auth', 'manage-users'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
});

// Admin routes
Route::middleware(['auth', 'role:direksi,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Master data
    Route::resource('departments', App\Http\Controllers\Admin\DepartmentController::class)
        ->except(['create', 'show', 'edit']);
    Route::resource('units', App\Http\Controllers\Admin\UnitController::class)
        ->except(['create', 'show', 'edit']);
    Route::resource('positions', App\Http\Controllers\Admin\PositionController::class)
        ->except(['create', 'show', 'edit']);
    Route::resource('user-attributes', App\Http\Controllers\Admin\UserAttributeController::class)
        ->except(['show']);
    Route::resource('branches', App\Http\Controllers\Admin\BranchController::class)
        ->except(['show']);
    Route::resource('leave-types', App\Http\Controllers\Admin\LeaveTypeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('holidays', App\Http\Controllers\Admin\HolidayController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('attendance-reasons', App\Http\Controllers\Admin\AttendanceReasonController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Office locations
    Route::resource('office-locations', App\Http\Controllers\Admin\OfficeLocationController::class);

    // Attendance management
    Route::get('/attendances', [App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/{attendance}', [App\Http\Controllers\Admin\AttendanceController::class, 'show'])->name('attendances.show');

    // Team attendance: admin and direksi see every employee.
    Route::get('/team-attendance', [App\Http\Controllers\Atasan\TeamAttendanceController::class, 'index'])->name('team-attendance.index');
    Route::get('/team-attendance/{user}', [App\Http\Controllers\Atasan\TeamAttendanceController::class, 'show'])->name('team-attendance.show');

    // Outside-zone attendance verification.
    Route::get('/attendance-verifications', [App\Http\Controllers\AttendanceVerificationController::class, 'index'])->name('attendance-verifications.index');
    Route::post('/attendance-verifications/{attendance}/approve', [App\Http\Controllers\AttendanceVerificationController::class, 'approve'])->name('attendance-verifications.approve');
    Route::post('/attendance-verifications/{attendance}/reject', [App\Http\Controllers\AttendanceVerificationController::class, 'reject'])->name('attendance-verifications.reject');

    // Leave management
    Route::get('/leaves', [App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/{leave}', [App\Http\Controllers\Admin\LeaveController::class, 'show'])->name('leaves.show');
    Route::post('/leaves/{leave}/approve', [App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('leaves.reject');

    // Reports
    Route::get('/reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/attendance/preview', [App\Http\Controllers\Admin\ReportController::class, 'preview'])->name('reports.attendance.preview');
    Route::get('/reports/attendance/download', [App\Http\Controllers\Admin\ReportController::class, 'download'])->name('reports.attendance.download');
    Route::post('/reports/attendance', [App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('reports.attendance');

    // Settings
    Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::get('/api-docs', [App\Http\Controllers\Admin\ApiDocumentationController::class, 'index'])->name('api-docs.index');
    Route::post('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [App\Http\Controllers\Admin\SettingController::class, 'testEmail'])->name('settings.test-email');
    Route::get('/settings/users', [App\Http\Controllers\Admin\SettingController::class, 'users'])->name('settings.users');

    // Audit logs
    Route::get('/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');
});

// Atasan routes
Route::middleware(['auth', 'role:general_manager,sekretaris,manager,supervisor'])->prefix('atasan')->name('atasan.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Atasan\DashboardController::class, 'index'])->name('dashboard');

    // Team attendance
    Route::get('/team-attendance', [App\Http\Controllers\Atasan\TeamAttendanceController::class, 'index'])->name('team-attendance.index');
    Route::get('/team-attendance/{user}', [App\Http\Controllers\Atasan\TeamAttendanceController::class, 'show'])->name('team-attendance.show');

    // Outside-zone attendance verification.
    Route::get('/attendance-verifications', [App\Http\Controllers\AttendanceVerificationController::class, 'index'])->name('attendance-verifications.index');
    Route::post('/attendance-verifications/{attendance}/approve', [App\Http\Controllers\AttendanceVerificationController::class, 'approve'])->name('attendance-verifications.approve');
    Route::post('/attendance-verifications/{attendance}/reject', [App\Http\Controllers\AttendanceVerificationController::class, 'reject'])->name('attendance-verifications.reject');

    // Leave approvals
    Route::get('/approvals', [App\Http\Controllers\Atasan\ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{leave}', [App\Http\Controllers\Atasan\ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('/approvals/{leave}/approve', [App\Http\Controllers\Atasan\ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{leave}/reject', [App\Http\Controllers\Atasan\ApprovalController::class, 'reject'])->name('approvals.reject');
});

// Leave verification: Sekretariat department, job level 2 or 3
Route::middleware(['auth', 'leave-verifier'])->prefix('verifikasi')->name('verifikasi.')->group(function () {
    Route::get('/leaves', [App\Http\Controllers\Verifikasi\LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/{leave}', [App\Http\Controllers\Verifikasi\LeaveController::class, 'show'])->name('leaves.show');
    Route::post('/leaves/{leave}/approve', [App\Http\Controllers\Verifikasi\LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [App\Http\Controllers\Verifikasi\LeaveController::class, 'reject'])->name('leaves.reject');
});

// Attendance & leave: every role that still has a superior. Direksi has none.
Route::middleware(['auth', 'role:general_manager,sekretaris,manager,supervisor,staff'])->prefix('karyawan')->name('karyawan.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Karyawan\DashboardController::class, 'index'])->name('dashboard');

    // Attendance
    Route::get('/attendance', [App\Http\Controllers\Karyawan\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/check-in', [App\Http\Controllers\Karyawan\AttendanceController::class, 'checkInForm'])->name('attendance.check-in');
    Route::post('/attendance/check-in', [App\Http\Controllers\Karyawan\AttendanceController::class, 'checkIn'])->name('attendance.check-in.submit');
    Route::get('/attendance/check-out', [App\Http\Controllers\Karyawan\AttendanceController::class, 'checkOutForm'])->name('attendance.check-out');
    Route::post('/attendance/check-out', [App\Http\Controllers\Karyawan\AttendanceController::class, 'checkOut'])->name('attendance.check-out.submit');
    Route::get('/attendance/history', [App\Http\Controllers\Karyawan\AttendanceController::class, 'history'])->name('attendance.history');
    Route::get('/attendance/history/{attendance}', [App\Http\Controllers\Karyawan\AttendanceController::class, 'show'])->name('attendance.show');

    // Leave
    Route::resource('leaves', App\Http\Controllers\Karyawan\LeaveController::class)
        ->parameters(['leaves' => 'leave']);
});

// Face capture screen embedded in the mobile app (signed, short-lived URL).
Route::get('/mobile/face-capture/{user}', [App\Http\Controllers\MobileFaceCaptureController::class, 'show'])
    ->middleware('signed')
    ->name('mobile.face-capture');

require __DIR__.'/internal-app.php';
require __DIR__.'/auth.php';
