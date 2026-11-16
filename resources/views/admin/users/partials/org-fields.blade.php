<div class="contents">
    <!-- Department: managers and below own a department -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.department') }}</label>
        <select name="department_id" x-model="department" @change="departmentChanged()" :disabled="!needsDepartment"
                x-init="$nextTick(() => $el.value = department)"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500">
            <option value="">{{ __('ui.none') }}</option>
            <template x-for="department in departments" :key="department.id">
                <option :value="String(department.id)" x-text="department.name"></option>
            </template>
        </select>
        @error('department_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Unit: only supervisor and staff work inside one; managers own the whole department -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.unit') }}</label>
        <select name="unit_id" x-model="unit" @change="unitChanged()" :disabled="!unitEnabled"
                x-init="$nextTick(() => $el.value = unit)"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500">
            <option value="">{{ __('ui.none') }}</option>
            <template x-for="unit in availableUnits" :key="unit.id">
                <option :value="String(unit.id)" x-text="unit.name"></option>
            </template>
        </select>
        @error('unit_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Position: filtered by the selected department and unit -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.position_required') }}</label>
        <select name="position_id" x-model="position" :disabled="!role"
                x-init="$nextTick(() => $el.value = position)"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500">
            <option value="">{{ __('ui.select_position') }}</option>
            <template x-for="position in availablePositions" :key="position.id">
                <option :value="String(position.id)" x-text="position.name"></option>
            </template>
        </select>
        @error('position_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
