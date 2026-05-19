import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        const lowerName = name.toLowerCase();

        switch (true) {
            case lowerName === 'welcome':
            case lowerName === 'dashboard':
            case lowerName === 'menumanagement':
            case lowerName === 'tablemanagement':
            case lowerName === 'integrationsetup':
            case lowerName === 'menupreview':
            case lowerName === 'menupreviewcheckout':
            case lowerName === 'menupreviewsuccess':
            case lowerName === 'auth/login':
            case lowerName.startsWith('customer/'):
                return null;
            case lowerName.startsWith('auth/'):
                return AuthLayout;
            case lowerName.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
