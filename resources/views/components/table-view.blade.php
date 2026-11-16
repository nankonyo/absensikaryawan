@props(['headers' => [], 'empty' => null, 'emptyColspan' => null])

@php
    $colspan = $emptyColspan ?? count($headers);
@endphp

<div {{ $attributes->merge(['class' => 'max-w-full overflow-x-auto rounded-lg bg-white shadow']) }}>
    <table class="w-full table-view">
        <thead class="bg-gray-50">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            {{ $slot }}

            @if ($empty && $slot->isEmpty())
                <tr>
                    <td colspan="{{ $colspan }}" class="table-empty px-6 py-4 text-center text-sm text-gray-500">{{ $empty }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
