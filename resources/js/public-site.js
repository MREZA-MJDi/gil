const header = document.querySelector('[data-kg-header]');
const menuToggle = document.querySelector('[data-kg-menu-toggle]');
const nav = document.querySelector('[data-kg-nav]');

if (header && menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
    });
    nav.addEventListener('click', event => {
        if (event.target.closest('a')) {
            nav.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'باز کردن منو');
        }
    });
}

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
        const addButton = event.target.closest('[data-add-online-product]');
        if (addButton) {
            if (uncertainRequest) return;
            const card = addButton.closest('[data-product-card]');
            if (!card) return;
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
            if (message) message.textContent = 'سفارش ' + (order.number || '') + ' ثبت شد. مبلغ نهایی: ' + money(order.total) + '. پرداخت هنوز ثبت نشده است.';
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

/* Reservation form: sends the backend's exact accepted fields, ISO-local date/time. */
const reservationForm = document.querySelector('[data-public-reservation]');
if (reservationForm) {
    const submitButton = reservationForm.querySelector('[data-reserve-submit]');
    const message = reservationForm.querySelector('[data-reserve-message]');
    const successPanel = reservationForm.querySelector('[data-reserve-success]');
    const referenceEl = reservationForm.querySelector('[data-reserve-reference]');
    const newButton = reservationForm.querySelector('[data-reserve-new]');
    let uncertain = false;
    let stablePayload = null;

    const makeNewKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('reserve' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    const setBusy = busy => {
        reservationForm.querySelectorAll('input, select, textarea').forEach(control => {
            if (control.name !== 'website') control.disabled = busy;
        });
        if (submitButton) {
            submitButton.disabled = busy;
            submitButton.textContent = busy ? 'در حال ثبت…' : 'ارسال درخواست ↗';
        }
    };

    reservationForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitButton?.disabled) return;
        const fields = new FormData(reservationForm);
        if (!stablePayload) {
            stablePayload = Object.fromEntries(fields.entries());
            stablePayload.party_size = Number(stablePayload.party_size);
            stablePayload.duration_minutes = Number(stablePayload.duration_minutes);
            if (stablePayload.estimated_budget === '') stablePayload.estimated_budget = null;
            else if (stablePayload.estimated_budget !== null) stablePayload.estimated_budget = Number(stablePayload.estimated_budget);
        }
        const key = stablePayload.idempotency_key;
        setBusy(true);
        if (message) message.textContent = uncertain ? 'در حال بررسی همان درخواست…' : 'در حال ثبت درخواست…';
        let response = null;
        try {
            response = await fetch(reservationForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Idempotency-Key': key
                },
                body: JSON.stringify(stablePayload)
            });
            let result = {};
            try { result = await response.json(); } catch { result = {}; }
            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت درخواست انجام نشد.');
            }
            if (referenceEl) referenceEl.textContent = result?.data?.reference || 'ثبت شد';
            if (successPanel) successPanel.hidden = false;
            if (message) message.textContent = 'درخواست با وضعیت «در انتظار تأیید» ثبت شد.';
            reservationForm.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(control => {
                if (control.name !== 'website' && !control.hasAttribute('data-reserve-new')) control.disabled = true;
            });
            uncertain = false;
            stablePayload = null;
        } catch (error) {
            const definiteClientError = response && response.status >= 400 && response.status < 500 && response.status !== 429;
            if (definiteClientError) {
                stablePayload = null;
                uncertain = false;
                setBusy(false);
            } else {
                uncertain = true;
                reservationForm.querySelectorAll('input, select, textarea').forEach(control => {
                    if (control.name !== 'website') control.disabled = true;
                });
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'ارسال دوباره با همان اطلاعات';
                }
            }
            if (message) message.textContent = error?.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از ثبت تکراری، با همان اطلاعات دوباره ارسال کن.';
        }
    });

    newButton?.addEventListener('click', () => {
        reservationForm.reset();
        const keyInput = reservationForm.querySelector('[name="idempotency_key"]');
        if (keyInput) keyInput.value = makeNewKey();
        if (successPanel) successPanel.hidden = true;
        if (message) message.textContent = '';
        stablePayload = null;
        uncertain = false;
        setBusy(false);
        reservationForm.querySelectorAll('input, select, textarea').forEach(control => { control.disabled = false; });
    });
}

/* Per-table QR menu. The API response is the only source of catalog data. */
const qrRoot = document.querySelector('[data-customer-qr-menu]');
if (qrRoot) {
    const escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const makeKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    const cart = new Map();
    let currency = 'تومان';
    let qrFormKey = makeKey();
    let qrPendingPayload = null;
    let qrUncertainRequest = false;
    const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ' + currency;

    function qrCard(product) {
        const image = product.image_url
            ? '<img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" loading="lazy">'
            : '<span>🍽️</span>';
        const options = (product.options || []).filter(option => option.is_active !== false).map(option => {
            const type = option.type?.value || option.type || 'addon';
            return '<label><input type="checkbox" data-qr-option="' + Number(option.id) + '" data-option-type="' + escapeHtml(type) + '" data-option-name="' + escapeHtml(option.name) + '" data-option-price="' + Number(option.price || 0) + '"> ' +
                escapeHtml(option.name) + ' <small>' + (Number(option.price || 0) > 0 ? '+' + money(option.price) : 'رایگان') + '</small></label>';
        }).join('');
        return '<article class="kg-qr-product" data-qr-product="' + Number(product.id) + '">' +
            '<div class="kg-qr-product-photo">' + image + '</div>' +
            '<div class="kg-qr-product-body"><h3>' + escapeHtml(product.name) + '</h3>' +
            '<p>' + escapeHtml(product.description || '') + '</p>' +
            '<b class="kg-qr-product-price">' + money(product.base_price) + '</b>' +
            (options ? '<div class="kg-qr-options">' + options + '</div>' : '') +
            '<button type="button" class="kg-qr-add" data-qr-add data-product-id="' + Number(product.id) + '" data-product-name="' + escapeHtml(product.name) + '" data-product-price="' + Number(product.base_price || 0) + '">افزودن به سفارش +</button>' +
            '</div></article>';
    }

    function renderQrCatalog(categories, tableName) {
        const sections = categories.map((category, index) =>
            '<section class="kg-qr-category" id="qr-category-' + Number(category.id) + '">' +
            '<h2>' + escapeHtml(category.name) + '</h2><div class="kg-qr-products">' +
            (category.products || []).map(qrCard).join('') + '</div></section>'
        ).join('');
        qrRoot.innerHTML =
            '<div class="kg-qr-catalog"><div class="kg-qr-catalog-list">' +
            (sections || '<div class="kg-empty-state"><span>🍒</span><b>منو در حال تکمیل است.</b></div>') +
            '</div><aside class="kg-qr-cart"><h2>سفارش میز ' + escapeHtml(tableName) + '</h2>' +
            '<div class="kg-qr-cart-lines" id="kg-qr-cart-lines"><p class="kg-cart-empty">هنوز محصولی انتخاب نشده است.</p></div>' +
            '<div class="kg-qr-cart-total"><span>جمع تخمینی</span><b id="kg-qr-total">۰ ' + escapeHtml(currency) + '</b></div>' +
            '<form id="kg-qr-order-form"><input name="customer_name" maxlength="150" placeholder="نام شما (اختیاری)">' +
            '<input name="customer_phone" maxlength="30" inputmode="tel" placeholder="شماره تماس (اختیاری)">' +
            '<button type="submit" id="kg-qr-submit" disabled>ثبت سفارش برای این میز</button>' +
            '<p class="kg-qr-status" id="kg-qr-status" role="status"></p></form></aside></div>';
    }

    function renderQrCart() {
        const lines = document.getElementById('kg-qr-cart-lines');
        const totalEl = document.getElementById('kg-qr-total');
        const submit = document.getElementById('kg-qr-submit');
        const sum = [...cart.values()].reduce((total, item) => total + item.quantity * (item.price + item.options.reduce((x, option) => x + option.price, 0)), 0);
        if (lines) {
            lines.innerHTML = cart.size ? [...cart.values()].map(item =>
                '<div class="kg-qr-cart-line"><div><b>' + escapeHtml(item.name) + '</b><small>' +
                escapeHtml(item.options.map(option => option.name).join('، ')) + '</small><small>' + item.quantity + ' × ' + money(item.price + item.options.reduce((x, option) => x + option.price, 0)) + '</small></div>' +
                '<div><button type="button" data-qr-qty="-1" data-qr-key="' + escapeHtml(item.key) + '">−</button> ' + item.quantity + ' <button type="button" data-qr-qty="1" data-qr-key="' + escapeHtml(item.key) + '">＋</button></div></div>'
            ).join('') : '<p class="kg-cart-empty">هنوز محصولی انتخاب نشده است.</p>';
        }
        if (totalEl) totalEl.textContent = money(sum);
        if (submit) submit.disabled = cart.size === 0;
    }

    async function loadQrMenu() {
        try {
            const response = await fetch(qrRoot.dataset.dataUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result?.message || 'منوی این میز در دسترس نیست.');
            const data = result.data || {};
            currency = data.settings?.currency_label || 'تومان';
            renderQrCatalog(data.categories || [], data.table?.name || qrRoot.dataset.tableName || '');
            renderQrCart();
        } catch (error) {
            qrRoot.innerHTML = '<div class="kg-empty-state"><span>🍒</span><b>' + escapeHtml(error?.message || 'دریافت منو انجام نشد.') + '</b><p>صفحه را تازه کن یا با کارکنان مجموعه هماهنگ کن.</p></div>';
        }
    }

    qrRoot.addEventListener('click', event => {
        const add = event.target.closest('[data-qr-add]');
        if (add) {
            if (qrUncertainRequest) return;
            const card = add.closest('[data-qr-product]');
            const options = [...card.querySelectorAll('[data-qr-option]:checked')].map(input => ({
                id: Number(input.dataset.qrOption),
                type: input.dataset.optionType || 'addon',
                name: input.dataset.optionName || '',
                price: Number(input.dataset.optionPrice || 0)
            }));
            if (options.filter(option => option.type === 'size').length > 1) {
                const status = document.getElementById('kg-qr-status');
                if (status) status.textContent = 'برای هر محصول فقط یک سایز انتخاب کن.';
                return;
            }
            const productId = Number(add.dataset.productId);
            const key = String(productId) + ':' + options.map(option => option.id).sort((a, b) => a - b).join(',');
            const existing = cart.get(key);
            if (existing) existing.quantity = Math.min(100, existing.quantity + 1);
            else cart.set(key, { key, product_id: productId, name: add.dataset.productName || '', price: Number(add.dataset.productPrice || 0), quantity: 1, options });
            renderQrCart();
            const status = document.getElementById('kg-qr-status');
            if (status) status.textContent = 'به سفارش اضافه شد.';
            return;
        }
        const qty = event.target.closest('[data-qr-qty]');
        if (qty) {
            if (qrUncertainRequest) return;
            const item = cart.get(qty.dataset.qrKey);
            if (!item) return;
            item.quantity += Number(qty.dataset.qrQty);
            if (item.quantity < 1) cart.delete(item.key);
            else item.quantity = Math.min(100, item.quantity);
            renderQrCart();
        }
    });

    qrRoot.addEventListener('submit', async event => {
        if (event.target.id !== 'kg-qr-order-form') return;
        event.preventDefault();
        if (!cart.size) return;
        const form = event.target;
        const submit = document.getElementById('kg-qr-submit');
        const status = document.getElementById('kg-qr-status');
        if (!qrPendingPayload) {
            const fields = new FormData(form);
            qrPendingPayload = {
                channel: 'dine_in',
                payment_preference: 'at_counter',
                discount: 0,
                customer_name: String(fields.get('customer_name') || '').trim() || null,
                customer_phone: String(fields.get('customer_phone') || '').trim() || null,
                items: [...cart.values()].map(item => ({ product_id: item.product_id, quantity: item.quantity, options: item.options.map(option => ({ id: option.id })) })),
                idempotency_key: qrFormKey
            };
        }
        const payload = qrPendingPayload;
        submit.disabled = true;
        if (status) status.textContent = 'در حال ثبت سفارش…';
        let response = null;
        try {
            response = await fetch(qrRoot.dataset.orderUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Idempotency-Key': qrFormKey
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت سفارش انجام نشد.');
            }
            cart.clear();
            qrRoot.querySelectorAll('[data-qr-option]').forEach(input => { input.checked = false; });
            qrFormKey = makeKey();
            qrPendingPayload = null;
            qrUncertainRequest = false;
            form.reset();
            qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], #kg-qr-order-form input').forEach(control => { control.disabled = false; });
            renderQrCart();
            if (status) status.textContent = 'سفارش ' + (result?.data?.number || '') + ' برای میز ثبت شد. پرداخت هنوز ثبت نشده است.';
        } catch (error) {
            const definiteClientError = response && response.status >= 400 && response.status < 500 && response.status !== 429;
            if (definiteClientError) {
                qrFormKey = makeKey();
                qrPendingPayload = null;
                qrUncertainRequest = false;
                qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], #kg-qr-order-form input').forEach(control => { control.disabled = false; });
            } else {
                /* Preserve the exact body and key: a timeout may happen after the order is saved. */
                qrUncertainRequest = true;
                qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], #kg-qr-order-form input').forEach(control => { control.disabled = true; });
                if (submit) submit.textContent = 'ارسال دوباره با همان اطلاعات';
            }
            if (status) status.textContent = error?.message || 'ارتباط با سرور برقرار نشد.';
            if (submit) submit.disabled = cart.size === 0;
        }
    });

    loadQrMenu();
}

const menuRoot = document.querySelector('[data-menu-showcase]');

if (menuRoot) {
    const categoryButtons = [...menuRoot.querySelectorAll('[data-menu-category-select]')];
    const categoryPanels = [...menuRoot.querySelectorAll('[data-menu-category-panel]')];
    const pageSize = 4;

    const setSelectedProduct = (panel, productId) => {
        const template = [...panel.querySelectorAll('template[data-menu-product-template]')]
            .find(item => item.dataset.menuProductTemplate === String(productId));
        const feature = panel.querySelector('[data-menu-featured]');

        if (!template || !feature) return;

        feature.replaceChildren(template.content.cloneNode(true));

        panel.querySelectorAll('[data-menu-product-select]').forEach(button => {
            const selected = button.dataset.menuProductId === String(productId);
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    };

    const setPage = (panel, requestedPage) => {
        const products = [...panel.querySelectorAll('[data-menu-product-select]')];
        const dots = [...panel.querySelectorAll('[data-menu-page-index]')];
        const pageCount = Math.max(1, Math.ceil(products.length / pageSize));
        const page = Math.max(0, Math.min(pageCount - 1, requestedPage));
        const firstIndex = page * pageSize;

        products.forEach((button, index) => {
            button.hidden = index < firstIndex || index >= firstIndex + pageSize;
        });

        dots.forEach((button, index) => {
            const active = index === page;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-current', active ? 'page' : 'false');
        });

        if (products[firstIndex]) {
            setSelectedProduct(panel, products[firstIndex].dataset.menuProductId);
        }
    };

    const setCategory = categoryId => {
        const panel = categoryPanels.find(item => item.dataset.menuCategoryPanel === String(categoryId));
        if (!panel) return;

        categoryPanels.forEach(item => {
            item.hidden = item !== panel;
        });

        categoryButtons.forEach(button => {
            const active = button.dataset.menuCategorySelect === String(categoryId);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        setPage(panel, 0);
    };

    menuRoot.addEventListener('click', event => {
        const categoryButton = event.target.closest('[data-menu-category-select]');
        if (categoryButton && menuRoot.contains(categoryButton)) {
            setCategory(categoryButton.dataset.menuCategorySelect);
            return;
        }

        const pageButton = event.target.closest('[data-menu-page-index]');
        if (pageButton && menuRoot.contains(pageButton)) {
            const panel = pageButton.closest('[data-menu-category-panel]');
            if (panel) setPage(panel, Number(pageButton.dataset.menuPageIndex) || 0);
            return;
        }

        const productButton = event.target.closest('[data-menu-product-select]');
        if (productButton && menuRoot.contains(productButton)) {
            const panel = productButton.closest('[data-menu-category-panel]');
            if (panel) setSelectedProduct(panel, productButton.dataset.menuProductId);
        }
    });

    menuRoot.addEventListener('error', event => {
        const image = event.target;
        if (!image.matches?.('[data-menu-image]')) return;

        image.hidden = true;
        const fallback = image.nextElementSibling;
        if (fallback?.matches('[data-menu-image-fallback]')) {
            fallback.hidden = false;
        }
    }, true);

    if (categoryButtons.length) {
        setCategory(categoryButtons[0].dataset.menuCategorySelect);
    }
}


/* Home food carousel: card data and image URLs come from active database products. */
const homeCarousel = document.querySelector('[data-home-carousel]');
if (homeCarousel) {
    const stage = homeCarousel.querySelector('[data-home-carousel-stage]');
    const cards = [...homeCarousel.querySelectorAll('[data-home-carousel-card]')];
    const dots = [...homeCarousel.querySelectorAll('[data-home-dot]')];
    const previous = homeCarousel.querySelector('[data-home-prev]');
    const next = homeCarousel.querySelector('[data-home-next]');
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    let current = 0;
    let timer = null;
    let pointerStart = null;

    const render = () => {
        const count = cards.length;
        cards.forEach((card, index) => {
            const raw = (index - current + count) % count;
            const offset = raw > count / 2 ? raw - count : raw;
            card.dataset.homePosition = Math.abs(offset) > 2 ? 'hidden' : String(offset);
            card.tabIndex = Math.abs(offset) <= 1 ? 0 : -1;
            card.setAttribute('aria-current', offset === 0 ? 'true' : 'false');
        });
        dots.forEach((dot, index) => {
            const active = index === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    const stop = () => { if (timer) window.clearInterval(timer); timer = null; };
    const start = () => {
        stop();
        if (!reducedMotion && cards.length > 1) timer = window.setInterval(() => go(current + 1), 4200);
    };
    const go = index => {
        if (!cards.length) return;
        current = (index + cards.length) % cards.length;
        render();
        start();
    };

    previous?.addEventListener('click', () => go(current - 1));
    next?.addEventListener('click', () => go(current + 1));
    dots.forEach(dot => dot.addEventListener('click', () => go(Number(dot.dataset.homeDot) || 0)));
    stage?.addEventListener('click', event => {
        const card = event.target.closest('[data-home-index]');
        if (!card) return;
        const index = Number(card.dataset.homeIndex);
        if (Number.isInteger(index) && index !== current) { event.preventDefault(); go(index); }
    });
    stage?.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight') { event.preventDefault(); go(current - 1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); go(current + 1); }
    });
    stage?.addEventListener('pointerdown', event => { pointerStart = event.clientX; });
    stage?.addEventListener('pointerup', event => {
        if (pointerStart === null) return;
        const delta = event.clientX - pointerStart;
        pointerStart = null;
        if (Math.abs(delta) > 35) go(current + (delta < 0 ? 1 : -1));
    });
    stage?.addEventListener('pointercancel', () => { pointerStart = null; });
    homeCarousel.addEventListener('mouseenter', stop);
    homeCarousel.addEventListener('mouseleave', start);
    homeCarousel.addEventListener('focusin', stop);
    homeCarousel.addEventListener('focusout', event => { if (!homeCarousel.contains(event.relatedTarget)) start(); });
    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

    if (cards.length) { render(); start(); }
}
