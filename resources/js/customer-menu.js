const root = document.getElementById('customer-menu-app');

if (root) {
    const token = root.dataset.token || '';
    const numberFa = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
    const money = (value, currency) => numberFa(value) + ' ' + (currency || 'تومان');
    const escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const safeUrl = value => {
        if (!value) return '';
        try {
            const url = new URL(String(value), window.location.origin);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch {
            return '';
        }
    };
    const lineKey = line => String(line.product_id) + ':' + (line.options || [])
        .map(option => Number(option.id)).sort((a, b) => a - b).join(',');

    let data = null;
    let notice = '';
    let activeCategoryId = null;
    let selectedProductId = null;
    let cart = [];
    let optionSelections = new Map();
    let customerName = '';
    let customerPhone = '';
    let paymentPreference = 'at_counter';
    let busy = false;
    let submissionUncertain = false;
    let idempotencyKey = '';
    let pendingPayload = null;

    const categories = () => (data?.categories || [])
        .filter(category => (category.products || []).length > 0);
    const allProducts = () => (data?.categories || [])
        .flatMap(category => category.products || []);
    const activeCategory = () => categories().find(category => Number(category.id) === Number(activeCategoryId))
        || categories()[0] || null;
    const selectedProduct = () => {
        const products = activeCategory()?.products || [];
        return products.find(product => Number(product.id) === Number(selectedProductId)) || products[0] || null;
    };
    const findProduct = id => allProducts().find(product => Number(product.id) === Number(id));
    const selectedIdsFor = product => new Set(optionSelections.get(Number(product.id)) || []);
    const selectedOptionsFor = product => (product?.options || [])
        .filter(option => selectedIdsFor(product).has(Number(option.id)));
    const optionTotalFor = product => selectedOptionsFor(product)
        .reduce((sum, option) => sum + Number(option.price || 0), 0);
    const currency = () => data?.settings?.currency_label || 'تومان';

    function cartSummary() {
        const count = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        const subtotal = cart.reduce((sum, line) => {
            const product = findProduct(line.product_id);
            const ids = new Set((line.options || []).map(option => Number(option.id)));
            const optionsTotal = (product?.options || [])
                .filter(option => ids.has(Number(option.id)))
                .reduce((sum, option) => sum + Number(option.price || 0), 0);
            return sum + (Number(product?.base_price || 0) + optionsTotal) * Number(line.quantity || 0);
        }, 0);
        const taxRate = Number(data?.settings?.tax_basis_points || 0);
        const serviceRate = Number(data?.settings?.service_basis_points || 0);
        const tax = Math.floor((Math.max(0, subtotal) * taxRate + 5000) / 10000);
        const service = Math.floor((Math.max(0, subtotal) * serviceRate + 5000) / 10000);
        return {count, subtotal, tax, service, total: subtotal + tax + service, taxRate, serviceRate};
    }

    function render() {
        if (!data) {
            root.innerHTML = notice
                ? '<div class="gil-menu-error" role="alert"><span class="gil-error-mark" aria-hidden="true">!</span><h1>منو در دسترس نیست</h1><p>' + escapeHtml(notice) + '</p><button type="button" data-action="reload">تلاش دوباره</button></div>'
                : '<div class="gil-menu-loading" role="status"><span class="gil-loading-mark" aria-hidden="true">گ</span><span>در حال آماده‌سازی منوی میز…</span></div>';
            return;
        }

        const cats = categories();
        const category = activeCategory();
        const product = selectedProduct();
        const products = category?.products || [];
        const selectedIds = product ? selectedIdsFor(product) : new Set();
        const selectedPrice = Number(product?.base_price || 0) + (product ? optionTotalFor(product) : 0);
        const summary = cartSummary();
        const locked = busy || submissionUncertain;
        const tableName = data.table?.name || 'شما';
        const restaurantName = data.settings?.name || 'خانه گیلاسی';
        const logoUrl = safeUrl(data.settings?.logo_url);

        const categoryMarkup = cats.map(item => {
            const active = Number(category?.id) === Number(item.id);
            return '<button type="button" data-action="category" data-id="' + Number(item.id) + '" class="' + (active ? 'active' : '') + '" aria-pressed="' + String(active) + '"' + (locked ? ' disabled' : '') + '><span>' + escapeHtml(item.name) + '</span><small>' + numberFa((item.products || []).length) + '</small></button>';
        }).join('');

        const optionsMarkup = product && (product.options || []).length
            ? '<fieldset class="gil-product-options"><legend>سفارشی‌سازی انتخاب</legend>' +
                product.options.map(option => {
                    const checked = selectedIds.has(Number(option.id));
                    const optionPrice = Number(option.price || 0);
                    const kind = option.type === 'size' ? 'سایز' : 'افزودنی';
                    return '<label class="' + (checked ? 'selected' : '') + '">' +
                        '<input type="checkbox" data-option-id="' + Number(option.id) + '" data-product-id="' + Number(product.id) + '"' + (checked ? ' checked' : '') + (locked ? ' disabled' : '') + '>' +
                        '<span class="gil-option-kind">' + kind + '</span>' +
                        '<span class="gil-option-name">' + escapeHtml(option.name) + '</span>' +
                        '<b>' + (optionPrice > 0 ? '+' + money(optionPrice, currency()) : 'رایگان') + '</b>' +
                    '</label>';
                }).join('') +
                ((product.options || []).some(option => option.type === 'size')
                    ? '<small>برای سایز فقط یک گزینه و برای افزودنی‌ها چند گزینه قابل انتخاب است.</small>' : '') +
              '</fieldset>'
            : '';

        const featuredMarkup = product
            ? '<section class="gil-featured-dish" aria-labelledby="gil-featured-title">' +
                '<div class="gil-featured-copy">' +
                    '<span class="gil-section-kicker">از دسته ' + escapeHtml(category?.name || 'منو') + '</span>' +
                    '<h2 id="gil-featured-title">' + escapeHtml(product.name) + '</h2>' +
                    (product.description ? '<p class="gil-featured-description">' + escapeHtml(product.description) + '</p>' : '') +
                    (product.ingredients ? '<p class="gil-featured-ingredients"><b>ترکیبات</b><span>' + escapeHtml(product.ingredients) + '</span></p>' : '') +
                    ((product.dietary_tags || []).length ? '<div class="gil-food-tags">' + product.dietary_tags.map(tag => '<span>' + escapeHtml(tag) + '</span>').join('') + '</div>' : '') +
                    ((product.allergens || []).length ? '<div class="gil-allergen-note"><b>آلرژن‌های اعلام‌شده:</b> ' + escapeHtml(product.allergens.join('، ')) + '<small>در صورت حساسیت غذایی، پیش از سفارش با کارکنان هماهنگ کن.</small></div>' : '') +
                    '<div class="gil-food-meta">' +
                        (product.calories != null ? '<span>' + numberFa(product.calories) + ' کیلوکالری</span>' : '') +
                        (product.preparation_time_minutes != null ? '<span>آماده‌سازی حدود ' + numberFa(product.preparation_time_minutes) + ' دقیقه</span>' : '') +
                    '</div>' +
                    optionsMarkup +
                    '<div class="gil-featured-buy"><div class="gil-featured-price"><small>قیمت انتخاب فعلی</small><strong>' + money(selectedPrice, currency()) + '</strong></div>' +
                    '<button type="button" data-action="add" data-id="' + Number(product.id) + '"' + (locked ? ' disabled' : '') + '>افزودن به سفارش <span aria-hidden="true">＋</span></button></div>' +
                '</div>' +
                '<div class="gil-featured-visual"><span class="gil-image-orbit gil-orbit-one" aria-hidden="true"></span><span class="gil-image-orbit gil-orbit-two" aria-hidden="true"></span>' +
                    (safeUrl(product.image_url)
                        ? '<img src="' + escapeHtml(safeUrl(product.image_url)) + '" alt="' + escapeHtml(product.name) + '" fetchpriority="high">'
                        : '<div class="gil-featured-placeholder" aria-hidden="true"><span>گ</span><small>' + escapeHtml(restaurantName) + '</small></div>') +
                    '<span class="gil-featured-category">' + escapeHtml(category?.name || 'منو') + '</span></div>' +
              '</section>'
            : '<section class="gil-menu-empty"><span aria-hidden="true">✳</span><h2>منو در حال آماده‌شدن است.</h2><p>به‌زودی انتخاب‌های این مجموعه در همین صفحه نمایش داده می‌شوند.</p></section>';

        const dishMarkup = product
            ? '<section class="gil-dish-section" aria-labelledby="gil-dish-section-title"><div class="gil-dish-heading"><div><span class="gil-section-kicker">انتخاب‌های این دسته</span><h2 id="gil-dish-section-title">' + escapeHtml(category?.name || '') + '</h2></div><span>' + numberFa(products.length) + ' انتخاب</span></div><div class="gil-dish-grid">' +
                products.map(item => {
                    const active = Number(item.id) === Number(product.id);
                    const image = safeUrl(item.image_url);
                    return '<button type="button" data-action="product" data-id="' + Number(item.id) + '" class="gil-dish-card' + (active ? ' active' : '') + '" aria-pressed="' + String(active) + '"' + (locked ? ' disabled' : '') + '>' +
                        '<span class="gil-dish-thumb">' + (image ? '<img src="' + escapeHtml(image) + '" alt="" loading="lazy">' : '<span aria-hidden="true">گ</span>') + '</span>' +
                        '<span class="gil-dish-card-copy"><strong>' + escapeHtml(item.name) + '</strong><small>' + money(item.base_price, currency()) + '</small></span>' +
                        '<span class="gil-dish-select" aria-hidden="true">↙</span></button>';
                }).join('') +
              '</div></section>'
            : '';

        const cartMarkup = cart.length
            ? cart.map(item => {
                const itemProduct = findProduct(item.product_id);
                const ids = new Set((item.options || []).map(option => Number(option.id)));
                const chosen = (itemProduct?.options || []).filter(option => ids.has(Number(option.id)));
                const unitPrice = Number(itemProduct?.base_price || 0) + chosen.reduce((sum, option) => sum + Number(option.price || 0), 0);
                const key = lineKey(item);
                return '<article class="gil-cart-row"><div class="gil-cart-product"><b>' + escapeHtml(itemProduct?.name || 'محصول') + '</b>' +
                    (chosen.length ? '<small>' + escapeHtml(chosen.map(option => option.name).join('، ')) + '</small>' : '') +
                    '<strong>' + money(unitPrice * item.quantity, currency()) + '</strong></div>' +
                    '<div class="gil-cart-quantity" aria-label="تعداد ' + escapeHtml(itemProduct?.name || 'محصول') + '">' +
                    '<button type="button" data-action="quantity" data-key="' + escapeHtml(key) + '" data-delta="-1" aria-label="کم کردن تعداد"' + (locked ? ' disabled' : '') + '>−</button>' +
                    '<b>' + numberFa(item.quantity) + '</b>' +
                    '<button type="button" data-action="quantity" data-key="' + escapeHtml(key) + '" data-delta="1" aria-label="افزایش تعداد"' + (locked ? ' disabled' : '') + '>＋</button></div></article>';
            }).join('')
            : '<div class="gil-cart-empty"><span aria-hidden="true">＋</span><b>هنوز انتخابی به سبد اضافه نکرده‌ای.</b><small>از بخش منو یک محصول انتخاب کن و به سفارش اضافه کن.</small></div>';

        root.innerHTML = '<div class="gil-menu-app">' +
            '<header class="gil-menu-header"><a class="gil-menu-brand" href="/menu/' + encodeURIComponent(token) + '" aria-label="منوی میز ' + escapeHtml(tableName) + '">' +
                (logoUrl ? '<img src="' + escapeHtml(logoUrl) + '" alt="">' : '<span class="gil-menu-brand-mark" aria-hidden="true">گ</span>') +
                '<span class="gil-menu-brand-copy"><strong>' + escapeHtml(restaurantName) + '</strong><small>طعم خوب، انتخاب آگاهانه</small></span></a>' +
                '<div class="gil-menu-header-meta"><span class="gil-table-chip"><i aria-hidden="true"></i> میز ' + escapeHtml(tableName) + '</span>' +
                '<button type="button" class="gil-header-cart" data-action="scroll-order"><span class="gil-cart-icon" aria-hidden="true">▱</span><span>سبد سفارش</span><b>' + numberFa(summary.count) + '</b></button></div></header>' +
            '<div class="gil-menu-layout"><aside class="gil-category-rail" aria-label="دسته‌بندی منو"><span class="gil-rail-label">انتخاب دسته</span>' + categoryMarkup + '</aside>' +
            '<main class="gil-menu-main"><section class="gil-menu-cover" data-menu-cover><div><span class="gil-cover-eyebrow">منوی دیجیتال · ' + escapeHtml(restaurantName) + '</span><h1>با حوصله انتخاب کن،<br><em>با لذت نوش جان کن.</em></h1><p>منوی همین میز با قیمت‌های به‌روز؛ انتخاب کن و سفارشت را مستقیم ثبت کن.</p></div><span class="gil-cover-seal" aria-hidden="true">G<span>✳</span></span></section>' +
            (notice ? '<div class="gil-menu-notice" role="status">' + escapeHtml(notice) + '</div>' : '') +
            featuredMarkup + dishMarkup +
            '<section class="gil-checkout" id="gil-menu-order" aria-labelledby="gil-checkout-title"><div class="gil-checkout-heading"><div><span class="gil-section-kicker">سفارش میز شما</span><h2 id="gil-checkout-title">سبد انتخاب‌ها</h2></div><span class="gil-checkout-count">' + numberFa(summary.count) + ' قلم</span></div>' +
            '<div class="gil-checkout-grid"><div class="gil-cart-panel">' + cartMarkup + '</div>' +
            '<form class="gil-checkout-form" id="gil-menu-checkout-form">' +
                '<label><span>نام (اختیاری)</span><input name="customer_name" data-field="customer-name" value="' + escapeHtml(customerName) + '" maxlength="150" autocomplete="name"' + (locked ? ' disabled' : '') + '></label>' +
                '<label><span>شماره تماس (اختیاری)</span><input name="customer_phone" data-field="customer-phone" value="' + escapeHtml(customerPhone) + '" maxlength="30" inputmode="tel" autocomplete="tel"' + (locked ? ' disabled' : '') + '></label>' +
                '<fieldset class="gil-payment-preference"><legend>روش پرداخت دلخواه</legend>' +
                    '<label><input type="radio" name="payment_preference" value="at_counter"' + (paymentPreference === 'at_counter' ? ' checked' : '') + (locked ? ' disabled' : '') + '><span><b>پرداخت در صندوق</b><small>پرداخت هنگام تسویه حضوری</small></span></label>' +
                    '<label><input type="radio" name="payment_preference" value="card_terminal"' + (paymentPreference === 'card_terminal' ? ' checked' : '') + (locked ? ' disabled' : '') + '><span><b>کارت‌خوان</b><small>ثبت پرداخت توسط کارکنان</small></span></label>' +
                    '<label><input type="radio" name="payment_preference" value="cash"' + (paymentPreference === 'cash' ? ' checked' : '') + (locked ? ' disabled' : '') + '><span><b>نقدی</b><small>تسویه حضوری با صندوق</small></span></label>' +
                '</fieldset>' +
                '<div class="gil-price-breakdown"><p><span>جمع اقلام</span><b>' + money(summary.subtotal, currency()) + '</b></p>' +
                    (summary.taxRate > 0 ? '<p><span>مالیات (' + numberFa(summary.taxRate / 100) + '٪)</span><b>' + money(summary.tax, currency()) + '</b></p>' : '') +
                    (summary.serviceRate > 0 ? '<p><span>هزینه خدمات (' + numberFa(summary.serviceRate / 100) + '٪)</span><b>' + money(summary.service, currency()) + '</b></p>' : '') +
                    '<p class="gil-total-row"><span>جمع تخمینی</span><b>' + money(summary.total, currency()) + '</b></p><small>مبلغ نهایی در سرور محاسبه می‌شود. انتخاب روش پرداخت به‌تنهایی به معنی تسویه سفارش نیست.</small></div>' +
                '<button type="submit" class="gil-submit-order"' + (!cart.length || busy ? ' disabled' : '') + '>' +
                    (busy ? 'در حال ثبت سفارش…' : submissionUncertain ? 'ارسال دوباره با همان شناسه' : 'ثبت سفارش برای این میز') + '<span aria-hidden="true">↗</span></button>' +
                (submissionUncertain ? '<p class="gil-uncertain-notice" role="alert">پاسخ قطعی از سرور نرسید. برای جلوگیری از ثبت تکراری، سبد و شناسه سفارش قفل شده‌اند؛ فقط با همان شناسه دوباره تلاش کن.</p>' : '') +
                '<p class="gil-checkout-footnote">سفارش مستقیماً به میز ' + escapeHtml(tableName) + ' متصل می‌شود. پرداخت پس از ثبت سفارش با کارکنان انجام خواهد شد.</p>' +
            '</form></div></section>' +
            '<footer class="gil-menu-footer"><span>' + escapeHtml(restaurantName) + '</span><small>با احترام، برای لحظه‌های خوش شما</small></footer></main></div></div>';

        const cover = root.querySelector('[data-menu-cover]');
        const coverUrl = safeUrl(data.settings?.menu_cover_url);
        if (cover && coverUrl) {
            cover.style.backgroundImage = 'linear-gradient(90deg, rgba(18, 16, 14, .91), rgba(18, 16, 14, .48)), url("' + coverUrl.replaceAll('"', '%22') + '")';
        }
    }

    function chooseCategory(id) {
        const category = categories().find(item => Number(item.id) === Number(id));
        if (!category || busy || submissionUncertain) return;
        activeCategoryId = category.id;
        selectedProductId = category.products?.[0]?.id || null;
        notice = '';
        render();
    }

    function chooseProduct(id) {
        const product = (activeCategory()?.products || []).find(item => Number(item.id) === Number(id));
        if (!product || busy || submissionUncertain) return;
        selectedProductId = product.id;
        notice = '';
        render();
    }

    function toggleOption(productId, optionId, checked) {
        if (busy || submissionUncertain) return;
        const product = findProduct(productId);
        const option = product?.options?.find(item => Number(item.id) === Number(optionId));
        if (!product || !option) return;
        const selected = new Set(optionSelections.get(Number(product.id)) || []);
        if (!checked) {
            selected.delete(Number(option.id));
        } else if (option.type === 'size') {
            (product.options || []).filter(item => item.type === 'size')
                .forEach(item => selected.delete(Number(item.id)));
            selected.add(Number(option.id));
        } else {
            selected.add(Number(option.id));
        }
        optionSelections.set(Number(product.id), [...selected]);
        idempotencyKey = '';
        pendingPayload = null;
        render();
    }

    function addProduct(id) {
        if (busy || submissionUncertain) return;
        const product = findProduct(id);
        if (!product) return;
        const options = [...selectedIdsFor(product)].map(Number).sort((a, b) => a - b);
        const line = {product_id: Number(product.id), quantity: 1, options: options.map(id => ({id}))};
        const key = lineKey(line);
        const existing = cart.find(item => lineKey(item) === key);
        if (existing) existing.quantity = Math.min(100, existing.quantity + 1);
        else cart.push(line);
        idempotencyKey = '';
        pendingPayload = null;
        notice = 'به سفارش اضافه شد؛ می‌توانی انتخابت را ادامه بدهی.';
        render();
    }

    function changeQuantity(key, delta) {
        if (busy || submissionUncertain) return;
        const item = cart.find(line => lineKey(line) === key);
        if (!item) return;
        item.quantity += Number(delta);
        if (item.quantity <= 0) cart = cart.filter(line => lineKey(line) !== key);
        else item.quantity = Math.min(100, item.quantity);
        idempotencyKey = '';
        pendingPayload = null;
        render();
    }

    async function submitOrder() {
        if (!cart.length || busy) return;
        busy = true;
        notice = '';
        const requestKey = idempotencyKey || (globalThis.crypto?.randomUUID
            ? globalThis.crypto.randomUUID().replaceAll('-', '')
            : 'qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 24));
        idempotencyKey = requestKey;
        if (!pendingPayload) {
            pendingPayload = {
                channel: 'dine_in',
                items: cart.map(line => ({
                    product_id: line.product_id,
                    quantity: line.quantity,
                    options: (line.options || []).map(option => ({id: option.id})),
                })),
                customer_name: customerName.trim() || null,
                customer_phone: customerPhone.trim() || null,
                payment_preference: paymentPreference,
                discount: 0,
            };
        }

        render();
        let response = null;
        try {
            response = await fetch('/menu/' + encodeURIComponent(token) + '/orders', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Idempotency-Key': requestKey,
                },
                body: JSON.stringify({...pendingPayload, idempotency_key: requestKey}),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const validation = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
                throw new Error(validation || payload?.message || 'ثبت سفارش انجام نشد؛ اطلاعات را بررسی کن.');
            }
            if (!payload?.data?.number) {
                throw new Error('پاسخ ثبت سفارش از سرور کامل نبود؛ برای جلوگیری از ثبت تکراری، با همان شناسه دوباره تلاش کن.');
            }

            cart = [];
            optionSelections = new Map();
            idempotencyKey = '';
            pendingPayload = null;
            submissionUncertain = false;
            notice = 'سفارش ' + payload.data.number + ' ثبت شد. مبلغ نهایی: ' + money(payload.data.total, currency()) + '؛ سفارش برای میز ' + (data.table?.name || 'شما') + ' ارسال شد. پرداخت هنوز ثبت نشده است.';
        } catch (error) {
            if (response && response.status >= 400 && response.status < 500 && response.status !== 429) {
                idempotencyKey = '';
                pendingPayload = null;
                submissionUncertain = false;
            } else {
                submissionUncertain = true;
            }
            notice = error?.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از سفارش تکراری با همان شناسه دوباره تلاش کن.';
        } finally {
            busy = false;
            render();
        }
    }

    root.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button || !root.contains(button)) return;
        const action = button.dataset.action;
        if (action === 'reload') window.location.reload();
        if (action === 'category') chooseCategory(button.dataset.id);
        if (action === 'product') chooseProduct(button.dataset.id);
        if (action === 'add') addProduct(button.dataset.id);
        if (action === 'quantity') changeQuantity(button.dataset.key, button.dataset.delta);
        if (action === 'scroll-order') document.getElementById('gil-menu-order')?.scrollIntoView({behavior:'smooth', block:'start'});
    });

    root.addEventListener('change', event => {
        const optionInput = event.target.closest('[data-option-id]');
        if (optionInput) {
            toggleOption(optionInput.dataset.productId, optionInput.dataset.optionId, optionInput.checked);
            return;
        }
        if (event.target.name === 'payment_preference') {
            paymentPreference = event.target.value;
        }
    });

    root.addEventListener('input', event => {
        if (event.target.dataset.field === 'customer-name') customerName = event.target.value;
        if (event.target.dataset.field === 'customer-phone') customerPhone = event.target.value;
    });

    root.addEventListener('submit', event => {
        if (event.target.id !== 'gil-menu-checkout-form') return;
        event.preventDefault();
        submitOrder();
    });

    render();
    if (!token) {
        notice = 'کد میز معتبر نیست.';
        render();
    } else {
        fetch('/menu/' + encodeURIComponent(token) + '/data', {
            headers: {Accept: 'application/json'},
            credentials: 'same-origin',
        })
            .then(async response => {
                if (!response.ok) {
                    throw new Error(response.status === 404
                        ? 'این منو غیرفعال شده یا کد میز دیگر معتبر نیست.'
                        : 'بارگذاری منو انجام نشد. اتصال اینترنت را بررسی و دوباره تلاش کن.');
                }
                return response.json();
            })
            .then(payload => {
                data = payload.data;
                activeCategoryId = categories()[0]?.id || null;
                selectedProductId = activeCategory()?.products?.[0]?.id || null;
                render();
            })
            .catch(error => {
                notice = error?.message || 'بارگذاری منو با مشکل روبه‌رو شد.';
                render();
            });
    }
}
