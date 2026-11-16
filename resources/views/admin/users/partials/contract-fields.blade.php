@php
    $user = $user ?? null;
    $employmentStatus = old('employment_status', $user?->employment_status ?? 'permanent');
    $documentLabel = $employmentStatus === 'contract'
        ? __('ui.contract_document')
        : __('ui.permanent_document');
@endphp

<div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.employment_status') }} *</label>
    <select name="employment_status" required onchange="toggleContractFields(this)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <option value="permanent" @selected($employmentStatus === 'permanent')>{{ __('ui.employment_permanent') }}</option>
        <option value="contract" @selected($employmentStatus === 'contract')>{{ __('ui.employment_contract') }}</option>
    </select>
    @error('employment_status')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror

    <div id="contract-dates" @class(['mt-4 grid grid-cols-1 md:grid-cols-2 gap-4', 'hidden' => $employmentStatus !== 'contract'])>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.contract_start_date') }} *</label>
            <input type="date" name="contract_start_date"
                   value="{{ old('contract_start_date', $user?->contract_start_date?->format('Y-m-d')) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            @error('contract_start_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.contract_end_date') }} *</label>
            <input type="date" name="contract_end_date"
                   value="{{ old('contract_end_date', $user?->contract_end_date?->format('Y-m-d')) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            @error('contract_end_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-4">
        <label id="contract-document-label" class="block text-sm font-medium text-gray-700 mb-2">{{ $documentLabel }}</label>
        @if($user?->contract_document_url)
            <a href="{{ $user->contract_document_url }}" target="_blank" class="mb-2 inline-block text-sm text-blue-600 hover:underline">
                {{ __('ui.view_contract_document') }}
            </a>
        @endif
        <input type="file" name="contract_document" accept=".pdf,.jpg,.jpeg,.png"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        <p class="text-xs text-gray-500 mt-1">{{ __('ui.contract_document_help') }}</p>
        @error('contract_document')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
    function toggleContractFields(select) {
        const isContract = select.value === 'contract';

        document.getElementById('contract-dates').classList.toggle('hidden', !isContract);
        document.getElementById('contract-document-label').textContent = isContract
            ? @json(__('ui.contract_document'))
            : @json(__('ui.permanent_document'));
    }
</script>
