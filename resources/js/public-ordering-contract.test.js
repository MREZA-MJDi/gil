import assert from 'node:assert/strict';
import test from 'node:test';
import { getConfirmedPublicOrder } from './public-ordering-contract.js';

test('accepts a complete server-confirmed unpaid takeaway order', () => {
    const order = {
        number: 'WEB-26001',
        status: 'new',
        payment_status: 'unpaid',
        total: 125000,
    };

    assert.deepEqual(getConfirmedPublicOrder({ data: order }), order);
});

test('rejects a successful HTTP response without a server order number', () => {
    assert.equal(getConfirmedPublicOrder({ data: {
        status: 'new',
        payment_status: 'unpaid',
        total: 125000,
    } }), null);
});

test('rejects incomplete or inconsistent payment and total fields', () => {
    assert.equal(getConfirmedPublicOrder({ data: {
        number: 'WEB-26002',
        status: 'new',
        payment_status: 'paid',
        total: 125000,
    } }), null);

    assert.equal(getConfirmedPublicOrder({ data: {
        number: 'WEB-26003',
        status: 'new',
        payment_status: 'unpaid',
        total: 'not-a-number',
    } }), null);
});

test('rejects an empty or malformed response body', () => {
    assert.equal(getConfirmedPublicOrder(null), null);
    assert.equal(getConfirmedPublicOrder({}), null);
    assert.equal(getConfirmedPublicOrder({ data: null }), null);
});
