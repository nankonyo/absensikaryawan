@extends('internal-app.layouts.app')
@section('title', 'Gantt Chart')
@section('subtitle', 'Timeline proyek dan milestone')

@section('body')
<div class="ia-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">Project</label>
            <select onchange="if(this.value){location.href=this.value}" class="min-w-[220px] rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <option value="">— Pilih Project —</option>
                @foreach($projects as $p)
                <option value="{{ route('internal.projects.gantt', ['project_id'=>$p->id]) }}" @selected($project && $project->id==$p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('internal.projects.index') }}" class="text-xs font-semibold text-blue-600"><i class="fa-solid fa-plus mr-1"></i>Buat Project</a>
    </div>
    <div class="overflow-x-auto p-4">
        @if($projects->isEmpty())
        <p class="py-12 text-center text-sm text-slate-400">Belum ada project. <a href="{{ route('internal.projects.index') }}" class="font-semibold text-blue-600">Buat project</a> terlebih dahulu.</p>
        @else
        <div id="gantt-wrap" class="min-w-[800px]">
            <div id="gantt-head" class="mb-2 grid text-[10px] font-semibold text-slate-400"></div>
            <div id="gantt-body" class="space-y-2"></div>
        </div>
        @if($tasks->isEmpty())
        <p class="py-12 text-center text-sm text-slate-400">Belum ada task dengan tanggal mulai dan tenggat.</p>
        @endif
        @endif
    </div>
</div>
@php
$ganttData = $tasks->map(fn($t) => [
    'id' => $t->id, 'title' => $t->title, 'start' => $t->start_date, 'due' => $t->due_date, 'progress' => $t->progress,
    'status' => $t->status, 'milestone' => (bool) $t->is_milestone, 'assignee' => $t->assignee_name,
])->values();
@endphp
<script>
const ganttTasks = @json($ganttData);
const GANTT_TASK_BASE = '{{ url('internal/project-management/tasks') }}';
(function(){
    if (!ganttTasks.length) return;
    const dates = ganttTasks.flatMap(t => [new Date(t.start), new Date(t.due)]).filter(d=>!isNaN(d));
    const min = new Date(Math.min(...dates)), max = new Date(Math.max(...dates));
    const totalDays = Math.max(1, Math.round((max-min)/86400000)+1);
    const cols = Math.min(31, totalDays);
    const head = document.getElementById('gantt-head');
    head.style.gridTemplateColumns = '220px 1fr';
    head.innerHTML = '<div></div><div class="grid" style="grid-template-columns:repeat('+cols+',minmax(0,1fr))">'+
        Array.from({length:cols},(_,i)=>{const d=new Date(min.getTime()+i*86400000);return '<span class="border-l border-slate-100 px-1">'+d.getDate()+'/'+(d.getMonth()+1)+'</span>';}).join('')+'</div>';
    const body = document.getElementById('gantt-body');
    body.innerHTML = ganttTasks.map(t=>{
        const s = new Date(t.start), e = new Date(t.due);
        const left = Math.max(0, Math.round((s-min)/86400000)/totalDays*100);
        const width = Math.max(1, (Math.round((e-s)/86400000)+1)/totalDays*100);
        const color = t.status==='done'?'bg-emerald-500':(t.status==='in_progress'?'bg-blue-600':(t.status==='in_review'?'bg-amber-500':'bg-slate-400'));
        return '<div class="grid items-center gap-2" style="grid-template-columns:220px 1fr"><a href="'+GANTT_TASK_BASE+'/'+t.id+'" class="truncate text-xs font-semibold text-slate-700 hover:text-blue-700">'+(t.milestone?'<i class="fa-solid fa-flag text-amber-500 mr-1"></i>':'')+escapeHtml(t.title)+'</a><div class="relative h-8 rounded bg-slate-50"><div class="absolute top-2 h-4 rounded '+color+'" style="left:'+left+'%;width:'+width+'%" title="'+t.start+' s/d '+t.due+' ('+t.progress+'%)"></div></div></div>';
    }).join('');
})();
function escapeHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML}
</script>
@endsection
