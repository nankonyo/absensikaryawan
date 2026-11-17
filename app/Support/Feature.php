<?php

namespace App\Support;

class Feature
{
    public static function groups(): array
    {
        return [
            [
                'group' => 'Absensi',
                'items' => [
                    ['id' => 'internalapp.absensi.presensi', 'name' => 'Presensi', 'actions' => ['read']],
                    ['id' => 'internalapp.absensi.approval', 'name' => 'Approval', 'actions' => ['read', 'update']],
                    ['id' => 'internalapp.absensi.entries', 'name' => 'Input Laporan', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.absensi.rekap', 'name' => 'Rekap Absensi', 'actions' => ['read']],
                    ['id' => 'internalapp.absensi.settings', 'name' => 'Pengaturan Absensi', 'actions' => ['read', 'update']],
                ],
            ],
            [
                'group' => 'Project Management',
                'items' => [
                    ['id' => 'internalapp.project.project', 'name' => 'Project', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.project.kanban', 'name' => 'Kanban', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.project.gantt', 'name' => 'Gantt', 'actions' => ['read']],
                ],
            ],
            [
                'group' => 'Administrasi',
                'items' => [
                    ['id' => 'internalapp.admin.users', 'name' => 'Pengguna', 'actions' => ['read', 'create', 'update', 'delete']],
                    ['id' => 'internalapp.admin.roles', 'name' => 'Role', 'actions' => ['read', 'create', 'update', 'delete']],
                ],
            ],
        ];
    }

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
}
