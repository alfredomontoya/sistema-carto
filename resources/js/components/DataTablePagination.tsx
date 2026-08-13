import * as React from 'react';
import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PaginationData } from '@/types';

type PageItem = number | 'ellipsis';

function buildPages(current: number, total: number): PageItem[] {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages: PageItem[] = [1];
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);

    if (start > 2) {
        pages.push('ellipsis');
    }
    for (let page = start; page <= end; page += 1) {
        pages.push(page);
    }
    if (end < total - 1) {
        pages.push('ellipsis');
    }
    pages.push(total);

    return pages;
}

export function DataTablePagination({
    pagination,
    filters,
}: {
    pagination: PaginationData;
    filters?: Record<string, unknown>;
}) {
    const { current_page, last_page, total } = pagination;

    const goto = (page: number) => {
        router.get(window.location.pathname, { ...filters, page }, { preserveState: true, replace: true });
    };

    if (last_page <= 1) return null;

    const pages = buildPages(current_page, last_page);

    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Página {current_page} de {last_page} · {total} registro(s)
            </p>
            <div className="flex flex-wrap items-center gap-1">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={current_page <= 1}
                    onClick={() => goto(current_page - 1)}
                >
                    <ChevronLeft /> Anterior
                </Button>

                {pages.map((item, index) =>
                    item === 'ellipsis' ? (
                        <span key={`ellipsis-${index}`} className="px-1 text-sm text-muted-foreground">
                            …
                        </span>
                    ) : (
                        <Button
                            key={item}
                            variant={item === current_page ? 'default' : 'outline'}
                            size="icon"
                            className={cn('h-8 w-8', item === current_page && 'pointer-events-none')}
                            onClick={() => goto(item)}
                        >
                            {item}
                        </Button>
                    ),
                )}

                <Button
                    variant="outline"
                    size="sm"
                    disabled={current_page >= last_page}
                    onClick={() => goto(current_page + 1)}
                >
                    Siguiente <ChevronRight />
                </Button>
            </div>
        </div>
    );
}