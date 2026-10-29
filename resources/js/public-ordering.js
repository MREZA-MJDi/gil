const root = document.querySelector('[data-public-ordering]');

if (root) {
    const cart = new Map();
    const currencyLabel = document.querySelector('meta[name="currency-label"]')?.content || 'تومان';
    const money = value => new Intl.NumberFormat('fa-IR-u-nu-arabext', { maximumFractionDigits: 0 }).format(Number(value || 0)) + ' ' + currencyLabel;
    const escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const idempotencyKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('web' + Date.now().toString(36) + Math.random().toString(36).slice(2, 24));
    const itemsEl = document.getElementById('online-cart-items');
    const emptyEl = document.getElementById('online-cart-empty');
    const totalEl = document.getElementById('online-cart-total');
    const form = document.getElementById('online-checkout-form');
    const submit = document.getElementById('online-checkout-submit');
    const message = document.getElementById('online-checkout-message');
    let pendingIdempotencyKey = null;
    let uncertainSubmission = false;
    const setCheckoutLocked = locked => {
        uncertainSubmission = locked;
        root.querySelectorAll('[data-add-online-product], [data-online-option], [data-online-quantity], #online-checkout-form input, #online-checkout-form textarea, #online-checkout-form select').forEach(control => { control.disabled = locked; });
        if (submit) submit.textContent = locked ? 'ارسال مجدد با همان شناسه' : 'ثبت سفارش بیرون‌بر ↗';
    };
    const getTotal = () => [...cart.values()].reduce((sum, line) => sum + line.quantity * (line.price + line.options.reduce((n, option) => n + option.price, 0)), 0);

    function render() {
        if (itemsEl) itemsEl.innerHTML = [...cart.values()].map(line => {
            const optionNames = line.options.map(option => option.name).filter(Boolean).join('، ');
            return '<article class="online-cart-row"><div><b>' + escapeHtml(line.name) + '</b><small>' + escapeHtml(optionNames || 'بدون افزودنی') + '</small><small>' + money(line.price + line.options.reduce((n, o) => n + o.price, 0)) + ' × ' + line.quantity + '</small></div><div class="online-cart-actions"><button type="button" data-cart-qty="-1" data-cart-key="' + escapeHtml(line.key) + '" aria-label="کم کردن تعداد">−</button><b>' + line.quantity + '</b><button type="button" data-cart-qty="1" data-cart-key="' + escapeHtml(line.key) + '" aria-label="زیاد کردن تعداد">＋</button><button type="button" data-cart-remove data-cart-key="' + escapeHtml(line.key) + '" aria-label="حذف از سبد">حذف</button></div></article>';
        }).join('');
        if (emptyEl) emptyEl.hidden = cart.size > 0;
        if (totalEl) totalEl.textContent = money(getTotal());
        if (submit) submit.disabled = cart.size === 0;
    }

    root.querySelectorAll('[data-add-online-product]').forEach(button => button.addEventListener('click', () => {
        const card = button.closest('.public-food-card');
        if (!card) return;
        const selected = [...card.querySelectorAll('[data-online-option]:checked')].map(input => ({
            id: Number(input.dataset.onlineOption), type: input.dataset.optionType || 'addon',
            name: input.dataset.optionName || '', price: Number(input.dataset.optionPrice || 0),
        }));
        if (selected.filter(option => option.type === 'size').length > 1) {
            message.textContent = 'برای هر محصول فقط یک سایز انتخاب کن.';
            return;
        }
        const quantityInput = card.querySelector('[data-online-quantity]');
        const quantity = Math.max(1, Math.min(100, Number.parseInt(quantityInput?.value || '1', 10) || 1));
        const productId = Number(button.dataset.productId);
        const key = String(productId) + ':' + selected.map(option => option.id).sort((a,b) => a-b).join(',');
        const existing = cart.get(key);
        if (existing) existing.quantity = Math.min(100, existing.quantity + quantity);
        else cart.set(key, { key, product_id: productId, name: button.dataset.productName || '', price: Number(button.dataset.productPrice || 0), quantity, options: selected });
        message.textContent = 'محصول به سبد اضافه شد. اطلاعات تماس را وارد کن.';
        render();
        document.getElementById('online-cart')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }));

    itemsEl?.addEventListener('click', event => {
        const target = event.target.closest('button');
        if (!target) return;
        const key = target.dataset.cartKey;
        const item = cart.get(key);
        if (!item) return;
        if (target.hasAttribute('data-cart-remove')) cart.delete(key);
        else if (target.hasAttribute('data-cart-qty')) {
            item.quantity += Number(target.dataset.cartQty);
            if (item.quantity <= 0) cart.delete(key);
            else item.quantity = Math.min(100, item.quantity);
        }
        render();
    });

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        if (cart.size === 0 || submit?.disabled) return;
        submit.disabled = true;
        root.querySelectorAll('[data-add-online-product], [data-online-option], [data-online-quantity]').forEach(control => { control.disabled = true; });
        message.textContent = uncertainSubmission ? 'در حال بررسی همان سفارش…' : 'در حال ثبت سفارش…';
        const fields = new FormData(form);
        const key = pendingIdempotencyKey || idempotencyKey();
        pendingIdempotencyKey = key;
        const payload = {
            items: [...cart.values()].map(line => ({ product_id: line.product_id, quantity: line.quantity, options: line.options.map(option => ({ id: option.id })) })),
            customer_name: String(fields.get('customer_name') || '').trim(),
            customer_phone: String(fields.get('customer_phone') || '').trim(),
            customer_note: String(fields.get('customer_note') || '').trim() || null,
            payment_preference: String(fields.get('payment_preference') || 'at_counter'),
            idempotency_key: key,
        };
        try {
            const response = await fetch('/online-orders', {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Idempotency-Key': key },
                body: JSON.stringify(payload),
            });
            let body = {};
            try { body = await response.json(); } catch { body = {}; }
            if (!response.ok) {
                const validation = body?.errors ? Object.values(body.errors).flat().find(Boolean) : null;
                throw new Error(validation || body?.message || 'سفارش ثبت نشد؛ اطلاعات را بررسی و دوباره تلاش کن.');
            }
            const order = body?.data || {};
            cart.clear(); form.reset(); pendingIdempotencyKey = null; setCheckoutLocked(false); render();
            message.textContent = 'سفارش ' + (order.number || '') + ' ثبت شد. مبلغ نهایی: ' + money(order.total) + '. پرداخت هنوز ثبت نشده و هنگام تحویل یا در صندوق انجام می‌شود.';
        } catch (error) {
            message.textContent = error?.message || 'ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کن.';
            if (response && response.status >= 400 && response.status < 500 && response.status !== 429) {
                pendingIdempotencyKey = null;
                setCheckoutLocked(false);
            } else {
                // The server may have committed before a timeout/5xx. Freeze the same payload/key to prevent duplicates.
                setCheckoutLocked(true);
            }
            if (submit) submit.disabled = cart.size === 0;
        }
    });
    render();
}
