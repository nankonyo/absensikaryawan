<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.my_team') }}</h3>
        <span class="text-sm text-gray-500">{{ __('ui.employees') }}: {{ $groupedTeam->flatten()->count() }}</span>
    </div>

    @forelse($groupedTeam as $departmentName => $members)
        <div class="border-t border-gray-200 first:border-t-0">
            <div class="px-6 py-3 bg-gray-50 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900">{{ $departmentName }}</h4>
                <span class="text-xs text-gray-500">{{ $members->count() }} {{ __('ui.employees') }}</span>
            </div>

            <ul class="divide-y divide-gray-200">
                @foreach($members as $member)
                    <li class="px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                {{ substr($member->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $member->positionMaster?->name ?? $member->position }}
                                    @if($member->unit)
                                        &middot; {{ $member->unit->name }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                {{ $member->roleLabel() }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ __('ui.supervisor') }}: {{ $member->supervisor?->name ?? '-' }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="px-6 py-6 text-center text-sm text-gray-500">{{ __('ui.no_team_members') }}</p>
    @endforelse
</div>
