/* Reservation form: sends the backend's exact accepted fields, ISO-local date/time. */
const reservationForm = document.querySelector('[data-public-reservation]');
if (reservationForm) {
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

