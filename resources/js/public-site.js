import { jalaliDateTimeToGregorian } from './date-utils.js';

(() => {
    const site = document.querySelector('.public-site');
    if (!site) return;

    const header = site.querySelector('[data-site-header]');
    const nav = site.querySelector('[data-site-nav]');
    const menuToggle = site.querySelector('[data-menu-toggle]');

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

    const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 14);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();

    // Accessible, touch-friendly featured product carousel. No scroll-triggered animation library.
    document.querySelectorAll('[data-food-carousel]').forEach(button => {
        button.addEventListener('click', () => {
            const track = document.querySelector('.gil-food-grid');
            if (!track) return;
            const factor = button.dataset.foodCarousel === 'next' ? 1 : -1;
            track.scrollBy({ left: factor * Math.max(260, track.clientWidth * 0.82), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        });
    });


    // Public reservation dates are entered in the Jalali calendar but stored as Gregorian
    // timestamps so Laravel/database queries remain consistent.
    document.querySelectorAll('[data-jalali-booking]').forEach(form => {
        const jalaliInput = form.querySelector('[name="starts_at_jalali"]');
        const gregorianInput = form.querySelector('[name="starts_at"]');
        if (!jalaliInput || !gregorianInput) return;

        const validate = () => {
            const value = jalaliInput.value.trim();
            if (!value) {
                jalaliInput.setCustomValidity('تاریخ و ساعت شمسی را وارد کن؛ نمونه: ۱۴۰۵/۰۸/۰۷ ۱۸:۳۰');
                return false;
            }
            const converted = jalaliDateTimeToGregorian(value);
            jalaliInput.setCustomValidity(converted ? '' : 'تاریخ شمسی معتبر وارد کن؛ قالب: سال/ماه/روز ساعت:دقیقه، مثل ۱۴۰۵/۰۸/۰۷ ۱۸:۳۰');
            return Boolean(converted);
        };

        jalaliInput.addEventListener('input', validate);
        form.addEventListener('submit', event => {
            if (!validate()) {
                event.preventDefault();
                jalaliInput.reportValidity();
                return;
            }
            const converted = jalaliDateTimeToGregorian(jalaliInput.value.trim());
            gregorianInput.value = converted.replace('T', ' ') + ':00';
        });
    });
})();
