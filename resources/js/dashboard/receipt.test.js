import test from 'node:test';
import assert from 'node:assert/strict';
import { buildPrintHtml } from './receipt.js';

const sampleOrder = {
    id: 42,
    number: 'KG-1405-0088',
    channel: 'takeaway',
    source: 'website',
    status: 'preparing',
    payment_status: 'partial',
    created_at: '2026-11-13T10:30:00+03:30',
    customer_name: 'مشتری نمونه',
    customer_phone: '09120000000',
    customer_note: 'بدون شکر',
    subtotal: 250000,
    discount: 10000,
    tax: 0,
    service: 0,
    total: 240000,
    table: null,
    items: [{
        name: 'لاته کلاسیک',
        quantity: 2,
        unit_price: 125000,
        options_total: 0,
        line_total: 250000,
        options: [{ name: 'شیر جو دوسر' }],
        note: 'یک عدد بدون یخ',
    }],
    payments: [{ status: 'succeeded', method: 'card_terminal', amount: 100000, reference: 'POS-123' }],
    refunds: [],
};

test('customer receipt prints explicit order number, order type, items and financial summary', () => {
    const html = buildPrintHtml(sampleOrder, 'receipt', {
        name: 'خانه گیلاسی',
        phone: '02112345678',
        address: 'تهران',
        currency_label: 'تومان',
        paper_width: 80,
    });

    assert.match(html, /رسید مشتری/);
    assert.match(html, /شماره سفارش/);
    assert.match(html, /KG-1405-0088/);
    assert.match(html, /نوع سفارش/);
    assert.match(html, /سفارش آنلاین سایت · بیرون‌بر/);
    assert.match(html, /لاته کلاسیک/);
    assert.match(html, /۲ × ۱۲۵٬۰۰۰ تومان/);
    assert.match(html, /۲۴۰٬۰۰۰ تومان/);
    assert.match(html, /جزئیات پرداخت/);
    assert.match(html, /POS-123/);
    assert.match(html, /بدون شکر/);
});

test('kitchen slip shows order identity and quantities but never prices or totals', () => {
    const html = buildPrintHtml(sampleOrder, 'kitchen', {
        name: 'خانه گیلاسی',
        currency_label: 'تومان',
        paper_width: 58,
    });

    assert.match(html, /فیش آشپزخانه/);
    assert.match(html, /شماره سفارش/);
    assert.match(html, /KG-1405-0088/);
    assert.match(html, /اقلام جهت آماده‌سازی/);
    assert.match(html, /تعداد: <strong>۲<\/strong>/);
    assert.doesNotMatch(html, /مبلغ قابل پرداخت/);
    assert.doesNotMatch(html, /۲۴۰٬۰۰۰ تومان/);
    assert.doesNotMatch(html, /POS-123/);
});

test('receipt escapes untrusted order and customer content', () => {
    const html = buildPrintHtml({
        ...sampleOrder,
        customer_name: '<script>alert(1)</script>',
        items: [{ ...sampleOrder.items[0], name: '<b>نام نامعتبر</b>' }],
    }, 'receipt', { currency_label: 'تومان' });

    assert.match(html, /&lt;script&gt;alert\(1\)&lt;\/script&gt;/);
    assert.match(html, /&lt;b&gt;نام نامعتبر&lt;\/b&gt;/);
    assert.doesNotMatch(html, /<script>alert\(1\)<\/script>/);
});
