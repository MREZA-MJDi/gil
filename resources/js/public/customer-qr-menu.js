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
            '<section class="kg-qr-success" id="kg-qr-success" role="status" aria-live="polite" hidden>' +
                '<div class="kg-qr-success-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="13"></circle><path d="M9 16.5l4.5 4.5L23 11.5"></path></svg></div>' +
                '<div class="kg-qr-success-copy"><small>ثبت سفارش موفق</small><strong>سفارشت با موفقیت ثبت شد</strong>' +
                '<p data-qr-success-message>سفارش برای میز ثبت شد. پرداخت هنوز انجام نشده است.</p>' +
                '<span class="kg-qr-success-number" data-qr-success-number></span></div>' +
                '<button type="button" data-qr-new-order>ثبت سفارش جدید</button>' +
            '</section>' +
            '<div class="kg-qr-catalog"><div class="kg-qr-catalog-list">' +
            (sections || '<div class="kg-empty-state"><b>منوی این میز هنوز تکمیل نشده است.</b><p>برای راهنمایی با کارکنان مجموعه هماهنگ کن.</p></div>') +
            '</div><aside class="kg-qr-cart"><h2>سفارش میز ' + escapeHtml(tableName) + '</h2>' +
            '<div class="kg-qr-cart-lines" id="kg-qr-cart-lines"><p class="kg-cart-empty">هنوز محصولی انتخاب نشده است.</p></div>' +
            '<div class="kg-qr-cart-total"><span>جمع تخمینی</span><b id="kg-qr-total">۰ ' + escapeHtml(currency) + '</b></div>' +
            '<form id="kg-qr-order-form"><input name="customer_name" maxlength="150" placeholder="نام شما (اختیاری)">' +
            '<input name="customer_phone" maxlength="30" inputmode="tel" placeholder="شماره تماس (اختیاری)">' +
            '<button type="submit" id="kg-qr-submit" disabled>ثبت سفارش برای این میز</button>' +
            '<p class="kg-qr-status" id="kg-qr-status" role="status"></p></form></aside></div>';
    }

    function showQrSuccess(orderNumber, tableName) {
        const panel = document.getElementById('kg-qr-success');
        if (!panel) return;

        const message = panel.querySelector('[data-qr-success-message]');
        const number = panel.querySelector('[data-qr-success-number]');
        if (message) {
            message.textContent = 'سفارش برای میز ' + (tableName || qrRoot.dataset.tableName || '') +
                ' به مجموعه ارسال شد؛ پرداخت هنوز انجام نشده و هنگام تسویه در صندوق انجام می‌شود.';
        }
        if (number) number.textContent = orderNumber ? 'شماره سفارش: ' + orderNumber : 'شماره سفارش ثبت شد';

        panel.hidden = false;
        panel.classList.remove('is-visible');
        void panel.offsetWidth;
        panel.classList.add('is-visible');
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            qrRoot.innerHTML = '<div class="kg-empty-state"><b>' + escapeHtml(error?.message || 'دریافت منو انجام نشد.') + '</b><p>صفحه را تازه کن یا با کارکنان مجموعه هماهنگ کن.</p></div>';
        }
    }

    qrRoot.addEventListener('click', event => {
        const newOrder = event.target.closest('[data-qr-new-order]');
        if (newOrder) {
            const panel = document.getElementById('kg-qr-success');
            panel?.classList.remove('is-visible');
            if (panel) panel.hidden = true;
            document.getElementById('kg-qr-order-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

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
            add.classList.remove('is-added');
            void add.offsetWidth;
            add.classList.add('is-added');
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
            if (status) status.textContent = 'سفارش با موفقیت ثبت شد.';
            showQrSuccess(result?.data?.number || '', qrRoot.dataset.tableName || '');
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

