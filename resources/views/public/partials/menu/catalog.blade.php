        <section class="public-catalog section-wrap cup-stop" id="catalog" data-cup-stop>
            @forelse($categories as $category)
                <section class="public-category" id="category-{{ $category->id }}" data-cup-stop>
                    <header class="public-category-heading"><div><span class="section-kicker">دسته‌بندی / {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $category->name }}</h2></div><span class="public-category-count">{{ $category->products->count() }} انتخاب</span></header>
                    <div class="public-food-grid">
                        @foreach($category->products as $product)
                            <article class="public-food-card reveal-card">
                                <div class="public-food-image">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <div class="food-image-placeholder food-placeholder-{{ $loop->iteration % 4 }}"><span>{{ ['☕','🥐','🍰','🥪'][$loop->index % 4] }}</span><small>{{ $settings->name }}</small></div>
                                    @endif
                                    @if($product->preparation_time_minutes)<span class="prep-time">◷ {{ $product->preparation_time_minutes }} دقیقه</span>@endif
                                </div>
                                <div class="public-food-info">
                                    <div class="public-food-title"><h3>{{ $product->name }}</h3><b>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></b></div>
                                    @if($product->description)<p>{{ $product->description }}</p>@endif
                                    @if($product->ingredients)<div class="public-food-line"><span>ترکیبات</span><p>{{ $product->ingredients }}</p></div>@endif
                                    @if(is_array($product->allergens) && count($product->allergens))<div class="public-food-allergens"><b>آلرژن‌های اعلام‌شده:</b> {{ implode('، ', $product->allergens) }}<small>برای حساسیت غذایی، پیش از سفارش با کارکنان هماهنگ کن.</small></div>@endif
                                    @if(is_array($product->dietary_tags) && count($product->dietary_tags))<div class="public-food-tags">@foreach($product->dietary_tags as $tag)<span>{{ $tag }}</span>@endforeach</div>@endif
                                    @if($product->options->isNotEmpty())
                                        <div class="public-option-list">
                                            <span class="public-option-label">سایز و افزودنی</span>
                                            @foreach($product->options as $option)
                                                <label class="public-option-choice">
                                                    <input type="checkbox" data-online-option="{{ $option->id }}" data-option-type="{{ ($option->type instanceof \BackedEnum ? $option->type->value : $option->type) }}" data-option-price="{{ (int) $option->price }}" data-option-name="{{ $option->name }}">
                                                    <span>{{ $option->name }} <small>{{ ($option->type instanceof \BackedEnum ? $option->type->value : $option->type) === 'size' ? 'سایز' : 'افزودنی' }}</small></span>
                                                    <b>@if($option->price > 0)+{{ number_format($option->price) }} {{ $settings->currency_label }}@else رایگان @endif</b>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="public-order-controls">
                                        <label><span>تعداد</span><input type="number" min="1" max="100" value="1" data-online-quantity aria-label="تعداد {{ $product->name }}"></label>
                                        <button type="button" class="button button-dark" data-add-online-product data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ (int) $product->base_price }}">افزودن به سبد <span>＋</span></button>
                                    </div>
                                    @if($product->calories !== null)<div class="public-food-calories">{{ number_format($product->calories) }} کیلوکالری در هر وعده <span>· فقط در صورت ثبت اطلاعات تأییدشده</span></div>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="public-catalog-empty"><span>✳</span><h2>منو در حال آماده‌شدن است.</h2><p>وقتی محصولات فعال در پنل ثبت شوند، اینجا نمایش داده می‌شوند.</p><a href="{{ route('admin.login') }}" class="text-link">ورود کارکنان برای مدیریت منو <span>↗</span></a></div>
            @endforelse
            <div class="catalog-disclaimer"><span>✳</span><p>ترکیبات، آلرژن‌ها و اطلاعات تغذیه‌ای صرفاً بر اساس داده‌های واردشده توسط کافه نمایش داده می‌شوند. اگر آلرژی یا محدودیت غذایی داری، قبل از سفارش حتماً جزئیات را با کارکنان بررسی کن.</p></div>
        </section>
