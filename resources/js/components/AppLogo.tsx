import * as React from 'react';
import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { BrandData } from '@/types';

export function AppLogo({
    className,
    iconOnly = false,
}: {
    className?: string;
    iconOnly?: boolean;
}) {
    const brand = usePage().props.brand as BrandData;

    if (brand.logo_url) {
        return (
            <div className={cn('flex items-center gap-2', className)}>
                <img
                    src={brand.logo_url}
                    alt={brand.app_name}
                    className={cn('h-9 w-9 rounded-lg object-contain', iconOnly && 'mx-auto')}
                />
                {!iconOnly && (
                    <span className="truncate text-base font-semibold text-foreground">
                        {brand.app_name}
                    </span>
                )}
            </div>
        );
    }

    const initials = brand.app_name
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <img
                src={brand.favicon_url ?? '/apple-touch-icon.png'}
                alt={initials || brand.app_name}
                title={brand.app_name}
                className={cn('h-9 w-9 shrink-0 rounded-lg object-contain', iconOnly && 'mx-auto')}
            />
            {!iconOnly && (
                <span className="truncate text-base font-semibold text-foreground">
                    {brand.app_name}
                </span>
            )}
        </div>
    );
}
