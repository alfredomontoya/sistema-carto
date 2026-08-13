import * as React from 'react';
import { Header } from '@/components/layout/Header';
import { Sidebar } from '@/components/layout/Sidebar';
import { Footer } from '@/components/layout/Footer';
import { BrandThemeProvider } from '@/components/brand/BrandThemeProvider';
import { cn } from '@/lib/utils';

const SIDEBAR_KEY = 'app-sidebar-collapsed';

export default function AppLayout({ children }: { children: React.ReactNode }) {
    const [collapsed, setCollapsed] = React.useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem(SIDEBAR_KEY) === '1';
    });
    const [mobileOpen, setMobileOpen] = React.useState(false);

    const toggle = React.useCallback(() => {
        setCollapsed((c) => {
            window.localStorage.setItem(SIDEBAR_KEY, c ? '0' : '1');
            return !c;
        });
    }, []);

    const openMobile = React.useCallback(() => setMobileOpen(true), []);
    const closeMobile = React.useCallback(() => setMobileOpen(false), []);

    return (
        <BrandThemeProvider>
            <div className="min-h-screen bg-background">
                <Sidebar
                    collapsed={collapsed}
                    onToggle={toggle}
                    mobileOpen={mobileOpen}
                    onClose={closeMobile}
                />
                <div
                    className={cn(
                        'flex min-h-screen flex-col transition-all duration-300',
                        collapsed ? 'lg:pl-16' : 'lg:pl-64',
                    )}
                >
                    <Header onOpenMobile={openMobile} />
                    <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">{children}</main>
                    <Footer collapsed={collapsed} />
                </div>
            </div>
        </BrandThemeProvider>
    );
}
