<x-app-layout>
    <x-slot name="header">Pengaturan</x-slot>

    <div x-data="settingsApp({{ \Illuminate\Support\Js::from($initial) }})" class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Pengaturan</h1>
                <p class="text-sm text-slate-500">Identitas aplikasi, absensi, pengiriman email, project, aktivitas, dan aplikasi mobile.</p>
            </div>
            <div class="flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                <span class="h-2 w-2 animate-pulse rounded-full bg-blue-600"></span>
                <span>Perubahan disimpan via AJAX</span>
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
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-blue-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100'"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- IDENTITAS --}}
        <div x-show="tab === 'identity'" x-cloak class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-bold text-slate-800">Identitas Aplikasi</h2>

                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Nama Aplikasi</label>
                    <input type="text" x-model="form.app_name" placeholder="Contoh: RST Internal App"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <p class="text-xs text-slate-500">Tampil di sidebar, judul halaman, dan login.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Nama Instansi / Perusahaan</label>
                    <input type="text" x-model="form.company_name"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                </div>

                <x-ia.tiptap label="Alamat / Identitas Lengkap" x-model="form.company_address" :value="$initial['company_address']"
                             placeholder="Tulis alamat instansi..." />

                <x-ia.select label="Timezone" x-model="form.timezone">
                    @foreach (['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'] as $zone)
                        <option value="{{ $zone }}">{{ $zone }}</option>
                    @endforeach
                </x-ia.select>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Logo Aplikasi</h2>

                <div x-show="previewLogo" class="flex items-start gap-3">
                    <img :src="previewLogo" alt="Logo" class="h-20 w-20 rounded-xl border border-slate-200 object-contain p-1">
                    <div class="text-xs text-slate-500">
                        <p class="font-semibold text-slate-700">Preview logo</p>
                        <p>Logo tampil di sidebar dan halaman login.</p>
                    </div>
                </div>

                <input type="file" x-ref="logo" @change="onLogoChange" accept="image/png,image/jpeg,image/webp"
                       class="block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-700">

                <div x-show="uploading" x-cloak class="space-y-1">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full bg-blue-600 transition-all" :style="`width: ${uploadProgress}%`"></div>
                    </div>
                    <p class="text-[11px] font-medium text-slate-500">Upload <span x-text="uploadProgress"></span>%</p>
                </div>

                <p class="text-xs text-slate-500">PNG/JPG/WEBP maksimal 2MB.</p>
            </div>
        </div>

        {{-- ABSENSI --}}
        <div x-show="tab === 'attendance'" x-cloak class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Jam Kerja & Cuti</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Jam Masuk</label>
                        <input type="time" x-model="form.work_start_time" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Jam Pulang</label>
                        <input type="time" x-model="form.work_end_time" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Toleransi Terlambat (menit)</label>
                        <input type="number" x-model="form.late_tolerance" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Kuota Cuti Tahunan</label>
                        <input type="number" x-model="form.annual_leave_quota" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">GPS & Face Recognition</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Latitude</label>
                        <input type="number" step="any" x-model="form.latitude" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Longitude</label>
                        <input type="number" step="any" x-model="form.longitude" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Radius Kantor (meter)</label>
                        <input type="number" x-model="form.radius" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                </div>

                <x-ia.switch label="Face Recognition" description="Wajibkan verifikasi wajah saat presensi" x-model="form.face_recognition_enabled" />
                <x-ia.switch label="Face Recognition Mobile" description="Aktifkan jika mobile sudah bisa menghitung skor wajah" x-model="form.mobile_face_recognition_enabled" />

                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Threshold Face Recognition (%)</label>
                    <input type="number" x-model="form.face_recognition_threshold" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                </div>
            </div>
        </div>

        {{-- EMAIL --}}
        <div x-show="tab === 'email'" x-cloak class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-bold text-slate-800">Konfigurasi Email</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ia.select label="Mailer" x-model="form.mail_mailer">
                        <option value="smtp">SMTP</option>
                        <option value="log">Log</option>
                        <option value="sendmail">Sendmail</option>
                    </x-ia.select>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Host</label>
                        <input type="text" x-model="form.mail_host" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Port</label>
                        <input type="number" x-model="form.mail_port" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <x-ia.select label="Encryption" x-model="form.mail_encryption">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="none">None</option>
                    </x-ia.select>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Username</label>
                        <input type="text" x-model="form.mail_username" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Password</label>
                        <input type="password" x-model="form.mail_password" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Email Pengirim</label>
                        <input type="email" x-model="form.mail_from_address" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Nama Pengirim</label>
                        <input type="text" x-model="form.mail_from_name" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Tes Pengiriman</h2>
                <p class="text-xs text-slate-500">Kirim email uji untuk memastikan konfigurasi berjalan.</p>
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Email Tujuan</label>
                    <input type="email" x-model="form.test_email_to" placeholder="nama@perusahaan.com" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                </div>
                <button type="button" @click="sendTestEmail" :disabled="testingEmail" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span x-text="testingEmail ? 'Mengirim...' : 'Kirim Email Uji'"></span>
                </button>
            </div>
        </div>

        {{-- MOBILE --}}
        <div x-show="tab === 'mobile'" x-cloak class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Link Download Aplikasi</h2>
                <p class="text-xs text-slate-500">Tampil di halaman login sebagai tombol download.</p>
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Google Play Store (Android)</label>
                    <input type="url" x-model="form.play_store_url" placeholder="https://play.google.com/store/apps/details?id=..." class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Apple App Store (iOS)</label>
                    <input type="url" x-model="form.app_store_url" placeholder="https://apps.apple.com/app/id..." class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                </div>
            </div>

            <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Preview Tombol</h2>
                <p class="text-xs text-slate-500">Tampilan tombol pada form login.</p>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <a :href="form.play_store_url || '#'" target="_blank"
                       :class="form.play_store_url ? '' : 'pointer-events-none opacity-40'"
                       class="flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white">
                        Google Play
                    </a>
                    <a :href="form.app_store_url || '#'" target="_blank"
                       :class="form.app_store_url ? '' : 'pointer-events-none opacity-40'"
                       class="flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white">
                        App Store
                    </a>
                </div>
            </div>
        </div>

        {{-- PROJECT --}}
        <div x-show="tab === 'project'" x-cloak class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Default Project</h2>
                <x-ia.select label="Status Default" x-model="form.project_default_status">
                    <option value="planning">Planning</option>
                    <option value="running">Running</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </x-ia.select>
                <x-ia.select label="Prioritas Default" x-model="form.project_default_priority">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </x-ia.select>
                <x-ia.datepicker label="Tanggal Review Project" x-model="form.project_review_date" hint="Tampilan d-m-Y, disimpan Y-m-d." />
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-800">Manajer Project Default</h2>
                <div class="relative">
                    <input type="text" x-model="userSearch" @input.debounce.300ms="searchUsers" placeholder="Cari pengguna..."
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                    <div x-show="users.length" x-cloak class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <template x-for="user in users" :key="user.id">
                            <button type="button" @click="pickManager(user)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" x-text="user.name"></button>
                        </template>
                    </div>
                </div>
                <p x-show="selectedManagerName" class="text-xs text-slate-500">
                    Dipilih: <span class="font-semibold text-slate-800" x-text="selectedManagerName"></span>
                </p>
                <p class="text-xs text-slate-500">Pencarian pengguna memakai AJAX.</p>
            </div>
        </div>

        {{-- ACTIVITY --}}
        <div x-show="tab === 'activity'" x-cloak class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-800">Default Aktivitas</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ia.select label="Status Default" x-model="form.activity_default_status">
                    <option value="planned">Planned</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </x-ia.select>
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700">Reminder (hari sebelum)</label>
                    <input type="number" x-model="form.activity_reminder_days" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm">
                </div>
            </div>
        </div>

        <div class="sticky bottom-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                <span>Perubahan belum disimpan</span>
            </div>
            <button type="button" @click="save" :disabled="saving"
                    class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
            </button>
        </div>
    </div>

    <script>
        function settingsApp(initial) {
            return {
                tab: 'identity',
                saving: false,
                testingEmail: false,
                uploading: false,
                uploadProgress: 0,
                previewLogo: initial.company_logo_url,
                userSearch: '',
                users: [],
                selectedManagerName: initial.project_manager_name || null,
                form: {
                    app_name: initial.app_name,
                    company_name: initial.company_name,
                    company_address: initial.company_address,
                    timezone: initial.timezone,
                    work_start_time: initial.work_start_time,
                    work_end_time: initial.work_end_time,
                    late_tolerance: initial.late_tolerance,
                    annual_leave_quota: initial.annual_leave_quota,
                    face_recognition_enabled: initial.face_recognition_enabled,
                    mobile_face_recognition_enabled: initial.mobile_face_recognition_enabled,
                    face_recognition_threshold: initial.face_recognition_threshold,
                    latitude: initial.latitude,
                    longitude: initial.longitude,
                    radius: initial.radius,
                    mail_mailer: initial.mail_mailer,
                    mail_host: initial.mail_host,
                    mail_port: initial.mail_port,
                    mail_username: initial.mail_username,
                    mail_password: '',
                    mail_encryption: initial.mail_encryption,
                    mail_from_address: initial.mail_from_address,
                    mail_from_name: initial.mail_from_name,
                    project_default_status: initial.project_default_status,
                    project_default_priority: initial.project_default_priority,
                    project_manager_id: initial.project_manager_id,
                    project_review_date: initial.project_review_date,
                    play_store_url: initial.play_store_url,
                    app_store_url: initial.app_store_url,
                    activity_default_status: initial.activity_default_status,
                    activity_reminder_days: initial.activity_reminder_days,
                    test_email_to: '',
                },

                onLogoChange(event) {
                    const file = event.target.files?.[0];
                    if (file) this.previewLogo = URL.createObjectURL(file);
                },

                searchUsers() {
                    fetch(`{{ route('admin.settings.users') }}?q=${encodeURIComponent(this.userSearch)}`, {
                        headers: { Accept: 'application/json' },
                    })
                        .then((response) => response.json())
                        .then((data) => { this.users = data.users || []; })
                        .catch(() => { this.users = []; });
                },

                pickManager(user) {
                    this.form.project_manager_id = user.id;
                    this.selectedManagerName = user.name;
                    this.users = [];
                    this.userSearch = '';
                },

                save() {
                    this.saving = true;
                    if (window.iaStartProgress) window.iaStartProgress('Menyimpan pengaturan...');

                    const formData = new FormData();
                    Object.entries(this.form).forEach(([key, value]) => {
                        if (key === 'test_email_to') return;
                        if (value === true) value = 1;
                        if (value === false) value = 0;
                        if (value !== null && value !== undefined) formData.append(key, value);
                    });

                    const file = this.$refs.logo?.files?.[0];
                    if (file) {
                        formData.append('company_logo', file);
                        this.uploading = true;
                        this.uploadProgress = 0;
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route('admin.settings.update') }}');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = (event) => {
                        if (event.lengthComputable) {
                            this.uploadProgress = Math.round((event.loaded / event.total) * 100);
                        }
                    };

                    xhr.onreadystatechange = () => {
                        if (xhr.readyState !== 4) return;

                        this.saving = false;
                        this.uploading = false;

                        let data = {};
                        try { data = JSON.parse(xhr.responseText || '{}'); } catch (error) {}

                        if (xhr.status >= 200 && xhr.status < 300 && data.status) {
                            if (data.logo_url) this.previewLogo = data.logo_url;
                            if (window.toastr) window.toastr.success(data.message || 'Pengaturan disimpan.', 'Berhasil');
                            if (window.iaFinishProgress) window.iaFinishProgress();
                        } else {
                            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                            const message = data.message || errors || 'Gagal menyimpan pengaturan.';
                            if (window.toastr) window.toastr.error(message, 'Gagal');
                            if (window.iaFailProgress) window.iaFailProgress();
                        }
                    };

                    xhr.onerror = () => {
                        this.saving = false;
                        this.uploading = false;
                        if (window.toastr) window.toastr.error('Gagal menghubungi server.', 'Gagal');
                        if (window.iaFailProgress) window.iaFailProgress();
                    };

                    xhr.send(formData);
                },

                sendTestEmail() {
                    this.testingEmail = true;
                    if (window.iaStartProgress) window.iaStartProgress('Mengirim email uji...');

                    fetch('{{ route('admin.settings.test-email') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            test_email_to: this.form.test_email_to,
                            mail_mailer: this.form.mail_mailer,
                            mail_host: this.form.mail_host,
                            mail_port: this.form.mail_port,
                            mail_username: this.form.mail_username,
                            mail_password: this.form.mail_password,
                            mail_encryption: this.form.mail_encryption,
                            mail_from_address: this.form.mail_from_address,
                            mail_from_name: this.form.mail_from_name,
                        }),
                    })
                        .then(async (response) => {
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'Gagal mengirim email.');
                            if (window.toastr) window.toastr.success(data.message, 'Berhasil');
                        })
                        .catch((error) => {
                            if (window.toastr) window.toastr.error(error.message, 'Gagal');
                        })
                        .finally(() => {
                            this.testingEmail = false;
                            if (window.iaFinishProgress) window.iaFinishProgress();
                        });
                },
            };
        }
    </script>
</x-app-layout>
