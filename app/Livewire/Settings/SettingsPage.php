<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\OfficeLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.livewire-app')]
class SettingsPage extends Component
{
    use WithFileUploads;

    public string $tab = 'identity';

    public string $app_name = '';
    public string $company_name = '';
    public string $company_address = '';
    public ?string $existing_logo = null;
    public $company_logo = null;
    public string $timezone = 'Asia/Jakarta';

    public string $work_start_time = '08:00';
    public string $work_end_time = '17:00';
    public int $late_tolerance = 15;
    public int $annual_leave_quota = 12;
    public bool $face_recognition_enabled = false;
    public int $face_recognition_threshold = 80;
    public float $latitude = -6.2;
    public float $longitude = 106.816666;
    public int $radius = 100;

    public string $mail_mailer = 'smtp';
    public string $mail_host = '';
    public int $mail_port = 587;
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = 'tls';
    public string $mail_from_address = '';
    public string $mail_from_name = '';
    public string $test_email_to = '';

    public string $project_default_status = 'planning';
    public string $project_default_priority = 'medium';
    public ?int $project_manager_id = null;
    public string $project_review_date = '';
    public string $userSearch = '';
    public array $userOptions = [];
    public ?string $selectedManagerName = null;

    public string $play_store_url = '';
    public string $app_store_url = '';

    public string $activity_default_status = 'planned';
    public int $activity_reminder_days = 1;

    public function mount(): void
    {
        $settings = Setting::all()->keyBy('key');
        $value = fn (string $key, $default = null) => $settings[$key]->value ?? $default;

        $this->app_name = (string) $value('app_name', config('app.name'));
        $this->company_name = (string) $value('company_name', config('app.name'));
        $this->company_address = (string) $value('company_address', '');
        $this->existing_logo = $value('company_logo');
        $this->timezone = (string) $value('timezone', config('app.timezone', 'Asia/Jakarta'));

        $this->work_start_time = (string) $value('work_start_time', '08:00');
        $this->work_end_time = (string) $value('work_end_time', '17:00');
        $this->late_tolerance = (int) $value('late_tolerance', 15);
        $this->annual_leave_quota = (int) $value('annual_leave_quota', 12);
        $this->face_recognition_enabled = filter_var($value('face_recognition_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $this->face_recognition_threshold = (int) $value('face_recognition_threshold', 80);

        $office = OfficeLocation::where('is_active', true)->first();
        if ($office) {
            $this->latitude = (float) $office->latitude;
            $this->longitude = (float) $office->longitude;
            $this->radius = (int) $office->radius;
        }

        $mailer = (string) $value('mail_mailer', config('mail.default', 'smtp'));
        $this->mail_mailer = in_array($mailer, ['smtp', 'log', 'sendmail'], true) ? $mailer : 'smtp';
        $this->mail_host = (string) $value('mail_host', config('mail.mailers.smtp.host', ''));
        $this->mail_port = (int) $value('mail_port', config('mail.mailers.smtp.port', 587));
        $this->mail_username = (string) $value('mail_username', config('mail.mailers.smtp.username', ''));
        $this->mail_encryption = (string) $value('mail_encryption', 'tls');
        $this->mail_from_address = (string) $value('mail_from_address', config('mail.from.address', ''));
        $this->mail_from_name = (string) $value('mail_from_name', config('mail.from.name', config('app.name')));

        $this->project_default_status = (string) $value('project_default_status', 'planning');
        $this->project_default_priority = (string) $value('project_default_priority', 'medium');
        $this->project_manager_id = $value('project_manager_id') ? (int) $value('project_manager_id') : null;
        $this->project_review_date = (string) $value('project_review_date', '');
        $this->play_store_url = (string) $value('play_store_url', '');
        $this->app_store_url = (string) $value('app_store_url', '');
        $this->activity_default_status = (string) $value('activity_default_status', 'planned');
        $this->activity_reminder_days = (int) $value('activity_reminder_days', 1);

        $this->loadUsers();
    }

    public function updatedUserSearch(): void
    {
        $this->loadUsers();
    }

    public function selectManager(int $id, string $name): void
    {
        $this->project_manager_id = $id;
        $this->selectedManagerName = $name;
        $this->userSearch = '';
        $this->userOptions = [];
    }

    public function save(): void
    {
        $data = $this->validate();

        try {
            $stringKeys = [
                'app_name', 'company_name', 'company_address', 'timezone',
                'work_start_time', 'work_end_time',
                'mail_mailer', 'mail_host', 'mail_encryption',
                'mail_from_address', 'mail_from_name',
                'project_default_status', 'project_default_priority', 'project_review_date',
                'play_store_url', 'app_store_url',
                'activity_default_status',
            ];

            foreach ($stringKeys as $key) {
                Setting::set($key, $data[$key] ?? null);
            }

            foreach (['late_tolerance', 'annual_leave_quota', 'face_recognition_threshold', 'mail_port', 'activity_reminder_days'] as $key) {
                Setting::set($key, $data[$key] ?? 0, 'integer');
            }

            Setting::set('face_recognition_enabled', $data['face_recognition_enabled'] ?? false, 'boolean');
            Setting::set('project_manager_id', $data['project_manager_id'] ?? null, 'integer');

            if (! empty($data['mail_password'])) {
                Setting::set('mail_password', $data['mail_password']);
            }

            if ($this->company_logo) {
                $old = Setting::get('company_logo');
                $path = $this->company_logo->store('logos', 'public');

                if ($old) {
                    Storage::disk('public')->delete($old);
                }

                Setting::set('company_logo', $path);
                $this->existing_logo = $path;
                $this->company_logo = null;
            }

            $office = OfficeLocation::where('is_active', true)->first()
                ?? new OfficeLocation(['name' => $this->company_name, 'is_active' => true]);

            $office->fill([
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'radius' => $this->radius,
            ])->save();

            AuditLog::log('settings_updated', 'Setting', null);

            $this->dispatch('notify', type: 'success', title: 'Berhasil', message: 'Pengaturan berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', title: 'Gagal', message: 'Gagal menyimpan pengaturan: '.$e->getMessage());
        }
    }

    public function sendTestEmail(): void
    {
        $this->validateOnly('test_email_to', ['test_email_to' => 'required|email']);

        try {
            config([
                'mail.default' => $this->mail_mailer,
                'mail.mailers.smtp.host' => $this->mail_host ?: config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $this->mail_port ?: config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $this->mail_username ?: config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password' => $this->mail_password ?: Setting::get('mail_password', config('mail.mailers.smtp.password')),
                'mail.mailers.smtp.encryption' => $this->mail_encryption ?: null,
                'mail.from.address' => $this->mail_from_address ?: config('mail.from.address'),
                'mail.from.name' => $this->mail_from_name ?: config('mail.from.name'),
            ]);

            Mail::raw('Ini adalah email uji dari RST Internal App.', function ($message) {
                $message->to($this->test_email_to)->subject('Test Email RST Internal App');
            });

            $this->dispatch('notify', type: 'success', title: 'Berhasil', message: 'Email uji berhasil dikirim ke '.$this->test_email_to.'.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', title: 'Gagal', message: 'Gagal mengirim email: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.settings.page', [
            'timezones' => [
                'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC',
            ],
        ]);
    }

    protected function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'timezone' => ['required', 'timezone'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i'],
            'late_tolerance' => ['required', 'integer', 'min:0', 'max:180'],
            'annual_leave_quota' => ['required', 'integer', 'min:1', 'max:365'],
            'face_recognition_enabled' => ['boolean'],
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
        ];
    }

    private function loadUsers(): void
    {
        $this->userOptions = User::query()
            ->when($this->userSearch !== '', fn ($query) => $query->where('name', 'like', '%'.$this->userSearch.'%'))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();

        if ($this->project_manager_id && ! $this->selectedManagerName) {
            $this->selectedManagerName = User::whereKey($this->project_manager_id)->value('name');
        }
    }
}
