import * as React from 'react';
import { useRef, useState } from 'react';
import { Check, Loader2, Search, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface AreaResult {
    id: string;
    name: string;
    code: string;
    parent: string | null;
}

export function AreaDestinoInput({
    value,
    areaId,
    onChange,
    disabled,
}: {
    value: string;
    areaId: string;
    onChange: (name: string, areaId: string | null) => void;
    disabled?: boolean;
}) {
    const [term, setTerm] = useState(value);
    const [results, setResults] = useState<AreaResult[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [highlight, setHighlight] = useState(0);
    const [selectedArea, setSelectedArea] = useState<AreaResult | null>(
        areaId ? { id: areaId, name: value, code: '', parent: null } : null,
    );
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const search = (q: string) => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            if (!q.trim()) {
                setResults([]);
                setLoading(false);
                return;
            }
            setLoading(true);
            try {
                const res = await fetch(`/areas/buscar?term=${encodeURIComponent(q)}&limit=10`);
                const data = (await res.json()) as AreaResult[];
                setResults(data);
                setHighlight(0);
                setOpen(true);
            } finally {
                setLoading(false);
            }
        }, 300);
    };

    const pickArea = (a: AreaResult) => {
        setSelectedArea(a);
        setTerm(a.name);
        onChange(a.name, a.id);
        setOpen(false);
    };

    const clearArea = () => {
        setSelectedArea(null);
        setTerm('');
        onChange('', null);
        setResults([]);
        setOpen(false);
    };

    const onKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!open || results.length === 0) return;
            e.preventDefault();
            setHighlight((h) =>
                e.key === 'ArrowDown'
                    ? (h + 1) % results.length
                    : (h - 1 + results.length) % results.length,
            );
        } else if (e.key === 'Enter') {
            if (open && results.length > 0) {
                e.preventDefault();
                pickArea(results[highlight] ?? results[0]);
            }
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2">
                <Label>Área destino</Label>
                {selectedArea && <Badge variant="success">Área registrada</Badge>}
            </div>
            <div className="relative">
                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                    className="pl-9 pr-9"
                    placeholder="Escribe el nombre o busca un área… (opcional)"
                    value={term}
                    disabled={disabled}
                    onChange={(e) => {
                        setTerm(e.target.value);
                        setSelectedArea(null);
                        onChange(e.target.value, null);
                        search(e.target.value);
                    }}
                    onFocus={() => results.length > 0 && setOpen(true)}
                    onBlur={() => setTimeout(() => setOpen(false), 150)}
                    onKeyDown={onKeyDown}
                />
                {loading && (
                    <Loader2 className="absolute right-3 top-2.5 h-4 w-4 animate-spin text-muted-foreground" />
                )}
                {!loading && (term || selectedArea) && (
                    <button
                        type="button"
                        onClick={clearArea}
                        className="absolute right-3 top-2.5 text-muted-foreground hover:text-foreground"
                        aria-label="Limpiar área destino"
                    >
                        <X className="h-4 w-4" />
                    </button>
                )}

                {open && results.length > 0 && (
                    <div className="absolute z-20 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md">
                        {results.map((a, i) => (
                            <button
                                key={a.id}
                                type="button"
                                onMouseDown={() => pickArea(a)}
                                onMouseEnter={() => setHighlight(i)}
                                className={cn(
                                    'flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-accent',
                                    i === highlight && 'bg-accent',
                                )}
                            >
                                <div>
                                    <p className="font-medium text-foreground">{a.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {a.parent ? `${a.parent} · ` : ''}
                                        {a.code}
                                    </p>
                                </div>
                                <Check className="h-4 w-4 shrink-0 text-muted-foreground" />
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
