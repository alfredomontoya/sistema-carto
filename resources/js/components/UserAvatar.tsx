import * as React from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import type { UserData } from '@/types';

function initialsOf(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

const defaultGradients: Record<string, [string, string]> = {
    ocean: ['#2563eb', '#06b6d4'],
    forest: ['#059669', '#84cc16'],
    sunset: ['#f97316', '#ef4444'],
    purple: ['#8b5cf6', '#ec4899'],
    slate: ['#334155', '#64748b'],
    amber: ['#d97706', '#f59e0b'],
    rose: ['#e11d48', '#f43f5e'],
    teal: ['#0d9488', '#22d3ee'],
    indigo: ['#4f46e5', '#818cf8'],
    lime: ['#65a30d', '#a3e635'],
    cyan: ['#0891b2', '#67e8f9'],
    emerald: ['#047857', '#34d399'],
};

export function UserAvatar({
    user,
    className,
    fallbackClassName,
}: {
    user: Pick<UserData, 'name' | 'avatar_kind' | 'avatar_value' | 'avatar_url'>;
    className?: string;
    fallbackClassName?: string;
}) {
    const [from, to] =
        user.avatar_kind === 'gallery' && user.avatar_value && defaultGradients[user.avatar_value]
            ? defaultGradients[user.avatar_value]
            : ['#64748b', '#94a3b8'];

    return (
        <Avatar className={cn('h-9 w-9', className)}>
            {user.avatar_kind === 'upload' && user.avatar_url ? (
                <AvatarImage src={user.avatar_url} alt={user.name} />
            ) : (
                <AvatarFallback
                    className={cn('text-xs font-bold text-white', fallbackClassName)}
                    style={{ background: `linear-gradient(135deg, ${from}, ${to})` }}
                >
                    {initialsOf(user.name)}
                </AvatarFallback>
            )}
        </Avatar>
    );
}
