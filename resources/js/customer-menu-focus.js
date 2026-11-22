export function captureCustomerMenuFocusKey(root, activeElement) {
    if (!root || !activeElement || !root.contains(activeElement)) return null;
    return activeElement.getAttribute?.('data-focus-key') || null;
}

export function restoreCustomerMenuFocus(root, focusKey) {
    if (!root || !focusKey) return false;

    const controls = Array.from(root.querySelectorAll('[data-focus-key]'));
    const target = controls.find(element => element.getAttribute('data-focus-key') === focusKey);
    if (target && !target.disabled) {
        target.focus({preventScroll: true});
        return true;
    }

    const status = controls.find(element => element.getAttribute('data-focus-key') === 'checkout-status');
    if (status) {
        status.focus({preventScroll: true});
        return true;
    }

    return false;
}
