import { Head, router } from '@inertiajs/react';
import { Download, FileText, Pencil, Plus, Search, ShieldX } from 'lucide-react';
import * as React from 'react';
import { useEffect, useState } from 'react';
import { useEffectOnUpdate } from '@/lib/use-effect-on-update';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { CommunicationCreateDialog } from '@/components/communications/CommunicationCreateDialog';
import { CommunicationEditDialog } from '@/components/communications/CommunicationEditDialog';
import { CommunicationShowDialog } from '@/components/communications/CommunicationShowDialog';
import { CopyNumberButton } from '@/components/communications/CopyNumberButton';
import {
    ClearFiltersButton,
    FilterDropdown,
    FilterSectionTitle,
} from '@/components/communications/FilterDropdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { DataTablePagination } from '@/components/DataTablePagination';
import { formatDateTime } from '@/lib/dates';
import AppLayout from '@/layouts/AppLayout';
import type { AreaData, CommunicationData, CountersData, PaginationData } from '@/types';

type Filters = {
    search?: string;
    type?: string;
    area_id?: string;
    status?: string;
    number?: string;
    date_from?: string;
    date_to?: string;
};

export default function CommunicationsIndex({
    communications,
    areas,
    filters,
    pagination,
    counters,
    current_area,
    year,
    created = null,
}: {
    communications?: CommunicationData[];
    areas: AreaData[];
    filters: Filters;
    pagination?: PaginationData;
    counters: CountersData;
    current_area: string | null;
    year: number;
    created?: CommunicationData | null;
}) {
    const [search, setSearch] = React.useState(filters.search ?? '');
    const [type, setType] = React.useState(filters.type ?? '');
    const [status, setStatus] = React.useState(filters.status ?? '');
    const [areaId, setAreaId] = React.useState(filters.area_id ?? 'all');
    const [dateFrom, setDateFrom] = React.useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = React.useState(filters.date_to ?? '');
    const [annulTarget, setAnnulTarget] = useState<CommunicationData | null>(null);
    const [annulling, setAnnulling] = useState(false);
    const [fromCreate, setFromCreate] = useState<CommunicationData | null>(null);
    const [selected, setSelected] = useState<CommunicationData | null>(null);
    const [createOpen, setCreateOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<CommunicationData | null>(null);

    const showTarget = fromCreate ?? selected;
    const dialogOpen = showTarget !== null;

    const closeDialog = () => {
        setFromCreate(null);
        setSelected(null);
    };

    useEffect(() => {
        if (created !== null) {
            setFromCreate(created);
            setCreateOpen(false);
        }
    }, [created]);

    const applyFilters = (overrides: Partial<Filters> = {}) => {
        router.get(
            '/comunicaciones',
            {
                search: search || undefined,
                type: type || undefined,
                status: status || undefined,
                area_id: areaId,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                ...overrides,
            },
            { preserveState: true, replace: true },
        );
    };

    useEffectOnUpdate(() => {
        const timeout = setTimeout(() => applyFilters(), 350);
        return () => clearTimeout(timeout);
    }, [search]);

    useEffectOnUpdate(() => {
        applyFilters();
    }, [type, status, areaId, dateFrom, dateTo]);

    const confirmAnnul = () => {
        if (!annulTarget) return;
        setAnnulling(true);
        router.post(`/comunicaciones/${annulTarget.id}/anular`, undefined, {
            onFinish: () => {
                setAnnulling(false);
                setAnnulTarget(null);
            },
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Comunicaciones" />

            <div className="space-y-6">
                <PageHeader
                    title="Comunicaciones y oficios"
                    description="Consulta, crea y administra los correlativos del sistema."
                    actions={
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus /> Nueva comunicación
                        </Button>
                    }
                />

                <Card>
                    <div className="border-b p-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    placeholder="Buscar por número, referencia o destinatario…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <FilterDropdown
                                activeCount={
                                    [type, status, areaId !== 'all' ? areaId : '', dateFrom, dateTo].filter(
                                        Boolean,
                                    ).length
                                }
                            >
                                <div className="space-y-4">
                                    <div>
                                        <FilterSectionTitle>Tipo</FilterSectionTitle>
                                        <Select value={type} onValueChange={(v) => setType(v === '__all' ? '' : v)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los tipos" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__all">Todos los tipos</SelectItem>
                                                <SelectItem value="ci">Comunicación interna</SelectItem>
                                                <SelectItem value="of">Oficio externo</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <FilterSectionTitle>Estado</FilterSectionTitle>
                                        <Select value={status} onValueChange={(v) => setStatus(v === '__all' ? '' : v)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Activos y anulados" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__all">Activos y anulados</SelectItem>
                                                <SelectItem value="activo">Solo activos</SelectItem>
                                                <SelectItem value="anulado">Solo anulados</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <FilterSectionTitle>Área</FilterSectionTitle>
                                        <Select value={areaId} onValueChange={(v) => setAreaId(v)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todas las áreas" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todas las áreas</SelectItem>
                                                {areas.map((area) => (
                                                    <SelectItem key={area.id} value={area.id}>
                                                        {area.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <FilterSectionTitle>Desde</FilterSectionTitle>
                                            <Input
                                                type="date"
                                                value={dateFrom}
                                                onChange={(e) => setDateFrom(e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <FilterSectionTitle>Hasta</FilterSectionTitle>
                                            <Input
                                                type="date"
                                                value={dateTo}
                                                onChange={(e) => setDateTo(e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    <ClearFiltersButton
                                        onClick={() => {
                                            setType('');
                                            setStatus('');
                                            setAreaId('all');
                                            setDateFrom('');
                                            setDateTo('');
                                        }}
                                    />
                                </div>
                            </FilterDropdown>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Número</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Remitente</TableHead>
                                    <TableHead>Destinatario</TableHead>
                                    <TableHead>Área</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {communications?.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                                            No hay registros con los filtros actuales.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {communications?.map((c) => (
                                                <TableRow key={c.id} className={c.status === 'anulado' ? 'opacity-60' : ''}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-1">
                                                             <button
                                                                 type="button"
                                                                 onClick={() => setSelected(c)}
                                                                 title="Ver detalle"
                                                                 className="font-mono text-sm font-medium uppercase text-primary hover:underline"
                                                             >
                                                                {c.number}
                                                            </button>
                                                            <CopyNumberButton value={c.number} />
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={c.type === 'ci' ? 'secondary' : 'outline'}>
                                                            {c.type === 'ci' ? 'Interna' : 'Externo'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <p className="text-sm font-medium">{c.user?.name ?? '—'}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {c.position?.name ?? '—'}
                                                            {c.user?.current_area ? ` · ${c.user.current_area.name}` : ''}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell>
                                                        <p className="text-sm">{c.recipient_name}</p>
                                                        {c.recipient_position && (
                                                            <p className="text-xs text-muted-foreground">
                                                                {c.recipient_position}
                                                            </p>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-sm">{c.area?.name ?? '—'}</TableCell>
                                                    <TableCell className="whitespace-nowrap text-sm">
                                                        {formatDateTime(c.created_at)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={c.status === 'activo' ? 'success' : 'warning'}>
                                                            {c.status === 'activo' ? 'Activo' : 'Anulado'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex justify-end gap-1">
                                                            {c.file_name && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8"
                                                                    onClick={() => window.open(c.download_url!, '_blank')}
                                                                    title={c.file_name}
                                                                >
                                                                    <Download className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {c.can_edit && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8"
                                                                    onClick={() => setEditTarget(c)}
                                                                    title="Editar"
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {c.can_annul && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8 text-destructive hover:text-destructive"
                                                                    onClick={() => setAnnulTarget(c)}
                                                                    title="Anular"
                                                                >
                                                                    <ShieldX className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {!c.can_edit && !c.can_annul && !c.file_name && (
                                                                <Button variant="ghost" size="icon" className="h-8 w-8" disabled>
                                                                    <FileText className="h-4 w-4 text-muted-foreground" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                    </div>

                    <DataTablePagination pagination={pagination!} filters={filters} />
                </Card>
            </div>

            <Dialog open={annulTarget !== null} onOpenChange={(o) => !o && setAnnulTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular registro</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas la anulación de{' '}
                            <strong className="font-mono uppercase">{annulTarget?.number}</strong>? El registro
                            quedará oculto del listado por defecto, pero seguirá consultable. El número
                            correlativo no se reutiliza.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAnnulTarget(null)}>
                            Cancelar
                        </Button>
                        <Button variant="destructive" onClick={confirmAnnul} disabled={annulling}>
                            {annulling ? 'Anulando…' : 'Anular registro'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <CommunicationShowDialog
                communication={showTarget}
                open={dialogOpen}
                onOpenChange={(o) => !o && closeDialog()}
                onEdit={() => {
                    if (showTarget) setEditTarget(showTarget);
                    closeDialog();
                }}
            />

            <CommunicationCreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                counters={counters}
                current_area={current_area}
                year={year}
            />

            <CommunicationEditDialog
                communication={editTarget}
                open={editTarget !== null}
                onOpenChange={(o) => !o && setEditTarget(null)}
            />
        </AppLayout>
    );
}
