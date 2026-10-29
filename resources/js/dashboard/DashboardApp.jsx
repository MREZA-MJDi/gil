import React, { useEffect, useMemo, useState } from 'react';
import { api, idempotencyKey, unwrapData, unwrapList } from './api.js';

const navItems = [
    ['overview', 'نمای کلی', '◫'],
    ['orders', 'سفارش‌ها و تحویل', '▤'],
    ['cashier', 'صندوق و پرداخت', '◈'],
    ['menu', 'منو و محصولات', '▦'],
    ['inventory', 'موجودی و انبار', '▧'],
    ['tables', 'میزها و QR', '▤'],
    ['reservations', 'رزروها', '◷'],
    ['customers', 'بازخورد مشتریان', '♧'],
    ['expenses', 'هزینه‌ها', '↗'],
    ['reports', 'گزارش‌ها', '▥'],
    ['settings', 'تنظیمات', '⚙'],
];

const orderStatusLabel = {
    new: 'جدید', confirmed: 'تأیید شده', preparing: 'در حال آماده‌سازی',
    ready: 'آماده تحویل', delivered: 'تحویل شده', cancelled: 'لغو شده',
};
const paymentStatusLabel = {
    unpaid: 'تسویه نشده', partial: 'پرداخت ناقص', paid: 'تسویه کامل',
    partially_refunded: 'بازپرداخت جزئی', refunded: 'بازپرداخت کامل',
};
const productStatusLabel = { active: 'فعال', inactive: 'غیرفعال', unavailable: 'ناموجود' };
const reservationStatusLabel = { pending: 'در انتظار', confirmed: 'تأیید شده', arrived: 'مراجعه کرده', cancelled: 'لغو شده', no_show: 'عدم مراجعه' };
const feedbackStatusLabel = { new: 'جدید', reviewing: 'در حال بررسی', resolved: 'رسیدگی‌شده', closed: 'بسته‌شده' };
const paymentMethodLabel = { cash: 'نقدی', card_terminal: 'کارت‌خوان (ثبت کاربر)', online: 'آنلاین', other: 'سایر' };
const expenseCategoryLabel = { rent: 'اجاره', payroll: 'حقوق', utilities: 'قبوض', ingredients: 'مواد اولیه', repairs: 'تعمیرات', packaging: 'بسته‌بندی', transport: 'حمل‌ونقل', other: 'سایر' };
const money = value => new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 }).format(Number(value || 0)) + ' تومان';
const numberFa = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const dateTime = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
const localDateTime = value => value ? String(value).replace('T', ' ') + ':00' : '';
const channelLabel = order => {
    if (order?.table?.name) return order.table.name;
    return ({ dine_in: 'حضوری', takeaway: 'بیرون‌بر', counter: 'صندوق', online: 'آنلاین' })[order?.channel] || order?.channel || '—';
};
const statusText = (value, dictionary) => dictionary[value] || value || '—';
const getRemaining = order => {
    const payments = (order?.payments || []).filter(p => p.status === 'succeeded').reduce((sum, p) => sum + Number(p.amount || 0), 0);
    const refunds = (order?.refunds || []).reduce((sum, r) => sum + Number(r.amount || 0), 0);
    return Math.max(0, Number(order?.total || 0) - payments + refunds);
};

function Icon({ children }) { return <span className="ui-icon" aria-hidden="true">{children}</span>; }
function Pill({ value, dictionary = {}, tone }) {
    const label = statusText(value, dictionary);
    const resolvedTone = tone || (
        ['delivered', 'paid', 'active', 'confirmed', 'resolved', 'arrived'].includes(value) ? 'success' :
        ['cancelled', 'refunded', 'inactive', 'unavailable', 'no_show'].includes(value) ? 'danger' :
        ['ready', 'partial', 'partially_refunded', 'preparing', 'reviewing'].includes(value) ? 'warning' : 'neutral'
    );
    return <span className={'status-pill ' + resolvedTone}><i />{label}</span>;
}
function Button({ children, kind = 'secondary', ...props }) {
    return <button className={kind === 'primary' ? 'primary-button' : kind === 'danger' ? 'op-danger-button' : 'secondary-button'} {...props}>{children}</button>;
}
function PageHeader({ title, subtitle, action, onAction }) {
    return <header className="page-header">
        <div><div className="eyebrow">KHANE GILASI / OPERATIONS</div><h1>{title}</h1><p>{subtitle}</p></div>
        {action && <div className="header-actions"><Button kind="primary" onClick={onAction}>{action}</Button></div>}
    </header>;
}
function Field({ label, children, hint }) {
    return <label className="op-field"><span>{label}</span>{children}{hint && <small>{hint}</small>}</label>;
}
function Empty({ children = 'هنوز داده‌ای برای نمایش وجود ندارد.' }) {
    return <div className="op-empty"><span>◇</span><b>{children}</b></div>;
}
function Loading() { return <div className="op-loading"><span className="op-spinner" />در حال دریافت اطلاعات از سرور…</div>; }
function TableWrap({ children }) { return <div className="table-scroll"><table className="data-table">{children}</table></div>; }
function Modal({ title, eyebrow, onClose, children, wide = false }) {
    return <div className="modal-backdrop" onMouseDown={e => { if (e.target === e.currentTarget) onClose(); }}>
        <section className={'modal-card op-modal ' + (wide ? 'op-modal-wide' : '')} role="dialog" aria-modal="true" aria-label={title}>
            <button className="modal-close" type="button" aria-label="بستن" onClick={onClose}>×</button>
            {eyebrow && <span className="eyebrow">{eyebrow}</span>}
            <h2>{title}</h2>
            {children}
        </section>
    </div>;
}
function Errors({ message }) { return message ? <div className="op-alert" role="alert"><b>عملیات انجام نشد</b><span>{message}</span></div> : null; }

function OverviewPage({ dashboard, orders = [], onNavigate, onNewOrder, loading }) {
    const recent = dashboard?.recent_orders || [];
    const cards = [
        ['فروش ثبت‌شده امروز', money(dashboard?.registered_sales), 'سفارش‌های لغونشده؛ قبل از تخفیف/مرجوعی فروش خالص نیست', '↗', 'coral'],
        ['دریافتی خالص امروز', money(dashboard?.collections_net), 'دریافتی منهای مبالغ بازپرداخت‌شده امروز', '◈', 'green'],
        ['تعداد سفارش‌های امروز', numberFa(dashboard?.orders_count), 'شامل سفارش‌های لغوشده', '▤', 'purple'],
        ['مانده صورت‌حساب‌ها', money(dashboard?.open_invoice_amount), 'سفارش‌های تسویه‌نشده', '◷', 'amber'],
    ];
    return <div className="page-content">
        <PageHeader title="نمای کلی عملیات" subtitle="وضعیت واقعی صندوق، سفارش‌ها و موجودی؛ از API و دیتابیس مجموعه." action="＋ سفارش جدید" onAction={onNewOrder} />
        {loading && <Loading />}
        <section className="stats-grid">
            {cards.map(([label, value, sub, icon, tone]) => <article className="stat-card" key={label}>
                <div className="stat-top"><span className={'stat-icon ' + tone}><Icon>{icon}</Icon></span><span className="stat-trend">زنده</span></div>
                <div className="stat-label">{label}</div><div className="stat-value">{value ?? '—'}</div><div className="stat-sub">{sub}</div>
            </article>)}
        </section>
        <div className="op-summary-strip">
            <div><span className="live-dot" /><b>{dashboard?.active_cash_shift ? 'شیفت صندوق باز است' : 'شیفت صندوق بسته است'}</b><small>{dashboard?.active_cash_shift ? ('شروع: ' + dateTime(dashboard.active_cash_shift.opened_at)) : 'برای ثبت نقدی، ابتدا شیفت را باز کن.'}</small></div>
            <div><b>{numberFa(dashboard?.low_stock_count)} محصول کم‌موجودی</b><small>بر اساس آستانه تعریف‌شده هر محصول</small><Button onClick={() => onNavigate('inventory')}>بررسی انبار ←</Button></div>
            <div><b>{numberFa(dashboard?.today_reservations_count)} رزرو امروز</b><small>رزروهای در انتظار یا تأییدشده</small><Button onClick={() => onNavigate('reservations')}>مدیریت رزرو ←</Button></div>
        </div>
        <section className="panel orders-panel">
            <div className="panel-heading"><div><h2>آخرین سفارش‌ها</h2><p>وضعیت آماده‌سازی و پرداخت را از همین‌جا پیگیری کن.</p></div><Button onClick={() => onNavigate('orders')}>همه سفارش‌ها ←</Button></div>
            {recent.length ? <TableWrap><thead><tr><th>شماره</th><th>کانال</th><th>مبلغ</th><th>آماده‌سازی</th><th>پرداخت</th><th>ثبت‌شده</th><th>جزئیات</th></tr></thead><tbody>
                {recent.slice(0, 8).map(order => <tr key={order.id}><td className="mono">{order.number}</td><td>{channelLabel(order)}</td><td>{money(order.total)}</td><td><Pill value={order.status} dictionary={orderStatusLabel} /></td><td><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></td><td>{dateTime(order.created_at)}</td><td><Button onClick={() => onNavigate('orders')}>بازکردن</Button></td></tr>)}
            </tbody></TableWrap> : <Empty>هنوز سفارشی ثبت نشده است.</Empty>}
        </section>
        <section className="bottom-grid">
            <article className="panel quick-panel"><div className="panel-heading"><div><h2>دسترسی سریع</h2><p>جریان‌های پرتکرار روزانه</p></div></div><div className="quick-grid">
                <button onClick={onNewOrder}><span className="quick-icon qi-red">＋</span><span><b>ثبت سفارش</b><small>محصول، گزینه، میز و تخفیف</small></span><em>←</em></button>
                <button onClick={() => onNavigate('cashier')}><span className="quick-icon qi-green">◈</span><span><b>صندوق و فیش</b><small>دریافت، بازپرداخت و چاپ</small></span><em>←</em></button>
                <button onClick={() => onNavigate('menu')}><span className="quick-icon qi-amber">▦</span><span><b>منو و قیمت</b><small>محصول، دسته و وضعیت فروش</small></span><em>←</em></button>
                <button onClick={() => onNavigate('inventory')}><span className="quick-icon qi-purple">▧</span><span><b>موجودی</b><small>ورود، خروج و گردش انبار</small></span><em>←</em></button>
            </div></article>
            <article className="panel attention-panel"><div className="panel-heading"><div><h2>مهم برای مدیر</h2><p>عددها از سامانه خوانده می‌شوند، نه داده نمونه.</p></div></div>
                <div className="attention-item"><span className="attention-icon amber-bg">◷</span><span><b>دریافتی و فروش متفاوت‌اند</b><small>گزارش مالی این دو را جدا نگه می‌دارد.</small></span><Button onClick={() => onNavigate('reports')}>گزارش</Button></div>
                <div className="attention-item"><span className="attention-icon red-bg">!</span><span><b>سفارش آماده تحویل</b><small>{numberFa(dashboard?.orders_by_status?.ready || 0)} مورد با این وضعیت</small></span><Button onClick={() => onNavigate('orders')}>سفارش‌ها</Button></div>
                <div className="attention-item"><span className="attention-icon green-bg">✓</span><span><b>تاریخچه چاپ و عملیات</b><small>اقدام کاربر در دفتر رویداد ثبت می‌شود.</small></span><Button onClick={() => onNavigate('settings')}>تنظیمات</Button></div>
            </article>
        </section>
    </div>;
}

function OrdersPage({ orders, tables, onRefresh, onDetails, onPrint, onNewOrder, onTransition, onCancel }) {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');
    const filtered = (orders || []).filter(order => (status === 'all' || (status === 'open' && !['delivered', 'cancelled'].includes(order.status)) || (status === 'unsettled' && !['paid', 'refunded'].includes(order.payment_status)) || order.status === status)
        && (String(order.number || '').toLowerCase().includes(query.toLowerCase()) || String(order.customer_phone || '').includes(query) || String(order.table?.name || '').toLowerCase().includes(query.toLowerCase())));
    return <div className="page-content">
        <PageHeader title="سفارش‌ها و تحویل" subtitle="از ثبت تا آماده‌سازی، تحویل، تسویه و لغو؛ تمام تغییرها روی سرور ثبت می‌شوند." action="＋ سفارش جدید" onAction={onNewOrder} />
        <div className="toolbar"><div className="filter-tabs">{[['all','همه'],['open','در جریان'],['unsettled','تسویه‌نشده'],['ready','آماده تحویل']].map(([key,label]) => <button key={key} className={status === key ? 'active' : ''} onClick={() => setStatus(key)}>{label}</button>)}</div><input value={query} onChange={e => setQuery(e.target.value)} placeholder="جست‌وجوی شماره، تلفن یا میز…" aria-label="جست‌وجوی سفارش" /><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
        <section className="panel">
            {filtered.length ? <TableWrap><thead><tr><th>شماره سفارش</th><th>کانال / میز</th><th>مبلغ</th><th>آماده‌سازی</th><th>تسویه</th><th>ثبت‌شده</th><th>عملیات</th></tr></thead><tbody>
                {filtered.map(order => <tr key={order.id}>
                    <td><button className="op-text-button mono" onClick={() => onDetails(order.id)}>{order.number || ('#' + order.id)}</button></td>
                    <td>{channelLabel(order)}<small className="op-subtext">{order.customer_name || order.customer_phone || 'بدون مشخصات مشتری'}</small></td>
                    <td className="amount-cell">{money(order.total)}</td><td><Pill value={order.status} dictionary={orderStatusLabel} /></td><td><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></td><td>{dateTime(order.created_at)}</td>
                    <td><div className="op-actions">
                        <Button onClick={() => onDetails(order.id)}>جزئیات</Button>
                        {nextStatus(order.status) && <Button kind="primary" onClick={() => onTransition(order, nextStatus(order.status))}>{nextStatusLabel(order.status)}</Button>}
                        {!['delivered','cancelled'].includes(order.status) && order.payment_status !== 'paid' && <Button kind="danger" onClick={() => onCancel(order)}>لغو</Button>}
                        <Button onClick={() => onPrint(order.id, 'kitchen')}>فیش آشپزخانه</Button>
                    </div></td>
                </tr>)}
            </tbody></TableWrap> : <Empty>سفارشی با این فیلتر پیدا نشد.</Empty>}
        </section>
    </div>;
}
function nextStatus(status) {
    return ({ new: 'confirmed', confirmed: 'preparing', preparing: 'ready', ready: 'delivered' })[status] || null;
}
function nextStatusLabel(status) {
    return ({ new: 'تأیید', confirmed: 'شروع آماده‌سازی', preparing: 'آماده تحویل', ready: 'ثبت تحویل' })[status] || 'تغییر وضعیت';
}

function CashierPage({ cashShift, orders, onRefresh, onOpenShift, onCloseShift, onMovement, onPayment, onDetails }) {
    const [openingCash, setOpeningCash] = useState('0');
    const [countedCash, setCountedCash] = useState('');
    const [shiftNote, setShiftNote] = useState('');
    const [movement, setMovement] = useState({ direction: 'out', amount: '', reason: '' });
    const [movementKey, setMovementKey] = useState(() => idempotencyKey());
    const payable = (orders || []).filter(order => order.payment_status !== 'paid' && order.payment_status !== 'refunded' && order.status !== 'cancelled');
    return <div className="page-content">
        <PageHeader title="صندوق و پرداخت" subtitle="شروع و پایان شیفت، ثبت دریافت و بازپرداخت؛ پرداخت کارت‌خوان به معنی ثبت تأییدشده توسط کارمند است." />
        <section className="op-grid op-grid-2">
            <article className="panel op-card">
                <div className="panel-heading"><div><h2>وضعیت شیفت صندوق</h2><p>هم‌زمان فقط یک شیفت باز قابل ثبت است.</p></div><Pill value={cashShift?.status || 'closed'} dictionary={{open:'باز',closed:'بسته'}} /></div>
                {cashShift ? <div className="op-kpi-grid"><div><small>وجه آغازین</small><b>{money(cashShift.opening_cash)}</b></div><div><small>زمان شروع</small><b>{dateTime(cashShift.opened_at)}</b></div><div><small>ورودی/خروجی ثبت‌شده</small><b>{numberFa((cashShift.movements || []).length)} حرکت</b></div></div> :
                    <form className="op-form" onSubmit={e => { e.preventDefault(); onOpenShift({ opening_cash: Number(openingCash), note: shiftNote }); }}>
                        <Field label="وجه نقد اولیه (تومان)"><input type="number" min="0" step="1" value={openingCash} onChange={e => setOpeningCash(e.target.value)} required /></Field>
                        <Field label="یادداشت شروع"><input value={shiftNote} onChange={e => setShiftNote(e.target.value)} maxLength="500" placeholder="مثلاً صندوق شیفت صبح" /></Field><Button kind="primary" type="submit">باز کردن شیفت</Button>
                    </form>}
                {cashShift && <form className="op-form op-form-separated" onSubmit={e => { e.preventDefault(); onCloseShift(cashShift.id, { counted_cash: Number(countedCash), note: shiftNote }); }}>
                    <Field label="وجه نقد شمارش‌شده (تومان)"><input type="number" min="0" value={countedCash} onChange={e => setCountedCash(e.target.value)} required /></Field>
                    <Field label="یادداشت بستن"><input value={shiftNote} onChange={e => setShiftNote(e.target.value)} maxLength="500" placeholder="توضیح اختلاف صندوق، در صورت وجود" /></Field>
                    <Button kind="danger" type="submit">بستن و ثبت مغایرت</Button>
                </form>}
            </article>
            <article className="panel op-card">
                <div className="panel-heading"><div><h2>ورودی / خروجی دستی صندوق</h2><p>برای تنخواه، شارژ صندوق یا خروج وجه، علت را شفاف ثبت کن.</p></div></div>
                {cashShift ? <form className="op-form" onSubmit={e => { e.preventDefault(); onMovement(cashShift.id, { ...movement, idempotency_key: movementKey }).then(ok => { if (ok) { setMovement({ direction: 'out', amount: '', reason: '' }); setMovementKey(idempotencyKey()); } }); }}>
                    <Field label="نوع حرکت"><select value={movement.direction} onChange={e => setMovement(v => ({...v, direction:e.target.value}))}><option value="in">ورودی وجه</option><option value="out">خروجی وجه</option></select></Field>
                    <Field label="مبلغ (تومان)"><input type="number" min="1" value={movement.amount} onChange={e => setMovement(v => ({...v, amount:e.target.value}))} required /></Field>
                    <Field label="علت / شرح"><input minLength="3" maxLength="500" value={movement.reason} onChange={e => setMovement(v => ({...v, reason:e.target.value}))} required placeholder="شرح قابل حسابرسی" /></Field>
                    <Button kind="primary" type="submit">ثبت حرکت صندوق</Button>
                </form> : <Empty>برای ثبت حرکت دستی، ابتدا شیفت را باز کن.</Empty>}
            </article>
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>مانده‌های قابل تسویه</h2><p>پرداخت نقدی فقط با شیفت باز امکان‌پذیر است.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {payable.length ? <TableWrap><thead><tr><th>شماره</th><th>کانال</th><th>مبلغ کل</th><th>وضعیت پرداخت</th><th>اقدام</th></tr></thead><tbody>
                {payable.map(order => <tr key={order.id}><td className="mono">{order.number}</td><td>{channelLabel(order)}</td><td>{money(order.total)}</td><td><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></td><td><div className="op-actions"><Button kind="primary" onClick={() => onPayment(order)}>ثبت پرداخت</Button><Button onClick={() => onDetails(order.id)}>سوابق / بازپرداخت</Button></div></td></tr>)}
            </tbody></TableWrap> : <Empty>هیچ مانده‌ی قابل تسویه‌ای وجود ندارد.</Empty>}
        </section>
    </div>;
}

function MenuPage({ products, categories, onRefresh, onProduct, onProductStatus, onCategory }) {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('all');
    const filtered = (products || []).filter(p => (category === 'all' || String(p.category_id) === category) && (String(p.name || '').toLowerCase().includes(query.toLowerCase()) || String(p.category?.name || '').toLowerCase().includes(query.toLowerCase())));
    return <div className="page-content">
        <PageHeader title="منو و محصولات" subtitle="قیمت، دسته، گزینه‌های محصول و وضعیت قابل سفارش بودن؛ تغییرها در دیتابیس ثبت می‌شوند." action="＋ محصول جدید" onAction={() => onProduct(null)} />
        <div className="op-toolbar"><input value={query} onChange={e => setQuery(e.target.value)} placeholder="جست‌وجوی نام محصول…" /><select value={category} onChange={e => setCategory(e.target.value)}><option value="all">همه دسته‌ها</option>{(categories || []).map(c => <option key={c.id} value={String(c.id)}>{c.name}</option>)}</select><Button onClick={() => onCategory(null)}>＋ دسته‌بندی</Button><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
        {filtered.length ? <div className="op-product-grid">{filtered.map(product => <article className="op-product-card" key={product.id}>
            {product.image_url ? <img src={product.image_url} alt="" /> : <div className="op-product-placeholder">☕</div>}
            <div className="op-product-body"><div className="op-product-top"><span>{product.category?.name || 'بدون دسته'}</span><Pill value={product.status} dictionary={productStatusLabel} /></div>
                <h3>{product.name}</h3><b className="op-product-price">{money(product.base_price)}</b>
                <div className="op-product-meta"><span>شناسه: {numberFa(product.id)}</span><span>{product.stock_quantity === null ? 'بدون رهگیری موجودی' : ('موجودی: ' + numberFa(product.stock_quantity))}</span></div>
                <div className="op-actions"><Button onClick={() => onProduct(product)}>ویرایش</Button><Button onClick={() => onProductStatus(product, product.status === 'active' ? 'unavailable' : 'active')}>{product.status === 'active' ? 'ناموجود کردن' : 'فعال‌سازی'}</Button></div>
            </div>
        </article>)}</div> : <Empty>محصولی پیدا نشد. یک محصول جدید بساز یا فیلتر را عوض کن.</Empty>}
    </div>;
}

function InventoryPage({ products, movements, onRefresh, onAdjust }) {
    const low = (products || []).filter(p => p.stock_quantity !== null && p.low_stock_threshold !== null && Number(p.stock_quantity) <= Number(p.low_stock_threshold));
    return <div className="page-content">
        <PageHeader title="موجودی و انبار" subtitle="موجودی قابل رهگیری با هر فروش کم و در لغو سفارش برگردانده می‌شود. هر اصلاح تاریخچه دارد." action="＋ اصلاح موجودی" onAction={() => onAdjust(null)} />
        <div className="op-summary-strip"><div><b>{numberFa(products?.filter(p => p.stock_quantity !== null).length || 0)} محصول تحت رهگیری</b><small>موجودی خالی یعنی رهگیری برای آن محصول فعال نشده است.</small></div><div><b>{numberFa(low.length)} محصول زیر آستانه</b><small>برای سفارش مواد اولیه بررسی شود.</small></div><div><b>{numberFa(movements?.length || 0)} حرکت اخیر</b><small>تاریخچه ثبت در دیتابیس.</small><Button onClick={onRefresh}>تازه‌سازی</Button></div></div>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>محصولات نیازمند توجه</h2><p>برای خروجی دقیق، موجودی اولیه و آستانه محصول را تنظیم کن.</p></div></div>
            {low.length ? <TableWrap><thead><tr><th>محصول</th><th>موجودی</th><th>آستانه کمبود</th><th>اقدام</th></tr></thead><tbody>{low.map(p => <tr key={p.id}><td>{p.name}</td><td><Pill value={String(p.stock_quantity)} tone="warning" /></td><td>{numberFa(p.low_stock_threshold)}</td><td><Button kind="primary" onClick={() => onAdjust(p)}>ثبت ورود کالا</Button></td></tr>)}</tbody></TableWrap> : <Empty>موردی زیر آستانه موجودی نیست یا آستانه‌ای تعریف نشده است.</Empty>}
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>گردش انبار</h2><p>مصرف سفارش، بازگشت از لغو، موجودی اولیه و اصلاح دستی.</p></div></div>
            {movements?.length ? <TableWrap><thead><tr><th>زمان</th><th>محصول</th><th>نوع حرکت</th><th>تغییر</th><th>قبل ← بعد</th><th>شماره سفارش</th><th>شرح</th><th>کاربر</th></tr></thead><tbody>
                {movements.map(m => <tr key={m.id}><td>{dateTime(m.created_at)}</td><td>{m.product?.name || 'محصول حذف‌شده'}</td><td>{({order:'مصرف سفارش',order_cancelled:'برگشت از لغو',manual:'اصلاح دستی',opening:'موجودی اولیه',stocktake:'شمارش موجودی'})[m.movement_type] || m.movement_type}</td><td className={Number(m.quantity_delta) < 0 ? 'op-negative' : 'op-positive'}>{Number(m.quantity_delta) > 0 ? '+' : ''}{numberFa(m.quantity_delta)}</td><td>{numberFa(m.stock_before)} ← {numberFa(m.stock_after)}</td><td>{m.order?.number || '—'}</td><td>{m.reason || '—'}</td><td>{m.user?.name || 'سیستم / QR'}</td></tr>)}
            </tbody></TableWrap> : <Empty>هنوز گردش موجودی ثبت نشده است.</Empty>}
        </section>
    </div>;
}

function TablesPage({ tables, onCreate, onUpdate, onRotate, onRefresh }) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    return <div className="page-content">
        <PageHeader title="میزها و QR" subtitle="هر QR به یک میز مشخص و غیرقابل‌جایگزینی وصل است؛ لینک از سرور گرفته می‌شود." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>افزودن میز</h2><p>اسم یکتا مثل «میز ۰۱» یا «تراس ۲» وارد کن.</p></div></div>
            <form className="op-form op-inline-form" onSubmit={e => { e.preventDefault(); onCreate({ name: name.trim(), description: description.trim() || null }); setName(''); setDescription(''); }}>
                <Field label="نام میز"><input value={name} onChange={e => setName(e.target.value)} maxLength="80" required placeholder="میز ۰۱" /></Field>
                <Field label="توضیح (اختیاری)"><input value={description} onChange={e => setDescription(e.target.value)} maxLength="255" placeholder="مثلاً تراس" /></Field>
                <Button kind="primary" type="submit">ایجاد میز</Button>
            </form>
        </section>
        <div className="op-toolbar op-toolbar-spaced"><span>{numberFa(tables?.length || 0)} میز در پاسخ فعلی</span><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
        {tables?.length ? <div className="op-table-grid">{tables.map(table => <article className="op-table-card" key={table.id}>
            <div className="op-table-head"><div className="op-table-symbol">⌂</div><div><h3>{table.name}</h3><small>{table.description || 'میز سالن'}</small></div><Pill value={table.is_active ? 'active' : 'inactive'} dictionary={{active:'فعال',inactive:'غیرفعال'}} /></div>
            <div className="op-table-state"><b>{numberFa(table.open_orders_count || 0)}</b><span>سفارش باز</span></div>
            <a className="op-link" href={table.menu_url} target="_blank" rel="noreferrer">{table.menu_url}</a>
            <div className="op-actions"><Button onClick={() => { navigator.clipboard?.writeText(new URL(table.menu_url, window.location.origin).href).then(() => alert('لینک QR کپی شد.')).catch(() => window.prompt('لینک را کپی کن:', new URL(table.menu_url, window.location.origin).href)); }}>کپی لینک QR</Button>
                <Button onClick={() => onRotate(table)}>تعویض QR</Button>
                <Button kind={table.is_active ? 'danger' : 'primary'} onClick={() => onUpdate(table, { is_active: !table.is_active })}>{table.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی'}</Button>
            </div>
        </article>)}</div> : <Empty>هنوز میزی ایجاد نشده است.</Empty>}
        <p className="op-note">این بخش لینک امن هر میز را آماده می‌کند. برای برچسب QR قابل اسکن، باید تصویر QR با پکیج تولید QR ساخته شود؛ لینک به‌تنهایی تصویر بارکد نیست.</p>
    </div>;
}

function ReservationsPage({ reservations, tables, onCreate, onTransition, onRefresh }) {
    const emptyForm = { restaurant_table_id: '', customer_name: '', phone: '', party_size: '2', starts_at: '', duration_minutes: '90', notes: '' };
    const [form, setForm] = useState(emptyForm);
    const [requestKey, setRequestKey] = useState(() => idempotencyKey());
    const set = (key, value) => setForm(v => ({...v, [key]: value}));
    return <div className="page-content">
        <PageHeader title="رزرو میز" subtitle="رزرو با بازه زمانی و کنترل هم‌پوشانی برای هر میز؛ تغییر وضعیت در تاریخچه حسابرسی می‌شود." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>ثبت رزرو جدید</h2><p>اگر میز مشخص شود، برخورد زمانی روی سرور کنترل می‌شود.</p></div></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onCreate({ ...form, restaurant_table_id: form.restaurant_table_id ? Number(form.restaurant_table_id) : null, party_size: Number(form.party_size), duration_minutes: Number(form.duration_minutes), starts_at: localDateTime(form.starts_at), idempotency_key: requestKey }).then(ok => { if (ok) { setForm(emptyForm); setRequestKey(idempotencyKey()); } }); }}>
                <Field label="نام مشتری"><input value={form.customer_name} onChange={e => set('customer_name', e.target.value)} maxLength="150" required /></Field>
                <Field label="تلفن"><input value={form.phone} onChange={e => set('phone', e.target.value)} maxLength="30" required /></Field>
                <Field label="تعداد نفرات"><input type="number" min="1" max="50" value={form.party_size} onChange={e => set('party_size', e.target.value)} required /></Field>
                <Field label="میز (اختیاری)"><select value={form.restaurant_table_id} onChange={e => set('restaurant_table_id', e.target.value)}><option value="">بدون تخصیص میز</option>{(tables || []).filter(t => t.is_active).map(t => <option key={t.id} value={t.id}>{t.name}</option>)}</select></Field>
                <Field label="تاریخ و ساعت"><input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)} required /></Field>
                <Field label="مدت (دقیقه)"><select value={form.duration_minutes} onChange={e => set('duration_minutes', e.target.value)}><option value="60">۶۰</option><option value="90">۹۰</option><option value="120">۱۲۰</option><option value="180">۱۸۰</option></select></Field>
                <Field label="یادداشت"><input value={form.notes} onChange={e => set('notes', e.target.value)} maxLength="1000" /></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ثبت رزرو</Button></div>
            </form>
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>رزروها</h2><p>وضعیت هر رزرو با قواعد گردش کار تغییر می‌کند.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {reservations?.length ? <TableWrap><thead><tr><th>شناسه</th><th>مشتری</th><th>میز</th><th>زمان</th><th>نفرات</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>{reservations.map(r => <tr key={r.id}><td className="mono">{r.reference}</td><td>{r.customer_name}<small className="op-subtext">{r.phone}</small></td><td>{r.table?.name || 'تخصیص‌نیافته'}</td><td>{dateTime(r.starts_at)}</td><td>{numberFa(r.party_size)}</td><td><Pill value={r.status} dictionary={reservationStatusLabel} /></td><td><div className="op-actions">{reservationNext(r.status).map(s => <Button key={s} kind={s === 'cancelled' ? 'danger' : 'secondary'} onClick={() => onTransition(r, s)}>{reservationStatusLabel[s]}</Button>)}</div></td></tr>)}</tbody></TableWrap> : <Empty>رزروی ثبت نشده است.</Empty>}
        </section>
    </div>;
}
function reservationNext(status) {
    return ({ pending: ['confirmed','cancelled'], confirmed: ['arrived','no_show','cancelled'], arrived: ['cancelled'], cancelled: [], no_show: [] })[status] || [];
}

function CustomersPage({ feedback, onRespond, onRefresh }) {
    const [drafts, setDrafts] = useState({});
    return <div className="page-content">
        <PageHeader title="بازخورد مشتریان" subtitle="پیگیری پیشنهادها و شکایت‌ها و ثبت پاسخ مسئول؛ هر پاسخ روی سرور می‌ماند." />
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>بازخوردها</h2><p>برای هر مورد وضعیت رسیدگی و پاسخ را ثبت کن.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {feedback?.length ? <div className="op-feedback-list">{feedback.map(item => <article className="op-feedback" key={item.id}>
                <div className="op-feedback-head"><div><b>{item.customer_name || 'مشتری بدون نام'}</b><small>{item.phone || item.email || 'راه ارتباطی ثبت نشده'} · {dateTime(item.created_at)}</small></div><Pill value={item.status} dictionary={feedbackStatusLabel} /></div>
                <div className="op-feedback-meta"><span>{({complaint:'شکایت',suggestion:'پیشنهاد',praise:'قدردانی'})[item.kind] || item.kind}</span>{item.rating && <span>امتیاز: {numberFa(item.rating)} از ۵</span>}{item.order?.number && <span>سفارش {item.order.number}</span>}</div>
                <p>{item.message}</p>{item.admin_response && <blockquote>{item.admin_response}</blockquote>}
                <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); const draft = drafts[item.id] || {}; onRespond(item, { status: draft.status || (item.status === 'new' ? 'reviewing' : item.status), admin_response: draft.response ?? item.admin_response ?? '' }); }}>
                    <Field label="وضعیت رسیدگی"><select value={(drafts[item.id]?.status) ?? item.status} onChange={e => setDrafts(v => ({...v,[item.id]:{...(v[item.id]||{}),status:e.target.value}}))}>{Object.entries(feedbackStatusLabel).map(([value,label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="پاسخ مدیر"><input value={(drafts[item.id]?.response) ?? item.admin_response ?? ''} onChange={e => setDrafts(v => ({...v,[item.id]:{...(v[item.id]||{}),response:e.target.value}}))} maxLength="5000" placeholder="پاسخ قابل ارسال یا یادداشت رسیدگی" /></Field>
                    <div className="op-form-actions"><Button kind="primary" type="submit">ثبت پاسخ</Button></div>
                </form>
            </article>)}</div> : <Empty>بازخوردی ثبت نشده است.</Empty>}
        </section>
    </div>;
}

function ExpensesPage({ expenses, onCreate, onRefresh }) {
    const [form, setForm] = useState({ title: '', category: 'ingredients', amount: '', payment_method: 'cash', is_paid: true, note: '' });
    const set = (key, value) => setForm(v => ({...v,[key]:value}));
    return <div className="page-content">
        <PageHeader title="هزینه‌های عملیاتی" subtitle="ثبت هزینه همراه روش پرداخت؛ هزینه نقدی پرداخت‌شده در دفتر صندوق نیز ثبت می‌شود." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>ثبت هزینه</h2><p>برای هزینه نقدی، شیفت صندوق باید باز باشد.</p></div></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onCreate({ ...form, amount: Number(form.amount) }); }}>
                <Field label="عنوان هزینه"><input value={form.title} onChange={e => set('title',e.target.value)} maxLength="150" required placeholder="مثلاً خرید شیر" /></Field>
                <Field label="دسته"><select value={form.category} onChange={e => set('category',e.target.value)}>{Object.entries(expenseCategoryLabel).map(([value,label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="مبلغ (تومان)"><input type="number" min="1" value={form.amount} onChange={e => set('amount',e.target.value)} required /></Field>
                <Field label="روش پرداخت"><select value={form.payment_method} onChange={e => set('payment_method',e.target.value)}><option value="cash">نقدی</option><option value="card_terminal">کارت‌خوان (ثبت‌شده)</option><option value="other">سایر</option></select></Field>
                <Field label="وضعیت پرداخت"><select value={form.is_paid ? 'paid' : 'unpaid'} onChange={e => set('is_paid',e.target.value === 'paid')}><option value="paid">پرداخت شده</option><option value="unpaid">پرداخت نشده</option></select></Field>
                <Field label="یادداشت"><input value={form.note} onChange={e => set('note',e.target.value)} maxLength="1000" /></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ثبت هزینه</Button></div>
            </form>
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>آخرین هزینه‌ها</h2><p>هزینه‌ی پرداخت‌نشده با خروج وجه صندوق یکی نیست.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {expenses?.length ? <TableWrap><thead><tr><th>زمان</th><th>عنوان</th><th>دسته</th><th>مبلغ</th><th>پرداخت</th><th>روش</th><th>ثبت‌کننده</th></tr></thead><tbody>{expenses.map(ex => <tr key={ex.id}><td>{dateTime(ex.created_at)}</td><td>{ex.title}</td><td>{expenseCategoryLabel[ex.category] || ex.category}</td><td>{money(ex.amount)}</td><td><Pill value={ex.is_paid ? 'paid' : 'unpaid'} dictionary={{paid:'پرداخت شده',unpaid:'پرداخت نشده'}} /></td><td>{paymentMethodLabel[ex.payment_method] || ex.payment_method}</td><td>{ex.user?.name || '—'}</td></tr>)}</tbody></TableWrap> : <Empty>هزینه‌ای ثبت نشده است.</Empty>}
        </section>
    </div>;
}

function ReportsPage({ report, onRefresh }) {
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    return <div className="page-content">
        <PageHeader title="گزارش‌های مالی و فروش" subtitle="فروش ثبت‌شده، دریافتی، بازپرداخت و پرداخت‌ها تفکیک شده‌اند؛ سود تا تکمیل بهای تمام‌شده گزارش نمی‌شود." />
        <form className="op-toolbar" onSubmit={e => { e.preventDefault(); onRefresh(from, to); }}><Field label="از تاریخ"><input type="date" value={from} onChange={e => setFrom(e.target.value)} /></Field><Field label="تا تاریخ"><input type="date" value={to} min={from || undefined} onChange={e => setTo(e.target.value)} /></Field><Button kind="primary" type="submit">اجرای گزارش</Button><Button type="button" onClick={() => { setFrom(''); setTo(''); onRefresh('', ''); }}>این ماه</Button></form>
        {report?.summary && <div className="op-grid op-grid-3">
            <article className="stat-card"><div className="stat-label">سفارش‌ها</div><div className="stat-value">{numberFa(report.summary.orders_count)}</div><div className="stat-sub">{numberFa(report.summary.cancelled_orders)} لغوشده (در تعداد کل)</div></article>
            <article className="stat-card"><div className="stat-label">فروش ثبت‌شده</div><div className="stat-value">{money(report.summary.registered_sales)}</div><div className="stat-sub">مجموع سفارش‌ها، به‌جز لغوشده‌ها</div></article>
            <article className="stat-card"><div className="stat-label">دریافتی خالص بازه</div><div className="stat-value">{money(report.summary.collections_net)}</div><div className="stat-sub">دریافتی ناخالص {money(report.summary.collections_gross)} منهای بازپرداخت {money(report.summary.refunds)}</div></article>
        </div>}
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>فروش روزانه</h2><p>بر اساس تاریخ ایجاد سفارش؛ فروش ثبت‌شده را با وجه دریافتی یکی نگیر.</p></div></div>
            {report?.daily?.length ? <TableWrap><thead><tr><th>روز</th><th>تعداد سفارش</th><th>فروش ثبت‌شده</th></tr></thead><tbody>{report.daily.map(day => <tr key={day.day}><td>{day.day}</td><td>{numberFa(day.orders_count)}</td><td>{money(day.registered_sales)}</td></tr>)}</tbody></TableWrap> : <Empty>در این بازه سفارشی وجود ندارد.</Empty>}
        </section>
        <section className="op-grid op-grid-2">
            <article className="panel orders-panel"><div className="panel-heading"><div><h2>دریافتی به تفکیک روش</h2></div></div>{report?.payments_by_method?.length ? <TableWrap><thead><tr><th>روش</th><th>تعداد</th><th>مبلغ</th></tr></thead><tbody>{report.payments_by_method.map(p => <tr key={p.method}><td>{paymentMethodLabel[p.method] || p.method}</td><td>{numberFa(p.count)}</td><td>{money(p.amount)}</td></tr>)}</tbody></TableWrap> : <Empty>دریافتی‌ای برای این بازه ثبت نشده است.</Empty>}</article>
            <article className="panel orders-panel"><div className="panel-heading"><div><h2>محصولات پرفروش</h2></div></div>{report?.top_products?.length ? <TableWrap><thead><tr><th>محصول</th><th>تعداد</th><th>فروش ناخالص</th></tr></thead><tbody>{report.top_products.map((p,i) => <tr key={(p.product_id || 'deleted') + '-' + p.name + '-' + i}><td>{p.name}</td><td>{numberFa(p.units_sold)}</td><td>{money(p.gross_sales)}</td></tr>)}</tbody></TableWrap> : <Empty>محصولی در این بازه فروخته نشده است.</Empty>}</article>
        </section>
        <p className="op-note">{report?.note || 'سود خالص تا زمان کامل‌بودن هزینه مواد اولیه، ضایعات، مالیات و هزینه‌های دوره محاسبه نمی‌شود.'}</p>
    </div>;
}

function SettingsPage({ settings, onSave, onRefresh }) {
    const [form, setForm] = useState(null);
    useEffect(() => { if (settings) setForm({ name: settings.name || '', phone: settings.phone || '', address: settings.address || '', currency_label: settings.currency_label || 'تومان', tax_basis_points: String(settings.tax_basis_points || 0), service_basis_points: String(settings.service_basis_points || 0), receipt_footer: settings.receipt_footer || '', paper_width: String(settings.paper_width || 80), customer_menu_enabled: Boolean(settings.customer_menu_enabled) }); }, [settings]);
    const set = (key, value) => setForm(v => ({...v,[key]:value}));
    if (!form) return <div className="page-content"><PageHeader title="تنظیمات مجموعه" subtitle="اطلاعات و مالیات خدمات، منوی QR و کاغذ فیش."/><Loading /></div>;
    return <div className="page-content">
        <PageHeader title="تنظیمات مجموعه" subtitle="محاسبات سفارش از تنظیمات ذخیره‌شده سرور استفاده می‌کند." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>اطلاعات و محاسبات فاکتور</h2><p>مالیات و سرویس به شکل basis point ذخیره می‌شوند؛ مثلاً ۹٪ برابر ۹۰۰ است.</p></div><Button onClick={onRefresh}>بازخوانی تنظیمات</Button></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onSave({ ...form, tax_basis_points: Number(form.tax_basis_points), service_basis_points: Number(form.service_basis_points), paper_width: Number(form.paper_width) }); }}>
                <Field label="نام مجموعه"><input value={form.name} onChange={e => set('name',e.target.value)} maxLength="150" required /></Field>
                <Field label="تلفن"><input value={form.phone} onChange={e => set('phone',e.target.value)} maxLength="30" /></Field>
                <Field label="واحد پول"><input value={form.currency_label} onChange={e => set('currency_label',e.target.value)} maxLength="20" required /></Field>
                <Field label="مالیات (%)"><input type="number" min="0" max="100" step="0.01" value={Number(form.tax_basis_points)/100} onChange={e => set('tax_basis_points', String(Math.round(Number(e.target.value || 0)*100)))} /></Field>
                <Field label="سرویس (%)"><input type="number" min="0" max="100" step="0.01" value={Number(form.service_basis_points)/100} onChange={e => set('service_basis_points', String(Math.round(Number(e.target.value || 0)*100)))} /></Field>
                <Field label="عرض کاغذ فیش"><select value={form.paper_width} onChange={e => set('paper_width',e.target.value)}><option value="58">۵۸ میلی‌متر</option><option value="80">۸۰ میلی‌متر</option></select></Field>
                <Field label="آدرس"><input value={form.address} onChange={e => set('address',e.target.value)} maxLength="2000" /></Field>
                <Field label="متن پایین فیش"><input value={form.receipt_footer} onChange={e => set('receipt_footer',e.target.value)} maxLength="1000" placeholder="مثلاً ممنون از همراهی شما" /></Field>
                <Field label="منوی QR مشتری"><select value={form.customer_menu_enabled ? 'yes' : 'no'} onChange={e => set('customer_menu_enabled',e.target.value === 'yes')}><option value="yes">فعال</option><option value="no">غیرفعال</option></select></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ذخیره تنظیمات</Button></div>
            </form>
        </section>
        <section className="panel op-card"><h2>چاپگر فیش</h2><p className="op-note">این تنظیم عرض صفحه چاپ را مشخص می‌کند، اما چاپ مستقیم و بی‌پنجره نیازمند نرم‌افزار واسط روی کامپیوتر صندوق است. راهنمای نصب در docs/receipt-printer-integration.md ثبت شده است.</p><Button onClick={() => window.print()}>آزمایش چاپ مرورگر</Button></section>
    </div>;
}

function OrderModal({ products, tables, onClose, onSubmit }) {
    const activeProducts = (products || []).filter(p => p.status === 'active');
    const [channel, setChannel] = useState('dine_in');
    const [tableId, setTableId] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [customerNote, setCustomerNote] = useState('');
    const [discount, setDiscount] = useState('0');
    const [requestKey] = useState(() => idempotencyKey());
    const [productId, setProductId] = useState(activeProducts[0] ? String(activeProducts[0].id) : '');
    const [optionIds, setOptionIds] = useState([]);
    const [cart, setCart] = useState([]);
    const selectedProduct = activeProducts.find(p => String(p.id) === productId);
    const subtotal = cart.reduce((sum,line) => {
        const product = activeProducts.find(p => Number(p.id) === Number(line.product_id));
        const optionTotal = (line.options || []).reduce((s,id) => s + Number(product?.options?.find(o => Number(o.id) === Number(id))?.price || 0), 0);
        return sum + (Number(product?.base_price || 0) + optionTotal) * Number(line.quantity || 0);
    },0);
    const add = () => {
        if (!selectedProduct) return;
        const options = [...optionIds].sort((a,b) => a-b);
        const signature = options.join(',');
        setCart(lines => {
            const existing = lines.find(line => Number(line.product_id) === Number(selectedProduct.id) && [...line.options].sort((a,b)=>a-b).join(',') === signature);
            if (existing) return lines.map(line => line === existing ? {...line,quantity:line.quantity+1} : line);
            return [...lines,{product_id:Number(selectedProduct.id),quantity:1,options,note:''}];
        });
        setOptionIds([]);
    };
    return <Modal title="ثبت سفارش" eyebrow="NEW ORDER / LIVE API" onClose={onClose} wide>
        <p className="op-note">قیمت نهایی و مالیات را سرور محاسبه می‌کند. این فرم فقط اقلام و تخفیف را می‌فرستد؛ پرداخت جدا ثبت می‌شود.</p>
        {activeProducts.length === 0 ? <Empty>برای ثبت سفارش، ابتدا یک محصول فعال بساز.</Empty> : <form className="op-form" onSubmit={e => { e.preventDefault(); onSubmit({ channel, restaurant_table_id: channel === 'dine_in' && tableId ? Number(tableId) : null, customer_name: customerName || null, customer_phone: customerPhone || null, customer_note: customerNote || null, discount: Number(discount || 0), idempotency_key: requestKey, items: cart.map(line => ({...line,options:line.options.map(id => ({id}))})) }); }}>
            <div className="op-form-grid">
                <Field label="کانال سفارش"><select value={channel} onChange={e => { setChannel(e.target.value); if (e.target.value !== 'dine_in') setTableId(''); }}><option value="dine_in">میز / حضوری</option><option value="counter">صندوق / حضوری</option><option value="takeaway">بیرون‌بر</option><option value="online">آنلاین</option></select></Field>
                {channel === 'dine_in' && <Field label="میز"><select value={tableId} onChange={e=>setTableId(e.target.value)}><option value="">بدون تخصیص میز</option>{(tables || []).filter(t=>t.is_active).map(t=><option key={t.id} value={t.id}>{t.name}</option>)}</select></Field>}
                <Field label="نام مشتری (اختیاری)"><input value={customerName} onChange={e=>setCustomerName(e.target.value)} maxLength="150" /></Field>
                <Field label="تلفن (اختیاری)"><input value={customerPhone} onChange={e=>setCustomerPhone(e.target.value)} maxLength="30" /></Field>
                <Field label="تخفیف (تومان)"><input type="number" min="0" max={subtotal} value={discount} onChange={e=>setDiscount(e.target.value)} required /></Field>
                <Field label="یادداشت سفارش"><input value={customerNote} onChange={e=>setCustomerNote(e.target.value)} maxLength="2000" /></Field>
            </div>
            <div className="op-picker">
                <Field label="محصول"><select value={productId} onChange={e=>{setProductId(e.target.value);setOptionIds([]);}}>{activeProducts.map(p=><option key={p.id} value={p.id}>{p.name} — {money(p.base_price)}{p.stock_quantity !== null ? (' · موجودی '+numberFa(p.stock_quantity)) : ''}</option>)}</select></Field>
                {selectedProduct?.options?.filter(o=>o.is_active).length > 0 && <div className="op-option-list"><b>سایز / افزودنی</b>{selectedProduct.options.filter(o=>o.is_active).map(opt=><label key={opt.id}><input type="checkbox" checked={optionIds.includes(Number(opt.id))} onChange={e=>setOptionIds(ids=>e.target.checked?[...ids,Number(opt.id)]:ids.filter(id=>id!==Number(opt.id)))} /><span>{opt.name}</span><small>{Number(opt.price) ? '+'+money(opt.price) : 'رایگان'}</small></label>)}</div>}
                <Button kind="primary" type="button" onClick={add}>افزودن به سفارش ＋</Button>
            </div>
            <div className="op-cart">
                <div className="panel-heading"><div><h3>اقلام سفارش</h3><p>{numberFa(cart.length)} ردیف</p></div><b>{money(subtotal)}</b></div>
                {cart.length ? cart.map((line,index)=>{
                    const product=activeProducts.find(p=>Number(p.id)===Number(line.product_id));
                    const optionNames=(line.options||[]).map(id=>product?.options?.find(o=>Number(o.id)===Number(id))?.name).filter(Boolean);
                    return <div className="op-cart-line" key={line.product_id+'-'+line.options.join('-')+'-'+index}><div><b>{product?.name}</b><small>{optionNames.join('، ') || 'بدون افزودنی'}</small></div><input aria-label="تعداد" type="number" min="1" max="100" value={line.quantity} onChange={e=>setCart(lines=>lines.map((entry,i)=>i===index?{...entry,quantity:Math.max(1,Math.min(100,Number(e.target.value)||1))}:entry))}/><Button type="button" kind="danger" onClick={()=>setCart(lines=>lines.filter((_,i)=>i!==index))}>حذف</Button></div>;
                }) : <Empty>برای شروع، یک محصول به سفارش اضافه کن.</Empty>}
            </div>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit" disabled={!cart.length || Number(discount) > subtotal}>ثبت در سرور و کم‌کردن موجودی رهگیری‌شده</Button></div>
        </form>}
    </Modal>;
}

function ProductModal({ product, categories, onClose, onSave }) {
    const [form,setForm]=useState({category_id:String(product?.category_id || categories?.[0]?.id || ''),name:product?.name || '',description:product?.description || '',base_price:String(product?.base_price ?? ''),status:product?.status || 'active',stock_quantity:product?.stock_quantity === null || product?.stock_quantity === undefined ? '' : String(product.stock_quantity),low_stock_threshold:product?.low_stock_threshold === null || product?.low_stock_threshold === undefined ? '' : String(product.low_stock_threshold), options:(product?.options || []).map(o=>({id:o.id,name:o.name,type:o.type,price:String(o.price),is_active:Boolean(o.is_active)}))});
    const set=(key,value)=>setForm(v=>({...v,[key]:value}));
    const addOption=()=>set('options',[...form.options,{name:'',type:'addon',price:'0',is_active:true}]);
    const updateOption=(i,key,value)=>set('options',form.options.map((o,index)=>index===i?{...o,[key]:value}:o));
    const removeOption=i=>set('options',form.options.filter((_,index)=>index!==i));
    return <Modal title={product ? 'ویرایش محصول' : 'محصول جدید'} eyebrow="PRODUCT CATALOG" onClose={onClose} wide>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSave({ ...form,category_id:Number(form.category_id),base_price:Number(form.base_price),stock_quantity:form.stock_quantity===''?null:Number(form.stock_quantity),low_stock_threshold:form.low_stock_threshold===''?null:Number(form.low_stock_threshold),options:form.options.map(o=>({...o,price:Number(o.price)})) },product?.id);}}>
            <div className="op-form-grid">
                <Field label="نام محصول"><input value={form.name} onChange={e=>set('name',e.target.value)} maxLength="150" required /></Field>
                <Field label="دسته‌بندی"><select value={form.category_id} onChange={e=>set('category_id',e.target.value)} required>{(categories||[]).map(c=><option key={c.id} value={c.id}>{c.name}</option>)}</select></Field>
                <Field label="قیمت (تومان)"><input type="number" min="0" value={form.base_price} onChange={e=>set('base_price',e.target.value)} required /></Field>
                <Field label="وضعیت فروش"><select value={form.status} onChange={e=>set('status',e.target.value)}><option value="active">فعال</option><option value="inactive">غیرفعال</option><option value="unavailable">ناموجود</option></select></Field>
                <Field label="موجودی فعلی" hint={product && product.stock_quantity !== null ? 'رهگیری موجودی فعال است؛ برای خاموش‌کردن آن سوابق قابل حسابرسی از بین می‌رود.' : 'خالی = بدون رهگیری'}><input type="number" min="0" value={form.stock_quantity} required={Boolean(product && product.stock_quantity !== null)} onChange={e=>set('stock_quantity',e.target.value)} placeholder="خالی = بدون رهگیری" /></Field>
                <Field label="آستانه کمبود"><input type="number" min="0" value={form.low_stock_threshold} onChange={e=>set('low_stock_threshold',e.target.value)} placeholder="مثلاً ۵" /></Field>
                <Field label="توضیحات"><input value={form.description} onChange={e=>set('description',e.target.value)} maxLength="3000" /></Field>
            </div>
            <div className="op-options-editor"><div className="panel-heading"><div><h3>سایزها و افزودنی‌ها</h3><p>تغییر گزینه‌ها با ارسال این فرم جایگزین لیست فعلی می‌شود.</p></div><Button type="button" onClick={addOption}>＋ گزینه</Button></div>
                {form.options.map((option,i)=><div className="op-option-row" key={option.id || 'new-'+i}>
                    <input value={option.name} onChange={e=>updateOption(i,'name',e.target.value)} placeholder="نام گزینه" maxLength="100" required />
                    <select value={option.type} onChange={e=>updateOption(i,'type',e.target.value)}><option value="size">سایز</option><option value="addon">افزودنی</option></select>
                    <input type="number" min="0" value={option.price} onChange={e=>updateOption(i,'price',e.target.value)} aria-label="قیمت گزینه" required />
                    <label className="op-option-active"><input type="checkbox" checked={Boolean(option.is_active)} onChange={e=>updateOption(i,'is_active',e.target.checked)} />فعال</label>
                    <Button type="button" kind="danger" onClick={()=>removeOption(i)}>حذف</Button>
                </div>)}
            </div>
            <p className="op-note">موجودی را اگر قابل شمارش نیست خالی بگذار. پس از فعال‌شدن رهگیری، اصلاح عدد موجودی در گردش انبار ثبت می‌شود.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره محصول</Button></div>
        </form>
    </Modal>;
}

function CategoryModal({ onClose, onSave }) {
    const [name,setName]=useState('');
    const [sortOrder,setSortOrder]=useState('0');
    return <Modal title="دسته‌بندی جدید" eyebrow="MENU CATEGORY" onClose={onClose}>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSave({name:name.trim(),sort_order:Number(sortOrder),is_active:true});}}>
            <Field label="نام دسته"><input value={name} onChange={e=>setName(e.target.value)} maxLength="150" required /></Field>
            <Field label="ترتیب نمایش"><input type="number" min="0" value={sortOrder} onChange={e=>setSortOrder(e.target.value)} /></Field>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ایجاد دسته</Button></div>
        </form>
    </Modal>;
}

function PaymentModal({ order, onClose, onSubmit }) {
    const [amount,setAmount]=useState(String(getRemaining(order)));
    const [method,setMethod]=useState('cash');
    const [reference,setReference]=useState('');
    const [note,setNote]=useState('');
    const [requestKey] = useState(() => idempotencyKey());
    const remaining=getRemaining(order);
    return <Modal title="ثبت پرداخت" eyebrow="PAYMENT / CASHIER" onClose={onClose}>
        <div className="op-payment-summary"><span>سفارش {order.number}</span><b>{money(order.total)}</b><small>مانده محاسبه‌شده: {money(remaining)}</small></div>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit({amount:Number(amount),method,reference:reference||null,note:note||null,idempotency_key:requestKey},order.id);}}>
            <Field label="مبلغ دریافت (تومان)"><input type="number" min="1" max={remaining} value={amount} onChange={e=>setAmount(e.target.value)} required /></Field>
            <Field label="روش پرداخت"><select value={method} onChange={e=>setMethod(e.target.value)}><option value="cash">نقدی (نیازمند شیفت باز)</option><option value="card_terminal">کارت‌خوان — ثبت تأیید توسط کارمند</option><option value="other">سایر / انتقال تأییدشده خارج از سامانه</option></select></Field>
            <Field label="شماره پیگیری (اختیاری)"><input value={reference} onChange={e=>setReference(e.target.value)} maxLength="120" /></Field>
            <Field label="یادداشت"><input value={note} onChange={e=>setNote(e.target.value)} maxLength="500" /></Field>
            <p className="op-note">روش «کارت‌خوان» به معنی تأیید خودکار دستگاه نیست؛ کاربر فقط دریافت وجه را ثبت می‌کند. اتصال درگاه/ترمینال برای تأیید فنی جداست.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit" disabled={Number(amount)<1 || Number(amount)>remaining}>ثبت پرداخت</Button></div>
        </form>
    </Modal>;
}

function CancelModal({ order, onClose, onSubmit }) {
    const [reason,setReason]=useState('');
    return <Modal title="لغو سفارش" eyebrow="CANCEL ORDER" onClose={onClose}>
        <p className="op-note">لغو فقط وقتی ممکن است که سفارش پرداخت خالص نداشته باشد. اگر پرداختی دارد، ابتدا بازپرداخت را از سوابق پرداخت انجام بده.</p>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit(reason,order.id);}}>
            <Field label="علت لغو (اجباری)"><input value={reason} onChange={e=>setReason(e.target.value)} minLength="3" maxLength="500" required /></Field>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>بازگشت</Button><Button kind="danger" type="submit">لغو و بازگرداندن موجودی رهگیری‌شده</Button></div>
        </form>
    </Modal>;
}

function RefundModal({ payment, orderId, onClose, onSubmit }) {
    const alreadyRefunded=(payment.refunds||[]).reduce((sum,r)=>sum+Number(r.amount||0),0);
    const refundable=Math.max(0,Number(payment.amount||0)-alreadyRefunded);
    const [amount,setAmount]=useState(String(refundable));
    const [reason,setReason]=useState('');
    const [requestKey] = useState(() => idempotencyKey());
    return <Modal title="بازپرداخت" eyebrow="REFUND / AUDITABLE" onClose={onClose}>
        <div className="op-payment-summary"><span>مبلغ پرداخت اولیه</span><b>{money(payment.amount)}</b><small>مانده قابل بازپرداخت: {money(refundable)}</small></div>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit({amount:Number(amount),reason,idempotency_key:requestKey},payment.id,orderId);}}>
            <Field label="مبلغ بازپرداخت (تومان)"><input type="number" min="1" max={refundable} value={amount} onChange={e=>setAmount(e.target.value)} required /></Field>
            <Field label="علت بازپرداخت"><input value={reason} onChange={e=>setReason(e.target.value)} minLength="3" maxLength="500" required /></Field>
            <p className="op-note">بازپرداخت نقدی نیازمند شیفت باز است. بازپرداخت آنلاین بدون اتصال به درگاه از سمت بانک انجام نمی‌شود.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="danger" type="submit" disabled={Number(amount)<1 || Number(amount)>refundable}>ثبت بازپرداخت</Button></div>
        </form>
    </Modal>;
}

function OrderDetailsModal({ order, onClose, onPrint, onRefund }) {
    const payments=order?.payments || [];
    const items=order?.items || [];
    const subtotal=(order?.subtotal || 0);
    const paid=payments.filter(p=>p.status==='succeeded').reduce((sum,p)=>sum+Number(p.amount||0),0);
    const refunded=(order?.refunds || []).reduce((sum,r)=>sum+Number(r.amount||0),0);
    return <Modal title={'سفارش ' + (order?.number || '')} eyebrow="ORDER DETAIL / AUDIT TRAIL" onClose={onClose} wide>
        {!order ? <Loading /> : <>
            <div className="op-detail-summary"><div><small>کانال / میز</small><b>{channelLabel(order)}</b></div><div><small>وضعیت آماده‌سازی</small><Pill value={order.status} dictionary={orderStatusLabel} /></div><div><small>وضعیت پرداخت</small><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></div><div><small>مبلغ نهایی</small><b>{money(order.total)}</b></div></div>
            <div className="panel-heading"><div><h3>اقلام</h3><p>مبلغ‌های ذخیره‌شده از زمان سفارش</p></div></div>
            {items.length ? <TableWrap><thead><tr><th>محصول</th><th>تعداد</th><th>قیمت واحد</th><th>افزودنی‌ها</th><th>جمع ردیف</th></tr></thead><tbody>{items.map(item=><tr key={item.id}><td>{item.name}<small className="op-subtext">{item.note || ''}</small></td><td>{numberFa(item.quantity)}</td><td>{money(item.unit_price)}</td><td>{(item.options||[]).map(o=>o.name).join('، ') || '—'}</td><td>{money(item.line_total)}</td></tr>)}</tbody></TableWrap> : <Empty />}
            <div className="op-detail-totals"><span>جمع اقلام: <b>{money(subtotal)}</b></span><span>تخفیف: <b>{money(order.discount)}</b></span><span>مالیات: <b>{money(order.tax)}</b></span><span>سرویس: <b>{money(order.service)}</b></span><span>دریافت ناخالص: <b>{money(paid)}</b></span><span>بازپرداخت: <b>{money(refunded)}</b></span><span>مانده: <b>{money(Math.max(0,Number(order.total)-paid+refunded))}</b></span></div>
            <div className="panel-heading"><div><h3>سوابق پرداخت</h3><p>پرداخت‌ها تغییرناپذیرند؛ اصلاح از مسیر بازپرداخت ثبت می‌شود.</p></div></div>
            {payments.length ? <TableWrap><thead><tr><th>زمان</th><th>روش</th><th>مبلغ</th><th>وضعیت</th><th>پیگیری</th><th>بازپرداخت</th></tr></thead><tbody>{payments.map(p=>{const refundable=Math.max(0,Number(p.amount)-(p.refunds||[]).reduce((sum,r)=>sum+Number(r.amount||0),0));return <tr key={p.id}><td>{dateTime(p.paid_at)}</td><td>{paymentMethodLabel[p.method]||p.method}</td><td>{money(p.amount)}</td><td>{p.status}</td><td>{p.reference||'—'}</td><td>{refundable>0&&p.status==='succeeded'?<Button kind="danger" onClick={()=>onRefund(p,order.id)}>بازپرداخت تا {money(refundable)}</Button>:'—'}</td></tr>;})}</tbody></TableWrap> : <Empty>برای این سفارش هنوز پرداختی ثبت نشده است.</Empty>}
            <div className="op-form-actions"><Button onClick={()=>onPrint(order.id,'invoice')}>چاپ فاکتور</Button><Button onClick={()=>onPrint(order.id,'kitchen')}>فیش آشپزخانه</Button><Button kind="primary" onClick={()=>onPrint(order.id,'receipt')}>چاپ رسید پرداخت</Button><Button onClick={onClose}>بستن</Button></div>
        </>}
    </Modal>;
}

function escapeHtml(value) {
    return String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;");
}
function printHtml(order, type, settings) {
    const width=Number(settings?.paper_width) === 58 ? 58 : 80;
    const kitchen=type==='kitchen';
    const title=kitchen?'فیش آشپزخانه':type==='receipt'?'رسید پرداخت':'فاکتور فروش';
    const itemRows=(order.items||[]).map(item=>'<tr><td>'+escapeHtml(item.name)+(item.options?.length?'<small>'+escapeHtml(item.options.map(o=>o.name).join('، '))+'</small>':'')+'</td><td>'+numberFa(item.quantity)+'</td><td>'+money(item.line_total)+'</td></tr>').join('');
    const paymentRows=(order.payments||[]).filter(p=>p.status==='succeeded').map(p=>'<p class="row"><span>'+escapeHtml(paymentMethodLabel[p.method]||p.method)+'</span><b>'+money(p.amount)+'</b></p>').join('');
    const totals=kitchen?'':('<hr/><p class="row"><span>جمع اقلام</span><b>'+money(order.subtotal)+'</b></p><p class="row"><span>تخفیف</span><b>'+money(order.discount)+'</b></p><p class="row"><span>مالیات</span><b>'+money(order.tax)+'</b></p><p class="row"><span>سرویس</span><b>'+money(order.service)+'</b></p><p class="row total"><span>مبلغ نهایی</span><b>'+money(order.total)+'</b></p>');
    return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'+title+'</title><style>@page{size:'+width+'mm auto;margin:3mm}*{box-sizing:border-box}body{width:'+(width-6)+'mm;margin:0 auto;font:12px Tahoma,Arial,sans-serif;color:#111}h1{text-align:center;font-size:18px;margin:0 0 6px}h2{text-align:center;font-size:13px;margin:0 0 10px}p{margin:4px 0}.muted{text-align:center;color:#555;font-size:10px}.row{display:flex;justify-content:space-between;gap:6px}hr{border:0;border-top:1px dashed #777;margin:8px 0}table{width:100%;border-collapse:collapse;font-size:11px;margin-top:10px}th,td{text-align:right;padding:5px 1px;border-bottom:1px dashed #aaa;vertical-align:top}td small{display:block;font-size:9px;margin-top:2px}.total{font-size:14px;font-weight:bold;border-top:1px solid #222;padding-top:7px}.foot{text-align:center;margin-top:15px;font-size:10px;white-space:pre-wrap}@media screen{body{margin:12px auto}}</style></head><body><h1>'+escapeHtml(settings?.name||'خانه گیلاسی')+'</h1><h2>'+title+'</h2><p class="muted">'+escapeHtml(settings?.phone||'')+'</p><p class="row"><span>شماره</span><b>'+escapeHtml(order.number||order.id)+'</b></p><p class="row"><span>زمان</span><b>'+escapeHtml(dateTime(order.created_at))+'</b></p><p class="row"><span>کانال</span><b>'+escapeHtml(channelLabel(order))+'</b></p>'+(order.customer_name?'<p class="row"><span>مشتری</span><b>'+escapeHtml(order.customer_name)+'</b></p>':'')+(order.customer_note?'<p>یادداشت: '+escapeHtml(order.customer_note)+'</p>':'')+'<hr/><table><thead><tr><th>شرح</th><th>تعداد</th><th>مبلغ</th></tr></thead><tbody>'+itemRows+'</tbody></table>'+totals+(type==='receipt'?'<hr/><b>پرداخت‌های ثبت‌شده</b>'+paymentRows:'')+'<div class="foot">'+escapeHtml(settings?.receipt_footer||'با تشکر از انتخاب شما')+'</div><script>window.addEventListener("load",function(){window.focus();setTimeout(function(){window.print();},250);});</script></body></html>';
}

export default function DashboardApp() {
    const [page,setPage]=useState('overview');
    const [data,setData]=useState({});
    const [loading,setLoading]=useState(false);
    const [error,setError]=useState('');
    const [notice,setNotice]=useState('');
    const [reloadVersion,setReloadVersion]=useState(0);
    const [mobileNav,setMobileNav]=useState(false);
    const [modal,setModal]=useState(null);
    const [detailOrder,setDetailOrder]=useState(null);
    const [busy,setBusy]=useState(false);
    const [settings,setSettings]=useState(null);

    useEffect(()=>{
        let alive=true;
        const load=async()=>{
            setLoading(true);setError('');
            try{
                let next={};
                if(page==='overview') next.dashboard=unwrapData(await api('/dashboard'));
                if(page==='orders') next.orders=unwrapList(await api('/orders?per_page=100'));
                if(page==='cashier'){
                    const [shift,orders]=await Promise.all([api('/cash-shift'),api('/orders?per_page=100&unsettled=1')]);
                    next.cashShift=unwrapData(shift);next.orders=unwrapList(orders);
                }
                if(page==='menu'){
                    const [products,categories]=await Promise.all([api('/products?per_page=100'),api('/categories')]);
                    next.products=unwrapList(products);next.categories=unwrapList(categories);
                }
                if(page==='inventory'){
                    const [products,movements]=await Promise.all([api('/products?per_page=100'),api('/inventory/movements?per_page=100')]);
                    next.products=unwrapList(products);next.movements=unwrapList(movements);
                }
                if(page==='tables') next.tables=unwrapList(await api('/tables?per_page=100'));
                if(page==='reservations'){
                    const [reservations,tables]=await Promise.all([api('/reservations?per_page=100'),api('/tables?per_page=100')]);
                    next.reservations=unwrapList(reservations);next.tables=unwrapList(tables);
                }
                if(page==='customers') next.feedback=unwrapList(await api('/feedback?per_page=100'));
                if(page==='expenses') next.expenses=unwrapList(await api('/expenses?per_page=100'));
                if(page==='reports') next.report=unwrapData(await api('/reports/sales'));
                if(page==='settings') { next.settings=unwrapData(await api('/settings')); }
                if(alive){setData(prev=>({...prev,...next}));if(next.settings)setSettings(next.settings);}
            }catch(e){if(alive)setError(e?.message||'دریافت اطلاعات از سرور ناموفق بود.');}
            finally{if(alive)setLoading(false);}
        };
        load();
        return ()=>{alive=false;};
    },[page,reloadVersion]);

    const navigate=next=>{setPage(next);setMobileNav(false);setError('');};
    const notify=message=>{setNotice(message);window.setTimeout(()=>setNotice(''),5000);};
    const refresh=()=>setReloadVersion(v=>v+1);
    const action=async(work, success, options={})=>{
        if(busy)return;
        setBusy(true);setError('');
        try{await work();if(success)notify(success);if(options.close!==false)setModal(null);if(options.reload!==false)refresh();return true;}
        catch(e){notify(e?.message||'عملیات ناموفق بود.');return false;}
        finally{setBusy(false);}
    };
    const ensureProductsAndTables=async()=>{
        let products=data.products||[], tables=data.tables||[];
        if(!products.length){products=unwrapList(await api('/products?per_page=100'));setData(p=>({...p,products}));}
        if(!tables.length){tables=unwrapList(await api('/tables?per_page=100'));setData(p=>({...p,tables}));}
        return {products,tables};
    };
    const newOrder=async()=>{
        try{const resources=await ensureProductsAndTables();setModal({type:'order',...resources});}
        catch(e){notify(e.message||'دریافت فهرست محصولات ناموفق بود.');}
    };
    const orderDetails=async id=>{
        try{const payload=await api('/orders/'+id);setDetailOrder(unwrapData(payload));setModal({type:'details'});}
        catch(e){notify(e.message||'جزئیات سفارش بارگذاری نشد.');}
    };
    const showRefund=(payment,orderId)=>setModal({type:'refund',payment,orderId});
    const createOrder=payload=>action(async()=>{
        await api('/orders',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key});
        setPage('orders');
    },'سفارش در سرور ثبت شد؛ موجودی رهگیری‌شده هم کم شد.');
    const transition=(order,status)=>action(()=>api('/orders/'+order.id+'/status',{method:'PATCH',body:{status,reason:'تغییر وضعیت از داشبورد'}}),'وضعیت سفارش به‌روزرسانی شد.',{close:false});
    const cancelOrder=(order)=>setModal({type:'cancel',order});
    const submitCancel=(reason,id)=>action(()=>api('/orders/'+id+'/cancel',{method:'POST',body:{reason}}),'سفارش لغو و موجودی رهگیری‌شده بازگردانده شد.',{close:true});
    const openPayment=async order=>{
        try{const detail=unwrapData(await api('/orders/'+order.id));setModal({type:'payment',order:detail});}
        catch(e){notify(e.message||'جزئیات سفارش بارگیری نشد.');}
    };
    const submitPayment=(payload,id)=>action(()=>api('/orders/'+id+'/payments',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'پرداخت ثبت شد؛ فاکتور را می‌توانی چاپ کنی.');
    const submitRefund=(payload,paymentId,orderId)=>action(()=>api('/payments/'+paymentId+'/refunds',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'بازپرداخت ثبت شد. اگر روش وجه نقد باشد، حرکت خروجی صندوق هم ثبت می‌شود.',{close:true});
    const printDocument=async(id,type)=>{
        const popup=window.open('about:blank','_blank','width=460,height=800');
        if(!popup){notify('مرورگر پنجره چاپ را مسدود کرد؛ اجازه Pop-up را برای این سایت فعال کن.');return;}
        try{
            const [payload,settingPayload]=await Promise.all([api('/orders/'+id+'/'+(type==='kitchen'?'kitchen-slip':type==='receipt'?'receipt':'invoice')),settings?Promise.resolve({data:settings}):api('/settings')]);
            const order=unwrapData(payload);const config=unwrapData(settingPayload);
            const printType=type==='kitchen'?'kitchen':type;
            try{await api('/orders/'+id+'/print-log',{method:'POST',body:{type:printType,payment_id:type==='receipt'?(order.payments||[]).find(p=>p.status==='succeeded')?.id||null:null,is_reprint:false});}catch(e){notify('چاپ ادامه پیدا می‌کند، اما ثبت گزارش چاپ ناموفق بود: '+e.message);}
            popup.document.open();popup.document.write(printHtml(order,type,config));popup.document.close();popup.focus();
        }catch(e){popup.close();notify(e?.message||'دریافت اطلاعات برای چاپ ناموفق بود.');}
    };
    const createProduct=(payload,id)=>action(()=>api('/products'+(id?'/'+id:''),{method:id?'PATCH':'POST',body:payload}),'محصول ذخیره شد.');
    const changeProductStatus=(product,status)=>action(()=>api('/products/'+product.id+'/status',{method:'PATCH',body:{status}}),'وضعیت محصول تغییر کرد.',{close:false});
    const createCategory=payload=>action(()=>api('/categories',{method:'POST',body:payload}),'دسته‌بندی ایجاد شد.');
    const createTable=payload=>action(()=>api('/tables',{method:'POST',body:payload}),'میز ایجاد شد.');
    const updateTable=(table,payload)=>action(()=>api('/tables/'+table.id,{method:'PATCH',body:payload}),'میز به‌روزرسانی شد.',{close:false});
    const rotateTable=table=>action(()=>api('/tables/'+table.id+'/rotate-qr',{method:'POST',body:{}}),'QR قبلی باطل و لینک جدید تولید شد.',{close:false});
    const adjustInventory=payload=>action(()=>api('/inventory/adjustments',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'گردش موجودی ثبت شد.');
    const createReservation=payload=>action(()=>api('/reservations',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'رزرو ثبت شد.');
    const transitionReservation=(item,status)=>action(()=>api('/reservations/'+item.id+'/status',{method:'PATCH',body:{status}}),'وضعیت رزرو تغییر کرد.',{close:false});
    const respondFeedback=(item,payload)=>action(()=>api('/feedback/'+item.id+'/response',{method:'PATCH',body:payload}),'پاسخ بازخورد ذخیره شد.',{close:false});
    const createExpense=payload=>action(()=>api('/expenses',{method:'POST',body:payload}),'هزینه ثبت شد.');
    const openShift=payload=>action(()=>api('/cash-shift/open',{method:'POST',body:payload}),'شیفت صندوق باز شد.');
    const closeShift=(id,payload)=>action(()=>api('/cash-shift/'+id+'/close',{method:'POST',body:payload}),'شیفت بسته و مغایرت محاسبه شد.');
    const cashMovement=(id,payload)=>action(()=>api('/cash-shift/'+id+'/movements',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'حرکت صندوق ثبت شد.',{close:false});
    const saveSettings=payload=>action(async()=>{await api('/settings',{method:'PATCH',body:payload});const refreshed=unwrapData(await api('/settings'));setSettings(refreshed);setData(prev=>({...prev,settings:refreshed}));},'تنظیمات ذخیره شد.',{close:false});
    const runReport=async(from='',to='')=>{
        try{setLoading(true);let qs=[];if(from)qs.push('from='+encodeURIComponent(from));if(to)qs.push('to='+encodeURIComponent(to));const next=unwrapData(await api('/reports/sales'+(qs.length?'?'+qs.join('&'):'')));setData(p=>({...p,report:next}));setError('');}
        catch(e){setError(e.message||'اجرای گزارش ناموفق بود.');}
        finally{setLoading(false);}
    };

    const renderPage=()=>{
        if(page==='overview') return <OverviewPage dashboard={data.dashboard} onNavigate={navigate} onNewOrder={newOrder} loading={loading} />;
        if(page==='orders') return <OrdersPage orders={data.orders} onRefresh={refresh} onDetails={orderDetails} onPrint={printDocument} onNewOrder={newOrder} onTransition={transition} onCancel={cancelOrder} />;
        if(page==='cashier') return <CashierPage cashShift={data.cashShift} orders={data.orders} onRefresh={refresh} onOpenShift={openShift} onCloseShift={closeShift} onMovement={cashMovement} onPayment={openPayment} onDetails={orderDetails} />;
        if(page==='menu') return <MenuPage products={data.products} categories={data.categories} onRefresh={refresh} onProduct={product=>setModal({type:'product',product})} onProductStatus={changeProductStatus} onCategory={()=>setModal({type:'category'})} />;
        if(page==='inventory') return <InventoryPage products={data.products} movements={data.movements} onRefresh={refresh} onAdjust={product=>setModal({type:'inventory',product})} />;
        if(page==='tables') return <TablesPage tables={data.tables} onRefresh={refresh} onCreate={createTable} onUpdate={updateTable} onRotate={rotateTable} />;
        if(page==='reservations') return <ReservationsPage reservations={data.reservations} tables={data.tables} onRefresh={refresh} onCreate={createReservation} onTransition={transitionReservation} />;
        if(page==='customers') return <CustomersPage feedback={data.feedback} onRefresh={refresh} onRespond={respondFeedback} />;
        if(page==='expenses') return <ExpensesPage expenses={data.expenses} onRefresh={refresh} onCreate={createExpense} />;
        if(page==='reports') return <ReportsPage report={data.report} onRefresh={runReport} />;
        if(page==='settings') return <SettingsPage settings={data.settings} onRefresh={refresh} onSave={saveSettings} />;
        return <Empty />;
    };

    return <div className="dashboard-app" dir="rtl">
        <aside className={'sidebar '+(mobileNav?'sidebar-open':'')}>
            <button type="button" className="brand-lockup" onClick={()=>navigate('overview')}><span className="brand-symbol">خ</span><span><strong>خانه گیلاسی</strong><small>CAFE / RESTAURANT</small></span><span className="brand-status" title="اتصال به سرور" /></button>
            <div className="sidebar-caption">فضای کاری</div><div className="workspace"><span className="workspace-avatar">KG</span><span><b>شعبه مرکزی</b><small>پنل عملیات و صندوق</small></span></div>
            <div className="sidebar-caption">مدیریت روزانه</div>
            <nav className="side-nav" aria-label="منوی داشبورد">{navItems.map(([key,label,icon])=><button type="button" key={key} className={(page===key?'active ':'')+'nav-item'} onClick={()=>navigate(key)}><Icon>{icon}</Icon><span>{label}</span>{key==='orders'&&data.orders&&<b className="nav-count">{numberFa(data.orders.filter(o=>!['delivered','cancelled'].includes(o.status)).length)}</b>}</button>)}</nav>
            <div className="sidebar-bottom"><div className="help-card"><span>✦</span><b>وضعیت سامانه</b><p>هر صفحه با API محافظت‌شده‌ی سرور کار می‌کند. خطاها در رابط نشان داده می‌شوند.</p><button type="button" onClick={refresh}>تازه‌سازی داده‌ها ←</button></div><form method="POST" action="/admin/logout" className="op-logout"><input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content || ''} /><button className="user-card" type="submit"><span className="user-avatar">م</span><span><b>خروج از پنل</b><small>پایان نشست امن</small></span><span className="user-menu">↪</span></button></form></div>
        </aside>
        {mobileNav&&<button type="button" aria-label="بستن منو" className="mobile-overlay" onClick={()=>setMobileNav(false)}/>}
        <main className="main-shell">
            <div className="topbar"><button type="button" className="mobile-menu-button" onClick={()=>setMobileNav(true)} aria-label="بازکردن منو">☰</button><div className="breadcrumb">خانه گیلاسی <span>/</span> {navItems.find(n=>n[0]===page)?.[1]||'مدیریت'}</div><div className="topbar-right"><span className={'connection-state '+(error?'op-connection-error':'')}><i/>{error?'خطا در ارتباط':'اتصال به سامانه'}</span><span className="topbar-date">{new Intl.DateTimeFormat('fa-IR',{weekday:'long',day:'numeric',month:'long'}).format(new Date())}</span><button type="button" className="icon-button" aria-label="تازه‌سازی صفحه" onClick={refresh}>↻</button><span className="topbar-avatar">KG</span></div></div>
            {error&&<div className="op-global-error" role="alert"><span>{error}</span><Button onClick={refresh}>تلاش دوباره</Button></div>}
            {loading&&<div className="op-loading op-loading-line"><span className="op-spinner"/>در حال دریافت اطلاعات از سرور…</div>}
            {renderPage()}
            <footer className="app-footer"><span>خانه گیلاسی <span className="footer-dot">·</span> پنل عملیات رستوران</span><span>نسخه متصل به API · مالی و موجودی با تاریخچه رویداد</span></footer>
        </main>
        {modal?.type==='order'&&<OrderModal products={modal.products} tables={modal.tables} onClose={()=>setModal(null)} onSubmit={createOrder}/>}
        {modal?.type==='product'&&<ProductModal key={modal.product?.id||'new'} product={modal.product} categories={data.categories||[]} onClose={()=>setModal(null)} onSave={createProduct}/>}
        {modal?.type==='category'&&<CategoryModal onClose={()=>setModal(null)} onSave={createCategory}/>}
        {modal?.type==='payment'&&<PaymentModal order={modal.order} onClose={()=>setModal(null)} onSubmit={submitPayment}/>}
        {modal?.type==='cancel'&&<CancelModal order={modal.order} onClose={()=>setModal(null)} onSubmit={submitCancel}/>}
        {modal?.type==='refund'&&<RefundModal payment={modal.payment} orderId={modal.orderId} onClose={()=>setModal(null)} onSubmit={submitRefund}/>}
        {modal?.type==='details'&&<OrderDetailsModal order={detailOrder} onClose={()=>setModal(null)} onPrint={printDocument} onRefund={showRefund}/>}
        {modal?.type==='inventory'&&<InventoryModal product={modal.product} products={data.products||[]} onClose={()=>setModal(null)} onSubmit={adjustInventory}/>}
        {notice&&<div className="toast-message" role="status"><span>✓</span>{notice}<button type="button" aria-label="بستن پیام" onClick={()=>setNotice('')}>×</button></div>}
        {busy&&<div className="op-busy" aria-live="polite"><span className="op-spinner"/>در حال ثبت عملیات…</div>}
    </div>;
}

function InventoryModal({ product, products, onClose, onSubmit }) {
    const tracked=(products||[]).filter(p=>p.stock_quantity!==null);
    const [productId,setProductId]=useState(String(product?.id || tracked[0]?.id || ''));
    const [direction,setDirection]=useState('in');
    const [quantity,setQuantity]=useState('1');
    const [reason,setReason]=useState('');
    const [requestKey] = useState(() => idempotencyKey());
    const selected=tracked.find(p=>String(p.id)===productId);
    return <Modal title="اصلاح موجودی" eyebrow="INVENTORY MOVEMENT" onClose={onClose}>
        {tracked.length===0?<Empty>ابتدا برای حداقل یک محصول مقدار موجودی اولیه تعریف کن.</Empty>:<form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit({product_id:Number(productId),direction,quantity:Number(quantity),reason:reason.trim(),idempotency_key:requestKey});}}>
            <Field label="محصول"><select value={productId} onChange={e=>setProductId(e.target.value)}>{tracked.map(p=><option key={p.id} value={p.id}>{p.name} · موجودی {numberFa(p.stock_quantity)}</option>)}</select></Field>
            <Field label="جهت حرکت"><select value={direction} onChange={e=>setDirection(e.target.value)}><option value="in">ورود کالا / افزایش</option><option value="out">خروج / ضایعات / مصرف</option></select></Field>
            <Field label="تعداد"><input type="number" min="1" value={quantity} onChange={e=>setQuantity(e.target.value)} required /></Field>
            {selected&&<div className="op-current-stock">موجودی فعلی: <b>{numberFa(selected.stock_quantity)}</b></div>}
            <Field label="علت (اجباری)"><input value={reason} onChange={e=>setReason(e.target.value)} minLength="3" maxLength="500" required placeholder="مثلاً رسید خرید شماره ۱۲ یا ضایعات" /></Field>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ثبت گردش انبار</Button></div>
        </form>}
    </Modal>;
}
