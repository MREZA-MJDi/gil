import React, { useMemo, useState } from 'react';

const navItems = [
  ['overview', 'نمای کلی', '◫'],
  ['orders', 'سفارش‌ها', '▤'],
  ['pos', 'صندوق و پرداخت', '◈'],
  ['menu', 'منو و محصولات', '▦'],
  ['tables', 'میزها و QR', '▧'],
  ['reservations', 'رزروها', '◷'],
  ['customers', 'مشتریان و بازخورد', '♧'],
  ['expenses', 'هزینه‌ها', '↗'],
  ['reports', 'گزارش‌ها', '▥'],
  ['settings', 'تنظیمات', '⚙'],
];

const initialOrders = [
  { id: 'KG-2408', channel: 'میز ۰۴', customer: 'میز داخل سالن', items: '۲ آیتم', total: 485000, status: 'در حال آماده‌سازی', payment: 'پرداخت نشده', time: '۱۲:۴۸', kind: 'dinein' },
  { id: 'KG-2407', channel: 'آنلاین', customer: 'سفارش آنلاین', items: '۴ آیتم', total: 1260000, status: 'آماده تحویل', payment: 'پرداخت‌شده', time: '۱۲:۴۲', kind: 'online' },
  { id: 'KG-2406', channel: 'میز ۰۲', customer: 'میز داخل سالن', items: '۳ آیتم', total: 720000, status: 'جدید', payment: 'پرداخت نشده', time: '۱۲:۳۷', kind: 'dinein' },
  { id: 'KG-2405', channel: 'بیرون‌بر', customer: 'سفارش حضوری', items: '۱ آیتم', total: 195000, status: 'تحویل‌شده', payment: 'پرداخت‌شده', time: '۱۲:۲۹', kind: 'takeaway' },
  { id: 'KG-2404', channel: 'آنلاین', customer: 'سفارش آنلاین', items: '۲ آیتم', total: 390000, status: 'لغوشده', payment: 'بازپرداخت لازم', time: '۱۲:۱۸', kind: 'online' },
];

const products = [
  { name: 'لاته کلاسیک', category: 'قهوه گرم', price: 185000, state: 'موجود', tone: 'latte', code: 'COF-01' },
  { name: 'پنکیک بری', category: 'صبحانه', price: 325000, state: 'موجود', tone: 'pancake', code: 'BRK-04' },
  { name: 'آیس آمریکانو', category: 'قهوه سرد', price: 155000, state: 'موجودی کم', tone: 'iced', code: 'COF-08' },
  { name: 'تست آووکادو', category: 'صبحانه', price: 295000, state: 'ناموجود', tone: 'toast', code: 'BRK-02' },
];

const money = (value) => new Intl.NumberFormat('fa-IR').format(value) + ' تومان';

function Icon({ children }) { return <span className="ui-icon" aria-hidden="true">{children}</span>; }

function StatCard({ label, value, sub, icon, trend, tone = '' }) {
  return <article className="stat-card">
    <div className="stat-top"><span className={'stat-icon ' + tone}><Icon>{icon}</Icon></span><span className="stat-trend">{trend}</span></div>
    <div className="stat-label">{label}</div>
    <div className="stat-value">{value}</div>
    <div className="stat-sub">{sub}</div>
  </article>;
}

function Status({ value }) {
  const cls = value.includes('لغو') || value.includes('بازپرداخت') || value.includes('ناموجود') ? 'danger'
    : value.includes('آماده') || value.includes('تحویل') || value.includes('پرداخت‌شده') || value === 'موجود' ? 'success'
    : value.includes('در حال') || value.includes('کم') ? 'warning' : 'neutral';
  return <span className={'status-pill ' + cls}><i />{value}</span>;
}

function Header({ title, subtitle, onNewOrder }) {
  return <header className="page-header">
    <div><div className="eyebrow">KHANE GILASI / OPERATIONS</div><h1>{title}</h1><p>{subtitle}</p></div>
    <div className="header-actions"><button className="icon-button" aria-label="اعلان‌ها" title="اعلان‌ها"><Icon>♧</Icon><b className="notify-dot" /></button><button className="secondary-button" onClick={() => window.print()}>چاپ گزارش ↗</button><button className="primary-button" onClick={onNewOrder}>＋ سفارش جدید</button></div>
  </header>;
}

function Overview({ orders, onSelect, onNewOrder }) {
  const active = orders.filter(o => !['تحویل‌شده', 'لغوشده'].includes(o.status));
  return <div className="page-content">
    <Header title="صبح بخیر، مدیر جان" subtitle="خلاصه‌ای از نبض خانه گیلاسی؛ همه‌چیز تحت کنترل است." onNewOrder={onNewOrder} />
    <div className="welcome-strip"><div><span className="live-dot" /> <strong>وضعیت مجموعه: فعال</strong><span>·</span><span>شیفت ظهر در جریان است</span></div><button onClick={() => onSelect('pos')}>مشاهده صندوق <span>←</span></button></div>
    <section className="stats-grid">
      <StatCard label="فروش ثبت‌شده امروز" value={money(8425000)} sub="نسبت به روز قبل" trend="↑ ۱۲.۸٪" icon="↗" tone="coral" />
      <StatCard label="دریافتی واقعی امروز" value={money(6910000)} sub="نقدی، کارت‌خوان و آنلاین" trend="۸۲٪ فروش" icon="◈" tone="green" />
      <StatCard label="سفارش‌های امروز" value="۴۸" sub="۷ سفارش در جریان" trend="+۶ سفارش" icon="▤" tone="purple" />
      <StatCard label="صورتحساب باز" value={money(1515000)} sub="۳ سفارش تسویه‌نشده" trend="نیازمند پیگیری" icon="◷" tone="amber" />
    </section>
    <section className="overview-grid">
      <article className="panel sales-panel">
        <div className="panel-heading"><div><h2>روند فروش</h2><p>عملکرد فروش در هفت روز اخیر</p></div><select aria-label="دوره گزارش" defaultValue="week"><option value="week">۷ روز اخیر</option><option value="month">این ماه</option></select></div>
        <div className="chart-legend"><span><i className="legend-dot main-dot" /> فروش ثبت‌شده</span><span><i className="legend-dot muted-dot" /> هفته قبل</span></div>
        <div className="sales-chart" role="img" aria-label="نمودار نمایشی روند فروش هفتگی">
          <div className="chart-y"><span>۱۰م</span><span>۷.۵م</span><span>۵م</span><span>۲.۵م</span><span>۰</span></div>
          <div className="chart-main"><div className="chart-gridlines"><i/><i/><i/><i/><i/></div><svg viewBox="0 0 700 230" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="salesFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stopColor="#bb513b" stopOpacity=".24"/><stop offset="100%" stopColor="#bb513b" stopOpacity="0"/></linearGradient></defs><path d="M0 164 C40 158 55 112 100 128 S155 176 200 132 S255 98 300 112 S360 64 400 90 S460 130 500 74 S560 53 600 62 S660 18 700 36 L700 230 L0 230Z" fill="url(#salesFill)"/><path d="M0 164 C40 158 55 112 100 128 S155 176 200 132 S255 98 300 112 S360 64 400 90 S460 130 500 74 S560 53 600 62 S660 18 700 36" fill="none" stroke="#bb513b" strokeWidth="3" vectorEffect="non-scaling-stroke" strokeLinecap="round"/></svg><div className="chart-x"><span>شنبه</span><span>یکشنبه</span><span>دوشنبه</span><span>سه‌شنبه</span><span>چهارشنبه</span><span>پنجشنبه</span><span>جمعه</span></div></div>
        </div>
        <div className="chart-foot"><span>فروش امروز <strong>۸,۴۲۵,۰۰۰ تومان</strong></span><span>اطلاعات نمایشی برای پیش‌نمایش رابط</span></div>
      </article>
      <article className="panel channel-panel">
        <div className="panel-heading"><div><h2>کانال سفارش</h2><p>سهم سفارش‌ها به تفکیک روش</p></div><button className="subtle-link" onClick={() => onSelect('orders')}>جزئیات ←</button></div>
        <div className="donut-wrap"><div className="donut"><div><strong>۴۸</strong><small>سفارش</small></div></div><div className="donut-key"><div><i className="key-dinein" /><span>حضوری</span><b>۲۲</b></div><div><i className="key-online" /><span>آنلاین</span><b>۱۶</b></div><div><i className="key-takeaway" /><span>بیرون‌بر</span><b>۱۰</b></div></div></div>
        <div className="mini-note"><span className="note-symbol">✦</span><span>سفارش حضوری امروز بیشترین سهم را داشته است.</span></div>
      </article>
    </section>
    <section className="panel orders-panel">
      <div className="panel-heading"><div><h2>آخرین سفارش‌ها</h2><p>در جریان سفارش‌ها را بررسی و وضعیت را پیگیری کن.</p></div><button className="secondary-button" onClick={() => onSelect('orders')}>همه سفارش‌ها ←</button></div>
      <OrdersTable orders={orders.slice(0, 5)} onSelect={onSelect} />
    </section>
    <section className="bottom-grid">
      <article className="panel quick-panel"><div className="panel-heading"><div><h2>دسترسی سریع</h2><p>کارهای پرتکرار روزانه</p></div></div><div className="quick-grid"><button onClick={onNewOrder}><span className="quick-icon qi-red">＋</span><span><b>ثبت سفارش</b><small>سفارش حضوری جدید</small></span><em>←</em></button><button onClick={() => onSelect('pos')}><span className="quick-icon qi-green">◈</span><span><b>ثبت پرداخت</b><small>تسویه و صدور رسید</small></span><em>←</em></button><button onClick={() => onSelect('menu')}><span className="quick-icon qi-amber">▦</span><span><b>مدیریت منو</b><small>قیمت و موجودی محصول</small></span><em>←</em></button><button onClick={() => onSelect('tables')}><span className="quick-icon qi-purple">▧</span><span><b>میز و QR</b><small>مشاهده وضعیت میزها</small></span><em>←</em></button></div></article>
      <article className="panel attention-panel"><div className="panel-heading"><div><h2>نیازمند توجه</h2><p>مواردی که بهتر است بررسی شوند</p></div><span className="attention-count">۳</span></div><div className="attention-item"><span className="attention-icon amber-bg">◷</span><span><b>۳ صورتحساب باز</b><small>مجموع مانده {money(1515000)}</small></span><button onClick={() => onSelect('pos')}>بررسی</button></div><div className="attention-item"><span className="attention-icon red-bg">!</span><span><b>۲ بازخورد جدید</b><small>یک مورد منتظر پاسخ است</small></span><button onClick={() => onSelect('customers')}>مشاهده</button></div><div className="attention-item"><span className="attention-icon green-bg">✓</span><span><b>شیفت صندوق باز است</b><small>شروع شیفت · ۰۹:۰۰</small></span><button onClick={() => onSelect('pos')}>مدیریت</button></div></article>
    </section>
  </div>;
}

function OrdersTable({ orders, onSelect }) {
  return <div className="table-scroll"><table className="data-table"><thead><tr><th>شماره سفارش</th><th>کانال</th><th>آیتم</th><th>مبلغ سفارش</th><th>وضعیت آماده‌سازی</th><th>پرداخت</th><th>ساعت</th><th></th></tr></thead><tbody>{orders.map(order => <tr key={order.id} onClick={() => onSelect('orders')} className="click-row"><td className="mono">{order.id}</td><td>{order.channel}</td><td>{order.items}</td><td className="amount-cell">{money(order.total)}</td><td><Status value={order.status}/></td><td><Status value={order.payment}/></td><td>{order.time}</td><td><button className="row-more" aria-label="جزئیات سفارش">•••</button></td></tr>)}</tbody></table></div>;
}

function OrdersPage({ orders, onSelect }) {
  const [filter, setFilter] = useState('all');
  const [query, setQuery] = useState('');
  const filtered = orders.filter(o => (filter === 'all' || (filter === 'open' && !['تحویل‌شده','لغوشده'].includes(o.status)) || (filter === 'unpaid' && !o.payment.includes('پرداخت‌شده'))) && (o.id + o.channel + o.status).toLowerCase().includes(query.toLowerCase()));
  return <div className="page-content"><Header title="مدیریت سفارش‌ها" subtitle="سفارش‌های حضوری، بیرون‌بر و آنلاین را از یک جا دنبال کن." onNewOrder={onSelect}/><div className="toolbar"><div className="filter-tabs">{[['all','همه'],['open','در جریان'],['unpaid','تسویه‌نشده']].map(([key,label])=><button key={key} className={filter===key?'active':''} onClick={()=>setFilter(key)}>{label}</button>)}</div><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="جست‌وجوی شماره یا وضعیت سفارش..." /></div><div className="panel"><OrdersTable orders={filtered} onSelect={onSelect}/></div></div>;
}

function PosPage({ orders, onPay }) {
  const unpaid = orders.filter(o => !o.payment.includes('پرداخت‌شده') && o.status !== 'لغوشده');
  return <div className="page-content"><Header title="صندوق و پرداخت" subtitle="صورتحساب‌ها را بررسی کن، پرداخت ثبت کن و رسید چاپ بگیر." onNewOrder={() => window.alert('فرم سفارش جدید در مرحله اتصال به API اضافه می‌شود.')}/><div className="stats-grid compact-stats"><StatCard label="صورتحساب باز" value={unpaid.length.toLocaleString('fa-IR')} sub="سفارش نیازمند تسویه" icon="◷" tone="amber"/><StatCard label="دریافتی امروز" value={money(6910000)} sub="مقدار نمایشی" icon="◈" tone="green"/><StatCard label="بازپرداخت در انتظار" value="۱" sub="نیازمند بررسی دستی" icon="↶" tone="coral"/></div><div className="panel"><div className="panel-heading"><div><h2>صورتحساب‌های باز</h2><p>ثبت پرداخت این دموی رابط را تغییر می‌دهد؛ هنوز تراکنش واقعی بانکی نیست.</p></div></div><div className="table-scroll"><table className="data-table"><thead><tr><th>سفارش</th><th>کانال</th><th>مبلغ قابل تسویه</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>{unpaid.map(o=><tr key={o.id}><td className="mono">{o.id}</td><td>{o.channel}</td><td className="amount-cell">{money(o.total)}</td><td><Status value={o.payment}/></td><td><button className="table-action" onClick={()=>onPay(o.id)}>ثبت پرداخت آزمایشی</button> <button className="print-action" onClick={()=>window.print()}>چاپ</button></td></tr>)}</tbody></table>{!unpaid.length&&<div className="empty-state">صورتحساب بازی وجود ندارد.</div>}</div></div><div className="notice-box"><b>حالت نمایشی</b><p>تا زمان اتصال به API لاراول، پرداخت‌ها صرفاً در وضعیت محلی صفحه ثبت می‌شوند. اطلاعات بانکی یا پرداخت واقعی ایجاد نمی‌شود.</p></div></div>;
}

function MenuPage() {
  const [items, setItems] = useState(products);
  const [filter, setFilter] = useState('همه');
  const [query, setQuery] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [draft, setDraft] = useState({name:'',category:'صبحانه',price:''});
  const filtered = items.filter(p => (filter==='همه'||p.category===filter) && p.name.includes(query));
  const toggle = (code) => setItems(items.map(p => p.code===code ? {...p,state:p.state==='ناموجود'?'موجود':'ناموجود'}:p));
  const add = (e) => { e.preventDefault(); if(!draft.name.trim()||Number(draft.price)<=0)return; setItems([...items,{...draft,price:Number(draft.price),state:'موجود',tone:'toast',code:'NEW-'+Date.now()}]);setDraft({name:'',category:'صبحانه',price:''});setShowForm(false); };
  return <div className="page-content"><Header title="منو و محصولات" subtitle="محصولات، دسته‌بندی و وضعیت موجودی منو را مدیریت کن." onNewOrder={()=>setShowForm(!showForm)}/><div className="toolbar"><div className="filter-tabs">{['همه','صبحانه','قهوه گرم','قهوه سرد'].map(c=><button key={c} className={filter===c?'active':''} onClick={()=>setFilter(c)}>{c}</button>)}</div><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="نام محصول..." /></div>{showForm&&<form className="panel product-form" onSubmit={add}><div className="panel-heading"><div><h2>محصول جدید</h2><p>این فرم فعلاً داده‌ها را در مرورگر نگه می‌دارد.</p></div></div><label>نام محصول<input value={draft.name} onChange={e=>setDraft({...draft,name:e.target.value})} required /></label><label>دسته‌بندی<select value={draft.category} onChange={e=>setDraft({...draft,category:e.target.value})}><option>صبحانه</option><option>قهوه گرم</option><option>قهوه سرد</option></select></label><label>قیمت به تومان<input type="number" min="1" value={draft.price} onChange={e=>setDraft({...draft,price:e.target.value})} required /></label><button className="primary-button" type="submit">افزودن به پیش‌نمایش</button></form>}<div className="product-grid">{filtered.map(p=><article className="product-card" key={p.code}><div className={'product-art art-'+p.tone}><span className="product-category">{p.category}</span><span className="product-illustration">{p.tone==='latte'?'☕':p.tone==='pancake'?'◉':p.tone==='iced'?'▥':'◒'}</span><small>{p.code}</small></div><div className="product-info"><div><h3>{p.name}</h3><p>{p.code}</p></div><Status value={p.state}/><strong>{money(p.price)}</strong><button className="secondary-button full-button" onClick={()=>toggle(p.code)}>{p.state==='ناموجود'?'موجود کردن':'ناموجود کردن موقت'}</button></div></article>)}</div></div>;
}

function TablesPage() {
  const [tables, setTables] = useState(Array.from({length:12},(_,i)=>({number:i+1,state:[2,4,7].includes(i+1)?'دارای سفارش':[5,9].includes(i+1)?'رزرو شده':'آزاد',amount:[2,4,7].includes(i+1)?[485000,720000,295000][[2,4,7].indexOf(i+1)]:0})));
  const [showQr,setShowQr] = useState(null);
  const addTable = () => setTables([...tables,{number:Math.max(0,...tables.map(t=>t.number))+1,state:'آزاد',amount:0}]);
  return <div className="page-content"><Header title="میزها و QR" subtitle="وضعیت سالن را در یک نگاه ببین و میزهای جدید بساز." onNewOrder={addTable}/><div className="toolbar"><div className="legend-inline"><span><i className="free-dot"/> آزاد</span><span><i className="occupied-dot"/> دارای سفارش</span><span><i className="reserved-dot"/> رزرو شده</span></div><button className="secondary-button" onClick={()=>window.print()}>چاپ فهرست میزها</button></div><div className="tables-grid">{tables.map(t=><article className={'table-card '+(t.state==='آزاد'?'is-free':t.state==='رزرو شده'?'is-reserved':'is-occupied')} key={t.number}><div className="table-card-head"><span>میز</span><strong>{String(t.number).padStart(2,'0')}</strong><button onClick={()=>setShowQr(t.number)} aria-label="نمایش QR">▦</button></div><div className="table-graphic"><div className="table-circle"><span>{t.number.toLocaleString('fa-IR')}</span></div>{[0,1,2,3].map(n=><i key={n} className={'seat seat-'+n}/>)}</div><Status value={t.state}/><strong className="table-amount">{t.amount?money(t.amount):'بدون صورتحساب باز'}</strong><button className="secondary-button full-button" onClick={()=>setShowQr(t.number)}>نمایش QR میز</button></article>)}</div>{showQr!==null&&<div className="modal-backdrop" onClick={()=>setShowQr(null)}><section className="modal-card" onClick={e=>e.stopPropagation()}><button className="modal-close" onClick={()=>setShowQr(null)}>×</button><div className="qr-placeholder"><span>▦</span><b>KG / TABLE {String(showQr).padStart(2,'0')}</b></div><h2>QR میز {showQr.toLocaleString('fa-IR')}</h2><p>در نسخهٔ متصل به بک‌اند، این بخش QR یکتا و قابل اسکن تولید می‌کند.</p><button className="primary-button" onClick={()=>window.print()}>چاپ برچسب آزمایشی</button></section></div>}</div>;
}

function SimplePage({ page, onSelect }) {
 const content = {
 reservations:['رزرو میز','رزروهای آینده، تأییدها، لغوها و ظرفیت سالن.','رزرو آزمایشی','در نسخه متصل، فرم رزرو، بازه زمانی، ظرفیت و وضعیت تأیید به API وصل می‌شود.'],
 customers:['مشتریان و بازخورد','پیگیری نظرها، شکایت‌ها و رضایت مشتریان.','بازخوردهای جدید','این بخش برای ثبت پیام، موضوع، اولویت و وضعیت پاسخ آمادهٔ توسعه است.'],
 expenses:['هزینه‌ها','ثبت هزینه‌های عملیاتی و مستندات مالی.','ثبت هزینه','برای نسخهٔ عملیاتی، هزینه باید به دفتر مالی و در صورت پرداخت نقدی به حرکت صندوق متصل شود.'],
 reports:['گزارش‌ها','فروش، دریافتی، مانده مطالبات، هزینه‌ها و عملکرد شیفت.','گزارش‌های مدیریتی','اعداد این پیش‌نمایش ساختگی‌اند؛ گزارش نهایی باید از دیتابیس و قواعد مالی محاسبه شود.'],
 settings:['تنظیمات','تنظیمات برند، واحد پول، چاپ و مدیریت مجموعه.','تنظیمات رستوران','اتصال به RestaurantSetting و ثبت تغییرات در AuditLog در گام بعد انجام می‌شود.']
 }[page] || ['داشبورد','مدیریت عملیات رستوران.','در حال توسعه','این بخش در اسکلت اولیه قرار دارد.'];
 return <div className="page-content"><Header title={content[0]} subtitle={content[1]} onNewOrder={()=>onSelect('orders')}/><div className="panel feature-placeholder"><div className="feature-mark">✦</div><span className="eyebrow">KHANE GILASI / MODULE</span><h2>{content[2]}</h2><p>{content[3]}</p><div className="feature-actions"><button className="primary-button" onClick={()=>onSelect('overview')}>بازگشت به نمای کلی</button><button className="secondary-button" onClick={()=>onSelect('orders')}>مشاهده سفارش‌ها</button></div></div></div>;
}

export default function DashboardApp() {
 const [page,setPage] = useState('overview');
 const [orders,setOrders] = useState(initialOrders);
 const [mobileNav,setMobileNav] = useState(false);
 const [newOrderOpen,setNewOrderOpen] = useState(false);
 const [notice,setNotice] = useState('');
 const totalOpen = useMemo(()=>orders.filter(o=>!['تحویل‌شده','لغوشده'].includes(o.status)).length,[orders]);
 const onPay = (id) => { setOrders(prev=>prev.map(o=>o.id===id?{...o,payment:'پرداخت‌شده'}:o));setNotice('پرداخت آزمایشی در پیش‌نمایش ثبت شد؛ اطلاعاتی به سرور ارسال نشده است.');window.setTimeout(()=>setNotice(''),4500); };
 const createOrder = (e) => { e.preventDefault();const fd=new FormData(e.currentTarget);const total=Number(fd.get('total'));if(!total||total<1)return;const id='KG-'+String(2409+orders.length-5);setOrders([{id,channel:fd.get('channel'),customer:'سفارش جدید نمایشی',items:'۱ آیتم',total,status:'جدید',payment:'پرداخت نشده',time:new Date().toLocaleTimeString('fa-IR',{hour:'2-digit',minute:'2-digit'}),kind:fd.get('channel')==='آنلاین'?'online':'dinein'},...orders]);setNewOrderOpen(false);setPage('orders');setNotice('سفارش آزمایشی ساخته شد؛ برای ذخیره دائمی باید API وصل شود.');window.setTimeout(()=>setNotice(''),4500); };
 const orderNew = () => setNewOrderOpen(true);
 const renderPage = () => {
  if(page==='overview') return <Overview orders={orders} onSelect={setPage} onNewOrder={orderNew}/>;
  if(page==='orders') return <OrdersPage orders={orders} onSelect={(p)=>p==='orders'?orderNew():setPage(p)}/>;
  if(page==='pos') return <PosPage orders={orders} onPay={onPay}/>;
  if(page==='menu') return <MenuPage/>;
  if(page==='tables') return <TablesPage/>;
  return <SimplePage page={page} onSelect={setPage}/>;
 };
 return <div className="dashboard-app" dir="rtl">
  <aside className={'sidebar '+(mobileNav?'sidebar-open':'')}>
   <a href="#" className="brand-lockup" onClick={e=>{e.preventDefault();setPage('overview');setMobileNav(false);}}><span className="brand-symbol">خ</span><span><strong>خانه گیلاسی</strong><small>CAFE / RESTAURANT</small></span><span className="brand-status" title="حالت نمایشی"/></a>
   <div className="sidebar-caption">فضای کاری</div><div className="workspace"><span className="workspace-avatar">KG</span><span><b>شعبه مرکزی</b><small>پنل مدیریت</small></span><span className="workspace-chevron">⌄</span></div>
   <div className="sidebar-caption">مدیریت روزانه</div>
   <nav className="side-nav" aria-label="منوی داشبورد">{navItems.map(([key,label,icon])=><button key={key} className={(page===key?'active ':'')+'nav-item'} onClick={()=>{setPage(key);setMobileNav(false);}}><Icon>{icon}</Icon><span>{label}</span>{key==='orders'&&<b className="nav-count">{totalOpen.toLocaleString('fa-IR')}</b>}{key==='customers'&&<i className="nav-notice"/>}</button>)}</nav>
   <div className="sidebar-bottom"><div className="help-card"><span>✦</span><b>به کمکی نیاز داری؟</b><p>راهنمای مدیریت خانه گیلاسی</p><button onClick={()=>setNotice('راهنمای داخلی در نسخه بعد اضافه می‌شود.')}>مشاهده راهنما ←</button></div><button className="user-card"><span className="user-avatar">م</span><span><b>مدیر مجموعه</b><small>مدیریت کل</small></span><span className="user-menu">•••</span></button></div>
  </aside>
  {mobileNav&&<button aria-label="بستن منو" className="mobile-overlay" onClick={()=>setMobileNav(false)}/>}
  <main className="main-shell"><div className="topbar"><button className="mobile-menu-button" onClick={()=>setMobileNav(true)} aria-label="بازکردن منو">☰</button><div className="breadcrumb">خانه گیلاسی <span>/</span> {navItems.find(n=>n[0]===page)?.[1]||'داشبورد'}</div><div className="topbar-right"><span className="connection-state"><i/> حالت پیش‌نمایش</span><span className="topbar-date">{new Intl.DateTimeFormat('fa-IR',{weekday:'long',day:'numeric',month:'long'}).format(new Date())}</span><button className="icon-button" aria-label="جستجو" onClick={()=>setPage('orders')}>⌕</button><span className="topbar-avatar">م</span></div></div>{renderPage()}<footer className="app-footer"><span>خانه گیلاسی <span className="footer-dot">·</span> پنل عملیات رستوران</span><span>نسخهٔ نمایشی رابط · اتصال API در مرحلهٔ بعد</span></footer></main>
  {newOrderOpen&&<div className="modal-backdrop" onClick={()=>setNewOrderOpen(false)}><section className="modal-card order-modal" onClick={e=>e.stopPropagation()}><button className="modal-close" onClick={()=>setNewOrderOpen(false)}>×</button><span className="eyebrow">NEW ORDER / DEMO</span><h2>ثبت سفارش جدید</h2><p>اطلاعات فقط در حالت پیش‌نمایش و حافظهٔ صفحه ذخیره می‌شود.</p><form onSubmit={createOrder}><label>کانال سفارش<select name="channel"><option>میز ۰۱</option><option>میز ۰۲</option><option>بیرون‌بر</option><option>آنلاین</option></select></label><label>مبلغ سفارش (تومان)<input name="total" type="number" min="1" defaultValue="250000" required/></label><div className="modal-actions"><button className="secondary-button" type="button" onClick={()=>setNewOrderOpen(false)}>انصراف</button><button className="primary-button" type="submit">ثبت سفارش آزمایشی</button></div></form></section></div>}
  {notice&&<div className="toast-message" role="status"><span>✓</span>{notice}<button onClick={()=>setNotice('')}>×</button></div>}
 </div>;
}
