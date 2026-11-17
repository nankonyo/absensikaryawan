<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Installation extends Model
{
    /**
     * Create / update every table required by the InternalApp modules.
     *
     * The method is idempotent: it only creates tables and columns that are
     * still missing, so it is safe to run on an existing installation.
     */
    public static function executeSchemaDatabase(): void
    {
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

        InternalAppInstallation::ensureForRst();
    }
}
