@extends('internal-app.layouts.app')
@section('title', 'Pengiriman Email')
@section('subtitle', 'Riwayat pengiriman email laporan mingguan & isi kontennya')

@section('body')
<div x-data="{ open: false, body: '', subject: '', info: {} }"
     @open-email.window="open = true; body = $event.detail.body || ''; subject = $event.detail.subject || ''; info = $event.detail"
     class="space-y-5">

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Total Email</p><p class="mt-1 text-2xl font-black text-slate-800">{{ number_format($counts['total']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Terkirim</p><p class="mt-1 text-2xl font-black text-emerald-600">{{ number_format($counts['sent']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Gagal</p><p class="mt-1 text-2xl font-black text-red-600">{{ number_format($counts['failed']) }}</p></div>
    </div>

    <form method="get" class="ia-card flex flex-wrap items-end gap-2 p-4">
        <div><label class="text-[11px] font-semibold text-slate-500">Cari</label><input name="q" value="{{ $q }}" placeholder="Nama / email / subject" class="w-60 rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
        <div><label class="text-[11px] font-semibold text-slate-500">Status</label>
            <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua</option>
                <option value="sent" @selected($status === 'sent')>Terkirim</option>
                <option value="failed" @selected($status === 'failed')>Gagal</option>
                <option value="pending" @selected($status === 'pending')>Pending</option>
            </select>
        </div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="ia-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Penerima</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Subjek</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $e)
                    <tr>
                        <td class="px-4 py-2 text-xs whitespace-nowrap">{{ $e->sent_at ?: $e->created_at }}</td>
                        <td class="px-4 py-2 font-medium">{{ $e->recipient_name }}</td>
                        <td class="px-4 py-2 text-xs">{{ $e->recipient_email }}</td>
                        <td class="px-4 py-2 text-xs max-w-[260px] truncate">{{ $e->subject }}</td>
                        <td class="px-4 py-2 text-xs whitespace-nowrap">{{ $e->period_from }} s/d {{ $e->period_to }}</td>
                        <td class="px-4 py-2">
                            @if($e->status === 'sent')
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Terkirim</span>
                            @elseif($e->status === 'failed')
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600">Gagal</span>
                            @else
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button onclick="openEmailLog({{ $e->id }})" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Lihat Konten</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada email terkirim.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="flex h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="open=false">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-4">
                <div class="min-w-0">
                    <h3 class="truncate font-bold text-slate-800" x-text="subject"></h3>
                    <p class="text-xs text-slate-500">
                        <span x-text="info.recipient_name"></span> · <span x-text="info.recipient_email"></span>
                        · <span x-text="info.status"></span>
                    </p>
                    <p x-show="info.error" x-cloak class="mt-1 text-xs text-red-600" x-text="'Error: ' + info.error"></p>
                </div>
                <button @click="open=false" class="text-slate-400"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <iframe :srcdoc="body" class="h-full w-full border-0 bg-white" title="Konten Email"></iframe>
        </div>
    </div>
</div>

<script>
async function openEmailLog(id) {
    try {
        const r = await fetch('{{ url('internal/email-logs') }}/' + id, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        window.dispatchEvent(new CustomEvent('open-email', { detail: d }));
    } catch (e) { iaToast('Gagal memuat konten email.', false); }
}
</script>
@endsection
