<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Support\Feature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AccessController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index(Request $request)
    {
        abort_unless($this->isAdmin(), 403);

        $features = Feature::groups();

        $roles = DB::table('roles')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $selectedRoleId = (int) $request->query('role', 0);
        $selectedRole = $roles->firstWhere('id', $selectedRoleId);

        if (! $selectedRole) {
            $selectedRoleId = 0;
        }

        $permissions = [];
        if ($selectedRoleId > 0) {
            $rows = DB::table('permissions')
                ->where('first_big_integer_key', $selectedRoleId)
                ->where('model', 'feature')
                ->where('code', 'like', 'internalapp.%')
                ->get(['code', 'action']);

            foreach ($rows as $row) {
                $permissions[$row->code][$row->action] = true;
            }
        }

        $groupStats = [];
        $grantedTotal = 0;
        $actionTotal = 0;

        foreach ($features as $index => $group) {
            $groupGranted = 0;
            $groupTotal = 0;

            foreach ($group['items'] as $item) {
                foreach ($item['actions'] as $action) {
                    $groupTotal++;

                    if (isset($permissions[$item['id']][$action])) {
                        $groupGranted++;
                    }
                }
            }

            $groupStats[$index] = ['granted' => $groupGranted, 'total' => $groupTotal];
            $grantedTotal += $groupGranted;
            $actionTotal += $groupTotal;
        }

        return view('internal-app.access.index', compact(
            'features',
            'roles',
            'selectedRoleId',
            'selectedRole',
            'permissions',
            'groupStats',
            'grantedTotal',
            'actionTotal',
        ));
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
