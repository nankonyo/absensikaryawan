<?php

use App\Http\Controllers\InternalApp\AccessController;
use App\Http\Controllers\InternalApp\ActivityController;
use App\Http\Controllers\InternalApp\ActivityReportController;
use App\Http\Controllers\InternalApp\AjaxController;
use App\Http\Controllers\InternalApp\AnnouncementController;
use App\Http\Controllers\InternalApp\CredentialController;
use App\Http\Controllers\InternalApp\DashboardController;
use App\Http\Controllers\InternalApp\DokumentasiController;
use App\Http\Controllers\InternalApp\EmailLogController;
use App\Http\Controllers\InternalApp\GuideController;
use App\Http\Controllers\InternalApp\ManageDataController;
use App\Http\Controllers\InternalApp\OrgController;
use App\Http\Controllers\InternalApp\ProjectController;
use App\Http\Controllers\InternalApp\RoleController;
use App\Http\Controllers\InternalApp\VideoTutorialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('internal')->name('internal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('ajax/options', [AjaxController::class, 'options'])->name('ajax.options');

    Route::prefix('project-management')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('board', [ProjectController::class, 'board'])->name('board');
        Route::get('gantt', [ProjectController::class, 'gantt'])->name('gantt');
        Route::get('guide', [GuideController::class, 'project'])->name('guide');
        Route::post('projects/store', [ProjectController::class, 'store'])->name('store');
        Route::get('projects/{id}/credential', [ProjectController::class, 'credential'])->name('credential');
        Route::get('projects/{id}', [ProjectController::class, 'show'])->name('show');
        Route::post('projects/{id}/update', [ProjectController::class, 'update'])->name('update');
        Route::post('projects/{id}/note', [ProjectController::class, 'note'])->name('note');
        Route::post('projects/{id}/delete', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('tasks/{id}', [ProjectController::class, 'task'])->name('tasks.show');
        Route::post('tasks/store', [ProjectController::class, 'taskStore'])->name('tasks.store');
        Route::post('tasks/{id}/update', [ProjectController::class, 'taskUpdate'])->name('tasks.update');
        Route::post('tasks/{id}/move', [ProjectController::class, 'taskMove'])->name('tasks.move');
        Route::post('tasks/{id}/subtask', [ProjectController::class, 'subtaskStore'])->name('tasks.subtask');
        Route::post('subtasks/{id}/toggle', [ProjectController::class, 'subtaskToggle'])->name('subtasks.toggle');
        Route::post('subtasks/{id}/delete', [ProjectController::class, 'subtaskDelete'])->name('subtasks.delete');
        Route::post('tasks/{id}/comment', [ProjectController::class, 'commentStore'])->name('tasks.comment');
        Route::post('tasks/{id}/worklog/start', [ProjectController::class, 'worklogStart'])->name('tasks.worklog.start');
        Route::post('tasks/{id}/worklog/stop', [ProjectController::class, 'worklogStop'])->name('tasks.worklog.stop');
        Route::post('tasks/{id}/worklog', [ProjectController::class, 'worklogStore'])->name('tasks.worklog.store');
        Route::post('tasks/{id}/attachment', [ProjectController::class, 'attachmentStore'])->name('tasks.attachment');
        Route::post('presence', [ProjectController::class, 'presence'])->name('presence');
    });

    Route::prefix('activity')->name('activities.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::post('store', [ActivityController::class, 'store'])->name('store');
        Route::get('export', [ActivityController::class, 'export'])->name('export');
    });

    Route::prefix('activity-report')->name('activity-report.')->group(function () {
        Route::get('/', [ActivityReportController::class, 'index'])->name('index');
        Route::get('export', [ActivityReportController::class, 'export'])->name('export');
    });

    Route::middleware('role:direksi,admin')->group(function () {
        Route::prefix('video-tutorials')->name('video-tutorials.')->group(function () {
            Route::get('/', [VideoTutorialController::class, 'index'])->name('index');
            Route::post('/', [VideoTutorialController::class, 'store'])->name('store');
            Route::post('{id}/update', [VideoTutorialController::class, 'update'])->name('update');
            Route::post('{id}/delete', [VideoTutorialController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle', [VideoTutorialController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('dokumentasi')->name('dokumentasi.')->group(function () {
            Route::get('/', [DokumentasiController::class, 'index'])->name('index');
            Route::post('/', [DokumentasiController::class, 'store'])->name('store');
            Route::post('{id}/update', [DokumentasiController::class, 'update'])->name('update');
            Route::post('{id}/delete', [DokumentasiController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle', [DokumentasiController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('pengumuman')->name('pengumuman.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::post('{id}/update', [AnnouncementController::class, 'update'])->name('update');
            Route::post('{id}/delete', [AnnouncementController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle', [AnnouncementController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('organization')->name('organization.')->group(function () {
            Route::get('/', [OrgController::class, 'index'])->name('index');
            Route::post('store', [OrgController::class, 'store'])->name('store');
            Route::post('reorder', [OrgController::class, 'reorder'])->name('reorder');
            Route::post('sync-defaults', [OrgController::class, 'syncDefaults'])->name('sync-defaults');
            Route::post('{id}/update', [OrgController::class, 'update'])->name('update');
            Route::post('{id}/delete', [OrgController::class, 'destroy'])->name('delete');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('store', [RoleController::class, 'store'])->name('store');
            Route::get('{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::post('{id}/update', [RoleController::class, 'update'])->name('update');
            Route::post('{id}/delete', [RoleController::class, 'destroy'])->name('delete');
            Route::get('{id}/permissions', [RoleController::class, 'permissionsData'])->name('permissions');
            Route::post('{id}/permissions', [RoleController::class, 'permissionsUpdate'])->name('permissions.update');
        });

        Route::get('access', [AccessController::class, 'index'])->name('access.index');

        Route::prefix('servers')->name('servers.')->group(function () {
            Route::get('/', [CredentialController::class, 'index'])->name('index');
            Route::post('store', [CredentialController::class, 'store'])->name('store');
            Route::post('import', [CredentialController::class, 'import'])->name('import');
            Route::get('{id}/password', [CredentialController::class, 'password'])->name('password');
            Route::post('{id}/update', [CredentialController::class, 'update'])->name('update');
            Route::post('{id}/delete', [CredentialController::class, 'destroy'])->name('delete');
        });

        Route::prefix('manage-data')->name('manage-data.')->group(function () {
            Route::get('/', [ManageDataController::class, 'index'])->name('index');
            Route::post('seed-dummy', [ManageDataController::class, 'seedDummy'])->name('seed-dummy');
            Route::post('reset-dummy', [ManageDataController::class, 'resetDummy'])->name('reset-dummy');
            Route::post('import-activity', [ManageDataController::class, 'importActivity'])->name('import-activity');
            Route::post('import-credentials', [ManageDataController::class, 'importCredentials'])->name('import-credentials');
            Route::post('send-weekly-email', [ManageDataController::class, 'sendWeeklyEmail'])->name('send-weekly-email');
            Route::post('weekly-email-settings', [ManageDataController::class, 'saveWeeklyEmailSettings'])->name('weekly-email-settings');
        });

        Route::prefix('email-logs')->name('email-logs.')->group(function () {
            Route::get('/', [EmailLogController::class, 'index'])->name('index');
            Route::get('{id}', [EmailLogController::class, 'show'])->name('show');
        });
    });
});
