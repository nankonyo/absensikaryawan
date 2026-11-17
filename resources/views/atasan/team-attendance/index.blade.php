<x-app-layout>
    <x-slot name="header">{{ __('ui.team_attendance') }}</x-slot>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.team_attendance') }}</h2>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="department_id" onchange="this.form.requestSubmit()"
                    class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                <option value="">{{ __('ui.all_departments') }}</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) $departmentId === (string) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            @if($departmentId)
                <select name="unit_id" onchange="this.form.requestSubmit()"
                        class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="">{{ __('ui.all_units') }}</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            @endif
            <input type="date" name="date" value="{{ $date }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                {{ __('ui.filter') }}
            </button>
            @if($departmentId)
                <a href="{{ route($routePrefix.'.index') }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                    {{ __('ui.reset') }}
                </a>
            @endif
        </form>
    </div>

    @forelse($groupedTeam as $departmentName => $members)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-3 bg-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">{{ $departmentName }}</h3>
                <span class="text-xs text-gray-500">{{ $members->count() }} {{ __('ui.employees') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-view">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.employee') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.role') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_in') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_out') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($members as $member)
                            @php($attendance = $attendances->get($member->id))
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                            {{ substr($member->name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $member->positionMaster?->name ?? $member->position }}
                                                @if($member->unit)
                                                    &middot; {{ $member->unit->name }}
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ __('ui.supervisor') }}: {{ $member->supervisor?->name ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                        {{ $member->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $attendance?->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance?->status === 'pending')
                                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ __('ui.statuses.pending') }}
                                        </span>
                                    @elseif($attendance?->status === 'invalid')
                                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ __('ui.statuses.invalid') }}
                                        </span>
                                    @elseif(!$attendance || !$attendance->check_in)
                                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ __('ui.statuses.absent') }}
                                        </span>
                                    @elseif($attendance->late_duration > 0)
                                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ $attendance->lateDurationLabel() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ __('ui.on_time') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route($routePrefix.'.show', $member) }}"
                                       class="text-blue-600 hover:underline">{{ __('ui.view_history') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-6 text-center text-sm text-gray-500">
            {{ __('ui.no_team_members') }}
        </div>
    @endforelse
</x-app-layout>
