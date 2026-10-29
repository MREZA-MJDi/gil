import { useEffect, useMemo, useState } from 'react';
import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const numberFa = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const money = (value, currency = 'تومان') => numberFa(value) + ' ' + currency;
const keyFor = line => String(line.product_id) + ':' + (line.options || []).map(o => Number(o.id)).sort((a, b) => a - b).join(',');
const optionLabel = option => option.type === 'size' ? 'سایز' : 'افزودنی';

function CustomerMenu() {
    const root = document.getElementById('customer-menu-app');
    const token = root?.dataset.token;
    const [data, setData] = useState(null);
    const [cart, setCart] = useState([]);
    const [optionSelections, setOptionSelections] = useState({});
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
                if (!response.ok) throw new Error(response.status === 404
                    ? 'این منو غیرفعال است یا QR دیگر اعتبار ندارد.'
                    : 'بارگذاری منو انجام نشد. اتصال اینترنت را بررسی کن.');
                return response.json();
            })
            .then(payload => { if (active) setData(payload.data); })
            .catch(error => { if (active) setNotice(error.message); });
        return () => { active = false; };
    }, [token]);

    const currency = data?.settings?.currency_label || 'تومان';
    const allProducts = useMemo(() => data?.categories?.flatMap(category => category.products || []) || [], [data]);
    const findProduct = id => allProducts.find(product => Number(product.id) === Number(id));
    const selectedIdsFor = product => optionSelections[product.id] || [];
    const optionTotalFor = (product, ids) => (product?.options || [])
        .filter(option => ids.includes(Number(option.id)))
        .reduce((sum, option) => sum + Number(option.price || 0), 0);

    const subtotal = cart.reduce((sum, line) => {
        const product = findProduct(line.product_id);
        const ids = (line.options || []).map(option => Number(option.id));
        return sum + (Number(product?.base_price || 0) + optionTotalFor(product, ids)) * Number(line.quantity || 0);
    }, 0);
    const tax = Math.floor((Math.max(0, subtotal) * Number(data?.settings?.tax_basis_points || 0) + 5000) / 10000);
    const service = Math.floor((Math.max(0, subtotal) * Number(data?.settings?.service_basis_points || 0) + 5000) / 10000);
    const total = subtotal + tax + service;

    const toggleOption = (product, option, checked) => {
        if (busy || submissionUncertain) return;
        setIdempotencyKey('');
        setOptionSelections(previous => {
            const current = previous[product.id] || [];
            let next;
            if (!checked) {
                next = current.filter(id => Number(id) !== Number(option.id));
            } else if (option.type === 'size') {
                const sizeIds = (product.options || []).filter(item => item.type === 'size').map(item => Number(item.id));
                next = [...current.filter(id => !sizeIds.includes(Number(id))), Number(option.id)];
            } else {
                next = current.includes(Number(option.id)) ? current : [...current, Number(option.id)];
            }
            return { ...previous, [product.id]: next };
        });
    };

    const add = product => {
        if (busy || submissionUncertain) return;
        const options = selectedIdsFor(product).map(Number).sort((a, b) => a - b);
        const nextLine = { product_id: product.id, quantity: 1, options: options.map(id => ({ id })) };
        const nextKey = keyFor(nextLine);
        setIdempotencyKey('');
        setCart(items => {
            const existing = items.find(item => keyFor(item) === nextKey);
            if (existing) return items.map(item => keyFor(item) === nextKey ? { ...item, quantity: item.quantity + 1 } : item);
            return [...items, nextLine];
        });
        setNotice(product.options?.length ? 'محصول با گزینه‌های انتخاب‌شده به سبد اضافه شد.' : '');
    };

    const changeQty = (lineKey, delta) => {
        if (busy || submissionUncertain) return;
        setIdempotencyKey('');
        setCart(items => items
            .map(item => keyFor(item) === lineKey ? { ...item, quantity: item.quantity + delta } : item)
            .filter(item => item.quantity > 0));
    };

    async function submitOrder() {
        if (!cart.length || busy) return;
        setBusy(true);
        setNotice('');
        let response = null;
        const requestKey = idempotencyKey || (crypto.randomUUID
            ? crypto.randomUUID().replaceAll('-', '')
            : 'qr' + Date.now().toString(36) + Math.random().toString(36).slice(2, 24));
        setIdempotencyKey(requestKey);
        try {
            response = await fetch('/menu/' + encodeURIComponent(token) + '/orders', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
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
                const validationMessage = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
                throw new Error(validationMessage || payload.message || 'ثبت سفارش انجام نشد.');
            }
            setCart([]);
            setOptionSelections({});
            setIdempotencyKey('');
            setSubmissionUncertain(false);
            setNotice('سفارش ' + payload.data.number + ' ثبت شد. مبلغ نهایی: ' + money(payload.data.total, currency) + ' — سفارش برای همین میز ارسال شد.');
        } catch (error) {
            if (response && response.status >= 400 && response.status < 500) {
                setIdempotencyKey('');
                setSubmissionUncertain(false);
            } else {
                // A timeout/5xx could happen after the DB commit. Preserve the key and freeze edits to prevent duplicates.
                setSubmissionUncertain(true);
            }
            setNotice(error.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از سفارش تکراری، با همان شناسه دوباره تلاش کن.');
        } finally {
            setBusy(false);
        }
    }

    if (!data && !notice) return <div className="menu-loading">در حال آماده‌سازی منو…</div>;
    if (!data) return <div className="customer-menu-content"><div className="menu-notice" role="alert">{notice}</div><button onClick={() => window.location.reload()}>تلاش دوباره</button></div>;

    return <div className="customer-menu-content">
        {notice && <div className="menu-notice" role="status">{notice}</div>}
        <section className="customer-menu-welcome">
            <div className="customer-menu-brand">
                {data.settings?.logo_url && <img className="customer-menu-logo" src={data.settings.logo_url} alt="" />}
                <div><span>منوی دیجیتال</span><h2>{data.settings?.name || 'منوی کافه'}</h2><p>میز {data.table?.name || 'شما'} · سفارش مستقیم از میز</p></div>
            </div>
            <div className="customer-menu-contact">
                {data.settings?.phone && <span>تلفن: {data.settings.phone}</span>}
                {data.settings?.address && <span>{data.settings.address}</span>}
            </div>
        </section>

        {data.categories?.some(category => category.products?.length) ? data.categories.map(category => category.products?.length ? <section className="customer-category" key={category.id}>
            <div className="customer-category-heading"><h2>{category.name}</h2><span>{numberFa(category.products.length)} محصول</span></div>
            <div className="customer-product-grid">{category.products.map(product => {
                const options = product.options || [];
                const selected = selectedIdsFor(product);
                return <article className="customer-product" key={product.id}>
                    {product.image_url
                        ? <img src={product.image_url} alt={product.name} loading="lazy" />
                        : <div className="customer-product-placeholder" aria-hidden="true">☕</div>}
                    <div className="customer-product-info">
                        <h3>{product.name}</h3>
                        {product.description && <p className="customer-product-description">{product.description}</p>}
                        {product.ingredients && <p className="customer-food-ingredients"><b>ترکیبات:</b> {product.ingredients}</p>}
                        {Array.isArray(product.dietary_tags) && product.dietary_tags.length > 0 && <div className="customer-food-tags">{product.dietary_tags.map(tag => <span key={tag}>{tag}</span>)}</div>}
                        {Array.isArray(product.allergens) && product.allergens.length > 0 && <div className="customer-allergen-warning"><b>آلرژن‌های اعلام‌شده:</b> {product.allergens.join('، ')}<small>اگر حساسیت غذایی داری، قبل از سفارش با کارکنان کافه تأیید کن.</small></div>}
                        {(product.calories !== null && product.calories !== undefined || product.preparation_time_minutes !== null && product.preparation_time_minutes !== undefined) && <div className="customer-food-meta">
                            {product.calories !== null && product.calories !== undefined && <span>{numberFa(product.calories)} کیلوکالری / وعده</span>}
                            {product.preparation_time_minutes !== null && product.preparation_time_minutes !== undefined && <span>آماده‌سازی حدود {numberFa(product.preparation_time_minutes)} دقیقه</span>}
                        </div>}
                        <strong className="customer-product-price">{money(product.base_price, currency)}</strong>
                        {options.length > 0 && <fieldset className="customer-product-options">
                            <legend>انتخاب سایز / افزودنی</legend>
                            {options.map(option => <label key={option.id} className={selected.includes(Number(option.id)) ? 'selected' : ''}>
                                <input
                                    type="checkbox"
                                    checked={selected.includes(Number(option.id))}
                                    disabled={busy || submissionUncertain}
                                    onChange={event => toggleOption(product, option, event.target.checked)}
                                />
                                <span className="customer-option-type">{optionLabel(option)}</span>
                                <span className="customer-option-name">{option.name}</span>
                                <b>{Number(option.price) ? '+' + money(option.price, currency) : 'رایگان'}</b>
                            </label>)}
                            {options.some(option => option.type === 'size') && <small>برای سایز، تنها یک گزینه می‌توان انتخاب کرد؛ افزودنی‌ها قابل ترکیب هستند.</small>}
                        </fieldset>}
                        {selected.length > 0 && <div className="customer-selected-price">قیمت با انتخاب‌ها: <b>{money(Number(product.base_price) + optionTotalFor(product, selected), currency)}</b></div>}
                        <button disabled={busy || submissionUncertain} onClick={() => add(product)}>افزودن به سفارش ＋</button>
                    </div>
                </article>;
            })}</div>
        </section> : null) : <div className="menu-empty">فعلاً محصول فعالی در منو ثبت نشده است. لطفاً از کارکنان کمک بگیرید.</div>}

        <section className="customer-checkout">
            <h2>سبد سفارش <span>{numberFa(cart.reduce((sum, item) => sum + item.quantity, 0))} قلم</span></h2>
            {cart.map(item => {
                const product = findProduct(item.product_id);
                const ids = (item.options || []).map(option => Number(option.id));
                const selectedOptions = (product?.options || []).filter(option => ids.includes(Number(option.id)));
                const lineKey = keyFor(item);
                const unitPrice = Number(product?.base_price || 0) + selectedOptions.reduce((sum, option) => sum + Number(option.price || 0), 0);
                return <div className="cart-row" key={lineKey}>
                    <div className="customer-cart-description">
                        <b>{product?.name || 'محصول'}</b>
                        {selectedOptions.length > 0 && <small>{selectedOptions.map(option => option.name).join('، ')}</small>}
                        <span>{money(unitPrice * item.quantity, currency)}</span>
                    </div>
                    <div className="customer-cart-quantity">
                        <button disabled={busy || submissionUncertain} onClick={() => changeQty(lineKey, -1)} aria-label="کم کردن">−</button>
                        <b>{numberFa(item.quantity)}</b>
                        <button disabled={busy || submissionUncertain} onClick={() => changeQty(lineKey, 1)} aria-label="زیاد کردن">＋</button>
                    </div>
                </div>;
            })}
            <label>نام (اختیاری)<input disabled={busy || submissionUncertain} value={customerName} onChange={e => setCustomerName(e.target.value)} maxLength={150} /></label>
            <label>شماره تماس (اختیاری)<input disabled={busy || submissionUncertain} value={customerPhone} onChange={e => setCustomerPhone(e.target.value)} maxLength={30} inputMode="tel" /></label>
            <label className="customer-payment-preference">روش پرداخت موردنظر
                <select value={paymentPreference} disabled={busy || submissionUncertain} onChange={event => setPaymentPreference(event.target.value)}>
                    <option value="at_counter">پرداخت در صندوق / هنگام تحویل</option>
                    <option value="cash">نقدی</option>
                    <option value="card_terminal">کارت‌خوان</option>
                </select>
                <small>این فقط روش ترجیحی است؛ تا ثبت پرداخت توسط کارکنان، سفارش تسویه‌نشده می‌ماند.</small>
            </label>
            <div className="customer-price-breakdown">
                <p><span>جمع اقلام</span><b>{money(subtotal, currency)}</b></p>
                {Number(data.settings?.tax_basis_points || 0) > 0 && <p><span>مالیات ({numberFa(Number(data.settings.tax_basis_points) / 100)}٪)</span><b>{money(tax, currency)}</b></p>}
                {Number(data.settings?.service_basis_points || 0) > 0 && <p><span>هزینه خدمات ({numberFa(Number(data.settings.service_basis_points) / 100)}٪)</span><b>{money(service, currency)}</b></p>}
                <p className="customer-total"><span>جمع تخمینی نهایی</span><b>{money(total, currency)}</b></p>
                <small>مبلغ نهایی در سرور محاسبه و هنگام ثبت سفارش تأیید می‌شود. در صورت تغییر قیمت یا تنظیمات، مبلغ سرور ملاک است.</small>
            </div>
            <button className="customer-submit" disabled={!cart.length || busy} onClick={submitOrder}>{busy ? 'در حال ثبت…' : submissionUncertain ? 'ارسال مجدد با همان شناسه' : 'ثبت سفارش'}</button>
            {submissionUncertain && <p role="alert" className="customer-uncertain-notice">پاسخ قطعی از سرور نرسید. سبد قفل است؛ برای جلوگیری از سفارش تکراری، فقط با همین محتوا و شناسه دوباره تلاش کن.</p>}
            <p>سفارش به میز {data.table?.name || ''} وصل است. پرداخت از این صفحه انجام نمی‌شود؛ پرداخت را با کارکنان صندوق انجام بده.</p>
        </section>
    </div>;
}

const mount = document.getElementById('customer-menu-app');
if (mount) createRoot(mount).render(<CustomerMenu />);
