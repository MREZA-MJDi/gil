const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

export function idempotencyKey() {
    if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID().replaceAll('-', '');
    return 'op' + Date.now().toString(36) + Math.random().toString(36).slice(2, 24);
}

export async function api(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');

    let body = options.body;
    if (body !== undefined && body !== null && !(body instanceof FormData) && typeof body !== 'string') {
        headers.set('Content-Type', 'application/json');
        body = JSON.stringify(body);
    }
    if (method !== 'GET' && method !== 'HEAD') {
        headers.set('X-CSRF-TOKEN', csrfToken());
    }
    if (options.idempotent) {
        headers.set('Idempotency-Key', options.idempotencyKey || idempotencyKey());
    }

    let response;
    try {
        response = await fetch('/admin/api' + path, {
            method,
            headers,
            body,
            credentials: 'same-origin',
            signal: options.signal,
        });
    } catch (error) {
        if (error?.name === 'AbortError') throw error;
        throw new Error('ارتباط با سرور برقرار نشد. اتصال شبکه را بررسی و دوباره تلاش کن.');
    }

    let payload = null;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        try { payload = await response.json(); } catch { payload = null; }
    }

    if (!response.ok) {
        if (response.status === 401 || response.status === 419 || response.redirected) {
            throw new Error('نشست ورود منقضی شده است. صفحه را تازه‌سازی و دوباره وارد شو.');
        }
        const validation = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
        const message = validation || payload?.message || ('درخواست با خطای ' + response.status + ' مواجه شد.');
        throw new Error(String(message));
    }

    return payload ?? {};
}

export function unwrapList(payload) {
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload?.data?.data)) return payload.data.data;
    return [];
}

export function unwrapData(payload) {
    return payload?.data ?? null;
}
