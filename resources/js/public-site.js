(() => {
    const site = document.querySelector('.public-site');
    if (!site) return;

    const header = site.querySelector('[data-site-header]');
    const nav = site.querySelector('[data-site-nav]');
    const menuToggle = site.querySelector('[data-menu-toggle]');
    const cup = site.querySelector('[data-journey-cup]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    site.classList.add('js-ready');

    const closeMenu = () => {
        nav?.classList.remove('is-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'باز کردن منو');
    };

    menuToggle?.addEventListener('click', () => {
        const isOpen = nav?.classList.toggle('is-open') ?? false;
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? 'بستن منو' : 'باز کردن منو');
    });

    nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

    let scrollQueued = false;
    const updateHeader = () => {
        header?.classList.toggle('is-scrolled', window.scrollY > 14);
    };

    const revealItems = site.querySelectorAll(
        '.reveal-card, .value-card, .moment-photo, .story-copy, .values-heading, .moments-top, .visit-copy, .visit-details'
    );

    if ('IntersectionObserver' in window && !reduceMotion) {
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in-view');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
        revealItems.forEach(item => revealObserver.observe(item));
    } else {
        revealItems.forEach(item => item.classList.add('is-in-view'));
    }

    if (!cup || reduceMotion || !('requestAnimationFrame' in window)) {
        if (cup) cup.remove();
        updateHeader();
        return;
    }

    site.classList.add('cup-ready');

    const stopElements = Array.from(site.querySelectorAll('[data-cup-stop]'));
    let anchors = [];
    let current = null;
    let destination = null;
    let animationFrame = 0;

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const lerp = (start, end, progress) => start + (end - start) * progress;

    const measureAnchors = () => {
        const width = window.innerWidth;
        const scrollY = window.scrollY;
        anchors = stopElements.map((element, index) => {
            const rect = element.getBoundingClientRect();
            const side = element.dataset.cupSide;
            const xRatio = side === 'left' ? 0.13 : side === 'right' ? 0.87 : (index % 2 === 0 ? 0.16 : 0.84);
            const anchorY = rect.top + scrollY + Math.min(rect.height * 0.42, window.innerHeight * 0.7);
            return {
                x: clamp(width * xRatio, 38, Math.max(38, width - 38)),
                y: anchorY,
            };
        }).sort((a, b) => a.y - b.y);

        if (!anchors.length) {
            anchors = [{ x: width * 0.15, y: scrollY + window.innerHeight * 0.55 }];
        }

        if (!current) {
            const first = anchors[0];
            current = { x: first.x, y: first.y - scrollY, rotation: 0 };
        }
        scheduleCup();
    };

    const calculateDestination = () => {
        const focusY = window.scrollY + window.innerHeight * 0.52;
        if (focusY <= anchors[0].y) {
            return { x: anchors[0].x, y: anchors[0].y - window.scrollY, rotation: -5 };
        }

        const last = anchors[anchors.length - 1];
        if (focusY >= last.y) {
            return { x: last.x, y: last.y - window.scrollY, rotation: 5 };
        }

        let nextIndex = anchors.findIndex(anchor => anchor.y >= focusY);
        if (nextIndex < 1) nextIndex = 1;
        const start = anchors[nextIndex - 1];
        const end = anchors[nextIndex];
        const progress = clamp((focusY - start.y) / Math.max(1, end.y - start.y), 0, 1);
        const rotation = clamp((end.x - start.x) / Math.max(1, window.innerWidth) * 25, -10, 10);
        return {
            x: lerp(start.x, end.x, progress),
            y: lerp(start.y, end.y, progress) - window.scrollY,
            rotation,
        };
    };

    const scheduleCup = () => {
        if (!anchors.length) return;
        destination = calculateDestination();
        if (!animationFrame) animationFrame = window.requestAnimationFrame(animateCup);
    };

    const animateCup = () => {
        animationFrame = 0;
        if (!destination || !current) return;

        const easing = 0.19;
        current.x = lerp(current.x, destination.x, easing);
        current.y = lerp(current.y, destination.y, easing);
        current.rotation = lerp(current.rotation, destination.rotation, easing);

        const width = cup.getBoundingClientRect().width || 100;
        const height = width * 1.27;
        cup.style.transform = 'translate3d(' + (current.x - width / 2) + 'px, ' + (current.y - height / 2) + 'px, 0) rotate(' + current.rotation + 'deg)';

        const distance = Math.abs(current.x - destination.x) + Math.abs(current.y - destination.y);
        if (distance > 0.6) {
            animationFrame = window.requestAnimationFrame(animateCup);
        }
    };

    const onScroll = () => {
        if (scrollQueued) return;
        scrollQueued = true;
        window.requestAnimationFrame(() => {
            scrollQueued = false;
            updateHeader();
            scheduleCup();
        });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', measureAnchors, { passive: true });
    window.addEventListener('load', measureAnchors, { once: true });
    updateHeader();
    measureAnchors();
})();