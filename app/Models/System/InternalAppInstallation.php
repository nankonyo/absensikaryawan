<?php

namespace App\Models\System;

use App\Services\InternalAppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InternalAppInstallation extends Model
{
    public static bool $ensured = false;

    public static function ensure(): void
    {
        self::ensureForRst();
    }

    public static function ensureForRst(): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;

        self::compatibilitySchema();
        self::attendanceSchema();
        self::attendanceExtrasSchema();
        self::projectSchema();
        self::guideSchema();
        self::dataCenterSchema();
        self::activityReportSchema();
        self::contentSchema();
        self::seedGuides();
    }

    protected static function compatibilitySchema(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                if (! Schema::hasColumn('users', 'user_name')) {
                    $t->string('user_name', 100)->nullable();
                }
                if (! Schema::hasColumn('users', 'is_active')) {
                    $t->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('users', 'is_admin')) {
                    $t->boolean('is_admin')->default(false);
                }
                if (! Schema::hasColumn('users', 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            foreach (DB::table('users')->whereNull('user_name')->get(['id', 'name', 'email']) as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'user_name' => $user->name ?: $user->email,
                    'is_active' => true,
                ]);
            }
        }

        if (! Schema::hasTable('personal_informations')) {
            Schema::create('personal_informations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $t->string('nick_name', 150)->nullable();
                $t->string('first_name', 150)->nullable();
                $t->string('last_name', 150)->nullable();
                $t->timestamps();
            });
        }

        foreach (DB::table('users')->get(['id', 'name']) as $user) {
            DB::table('personal_informations')->updateOrInsert(
                ['user_id' => $user->id],
                ['nick_name' => $user->name, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $t->timestamps();
                $t->unique(['user_id', 'role_id']);
            });
        }

        if (Schema::hasTable('roles') && Schema::hasTable('users')) {
            Schema::table('roles', function (Blueprint $t) {
                if (! Schema::hasColumn('roles', 'slug')) {
                    $t->string('slug', 150)->nullable();
                }
                if (! Schema::hasColumn('roles', 'model')) {
                    $t->string('model', 50)->nullable();
                }
                if (! Schema::hasColumn('roles', 'is_active')) {
                    $t->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('roles', 'is_super_admin')) {
                    $t->boolean('is_super_admin')->default(false);
                }
                if (! Schema::hasColumn('roles', 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            foreach (DB::table('roles')->whereNull('slug')->get(['id', 'name']) as $role) {
                DB::table('roles')->where('id', $role->id)->update(['slug' => Str::slug($role->name)]);
            }

            foreach (DB::table('users')->whereNotNull('role_id')->get(['id', 'role_id']) as $user) {
                DB::table('user_roles')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $user->role_id],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $t) {
                $t->id();
                $t->boolean('is_active')->default(true)->nullable();
                $t->string('first_string_key', 50)->nullable();
                $t->bigInteger('first_big_integer_key')->nullable();
                $t->string('second_string_key', 50)->nullable();
                $t->bigInteger('second_big_integer_key')->nullable();
                $t->string('code')->nullable();
                $t->string('model')->nullable();
                $t->string('action')->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->index('first_big_integer_key');
                $t->index('second_big_integer_key');
            });
        }

        if (Schema::hasTable('internal_attendance_manual') && ! Schema::hasIndex('internal_attendance_manual', 'iam_user_period_unique')) {
            Schema::table('internal_attendance_manual', function (Blueprint $t) {
                $t->unique(['user_id', 'period_start', 'period_end'], 'iam_user_period_unique');
            });
        }

        if (! Schema::hasTable('internal_notifications')) {
            Schema::create('internal_notifications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('title');
                $t->text('message')->nullable();
                $t->string('type', 30)->default('info');
                $t->string('link', 500)->nullable();
                $t->boolean('is_read')->default(false);
                $t->timestamps();
            });
        }
    }

    protected static function attendanceSchema(): void
    {
        if (! Schema::hasTable('internal_offices')) {
            Schema::create('internal_offices', function (Blueprint $t) {
                $t->id();
                $t->string('name', 150);
                $t->decimal('latitude', 10, 7)->nullable();
                $t->decimal('longitude', 10, 7)->nullable();
                $t->integer('radius_m')->default(50);
                $t->text('address')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_shifts')) {
            Schema::create('internal_shifts', function (Blueprint $t) {
                $t->id();
                $t->string('name', 100);
                $t->time('start_time')->nullable();
                $t->time('end_time')->nullable();
                $t->integer('grace_minutes')->default(15);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_employee_settings')) {
            Schema::create('internal_employee_settings', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $t->string('work_mode', 10)->default('WFO');
                $t->foreignId('office_id')->nullable()->constrained('internal_offices')->onDelete('set null');
                $t->foreignId('shift_id')->nullable()->constrained('internal_shifts')->onDelete('set null');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_attendance_records')) {
            Schema::create('internal_attendance_records', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->date('attendance_date');
                $t->timestamp('check_in_at')->nullable();
                $t->timestamp('check_out_at')->nullable();
                $t->decimal('check_in_lat', 10, 7)->nullable();
                $t->decimal('check_in_lng', 10, 7)->nullable();
                $t->decimal('check_out_lat', 10, 7)->nullable();
                $t->decimal('check_out_lng', 10, 7)->nullable();
                $t->longText('check_in_photo')->nullable();
                $t->longText('check_out_photo')->nullable();
                $t->string('work_mode', 10)->default('WFO');
                $t->foreignId('office_id')->nullable()->constrained('internal_offices')->onDelete('set null');
                $t->integer('distance_m')->nullable();
                $t->boolean('is_mock_location')->default(false);
                $t->string('status', 20)->default('present');
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'attendance_date']);
            });
        }

        if (! Schema::hasTable('internal_leave_requests')) {
            Schema::create('internal_leave_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->string('type', 20);
                $t->date('start_date');
                $t->date('end_date');
                $t->text('reason')->nullable();
                $t->string('attachment_path', 500)->nullable();
                $t->string('status', 20)->default('pending');
                $t->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $t->timestamp('approved_at')->nullable();
                $t->text('approval_note')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_overtime_requests')) {
            Schema::create('internal_overtime_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->date('work_date');
                $t->time('start_time')->nullable();
                $t->time('end_time')->nullable();
                $t->text('reason')->nullable();
                $t->string('status', 20)->default('pending');
                $t->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $t->timestamp('approved_at')->nullable();
                $t->timestamps();
            });
        }

        if (DB::table('internal_offices')->count() === 0) {
            DB::table('internal_offices')->insert([
                'name' => 'Kantor Pusat',
                'latitude' => -0.9470834,
                'longitude' => 100.4171810,
                'radius_m' => 50,
                'address' => 'Kantor Pusat Pemerintahan Daerah',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('internal_shifts')->count() === 0) {
            DB::table('internal_shifts')->insert([
                'name' => 'Reguler',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'grace_minutes' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Default setting untuk semua user yang belum punya.
        $officeId = DB::table('internal_offices')->orderBy('id')->value('id');
        $shiftId = DB::table('internal_shifts')->orderBy('id')->value('id');
        foreach (DB::table('users')->whereNull('deleted_at')->pluck('id') as $userId) {
            if (! DB::table('internal_employee_settings')->where('user_id', $userId)->exists()) {
                DB::table('internal_employee_settings')->insert([
                    'user_id' => $userId,
                    'work_mode' => 'WFO',
                    'office_id' => $officeId,
                    'shift_id' => $shiftId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected static function attendanceExtrasSchema(): void
    {
        if (! Schema::hasColumn('internal_employee_settings', 'cuti_quota')) {
            Schema::table('internal_employee_settings', function (Blueprint $t) {
                $t->integer('cuti_quota')->default(12);
            });
        }

        if (! Schema::hasTable('internal_holidays')) {
            Schema::create('internal_holidays', function (Blueprint $t) {
                $t->id();
                $t->date('holiday_date');
                $t->string('name', 200);
                $t->string('type', 30)->default('nasional');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index('holiday_date');
            });
        }

        if (! Schema::hasTable('internal_app_settings')) {
            Schema::create('internal_app_settings', function (Blueprint $t) {
                $t->id();
                $t->string('key', 100)->unique();
                $t->text('value')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_attendance_manual')) {
            Schema::create('internal_attendance_manual', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->date('period_start');
                $t->date('period_end');
                $t->integer('tugas_dalam_kota')->default(0);
                $t->integer('tugas_pelatihan')->default(0);
                $t->integer('tugas_keluar_kota')->default(0);
                $t->integer('izin_keluar')->default(0);
                $t->decimal('jam_kompensasi', 8, 2)->default(0);
                $t->integer('libur_bersama')->default(0);
                $t->integer('libur_tanggal_merah')->default(0);
                $t->text('notes')->nullable();
                $t->string('status', 20)->default('pending');
                $t->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
                $t->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $t->timestamp('approved_at')->nullable();
                $t->text('approval_note')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'period_start', 'period_end'], 'iam_user_period_unique');
            });
        }

        if (Schema::hasTable('internal_attendance_manual') && ! Schema::hasIndex('internal_attendance_manual', 'iam_user_period_unique')) {
            Schema::table('internal_attendance_manual', function (Blueprint $t) {
                $t->unique(['user_id', 'period_start', 'period_end'], 'iam_user_period_unique');
            });
        }

        if (! Schema::hasTable('internal_notifications')) {
            Schema::create('internal_notifications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->string('title', 200);
                $t->text('message')->nullable();
                $t->string('type', 40)->default('info');
                $t->string('link', 500)->nullable();
                $t->boolean('is_read')->default(false);
                $t->timestamps();
                $t->index(['user_id', 'is_read']);
            });
        }
    }

    protected static function projectSchema(): void
    {
        if (! Schema::hasTable('internal_projects')) {
            Schema::create('internal_projects', function (Blueprint $t) {
                $t->id();
                $t->string('code', 50)->nullable();
                $t->string('name', 200);
                $t->text('description')->nullable();
                $t->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
                $t->date('start_date')->nullable();
                $t->date('end_date')->nullable();
                $t->string('status', 30)->default('planning');
                $t->integer('progress')->default(0);
                $t->string('color', 20)->default('#2563eb');
                $t->string('production_url', 500)->nullable();
                $t->string('development_url', 500)->nullable();
                $t->string('hosting_provider', 150)->nullable();
                $t->string('account_type', 30)->nullable();
                $t->string('account_email', 255)->nullable();
                $t->string('account_username', 255)->nullable();
                $t->text('account_password')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        self::addProjectMetaColumns();

        if (! Schema::hasTable('internal_project_members')) {
            Schema::create('internal_project_members', function (Blueprint $t) {
                $t->id();
                $t->foreignId('project_id')->constrained('internal_projects')->onDelete('cascade');
                $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $t->string('role', 40)->default('member');
                $t->timestamps();
                $t->unique(['project_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('internal_tasks')) {
            Schema::create('internal_tasks', function (Blueprint $t) {
                $t->id();
                $t->foreignId('project_id')->constrained('internal_projects')->onDelete('cascade');
                $t->foreignId('parent_id')->nullable()->constrained('internal_tasks')->onDelete('cascade');
                $t->string('title', 200);
                $t->text('description')->nullable();
                $t->string('status', 30)->default('backlog');
                $t->string('priority', 20)->default('medium');
                $t->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
                $t->foreignId('reporter_id')->nullable()->constrained('users')->onDelete('set null');
                $t->date('start_date')->nullable();
                $t->date('due_date')->nullable();
                $t->decimal('estimated_hours', 8, 2)->nullable();
                $t->integer('progress')->default(0);
                $t->integer('position')->default(0);
                $t->text('labels')->nullable();
                $t->boolean('is_milestone')->default(false);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_task_dependencies')) {
            Schema::create('internal_task_dependencies', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->foreignId('depends_on_task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->string('type', 30)->default('finish_to_start');
                $t->timestamps();
                $t->unique(['task_id', 'depends_on_task_id']);
            });
        }

        if (! Schema::hasTable('internal_subtasks')) {
            Schema::create('internal_subtasks', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->string('title', 200);
                $t->boolean('is_done')->default(false);
                $t->integer('position')->default(0);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_task_comments')) {
            Schema::create('internal_task_comments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->text('body');
                $t->text('mentions')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_task_attachments')) {
            Schema::create('internal_task_attachments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->string('file_path', 500);
                $t->string('original_name', 255);
                $t->string('mime', 100)->nullable();
                $t->unsignedBigInteger('size')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_task_worklogs')) {
            Schema::create('internal_task_worklogs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->timestamp('started_at')->nullable();
                $t->timestamp('ended_at')->nullable();
                $t->integer('duration_minutes')->default(0);
                $t->text('note')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_task_activity_logs')) {
            Schema::create('internal_task_activity_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('task_id')->constrained('internal_tasks')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->string('action', 60);
                $t->text('description')->nullable();
                $t->text('meta')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_user_presence')) {
            Schema::create('internal_user_presence', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $t->timestamp('last_seen_at')->nullable();
                $t->foreignId('current_task_id')->nullable()->constrained('internal_tasks')->onDelete('set null');
                $t->string('status', 20)->default('offline');
                $t->timestamps();
            });
        }
    }

    protected static function addProjectMetaColumns(): void
    {
        if (Schema::hasColumn('internal_projects', 'notes')) {
            return;
        }

        $columns = [
            'production_url' => fn (Blueprint $t) => $t->string('production_url', 500)->nullable(),
            'development_url' => fn (Blueprint $t) => $t->string('development_url', 500)->nullable(),
            'hosting_provider' => fn (Blueprint $t) => $t->string('hosting_provider', 150)->nullable(),
            'account_type' => fn (Blueprint $t) => $t->string('account_type', 30)->nullable(),
            'account_email' => fn (Blueprint $t) => $t->string('account_email', 255)->nullable(),
            'account_username' => fn (Blueprint $t) => $t->string('account_username', 255)->nullable(),
            'account_password' => fn (Blueprint $t) => $t->text('account_password')->nullable(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('internal_projects', $name)) {
                Schema::table('internal_projects', function (Blueprint $t) use ($definition) {
                    $definition($t);
                });
            }
        }
    }

    protected static function guideSchema(): void
    {
        if (! Schema::hasTable('internal_guides')) {
            Schema::create('internal_guides', function (Blueprint $t) {
                $t->id();
                $t->string('module_key', 40);
                $t->string('role_scope', 20)->default('all');
                $t->string('title', 200);
                $t->string('slug', 200);
                $t->longText('content');
                $t->integer('sort')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_guide_steps')) {
            Schema::create('internal_guide_steps', function (Blueprint $t) {
                $t->id();
                $t->string('module_key', 40);
                $t->string('title', 200);
                $t->string('target_selector', 255)->nullable();
                $t->text('description');
                $t->integer('sort')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('internal_faqs')) {
            Schema::create('internal_faqs', function (Blueprint $t) {
                $t->id();
                $t->string('module_key', 40);
                $t->string('question', 255);
                $t->text('answer');
                $t->integer('sort')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
    }

    protected static function seedGuides(): void
    {
        if (DB::table('internal_guides')->count() === 0) {
            $guides = [
                ['absensi', 'all', 'Panduan Presensi & Selfie', 'panduan-presensi-selfie', 'Aktifkan izin lokasi (GPS) dan izin kamera pada browser. Buka menu Absensi, pilih tombol Ambil Foto, lalu arahkan wajah ke kamera. Foto akan diberi watermark waktu, koordinat, dan nama Anda. Tekan Check In sebelum batas toleransi shift. Untuk Check Out, ulangi langkah yang sama pada sore hari.'],
                ['absensi', 'all', 'Pengajuan Izin, Sakit, dan Cuti', 'pengajuan-izin-cuti', 'Buka tab Izin/Cuti pada halaman Absensi. Pilih jenis pengajuan (izin, sakit, atau cuti), isi tanggal mulai dan selesai, tulis alasan, lalu unggah lampiran bukti bila ada. Setelah dikirim, pengajuan menunggu persetujuan atasan.'],
                ['absensi', 'admin', 'Pengaturan Geofence dan Shift', 'pengaturan-geofence-shift', 'Admin dapat mengatur titik koordinat kantor, radius aman, dan shift kerja. Buka tab Pengaturan pada halaman Absensi. Tambahkan kantor dengan latitude/longitude, tentukan radius (meter), lalu atur jam masuk, jam keluar, dan grace period. Tetapkan mode kerja karyawan: WFO (wajib dalam radius), WFH (bebas lokasi rumah), atau WFA (bebas lokasi).'],
                ['project_management', 'all', 'Membuat Proyek dan Task', 'membuat-proyek-task', 'Buka menu Project Management, klik Proyek Baru, isi nama, tanggal mulai, dan tenggat. Setelah proyek dibuat, tambahkan task pada papan Kanban. Isi judul, assignee, prioritas, dan tenggat. Pecah task besar menjadi sub-task melalui halaman detail task.'],
                ['project_management', 'all', 'Menggunakan Kanban dan Gantt', 'kanban-gantt', 'Kanban menampilkan task per status: Backlog, To Do, In Progress, In Review, dan Done. Seret kartu ke kolom lain untuk mengubah status. Gantt menampilkan timeline proyek; Anda dapat membuka detail task untuk menyesuaikan tanggal mulai dan tenggat.'],
                ['project_management', 'all', 'Worklog, Komentar, dan Lampiran', 'worklog-komentar-lampiran', 'Pada detail task, gunakan timer Mulai/Pause untuk mencatat durasi kerja atau tambahkan worklog manual. Diskusikan pekerjaan melalui komentar; sebut anggota dengan @username. Unggah dokumen hasil pekerjaan pada bagian Lampiran.'],
            ];
            foreach ($guides as $i => $g) {
                DB::table('internal_guides')->insert([
                    'module_key' => $g[0], 'role_scope' => $g[1], 'title' => $g[2], 'slug' => $g[3],
                    'content' => $g[4], 'sort' => $i + 1, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('internal_faqs')->count() === 0) {
            $faqs = [
                ['absensi', 'GPS tidak terdeteksi / gagal absen GPS?', 'Pastikan izin lokasi browser aktif, mode GPS perangkat menyala, dan Anda berada di area terbuka. Coba muat ulang halaman dan tekan tombol lokasi kembali. Jika memakai WFO, pastikan berada dalam radius kantor yang ditentukan.'],
                ['absensi', 'Kamera tidak bisa dibuka?', 'Periksa izin kamera pada browser. Tutup aplikasi lain yang menggunakan kamera, lalu muat ulang halaman. Gunakan browser modern (Chrome/Safari/Edge terbaru) dan koneksi HTTPS.'],
                ['absensi', 'Kenapa absen ditolak karena fake GPS?', 'Sistem mendeteksi indikasi lokasi palsu/root. Matikan aplikasi Fake GPS atau mock location pada perangkat, restart perangkat, lalu coba absen kembali.'],
                ['project_management', 'Cara mengubah status task?', 'Seret kartu task pada papan Kanban ke kolom status yang diinginkan, atau buka detail task lalu ubah status melalui dropdown. Perubahan tersimpan otomatis dan tercatat pada audit trail.'],
                ['project_management', 'Kenapa progress task tidak 100%?', 'Progress dihitung dari checklist sub-task dan status task. Centang seluruh sub-task dan pindahkan task ke Done untuk mencapai 100%.'],
                ['project_management', 'Bagaimana mencatat jam kerja?', 'Buka detail task, klik Mulai pada Worklog saat mulai bekerja, lalu Pause/Selesai saat berhenti. Anda juga dapat menambahkan worklog manual dengan durasi dan catatan.'],
            ];
            foreach ($faqs as $i => $f) {
                DB::table('internal_faqs')->insert([
                    'module_key' => $f[0], 'question' => $f[1], 'answer' => $f[2],
                    'sort' => $i + 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('internal_guide_steps')->count() === 0) {
            $steps = [
                ['absensi', 'Ambil Foto Selfie', '#attendance-camera', 'Pastikan wajah terlihat jelas dalam kamera, lalu tekan Ambil Foto.', 1],
                ['absensi', 'Aktifkan Lokasi', '#attendance-location', 'Tekan tombol Lokasi Saya dan izinkan browser mengakses GPS.', 2],
                ['absensi', 'Check In', '#attendance-action', 'Tekan Check In sebelum batas toleransi shift.', 3],
                ['project_management', 'Pilih Proyek', '#pm-project-select', 'Pilih proyek yang akan dikerjakan dari daftar proyek.', 1],
                ['project_management', 'Kanban Board', '#pm-kanban', 'Seret kartu task ke kolom status untuk memperbarui progres.', 2],
                ['project_management', 'Detail Task', '#pm-task-link', 'Klik kartu task untuk membuka detail, komentar, worklog, dan lampiran.', 3],
            ];
            foreach ($steps as $s) {
                DB::table('internal_guide_steps')->insert([
                    'module_key' => $s[0], 'title' => $s[1], 'target_selector' => $s[2],
                    'description' => $s[3], 'sort' => $s[4], 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    protected static function dataCenterSchema(): void
    {
        if (! Schema::hasTable('internal_credentials')) {
            Schema::create('internal_credentials', function (Blueprint $t) {
                $t->id();
                $t->string('category', 40)->default('other');
                $t->string('group_name', 100)->nullable();
                $t->string('vendor', 150)->nullable();
                $t->string('name', 255)->nullable();
                $t->string('ip_address', 255)->nullable();
                $t->string('url', 500)->nullable();
                $t->string('username', 255)->nullable();
                $t->text('password')->nullable();
                $t->string('email', 255)->nullable();
                $t->date('expired_date')->nullable();
                $t->string('status', 40)->nullable();
                $t->text('notes')->nullable();
                $t->text('extra')->nullable();
                $t->timestamps();
                $t->index('category');
                $t->index('expired_date');
            });
        }

        if (! Schema::hasTable('internal_activity_logs')) {
            Schema::create('internal_activity_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->foreignId('project_id')->nullable()->constrained('internal_projects')->onDelete('cascade');
                $t->foreignId('task_id')->nullable()->constrained('internal_tasks')->onDelete('set null');
                $t->string('type', 40)->default('task');
                $t->string('action', 40)->default('update');
                $t->text('description')->nullable();
                $t->text('meta')->nullable();
                $t->date('activity_date')->nullable();
                $t->timestamps();
                $t->index(['user_id', 'activity_date']);
                $t->index('project_id');
            });
        }
    }

    protected static function activityReportSchema(): void
    {
        if (! Schema::hasColumn('internal_employee_settings', 'divisi')) {
            Schema::table('internal_employee_settings', function (Blueprint $t) {
                $t->string('divisi', 100)->default('Divisi Project RST');
            });
        }
        if (! Schema::hasColumn('internal_tasks', 'phase')) {
            Schema::table('internal_tasks', function (Blueprint $t) {
                $t->string('phase', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('internal_projects', 'last_status')) {
            Schema::table('internal_projects', function (Blueprint $t) {
                $t->text('last_status')->nullable();
                $t->timestamp('last_status_at')->nullable();
                $t->unsignedBigInteger('last_status_by')->nullable();
            });
        }

        if (! Schema::hasColumn('internal_projects', 'daerah')) {
            Schema::table('internal_projects', function (Blueprint $t) {
                $t->string('daerah', 150)->nullable();
                $t->string('instansi', 200)->nullable();
            });
        }

        if (! Schema::hasTable('internal_email_logs')) {
            Schema::create('internal_email_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->string('recipient_email', 255)->nullable();
                $t->string('recipient_name', 200)->nullable();
                $t->string('subject', 255)->nullable();
                $t->longText('body')->nullable();
                $t->string('status', 20)->default('pending');
                $t->text('error')->nullable();
                $t->date('period_from')->nullable();
                $t->date('period_to')->nullable();
                $t->timestamp('sent_at')->nullable();
                $t->timestamps();
                $t->index('status');
            });
        }

        if (! Schema::hasTable('internal_org_structure')) {
            Schema::create('internal_org_structure', function (Blueprint $t) {
                $t->id();
                $t->string('name', 150);
                $t->foreignId('parent_id')->nullable()->constrained('internal_org_structure')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $t->integer('sort')->default(0);
                $t->timestamps();
            });

            self::syncDefaultOrgStructure();
        } else {
            // DB existing: lengkapi bila ada node default yang belum ada (mis. Tim IT / System Analyst).
            $defaultNames = array_column(self::defaultOrgTree(), 'name');
            $existingNames = DB::table('internal_org_structure')
                ->whereIn('name', $defaultNames)->distinct()->pluck('name')->all();
            if (count($existingNames) < count($defaultNames)) {
                self::syncDefaultOrgStructure();
            }
        }
    }

    public static function defaultOrgTree(): array
    {
        return [
            ['name' => 'Direktur', 'parent' => null],
            ['name' => 'General Manager', 'parent' => 'Direktur'],

            // Divisi Project & IT
            ['name' => 'Project Manager', 'parent' => 'General Manager'],
            ['name' => 'Konsultan', 'parent' => 'Project Manager'],
            ['name' => 'Tim IT', 'parent' => 'Konsultan'],
            ['name' => 'System Analyst', 'parent' => 'Tim IT'],
            ['name' => 'Programmer', 'parent' => 'Tim IT'],
            ['name' => 'Designer', 'parent' => 'Tim IT'],
            ['name' => 'Teknisi Jaringan', 'parent' => 'Tim IT'],
            ['name' => 'System Administrator', 'parent' => 'Tim IT'],
            ['name' => 'QA / Tester', 'parent' => 'Tim IT'],
            ['name' => 'IT Support', 'parent' => 'Tim IT'],
            ['name' => 'Staf', 'parent' => 'Tim IT'],

            // HRD
            ['name' => 'HRD', 'parent' => 'General Manager'],
            ['name' => 'HR Manager', 'parent' => 'HRD'],
            ['name' => 'Staf HR', 'parent' => 'HRD'],

            // Akuntansi & Keuangan
            ['name' => 'Akuntansi & Keuangan', 'parent' => 'General Manager'],
            ['name' => 'Akuntan', 'parent' => 'Akuntansi & Keuangan'],
            ['name' => 'Staf Keuangan', 'parent' => 'Akuntansi & Keuangan'],

            // Marketing
            ['name' => 'Marketing', 'parent' => 'General Manager'],
            ['name' => 'Marketing Manager', 'parent' => 'Marketing'],
            ['name' => 'Staf Marketing', 'parent' => 'Marketing'],

            // Umum & Operasional
            ['name' => 'Umum & Operasional', 'parent' => 'General Manager'],
            ['name' => 'Admin / GA', 'parent' => 'Umum & Operasional'],
            ['name' => 'Staf Operasional', 'parent' => 'Umum & Operasional'],
        ];
    }

    public static function syncDefaultOrgStructure(): array
    {
        $ids = [];
        $sortCounters = [];
        $created = 0;
        $updated = 0;

        foreach (self::defaultOrgTree() as $node) {
            $parentKey = $node['parent'] ?? 'root';
            $sortCounters[$parentKey] = ($sortCounters[$parentKey] ?? 0) + 1;
            $sort = $sortCounters[$parentKey];
            $parentId = $node['parent'] ? ($ids[$node['parent']] ?? null) : null;

            $existing = DB::table('internal_org_structure')->where('name', $node['name'])->orderBy('id')->first();
            if ($existing) {
                $ids[$node['name']] = (int) $existing->id;
                if ((int) ($existing->parent_id ?? 0) !== (int) ($parentId ?? 0) || (int) $existing->sort !== (int) $sort) {
                    DB::table('internal_org_structure')->where('id', $existing->id)->update([
                        'parent_id' => $parentId, 'sort' => $sort, 'updated_at' => now(),
                    ]);
                    $updated++;
                }
            } else {
                $ids[$node['name']] = (int) DB::table('internal_org_structure')->insertGetId([
                    'name' => $node['name'], 'parent_id' => $parentId, 'user_id' => null,
                    'sort' => $sort, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $created++;
            }

            // Pastikan role untuk setiap jabatan struktur tersedia.
            self::ensureOrgRole($node['name']);
        }

        return ['created' => $created, 'updated' => $updated];
    }

    protected static function contentSchema(): void
    {
        if (! Schema::hasTable('internal_video_tutorials')) {
            Schema::create('internal_video_tutorials', function (Blueprint $t) {
                $t->id();
                $t->string('title', 255);
                $t->string('youtube_url', 500);
                $t->string('badge_text', 100)->nullable();
                $t->string('badge_color', 50)->nullable()->default('bg-blue-600');
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('sort_order')->default(0);
                $t->timestamps();
                $t->softDeletes();
            });
        }
        if (! Schema::hasTable('internal_dokumentasi')) {
            Schema::create('internal_dokumentasi', function (Blueprint $t) {
                $t->id();
                $t->string('title', 255);
                $t->text('description')->nullable();
                $t->string('file_url', 500)->nullable();
                $t->string('icon_color', 50)->nullable()->default('emerald');
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('sort_order')->default(0);
                $t->timestamps();
                $t->softDeletes();
            });
        }
        if (! Schema::hasTable('internal_announcements')) {
            Schema::create('internal_announcements', function (Blueprint $t) {
                $t->id();
                $t->string('code', 100)->nullable();
                $t->string('slug', 200)->nullable();
                $t->string('title', 255);
                $t->longText('content')->nullable();
                $t->string('pdf_path', 500)->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->softDeletes();
            });
        }

        if (Schema::hasTable('internal_video_tutorials') && DB::table('internal_video_tutorials')->count() === 0) {
            $now = now();
            $rows = [
                ['title' => 'Tentang InternalApp', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Pendahuluan', 'badge_color' => 'bg-indigo-600', 'sort_order' => 1],
                ['title' => 'Cara Presensi / Check In', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Absensi', 'badge_color' => 'bg-blue-600', 'sort_order' => 2],
                ['title' => 'Mengelola Project & Task', 'youtube_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'badge_text' => 'Project', 'badge_color' => 'bg-emerald-600', 'sort_order' => 3],
            ];
            foreach ($rows as $r) {
                DB::table('internal_video_tutorials')->insert($r + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (Schema::hasTable('internal_dokumentasi') && DB::table('internal_dokumentasi')->count() === 0) {
            $now = now();
            $rows = [
                ['title' => 'Panduan Penggunaan InternalApp', 'description' => 'Panduan umum aplikasi', 'icon_color' => 'emerald', 'sort_order' => 1],
                ['title' => 'SOP Presensi & Cuti', 'description' => 'Alur presensi dan pengajuan cuti', 'icon_color' => 'rose', 'sort_order' => 2],
                ['title' => 'SOP Project Management', 'description' => 'Alur project, task, dan laporan aktivitas', 'icon_color' => 'blue', 'sort_order' => 3],
            ];
            foreach ($rows as $r) {
                DB::table('internal_dokumentasi')->insert($r + ['file_url' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (Schema::hasTable('internal_announcements') && DB::table('internal_announcements')->count() === 0) {
            DB::table('internal_announcements')->insert([
                'code' => 'INFO-001', 'slug' => 'selamat-datang', 'title' => 'Selamat Datang di InternalApp',
                'content' => '<p>Gunakan aplikasi ini untuk presensi, pengajuan cuti, project management, dan laporan aktivitas karyawan.</p>',
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected static function seedImportedData(): void
    {
        if (! class_exists(\App\Services\InternalAppImportService::class)) {
            return;
        }

        if (Schema::hasTable('internal_credentials') && DB::table('internal_credentials')->count() === 0) {
            $path = public_path('static-files/internal_app/Update Data Server & Domain 2026 .xlsx');
            if (is_file($path)) {
                try {
                    \App\Services\InternalAppImportService::importCredentials($path);
                } catch (\Throwable $e) {
                    // Biarkan kosong; admin bisa import manual dari menu Server & Domain.
                }
            }
        }

        if (Schema::hasTable('internal_projects') && DB::table('internal_projects')->count() === 0) {
            $path = public_path('static-files/internal_app/ACTIVITY DIVISI PROJECT RAJA SAKTI TELEMATIKA 2026.xlsx');
            if (is_file($path)) {
                try {
                    \App\Services\InternalAppImportService::importActivityProjects($path);
                } catch (\Throwable $e) {
                    // Biarkan kosong; admin bisa import manual dari menu Activity.
                }
            }
        }
    }

    protected static function seedRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        if (! DB::table('roles')->where('slug', 'super-admin')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'model' => 'admin',
                'is_active' => true,
                'is_super_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function orgRoleSlugs(): array
    {
        $slugs = ['super-admin'];
        foreach (self::defaultOrgTree() as $node) {
            $slug = Str::slug($node['name']);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public static function ensureOrgRole(string $name, ?string $oldName = null): int
    {
        if (! Schema::hasTable('roles')) {
            return 0;
        }
        $newSlug = Str::slug($name);
        if ($newSlug === '') {
            return 0;
        }

        if ($oldName) {
            $oldSlug = Str::slug($oldName);
            if ($oldSlug && $oldSlug !== $newSlug) {
                $oldRole = DB::table('roles')->where('slug', $oldSlug)->first();
                if ($oldRole) {
                    $conflict = DB::table('roles')->where('slug', $newSlug)->where('id', '<>', $oldRole->id)->first();
                    if (! $conflict) {
                        DB::table('roles')->where('id', $oldRole->id)->update([
                            'name' => $name, 'slug' => $newSlug, 'updated_at' => now(),
                        ]);

                        return (int) $oldRole->id;
                    }
                    foreach (DB::table('user_roles')->where('role_id', $oldRole->id)->get() as $ur) {
                        $exists = DB::table('user_roles')->where('user_id', $ur->user_id)->where('role_id', $conflict->id)->exists();
                        if ($exists) {
                            DB::table('user_roles')->where('id', $ur->id)->delete();
                        } else {
                            DB::table('user_roles')->where('id', $ur->id)->update(['role_id' => $conflict->id]);
                        }
                    }
                    DB::table('roles')->where('id', $oldRole->id)->delete();

                    return (int) $conflict->id;
                }
            }
        }

        $role = DB::table('roles')->where('slug', $newSlug)->first();
        if ($role) {
            if ($role->name !== $name) {
                DB::table('roles')->where('id', $role->id)->update(['name' => $name, 'updated_at' => now()]);
            }

            return (int) $role->id;
        }

        return (int) DB::table('roles')->insertGetId([
            'name' => $name,
            'slug' => $newSlug,
            'model' => 'organization',
            'is_active' => true,
            'is_super_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected static function syncOrgRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        if (InternalAppService::setting('org_roles_synced_v3') === '1') {
            return;
        }

        foreach (self::defaultOrgTree() as $node) {
            self::ensureOrgRole($node['name']);
        }

        // Migrasi role lama agar sesuai struktur organisasi.
        $legacyMap = [
            'member' => 'Staf',
            'atasan' => 'Project Manager',
            'supervisor' => 'Project Manager',
        ];
        foreach ($legacyMap as $oldSlug => $newName) {
            $old = DB::table('roles')->where('slug', $oldSlug)->first();
            $new = DB::table('roles')->where('slug', Str::slug($newName))->first();
            if (! $old || ! $new) {
                continue;
            }
            foreach (DB::table('user_roles')->where('role_id', $old->id)->get() as $ur) {
                $exists = DB::table('user_roles')->where('user_id', $ur->user_id)->where('role_id', $new->id)->exists();
                if ($exists) {
                    DB::table('user_roles')->where('id', $ur->id)->delete();
                } else {
                    DB::table('user_roles')->where('id', $ur->id)->update(['role_id' => $new->id]);
                }
            }
            if (! DB::table('user_roles')->where('role_id', $old->id)->exists()) {
                DB::table('roles')->where('id', $old->id)->delete();
            }
        }

        InternalAppService::putSetting('org_roles_synced_v3', '1');
    }
}
