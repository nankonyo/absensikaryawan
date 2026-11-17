@php
    $companyLogo = \App\Models\Setting::get('company_logo');
    $appName = \App\Models\Setting::get('app_name', config('app.name'));
    $companyName = \App\Models\Setting::get('company_name', config('app.name'));
    $companyAddress = \App\Models\Setting::get('company_address');
    $playStoreUrl = \App\Models\Setting::get('play_store_url');
    $appStoreUrl = \App\Models\Setting::get('app_store_url');
@endphp

<x-guest-layout :full="true">
    <div class="flex min-h-screen w-full bg-white font-sans text-slate-700">
        {{-- SISI KIRI: VISUAL --}}
        <div class="relative hidden items-center justify-center overflow-hidden bg-slate-900 p-12 lg:flex lg:w-1/2">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-950"></div>
            <div class="absolute inset-0 opacity-20"
                 style="background-image: radial-gradient(circle at 20% 20%, #ffffff 0, transparent 35%), radial-gradient(circle at 80% 80%, #ffffff 0, transparent 30%);"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/50 to-transparent"></div>

            <div class="relative z-10 mx-auto flex max-w-lg flex-col items-center space-y-6 text-center">
                <div class="flex h-32 w-32 items-center justify-center rounded-3xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-sm transition duration-300 hover:scale-105 md:h-40 md:w-40">
                    @if($companyLogo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo) }}"
                             alt="{{ $companyName }}"
                             class="max-h-full max-w-full object-contain drop-shadow-lg">
                    @else
                        <x-application-logo class="h-20 w-20 fill-current text-white/90" />
                    @endif
                </div>

                <div class="space-y-2">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-300">Portal Internal</h2>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white drop-shadow-md md:text-4xl">
                        {{ $appName }}
                    </h1>
                    <p class="mx-auto max-w-sm pt-2 text-sm leading-relaxed text-slate-300">
                        {{ $companyName }} — presensi, cuti, project management, aktivitas, dan administrasi internal dalam satu aplikasi.
                    </p>
                    @if($companyAddress)
                        <p class="mx-auto max-w-sm text-xs leading-relaxed text-slate-400">
                            {{ \Illuminate\Support\Str::limit(strip_tags($companyAddress), 140) }}
                        </p>
                    @endif
                </div>

                <div class="pt-8">
                    <span class="inline-flex items-center space-x-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs text-slate-300 backdrop-blur-sm">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                        <span>Server Online</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- SISI KANAN: FORM LOGIN --}}
        <div class="flex w-full items-center justify-center overflow-y-auto bg-white p-6 sm:p-12 md:p-16 lg:w-1/2">
            <div class="w-full max-w-md space-y-6">
                <div class="flex items-center justify-between">
                    <a href="/" class="inline-flex items-center gap-3 rounded-2xl bg-[#2c3e50] px-4 py-3 text-white shadow-lg">
                        @if($companyLogo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo) }}"
                                 alt="{{ $companyName }}"
                                 class="h-10 w-10 rounded-lg bg-white/10 object-contain p-1">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"></path>
                                </svg>
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold leading-tight">{{ $appName }}</p>
                            <p class="truncate text-[10px] font-medium uppercase tracking-widest text-slate-400">{{ $companyName }}</p>
                        </div>
                    </a>

                    <label for="guest-locale-switcher" class="sr-only">{{ __('ui.language') }}</label>
                    <select id="guest-locale-switcher"
                            onchange="window.location.href = this.value"
                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="{{ route('locale.switch', 'id') }}" @selected(app()->getLocale() === 'id')>ID</option>
                        <option value="{{ route('locale.switch', 'en') }}" @selected(app()->getLocale() === 'en')>EN</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat Datang</h2>
                    <p class="text-sm leading-relaxed text-slate-500">
                        Masukkan email dan kata sandi akun kepegawaian Anda.
                    </p>
                </div>

                <div id="loginAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

                <form id="loginForm" method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                        <div class="flex min-h-[46px] items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white transition focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <span class="flex w-12 shrink-0 items-center justify-center border-r border-slate-200 bg-slate-50 text-base font-bold leading-none text-slate-400">
                                @
                            </span>
                            <input id="email" type="email" name="email" required autofocus autocomplete="username"
                                   value="{{ old('email') }}"
                                   class="min-w-0 w-full px-3 py-2.5 text-sm font-medium text-slate-800 outline-none"
                                   placeholder="nama@perusahaan.com">
                        </div>
                        <p id="emailError" class="hidden text-xs font-medium text-red-600"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-600">Kata Sandi</label>
                        <div class="flex min-h-[46px] items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white transition focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <span class="flex w-12 shrink-0 items-center justify-center border-r border-slate-200 bg-slate-50 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                   class="min-w-0 w-full px-3 py-2.5 text-sm font-medium text-slate-800 outline-none"
                                   placeholder="Masukkan kata sandi...">
                            <button type="button" id="togglePassword" class="flex w-12 shrink-0 items-center justify-center text-slate-400 hover:text-slate-600" aria-label="Tampilkan kata sandi">
                                <svg id="eyeIcon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="passwordError" class="hidden text-xs font-medium text-red-600"></p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="remember" type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            Ingat saya
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline" href="{{ route('password.request') }}">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>

                    <button type="submit" id="submitBtn"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <span id="submitText">Masuk Aplikasi</span>
                        <svg id="submitIcon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </form>

                @if ($playStoreUrl || $appStoreUrl)
                    <div class="space-y-3 border-t border-slate-100 pt-5">
                        <p class="text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Download Aplikasi</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @if ($playStoreUrl)
                                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener"
                                   class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M3.6 2.4c-.3.2-.5.5-.5.9v17.4c0 .4.2.7.5.9l9.1-9.6L3.6 2.4zm10.3 8.8l2.8-3-9.7-5.5 6.9 8.5zm0 1.6l-6.9 8.5 9.7-5.5-2.8-3zm1.4-1.6l3.2 3.4c.7-.4 1.1-1 1.1-1.8 0-.8-.4-1.4-1.1-1.8l-3.2 3.4-2.8-3-1.4 1.6 4.2 4.4 4.2-4.4-1.4-1.6-2.8 3z"/>
                                    </svg>
                                    Google Play
                                </a>
                            @endif
                            @if ($appStoreUrl)
                                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener"
                                   class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-900 px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M16.365 1.43c.022 1.34-.52 2.63-1.42 3.57-.9.95-2.2 1.53-3.48 1.44-.13-1.3.47-2.63 1.35-3.5.93-.97 2.43-1.6 3.55-1.51zM20.6 17.08c-.62 1.42-.92 2.05-1.72 3.3-1.12 1.75-2.7 3.92-4.66 3.94-1.74.02-2.19-1.14-4.55-1.13-2.36.01-2.85 1.15-4.59 1.13-1.96-.02-3.45-1.98-4.57-3.73C-2.02 15.7-2.3 9.7.2 6.55 1.53 4.87 3.56 3.8 5.56 3.8c2.06 0 3.36 1.14 5.07 1.14 1.66 0 2.67-1.14 5.06-1.14 1.79 0 3.69.98 5.06 2.66-4.45 2.44-3.73 8.79 2.85 10.62z"/>
                                    </svg>
                                    App Store
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <p class="text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitIcon = document.getElementById('submitIcon');
            const alertBox = document.getElementById('loginAlert');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            const toast = (type, message) => {
                if (window.toastr) {
                    window.toastr[type](message, type === 'success' ? 'Berhasil' : 'Gagal');
                }
            };

            const showAlert = (message, type = 'error') => {
                alertBox.textContent = message;
                alertBox.className = 'rounded-xl border px-4 py-3 text-sm font-medium ' + (type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-red-200 bg-red-50 text-red-600');
                alertBox.classList.remove('hidden');
            };

            const clearErrors = () => {
                alertBox.classList.add('hidden');
                [emailError, passwordError].forEach((element) => {
                    element.textContent = '';
                    element.classList.add('hidden');
                });
            };

            document.getElementById('togglePassword').addEventListener('click', function () {
                const password = document.getElementById('password');
                const icon = document.getElementById('eyeIcon');
                const visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                icon.style.opacity = visible ? '1' : '.5';
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearErrors();

                submitBtn.disabled = true;
                submitText.textContent = 'Memverifikasi...';
                submitIcon.classList.add('animate-spin');
                if (window.iaStartProgress) window.iaStartProgress();

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    const data = await response.json();

                    if (response.ok && data.status) {
                        if (window.iaFinishProgress) window.iaFinishProgress();
                        toast('success', data.message || 'Berhasil masuk.');
                        const redirectUrl = data.redirect || '{{ route('dashboard') }}';

                        window.location.href = redirectUrl;
                        return;
                    }

                    if (response.status === 422 && data.errors) {
                        if (data.errors.email) {
                            emailError.textContent = data.errors.email[0];
                            emailError.classList.remove('hidden');
                        }
                        if (data.errors.password) {
                            passwordError.textContent = data.errors.password[0];
                            passwordError.classList.remove('hidden');
                        }
                        toast('error', Object.values(data.errors)[0]?.[0] || 'Data login tidak valid.');
                    } else {
                        showAlert(data.message || 'Email atau kata sandi salah.');
                        toast('error', data.message || 'Email atau kata sandi salah.');
                    }
                } catch (error) {
                    showAlert('Terjadi kesalahan saat menghubungi server.');
                    toast('error', 'Terjadi kesalahan saat menghubungi server.');
                } finally {
                    if (window.iaFinishProgress) window.iaFinishProgress();
                    submitBtn.disabled = false;
                    submitText.textContent = 'Masuk Aplikasi';
                    submitIcon.classList.remove('animate-spin');
                }
            });
        });
    </script>
</x-guest-layout>
