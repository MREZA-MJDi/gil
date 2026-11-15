        <div class="menu-jumpbar" aria-label="رفتن به دسته‌بندی">
            @forelse($categories as $category)
                <a href="#category-{{ $category->id }}">{{ $category->name }} <span>{{ $category->products->count() }}</span></a>
            @empty
                <span>منو در حال تکمیل است</span>
            @endforelse
        </div>
