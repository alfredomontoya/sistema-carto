import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { ThemeProvider } from '@/components/brand/ThemeProvider';
import { NavigationLoader, dismissBootSplash } from '@/components/PageLoader';
import { getAppName } from '@/lib/app-name';
import type { BrandData } from '@/types';

createInertiaApp({
    title: (title) =>
        title ? `${title} - ${getAppName().toUpperCase()}` : getAppName().toUpperCase(),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const brand = (props.initialPage.props.brand ?? null) as BrandData | null;

        root.render(
            <ThemeProvider>
                <App {...props} />
                <NavigationLoader brand={brand} />
                <Toaster richColors position="top-right" offset={{ top: '80px' }} closeButton />
            </ThemeProvider>,
        );

        requestAnimationFrame(() => dismissBootSplash());
        window.setTimeout(() => dismissBootSplash(), 5000);
    },
    progress: {
        color: 'var(--brand-primary, #1d4ed8)',
    },
});
