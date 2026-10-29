import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { JalaliDateTimePicker, toAsciiDigits } from './shared/iranian-ui.jsx';

const faNumber = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const makeKey = () => globalThis.crypto?.randomUUID
    ? globalThis.crypto.randomUUID().replaceAll('-', '')
    : 'reserve' + Date.now().toString(36) + Math.random().toString(36).slice(2, 23);

function PublicReservationForm() {
    const root = document.getElementById('public-reservation-app');
    const endpoint = root?.dataset.endpoint || '/reservations';
    const [form, setForm] = useState({
        customer_name: '',
        phone: '',
        party_size: '2',
        starts_at: '',
        duration_minutes: '90',
        notes: '',
    });
    const [requestKey, setRequestKey] = useState(makeKey);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(null);

    const set = (key, value) => setForm(previous => ({ ...previous, [key]: value }));

    const submit = async event => {
        event.preventDefault();
        if (busy) return;
        setBusy(true);
        setError('');
        setSuccess(null);
        const payload = {
            ...form,
            phone: toAsciiDigits(form.phone),
            party_size: Number(form.party_size),
            duration_minutes: Number(form.duration_minutes),
            idempotency_key: requestKey,
        };
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Idempotency-Key': requestKey,
                },
                body: JSON.stringify(payload),
            });
            let result = {};
            try { result = await response.json(); } catch {}
            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت رزرو انجام نشد. ورودی‌ها را بررسی کن.');
            }
            setSuccess(result.data || {});
            setForm(previous => ({ ...previous, starts_at: '', notes: '' }));
            setRequestKey(makeKey());
        } catch (e) {
            setError(e?.message || 'ارتباط با سرور برقرار نشد. دوباره تلاش کن.');
        } finally {
            setBusy(false);
        }
    };

    return <div className="public-reservation-card">
        <div className="reservation-form-intro">
            <span className="reservation-form-mark" aria-hidden="true">↗</span>
            <div>
                <span className="section-kicker">A TABLE FOR YOUR MOMENT</span>
                <h3>درخواست رزرو میز</h3>
                <p>زمانت را به تقویم شمسی انتخاب کن؛ تیم کافه درخواست را بررسی و تأیید می‌کند.</p>
            </div>
        </div>
        {success && <div className="public-reservation-success" role="status">
            <strong>درخواستت ثبت شد.</strong>
            <span>کد پیگیری: <b dir="ltr">{success.reference}</b></span>
            <small>وضعیت فعلی «در انتظار تأیید» است؛ این پیام به معنی تأیید نهایی میز نیست.</small>
            <button type="button" onClick={() => setSuccess(null)}>ثبت درخواست دیگر</button>
        </div>}
        {error && <div className="public-reservation-error" role="alert">{error}</div>}
        <form className="public-reservation-form" onSubmit={submit}>
            <label className="reservation-field">
                <span>نام و نام خانوادگی</span>
                <input type="text" value={form.customer_name} onChange={e => set('customer_name', e.target.value)} autoComplete="name" maxLength="150" placeholder="نام شما" required />
            </label>
            <label className="reservation-field">
                <span>شماره موبایل</span>
                <input type="tel" dir="ltr" inputMode="tel" value={form.phone} onChange={e => set('phone', e.target.value)} autoComplete="tel" maxLength="30" placeholder="0912 123 4567" required />
            </label>
            <label className="reservation-field">
                <span>تعداد مهمان</span>
                <select value={form.party_size} onChange={e => set('party_size', e.target.value)} required>
                    {Array.from({length: 12}, (_, i) => i + 1).map(number => <option key={number} value={number}>{faNumber(number)} نفر</option>)}
                    <option value="15">بیش از ۱۲ نفر؛ تا ۳۰ نفر</option>
                </select>
            </label>
            <div className="reservation-field">
                <span>تاریخ و ساعت (شمسی)</span>
                <JalaliDateTimePicker value={form.starts_at} onChange={value => set('starts_at', value)} includeTime required ariaLabel="تاریخ و ساعت رزرو به شمسی" placeholder="انتخاب تاریخ شمسی" />
            </div>
            <label className="reservation-field">
                <span>مدت حضور</span>
                <select value={form.duration_minutes} onChange={e => set('duration_minutes', e.target.value)} required>
                    <option value="60">۱ ساعت</option>
                    <option value="90">۱ ساعت و نیم</option>
                    <option value="120">۲ ساعت</option>
                    <option value="180">۳ ساعت</option>
                </select>
            </label>
            <label className="reservation-field reservation-field-wide">
                <span>درخواست یا توضیح (اختیاری)</span>
                <textarea rows="3" value={form.notes} onChange={e => set('notes', e.target.value)} maxLength="1000" placeholder="مثلاً کودک همراه داریم یا ترجیح خاصی برای نشستن..." />
            </label>
            <div className="reservation-submit-row">
                <p>درخواست ابتدا در وضعیت «در انتظار» ثبت می‌شود و تا تأیید کافه قطعی نیست.</p>
                <button className="button reservation-submit-button" type="submit" disabled={busy}>
                    {busy ? 'در حال ثبت…' : 'ارسال درخواست رزرو'} <span aria-hidden="true">↗</span>
                </button>
            </div>
        </form>
    </div>;
}

const root = document.getElementById('public-reservation-app');
if (root) createRoot(root).render(<PublicReservationForm />);
