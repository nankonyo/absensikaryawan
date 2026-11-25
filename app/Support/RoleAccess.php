<?php

namespace App\Support;

class RoleAccess
{
    /**
     * Roles that receive every available access.
     */
    public const FULL_ROLES = ['admin', 'direksi', 'direktur', 'super-admin'];

    /**
     * Default feature permissions for a role, keyed by feature code.
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaultsFor(string $slug): array
    {
        $slug = strtolower(trim($slug));

        if (in_array($slug, self::FULL_ROLES, true)) {
            return self::all();
        }

        $permissions = self::standard();

        if (self::isManagerial($slug)) {
            $permissions = array_replace_recursive($permissions, self::managerial());
        }

        if (self::isHr($slug)) {
            $permissions = array_replace_recursive($permissions, self::hr());
        }

        if (self::isIt($slug)) {
            $permissions = array_replace_recursive($permissions, self::it());
        }

        if ($slug === 'system-administrator') {
            $permissions = array_replace_recursive($permissions, self::systemAdministrator());
        }

        return $permissions;
    }

    /**
     * Every feature and action in the catalog.
     *
     * @return array<string, array<string, bool>>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (Feature::groups() as $group) {
            foreach ($group['items'] as $item) {
                foreach ($item['actions'] as $action) {
                    $permissions[$item['id']][$action] = true;
                }
            }
        }

        return $permissions;
    }

    /**
     * Access shared by every non-admin role.
     *
     * @return array<string, array<string, bool>>
     */
    protected static function standard(): array
    {
        return self::map([
            'internalapp.dashboard' => ['read'],
            'internalapp.absensi.presensi' => ['read'],
            'internalapp.leave.requests' => ['read', 'create', 'update', 'delete'],
            'internalapp.project.project' => ['read', 'create', 'update', 'delete'],
            'internalapp.project.kanban' => ['read', 'create', 'update', 'delete'],
            'internalapp.project.gantt' => ['read'],
            'internalapp.project.guide' => ['read'],
            'internalapp.activity.activities' => ['read', 'create', 'export'],
            'internalapp.activity.report' => ['read'],
            'internalapp.content.video-tutorials' => ['read'],
            'internalapp.content.dokumentasi' => ['read'],
            'internalapp.content.pengumuman' => ['read'],
            'internalapp.admin.organization' => ['read'],
        ]);
    }

    /**
     * Extra access for managers and supervisors.
     *
     * @return array<string, array<string, bool>>
     */
    protected static function managerial(): array
    {
        return self::map([
            'internalapp.absensi.approval' => ['read', 'update'],
            'internalapp.absensi.rekap' => ['read', 'export'],
            'internalapp.absensi.verification' => ['read', 'update'],
            'internalapp.leave.approval' => ['read', 'update'],
            'internalapp.admin.users' => ['read'],
            'internalapp.hr.team-attendance' => ['read'],
            'internalapp.hr.leave-approval' => ['read', 'update'],
            'internalapp.hr.leave-verification' => ['read', 'update'],
            'internalapp.hr.reports' => ['read', 'export'],
            'internalapp.hr.audit-logs' => ['read'],
        ]);
    }

    /**
     * Extra access for HR roles.
     *
     * @return array<string, array<string, bool>>
     */
    protected static function hr(): array
    {
        return self::map([
            'internalapp.absensi.approval' => ['read', 'update'],
            'internalapp.absensi.entries' => ['read', 'create', 'update', 'delete'],
            'internalapp.absensi.rekap' => ['read', 'export'],
            'internalapp.absensi.verification' => ['read', 'update'],
            'internalapp.absensi.holidays' => ['read', 'create', 'update', 'delete'],
            'internalapp.absensi.settings' => ['read', 'update'],
            'internalapp.leave.approval' => ['read', 'update'],
            'internalapp.leave.types' => ['read', 'create', 'update', 'delete'],
            'internalapp.admin.users' => ['read', 'create', 'update', 'delete'],
            'internalapp.admin.organization' => ['read', 'create', 'update', 'delete'],
            'internalapp.hr.attendances' => ['read', 'export'],
            'internalapp.hr.team-attendance' => ['read'],
            'internalapp.hr.leave-approval' => ['read', 'update'],
            'internalapp.hr.leave-verification' => ['read', 'update'],
            'internalapp.hr.reports' => ['read', 'export'],
            'internalapp.hr.audit-logs' => ['read'],
            'internalapp.hr.master-data' => ['read', 'create', 'update', 'delete'],
            'internalapp.data.email-logs' => ['read'],
        ]);
    }

    /**
     * Extra access for IT roles.
     *
     * @return array<string, array<string, bool>>
     */
    protected static function it(): array
    {
        return self::map([
            'internalapp.admin.organization' => ['read', 'create', 'update', 'delete'],
            'internalapp.hr.audit-logs' => ['read'],
            'internalapp.data.servers' => ['read'],
            'internalapp.data.manage-data' => ['read'],
            'internalapp.data.email-logs' => ['read'],
        ]);
    }

    /**
     * Extra access for the system administrator role.
     *
     * @return array<string, array<string, bool>>
     */
    protected static function systemAdministrator(): array
    {
        return self::map([
            'internalapp.data.servers' => ['read', 'create', 'update', 'delete'],
            'internalapp.data.manage-data' => ['read', 'create', 'update', 'delete'],
        ]);
    }

    protected static function isManagerial(string $slug): bool
    {
        foreach (['general-manager', 'manager', 'supervisor', 'sekretaris', 'project-manager', 'konsultan'] as $keyword) {
            if ($slug === $keyword || str_contains($slug, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected static function isHr(string $slug): bool
    {
        return str_contains($slug, 'hr') || str_contains($slug, 'personalia');
    }

    protected static function isIt(string $slug): bool
    {
        return in_array($slug, [
            'tim-it',
            'system-analyst',
            'programmer',
            'designer',
            'teknisi-jaringan',
            'system-administrator',
            'qa-tester',
            'it-support',
            'konsultan',
        ], true);
    }

    /**
     * @param  array<string, array<int, string>>  $definitions
     * @return array<string, array<string, bool>>
     */
    protected static function map(array $definitions): array
    {
        $permissions = [];

        foreach ($definitions as $code => $actions) {
            foreach ($actions as $action) {
                $permissions[$code][$action] = true;
            }
        }

        return $permissions;
    }
}
