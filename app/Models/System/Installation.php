<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Installation extends Model
{
    /**
     * Create the base application schema before module-specific tables.
     */
    protected static function baseSchema(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('level')->default(0);
                $table->string('display_name')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedTinyInteger('level')->default(5);
                $table->text('description')->nullable();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->string('name');
                $table->string('user_name', 100)->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('employee_id')->unique()->nullable();
                $table->string('position')->nullable();
                $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('avatar')->nullable();
                $table->string('birth_place')->nullable();
                $table->date('birth_date')->nullable();
                $table->string('gender', 20)->nullable();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('employment_status', 20)->default('permanent');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_admin')->default(false);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('settings') && ! DB::table('settings')->where('key', 'timezone')->exists()) {
            DB::table('settings')->insert([
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'description' => 'Application timezone',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('office_locations')) {
            Schema::create('office_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->integer('radius')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendance_reasons')) {
            Schema::create('attendance_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('attendance_reasons') && DB::table('attendance_reasons')->count() === 0) {
            $now = now();
            foreach (['WFO', 'WFH', 'WFA', 'Tugas dalam kota', 'Tugas pelatihan', 'Tugas keluar kota'] as $name) {
                DB::table('attendance_reasons')->insert([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->time('check_in')->nullable();
                $table->decimal('check_in_latitude', 10, 8)->nullable();
                $table->decimal('check_in_longitude', 11, 8)->nullable();
                $table->string('check_in_photo')->nullable();
                $table->decimal('check_in_face_score', 5, 2)->nullable();
                $table->string('check_in_address')->nullable();
                $table->time('check_out')->nullable();
                $table->decimal('check_out_latitude', 10, 8)->nullable();
                $table->decimal('check_out_longitude', 11, 8)->nullable();
                $table->string('check_out_photo')->nullable();
                $table->decimal('check_out_face_score', 5, 2)->nullable();
                $table->string('check_out_address')->nullable();
                $table->enum('status', ['valid', 'invalid', 'pending'])->default('pending');
                $table->text('notes')->nullable();
                $table->integer('work_duration')->nullable();
                $table->integer('late_duration')->nullable();
                $table->integer('early_leave_duration')->nullable();
                $table->foreignId('attendance_reason_id')->nullable()->constrained('attendance_reasons')->nullOnDelete();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('verification_notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'date']);
                $table->index('date');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category', 20)->default('cuti');
                $table->boolean('requires_attachment')->default(false);
                $table->boolean('requires_reason')->default(true);
                $table->boolean('deducts_quota')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leaves')) {
            Schema::create('leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('days');
                $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->string('attachment')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'start_date']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('user_attributes')) {
            Schema::create('user_attributes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label');
                $table->string('type')->default('text');
                $table->json('options')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_attribute_values')) {
            Schema::create('user_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_attribute_id')->constrained()->cascadeOnDelete();
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'user_attribute_id']);
            });
        }

        if (! Schema::hasTable('user_contracts')) {
            Schema::create('user_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('document')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('model')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
                $table->index('action');
                $table->index(['model', 'model_id']);
            });
        }
    }

    /**
     * Create / update every table required by the InternalApp modules.
     *
     * The method is idempotent: it only creates tables and columns that are
     * still missing, so it is safe to run on an existing installation.
     */
    public static function executeSchemaDatabase(): void
    {
        self::baseSchema();

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('code', 50)->nullable()->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 30)->default('planning');
                $table->string('priority', 20)->default('medium');
                $table->unsignedSmallInteger('progress')->default(0);
                $table->decimal('budget', 15, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        } else {
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('projects', 'code')) {
                    $table->string('code', 50)->nullable();
                }
                if (! Schema::hasColumn('projects', 'manager_id')) {
                    $table->unsignedBigInteger('manager_id')->nullable();
                    $table->index('manager_id');
                }
                if (! Schema::hasColumn('projects', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'status')) {
                    $table->string('status', 30)->default('planning');
                    $table->index('status');
                }
                if (! Schema::hasColumn('projects', 'priority')) {
                    $table->string('priority', 20)->default('medium');
                }
                if (! Schema::hasColumn('projects', 'progress')) {
                    $table->unsignedSmallInteger('progress')->default(0);
                }
                if (! Schema::hasColumn('projects', 'budget')) {
                    $table->decimal('budget', 15, 2)->nullable();
                }
                if (! Schema::hasColumn('projects', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (! Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('todo');
                $table->string('priority', 20)->default('medium');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedSmallInteger('progress')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('activity_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->string('status', 30)->default('planned');
                $table->timestamps();
                $table->softDeletes();
                $table->index('activity_date');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('contents')) {
            Schema::create('contents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('type', 30)->default('article');
                $table->string('category', 100)->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->string('status', 20)->default('draft');
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('type');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('administrations')) {
            Schema::create('administrations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('number', 100)->nullable()->unique();
                $table->string('title');
                $table->string('category', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('file_path')->nullable();
                $table->date('document_date')->nullable();
                $table->string('status', 30)->default('active');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index('category');
                $table->index('status');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (Schema::hasTable('contents')) {
            Schema::table('contents', function (Blueprint $table) {
                if (! Schema::hasColumn('contents', 'url')) {
                    $table->string('url', 500)->nullable();
                }
                if (! Schema::hasColumn('contents', 'file_path')) {
                    $table->string('file_path')->nullable();
                }
            });
        }

        if (! Schema::hasTable('servers')) {
            Schema::create('servers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_active')->default(true);
                $table->string('category', 40)->default('server');
                $table->string('group_name', 100)->nullable();
                $table->string('name')->nullable();
                $table->string('vendor', 150)->nullable();
                $table->string('ip_address', 255)->nullable();
                $table->string('url', 500)->nullable();
                $table->string('username', 255)->nullable();
                $table->text('password')->nullable();
                $table->string('email')->nullable();
                $table->date('expired_date')->nullable();
                $table->string('status', 40)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('category');
                $table->index('expired_date');
                $table->index('created_at');
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('recipient_name')->nullable();
                $table->string('recipient_email')->nullable();
                $table->string('subject')->nullable();
                $table->longText('body')->nullable();
                $table->string('status', 20)->default('sent');
                $table->text('error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->timestamps();
                $table->index('status');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('feature', 100);
                $table->boolean('can_view')->default(false);
                $table->boolean('can_manage')->default(false);
                $table->timestamps();
                $table->unique(['role_id', 'feature']);
            });
        }

        if (! Schema::hasTable('internal_video_tutorials')) {
            Schema::create('internal_video_tutorials', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('youtube_url', 500);
                $table->string('badge_text', 100)->nullable();
                $table->string('badge_color', 50)->nullable()->default('bg-blue-600');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('internal_dokumentasi')) {
            Schema::create('internal_dokumentasi', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('file_url', 500)->nullable();
                $table->string('icon_color', 50)->nullable()->default('emerald');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('internal_announcements')) {
            Schema::create('internal_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('code', 100)->nullable();
                $table->string('slug', 200)->nullable();
                $table->string('title', 255);
                $table->longText('content')->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('internal_video_tutorials') && DB::table('internal_video_tutorials')->count() === 0) {
            $now = now();
            $rows = [
                ['title' => 'Tentang InternalApp', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Pendahuluan', 'badge_color' => 'bg-indigo-600', 'sort_order' => 1],
                ['title' => 'Cara Presensi / Check In', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Absensi', 'badge_color' => 'bg-blue-600', 'sort_order' => 2],
                ['title' => 'Mengelola Project & Task', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Project', 'badge_color' => 'bg-emerald-600', 'sort_order' => 3],
            ];
            foreach ($rows as $row) {
                DB::table('internal_video_tutorials')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (Schema::hasTable('internal_dokumentasi') && DB::table('internal_dokumentasi')->count() === 0) {
            $now = now();
            $rows = [
                ['title' => 'Panduan Penggunaan InternalApp', 'description' => 'Panduan umum aplikasi', 'icon_color' => 'emerald', 'sort_order' => 1],
                ['title' => 'SOP Presensi & Cuti', 'description' => 'Alur presensi dan pengajuan cuti', 'icon_color' => 'rose', 'sort_order' => 2],
                ['title' => 'SOP Project Management', 'description' => 'Alur project, task, dan laporan aktivitas', 'icon_color' => 'blue', 'sort_order' => 3],
            ];
            foreach ($rows as $row) {
                DB::table('internal_dokumentasi')->insert($row + ['file_url' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (Schema::hasTable('internal_announcements') && DB::table('internal_announcements')->count() === 0) {
            DB::table('internal_announcements')->insert([
                'code' => 'INFO-001', 'slug' => 'selamat-datang', 'title' => 'Selamat Datang di InternalApp',
                'content' => '<p>Gunakan aplikasi ini untuk presensi, pengajuan cuti, project management, dan laporan aktivitas karyawan.</p>',
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 100)->default('mobile');
                $table->string('token', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->string('name');
                $table->string('type', 20)->default('national');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('date');
            });
        }

        if (! Schema::hasTable('password_reset_otps')) {
            Schema::create('password_reset_otps', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('otp_hash');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();
                $table->index(['email', 'consumed_at']);
            });
        }

        InternalAppInstallation::ensureForRst();

        if (! Schema::hasTable('internal_task_assignees')) {
            Schema::create('internal_task_assignees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('internal_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['task_id', 'user_id']);
            });
        }
    }
}
