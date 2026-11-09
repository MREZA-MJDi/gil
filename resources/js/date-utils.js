import { toGregorian, toJalaali } from 'jalaali-js';

const pad = value => String(value).padStart(2, '0');

export function normalizeDigits(value) {
    return String(value ?? '')
        .replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
        .replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
}

export function toPersianDigits(value) {
    return String(value ?? '').replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
}

export function gregorianDateToJalali(value) {
    const match = normalizeDigits(value).match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (!match) return '';
    const jalali = toJalaali(Number(match[1]), Number(match[2]), Number(match[3]));
    return String(jalali.jy) + '/' + pad(jalali.jm) + '/' + pad(jalali.jd);
}

export function jalaliDateToGregorian(value) {
    const match = normalizeDigits(value).trim().match(/^(\d{4})[/-](\d{1,2})[/-](\d{1,2})$/);
    if (!match) return '';
    const jy = Number(match[1]), jm = Number(match[2]), jd = Number(match[3]);
    if (jm < 1 || jm > 12 || jd < 1 || jd > 31) return '';
    try {
        const gregorian = toGregorian(jy, jm, jd);
        const roundTrip = toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);
        if (roundTrip.jy !== jy || roundTrip.jm !== jm || roundTrip.jd !== jd) return '';
        return String(gregorian.gy) + '-' + pad(gregorian.gm) + '-' + pad(gregorian.gd);
    } catch {
        return '';
    }
}

export function gregorianDateTimeToJalali(value) {
    const normalized = normalizeDigits(value).trim();
    const match = normalized.match(/^(\d{4})-(\d{1,2})-(\d{1,2})[T ](\d{1,2}):(\d{2})/);
    if (!match) return '';
    const date = gregorianDateToJalali(match[1] + '-' + match[2] + '-' + match[3]);
    if (!date) return '';
    return date + ' ' + pad(match[4]) + ':' + pad(match[5]);
}

export function jalaliDateTimeToGregorian(value) {
    const normalized = normalizeDigits(value).trim();
    const match = normalized.match(/^(\d{4})[/-](\d{1,2})[/-](\d{1,2})[ T]+(\d{1,2}):(\d{2})$/);
    if (!match) return '';
    const hours = Number(match[4]), minutes = Number(match[5]);
    if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) return '';
    const date = jalaliDateToGregorian(match[1] + '/' + match[2] + '/' + match[3]);
    if (!date) return '';
    return date + 'T' + pad(hours) + ':' + pad(minutes);
}

export function currentJalaliDate() {
    const now = new Date();
    const parts = new Intl.DateTimeFormat('en-US-u-ca-persian-nu-latn', {
        timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit',
    }).formatToParts(now);
    const field = Object.fromEntries(parts.map(part => [part.type, part.value]));
    return String(field.year) + '/' + pad(field.month) + '/' + pad(field.day);
}
