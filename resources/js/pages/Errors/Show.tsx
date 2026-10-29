import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    LayoutDashboard,
    Moon,
    SearchX,
    ServerCrash,
    ShieldAlert,
    Sun,
    TriangleAlert,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { AppLogo } from '@/components/AppLogo';
import { BrandThemeProvider } from '@/components/brand/BrandThemeProvider';
import { useTheme } from '@/components/brand/ThemeProvider';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    status: number;
    title: string;
    message: string;
    action: string;
    action_url: string;
};

type Tone = {
    icon: LucideIcon;
    ring: string;
    iconColor: string;
};

const tones: Record<number, Tone> = {
    403: {
        icon: ShieldAlert,
        ring: 'bg-amber-100 dark:bg-amber-500/15',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
    404: {
        icon: SearchX,
        ring: 'bg-slate-100 dark:bg-slate-500/15',
        iconColor: 'text-slate-600 dark:text-slate-300',
    },
    419: {
        icon: Clock,
        ring: 'bg-amber-100 dark:bg-amber-500/15',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
    429: {
        icon: Clock,
        ring: 'bg-amber-100 dark:bg-amber-500/15',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
    500: {
        icon: TriangleAlert,
        ring: 'bg-red-100 dark:bg-red-500/15',
        iconColor: 'text-red-600 dark:text-red-400',
    },
    503: {
        icon: ServerCrash,
        ring: 'bg-slate-100 dark:bg-slate-500/15',
        iconColor: 'text-slate-600 dark:text-slate-300',
    },
};

function ErrorThemeToggle() {
    const { theme, toggle } = useTheme();

    return (
        <Button
            variant="ghost"
            size="icon"
            onClick={toggle}
            aria-label="Cambiar tema"
            title={theme === 'light' ? 'Cambiar a oscuro' : 'Cambiar a claro'}
            className="absolute right-4 top-4 text-muted-foreground"
        >
            {theme === 'light' ? <Moon className="h-4 w-4" /> : <Sun className="h-4 w-4" />}
        </Button>
    );
}

export default function Show({ status, title, message, action, action_url }: Props) {
    const tone = tones[status] ?? tones[500];
    const Icon = tone.icon;
    const canGoBack = status !== 419 && typeof window !== 'undefined' && window.history.length > 1;

    const goBack = () => {
        if (canGoBack) {
            window.history.back();
            return;
        }

        router.visit(action_url);
    };

    return (
        <BrandThemeProvider>
            <Head title={`${status} - ${title}`} />

            <div
                className="relative flex min-h-screen flex-col items-center justify-center px-4 py-10"
                style={{
                    background:
                        'radial-gradient(1200px 600px at 20% -10%, color-mix(in srgb, var(--brand-primary) 22%, transparent), transparent), radial-gradient(1000px 500px at 90% 110%, color-mix(in srgb, var(--brand-secondary) 20%, transparent), transparent), var(--background)',
                }}
            >
                <ErrorThemeToggle />

                <div className="mb-6">
                    <Link href="/dashboard">
                        <AppLogo className="justify-center" />
                    </Link>
                </div>

                <Card className="w-full max-w-lg shadow-lg">
                    <CardContent className="flex flex-col items-center p-8 text-center">
                        <div
                            className={`mb-5 flex h-16 w-16 items-center justify-center rounded-2xl ${tone.ring}`}
                        >
                            <Icon className={`h-8 w-8 ${tone.iconColor}`} />
                        </div>

                        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Error {status}
                        </p>
                        <h1 className="mt-2 text-xl font-bold text-foreground">{title}</h1>
                        <p className="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">
                            {message}
                        </p>

                        <div className="mt-7 flex w-full flex-col gap-2 sm:flex-row sm:justify-center">
                            {status !== 419 && (
                                <Button variant="outline" onClick={goBack} className="sm:w-auto">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    {canGoBack ? 'Volver' : 'Volver al panel'}
                                </Button>
                            )}

                            <Button onClick={() => router.visit(action_url)} className="sm:w-auto">
                                <LayoutDashboard className="mr-2 h-4 w-4" />
                                {action}
                            </Button>
                        </div>

                        <p className="mt-6 text-xs text-muted-foreground">
                            Si necesitas ayuda, comunícate con el administrador del sistema.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </BrandThemeProvider>
    );
}
