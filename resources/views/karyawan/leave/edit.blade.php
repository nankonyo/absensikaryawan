<x-app-layout>
    <x-slot name="header">{{ __('ui.edit_leave_request') }}</x-slot>
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('karyawan.leaves.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_leave') }}</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.edit_leave_request') }}</h2>

            <form action="{{ route('karyawan.leaves.update', $leave) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.start_date') }} *</label>
                            <input type="date" name="start_date" value="{{ old('start_date', $leave->start_date->format('Y-m-d')) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @error('start_date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.end_date') }} *</label>
                            <input type="date" name="end_date" value="{{ old('end_date', $leave->end_date->format('Y-m-d')) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @error('end_date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.leave_type') }} *</label>
                        <select name="leave_type_id" id="leave_type_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('ui.select_type') }}</option>
                            @foreach(['sakit', 'izin', 'cuti'] as $category)
                                @php $group = ($leaveTypes ?? collect())->where('category', $category); @endphp
                                @if($group->isNotEmpty())
                                    <optgroup label="{{ __('ui.leave_categories')[$category] }}">
                                        @foreach($group as $leaveType)
                                            <option value="{{ $leaveType->id }}"
                                                    data-requires-attachment="{{ $leaveType->requires_attachment ? '1' : '0' }}"
                                                    data-requires-reason="{{ $leaveType->requires_reason ? '1' : '0' }}"
                                                    {{ (string) old('leave_type_id', $leave->leave_type_id) === (string) $leaveType->id ? 'selected' : '' }}>
                                                {{ $leaveType->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                            @if(($leaveTypes ?? collect())->isEmpty())
                                <option value="" disabled>{{ __('ui.no_leave_types_available') }}</option>
                            @endif
                        </select>
                        @error('leave_type_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div id="reason_wrapper">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.reason') }} *</label>
                        <textarea name="reason" id="reason" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('reason', $leave->reason) }}</textarea>
                        @error('reason')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">{{ __('ui.max_500_chars') }}</p>
                    </div>

                    <!-- Current Attachment -->
                    @if($leave->attachment)
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-700 mb-2">{{ __('ui.current_attachment') }}</p>
                            <a href="{{ Storage::disk('public')->url($leave->attachment) }}" target="_blank" 
                               class="text-blue-600 hover:underline text-sm">
                                {{ __('ui.view_attachment') }}
                            </a>
                        </div>
                    @endif

                    <!-- New Attachment -->
                    <div id="attachment_wrapper">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $leave->attachment ? __('ui.replace_attachment_optional') : __('ui.attachment') }} *
                        </label>
                        <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('attachment')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">{{ __('ui.attachment_formats') }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <a href="{{ route('karyawan.leaves.index') }}" 
                       class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                        {{ __('ui.cancel') }}
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        {{ __('ui.update_request') }}
                    </button>
                </div>
            </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const type = document.getElementById('leave_type_id');
            if (!type) return;

            const reason = document.getElementById('reason');
            const reasonWrapper = document.getElementById('reason_wrapper');
            const attachment = document.getElementById('attachment');
            const attachmentWrapper = document.getElementById('attachment_wrapper');
            const hasExistingAttachment = {{ $leave->attachment ? 'true' : 'false' }};

            function sync() {
                const option = type.options[type.selectedIndex];
                const needsReason = option?.dataset.requiresReason === '1';
                const needsAttachment = option?.dataset.requiresAttachment === '1';

                reason.required = needsReason;
                attachment.required = needsAttachment && !hasExistingAttachment;
                reasonWrapper.classList.toggle('hidden', !needsReason);
                attachmentWrapper.classList.toggle('hidden', !needsAttachment);
            }

            type.addEventListener('change', sync);
            sync();
        });
    </script>
</x-app-layout>
