(function () {
    const overlay = document.getElementById('global-loader');
    if (!overlay) return;

    const showLoader = () => {
        overlay.classList.add('is-active');
        document.body.classList.add('fx-loading');
    };

    const hideLoader = () => {
        overlay.classList.remove('is-active');
        document.body.classList.remove('fx-loading');
    };

    window.showLoader = showLoader;
    window.hideLoader = hideLoader;

    // Initial load
    showLoader();
    window.addEventListener('load', () => hideLoader());

    // Form submissions
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form && form.matches('[data-no-loader]')) return;
        setTimeout(() => {
            if (!e.defaultPrevented) showLoader();
        }, 0);
    }, true);

    // fetch wrapper
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function (...args) {
            const options = args[1] || {};
            const headers = options.headers instanceof Headers
                ? options.headers
                : new Headers(options.headers || {});
            const skip = headers.get('X-No-Loader') === '1';
            if (!skip) showLoader();
            return originalFetch.apply(this, args)
                .finally(() => {
                    if (!skip) hideLoader();
                });
        };
    }

    // XMLHttpRequest wrapper (for ajax/libs)
    if (window.XMLHttpRequest) {
        const originalSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function (...args) {
            showLoader();
            this.addEventListener('loadend', hideLoader, { once: true });
            return originalSend.apply(this, args);
        };
    }

    // Axios support
    if (window.axios && !window.axios.__loaderBound) {
        window.axios.__loaderBound = true;
        window.axios.interceptors.request.use((config) => {
            showLoader();
            return config;
        });
        window.axios.interceptors.response.use(
            (response) => {
                hideLoader();
                return response;
            },
            (error) => {
                hideLoader();
                return Promise.reject(error);
            }
        );
    }
})();
