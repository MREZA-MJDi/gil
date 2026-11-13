<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="درخواست رزرو میز و مناسبت‌ها در {{ $settings->name }}.">
    <!-- Legacy resource entry resources/css/public.css is retained for independent QR Menu and reservation receipt pages; this page loads public-reference.css only. -->
    <title>رزرو میز | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-reserve" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <section class="frame">
        <div class="form">
            <span class="form-eyebrow">رزرو و برنامه‌ریزی قرار بعدی‌ات</span>
            <h2>رزرو میز</h2>
            <p class="sub">میزتان را از قبل رزرو کنید تا فقط به لذت طعم‌ها فکر کنید. درخواست پس از بررسی ظرفیت توسط مجموعه تأیید می‌شود.</p>

            <form method="POST" action="{{ route('public.reservations.store') }}" data-public-reservation>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::random(40)) }}">
                <div class="honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>

                @if($errors->any())
                    <div class="form-errors" role="alert">
                        <b>درخواست ثبت نشد.</b>
                        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="row">
                    <label><span>نام و نام خانوادگی *</span><input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="150" placeholder="نام شما"></label>
                    <label><span>شماره تماس *</span><input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="0912 123 4567"></label>
                </div>

                <div class="row">
                    <label><span>تاریخ شمسی و ساعت *</span>
                        <div class="date-time" data-jalali-datetime data-min="{{ now(config('app.timezone'))->addMinutes(30)->format('Y-m-d\\TH:i') }}">
                            <input type="hidden" name="starts_at" value="{{ old('starts_at') }}" data-jalali-value>
                            <div class="date-shell">
                                <button type="button" class="date-trigger" data-jalali-trigger aria-haspopup="dialog" aria-expanded="false">انتخاب تاریخ شمسی</button>
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
                            <input type="time" data-jalali-time required value="{{ old('starts_at') ? substr((string) old('starts_at'), 11, 5) : '18:00' }}" aria-label="ساعت رزرو">
                        </div>
                    </label>
                    <label><span>تعداد نفرات *</span><input type="number" name="party_size" value="{{ old('party_size', 2) }}" min="1" max="50" required></label>
                </div>

                <div class="row">
                    <label><span>نوع درخواست *</span>
                        <select name="purpose" required>
                            <option value="table" @selected(old('purpose', 'table') === 'table')>رزرو میز</option>
                            <option value="birthday" @selected(old('purpose') === 'birthday')>جشن تولد</option>
                            <option value="consultation" @selected(old('purpose') === 'consultation')>وقت مشاوره</option>
                            <option value="private_event" @selected(old('purpose') === 'private_event')>دورهمی خصوصی / مراسم</option>
                            <option value="catering" @selected(old('purpose') === 'catering')>پذیرایی و کترینگ</option>
                        </select>
                    </label>
                    <label><span>مدت تقریبی حضور *</span>
                        <select name="duration_minutes" required>
                            <option value="60" @selected(old('duration_minutes') == 60)>۱ ساعت</option>
                            <option value="90" @selected(old('duration_minutes', 90) == 90)>۱ ساعت و نیم</option>
                            <option value="120" @selected(old('duration_minutes') == 120)>۲ ساعت</option>
                            <option value="180" @selected(old('duration_minutes') == 180)>۳ ساعت</option>
                            <option value="240" @selected(old('duration_minutes') == 240)>۴ ساعت</option>
                            <option value="360" @selected(old('duration_minutes') == 360)>۶ ساعت</option>
                        </select>
                    </label>
                </div>

                <div class="row">
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" placeholder="name@example.com"></label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد یا دورهمی"></label>
                </div>

                <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"></label>
                <label><span>توضیحات (اختیاری)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="مناسبت، نیازها یا توضیحات تکمیلی">{{ old('notes') }}</textarea></label>

                <button class="submit" type="submit" data-reserve-submit>ثبت درخواست رزرو ↗</button>
                <p class="reserve-message" data-reserve-message role="status" aria-live="polite"></p>
                <div class="reserve-success" data-reserve-success hidden role="status">
                    <b>درخواستت ثبت شد.</b>
                    <p>کد پیگیری: <strong data-reserve-reference></strong></p>
                    <small>وضعیت فعلی: در انتظار تأیید مجموعه.</small>
                    <button type="button" data-reserve-new>ثبت درخواست دیگر</button>
                </div>
            </form>
        </div>

        <div class="slot hero reserve-art">
            @if(!empty($media['reservation_cover']['url']))
                <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'فضای رزرو خانه گیلاسی' }}" fetchpriority="high">
            @elseif(!empty($media['gallery_1']['url']))
                <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'چیدمان میز' }}" fetchpriority="high">
            @elseif(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?: 'فضای دورهمی' }}" fetchpriority="high">
            @elseif(!empty($media['story_small']['url']))
                <img src="{{ $media['story_small']['url'] }}" alt="{{ $media['story_small']['alt_text'] ?: 'فضای کافه' }}" fetchpriority="high">
            @endif
            @include('public.partials.nav')
            <div class="reserve-art-copy">
                <span>تولد · دورهمی · قرار دوستانه</span>
                <h2>یک میز خوب،<br><em>کنار بگذار.</em></h2>
                <p>زمان و تعداد مهمان را انتخاب کن؛ برای تأیید نهایی با تو هماهنگ می‌کنیم.</p>
            </div>
        </div>
    </section>
</div>
</body>
</html>