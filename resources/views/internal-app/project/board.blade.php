@extends('internal-app.layouts.app')
@section('title', 'Kanban Board')
@section('subtitle', 'Drag & drop task antar status')

@section('body')
@php
    $labels = ['backlog'=>'Backlog','todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done'];
    $colors = ['backlog'=>'bg-slate-100 text-slate-600','todo'=>'bg-blue-50 text-blue-700','in_progress'=>'bg-indigo-50 text-indigo-700','in_review'=>'bg-amber-50 text-amber-700','done'=>'bg-emerald-50 text-emerald-700'];
    $prio = ['low'=>'bg-slate-100 text-slate-600','medium'=>'bg-blue-50 text-blue-700','high'=>'bg-amber-50 text-amber-700','urgent'=>'bg-red-50 text-red-600'];
@endphp
<div x-data="{modal:false}" class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">Project</label>
            <select id="pm-project-select" onchange="if(this.value){(window.Alpine&&Alpine.store('spa'))?Alpine.store('spa').navigate(this.value):(location.href=this.value)}" class="min-w-[220px] rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <option value="">— Pilih Project —</option>
                @foreach($projects as $p)
                <option value="{{ route('internal.projects.board', ['project_id'=>$p->id]) }}" @selected($project && $project->id==$p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        @if($project)
        <button @click="modal=true" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Task</button>
        @else
        <a href="{{ route('internal.projects.index') }}" @click.prevent="$store.spa.navigate($el.href)" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Buat Project</a>
        @endif
    </div>

    @if($projects->isEmpty())
    <div class="ia-card p-10 text-center text-sm text-slate-400">
        Belum ada project. <a href="{{ route('internal.projects.index') }}" @click.prevent="$store.spa.navigate($el.href)" class="font-semibold text-blue-600">Buat project</a> terlebih dahulu.
    </div>
    @else
    <div id="pm-kanban" class="grid gap-4 xl:grid-cols-5 overflow-x-auto pb-2">
        @foreach($statuses as $status)
        <div class="min-w-[240px] rounded-2xl bg-slate-100/70 p-3" data-status="{{ $status }}" ondragover="event.preventDefault()" ondrop="dropTask(event)">
            <div class="mb-3 flex items-center justify-between px-1">
                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $colors[$status] }}">{{ $labels[$status] }}</span>
                <span class="text-xs font-bold text-slate-400">{{ ($grouped[$status] ?? collect())->count() }}</span>
            </div>
            <div class="space-y-2 min-h-[80px]" data-dropzone>
                @foreach(($grouped[$status] ?? collect()) as $task)
                <div draggable="true" data-task-id="{{ $task->id }}" ondragstart="dragTask(event)" class="cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm active:cursor-grabbing">
                    <div class="flex items-start justify-between gap-2">
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $prio[$task->priority] ?? '' }}">{{ strtoupper($task->priority) }}</span>
                        <span class="text-[10px] font-semibold text-slate-400">{{ $task->progress }}%</span>
                    </div>
                    <a href="{{ route('internal.projects.tasks.show', $task->id) }}" class="mt-2 block text-sm font-semibold text-slate-800 hover:text-blue-700" onclick="event.stopPropagation()">{{ $task->title }}</a>
                    <p class="mt-1 text-[11px] text-slate-500">{{ $task->assignee_name }}</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-blue-600" style="width:{{ $task->progress }}%"></div></div>
                    <p class="mt-1 text-[10px] text-slate-400">{{ $task->due_date ? 'Deadline '.$task->due_date : 'Tanpa deadline' }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl" @click.outside="modal=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Task</h3><button @click="modal=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return createTask(event)">
                <input type="hidden" name="project_id" value="{{ $project->id ?? '' }}">
                <input name="title" placeholder="Judul task" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
                <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">@foreach($statuses as $s)<option value="{{ $s }}">{{ $labels[$s] }}</option>@endforeach</select>
                <select name="priority" class="rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                <select name="assignee_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'users']) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u['id'] }}">{{ $u['name'] }}</option>@endforeach</select>
                <input name="due_date" type="date" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="start_date" type="date" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="estimated_hours" type="number" step="0.5" placeholder="Estimasi jam" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <textarea name="description" rows="3" placeholder="Deskripsi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Task</button>
            </form>
        </div>
    </div>
</div>

<script>
let draggedTask = null;
function dragTask(e){ draggedTask = e.target.closest('[data-task-id]'); e.dataTransfer.effectAllowed='move'; }
async function dropTask(e){
    e.preventDefault();
    const zone = e.target.closest('[data-status]');
    const list = zone ? zone.querySelector('[data-dropzone]') : null;
    if (!zone || !draggedTask || !list) return;
    const status = zone.getAttribute('data-status');
    const id = draggedTask.getAttribute('data-task-id');
    list.appendChild(draggedTask);
    try {
        const d = await postJson('{{ url('internal/project-management/tasks') }}/' + id + '/move', {status: status});
        iaToast(d.message);
    } catch(err) { iaToast(err.message, false); setTimeout(()=>iaReload(), 700); }
    draggedTask = null;
}
async function createTask(e){
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.project_id = Number(payload.project_id);
    payload.assignee_id = payload.assignee_id ? Number(payload.assignee_id) : null;
    try { const d = await postJson('{{ route('internal.projects.tasks.store') }}', payload); iaToast(d.message); setTimeout(()=>iaReload(),500); }
    catch(err){ iaToast(err.message,false); }
    return false;
}
</script>
@endsection
