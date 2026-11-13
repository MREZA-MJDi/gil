<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>حساب مشتری | {{ config('app.name') }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-menu account-page" id="top">
<div class="m">
    <header class="top menu-top" data-kg-header>
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}"><span>{{ config('app.name') }}</span></a>
        <nav class="menu-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a><a href="{{ route('public.menu') }}">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
            <a href="{{ route('customer.dashboard') }}" class="act" aria-current="page">حساب من</a>
        </nav>
        <form method="POST" action="{{ route('customer.logout') }}" class="account-logout">@csrf<button type="submit">خروج</button></form>
    </header>
    <main class="customer-dashboard">
        <section class="customer-welcome">
            <div><span class="account-eyebrow">حساب مشتری</span><h1>سلام، {{ $customer->name }}</h1><p>درخواست‌های رزرو و آخرین وضعیت بررسی مجموعه را از اینجا دنبال کن.</p></div>
            <a href="{{ route('public.reservations.create') }}" class="account-primary-link">درخواست رزرو جدید +</a>
        </section>
        <section class="customer-account-meta">
            <div><small>شماره موبایل حساب</small><b dir="ltr">{{ $customer->phone }}</b></div>
            @if($customer->email)<div><small>ایمیل</small><b>{{ $customer->email }}</b></div>@endif
            <div><small>تعداد درخواست‌ها</small><b>{{ number_format($reservations->total()) }}</b></div>
        </section>
        <section class="customer-reservations">
            <div class="customer-section-heading"><div><span>پیگیری درخواست‌ها</span><h2>رزروهای من</h2></div><button type="button" data-account-refresh>تازه‌سازی وضعیت ↻</button></div>
            @if($reservations->count())
                <div class="customer-reservation-list">
                    @foreach($reservations as $reservation)
                        @php
                            $status = $reservation->status instanceof \BackedEnum ? $reservation->status->value : (string) $reservation->status;
                            $statusText = match ($status) {
                                'pending' => 'در انتظار بررسی',
                                'confirmed' => 'تأیید شده',
                                'arrived' => 'مراجعه ثبت شده',
                                'cancelled' => 'لغو / تأیید نشده',
                                'no_show' => 'عدم مراجعه',
                                default => 'در حال بررسی',
                            };
                            $purposeText = match ($reservation->purpose) {
                                'birthday' => 'تولد',
                                'consultation' => 'مشاوره',
                                'private_event' => 'مراسم خصوصی',
                                'catering' => 'کترینگ',
                                default => 'رزرو میز',
                            };
                        @endphp
                        <article class="customer-reservation-card" id="reservation-{{ $reservation->id }}">
                            <div class="customer-reservation-top"><div><span>کد پیگیری</span><b>{{ $reservation->reference }}</b></div><span class="reservation-status status-{{ $status }}">{{ $statusText }}</span></div>
                            <div class="customer-reservation-details">
                                <div><small>تاریخ و ساعت</small><b data-jalali-date="{{ $reservation->starts_at->toIso8601String() }}">{{ $reservation->starts_at->format('Y/m/d H:i') }}</b></div>
                                <div><small>نوع درخواست</small><b>{{ $purposeText }}</b></div>
                                <div><small>تعداد نفرات</small><b>{{ number_format($reservation->party_size) }}</b></div>
                                <div><small>مدت حضور</small><b>{{ number_format($reservation->duration_minutes) }} دقیقه</b></div>
                                @if($reservation->table)<div><small>میز تخصیص‌یافته</small><b>{{ $reservation->table->name }}</b></div>@endif
                            </div>
                            @if($status === 'pending')<p class="customer-status-note">درخواستت ثبت شده و در انتظار بررسی مجموعه است. برای دیدن آخرین وضعیت، این صفحه را تازه کن.</p>
                            @elseif($status === 'confirmed')<p class="customer-status-note is-confirmed">رزروت تأیید شده است. {{ $reservation->table ? 'میز '.$reservation->table->name.' برایت در نظر گرفته شده.' : 'جزئیات میز با مجموعه هماهنگ می‌شود.' }}</p>
                            @elseif(in_array($status, ['cancelled', 'no_show'], true))<p class="customer-status-note is-cancelled">این درخواست در وضعیت فعال نیست. برای هماهنگی بیشتر با مجموعه تماس بگیر.</p>@endif
                        </article>
                    @endforeach
                </div>
                @if($reservations->hasPages())
                    <nav class="account-pagination" aria-label="صفحه‌بندی رزروها">
                        @if($reservations->previousPageUrl())<a href="{{ $reservations->previousPageUrl() }}">← قبلی</a>@endif
                        <span>صفحه {{ number_format($reservations->currentPage()) }} از {{ number_format($reservations->lastPage()) }}</span>
                        @if($reservations->nextPageUrl())<a href="{{ $reservations->nextPageUrl() }}">بعدی →</a>@endif
                    </nav>
                @endif
            @else
                <div class="customer-empty"><span>◇</span><b>هنوز رزروی ثبت نکرده‌ای.</b><p>برای ثبت اولین درخواست، زمان و تعداد نفرات را انتخاب کن.</p><a href="{{ route('public.reservations.create') }}">رزرو میز</a></div>
            @endif
        </section>
    </main>
</div>
</body>
</html>