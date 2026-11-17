<input name="title" placeholder="Judul dokumentasi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
<textarea name="description" rows="2" placeholder="Deskripsi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
<input name="file_url" placeholder="URL file eksternal (opsional)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
<div class="sm:col-span-2">
    <label class="text-xs font-semibold text-slate-500">Upload file (PDF / gambar, opsional)</label>
    <input type="file" name="file" accept="application/pdf,image/*" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
</div>
<select name="icon_color" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
    <option value="emerald">Hijau</option>
    <option value="blue">Biru</option>
    <option value="rose">Merah</option>
    <option value="amber">Kuning</option>
</select>
<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_active" value="1" class="toggle-sw" checked> Aktif
</label>
