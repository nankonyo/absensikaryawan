<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-semibold text-gray-900">@yield('title')</p>
            @hasSection('subtitle')
                <p class="text-xs text-gray-500">@yield('subtitle')</p>
            @endif
        </div>
    </x-slot>

    @include('internal-app.partials.ia-assets')

    @yield('body')

    @stack('scripts')
</x-app-layout>
