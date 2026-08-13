import { Link } from '@inertiajs/react';
import { AppLogo } from '@/components/AppLogo';
import { BrandThemeProvider } from '@/components/brand/BrandThemeProvider';
import { Card, CardContent } from '@/components/ui/card';
import type { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <BrandThemeProvider>
            <div
            className="flex min-h-screen flex-col items-center justify-center px-4 py-10"
            style={{
                background:
                    'radial-gradient(1200px 600px at 20% -10%, color-mix(in srgb, var(--brand-primary) 22%, transparent), transparent), radial-gradient(1000px 500px at 90% 110%, color-mix(in srgb, var(--brand-secondary) 20%, transparent), transparent), var(--background)',
            }}
        >
            <div className="mb-6">
                <Link href="/">
                    <AppLogo className="justify-center" />
                </Link>
            </div>

            <Card className="w-full max-w-md shadow-lg">
                <CardContent className="p-8">{children}</CardContent>
            </Card>

            <p className="mt-6 text-xs text-muted-foreground">
                © {new Date().getFullYear()} · Todos los derechos reservados
            </p>
            </div>
        </BrandThemeProvider>
    );
}
