<x-app-layout>
    <x-slot name="header">{{ __('ui.reports') }}</x-slot>
    <div class="max-w-2xl">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.generate_reports') }}</h2>

        <div class="grid grid-cols-1 gap-6">
            <!-- Attendance Report -->
            <div class="bg-white rounded-lg shadow p-6"
                 x-data="{
                    startDate: '',
                    endDate: '',
                    format: 'pdf',
                    previewOpen: false,
                    previewLoading: false,
                    previewError: '',
                    previewUrl: '',
                    get isValid() {
                        return this.startDate !== '' && this.endDate !== '' && this.endDate >= this.startDate;
                    },
                    get dateHint() {
                        if (this.startDate === '' || this.endDate === '') {
                            return @js(__('ui.date_range_required'));
                        }

                        if (this.endDate < this.startDate) {
                            return @js(__('ui.invalid_date_range'));
                        }

                        return '';
                    },
                    async openPreview() {
                        if (!this.isValid || this.previewLoading) {
                            return;
                        }

                        this.previewOpen = true;
                        this.previewLoading = true;
                        this.previewError = '';
                        this.releasePreview();

                        const url = new URL(@js(route('admin.reports.attendance.preview')), window.location.origin);
                        url.searchParams.set('start_date', this.startDate);
                        url.searchParams.set('end_date', this.endDate);

                        try {
                            const response = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/pdf' },
                            });

                            if (!response.ok) {
                                throw new Error('HTTP ' + response.status);
                            }

                            const blob = await response.blob();
                            this.previewUrl = URL.createObjectURL(blob);
                        } catch (error) {
                            this.previewError = @js(__('ui.preview_failed'));
                        } finally {
                            this.previewLoading = false;
                        }
                    },
                    closePreview() {
                        this.previewOpen = false;
                        this.previewLoading = false;
                        this.previewError = '';
                        this.releasePreview();
                    },
                    releasePreview() {
                        if (this.previewUrl) {
                            URL.revokeObjectURL(this.previewUrl);
                            this.previewUrl = '';
                        }
                    }
                 }"
                 x-on:keydown.escape.window="previewOpen ? closePreview() : null"
                 x-effect="document.body.classList.toggle('overflow-hidden', previewOpen)">
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.start_date') }}</label>
                                <input id="start_date" type="date" name="start_date" required
                                       x-model="startDate"
                                       placeholder="{{ __('ui.start_date_placeholder') }}"
                                       aria-label="{{ __('ui.start_date_placeholder') }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.end_date') }}</label>
                                <input id="end_date" type="date" name="end_date" required
                                       x-model="endDate"
                                       placeholder="{{ __('ui.end_date_placeholder') }}"
                                       aria-label="{{ __('ui.end_date_placeholder') }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        <div>
                            <label for="format" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.format') }}</label>
                            <select id="format" name="format" required x-model="format"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                        <p class="text-xs text-amber-600" x-show="dateHint !== ''" x-text="dateHint"></p>
                        <div class="flex flex-col sm:flex-row gap-3 pt-1">
                            <button type="button" @click="openPreview()" :disabled="!isValid"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-blue-600 text-blue-700 hover:bg-blue-50 text-sm font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ __('ui.view') }}
                            </button>
                            <button type="submit" :disabled="!isValid"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('ui.download') }}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- PDF Preview Modal (full screen) -->
                <div x-show="previewOpen" x-cloak
                     class="fixed inset-0 z-50 flex h-screen w-screen flex-col bg-white"
                     role="dialog" aria-modal="true" aria-labelledby="report-preview-title">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 sm:px-6">
                        <h3 id="report-preview-title" class="text-lg font-semibold text-gray-900">{{ __('ui.report_preview') }}</h3>
                        <button type="button" @click="closePreview()"
                                class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                                aria-label="{{ __('ui.close') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="hidden sm:inline">{{ __('ui.close') }}</span>
                        </button>
                    </div>

                    <div class="relative min-h-0 flex-1 bg-gray-100">
                        <div x-show="previewLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-gray-600">
                            <svg class="w-8 h-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>{{ __('ui.preview_loading') }}</span>
                        </div>

                        <div x-show="!previewLoading && previewError !== ''"
                             class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-red-600">
                            <span x-text="previewError"></span>
                        </div>

                        <iframe x-show="!previewLoading && previewError === '' && previewUrl !== ''"
                                :src="previewUrl || 'about:blank'"
                                title="{{ __('ui.report_preview') }}"
                                class="h-full w-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
