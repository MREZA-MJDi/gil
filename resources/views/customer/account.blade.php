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
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-account-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}"><span>{{ config('app.name') }}</span></a>
        <nav class="menu-nav" id="kg-account-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a><a href="{{ route('public.menu') }}">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
        </nav>
        @include('public.partials.profile-link')
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
        @php
            $eligibleFeedbackOrders = $orders->filter(fn ($order) => $order->customerFeedback->isEmpty());
        @endphp
        <section class="customer-feedback-panel" id="customer-feedback">
            <div class="customer-section-heading">
                <div><span>صدای مشتری</span><h2>ثبت بازخورد</h2></div>
                <small>نظر تو مستقیماً برای مجموعه ارسال می‌شود.</small>
            </div>
            @if(session('status'))
                <div class="customer-feedback-flash" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="customer-feedback-error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('customer.feedback.store') }}" class="customer-feedback-form">
                @csrf
                <div class="customer-feedback-form-grid">
                    <label class="customer-feedback-field">
                        <span>موضوع بازخورد</span>
                        <select name="kind" required>
                            <option value="">انتخاب کن</option>
                            <option value="praise" @selected(old('kind') === 'praise')>قدردانی از تجربه خوب</option>
                            <option value="suggestion" @selected(old('kind') === 'suggestion')>پیشنهاد برای بهتر شدن</option>
                            <option value="complaint" @selected(old('kind') === 'complaint')>ثبت مشکل یا شکایت</option>
                        </select>
                    </label>
                    <label class="customer-feedback-field">
                        <span>سفارش مرتبط <small>(اختیاری)</small></span>
                        <select name="order_id">
                            <option value="">بازخورد عمومی؛ بدون اتصال به سفارش</option>
                            @foreach($eligibleFeedbackOrders as $order)
                                <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                    {{ $order->number }} · {{ number_format((int) $order->total) }} تومان
                                </option>
                            @endforeach
                        </select>
                        @if($eligibleFeedbackOrders->isEmpty())
                            <small>در حال حاضر سفارش تحویل‌شده و ثبت‌نشده‌ای برای این حساب پیدا نشد؛ همچنان می‌توانی بازخورد عمومی بفرستی.</small>
                        @else
                            <small>فقط سفارش‌های تحویل‌شده‌ای نمایش داده می‌شوند که شماره موبایل آن‌ها با این حساب مطابقت دارد.</small>
                        @endif
                    </label>
                </div>
                <fieldset class="customer-feedback-rating">
                    <legend>امتیاز تجربه‌ات از ۱ تا ۵</legend>
                    <div>
                        @foreach([1 => 'خیلی ضعیف', 2 => 'ضعیف', 3 => 'معمولی', 4 => 'خوب', 5 => 'عالی'] as $score => $label)
                            <label>
                                <input type="radio" name="rating" value="{{ $score }}" required @checked((string) old('rating') === (string) $score)>
                                <span>{{ $score }} ★</span>
                                <small>{{ $label }}</small>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="customer-feedback-field customer-feedback-message">
                    <span>توضیحات</span>
                    <textarea name="message" rows="4" minlength="5" maxlength="5000" required placeholder="چه چیزی خوب بود؟ چه چیزی باید بهتر شود؟">{{ old('message') }}</textarea>
                </label>
                <div class="customer-feedback-form-footer">
                    <p>اطلاعات حساب تو همراه بازخورد ثبت می‌شود تا مجموعه بتواند پیگیری کند. اطلاعاتت در صفحه عمومی نمایش داده نمی‌شود.</p>
                    <button type="submit">ارسال بازخورد</button>
                </div>
            </form>
        </section>

        <section class="customer-feedback-history" id="customer-feedback-history">
            <div class="customer-section-heading">
                <div><span>پیگیری پاسخ مجموعه</span><h2>بازخوردهای من</h2></div>
            </div>
            @if($feedback->count())
                <div class="customer-feedback-list">
                    @foreach($feedback as $entry)
                        @php
                            $feedbackStatus = $entry->status instanceof \BackedEnum ? $entry->status->value : (string) $entry->status;
                            $feedbackStatusText = match ($feedbackStatus) {
                                'new' => 'ثبت‌شده؛ در انتظار بررسی',
                                'in_progress' => 'در حال بررسی',
                                'resolved' => 'رسیدگی‌شده',
                                'closed' => 'بسته‌شده',
                                default => 'در انتظار بررسی',
                            };
                            $feedbackKindText = match ($entry->kind) {
                                'complaint' => 'شکایت',
                                'suggestion' => 'پیشنهاد',
                                'praise' => 'قدردانی',
                                default => 'بازخورد',
                            };
                        @endphp
                        <article class="customer-feedback-card">
                            <div class="customer-feedback-card-head">
                                <div><b>{{ $feedbackKindText }}</b><small>{{ $entry->created_at?->format('Y/m/d · H:i') }}@if($entry->order) · سفارش {{ $entry->order->number }}@endif</small></div>
                                <span class="customer-feedback-status status-{{ $feedbackStatus }}">{{ $feedbackStatusText }}</span>
                            </div>
                            <div class="customer-feedback-card-rating">امتیاز تو: {{ number_format((int) $entry->rating) }} از ۵</div>
                            <p class="customer-feedback-card-message">{{ $entry->message }}</p>
                            @if($entry->admin_response)
                                <div class="customer-feedback-reply">
                                    <b>پاسخ مجموعه</b>
                                    <p>{{ $entry->admin_response }}</p>
                                    @if($entry->responder)<small>پاسخ از طرف {{ $entry->responder->name }}</small>@endif
                                    @if($entry->responded_at)<small> · {{ $entry->responded_at->format('Y/m/d · H:i') }}</small>@endif
                                </div>
                            @else
                                <p class="customer-feedback-awaiting">بازخوردت ثبت شده است؛ پس از بررسی، پاسخ مجموعه در همین قسمت نمایش داده می‌شود.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
                @if($feedback->hasPages())
                    <nav class="account-pagination" aria-label="صفحه‌بندی بازخوردها">
                        @if($feedback->previousPageUrl())<a href="{{ $feedback->previousPageUrl() }}#customer-feedback-history">← قبلی</a>@endif
                        <span>صفحه {{ number_format($feedback->currentPage()) }} از {{ number_format($feedback->lastPage()) }}</span>
                        @if($feedback->nextPageUrl())<a href="{{ $feedback->nextPageUrl() }}#customer-feedback-history">بعدی →</a>@endif
                    </nav>
                @endif
            @else
                <div class="customer-feedback-empty">هنوز بازخوردی ثبت نکرده‌ای. بعد از تجربه‌ات، نظرت را برای مجموعه بفرست.</div>
            @endif
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