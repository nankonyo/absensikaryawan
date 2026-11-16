@if($activeAttributes->isNotEmpty())
    <div class="md:col-span-2 border-t pt-6 mt-2">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.additional_attributes') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($activeAttributes as $attr)
                @php
                    $fieldValue = old("attributes.{$attr->id}", $user?->attributeValues->where('user_attribute_id', $attr->id)->first()?->value);
                @endphp
                <div @if($attr->type === 'textarea') class="md:col-span-2" @endif>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $attr->label }}</label>
                    @switch($attr->type)
                        @case('date')
                            <input type="date" name="attributes[{{ $attr->id }}]" value="{{ $fieldValue }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @break
                        @case('textarea')
                            <textarea name="attributes[{{ $attr->id }}]" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ $fieldValue }}</textarea>
                            @break
                        @case('select')
                            <select name="attributes[{{ $attr->id }}]"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('ui.none') }}</option>
                                @foreach($attr->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected($fieldValue === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @break
                        @default
                            <input type="text" name="attributes[{{ $attr->id }}]" value="{{ $fieldValue }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @endswitch
                </div>
            @endforeach
        </div>
    </div>
@endif
