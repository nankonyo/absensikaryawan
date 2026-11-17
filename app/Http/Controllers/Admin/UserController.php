<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeSetting;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserAttribute;
use App\Models\UserAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $departmentId = $request->integer('department_id') ?: null;
        $unitId = $request->integer('unit_id') ?: null;
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 5;

        $users = User::with(['role', 'department', 'unit', 'positionMaster'])
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhereHas('role', fn ($q) => $q->where('display_name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('unit', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('positionMaster', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'departmentId' => $departmentId,
            'unitId' => $unitId,
            'perPage' => $perPage,
            'departments' => Department::orderBy('name')->get(),
            'units' => $departmentId
                ? Unit::where('department_id', $departmentId)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_id' => 'required|string|max:50|unique:users,employee_id',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'work_mode' => ['nullable', Rule::in(['WFO', 'WFH', 'WFA'])],
            'position' => 'nullable|string|max:100',
            'position_id' => $this->positionRules($request),
            'department_id' => 'nullable|exists:departments,id',
            'unit_id' => $this->unitRules($request),
            'role_id' => 'required|exists:roles,id',
            'supervisor_id' => $this->supervisorRules($request),
            'branch_id' => 'nullable|exists:branches,id',
            'employment_status' => 'nullable|in:permanent,contract',
            'contract_start_date' => 'required_if:employment_status,contract|nullable|date',
            'contract_end_date' => 'required_if:employment_status,contract|nullable|date|after_or_equal:contract_start_date',
            'contract_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'attributes' => 'nullable|array',
            'attributes.*' => 'nullable|string|max:255',
        ]);

        $user = User::create($this->payload($request) + [
            'avatar' => $request->hasFile('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null,
            'password' => Hash::make($request->password),
        ]);

        $this->saveContract($user, $request);
        $this->saveWorkMode($user, $request);

        $this->syncAttributes($user, $request);

        \App\Models\AuditLog::log('user_created', 'User', null);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('ui.user_created'));
    }

    public function show(User $user)
    {
        $user->load('role', 'supervisor', 'subordinates.role', 'department', 'unit', 'branch', 'positionMaster', 'employeeSetting', 'attributeValues.attribute');

        return view('admin.users.show', [
            'user' => $user,
            'activeAttributes' => UserAttribute::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', $this->formData($user) + ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'work_mode' => ['nullable', Rule::in(['WFO', 'WFH', 'WFA'])],
            'position' => 'nullable|string|max:100',
            'position_id' => $this->positionRules($request),
            'department_id' => 'nullable|exists:departments,id',
            'unit_id' => $this->unitRules($request),
            'role_id' => 'required|exists:roles,id',
            'supervisor_id' => $this->supervisorRules($request),
            'branch_id' => 'nullable|exists:branches,id',
            'employment_status' => 'nullable|in:permanent,contract',
            'contract_start_date' => 'required_if:employment_status,contract|nullable|date',
            'contract_end_date' => 'required_if:employment_status,contract|nullable|date|after_or_equal:contract_start_date',
            'contract_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'attributes' => 'nullable|array',
            'attributes.*' => 'nullable|string|max:255',
        ]);

        $oldValues = $user->toArray();
        $data = $this->payload($request);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        $this->saveContract($user, $request);
        $this->saveWorkMode($user, $request);

        $this->syncAttributes($user, $request);

        \App\Models\AuditLog::log('user_updated', 'User', $user->id, $oldValues, $user->toArray());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('ui.user_updated'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('ui.cannot_delete_self'));
        }

        $user->delete();

        \App\Models\AuditLog::log('user_deleted', 'User', $user->id);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('ui.user_deleted'));
    }

    private function payload(Request $request): array
    {
        $positionName = $request->position;

        if ($request->filled('position_id')) {
            $positionName = Position::find($request->position_id)?->name ?? $positionName;
        }

        return [
            'name' => $request->name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'phone' => $request->phone,
            'birth_place' => $request->birth_place,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'position' => $positionName,
            'position_id' => $request->position_id,
            'department_id' => $request->department_id,
            'unit_id' => $request->unit_id,
            'role_id' => $request->role_id,
            'supervisor_id' => $request->supervisor_id,
            'branch_id' => $request->branch_id,
            'employment_status' => $request->employment_status ?: 'permanent',
        ];
    }

    private function saveWorkMode(User $user, Request $request): void
    {
        if (! $request->filled('work_mode')) {
            return;
        }

        EmployeeSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['work_mode' => strtoupper((string) $request->input('work_mode'))],
        );
    }

    /**
     * Contract details are stored in user_contracts, separate from the user row.
     * Permanent employees keep a supporting document but no contract dates.
     */
    private function saveContract(User $user, Request $request): void
    {
        $contract = $user->contract()->firstOrNew([]);
        $isContract = $request->employment_status === 'contract';

        $contract->start_date = $isContract ? $request->contract_start_date : null;
        $contract->end_date = $isContract ? $request->contract_end_date : null;

        if ($request->hasFile('contract_document')) {
            if ($contract->document) {
                Storage::disk('public')->delete($contract->document);
            }

            $contract->document = $request->file('contract_document')->store('contracts', 'public');
        }

        if ($contract->exists || $contract->document || $contract->start_date || $contract->end_date) {
            $contract->user_id = $user->id;
            $contract->save();
        }
    }

    /**
     * General manager and secretary only own a department, never a unit.
     */
    private function unitRules(Request $request): array
    {
        return [
            'nullable',
            Rule::exists('units', 'id')->where('department_id', $request->department_id),
            function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                if ($value && $this->roleLevel($request->role_id) <= 2) {
                    $fail(__('ui.unit_not_allowed'));
                }
            },
        ];
    }

    /**
     * A manager may sit inside a unit while keeping the department-level
     * manager position, so allow a position without its own unit.
     */
    private function positionRules(Request $request): array
    {
        return [
            'nullable',
            Rule::exists('positions', 'id')->where(function ($query) use ($request) {
                $query->where('department_id', $request->department_id);

                if ($this->roleLevel($request->role_id) === 3) {
                    $query->where(fn ($query) => $query
                        ->whereNull('unit_id')
                        ->orWhere('unit_id', $request->unit_id));

                    return;
                }

                $query->where('unit_id', $request->unit_id);
            }),
        ];
    }

    /**
     * Supervisor is mandatory above the top level (admin/direksi) and must have a higher role level.
     */
    private function supervisorRules(Request $request): array
    {
        return [
            Rule::requiredIf(fn () => $this->roleLevel($request->role_id) > 1),
            'nullable',
            'exists:users,id',
            function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                if (! $value) {
                    return;
                }

                $roleLevel = $this->roleLevel($request->role_id);
                $supervisor = User::with('role')->find($value);
                $targetLevel = $roleLevel - 1;
                $allowedLevels = $targetLevel >= 2
                    ? [$targetLevel, $targetLevel - 1]
                    : ($targetLevel >= 1 ? [$targetLevel] : []);

                if (! in_array((int) ($supervisor?->role?->level ?? 99), $allowedLevels, true)) {
                    $fail(__('ui.supervisor_level_invalid'));

                    return;
                }

                $supervisorLevel = (int) ($supervisor?->role?->level ?? 99);

                if ($supervisorLevel === 1) {
                    $inBranch = ! $supervisor->department_id && ! $supervisor->unit_id;
                } elseif ($supervisorLevel === 2) {
                    $inBranch = ! $supervisor->unit_id
                        && (! $supervisor->department_id
                            || (int) $supervisor->department_id === (int) $request->department_id);
                } elseif ($supervisorLevel === 3) {
                    $inBranch = (int) $supervisor->department_id === (int) $request->department_id;
                } else {
                    $inBranch = (int) $supervisor->unit_id === (int) $request->unit_id;
                }

                if (! $inBranch) {
                    $fail(__('ui.supervisor_branch_invalid'));
                }
            },
        ];
    }

    private function roleLevel(?int $roleId): int
    {
        return (int) (Role::find($roleId)?->level ?? -1);
    }

    private function syncAttributes(User $user, Request $request): void
    {
        $values = $request->input('attributes', []);

        foreach ($values as $attributeId => $value) {
            if ($value === null || $value === '') {
                UserAttributeValue::where('user_id', $user->id)
                    ->where('user_attribute_id', $attributeId)
                    ->delete();

                continue;
            }

            UserAttributeValue::updateOrCreate(
                ['user_id' => $user->id, 'user_attribute_id' => $attributeId],
                ['value' => $value],
            );
        }
    }

    private function formData(?User $user = null): array
    {
        $roles = Role::orderBy('level')->get();
        $departments = Department::orderBy('name')->get();
        $units = Unit::with('department')->orderBy('name')->get();
        $positions = Position::orderBy('level')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $supervisors = User::with(['role', 'department', 'unit', 'positionMaster'])
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['direksi', 'general_manager', 'sekretaris', 'manager', 'supervisor']))
            ->when($user, fn ($q) => $q->where('id', '!=', $user->id))
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
            'departments' => $departments,
            'units' => $units,
            'positions' => $positions,
            'supervisors' => $supervisors,
            'branches' => $branches,
            'workMode' => old('work_mode', $user?->employeeSetting?->work_mode ?: 'WFO'),
            'activeAttributes' => UserAttribute::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get(),
            'picker' => [
                'departments' => $departments->map(fn ($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])->values(),
                'units' => $units->map(fn ($unit) => [
                    'id' => $unit->id,
                    'department_id' => $unit->department_id,
                    'name' => $unit->name,
                ])->values(),
                'positions' => $positions->map(fn ($position) => [
                    'id' => $position->id,
                    'department_id' => $position->department_id,
                    'unit_id' => $position->unit_id,
                    'level' => $position->level,
                    'name' => $position->name,
                ])->values(),
                'supervisors' => $supervisors->map(fn ($supervisor) => [
                    'id' => $supervisor->id,
                    'name' => $supervisor->name,
                    'employee_id' => $supervisor->employee_id,
                    'department_id' => $supervisor->department_id,
                    'unit_id' => $supervisor->unit_id,
                    'level' => $supervisor->role?->level ?? 99,
                    'avatar_url' => $supervisor->avatar_url,
                    'department_name' => $supervisor->department?->name,
                    'position_name' => $supervisor->positionMaster?->name,
                ])->values(),
                'roleLevels' => $roles->pluck('level', 'id'),
            ],
        ];
    }
}
