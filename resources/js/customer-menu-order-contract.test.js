import assert from 'node:assert/strict';
import test from 'node:test';
import { getConfirmedCustomerMenuOrder } from './customer-menu-order-contract.js';

test('accepts the complete server response returned by the QR order endpoint', () => {
    const order = {
        number: 'QR-26001',
        status: 'new',
        total: 185000,
        created_at: '2026-11-18T12:00:00Z',
    };

    assert.deepEqual(getConfirmedCustomerMenuOrder({data: order}), order);
});

test('rejects a response without an order number or status', () => {
    assert.equal(getConfirmedCustomerMenuOrder({data: {status: 'new', total: 185000}}), null);
    assert.equal(getConfirmedCustomerMenuOrder({data: {number: 'QR-26002', total: 185000}}), null);
});

test('rejects a response with a missing or invalid total', () => {
    assert.equal(getConfirmedCustomerMenuOrder({data: {number: 'QR-26003', status: 'new'}}), null);
    assert.equal(getConfirmedCustomerMenuOrder({data: {number: 'QR-26004', status: 'new', total: 'NaN'}}), null);
    assert.equal(getConfirmedCustomerMenuOrder({data: {number: 'QR-26005', status: 'new', total: -1}}), null);
});

test('rejects an empty or malformed response body', () => {
    assert.equal(getConfirmedCustomerMenuOrder(null), null);
    assert.equal(getConfirmedCustomerMenuOrder({}), null);
    assert.equal(getConfirmedCustomerMenuOrder({data: null}), null);
});
