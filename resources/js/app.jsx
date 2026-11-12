import React from 'react';
import { createRoot } from 'react-dom/client';
import DashboardApp from './dashboard/DashboardApp.jsx';

const dashboardMount = document.getElementById('app');

if (dashboardMount?.dataset.page === 'dashboard') {
    createRoot(dashboardMount).render(
        <React.StrictMode>
            <DashboardApp />
        </React.StrictMode>,
    );
}
