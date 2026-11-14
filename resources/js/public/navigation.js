const headers = document.querySelectorAll('[data-kg-header]');

headers.forEach(header => {
    const menuToggle = header.querySelector('[data-kg-menu-toggle]');
    const nav = header.querySelector('[data-kg-nav]');

    if (!menuToggle || !nav) return;

    const setOpen = open => {
        nav.classList.toggle('is-open', open);
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
    };

    menuToggle.addEventListener('click', event => {
        event.preventDefault();
        setOpen(!nav.classList.contains('is-open'));
    });

    nav.addEventListener('click', event => {
        if (event.target.closest('a')) setOpen(false);
    });

    document.addEventListener('pointerdown', event => {
        if (!header.contains(event.target)) setOpen(false);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && nav.classList.contains('is-open')) {
            setOpen(false);
            menuToggle.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 860) setOpen(false);
    });
});
