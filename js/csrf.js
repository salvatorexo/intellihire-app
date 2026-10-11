/* =========================================================
   IntelliHire - CSRF Auto-Attach
   Fetches a token on page load and injects it into every
   fetch() POST/PUT/DELETE automatically.
   ========================================================= */

(function () {
    let csrfToken = null;
    let tokenPromise = null;

    /* Fetch token from server */
    function fetchToken() {
        if (tokenPromise) return tokenPromise;
        tokenPromise = fetch('/IntelliHire/php/csrf-token.php', {
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                csrfToken = data.token;
                console.log('[CSRF] Token loaded:', csrfToken.substring(0, 12) + '...');
            }
            return csrfToken;
        })
        .catch(err => {
            console.warn('[CSRF] Token load failed:', err);
            return null;
        });
        return tokenPromise;
    }

    /* Wrap the global fetch */
    const originalFetch = window.fetch;
    window.fetch = async function (url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            if (!csrfToken) await fetchToken();
            options.headers = options.headers || {};
            if (csrfToken) options.headers['X-CSRF-Token'] = csrfToken;
        }
        options.credentials = options.credentials || 'same-origin';
        return originalFetch.call(this, url, options);
    };

    /* Load token immediately */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchToken);
    } else {
        fetchToken();
    }

    /* Expose getters/setters for manual use */
    window.__getCsrfToken = () => csrfToken;
    window.__setCsrfToken = (t) => { csrfToken = t; };
    window.__ensureCsrfToken = fetchToken;
})();