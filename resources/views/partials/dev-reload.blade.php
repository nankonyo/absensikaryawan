@if (config('app.asset_mode') === 'cdn' && app()->environment('local') && Route::has('dev.version'))
    <script>
        (() => {
            if (window.__iaDevReloadStarted) {
                return;
            }
            window.__iaDevReloadStarted = true;

            const STORAGE_KEY = 'ia-dev-version';
            const INTERVAL = 30000;
            const START_DELAY = 5000;

            let currentVersion = window.localStorage.getItem(STORAGE_KEY);
            let running = false;
            let failures = 0;
            let stopped = false;
            let timer = null;

            const stop = () => {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
            };

            const schedule = (delay = INTERVAL) => {
                if (stopped) return;
                stop();
                timer = setTimeout(checkVersion, delay);
            };

            const checkVersion = async () => {
                if (running) {
                    schedule();
                    return;
                }

                if (document.visibilityState === 'hidden') {
                    schedule();
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
                        stopped = true;
                        stop();
                    }
                } finally {
                    running = false;

                    if (!stopped) {
                        schedule();
                    }
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (stopped) return;

                if (document.visibilityState === 'hidden') {
                    stop();
                } else {
                    schedule(0);
                }
            });

            schedule(START_DELAY);
        })();
    </script>
@endif
