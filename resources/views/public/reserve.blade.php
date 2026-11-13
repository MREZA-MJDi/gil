<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#0d0f11">
    <title>رزرو میز | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-reserve" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <div class="frame">
        <div class="form">
            <h2>رزرو میز</h2>
            <p class="sub">میزتان را از قبل رزرو کنید تا فقط به لذت طعم‌ها فکر کنید. پس از بررسی ظرفیت، نتیجه در حساب مشتری نمایش داده می‌شود.</p>
            <div class="reservation-account-strip">
                <span>درخواست برای</span><b>{{ auth()->user()->name }}</b><small dir="ltr">{{ auth()->user()->phone }}</small>
                <a href="{{ route('customer.dashboard') }}">پیگیری رزروها</a>
            </div>
            <form method="POST" action="{{ route('public.reservations.store') }}" data-public-reservation>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::random(40)) }}">
                <div class="honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
                @if($errors->any())<div class="form-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <div class="row">
                    <label><span>تاریخ</span>
                        <div class="date-time" data-jalali-datetime data-min="{{ now(config('app.timezone'))->addMinutes(30)->format('Y-m-d\\TH:i') }}">
                            <input type="hidden" name="starts_at" value="{{ old('starts_at') }}" data-jalali-value>
                            <div class="date-shell">
                                <button type="button" class="date-trigger" data-jalali-trigger aria-haspopup="dialog" aria-expanded="false">انتخاب تاریخ شمسی</button>
                                <div class="jalali-calendar-popover" data-jalali-calendar role="dialog" aria-label="انتخاب تاریخ شمسی" hidden>
                                    <div class="jalali-calendar-heading"><button type="button" data-jalali-prev aria-label="ماه قبل">‹</button><strong data-jalali-month></strong><button type="button" data-jalali-next aria-label="ماه بعد">›</button></div>
                                    <div class="jalali-calendar-weekdays"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div>
                                    <div class="jalali-calendar-days" data-jalali-days></div>
                                </div>
                            </div>
                            <input type="time" data-jalali-time required value="{{ old('starts_at') ? substr((string)old('starts_at'),11,5) : '18:00' }}" aria-label="ساعت رزرو">
                        </div>
                    </label>
                    <div class="chip-field">
                        <label>تعداد نفرات</label>
                        <input type="hidden" name="party_size" value="{{ old('party_size',2) }}" required>
                        <div class="chips" data-reservation-chip-group data-field="party_size">
                            @foreach([1,2,3,4,5,6,8,10] as $count)
                                <button type="button" class="chip {{ (int)old('party_size',2)===$count?'act':'' }}" data-reservation-chip data-value="{{ $count }}" aria-pressed="{{ (int)old('party_size',2)===$count?'true':'false' }}">{{ number_format($count) }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="chip-field">
                        <label>نوع درخواست</label>
                        <input type="hidden" name="purpose" value="{{ old('purpose','table') }}" required>
                        <div class="chips purpose-chips" data-reservation-chip-group data-field="purpose">
                            @foreach(['table'=>'میز','birthday'=>'تولد','private_event'=>'مراسم','catering'=>'کترینگ'] as $value=>$label)
                                <button type="button" class="chip {{ old('purpose','table')===$value?'act':'' }}" data-reservation-chip data-value="{{ $value }}" aria-pressed="{{ old('purpose','table')===$value?'true':'false' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <label><span>مدت حضور</span><select name="duration_minutes" required>
                        <option value="60" @selected(old('duration_minutes')==60)>۱ ساعت</option><option value="90" @selected(old('duration_minutes',90)==90)>۱ ساعت و نیم</option>
                        <option value="120" @selected(old('duration_minutes')==120)>۲ ساعت</option><option value="180" @selected(old('duration_minutes')==180)>۳ ساعت</option>
                        <option value="240" @selected(old('duration_minutes')==240)>۴ ساعت</option><option value="360" @selected(old('duration_minutes')==360)>۶ ساعت</option>
                    </select></label>
                </div>
                <div class="row">
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email',auth()->user()->email) }}" maxlength="150" autocomplete="email"></label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد یا دورهمی"></label>
                </div>
                <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"></label>
                <label><span>توضیحات (اختیاری)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="مناسبت، حساسیت غذایی و توضیحات تکمیلی">{{ old('notes') }}</textarea></label>
                <button class="submit" type="submit" data-reserve-submit>ثبت رزرو</button>
                <p class="reserve-message" data-reserve-message role="status" aria-live="polite"></p>
                <div class="reserve-success" data-reserve-success hidden role="status"><b>درخواستت ثبت شد.</b><p>کد پیگیری: <strong data-reserve-reference></strong></p><small>وضعیت فعلی: در انتظار تأیید مجموعه.</small><a href="{{ route('customer.dashboard') }}">مشاهده رزروها در حساب من</a><button type="button" data-reserve-new>ثبت درخواست دیگر</button></div>
            </form>
        </div>
        <div class="slot hero reserve-art">
            @if(!empty($media['reservation_cover']['url']))
                <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'رزرو میز' }}" fetchpriority="high">
            @elseif(!empty($media['gallery_1']['url']))
                <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای کافه' }}" fetchpriority="high">
            @elseif(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?: 'فضای دورهمی' }}" fetchpriority="high">
            @endif
            @include('public.partials.reference-nav')
            <div class="reserve-art-copy"><span>تولد · دورهمی · قرار دوستانه</span><h2>یک میز خوب،<br>کنار بگذار.</h2><p>زمان و تعداد مهمان را انتخاب کن؛ برای تأیید نهایی با تو هماهنگ می‌کنیم.</p></div>
        </div>
    </div>
</div>
</body>
</html>