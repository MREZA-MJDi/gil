import React, { useMemo, useState } from 'react';

const formatMoney = value => new Intl.NumberFormat('fa-IR-u-nu-arabext', { maximumFractionDigits: 0 })
    .format(Number(value || 0));
const formatNumber = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const money = (value, currency) => formatMoney(value) + ' ' + (currency || 'تومان');
const makeKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '')
    || ('pos' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));

function StockLabel({ product }) {
    if (product.stock_quantity === null || product.stock_quantity === undefined) {
        return <span className="pos-stock pos-stock-open">بدون رهگیری موجودی</span>;
    }
    const stock = Number(product.stock_quantity);
    return <span className={'pos-stock ' + (stock <= 0 ? 'pos-stock-empty' : stock <= Number(product.low_stock_threshold || 0) ? 'pos-stock-low' : 'pos-stock-open')}>
        {stock <= 0 ? 'ناموجود' : 'موجودی ' + formatNumber(stock)}
    </span>;
}

export default function PosPage({
    products = [],
    categories = [],
    tables = [],
    cashShift,
    settings,
    submitting = false,
    onSubmit,
    onOpenCashier,
    onPrint,
    onViewOrders,
}) {
    const activeProducts = useMemo(() => (products || []).filter(product =>
        product.status === 'active'
        && product.category?.is_active !== false
        && (product.stock_quantity === null || product.stock_quantity === undefined || Number(product.stock_quantity) > 0)
    ), [products]);
    const [categoryId, setCategoryId] = useState('all');
    const [query, setQuery] = useState('');
    const [selectedProductId, setSelectedProductId] = useState(() => String(activeProducts[0]?.id || ''));
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [selectedQuantity, setSelectedQuantity] = useState('1');
    const [selectedLineNote, setSelectedLineNote] = useState('');
    const [cart, setCart] = useState([]);
    const [channel, setChannel] = useState('dine_in');
    const [tableId, setTableId] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [customerNote, setCustomerNote] = useState('');
    const [discount, setDiscount] = useState('0');
    const [paymentMethod, setPaymentMethod] = useState('card_terminal');
    const [paymentReference, setPaymentReference] = useState('');
    const [paymentNote, setPaymentNote] = useState('');
    const [orderKey, setOrderKey] = useState(makeKey);
    const [paymentKey, setPaymentKey] = useState(makeKey);
    const [message, setMessage] = useState('');
    const [lastOrder, setLastOrder] = useState(null);
    const [paymentPending, setPaymentPending] = useState(false);

    const currency = settings?.currency_label || 'تومان';
    const selectedProduct = activeProducts.find(product => String(product.id) === selectedProductId) || null;
    const selectedProductOptions = (selectedProduct?.options || []).filter(option => option.is_active);
    const selectedProductInCart = cart
        .filter(line => Number(line.product_id) === Number(selectedProduct?.id))
        .reduce((sum, line) => sum + Number(line.quantity || 0), 0);
    const remainingStock = selectedProduct?.stock_quantity === null || selectedProduct?.stock_quantity === undefined
        ? null
        : Math.max(0, Number(selectedProduct.stock_quantity) - selectedProductInCart);
    const filteredProducts = activeProducts.filter(product => {
        const matchesCategory = categoryId === 'all' || String(product.category_id) === String(categoryId);
        const needle = query.trim().toLocaleLowerCase();
        const matchesQuery = !needle || [product.name, product.category?.name, product.description]
            .some(value => String(value || '').toLocaleLowerCase().includes(needle));
        return matchesCategory && matchesQuery;
    });

    const subtotal = cart.reduce((sum, line) => {
        const product = activeProducts.find(item => Number(item.id) === Number(line.product_id));
        const optionTotal = line.options.reduce((value, optionId) =>
            value + Number(product?.options?.find(option => Number(option.id) === Number(optionId))?.price || 0), 0);
        return sum + (Number(product?.base_price || 0) + optionTotal) * Number(line.quantity || 0);
    }, 0);
    const safeDiscount = Math.max(0, Math.min(subtotal, Number(discount || 0)));
    const taxable = subtotal - safeDiscount;
    const estimatedTax = Math.round(taxable * Number(settings?.tax_basis_points || 0) / 10000);
    const estimatedService = Math.round(taxable * Number(settings?.service_basis_points || 0) / 10000);
    const estimatedTotal = taxable + estimatedTax + estimatedService;
    const activeTables = (tables || []).filter(table => table.is_active);
    const shiftOpen = cashShift?.status === 'open';

    const setProduct = product => {
        setSelectedProductId(String(product.id));
        setSelectedOptions([]);
        setSelectedQuantity('1');
        setSelectedLineNote('');
    };

    const addSelectedProduct = () => {
        if (!selectedProduct) return;
        const quantity = Math.max(1, Math.min(100, Number.parseInt(selectedQuantity || '1', 10) || 1));
        if (remainingStock !== null && quantity > remainingStock) {
            setMessage('تعداد انتخاب‌شده از موجودی فعلی بیشتر است.');
            return;
        }
        const optionSignature = [...selectedOptions].sort((a, b) => a - b).join(',');
        const note = selectedLineNote.trim();
        setCart(current => {
            const existing = current.find(line =>
                Number(line.product_id) === Number(selectedProduct.id)
                && [...line.options].sort((a, b) => a - b).join(',') === optionSignature
                && line.note === note
            );
            if (existing) {
                return current.map(line => line === existing ? { ...line, quantity: line.quantity + quantity } : line);
            }
            return [...current, {
                product_id: Number(selectedProduct.id),
                quantity,
                options: [...selectedOptions].sort((a, b) => a - b),
                note,
            }];
        });
        setMessage('محصول به سفارش اضافه شد.');
        setSelectedOptions([]);
        setSelectedQuantity('1');
        setSelectedLineNote('');
    };

    const updateLineQuantity = (index, requested) => {
        const line = cart[index];
        const product = activeProducts.find(item => Number(item.id) === Number(line?.product_id));
        if (!line || !product) return;
        const otherQuantity = cart.reduce((sum, item, itemIndex) =>
            itemIndex !== index && Number(item.product_id) === Number(line.product_id) ? sum + Number(item.quantity) : sum, 0);
        const stockLimit = product.stock_quantity === null || product.stock_quantity === undefined ? 100 : Math.max(0, Number(product.stock_quantity) - otherQuantity);
        const quantity = Math.max(1, Math.min(stockLimit, Number(requested) || 1));
        setCart(current => current.map((item, itemIndex) => itemIndex === index ? { ...item, quantity } : item));
    };

    const toggleOption = option => {
        const id = Number(option.id);
        setSelectedOptions(current => {
            if (current.includes(id)) return current.filter(value => value !== id);
            if (option.type === 'size') {
                const sizeIds = selectedProductOptions.filter(item => item.type === 'size').map(item => Number(item.id));
                return [...current.filter(value => !sizeIds.includes(value)), id];
            }
            return [...current, id];
        });
    };

    const handleSubmit = async event => {
        event.preventDefault();
        setMessage('');
        if (!cart.length) {
            setMessage('برای ثبت سفارش، ابتدا حداقل یک محصول انتخاب کن.');
            return;
        }
        if (channel === 'dine_in' && !tableId) {
            setMessage('برای سفارش حضوری، میز را انتخاب کن یا نوع سفارش را به صندوق تغییر بده.');
            return;
        }
        if (paymentMethod === 'cash' && !shiftOpen) {
            setMessage('برای دریافت نقدی، ابتدا شیفت صندوق را باز کن.');
            return;
        }
        if (Number(discount || 0) > subtotal) {
            setMessage('تخفیف نمی‌تواند از جمع اقلام بیشتر باشد.');
            return;
        }

        const payload = {
            channel,
            restaurant_table_id: channel === 'dine_in' ? Number(tableId) : null,
            payment_preference: paymentMethod === 'cash' ? 'cash' : paymentMethod === 'card_terminal' ? 'card_terminal' : 'at_counter',
            customer_name: customerName.trim() || null,
            customer_phone: customerPhone.trim() || null,
            customer_note: customerNote.trim() || null,
            discount: Number(discount || 0),
            idempotency_key: orderKey,
            items: cart.map(line => ({
                product_id: line.product_id,
                quantity: line.quantity,
                options: line.options.map(id => ({ id })),
                note: line.note || null,
            })),
        };
        const payment = paymentMethod === 'later' ? null : {
            method: paymentMethod,
            reference: paymentReference.trim() || null,
            note: paymentNote.trim() || null,
            idempotency_key: paymentKey,
        };
        const result = await onSubmit({ payload, payment });
        if (!result?.ok) {
            setMessage(result?.error || 'ثبت سفارش انجام نشد؛ اطلاعات را بررسی و دوباره تلاش کن.');
            return;
        }

        setLastOrder(result.order || null);
        setPaymentPending(Boolean(result.paymentPending));
        setCart([]);
        setDiscount('0');
        setPaymentReference('');
        setPaymentNote('');
        setOrderKey(makeKey());
        setPaymentKey(makeKey());
        setMessage(result.paymentPending
            ? 'سفارش ثبت شد، اما پرداخت کامل نشد. از صفحه سفارش‌ها پرداخت را ادامه بده.'
            : 'سفارش در سامانه ثبت شد.');
    };

    const resetForNextOrder = () => {
        setLastOrder(null);
        setPaymentPending(false);
        setCustomerName('');
        setCustomerPhone('');
        setCustomerNote('');
        setMessage('');
        setSelectedProductId(String(activeProducts[0]?.id || ''));
    };

    return <div className="page-content pos-page">
        <header className="pos-page-heading">
            <div>
                <span className="pos-eyebrow">خانه گیلاسی / نقطه فروش</span>
                <h1>صندوق فروش و ثبت سفارش</h1>
                <p>سفارش را مثل روند واقعی رستوران ثبت کن: انتخاب غذا، شخصی‌سازی، میز، تسویه و چاپ فیش.</p>
            </div>
            <div className="pos-heading-actions">
                <span className={'pos-shift-state ' + (shiftOpen ? 'is-open' : 'is-closed')}>
                    <i />{shiftOpen ? 'شیفت صندوق باز است' : 'شیفت صندوق بسته است'}
                </span>
                <button type="button" className="pos-secondary-button" onClick={onViewOrders}>آخرین سفارش‌ها ↗</button>
            </div>
        </header>

        {lastOrder && <section className={'pos-success-banner ' + (paymentPending ? 'has-payment-warning' : '')}>
            <span className="pos-success-icon">{paymentPending ? '!' : '✓'}</span>
            <div>
                <b>{paymentPending ? 'سفارش ثبت شد؛ پرداخت در انتظار تکمیل است' : 'سفارش با موفقیت ثبت شد'}</b>
                <p>شماره سفارش <strong dir="ltr">{lastOrder.number || ('#' + lastOrder.id)}</strong> · مبلغ {money(lastOrder.total, currency)}</p>
            </div>
            <div className="pos-success-actions">
                <button type="button" onClick={() => onPrint(lastOrder.id, 'receipt')}>چاپ فیش مشتری</button>
                <button type="button" onClick={() => onPrint(lastOrder.id, 'kitchen')}>چاپ فیش آشپزخانه</button>
                <button type="button" onClick={resetForNextOrder}>سفارش بعدی ＋</button>
            </div>
        </section>}

        <div className="pos-layout">
            <section className="pos-catalog panel">
                <div className="pos-catalog-heading">
                    <div><span className="pos-section-kicker">مرحله ۱</span><h2>انتخاب از منو</h2><p>{formatNumber(activeProducts.length)} محصول فعال قابل سفارش</p></div>
                    <label className="pos-search"><span aria-hidden="true">⌕</span><input value={query} onChange={event => setQuery(event.target.value)} placeholder="جست‌وجوی غذا یا نوشیدنی…" aria-label="جست‌وجوی محصولات" /></label>
                </div>

                <div className="pos-category-tabs" role="group" aria-label="دسته‌بندی محصولات">
                    <button type="button" className={categoryId === 'all' ? 'is-active' : ''} onClick={() => setCategoryId('all')}>همه محصولات <small>{formatNumber(activeProducts.length)}</small></button>
                    {(categories || []).filter(category => category.is_active !== false).map(category => {
                        const count = activeProducts.filter(product => Number(product.category_id) === Number(category.id)).length;
                        if (!count) return null;
                        return <button type="button" key={category.id} className={String(categoryId) === String(category.id) ? 'is-active' : ''} onClick={() => setCategoryId(String(category.id))}>
                            {category.name}<small>{formatNumber(count)}</small>
                        </button>;
                    })}
                </div>

                {selectedProduct && <section className="pos-product-builder">
                    <div className="pos-builder-image">
                        {selectedProduct.image_url ? <img src={selectedProduct.image_url} alt={selectedProduct.name} /> : <span>🍒</span>}
                    </div>
                    <div className="pos-builder-main">
                        <div className="pos-builder-heading"><div><small>محصول انتخاب‌شده</small><h3>{selectedProduct.name}</h3></div><strong>{money(selectedProduct.base_price, currency)}</strong></div>
                        {selectedProduct.description && <p className="pos-builder-description">{selectedProduct.description}</p>}
                        {selectedProductOptions.length > 0 && <div className="pos-option-grid">
                            {selectedProductOptions.map(option => <label className={selectedOptions.includes(Number(option.id)) ? 'is-selected' : ''} key={option.id}>
                                <input type="checkbox" checked={selectedOptions.includes(Number(option.id))} onChange={() => toggleOption(option)} />
                                <span>{option.type === 'size' ? 'سایز · ' : 'افزودنی · '}{option.name}</span>
                                <b>{Number(option.price) > 0 ? '+' + money(option.price, currency) : 'رایگان'}</b>
                            </label>)}
                        </div>}
                        <div className="pos-builder-footer">
                            <label className="pos-quantity-field"><span>تعداد</span><input type="number" min="1" max={remainingStock === null ? 100 : Math.max(1, remainingStock)} value={selectedQuantity} onChange={event => setSelectedQuantity(event.target.value)} /></label>
                            <label className="pos-line-note"><span>یادداشت آشپزخانه (اختیاری)</span><input value={selectedLineNote} maxLength="500" onChange={event => setSelectedLineNote(event.target.value)} placeholder="مثلاً بدون پیاز، سس جدا…" /></label>
                            <button type="button" className="pos-add-button" onClick={addSelectedProduct} disabled={!selectedProduct || remainingStock === 0 || (remainingStock !== null && Number(selectedQuantity) > remainingStock)}>افزودن به سفارش ＋</button>
                        </div>
                    </div>
                </section>}

                <div className="pos-product-grid">
                    {filteredProducts.length ? filteredProducts.map(product => {
                        const selected = String(product.id) === String(selectedProductId);
                        return <button type="button" key={product.id} className={'pos-product-card ' + (selected ? 'is-selected' : '')} onClick={() => setProduct(product)} aria-pressed={selected}>
                            <span className="pos-product-photo">
                                {product.image_url ? <img src={product.image_url} alt="" loading="lazy" /> : <span>🍽️</span>}
                                <i>{product.category?.name || 'منو'}</i>
                            </span>
                            <span className="pos-product-copy"><b>{product.name}</b><strong>{money(product.base_price, currency)}</strong><StockLabel product={product} /></span>
                        </button>;
                    }) : <div className="pos-empty-catalog"><span>⌕</span><b>محصولی پیدا نشد</b><p>جست‌وجو یا دسته‌بندی را تغییر بده.</p></div>}
                </div>
                {!activeProducts.length && <div className="pos-empty-catalog"><span>🍒</span><b>محصول فعالی برای فروش وجود ندارد</b><p>ابتدا از بخش منو و محصولات، کالاهای قابل فروش را فعال کن.</p></div>}
            </section>

            <aside className="pos-checkout panel">
                <div className="pos-checkout-heading"><div><span className="pos-section-kicker">مرحله ۲ و ۳</span><h2>سبد سفارش</h2><p>{formatNumber(cart.length)} ردیف · میز و پرداخت</p></div><span className="pos-cart-count">{formatNumber(cart.reduce((sum, line) => sum + line.quantity, 0))}</span></div>

                <div className="pos-channel-select" role="group" aria-label="نوع سفارش">
                    {[['dine_in','سالن / میز','⌂'],['counter','حضوری صندوق','▣'],['takeaway','بیرون‌بر','↗']].map(([value,label,icon]) =>
                        <button type="button" key={value} className={channel === value ? 'is-active' : ''} onClick={() => { setChannel(value); if (value !== 'dine_in') setTableId(''); }}>
                            <span>{icon}</span>{label}
                        </button>
                    )}
                </div>

                {channel === 'dine_in' && <label className="pos-field"><span>انتخاب میز</span>
                    <select value={tableId} onChange={event => setTableId(event.target.value)}>
                        <option value="">انتخاب میز…</option>
                        {activeTables.map(table => <option key={table.id} value={table.id}>{table.name}</option>)}
                    </select>
                </label>}

                <div className="pos-cart-lines">
                    {cart.length ? cart.map((line, index) => {
                        const product = activeProducts.find(item => Number(item.id) === Number(line.product_id));
                        const options = (line.options || []).map(id => product?.options?.find(option => Number(option.id) === Number(id))).filter(Boolean);
                        const lineTotal = (Number(product?.base_price || 0) + options.reduce((sum, option) => sum + Number(option.price || 0), 0)) * Number(line.quantity);
                        return <article className="pos-cart-line" key={line.product_id + '-' + line.options.join('-') + '-' + index}>
                            <div className="pos-cart-line-main">
                                <b>{product?.name || 'محصول حذف‌شده'}</b>
                                <small>{options.map(option => option.name).join('، ') || 'بدون افزودنی'}</small>
                                {line.note && <small className="pos-cart-note">یادداشت: {line.note}</small>}
                                <strong>{money(lineTotal, currency)}</strong>
                            </div>
                            <div className="pos-cart-line-actions">
                                <button type="button" aria-label="کم کردن تعداد" onClick={() => updateLineQuantity(index, Math.max(1, line.quantity - 1))}>−</button>
                                <span>{formatNumber(line.quantity)}</span>
                                <button type="button" aria-label="افزایش تعداد" onClick={() => updateLineQuantity(index, line.quantity + 1)}>＋</button>
                                <button type="button" className="pos-line-remove" aria-label="حذف از سفارش" onClick={() => setCart(current => current.filter((_, i) => i !== index))}>×</button>
                            </div>
                        </article>;
                    }) : <div className="pos-empty-cart"><span>🧾</span><b>سفارش هنوز خالی است</b><small>از منو یک غذا یا نوشیدنی انتخاب کن.</small></div>}
                </div>

                <div className="pos-customer-fields">
                    <label className="pos-field"><span>نام مشتری (اختیاری)</span><input value={customerName} onChange={event => setCustomerName(event.target.value)} maxLength="150" placeholder="نام مشتری" /></label>
                    <label className="pos-field"><span>شماره تماس (اختیاری)</span><input value={customerPhone} onChange={event => setCustomerPhone(event.target.value)} inputMode="tel" maxLength="30" placeholder="شماره تماس" /></label>
                    <label className="pos-field"><span>توضیح کلی سفارش</span><input value={customerNote} onChange={event => setCustomerNote(event.target.value)} maxLength="2000" placeholder="توضیح برای صندوق یا تحویل" /></label>
                </div>

                <div className="pos-discount-row"><label htmlFor="pos-discount">تخفیف ({currency})</label><input id="pos-discount" type="number" min="0" max={subtotal} step="1" value={discount} onChange={event => setDiscount(event.target.value)} /></div>
                <div className="pos-totals">
                    <div><span>جمع اقلام</span><b>{money(subtotal, currency)}</b></div>
                    <div><span>تخفیف</span><b>− {money(safeDiscount, currency)}</b></div>
                    {estimatedTax > 0 && <div><span>مالیات تخمینی</span><b>{money(estimatedTax, currency)}</b></div>}
                    {estimatedService > 0 && <div><span>حق سرویس تخمینی</span><b>{money(estimatedService, currency)}</b></div>}
                    <div className="pos-total-final"><span>مبلغ نهایی تخمینی</span><strong>{money(estimatedTotal, currency)}</strong></div>
                    <small>مبلغ قطعی، مالیات و سرویس در سمت سرور محاسبه می‌شود.</small>
                </div>

                <div className="pos-payment-section">
                    <b>روش تسویه</b>
                    <div className="pos-payment-options">
                        <button type="button" className={paymentMethod === 'card_terminal' ? 'is-active' : ''} onClick={() => setPaymentMethod('card_terminal')}>کارت‌خوان</button>
                        <button type="button" className={paymentMethod === 'cash' ? 'is-active' : ''} disabled={!shiftOpen} onClick={() => setPaymentMethod('cash')}>نقدی</button>
                        <button type="button" className={paymentMethod === 'later' ? 'is-active' : ''} onClick={() => setPaymentMethod('later')}>بعداً تسویه</button>
                    </div>
                    {paymentMethod === 'cash' && !shiftOpen && <div className="pos-cash-warning">برای دریافت نقدی، شیفت صندوق باید باز باشد. <button type="button" onClick={onOpenCashier}>بازکردن شیفت ←</button></div>}
                    {paymentMethod === 'card_terminal' && <label className="pos-field"><span>شماره پیگیری کارت‌خوان (اختیاری)</span><input value={paymentReference} onChange={event => setPaymentReference(event.target.value)} maxLength="120" placeholder="شماره رسید یا پیگیری" /></label>}
                    {paymentMethod === 'later' && <p className="pos-pay-later-note">سفارش ثبت می‌شود و مانده آن برای تسویه بعدی در صفحه صندوق باقی می‌ماند.</p>}
                    <p className="pos-card-note">ثبت «کارت‌خوان» فقط تأیید دریافت توسط کارمند است؛ اتصال خودکار به دستگاه کارت‌خوان در این مرحله انجام نمی‌شود.</p>
                </div>

                {message && <p className="pos-feedback" role="status">{message}</p>}
                <button type="button" className="pos-checkout-submit" onClick={handleSubmit} disabled={submitting || !cart.length || (channel === 'dine_in' && !tableId) || Number(discount || 0) > subtotal || (paymentMethod === 'cash' && !shiftOpen)}>
                    {submitting ? 'در حال ثبت سفارش…' : paymentMethod === 'later' ? 'ثبت سفارش و تسویه بعدی' : 'ثبت سفارش و ثبت دریافت وجه'}
                    <span>↗</span>
                </button>
            </aside>
        </div>
    </div>;
}
