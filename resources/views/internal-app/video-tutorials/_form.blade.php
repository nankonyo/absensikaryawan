<input name="title" placeholder="Judul video" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
<input name="youtube_url" placeholder="URL YouTube (embed)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
<input name="badge_text" placeholder="Badge (mis. Pendahuluan)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
<select name="badge_color" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
    <option value="bg-blue-600">Biru</option>
    <option value="bg-indigo-600">Indigo</option>
    <option value="bg-emerald-600">Hijau</option>
    <option value="bg-amber-600">Kuning</option>
    <option value="bg-red-600">Merah</option>
</select>
<label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" class="toggle-sw" checked> Aktif
</label>
