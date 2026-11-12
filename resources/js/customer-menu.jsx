import { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const numberFa = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const money = (value, currency = 'تومان') => numberFa(value) + ' ' + currency;
const lineKey = line => String(line.product_id) + ':' + (line.options || []).map(option => Number(option.id)).sort((a, b) => a - b).join(',');
const optionLabel = option => option.type === 'size' ? 'سایز' : 'افزودنی';

function CustomerMenu() {
    const root = document.getElementById('customer-menu-app');
    const token = root?.dataset.token;

    const [data, setData] = useState(null);
    const [cart, setCart] = useState([]);
    const [optionSelections, setOptionSelections] = useState({});
    const [activeCategoryId, setActiveCategoryId] = useState(null);
    const [selectedProductId, setSelectedProductId] = useState(null);
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [paymentPreference, setPaymentPreference] = useState('at_counter');
    const [idempotencyKey, setIdempotencyKey] = useState('');
    const [submissionUncertain, setSubmissionUncertain] = useState(false);

    useEffect(() => {
        let active = true;

        fetch('/menu/' + encodeURIComponent(token) + '/data', {
            headers: { Accept: 'application/json' },
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
                if (active) setData(payload.data);
            })
            .catch(error => {
                if (active) setNotice(error.message || 'بارگذاری منو با مشکل روبه‌رو شد.');
            });

        return () => {
            active = false;
        };
    }, [token]);

    const categories = useMemo(
        () => (data?.categories || []).filter(category => (category.products || []).length > 0),
        [data],
    );
    const activeCategory = categories.find(category => Number(category.id) === Number(activeCategoryId)) || categories[0] || null;
    const products = activeCategory?.products || [];
    const selectedProduct = products.find(product => Number(product.id) === Number(selectedProductId)) || products[0] || null;
    const allProducts = useMemo(
        () => (data?.categories || []).flatMap(category => category.products || []),
        [data],
    );
    const findProduct = id => allProducts.find(product => Number(product.id) === Number(id));
    const selectedIdsFor = product => optionSelections[product.id] || [];
    const optionTotalFor = (product, ids) => (product?.options || [])
        .filter(option => ids.includes(Number(option.id)))
        .reduce((sum, option) => sum + Number(option.price || 0), 0);

    const currency = data?.settings?.currency_label || 'تومان';
    const cartCount = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const subtotal = cart.reduce((sum, line) => {
        const product = findProduct(line.product_id);
        const ids = (line.options || []).map(option => Number(option.id));
        return sum + (Number(product?.base_price || 0) + optionTotalFor(product, ids)) * Number(line.quantity || 0);
    }, 0);
    const tax = Math.floor((Math.max(0, subtotal) * Number(data?.settings?.tax_basis_points || 0) + 5000) / 10000);
    const service = Math.floor((Math.max(0, subtotal) * Number(data?.settings?.service_basis_points || 0) + 5000) / 10000);
    const total = subtotal + tax + service;

    function chooseCategory(category) {
        setActiveCategoryId(category.id);
        setSelectedProductId(category.products?.[0]?.id || null);
        setNotice('');
    }

    function chooseProduct(product) {
        setSelectedProductId(product.id);
        setNotice('');
    }

    function toggleOption(product, option, checked) {
        if (busy || submissionUncertain) return;

        setIdempotencyKey('');
        setOptionSelections(previous => {
            const current = previous[product.id] || [];
            let next;

            if (!checked) {
                next = current.filter(id => Number(id) !== Number(option.id));
            } else if (option.type === 'size') {
                const sizeIds = (product.options || [])
                    .filter(item => item.type === 'size')
                    .map(item => Number(item.id));
                next = [...current.filter(id => !sizeIds.includes(Number(id))), Number(option.id)];
            } else {
                next = current.includes(Number(option.id)) ? current : [...current, Number(option.id)];
            }

            return { ...previous, [product.id]: next };
        });
    }

    function add(product) {
        if (!product || busy || submissionUncertain) return;

        const options = selectedIdsFor(product).map(Number).sort((a, b) => a - b);
        const nextLine = {
            product_id: product.id,
            quantity: 1,
            options: options.map(id => ({ id })),
        };
        const key = lineKey(nextLine);

        setIdempotencyKey('');
        setCart(items => {
            const existing = items.find(item => lineKey(item) === key);

            if (existing) {
                return items.map(item => lineKey(item) === key
                    ? { ...item, quantity: Math.min(100, item.quantity + 1) }
                    : item);
            }

            return [...items, nextLine];
        });

        setNotice('به سفارش اضافه شد؛ می‌توانی انتخابت را ادامه بدهی.');
    }

    function changeQuantity(key, delta) {
        if (busy || submissionUncertain) return;

        setIdempotencyKey('');
        setCart(items => items
            .map(item => lineKey(item) === key
                ? { ...item, quantity: item.quantity + delta }
                : item)
            .filter(item => item.quantity > 0 && item.quantity <= 100));
    }

    async function submitOrder() {
        if (!cart.length || busy) return;

        setBusy(true);
        setNotice('');

        const requestKey = idempotencyKey || (globalThis.crypto?.randomUUID
            ? globalThis.crypto.randomUUID().replaceAll('-', '')
            : 'qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 24));

        setIdempotencyKey(requestKey);
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
                body: JSON.stringify({
                    channel: 'dine_in',
                    items: cart,
                    customer_name: customerName.trim() || null,
                    customer_phone: customerPhone.trim() || null,
                    payment_preference: paymentPreference,
                    discount: 0,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = payload?.errors
                    ? Object.values(payload.errors).flat().find(Boolean)
                    : null;
                throw new Error(validationMessage || payload.message || 'ثبت سفارش انجام نشد.');
            }

            setCart([]);
            setOptionSelections({});
            setIdempotencyKey('');
            setSubmissionUncertain(false);
            setNotice(
                'سفارش ' + payload.data.number + ' ثبت شد. مبلغ نهایی: ' +
                money(payload.data.total, currency) + '؛ سفارش برای میز ' +
                (data?.table?.name || 'شما') + ' ارسال شد.',
            );
        } catch (error) {
            if (response && response.status >= 400 && response.status < 500 && response.status !== 429) {
                setIdempotencyKey('');
                setSubmissionUncertain(false);
            } else {
                // A timeout or 5xx may happen after commit. Keep the same key and payload to prevent duplicates.
                setSubmissionUncertain(true);
            }

            setNotice(error.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از سفارش تکراری با همان شناسه دوباره تلاش کن.');
        } finally {
            setBusy(false);
        }
    }

    function scrollToOrder() {
        document.getElementById('gil-menu-order')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (!data && !notice) {
        return (
            <div className="gil-menu-loading" role="status">
                <span className="gil-loading-mark" aria-hidden="true">گ</span>
                <span>در حال آماده‌سازی منو…</span>
            </div>
        );
    }

    if (!data) {
        return (
            <div className="gil-menu-error" role="alert">
                <span className="gil-error-mark" aria-hidden="true">!</span>
                <h1>منو در دسترس نیست</h1>
                <p>{notice}</p>
                <button type="button" onClick={() => window.location.reload()}>تلاش دوباره</button>
            </div>
        );
    }

    const selectedIds = selectedProduct ? selectedIdsFor(selectedProduct) : [];
    const selectedOptionTotal = selectedProduct ? optionTotalFor(selectedProduct, selectedIds) : 0;
    const selectedPrice = Number(selectedProduct?.base_price || 0) + selectedOptionTotal;
    const menuCoverStyle = data.settings?.menu_cover_url
        ? { backgroundImage: 'linear-gradient(90deg, rgba(18, 16, 14, .91), rgba(18, 16, 14, .48)), url("' + data.settings.menu_cover_url + '")' }
        : undefined;

    return (
        <div className="gil-menu-app">
            <header className="gil-menu-header">
                <a className="gil-menu-brand" href="/" aria-label="صفحه اصلی خانه گیلاسی">
                    {data.settings?.logo_url
                        ? <img src={data.settings.logo_url} alt="" />
                        : <span className="gil-menu-brand-mark" aria-hidden="true">گ</span>}
                    <span className="gil-menu-brand-copy">
                        <strong>{data.settings?.name || 'خانه گیلاسی'}</strong>
                        <small>طعم خوب، انتخاب آگاهانه</small>
                    </span>
                </a>

                <div className="gil-menu-header-meta">
                    <span className="gil-table-chip"><i aria-hidden="true"></i> میز {data.table?.name || 'شما'}</span>
                    <button type="button" className="gil-header-cart" onClick={scrollToOrder}>
                        <span className="gil-cart-icon" aria-hidden="true">▱</span>
                        <span>سبد سفارش</span>
                        <b>{numberFa(cartCount)}</b>
                    </button>
                </div>
            </header>

            <div className="gil-menu-layout">
                <aside className="gil-category-rail" aria-label="دسته‌بندی منو">
                    <span className="gil-rail-label">انتخاب دسته</span>
                    {categories.map(category => (
                        <button
                            type="button"
                            key={category.id}
                            className={Number(activeCategory?.id) === Number(category.id) ? 'active' : ''}
                            onClick={() => chooseCategory(category)}
                            aria-pressed={Number(activeCategory?.id) === Number(category.id)}
                        >
                            <span>{category.name}</span>
                            <small>{numberFa(category.products.length)}</small>
                        </button>
                    ))}
                </aside>

                <main className="gil-menu-main">
                    <section className="gil-menu-cover" style={menuCoverStyle}>
                        <div>
                            <span className="gil-cover-eyebrow">منوی دیجیتال · خانه گیلاسی</span>
                            <h1>با حوصله انتخاب کن،<br /><em>با لذت نوش جان کن.</em></h1>
                            <p>منوی همین میز با قیمت‌های به‌روز؛ انتخاب کن و سفارشت را مستقیم ثبت کن.</p>
                        </div>
                        <span className="gil-cover-seal" aria-hidden="true">G<span>✳</span></span>
                    </section>

                    {notice && <div className="gil-menu-notice" role="status">{notice}</div>}

                    {selectedProduct ? (
                        <section className="gil-featured-dish" aria-labelledby="gil-featured-title">
                            <div className="gil-featured-copy">
                                <span className="gil-section-kicker">از دسته {activeCategory?.name || 'منو'}</span>
                                <h2 id="gil-featured-title">{selectedProduct.name}</h2>
                                {selectedProduct.description && <p className="gil-featured-description">{selectedProduct.description}</p>}

                                {selectedProduct.ingredients && (
                                    <p className="gil-featured-ingredients">
                                        <b>ترکیبات</b>
                                        <span>{selectedProduct.ingredients}</span>
                                    </p>
                                )}

                                {(selectedProduct.dietary_tags || []).length > 0 && (
                                    <div className="gil-food-tags">
                                        {selectedProduct.dietary_tags.map(tag => <span key={tag}>{tag}</span>)}
                                    </div>
                                )}

                                {(selectedProduct.allergens || []).length > 0 && (
                                    <div className="gil-allergen-note">
                                        <b>آلرژن‌های اعلام‌شده:</b> {selectedProduct.allergens.join('، ')}
                                        <small>در صورت حساسیت غذایی، پیش از سفارش با کارکنان هماهنگ کن.</small>
                                    </div>
                                )}

                                <div className="gil-food-meta">
                                    {selectedProduct.calories !== null && selectedProduct.calories !== undefined
                                        && <span>{numberFa(selectedProduct.calories)} کیلوکالری</span>}
                                    {selectedProduct.preparation_time_minutes !== null && selectedProduct.preparation_time_minutes !== undefined
                                        && <span>آماده‌سازی حدود {numberFa(selectedProduct.preparation_time_minutes)} دقیقه</span>}
                                </div>

                                {(selectedProduct.options || []).length > 0 && (
                                    <fieldset className="gil-product-options">
                                        <legend>سفارشی‌سازی انتخاب</legend>
                                        {selectedProduct.options.map(option => (
                                            <label key={option.id} className={selectedIds.includes(Number(option.id)) ? 'selected' : ''}>
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(Number(option.id))}
                                                    disabled={busy || submissionUncertain}
                                                    onChange={event => toggleOption(selectedProduct, option, event.target.checked)}
                                                />
                                                <span className="gil-option-kind">{optionLabel(option)}</span>
                                                <span className="gil-option-name">{option.name}</span>
                                                <b>{Number(option.price) > 0 ? '+' + money(option.price, currency) : 'رایگان'}</b>
                                            </label>
                                        ))}
                                        {selectedProduct.options.some(option => option.type === 'size')
                                            && <small>برای سایز فقط یک گزینه و برای افزودنی‌ها چند گزینه قابل انتخاب است.</small>}
                                    </fieldset>
                                )}

                                <div className="gil-featured-buy">
                                    <div className="gil-featured-price">
                                        <small>قیمت انتخاب فعلی</small>
                                        <strong>{money(selectedPrice, currency)}</strong>
                                    </div>
                                    <button type="button" onClick={() => add(selectedProduct)} disabled={busy || submissionUncertain}>
                                        افزودن به سفارش <span aria-hidden="true">＋</span>
                                    </button>
                                </div>
                            </div>

                            <div className="gil-featured-visual">
                                <span className="gil-image-orbit gil-orbit-one" aria-hidden="true"></span>
                                <span className="gil-image-orbit gil-orbit-two" aria-hidden="true"></span>
                                {selectedProduct.image_url
                                    ? <img src={selectedProduct.image_url} alt={selectedProduct.name} fetchPriority="high" />
                                    : <div className="gil-featured-placeholder" aria-hidden="true"><span>گ</span><small>خانه گیلاسی</small></div>}
                                <span className="gil-featured-category">{activeCategory?.name || 'منو'}</span>
                            </div>
                        </section>
                    ) : (
                        <section className="gil-menu-empty">
                            <span aria-hidden="true">✳</span>
                            <h2>منو در حال آماده‌شدن است.</h2>
                            <p>به‌زودی انتخاب‌های این مجموعه در همین صفحه نمایش داده می‌شوند.</p>
                        </section>
                    )}

                    {selectedProduct && (
                        <section className="gil-dish-section" aria-labelledby="gil-dish-section-title">
                            <div className="gil-dish-heading">
                                <div>
                                    <span className="gil-section-kicker">انتخاب‌های این دسته</span>
                                    <h2 id="gil-dish-section-title">{activeCategory?.name}</h2>
                                </div>
                                <span>{numberFa(products.length)} انتخاب</span>
                            </div>

                            <div className="gil-dish-grid">
                                {products.map(product => (
                                    <button
                                        type="button"
                                        key={product.id}
                                        className={'gil-dish-card' + (Number(selectedProduct.id) === Number(product.id) ? ' active' : '')}
                                        onClick={() => chooseProduct(product)}
                                        aria-pressed={Number(selectedProduct.id) === Number(product.id)}
                                    >
                                        <span className="gil-dish-thumb">
                                            {product.image_url
                                                ? <img src={product.image_url} alt="" loading="lazy" />
                                                : <span aria-hidden="true">گ</span>}
                                        </span>
                                        <span className="gil-dish-card-copy">
                                            <strong>{product.name}</strong>
                                            <small>{money(product.base_price, currency)}</small>
                                        </span>
                                        <span className="gil-dish-select" aria-hidden="true">↙</span>
                                    </button>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="gil-checkout" id="gil-menu-order" aria-labelledby="gil-checkout-title">
                        <div className="gil-checkout-heading">
                            <div>
                                <span className="gil-section-kicker">سفارش میز شما</span>
                                <h2 id="gil-checkout-title">سبد انتخاب‌ها</h2>
                            </div>
                            <span className="gil-checkout-count">{numberFa(cartCount)} قلم</span>
                        </div>

                        <div className="gil-checkout-grid">
                            <div className="gil-cart-panel">
                                {cart.length === 0 ? (
                                    <div className="gil-cart-empty">
                                        <span aria-hidden="true">＋</span>
                                        <b>هنوز انتخابی به سبد اضافه نکرده‌ای.</b>
                                        <small>از بخش منو یک محصول انتخاب کن و به سفارش اضافه کن.</small>
                                    </div>
                                ) : cart.map(item => {
                                    const product = findProduct(item.product_id);
                                    const ids = (item.options || []).map(option => Number(option.id));
                                    const options = (product?.options || []).filter(option => ids.includes(Number(option.id)));
                                    const unitPrice = Number(product?.base_price || 0) + options.reduce((sum, option) => sum + Number(option.price || 0), 0);
                                    const key = lineKey(item);

                                    return (
                                        <article className="gil-cart-row" key={key}>
                                            <div className="gil-cart-product">
                                                <b>{product?.name || 'محصول'}</b>
                                                {options.length > 0 && <small>{options.map(option => option.name).join('، ')}</small>}
                                                <strong>{money(unitPrice * item.quantity, currency)}</strong>
                                            </div>
                                            <div className="gil-cart-quantity" aria-label={'تعداد ' + (product?.name || 'محصول')}>
                                                <button type="button" disabled={busy || submissionUncertain} onClick={() => changeQuantity(key, -1)} aria-label="کم کردن تعداد">−</button>
                                                <b>{numberFa(item.quantity)}</b>
                                                <button type="button" disabled={busy || submissionUncertain} onClick={() => changeQuantity(key, 1)} aria-label="افزایش تعداد">＋</button>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>

                            <div className="gil-checkout-form">
                                <label>
                                    <span>نام (اختیاری)</span>
                                    <input disabled={busy || submissionUncertain} value={customerName} onChange={event => setCustomerName(event.target.value)} maxLength={150} autoComplete="name" />
                                </label>
                                <label>
                                    <span>شماره تماس (اختیاری)</span>
                                    <input disabled={busy || submissionUncertain} value={customerPhone} onChange={event => setCustomerPhone(event.target.value)} maxLength={30} inputMode="tel" autoComplete="tel" />
                                </label>

                                <fieldset className="gil-payment-preference">
                                    <legend>روش پرداخت دلخواه</legend>
                                    <label>
                                        <input type="radio" name="gil-payment-preference" value="at_counter" checked={paymentPreference === 'at_counter'} disabled={busy || submissionUncertain} onChange={() => setPaymentPreference('at_counter')} />
                                        <span><b>پرداخت در صندوق</b><small>پرداخت هنگام تسویه حضوری</small></span>
                                    </label>
                                    <label>
                                        <input type="radio" name="gil-payment-preference" value="card_terminal" checked={paymentPreference === 'card_terminal'} disabled={busy || submissionUncertain} onChange={() => setPaymentPreference('card_terminal')} />
                                        <span><b>کارت‌خوان</b><small>ثبت پرداخت توسط کارکنان</small></span>
                                    </label>
                                    <label>
                                        <input type="radio" name="gil-payment-preference" value="cash" checked={paymentPreference === 'cash'} disabled={busy || submissionUncertain} onChange={() => setPaymentPreference('cash')} />
                                        <span><b>نقدی</b><small>تسویه حضوری با صندوق</small></span>
                                    </label>
                                </fieldset>

                                <div className="gil-price-breakdown">
                                    <p><span>جمع اقلام</span><b>{money(subtotal, currency)}</b></p>
                                    {Number(data.settings?.tax_basis_points || 0) > 0
                                        && <p><span>مالیات ({numberFa(Number(data.settings.tax_basis_points) / 100)}٪)</span><b>{money(tax, currency)}</b></p>}
                                    {Number(data.settings?.service_basis_points || 0) > 0
                                        && <p><span>هزینه خدمات ({numberFa(Number(data.settings.service_basis_points) / 100)}٪)</span><b>{money(service, currency)}</b></p>}
                                    <p className="gil-total-row"><span>جمع تخمینی</span><b>{money(total, currency)}</b></p>
                                    <small>مبلغ نهایی در سرور محاسبه می‌شود. انتخاب روش پرداخت به‌تنهایی به معنی تسویه سفارش نیست.</small>
                                </div>

                                <button
                                    type="button"
                                    className="gil-submit-order"
                                    disabled={!cart.length || busy}
                                    onClick={submitOrder}
                                >
                                    {busy ? 'در حال ثبت سفارش…' : submissionUncertain ? 'ارسال دوباره با همان شناسه' : 'ثبت سفارش برای این میز'}
                                    <span aria-hidden="true">↗</span>
                                </button>

                                {submissionUncertain && (
                                    <p className="gil-uncertain-notice" role="alert">
                                        پاسخ قطعی از سرور نرسید. برای جلوگیری از ثبت تکراری، سبد و شناسه سفارش قفل شده‌اند؛ فقط با همان شناسه دوباره تلاش کن.
                                    </p>
                                )}
                                <p className="gil-checkout-footnote">سفارش مستقیماً به میز {data.table?.name || 'شما'} متصل می‌شود. پرداخت پس از ثبت سفارش با کارکنان انجام خواهد شد.</p>
                            </div>
                        </div>
                    </section>

                    <footer className="gil-menu-footer">
                        <span>{data.settings?.name || 'خانه گیلاسی'}</span>
                        <small>با احترام، برای لحظه‌های خوش شما</small>
                    </footer>
                </main>
            </div>

            {cartCount > 0 && (
                <button type="button" className="gil-mobile-cart" onClick={scrollToOrder}>
                    <span>مشاهده سبد · {numberFa(cartCount)} قلم</span>
                    <b>{money(total, currency)}</b>
                </button>
            )}
        </div>
    );
}

const mount = document.getElementById('customer-menu-app');
if (mount) createRoot(mount).render(<CustomerMenu />);
