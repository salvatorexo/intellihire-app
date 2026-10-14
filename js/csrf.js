/* =========================================================
   IntelliHire - CSRF Auto-Attach + InfinityFree challenge wait
   Handles both local XAMPP and live hosting.
   ========================================================= */

(function () {
    let csrfToken = null;
    let tokenPromise = null;
    let infinityFreeReady = false;

    /* ---------------------------------------------------
       Wait for InfinityFree's __test cookie to appear.
       Localhost doesn't need it, so this resolves anyway.
       --------------------------------------------------- */
    function waitForInfinityFree(maxMs = 5000) {
        return new Promise(resolve => {
            // Already passed?
            if (infinityFreeReady) return resolve(true);

            // Cookie already set?
            if (document.cookie.indexOf('__test=') !== -1) {
                infinityFreeReady = true;
                return resolve(true);
            }

            const start = Date.now();
            const timer = setInterval(() => {
                if (document.cookie.indexOf('__test=') !== -1) {
                    infinityFreeReady = true;
                    clearInterval(timer);
                    console.log('[CSRF] InfinityFree challenge passed');
                    resolve(true);
                } else if (Date.now() - start > maxMs) {
                    clearInterval(timer);
                    console.log('[CSRF] No __test cookie (probably localhost, continuing)');
                    resolve(false);
                }
            }, 200);
        });
    }

    /* ---------------------------------------------------
       Fetch a CSRF token from the server.
       --------------------------------------------------- */
    function fetchToken() {
        if (tokenPromise) return tokenPromise;
        tokenPromise = fetch('/php/csrf-token.php', {
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                csrfToken = data.token;
                console.log('[CSRF] Token loaded');
            }
            return csrfToken;
        })
        .catch(err => {
            console.warn('[CSRF] Token fetch failed:', err);
            return null;
        });
        return tokenPromise;
    }

    /* ---------------------------------------------------
       Patch window.fetch: wait for challenge, then add token.
       --------------------------------------------------- */
    const originalFetch = window.fetch;
    window.fetch = async function (url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();

        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            await waitForInfinityFree();
            if (!csrfToken) await fetchToken();

            options.headers = options.headers || {};
            if (csrfToken) {
                options.headers['X-CSRF-Token'] = csrfToken;
            }
        }

        options.credentials = options.credentials || 'same-origin';
        return originalFetch.call(this, url, options);
    };

    /* ---------------------------------------------------
       Load token on page start.
       --------------------------------------------------- */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchToken);
    } else {
        fetchToken();
    }

    /* ---------------------------------------------------
       Public helpers (used by login.js and other pages).
       --------------------------------------------------- */
    window.__getCsrfToken     = () => csrfToken;
    window.__setCsrfToken     = (t) => { csrfToken = t; };
    window.__ensureCsrfToken  = fetchToken;
    window.__waitForInfinityFree = waitForInfinityFree;
})();
