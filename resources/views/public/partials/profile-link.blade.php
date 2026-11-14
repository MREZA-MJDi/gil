@php
    $profileUrl = auth()->check()
        ? (auth()->user()->is_admin ? route('admin.dashboard') : route('customer.dashboard'))
        : route('customer.login');
    $profileLabel = auth()->check() ? 'حساب کاربری' : 'ورود به حساب';
@endphp
<a class="profile-link" href="{{ $profileUrl }}" aria-label="{{ $profileLabel }}" title="{{ $profileLabel }}">
    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="8" r="3.5"></circle>
        <path d="M4.8 20c.5-3.5 3.4-5.7 7.2-5.7s6.7 2.2 7.2 5.7"></path>
    </svg>
</a>
