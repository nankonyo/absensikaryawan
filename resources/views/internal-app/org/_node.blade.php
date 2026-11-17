@php
    $uName = $node->user_id ? ($userNames[$node->user_id] ?? ('User #' . $node->user_id)) : null;
    $nodeData = ['id' => $node->id, 'name' => $node->name, 'parent_id' => $node->parent_id, 'user_id' => $node->user_id, 'sort' => $node->sort];
@endphp
<div class="org-item" data-id="{{ $node->id }}">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="drag-handle cursor-move rounded-lg bg-slate-100 px-2 py-1 text-slate-400" title="Drag untuk pindah/urutkan"><i class="fa-solid fa-grip-vertical"></i></span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-700"><i class="fa-solid fa-sitemap"></i></span>
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ $node->name }}</p>
                <p class="text-[11px] text-slate-500">{{ $uName ?: 'Belum ada user' }}</p>
            </div>
        </div>
        <div class="flex gap-1">
            <button onclick='editNode({{ Illuminate\Support\Js::from($nodeData) }})' class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Edit</button>
            <button onclick="deleteNode({{ $node->id }})" class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Hapus</button>
        </div>
    </div>
    <div class="org-children min-h-[8px] border-l-2 border-dashed border-slate-200 pl-6" data-parent="{{ $node->id }}">
        @foreach($children as $child)
            @include('internal-app.org._node', ['node' => $child['node'], 'children' => $child['children']])
        @endforeach
    </div>
</div>
