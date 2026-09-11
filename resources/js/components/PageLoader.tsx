import * as React from 'react';
import { router, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { BrandData } from '@/types';

export function dismissBootSplash() {
    if (typeof document === 'undefined') return;
    const el = document.getElementById('boot-splash');
    if (!el || el.dataset.done === '1') return;
    el.dataset.done = '1';
    el.classList.add('boot-splash-hide');
    window.setTimeout(() => el.remove(), 450);
}

function LoaderVisual({ logoUrl, appName }: { logoUrl: string; appName: string }) {
    const softFont = {
        fontFamily: '"Figtree", ui-sans-serif, system-ui, sans-serif',
    } as const;

    return (
        <div className="flex flex-col items-center gap-5">
            <div className="page-loader-logo">
                <img src={logoUrl} alt={appName} className="h-20 w-20 rounded-2xl object-contain" />
                <span className="page-loader-shine" aria-hidden="true" />
            </div>
            <div className="flex flex-col items-center gap-3">
                <p
                    style={softFont}
                    className="text-sm font-medium uppercase tracking-[0.18em] text-foreground"
                >
                    {appName}
                </p>
                <div className="h-1 w-48 overflow-hidden rounded-full bg-muted">
                    <div className="page-loader-bar h-full w-1/2 rounded-full" />
                </div>
                <p style={softFont} className="text-xs text-muted-foreground">
                    Cargando página…
                </p>
            </div>
        </div>
    );
}

export function PageLoader({ className }: { className?: string }) {
    const brand = (usePage().props.brand ?? null) as BrandData | null;
    const appName = (brand?.app_name ?? 'Carto').toUpperCase();
    const logoUrl = brand?.logo_url ?? brand?.favicon_url ?? '/logo-carto.png';

    return (
        <div
            role="status"
            aria-label="Cargando página"
            className={cn(
                'flex min-h-screen flex-col items-center justify-center bg-background px-4',
                className,
            )}
        >
            <LoaderVisual logoUrl={logoUrl} appName={appName} />
        </div>
    );
}

export function NavigationLoader({ brand }: { brand?: BrandData | null }) {
    const [visible, setVisible] = React.useState(false);
    const appName = (brand?.app_name ?? 'Carto').toUpperCase();
    const logoUrl = brand?.logo_url ?? brand?.favicon_url ?? '/logo-carto.png';
    const timer = React.useRef<number | null>(null);

    React.useEffect(() => {
        const show = () => {
            if (timer.current) window.clearTimeout(timer.current);
            timer.current = window.setTimeout(() => setVisible(true), 300);
        };
        const hide = () => {
            if (timer.current) window.clearTimeout(timer.current);
            timer.current = null;
            setVisible(false);
        };
        const offStart = router.on('start', show);
        const offFinish = router.on('finish', hide);

        return () => {
            if (timer.current) window.clearTimeout(timer.current);
            offStart();
            offFinish();
        };
    }, []);

    if (!visible) return null;

    return (
        <div
            role="status"
            aria-label="Cargando página"
            className="page-loader-overlay fixed inset-0 z-[100] flex items-center justify-center bg-background/80 px-4 backdrop-blur-sm"
        >
            <LoaderVisual logoUrl={logoUrl} appName={appName} />
        </div>
    );
}
