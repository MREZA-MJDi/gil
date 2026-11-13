import React, { useEffect, useMemo, useState } from 'react';
import MediaManager from './MediaManager.jsx';
import BrandLogoSettings from './BrandLogoSettings.jsx';
import PosPage from './PosPage.jsx';
import { MoneyInput } from '../shared/iranian-ui.jsx';
import { api, idempotencyKey, unwrapData, unwrapList, unwrapPagination } from './api.js';
import QRCode from 'qrcode';
import { buildPrintHtml } from './receipt.js';
import { currentJalaliDate, gregorianDateToJalali, jalaliDateToGregorian, jalaliMonthInfo, shiftJalaliMonth, toPersianDigits, JALALI_WEEKDAYS } from '../date-utils.js';

const navItems = [
    ['overview', 'نمای کلی', '◫'],
    ['pos', 'صندوق فروش و شیفت', '◈'],
    ['orders', 'سفارش‌ها و تحویل', '▤'],
    ['menu', 'منو و محصولات', '▦'],
    ['inventory', 'موجودی و انبار', '▧'],
    ['recipes', 'رسپی و مواد اولیه', '♨'],
    ['tables', 'میزها و QR', '▤'],
    ['reservations', 'رزروها', '◷'],
    ['customers', 'بازخورد مشتریان', '♧'],
    ['expenses', 'هزینه‌ها', '↗'],
    ['reports', 'گزارش‌ها', '▥'],
    ['settings', 'تنظیمات', '⚙'],
    ['media', 'رسانه و تصاویر سایت', '▧'],
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
const feedbackStatusLabel = { new: 'جدید', in_progress: 'در حال بررسی', resolved: 'رسیدگی‌شده', closed: 'بسته‌شده' };
const reservationPurposeLabel = { table: 'رزرو میز', birthday: 'تولد', consultation: 'مشاوره', private_event: 'مراسم خصوصی', catering: 'کترینگ' };
const paymentMethodLabel = { cash: 'نقدی', card_terminal: 'کارت‌خوان (ثبت کاربر)', online: 'آنلاین', other: 'سایر' };
const paymentPreferenceLabel = { cash: 'نقدی', card_terminal: 'کارت‌خوان', at_counter: 'در صندوق / هنگام تحویل' };
const orderSourceLabel = { staff: 'ثبت پرسنل', qr: 'QR میز', website: 'سفارش سایت', snappfood: 'اسنپ‌فود', partner: 'سرویس بیرونی' };
const expenseCategoryLabel = { rent: 'اجاره', payroll: 'حقوق', utilities: 'قبوض', ingredients: 'مواد اولیه', repairs: 'تعمیرات', packaging: 'بسته‌بندی', transport: 'حمل‌ونقل', other: 'سایر' };
const currencyLabel = () => window.__restaurantCurrencyLabel || document.querySelector('meta[name="currency-label"]')?.content || 'تومان';
const money = value => new Intl.NumberFormat('fa-IR-u-nu-arabext', { maximumFractionDigits: 0 }).format(Number(value || 0)) + ' ' + currencyLabel();
const numberFa = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const dateTime = value => value ? new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-arabext', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Tehran' }).format(new Date(value)) : '—';
const localDateTime = value => value ? String(value).replace('T', ' ') + ':00' : '';
const tehranDateTimeInput = value => {
    if (!value) return '';
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Tehran',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(new Date(value)).reduce((result, part) => ({
        ...result, [part.type]: part.value,
    }), {});
    return parts.year && parts.month && parts.day
        ? parts.year + '-' + parts.month + '-' + parts.day + 'T' + parts.hour + ':' + parts.minute
        : '';
};
const channelLabel = order => {
    if (order?.table?.name) return order.table.name;
    if (order?.source === 'snappfood') return 'اسنپ‌فود';
    if (order?.source === 'partner') return 'سرویس بیرونی';
    if (order?.source === 'website') return 'سفارش سایت · بیرون‌بر';
    if (order?.source === 'qr') return 'سفارش QR';
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
        ['ready', 'partial', 'partially_refunded', 'preparing', 'reviewing', 'in_progress'].includes(value) ? 'warning' : 'neutral'
    );
    return <span className={'status-pill ' + resolvedTone}><i />{label}</span>;
}
function Button({ children, kind = 'secondary', type = 'button', ...props }) {
    return <button type={type} className={kind === 'primary' ? 'primary-button' : kind === 'danger' ? 'op-danger-button' : 'secondary-button'} {...props}>{children}</button>;
}
function PageHeader({ title, subtitle, action, onAction }) {
    return <header className="page-header">
        <div><div className="eyebrow">خانه گیلاسی / عملیات</div><h1>{title}</h1><p>{subtitle}</p></div>
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
function Pagination({ meta, onPageChange }) {
    if (!meta || Number(meta.lastPage || 1) <= 1) {
        return meta?.total > 0 ? <div className="op-pagination"><small>نمایش {numberFa(meta.from || 0)} تا {numberFa(meta.to || 0)} از {numberFa(meta.total)} رکورد</small></div> : null;
    }
    const current = Number(meta.currentPage || 1);
    const last = Number(meta.lastPage || 1);
    return <nav className="op-pagination" aria-label="صفحه‌بندی">
        <small>نمایش {numberFa(meta.from || 0)} تا {numberFa(meta.to || 0)} از {numberFa(meta.total)} رکورد</small>
        <div><Button disabled={current <= 1} onClick={() => onPageChange(current - 1)}>قبلی</Button><b>صفحه {numberFa(current)} از {numberFa(last)}</b><Button disabled={current >= last} onClick={() => onPageChange(current + 1)}>بعدی</Button></div>
    </nav>;
}

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

function OverviewSkeleton() {
    return <div className="page-content overview-page" aria-label="بارگذاری داشبورد">
        <section className="overview-hero overview-skeleton-hero">
            <div className="overview-skeleton-line short" />
            <div className="overview-skeleton-line title" />
            <div className="overview-skeleton-line wide" />
            <div className="overview-skeleton-actions"><i /><i /></div>
        </section>
        <div className="overview-section-heading"><div><div className="overview-skeleton-line short" /><div className="overview-skeleton-line medium" /></div></div>
        <section className="overview-kpi-grid">
            {[0,1,2,3].map(key=><div className="overview-kpi-card overview-skeleton-card" key={key}><i /><div><span /><b /><small /></div></div>)}
        </section>
        <div className="overview-section-heading"><div><div className="overview-skeleton-line short" /><div className="overview-skeleton-line medium" /></div></div>
        <section className="overview-alert-grid">
            {[0,1,2].map(key=><div className="overview-alert-card overview-skeleton-card" key={key}><i /><div><span /><b /><small /></div></div>)}
        </section>
        <section className="overview-main-grid">
            <div className="panel overview-skeleton-panel"><div className="overview-skeleton-line medium" /><div className="overview-skeleton-line wide" /><div className="overview-skeleton-line wide" /><div className="overview-skeleton-line wide" /></div>
            <div className="panel overview-skeleton-panel"><div className="overview-skeleton-line medium" /><div className="overview-skeleton-line wide" /><div className="overview-skeleton-line wide" /><div className="overview-skeleton-line wide" /></div>
        </section>
    </div>;
}

function OverviewPage({ dashboard, onNavigate, onNewOrder, onOrderOpen }) {
    const recent = dashboard?.recent_orders || [];
    const lowStock = Number(dashboard?.low_stock_count || 0);
    const reservationsCount = Number(dashboard?.today_reservations_count || 0);
    const readyOrders = Number(dashboard?.orders_by_status?.ready || 0);
    const openInvoices = Number(dashboard?.open_invoice_amount || 0);
    const cashShiftOpen = Boolean(dashboard?.active_cash_shift);

    const cards = [
        { label: 'فروش ثبت‌شده', value: money(dashboard?.registered_sales), hint: 'مجموع سفارش‌های غیرلغوشده', icon: '↗', tone: 'coral', destination: 'reports', action: 'مشاهده گزارش فروش' },
        { label: 'دریافتی خالص', value: money(dashboard?.collections_net), hint: 'پس از کسر بازپرداخت‌ها', icon: '◈', tone: 'green', destination: 'pos', action: 'رفتن به صندوق' },
        { label: 'سفارش‌های امروز', value: numberFa(dashboard?.orders_count), hint: 'همه وضعیت‌ها، شامل لغوشده', icon: '▤', tone: 'purple', destination: 'orders', action: 'پیگیری سفارش‌ها' },
        { label: 'مانده صورت‌حساب', value: money(dashboard?.open_invoice_amount), hint: 'سفارش‌های تسویه‌نشده', icon: '◷', tone: 'amber', destination: 'orders', action: 'بررسی مانده‌ها' },
    ];

    const alerts = [
        !cashShiftOpen ? { tone: 'warning', icon: '◷', label: 'نیاز به اقدام', title: 'شیفت صندوق بسته است', detail: 'برای ثبت دریافت و پرداخت نقدی، شیفت صندوق را باز کن.', action: 'مدیریت صندوق', page: 'pos', rank: 1 } : null,
        lowStock > 0 ? { tone: 'danger', icon: '!', label: 'موجودی', title: lowStock + ' محصول کم‌موجودی', detail: 'محصول‌های رسیده به آستانه هشدار را بررسی و رسید خرید را ثبت کن.', action: 'بررسی انبار', page: 'inventory', rank: 2 } : null,
        openInvoices > 0 ? { tone: 'warning', icon: '↗', label: 'پیگیری مالی', title: 'صورت‌حساب باز داری', detail: 'مانده‌های پرداخت‌نشده را بررسی کن؛ فروش با دریافتی یکی نیست.', action: 'مشاهده سفارش‌ها', page: 'orders', rank: 3 } : null,
        reservationsCount > 0 ? { tone: 'info', icon: '◷', label: 'مهمان‌ها', title: reservationsCount + ' رزرو برای امروز', detail: 'زمان رزروها و تخصیص میزها را پیش از مراجعه بررسی کن.', action: 'مدیریت رزروها', page: 'reservations', rank: 4 } : null,
        readyOrders > 0 ? { tone: 'success', icon: '✓', label: 'آماده تحویل', title: readyOrders + ' سفارش آماده تحویل', detail: 'سفارش‌های آماده را بررسی کن تا تحویل معطل نماند.', action: 'رفتن به سفارش‌ها', page: 'orders', rank: 5 } : null,
    ].filter(Boolean).sort((a,b)=>a.rank-b.rank).slice(0,4);

    const quickLinks = [
        { title: 'ثبت سفارش جدید', description: 'ثبت آیتم‌ها، تخفیف و میز', icon: '＋', tone: 'coral', run: onNewOrder },
        { title: 'صندوق و پرداخت', description: 'دریافت وجه، فیش و بازپرداخت', icon: '◈', tone: 'green', run: ()=>onNavigate('pos') },
        { title: 'منو و قیمت‌ها', description: 'مدیریت دسته‌ها و محصولات', icon: '▦', tone: 'amber', run: ()=>onNavigate('menu') },
        { title: 'انبار و موجودی', description: 'گردش موجودی و اصلاح شمارش', icon: '▧', tone: 'purple', run: ()=>onNavigate('inventory') },
    ];

    return <div className="page-content overview-page">
        <header className="overview-hero">
            <div className="overview-hero-copy">
                <div className="overview-eyebrow"><span className="overview-live-dot" /> خانه گیلاسی <span>/</span> مرکز عملیات</div>
                <h1>سلام، آماده‌ی یک روز منظم؟</h1>
                <p>تصویر روشن صندوق، سفارش‌ها و مواردی که امروز نیاز به توجه دارند.</p>
                <div className="overview-hero-meta">
                    <span><i>◷</i>{new Intl.DateTimeFormat('fa-IR-u-ca-persian',{weekday:'long',day:'numeric',month:'long',timeZone:'Asia/Tehran'}).format(new Date())}</span>
                    <span><i>✓</i> شاخص‌ها بر اساس اطلاعات ثبت‌شده در سامانه</span>
                </div>
            </div>
            <div className="overview-hero-actions">
                <Button kind="primary" onClick={onNewOrder}>＋ ثبت سفارش</Button>
                <Button onClick={()=>onNavigate('orders')}>مشاهده سفارش‌ها <span aria-hidden="true">←</span></Button>
            </div>
            <div className="overview-hero-orb" aria-hidden="true" />
        </header>

        <div className="overview-section-heading">
            <div><span className="overview-section-kicker">01 / عملکرد</span><h2>شاخص‌های کلیدی امروز</h2><p>هر کارت مسیر مستقیم به صفحه‌ی مرتبط دارد.</p></div>
            <span className="overview-data-note"><i /> متصل به دیتابیس</span>
        </div>
        <section className="overview-kpi-grid" aria-label="شاخص‌های کلیدی امروز">
            {cards.map((card,index)=><button type="button" className={'overview-kpi-card tone-'+card.tone} key={card.label} onClick={()=>onNavigate(card.destination)} aria-label={card.action}>
                <span className="overview-kpi-top"><span className="overview-kpi-icon">{card.icon}</span><span className="overview-kpi-index">{String(index+1).padStart(2,'0')}</span></span>
                <span className="overview-kpi-label">{card.label}</span>
                <strong className="overview-kpi-value">{card.value ?? '—'}</strong>
                <span className="overview-kpi-bottom"><small>{card.hint}</small><span className="overview-kpi-arrow" aria-hidden="true">↗</span></span>
            </button>)}
        </section>

        <div className="overview-section-heading overview-alert-heading">
            <div><span className="overview-section-kicker">02 / اولویت‌های روز</span><h2>نیاز به پیگیری</h2><p>هشدارها فقط وقتی نمایش داده می‌شوند که اقدامی واقعاً لازم باشد.</p></div>
            <span className={'overview-alert-count '+(alerts.length ? 'has-alerts' : '')}>{numberFa(alerts.length)} مورد</span>
        </div>
        <section className="overview-alert-grid" aria-label="هشدارها و کارهای نیازمند پیگیری">
            {alerts.length ? alerts.map((item,index)=><button type="button" className={'overview-alert-card alert-'+item.tone} key={item.title} onClick={()=>onNavigate(item.page)}>
                <span className="overview-alert-icon">{item.icon}</span>
                <span className="overview-alert-content"><small>{item.label}</small><strong>{item.title}</strong><span>{item.detail}</span><b>{item.action} <i aria-hidden="true">←</i></b></span>
                <span className="overview-alert-index">{numberFa(index+1)}</span>
            </button>) : <button type="button" className="overview-clear-card" onClick={()=>onNavigate('reports')}>
                <span className="overview-clear-icon">✓</span><span><strong>فعلاً مورد فوری ثبت نشده</strong><small>وضعیت‌ها پایش می‌شوند؛ گزارش‌های روز را هم می‌توانی بررسی کنی.</small></span><b>مشاهده گزارش <i aria-hidden="true">←</i></b>
            </button>}
        </section>

        <section className="overview-main-grid">
            <article className="panel overview-orders-panel">
                <div className="overview-panel-heading"><div><span className="overview-section-kicker">تازه‌ترین فعالیت</span><h2>آخرین سفارش‌ها</h2><p>برای دیدن جزئیات هر سفارش، روی ردیف آن بزن.</p></div><Button onClick={()=>onNavigate('orders')}>همه سفارش‌ها <span aria-hidden="true">←</span></Button></div>
                {recent.length ? <TableWrap><thead><tr><th>شماره</th><th>کانال / میز</th><th>مبلغ</th><th>آماده‌سازی</th><th>پرداخت</th><th>زمان ثبت</th><th><span className="sr-only">بازکردن</span></th></tr></thead><tbody>
                    {recent.slice(0,6).map(order=><tr key={order.id} className="overview-order-row" role="button" tabIndex={0} aria-label={'جزئیات سفارش '+(order.number || order.id)} onClick={()=>onOrderOpen(order.id)} onKeyDown={e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();onOrderOpen(order.id);}}}>
                        <td className="mono">{order.number || ('#'+order.id)}</td><td>{channelLabel(order)}</td><td className="amount-cell">{money(order.total)}</td><td><Pill value={order.status} dictionary={orderStatusLabel} /></td><td><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></td><td>{dateTime(order.created_at)}</td><td><span className="overview-row-arrow" aria-hidden="true">↙</span></td>
                    </tr>)}
                </tbody></TableWrap> : <button type="button" className="overview-empty-orders" onClick={onNewOrder}><span>＋</span><strong>هنوز سفارشی ثبت نشده</strong><small>اولین سفارش را ثبت کن تا فعالیت امروز اینجا دیده شود.</small><b>ثبت سفارش <i aria-hidden="true">←</i></b></button>}
            </article>

            <aside className="panel overview-side-panel">
                <div className="overview-panel-heading"><div><span className="overview-section-kicker">مرکز دسترسی</span><h2>کارهای پرتکرار</h2><p>با یک انتخاب به بخش موردنظر برو.</p></div></div>
                <div className="overview-quick-links">
                    {quickLinks.map(link=><button type="button" className="overview-quick-link" key={link.title} onClick={link.run}>
                        <span className={'overview-quick-icon tone-'+link.tone}>{link.icon}</span><span className="overview-quick-copy"><strong>{link.title}</strong><small>{link.description}</small></span><span className="overview-quick-arrow" aria-hidden="true">↙</span>
                    </button>)}
                </div>
                <button type="button" className="overview-cash-status" onClick={()=>onNavigate('pos')}>
                    <span className={'overview-cash-status-icon '+(cashShiftOpen?'is-open':'is-closed')}>{cashShiftOpen?'✓':'!'}</span>
                    <span><strong>{cashShiftOpen?'شیفت صندوق باز است':'شیفت صندوق بسته است'}</strong><small>{cashShiftOpen?('شروع: '+dateTime(dashboard?.active_cash_shift?.opened_at)):'برای عملیات نقدی، شیفت را باز کن.'}</small></span>
                    <span aria-hidden="true">←</span>
                </button>
            </aside>
        </section>
    </div>;
}

function OrdersPage({ orders, filters, onFiltersChange, pagination, onPageChange, onRefresh, onDetails, onPrint, onNewOrder, onTransition, onCancel }) {
    const [queryDraft, setQueryDraft] = useState(filters?.query || '');
    const status = filters?.status || 'all';
    useEffect(() => {
        if (queryDraft === (filters?.query || '')) return undefined;
        const timer = window.setTimeout(() => onFiltersChange({ query: queryDraft }), 300);
        return () => window.clearTimeout(timer);
    }, [queryDraft, filters?.query, onFiltersChange]);
    const items = orders || [];
    return <div className="page-content">
        <PageHeader title="سفارش‌ها و تحویل" subtitle="سفارش‌ها را جست‌وجو کن، وضعیت آماده‌سازی را تغییر بده و از همین‌جا به صندوق یا چاپ فیش برو." action="＋ سفارش جدید" onAction={onNewOrder} />
        <div className="toolbar"><div className="filter-tabs">{[['all','همه'],['open','در جریان'],['unsettled','تسویه‌نشده'],['ready','آماده تحویل']].map(([key,label]) => <button type="button" key={key} className={status === key ? 'active' : ''} onClick={() => onFiltersChange({ status: key })}>{label}</button>)}</div><input value={queryDraft} onChange={e => setQueryDraft(e.target.value)} placeholder="جست‌وجوی شماره، تلفن یا میز…" aria-label="جست‌وجوی سفارش" /><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
        <section className="panel">
            {items.length ? <TableWrap><thead><tr><th>شماره سفارش</th><th>کانال / میز</th><th>مبلغ</th><th>آماده‌سازی</th><th>تسویه</th><th>ثبت‌شده</th><th>عملیات</th></tr></thead><tbody>
                {items.map(order => <tr key={order.id}>
                    <td><button type="button" className="op-text-button mono" onClick={() => onDetails(order.id)}>{order.number || ('#' + order.id)}</button></td>
                    <td>{channelLabel(order)}<small className="op-subtext">{order.customer_name || order.customer_phone || 'بدون مشخصات مشتری'}</small><small className="op-subtext">{orderSourceLabel[order.source] || 'ثبت پرسنل'} · پرداخت ترجیحی: {paymentPreferenceLabel[order.payment_preference] || 'در صندوق / هنگام تحویل'}</small></td>
                    <td className="amount-cell">{money(order.total)}</td><td><Pill value={order.status} dictionary={orderStatusLabel} /></td><td><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></td><td>{dateTime(order.created_at)}</td>
                    <td><div className="op-actions">
                        <Button onClick={() => onDetails(order.id)}>جزئیات</Button>
                        {nextStatus(order.status) && <Button kind="primary" onClick={() => onTransition(order, nextStatus(order.status))}>{nextStatusLabel(order.status)}</Button>}
                        {!['delivered','cancelled'].includes(order.status) && !['paid','partial','partially_refunded'].includes(order.payment_status) && <Button kind="danger" onClick={() => onCancel(order)}>لغو</Button>}
                        <Button onClick={() => onPrint(order.id, 'receipt')}>فیش مشتری</Button>
                        <Button onClick={() => onPrint(order.id, 'kitchen')}>فیش آشپزخانه</Button>
                    </div></td>
                </tr>)}
            </tbody></TableWrap> : <Empty>{queryDraft || status !== 'all' ? 'سفارشی با این جست‌وجو یا فیلتر پیدا نشد.' : 'هنوز سفارشی ثبت نشده است.'}</Empty>}
            <Pagination meta={pagination} onPageChange={onPageChange} />
        </section>
    </div>;
}
function nextStatus(status) {
    return ({ new: 'confirmed', confirmed: 'preparing', preparing: 'ready', ready: 'delivered' })[status] || null;
}
function nextStatusLabel(status) {
    return ({ new: 'تأیید', confirmed: 'شروع آماده‌سازی', preparing: 'آماده تحویل', ready: 'ثبت تحویل' })[status] || 'تغییر وضعیت';
}

function MenuPage({ products, categories, filters, onFiltersChange, pagination, onPageChange, onRefresh, onProduct, onProductStatus, onProductDelete, onCategory, onCategoryDelete }) {
    const [queryDraft, setQueryDraft] = useState(filters?.query || '');
    const category = filters?.category || 'all';
    useEffect(() => {
        if (queryDraft === (filters?.query || '')) return undefined;
        const timer = window.setTimeout(() => onFiltersChange({ query: queryDraft }), 300);
        return () => window.clearTimeout(timer);
    }, [queryDraft, filters?.query, onFiltersChange]);
    const items = products || [];
    return <div className="page-content">
        <PageHeader title="منو و محصولات" subtitle="از این بخش نام، قیمت، تصویر، دسته‌بندی، افزودنی‌ها و وضعیت فروش را مدیریت کن. محصولات غیرفعال یا ناموجود در سفارش جدید قابل انتخاب نیستند." action="＋ محصول جدید" onAction={() => onProduct(null)} />
        <div className="op-toolbar"><input value={queryDraft} onChange={e => setQueryDraft(e.target.value)} placeholder="جست‌وجوی نام محصول…" aria-label="جست‌وجوی محصول" /><select value={category} onChange={e => onFiltersChange({ category: e.target.value })}><option value="all">همه دسته‌ها</option>{(categories || []).map(c => <option key={c.id} value={String(c.id)}>{c.name}</option>)}</select><Button onClick={() => onCategory(null)}>＋ دسته‌بندی</Button><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
        {(categories || []).length > 0 && <section className="panel op-card"><div className="panel-heading"><div><h2>مدیریت دسته‌بندی‌ها</h2><p>نام و ترتیب دسته‌ها را تغییر بده؛ دسته‌ای که محصول دارد تا زمان انتقال محصولات حذف نمی‌شود.</p></div></div><div className="op-actions">{categories.map(c => <span className="op-category-manager-item" key={c.id}><b>{c.name}</b><Button onClick={() => onCategory(c)}>ویرایش</Button><Button kind="danger" onClick={() => onCategoryDelete(c)}>حذف</Button></span>)}</div></section>}
        {items.length ? <div className="op-product-grid">{items.map(product => <article className="op-product-card" key={product.id}>
            {product.image_url ? <img src={product.image_url} alt={product.name || ''} loading="lazy" /> : <div className="op-product-placeholder">☕</div>}
            <div className="op-product-body"><div className="op-product-top"><span>{product.category?.name || 'بدون دسته'}</span><Pill value={product.status} dictionary={productStatusLabel} /></div>
                <h3>{product.name}</h3><b className="op-product-price">{money(product.base_price)}</b>
                <div className="op-product-meta"><span>شناسه: {numberFa(product.id)}</span><span>{product.stock_quantity === null ? 'بدون رهگیری موجودی' : ('موجودی: ' + numberFa(product.stock_quantity))}</span></div>
                <div className="op-actions"><Button onClick={() => onProduct(product)}>ویرایش</Button><Button onClick={() => onProductStatus(product, product.status === 'active' ? 'unavailable' : 'active')}>{product.status === 'active' ? 'ناموجود کردن' : 'فعال‌سازی'}</Button><Button kind="danger" onClick={() => onProductDelete(product)}>حذف</Button></div>
            </div>
        </article>)}</div> : <Empty>محصولی با این جست‌وجو پیدا نشد. فیلتر را تغییر بده یا محصول جدید بساز.</Empty>}
        <Pagination meta={pagination} onPageChange={onPageChange} />
    </div>;
}
function InventoryPage({ products, movements, pagination, onPageChange, onRefresh, onAdjust }) {
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
            <Pagination meta={pagination} onPageChange={onPageChange} />
        </section>
    </div>;
}

function TablesPage({ tables, pagination, onPageChange, onCreate, onUpdate, onEdit, onDelete, onRotate, onShowQr, onRefresh }) {
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
            <div className="op-actions"><Button onClick={() => onEdit(table)}>ویرایش اطلاعات</Button><Button kind="primary" onClick={() => onShowQr(table)}>نمایش / چاپ QR</Button>
                <Button onClick={() => { const url = new URL(table.menu_url, window.location.origin).href; if (navigator.clipboard?.writeText) navigator.clipboard.writeText(url).then(() => alert('لینک منوی این میز کپی شد.')).catch(() => window.prompt('لینک منو را کپی کن:', url)); else window.prompt('لینک منو را کپی کن:', url); }}>کپی لینک منو</Button>
                <Button onClick={() => onRotate(table)}>تعویض QR</Button>
                <Button kind={table.is_active ? 'danger' : 'primary'} onClick={() => onUpdate(table, { is_active: !table.is_active })}>{table.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی'}</Button>{table.is_active && <Button kind="danger" onClick={() => onDelete(table)}>حذف / بایگانی</Button>}
            </div>
        </article>)}</div> : <Empty>هنوز میزی ایجاد نشده است.</Empty>}
        <Pagination meta={pagination} onPageChange={onPageChange} />
        <p className="op-note">برای هر میز، تصویر QR از لینک اختصاصی آن ساخته می‌شود. تلفن مشتری باید به نشانی‌ای دسترسی داشته باشد که در APP_URL تنظیم کرده‌ای؛ آدرس localhost فقط روی خود سرور/کامپیوتر صندوق باز می‌شود.</p>
    </div>;
}

function ReservationsPage({ reservations, tables, pagination, onPageChange, onCreate, onTransition, onRefresh, onEdit, onDelete }) {
    const emptyForm = { restaurant_table_id: '', customer_name: '', phone: '', party_size: '2', starts_at: '', duration_minutes: '90', notes: '' };
    const [form, setForm] = useState(emptyForm);
    const [requestKey, setRequestKey] = useState(() => idempotencyKey());
    const set = (key, value) => setForm(v => ({...v, [key]: value}));
    return <div className="page-content">
        <PageHeader title="رزرو میز" subtitle="اطلاعات رزرو قابل ویرایش است؛ تغییر وضعیت و حذف رزروهای نمونه با قواعد جداگانه کنترل می‌شود." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>ثبت رزرو جدید</h2><p>اگر میز مشخص شود، برخورد زمانی روی سرور کنترل می‌شود.</p></div></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onCreate({ ...form, restaurant_table_id: form.restaurant_table_id ? Number(form.restaurant_table_id) : null, party_size: Number(form.party_size), duration_minutes: Number(form.duration_minutes), starts_at: localDateTime(form.starts_at), idempotency_key: requestKey }).then(ok => { if (ok) { setForm(emptyForm); setRequestKey(idempotencyKey()); } }); }}>
                <Field label="نام مشتری"><input value={form.customer_name} onChange={e => set('customer_name', e.target.value)} maxLength="150" required /></Field>
                <Field label="تلفن"><input value={form.phone} onChange={e => set('phone', e.target.value)} maxLength="30" required /></Field>
                <Field label="تعداد نفرات"><input type="number" min="1" max="50" value={form.party_size} onChange={e => set('party_size', e.target.value)} required /></Field>
                <Field label="میز (اختیاری)"><select value={form.restaurant_table_id} onChange={e => set('restaurant_table_id', e.target.value)}><option value="">بدون تخصیص میز</option>{(tables || []).filter(t => t.is_active).map(t => <option key={t.id} value={t.id}>{t.name}</option>)}</select></Field>
                <Field label="تاریخ و ساعت"><JalaliDateTimeInput value={form.starts_at} onChange={value => set('starts_at', value)} required /></Field>
                <Field label="مدت (دقیقه)"><select value={form.duration_minutes} onChange={e => set('duration_minutes', e.target.value)}><option value="60">۶۰</option><option value="90">۹۰</option><option value="120">۱۲۰</option><option value="180">۱۸۰</option></select></Field>
                <Field label="یادداشت"><input value={form.notes} onChange={e => set('notes', e.target.value)} maxLength="1000" /></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ثبت رزرو</Button></div>
            </form>
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>رزروها</h2><p>رزروهای نمونه با برچسب مشخص، از همین جدول قابل ویرایش و حذف هستند.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {reservations?.length ? <TableWrap><thead><tr><th>شناسه</th><th>نوع درخواست</th><th>مشتری</th><th>میز</th><th>زمان</th><th>نفرات</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>{reservations.map(r => <tr key={r.id}>
                <td className="mono">{r.reference}<small className="op-subtext">{r.source==='website'?'ثبت از سایت':'ثبت توسط کارکنان'}{String(r.reference||'').startsWith('DEMO-RSV-')?' · نمونه':''}</small></td>
                <td><b>{reservationPurposeLabel[r.purpose] || 'رزرو میز'}</b>{r.occasion_name&&<small className="op-subtext">{r.occasion_name}</small>}</td>
                <td>{r.customer_name}<small className="op-subtext">{r.phone}</small></td><td>{r.table?.name || 'تخصیص‌نیافته'}</td><td>{dateTime(r.starts_at)}</td><td>{numberFa(r.party_size)}</td>
                <td><Pill value={r.status} dictionary={reservationStatusLabel} /></td>
                <td><div className="op-actions"><Button onClick={() => onEdit(r)}>ویرایش</Button>{reservationNext(r.status).map(s => <Button key={s} kind={s === 'cancelled' ? 'danger' : 'secondary'} onClick={() => onTransition(r, s)}>{reservationStatusLabel[s]}</Button>)}{String(r.reference||'').startsWith('DEMO-RSV-')&&<Button kind="danger" onClick={()=>onDelete(r)}>حذف نمونه</Button>}</div></td>
            </tr>)}</tbody></TableWrap> : <Empty>رزروی ثبت نشده است.</Empty>}
            <Pagination meta={pagination} onPageChange={onPageChange} />
        </section>
    </div>;
}
function reservationNext(status) {
    return ({ pending: ['confirmed','cancelled'], confirmed: ['arrived','no_show','cancelled'], arrived: ['cancelled'], cancelled: [], no_show: [] })[status] || [];
}

function ReservationEditModal({ reservation, tables, onClose, onSubmit }) {
    const [form, setForm] = useState(() => ({
        restaurant_table_id: reservation.restaurant_table_id ? String(reservation.restaurant_table_id) : '',
        customer_name: reservation.customer_name || '',
        phone: reservation.phone || '',
        email: reservation.email || '',
        party_size: String(reservation.party_size || 2),
        starts_at: tehranDateTimeInput(reservation.starts_at),
        duration_minutes: String(reservation.duration_minutes || 90),
        purpose: reservation.purpose || 'table',
        occasion_name: reservation.occasion_name || '',
        estimated_budget: reservation.estimated_budget == null ? '' : String(reservation.estimated_budget),
        notes: reservation.notes || '',
    }));
    const set = (key, value) => setForm(current => ({ ...current, [key]: value }));
    return <Modal title={'ویرایش رزرو ' + (reservation.reference || '')} eyebrow="RESERVATION DETAILS" onClose={onClose} wide>
        <form className="op-form op-form-grid" onSubmit={event => {
            event.preventDefault();
            onSubmit({
                ...form,
                restaurant_table_id: form.restaurant_table_id ? Number(form.restaurant_table_id) : null,
                party_size: Number(form.party_size),
                starts_at: localDateTime(form.starts_at),
                duration_minutes: Number(form.duration_minutes),
                estimated_budget: form.estimated_budget === '' ? null : Number(form.estimated_budget),
                email: form.email.trim() || null,
                occasion_name: form.occasion_name.trim() || null,
                notes: form.notes.trim() || null,
            });
        }}>
            <Field label="نام مشتری"><input value={form.customer_name} onChange={event=>set('customer_name',event.target.value)} maxLength="150" required /></Field>
            <Field label="تلفن"><input value={form.phone} onChange={event=>set('phone',event.target.value)} maxLength="30" required /></Field>
            <Field label="ایمیل"><input type="email" value={form.email} onChange={event=>set('email',event.target.value)} maxLength="150" /></Field>
            <Field label="تعداد نفرات"><input type="number" min="1" max="50" value={form.party_size} onChange={event=>set('party_size',event.target.value)} required /></Field>
            <Field label="میز"><select value={form.restaurant_table_id} onChange={event=>set('restaurant_table_id',event.target.value)}><option value="">بدون تخصیص میز</option>{(tables||[]).filter(table=>table.is_active || String(table.id)===form.restaurant_table_id).map(table=><option key={table.id} value={table.id}>{table.name}</option>)}</select></Field>
            <Field label="تاریخ و ساعت"><JalaliDateTimeInput value={form.starts_at} onChange={value=>set('starts_at',value)} required /></Field>
            <Field label="مدت"><select value={form.duration_minutes} onChange={event=>set('duration_minutes',event.target.value)}><option value="30">۳۰ دقیقه</option><option value="60">۶۰ دقیقه</option><option value="90">۹۰ دقیقه</option><option value="120">۱۲۰ دقیقه</option><option value="150">۱۵۰ دقیقه</option><option value="180">۱۸۰ دقیقه</option><option value="240">۲۴۰ دقیقه</option><option value="300">۳۰۰ دقیقه</option><option value="360">۳۶۰ دقیقه</option></select></Field>
            <Field label="نوع درخواست"><select value={form.purpose} onChange={event=>set('purpose',event.target.value)}>{Object.entries(reservationPurposeLabel).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></Field>
            <Field label="مناسبت"><input value={form.occasion_name} onChange={event=>set('occasion_name',event.target.value)} maxLength="150" /></Field>
            <Field label={'بودجه تخمینی ('+currencyLabel()+')'}><input type="number" min="0" value={form.estimated_budget} onChange={event=>set('estimated_budget',event.target.value)} /></Field>
            <Field label="یادداشت"><input value={form.notes} onChange={event=>set('notes',event.target.value)} maxLength="1000" /></Field>
            <div className="op-form-actions"><Button onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره تغییرات</Button></div>
        </form>
    </Modal>;
}


function CustomersPage({ feedback, pagination, onPageChange, onRespond, onRefresh }) {
    const [drafts, setDrafts] = useState({});
    return <div className="page-content">
        <PageHeader title="بازخورد مشتریان" subtitle="پیگیری پیشنهادها و شکایت‌ها و ثبت پاسخ مسئول؛ هر پاسخ روی سرور می‌ماند." />
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>بازخوردها</h2><p>برای هر مورد وضعیت رسیدگی و پاسخ را ثبت کن.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {feedback?.length ? <div className="op-feedback-list">{feedback.map(item => <article className="op-feedback" key={item.id}>
                <div className="op-feedback-head"><div><b>{item.customer_name || 'مشتری بدون نام'}</b><small>{item.phone || item.email || 'راه ارتباطی ثبت نشده'} · {dateTime(item.created_at)}</small></div><Pill value={item.status} dictionary={feedbackStatusLabel} /></div>
                <div className="op-feedback-meta"><span>{({complaint:'شکایت',suggestion:'پیشنهاد',praise:'قدردانی'})[item.kind] || item.kind}</span>{item.rating && <span>امتیاز: {numberFa(item.rating)} از ۵</span>}{item.order?.number && <span>سفارش {item.order.number}</span>}</div>
                <p>{item.message}</p>{item.admin_response && <blockquote>{item.admin_response}</blockquote>}
                <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); const draft = drafts[item.id] || {}; onRespond(item, { status: draft.status || (item.status === 'new' ? 'in_progress' : item.status), admin_response: draft.response ?? item.admin_response ?? '' }); }}>
                    <Field label="وضعیت رسیدگی"><select value={(drafts[item.id]?.status) ?? item.status} onChange={e => setDrafts(v => ({...v,[item.id]:{...(v[item.id]||{}),status:e.target.value}}))}>{Object.entries(feedbackStatusLabel).map(([value,label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="پاسخ مدیر"><input value={(drafts[item.id]?.response) ?? item.admin_response ?? ''} onChange={e => setDrafts(v => ({...v,[item.id]:{...(v[item.id]||{}),response:e.target.value}}))} maxLength="5000" placeholder="پاسخ قابل ارسال یا یادداشت رسیدگی" /></Field>
                    <div className="op-form-actions"><Button kind="primary" type="submit">ثبت پاسخ</Button></div>
                </form>
            </article>)}</div> : <Empty>بازخوردی ثبت نشده است.</Empty>}
            <Pagination meta={pagination} onPageChange={onPageChange} />
        </section>
    </div>;
}

function ExpensesPage({ expenses, pagination, onPageChange, onCreate, onEdit, onDelete, onRefresh }) {
    const [form, setForm] = useState({ title: '', category: 'ingredients', amount: '', payment_method: 'cash', is_paid: true, note: '' });
    const set = (key, value) => setForm(v => ({...v,[key]:value}));
    return <div className="page-content">
        <PageHeader title="هزینه‌های عملیاتی" subtitle="هزینه‌ها از این بخش قابل ثبت و ویرایش هستند؛ حذف مستقیم فقط برای رکوردهای نمونه مجاز است." />
        <section className="panel op-card"><div className="panel-heading"><div><h2>ثبت هزینه</h2><p>برای هزینه نقدی، شیفت صندوق باید باز باشد.</p></div></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onCreate({ ...form, amount: Number(form.amount) }); }}>
                <Field label="عنوان هزینه"><input value={form.title} onChange={e => set('title',e.target.value)} maxLength="150" required placeholder="مثلاً خرید شیر" /></Field>
                <Field label="دسته"><select value={form.category} onChange={e => set('category',e.target.value)}>{Object.entries(expenseCategoryLabel).map(([value,label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label={'مبلغ ('+currencyLabel()+')'}><input type="number" min="1" value={form.amount} onChange={e => set('amount',e.target.value)} required /></Field>
                <Field label="روش پرداخت"><select value={form.payment_method} onChange={e => set('payment_method',e.target.value)}><option value="cash">نقدی</option><option value="card_terminal">کارت‌خوان (ثبت‌شده)</option><option value="online">آنلاین</option><option value="other">سایر</option></select></Field>
                <Field label="وضعیت پرداخت"><select value={form.is_paid ? 'paid' : 'unpaid'} onChange={e => set('is_paid',e.target.value === 'paid')}><option value="paid">پرداخت شده</option><option value="unpaid">پرداخت نشده</option></select></Field>
                <Field label="یادداشت"><input value={form.note} onChange={e => set('note',e.target.value)} maxLength="1000" /></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ثبت هزینه</Button></div>
            </form>
        </section>
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>آخرین هزینه‌ها</h2><p>هزینه‌ی پرداخت‌نشده با خروج وجه صندوق یکی نیست. رکوردهای DEMO برای تست ساخته شده‌اند.</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {expenses?.length ? <TableWrap><thead><tr><th>زمان</th><th>عنوان</th><th>دسته</th><th>مبلغ</th><th>پرداخت</th><th>روش</th><th>ثبت‌کننده</th><th>عملیات</th></tr></thead><tbody>{expenses.map(ex => <tr key={ex.id}>
                <td>{dateTime(ex.created_at)}</td><td>{ex.title}{String(ex.seed_key||'').startsWith('demo-expense:')&&<small className="op-subtext">داده نمونه</small>}</td><td>{expenseCategoryLabel[ex.category] || ex.category}</td><td>{money(ex.amount)}</td>
                <td><Pill value={ex.is_paid ? 'paid' : 'unpaid'} dictionary={{paid:'پرداخت شده',unpaid:'پرداخت نشده'}} /></td><td>{paymentMethodLabel[ex.payment_method] || ex.payment_method}</td><td>{ex.user?.name || '—'}</td>
                <td><div className="op-actions"><Button onClick={()=>onEdit(ex)}>ویرایش</Button>{String(ex.title||'').startsWith('DEMO ·')&&<Button kind="danger" onClick={()=>onDelete(ex)}>حذف نمونه</Button>}</div></td>
            </tr>)}</tbody></TableWrap> : <Empty>هزینه‌ای ثبت نشده است.</Empty>}
            <Pagination meta={pagination} onPageChange={onPageChange} />
        </section>
    </div>;
}

function ExpenseEditModal({ expense, onClose, onSubmit }) {
    const [form, setForm] = useState({
        title: expense.title || '',
        category: expense.category || 'other',
        amount: String(expense.amount || ''),
        payment_method: expense.payment_method || 'other',
        is_paid: Boolean(expense.is_paid),
        note: expense.note || '',
    });
    const set = (key, value) => setForm(current => ({ ...current, [key]: value }));
    const isDemo = String(expense.seed_key || '').startsWith('demo-expense:');
    return <Modal title="ویرایش هزینه" eyebrow={isDemo ? 'SAMPLE EXPENSE' : 'EXPENSE DETAILS'} onClose={onClose}>
        <form className="op-form" onSubmit={event => { event.preventDefault(); onSubmit({ ...form, title: form.title.trim(), amount: Number(form.amount), note: form.note.trim() || null }); }}>
            <Field label="عنوان هزینه"><input value={form.title} onChange={event=>set('title',event.target.value)} maxLength="150" required /></Field>
            <Field label="دسته"><select value={form.category} onChange={event=>set('category',event.target.value)}>{Object.entries(expenseCategoryLabel).map(([value,label])=><option key={value} value={value}>{label}</option>)}</select></Field>
            <Field label={'مبلغ ('+currencyLabel()+')'}><input type="number" min="1" value={form.amount} onChange={event=>set('amount',event.target.value)} required /></Field>
            <Field label="روش پرداخت"><select value={form.payment_method} onChange={event=>set('payment_method',event.target.value)}><option value="cash">نقدی</option><option value="card_terminal">کارت‌خوان</option><option value="online">آنلاین</option><option value="other">سایر</option></select></Field>
            <Field label="وضعیت"><select value={form.is_paid?'paid':'unpaid'} onChange={event=>set('is_paid',event.target.value==='paid')}><option value="paid">پرداخت شده</option><option value="unpaid">پرداخت نشده</option></select></Field>
            <Field label="یادداشت"><textarea value={form.note} onChange={event=>set('note',event.target.value)} maxLength="1000" rows="3" /></Field>
            {expense.cash_shift_id&&<p className="op-note">این هزینه به دفتر صندوق متصل است؛ ویرایش مالی فقط تا زمانی مجاز است که شیفت مرتبط باز باشد.</p>}
            <div className="op-form-actions"><Button onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره تغییرات</Button></div>
        </form>
    </Modal>;
}

function ReportsPage({ report, onRefresh }) {
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    return <div className="page-content">
        <PageHeader title="گزارش‌های مالی و فروش" subtitle="فروش ثبت‌شده، دریافتی، بازپرداخت و پرداخت‌ها تفکیک شده‌اند؛ سود تا تکمیل بهای تمام‌شده گزارش نمی‌شود." />
        <form className="op-toolbar" onSubmit={e => { e.preventDefault(); onRefresh(from, to); }}><Field label="از تاریخ"><JalaliDateInput value={from} onChange={setFrom} placeholder="۱۴۰۵/۰۸/۰۱" ariaLabel="از تاریخ شمسی" /></Field><Field label="تا تاریخ"><JalaliDateInput value={to} onChange={setTo} placeholder="۱۴۰۵/۰۸/۳۰" ariaLabel="تا تاریخ شمسی" /></Field><Button kind="primary" type="submit">اجرای گزارش</Button><Button type="button" onClick={() => { setFrom(''); setTo(''); onRefresh('', ''); }}>این ماه</Button></form>
        {report?.summary && <div className="op-grid op-grid-3">
            <article className="stat-card"><div className="stat-label">سفارش‌ها</div><div className="stat-value">{numberFa(report.summary.orders_count)}</div><div className="stat-sub">{numberFa(report.summary.cancelled_orders)} لغوشده (در تعداد کل)</div></article>
            <article className="stat-card"><div className="stat-label">فروش ثبت‌شده</div><div className="stat-value">{money(report.summary.registered_sales)}</div><div className="stat-sub">مجموع سفارش‌ها، به‌جز لغوشده‌ها</div></article>
            <article className="stat-card"><div className="stat-label">دریافتی خالص بازه</div><div className="stat-value">{money(report.summary.collections_net)}</div><div className="stat-sub">دریافتی ناخالص {money(report.summary.collections_gross)} منهای بازپرداخت {money(report.summary.refunds)}</div></article>
        </div>}
        <section className="panel orders-panel"><div className="panel-heading"><div><h2>فروش روزانه</h2><p>بر اساس تاریخ ایجاد سفارش؛ فروش ثبت‌شده را با وجه دریافتی یکی نگیر.</p></div></div>
            {report?.daily?.length ? <TableWrap><thead><tr><th>روز</th><th>تعداد سفارش</th><th>فروش ثبت‌شده</th></tr></thead><tbody>{report.daily.map(day => <tr key={day.day}><td>{toPersianDigits(gregorianDateToJalali(day.day)) || day.day}</td><td>{numberFa(day.orders_count)}</td><td>{money(day.registered_sales)}</td></tr>)}</tbody></TableWrap> : <Empty>در این بازه سفارشی وجود ندارد.</Empty>}
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
    const [printerStatus, setPrinterStatus] = useState('not-connected');
    const [printerNames, setPrinterNames] = useState([]);
    const [cashierPrinter, setCashierPrinter] = useState('');
    const [kitchenPrinter, setKitchenPrinter] = useState('');
    const [printerError, setPrinterError] = useState('');
    const [printerNotice, setPrinterNotice] = useState('');

    useEffect(() => {
        try {
            setCashierPrinter(window.localStorage.getItem('gil-qz-cashier-printer') || '');
            setKitchenPrinter(window.localStorage.getItem('gil-qz-kitchen-printer') || '');
        } catch {
            setPrinterError('ذخیره تنظیمات چاپگر در این مرورگر در دسترس نیست.');
        }
    }, []);

    const discoverPrinters = async () => {
        setPrinterError('');
        setPrinterNotice('');
        const qz = window.qz;
        if (!qz?.websocket || !qz?.printers) {
            setPrinterStatus('missing');
            setPrinterError('کتابخانه QZ Tray بارگذاری نشده است؛ صفحه را بازخوانی کن.');
            return;
        }
        setPrinterStatus('connecting');
        try {
            if (!qz.websocket.isActive()) await qz.websocket.connect({ retries: 2, delay: 1 });
            setPrinterStatus('connected');
            const list = await qz.printers.find();
            const names = Array.isArray(list) ? list : [];
            setPrinterNames(names);
            setPrinterNotice(names.length ? (numberFa(names.length) + ' چاپگر از سیستم‌عامل این کامپیوتر شناسایی شد.') : 'چاپگری در این کامپیوتر پیدا نشد؛ ابتدا درایور چاپگر را نصب و آن را به سیستم‌عامل اضافه کن.');
        } catch (error) {
            setPrinterStatus('error');
            setPrinterError(error?.message || 'ارتباط با QZ Tray برقرار نشد. نصب و اجرای برنامه روی کامپیوتر صندوق را بررسی کن.');
        }
    };

    const savePrinterRouting = () => {
        try {
            window.localStorage.setItem('gil-qz-cashier-printer', cashierPrinter);
            window.localStorage.setItem('gil-qz-kitchen-printer', kitchenPrinter);
            setPrinterNotice('انتخاب چاپگرهای این کامپیوتر ذخیره شد.');
            setPrinterError('');
        } catch {
            setPrinterError('ذخیره انتخاب چاپگر در این مرورگر ناموفق بود.');
        }
    };

    const testPrinter = async (printerName, kind) => {
        if (!printerName) {
            setPrinterError('ابتدا چاپگر موردنظر را انتخاب کن.');
            return;
        }
        const qz = window.qz;
        if (!qz?.websocket) {
            setPrinterError('QZ Tray در دسترس نیست.');
            return;
        }
        setPrinterError('');
        setPrinterNotice('');
        try {
            if (!qz.websocket.isActive()) await qz.websocket.connect({ retries: 2, delay: 1 });
            const width = Number(form?.paper_width) === 58 ? 58 : 80;
            const html = '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><style>@page{size:'+width+'mm auto;margin:3mm}body{width:'+(width-6)+'mm;margin:0 auto;font:13px Tahoma,Arial;text-align:center}h1{font-size:18px}p{font-size:11px}</style><body><h1>خانه گیلاسی</h1><p>آزمایش چاپگر '+escapeHtml(kind)+'</p><b>اتصال چاپگر موفق است</b></body></html>';
            const config = qz.configs.create(printerName);
            await qz.print(config, [{ type: 'pixel', format: 'html', flavor: 'plain', data: html }]);
            setPrinterNotice('درخواست آزمایش برای «'+printerName+'» ارسال شد. خروج واقعی کاغذ باید روی دستگاه بررسی شود.');
        } catch (error) {
            setPrinterError(error?.message || 'ارسال آزمایشی به چاپگر ناموفق بود.');
        }
    };
    const defaults = {
        hero_eyebrow: 'کافه و رستوران خانه گیلاسی',
        hero_title_line1: 'طعم خوب،',
        hero_title_line2: 'حال خوب.',
        hero_description: 'برای یک قهوه کوتاه، یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی؛ انتخاب و سفارش را ساده کرده‌ایم تا وقتت برای لذت‌بردن بماند.',
        hero_primary_cta: 'دیدن منو و سفارش',
        hero_secondary_cta: 'درخواست رزرو',
        hero_badge_title: 'با عشق پخته‌شده',
        hero_badge_description: 'از آشپزخانه تا میز تو',
        showcase_eyebrow: 'انتخاب‌های خانه گیلاسی',
        showcase_title: 'بهترین طعم‌ها برای تو',
        showcase_description: 'محصولات فعال و تصاویر ثبت‌شده در منوی مجموعه؛ برای دیدن جزئیات و سفارش، انتخابشان کن.'
    };
    useEffect(() => {
        if (settings) setForm({
            name: settings.name || '', phone: settings.phone || '', address: settings.address || '',
            currency_label: settings.currency_label || 'تومان',
            tax_basis_points: String(settings.tax_basis_points || 0),
            service_basis_points: String(settings.service_basis_points || 0),
            receipt_footer: settings.receipt_footer || '',
            paper_width: String(settings.paper_width || 80),
            customer_menu_enabled: Boolean(settings.customer_menu_enabled),
            homepage_content: { ...defaults, ...(settings.homepage_content || {}) }
        });
    }, [settings]);
    const set = (key, value) => setForm(v => ({ ...v, [key]: value }));
    const setHome = (key, value) => setForm(v => ({ ...v, homepage_content: { ...v.homepage_content, [key]: value } }));
    if (!form) return <div className="page-content"><PageHeader title="تنظیمات مجموعه" subtitle="اطلاعات، محاسبات و محتوای صفحه اصلی."/><Loading /></div>;
    return <div className="page-content">
        <PageHeader title="تنظیمات مجموعه" subtitle="محاسبات سفارش از تنظیمات ذخیره‌شده سرور استفاده می‌کند." />
        <BrandLogoSettings restaurantName={form.name} />
        <section className="panel op-card"><div className="panel-heading"><div><h2>اطلاعات و محاسبات فاکتور</h2><p>مالیات و سرویس به شکل basis point ذخیره می‌شوند؛ مثلاً ۹٪ برابر ۹۰۰ است.</p></div><Button onClick={onRefresh}>بازخوانی تنظیمات</Button></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onSave({ ...form, tax_basis_points: Number(form.tax_basis_points), service_basis_points: Number(form.service_basis_points), paper_width: Number(form.paper_width) }); }}>
                <Field label="نام مجموعه"><input value={form.name} onChange={e => set('name',e.target.value)} maxLength="150" required /></Field>
                <Field label="تلفن"><input value={form.phone} onChange={e => set('phone',e.target.value)} maxLength="30" /></Field>
                <Field label="واحد پول" hint="این واحد مبنای تمام مبلغ‌هاست؛ پیش از ثبت محصول انتخابش کن."><select value={form.currency_label} onChange={e => set('currency_label', e.target.value)}><option value="تومان">تومان</option><option value="ریال">ریال</option></select></Field>
                <Field label="مالیات (%)"><input type="number" min="0" max="100" step="0.01" value={Number(form.tax_basis_points)/100} onChange={e => set('tax_basis_points', String(Math.round(Number(e.target.value || 0)*100)))} /></Field>
                <Field label="سرویس (%)"><input type="number" min="0" max="100" step="0.01" value={Number(form.service_basis_points)/100} onChange={e => set('service_basis_points', String(Math.round(Number(e.target.value || 0)*100)))} /></Field>
                <Field label="عرض کاغذ فیش"><select value={form.paper_width} onChange={e => set('paper_width',e.target.value)}><option value="58">۵۸ میلی‌متر</option><option value="80">۸۰ میلی‌متر</option></select></Field>
                <Field label="آدرس"><input value={form.address} onChange={e => set('address',e.target.value)} maxLength="2000" /></Field>
                <Field label="متن پایین فیش"><input value={form.receipt_footer} onChange={e => set('receipt_footer',e.target.value)} maxLength="1000" placeholder="مثلاً ممنون از همراهی شما" /></Field>
                <Field label="منوی QR مشتری"><select value={form.customer_menu_enabled ? 'yes' : 'no'} onChange={e => set('customer_menu_enabled',e.target.value === 'yes')}><option value="yes">فعال</option><option value="no">غیرفعال</option></select></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ذخیره تنظیمات</Button></div>
            </form>
        </section>
        <section className="panel op-card">
            <div className="panel-heading"><div><h2>محتوای هیرو و اسلایدر صفحه اصلی</h2><p>متن‌های این دو بخش از تنظیمات ذخیره می‌شوند. عکس‌ها از رسانه سایت و محصولات فعال خوانده می‌شوند.</p></div></div>
            <form className="op-form op-form-grid" onSubmit={e => { e.preventDefault(); onSave({ ...form, tax_basis_points: Number(form.tax_basis_points), service_basis_points: Number(form.service_basis_points), paper_width: Number(form.paper_width) }); }}>
                <Field label="برچسب بالای تیتر"><input value={form.homepage_content.hero_eyebrow} onChange={e => setHome('hero_eyebrow',e.target.value)} maxLength="100" required /></Field>
                <Field label="خط اول تیتر"><input value={form.homepage_content.hero_title_line1} onChange={e => setHome('hero_title_line1',e.target.value)} maxLength="80" required /></Field>
                <Field label="خط دوم تیتر"><input value={form.homepage_content.hero_title_line2} onChange={e => setHome('hero_title_line2',e.target.value)} maxLength="80" required /></Field>
                <Field label="متن معرفی هیرو"><textarea rows="3" value={form.homepage_content.hero_description} onChange={e => setHome('hero_description',e.target.value)} maxLength="600" required /></Field>
                <Field label="متن دکمه منو"><input value={form.homepage_content.hero_primary_cta} onChange={e => setHome('hero_primary_cta',e.target.value)} maxLength="60" required /></Field>
                <Field label="متن دکمه رزرو"><input value={form.homepage_content.hero_secondary_cta} onChange={e => setHome('hero_secondary_cta',e.target.value)} maxLength="60" required /></Field>
                <Field label="عنوان برچسب روی عکس"><input value={form.homepage_content.hero_badge_title} onChange={e => setHome('hero_badge_title',e.target.value)} maxLength="80" required /></Field>
                <Field label="متن کوچک برچسب عکس"><input value={form.homepage_content.hero_badge_description} onChange={e => setHome('hero_badge_description',e.target.value)} maxLength="120" required /></Field>
                <Field label="برچسب بخش اسلایدر"><input value={form.homepage_content.showcase_eyebrow} onChange={e => setHome('showcase_eyebrow',e.target.value)} maxLength="100" required /></Field>
                <Field label="تیتر اسلایدر"><input value={form.homepage_content.showcase_title} onChange={e => setHome('showcase_title',e.target.value)} maxLength="100" required /></Field>
                <Field label="توضیح اسلایدر"><textarea rows="3" value={form.homepage_content.showcase_description} onChange={e => setHome('showcase_description',e.target.value)} maxLength="500" required /></Field>
                <div className="op-form-actions"><Button kind="primary" type="submit">ذخیره تنظیمات صفحه اصلی</Button></div>
            </form>
            <p className="op-note">تصویر اصلی را از «رسانه و تصاویر سایت ← تصویر اصلی صفحه خانه» عوض کن. ترتیب و تصویرهای اسلایدر از ترتیب و تصاویر محصولات فعال در «منو و محصولات» گرفته می‌شوند.</p>
        </section>
        <section className="panel op-card op-printer-settings">
            <div className="panel-heading"><div><h2>اتصال چاپگرهای همین کامپیوتر</h2><p>چاپگرها از طریق QZ Tray و درایور سیستم‌عامل کامپیوتر صندوق پیدا می‌شوند. انتخاب‌ها فقط روی همین مرورگر ذخیره می‌شوند.</p></div><Pill value={printerStatus === 'connected' ? 'open' : printerStatus === 'connecting' ? 'pending' : 'closed'} dictionary={{open:'متصل',pending:'در حال اتصال',closed:'قطع'}} /></div>
            <div className="op-printer-setup-note">
                <b>پیش‌نیاز</b>
                <span>QZ Tray را روی کامپیوتر صندوق نصب و اجرا کن و مطمئن شو چاپگر در تنظیمات Windows / macOS / Linux دیده می‌شود. سرور سایت به چاپگر USB کامپیوتر تو دسترسی مستقیم ندارد.</span>
            </div>
            <div className="op-form-actions">
                <Button kind="primary" onClick={discoverPrinters} disabled={printerStatus === 'connecting'}>{printerStatus === 'connecting' ? 'در حال اتصال…' : 'اتصال و شناسایی چاپگرها'}</Button>
                <Button onClick={() => window.print()}>آزمایش چاپ مرورگر</Button>
            </div>
            {printerError && <p className="media-manager-message is-error" role="alert">{printerError}</p>}
            {printerNotice && <p className="media-manager-message is-success" role="status">{printerNotice}</p>}
            {printerNames.length > 0 && <>
                <div className="op-form op-form-grid op-printer-routing">
                    <Field label="چاپگر فیش صندوق / مشتری">
                        <select value={cashierPrinter} onChange={event => setCashierPrinter(event.target.value)}>
                            <option value="">انتخاب چاپگر صندوق…</option>
                            {printerNames.map(name => <option value={name} key={name}>{name}</option>)}
                        </select>
                    </Field>
                    <Field label="چاپگر فیش آشپزخانه">
                        <select value={kitchenPrinter} onChange={event => setKitchenPrinter(event.target.value)}>
                            <option value="">انتخاب چاپگر آشپزخانه…</option>
                            {printerNames.map(name => <option value={name} key={name}>{name}</option>)}
                        </select>
                    </Field>
                </div>
                <div className="op-form-actions">
                    <Button kind="primary" onClick={savePrinterRouting}>ذخیره مسیر چاپ</Button>
                    <Button onClick={() => testPrinter(cashierPrinter, 'صندوق / مشتری')}>تست چاپگر صندوق</Button>
                    <Button onClick={() => testPrinter(kitchenPrinter, 'آشپزخانه')}>تست چاپگر آشپزخانه</Button>
                </div>
            </>}
            <p className="op-note">چاپ فیش مشتری حتی پیش از تسویه، شماره سفارش و مبلغ قابل پرداخت را نشان می‌دهد. چاپ رسید پرداخت هم پرداخت‌های ثبت‌شده را درج می‌کند. اگر QZ Tray در دسترس نباشد، پنجره چاپ مرورگر باز می‌شود.</p>
        </section>
    </div>;
}

function ProductModal({ product, categories, onClose, onSave }) {
    const [imageFile,setImageFile]=useState(null);
    const [form,setForm]=useState({category_id:String(product?.category_id || categories?.[0]?.id || ''),name:product?.name || '',description:product?.description || '',ingredients:product?.ingredients || '',allergens:(product?.allergens || []).join('، '),dietary_tags:(product?.dietary_tags || []).join('، '),calories:product?.calories === null || product?.calories === undefined ? '' : String(product.calories),preparation_time_minutes:product?.preparation_time_minutes === null || product?.preparation_time_minutes === undefined ? '' : String(product.preparation_time_minutes),base_price:String(product?.base_price ?? ''),status:product?.status || 'active',stock_quantity:product?.stock_quantity === null || product?.stock_quantity === undefined ? '' : String(product.stock_quantity),low_stock_threshold:product?.low_stock_threshold === null || product?.low_stock_threshold === undefined ? '' : String(product.low_stock_threshold), options:(product?.options || []).map(o=>({id:o.id,name:o.name,type:o.type,price:String(o.price),is_active:Boolean(o.is_active)}))});
    const set=(key,value)=>setForm(v=>({...v,[key]:value}));
    const addOption=()=>set('options',[...form.options,{name:'',type:'addon',price:'0',is_active:true}]);
    const updateOption=(i,key,value)=>set('options',form.options.map((o,index)=>index===i?{...o,[key]:value}:o));
    const removeOption=i=>set('options',form.options.filter((_,index)=>index!==i));
    const previewUrl=useMemo(()=>imageFile?URL.createObjectURL(imageFile):null,[imageFile]);
    useEffect(()=>()=>{if(previewUrl)URL.revokeObjectURL(previewUrl);},[previewUrl]);
    return <Modal title={product ? 'ویرایش محصول' : 'محصول جدید'} eyebrow="فهرست محصولات" onClose={onClose} wide>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSave({ ...form,category_id:Number(form.category_id),base_price:Number(form.base_price),stock_quantity:form.stock_quantity===''?null:Number(form.stock_quantity),low_stock_threshold:form.low_stock_threshold===''?null:Number(form.low_stock_threshold),calories:form.calories===''?null:Number(form.calories),preparation_time_minutes:form.preparation_time_minutes===''?null:Number(form.preparation_time_minutes),allergens:form.allergens.split(/[،,]/).map(v=>v.trim()).filter(Boolean),dietary_tags:form.dietary_tags.split(/[،,]/).map(v=>v.trim()).filter(Boolean),options:form.options.map(o=>({...o,price:Number(o.price)})),image:imageFile||undefined },product?.id);}}>
            <div className="op-product-image-editor">
                {(previewUrl||product?.image_url) ? <img src={previewUrl||product.image_url} alt="پیش‌نمایش تصویر محصول" /> : <span>☕</span>}
                <Field label="عکس محصول (JPG / PNG / WebP، حداکثر ۴ مگابایت)">
                    <input type="file" accept="image/jpeg,image/png,image/webp" onChange={e=>setImageFile(e.target.files?.[0]||null)} />
                </Field>
            </div>
            <div className="op-form-grid">
                <Field label="نام محصول"><input value={form.name} onChange={e=>set('name',e.target.value)} maxLength="150" required /></Field>
                <Field label="دسته‌بندی"><select value={form.category_id} onChange={e=>set('category_id',e.target.value)} required>{(categories||[]).map(c=><option key={c.id} value={c.id}>{c.name}</option>)}</select></Field>
                <Field label={"قیمت (" + currencyLabel() + ')'}><input type="number" min="0" value={form.base_price} onChange={e=>set('base_price',e.target.value)} required /></Field>
                <Field label="وضعیت فروش"><select value={form.status} onChange={e=>set('status',e.target.value)}><option value="active">فعال</option><option value="inactive">غیرفعال</option><option value="unavailable">ناموجود</option></select></Field>
                <Field label="موجودی فعلی" hint={product && product.stock_quantity !== null ? 'رهگیری موجودی فعال است؛ برای خاموش‌کردن آن سوابق قابل حسابرسی از بین می‌رود.' : 'خالی = بدون رهگیری'}><input type="number" min="0" value={form.stock_quantity} required={Boolean(product && product.stock_quantity !== null)} onChange={e=>set('stock_quantity',e.target.value)} placeholder="خالی = بدون رهگیری" /></Field>
                <Field label="آستانه کمبود"><input type="number" min="0" value={form.low_stock_threshold} onChange={e=>set('low_stock_threshold',e.target.value)} placeholder="مثلاً ۵" /></Field>
                <Field label="توضیحات"><textarea rows="2" value={form.description} onChange={e=>set('description',e.target.value)} maxLength="3000" placeholder="توضیح کوتاه برای منوی مشتری" /></Field>
                <Field label="مواد تشکیل‌دهنده"><textarea rows="3" value={form.ingredients} onChange={e=>set('ingredients',e.target.value)} maxLength="5000" placeholder="مواد و ترکیبات واقعی محصول را وارد کن" /></Field>
                <Field label="آلرژن‌ها (با ویرگول جدا کن)"><input value={form.allergens} onChange={e=>set('allergens',e.target.value)} maxLength="1000" placeholder="مثلاً شیر، مغزها، گلوتن" /></Field>
                <Field label="برچسب‌های غذایی (با ویرگول جدا کن)"><input value={form.dietary_tags} onChange={e=>set('dietary_tags',e.target.value)} maxLength="1000" placeholder="مثلاً گیاهی، بدون شکر افزوده" /></Field>
                <Field label="انرژی (کیلوکالری در هر وعده)"><input type="number" min="0" max="10000" value={form.calories} onChange={e=>set('calories',e.target.value)} placeholder="اختیاری؛ فقط عدد تأییدشده" /></Field>
                <Field label="زمان آماده‌سازی (دقیقه)"><input type="number" min="0" max="1440" value={form.preparation_time_minutes} onChange={e=>set('preparation_time_minutes',e.target.value)} placeholder="مثلاً ۸" /></Field>
            </div>
            <div className="op-options-editor"><div className="panel-heading"><div><h3>سایزها و افزودنی‌ها</h3><p>تغییر گزینه‌ها با ارسال این فرم جایگزین لیست فعلی می‌شود.</p></div><Button type="button" onClick={addOption}>＋ گزینه</Button></div>
                {form.options.map((option,i)=><div className="op-option-row" key={option.id || 'new-'+i}>
                    <input value={option.name} onChange={e=>updateOption(i,'name',e.target.value)} placeholder="نام گزینه" maxLength="100" required />
                    <select value={option.type} onChange={e=>updateOption(i,'type',e.target.value)}><option value="size">سایز</option><option value="addon">افزودنی</option></select>
                    <MoneyInput min="0" value={option.price} onChange={value=>updateOption(i,'price',value)} aria-label="قیمت گزینه" required />
                    <label className="op-option-active"><input type="checkbox" checked={Boolean(option.is_active)} onChange={e=>updateOption(i,'is_active',e.target.checked)} />فعال</label>
                    <Button type="button" kind="danger" onClick={()=>removeOption(i)}>حذف</Button>
                </div>)}
            </div>
            <p className="op-note">موجودی را اگر قابل شمارش نیست خالی بگذار. پس از فعال‌شدن رهگیری، اصلاح عدد موجودی در گردش انبار ثبت می‌شود.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره محصول</Button></div>
        </form>
    </Modal>;
}

function CategoryModal({ category, onClose, onSave }) {
    const [name,setName]=useState(category?.name || '');
    const [sortOrder,setSortOrder]=useState(String(category?.sort_order ?? 0));
    const [isActive,setIsActive]=useState(category?.is_active !== false);
    return <Modal title={category ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'} eyebrow="دسته‌بندی منو" onClose={onClose}>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSave({name:name.trim(),sort_order:Number(sortOrder),is_active:isActive},category);}}>
            <Field label="نام دسته"><input value={name} onChange={e=>setName(e.target.value)} maxLength="150" required /></Field>
            <Field label="ترتیب نمایش"><input type="number" min="0" value={sortOrder} onChange={e=>setSortOrder(e.target.value)} /></Field>
            <Field label="وضعیت"><select value={isActive ? '1' : '0'} onChange={e=>setIsActive(e.target.value === '1')}><option value="1">فعال</option><option value="0">غیرفعال</option></select></Field>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">{category ? 'ذخیره تغییرات' : 'ایجاد دسته'}</Button></div>
        </form>
    </Modal>;
}

function TableEditModal({ table, onClose, onSave }) {
    const [name,setName]=useState(table?.name || '');
    const [description,setDescription]=useState(table?.description || '');
    return <Modal title="ویرایش میز" eyebrow="تنظیمات میز" onClose={onClose}>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSave({name:name.trim(),description:description.trim() || null});}}>
            <Field label="نام میز"><input value={name} onChange={e=>setName(e.target.value)} maxLength="80" required /></Field>
            <Field label="توضیح"><input value={description} onChange={e=>setDescription(e.target.value)} maxLength="255" /></Field>
            <p className="op-note">تغییر نام یا توضیح روی همان رکورد ذخیره می‌شود و لینک QR همین میز حفظ می‌شود.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره تغییرات</Button></div>
        </form>
    </Modal>;
}

function PaymentModal({ order, cashShiftOpen, onClose, onSubmit }) {
    const [amount,setAmount]=useState(String(getRemaining(order)));
    const [method,setMethod]=useState(cashShiftOpen ? 'cash' : 'card_terminal');
    const [reference,setReference]=useState('');
    const [note,setNote]=useState('');
    const [requestKey] = useState(() => idempotencyKey());
    const remaining=getRemaining(order);
    return <Modal title="ثبت پرداخت" eyebrow="پرداخت و صندوق" onClose={onClose}>
        <div className="op-payment-summary"><span>سفارش {order.number}</span><b>{money(order.total)}</b><small>مانده محاسبه‌شده: {money(remaining)}</small></div>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit({amount:Number(amount),method,reference:reference||null,note:note||null,idempotency_key:requestKey},order.id);}}>
            <Field label={"مبلغ دریافت (" + currencyLabel() + ')'}><input type="number" min="1" max={remaining} value={amount} onChange={e=>setAmount(e.target.value)} required /></Field>
            <Field label="روش پرداخت"><select value={method} onChange={e=>setMethod(e.target.value)}><option value="cash" disabled={!cashShiftOpen}>نقدی{cashShiftOpen ? '' : ' — ابتدا شیفت صندوق را باز کن'}</option><option value="card_terminal">کارت‌خوان — ثبت تأیید توسط کارمند</option><option value="other">سایر / انتقال تأییدشده خارج از سامانه</option></select></Field>
            <Field label="شماره پیگیری (اختیاری)"><input value={reference} onChange={e=>setReference(e.target.value)} maxLength="120" /></Field>
            <Field label="یادداشت"><input value={note} onChange={e=>setNote(e.target.value)} maxLength="500" /></Field>
            <p className="op-note">روش «کارت‌خوان» به معنی تأیید خودکار دستگاه نیست؛ کاربر فقط دریافت وجه را ثبت می‌کند. اتصال درگاه/ترمینال برای تأیید فنی جداست.</p>
            <div className="op-form-actions"><Button type="button" onClick={onClose}>انصراف</Button><Button kind="primary" type="submit" disabled={Number(amount)<1 || Number(amount)>remaining}>ثبت پرداخت</Button></div>
        </form>
    </Modal>;
}

function CancelModal({ order, onClose, onSubmit }) {
    const [reason,setReason]=useState('');
    return <Modal title="لغو سفارش" eyebrow="لغو سفارش" onClose={onClose}>
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
    return <Modal title="بازپرداخت" eyebrow="بازپرداخت و ثبت رویداد" onClose={onClose}>
        <div className="op-payment-summary"><span>مبلغ پرداخت اولیه</span><b>{money(payment.amount)}</b><small>مانده قابل بازپرداخت: {money(refundable)}</small></div>
        <form className="op-form" onSubmit={e=>{e.preventDefault();onSubmit({amount:Number(amount),reason,idempotency_key:requestKey},payment.id,orderId);}}>
            <Field label={"مبلغ بازپرداخت (" + currencyLabel() + ')'}><input type="number" min="1" max={refundable} value={amount} onChange={e=>setAmount(e.target.value)} required /></Field>
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
    return <Modal title={'سفارش ' + (order?.number || '')} eyebrow="جزئیات سفارش / تاریخچه رویداد" onClose={onClose} wide>
        {!order ? <Loading /> : <>
            <div className="op-detail-summary"><div><small>کانال / میز</small><b>{channelLabel(order)}</b></div><div><small>وضعیت آماده‌سازی</small><Pill value={order.status} dictionary={orderStatusLabel} /></div><div><small>وضعیت پرداخت</small><Pill value={order.payment_status} dictionary={paymentStatusLabel} /></div><div><small>مبلغ نهایی</small><b>{money(order.total)}</b></div></div>
            <div className="panel-heading"><div><h3>اقلام</h3><p>مبلغ‌های ذخیره‌شده از زمان سفارش</p></div></div>
            {items.length ? <TableWrap><thead><tr><th>محصول</th><th>تعداد</th><th>قیمت واحد</th><th>افزودنی‌ها</th><th>جمع ردیف</th></tr></thead><tbody>{items.map(item=><tr key={item.id}><td>{item.name}<small className="op-subtext">{item.note || ''}</small></td><td>{numberFa(item.quantity)}</td><td>{money(item.unit_price)}</td><td>{(item.options||[]).map(o=>o.name).join('، ') || '—'}</td><td>{money(item.line_total)}</td></tr>)}</tbody></TableWrap> : <Empty />}
            <div className="op-detail-totals"><span>جمع اقلام: <b>{money(subtotal)}</b></span><span>تخفیف: <b>{money(order.discount)}</b></span><span>مالیات: <b>{money(order.tax)}</b></span><span>سرویس: <b>{money(order.service)}</b></span><span>دریافت ناخالص: <b>{money(paid)}</b></span><span>بازپرداخت: <b>{money(refunded)}</b></span><span>مانده: <b>{money(Math.max(0,Number(order.total)-paid+refunded))}</b></span></div>
            <div className="panel-heading"><div><h3>سوابق پرداخت</h3><p>پرداخت‌ها تغییرناپذیرند؛ اصلاح از مسیر بازپرداخت ثبت می‌شود.</p></div></div>
            {payments.length ? <TableWrap><thead><tr><th>زمان</th><th>روش</th><th>مبلغ</th><th>وضعیت</th><th>پیگیری</th><th>بازپرداخت</th></tr></thead><tbody>{payments.map(p=>{const refundable=Math.max(0,Number(p.amount)-(p.refunds||[]).reduce((sum,r)=>sum+Number(r.amount||0),0));return <tr key={p.id}><td>{dateTime(p.paid_at)}</td><td>{paymentMethodLabel[p.method]||p.method}</td><td>{money(p.amount)}</td><td>{p.status}</td><td>{p.reference||'—'}</td><td>{refundable>0&&p.status==='succeeded'?<Button kind="danger" onClick={()=>onRefund(p,order.id)}>بازپرداخت تا {money(refundable)}</Button>:'—'}</td></tr>;})}</tbody></TableWrap> : <Empty>برای این سفارش هنوز پرداختی ثبت نشده است.</Empty>}
            <div className="op-form-actions"><Button onClick={()=>onPrint(order.id,'invoice')}>چاپ فاکتور</Button><Button onClick={()=>onPrint(order.id,'kitchen')}>فیش آشپزخانه</Button><Button kind="primary" onClick={()=>onPrint(order.id,'receipt')}>فیش مشتری / رسید</Button><Button onClick={onClose}>بستن</Button></div>
        </>}
    </Modal>;
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
    const [listPage,setListPage]=useState(1);
    const [listFilters,setListFilters]=useState({
        orders:{query:'',status:'all'},
        products:{query:'',category:'all'},
    });

    const updateListFilters=(section,patch)=>{
        const previous=listFilters[section] || {};
        const next={...previous,...patch};
        if(Object.keys(next).every(key=>next[key]===previous[key]) && Object.keys(previous).length===Object.keys(next).length) return;
        setListPage(1);
        setListFilters(current=>({...current,[section]:next}));
    };
    const queryString=params=>{
        const entries=Object.entries(params).filter(([,value])=>value!==undefined&&value!==null&&value!=='');
        return entries.length?'?'+entries.map(([key,value])=>encodeURIComponent(key)+'='+encodeURIComponent(String(value))).join('&'):'';
    };

    useEffect(()=>{
        let alive=true;
        const load=async()=>{
            setLoading(true);setError('');
            try{
                let next={};
                const listParams={per_page:25,page:listPage};
                if(page==='overview') {
                    const [dashboardPayload, settingsPayload] = await Promise.all([api('/dashboard'), api('/settings')]);
                    next.dashboard=unwrapData(dashboardPayload);next.settings=unwrapData(settingsPayload);
                }
                if(page==='pos'){
                    const [products,categories,tables,shift,settingsPayload]=await Promise.all([
                        api('/products?per_page=100&available=1'),
                        api('/categories?per_page=100'),
                        api('/tables?per_page=100'),
                        api('/cash-shift'),
                        settings ? Promise.resolve({data:settings}) : api('/settings'),
                    ]);
                    next.posProducts=unwrapList(products);
                    next.posCategories=unwrapList(categories);
                    next.posTables=unwrapList(tables);
                    next.posCashShift=unwrapData(shift);
                    next.settings=unwrapData(settingsPayload);
                }
                if(page==='orders'){
                    const params={...listParams,sort:'latest'};
                    const filters=listFilters.orders;
                    if(filters.query?.trim()) params.q=filters.query.trim();
                    if(filters.status==='open') params.active=1;
                    else if(filters.status==='unsettled') params.unsettled=1;
                    else if(filters.status!=='all') params.status=filters.status;
                    const payload=await api('/orders'+queryString(params));
                    next.orders=unwrapList(payload);next.ordersPagination=unwrapPagination(payload);
                }
                if(page==='menu'){
                    const params={...listParams};
                    const filters=listFilters.products;
                    if(filters.query?.trim()) params.q=filters.query.trim();
                    if(filters.category!=='all') params.category_id=filters.category;
                    const [products,categories]=await Promise.all([api('/products'+queryString(params)),api('/categories')]);
                    next.products=unwrapList(products);next.productsPagination=unwrapPagination(products);next.categories=unwrapList(categories);
                }
                if(page==='inventory'){
                    const [products,movements]=await Promise.all([api('/products?per_page=100'),api('/inventory/movements'+queryString(listParams))]);
                    next.products=unwrapList(products);next.movements=unwrapList(movements);next.movementsPagination=unwrapPagination(movements);
                }
                if(page==='recipes') next.recipes=unwrapData(await api('/recipes'));
                if(page==='tables'){
                    const payload=await api('/tables'+queryString(listParams));
                    next.tables=unwrapList(payload);next.tablesPagination=unwrapPagination(payload);
                }
                if(page==='reservations'){
                    const [reservations,tables]=await Promise.all([api('/reservations'+queryString(listParams)),api('/tables?per_page=100')]);
                    next.reservations=unwrapList(reservations);next.reservationsPagination=unwrapPagination(reservations);next.tables=unwrapList(tables);
                }
                if(page==='customers'){
                    const payload=await api('/feedback'+queryString(listParams));
                    next.feedback=unwrapList(payload);next.feedbackPagination=unwrapPagination(payload);
                }
                if(page==='expenses'){
                    const payload=await api('/expenses'+queryString(listParams));
                    next.expenses=unwrapList(payload);next.expensesPagination=unwrapPagination(payload);
                }
                if(page==='reports') next.report=unwrapData(await api('/reports/sales'));
                if(page==='settings') { next.settings=unwrapData(await api('/settings')); }
                if(alive){setData(prev=>({...prev,...next}));if(next.settings){setSettings(next.settings);window.__restaurantCurrencyLabel=next.settings.currency_label||'تومان';}}
            }catch(e){if(alive)setError(e?.message||'دریافت اطلاعات از سرور ناموفق بود.');}
            finally{if(alive)setLoading(false);}
        };
        load();
        return ()=>{alive=false;};
    },[page,reloadVersion,listPage,listFilters]);

    useEffect(() => {
        if (!['overview', 'orders', 'pos'].includes(page)) return undefined;
        const timer = window.setInterval(() => {
            if (!document.hidden) setReloadVersion(version => version + 1);
        }, 15000);
        return () => window.clearInterval(timer);
    }, [page]);

    const navigate=next=>{setPage(next);setListPage(1);setMobileNav(false);setError('');};
    const notify=message=>{setNotice(message);window.setTimeout(()=>setNotice(''),5000);};
    const refresh=()=>setReloadVersion(v=>v+1);
    const action=async(work, success, options={})=>{
        if(busy)return;
        setBusy(true);setError('');
        try{await work();if(success)notify(success);if(options.close!==false)setModal(null);if(options.reload!==false)refresh();return true;}
        catch(e){notify(e?.message||'عملیات ناموفق بود.');return false;}
        finally{setBusy(false);}
    };
    const newOrder=()=>navigate('pos');
    const orderDetails=async id=>{
        try{const payload=await api('/orders/'+id);setDetailOrder(unwrapData(payload));setModal({type:'details'});}
        catch(e){notify(e.message||'جزئیات سفارش بارگذاری نشد.');}
    };
    const showRefund=(payment,orderId)=>setModal({type:'refund',payment,orderId});
    const transition=(order,status)=>action(()=>api('/orders/'+order.id+'/status',{method:'PATCH',body:{status,reason:'تغییر وضعیت از داشبورد'}}),'وضعیت سفارش به‌روزرسانی شد.',{close:false});
    const cancelOrder=(order)=>setModal({type:'cancel',order});
    const submitCancel=(reason,id)=>action(()=>api('/orders/'+id+'/cancel',{method:'POST',body:{reason}}),'سفارش لغو و موجودی رهگیری‌شده بازگردانده شد.',{close:true});
    const openPayment=async order=>{
        try{const detail=unwrapData(await api('/orders/'+order.id));setModal({type:'payment',order:detail});}
        catch(e){notify(e.message||'جزئیات سفارش بارگیری نشد.');}
    };
    const submitPayment=(payload,id)=>action(()=>api('/orders/'+id+'/payments',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'پرداخت ثبت شد؛ فاکتور را می‌توانی چاپ کنی.');
    const submitPosOrder=async ({payload,payment})=>{
        if(busy)return {ok:false,error:'یک عملیات دیگر در حال ثبت است.'};
        setBusy(true);setError('');
        let savedOrder=null;
        try{
            savedOrder=unwrapData(await api('/orders',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}));
            if(payment){
                try{
                    await api('/orders/'+savedOrder.id+'/payments',{
                        method:'POST',
                        body:{
                            amount:Number(savedOrder.total),
                            method:payment.method,
                            reference:payment.reference||null,
                            note:payment.note||null,
                            idempotency_key:payment.idempotency_key,
                        },
                        idempotent:true,
                        idempotencyKey:payment.idempotency_key,
                    });
                }catch(paymentError){
                    notify('سفارش '+(savedOrder.number||'')+' ثبت شد، اما پرداخت ثبت نشد: '+(paymentError?.message||'خطای نامشخص')+'. از صفحه سفارش‌ها تسویه را ادامه بده.');
                    refresh();
                    return {ok:true,order:savedOrder,paymentPending:true};
                }
            }
            notify('سفارش '+(savedOrder.number||'')+' در سامانه ثبت شد.'+(payment?' دریافت وجه هم ثبت شد.':' برای تسویه بعدی در فهرست مانده‌ها قرار گرفت.'));
            refresh();
            return {ok:true,order:savedOrder,paymentPending:false};
        }catch(operationError){
            notify(operationError?.message||'ثبت سفارش ناموفق بود.');
            return {ok:false,error:operationError?.message||'ثبت سفارش ناموفق بود.'};
        }finally{
            setBusy(false);
        }
    };
    const submitRefund=(payload,paymentId,orderId)=>action(()=>api('/payments/'+paymentId+'/refunds',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'بازپرداخت ثبت شد. اگر روش وجه نقد باشد، حرکت خروجی صندوق هم ثبت می‌شود.',{close:true});
    const printDocument=async(id,type)=>{
        const popup=window.open('about:blank','_blank','width=460,height=800');
        try{
            const [payload,settingPayload]=await Promise.all([api('/orders/'+id+'/'+(type==='kitchen'?'kitchen-slip':type==='receipt'?'receipt':'invoice')),settings?Promise.resolve({data:settings}):api('/settings')]);
            const order=unwrapData(payload);
            const printerKey=type==='kitchen'?'gil-qz-kitchen-printer':'gil-qz-cashier-printer';
            let targetPrinter='';
            try{targetPrinter=window.localStorage.getItem(printerKey)||'';}catch{}
            const qz=window.qz;
            let sentToLocalPrinter=false;

            if(targetPrinter && qz?.websocket && qz?.printers && qz?.configs && qz?.print){
                try{
                    if(!qz.websocket.isActive || !qz.websocket.isActive()) await qz.websocket.connect({retries:2,delay:1});
                    const printerConfig=qz.configs.create(targetPrinter);
                    await qz.print(printerConfig,[{type:'pixel',format:'html',flavor:'plain',data:buildPrintHtml(order,type,unwrapData(settingPayload))}]);
                    sentToLocalPrinter=true;
                    notify('درخواست '+(type==='kitchen'?'فیش آشپزخانه':'فیش مشتری')+' به چاپگر «'+targetPrinter+'» ارسال شد. خروج واقعی کاغذ را بررسی کن.');
                }catch(localError){
                    notify('چاپ مستقیم در دسترس نبود؛ پنجره چاپ مرورگر باز می‌شود. '+(localError?.message||''));
                }
            }

            if(!sentToLocalPrinter){
                if(!popup){notify('چاپگر محلی متصل نیست و مرورگر نیز پنجره چاپ را مسدود کرده است.');return;}
                popup.document.open();
                popup.document.write(buildPrintHtml(order,type,unwrapData(settingPayload)));
                popup.document.close();
                popup.focus();
                window.setTimeout(()=>{try{popup.focus();popup.print();}catch{}},400);
            }else if(popup && !popup.closed){
                popup.close();
            }

            const printType=type==='kitchen'?'kitchen':type;
            try{await api('/orders/'+id+'/print-log',{method:'POST',body:{type:printType,payment_id:type==='receipt'?(order.payments||[]).find(p=>p.status==='succeeded')?.id||null:null,is_reprint:false}});}
            catch(e){notify('درخواست چاپ ارسال شد، اما ثبت گزارش چاپ ناموفق بود: '+e.message);}
        }catch(e){
            if(popup && !popup.closed) popup.close();
            notify(e?.message||'دریافت اطلاعات برای چاپ ناموفق بود.');
        }
    };
    const createProduct=(payload,id)=>action(()=>{
        if (typeof File !== 'undefined' && payload.image instanceof File) {
            const body = new FormData();
            Object.entries(payload).forEach(([key,value]) => {
                if (value === undefined || key === 'image') return;
                if (['options', 'allergens', 'dietary_tags'].includes(key)) body.append(key, JSON.stringify(value));
                else body.append(key, value === null ? '' : typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
            });
            body.append('image', payload.image);
            if (id) body.append('_method', 'PATCH');
            return api('/products'+(id?'/'+id:''),{method:'POST',body});
        }
        const { image, ...json } = payload;
        return api('/products'+(id?'/'+id:''),{method:id?'PATCH':'POST',body:json});
    },'محصول و تصویر ذخیره شد.');
    const changeProductStatus=(product,status)=>action(()=>api('/products/'+product.id+'/status',{method:'PATCH',body:{status}}),'وضعیت محصول تغییر کرد.',{close:false});
    const createCategory=payload=>action(()=>api('/categories',{method:'POST',body:payload}),'دسته‌بندی ایجاد شد.');
    const updateCategory=(category,payload)=>action(()=>api('/categories/'+category.id,{method:'PATCH',body:payload}),'دسته‌بندی به‌روزرسانی شد.');
    const deleteCategory=category=>{
        if(!window.confirm('دسته «'+category.name+'» حذف شود؟ اگر محصولی به آن متصل باشد، سرور حذف را رد می‌کند.')) return;
        return action(()=>api('/categories/'+category.id,{method:'DELETE'}),'دسته‌بندی حذف شد.');
    };
    const deleteProduct=product=>{
        if(!window.confirm('محصول «'+product.name+'» حذف شود؟ محصول دارای سابقه فروش یا گردش موجودی قابل حذف نیست و باید غیرفعال شود.')) return;
        return action(()=>api('/products/'+product.id,{method:'DELETE'}),'محصول حذف شد.');
    };
    const createTable=payload=>action(()=>api('/tables',{method:'POST',body:payload}),'میز ایجاد شد.');
    const updateTable=(table,payload)=>action(()=>api('/tables/'+table.id,{method:'PATCH',body:payload}),'میز به‌روزرسانی شد.',{close:false});
    const editTable=(table,payload)=>action(()=>api('/tables/'+table.id,{method:'PATCH',body:payload}),'اطلاعات میز به‌روزرسانی شد.');
    const archiveTable=table=>{
        if(!window.confirm('میز «'+table.name+'» بایگانی و از دسترس منوی مشتری خارج شود؟ سوابق سفارش حذف نمی‌شوند.')) return;
        return action(()=>api('/tables/'+table.id,{method:'PATCH',body:{is_active:false}}),'میز بایگانی شد؛ سوابق آن در دیتابیس باقی ماند.',{close:false});
    };
    const rotateTable=table=>action(()=>api('/tables/'+table.id+'/rotate-qr',{method:'POST',body:{}}),'QR قبلی باطل و لینک جدید تولید شد.',{close:false});
    const adjustInventory=payload=>action(()=>api('/inventory/adjustments',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'گردش موجودی ثبت شد.');
    const createIngredient=payload=>action(()=>api('/ingredients',{method:'POST',body:payload}),'ماده اولیه ثبت شد.');
    const updateIngredient=(ingredient,payload)=>action(()=>api('/ingredients/'+ingredient.id,{method:'PATCH',body:payload}),'مشخصات ماده اولیه به‌روز شد.',{close:false});
    const saveRecipe=(productId,ingredients)=>action(()=>api('/products/'+productId+'/recipe',{method:'PUT',body:{ingredients}}),'رسپی ذخیره شد؛ از این پس مصرف مواد این محصول در سفارش‌ها محاسبه می‌شود.',{close:false});
    const submitIngredientStock=(ingredient,payload)=>action(()=>api('/ingredients/'+ingredient.id+'/movements',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'گردش ماده اولیه ثبت شد.');
    const createReservation=payload=>action(()=>api('/reservations',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'رزرو ثبت شد.');
    const updateReservation=(reservation,payload)=>action(()=>api('/reservations/'+reservation.id,{method:'PATCH',body:payload}),'اطلاعات رزرو به‌روزرسانی شد.');
    const deleteReservation=reservation=>{
        if(!window.confirm('رزرو نمونه «'+reservation.reference+'» حذف شود؟ این عملیات فقط برای رکوردهای DEMO مجاز است.')) return;
        return action(()=>api('/reservations/'+reservation.id,{method:'DELETE'}),'رزرو نمونه حذف شد.');
    };
    const transitionReservation=(item,status,tableId)=>action(()=>api('/reservations/'+item.id+'/status',{method:'PATCH',body:{status,restaurant_table_id:tableId}}),'وضعیت رزرو و تخصیص میز به‌روزرسانی شد.',{close:false});
    const respondFeedback=(item,payload)=>action(()=>api('/feedback/'+item.id+'/response',{method:'PATCH',body:payload}),'پاسخ بازخورد ذخیره شد.',{close:false});
    const createExpense=payload=>action(()=>api('/expenses',{method:'POST',body:payload}),'هزینه ثبت شد.');
    const updateExpense=(expense,payload)=>action(()=>api('/expenses/'+expense.id,{method:'PATCH',body:payload}),'هزینه به‌روزرسانی شد.');
    const deleteExpense=expense=>{
        if(!window.confirm('هزینه نمونه «'+expense.title+'» حذف شود؟ اگر به شیفت باز متصل باشد، گردش صندوق آن نیز حذف می‌شود.')) return;
        return action(()=>api('/expenses/'+expense.id,{method:'DELETE'}),'هزینه نمونه حذف شد.');
    };
    const deleteIngredient=ingredient=>{
        if(!window.confirm('ماده اولیه «'+ingredient.name+'» حذف شود؟ فقط ماده‌ای بدون رسپی و گردش انبار قابل حذف است.')) return;
        return action(()=>api('/ingredients/'+ingredient.id,{method:'DELETE'}),'ماده اولیه حذف شد.');
    };
    const openShift=payload=>action(()=>api('/cash-shift/open',{method:'POST',body:payload}),'شیفت صندوق باز شد.');
    const closeShift=(id,payload)=>action(()=>api('/cash-shift/'+id+'/close',{method:'POST',body:payload}),'شیفت بسته و مغایرت محاسبه شد.');
    const cashMovement=(id,payload)=>action(()=>api('/cash-shift/'+id+'/movements',{method:'POST',body:payload,idempotent:true,idempotencyKey:payload.idempotency_key}),'حرکت صندوق ثبت شد.',{close:false});
    const saveSettings=payload=>action(async()=>{await api('/settings',{method:'PATCH',body:payload});const refreshed=unwrapData(await api('/settings'));setSettings(refreshed);window.__restaurantCurrencyLabel=refreshed.currency_label||'تومان';setData(prev=>({...prev,settings:refreshed}));},'تنظیمات ذخیره شد.',{close:false});
    const runReport=async(from='',to='')=>{
        try{setLoading(true);let qs=[];if(from)qs.push('from='+encodeURIComponent(from));if(to)qs.push('to='+encodeURIComponent(to));const next=unwrapData(await api('/reports/sales'+(qs.length?'?'+qs.join('&'):'')));setData(p=>({...p,report:next}));setError('');}
        catch(e){setError(e.message||'اجرای گزارش ناموفق بود.');}
        finally{setLoading(false);}
    };

    const renderPage=()=>{
        if(page==='overview') return data.dashboard ? <OverviewPage dashboard={data.dashboard} onNavigate={navigate} onNewOrder={newOrder} onOrderOpen={orderDetails} /> : (loading ? <OverviewSkeleton /> : <OverviewPage dashboard={{}} onNavigate={navigate} onNewOrder={newOrder} onOrderOpen={orderDetails} />);
        if(page==='pos') return <PosPage products={data.posProducts||[]} categories={data.posCategories||[]} tables={data.posTables||[]} cashShift={data.posCashShift} settings={data.settings||settings} submitting={busy} onSubmit={submitPosOrder} onOpenShift={openShift} onCloseShift={closeShift} onMovement={cashMovement} onPrint={printDocument} onViewOrders={()=>navigate('orders')} />;
        if(page==='orders') return <OrdersPage orders={data.orders} filters={listFilters.orders} onFiltersChange={patch=>updateListFilters('orders',patch)} pagination={data.ordersPagination} onPageChange={setListPage} onRefresh={refresh} onDetails={orderDetails} onPrint={printDocument} onNewOrder={newOrder} onTransition={transition} onCancel={cancelOrder} />;
        if(page==='menu') return <MenuPage products={data.products} categories={data.categories} filters={listFilters.products} onFiltersChange={patch=>updateListFilters('products',patch)} pagination={data.productsPagination} onPageChange={setListPage} onRefresh={refresh} onProduct={product=>setModal({type:'product',product})} onProductStatus={changeProductStatus} onProductDelete={deleteProduct} onCategory={category=>setModal({type:'category',category})} onCategoryDelete={deleteCategory} />;
        if(page==='inventory') return <InventoryPage products={data.products} movements={data.movements} pagination={data.movementsPagination} onPageChange={setListPage} onRefresh={refresh} onAdjust={product=>setModal({type:'inventory',product})} />;
        if(page==='recipes') return <RecipesPage recipeData={data.recipes} onRefresh={refresh} onCreateIngredient={createIngredient} onUpdateIngredient={updateIngredient} onEditIngredient={ingredient=>setModal({type:'ingredient-edit',ingredient})} onDeleteIngredient={deleteIngredient} onAdjustIngredient={ingredient=>setModal({type:'ingredient-stock',ingredient})} onSaveRecipe={saveRecipe} />;
        if(page==='tables') return <TablesPage tables={data.tables} pagination={data.tablesPagination} onPageChange={setListPage} onRefresh={refresh} onCreate={createTable} onUpdate={updateTable} onEdit={table=>setModal({type:'table-edit',table})} onDelete={archiveTable} onRotate={rotateTable} onShowQr={table=>setModal({type:'qr',table})} />;
        if(page==='reservations') return <ReservationsPage reservations={data.reservations} tables={data.tables} pagination={data.reservationsPagination} onPageChange={setListPage} onRefresh={refresh} onCreate={createReservation} onEdit={reservation=>setModal({type:'reservation-edit',reservation})} onDelete={deleteReservation} onTransition={transitionReservation} />;
        if(page==='customers') return <CustomersPage feedback={data.feedback} pagination={data.feedbackPagination} onPageChange={setListPage} onRefresh={refresh} onRespond={respondFeedback} />;
        if(page==='expenses') return <ExpensesPage expenses={data.expenses} pagination={data.expensesPagination} onPageChange={setListPage} onRefresh={refresh} onCreate={createExpense} onEdit={expense=>setModal({type:'expense-edit',expense})} onDelete={deleteExpense} />;
        if(page==='reports') return <ReportsPage report={data.report} onRefresh={runReport} />;
        if(page==='settings') return <SettingsPage settings={data.settings} onRefresh={refresh} onSave={saveSettings} />;
        if(page==='media') return <MediaManager onNavigate={navigate} />;
        return <Empty />;
    };

    return <div className="dashboard-app" dir="rtl">
        <aside className={'sidebar '+(mobileNav?'sidebar-open':'')}>
            <button type="button" className="brand-lockup" onClick={()=>navigate('overview')}><span className="brand-symbol">خ</span><span><strong>خانه گیلاسی</strong><small>کافه و رستوران</small></span><span className="brand-status" title="اتصال به سرور" /></button>
            <div className="sidebar-caption">فضای کاری</div><div className="workspace"><span className="workspace-avatar">KG</span><span><b>شعبه مرکزی</b><small>پنل عملیات و صندوق</small></span></div>
            <div className="sidebar-caption">مدیریت روزانه</div>
            <nav className="side-nav" aria-label="منوی داشبورد">{navItems.map(([key,label,icon])=><button type="button" key={key} className={(page===key?'active ':'')+'nav-item'} onClick={()=>navigate(key)}><Icon>{icon}</Icon><span>{label}</span>{key==='orders'&&data.orders&&<b className="nav-count">{numberFa(data.orders.filter(o=>!['delivered','cancelled'].includes(o.status)).length)}</b>}</button>)}</nav>
            <div className="sidebar-bottom"><div className="help-card"><span>✦</span><b>وضعیت سامانه</b><p>هر صفحه با API محافظت‌شده‌ی سرور کار می‌کند. خطاها در رابط نشان داده می‌شوند.</p><button type="button" onClick={refresh}>تازه‌سازی داده‌ها ←</button></div><form method="POST" action="/admin/logout" className="op-logout"><input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content || ''} /><button className="user-card" type="submit"><span className="user-avatar">م</span><span><b>خروج از پنل</b><small>پایان نشست امن</small></span><span className="user-menu">↪</span></button></form></div>
        </aside>
        {mobileNav&&<button type="button" aria-label="بستن منو" className="mobile-overlay" onClick={()=>setMobileNav(false)}/>}
        <main className="main-shell">
            <div className="topbar"><button type="button" className="mobile-menu-button" onClick={()=>setMobileNav(true)} aria-label="بازکردن منو">☰</button><div className="breadcrumb">خانه گیلاسی <span>/</span> {navItems.find(n=>n[0]===page)?.[1]||'مدیریت'}</div><div className="topbar-right"><span className={'connection-state '+(error?'op-connection-error':'')}><i/>{error?'خطا در ارتباط':'اتصال به سامانه'}</span><span className="topbar-date">{new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-arabext',{weekday:'long',day:'numeric',month:'long',timeZone:'Asia/Tehran'}).format(new Date())}</span><button type="button" className="icon-button" aria-label="تازه‌سازی صفحه" onClick={refresh}>↻</button><span className="topbar-avatar">KG</span></div></div>
            {error&&<div className="op-global-error" role="alert"><span>{error}</span><Button onClick={refresh}>تلاش دوباره</Button></div>}
            {loading&&<div className="op-loading op-loading-line"><span className="op-spinner"/>در حال دریافت اطلاعات از سرور…</div>}
            {renderPage()}
            <footer className="app-footer"><span>خانه گیلاسی <span className="footer-dot">·</span> پنل عملیات رستوران</span><span>نسخه متصل به API · مالی و موجودی با تاریخچه رویداد</span></footer>
        </main>
        {modal?.type==='product'&&<ProductModal key={modal.product?.id||'new'} product={modal.product} categories={data.categories||[]} onClose={()=>setModal(null)} onSave={createProduct}/>}
        {modal?.type==='category'&&<CategoryModal key={modal.category?.id || 'new'} category={modal.category} onClose={()=>setModal(null)} onSave={(payload,category)=>category?updateCategory(category,payload):createCategory(payload)}/>}
        {modal?.type==='table-edit'&&<TableEditModal key={modal.table?.id} table={modal.table} onClose={()=>setModal(null)} onSave={(payload)=>editTable(modal.table,payload)}/>}
        {modal?.type==='payment'&&<PaymentModal order={modal.order} cashShiftOpen={Boolean(data.cashShift?.status==='open')} onClose={()=>setModal(null)} onSubmit={submitPayment}/>}
        {modal?.type==='cancel'&&<CancelModal order={modal.order} onClose={()=>setModal(null)} onSubmit={submitCancel}/>}
        {modal?.type==='refund'&&<RefundModal payment={modal.payment} orderId={modal.orderId} onClose={()=>setModal(null)} onSubmit={submitRefund}/>}
        {modal?.type==='details'&&<OrderDetailsModal order={detailOrder} onClose={()=>setModal(null)} onPrint={printDocument} onRefund={showRefund}/>}
        {modal?.type==='inventory'&&<InventoryModal product={modal.product} products={data.products||[]} onClose={()=>setModal(null)} onSubmit={adjustInventory}/>}
        {modal?.type==='ingredient-stock'&&<IngredientStockModal ingredient={modal.ingredient} onClose={()=>setModal(null)} onSubmit={payload=>submitIngredientStock(modal.ingredient,payload)}/>}
        {modal?.type==='ingredient-edit'&&<IngredientEditModal ingredient={modal.ingredient} onClose={()=>setModal(null)} onSubmit={payload=>updateIngredient(modal.ingredient,payload)}/>}
        {modal?.type==='reservation-edit'&&<ReservationEditModal reservation={modal.reservation} tables={data.tables||[]} onClose={()=>setModal(null)} onSubmit={payload=>updateReservation(modal.reservation,payload)}/>}
        {modal?.type==='expense-edit'&&<ExpenseEditModal expense={modal.expense} onClose={()=>setModal(null)} onSubmit={payload=>updateExpense(modal.expense,payload)}/>} 
        {modal?.type==='qr'&&<QRCodeModal table={modal.table} onClose={()=>setModal(null)} />}
        {notice&&<div className="toast-message" role="status"><span>✓</span>{notice}<button type="button" aria-label="بستن پیام" onClick={()=>setNotice('')}>×</button></div>}
        {busy&&<div className="op-busy" aria-live="polite"><span className="op-spinner"/>در حال ثبت عملیات…</div>}
    </div>;
}

function RecipesPage({ recipeData, onRefresh, onCreateIngredient, onUpdateIngredient, onEditIngredient, onDeleteIngredient, onAdjustIngredient, onSaveRecipe }) {
    const ingredients = recipeData?.ingredients || [];
    const products = recipeData?.products || [];
    const [ingredientForm, setIngredientForm] = useState({ name: '', unit: 'g', stock_quantity: '0', low_stock_threshold: '', cost_per_unit: '0' });
    const [selectedProductId, setSelectedProductId] = useState('');
    const [recipeRows, setRecipeRows] = useState([]);
    const [saving, setSaving] = useState(false);
    const selectedProduct = products.find(product => String(product.id) === selectedProductId) || null;

    useEffect(() => {
        if (!selectedProductId && products.length) setSelectedProductId(String(products[0].id));
    }, [products, selectedProductId]);

    useEffect(() => {
        const product = products.find(item => String(item.id) === selectedProductId);
        setRecipeRows((product?.recipe_ingredients || []).map(ingredient => ({
            ingredient_id: String(ingredient.id),
            quantity: String(ingredient.pivot?.quantity ?? ingredient.pivot?.quantity ?? '1'),
        })));
    }, [selectedProductId, products]);

    const recipeCost = recipeRows.reduce((total, row) => {
        const ingredient = ingredients.find(item => String(item.id) === String(row.ingredient_id));
        return total + (Number(row.quantity || 0) * Number(ingredient?.cost_per_unit || 0));
    }, 0);
    const margin = Number(selectedProduct?.base_price || 0) - recipeCost;

    const addIngredient = async event => {
        event.preventDefault();
        const ok = await onCreateIngredient({
            name: ingredientForm.name.trim(), unit: ingredientForm.unit,
            stock_quantity: Number(ingredientForm.stock_quantity || 0),
            low_stock_threshold: ingredientForm.low_stock_threshold === '' ? null : Number(ingredientForm.low_stock_threshold),
            cost_per_unit: Number(ingredientForm.cost_per_unit || 0), is_active: true,
        });
        if (ok) setIngredientForm({ name: '', unit: 'g', stock_quantity: '0', low_stock_threshold: '', cost_per_unit: '0' });
    };

    const toggleIngredient = ingredient => onUpdateIngredient(ingredient, {
        name: ingredient.name, unit: ingredient.unit,
        low_stock_threshold: ingredient.low_stock_threshold === null ? null : Number(ingredient.low_stock_threshold),
        cost_per_unit: Number(ingredient.cost_per_unit), is_active: !ingredient.is_active,
    });

    const save = async () => {
        if (!selectedProduct) return;
        setSaving(true);
        try {
            await onSaveRecipe(selectedProduct.id, recipeRows.filter(row => row.ingredient_id && Number(row.quantity) > 0)
                .map(row => ({ ingredient_id: Number(row.ingredient_id), quantity: Number(row.quantity) })));
        } finally {
            setSaving(false);
        }
    };

    return <div className="page-content">
        <PageHeader title="رسپی و مواد اولیه" subtitle="هر محصول را به مواد واقعی وصل کن تا مصرف انبار و بهای مواد هنگام ثبت سفارش به‌صورت خودکار محاسبه شود." />
        <div className="recipe-help"><b>راهنمای سریع</b><p>اول مواد اولیه را با موجودی و هزینه خرید وارد کن؛ سپس محصول را انتخاب کن و مقدار هر ماده برای یک پرس را بنویس. واحدها باید پایه و ثابت باشند: گرم، میلی‌لیتر یا عدد. محصولی که رسپی ندارد فقط از موجودی خود محصول استفاده می‌کند.</p></div>
        <section className="panel recipe-create-panel">
            <div className="panel-heading"><div><h2>＋ ماده اولیه جدید</h2><p>مثلاً دانه قهوه، شیر، آرد، شربت یا لیوان بیرون‌بر.</p></div></div>
            <form className="op-form" onSubmit={addIngredient}>
                <div className="op-form-grid">
                    <Field label="نام ماده اولیه"><input value={ingredientForm.name} onChange={e=>setIngredientForm(s=>({...s,name:e.target.value}))} maxLength="150" required placeholder="مثلاً دانه قهوه" /></Field>
                    <Field label="واحد پایه" hint="این واحد در رسپی و موجودی تغییرناپذیر می‌ماند."><select value={ingredientForm.unit} onChange={e=>setIngredientForm(s=>({...s,unit:e.target.value}))}><option value="g">گرم (g)</option><option value="ml">میلی‌لیتر (ml)</option><option value="pcs">عدد (pcs)</option></select></Field>
                    <Field label="موجودی اولیه" hint="تا ۳ رقم اعشار؛ مثلاً 2500 گرم."><input type="number" min="0" max="99999999999.999" step="0.001" value={ingredientForm.stock_quantity} onChange={e=>setIngredientForm(s=>({...s,stock_quantity:e.target.value}))} required /></Field>
                    <Field label="هشدار کمبود" hint="وقتی موجودی به این مقدار یا کمتر برسد، کمبود نشان داده می‌شود."><input type="number" min="0" step="0.001" value={ingredientForm.low_stock_threshold} onChange={e=>setIngredientForm(s=>({...s,low_stock_threshold:e.target.value}))} placeholder="اختیاری" /></Field>
                    <Field label={'هزینه هر واحد پایه ('+currencyLabel()+')'} hint={'اگر یک کیلو قهوه ۴۰۰٬۰۰۰ '+currencyLabel()+' است، هزینه هر گرم را اینجا وارد کن.'}><input type="number" min="0" max="1000000000" value={ingredientForm.cost_per_unit} onChange={e=>setIngredientForm(s=>({...s,cost_per_unit:e.target.value}))} required /></Field>
                </div>
                <div className="op-form-actions"><Button kind="primary" type="submit">ثبت ماده اولیه</Button></div>
            </form>
        </section>
        <section className="panel">
            <div className="panel-heading"><div><h2>مواد اولیه ثبت‌شده</h2><p>{numberFa(ingredients.length)} ماده · هزینه بر اساس واحد پایه ثبت‌شده</p></div><Button onClick={onRefresh}>↻ تازه‌سازی</Button></div>
            {ingredients.length ? <TableWrap><thead><tr><th>ماده</th><th>واحد پایه</th><th>موجودی فعلی</th><th>هشدار کمبود</th><th>هزینه واحد</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
                {ingredients.map(item=><tr key={item.id}><td><b>{item.name}</b>{String(item.seed_key||'').startsWith('demo-ingredient:')&&<small className="op-subtext">داده نمونه</small>}</td><td>{({g:'گرم',ml:'میلی‌لیتر',pcs:'عدد'})[item.unit]||item.unit}</td><td>{numberFa(item.stock_quantity)}</td><td>{item.low_stock_threshold===null?'تعریف نشده':numberFa(item.low_stock_threshold)}</td><td>{money(item.cost_per_unit)}</td><td><Pill value={item.is_active?'active':'inactive'} dictionary={{active:'فعال',inactive:'غیرفعال'}} /></td><td><div className="op-actions"><Button onClick={()=>onAdjustIngredient(item)}>ورود / خروج</Button><Button onClick={()=>onEditIngredient(item)}>ویرایش</Button><Button onClick={()=>toggleIngredient(item)}>{item.is_active?'غیرفعال‌کردن':'فعال‌کردن'}</Button>{String(item.seed_key||'').startsWith('demo-ingredient:')&&<Button kind="danger" disabled={Number(item.products_count||0)>0||Number(item.movements_count||0)>0} onClick={()=>onDeleteIngredient(item)}>حذف</Button>}</div></td></tr>)}
            </tbody></TableWrap> : <Empty>هنوز ماده‌ای ثبت نشده است. مواد را از فرم بالا اضافه کن.</Empty>}
        </section>
        <section className="panel recipe-editor-panel">
            <div className="panel-heading"><div><h2>ساخت رسپی محصول</h2><p>مقدار هر ردیف برای تولید یک عدد یا یک پرس از محصول است.</p></div></div>
            {!products.length ? <Empty>ابتدا از بخش «منو و محصولات» یک محصول بساز.</Empty> : <>
                <Field label="محصول موردنظر" hint="رسپی به همین محصول وصل می‌شود؛ سفارش از منو، صندوق یا QR آن را مصرف می‌کند."><select value={selectedProductId} onChange={e=>setSelectedProductId(e.target.value)}>{products.map(product=><option key={product.id} value={product.id}>{product.name} — {money(product.base_price)}</option>)}</select></Field>
                <div className="recipe-row-list">
                    <div className="recipe-row recipe-row-header"><span>ماده اولیه</span><span>مقدار برای یک پرس</span><span>هزینه تخمینی</span><span></span></div>
                    {recipeRows.map((row,index)=>{
                        const ingredient=ingredients.find(item=>String(item.id)===String(row.ingredient_id));
                        const lineCost=Number(row.quantity||0)*Number(ingredient?.cost_per_unit||0);
                        return <div className="recipe-row" key={index}>
                            <label className="recipe-ingredient-select"><span className="sr-only">ماده اولیه ردیف {index+1}</span><select value={row.ingredient_id} onChange={e=>setRecipeRows(rows=>rows.map((item,i)=>i===index?{...item,ingredient_id:e.target.value}:item))} required><option value="">انتخاب ماده</option>{ingredients.filter(item=>item.is_active).map(item=><option key={item.id} value={item.id}>{item.name} · {({g:'گرم',ml:'میلی‌لیتر',pcs:'عدد'})[item.unit]||item.unit}</option>)}</select></label>
                            <label className="recipe-quantity"><span className="sr-only">مقدار برای هر پرس</span><input type="number" min="0.001" step="0.001" value={row.quantity} onChange={e=>setRecipeRows(rows=>rows.map((item,i)=>i===index?{...item,quantity:e.target.value}:item))} required /></label>
                            <b className="recipe-line-cost">{money(lineCost)}</b>
                            <Button kind="danger" onClick={()=>setRecipeRows(rows=>rows.filter((_,i)=>i!==index))}>حذف</Button>
                        </div>;
                    })}
                    {ingredients.length===0&&<p className="op-note">برای ساخت رسپی ابتدا مواد اولیه را اضافه کن.</p>}
                    <div className="recipe-row-actions"><Button disabled={!ingredients.some(item=>item.is_active)} onClick={()=>setRecipeRows(rows=>[...rows,{ingredient_id:'',quantity:'1'}])}>＋ افزودن ماده به رسپی</Button><Button kind="primary" disabled={!selectedProduct || saving} onClick={save}>{saving?'در حال ذخیره…':'ذخیره رسپی'}</Button></div>
                </div>
                <div className="recipe-cost-summary"><div><small>بهای مواد برای یک پرس (تخمینی)</small><b>{money(recipeCost)}</b></div><div><small>قیمت فروش فعلی</small><b>{money(selectedProduct?.base_price)}</b></div><div><small>مانده پس از مواد اولیه</small><b className={margin<0?'recipe-negative':''}>{money(margin)}</b></div></div>
                <p className="op-note">این «سود خالص» نیست؛ فقط قیمت فروش منهای هزینه مواد واردشده است. دستمزد، اجاره، انرژی، ضایعات و مالیات باید جداگانه محاسبه شوند. اگر رسپی را خالی ذخیره کنی، اتصال مواد این محصول پاک می‌شود.</p>
            </>}
        </section>
    </div>;
}

function IngredientEditModal({ ingredient, onClose, onSubmit }) {
    const [form, setForm] = useState({
        name: ingredient.name || '',
        unit: ingredient.unit || 'g',
        low_stock_threshold: ingredient.low_stock_threshold == null ? '' : String(ingredient.low_stock_threshold),
        cost_per_unit: String(ingredient.cost_per_unit || 0),
        is_active: Boolean(ingredient.is_active),
    });
    const set = (key, value) => setForm(current => ({ ...current, [key]: value }));
    const hasHistory = Number(ingredient.products_count || 0) > 0 || Number(ingredient.movements_count || 0) > 0;
    return <Modal title={'ویرایش ماده اولیه · '+ingredient.name} eyebrow="INGREDIENT DETAILS" onClose={onClose}>
        <form className="op-form" onSubmit={event => {
            event.preventDefault();
            onSubmit({
                ...form,
                name: form.name.trim(),
                low_stock_threshold: form.low_stock_threshold === '' ? null : Number(form.low_stock_threshold),
                cost_per_unit: Number(form.cost_per_unit),
            });
        }}>
            <Field label="نام ماده"><input value={form.name} onChange={event=>set('name',event.target.value)} maxLength="150" required /></Field>
            <Field label="واحد پایه"><select value={form.unit} onChange={event=>set('unit',event.target.value)}><option value="g">گرم (g)</option><option value="ml">میلی‌لیتر (ml)</option><option value="pcs">عدد (pcs)</option></select></Field>
            <Field label="هشدار کمبود"><input type="number" min="0" step="0.001" value={form.low_stock_threshold} onChange={event=>set('low_stock_threshold',event.target.value)} /></Field>
            <Field label={'هزینه هر واحد ('+currencyLabel()+')'}><input type="number" min="0" max="1000000000" value={form.cost_per_unit} onChange={event=>set('cost_per_unit',event.target.value)} required /></Field>
            <Field label="وضعیت"><select value={form.is_active?'active':'inactive'} onChange={event=>set('is_active',event.target.value==='active')}><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></Field>
            <p className="op-note">موجودی فقط از مسیر ورود و خروج اصلاح می‌شود. {hasHistory?'این ماده سابقه رسپی یا گردش انبار دارد؛ حذف آن برای حفظ تاریخچه غیرفعال است.':'این ماده در حال حاضر سابقه وابسته‌ای ندارد و در صورت نیاز قابل حذف است.'}</p>
            <div className="op-form-actions"><Button onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ذخیره تغییرات</Button></div>
        </form>
    </Modal>;
}

function IngredientStockModal({ ingredient, onClose, onSubmit }) {
    const [direction,setDirection] = useState('in');
    const [quantity,setQuantity] = useState('1');
    const [reason,setReason] = useState('');
    const [requestKey] = useState(() => idempotencyKey());
    return <Modal title={'گردش موجودی · '+ingredient.name} eyebrow="INGREDIENT STOCK MOVEMENT" onClose={onClose}>
        <div className="op-payment-summary"><span>موجودی فعلی</span><b>{numberFa(ingredient.stock_quantity)} {({g:'گرم',ml:'میلی‌لیتر',pcs:'عدد'})[ingredient.unit]||ingredient.unit}</b></div>
        <form className="op-form" onSubmit={event=>{event.preventDefault();onSubmit({direction,quantity:Number(quantity),reason:reason.trim(),idempotency_key:requestKey});}}>
            <Field label="نوع حرکت"><select value={direction} onChange={e=>setDirection(e.target.value)}><option value="in">ورود / خرید ماده</option><option value="out">خروج / ضایعات / مصرف دستی</option></select></Field>
            <Field label={'مقدار ('+ingredient.unit+')'}><input type="number" min="0.001" step="0.001" value={quantity} onChange={e=>setQuantity(e.target.value)} required /></Field>
            <Field label="علت (اجباری)"><input value={reason} onChange={e=>setReason(e.target.value)} minLength="3" maxLength="500" required placeholder="مثلاً خرید هفتگی یا ثبت ضایعات" /></Field>
            <p className="op-note">ثبت این فرم موجودی را تغییر می‌دهد و ردیف قابل پیگیری در گردش مواد اولیه ایجاد می‌کند.</p>
            <div className="op-form-actions"><Button onClick={onClose}>انصراف</Button><Button kind="primary" type="submit">ثبت گردش</Button></div>
        </form>
    </Modal>;
}

function JalaliCalendarPopover({ value, onSelect, onClose }) {
    const today = currentJalaliDate().split('/').map(Number);
    const initial = (value ? gregorianDateToJalali(value) : currentJalaliDate()).split('/').map(Number);
    const [view, setView] = useState(() => ({
        year: initial[0] || today[0],
        month: initial[1] || today[1],
    }));

    useEffect(() => {
        if (!value) return;
        const parts = gregorianDateToJalali(value).split('/').map(Number);
        if (parts[0] && parts[1]) setView({ year: parts[0], month: parts[1] });
    }, [value]);

    const info = jalaliMonthInfo(view.year, view.month);
    const selected = value ? String(value).slice(0, 10) : '';
    const cells = Array.from({ length: 42 }, (_, index) => {
        const day = index - info.startOffset + 1;
        if (day < 1 || day > info.daysInMonth) return null;
        const iso = jalaliDateToGregorian(view.year + '/' + String(view.month).padStart(2, '0') + '/' + String(day).padStart(2, '0'));
        return { day, iso };
    });

    return <div className="jalali-calendar-popover" role="dialog" aria-label="انتخاب تاریخ شمسی">
        <div className="jalali-calendar-heading">
            <button type="button" aria-label="ماه قبل" onClick={() => setView(current => shiftJalaliMonth(current.year, current.month, -1))}>‹</button>
            <strong>{info.monthName} {toPersianDigits(view.year)}</strong>
            <button type="button" aria-label="ماه بعد" onClick={() => setView(current => shiftJalaliMonth(current.year, current.month, 1))}>›</button>
        </div>
        <div className="jalali-calendar-weekdays">
            {JALALI_WEEKDAYS.map((day, index) => <span key={index}>{day}</span>)}
        </div>
        <div className="jalali-calendar-days">
            {cells.map((cell, index) => cell
                ? <button key={cell.iso} type="button" className={cell.iso === selected ? 'is-selected' : ''} aria-pressed={cell.iso === selected} onClick={() => { onSelect(cell.iso); onClose(); }}>{toPersianDigits(cell.day)}</button>
                : <span key={'empty-' + index} aria-hidden="true" />)}
        </div>
    </div>;
}

function JalaliDateInput({ value, onChange, placeholder = 'انتخاب تاریخ شمسی', ariaLabel = 'تاریخ شمسی' }) {
    const [open, setOpen] = useState(false);
    const text = value ? toPersianDigits(gregorianDateToJalali(value)) : '';
    return <div className="jalali-picker">
        <input className="jalali-picker-trigger" type="text" readOnly value={text} placeholder={placeholder} aria-label={ariaLabel} aria-haspopup="dialog" aria-expanded={open} onClick={() => setOpen(current => !current)} />
        {open && <JalaliCalendarPopover value={value} onSelect={onChange} onClose={() => setOpen(false)} />}
    </div>;
}

function JalaliDateTimeInput({ value, onChange, required = false }) {
    const selectedDate = value ? String(value).slice(0, 10) : '';
    const [open, setOpen] = useState(false);
    const [time, setTime] = useState(() => value && String(value).match(/[T ](\d{2}:\d{2})/)?.[1] || '18:00');

    useEffect(() => {
        setTime(value && String(value).match(/[T ](\d{2}:\d{2})/)?.[1] || '18:00');
    }, [value]);

    const displayDate = selectedDate ? toPersianDigits(gregorianDateToJalali(selectedDate)) : '';
    return <div className="jalali-datetime-picker">
        <div className="jalali-picker">
            <input className="jalali-picker-trigger" type="text" readOnly required={required} value={displayDate} placeholder="انتخاب تاریخ شمسی" aria-label="تاریخ شمسی رزرو" aria-haspopup="dialog" aria-expanded={open} onClick={() => setOpen(current => !current)} />
            {open && <JalaliCalendarPopover value={selectedDate} onSelect={date => onChange(date + 'T' + (time || '18:00'))} onClose={() => setOpen(false)} />}
        </div>
        <div className="jalali-time-field"><span>ساعت</span>
            <input type="time" required={required} value={time} onChange={event => { const next = event.target.value; setTime(next); if (selectedDate && next) onChange(selectedDate + 'T' + next); }} />
        </div>
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



function QRCodeModal({ table, onClose }) {
    const [qrDataUrl, setQrDataUrl] = useState('');
    const [qrError, setQrError] = useState('');
    const [linkCopied, setLinkCopied] = useState(false);
    const absoluteUrl = useMemo(() => {
        try { return new URL(table.menu_url, window.location.origin).href; }
        catch { return table.menu_url; }
    }, [table.menu_url]);

    useEffect(() => {
        let active = true;
        setQrDataUrl('');
        setQrError('');
        QRCode.toDataURL(absoluteUrl, {
            errorCorrectionLevel: 'H',
            margin: 2,
            width: 420,
            color: { dark: '#211f1c', light: '#ffffff' },
        }).then(url => { if (active) setQrDataUrl(url); })
          .catch(() => { if (active) setQrError('ساخت تصویر QR ناموفق شد. لینک مستقیم منو همچنان قابل استفاده است.'); });
        return () => { active = false; };
    }, [absoluteUrl]);

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(absoluteUrl);
            setLinkCopied(true);
        } catch {
            window.prompt('لینک منوی این میز را کپی کن:', absoluteUrl);
        }
    };

    const printLabel = () => {
        if (!qrDataUrl) return;
        const popup = window.open('', '_blank', 'width=520,height=650');
        if (!popup) {
            setQrError('مرورگر پنجره چاپ را مسدود کرده است؛ Pop-up را برای این سایت فعال کن.');
            return;
        }
        const safeName = escapeHtml(table.name);
        popup.document.open();
        popup.document.write('<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>QR '+safeName+'</title><style>@page{size:A6 portrait;margin:8mm}body{font-family:Tahoma,Arial,sans-serif;text-align:center;color:#211f1c}h1{font-size:22px;margin:8px 0}p{font-size:12px;overflow-wrap:anywhere;line-height:1.8}img{width:90mm;height:90mm;max-width:100%;object-fit:contain}footer{margin-top:12px;font-size:11px}</style></head><body><h1>منوی دیجیتال</h1><h2>'+safeName+'</h2><img alt="QR منو" src="'+qrDataUrl+'"><p>دوربین گوشی را روی QR بگیر و منو را باز کن.</p><p>'+escapeHtml(absoluteUrl)+'</p><footer>با انتخاب شما، سفارش ثبت می‌شود.</footer><script>window.addEventListener("load",function(){window.focus();setTimeout(function(){window.print();},250);});</script></body></html>');
        popup.document.close();
    };

    return <Modal title={'QR منوی '+table.name} eyebrow="TABLE QR / PRINT LABEL" onClose={onClose}>
        <div className="op-qr-preview">
            {qrDataUrl ? <img src={qrDataUrl} alt={'QR منوی '+table.name} /> : <div className="op-qr-placeholder">{qrError || 'در حال ساخت QR…'}</div>}
            <h3>{table.name}</h3>
            <p>با اسکن این کد، مشتری منوی همین میز را می‌بیند و سفارش به همین میز متصل می‌شود.</p>
        </div>
        <Field label="لینک منوی اختصاصی"><input readOnly value={absoluteUrl} onFocus={e=>e.target.select()} /></Field>
        {qrError && <Errors message={qrError} />}
        <div className="op-form-actions op-qr-actions">
            <Button kind="primary" disabled={!qrDataUrl} onClick={printLabel}>چاپ برچسب QR</Button>
            <Button onClick={copyLink}>{linkCopied?'لینک کپی شد':'کپی لینک منو'}</Button>
            <Button onClick={()=>window.open(absoluteUrl,'_blank','noopener,noreferrer')}>پیش‌نمایش منو</Button>
        </div>
        <p className="op-note">اگر QR روی گوشی باز نشد، APP_URL باید دامنه یا IP قابل دسترسی از شبکه مشتریان باشد؛ localhost یا آدرس داخلی سرور از گوشی باز نمی‌شود.</p>
    </Modal>;
}
