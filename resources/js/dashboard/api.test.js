import test from 'node:test';
import assert from 'node:assert/strict';
import { api, idempotencyKey, unwrapData, unwrapList } from './api.js';

function mockBrowser(t, fetchImpl) {
    const originalFetch = globalThis.fetch;
    const originalDocument = globalThis.document;
    globalThis.document = {
        querySelector(selector) {
            if (selector === 'meta[name="csrf-token"]') {
                return { getAttribute: () => 'csrf-test-token' };
            }
            return null;
        },
    };
    globalThis.fetch = fetchImpl;
    t.after(() => {
        globalThis.fetch = originalFetch;
        if (originalDocument === undefined) delete globalThis.document;
        else globalThis.document = originalDocument;
    });
}

test('idempotency keys are nonempty and suitable for API headers', () => {
    const first = idempotencyKey();
    const second = idempotencyKey();
    assert.match(first, /^[A-Za-z0-9._:-]{16,64}$/);
    assert.notEqual(first, second);
});

test('unwrap helpers accept Laravel paginator and wrapped data', () => {
    assert.deepEqual(unwrapList({ data: [{ id: 1 }] }), [{ id: 1 }]);
    assert.deepEqual(unwrapList({ data: { data: [{ id: 2 }], current_page: 1 } }), [{ id: 2 }]);
    assert.deepEqual(unwrapList({}), []);
    assert.deepEqual(unwrapData({ data: { id: 7 } }), { id: 7 });
});

test('api posts JSON with CSRF, same-origin credentials and idempotency key', async t => {
    let captured;
    mockBrowser(t, async (url, options) => {
        captured = { url, options };
        return new Response(JSON.stringify({ data: { id: 42 } }), {
            status: 201,
            headers: { 'content-type': 'application/json' },
        });
    });

    const result = await api('/orders', {
        method: 'POST',
        body: { total: 1000 },
        idempotent: true,
        idempotencyKey: 'order-test-key-0001',
    });

    assert.deepEqual(result, { data: { id: 42 } });
    assert.equal(captured.url, '/admin/api/orders');
    assert.equal(captured.options.method, 'POST');
    assert.equal(captured.options.credentials, 'same-origin');
    assert.equal(captured.options.headers.get('X-CSRF-TOKEN'), 'csrf-test-token');
    assert.equal(captured.options.headers.get('Idempotency-Key'), 'order-test-key-0001');
    assert.deepEqual(JSON.parse(captured.options.body), { total: 1000 });
});

test('api surfaces Laravel validation messages instead of silently failing', async t => {
    mockBrowser(t, async () => new Response(JSON.stringify({
        message: 'The given data was invalid.',
        errors: { amount: ['مبلغ پرداخت معتبر نیست.'] },
    }), {
        status: 422,
        headers: { 'content-type': 'application/json' },
    }));

    await assert.rejects(api('/orders/1/payments', {
        method: 'POST',
        body: { amount: 0 },
    }), /مبلغ پرداخت معتبر نیست/);
});

test('api gives a helpful message when the network is unavailable', async t => {
    mockBrowser(t, async () => { throw new TypeError('network down'); });
    await assert.rejects(api('/dashboard'), /ارتباط با سرور برقرار نشد/);
});
