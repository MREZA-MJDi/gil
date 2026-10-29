<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#191919">
    <meta name="description" content="{{ $settings->name }}؛ مزه‌های تازه، غذای خوشمزه و لحظه‌هایی برای باهم‌بودن.">
    <title>{{ $settings->name }} — Fresh flavors, good company</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-home">
    @include('public.partials.nav')
    <main>
        <section class="dark-food-hero cup-stop" id="home" data-cup-stop>
            <div class="dark-hero-copy">
                <span class="delivery-pill"><i></i> FRESHLY MADE · READY FOR YOU</span>
                <h1>Pan-Asian Crave <span>Fresh Flavors,</span> <b>or Enjoy Here</b></h1>
                <p class="dark-hero-description">Bold flavors, fresh ingredients, and comforting favorites — made for sharing, staying in, or enjoying around our table.</p>
                <div class="dark-hero-actions">
                    <a class="dark-action primary" href="{{ route('public.menu') }}"><span>↗</span> Order Now</a>
                    <a class="dark-action secondary" href="#reservation"><span>⌑</span> Reserve Table</a>
                    <a class="dark-action tertiary" href="{{ route('public.menu') }}"><span>＋</span> View Menu</a>
                </div>
                <div class="hero-proof-row">
                    <span class="proof-stars" aria-hidden="true">✦ ✦ ✦ ✦ ✦</span>
                    <span>Fresh ingredients</span><i></i><span>Made with care</span>
                </div>
            </div>
            <div class="dark-hero-art" aria-label="A fresh bowl prepared in our kitchen">
                <div class="hero-art-halo"></div>
                <div class="hero-art-swoop"></div>
                <span class="hero-produce tomato tomato-one" aria-hidden="true"></span>
                <span class="hero-produce tomato tomato-two" aria-hidden="true"></span>
                <span class="hero-produce leaf leaf-one" aria-hidden="true"></span>
                <span class="hero-produce leaf leaf-two" aria-hidden="true"></span>
                <span class="hero-produce leaf leaf-three" aria-hidden="true"></span>
                <img class="hero-bowl-image" src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&amp;fit=crop&amp;w=1300&amp;q=90" alt="A colorful bowl of fresh food with vegetables and herbs" fetchpriority="high">
                <div class="hero-art-label"><span>GILASI KITCHEN</span><i></i><span>MADE FRESH</span></div>
            </div>
            <a class="dark-hero-scroll" href="#experience" aria-label="Scroll to discover"><span></span></a>
        </section>

        <section class="dark-experience-section cup-stop" id="experience" data-cup-stop>
            <div class="dark-section-kicker">ONE KITCHEN, TWO EXPERIENCES</div>
            <h2>One Kitchen<br><span>Two Experiences</span></h2>
            <p class="dark-section-lead">Same kitchen. Same care. A little more choice in how you enjoy it.</p>
            <div class="experience-grid">
                <a class="experience-card" href="{{ route('public.menu') }}">
                    <div class="experience-photo"><img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&amp;fit=crop&amp;w=900&amp;q=85" alt="Fresh bowl served from the kitchen" loading="lazy"><span class="experience-number">01</span><span class="experience-arrow">↗</span></div>
                    <div class="experience-card-copy"><span>MADE FOR YOUR MOMENT</span><h3>Enjoy Here</h3><p>Settle in, take your time, and make a meal of the moment.</p></div>
                </a>
                <a class="experience-card" href="{{ route('public.menu') }}">
                    <div class="experience-photo"><img src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&amp;fit=crop&amp;w=900&amp;q=85" alt="Fresh meal packed with colorful ingredients" loading="lazy"><span class="experience-number">02</span><span class="experience-arrow">↗</span></div>
                    <div class="experience-card-copy"><span>YOUR FAVORITES, YOUR WAY</span><h3>Enjoy Anywhere</h3><p>Explore the menu and bring something delicious to your day.</p></div>
                </a>
            </div>
        </section>

        <section class="dark-belief-section cup-stop" id="story" data-cup-stop>
            <div class="belief-image-strip">
                <div><img src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Fresh food prepared with colorful vegetables" loading="lazy"></div>
                <div><img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Fresh bowl served at the table" loading="lazy"></div>
                <div><img src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&amp;fit=crop&amp;w=800&amp;q=85" alt="Fresh ingredients and herbs" loading="lazy"></div>
            </div>
            <span class="belief-leaf belief-leaf-left" aria-hidden="true">✦</span>
            <p class="belief-statement">We believe great food <span>shouldn’t compromise on quality,</span> no matter where you enjoy it.</p>
            <span class="belief-leaf belief-leaf-right" aria-hidden="true">✦</span>
        </section>

        <section class="dark-journey-section cup-stop" id="moments" data-cup-stop>
            <div class="dark-journey-heading">
                <span class="dark-section-kicker">A STORY MADE TO BE SHARED</span>
                <h2>Our <span>Journey</span></h2>
                <p>Good food is just the beginning. The best part is the moments that come with it.</p>
            </div>
            <div class="journey-timeline">
                <article class="journey-item"><span class="journey-dot"></span><div class="journey-card"><div><h3>The Beginning</h3><span>CHAPTER 01</span></div><p>A simple idea: make good food with care, and welcome everyone to the table.</p></div></article>
                <article class="journey-item"><span class="journey-dot"></span><div class="journey-card"><div><h3>Finding Our Flavor</h3><span>CHAPTER 02</span></div><p>Bringing fresh ingredients and familiar favorites together in every plate.</p></div></article>
                <article class="journey-item"><span class="journey-dot"></span><div class="journey-card"><div><h3>More Ways to Enjoy</h3><span>CHAPTER 03</span></div><p>Making it easier to explore the menu, reserve a table, and find your next favorite.</p></div></article>
                <article class="journey-item"><span class="journey-dot"></span><div class="journey-card"><div><h3>And We're Just Getting Started</h3><span>THE NEXT CHAPTER</span></div><p>More meals, more memories, and always a seat for you.</p></div></article>
            </div>
        </section>

        <section class="dark-menu-section cup-stop" id="menu" data-cup-stop>
            <div class="dark-menu-heading">
                <div><span class="dark-section-kicker">FROM OUR MENU</span><h2>Find your <span>new favorite.</span></h2></div>
                <a class="dark-text-link" href="{{ route('public.menu') }}">Explore the full menu <span>↗</span></a>
            </div>
            <div class="dark-featured-grid">
                @forelse ($featuredProducts as $index => $product)
                    <article class="dark-food-card">
                        <a href="{{ route('public.menu') }}" class="dark-food-photo">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <div class="dark-food-placeholder dark-food-placeholder-{{ $index % 3 }}"><span>{{ ['✦','✳','❋'][$index % 3] }}</span><small>KHANE GILASI</small></div>
                            @endif
                            <span class="dark-food-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="dark-food-arrow">↗</span>
                        </a>
                        <div class="dark-food-info"><div><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'Prepared with care and fresh ingredients.' }}</p></div><strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong></div>
                    </article>
                @empty
                    <div class="dark-menu-empty"><span>✳</span><h3>Something good is on the way.</h3><p>Our menu is getting ready. Check back soon.</p></div>
                @endforelse
            </div>
            <div class="dark-menu-foot"><span>✳</span><p>All cravings welcome. Browse the menu for ingredients, options, and details.</p><a class="dark-action secondary" href="{{ route('public.menu') }}">View Full Menu <span>↗</span></a></div>
        </section>

        <section class="dark-reservation-section cup-stop" id="reservation" data-cup-stop>
            <div class="reservation-copy"><span class="dark-section-kicker">SAVE YOUR SEAT</span><h2>Let's make it<br><span>a plan.</span></h2><p>Planning a visit? Send us a reservation request and our team will follow up to confirm the details.</p></div>
            <div class="dark-reservation-form"><div id="public-reservation-app" data-endpoint="{{ route('public.reservations.store') }}"></div><noscript><p>Enable JavaScript to submit a reservation request.</p></noscript></div>
        </section>

        <section class="dark-visit-section cup-stop" id="visit" data-cup-stop>
            <div class="dark-visit-copy"><span class="dark-section-kicker">YOUR TABLE IS WAITING</span><h2>Good food.<br><span>Good company.</span></h2><p>A little pause, a new favorite, or a table shared with friends. We'd love to see you.</p><div class="dark-visit-actions"><a class="dark-action primary" href="{{ route('public.menu') }}">Explore Menu <span>↗</span></a>@if ($settings->phone)<a class="dark-action secondary" href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">Call Us</a>@endif</div></div>
            <div class="dark-visit-details"><div><span>ADDRESS</span><b>{{ $settings->address ?: 'Add your cafe address in Settings.' }}</b></div><div><span>PHONE</span><b>{{ $settings->phone ?: 'No phone number added yet.' }}</b></div><a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($settings->address ?: $settings->name) }}" target="_blank" rel="noopener noreferrer">Get directions <span>↗</span></a></div>
        </section>
    </main>
    @include('public.partials.footer')
    @include('public.partials.traveling-cup')
</body>
</html>