<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.work_mode') }}</label>
    <select name="work_mode" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <option value="WFO" @selected(old('work_mode', $workMode ?? 'WFO') === 'WFO')>{{ __('ui.work_mode_wfo') }}</option>
        <option value="WFH" @selected(old('work_mode', $workMode ?? 'WFO') === 'WFH')>{{ __('ui.work_mode_wfh') }}</option>
        <option value="WFA" @selected(old('work_mode', $workMode ?? 'WFO') === 'WFA')>{{ __('ui.work_mode_wfa') }}</option>
    </select>
    <p class="text-xs text-gray-500 mt-1">{{ __('ui.work_mode_help') }}</p>
    @error('work_mode')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
