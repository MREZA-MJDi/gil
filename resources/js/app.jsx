import React from 'react';
import { createRoot } from 'react-dom/client';
import './dashboard/dashboard.css';
import DashboardApp from './dashboard/DashboardApp.jsx';

const dashboardMount = document.getElementById('app');
if (dashboardMount?.dataset.page === 'dashboard') {
    createRoot(dashboardMount).render(<React.StrictMode><DashboardApp /></React.StrictMode>);
}

const customerMenuMount = document.getElementById('customer-menu-app');
if (customerMenuMount) {
    import('./customer-menu.jsx').catch(error => {
        console.error('Customer menu failed to load.', error);
        customerMenuMount.textContent = 'بارگذاری منو با مشکل مواجه شد.';
    });
}
