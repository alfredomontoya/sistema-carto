import * as React from 'react';
import { usePage } from '@inertiajs/react';
import { setAppName } from '@/lib/app-name';
import type { BrandData } from '@/types';

function luminance(hex: string): number {
    const c = hex.replace('#', '');
    const full = c.length === 3 ? c.split('').map((x) => x + x).join('') : c;
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16) / 255);

    const lin = (v: number) => (v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));

    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
}

function readableForeground(hex: string): string {
    return luminance(hex) > 0.45 ? '#0f172a' : '#ffffff';
}

/**
 * Applies the brand settings (colors/logo) as CSS variables at runtime.
 * Defaults live in config/brand.php; the DB overrides them.
 */
export function BrandThemeProvider({ children }: { children: React.ReactNode }) {
    const brand = usePage().props.brand as BrandData;
    const root = typeof document !== 'undefined' ? document.documentElement : null;

    React.useEffect(() => {
        if (!root) return;

        setAppName(brand.app_name);

        root.style.setProperty('--brand-primary', brand.primary_color);
        root.style.setProperty('--brand-secondary', brand.secondary_color);
        root.style.setProperty(
            '--brand-primary-foreground',
            readableForeground(brand.primary_color),
        );

        if (brand.favicon_url) {
            let link = document.querySelector<HTMLLinkElement>("link[rel='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.href = brand.favicon_url;
        }
    }, [brand, root]);

    return <>{children}</>;
}
