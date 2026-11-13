/* QR table menu: catalog and prices are read only from the per-table backend endpoint. */
const qrRoot = document.querySelector('[data-customer-qr-menu]');

if (qrRoot) {
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const makeKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '')
        || ('qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    const cart = new Map();
    const categoriesEl = qrRoot.querySelector('[data-qr-categories]');
    const featureEl = qrRoot.querySelector('[data-qr-feature]');
    const cardsEl = qrRoot.querySelector('[data-qr-cards]');
    let currency = 'تومان';
    let tableName = qrRoot.dataset.tableName || '';
    let categories = [];
    let activeCategoryId = null;
    let activeProductId = null;
    let qrFormKey = makeKey();
    let pendingPayload = null;
    let uncertainRequest = false;

    const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ' + currency;
    const productsForActiveCategory = () => categories.find(category => String(category.id) === String(activeCategoryId))?.products || [];

    function renderCategories() {
        if (!categoriesEl) return;
        categoriesEl.innerHTML = categories.map(category =>
            '<button type="button" data-qr-category="' + Number(category.id) + '" class="' +
            (String(category.id) === String(activeCategoryId) ? 'is-active' : '') + '" aria-pressed="' +
            (String(category.id) === String(activeCategoryId) ? 'true' : 'false') + '">' +
            escapeHtml(category.name) + '</button>'
        ).join('');
    }

    function renderFeature(product) {
        if (!featureEl || !product) {
            if (featureEl) featureEl.innerHTML = '<div class="menu-empty">در این دسته محصول فعالی ثبت نشده است.</div>';
            return;
        }

        const image = product.image_url
            ? '<img data-qr-image src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" loading="eager" decoding="async"><span class="menu-plate-fallback" data-qr-image-fallback hidden>🍒</span>'
            : '<div class="menu-plate-fallback">🍒</div>';
        const options = (product.options || []).map(option => {
            const type = option.type?.value || option.type || 'addon';
            return '<label><input type="checkbox" data-qr-option="' + Number(option.id) +
                '" data-option-type="' + escapeHtml(type) + '" data-option-name="' + escapeHtml(option.name) +
                '" data-option-price="' + Number(option.price || 0) + '"> ' + escapeHtml(option.name) +
                ' <small>' + (Number(option.price || 0) > 0 ? '+' + money(option.price) : 'رایگان') + '</small></label>';
        }).join('');

        featureEl.innerHTML =
            '<article class="menu-featured" data-qr-product="' + Number(product.id) + '" data-product-card>' +
                '<div class="menu-plate">' + image + '</div>' +
                '<div class="menu-info">' +
                    '<span class="menu-kicker">انتخاب از منوی میز ' + escapeHtml(tableName) + '</span>' +
                    '<h2>' + escapeHtml(product.name) + '</h2>' +
                    '<p>' + escapeHtml(product.description || 'با عشق و مواد اولیه تازه در خانه گیلاسی آماده می‌شود.') + '</p>' +
                    (product.ingredients ? '<small class="menu-ingredients">ترکیبات: ' + escapeHtml(product.ingredients) + '</small>' : '') +
                    (Array.isArray(product.allergens) && product.allergens.length ? '<small class="menu-ingredients">آلرژن‌ها: ' + escapeHtml(product.allergens.join('، ')) + '</small>' : '') +
                    (options ? '<div class="menu-options">' + options + '</div>' : '') +
                    '<div class="menu-buy"><div class="menu-price">' + money(product.base_price) + '<small>قیمت پایه</small></div>' +
                    '<button type="button" class="menu-order" data-qr-add data-product-id="' + Number(product.id) +
                    '" data-product-name="' + escapeHtml(product.name) + '" data-product-price="' + Number(product.base_price || 0) +
                    '">افزودن به سفارش +</button></div>' +
                '</div>' +
            '</article>';
    }

    function renderCards() {
        if (!cardsEl) return;
        const products = productsForActiveCategory();
        cardsEl.innerHTML = products.map(product =>
            '<button type="button" class="menu-card ' + (String(product.id) === String(activeProductId) ? 'is-active' : '') +
            '" data-qr-select="' + Number(product.id) + '" aria-pressed="' +
            (String(product.id) === String(activeProductId) ? 'true' : 'false') + '">' +
            (product.image_url
                ? '<img data-qr-image src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" loading="lazy"><span data-qr-image-fallback hidden>🍒</span>'
                : '<span class="menu-card-placeholder">🍒</span>') +
            '<span class="card-price">' + money(product.base_price) + '</span>' +
            '<span class="card-name">' + escapeHtml(product.name) + '</span>' +
            '<span class="card-sub">' + (product.preparation_time_minutes ? Number(product.preparation_time_minutes) + ' دقیقه آماده‌سازی' : 'مشاهده جزئیات') + '</span>' +
            '</button>'
        ).join('');
    }

    function selectProduct(productId) {
        const product = productsForActiveCategory().find(item => String(item.id) === String(productId));
        if (!product) return;
        activeProductId = product.id;
        renderFeature(product);
        renderCards();
    }

    function selectCategory(categoryId) {
        const category = categories.find(item => String(item.id) === String(categoryId));
        if (!category) return;
        activeCategoryId = category.id;
        activeProductId = category.products?.[0]?.id ?? null;
        renderCategories();
        renderCards();
        selectProduct(activeProductId);
    }

    function renderCart() {
        const lines = document.getElementById('kg-qr-cart-lines');
        const totalEl = document.getElementById('kg-qr-total');
        const submit = document.getElementById('kg-qr-submit');
        const total = [...cart.values()].reduce((sum, item) =>
            sum + item.quantity * (item.price + item.options.reduce((value, option) => value + option.price, 0)), 0);

        if (lines) {
            lines.innerHTML = cart.size ? [...cart.values()].map(item =>
                '<div class="menu-cart-row"><div><b>' + escapeHtml(item.name) + '</b><small>' +
                escapeHtml(item.options.map(option => option.name).join('، ') || 'بدون افزودنی') +
                '</small><small>' + item.quantity + ' × ' + money(item.price + item.options.reduce((value, option) => value + option.price, 0)) + '</small></div>' +
                '<div><button type="button" data-qr-qty="-1" data-qr-key="' + escapeHtml(item.key) + '" aria-label="کم کردن">−</button> ' +
                item.quantity + ' <button type="button" data-qr-qty="1" data-qr-key="' + escapeHtml(item.key) + '" aria-label="زیاد کردن">＋</button></div></div>'
            ).join('') : '<div class="menu-empty">هنوز محصولی انتخاب نشده است.</div>';
        }
        if (totalEl) totalEl.textContent = money(total);
        if (submit) submit.disabled = cart.size === 0;
        qrRoot.querySelectorAll('[data-qr-add], [data-qr-option], [data-qr-qty], '# + 'kg-qr-order-form input').forEach(control => {\n            control.disabled = uncertainRequest;\n        });
    }

    async function loadQrMenu() {
        try {
            const response = await fetch(qrRoot.dataset.dataUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result?.message || 'منوی این میز در دسترس نیست.');
            const data = result.data || {};
            currency = data.settings?.currency_label || 'تومان';
            tableName = data.table?.name || tableName;
            categories = (data.categories || []).map(category => ({
                ...category,
                products: Array.isArray(category.products) ? category.products : []
            })).filter(category => category.products.length > 0);

            if (!categories.length) {
                if (featureEl) featureEl.innerHTML = '<div class="menu-empty">در حال حاضر محصول فعالی برای این میز ثبت نشده است.</div>';
                if (cardsEl) cardsEl.replaceChildren();
                if (categoriesEl) categoriesEl.replaceChildren();
                return;
            }
            selectCategory(categories[0].id);
            renderCart();
        } catch (error) {
            if (featureEl) featureEl.innerHTML = '<div class="menu-empty">' + escapeHtml(error?.message || 'دریافت منو انجام نشد.') + '<p>صفحه را تازه کن یا با کارکنان مجموعه هماهنگ کن.</p></div>';
        }
    }

    qrRoot.addEventListener('click', event => {
        const categoryButton = event.target.closest('[data-qr-category]');
        if (categoryButton) {
            selectCategory(categoryButton.dataset.qrCategory);
            return;
        }

        const productButton = event.target.closest('[data-qr-select]');
        if (productButton) {
            selectProduct(productButton.dataset.qrSelect);
            return;
        }

        const add = event.target.closest('[data-qr-add]');
        if (add) {
            if (uncertainRequest) return;
            const feature = add.closest('[data-qr-product]');
            const options = [...feature.querySelectorAll('[data-qr-option]:checked')].map(input => ({
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
            else cart.set(key, {
                key,
                product_id: productId,
                name: add.dataset.productName || '',
                price: Number(add.dataset.productPrice || 0),
                quantity: 1,
                options
            });
            renderCart();
            const status = document.getElementById('kg-qr-status');
            if (status) status.textContent = 'به سفارش اضافه شد.';
            return;
        }

        const quantity = event.target.closest('[data-qr-qty]');
        if (quantity) {
            if (uncertainRequest) return;
            const item = cart.get(quantity.dataset.qrKey);
            if (!item) return;
            item.quantity += Number(quantity.dataset.qrQty);
            if (item.quantity < 1) cart.delete(item.key);
            else item.quantity = Math.min(100, item.quantity);
            renderCart();
        }
    });

    qrRoot.addEventListener('error', event => {
        const image = event.target;
        if (!image.matches?.('[data-qr-image]')) return;
        image.hidden = true;
        const fallback = image.nextElementSibling;
        if (fallback?.matches('[data-qr-image-fallback]')) fallback.hidden = false;
    }, true);

    qrRoot.addEventListener('submit', async event => {
        if (event.target.id !== 'kg-qr-order-form') return;
        event.preventDefault();
        if (!cart.size || uncertainRequest) return;

        const form = event.target;
        const submit = document.getElementById('kg-qr-submit');
        const status = document.getElementById('kg-qr-status');
        if (!pendingPayload) {
            const fields = new FormData(form);
            pendingPayload = {
                channel: 'dine_in',
                payment_preference: 'at_counter',
                discount: 0,
                customer_name: String(fields.get('customer_name') || '').trim() || null,
                customer_phone: String(fields.get('customer_phone') || '').trim() || null,
                items: [...cart.values()].map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    options: item.options.map(option => ({ id: option.id }))
                })),
                idempotency_key: qrFormKey
            };
        }

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
                body: JSON.stringify(pendingPayload)
            });
            const result = await response.json();
            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت سفارش انجام نشد.');
            }

            const orderNumber = result?.data?.number || '';
            cart.clear();
            qrFormKey = makeKey();
            pendingPayload = null;
            uncertainRequest = false;
            form.reset();
            renderCart();
            if (status) status.textContent = 'سفارش ' + orderNumber + ' برای میز ' + tableName + ' ثبت شد. پرداخت هنوز ثبت نشده است.';
        } catch (error) {
            const definiteClientError = response && response.status >= 400 && response.status < 500 && response.status !== 429;
            if (definiteClientError) {
                qrFormKey = makeKey();
                pendingPayload = null;
                uncertainRequest = false;
                renderCart();
            } else {
                /* Preserve payload and key on uncertain network/server failures to prevent duplicate orders. */
                uncertainRequest = true;
                renderCart();
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'ارسال دوباره با همان اطلاعات';
                }
            }
            if (status) status.textContent = error?.message || 'ارتباط با سرور برقرار نشد.';
        }
    });

    loadQrMenu();
}
