<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="درخواست رزرو میز در {{ $settings->name }}.">
    <title>رزرو میز | {{ $settings->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-reserve">
<div class="wrap">
    <div class="logo">{{ $settings->name ?: 'خانه گیلاسی' }}</div>
    <main class="frame">
        <section class="reserve-form">
            <h1>رزرو میز</h1>
            <p class="sub">میزتان را از قبل رزرو کنید تا فقط به لذت طعم‌ها فکر کنید.</p>

            <form method="POST" action="{{ route('public.reservations.store') }}" data-public-reservation>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::random(40)) }}">
                <div class="kg-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
                @if($errors->any())
                    <div class="kg-form-errors" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="reserve-row">
                    <div><label for="reserve-name">نام و نام خانوادگی</label><input id="reserve-name" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="150" placeholder="مثلاً مریم احمدی"></div>
                    <div><label for="reserve-phone">شماره تماس</label><input id="reserve-phone" name="phone" value="{{ old('phone') }}" type="tel" inputmode="tel" autocomplete="tel" required maxlength="30" placeholder="۰۹۱۲۰۰۰۰۰۰۰"></div>
                </div>
                <div class="reserve-row">
                    <div><label>تاریخ شمسی</label>
                        <div class="kg-jalali-datetime" data-jalali-datetime data-min="{{ now(config('app.timezone'))->addMinutes(30)->format('Y-m-d\\TH:i') }}">
                            <input type="hidden" name="starts_at" value="{{ old('starts_at') }}" data-jalali-value>
                            <div class="kg-jalali-date-shell">
                                <button type="button" class="kg-jalali-date-trigger" data-jalali-trigger aria-haspopup="dialog" aria-expanded="false">انتخاب تاریخ شمسی</button>
                                <div class="jalali-calendar-popover" data-jalali-calendar role="dialog" aria-label="انتخاب تاریخ شمسی" hidden>
                                    <div class="jalali-calendar-heading"><button type="button" data-jalali-prev aria-label="ماه قبل">‹</button><strong data-jalali-month></strong><button type="button" data-jalali-next aria-label="ماه بعد">›</button></div>
                                    <div class="jalali-calendar-weekdays"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div>
                                    <div class="jalali-calendar-days" data-jalali-days></div>
                                </div>
                            </div>
                            <div class="kg-jalali-time-field"><span>ساعت</span><input type="time" data-jalali-time required value="{{ old('starts_at') ? substr((string) old('starts_at'), 11, 5) : '18:00' }}"></div>
                        </div>
                    </div>
                    <div><label for="reserve-purpose">نوع رزرو</label>
                        <select id="reserve-purpose" name="purpose" required>
                            <option value="table" @selected(old('purpose', 'table') === 'table')>رزرو میز</option>
                            <option value="birthday" @selected(old('purpose') === 'birthday')>جشن تولد</option>
                            <option value="private_event" @selected(old('purpose') === 'private_event')>دورهمی خصوصی</option>
                            <option value="catering" @selected(old('purpose') === 'catering')>پذیرایی و کترینگ</option>
                            <option value="consultation" @selected(old('purpose') === 'consultation')>وقت مشاوره</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>تعداد نفرات</label>
                    <div class="reserve-chips" data-reserve-guests>
                        @foreach([1,2,3,4,5,6] as $count)
                            <button type="button" class="reserve-chip {{ (int)old('party_size',2)===$count?'is-active':'' }}" data-party-size="{{ $count }}" aria-pressed="{{ (int)old('party_size',2)===$count?'true':'false' }}">{{ $count===6?'۶+':\Illuminate\Support\Number::format($count, locale: 'fa') }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="party_size" value="{{ old('party_size',2) }}" data-reserve-party-size>
                </div>
                <div class="reserve-row">
                    <div><label for="reserve-duration">مدت حضور</label>
                        <select name="duration_minutes" id="reserve-duration">
                            <option value="60" @selected(old('duration_minutes', 90) == 60)>۱ ساعت</option><option value="90" @selected(old('duration_minutes', 90) == 90)>۱ ساعت و نیم</option><option value="120" @selected(old('duration_minutes') == 120)>۲ ساعت</option><option value="180" @selected(old('duration_minutes') == 180)>۳ ساعت</option><option value="240" @selected(old('duration_minutes') == 240)>۴ ساعت</option><option value="360" @selected(old('duration_minutes') == 360)>۶ ساعت</option>
                        </select>
                    </div>
                    <div><label for="reserve-email">ایمیل (اختیاری)</label><input id="reserve-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="150" placeholder="name@example.com"></div>
                </div>
                <div><label for="reserve-notes">توضیحات (اختیاری)</label><textarea id="reserve-notes" name="notes" maxlength="1000" placeholder="مناسبت، حساسیت غذایی و...">{{ old('notes') }}</textarea></div>
                <button class="reserve-submit" type="submit" data-reserve-submit>ثبت درخواست رزرو</button>
                <p class="reserve-message" data-reserve-message role="status"></p>
                <div class="reserve-success" data-reserve-success hidden role="status">
                    <b>درخواستت ثبت شد.</b>
                    <p>کد پیگیری: <strong data-reserve-reference></strong></p>
                    <small>رزرو پس از بررسی ظرفیت و تماس مجموعه قطعی می‌شود.</small>
                    <button type="button" data-reserve-new>ثبت درخواست دیگر</button>
                </div>
            </form>
        </section>
        <section class="slot hero reserve-image">
            @if(!empty($media['reservation_cover']['url']))
                <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'فضای رزرو خانه گیلاسی' }}" loading="eager">
            @elseif(!empty($media['gallery_1']['url']))
                <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای خانه گیلاسی' }}" loading="eager">
            @elseif(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?: 'چیدمان میز' }}" loading="eager">
            @endif
            @include('public.partials.reference-nav')
            <h2 class="headline">لحظه‌ات را رزرو کن</h2>
        </section>
    </main>
</div>
</body>
</html>