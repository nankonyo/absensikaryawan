<style>
    .ia-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .toggle-sw { appearance: none; width: 2.25rem; height: 1.25rem; border-radius: 9999px; background: #cbd5e1; position: relative; cursor: pointer; transition: background .2s; vertical-align: middle; }
    .toggle-sw:checked { background: #D34B52; }
    .toggle-sw::before { content: ''; position: absolute; top: 2px; left: 2px; width: 1rem; height: 1rem; border-radius: 50%; background: #fff; transition: transform .2s; }
    .toggle-sw:checked::before { transform: translateX(1rem); }
    .tour-highlight { outline: 3px solid #6366f1; outline-offset: 3px; border-radius: .5rem; }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script>
    function iaToast(message, ok = true) {
        if (window.showToast) {
            window.showToast(message, ok ? 3000 : 5000);
        } else {
            alert(message);
        }
    }

    function iaReload() {
        window.location.reload();
    }

    function iaCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function iaParse(response) {
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = json.errors ? Object.values(json.errors).flat()[0] : null;
            throw new Error(json.message || errors || 'Request gagal.');
        }
        return json;
    }

    async function postJson(url, data) {
        if (window.iaStartProgress) window.iaStartProgress();
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': iaCsrf(),
                },
                body: JSON.stringify(data),
            });
            return await iaParse(response);
        } catch (error) {
            if (window.iaFailProgress) window.iaFailProgress();
            throw error;
        } finally {
            if (window.iaFinishProgress) window.iaFinishProgress();
        }
    }

    async function postForm(url, formData) {
        if (window.iaStartProgress) window.iaStartProgress();
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': iaCsrf(),
                },
                body: formData,
            });
            return await iaParse(response);
        } catch (error) {
            if (window.iaFailProgress) window.iaFailProgress();
            throw error;
        } finally {
            if (window.iaFinishProgress) window.iaFinishProgress();
        }
    }
</script>
