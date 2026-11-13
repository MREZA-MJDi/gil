/* Per-table QR menu: catalog and prices are fetched from the server. */
const qrRoot = document.querySelector('[data-customer-qr-menu]');
if (qrRoot) {
    const escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const makeKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    const cart = new Map();
    const productsById = new Map();
    let currency = 'تومان';
    let qrFormKey = makeKey();
    let qrPendingPayload = null;
    let qrUncertainRequest = false;
    let activeCategory = null;
    let activePage = 0;
    let qrCategories = [];
    const pageSize = 4;
    const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ' + currency;

    function featureMarkup(product) {
        const image = product.image_url
            ? '<img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" loading="eager">'
            : '<span data-menu-image-fallback>🍒</span>';
        const options = (product.options || []).map(option => {
            const type = option.type?.value || option.type || 'addon';
            return '<label class="kg-option-row"><input type="checkbox" data-qr-option="' + Number(option.id) + '" data-option-type="' + escapeHtml(type) + '" data-option-name="' + escapeHtml(option.name) + '" data-option-price="' + Number(option.price || 0) + '">' +
                '<span>' + escapeHtml(option.name) + ' <small>' + (type === 'size' ? 'سایز' : 'افزودنی') + '</small></span><b>' +
                (Number(option.price || 0) > 0 ? '+' + money(option.price) : 'رایگان') + '</b></label>';
        }).join('');
        return '<div class="plate">' + image + '</div><div class="info" data-qr-product="' + Number(product.id) + '">' +
            '<h2>' + escapeHtml(product.name) + '</h2><p>' + escapeHtml(product.description || 'با مواد اولیه تازه و با دقت در خانه گیلاسی آماده می‌شود.') + '</p>' +
            (product.ingredients ? '<p><strong>ترکیبات:</strong> ' + escapeHtml(product.ingredients) + '</p>' : '') +
            (Array.isArray(product.allergens) && product.allergens.length ? '<p><strong>آلرژن‌ها:</strong> ' + escapeHtml(product.allergens.join('، ')) + '</p>' : '') +
            (Array.isArray(product.dietary_tags) && product.dietary_tags.length ? '<p>' + escapeHtml(product.dietary_tags.join(' · ')) + '</p>' : '') +
            (options ? '<div class="product-extra">' + options + '</div>' : '') +
            '<div class="buy"><div class="price">' + money(product.base_price) + '<small>قیمت پایه</small></div>' +
            '<button type="button" class="order" data-qr-add data-product-id="' + Number(product.id) + '" data-product-name="' + escapeHtml(product.name) + '" data-product-price="' + Number(product.base_price || 0) + '">افزودن به سفارش +</button></div>' +
            (product.preparation_time_minutes ? '<p class="product-extra">◷ ' + Number(product.preparation_time_minutes) + ' دقیقه آماده‌سازی</p>' : '') +
            '</div>';
    }

    function renderCategory(category) {
        const products = category.products || [];
        const visible = products.slice(activePage * pageSize, activePage * pageSize + pageSize);
        const first = products[activePage * pageSize] || products[0];
        const cards = visible.map(product =>
            '<button type="button" class="card ' + (first && product.id === first.id ? 'is-active' : '') + '" data-qr-select="' + Number(product.id) + '" aria-pressed="' + (first && product.id === first.id ? 'true' : 'false') + '">' +
            (product.image_url ? '<img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" loading="lazy">' : '') +
            '<span class="p">' + new Intl.NumberFormat('fa-IR').format(Number(product.base_price || 0)) + '</span>' +
            '<span class="n">' + escapeHtml(product.name) + '</span><span class="s">' + escapeHtml(product.preparation_time_minutes ? product.preparation_time_minutes + ' دقیقه آماده‌سازی' : 'پیشنهاد خانه گیلاسی') + '</span></button>'
        ).join('');
        const pages = Math.max(1, Math.ceil(products.length / pageSize));
        const dots = pages > 1 ? '<div class="dots">' + Array.from({ length: pages }, (_, index) =>
            '<button type="button" data-qr-page="' + index + '" class="' + (index === activePage ? 'is-active' : '') + '" aria-label="صفحه ' + (index + 1) + '" aria-current="' + (index === activePage ? 'page' : 'false') + '"></button>'
        ).join('') + '</div>' : '';
        return '<section class="category-panel" data-qr-category-panel="' + Number(category.id) + '">' +
            '<div class="stage" data-qr-featured>' + (first ? featureMarkup(first) : '') + '</div>' +
            '<div class="cards">' + (cards || '<p class="kg-cart-empty">در این دسته محصول فعالی وجود ندارد.</p>') + '</div>' + dots + '</section>';
    }

    function renderQrCatalog(categories, tableName) {
        categories.forEach(category => (category.products || []).forEach(product => productsById.set(Number(product.id), product)));
        activeCategory = categories[0]?.id ?? null;
        const categoryNav = categories.map((category, index) =>
            '<button type="button" data-qr-category="' + Number(category.id) + '" class="' + (index === 0 ? 'is-active' : '') + '" aria-pressed="' + (index === 0 ? 'true' : 'false') + '">' + escapeHtml(category.name) + '</button>'
        ).join('');
        const panels = categories.map((category, index) =>
            '<div data-qr-panel-wrap="' + Number(category.id) + '" ' + (index ? 'hidden' : '') + '>' + renderCategory(category) + '</div>'
        ).join('');
        qrRoot.innerHTML =
            '<div class="kg-qr-intro"><div><span class="kg-eyebrow">خوش آمدی</span><h1>از میز خودت سفارش بده.</h1><p>محصولات را انتخاب کن و سفارش را برای همین میز ثبت کن.</p></div><span class="kg-table-badge">میز ' + escapeHtml(tableName) + '</span></div>' +
            '<div class="menu-layout"><nav class="cats" aria-label="دسته‌بندی منو">' + (categoryNav || '<span>منو</span>') + '</nav>' +
            '<div class="kg-qr-panels">' + (panels || '<div class="kg-empty-state"><span>🍒</span><b>منو در حال تکمیل است.</b></div>') + '</div></div>' +
            '<section class="kg-qr-cart" aria-live="polite"><h2>سفارش میز ' + escapeHtml(tableName) + '</h2>' +
            '<div class="kg-qr-cart-lines" id="kg-qr-cart-lines"><p class="kg-cart-empty">هنوز محصولی انتخاب نشده است.</p></div>' +
            '<div class="kg-qr-cart-total"><span>جمع تخمینی</span><b id="kg-qr-total">۰ ' + escapeHtml(currency) + '</b></div>' +
            '<form id="kg-qr-order-form"><input name="customer_name" maxlength="150" placeholder="نام شما (اختیاری)">' +
            '<input name="customer_phone" maxlength="30" inputmode="tel" placeholder="شماره تماس (اختیاری)">' +
            '<button type="submit" id="kg-qr-submit" disabled>ثبت سفارش برای این میز</button>' +
            '<p class="kg-qr-status" id="kg-qr-status" role="status"></p></form></section>';
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
        const categoryButton = event.target.closest('[data-qr-category]');
        if (categoryButton) {
            activeCategory = Number(categoryButton.dataset.qrCategory);
            activePage = 0;
            qrRoot.querySelectorAll('[data-qr-category]').forEach(button => {
                const active = Number(button.dataset.qrCategory) === activeCategory;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            qrRoot.querySelectorAll('[data-qr-panel-wrap]').forEach(panel => { panel.hidden = Number(panel.dataset.qrPanelWrap) !== activeCategory; });
            return;
        }
        const pageButton = event.target.closest('[data-qr-page]');
        if (pageButton) {
            const category = qrCategories.find(item => Number(item.id) === activeCategory);
            if (!category) return;
            activePage = Number(pageButton.dataset.qrPage) || 0;
            const wrap = qrRoot.querySelector('[data-qr-panel-wrap="' + activeCategory + '"]');
            if (wrap) wrap.innerHTML = renderCategory(category);
            return;
        }
        const select = event.target.closest('[data-qr-select]');
        if (select) {
            const product = productsById.get(Number(select.dataset.qrSelect));
            const panel = select.closest('[data-qr-panel-wrap]');
            if (!product || !panel) return;
            panel.querySelector('[data-qr-featured]').innerHTML = featureMarkup(product);
            panel.querySelectorAll('[data-qr-select]').forEach(button => {
                const active = Number(button.dataset.qrSelect) === Number(product.id);
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            return;
        }
        const add = event.target.closest('[data-qr-add]');
        if (add) {
            if (qrUncertainRequest) return;
            const card = add.closest('[data-qr-product]') || add.closest('.info');
            const options = [...(card?.querySelectorAll('[data-qr-option]:checked') || [])].map(input => ({
                id: Number(input.dataset.qrOption), type: input.dataset.optionType || 'addon',
                name: input.dataset.optionName || '', price: Number(input.dataset.optionPrice || 0)
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
                channel: 'dine_in', payment_preference: 'at_counter', discount: 0,
                customer_name: String(fields.get('customer_name') || '').trim() || null,
                customer_phone: String(fields.get('customer_phone') || '').trim() || null,
                items: [...cart.values()].map(item => ({ product_id: item.product_id, quantity: item.quantity, options: item.options.map(option => ({ id: option.id })) })),
                idempotency_key: qrFormKey
            };
        }
        submit.disabled = true;
        if (status) status.textContent = 'در حال ثبت سفارش…';
        let response = null;
        try {
            response = await fetch(qrRoot.dataset.orderUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Idempotency-Key': qrFormKey },
                body: JSON.stringify(qrPendingPayload)
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
                qrFormKey = makeKey(); qrPendingPayload = null; qrUncertainRequest = false;
                qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], #kg-qr-order-form input').forEach(control => { control.disabled = false; });
            } else {
                qrUncertainRequest = true;
                qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], #kg-qr-order-form input').forEach(control => { control.disabled = true; });
                if (submit) submit.textContent = 'ارسال دوباره با همان اطلاعات';
            }
            if (status) status.textContent = error?.message || 'ارتباط با سرور برقرار نشد.';
            if (submit) submit.disabled = cart.size === 0;
        }
    });

    /* Keep the fetched catalog available to delegated page navigation. */
    fetch(qrRoot.dataset.dataUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(response => response.json().then(result => ({ response, result })))
        .then(({ response, result }) => {
            if (!response.ok) throw new Error(result?.message || 'منوی این میز در دسترس نیست.');
            const data = result.data || {};
            currency = data.settings?.currency_label || 'تومان';
            qrCategories = data.categories || [];
            renderQrCatalog(qrCategories, data.table?.name || qrRoot.dataset.tableName || '');
            renderQrCart();
        })
        .catch(error => {
            qrRoot.innerHTML = '<div class="kg-empty-state"><span>🍒</span><b>' + escapeHtml(error?.message || 'دریافت منو انجام نشد.') + '</b><p>صفحه را تازه کن یا با کارکنان مجموعه هماهنگ کن.</p></div>';
        });
}
