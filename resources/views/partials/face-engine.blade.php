@auth
@if (config('app.asset_mode') === 'cdn')
    <script>window.FACE_MODEL_URL = @json(asset('models'));</script>
    <script defer src="{{ asset('cdn/face-api.min.js') }}?v={{ @filemtime(public_path('cdn/face-api.min.js')) }}"></script>
    <script defer src="{{ asset('cdn/face-match.js') }}?v={{ @filemtime(public_path('cdn/face-match.js')) }}"></script>
    <script>
        (function () {
            var started = false;

            var warm = function () {
                if (started) {
                    return;
                }

                if (!window.FaceMatch || !window.faceapi) {
                    setTimeout(warm, 150);

                    return;
                }

                started = true;

                // Hanya detector yang dibutuhkan untuk auto-shot.
                // Verifikasi identitas dilakukan server-side.
                window.FaceMatch.loadDetector().catch(function () {});
            };

            if (document.readyState === 'complete') {
                warm();
            } else {
                window.addEventListener('load', warm, { once: true });
            }
        })();
    </script>
@endif
@endauth
