<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#f4efe6"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>رزرو، تولد و مشاوره | {{ $settings->name }}</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="public-site">
<div class="site-topline"><span>برای یک قرار خوب، از همین‌جا شروع کن.</span><span class="site-topline-right"><i></i> رزرو و برنامه‌ریزی</span></div>
@include('public.partials.nav')
<main class="reserve-page section-wrap">
<div class="reserve-intro"><span class="section-kicker">YOUR MOMENT / OUR CARE</span><h1>قرار بعدی‌ات را<br><em>با خیال راحت برنامه‌ریزی کن.</em></h1><p>برای رزرو میز، جشن تولد، دورهمی خصوصی یا مشاوره برای مراسم، درخواستت را ثبت کن. تیم کافه زمان و جزئیات را بررسی می‌کند و برای تأیید نهایی با تو تماس می‌گیرد.</p><div class="reserve-assurance"><span>✓</span><div><b>ثبت درخواست، بدون تأیید خودکار</b><small>بعد از بررسی ظرفیت و جزئیات، تأیید نهایی را از ما دریافت می‌کنی.</small></div></div></div>
<section class="reserve-form-panel"><div class="reserve-form-heading"><span class="section-kicker">BOOKING REQUEST</span><h2>جزئیات درخواست</h2><p>فیلدهای ستاره‌دار برای پیگیری درخواست لازم‌اند.</p></div>
<form method="POST" action="{{ route('public.reservations.store') }}" class="reserve-form">
@csrf
<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::random(40) }}">
<div class="reserve-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
@if ($errors->any())<div class="reserve-errors" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="reserve-form-grid">
<label><span>نوع درخواست <b>*</b></span><select name="purpose" required><option value="table">رزرو میز</option><option value="birthday" @selected(old('purpose')==='birthday')>جشن تولد</option><option value="consultation" @selected(old('purpose')==='consultation')>وقت مشاوره</option><option value="private_event" @selected(old('purpose')==='private_event')>دورهمی خصوصی / مراسم</option><option value="catering" @selected(old('purpose')==='catering')>پذیرایی و کترینگ</option></select><small>مشخص می‌کند درخواست به کدام جریان کاری ارجاع شود.</small></label>
<label><span>نام و نام خانوادگی <b>*</b></span><input name="customer_name" value="{{ old('customer_name') }}" required maxlength="150" autocomplete="name"><small>نامی که برای پیگیری با آن تماس بگیریم.</small></label>
<label><span>شماره تماس <b>*</b></span><input name="phone" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" inputmode="tel"><small>برای تأیید زمان و هماهنگی جزئیات.</small></label>
<label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email"></label>
<label><span>تاریخ و ساعت مدنظر <b>*</b></span><input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" min="{{ now()->addMinutes(30)->format('Y-m-d\TH:i') }}" step="900" required><small>زمان پیشنهادی است؛ ظرفیت را همکاران تأیید می‌کنند.</small></label>
<label><span>تعداد مهمان <b>*</b></span><input type="number" name="party_size" value="{{ old('party_size',2) }}" min="1" max="50" required><small>برای گروه‌های بزرگ‌تر در توضیحات بنویس.</small></label>
<label><span>مدت تقریبی (دقیقه) <b>*</b></span><select name="duration_minutes" required><option value="60">۶۰ دقیقه</option><option value="90" selected>۹۰ دقیقه</option><option value="120">۱۲۰ دقیقه</option><option value="180">۱۸۰ دقیقه</option><option value="240">۲۴۰ دقیقه</option><option value="360">۳۶۰ دقیقه</option></select></label>
<label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد سارا یا جلسه تیم"></label>
<label><span>بودجه حدودی (تومان، اختیاری)</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"><small>برای پیشنهاد مناسب‌تر؛ تعهد قیمت یا پیش‌فاکتور نیست.</small></label>
<label class="reserve-notes-field"><span>توضیحات و نیازها (اختیاری)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="مثلاً کیک، تزیین ساده، نیاز به فضای آرام یا ساعت انعطاف‌پذیر">{{ old('notes') }}</textarea><small>جزئیاتی که به تصمیم‌گیری و هماهنگی کمک می‌کند.</small></label>
</div>
<div class="reserve-form-footer"><p>درخواست در پنل مجموعه ثبت می‌شود و تا تماس همکاران «در انتظار» باقی می‌ماند.</p><button type="submit" class="button button-dark">ارسال درخواست <span>↗</span></button></div>
</form></section></main>
@include('public.partials.footer')
@include('public.partials.traveling-cup')
</body></html>
