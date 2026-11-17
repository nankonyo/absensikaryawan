<input name="code" placeholder="Kode (opsional)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="slug" placeholder="Slug (opsional)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<input name="title" placeholder="Judul pengumuman" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
<textarea name="content" rows="4" placeholder="Isi pengumuman" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
<div class="sm:col-span-2">
    <label class="text-xs font-semibold text-slate-500">Lampiran PDF (opsional)</label>
    <input type="file" name="pdf" accept="application/pdf" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
</div>
<label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" class="toggle-sw" checked> Aktif
</label>
