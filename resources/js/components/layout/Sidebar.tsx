import * as React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PanelLeftClose, PanelLeftOpen } from 'lucide-react';
import { AppLogo } from '@/components/AppLogo';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { navigation } from '@/lib/navigation';
import type { UserData } from '@/types';

export function Sidebar({
    collapsed,
    onToggle,
}: {
    collapsed: boolean;
    onToggle: () => void;
}) {
    const { url } = usePage();
    const user = usePage().props.auth.user as UserData;

    const nav = navigation(user.can);

    return (
        <aside
            className={cn(
                'fixed inset-y-0 left-0 z-30 flex flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-all duration-300',
                collapsed ? 'w-16' : 'w-64',
            )}
        >
            <div className={cn('flex h-16 items-center border-b border-sidebar-border', collapsed ? 'justify-center' : 'justify-between px-4')}>
                <Link href="/dashboard">
                    <AppLogo iconOnly={collapsed} />
                </Link>
                {!collapsed && (
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onToggle}
                        className="text-sidebar-foreground"
                        aria-label="Contraer menú"
                    >
                        <PanelLeftClose className="h-4 w-4" />
                    </Button>
                )}
            </div>

            {collapsed && (
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={onToggle}
                    className="mx-auto mt-3 text-sidebar-foreground"
                    aria-label="Expandir menú"
                >
                    <PanelLeftOpen className="h-4 w-4" />
                </Button>
            )}

            <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                {nav.map((section) => (
                    <div key={section.title}>
                        {!collapsed && (
                            <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/60">
                                {section.title}
                            </p>
                        )}
                        <div className="space-y-1">
                            {section.items.map((item) => {
                                const active =
                                    url === item.href || url.startsWith(`${item.href}/`);
                                const content = (
                                    <Link
                                        href={item.href}
                                        className={cn(
                                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                            collapsed && 'justify-center px-0',
                                            active
                                                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
                                        )}
                                    >
                                        <item.icon className="h-5 w-5 shrink-0" />
                                        {!collapsed && <span className="truncate">{item.label}</span>}
                                    </Link>
                                );

                                if (collapsed) {
                                    return (
                                        <TooltipProvider key={item.href} delayDuration={0}>
                                            <Tooltip>
                                                <TooltipTrigger asChild>{content}</TooltipTrigger>
                                                <TooltipContent side="right">{item.label}</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    );
                                }

                                return <React.Fragment key={item.href}>{content}</React.Fragment>;
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="border-t border-sidebar-border p-3">
                <p className={cn('px-2 text-xs text-sidebar-foreground/50', collapsed && 'text-center')}>
                    {collapsed ? 'v1.0' : '© ' + new Date().getFullYear() + ' · v1.0'}
                </p>
            </div>
        </aside>
    );
}
