<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#0d0f11">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ثبت درخواست رزرو میز و مناسبت‌ها در {{ $settings->name }}.">
    <title>رزرو میز | {{ $settings->name }}</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-reserve" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <main class="frame">
        <section class="form" aria-labelledby="reserve-title">
            <h1 id="reserve-title">رزرو میز</h1>
            <p class="sub">برای رزرو و برنامه‌ریزی قرار بعدی، زمان و تعداد نفرات را انتخاب کنید. درخواست پس از بررسی ظرفیت توسط همکاران مجموعه تأیید می‌شود.</p>

            <form method="POST" action="{{ route('public.reservations.store') }}" data-public-reservation>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::random(40)) }}">
                <div class="kg-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
                @if($errors->any())
                    <div class="ok is-visible" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
                    <label><span>نام و نام خانوادگی <b>*</b></span><input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="150" placeholder="مثلاً مریم احمدی"></label>
                    <label><span>شماره تماس <b>*</b></span><input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="۰۹۱۲۰۰۰۰۰۰۰"></label>
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="150" placeholder="name@example.com"></label>

                    <div class="field-wide">
                        <label>تاریخ و ساعت پیشنهادی <b>*</b></label>
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
                            <label class="kg-jalali-time-field"><span>ساعت</span><input type="time" data-jalali-time required value="{{ old('starts_at') ? substr((string) old('starts_at'), 11, 5) : '18:00' }}"></label>
                        </div>
                        <small class="hint">تاریخ شمسی و ساعت به وقت مجموعه ثبت می‌شود؛ ظرفیت هنگام بررسی درخواست کنترل خواهد شد.</small>
                    </div>

                    <label><span>تعداد نفرات <b>*</b></span><input type="number" name="party_size" value="{{ old('party_size', 2) }}" min="1" max="50" required></label>
                    <label><span>مدت حضور <b>*</b></span>
                        <select name="duration_minutes" required>
                            <option value="60" @selected(old('duration_minutes') == 60)>۱ ساعت</option>
                            <option value="90" @selected(old('duration_minutes', 90) == 90)>۱ ساعت و نیم</option>
                            <option value="120" @selected(old('duration_minutes') == 120)>۲ ساعت</option>
                            <option value="180" @selected(old('duration_minutes') == 180)>۳ ساعت</option>
                            <option value="240" @selected(old('duration_minutes') == 240)>۴ ساعت</option>
                            <option value="360" @selected(old('duration_minutes') == 360)>۶ ساعت</option>
                        </select>
                    </label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد یا دورهمی دوستانه"></label>
                    <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"></label>
                    <label class="field-wide"><span>توضیحات (اختیاری)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="مناسبت، نیازهای ویژه یا محدودیت غذایی">{{ old('notes') }}</textarea></label>
                </div>

                <button class="submit" type="submit" data-reserve-submit>ثبت درخواست رزرو</button>
                <p class="reserve-status">درخواست ابتدا با وضعیت «در انتظار تأیید» ثبت می‌شود؛ رزرو پس از هماهنگی با شما قطعی خواهد شد.</p>
                <div class="ok" data-reserve-message role="status"></div>
                <div class="kg-reserve-success" data-reserve-success hidden role="status">
                    <span>✓</span><div><b>درخواستت ثبت شد.</b><p>کد پیگیری: <strong data-reserve-reference></strong></p><small>وضعیت فعلی: در انتظار تأیید مجموعه.</small></div>
                    <button type="button" data-reserve-new>ثبت درخواست دیگر</button>
                </div>
            </form>
        </section>

        <section class="slot hero" aria-label="فضای خانه گیلاسی">
            @if(!empty($media['reservation_cover']['url']))
                <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'فضای خانه گیلاسی' }}" loading="eager">
            @elseif(!empty($media['gallery_1']['url']))
                <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?? 'چیدمان میز' }}" loading="eager">
            @elseif(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?? 'چیدمان میز' }}" loading="eager">
            @endif
            <header class="kg-public-nav" data-kg-header>
                <button class="burger" type="button" data-kg-menu-toggle aria-expanded="false" aria-controls="reserve-nav" aria-label="باز کردن منو"><i></i><i></i></button>
                <a href="{{ route('home') }}" class="brand">{{ $settings->name }}</a>
                <nav class="nav-links" id="reserve-nav" data-kg-nav aria-label="ناوبری اصلی">
                    <a href="{{ route('home') }}">خانه</a>
                    <a href="{{ route('public.menu') }}">منو</a>
                    <a href="{{ route('public.reservations.create') }}" class="cta" aria-current="page">رزرو میز</a>
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">خروج</button></form>
                        @else
                            <a href="{{ route('admin.login') }}">ورود کارکنان</a>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}">ورود کارکنان</a>
                    @endauth
                </nav>
            </header>
            <div class="headline">لحظه‌های خوب<br>از یک میز شروع می‌شوند.</div>
        </section>
    </main>
</div>
</body>
</html>
