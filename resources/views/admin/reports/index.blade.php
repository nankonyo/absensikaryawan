<x-app-layout>
    <x-slot name="header">{{ __('ui.reports') }}</x-slot>
    <div class="max-w-2xl">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.generate_reports') }}</h2>

        <div class="grid grid-cols-1 gap-6">
            <!-- Attendance Report -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('ui.attendance_report') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('ui.attendance_report_desc') }}</p>
                
                <form action="{{ route('admin.reports.attendance') }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <input type="date" name="start_date" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <input type="date" name="end_date" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <select name="format" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                            {{ __('ui.generate') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
