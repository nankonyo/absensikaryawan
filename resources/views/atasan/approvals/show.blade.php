<x-app-layout>
    <x-slot name="header">{{ __('ui.leave_request_review') }}</x-slot>
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('atasan.approvals.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_leave') }}</a>
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

            <!-- Employee Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-200">
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.employee') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $leave->user->employee_id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.position') }}</p>
                    <p class="text-gray-900 font-medium">{{ $leave->user->position }}</p>
                </div>
            </div>

            <!-- Request Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
            </div>

            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">{{ __('ui.reason') }}</p>
                <p class="text-gray-900">{{ $leave->reason }}</p>
            </div>

            @if($leave->attachment)
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-2">{{ __('ui.attachment') }}</p>
                    <a href="{{ Storage::url($leave->attachment) }}" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"></path>
                        </svg>
                        {{ __('ui.download_attachment') }}
                    </a>
                </div>
            @endif

            <!-- Approval Actions -->
            @if($leave->status === 'pending')
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.review_request') }}</h3>
                    
                    <form id="approvalForm" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.notes_optional') }}</label>
                            <textarea name="notes" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" 
                                    onclick="submitApproval('{{ route('atasan.approvals.approve', $leave) }}')"
                                    class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                {{ __('ui.approve') }}
                            </button>
                            <button type="button" 
                                    onclick="submitApproval('{{ route('atasan.approvals.reject', $leave) }}')"
                                    class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                {{ __('ui.reject') }}
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="border-t border-gray-200 pt-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('ui.reviewed_by') }}</p>
                            <p class="text-gray-900 font-medium">{{ $leave->approver?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('ui.reviewed_at') }}</p>
                            <p class="text-gray-900 font-medium">{{ $leave->approved_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('ui.verifier') }}</p>
                            <p class="text-gray-900 font-medium">{{ $leave->verifier?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('ui.verified_at') }}</p>
                            <p class="text-gray-900 font-medium">{{ $leave->verified_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                    @if($leave->notes)
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 mb-2">{{ __('ui.notes') }}</p>
                            <p class="text-gray-900">{{ $leave->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        function submitApproval(action) {
            const form = document.getElementById('approvalForm');
            form.action = action;
            form.submit();
        }
    </script>
</x-app-layout>
