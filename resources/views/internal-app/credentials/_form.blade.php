@php
    $credCategories = [
        'domain' => 'Domain', 'cloud' => 'Cloud / IP', 'server_account' => 'Akun Server',
        'vm' => 'Virtual Machine', 'portal' => 'Portal', 'wifi' => 'WiFi',
        'wifi_device' => 'WiFi Device', 'wifi_account' => 'WiFi Account',
        'account' => 'Akun Lainnya', 'opd_account' => 'Akun OPD', 'other' => 'Lainnya',
    ];
@endphp
<select name="category" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
    @foreach($credCategories as $val => $label)
    <option value="{{ $val }}">{{ $label }}</option>
    @endforeach
</select>
<input name="group_name" placeholder="Group (mis. OFFICE / Cloud)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="vendor" placeholder="Vendor / Provider" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="name" placeholder="Nama / SSID / Domain / Aplikasi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="ip_address" placeholder="IP Address / Host" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="url" placeholder="URL / Link" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="username" placeholder="Username" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="password" placeholder="Password (kosongkan jika tidak diubah)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="email" type="email" placeholder="Email akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="expired_date" type="date" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="status" placeholder="Status (Aktif/Nonaktif)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
<textarea name="notes" rows="2" placeholder="Catatan" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
