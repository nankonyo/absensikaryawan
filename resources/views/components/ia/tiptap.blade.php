@props(['value' => '', 'placeholder' => 'Tulis di sini...', 'label' => null, 'error' => null])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100"
         x-data="tiptapEditor({ content: @js($value ?? ''), placeholder: @js($placeholder) })">
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-100 bg-slate-50 px-2 py-1.5 text-slate-600">
            <button type="button" class="rounded px-2 py-1 text-xs font-bold hover:bg-slate-200" @click="editor.chain().focus().toggleBold().run()">B</button>
            <button type="button" class="rounded px-2 py-1 text-xs italic hover:bg-slate-200" @click="editor.chain().focus().toggleItalic().run()">I</button>
            <button type="button" class="rounded px-2 py-1 text-xs line-through hover:bg-slate-200" @click="editor.chain().focus().toggleStrike().run()">S</button>
            <span class="mx-1 h-4 w-px bg-slate-200"></span>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().toggleBulletList().run()">• List</button>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().toggleOrderedList().run()">1. List</button>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().toggleBlockquote().run()">Quote</button>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().undo().run()">↶</button>
            <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-200" @click="editor.chain().focus().redo().run()">↷</button>
        </div>

        <div x-ref="editor" class="tiptap"></div>
        <input type="hidden" x-ref="input" {{ $attributes->except(['value', 'placeholder', 'label', 'error'])->merge(['value' => $value]) }}>
    </div>

    @if($error)
        <p class="text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
