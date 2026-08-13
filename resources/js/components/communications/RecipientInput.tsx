import * as React from 'react';
import { useRef, useState } from 'react';
import { Check, Loader2, Search, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface UserResult {
    id: string;
    name: string;
    email: string;
    position: string | null;
    area: string | null;
}

export function RecipientInput({
    value,
    position,
    onChange,
    onPositionChange,
    disabled,
}: {
    value: string;
    position: string;
    onChange: (name: string, userId: string | null) => void;
    onPositionChange: (position: string) => void;
    disabled?: boolean;
}) {
    const [term, setTerm] = useState(value);
    const [results, setResults] = useState<UserResult[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [selectedUser, setSelectedUser] = useState<UserResult | null>(null);
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
                const res = await fetch(`/usuarios/buscar?term=${encodeURIComponent(q)}`);
                const data = (await res.json()) as UserResult[];
                setResults(data);
                setOpen(true);
            } finally {
                setLoading(false);
            }
        }, 300);
    };

    const pickUser = (u: UserResult) => {
        setSelectedUser(u);
        setTerm(u.name);
        onChange(u.name, u.id);
        if (u.position) onPositionChange(u.position);
        setOpen(false);
    };

    const clearUser = () => {
        setSelectedUser(null);
        setTerm('');
        onChange('', null);
        setOpen(false);
    };

    return (
        <div className="space-y-3">
            <div className="space-y-2">
                <div className="flex items-center gap-2">
                    <Label>A quién va dirigida</Label>
                    {selectedUser && <Badge variant="success">Usuario del sistema</Badge>}
                </div>
                <div className="relative">
                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        className="pl-9 pr-9"
                        placeholder="Escribe el nombre o busca un usuario…"
                        value={term}
                        disabled={disabled}
                        onChange={(e) => {
                            setTerm(e.target.value);
                            onChange(e.target.value, null);
                            search(e.target.value);
                        }}
                        onFocus={() => results.length > 0 && setOpen(true)}
                        onBlur={() => setTimeout(() => setOpen(false), 150)}
                    />
                    {loading && (
                        <Loader2 className="absolute right-3 top-2.5 h-4 w-4 animate-spin text-muted-foreground" />
                    )}
                    {selectedUser && (
                        <button
                            type="button"
                            onClick={clearUser}
                            className="absolute right-3 top-2.5 text-muted-foreground hover:text-foreground"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    )}

                    {open && results.length > 0 && (
                        <div className="absolute z-20 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md">
                            {results.map((u) => (
                                <button
                                    key={u.id}
                                    type="button"
                                    onMouseDown={() => pickUser(u)}
                                    className="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-accent"
                                >
                                    <div>
                                        <p className="font-medium text-foreground">{u.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {u.position ? `${u.position} · ${u.area ?? ''}` : 'Sin puesto'} · {u.email}
                                        </p>
                                    </div>
                                    <Check className="h-4 w-4 shrink-0 text-muted-foreground" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="recipient-position">Puesto del destinatario</Label>
                <Input
                    id="recipient-position"
                    className={cn(!position && 'border-dashed')}
                    placeholder="Ej. TECNICO"
                    value={position}
                    disabled={disabled}
                    onChange={(e) => onPositionChange(e.target.value)}
                />
            </div>
        </div>
    );
}
