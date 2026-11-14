<section class="gil-category-strip section-wrap" aria-label="دسته‌بندی منو">
    <div class="gil-strip-intro">
        <span class="gil-eyebrow">گشتی در منو</span>
        <h2>هوسِ کدام طعم را کرده‌ای؟</h2>
        <p>از یک اسپرسوی کوتاه تا یک وعدهٔ کامل؛ از دسته‌بندی شروع کن.</p>
    </div>
    <div class="gil-category-links">
        @forelse ($categories as $category)
            @php($categoryProduct = $category->products->first())
            <a href="{{ route('public.menu') }}#category-{{ $category->id }}">
                <span class="gil-category-image">
                    @if ($categoryProduct && $categoryProduct->image_url)
                        <img src="{{ $categoryProduct->image_url }}" alt="" loading="lazy">
                    @else
                        <img src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=700&q=82" alt="" loading="lazy">
                    @endif
                    <span class="gil-category-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="gil-category-arrow">↗</span>
                </span>
                <span class="gil-category-copy"><b>{{ $category->name }}</b><small>{{ number_format($category->products->count()) }} انتخاب از منو</small></span>
            </a>
        @empty
            <a href="{{ route('public.menu') }}"><span class="gil-category-image"><img src="https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=700&q=82" alt="" loading="lazy"></span><span class="gil-category-copy"><b>منوی کافه</b><small>مشاهدهٔ همهٔ انتخاب‌ها</small></span><span class="gil-category-arrow">↗</span></a>
        @endforelse
    </div>
</section>
