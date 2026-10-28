import React from 'react';
import { createRoot } from 'react-dom/client';
import DashboardApp from './dashboard/DashboardApp.jsx';

const mount = document.getElementById('app');

if (mount?.dataset.page === 'dashboard') {
    createRoot(mount).render(
        <React.StrictMode>
            <DashboardApp />
        </React.StrictMode>,
    );
}
