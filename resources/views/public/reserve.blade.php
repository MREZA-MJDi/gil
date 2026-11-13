<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ثبت درخواست رزرو میز، جشن و دورهمی در {{ $settings->name }}.">
    <title>رزرو و مناسبت‌ها | {{ $settings->name }}</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site kg-reserve-page" id="top">
    <div class="kg-topline"><span>🍒 یک قرار خوب، با برنامه‌ریزی بهتر</span><span>رزرو میز و مناسبت‌ها</span></div>
    @include('public.partials.nav')

    <main class="kg-reserve-main">
        <section class="kg-reserve-intro">
            <div class="kg-reserve-intro-copy">
                <span class="kg-eyebrow">رزرو و برنامه‌ریزی قرار بعدی‌ات</span>
                <h1>یک میز خوب،<br><em>کنار بگذار.</em></h1>
                <p>برای رزرو میز، جشن تولد، دورهمی خصوصی، مشاوره یا کترینگ درخواست بفرست. درخواست پس از بررسی ظرفیت توسط همکاران مجموعه تأیید می‌شود.</p>
                <div class="kg-reserve-assurance"><span>✓</span><div><b>ثبت درخواست، نه تأیید خودکار</b><small>زمان و تعداد مهمان ثبت می‌شود تا مجموعه درخواست را بررسی و برای هماهنگی با تو تماس بگیرد.</small></div></div>
            </div>
            <div class="kg-reserve-art">
                @if(!empty($media['reservation_cover']['url']))
                    <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'تصویر صفحه رزرو' }}" loading="lazy">
                @elseif(!empty($media['gallery_1']['url']))
                    <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?? 'چیدمان میز' }}" loading="lazy">
                @elseif(!empty($media['gallery_3']['url']))
                    <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?? 'چیدمان میز' }}" loading="lazy">
                @elseif(!empty($media['story_small']['url']))
                    <img src="{{ $media['story_small']['url'] }}" alt="{{ $media['story_small']['alt_text'] ?? 'فضای کافه' }}" loading="lazy">
                @else
                    <div class="kg-reserve-art-fallback"><span>🍒</span><b>میزت را انتخاب کن،<br>لحظه‌ات را بساز.</b></div>
                @endif
                <span class="kg-reserve-art-tag">تولد · دورهمی · کترینگ</span>
            </div>
        </section>

        <section class="kg-reserve-form-wrap">
            <div class="kg-reserve-form-intro">
                <span class="kg-eyebrow">درخواست رزرو</span>
                <h2>جزئیات قرار تو</h2>
                <p>موارد ستاره‌دار برای ثبت درخواست ضروری‌اند. تاریخ و ساعت بر اساس زمان مجموعه (تهران) بررسی می‌شود.</p>
            </div>

            <form class="kg-reserve-form" method="POST" action="{{ route('public.reservations.store') }}" data-public-reservation>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::random(40)) }}">
                <div class="kg-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>

                @if($errors->any())
                    <div class="kg-form-errors" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="kg-reserve-fields">
                    <label><span>نوع درخواست <b>*</b></span>
                        <select name="purpose" required>
                            <option value="table" @selected(old('purpose', 'table') === 'table')>رزرو میز</option>
                            <option value="birthday" @selected(old('purpose') === 'birthday')>جشن تولد</option>
                            <option value="consultation" @selected(old('purpose') === 'consultation')>وقت مشاوره</option>
                            <option value="private_event" @selected(old('purpose') === 'private_event')>دورهمی خصوصی / مراسم</option>
                            <option value="catering" @selected(old('purpose') === 'catering')>پذیرایی و کترینگ</option>
                        </select>
                    </label>
                    <label><span>نام و نام خانوادگی <b>*</b></span><input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="150" placeholder="نام شما"></label>
                    <label><span>شماره تماس <b>*</b></span><input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="0912 123 4567"></label>
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="150" placeholder="name@example.com"></label>
                    <label class="kg-reservation-date-field"><span>تاریخ و ساعت پیشنهادی <b>*</b></span>
                        <div class="kg-jalali-datetime" data-jalali-datetime data-min="{{ now(config('app.timezone'))->addMinutes(30)->format('Y-m-d\\TH:i') }}">
                            <input type="hidden" name="starts_at" value="{{ old('starts_at') }}" data-jalali-value>
                            <div class="kg-jalali-date-shell">
                                <button type="button" class="kg-jalali-date-trigger" data-jalali-trigger aria-haspopup="dialog" aria-expanded="false">انتخاب تاریخ شمسی</button>
                                <div class="jalali-calendar-popover" data-jalali-calendar role="dialog" aria-label="انتخاب تاریخ شمسی" hidden>
                                    <div class="jalali-calendar-heading">
                                        <button type="button" data-jalali-prev aria-label="ماه قبل">‹</button>
                                        <strong data-jalali-month></strong>
                                        <button type="button" data-jalali-next aria-label="ماه بعد">›</button>
                                    </div>
                                    <div class="jalali-calendar-weekdays"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div>
                                    <div class="jalali-calendar-days" data-jalali-days></div>
                                </div>
                            </div>
                            <div class="kg-jalali-time-field"><span>ساعت</span><input type="time" data-jalali-time required value="{{ old('starts_at') ? substr((string) old('starts_at'), 11, 5) : '18:00' }}"></div>
                        </div>
                        <small>تاریخ شمسی و ساعت به وقت تهران ثبت می‌شود؛ ظرفیت هنگام بررسی درخواست کنترل خواهد شد.</small>
                    </label>
                    <label><span>تعداد مهمان <b>*</b></span><input type="number" name="party_size" value="{{ old('party_size', 2) }}" min="1" max="50" required></label>
                    <label><span>مدت تقریبی حضور <b>*</b></span>
                        <select name="duration_minutes" required>
                            <option value="60" @selected(old('duration_minutes') == 60)>۱ ساعت</option>
                            <option value="90" @selected(old('duration_minutes', 90) == 90)>۱ ساعت و نیم</option>
                            <option value="120" @selected(old('duration_minutes') == 120)>۲ ساعت</option>
                            <option value="180" @selected(old('duration_minutes') == 180)>۳ ساعت</option>
                            <option value="240" @selected(old('duration_minutes') == 240)>۴ ساعت</option>
                            <option value="360" @selected(old('duration_minutes') == 360)>۶ ساعت</option>
                        </select>
                    </label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد یا جلسه دوستانه"></label>
                    <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"></label>
                    <label class="kg-reserve-notes"><span>توضیحات و نیازها (اختیاری)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="مثلاً کیک، تزیین ساده، فضای آرام یا محدودیت غذایی">{{ old('notes') }}</textarea></label>
                </div>

                <div class="kg-reserve-form-footer">
                    <p>درخواست ابتدا با وضعیت «در انتظار تأیید» ثبت می‌شود. رزرو پس از هماهنگی و تأیید مجموعه قطعی خواهد شد.</p>
                    <button class="kg-button kg-button-primary" type="submit" data-reserve-submit>ارسال درخواست <span>↗</span></button>
                </div>
                <p class="kg-form-message" data-reserve-message role="status"></p>
                <div class="kg-reserve-success" data-reserve-success hidden role="status">
                    <span>✓</span><div><b>درخواستت ثبت شد.</b><p>کد پیگیری: <strong data-reserve-reference></strong></p><small>وضعیت فعلی: در انتظار تأیید مجموعه.</small></div>
                    <button type="button" data-reserve-new>ثبت درخواست دیگر</button>
                </div>
            </form>
        </section>
    </main>
    @include('public.partials.footer')
</body>
</html>
