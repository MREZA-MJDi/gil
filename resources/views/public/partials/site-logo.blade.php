<a class="logo" href="{{ route('home') }}" aria-label="صفحه اصلی {{ $settings->name }}" title="{{ $settings->name }}">
    @if(!empty($media['brand_logo']['url']))
        <img src="{{ $media['brand_logo']['url'] }}" alt="{{ $media['brand_logo']['alt_text'] ?: $settings->name }}" loading="eager">
    @else
        <span aria-hidden="true">{{ \Illuminate\Support\Str::substr($settings->name, 0, 1) }}</span>
    @endif
</a>
