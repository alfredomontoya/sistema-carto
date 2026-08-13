import { FilterX, ListFilter } from 'lucide-react';
import * as React from 'react';
import { Button } from '@/components/ui/button';

export function FilterDropdown({
    activeCount,
    children,
}: {
    activeCount: number;
    children: React.ReactNode;
}) {
    const [open, setOpen] = React.useState(false);
    const ref = React.useRef<HTMLDivElement>(null);

    React.useEffect(() => {
        if (!open) return;
        const onPointerDown = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    return (
        <div ref={ref} className="relative shrink-0">
            <Button
                variant="outline"
                size="sm"
                onClick={() => setOpen((o) => !o)}
                aria-expanded={open}
                aria-label="Filtros"
                className="gap-2"
            >
                <ListFilter className="h-4 w-4" />
                <span>Filtros</span>
                {activeCount > 0 && (
                    <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs font-semibold text-primary-foreground">
                        {activeCount}
                    </span>
                )}
            </Button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-72 rounded-md border bg-popover p-3 text-popover-foreground shadow-md">
                    {children}
                </div>
            )}
        </div>
    );
}

export function FilterSectionTitle({ children }: { children: React.ReactNode }) {
    return <p className="mb-1.5 text-xs font-semibold text-muted-foreground">{children}</p>;
}

export function ClearFiltersButton({ onClick }: { onClick: () => void }) {
    return (
        <Button variant="ghost" size="sm" className="w-full gap-2 text-muted-foreground" onClick={onClick}>
            <FilterX className="h-4 w-4" />
            Limpiar filtros
        </Button>
    );
}
