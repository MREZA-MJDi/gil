<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171614">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>رزرو، تولد و مشاوره | {{ $settings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-booking-v2">
    <div class="site-topline"><span>قرار خوب، با برنامه‌ریزی بهتر شروع می‌شود.</span><span class="site-topline-right"><i></i> رزرو و مناسبت‌ها</span></div>
    @include('public.partials.nav')
    @php($reservePhoto = \App\Models\SiteMediaAsset::urlFor('gallery_3', 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1000&q=85'))
    <main class="reserve-page section-wrap cup-stop" data-cup-stop>
        <div class="reserve-intro">
            <span class="gil-eyebrow">برای قرار بعدی‌ات</span>
            <h1>برای قرار بعدی‌ات<br><em>یک میز خوب کنار بگذار.</em></h1>
            <p>برای رزرو میز، جشن تولد، دورهمی خصوصی، مشاوره یا کترینگ درخواست بفرست. ثبت درخواست به معنی تأیید نهایی نیست؛ تیم کافه بعد از بررسی ظرفیت و جزئیات با تو هماهنگ می‌کند.</p>
            <div class="reserve-assurance"><span>✓</span><div><b>درخواست شفاف، هماهنگی انسانی</b><small>زمان، تعداد مهمان و توضیحات ثبت می‌شوند تا همکاران اطلاعات لازم را یک‌جا ببینند.</small></div></div>
            <div class="reserve-side-photo"><img src="{{ $reservePhoto }}" alt="چیدمان میز برای جشن و مراسم" loading="lazy"><span>تولد · دورهمی خصوصی · کترینگ</span></div>
        </div>
        <section class="reserve-form-panel">
            <div class="reserve-form-heading"><span class="gil-eyebrow">درخواست رزرو</span><h2>جزئیات درخواست</h2><p>موارد ستاره‌دار برای پیگیری درخواست ضروری‌اند. از رقم فارسی یا انگلیسی می‌توانی استفاده کنی.</p></div>
            <form method="POST" action="{{ route('public.reservations.store') }}" class="reserve-form" data-jalali-booking>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::random(40) }}">
                <input type="hidden" name="starts_at" value="{{ old('starts_at') }}">
                <div class="reserve-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
                @if ($errors->any())
                    <div class="reserve-errors" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="reserve-form-grid">
                    <label><span>نوع درخواست <b>*</b></span><select name="purpose" required><option value="table" @selected(old('purpose','table')==='table')>رزرو میز</option><option value="birthday" @selected(old('purpose')==='birthday')>جشن تولد</option><option value="consultation" @selected(old('purpose')==='consultation')>وقت مشاوره</option><option value="private_event" @selected(old('purpose')==='private_event')>دورهمی خصوصی / مراسم</option><option value="catering" @selected(old('purpose')==='catering')>پذیرایی و کترینگ</option></select><small>برای ارجاع درست درخواست در پنل مدیریت.</small></label>
                    <label><span>نام و نام خانوادگی <b>*</b></span><input name="customer_name" value="{{ old('customer_name') }}" required maxlength="150" autocomplete="name"><small>نامی که برای پیگیری با آن تماس بگیریم.</small></label>
                    <label><span>شماره تماس <b>*</b></span><input name="phone" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" inputmode="tel"><small>برای تأیید زمان و هماهنگی جزئیات.</small></label>
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email"></label>
                    <label class="reserve-datetime-field"><span>تاریخ و ساعت شمسی <b>*</b></span><input type="text" name="starts_at_jalali" value="{{ old('starts_at_jalali') }}" inputmode="numeric" autocomplete="off" placeholder="۱۴۰۵/۰۸/۰۷ ۱۸:۳۰" maxlength="16" required><small>قالب: سال/ماه/روز ساعت:دقیقه — مثال: ۱۴۰۵/۰۸/۰۷ ۱۸:۳۰. زمان پیشنهادی است و ظرفیت بررسی می‌شود.</small></label>
                    <label><span>تعداد مهمان <b>*</b></span><input type="number" name="party_size" value="{{ old('party_size',2) }}" min="1" max="50" required><small>برای گروه بزرگ‌تر در توضیحات بنویس.</small></label>
                    <label><span>مدت تقریبی (دقیقه) <b>*</b></span><select name="duration_minutes" required><option value="60">۶۰ دقیقه</option><option value="90" selected>۹۰ دقیقه</option><option value="120">۱۲۰ دقیقه</option><option value="180">۱۸۰ دقیقه</option><option value="240">۲۴۰ دقیقه</option><option value="360">۳۶۰ دقیقه</option></select></label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد سارا یا جلسه تیم"></label>
                    <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"><small>برای پیشنهاد مناسب‌تر؛ تعهد قیمت یا پیش‌فاکتور نیست.</small></label>
                    <label class="reserve-notes-field"><span>توضیحات و نیازها (اختیاری)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="مثلاً کیک، تزیین ساده، فضای آرام، محدودیت غذایی یا ساعت انعطاف‌پذیر">{{ old('notes') }}</textarea><small>هر جزئیاتی که به تصمیم‌گیری و هماهنگی کمک کند.</small></label>
                </div>
                <div class="reserve-form-footer"><p>درخواست ابتدا با وضعیت «در انتظار» ذخیره می‌شود و پس از بررسی، همکاران برای تأیید نهایی تماس می‌گیرند.</p><button type="submit" class="gil-button gil-button-dark">ارسال درخواست <span>↗</span></button></div>
            </form>
        </section>
    </main>
    @include('public.partials.footer')
</body>
</html>
