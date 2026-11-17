<x-app-layout>
    <x-slot name="header">Dokumentasi API Mobile</x-slot>

    @php
        $sections = [
            'overview' => [
                'title' => 'Overview',
                'intro' => 'Dokumentasi endpoint yang dipakai aplikasi mobile (React Native). API memakai token Bearer, bukan cookie/session.',
                'endpoints' => [],
                'notes' => [
                    'Base URL: '.$baseUrl,
                    'Header wajib: Accept: application/json',
                    'Header auth: Authorization: Bearer <token>',
                    'Token didapat dari POST /login dan berlaku 30 hari.',
                    'Request/response memakai JSON, kecuali upload file memakai multipart/form-data.',
                    'Semua tanggal API memakai format Y-m-d (contoh 2026-11-17).',
                ],
            ],
            'auth' => [
                'title' => 'Autentikasi & Profil',
                'intro' => 'Login, cek user aktif, logout, dan profil.',
                'endpoints' => [
                    ['method' => 'POST', 'path' => '/login', 'desc' => 'Login dan dapatkan token.', 'auth' => false, 'body' => '{"email":"user@example.com","password":"********","device_name":"Android"}', 'response' => '{"token":"TOKEN_STRING","user":{"id":1,"name":"Nama User","email":"user@example.com","role":{"name":"staff"},"is_admin":false,"is_atasan":false,"is_karyawan":true}}'],
                    ['method' => 'GET', 'path' => '/me', 'desc' => 'Data user yang sedang login.'],
                    ['method' => 'POST', 'path' => '/logout', 'desc' => 'Hapus token aktif.', 'response' => '{"message":"Logged out."}'],
                    ['method' => 'GET', 'path' => '/profile', 'desc' => 'Detail profil user.'],
                    ['method' => 'PUT', 'path' => '/profile', 'desc' => 'Ubah nama/telepon.', 'body' => '{"name":"Nama Baru","phone":"08123456789"}'],
                ],
                'notes' => ['Gunakan token dari response login pada semua endpoint selain /login.'],
            ],
            'dashboard' => [
                'title' => 'Dashboard',
                'intro' => 'Ringkasan role, presensi hari ini, cuti, approval, project, dan aktivitas.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/dashboard', 'desc' => 'Statistik dashboard sesuai role.', 'response' => '{"user":{...},"stats":{"today_attendance":{...},"my_pending_leaves":0,"team_members":5,"pending_approvals":2,"projects":3,"my_activities_this_month":8},"announcements":[...],"projects":[...]}'],
                ],
                'notes' => ['Menu yang dikembalikan menyesuaikan role: admin, atasan, atau karyawan.'],
            ],
            'attendance' => [
                'title' => 'Presensi',
                'intro' => 'Presensi harian, riwayat, alasan kehadiran, check-in, dan check-out.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/attendance/today', 'desc' => 'Presensi hari ini + data kantor + alasan.', 'response' => '{"attendance":null,"office":{"id":1,"name":"Kantor Pusat","latitude":-6.2,"longitude":106.8,"radius":100},"work_start_time":"08:00","work_end_time":"17:00","reasons":[{"id":1,"name":"WFO"}]}'],
                    ['method' => 'GET', 'path' => '/attendance/history', 'desc' => 'Riwayat presensi. Query: month=YYYY-MM, status=valid|pending|invalid, per_page.', 'response' => '{"data":[{"id":10,"date":"2026-11-17","check_in":"08:01","check_out":"17:02","status":"valid"}],"current_page":1,"last_page":1}'],
                    ['method' => 'GET', 'path' => '/attendance/reasons', 'desc' => 'Daftar alasan kehadiran aktif.'],
                    ['method' => 'POST', 'path' => '/attendance/check-in', 'desc' => 'Check-in dengan GPS/foto. Jika di luar radius wajib isi attendance_reason_id.', 'body' => '{"latitude":-6.2,"longitude":106.8,"address":"Kantor","photo_data":"data:image/jpeg;base64,...","face_score":92.5,"attendance_reason_id":1,"notes":"WFH"}', 'response' => '{"success":true,"message":"Check-in berhasil.","attendance":{...}}'],
                    ['method' => 'POST', 'path' => '/attendance/check-out', 'desc' => 'Check-out dengan payload yang sama seperti check-in.'],
                ],
                'notes' => ['photo_data opsional (base64). face_score diisi bila face recognition aktif.', 'Respons gagal memakai HTTP 422 dengan message alasan.'],
            ],
            'leave' => [
                'title' => 'Cuti & Approval',
                'intro' => 'Pengajuan cuti karyawan dan approval atasan/verifikator.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/leaves', 'desc' => 'Daftar cuti user. Query: status, per_page.'],
                    ['method' => 'GET', 'path' => '/leaves/types', 'desc' => 'Jenis cuti aktif.'],
                    ['method' => 'POST', 'path' => '/leaves', 'desc' => 'Ajukan cuti.', 'body' => '{"leave_type_id":1,"start_date":"2026-11-20","end_date":"2026-11-21","reason":"Keperluan keluarga"}', 'response' => '{"message":"Pengajuan cuti berhasil dikirim.","leave":{...}}'],
                    ['method' => 'GET', 'path' => '/leaves/{id}', 'desc' => 'Detail pengajuan cuti.'],
                    ['method' => 'DELETE', 'path' => '/leaves/{id}', 'desc' => 'Batalkan pengajuan (hanya status pending).'],
                    ['method' => 'GET', 'path' => '/approvals', 'desc' => 'Pengajuan pending untuk atasan/verifikator.'],
                    ['method' => 'POST', 'path' => '/approvals/{id}/approve', 'desc' => 'Setujui pengajuan.', 'body' => '{"notes":"Disetujui"}'],
                    ['method' => 'POST', 'path' => '/approvals/{id}/reject', 'desc' => 'Tolak pengajuan.', 'body' => '{"notes":"Ditolak"}'],
                    ['method' => 'POST', 'path' => '/approvals/{id}/verify', 'desc' => 'Verifikasi oleh sekretariat/verifikator.'],
                ],
                'notes' => ['Approval hanya bisa untuk bawahan/anggota tim sesuai role.'],
            ],
            'team' => [
                'title' => 'Tim',
                'intro' => 'Monitoring anggota tim dan presensi hari ini.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/team', 'desc' => 'Anggota tim + presensi hari ini.', 'response' => '{"members":[{"id":2,"name":"Anggota","role":"Staff","attendance":{"check_in":"08:00","check_out":null,"status":"valid"}}]}'],
                    ['method' => 'GET', 'path' => '/team/{member}', 'desc' => 'Detail anggota + riwayat presensi.'],
                ],
                'notes' => ['Hanya untuk atasan/admin.'],
            ],
            'project' => [
                'title' => 'Project & Task',
                'intro' => 'Project management, task, kanban, dan gantt.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/projects', 'desc' => 'Daftar project. Query: q, page.'],
                    ['method' => 'GET', 'path' => '/projects/{id}', 'desc' => 'Detail project + task + member.'],
                    ['method' => 'GET', 'path' => '/projects/{id}/tasks', 'desc' => 'Task milik project.'],
                    ['method' => 'GET', 'path' => '/projects/kanban', 'desc' => 'Board task per status. Query: project_id.'],
                    ['method' => 'GET', 'path' => '/projects/gantt', 'desc' => 'Timeline task. Query: project_id.'],
                    ['method' => 'POST', 'path' => '/projects/tasks/{task}', 'desc' => 'Update status/progress task.', 'body' => '{"status":"in_progress","progress":40,"assignee_id":2,"due_date":"2026-11-30"}'],
                ],
                'notes' => ['Status task: backlog, todo, in_progress, in_review, done.', 'Priority: low, medium, high, urgent.'],
            ],
            'activity' => [
                'title' => 'Aktivitas',
                'intro' => 'Log aktivitas project per karyawan.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/activities', 'desc' => 'Daftar aktivitas. Query: scope=mine|team, project_id, from, to, page.'],
                    ['method' => 'POST', 'path' => '/activities', 'desc' => 'Input aktivitas baru.', 'body' => '{"project_id":1,"title":"Update modul presensi","description":"Perbaikan validasi GPS","status":"in_progress","priority":"medium","activity_date":"2026-11-17"}', 'response' => '{"message":"Aktivitas berhasil disimpan.","task_id":12}'],
                ],
                'notes' => ['scope=team hanya berlaku untuk atasan/admin.'],
            ],
            'content' => [
                'title' => 'Konten',
                'intro' => 'Video tutorial, dokumentasi, dan pengumuman.',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/contents/videos', 'desc' => 'Daftar video tutorial aktif.'],
                    ['method' => 'GET', 'path' => '/contents/dokumentasi', 'desc' => 'Daftar dokumentasi aktif.'],
                    ['method' => 'GET', 'path' => '/contents/pengumuman', 'desc' => 'Daftar pengumuman aktif.'],
                ],
                'notes' => ['Video berisi youtube_url.', 'Dokumentasi berisi file_url.', 'Pengumuman berisi content dan pdf_path.'],
            ],
            'errors' => [
                'title' => 'Error & Catatan',
                'intro' => 'Format error dan ketentuan umum.',
                'endpoints' => [],
                'notes' => [
                    '401 Unauthorized: token tidak ada/kadaluarsa → login ulang.',
                    '403 Forbidden: role tidak berhak mengakses endpoint.',
                    '404 Not Found: data tidak ditemukan.',
                    '422 Unprocessable Entity: validasi gagal, berisi errors per field.',
                    '500 Server Error: hubungi admin.',
                    'Contoh error validasi: {"message":"The email field is required.","errors":{"email":["The email field is required."]}}',
                ],
            ],
        ];

        $methodColor = [
            'GET' => 'bg-blue-50 text-blue-700 border-blue-200',
            'POST' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'PUT' => 'bg-amber-50 text-amber-700 border-amber-200',
            'DELETE' => 'bg-red-50 text-red-700 border-red-200',
        ];
    @endphp

    <div x-data="{ tab: 'overview' }" class="space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dokumentasi API Mobile</h1>
            <p class="text-sm text-slate-500">Referensi endpoint untuk aplikasi Android/iOS. Base URL: <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $baseUrl }}</code></p>
        </div>

        <div class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
            @foreach ($sections as $key => $section)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-blue-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100'"
                        class="shrink-0 rounded-xl px-3.5 py-2 text-xs font-semibold transition">
                    {{ $section['title'] }}
                </button>
            @endforeach
        </div>

        @foreach ($sections as $key => $section)
            <div x-show="tab === '{{ $key }}'" x-cloak class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">{{ $section['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $section['intro'] }}</p>
                </div>

                @foreach ($section['endpoints'] as $endpoint)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg border px-2 py-1 text-[10px] font-bold {{ $methodColor[$endpoint['method']] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                {{ $endpoint['method'] }}
                            </span>
                            <code class="break-all text-sm font-semibold text-slate-800">{{ $baseUrl }}{{ $endpoint['path'] }}</code>
                            @if (($endpoint['auth'] ?? true) === true)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Bearer Token</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Public</span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm text-slate-600">{{ $endpoint['desc'] }}</p>

                        @if (! empty($endpoint['body']))
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Request Body</p>
                            <pre class="mt-1 overflow-x-auto rounded-xl bg-slate-900 p-3 text-xs text-slate-100"><code>{{ $endpoint['body'] }}</code></pre>
                        @endif

                        @if (! empty($endpoint['response']))
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Response Contoh</p>
                            <pre class="mt-1 overflow-x-auto rounded-xl bg-slate-50 p-3 text-xs text-slate-700 ring-1 ring-slate-200"><code>{{ $endpoint['response'] }}</code></pre>
                        @endif
                    </div>
                @endforeach

                @if (! empty($section['notes']))
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-sm font-bold text-blue-800">Catatan</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-blue-700">
                            @foreach ($section['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
