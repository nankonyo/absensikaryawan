<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'role_id',
        'phone',
        'employee_id',
        'birth_place',
        'birth_date',
        'gender',
        'position',
        'position_id',
        'department_id',
        'unit_id',
        'supervisor_id',
        'branch_id',
        'employment_status',
    ];

    /**
     * Get the role of the user
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Public URL of the uploaded avatar (also used as face reference).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::url($this->avatar) : null;
    }

    /**
     * Employment contract details live in their own table.
     */
    public function contract()
    {
        return $this->hasOne(UserContract::class);
    }

    public function getContractStartDateAttribute()
    {
        return $this->contract?->start_date;
    }

    public function getContractEndDateAttribute()
    {
        return $this->contract?->end_date;
    }

    public function getContractDocumentAttribute(): ?string
    {
        return $this->contract?->document;
    }

    /**
     * Public URL of the uploaded contract / permanent employee document.
     */
    public function getContractDocumentUrlAttribute(): ?string
    {
        return $this->contract_document ? \Illuminate\Support\Facades\Storage::url($this->contract_document) : null;
    }

    public function isContract(): bool
    {
        return $this->employment_status === 'contract';
    }

    /**
     * Get the supervisor of the user
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get all subordinates of the user
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function positionMaster()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(UserAttributeValue::class);
    }

    /**
     * Whether this account still has a superior, i.e. is allowed to take attendance.
     */
    public function hasSupervisor(): bool
    {
        return ! is_null($this->supervisor_id);
    }

    /**
     * Group name for "Tim Saya": supervisors group by unit,
     * every other manager groups by department.
     */
    public function teamGroupName(User $member): string
    {
        if ($this->isSupervisor() || ($this->isManager() && $this->unit_id)) {
            return $member->unit?->name ?? __('ui.no_unit');
        }

        return $member->department?->name ?? __('ui.no_department');
    }

    /**
     * All subordinate ids at any depth below this user.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $all = [];
        $level = [$this->id];

        while ($level !== []) {
            $children = static::whereIn('supervisor_id', $level)->pluck('id')->all();

            if ($children === []) {
                break;
            }

            $all = array_merge($all, $children);
            $level = $children;
        }

        return array_values(array_unique($all));
    }

    public function isDireksi(): bool
    {
        return $this->role?->name === 'direksi';
    }

    public function isGeneralManager(): bool
    {
        return $this->role?->name === 'general_manager';
    }

    public function isSekretaris(): bool
    {
        return $this->role?->name === 'sekretaris';
    }

    /**
     * Secretariat department with job level 1, 2 or 3, plus admin/direksi.
     */
    public function hasFullEmployeeAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $level = $this->positionMaster?->level ?? $this->role?->level;

        return $this->department?->name === 'Sekretariat'
            && in_array((int) $level, [1, 2, 3], true);
    }

    /**
     * Secretariat department with job level 1, 2 or 3 may verify leave requests.
     */
    public function canVerifyLeaves(): bool
    {
        return $this->hasFullEmployeeAccess();
    }

    /**
     * Full-access roles plus general managers may see every employee's attendance.
     */
    public function canViewAllTeamAttendance(): bool
    {
        return $this->hasFullEmployeeAccess() || $this->isGeneralManager() || $this->isDireksi();
    }

    /**
     * Full-access roles may verify outside-zone attendance for every employee.
     */
    public function canVerifyAllAttendance(): bool
    {
        return $this->hasFullEmployeeAccess() || $this->isDireksi();
    }

    /**
     * Secretariat department with job level 1, 2 or 3 may fully manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasFullEmployeeAccess();
    }

    public function isManager(): bool
    {
        return $this->role?->name === 'manager';
    }

    public function isSupervisor(): bool
    {
        return $this->role?->name === 'supervisor';
    }

    public function isStaff(): bool
    {
        return $this->role?->name === 'staff';
    }

    /**
     * Get all attendances of the user
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all leaves of the user
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Admin and direksi own the admin area (master data + all data access).
     */
    public function isAdmin(): bool
    {
        return (bool) $this->role?->isAdmin();
    }

    /**
     * Any hierarchical role that opens the team dashboard.
     */
    public function isAtasan(): bool
    {
        return $this->isGeneralManager() || $this->isSekretaris() || $this->isManager() || $this->isSupervisor();
    }

    public function isKaryawan(): bool
    {
        return $this->isStaff();
    }

    /**
     * Label of the role for display, translated.
     */
    public function roleLabel(): string
    {
        return __('ui.roles.'.($this->role?->name ?? 'staff'));
    }

    /**
     * Get role name
     */
    public function getRoleName(): string
    {
        return $this->role?->display_name ?? 'Unknown';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }
}
