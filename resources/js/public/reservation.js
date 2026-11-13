import {
    currentJalaliDate,
    gregorianDateToJalali,
    jalaliDateToGregorian,
    jalaliMonthInfo,
    shiftJalaliMonth,
    toPersianDigits,
} from '../date-utils.js';

function initializeJalaliReservationPicker(form) {
    const wrapper = form.querySelector('[data-jalali-datetime]');
    if (!wrapper) return;

    const hidden = wrapper.querySelector('[data-jalali-value]');
    const trigger = wrapper.querySelector('[data-jalali-trigger]');
    const calendar = wrapper.querySelector('[data-jalali-calendar]');
    const monthLabel = wrapper.querySelector('[data-jalali-month]');
    const days = wrapper.querySelector('[data-jalali-days]');
    const timeInput = wrapper.querySelector('[data-jalali-time]');
    const previous = wrapper.querySelector('[data-jalali-prev]');
    const next = wrapper.querySelector('[data-jalali-next]');
    if (!hidden || !trigger || !calendar || !monthLabel || !days || !timeInput) return;

    const minValue = String(wrapper.dataset.min || '');
    const minDate = minValue.slice(0, 10);
    const minTime = minValue.slice(11, 16);
    let selectedDate = hidden.value ? hidden.value.slice(0, 10) : '';
    let calendarParts = (selectedDate ? gregorianDateToJalali(selectedDate) : currentJalaliDate()).split('/').map(Number);
    let calendarYear = calendarParts[0];
    let calendarMonth = calendarParts[1];

    const setOpen = open => {
        calendar.hidden = !open;
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const updateValue = () => {
        const time = timeInput.value;
        timeInput.min = selectedDate === minDate ? minTime : '';
        const tooEarly = selectedDate && minDate && (
            selectedDate < minDate || (selectedDate === minDate && minTime && time && time < minTime)
        );

        if (!selectedDate) timeInput.setCustomValidity('ابتدا یک تاریخ شمسی انتخاب کن.');
        else if (!time) timeInput.setCustomValidity('ساعت رزرو را انتخاب کن.');
        else if (tooEarly) timeInput.setCustomValidity('زمان رزرو باید دست‌کم ۳۰ دقیقه بعد از زمان فعلی باشد.');
        else timeInput.setCustomValidity('');

        hidden.value = selectedDate && time && !tooEarly ? selectedDate + 'T' + time : '';
        trigger.textContent = selectedDate
            ? toPersianDigits(gregorianDateToJalali(selectedDate))
            : 'انتخاب تاریخ شمسی';
    };

    const render = () => {
        const info = jalaliMonthInfo(calendarYear, calendarMonth);
        monthLabel.textContent = info.monthName + ' ' + toPersianDigits(calendarYear);
        days.replaceChildren();

        for (let index = 0; index < info.startOffset; index += 1) {
            const blank = document.createElement('span');
            blank.setAttribute('aria-hidden', 'true');
            days.append(blank);
        }

        for (let day = 1; day <= info.daysInMonth; day += 1) {
            const date = jalaliDateToGregorian(
                calendarYear + '/' + String(calendarMonth).padStart(2, '0') + '/' + String(day).padStart(2, '0')
            );
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = toPersianDigits(day);
            button.className = 'jalali-calendar-day';
            button.setAttribute('aria-pressed', date === selectedDate ? 'true' : 'false');
            if (date === selectedDate) button.classList.add('is-selected');
            if (minDate && date < minDate) button.disabled = true;
            button.addEventListener('click', () => {
                selectedDate = date;
                updateValue();
                render();
                setOpen(false);
            });
            days.append(button);
        }
    };

    previous?.addEventListener('click', () => {
        const shifted = shiftJalaliMonth(calendarYear, calendarMonth, -1);
        calendarYear = shifted.year;
        calendarMonth = shifted.month;
        render();
    });
    next?.addEventListener('click', () => {
        const shifted = shiftJalaliMonth(calendarYear, calendarMonth, 1);
        calendarYear = shifted.year;
        calendarMonth = shifted.month;
        render();
    });
    trigger.addEventListener('click', () => {
        render();
        setOpen(calendar.hidden);
    });
    timeInput.addEventListener('input', updateValue);
    timeInput.addEventListener('change', updateValue);

    document.addEventListener('click', event => {
        if (!wrapper.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') setOpen(false);
    });

    form.addEventListener('submit', event => {
        updateValue();
        if (!hidden.value) {
            event.preventDefault();
            event.stopImmediatePropagation();
            timeInput.reportValidity();
        }
    }, true);

    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            selectedDate = '';
            timeInput.value = '18:00';
            calendarParts = currentJalaliDate().split('/').map(Number);
            calendarYear = calendarParts[0];
            calendarMonth = calendarParts[1];
            updateValue();
            render();
            setOpen(false);
        }, 0);
    });

    updateValue();
    render();
}

/* Reservation form: sends the backend's exact accepted fields, ISO-local date/time. */
const reservationForm = document.querySelector('[data-public-reservation]');
if (reservationForm) {
    initializeJalaliReservationPicker(reservationForm);
    const submitButton = reservationForm.querySelector('[data-reserve-submit]');
    const message = reservationForm.querySelector('[data-reserve-message]');
    const successPanel = reservationForm.querySelector('[data-reserve-success]');
    const referenceEl = reservationForm.querySelector('[data-reserve-reference]');
    const newButton = reservationForm.querySelector('[data-reserve-new]');
    let uncertain = false;
    let stablePayload = null;

    const makeNewKey = () => globalThis.crypto?.randomUUID?.().replaceAll('-', '') || ('reserve' + Date.now().toString(36) + Math.random().toString(36).slice(2, 22));
    const setBusy = busy => {
        reservationForm.querySelectorAll('input, select, textarea').forEach(control => {
            if (control.name !== 'website') control.disabled = busy;
        });
        if (submitButton) {
            submitButton.disabled = busy;
            submitButton.textContent = busy ? 'در حال ثبت…' : 'ارسال درخواست ↗';
        }
    };

    reservationForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitButton?.disabled) return;
        const fields = new FormData(reservationForm);
        if (!stablePayload) {
            stablePayload = Object.fromEntries(fields.entries());
            stablePayload.party_size = Number(stablePayload.party_size);
            stablePayload.duration_minutes = Number(stablePayload.duration_minutes);
            if (stablePayload.estimated_budget === '') stablePayload.estimated_budget = null;
            else if (stablePayload.estimated_budget !== null) stablePayload.estimated_budget = Number(stablePayload.estimated_budget);
        }
        const key = stablePayload.idempotency_key;
        setBusy(true);
        if (message) message.textContent = uncertain ? 'در حال بررسی همان درخواست…' : 'در حال ثبت درخواست…';
        let response = null;
        try {
            response = await fetch(reservationForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Idempotency-Key': key
                },
                body: JSON.stringify(stablePayload)
            });
            let result = {};
            try { result = await response.json(); } catch { result = {}; }
            if (!response.ok) {
                const validation = result?.errors ? Object.values(result.errors).flat().find(Boolean) : null;
                throw new Error(validation || result?.message || 'ثبت درخواست انجام نشد.');
            }
            if (referenceEl) referenceEl.textContent = result?.data?.reference || 'ثبت شد';
            if (successPanel) successPanel.hidden = false;
            if (message) message.textContent = 'درخواست با وضعیت «در انتظار تأیید» ثبت شد.';
            reservationForm.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(control => {
                if (control.name !== 'website' && !control.hasAttribute('data-reserve-new')) control.disabled = true;
            });
            uncertain = false;
            stablePayload = null;
        } catch (error) {
            const definiteClientError = response && response.status >= 400 && response.status < 500 && response.status !== 429;
            if (definiteClientError) {
                stablePayload = null;
                uncertain = false;
                setBusy(false);
            } else {
                uncertain = true;
                reservationForm.querySelectorAll('input, select, textarea').forEach(control => {
                    if (control.name !== 'website') control.disabled = true;
                });
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'ارسال دوباره با همان اطلاعات';
                }
            }
            if (message) message.textContent = error?.message || 'ارتباط با سرور برقرار نشد؛ برای جلوگیری از ثبت تکراری، با همان اطلاعات دوباره ارسال کن.';
        }
    });

    newButton?.addEventListener('click', () => {
        reservationForm.reset();
        const keyInput = reservationForm.querySelector('[name="idempotency_key"]');
        if (keyInput) keyInput.value = makeNewKey();
        if (successPanel) successPanel.hidden = true;
        if (message) message.textContent = '';
        stablePayload = null;
        uncertain = false;
        setBusy(false);
        reservationForm.querySelectorAll('input, select, textarea').forEach(control => { control.disabled = false; });
    });
}

