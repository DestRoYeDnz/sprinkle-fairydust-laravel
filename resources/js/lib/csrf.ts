const CSRF_META_SELECTOR = 'meta[name="csrf-token"]';
const XSRF_COOKIE_NAME = 'XSRF-TOKEN';

function readCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const encodedName = encodeURIComponent(name);
    const cookieRow = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${encodedName}=`));

    if (!cookieRow) {
        return null;
    }

    return decodeURIComponent(cookieRow.split('=').slice(1).join('='));
}

function readMetaToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return document.querySelector(CSRF_META_SELECTOR)?.getAttribute('content') ?? '';
}

function setMetaToken(token: string): void {
    if (typeof document === 'undefined' || token === '') {
        return;
    }

    const meta = document.querySelector(CSRF_META_SELECTOR);
    if (meta) {
        meta.setAttribute('content', token);
    }
}

export function getCsrfToken(): string {
    const cookieToken = readCookie(XSRF_COOKIE_NAME);
    if (cookieToken) {
        return cookieToken;
    }

    return readMetaToken();
}

export function csrfHeaders(includeContentType = true): Record<string, string> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (includeContentType) {
        headers['Content-Type'] = 'application/json';
    }

    const token = getCsrfToken();
    if (token) {
        headers['X-CSRF-TOKEN'] = token;
        headers['X-XSRF-TOKEN'] = token;
    }

    return headers;
}

export function withCsrfToken<T extends Record<string, unknown>>(payload: T): T & { _token: string } {
    return {
        ...payload,
        _token: getCsrfToken(),
    };
}

export function appendCsrfToken(formData: FormData): FormData {
    formData.set('_token', getCsrfToken());

    return formData;
}

export async function refreshCsrfToken(): Promise<string> {
    try {
        const response = await fetch('/csrf-token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return getCsrfToken();
        }

        const data = await response.json().catch(() => ({}));
        const refreshed = typeof data?.token === 'string' ? data.token : getCsrfToken();
        setMetaToken(refreshed);

        return refreshed;
    } catch {
        return getCsrfToken();
    }
}

export async function fetchWithCsrfRetry(input: RequestInfo | URL, init: RequestInit = {}): Promise<Response> {
    const firstResponse = await fetch(input, init);
    if (firstResponse.status !== 419) {
        return firstResponse;
    }

    const refreshedToken = await refreshCsrfToken();
    if (!refreshedToken) {
        return firstResponse;
    }

    const retryHeaders = new Headers(init.headers ?? {});
    if (!retryHeaders.has('Accept')) {
        retryHeaders.set('Accept', 'application/json');
    }
    retryHeaders.set('X-Requested-With', 'XMLHttpRequest');
    retryHeaders.set('X-CSRF-TOKEN', refreshedToken);
    retryHeaders.set('X-XSRF-TOKEN', refreshedToken);

    let retryBody = init.body;
    if (typeof init.body === 'string') {
        try {
            const parsed = JSON.parse(init.body);
            if (parsed && typeof parsed === 'object') {
                retryBody = JSON.stringify({
                    ...parsed,
                    _token: refreshedToken,
                });
            }
        } catch {
            // Keep original body when not valid JSON.
        }
    }

    if (init.body instanceof FormData) {
        const formData = new FormData();
        init.body.forEach((value, key) => {
            formData.append(key, value);
        });
        formData.set('_token', refreshedToken);
        retryBody = formData;
    }

    if (init.body instanceof URLSearchParams) {
        const params = new URLSearchParams(init.body);
        params.set('_token', refreshedToken);
        retryBody = params;
    }

    return fetch(input, {
        ...init,
        credentials: init.credentials ?? 'same-origin',
        headers: retryHeaders,
        body: retryBody,
    });
}
