import React, { useEffect, useMemo, useRef, useState } from 'react';

const digitMap = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
export const toAsciiDigits = value => String(value ?? '').replace(/[۰-۹٠-٩]/g, d => digitMap[d] ?? d).replace(/\D/g, '');
export const groupedNumber = value => {
    const raw = toAsciiDigits(value);
    return raw ? new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0, useGrouping: true }).format(Number(raw)) : '';
};
export const formatIranianMoney = (value, currency = 'تومان') =>
    new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0, useGrouping: true }).format(Number(value || 0)) + ' ' + currency;

const jalaliFormatter = new Intl.DateTimeFormat('en-u-ca-persian-nu-latn', { year: 'numeric', month: 'numeric', day: 'numeric', timeZone: 'UTC' });
const jalaliParts = date => Object.fromEntries(jalaliFormatter.formatToParts(date).filter(p => p.type !== 'literal').map(p => [p.type, Number(p.value)]));
export function gregorianToJalali(isoDate) {
    if (!isoDate || !/^\d{4}-\d{2}-\d{2}/.test(String(isoDate))) return null;
    const [y, m, d] = String(isoDate).slice(0, 10).split('-').map(Number);
    return jalaliParts(new Date(Date.UTC(y, m - 1, d, 12)));
}
function jalaliYearStart(jy) {
    const start = Date.UTC(jy + 621, 2, 18, 12);
    for (let offset = 0; offset < 22; offset++) {
        const date = new Date(start + offset * 86400000);
        const p = jalaliParts(date);
        if (p.year === jy && p.month === 1 && p.day === 1) return date;
    }
    throw new RangeError('Jalali year out of supported range');
}
export function jalaliToGregorian(jy, jm, jd) {
    if (!Number.isInteger(jy) || !Number.isInteger(jm) || !Number.isInteger(jd) || jm < 1 || jm > 12 || jd < 1) throw new RangeError('Invalid Jalali date');
    const yearStart = jalaliYearStart(jy);
    const monthOffset = jm <= 6 ? (jm - 1) * 31 : 186 + (jm - 7) * 30;
    const monthLength = jm <= 6 ? 31 : jm <= 11 ? 30 : (jalaliYearStart(jy + 1).getTime() - yearStart.getTime()) / 86400000 - 336;
    if (jd > monthLength) throw new RangeError('Invalid Jalali day');
    const date = new Date(yearStart.getTime() + (monthOffset + jd - 1) * 86400000);
    return [date.getUTCFullYear(), String(date.getUTCMonth() + 1).padStart(2, '0'), String(date.getUTCDate()).padStart(2, '0')].join('-');
}
const monthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
const weekDays = ['ش','ی','د','س','چ','پ','ج'];
const faDigits = n => new Intl.NumberFormat('fa-IR', { useGrouping: false }).format(Number(n));
const pad2 = n => String(n).padStart(2, '0');
const todayIso = () => { const n = new Date(); return [n.getFullYear(), pad2(n.getMonth()+1), pad2(n.getDate())].join('-'); };
const shiftMonth = ({year, month}, step) => {
    const absolute = year * 12 + month - 1 + step;
    return {year: Math.floor(absolute / 12), month: ((absolute % 12) + 12) % 12 + 1};
};

export function JalaliDateTimePicker({value = '', onChange, includeTime = false, required = false, placeholder = 'انتخاب تاریخ شمسی', ariaLabel = 'انتخاب تاریخ شمسی'}) {
    const [open, setOpen] = useState(false);
    const [time, setTime] = useState(String(value).includes('T') ? String(value).slice(11, 16) : '18:00');
    const selected = useMemo(() => gregorianToJalali(String(value).slice(0, 10)), [value]);
    const [view, setView] = useState(() => selected || gregorianToJalali(todayIso()) || {year: 1405, month: 1, day: 1});
    const rootRef = useRef(null);
    useEffect(() => {
        if (selected) setView({year: selected.year, month: selected.month});
        if (String(value).includes('T')) setTime(String(value).slice(11, 16));
    }, [value]);
    useEffect(() => {
        if (!open) return undefined;
        const outside = event => { if (rootRef.current && !rootRef.current.contains(event.target)) setOpen(false); };
        const escape = event => { if (event.key === 'Escape') setOpen(false); };
        document.addEventListener('pointerdown', outside);
        document.addEventListener('keydown', escape);
        return () => { document.removeEventListener('pointerdown', outside); document.removeEventListener('keydown', escape); };
    }, [open]);

    const first = jalaliToGregorian(view.year, view.month, 1).split('-').map(Number);
    const leading = (new Date(Date.UTC(first[0], first[1]-1, first[2])).getUTCDay() + 1) % 7;
    const monthLength = view.month <= 6 ? 31 : view.month <= 11 ? 30 : (jalaliYearStart(view.year + 1).getTime() - jalaliYearStart(view.year).getTime()) / 86400000 - 336;
    const days = [...Array(leading).fill(null), ...Array.from({length: monthLength}, (_, i) => i+1)];
    while (days.length % 7) days.push(null);
    const display = selected ? faDigits(selected.year) + '/' + pad2(selected.month).replace(/\d/g, faDigits) + '/' + pad2(selected.day).replace(/\d/g, faDigits) : '';
    const pick = day => {
        if (!day) return;
        const date = jalaliToGregorian(view.year, view.month, day);
        onChange(includeTime ? date + 'T' + (time || '18:00') : date);
        setOpen(false);
    };
    return <div className="jalali-picker" ref={rootRef}>
        <div className="jalali-picker-input-row">
            <input className="jalali-date-display" type="text" dir="ltr" readOnly required={required} value={display}
                placeholder={placeholder} aria-label={ariaLabel} aria-haspopup="dialog" aria-expanded={open}
                onClick={() => setOpen(v => !v)}
                onKeyDown={e => {if (e.key === 'Enter' || e.key === ' ') {e.preventDefault(); setOpen(v => !v);}}} />
            <button type="button" className="jalali-open-button" onClick={() => setOpen(v => !v)} aria-label={ariaLabel}>▦</button>
            {includeTime && <input className="jalali-time-input" type="time" value={time} aria-label="ساعت رزرو" required={required}
                onChange={e => {const t = e.target.value; setTime(t); if (value) onChange(String(value).slice(0,10) + 'T' + t);}} />}
        </div>
        {open && <div className="jalali-calendar" role="dialog" aria-label="تقویم شمسی">
            <div className="jalali-calendar-header">
                <button type="button" aria-label="ماه قبل" onClick={() => setView(v => shiftMonth(v, -1))}>›</button>
                <strong>{monthNames[view.month-1]} {faDigits(view.year)}</strong>
                <button type="button" aria-label="ماه بعد" onClick={() => setView(v => shiftMonth(v, 1))}>‹</button>
            </div>
            <div className="jalali-weekdays">{weekDays.map((day, i) => <span key={i}>{day}</span>)}</div>
            <div className="jalali-calendar-days">{days.map((day, i) => {
                const chosen = Boolean(day && selected && selected.year === view.year && selected.month === view.month && selected.day === day);
                return day ? <button type="button" key={view.year+'-'+view.month+'-'+day} className={chosen ? 'selected' : ''} aria-pressed={chosen} onClick={() => pick(day)}>{faDigits(day)}</button>
                    : <span key={'blank-'+i} aria-hidden="true" />;
            })}</div>
            {includeTime && <p className="jalali-calendar-hint">تاریخ به شمسی انتخاب می‌شود؛ ساعت را کنار فیلد تنظیم کن.</p>}
        </div>}
    </div>;
}

export function MoneyInput({value = '', onChange, currency, ...props}) {
    const currencyText = currency || (typeof document !== 'undefined' ? document.documentElement.dataset.currencyLabel : '') || 'تومان';
    const inputRef = useRef(null);
    const [display, setDisplay] = useState(() => groupedNumber(value));
    useEffect(() => setDisplay(groupedNumber(value)), [value]);
    const change = e => {
        const input = e.currentTarget;
        const digitsBefore = toAsciiDigits(input.value.slice(0, input.selectionStart ?? input.value.length)).length;
        const raw = toAsciiDigits(input.value);
        setDisplay(groupedNumber(raw));
        onChange?.(raw);
        requestAnimationFrame(() => {
            if (!inputRef.current) return;
            const text = inputRef.current.value;
            let pos = text.length, count = 0;
            for (let i = 0; i < text.length; i++) {
                if (/[0-9۰-۹٠-٩]/.test(text[i])) count++;
                if (count >= digitsBefore) {pos = i+1; break;}
            }
            try { inputRef.current.setSelectionRange(pos, pos); } catch {}
        });
    };
    return <div className="money-input-wrap">
        <input {...props} ref={inputRef} type="text" dir="ltr" inputMode="numeric" autoComplete="off" value={display} onChange={change}
            aria-label={props['aria-label'] || (currencyText ? 'مبلغ به ' + currencyText : 'مبلغ')}
            className={('money-input ' + (props.className || '')).trim()} />
        {currencyText && <span className="money-input-currency">{currencyText}</span>}
    </div>;
}
