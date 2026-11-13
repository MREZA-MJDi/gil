/* Home food carousel: card data and image URLs come from active database products. */
const homeCarousel = document.querySelector('[data-home-carousel]');
if (homeCarousel) {
    const stage = homeCarousel.querySelector('[data-home-carousel-stage]');
    const cards = [...homeCarousel.querySelectorAll('[data-home-carousel-card]')];
    const dots = [...homeCarousel.querySelectorAll('[data-home-dot]')];
    const previous = homeCarousel.querySelector('[data-home-prev]');
    const next = homeCarousel.querySelector('[data-home-next]');
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    let current = 0;
    let timer = null;
    let pointerStart = null;

    const render = () => {
        const count = cards.length;
        cards.forEach((card, index) => {
            const raw = (index - current + count) % count;
            const offset = raw > count / 2 ? raw - count : raw;
            card.dataset.homePosition = Math.abs(offset) > 2 ? 'hidden' : String(offset);
            card.tabIndex = Math.abs(offset) <= 1 ? 0 : -1;
            card.setAttribute('aria-current', offset === 0 ? 'true' : 'false');
        });
        dots.forEach((dot, index) => {
            const active = index === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    const stop = () => { if (timer) window.clearInterval(timer); timer = null; };
    const start = () => {
        stop();
        if (!reducedMotion && cards.length > 1) timer = window.setInterval(() => go(current + 1), 4200);
    };
    const go = index => {
        if (!cards.length) return;
        current = (index + cards.length) % cards.length;
        render();
        start();
    };

    previous?.addEventListener('click', () => go(current - 1));
    next?.addEventListener('click', () => go(current + 1));
    dots.forEach(dot => dot.addEventListener('click', () => go(Number(dot.dataset.homeDot) || 0)));
    stage?.addEventListener('click', event => {
        const card = event.target.closest('[data-home-index]');
        if (!card) return;
        const index = Number(card.dataset.homeIndex);
        if (Number.isInteger(index) && index !== current) { event.preventDefault(); go(index); }
    });
    stage?.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight') { event.preventDefault(); go(current - 1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); go(current + 1); }
    });
    stage?.addEventListener('pointerdown', event => { pointerStart = event.clientX; });
    stage?.addEventListener('pointerup', event => {
        if (pointerStart === null) return;
        const delta = event.clientX - pointerStart;
        pointerStart = null;
        if (Math.abs(delta) > 35) go(current + (delta < 0 ? 1 : -1));
    });
    stage?.addEventListener('pointercancel', () => { pointerStart = null; });
    homeCarousel.addEventListener('mouseenter', stop);
    homeCarousel.addEventListener('mouseleave', start);
    homeCarousel.addEventListener('focusin', stop);
    homeCarousel.addEventListener('focusout', event => { if (!homeCarousel.contains(event.relatedTarget)) start(); });
    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

    if (cards.length) { render(); start(); }
}
