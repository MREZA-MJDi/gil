import assert from 'node:assert/strict';
import test from 'node:test';
import { captureCustomerMenuFocusKey, restoreCustomerMenuFocus } from './customer-menu-focus.js';

function control(key, disabled = false) {
    return {
        disabled,
        focused: false,
        options: null,
        getAttribute(name) {
            return name === 'data-focus-key' ? key : null;
        },
        focus(options) {
            this.focused = true;
            this.options = options;
        },
    };
}

test('captures a focus key only from an element inside the QR menu', () => {
    const active = control('product-12');
    const root = {contains: element => element === active};

    assert.equal(captureCustomerMenuFocusKey(root, active), 'product-12');
    assert.equal(captureCustomerMenuFocusKey(root, control('outside')), null);
    assert.equal(captureCustomerMenuFocusKey(root, null), null);
});

test('restores focus to the matching enabled control after rendering', () => {
    const target = control('category-4');
    const root = {querySelectorAll: () => [target]};

    assert.equal(restoreCustomerMenuFocus(root, 'category-4'), true);
    assert.equal(target.focused, true);
    assert.deepEqual(target.options, {preventScroll: true});
});

test('moves focus to the status message when the previous control becomes disabled', () => {
    const submit = control('checkout-submit', true);
    const status = control('checkout-status');
    const root = {querySelectorAll: () => [submit, status]};

    assert.equal(restoreCustomerMenuFocus(root, 'checkout-submit'), true);
    assert.equal(submit.focused, false);
    assert.equal(status.focused, true);
});

test('does nothing when there is no prior focus key or matching status message', () => {
    const root = {querySelectorAll: () => []};

    assert.equal(restoreCustomerMenuFocus(root, null), false);
    assert.equal(restoreCustomerMenuFocus(root, 'missing-control'), false);
});
