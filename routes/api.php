<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminResourceController;
use App\Http\Controllers\Api\AppInfoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('__dev/version', function () {
        $version = \Illuminate\Support\Facades\Cache::store('file')->remember('dev.asset.version', 3, function () {
            $paths = [
                resource_path('views'),
                resource_path('css'),
                resource_path('js'),
                public_path('css'),
                public_path('cdn'),
                base_path('routes'),
            ];

            $versions = [];

            foreach ($paths as $path) {
                if (! is_dir($path)) {
                    continue;
                }

                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $versions[] = $file->getMTime();
                    }
                }
            }

            return md5(implode(',', $versions));
        });

        return response()->json(['version' => $version]);
    })->name('dev.version');
}

Route::post('mobile/login', [AuthController::class, 'login']);

Route::middleware('api-token')->prefix('mobile')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::get('app-info', [AppInfoController::class, 'index']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::prefix('attendance')->group(function () {
        Route::get('today', [AttendanceController::class, 'today']);
        Route::get('history', [AttendanceController::class, 'history']);
        Route::get('reasons', [AttendanceController::class, 'reasons']);
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
    });

    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);
        Route::get('types', [LeaveController::class, 'types']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::get('{leave}', [LeaveController::class, 'show']);
        Route::delete('{leave}', [LeaveController::class, 'destroy']);
    });

    Route::prefix('approvals')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::post('{leave}/approve', [ApprovalController::class, 'approve']);
        Route::post('{leave}/reject', [ApprovalController::class, 'reject']);
        Route::post('{leave}/verify', [ApprovalController::class, 'verify']);
    });

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::get('{member}', [TeamController::class, 'show']);
    });

    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('kanban', [ProjectController::class, 'kanban']);
        Route::get('gantt', [ProjectController::class, 'gantt']);
        Route::get('{id}', [ProjectController::class, 'show']);
        Route::get('{id}/tasks', [ProjectController::class, 'tasks']);
        Route::post('tasks/{task}', [ProjectController::class, 'updateTask']);
    });

    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/', [ActivityController::class, 'store']);
    });

    Route::prefix('contents')->group(function () {
        Route::get('videos', [ContentController::class, 'videos']);
        Route::get('dokumentasi', [ContentController::class, 'dokumentasi']);
        Route::get('pengumuman', [ContentController::class, 'pengumuman']);
    });


    Route::prefix('admin/resources')->group(function () {
        Route::get('{resource}/schema', [AdminResourceController::class, 'schema']);
        Route::get('{resource}', [AdminResourceController::class, 'index']);
        Route::post('{resource}', [AdminResourceController::class, 'store']);
        Route::post('attendances/{id}/verify', [AdminResourceController::class, 'verifyAttendance']);
        Route::put('{resource}/{id}', [AdminResourceController::class, 'update']);
        Route::delete('{resource}/{id}', [AdminResourceController::class, 'destroy']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/', [ProfileController::class, 'update']);
    });
});
