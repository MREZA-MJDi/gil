        <section class="online-order-panel section-wrap" id="online-cart" aria-live="polite">
            <div class="online-order-heading"><span class="section-kicker">سفارش بیرون‌بر</span><h2>سبد سفارش بیرون‌بر</h2><p>اقلام را اضافه کن؛ سفارش پس از ثبت در پنل مجموعه دیده می‌شود. مبلغ نهایی در سرور محاسبه می‌شود و پرداخت در این نسخه هنگام تحویل یا در صندوق است.</p></div>
            <div id="online-cart-empty" class="online-cart-empty">هنوز چیزی به سبد اضافه نکرده‌ای.</div>
            <div id="online-cart-items" class="online-cart-items"></div>
            <div class="online-cart-total"><span>جمع تخمینی سبد</span><b id="online-cart-total">۰ {{ $settings->currency_label }}</b><small>قیمت و افزودنی‌ها در سرور دوباره بررسی می‌شوند.</small></div>
            <form id="online-checkout-form" class="online-checkout-form">
                    <label><span>نام و نام خانوادگی *</span><input name="customer_name" autocomplete="name" required maxlength="150"></label>
                <label><span>شماره تماس *</span><input name="customer_phone" autocomplete="tel" inputmode="tel" required maxlength="30"></label>
                <label class="online-checkout-note"><span>توضیح سفارش (اختیاری)</span><textarea name="customer_note" rows="2" maxlength="2000" placeholder="مثلاً بدون شکر یا توضیح ضروری"></textarea></label>
                <fieldset class="online-payment-preference">
                    <legend>روش پرداخت دلخواه</legend>
                    <label><input type="radio" name="payment_preference" value="at_counter" checked><span><b>هنگام تحویل / در صندوق</b><small>گزینهٔ پیشنهادی اگر هنوز تصمیم نگرفته‌ای.</small></span></label>
                    <label><input type="radio" name="payment_preference" value="card_terminal"><span><b>کارت‌خوان</b><small>پرداخت را کارکنان ثبت می‌کنند.</small></span></label>
                    <label><input type="radio" name="payment_preference" value="cash"><span><b>نقدی</b><small>این انتخاب به‌تنهایی به معنی پرداخت نیست.</small></span></label>
                    <p>ترجیح پرداخت ثبت می‌شود؛ سفارش زمانی تسویه‌شده است که مبلغ واقعی در صندوق ثبت شود.</p>
                </fieldset>
                <button class="button button-dark" type="submit" id="online-checkout-submit" disabled>ثبت سفارش بیرون‌بر <span>↗</span></button>
                <p id="online-checkout-message" role="status"></p>
            </form>
        </section>
