<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\OfficeLocation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $office = OfficeLocation::where('is_active', true)->first();

        $value = fn (string $key, $default = null) => $settings[$key]->value ?? $default;

        $managerId = $value('project_manager_id') ? (int) $value('project_manager_id') : null;

        $weeklyWorkModes = $value('weekly_work_modes', []);
        if (is_string($weeklyWorkModes)) {
            $weeklyWorkModes = json_decode($weeklyWorkModes, true) ?: [];
        }

        $exemptRoles = $value('outside_zone_exempt_roles', ['General Manager', 'sekretaris']);
        if (is_string($exemptRoles)) {
            $exemptRoles = json_decode($exemptRoles, true) ?: [];
        }

        $roles = Role::query()->whereNull('deleted_at')->orderBy('name')->get(['name', 'display_name']);

        $initial = [
            'app_name' => (string) $value('app_name', config('app.name')),
            'company_name' => (string) $value('company_name', config('app.name')),
            'company_address' => (string) $value('company_address', ''),
            'company_logo_url' => ($logo = $value('company_logo')) ? Storage::disk('public')->url($logo) : null,
            'timezone' => (string) $value('timezone', config('app.timezone', 'Asia/Jakarta')),
            'work_start_time' => (string) $value('work_start_time', '08:00'),
            'work_end_time' => (string) $value('work_end_time', '17:00'),
            'late_tolerance' => (int) $value('late_tolerance', 15),
            'annual_leave_quota' => (int) $value('annual_leave_quota', 12),
            'weekly_holidays' => Holiday::weeklyDays(),
            'face_recognition_enabled' => filter_var($value('face_recognition_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'mobile_face_recognition_enabled' => filter_var($value('mobile_face_recognition_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'face_recognition_threshold' => (int) $value('face_recognition_threshold', 80),
            'latitude' => (float) ($office->latitude ?? -6.2),
            'longitude' => (float) ($office->longitude ?? 106.816666),
            'radius' => (int) ($office->radius ?? 100),
            'mail_mailer' => (string) $value('mail_mailer', 'smtp'),
            'mail_host' => (string) $value('mail_host', config('mail.mailers.smtp.host', '')),
            'mail_port' => (int) $value('mail_port', config('mail.mailers.smtp.port', 587)),
            'mail_username' => (string) $value('mail_username', config('mail.mailers.smtp.username', '')),
            'mail_encryption' => (string) $value('mail_encryption', 'tls'),
            'mail_from_address' => (string) $value('mail_from_address', config('mail.from.address', '')),
            'mail_from_name' => (string) $value('mail_from_name', config('mail.from.name', config('app.name'))),
            'project_default_status' => (string) $value('project_default_status', 'planning'),
            'project_default_priority' => (string) $value('project_default_priority', 'medium'),
            'project_manager_id' => $managerId,
            'project_manager_name' => $managerId ? User::whereKey($managerId)->value('name') : null,
            'project_review_date' => (string) $value('project_review_date', ''),
            'play_store_url' => (string) $value('play_store_url', ''),
            'app_store_url' => (string) $value('app_store_url', ''),
            'weekly_work_modes' => is_array($weeklyWorkModes) ? $weeklyWorkModes : [],
            'outside_zone_exempt_roles' => array_values((array) $exemptRoles),
            'activity_default_status' => (string) $value('activity_default_status', 'planned'),
            'activity_reminder_days' => (int) $value('activity_reminder_days', 1),
            'activity_report_enabled' => filter_var($value('activity_report_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'activity_report_frequency' => (string) $value('activity_report_frequency', 'weekly'),
            'activity_report_day' => (int) $value('activity_report_day', 5),
            'activity_report_time' => (string) $value('activity_report_time', '18:00'),
        ];

        return view('admin.settings.index', compact('settings', 'office', 'initial', 'roles'));
    }

    public function update(Request $request)
    {
        $exemptRoles = $request->input('outside_zone_exempt_roles', []);
        if (is_string($exemptRoles)) {
            $decoded = json_decode($exemptRoles, true);
            $exemptRoles = is_array($decoded) ? $decoded : ($exemptRoles !== '' ? [$exemptRoles] : []);
        }
        $request->merge(['outside_zone_exempt_roles' => array_values((array) $exemptRoles)]);

        $weeklyWorkModes = $request->input('weekly_work_modes', []);
        if (is_string($weeklyWorkModes)) {
            $decoded = json_decode($weeklyWorkModes, true);
            $weeklyWorkModes = is_array($decoded) ? $decoded : [];
        }
        $request->merge(['weekly_work_modes' => (array) $weeklyWorkModes]);

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'timezone' => ['required', 'timezone'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i'],
            'late_tolerance' => ['required', 'integer', 'min:0', 'max:180'],
            'annual_leave_quota' => ['required', 'integer', 'min:1', 'max:365'],
            'weekly_holidays' => ['nullable', 'array'],
            'weekly_holidays.*' => ['integer', 'between:0,6'],
            'weekly_work_modes' => ['nullable', 'array'],
            'weekly_work_modes.*' => ['nullable', 'in:WFO,WFH,WFA'],
            'outside_zone_exempt_roles' => ['nullable', 'array'],
            'outside_zone_exempt_roles.*' => ['string', 'max:100'],
            'face_recognition_enabled' => ['boolean'],
            'mobile_face_recognition_enabled' => ['boolean'],
            'face_recognition_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1'],
            'mail_mailer' => ['required', 'in:smtp,log,sendmail'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'project_default_status' => ['required', 'in:planning,running,on_hold,completed,cancelled'],
            'project_default_priority' => ['required', 'in:low,medium,high,urgent'],
            'project_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_review_date' => ['nullable', 'date_format:Y-m-d'],
            'play_store_url' => ['nullable', 'url', 'max:500'],
            'app_store_url' => ['nullable', 'url', 'max:500'],
            'activity_default_status' => ['required', 'in:planned,ongoing,completed,cancelled'],
            'activity_reminder_days' => ['required', 'integer', 'min:0', 'max:30'],
            'activity_report_enabled' => ['boolean'],
            'activity_report_frequency' => ['required', 'in:daily,weekly'],
            'activity_report_day' => ['required', 'integer', 'between:1,7'],
            'activity_report_time' => ['required', 'date_format:H:i'],
        ]);

        $stringKeys = [
            'app_name', 'company_name', 'company_address', 'timezone',
            'work_start_time', 'work_end_time',
            'mail_mailer', 'mail_host', 'mail_encryption', 'mail_from_address', 'mail_from_name',
            'project_default_status', 'project_default_priority', 'project_review_date',
            'play_store_url', 'app_store_url', 'activity_default_status',
        ];

        foreach ($stringKeys as $key) {
            Setting::set($key, $data[$key] ?? null);
        }

        foreach (['late_tolerance', 'annual_leave_quota', 'face_recognition_threshold', 'mail_port', 'activity_reminder_days', 'activity_report_day'] as $key) {
            Setting::set($key, $data[$key] ?? 0, 'integer');
        }

        Setting::set('activity_report_enabled', $request->boolean('activity_report_enabled'), 'boolean');
        Setting::set('activity_report_frequency', $data['activity_report_frequency'] ?? 'weekly');
        Setting::set('activity_report_time', $data['activity_report_time'] ?? '18:00');

        $weeklyHolidays = collect($data['weekly_holidays'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        Setting::set('weekly_holidays', $weeklyHolidays, 'json');

        $weeklyWorkModes = collect($data['weekly_work_modes'] ?? [])
            ->mapWithKeys(function ($mode, $day) {
                $day = (int) $day;
                $mode = strtoupper((string) $mode);

                return $day >= 1 && $day <= 7 && in_array($mode, ['WFO', 'WFH', 'WFA'], true)
                    ? [$day => $mode]
                    : [];
            })
            ->all();

        Setting::set('weekly_work_modes', $weeklyWorkModes, 'json');
        Setting::set('outside_zone_exempt_roles', array_values((array) ($data['outside_zone_exempt_roles'] ?? [])), 'json');

        Setting::set('face_recognition_enabled', $request->boolean('face_recognition_enabled'), 'boolean');
        Setting::set('mobile_face_recognition_enabled', $request->boolean('mobile_face_recognition_enabled'), 'boolean');
        Setting::set('project_manager_id', $data['project_manager_id'] ?? null, 'integer');

        if (! empty($data['mail_password'])) {
            Setting::set('mail_password', $data['mail_password']);
        }

        $logoUrl = Setting::get('company_logo')
            ? Storage::disk('public')->url(Setting::get('company_logo'))
            : null;

        if ($request->hasFile('company_logo')) {
            $oldLogo = Setting::get('company_logo');
            $path = $request->file('company_logo')->store('logos', 'public');

            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            Setting::set('company_logo', $path);
            $logoUrl = Storage::disk('public')->url($path);
        }

        $office = OfficeLocation::where('is_active', true)->first()
            ?? new OfficeLocation(['name' => $data['company_name'], 'is_active' => true]);

        $office->fill([
            'name' => $data['company_name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius' => $data['radius'],
        ])->save();

        AuditLog::log('settings_updated', 'Setting', null);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Pengaturan berhasil disimpan.',
                'logo_url' => $logoUrl,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', __('ui.settings_updated'));
    }

    public function runScheduler(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        try {
            Artisan::call('schedule:run');
            $output = trim(Artisan::output());

            return response()->json([
                'status' => true,
                'message' => $output !== ''
                    ? 'Scheduler dijalankan. '.$output
                    : 'Scheduler dijalankan. Tidak ada task yang jatuh tempo saat ini.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => false,
                'message' => 'Gagal menjalankan scheduler: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function testEmail(Request $request)
    {
        $data = $request->validate([
            'test_email_to' => ['required', 'email'],
            'mail_mailer' => ['nullable', 'in:smtp,log,sendmail'],
            'mail_host' => ['nullable', 'string'],
            'mail_port' => ['nullable', 'integer'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email'],
            'mail_from_name' => ['nullable', 'string'],
        ]);

        try {
            config([
                'mail.default' => $data['mail_mailer'] ?: Setting::get('mail_mailer', config('mail.default')),
                'mail.mailers.smtp.host' => $data['mail_host'] ?: Setting::get('mail_host', config('mail.mailers.smtp.host')),
                'mail.mailers.smtp.port' => $data['mail_port'] ?: Setting::get('mail_port', config('mail.mailers.smtp.port')),
                'mail.mailers.smtp.username' => $data['mail_username'] ?: Setting::get('mail_username', config('mail.mailers.smtp.username')),
                'mail.mailers.smtp.password' => $data['mail_password'] ?: Setting::get('mail_password', config('mail.mailers.smtp.password')),
                'mail.mailers.smtp.encryption' => ($data['mail_encryption'] ?? Setting::get('mail_encryption')) ?: null,
                'mail.from.address' => $data['mail_from_address'] ?: Setting::get('mail_from_address', config('mail.from.address')),
                'mail.from.name' => $data['mail_from_name'] ?: Setting::get('mail_from_name', config('mail.from.name')),
            ]);

            Mail::raw('Ini adalah email uji dari '.Setting::get('app_name', config('app.name')).'.', function ($message) use ($data) {
                $message->to($data['test_email_to'])->subject('Test Email '.Setting::get('app_name', config('app.name')));
            });

            return response()->json(['status' => true, 'message' => 'Email uji berhasil dikirim ke '.$data['test_email_to'].'.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'Gagal mengirim email: '.$e->getMessage()], 422);
        }
    }

    public function users(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name]);

        return response()->json(['users' => $users]);
    }
}
