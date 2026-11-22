<x-app-layout>
    <x-slot name="header">{{ __('ui.holiday_calendar') }}</x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.holiday_calendar') }}</h2>
        <p class="text-sm text-gray-500">{{ __('ui.holiday_calendar_help') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.holidays.index', ['month' => $prevMonth]) }}"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                           aria-label="{{ __('ui.previous_month') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <a href="{{ route('admin.holidays.index', ['month' => $nextMonth]) }}"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                           aria-label="{{ __('ui.next_month') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $monthStart->translatedFormat('F Y') }}
                    </h3>

                    <a href="{{ route('admin.holidays.index', ['month' => now()->format('Y-m')]) }}"
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        {{ __('ui.this_month') }}
                    </a>
                </div>

                <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2">
                    @foreach(__('ui.weekdays') as $weekday)
                        <div class="py-2 text-center text-[11px] sm:text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1 sm:gap-2">
                    @foreach($days as $day)
                        @php
                            $key = $day->toDateString();
                            $holiday = $holidays->get($key);
                            $inMonth = $day->format('Y-m') === $monthStart->format('Y-m');
                            $isToday = $day->isToday();
                            $badge = match ($holiday?->type) {
                                'national' => 'bg-red-100 text-red-700 border-red-200',
                                'company' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'collective' => 'bg-blue-100 text-blue-700 border-blue-200',
                                default => '',
                            };
                            $href = $holiday
                                ? route('admin.holidays.index', ['month' => $day->format('Y-m'), 'edit' => $holiday->id])
                                : route('admin.holidays.index', ['month' => $day->format('Y-m'), 'date' => $key]);
                        @endphp

                        <a href="{{ $href }}"
                           class="min-h-20 sm:min-h-24 rounded-lg border p-1.5 sm:p-2 transition hover:border-blue-400
                                  {{ $inMonth ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100 text-gray-400' }}
                                  {{ $holiday && $holiday->is_active ? 'ring-1 ring-inset '.$badge : '' }}">
                            <div class="flex items-center justify-between gap-1">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold
                                             {{ $isToday ? 'bg-blue-600 text-white' : '' }}">
                                    {{ $day->day }}
                                </span>
                                @if($holiday && ! $holiday->is_active)
                                    <span class="text-[10px] text-gray-400">{{ __('ui.inactive') }}</span>
                                @endif
                            </div>

                            @if($holiday)
                                <div class="mt-1 text-[10px] sm:text-[11px] font-medium leading-tight line-clamp-3 {{ $holiday->is_active ? '' : 'text-gray-400 line-through' }}">
                                    {{ $holiday->name }}
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-3 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-400"></span>{{ __('ui.holiday_types.national') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-400"></span>{{ __('ui.holiday_types.company') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-400"></span>{{ __('ui.holiday_types.collective') }}</span>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $editing ? __('ui.edit_holiday') : __('ui.add_holiday') }}
                </h3>

                <form action="{{ $editing ? route('admin.holidays.update', $editing) : route('admin.holidays.store') }}"
                      method="POST" class="space-y-4">
                    @csrf
                    @if($editing)
                        @method('PUT')
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.holiday_date') }} *</label>
                        <input type="date" name="date" value="{{ old('date', $formDate) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.holiday_name') }} *</label>
                        <input type="text" name="name" value="{{ old('name', $editing?->name) }}" required
                               placeholder="{{ __('ui.holiday_name_placeholder') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.holiday_type') }} *</label>
                        <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach(\App\Models\Holiday::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('type', $editing?->type ?? 'national') === $type)>
                                    {{ __('ui.holiday_types.'.$type) }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        {{ __('ui.is_active') }}
                    </label>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            {{ $editing ? __('ui.update') : __('ui.save') }}
                        </button>
                        @if($editing)
                            <a href="{{ route('admin.holidays.index', ['month' => $monthStart->format('Y-m')]) }}"
                               class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg">
                                {{ __('ui.cancel') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.holidays_this_month') }}</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('ui.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('ui.holiday_name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('ui.holiday_type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('ui.is_active') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($monthHolidays as $holiday)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $holiday->date->translatedFormat('d F Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $holiday->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $holiday->typeLabel() }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($holiday->is_active)
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.active') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <x-action-menu>
                                    <a href="{{ route('admin.holidays.index', ['month' => $monthStart->format('Y-m'), 'edit' => $holiday->id]) }}"
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ __('ui.edit') }}
                                    </a>
                                    <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                                                onclick="return confirm('{{ __('ui.confirm_title') }}')">
                                            {{ __('ui.delete') }}
                                        </button>
                                    </form>
                                </x-action-menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">{{ __('ui.no_holidays') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
