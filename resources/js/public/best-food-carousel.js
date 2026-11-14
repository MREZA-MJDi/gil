/* Database-backed food carousel: only products rendered by Blade can become slides. */
const bestFood = document.querySelector('[data-best-food-carousel]');

if (bestFood) {
    const track = bestFood.querySelector('[data-best-food-track]');
    const slides = [...bestFood.querySelectorAll('[data-best-food-slide]')];
    const dots = [...bestFood.querySelectorAll('[data-best-food-dot]')];
    const previous = bestFood.querySelector('[data-best-food-prev]');
    const next = bestFood.querySelector('[data-best-food-next]');
    const description = bestFood.querySelector('[data-best-food-description]');
    const detailLink = bestFood.querySelector('[data-best-food-link]');
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    if (track && slides.length) {
        let active = Math.floor(slides.length / 2);
        let timer = null;
        let pointerStart = null;
        let suppressClick = false;

        const stop = () => {
            if (timer !== null) window.clearInterval(timer);
            timer = null;
        };

        const update = () => {
            const count = slides.length;

            slides.forEach((slide, index) => {
                let offset = index - active;
                if (offset > count / 2) offset -= count;
                if (offset < -count / 2) offset += count;

                const distance = Math.min(Math.abs(offset), 3);
                slide.style.setProperty('--o', String(offset));
                slide.dataset.distance = String(distance);
                slide.setAttribute('aria-current', offset === 0 ? 'true' : 'false');
                slide.setAttribute('aria-hidden', distance > 2 ? 'true' : 'false');
                slide.tabIndex = distance > 2 ? -1 : 0;
            });

            dots.forEach((dot, index) => {
                const selected = index === active;
                dot.classList.toggle('is-active', selected);
                dot.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            const selected = slides[active];
            if (description) description.textContent = selected.dataset.description || '';
            if (detailLink) detailLink.href = selected.dataset.url || detailLink.href;
        };

        const start = () => {
            stop();
            if (!reducedMotion && slides.length > 1) {
                timer = window.setInterval(() => go(active + 1), 4400);
            }
        };

        const go = index => {
            active = (index + slides.length) % slides.length;
            update();
            start();
        };

        previous?.addEventListener('click', () => go(active - 1));
        next?.addEventListener('click', () => go(active + 1));
        dots.forEach(dot => dot.addEventListener('click', () => go(Number(dot.dataset.bestFoodDot) || 0)));

        bestFood.addEventListener('click', event => {
            if (!suppressClick) return;
            const slideLink = event.target.closest('[data-best-food-slide]');
            if (slideLink && bestFood.contains(slideLink)) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        track.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                go(active + 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                go(active - 1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                go(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                go(slides.length - 1);
            }
        });

        track.addEventListener('pointerdown', event => {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            pointerStart = event.clientX;
            stop();
        });

        // Listen on window so a press that starts on a card still produces a native link click.
        window.addEventListener('pointerup', event => {
            if (pointerStart === null) return;
            const delta = event.clientX - pointerStart;
            pointerStart = null;
            if (Math.abs(delta) > 42) {
                suppressClick = true;
                go(active + (delta < 0 ? 1 : -1));
                window.setTimeout(() => { suppressClick = false; }, 0);
            } else {
                start();
            }
        });

        window.addEventListener('pointercancel', () => {
            pointerStart = null;
            start();
        });

        track.addEventListener('dragstart', event => event.preventDefault());
        bestFood.addEventListener('mouseenter', stop);
        bestFood.addEventListener('mouseleave', start);
        bestFood.addEventListener('focusin', stop);
        bestFood.addEventListener('focusout', event => {
            if (!bestFood.contains(event.relatedTarget)) start();
        });
        document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

        update();
        start();
    }
}
