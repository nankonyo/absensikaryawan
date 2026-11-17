<input name="name" placeholder="Jabatan / Nama node (mis. Programmer)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
<label class="block text-xs font-semibold text-slate-500">Atasan (parent)</label>
<select name="parent_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    <option value="">— Root —</option>
    @foreach($allNodes as $n)
    <option value="{{ $n->id }}">{{ $n->name }}</option>
    @endforeach
</select>
<label class="block text-xs font-semibold text-slate-500">User</label>
<select name="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
    <option value="">— Belum di-assign —</option>
    @foreach($users as $u)
    <option value="{{ $u->id }}">{{ $u->nama }}{{ $u->divisi ? ' · ' . $u->divisi : '' }}</option>
    @endforeach
</select>
<input name="sort" type="number" min="0" value="0" placeholder="Urutan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
