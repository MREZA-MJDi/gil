const header = document.querySelector('[data-kg-header]');
const menuToggle = document.querySelector('[data-kg-menu-toggle]');
const nav = document.querySelector('[data-kg-nav]');

if (header && menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
    });
    nav.addEventListener('click', event => {
        if (event.target.closest('a')) {
            nav.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'باز کردن منو');
        }
    });
}

