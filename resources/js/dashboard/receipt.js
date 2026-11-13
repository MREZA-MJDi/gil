const orderStatusLabel = {
    new: 'جدید',
    confirmed: 'تأیید شده',
    preparing: 'در حال آماده‌سازی',
    ready: 'آماده تحویل',
    delivered: 'تحویل شده',
    cancelled: 'لغو شده',
};

const paymentStatusLabel = {
    unpaid: 'تسویه نشده',
    partial: 'پرداخت ناقص',
    paid: 'تسویه کامل',
    partially_refunded: 'بازپرداخت جزئی',
    refunded: 'بازپرداخت کامل',
};

const paymentMethodLabel = {
    cash: 'نقدی',
    card_terminal: 'کارت‌خوان',
    online: 'آنلاین',
    other: 'سایر',
};

const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const numberFa = value => new Intl.NumberFormat('fa-IR-u-nu-arabext', {
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const money = (value, currency) => numberFa(value) + ' ' + (currency || 'تومان');

const dateTime = value => value
    ? new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-arabext', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Tehran',
    }).format(new Date(value))
    : '—';

function orderKind(order) {
    const source = order?.source;
    const channel = order?.channel;

    if (source === 'snappfood') return 'سفارش اسنپ‌فود';
    if (source === 'partner') return 'سفارش سرویس همکار';
    if (source === 'website') return channel === 'takeaway' ? 'سفارش آنلاین سایت · بیرون‌بر' : 'سفارش آنلاین سایت';
    if (source === 'qr') return 'سفارش با QR میز';
    if (channel === 'counter') return 'ثبت‌شده در صندوق';
    if (channel === 'takeaway') return 'بیرون‌بر';
    if (channel === 'dine_in') return source === 'staff' ? 'حضوری · ثبت پرسنل' : 'حضوری';
    if (channel === 'online') return 'سفارش آنلاین';
    return channel || 'ثبت‌شده';
}

function statusLabel(value, dictionary, fallback) {
    return dictionary[value] || value || fallback;
}

export function buildPrintHtml(order, type, settings = {}) {
    const paperWidth = Number(settings?.paper_width) === 58 ? 58 : 80;
    const isKitchen = type === 'kitchen';
    const isReceipt = type === 'receipt';
    const title = isKitchen ? 'فیش آشپزخانه' : isReceipt ? 'رسید مشتری' : 'فاکتور فروش';
    const orderNumber = order?.number || ('#' + (order?.id ?? '—'));
    const currency = settings?.currency_label || 'تومان';
    const items = Array.isArray(order?.items) ? order.items : [];
    const payments = (Array.isArray(order?.payments) ? order.payments : [])
        .filter(payment => payment.status === 'succeeded');
    const refunds = Array.isArray(order?.refunds) ? order.refunds : [];
    const paid = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
    const refunded = refunds.reduce((sum, refund) => sum + Number(refund.amount || 0), 0);
    const balance = Math.max(0, Number(order?.total || 0) - paid + refunded);
    const status = statusLabel(order?.status, orderStatusLabel, 'ثبت‌شده');
    const paymentStatus = statusLabel(order?.payment_status, paymentStatusLabel, 'تسویه نشده');

    const itemRows = items.map((item, index) => {
        const options = (Array.isArray(item.options) ? item.options : [])
            .map(option => option?.name)
            .filter(Boolean);
        const notes = [options.join('، '), item.note].filter(Boolean).join(' · ');
        const quantity = numberFa(item.quantity || 0);
        if (isKitchen) {
            return '<article class="item kitchen-item">' +
                '<div class="item-name"><b>' + numberFa(index + 1) + '. ' + escapeHtml(item.name || 'محصول') + '</b></div>' +
                '<div class="item-qty">تعداد: <strong>' + quantity + '</strong></div>' +
                (notes ? '<div class="item-note">' + escapeHtml(notes) + '</div>' : '') +
                '</article>';
        }

        return '<article class="item">' +
            '<div class="item-heading"><b>' + numberFa(index + 1) + '. ' + escapeHtml(item.name || 'محصول') + '</b><strong>' + money(item.line_total, currency) + '</strong></div>' +
            '<div class="item-meta"><span>' + quantity + ' × ' + money(item.unit_price, currency) + '</span>' +
            (Number(item.options_total || 0) > 0 ? '<span>افزودنی‌ها: ' + money(item.options_total, currency) + '</span>' : '') +
            '</div>' +
            (notes ? '<div class="item-note">' + escapeHtml(notes) + '</div>' : '') +
            '</article>';
    }).join('');

    const totals = isKitchen ? '' :
        '<section class="totals">' +
        '<div class="amount-row"><span>جمع اقلام</span><b>' + money(order?.subtotal, currency) + '</b></div>' +
        (Number(order?.discount || 0) > 0 ? '<div class="amount-row"><span>تخفیف</span><b>− ' + money(order.discount, currency) + '</b></div>' : '') +
        (Number(order?.tax || 0) > 0 ? '<div class="amount-row"><span>مالیات</span><b>' + money(order.tax, currency) + '</b></div>' : '') +
        (Number(order?.service || 0) > 0 ? '<div class="amount-row"><span>حق سرویس</span><b>' + money(order.service, currency) + '</b></div>' : '') +
        '<div class="amount-row grand-total"><span>مبلغ قابل پرداخت</span><b>' + money(order?.total, currency) + '</b></div>' +
        '<div class="amount-row"><span>وضعیت پرداخت</span><b>' + escapeHtml(paymentStatus) + '</b></div>' +
        (paid > 0 ? '<div class="amount-row"><span>مجموع دریافتی</span><b>' + money(paid, currency) + '</b></div>' : '') +
        (refunded > 0 ? '<div class="amount-row"><span>بازپرداخت</span><b>' + money(refunded, currency) + '</b></div>' : '') +
        (balance > 0 ? '<div class="amount-row balance"><span>مانده</span><b>' + money(balance, currency) + '</b></div>' : '') +
        '</section>';

    const paymentSection = isReceipt
        ? '<section class="payments"><b class="section-title">جزئیات پرداخت</b>' +
            (payments.length
                ? payments.map(payment => '<div class="amount-row"><span>' +
                    escapeHtml(paymentMethodLabel[payment.method] || payment.method || 'پرداخت') +
                    (payment.reference ? '<small> · ' + escapeHtml(payment.reference) + '</small>' : '') +
                    '</span><b>' + money(payment.amount, currency) + '</b></div>').join('')
                : '<p class="muted">هنوز پرداختی برای این سفارش ثبت نشده است.</p>') +
            '</section>'
        : '';

    const address = settings?.address ? '<div class="store-address">' + escapeHtml(settings.address) + '</div>' : '';
    const phone = settings?.phone ? '<div class="store-phone">تلفن: ' + escapeHtml(settings.phone) + '</div>' : '';
    const table = order?.table?.name ? '<div class="meta-row"><span>میز</span><b>' + escapeHtml(order.table.name) + '</b></div>' : '';
    const customer = order?.customer_name ? '<div class="meta-row"><span>مشتری</span><b>' + escapeHtml(order.customer_name) + '</b></div>' : '';
    const customerPhone = order?.customer_phone ? '<div class="meta-row"><span>شماره تماس</span><b dir="ltr">' + escapeHtml(order.customer_phone) + '</b></div>' : '';
    const note = order?.customer_note ? '<div class="customer-note"><b>توضیحات سفارش</b><p>' + escapeHtml(order.customer_note) + '</p></div>' : '';
    const footer = settings?.receipt_footer || 'از اینکه خانه گیلاسی را انتخاب کردید سپاسگزاریم.';

    return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>' + escapeHtml(title + ' ' + orderNumber) + '</title>' +
        '<style>' +
        '@page{size:' + paperWidth + 'mm auto;margin:2mm}' +
        '*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
        'html,body{margin:0;padding:0;background:#fff;color:#111}' +
        'body{width:' + (paperWidth - 4) + 'mm;margin:0 auto;font-family:Tahoma,Arial,sans-serif;font-size:11px;line-height:1.8}' +
        '.ticket{width:100%;padding:2mm 0}' +
        '.store{text-align:center;border-bottom:2px solid #111;padding-bottom:8px;margin-bottom:8px}' +
        '.store-name{font-size:17px;font-weight:900;line-height:1.7}' +
        '.store-address,.store-phone{font-size:9px;color:#333}' +
        '.ticket-title{text-align:center;font-size:14px;font-weight:900;margin:7px 0 3px}' +
        '.order-number-label{text-align:center;font-size:10px;color:#333}' +
        '.order-number{direction:ltr;unicode-bidi:plaintext;text-align:center;font-size:24px;font-weight:900;letter-spacing:.4px;border:1px dashed #222;padding:5px 3px;margin:4px 0 9px}' +
        '.meta{display:grid;gap:2px}.meta-row,.amount-row{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}' +
        '.meta-row span,.amount-row span{color:#333}.meta-row b,.amount-row b{text-align:left;max-width:65%;overflow-wrap:anywhere}' +
        '.rule{border:0;border-top:1px dashed #555;margin:8px 0}' +
        '.section-title{display:block;font-size:11px;font-weight:900;margin-bottom:5px}' +
        '.items{display:grid;gap:0}.item{padding:7px 0;border-bottom:1px dashed #999;break-inside:avoid}' +
        '.item-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:6px}.item-heading b{flex:1;min-width:0;font-size:11px}.item-heading strong{font-size:10px;white-space:nowrap}' +
        '.item-meta{display:flex;justify-content:space-between;gap:5px;font-size:9px;color:#444;margin-top:2px}.item-note{font-size:9px;color:#333;margin-top:2px;overflow-wrap:anywhere}' +
        '.kitchen-item{padding:9px 0}.kitchen-item .item-name b{font-size:13px}.item-qty{font-size:14px;font-weight:800;margin-top:2px}.item-qty strong{font-size:18px}' +
        '.totals,.payments{padding-top:7px}.amount-row{font-size:10px;padding:2px 0}.amount-row small{font-size:8px}.grand-total{border-top:1px solid #111;margin-top:5px;padding-top:7px;font-size:12px;font-weight:900}.grand-total b{font-size:14px}.balance{font-weight:900;border-top:1px dashed #555;margin-top:3px;padding-top:5px}' +
        '.customer-note{border:1px dashed #555;padding:6px;margin-top:7px}.customer-note p{white-space:pre-wrap;margin:2px 0 0}' +
        '.muted{text-align:center;font-size:9px;color:#555;margin:5px 0}' +
        '.footer{text-align:center;border-top:1px dashed #555;margin-top:10px;padding-top:8px;font-size:9px;white-space:pre-wrap}' +
        '.print-meta{text-align:center;margin-top:8px;color:#555;font-size:8px}' +
        '@media screen{body{margin:12px auto;box-shadow:0 0 0 1px #ddd;padding:8px}.ticket{padding:8px}}' +
        '</style></head><body><main class="ticket">' +
        '<header class="store"><div class="store-name">' + escapeHtml(settings?.name || 'خانه گیلاسی') + '</div>' +
        address + phone + '<div class="ticket-title">' + title + '</div>' +
        '<div class="order-number-label">شماره سفارش</div><div class="order-number">' + escapeHtml(orderNumber) + '</div></header>' +
        '<section class="meta">' +
        '<div class="meta-row"><span>نوع سفارش</span><b>' + escapeHtml(orderKind(order)) + '</b></div>' +
        '<div class="meta-row"><span>تاریخ و ساعت ثبت</span><b>' + escapeHtml(dateTime(order?.created_at)) + '</b></div>' +
        table + customer + customerPhone +
        '<div class="meta-row"><span>وضعیت سفارش</span><b>' + escapeHtml(status) + '</b></div>' +
        '</section><hr class="rule">' +
        '<section class="items"><b class="section-title">' + (isKitchen ? 'اقلام جهت آماده‌سازی' : 'شرح سفارش') + '</b>' +
        (itemRows || '<p class="muted">برای این سفارش قلمی ثبت نشده است.</p>') +
        '</section>' +
        (note ? '<hr class="rule">' + note : '') +
        (totals ? '<hr class="rule">' + totals : '') +
        paymentSection +
        '<footer class="footer">' + escapeHtml(footer) + '</footer>' +
        '<div class="print-meta">شناسه داخلی: ' + escapeHtml(order?.id ?? '—') + ' · نسخه چاپی ' + (isKitchen ? 'آشپزخانه' : 'مشتری') + '</div>' +
        '</main></body></html>';
}
