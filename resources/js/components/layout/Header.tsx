import * as React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Menu, Moon, Sun, UserRound } from 'lucide-react';
import { UserAvatar } from '@/components/UserAvatar';
import { useTheme } from '@/components/brand/ThemeProvider';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { UserData } from '@/types';

export function Header({ onOpenMobile }: { onOpenMobile: () => void }) {
    const user = usePage().props.auth.user as UserData;
    const { theme, toggle } = useTheme();
    const userDomain = usePage().props.app.user_domain;

    return (
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-card/80 px-4 backdrop-blur sm:px-6">
            <div className="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={onOpenMobile}
                    className="text-muted-foreground lg:hidden"
                    aria-label="Abrir menú"
                >
                    <Menu className="h-5 w-5" />
                </Button>
                <div className="hidden text-sm text-muted-foreground sm:block">
                    {user.current_position ? (
                        <>
                            {user.current_position.name}
                            <span className="mx-1 text-muted-foreground/50">·</span>
                            <span className="font-semibold text-foreground">
                                {user.current_area?.name ?? '—'}
                            </span>
                        </>
                    ) : (
                        <span className="text-muted-foreground/70">Sin puesto asignado</span>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={toggle}
                    aria-label="Cambiar tema"
                    className="text-muted-foreground"
                >
                    {theme === 'light' ? <Moon className="h-4 w-4" /> : <Sun className="h-4 w-4" />}
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="flex items-center gap-2 rounded-full outline-none ring-ring focus-visible:ring-2">
                            <UserAvatar user={user} />
                            <span className="hidden text-sm font-medium text-foreground md:block">
                                {user.name}
                            </span>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>
                            <p className="text-sm font-medium text-foreground">{user.name}</p>
                            <p className="text-xs text-muted-foreground">
                                {user.username}@{userDomain}
                            </p>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link prefetch href="/profile">
                                <UserRound /> Mi perfil
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="text-destructive focus:text-destructive"
                            onClick={() => router.post('/logout')}
                        >
                            <LogOut /> Cerrar sesión
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
