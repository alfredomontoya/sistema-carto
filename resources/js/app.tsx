import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { ThemeProvider } from '@/components/brand/ThemeProvider';
import { getAppName } from '@/lib/app-name';

createInertiaApp({
    title: (title) => (title ? `${title} - ${getAppName()}` : getAppName()),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ThemeProvider>
                <App {...props} />
                <Toaster richColors position="top-right" />
            </ThemeProvider>,
        );
    },
    progress: {
        color: 'var(--brand-primary, #1d4ed8)',
    },
});
