/* Public takeaway cart: server validates stock, options and final prices. */
const orderingRoot = document.querySelector('[data-public-ordering]');
if (orderingRoot) {
    const cart = new Map();
    const form = document.getElementById('online-checkout-form');
    const itemsEl = document.getElementById('online-cart-items');
    const emptyEl = document.getElementById('online-cart-empty');
    const totalEl = document.getElementById('online-cart-total');
    const submitButton = document.getElementById('online-checkout-submit');
    const message = document.getElementById('online-checkout-message');
    const successPanel = document.getElementById('online-order-success');
    const currency = document.querySelector('meta[name="currency-label"]')?.content || 'تومان';
    const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ' + currency;
    const escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const makeKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('web' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    let pendingKey = null;
    let pendingPayload = null;
    let uncertainRequest = false;

    const setLocked = locked => {
        uncertainRequest = locked;
        orderingRoot.classList.toggle('kg-checkout-locked', locked);
        orderingRoot.querySelectorAll('[data-add-online-product], [data-online-option], [data-online-quantity], #online-checkout-form input, #online-checkout-form textarea, #online-checkout-form select').forEach(control => {
            if (control.type !== 'submit') control.disabled = locked;
        });
        if (submitButton) {
            submitButton.textContent = locked ? 'ارسال دوباره با همان شناسه' : 'ثبت سفارش بیرون‌بر ↗';
            submitButton.disabled = cart.size === 0;
        }
    };

    const estimatedTotal = () => [...cart.values()].reduce((sum, item) => {
        const optionsTotal = item.options.reduce((value, option) => value + option.price, 0);
        return sum + (item.price + optionsTotal) * item.quantity;
    }, 0);

    function hideOrderSuccess() {
        if (!successPanel) return;
        successPanel.hidden = true;
        successPanel.classList.remove('is-visible');
    }

    function showOrderSuccess(order) {
        if (!successPanel) return;
        const number = successPanel.querySelector('[data-online-order-number]');
        const total = successPanel.querySelector('[data-online-order-total]');
        if (number) number.textContent = order.number || 'ثبت شد';
        if (total) total.textContent = money(order.total);
        successPanel.hidden = false;
        successPanel.classList.remove('is-visible');
        void successPanel.offsetWidth;
        successPanel.classList.add('is-visible');
        successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function renderCart() {
        if (itemsEl) {
            itemsEl.innerHTML = [...cart.values()].map(item => {
                const optionText = item.options.map(option => option.name).filter(Boolean).join('، ');
                const perItem = item.price + item.options.reduce((sum, option) => sum + option.price, 0);
                return '<article class="kg-cart-row">' +
                    '<div class="kg-cart-row-main"><b>' + escapeHtml(item.name) + '</b>' +
                    '<small>' + escapeHtml(optionText || 'بدون افزودنی') + '</small>' +
                    '<small>' + money(perItem) + ' × ' + item.quantity + '</small></div>' +
                    '<div class="kg-cart-row-actions">' +
                    '<button type="button" data-cart-qty="-1" data-cart-key="' + escapeHtml(item.key) + '" aria-label="کم کردن تعداد">−</button>' +
                    '<b>' + item.quantity + '</b>' +
                    '<button type="button" data-cart-qty="1" data-cart-key="' + escapeHtml(item.key) + '" aria-label="زیاد کردن تعداد">＋</button>' +
                    '<button type="button" class="kg-remove" data-cart-remove data-cart-key="' + escapeHtml(item.key) + '">حذف</button>' +
                    '</div></article>';
            }).join('');
        }
        if (emptyEl) emptyEl.hidden = cart.size > 0;
        if (totalEl) totalEl.textContent = money(estimatedTotal());
        if (submitButton) submitButton.disabled = cart.size === 0;
    }

    orderingRoot.addEventListener('click', event => {
        const newOrderButton = event.target.closest('[data-online-new-order]');
        if (newOrderButton) {
            hideOrderSuccess();
            document.querySelector('.menu-intro')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const addButton = event.target.closest('[data-add-online-product]');
        if (addButton) {
            if (uncertainRequest) return;
            const card = addButton.closest('[data-product-card]');
            if (!card) return;
            hideOrderSuccess();
            const selected = [...card.querySelectorAll('[data-online-option]:checked')].map(input => ({
                id: Number(input.dataset.onlineOption),
                type: input.dataset.optionType || 'addon',
                name: input.dataset.optionName || '',
                price: Number(input.dataset.optionPrice || 0)
            }));
            if (selected.filter(option => option.type === 'size').length > 1) {
                if (message) message.textContent = 'برای هر محصول فقط یک سایز انتخاب کن.';
                return;
            }
            const quantityInput = card.querySelector('[data-online-quantity]');
            const quantity = Math.max(1, Math.min(100, Number.parseInt(quantityInput?.value || '1', 10) || 1));
            const productId = Number(addButton.dataset.productId);
            if (!Number.isInteger(productId) || productId < 1) return;
            const key = String(productId) + ':' + selected.map(option => option.id).sort((a, b) => a - b).join(',');
            const existing = cart.get(key);
            if (existing) existing.quantity = Math.min(100, existing.quantity + quantity);
            else cart.set(key, {
                key,
                product_id: productId,
                name: addButton.dataset.productName || '',
                price: Number(addButton.dataset.productPrice || 0),
                quantity,
                options: selected
            });
            if (message) message.textContent = 'به سبد اضافه شد.';
            renderCart();
            document.getElementById('online-cart')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        const qtyButton = event.target.closest('[data-cart-qty], [data-cart-remove]');
        if (!qtyButton || uncertainRequest) return;
        hideOrderSuccess();
        const key = qtyButton.dataset.cartKey;
        const item = cart.get(key);
        if (!item) return;
        if (qtyButton.hasAttribute('data-cart-remove')) cart.delete(key);
        else {
            item.quantity += Number(qtyButton.dataset.cartQty);
            if (item.quantity < 1) cart.delete(key);
            else item.quantity = Math.min(100, item.quantity);
        }
        renderCart();
    });

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        if (cart.size === 0 || submitButton?.disabled) return;
        if (!pendingKey) pendingKey = makeKey();
        if (!pendingPayload) {
            const fields = new FormData(form);
            pendingPayload = {
                items: [...cart.values()].map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    options: item.options.map(option => ({ id: option.id }))
                })),
                customer_name: String(fields.get('customer_name') || '').trim(),
                customer_phone: String(fields.get('customer_phone') || '').trim(),
                customer_note: String(fields.get('customer_note') || '').trim() || null,
                payment_preference: String(fields.get('payment_preference') || 'at_counter'),
                idempotency_key: pendingKey
            };
        }

        submitButton.disabled = true;
        if (message) message.textContent = uncertainRequest ? 'در حال بررسی همان سفارش…' : 'در حال ثبت سفارش…';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let response = null;

        try {
            response = await fetch(orderingRoot.dataset.orderEndpoint || form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Idempotency-Key': pendingKey
                },
                body: JSON.stringify(pendingPayload)
            });
            let result = {};
            try { result = await response.json(); } catch { result = {}; }

            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت سفارش انجام نشد. اطلاعات را بررسی کن.');
            }

            const order = result?.data || {};
            cart.clear();
            form.reset();
            orderingRoot.querySelectorAll('[data-online-option]').forEach(input => { input.checked = false; });
            orderingRoot.querySelectorAll('[data-online-quantity]').forEach(input => { input.value = '1'; });
            pendingKey = null;
            pendingPayload = null;
            setLocked(false);
            renderCart();
            if (message) message.textContent = 'سفارش با موفقیت ثبت شد. پرداخت هنگام دریافت انجام می‌شود.';
            showOrderSuccess(order);
        } catch (error) {
            const definiteClientError = response && response.status >= 400 && response.status < 500 && response.status !== 429;
            if (definiteClientError) {
                pendingKey = null;
                pendingPayload = null;
                setLocked(false);
            } else {
                /* Network/5xx can happen after commit: keep the same key and exact payload. */
                setLocked(true);
            }
            if (message) message.textContent = error?.message || 'ارتباط با سرور برقرار نشد؛ با همان اطلاعات دوباره تلاش کن.';
            if (submitButton) submitButton.disabled = false;
        }
    });

    renderCart();
}

