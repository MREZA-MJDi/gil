<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود مدیر | خانه گیلاسی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-login-page">
    <main class="login-card">
        <a class="brand-lockup login-brand" href="{{ route('home') }}"><span class="brand-symbol">خ</span><span><strong>خانه گیلاسی</strong><small>CAFE / RESTAURANT</small></span></a>
        <span class="eyebrow">SECURE ADMIN ACCESS</span>
        <h1>ورود به پنل مدیریت</h1>
        <p>برای دسترسی به سفارش‌ها و صندوق وارد حساب مدیر شوید.</p>
        @if ($errors->any())
            <div class="login-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login.store') }}" class="login-form">
            @csrf
            <label for="email">ایمیل مدیر</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <label for="password">رمز عبور</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <label class="remember-line"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
            <button class="primary-button" type="submit">ورود امن به داشبورد ←</button>
        </form>
        <div class="login-foot">خانه گیلاسی · پنل عملیات داخلی</div>
    </main>
</body>
</html>
