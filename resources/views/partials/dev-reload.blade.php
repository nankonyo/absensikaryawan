@if (config('app.asset_mode') === 'cdn' && app()->environment('local') && Route::has('dev.version'))
    <script>
        (() => {
            if (window.__iaDevReloadStarted) {
                return;
            }
            window.__iaDevReloadStarted = true;

            const STORAGE_KEY = 'ia-dev-version';
            const INTERVAL = 10000;

            let currentVersion = window.localStorage.getItem(STORAGE_KEY);
            let running = false;
            let failures = 0;
            let timer = null;

            const checkVersion = async () => {
                if (running || document.visibilityState === 'hidden') {
                    return;
                }

                running = true;

                try {
                    const response = await fetch('{{ route('dev.version') }}', {
                        cache: 'no-store',
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    failures = 0;

                    if (!currentVersion) {
                        currentVersion = data.version;
                        window.localStorage.setItem(STORAGE_KEY, currentVersion);
                        return;
                    }

                    if (data.version !== currentVersion) {
                        window.localStorage.setItem(STORAGE_KEY, data.version);
                        window.location.reload();
                    }
                } catch (error) {
                    failures++;

                    if (failures >= 3) {
                        clearInterval(timer);
                    }
                } finally {
                    running = false;
                }
            };

            setTimeout(checkVersion, 3000);
            timer = setInterval(checkVersion, INTERVAL);
        })();
    </script>
@endif
