import test from 'node:test';
import assert from 'node:assert/strict';
import {
    gregorianDateToJalali,
    jalaliDateToGregorian,
    gregorianDateTimeToJalali,
    jalaliDateTimeToGregorian,
    normalizeDigits,
} from './date-utils.js';

test('converts Nowruz correctly between Gregorian and Jalali calendars', () => {
    assert.equal(gregorianDateToJalali('2024-03-20'), '1403/01/01');
    assert.equal(jalaliDateToGregorian('1403/01/01'), '2024-03-20');
});

test('accepts Persian and Arabic numerals in date input', () => {
    assert.equal(normalizeDigits('۱۴۰۳/۰۱/۰۱'), '1403/01/01');
    assert.equal(jalaliDateToGregorian('۱۴۰۳/۰۱/۰۱'), '2024-03-20');
    assert.equal(jalaliDateToGregorian('١٤٠٣/٠١/٠١'), '2024-03-20');
});

test('round trips Jalali date-time while preserving the restaurant local clock time', () => {
    const gregorian = jalaliDateTimeToGregorian('۱۴۰۳/۰۱/۰۱ ۱۸:۳۰');
    assert.equal(gregorian, '2024-03-20T18:30');
    assert.equal(gregorianDateTimeToJalali(gregorian), '1403/01/01 18:30');
});

test('rejects invalid Jalali dates and times instead of silently changing them', () => {
    assert.equal(jalaliDateToGregorian('1403/13/01'), '');
    assert.equal(jalaliDateToGregorian('1403/12/31'), '');
    assert.equal(jalaliDateTimeToGregorian('1403/01/01 25:00'), '');
    assert.equal(jalaliDateTimeToGregorian('1403/01/01 08:70'), '');
});
