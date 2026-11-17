(function () {
    if (window.__iaSpaReady) {
        return;
    }
    window.__iaSpaReady = true;

    window.iaSkeletonShow = function () {
        const overlay = document.getElementById('ia-skeleton-overlay');
        if (overlay) overlay.classList.remove('hidden');
    };

    window.iaSkeletonHide = function () {
        const overlay = document.getElementById('ia-skeleton-overlay');
        if (overlay) overlay.classList.add('hidden');
    };

    const registerStore = () => {
        if (!window.Alpine || window.Alpine.store('spa')) {
            return;
        }

        window.Alpine.store('spa', {
            loading: false,
            currentUrl: window.location.pathname,

            isActive(url) {
                try {
                    return this.currentUrl === new URL(url, window.location.origin).pathname;
                } catch (error) {
                    return false;
                }
            },

            async navigate(url, push = true) {
                if (!url) return;
                if (push !== false && url === window.location.href) return;

                const currentContent = document.getElementById('ia-page-content');
                if (!currentContent) {
                    window.location.href = url;
                    return;
                }

                this.loading = true;
                if (window.iaSkeletonShow) window.iaSkeletonShow();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) throw new Error('HTTP ' + response.status);

                    const finalUrl = response.url || url;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('ia-page-content');

                    if (!newContent) {
                        window.location.href = url;
                        return;
                    }

                    currentContent.innerHTML = newContent.innerHTML;

                    if (doc.title) document.title = doc.title;

                    if (push !== false) {
                        history.pushState({ url: finalUrl }, '', finalUrl);
                    }

                    this.currentUrl = new URL(finalUrl, window.location.origin).pathname;

                    Array.from(newBody.querySelectorAll('script')).forEach((oldScript) => {
                        const script = document.createElement('script');
                        Array.from(oldScript.attributes).forEach((attribute) => {
                            script.setAttribute(attribute.name, attribute.value);
                        });
                        script.textContent = oldScript.textContent;
                        document.body.appendChild(script);
                        document.body.removeChild(script);
                    });

                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(currentContent);
                    }

                    window.dispatchEvent(new CustomEvent('spa:navigated', { detail: { url: this.currentUrl } }));
                } catch (error) {
                    window.location.href = url;
                } finally {
                    this.loading = false;
                    if (window.iaSkeletonHide) window.iaSkeletonHide();
                }
            },

            reload() {
                return this.navigate(window.location.href, false);
            },
        });
    };

    if (window.Alpine) {
        registerStore();
    } else {
        document.addEventListener('alpine:init', registerStore);
    }

    window.addEventListener('popstate', function () {
        const store = window.Alpine && window.Alpine.store('spa');
        if (store) {
            store.navigate(window.location.href, false);
        } else {
            window.location.reload();
        }
    });

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-spa')) {
            return;
        }

        let url;
        try {
            url = new URL(link.href, window.location.origin);
        } catch (error) {
            return;
        }

        if (url.origin !== window.location.origin) return;
        if (/(?:^|\/)(?:logout|signout|get-signout)(?:$|\?|\/)/.test(url.pathname)) return;
        if (!document.getElementById('ia-page-body')) return;

        const store = window.Alpine && window.Alpine.store('spa');
        if (!store) return;

        event.preventDefault();
        store.navigate(link.href);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'GET') return;
        if (!document.getElementById('ia-page-body')) return;

        event.preventDefault();

        let url;
        try {
            url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        } catch (error) {
            return;
        }

        new FormData(form).forEach(function (value, key) {
            if (value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        const store = window.Alpine && window.Alpine.store('spa');
        if (store) {
            store.navigate(url.toString());
        } else {
            window.location.href = url.toString();
        }
    });
})();
