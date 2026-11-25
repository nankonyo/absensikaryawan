<?php

namespace App\Support;

use Illuminate\Support\Str;

class Feature
{
    /**
     * Katalog lengkap fitur Internal App beserta aksi yang tersedia.
     * Kode fitur memakai awalan "internalapp." agar mudah difilter.
     */
    public static function groups(): array
    {
        return [
            [
                'group' => 'Dashboard',
                'items' => [
                    ['id' => 'internalapp.dashboard', 'name' => 'Dashboard', 'actions' => ['read']],
                ],
            ],
            [
                'group' => 'Absensi',
                'items' => [
                    ['id' => 'internalapp.absensi.presensi', 'name' => 'Presensi', 'actions' => ['read']],
                    ['id' => 'internalapp.absensi.approval', 'name' => 'Approval Presensi', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.absensi.entries', 'name' => 'Input Laporan', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.absensi.rekap', 'name' => 'Rekap Absensi', 'actions' => ['read', 'export']],
                    ['id' => 'internalapp.absensi.verification', 'name' => 'Verifikasi Presensi', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.absensi.holidays', 'name' => 'Hari Libur', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.absensi.settings', 'name' => 'Pengaturan Absensi', 'actions' => ['read', 'update']],
                ],
            ],
            [
                'group' => 'Cuti & Izin',
                'items' => [
                    ['id' => 'internalapp.leave.requests', 'name' => 'Pengajuan Cuti & Izin', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.leave.approval', 'name' => 'Persetujuan Cuti & Izin', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.leave.types', 'name' => 'Jenis Cuti', 'actions' => ['read', 'create', 'update', 'delete']],
                ],
            ],
            [
                'group' => 'Project Management',
                'items' => [
                    ['id' => 'internalapp.project.project', 'name' => 'Project', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.project.kanban', 'name' => 'Kanban', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.project.gantt', 'name' => 'Gantt', 'actions' => ['read']],
                    ['id' => 'internalapp.project.guide', 'name' => 'Panduan Project', 'actions' => ['read']],
                ],
            ],
            [
                'group' => 'Aktivitas',
                'items' => [
                    ['id' => 'internalapp.activity.activities', 'name' => 'Aktivitas Karyawan', 'actions' => ['read', 'create', 'export']],
                    ['id' => 'internalapp.activity.report', 'name' => 'Laporan Activity', 'actions' => ['read', 'export']],
                ],
            ],
            [
                'group' => 'Konten',
                'items' => [
                    ['id' => 'internalapp.content.video-tutorials', 'name' => 'Video Tutorial', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.content.dokumentasi', 'name' => 'Dokumentasi', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.content.pengumuman', 'name' => 'Pengumuman', 'actions' => ['read', 'create', 'update', 'delete']],
                ],
            ],
            [
                'group' => 'Organisasi & Pengguna',
                'items' => [
                    ['id' => 'internalapp.admin.users', 'name' => 'Pengguna', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.admin.roles', 'name' => 'Role', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.admin.access', 'name' => 'Hak Akses', 'actions' => ['read']],
                    ['id' => 'internalapp.admin.organization', 'name' => 'Struktur Organisasi', 'actions' => ['read', 'create', 'update', 'delete']],
                ],
            ],
            [
                'group' => 'Administrasi HR',
                'items' => [
                    ['id' => 'internalapp.hr.attendances', 'name' => 'Presensi Karyawan', 'actions' => ['read', 'export']],
                    ['id' => 'internalapp.hr.team-attendance', 'name' => 'Presensi Tim', 'actions' => ['read']],
                    ['id' => 'internalapp.hr.leave-approval', 'name' => 'Persetujuan Cuti Karyawan', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.hr.leave-verification', 'name' => 'Verifikasi Cuti', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.hr.reports', 'name' => 'Laporan', 'actions' => ['read', 'export']],
                    ['id' => 'internalapp.hr.audit-logs', 'name' => 'Audit Log', 'actions' => ['read']],
                    ['id' => 'internalapp.hr.master-data', 'name' => 'Data Master (Departemen, Unit, Jabatan, Cabang)', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.hr.settings', 'name' => 'Pengaturan Aplikasi', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.hr.api-docs', 'name' => 'Dokumentasi API', 'actions' => ['read']],
                ],
            ],
            [
                'group' => 'Data & Infrastruktur',
                'items' => [
                    ['id' => 'internalapp.data.servers', 'name' => 'Server & Domain', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.data.manage-data', 'name' => 'Manage Data', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.data.email-logs', 'name' => 'Pengiriman Email', 'actions' => ['read']],
                ],
            ],
        ];
    }

    /**
     * Label aksi yang ditampilkan ke pengguna.
     */
    public static function actionLabels(): array
    {
        return [
            'read' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'export' => 'Ekspor',
            'approve' => 'Setujui',
        ];
    }

    public static function actionLabel(string $action): string
    {
        return self::actionLabels()[$action] ?? Str::headline($action);
    }

    /**
     * Semua kode fitur pada katalog.
     */
    public static function codes(): array
    {
        $codes = [];
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                $codes[] = $item['id'];
            }
        }

        return $codes;
    }

    /**
     * Total aksi dari seluruh fitur.
     */
    public static function totalActions(): int
    {
        $total = 0;
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                $total += count($item['actions']);
            }
        }

        return $total;
    }
}
