<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <title>ثبت‌نام مشتری | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-reserve account-auth-page">
<div class="wrap">
    @include('public.partials.site-logo')
    <section class="frame account-auth-frame">
        <div class="form account-auth-form">
            <span class="account-eyebrow">یک حساب برای پیگیری رزروها</span>
            <h2>ثبت‌نام</h2>
            <p class="sub">اطلاعاتت را ثبت کن تا رزروها و وضعیت تأییدشان همیشه در دسترست باشند.</p>
            @if($errors->any())<div class="form-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('customer.register.store') }}">
                @csrf
                <label><span>نام و نام خانوادگی</span><input name="name" value="{{ old('name') }}" autocomplete="name" maxlength="150" required></label>
                <label><span>شماره موبایل</span><input type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="۰۹۱۲۰۰۰۰۰۰۰" required></label>
                <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="190"></label>
                <label><span>رمز عبور (حداقل ۱۴ نویسه)</span><input type="password" name="password" autocomplete="new-password" minlength="14" required></label>
                <label><span>تکرار رمز عبور</span><input type="password" name="password_confirmation" autocomplete="new-password" minlength="14" required></label>
                <button class="submit" type="submit">ساخت حساب و ادامه </button>
            </form>
            <p class="account-switch">قبلاً حساب ساختی؟ <a href="{{ route('customer.login') }}">وارد شو</a></p>
            <a class="account-back" href="{{ route('home') }}">بازگشت به سایت</a>
        </div>
        <div class="slot hero account-auth-art">
            @if(!empty($media['login_background']['url']))
                <img src="{{ $media['login_background']['url'] }}" alt="{{ $media['login_background']['alt_text'] ?: $settings->name }}">
            @elseif(!empty($media['hero']['url']))
                <img src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: $settings->name }}">
            @endif
            <div class="account-art-copy"><span>{{ $settings->name }}</span><h2>رزروت را<br>با خیال راحت دنبال کن.</h2><p>هر زمان وضعیت درخواستت تغییر کند، نتیجه را در حساب کاربری خودت می‌بینی.</p></div>
        </div>
    </section>
</div>
</body>
</html>