<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReason;
use App\Models\Internal\Announcement;
use App\Models\Internal\Credential;
use App\Models\Internal\Dokumentasi;
use App\Models\Internal\OrgNode;
use App\Models\Internal\VideoTutorial;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminResourceController extends Controller
{
    public function schema(Request $request, string $resource)
    {
        $config = $this->config($resource, $request);

        return response()->json([
            'resource' => $resource,
            'title' => $config['title'],
            'read_only' => $config['read_only'],
            'fields' => $config['fields'],
        ]);
    }

    public function index(Request $request, string $resource)
    {
        $config = $this->config($resource, $request);
        $search = trim((string) $request->query('q'));
        $perPage = min(50, max(5, (int) ($request->query('per_page') ?: 20)));

        if ($config['read_only']) {
            $items = $this->readOnlyQuery($resource, $search)->paginate($perPage);

            $items->through(fn ($row) => [
                'id' => (int) ($row->id ?? 0),
                'title' => (string) ($row->{$config['title_field']} ?? '-'),
                'subtitle' => (string) ($row->{$config['subtitle_field']} ?? ''),
                'meta' => (string) ($row->{$config['meta_field']} ?? ''),
                'raw' => (array) $row,
            ]);

            return response()->json(['data' => $items]);
        }

        $modelClass = $config['model'];
        $items = $modelClass::query()
            ->when($search !== '' && ! empty($config['search']), function ($query) use ($search, $config) {
                $query->where(function ($query) use ($search, $config) {
                    foreach ($config['search'] as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderBy($config['order_by'] ?? 'id', $config['order_dir'] ?? 'desc')
            ->paginate($perPage);

        $items->through(function ($model) use ($config, $resource) {
            $data = $model->toArray();

            if ($resource === 'settings' && preg_match('/password|secret|token/i', (string) ($data['key'] ?? ''))) {
                $data['value'] = '********';
            }

            return array_merge($data, [
                'title' => (string) ($data[$config['title_field']] ?? '-'),
                'subtitle' => (string) ($data[$config['subtitle_field']] ?? ''),
            ]);
        });

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, string $resource)
    {
        $config = $this->writableConfig($resource);
        $modelClass = $config['model'];
        $data = $this->validated($request, $config, null);
        $data = $this->transform($data, $config, $request, true, $resource);

        $model = $modelClass::create($data);

        return response()->json(['status' => true, 'message' => 'Data berhasil ditambahkan.', 'id' => $model->id]);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $config = $this->writableConfig($resource);
        $modelClass = $config['model'];
        $model = $modelClass::findOrFail($id);

        $data = $this->validated($request, $config, $model);
        $data = $this->transform($data, $config, $request, false, $resource);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $model->update($data);

        return response()->json(['status' => true, 'message' => 'Data berhasil diperbarui.']);
    }

    public function destroy(Request $request, string $resource, int $id)
    {
        $config = $this->writableConfig($resource);
        $modelClass = $config['model'];
        $model = $modelClass::findOrFail($id);

        if ($resource === 'users' && (int) $model->id === (int) $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        $model->delete();

        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus.']);
    }

    private function config(string $resource, Request $request): array
    {
        abort_unless($this->canManage($request), 403);

        $configs = [
            'users' => [
                'title' => 'Pengguna', 'model' => User::class, 'read_only' => false,
                'search' => ['name', 'email', 'employee_id'], 'title_field' => 'name',
                'subtitle_field' => 'email', 'order_by' => 'name', 'order_dir' => 'asc',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'unique' => true],
                    ['name' => 'employee_id', 'label' => 'NIP', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'create_only' => true],
                    ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'required' => true, 'options' => $this->roleOptions()],
                    ['name' => 'department_id', 'label' => 'Departemen', 'type' => 'select', 'options' => $this->departmentOptions()],
                    ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'options' => $this->unitOptions()],
                    ['name' => 'position_id', 'label' => 'Jabatan', 'type' => 'select', 'options' => $this->positionOptions()],
                    ['name' => 'branch_id', 'label' => 'Cabang', 'type' => 'select', 'options' => $this->branchOptions()],
                    ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                    ['name' => 'employment_status', 'label' => 'Status Kerja', 'type' => 'select', 'options' => [
                        ['value' => 'permanent', 'label' => 'Tetap'],
                        ['value' => 'contract', 'label' => 'Kontrak'],
                    ]],
                ],
            ],
            'departments' => [
                'title' => 'Departemen', 'model' => Department::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'description',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
            ],
            'units' => [
                'title' => 'Unit', 'model' => Unit::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'description',
                'fields' => [
                    ['name' => 'department_id', 'label' => 'Departemen', 'type' => 'select', 'required' => true, 'options' => $this->departmentOptions()],
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
            ],
            'positions' => [
                'title' => 'Jabatan', 'model' => Position::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'description',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'level', 'label' => 'Level', 'type' => 'number', 'required' => true],
                    ['name' => 'department_id', 'label' => 'Departemen', 'type' => 'select', 'options' => $this->departmentOptions()],
                    ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'options' => $this->unitOptions()],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
            ],
            'branches' => [
                'title' => 'Cabang', 'model' => Branch::class, 'read_only' => false,
                'search' => ['name', 'code'], 'title_field' => 'name', 'subtitle_field' => 'address',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'user-attributes' => [
                'title' => 'Atribut Pengguna', 'model' => UserAttribute::class, 'read_only' => false,
                'search' => ['name', 'label'], 'title_field' => 'label', 'subtitle_field' => 'name',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => [
                        ['value' => 'text', 'label' => 'Text'],
                        ['value' => 'textarea', 'label' => 'Textarea'],
                        ['value' => 'select', 'label' => 'Select'],
                        ['value' => 'date', 'label' => 'Date'],
                    ]],
                    ['name' => 'options', 'label' => 'Options (pisahkan koma)', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                    ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'],
                ],
            ],
            'leave-types' => [
                'title' => 'Jenis Cuti', 'model' => LeaveType::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'category',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                    ['name' => 'requires_attachment', 'label' => 'Wajib Lampiran', 'type' => 'switch'],
                    ['name' => 'requires_reason', 'label' => 'Wajib Alasan', 'type' => 'switch'],
                    ['name' => 'deducts_quota', 'label' => 'Mengurangi Kuota', 'type' => 'switch'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'attendance-reasons' => [
                'title' => 'Alasan Kehadiran', 'model' => AttendanceReason::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'is_active',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'office-locations' => [
                'title' => 'Lokasi Kantor', 'model' => OfficeLocation::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'radius',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'required' => true],
                    ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'required' => true],
                    ['name' => 'radius', 'label' => 'Radius (meter)', 'type' => 'number', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'attendances' => ['title' => 'Presensi', 'read_only' => true, 'title_field' => 'user_name', 'subtitle_field' => 'date', 'meta_field' => 'status'],
            'attendance-verifications' => ['title' => 'Verifikasi Presensi', 'read_only' => true, 'title_field' => 'user_name', 'subtitle_field' => 'date', 'meta_field' => 'status'],
            'leaves' => ['title' => 'Cuti', 'read_only' => true, 'title_field' => 'user_name', 'subtitle_field' => 'leave_type_name', 'meta_field' => 'status'],
            'audit-logs' => ['title' => 'Audit Log', 'read_only' => true, 'title_field' => 'action', 'subtitle_field' => 'description', 'meta_field' => 'created_at'],
            'video-tutorials' => [
                'title' => 'Video Tutorial', 'model' => VideoTutorial::class, 'read_only' => false,
                'search' => ['title'], 'title_field' => 'title', 'subtitle_field' => 'youtube_url',
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'youtube_url', 'label' => 'URL YouTube', 'type' => 'text', 'required' => true],
                    ['name' => 'badge_text', 'label' => 'Badge', 'type' => 'text'],
                    ['name' => 'badge_color', 'label' => 'Warna Badge', 'type' => 'text'],
                    ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'dokumentasi' => [
                'title' => 'Dokumentasi', 'model' => Dokumentasi::class, 'read_only' => false,
                'search' => ['title'], 'title_field' => 'title', 'subtitle_field' => 'description',
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'file_url', 'label' => 'URL File', 'type' => 'text'],
                    ['name' => 'icon_color', 'label' => 'Warna Icon', 'type' => 'text'],
                    ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'pengumuman' => [
                'title' => 'Pengumuman', 'model' => Announcement::class, 'read_only' => false,
                'search' => ['title', 'code'], 'title_field' => 'title', 'subtitle_field' => 'code',
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'content', 'label' => 'Isi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
                ],
            ],
            'roles' => [
                'title' => 'Role', 'model' => Role::class, 'read_only' => false,
                'search' => ['name', 'display_name'], 'title_field' => 'name', 'subtitle_field' => 'display_name',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'level', 'label' => 'Level', 'type' => 'number', 'required' => true],
                    ['name' => 'display_name', 'label' => 'Nama Tampilan', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
            ],
            'servers' => [
                'title' => 'Server & Domain', 'model' => Credential::class, 'read_only' => false,
                'search' => ['name', 'vendor', 'url', 'ip_address'], 'title_field' => 'name', 'subtitle_field' => 'url',
                'fields' => [
                    ['name' => 'category', 'label' => 'Kategori', 'type' => 'text', 'required' => true],
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'vendor', 'label' => 'Vendor', 'type' => 'text'],
                    ['name' => 'ip_address', 'label' => 'IP', 'type' => 'text'],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text'],
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text'],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
                    ['name' => 'expired_date', 'label' => 'Expired (Y-m-d)', 'type' => 'text'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
                    ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
            ],
            'email-logs' => ['title' => 'Pengiriman Email', 'read_only' => true, 'title_field' => 'subject', 'subtitle_field' => 'recipient_email', 'meta_field' => 'status'],
            'organization' => [
                'title' => 'Struktur Organisasi', 'model' => OrgNode::class, 'read_only' => false,
                'search' => ['name'], 'title_field' => 'name', 'subtitle_field' => 'sort',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama Node', 'type' => 'text', 'required' => true],
                    ['name' => 'parent_id', 'label' => 'Parent', 'type' => 'select', 'options' => $this->orgOptions()],
                    ['name' => 'user_id', 'label' => 'Pengguna', 'type' => 'select', 'options' => $this->userOptions()],
                    ['name' => 'sort', 'label' => 'Urutan', 'type' => 'number'],
                ],
            ],
            'reports' => ['title' => 'Laporan', 'read_only' => true, 'title_field' => 'title', 'subtitle_field' => 'value', 'meta_field' => 'note'],
            'settings' => [
                'title' => 'Pengaturan', 'model' => Setting::class, 'read_only' => false,
                'search' => ['key'], 'title_field' => 'key', 'subtitle_field' => 'value',
                'fields' => [
                    ['name' => 'key', 'label' => 'Key', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'value', 'label' => 'Value', 'type' => 'textarea'],
                    ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'options' => [
                        ['value' => 'string', 'label' => 'String'],
                        ['value' => 'integer', 'label' => 'Integer'],
                        ['value' => 'boolean', 'label' => 'Boolean'],
                        ['value' => 'json', 'label' => 'JSON'],
                    ]],
                ],
            ],
            'access' => ['title' => 'Hak Akses', 'read_only' => true, 'title_field' => 'code', 'subtitle_field' => 'action', 'meta_field' => 'model'],
            'manage-data' => ['title' => 'Manage Data', 'read_only' => true, 'title_field' => 'title', 'subtitle_field' => 'value', 'meta_field' => 'note'],
        ];

        abort_unless(isset($configs[$resource]), 404);

        return $configs[$resource];
    }

    private function writableConfig(string $resource): array
    {
        $config = $this->config($resource, request());

        abort_if($config['read_only'], 404);

        return $config;
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->isAdmin() || $user->canManageUsers());
    }

    public function verifyAttendance(Request $request, int $id)
    {
        abort_unless($this->canManage($request), 403);

        $data = $request->validate([
            'status' => ['required', 'in:valid,invalid,pending'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->update([
            'status' => $data['status'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['status' => true, 'message' => 'Verifikasi presensi disimpan.']);
    }

    private function readOnlyQuery(string $resource, string $search)
    {
        return match ($resource) {
            'attendances' => DB::table('attendances as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->select('a.*', 'u.name as user_name')->when($search !== '', fn ($q) => $q->where('u.name', 'like', "%{$search}%"))->orderByDesc('a.date'),
            'attendance-verifications' => DB::table('attendances as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->select('a.*', 'u.name as user_name')->where('a.status', 'pending')->when($search !== '', fn ($q) => $q->where('u.name', 'like', "%{$search}%"))->orderByDesc('a.date'),
            'leaves' => DB::table('leaves as l')->leftJoin('users as u', 'u.id', '=', 'l.user_id')->leftJoin('leave_types as t', 't.id', '=', 'l.leave_type_id')
                ->select('l.*', 'u.name as user_name', 't.name as leave_type_name')->when($search !== '', fn ($q) => $q->where('u.name', 'like', "%{$search}%"))->orderByDesc('l.start_date'),
            'audit-logs' => DB::table('audit_logs')->when($search !== '', fn ($q) => $q->where('action', 'like', "%{$search}%"))->orderByDesc('id'),
            'video-tutorials' => DB::table('internal_video_tutorials')->whereNull('deleted_at')->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))->orderBy('sort_order'),
            'dokumentasi' => DB::table('internal_dokumentasi')->whereNull('deleted_at')->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))->orderBy('sort_order'),
            'pengumuman' => DB::table('internal_announcements')->whereNull('deleted_at')->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))->orderByDesc('created_at'),
            'roles' => DB::table('roles')->when(Schema::hasColumn('roles', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orderBy('name'),
            'servers' => DB::table('internal_credentials')->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orderBy('name'),
            'email-logs' => DB::table('internal_email_logs')->when($search !== '', fn ($q) => $q->where('subject', 'like', "%{$search}%"))->orderByDesc('id'),
            'organization' => DB::table('internal_org_structure')->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orderBy('sort'),
            'reports' => DB::table('attendances')->selectRaw("'Total Presensi' as title, count(*) as value, '' as note")->when($search !== '', fn ($q) => $q->whereRaw('1=1')),
            'settings' => DB::table('settings')->when($search !== '', fn ($q) => $q->where('key', 'like', "%{$search}%"))->orderBy('key'),
            'access' => DB::table('permissions')->when($search !== '', fn ($q) => $q->where('code', 'like', "%{$search}%"))->orderBy('code'),
            'manage-data' => DB::query()->fromSub(
                DB::table('internal_projects')->selectRaw("'Projects' as title, count(*) as value, '' as note")
                    ->unionAll(DB::table('internal_tasks')->selectRaw("'Tasks' as title, count(*) as value, '' as note"))
                    ->unionAll(DB::table('internal_activity_logs')->selectRaw("'Activity Logs' as title, count(*) as value, '' as note")),
                'stats'
            ),
            default => abort(404),
        };
    }

    private function validated(Request $request, array $config, $model = null): array
    {
        $rules = [];
        foreach ($config['fields'] as $field) {
            if (($field['create_only'] ?? false) && $model) {
                continue;
            }
            $name = $field['name'];
            $rule = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $rule[] = match ($field['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'switch' => 'boolean',
                default => 'string',
            };
            if (($field['unique'] ?? false) && ! empty($config['model'])) {
                $table = (new $config['model'])->getTable();
                $rule[] = Rule::unique($table, $name)->ignore($model?->id);
            }
            $rules[$name] = $rule;
        }

        return $request->validate($rules);
    }

    private function transform(array $data, array $config, Request $request, bool $creating, string $resource): array
    {
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            if ($field['type'] === 'switch') {
                $data[$name] = $request->boolean($name);
            }
            if ($field['type'] === 'password') {
                if (! empty($data[$name])) {
                    $data[$name] = Hash::make($data[$name]);
                } else {
                    unset($data[$name]);
                }
            }
            if ($name === 'options' && isset($data[$name]) && is_string($data[$name])) {
                $data[$name] = array_values(array_filter(array_map('trim', explode(',', $data[$name]))));
            }
            if ($resource === 'settings' && $name === 'value' && ($data[$name] ?? null) === '********') {
                unset($data[$name]);
            }
            if ($resource === 'servers' && $name === 'password') {
                if (! empty($data[$name])) {
                    $data[$name] = Crypt::encryptString($data[$name]);
                } else {
                    unset($data[$name]);
                }
            }
        }

        return $data;
    }

    private function orgOptions(): array
    {
        return OrgNode::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function userOptions(): array
    {
        return User::orderBy('name')->limit(200)->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function roleOptions(): array
    {
        return Role::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function departmentOptions(): array
    {
        return Department::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function unitOptions(): array
    {
        return Unit::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function positionOptions(): array
    {
        return Position::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }

    private function branchOptions(): array
    {
        return Branch::orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->all();
    }
}
