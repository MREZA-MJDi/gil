import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

(() => {
    const site = document.querySelector('.public-site');
    if (!site) return;

    const header = site.querySelector('[data-site-header]');
    const nav = site.querySelector('[data-site-nav]');
    const menuToggle = site.querySelector('[data-menu-toggle]');
    const cup = site.querySelector('[data-journey-cup]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    site.querySelectorAll('[data-image-fallback]').forEach(image => {
        image.addEventListener('error', () => {
            const fallbackUrl = image.dataset.imageFallback;
            if (!fallbackUrl) return;
            image.removeAttribute('data-image-fallback');
            image.src = fallbackUrl;
        }, { once: true });
    });

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
        '.reveal-card, .value-card, .moment-photo, .story-copy, .values-heading, .moments-top, .visit-copy, .visit-details, .gil-category-links > a, .gil-food-card, .gil-experience, .gil-order-card, .gil-gallery-item, .gil-final-cta'
    );

    if (!reduceMotion) {
        site.classList.add('gsap-ready');
        const hero = site.querySelector('.gil-hero-center') || site.querySelector('.hero-copy');
        if (hero) {
            const eyebrow = hero.querySelector('.gil-eyebrow, .eyebrow');
            const title = hero.querySelector('h1');
            const copy = hero.querySelector('.gil-hero-lead, .hero-description');
            const actions = hero.querySelector('.gil-hero-actions, .hero-actions');
            const special = hero.querySelector('.gil-hero-special, .hero-social-proof');
            const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
            if (eyebrow) timeline.fromTo(eyebrow, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: .55 });
            if (title) timeline.fromTo(title, { autoAlpha: 0, y: 32, clipPath: 'inset(0 0 100% 0)' }, { autoAlpha: 1, y: 0, clipPath: 'inset(0 0 0% 0)', duration: .85 }, '-=.18');
            if (copy) timeline.fromTo(copy, { autoAlpha: 0, y: 20 }, { autoAlpha: 1, y: 0, duration: .62 }, '-=.28');
            if (actions) timeline.fromTo(actions, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: .55 }, '-=.25');
            if (special) timeline.fromTo(special, { autoAlpha: 0, y: 15 }, { autoAlpha: 1, y: 0, duration: .52 }, '-=.2');
        }
        const heroPhoto = site.querySelector('.gil-hero-photo');
        if (heroPhoto) gsap.fromTo(heroPhoto, { scale: 1.08 }, {
            scale: 1, duration: 1.5, ease: 'power2.out',
            scrollTrigger: { trigger: heroPhoto, start: 'top top', once: true }
        });
        if (site.querySelector('.gil-hero-cinematic')) {
            gsap.to('.gil-hero-photo', {
                yPercent: 7, ease: 'none',
                scrollTrigger: { trigger: '.gil-hero-cinematic', start: 'top top', end: 'bottom top', scrub: .8 }
            });
        }
        revealItems.forEach(item => {
            gsap.fromTo(item,
                { autoAlpha: 0, y: 24 },
                {
                    autoAlpha: 1, y: 0, duration: 0.72, ease: 'power2.out', overwrite: 'auto',
                    scrollTrigger: { trigger: item, start: 'top 88%', once: true },
                    onComplete: () => item.classList.add('is-in-view'),
                }
            );
        });
    } else {
        revealItems.forEach(item => item.classList.add('is-in-view'));
    }

    if (!cup || reduceMotion || !('requestAnimationFrame' in window)) {
        if (cup) cup.remove();
        let fallbackScrollQueued = false;
        const updateHeaderOnScroll = () => {
            if (fallbackScrollQueued) return;
            fallbackScrollQueued = true;
            window.requestAnimationFrame(() => {
                fallbackScrollQueued = false;
                updateHeader();
            });
        };
        window.addEventListener('scroll', updateHeaderOnScroll, { passive: true });
        window.addEventListener('resize', updateHeader, { passive: true });
        updateHeader();
        return;
    }

    site.classList.add('cup-ready');

    const stopElements = Array.from(site.querySelectorAll('[data-cup-stop]'));
    let anchors = [];
    let current = null;
    let destination = null;
    let animationFrame = 0;
    let pointerIsControlling = false;
    let pointerResumeTimer = null;

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
        if (pointerIsControlling) return;
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
        gsap.set(cup, { x: current.x - width / 2, y: current.y - height / 2, rotation: current.rotation, force3D: true });

        const distance = Math.abs(current.x - destination.x) + Math.abs(current.y - destination.y);
        if (distance > 0.6) {
            animationFrame = window.requestAnimationFrame(animateCup);
        }
    };

    const followPointer = event => {
        if (!cup || !event || (event.pointerType === 'touch' && event.pressure <= 0 && !event.buttons)) return;
        pointerIsControlling = true;
        if (pointerResumeTimer) window.clearTimeout(pointerResumeTimer);
        const width = cup.getBoundingClientRect().width || 88;
        const height = width * 1.27;
        const x = clamp(event.clientX, 18, window.innerWidth - 18) - width / 2;
        const y = clamp(event.clientY, 18, window.innerHeight - 18) - height / 2;
        const rotation = clamp((Number(event.movementX) || 0) * .12, -8, 8);
        gsap.to(cup, {
            x, y, rotation,
            duration: event.pointerType === 'touch' ? .2 : .48,
            ease: 'power3.out',
            overwrite: 'auto',
        });
        pointerResumeTimer = window.setTimeout(() => {
            pointerIsControlling = false;
            measureAnchors();
        }, event.pointerType === 'touch' ? 650 : 1100);
    };

    window.addEventListener('pointermove', event => {
        if (event.pointerType === 'mouse' || event.pointerType === 'touch') followPointer(event);
    }, { passive: true });
    window.addEventListener('pointerdown', event => {
        if (event.pointerType === 'touch') followPointer(event);
    }, { passive: true });
    window.addEventListener('pointerup', () => {
        if (pointerResumeTimer) window.clearTimeout(pointerResumeTimer);
        pointerResumeTimer = window.setTimeout(() => {
            pointerIsControlling = false;
            measureAnchors();
        }, 420);
    }, { passive: true });
    window.addEventListener('pointercancel', () => {
        pointerIsControlling = false;
        measureAnchors();
    }, { passive: true });

    const onScroll = () => {
        if (scrollQueued) return;
        scrollQueued = true;
        window.requestAnimationFrame(() => {
            scrollQueued = false;
            updateHeader();
            if (!pointerIsControlling) scheduleCup();
        });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', measureAnchors, { passive: true });
    window.addEventListener('load', measureAnchors, { once: true });
    updateHeader();
    measureAnchors();
})();