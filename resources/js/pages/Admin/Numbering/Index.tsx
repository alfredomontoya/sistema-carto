import { Head, router } from '@inertiajs/react';
import { Hash, RotateCcw, Search } from 'lucide-react';
import * as React from 'react';
import { useEffect, useState } from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';

interface NumberingRow {
    area_id: string;
    area_name: string;
    area_code: string;
    ci_current: number;
    of_current: number;
    ci_issued_max: number;
    of_issued_max: number;
}

type DocType = 'ci' | 'of';

interface ConfirmTarget {
    area_id: string;
    area_name: string;
    type: DocType;
    value: number;
    issuedMax: number;
}

function TypeCell({
    row,
    type,
    onConfirm,
}: {
    row: NumberingRow;
    type: DocType;
    onConfirm: (t: ConfirmTarget) => void;
}) {
    const current = type === 'ci' ? row.ci_current : row.of_current;
    const issuedMax = type === 'ci' ? row.ci_issued_max : row.of_issued_max;
    const [value, setValue] = useState('');

    return (
        <div className="space-y-2">
            <div className="flex items-baseline gap-2">
                <span className="font-mono text-lg font-semibold">{current}</span>
                <span className="text-xs text-muted-foreground">
                    siguiente: {current + 1} · emitido: {issuedMax}
                </span>
            </div>
            <div className="flex items-center gap-1.5">
                <Input
                    type="number"
                    min={0}
                    max={9999}
                    className="h-8 w-24"
                    placeholder="N"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                />
                <Button
                    variant="outline"
                    size="sm"
                    disabled={value === ''}
                    onClick={() =>
                        onConfirm({
                            area_id: row.area_id,
                            area_name: row.area_name,
                            type,
                            value: Number(value),
                            issuedMax,
                        })
                    }
                >
                    Fijar
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    title="Reiniciar (el siguiente será el 1)"
                    onClick={() =>
                        onConfirm({
                            area_id: row.area_id,
                            area_name: row.area_name,
                            type,
                            value: 0,
                            issuedMax,
                        })
                    }
                >
                    <RotateCcw className="h-3.5 w-3.5" /> 1
                </Button>
            </div>
        </div>
    );
}

export default function NumberingIndex({
    rows,
    filters,
    year,
}: {
    rows: NumberingRow[];
    filters: { search: string };
    year: number;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [target, setTarget] = useState<ConfirmTarget | null>(null);
    const [force, setForce] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                '/admin/numbering',
                { search: search || undefined },
                { preserveState: true, replace: true },
            );
        }, 350);
        return () => clearTimeout(timeout);
    }, [search]);

    useEffect(() => {
        setForce(false);
    }, [target]);

    const risky = target !== null && target.value <= target.issuedMax;

    const confirm = () => {
        if (!target) return;
        setSaving(true);
        router.post(route('admin.numbering.set', target.area_id), {
            type: target.type,
            value: target.value,
            force: risky ? force : false,
        }, {
            onFinish: () => {
                setSaving(false);
                setTarget(null);
            },
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Numeración actual" />

            <div className="space-y-6">
                <PageHeader
                    title="Numeración actual"
                    description={`Correlativos del año ${year}, ordenados de mayor a menor.`}
                />

                <Card>
                    <div className="border-b p-4">
                        <div className="relative max-w-sm">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder="Buscar por área…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Área</TableHead>
                                    <TableHead>
                                        <span className="flex items-center gap-1.5">
                                            <Hash className="h-3.5 w-3.5" /> Internas (ci)
                                        </span>
                                    </TableHead>
                                    <TableHead>
                                        <span className="flex items-center gap-1.5">
                                            <Hash className="h-3.5 w-3.5" /> Oficios (of)
                                        </span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={3} className="h-24 text-center text-muted-foreground">
                                            Sin áreas para el filtro actual.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {rows.map((row) => (
                                    <TableRow key={row.area_id}>
                                        <TableCell>
                                            <p className="font-medium">{row.area_name}</p>
                                            <p className="font-mono text-xs text-muted-foreground">
                                                {row.area_code}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <TypeCell row={row} type="ci" onConfirm={setTarget} />
                                        </TableCell>
                                        <TableCell>
                                            <TypeCell row={row} type="of" onConfirm={setTarget} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            <Dialog open={target !== null} onOpenChange={(o) => !o && setTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Fijar numeración de {target?.type === 'ci' ? 'internas' : 'oficios'}
                        </DialogTitle>
                        <DialogDescription>
                            {target?.value === 0 ? (
                                <>
                                    Se <strong>reiniciará</strong> {target?.area_name} ({target?.type}):
                                    el siguiente correlativo será el <strong>1</strong>.
                                </>
                            ) : (
                                <>
                                    Se fijará {target?.area_name} ({target?.type}) en{' '}
                                    <strong className="font-mono">{target?.value}</strong>: el
                                    siguiente correlativo será el{' '}
                                    <strong className="font-mono">{(target?.value ?? 0) + 1}</strong>.
                                </>
                            )}{' '}
                            Emitido hasta ahora: <strong className="font-mono">{target?.issuedMax}</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    {risky && (
                        <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm">
                            <p className="font-medium text-destructive">
                                Riesgo de duplicados: ya se emitieron números superiores.
                            </p>
                            <label className="mt-2 flex items-center gap-2">
                                <Switch checked={force} onCheckedChange={setForce} />
                                Forzar de todos modos
                            </label>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTarget(null)}>
                            Cancelar
                        </Button>
                        <Button onClick={confirm} disabled={saving || (risky && !force)}>
                            {saving ? 'Guardando…' : 'Confirmar'}
                        </Button>
                        {!risky && (
                            <Badge variant="secondary" className="self-center">
                                Sin riesgo
                            </Badge>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
