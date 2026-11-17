<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan</h1>
            <p class="text-sm text-slate-500">Identitas aplikasi, absensi, pengiriman email, project, dan aktivitas.</p>
        </div>
        <div class="flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
            <span class="h-2 w-2 animate-pulse rounded-full bg-blue-600"></span>
            <span>Perubahan tersimpan otomatis via Livewire</span>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
        @foreach ([
            'identity' => 'Identitas Aplikasi',
            'attendance' => 'Absensi',
            'email' => 'Pengiriman Email',
            'mobile' => 'Aplikasi Mobile',
            'project' => 'Manajemen Proyek',
            'activity' => 'Aktivitas',
        ] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $tab === $key ? 'bg-blue-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

        @if ($tab === 'identity')
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <h2 class="font-bold text-slate-800">Identitas Aplikasi</h2>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Nama Aplikasi</label>
                        <input type="text" wire:model="app_name" placeholder="Contoh: RST Internal App"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        @error('app_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="text-xs text-slate-500">Nama ini tampil di sidebar, judul halaman, dan halaman login.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Nama Instansi / Perusahaan</label>
                        <input type="text" wire:model="company_name"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        @error('company_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <x-ia.tiptap label="Alamat / Identitas Lengkap" wire:model="company_address" :value="$company_address"
                                 placeholder="Tulis alamat instansi..." />

                    <x-ia.select label="Timezone" wire:model="timezone">
                        @foreach ($timezones as $zone)
                            <option value="{{ $zone }}">{{ $zone }}</option>
                        @endforeach
                    </x-ia.select>
                    @error('timezone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Logo Aplikasi</h2>
                    <x-ia.file-upload
                        label="Upload Logo"
                        wire:model="company_logo"
                        accept="image/png,image/jpeg,image/webp"
                        :preview="$company_logo ? $company_logo->temporaryUrl() : ($existing_logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($existing_logo) : null)"
                        hint="PNG/JPG/WEBP maksimal 2MB."
                    />
                    @error('company_logo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($tab === 'attendance')
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Jam Kerja & Cuti</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Jam Masuk</label>
                            <input type="time" wire:model="work_start_time" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                            @error('work_start_time') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Jam Pulang</label>
                            <input type="time" wire:model="work_end_time" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                            @error('work_end_time') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Toleransi Terlambat (menit)</label>
                            <input type="number" wire:model="late_tolerance" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                            @error('late_tolerance') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Kuota Cuti Tahunan</label>
                            <input type="number" wire:model="annual_leave_quota" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                            @error('annual_leave_quota') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">GPS & Face Recognition</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Latitude</label>
                            <input type="number" step="any" wire:model="latitude" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Longitude</label>
                            <input type="number" step="any" wire:model="longitude" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">Radius Kantor (meter)</label>
                            <input type="number" wire:model="radius" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                    </div>

                    <x-ia.switch label="Face Recognition" description="Wajibkan verifikasi wajah saat presensi" wire:model="face_recognition_enabled" />

                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Threshold Face Recognition (%)</label>
                        <input type="number" wire:model="face_recognition_threshold" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                </div>
            </div>
        @endif

        @if ($tab === 'email')
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <h2 class="font-bold text-slate-800">Konfigurasi Email</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ia.select label="Mailer" wire:model="mail_mailer">
                            <option value="smtp">SMTP</option>
                            <option value="log">Log</option>
                            <option value="sendmail">Sendmail</option>
                        </x-ia.select>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Host</label>
                            <input type="text" wire:model="mail_host" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Port</label>
                            <input type="number" wire:model="mail_port" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <x-ia.select label="Encryption" wire:model="mail_encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </x-ia.select>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Username</label>
                            <input type="text" wire:model="mail_username" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Password</label>
                            <input type="password" wire:model="mail_password" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Email Pengirim</label>
                            <input type="email" wire:model="mail_from_address" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Nama Pengirim</label>
                            <input type="text" wire:model="mail_from_name" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Tes Pengiriman</h2>
                    <p class="text-xs text-slate-500">Kirim email uji untuk memastikan konfigurasi berjalan.</p>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Email Tujuan</label>
                        <input type="email" wire:model="test_email_to" placeholder="nama@perusahaan.com" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        @error('test_email_to') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="sendTestEmail" wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                        Kirim Email Uji
                    </button>
                </div>
            </div>
        @endif

        @if ($tab === 'mobile')
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Link Download Aplikasi</h2>
                    <p class="text-xs text-slate-500">Link ini akan tampil di halaman login sebagai tombol download aplikasi.</p>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Google Play Store (Android)</label>
                        <input type="url" wire:model="play_store_url" placeholder="https://play.google.com/store/apps/details?id=..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        @error('play_store_url') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Apple App Store (iOS)</label>
                        <input type="url" wire:model="app_store_url" placeholder="https://apps.apple.com/app/id..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        @error('app_store_url') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Preview Tombol</h2>
                    <p class="text-xs text-slate-500">Tampilan tombol pada form login.</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <a href="{{ $play_store_url ?: '#' }}" target="_blank"
                           class="flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white {{ $play_store_url ? '' : 'opacity-40 pointer-events-none' }}">
                            Google Play
                        </a>
                        <a href="{{ $app_store_url ?: '#' }}" target="_blank"
                           class="flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white {{ $app_store_url ? '' : 'opacity-40 pointer-events-none' }}">
                            App Store
                        </a>
                    </div>
                    <p class="text-[11px] text-slate-400">Kosongkan jika aplikasi belum tersedia di store.</p>
                </div>
            </div>
        @endif

        @if ($tab === 'project')
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Default Project</h2>
                    <x-ia.select label="Status Default" wire:model="project_default_status">
                        <option value="planning">Planning</option>
                        <option value="running">Running</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </x-ia.select>
                    <x-ia.select label="Prioritas Default" wire:model="project_default_priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </x-ia.select>
                    <x-ia.datepicker label="Tanggal Review Project" wire:model="project_review_date" hint="Tampilan d-m-Y, disimpan Y-m-d." />
                </div>

                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-slate-800">Manajer Project Default</h2>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Cari pengguna..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                        @if (! empty($userOptions))
                            <div class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                @foreach ($userOptions as $user)
                                    <button type="button" wire:click="selectManager({{ $user['id'] }}, @js($user['name']))"
                                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">
                                        {{ $user['name'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if ($project_manager_id)
                        <p class="text-xs text-slate-500">
                            Dipilih: <span class="font-semibold text-slate-800">{{ $selectedManagerName ?? 'User #'.$project_manager_id }}</span>
                        </p>
                    @endif
                    <p class="text-xs text-slate-500">Pencarian pengguna memakai AJAX Livewire (tanpa reload halaman).</p>
                </div>
            </div>
        @endif

        @if ($tab === 'activity')
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Default Aktivitas</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ia.select label="Status Default" wire:model="activity_default_status">
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </x-ia.select>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Reminder (hari sebelum)</label>
                        <input type="number" wire:model="activity_reminder_days" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                </div>
            </div>
        @endif

    <div class="sticky bottom-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            <span>Perubahan belum disimpan</span>
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            Simpan Pengaturan
        </button>
    </div>
</div>
