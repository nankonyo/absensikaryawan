@extends('internal-app.layouts.app')
@section('title', 'Detail Task')
@section('subtitle', $project->name ?? 'Project Management')

@section('body')
@php
    $labelStatus = ['backlog'=>'Backlog','todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done'];
@endphp
<div x-data="{edit:false}" class="grid gap-5 xl:grid-cols-[1.4fr_.6fr]">
    <div class="space-y-5">
        <div class="ia-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('internal.projects.board', ['project_id'=>$task->project_id]) }}" class="text-xs font-semibold text-blue-600"><i class="fa-solid fa-arrow-left mr-1"></i>{{ $project->name ?? '-' }}</a>
                    <h1 id="pm-task-link" class="mt-2 text-2xl font-black text-slate-800">{{ $task->title }}</h1>
                    <p class="mt-1 text-xs text-slate-500">{{ $task->assignee_name }} · Deadline {{ $task->due_date ?: '-' }}</p>
                </div>
                <button @click="edit=true" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600"><i class="fa-solid fa-pen mr-1"></i>Edit</button>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">{{ $labelStatus[$task->status] ?? $task->status }}</span>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">{{ strtoupper($task->priority) }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">{{ $task->progress }}%</span>
                @if($task->is_milestone)<span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700"><i class="fa-solid fa-flag mr-1"></i>Milestone</span>@endif
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width:{{ $task->progress }}%"></div></div>
            <div class="prose prose-sm mt-4 max-w-none text-sm text-slate-600">{!! nl2br(e($task->description ?: 'Belum ada deskripsi.')) !!}</div>
        </div>

        <div class="ia-card p-5">
            <div class="flex items-center justify-between"><h2 class="font-bold">Sub-task</h2><span class="text-xs text-slate-400">{{ $subtasks->where('is_done',true)->count() }}/{{ $subtasks->count() }}</span></div>
            <div class="mt-3 space-y-2">
                @foreach($subtasks as $s)
                <div class="flex items-center gap-2 rounded-xl border border-slate-100 px-3 py-2">
                    <input type="checkbox" {{ $s->is_done ? 'checked' : '' }} onchange="toggleSubtask({{ $s->id }})" class="rounded border-slate-300">
                    <span class="flex-1 text-sm {{ $s->is_done ? 'line-through text-slate-400' : '' }}">{{ $s->title }}</span>
                    <button onclick="deleteSubtask({{ $s->id }})" class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                @endforeach
            </div>
            <form class="mt-3 flex gap-2" onsubmit="return addSubtask(event)">
                <input name="title" placeholder="Tambah sub-task..." class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                <button class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tambah</button>
            </form>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold">Komentar & Mention</h2>
            <div class="mt-3 space-y-3">
                @forelse($comments as $c)
                <div class="rounded-xl bg-slate-50 p-3"><div class="flex items-center justify-between text-xs"><b>{{ $c->nama }}</b><span class="text-slate-400">{{ $c->created_at }}</span></div><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $c->body }}</p></div>
                @empty
                <p class="py-4 text-center text-sm text-slate-400">Belum ada komentar.</p>
                @endforelse
            </div>
            <form class="mt-3" onsubmit="return addComment(event)">
                <textarea name="body" rows="3" placeholder="Tulis komentar... gunakan @username untuk mention" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                <div class="mt-2 text-right"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Kirim</button></div>
            </form>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold">Worklog</h2>
            <div class="mt-3 space-y-2">
                @forelse($worklogs as $w)
                <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm"><span>{{ $w->user_name }} · {{ $w->duration_minutes }} menit</span><span class="text-xs text-slate-400">{{ $w->started_at }}</span></div>
                @empty
                <p class="py-4 text-center text-sm text-slate-400">Belum ada worklog.</p>
                @endforelse
            </div>
            <form class="mt-3 flex gap-2" onsubmit="return addWorklog(event)">
                <input name="duration_minutes" type="number" min="1" placeholder="Menit" class="w-28 rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                <input name="note" placeholder="Catatan" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">Manual</button>
            </form>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold">Lampiran</h2>
            <div class="mt-3 space-y-2">
                @forelse($attachments as $a)
                <a href="{{ asset(ltrim($a->file_path,'/')) }}" target="_blank" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm hover:bg-slate-50"><span><i class="fa-solid fa-paperclip mr-1 text-slate-400"></i>{{ $a->original_name }}</span><span class="text-xs text-slate-400">{{ $a->user_name }}</span></a>
                @empty
                <p class="py-4 text-center text-sm text-slate-400">Belum ada lampiran.</p>
                @endforelse
            </div>
            <form class="mt-3 flex gap-2" onsubmit="return uploadAttachment(event)">
                <input type="file" name="file" class="flex-1 text-xs" required>
                <button class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Unggah</button>
            </form>
        </div>
    </div>

    <div class="space-y-5">
        <div class="ia-card p-5">
            <h2 class="font-bold">Timer Kerja</h2>
            <div class="mt-3 grid gap-2">
                <button onclick="startTimer()" class="rounded-xl bg-emerald-600 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-play mr-1"></i>Mulai</button>
                <button onclick="stopTimer()" class="rounded-xl bg-amber-500 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-pause mr-1"></i>Pause / Selesai</button>
            </div>
        </div>
        <div class="ia-card p-5">
            <h2 class="font-bold">Audit Trail</h2>
            <div class="mt-3 max-h-[420px] space-y-2 overflow-y-auto">
                @forelse($logs as $l)
                <div class="rounded-lg bg-slate-50 p-2 text-xs"><b>{{ $l->user_name ?: 'System' }}</b> · {{ $l->description }}<p class="text-[10px] text-slate-400">{{ $l->created_at }}</p></div>
                @empty
                <p class="py-4 text-center text-sm text-slate-400">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Edit Task</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return updateTask(event)">
                <input name="title" value="{{ $task->title }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
                <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">@foreach($statuses as $s)<option value="{{ $s }}" @selected($task->status===$s)>{{ $labelStatus[$s] }}</option>@endforeach</select>
                <select name="priority" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">@foreach($priorities as $p)<option value="{{ $p }}" @selected($task->priority===$p)>{{ ucfirst($p) }}</option>@endforeach</select>
                <select name="assignee_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'users']) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u['id'] }}" @selected($task->assignee_id==$u['id'])>{{ $u['name'] }}</option>@endforeach</select>
                <input name="due_date" type="date" value="{{ $task->due_date }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="start_date" type="date" value="{{ $task->start_date }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="estimated_hours" type="number" step="0.5" value="{{ $task->estimated_hours }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_milestone" value="1" {{ $task->is_milestone?'checked':'' }}> Milestone</label>
                <textarea name="description" rows="3" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">{{ $task->description }}</textarea>
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
const TASK_ID = {{ $task->id }};
const TASK_BASE = '{{ url('internal/project-management/tasks/'.$task->id) }}';
async function toggleSubtask(id){try{const d=await postJson('{{ url('internal/project-management/subtasks') }}/'+id+'/toggle',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
async function deleteSubtask(id){try{const d=await postJson('{{ url('internal/project-management/subtasks') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
async function addSubtask(e){e.preventDefault();try{const d=await postJson(TASK_BASE+'/subtask',{title:e.target.title.value});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(err){iaToast(err.message,false)}return false}
async function addComment(e){e.preventDefault();try{const d=await postJson(TASK_BASE+'/comment',{body:e.target.body.value});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(err){iaToast(err.message,false)}return false}
async function addWorklog(e){e.preventDefault();const fd=new FormData(e.target);try{const d=await postJson(TASK_BASE+'/worklog',Object.fromEntries(fd.entries()));iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(err){iaToast(err.message,false)}return false}
async function uploadAttachment(e){e.preventDefault();try{const d=await postForm(TASK_BASE+'/attachment',new FormData(e.target));iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(err){iaToast(err.message,false)}return false}
async function startTimer(){try{const d=await postJson(TASK_BASE+'/worklog/start',{});iaToast(d.message);}catch(e){iaToast(e.message,false)}}
async function stopTimer(){try{const d=await postJson(TASK_BASE+'/worklog/stop',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
async function updateTask(e){e.preventDefault();const fd=new FormData(e.target);const payload=Object.fromEntries(fd.entries());payload.project_id={{ $task->project_id }};payload.assignee_id=payload.assignee_id?Number(payload.assignee_id):null;payload.is_milestone=fd.get('is_milestone')?1:0;try{const d=await postJson(TASK_BASE+'/update',payload);iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(err){iaToast(err.message,false)}return false}
</script>
@endsection
