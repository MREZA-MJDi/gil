import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';

const money = n => new Intl.NumberFormat('fa-IR').format(n) + ' تومان';

function CustomerMenu() {
    const root = document.getElementById('customer-menu-app');
    const token = root?.dataset.token;
    const [data, setData] = useState(null);
    const [cart, setCart] = useState([]);
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [idempotencyKey, setIdempotencyKey] = useState('');
    const [submissionUncertain, setSubmissionUncertain] = useState(false);

    useEffect(() => {
        let active = true;
        fetch('/menu/' + encodeURIComponent(token) + '/data', { headers: { Accept: 'application/json' } })
            .then(async response => {
                if (!response.ok) throw new Error('بارگذاری منو انجام نشد.');
                return response.json();
            })
            .then(payload => { if (active) setData(payload.data); })
            .catch(error => { if (active) setNotice(error.message); });
        return () => { active = false; };
    }, [token]);

    const add = product => {
        if (busy || submissionUncertain) return;
        setIdempotencyKey('');
        setCart(items => {
        const existing = items.find(item => item.product_id === product.id && item.options.length === 0);
        if (existing) return items.map(item => item === existing ? { ...item, quantity: item.quantity + 1 } : item);
        return [...items, { product_id: product.id, quantity: 1, options: [] }];
        });
    };
    const changeQty = (id, delta) => {
        if (busy || submissionUncertain) return;
        setIdempotencyKey('');
        setCart(items => items.map(item => item.product_id === id ? { ...item, quantity: item.quantity + delta } : item).filter(item => item.quantity > 0));
    };

    async function submitOrder() {
        if (!cart.length || busy) return;
        setBusy(true);
        setNotice('');
        let response = null;
        const requestKey = idempotencyKey || crypto.randomUUID();
        setIdempotencyKey(requestKey);
        try {
            response = await fetch('/menu/' + encodeURIComponent(token) + '/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Idempotency-Key': requestKey,
                },
                body: JSON.stringify({
                    channel: 'dine_in',
                    items: cart,
                    customer_name: customerName || null,
                    customer_phone: customerPhone || null,
                    discount: 0,
                }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'ثبت سفارش انجام نشد.');
            setCart([]);
            setIdempotencyKey('');
            setSubmissionUncertain(false);
            setNotice('سفارش ' + payload.data.number + ' ثبت شد. مبلغ: ' + money(payload.data.total));
        } catch (error) {
            if (response && response.status < 500) {
                setIdempotencyKey('');
                setSubmissionUncertain(false);
            } else {
                // Keep the same request key and freeze edits so a retry cannot create a duplicate order.
                setSubmissionUncertain(true);
            }
            setNotice(error.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از سفارش تکراری، با همان محتوا دوباره تلاش کن.');
        } finally {
            setBusy(false);
        }
    }

    if (!data && !notice) return <div className="menu-loading">در حال آماده‌سازی منو…</div>;

    return <div className="customer-menu-content">
        {notice && <div className="menu-notice" role="status">{notice}</div>}
        {data?.categories?.map(category => <section className="customer-category" key={category.id}>
            <div className="customer-category-heading"><h2>{category.name}</h2><span>{category.products.length.toLocaleString('fa-IR')} محصول</span></div>
            <div className="customer-product-grid">{category.products.map(product => <article className="customer-product" key={product.id}>
                {product.image_url && <img src={product.image_url} alt="" loading="lazy" />}
                <div className="customer-product-info">
                    <h3>{product.name}</h3>
                    {product.description && <p>{product.description}</p>}
                    <strong>{money(product.base_price)}</strong>
                    <button disabled={busy || submissionUncertain} onClick={() => add(product)}>افزودن به سفارش ＋</button>
                </div>
            </article>)}</div>
        </section>)}
        <section className="customer-checkout">
            <h2>سفارش شما <span>{cart.reduce((sum, item) => sum + item.quantity, 0).toLocaleString('fa-IR')} قلم</span></h2>
            {cart.map(item => {
                const product = data?.categories.flatMap(c => c.products).find(p => p.id === item.product_id);
                return <div className="cart-row" key={item.product_id}>
                    <span>{product?.name || 'محصول'} × {item.quantity.toLocaleString('fa-IR')}</span>
                    <div>
                        <button disabled={busy || submissionUncertain} onClick={() => changeQty(item.product_id, -1)} aria-label="کم کردن">−</button>
                        <button disabled={busy || submissionUncertain} onClick={() => changeQty(item.product_id, 1)} aria-label="زیاد کردن">＋</button>
                    </div>
                </div>;
            })}
            <label>نام (اختیاری)<input disabled={busy || submissionUncertain} value={customerName} onChange={e => setCustomerName(e.target.value)} maxLength={150} /></label>
            <label>شماره تماس (اختیاری)<input disabled={busy || submissionUncertain} value={customerPhone} onChange={e => setCustomerPhone(e.target.value)} maxLength={30} inputMode="tel" /></label>
            <button className="customer-submit" disabled={!cart.length || busy} onClick={submitOrder}>{busy ? 'در حال ثبت…' : submissionUncertain ? 'ارسال مجدد با همان شناسه' : 'ثبت سفارش'}</button>
            {submissionUncertain && <p role="alert">پاسخ قطعی از سرور نرسید. محتوای سفارش قفل شده است؛ ارسال مجدد از همان شناسه استفاده می‌کند تا سفارش تکراری ساخته نشود.</p>}
            <p>مبلغ نهایی در سمت سرور محاسبه می‌شود. پرداخت از این صفحه انجام نمی‌شود.</p>
        </section>
    </div>;
}

const mount = document.getElementById('customer-menu-app');
if (mount) createRoot(mount).render(<CustomerMenu />);
