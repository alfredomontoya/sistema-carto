import * as React from 'react';

export function Footer({ collapsed }: { collapsed: boolean }) {
    const year = new Date().getFullYear();

    return (
        <footer
            className={`border-t bg-card/60 px-4 py-3 transition-all duration-300 ${
                collapsed ? 'lg:pl-20' : 'lg:pl-68'
            }`}
        >
            <div className="flex flex-col items-center justify-between gap-1 text-xs text-muted-foreground sm:flex-row">
                <p>
                    {import.meta.env.VITE_APP_NAME ?? 'Sistema de Información'} · Sistema de
                    correlativos
                </p>
                <p>© {year} · Todos los derechos reservados</p>
            </div>
        </footer>
    );
}
