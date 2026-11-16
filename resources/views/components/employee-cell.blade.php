@props(['user'])

<div class="flex items-center">
    @if($user->avatar_url)
        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover">
    @else
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
            {{ substr($user->name, 0, 1) }}
        </div>
    @endif
    <div class="ml-3">
        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
        <p class="text-xs text-gray-500">{{ $user->employee_id }}</p>
        <p class="text-xs text-gray-500">{{ $user->department?->name ?? __('ui.empty') }}</p>
    </div>
</div>
