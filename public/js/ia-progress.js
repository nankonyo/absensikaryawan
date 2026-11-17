(function () {
    if (window.__iaProgressReady) {
        return;
    }
    window.__iaProgressReady = true;

    let value = 0;
    let visible = false;
    let timer = null;

    const ensureBar = () => {
        let el = document.getElementById('ia-global-progress');

        if (!el) {
            el = document.createElement('div');
            el.id = 'ia-global-progress';
            el.style.cssText =
                'position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;' +
                'background:rgba(15,23,42,.38);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);' +
                'pointer-events:auto;';
            el.innerHTML =
                '<div style="width:min(320px,90vw);background:#fff;border-radius:18px;padding:22px 22px 18px;' +
                'box-shadow:0 25px 60px rgba(15,23,42,.35);text-align:center;font-family:inherit;">' +
                '<div data-caption style="font-size:12px;font-weight:600;color:#475569;margin-bottom:12px;">Memproses data...</div>' +
                '<div style="height:9px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
                '<div data-bar style="height:100%;width:0;background:#2563eb;border-radius:999px;transition:width .18s ease;"></div>' +
                '</div>' +
                '<div data-label style="margin-top:12px;font-size:24px;font-weight:800;color:#0f172a;letter-spacing:-.02em;">0%</div>' +
                '</div>';
            document.body.appendChild(el);
        }

        return el;
    };

    const render = () => {
        if (!document.body) return;

        const el = ensureBar();
        el.style.display = visible ? 'flex' : 'none';
        el.querySelector('[data-bar]').style.width = value + '%';
        el.querySelector('[data-label]').textContent = Math.round(value) + '%';
    };

    window.iaSetProgress = function (nextValue, nextVisible) {
        value = Math.max(0, Math.min(100, Number(nextValue) || 0));
        visible = nextVisible !== false;
        render();
    };

    window.iaStartProgress = function (caption) {
        const el = ensureBar();
        el.querySelector('[data-caption]').textContent = caption || 'Memproses data...';
        visible = true;
        value = 6;
        render();
        clearInterval(timer);
        timer = setInterval(() => {
            if (value < 92) {
                value = Math.min(92, value + Math.ceil(Math.random() * 12));
                render();
            }
        }, 240);
    };

    window.iaFinishProgress = function () {
        clearInterval(timer);
        value = 100;
        visible = true;
        render();
        setTimeout(() => {
            visible = false;
            value = 0;
            render();
        }, 450);
    };

    window.iaFailProgress = function () {
        clearInterval(timer);
        visible = false;
        value = 0;
        render();
    };

    document.addEventListener(
        'submit',
        function (event) {
            const form = event.target;
            if (!form || form.hasAttribute('data-no-progress')) {
                return;
            }

            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET' && document.getElementById('ia-page-body')) {
                return;
            }

            window.iaStartProgress();
        },
        true,
    );

    document.addEventListener(
        'change',
        function (event) {
            const el = event.target;
            if (!el || !el.form) return;
            if (el.hasAttribute('onchange') || el.hasAttribute('data-auto-submit')) {
                const method = (el.form.getAttribute('method') || 'GET').toUpperCase();
                if (method === 'GET' && document.getElementById('ia-page-body')) {
                    return;
                }
                window.iaStartProgress();
            }
        },
        true,
    );
})();
