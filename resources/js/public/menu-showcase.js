const menuRoot = document.querySelector('[data-menu-showcase]');

if (menuRoot) {
    const categoryButtons = [...menuRoot.querySelectorAll('[data-menu-category-select]')];
    const categoryPanels = [...menuRoot.querySelectorAll('[data-menu-category-panel]')];
    const pageSize = 4;

    const setSelectedProduct = (panel, productId) => {
        const template = [...panel.querySelectorAll('template[data-menu-product-template]')]
            .find(item => item.dataset.menuProductTemplate === String(productId));
        const feature = panel.querySelector('[data-menu-featured]');

        if (!template || !feature) return;

        feature.replaceChildren(template.content.cloneNode(true));

        panel.querySelectorAll('[data-menu-product-select]').forEach(button => {
            const selected = button.dataset.menuProductId === String(productId);
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    };

    const setPage = (panel, requestedPage) => {
        const products = [...panel.querySelectorAll('[data-menu-product-select]')];
        const dots = [...panel.querySelectorAll('[data-menu-page-index]')];
        const pageCount = Math.max(1, Math.ceil(products.length / pageSize));
        const page = Math.max(0, Math.min(pageCount - 1, requestedPage));
        const firstIndex = page * pageSize;

        products.forEach((button, index) => {
            button.hidden = index < firstIndex || index >= firstIndex + pageSize;
        });

        dots.forEach((button, index) => {
            const active = index === page;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-current', active ? 'page' : 'false');
        });

        if (products[firstIndex]) {
            setSelectedProduct(panel, products[firstIndex].dataset.menuProductId);
        }
    };

    const setCategory = categoryId => {
        const panel = categoryPanels.find(item => item.dataset.menuCategoryPanel === String(categoryId));
        if (!panel) return;

        categoryPanels.forEach(item => {
            item.hidden = item !== panel;
        });

        categoryButtons.forEach(button => {
            const active = button.dataset.menuCategorySelect === String(categoryId);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        setPage(panel, 0);
    };

    menuRoot.addEventListener('click', event => {
        const categoryButton = event.target.closest('[data-menu-category-select]');
        if (categoryButton && menuRoot.contains(categoryButton)) {
            setCategory(categoryButton.dataset.menuCategorySelect);
            return;
        }

        const pageButton = event.target.closest('[data-menu-page-index]');
        if (pageButton && menuRoot.contains(pageButton)) {
            const panel = pageButton.closest('[data-menu-category-panel]');
            if (panel) setPage(panel, Number(pageButton.dataset.menuPageIndex) || 0);
            return;
        }

        const productButton = event.target.closest('[data-menu-product-select]');
        if (productButton && menuRoot.contains(productButton)) {
            const panel = productButton.closest('[data-menu-category-panel]');
            if (panel) setSelectedProduct(panel, productButton.dataset.menuProductId);
        }
    });

    menuRoot.addEventListener('error', event => {
        const image = event.target;
        if (!image.matches?.('[data-menu-image]')) return;

        image.hidden = true;
        const fallback = image.nextElementSibling;
        if (fallback?.matches('[data-menu-image-fallback]')) {
            fallback.hidden = false;
        }
    }, true);

    if (categoryButtons.length) {
        const requestedCategoryId = window.location.hash.match(/^#category-(\\d+)$/)?.[1] || null;
        const requestedProductId = new URLSearchParams(window.location.search).get('product');
        const requestedPanel = requestedCategoryId
            ? categoryPanels.find(item => item.dataset.menuCategoryPanel === requestedCategoryId)
            : null;
        const initialPanel = requestedPanel || categoryPanels[0];
        const initialCategoryId = initialPanel?.dataset.menuCategoryPanel || categoryButtons[0].dataset.menuCategorySelect;

        setCategory(initialCategoryId);

        if (requestedProductId && initialPanel) {
            const products = [...initialPanel.querySelectorAll('[data-menu-product-select]')];
            const productIndex = products.findIndex(button => button.dataset.menuProductId === requestedProductId);
            if (productIndex >= 0) {
                setPage(initialPanel, Math.floor(productIndex / pageSize));
                setSelectedProduct(initialPanel, requestedProductId);
            }
        }

        if (requestedPanel) {
            window.requestAnimationFrame(() => requestedPanel.scrollIntoView({ block: 'start', behavior: 'smooth' }));
        }
    }
}


