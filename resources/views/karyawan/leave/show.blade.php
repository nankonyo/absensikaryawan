<x-app-layout>
    <x-slot name="header">{{ __('ui.leave_details') }}</x-slot>
    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('karyawan.leaves.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_leave') }}</a>
            @if($leave->status === 'pending')
                <div class="flex gap-2">
                    <a href="{{ route('karyawan.leaves.edit', $leave) }}" 
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                        {{ __('ui.edit') }}
                    </a>
                    <form action="{{ route('karyawan.leaves.destroy', $leave) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('{{ __('ui.confirm_title') }}')"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            {{ __('ui.delete') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('ui.leave_request') }}</h2>
                @if($leave->status === 'pending')
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.pending_approval') }}</span>
                @elseif($leave->status === 'approved')
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.approved') }}</span>
                @else
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.rejected') }}</span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.start_date') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->start_date->format('d/m/Y') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.end_date') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->end_date->format('d/m/Y') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.duration') }}</p>
                    <p class="text-gray-900 font-medium">{{ __('ui.days', ['count' => $leave->days]) }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.leave_type') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->type_label }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.requested_on') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->created_at->format('d/m/Y H:i') }}</p>
                </div>

                @if($leave->approved_by)
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.approver') }}</p>
                        <p class="text-gray-900 font-medium">{{ $leave->approver?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.approved_at') }}</p>
                        <p class="text-gray-900 font-medium">{{ $leave->approved_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                @endif

                @if($leave->verified_by)
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.verifier') }}</p>
                        <p class="text-gray-900 font-medium">{{ $leave->verifier?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.verified_at') }}</p>
                        <p class="text-gray-900 font-medium">{{ $leave->verified_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <p class="text-sm text-gray-500 mb-2">{{ __('ui.reason') }}</p>
                <p class="text-gray-900">{{ $leave->reason }}</p>
            </div>

            @if($leave->attachment)
                <div class="mt-6">
                    <p class="text-sm text-gray-500 mb-2">{{ __('ui.attachment') }}</p>
                    <a href="{{ Storage::disk('public')->url($leave->attachment) }}" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"></path>
                        </svg>
                        {{ __('ui.download_attachment') }}
                    </a>
                </div>
            @endif

            @if($leave->notes)
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-500 mb-2">{{ __('ui.notes_from_supervisor') }}</p>
                    <p class="text-gray-900">{{ $leave->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
