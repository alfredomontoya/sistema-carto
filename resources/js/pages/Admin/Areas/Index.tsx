import { Deferred, Head, useForm } from '@inertiajs/react';
import {
    Briefcase,
    ChevronDown,
    ChevronRight,
    FolderPlus,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import { cn } from '@/lib/utils';
import { treeToOptions } from '@/lib/area-tree';
import type { AreaNode, PositionData } from '@/types';

interface AreaFormState {
    name: string;
    code: string;
    description: string;
    parent_id: string;
    numbering_area_id: string;
    is_active: boolean;
    reset_annually: boolean;
}

interface PositionFormState {
    area_id: string;
    name: string;
    code: string;
    is_active: boolean;
    sort_order: string;
}

export default function AreasIndex({ areas }: { areas?: AreaNode[] }) {
    const [expanded, setExpanded] = React.useState<Set<string>>(new Set());
    const [search, setSearch] = React.useState('');
    const tree = areas ?? [];
    const [areaDialogOpen, setAreaDialogOpen] = React.useState(false);
    const [editing, setEditing] = React.useState<AreaNode | null>(null);
    const [deleting, setDeleting] = React.useState<AreaNode | null>(null);

    const [positionDialogOpen, setPositionDialogOpen] = React.useState(false);
    const [editingPosition, setEditingPosition] = React.useState<PositionData | null>(null);
    const [positionArea, setPositionArea] = React.useState<AreaNode | null>(null);
    const [deletingPosition, setDeletingPosition] = React.useState<PositionData | null>(null);

    const areaForm = useForm<AreaFormState>({
        name: '',
        code: '',
        description: '',
        parent_id: '',
        numbering_area_id: '',
        is_active: true,
        reset_annually: true,
    });

    const positionForm = useForm<PositionFormState>({
        area_id: '',
        name: '',
        code: '',
        is_active: true,
        sort_order: '0',
    });

    const openCreateArea = (parentId = '') => {
        setEditing(null);
        areaForm.reset();
        areaForm.setData('parent_id', parentId);
        setAreaDialogOpen(true);
    };

    const openEditArea = (node: AreaNode) => {
        setEditing(node);
        areaForm.setData({
            name: node.name,
            code: node.code,
            description: node.description ?? '',
            parent_id: node.parent_id ?? '',
            numbering_area_id: node.numbering_area_id ?? '',
            is_active: node.is_active,
            reset_annually: node.reset_annually,
        });
        setAreaDialogOpen(true);
    };

    const submitArea = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            areaForm.put(route('admin.areas.update', editing.id), {
                onSuccess: () => setAreaDialogOpen(false),
            });
        } else {
            areaForm.post(route('admin.areas.store'), {
                onSuccess: () => setAreaDialogOpen(false),
            });
        }
    };

    const confirmDeleteArea = () => {
        if (!deleting) return;
        areaForm.delete(route('admin.areas.destroy', deleting.id), {
            onSuccess: () => setDeleting(null),
        });
    };

    const openCreatePosition = (area: AreaNode) => {
        setEditingPosition(null);
        setPositionArea(area);
        positionForm.reset();
        positionForm.setData('area_id', area.id);
        setPositionDialogOpen(true);
    };

    const openEditPosition = (area: AreaNode, position: PositionData) => {
        setEditingPosition(position);
        setPositionArea(area);
        positionForm.setData({
            area_id: area.id,
            name: position.name,
            code: position.code ?? '',
            is_active: position.is_active,
            sort_order: String(position.sort_order),
        });
        setPositionDialogOpen(true);
    };

    const submitPosition = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingPosition) {
            positionForm.put(route('admin.positions.update', editingPosition.id), {
                onSuccess: () => setPositionDialogOpen(false),
            });
        } else {
            positionForm.post(route('admin.positions.store'), {
                onSuccess: () => setPositionDialogOpen(false),
            });
        }
    };

    const confirmDeletePosition = () => {
        if (!deletingPosition) return;
        positionForm.delete(route('admin.positions.destroy', deletingPosition.id), {
            onSuccess: () => setDeletingPosition(null),
        });
    };

    const toggle = (id: string) => {
        setExpanded((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const searching = search.trim().length > 0;
    const visibleAreas = searching ? filterTree(tree, search) : tree;

    React.useEffect(() => {
        if (areas && areas.length > 0) {
            setExpanded(new Set(collectExpandableIds(areas)));
        }
    }, [areas]);

    const renderPositions = (node: AreaNode) => {
        if ((node.positions?.length ?? 0) === 0) {
            return (
                <div
                    className="flex items-center gap-2 px-2 py-1 text-xs text-muted-foreground"
                    style={{ marginLeft: (node.depth + 1) * 20 + 24 }}
                >
                    <Briefcase className="h-3 w-3" />
                    Sin puestos. Agrega el primero.
                </div>
            );
        }

        return (
            <div className="space-y-0.5">
                {node.positions.map((position) => (
                    <div
                        key={position.id}
                        className="group flex items-center gap-2 rounded-md px-2 py-1 hover:bg-muted/50"
                        style={{ marginLeft: (node.depth + 1) * 20 + 24 }}
                    >
                        <Briefcase className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                        <span
                            className={cn(
                                'text-sm text-foreground',
                                position.is_active === false && 'opacity-60 line-through',
                            )}
                        >
                            {position.name}
                        </span>
                        {position.code && (
                            <span className="text-xs text-muted-foreground">{position.code}</span>
                        )}
                        <Badge variant="secondary" className="ml-1">
                            {position.user_count} usuario(s)
                        </Badge>
                        <div className="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6"
                                onClick={() => openEditPosition(node, position)}
                            >
                                <Pencil className="h-3.5 w-3.5" />
                                <span className="sr-only">Editar puesto</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6 text-destructive hover:text-destructive"
                                onClick={() => setDeletingPosition(position)}
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                                <span className="sr-only">Eliminar puesto</span>
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        );
    };

    const renderNode = (node: AreaNode, forceOpen = false) => {
        const isExpandable =
            (node.children?.length ?? 0) > 0 || (node.positions?.length ?? 0) > 0;
        const isOpen = forceOpen || expanded.has(node.id);

        return (
            <React.Fragment key={node.id}>
                <div
                    className={cn(
                        'group flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-muted/50',
                        node.is_active === false && 'opacity-60',
                    )}
                    style={{ marginLeft: node.depth * 20 }}
                >
                    <button
                        type="button"
                        onClick={() => isExpandable && toggle(node.id)}
                        className={cn(
                            'flex h-6 w-6 items-center justify-center rounded',
                            isExpandable ? 'text-muted-foreground hover:bg-accent' : 'invisible',
                        )}
                    >
                        {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                    </button>

                    <FolderIcon color={isExpandable ? 'var(--brand-primary)' : 'var(--brand-secondary)'} />

                    <div
                        className={cn(
                            'min-w-0 flex-1',
                            isExpandable && 'cursor-pointer',
                        )}
                        onClick={() => isExpandable && toggle(node.id)}
                        title={isExpandable ? (isOpen ? 'Colapsar' : 'Expandir') : undefined}
                    >
                        <span className="text-sm font-medium text-foreground">{node.name}</span>
                        <span className="ml-2 text-xs text-muted-foreground">{node.code}</span>
                        {node.numbering_area_id && (
                            <Badge variant="outline" className="ml-2 border-primary text-primary">
                                numeración → {node.numbering_area_name}
                            </Badge>
                        )}
                        {node.position_count > 0 && (
                            <Badge variant="secondary" className="ml-2">
                                {node.position_count} puesto(s)
                            </Badge>
                        )}
                        {node.user_count > 0 && (
                            <Badge variant="secondary" className="ml-2">
                                {node.user_count} usuario(s)
                            </Badge>
                        )}
                    </div>

                    <div className="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                        <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => openCreateArea(node.id)}>
                            <Plus className="h-4 w-4" />
                            <span className="sr-only">Agregar subárea</span>
                        </Button>
                        <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => openEditArea(node)}>
                            <Pencil className="h-4 w-4" />
                            <span className="sr-only">Editar</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-destructive hover:text-destructive"
                            onClick={() => setDeleting(node)}
                        >
                            <Trash2 className="h-4 w-4" />
                            <span className="sr-only">Eliminar</span>
                        </Button>
                    </div>
                </div>

                {isOpen && (
                    <>
                        {renderPositions(node)}
                        {node.children?.map((child) => renderNode(child, forceOpen))}
                    </>
                )}
            </React.Fragment>
        );
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Áreas" />

            <div className="mx-auto max-w-4xl space-y-6">
                <PageHeader
                    title="Áreas"
                    description="Estructura jerárquica de áreas (departamentos) y sus puestos."
                    actions={
                        <Button onClick={() => openCreateArea()}>
                            <FolderPlus /> Nueva área
                        </Button>
                    }
                />

                <Card>
                    <CardContent className="space-y-3 p-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar área por nombre o código…"
                                className="pl-9"
                            />
                        </div>
                        <div className="space-y-0.5">
                            <Deferred data="areas" fallback={<AreasSkeleton />}>
                                {visibleAreas.length === 0 ? (
                                    <p className="py-12 text-center text-muted-foreground">
                                        {searching
                                            ? `No se encontraron áreas para "${search.trim()}".`
                                            : 'No hay áreas creadas. Crea la primera.'}
                                    </p>
                                ) : (
                                    visibleAreas.map((node) => renderNode(node, searching))
                                )}
                            </Deferred>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={areaDialogOpen} onOpenChange={setAreaDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Editar área' : 'Nueva área'}</DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Modifica los datos del área.'
                                : 'Crea una nueva área. Puede ser una subárea de otra existente.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitArea} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="area-name">Nombre</Label>
                            <Input
                                id="area-name"
                                value={areaForm.data.name}
                                onChange={(e) => areaForm.setData('name', e.target.value)}
                                placeholder="Ej. CARTOGRAFIA"
                            />
                            {areaForm.errors.name && (
                                <p className="text-sm text-destructive">{areaForm.errors.name}</p>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-2">
                                <Label htmlFor="area-code">Código</Label>
                                <Input
                                    id="area-code"
                                    value={areaForm.data.code}
                                    onChange={(e) =>
                                        areaForm.setData('code', e.target.value.toLowerCase())
                                    }
                                    placeholder="Ej. carto"
                                />
                                {areaForm.errors.code && (
                                    <p className="text-sm text-destructive">{areaForm.errors.code}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="area-parent">Área padre</Label>
                                <select
                                    id="area-parent"
                                    className="flex h-9 w-full rounded-md border border-input bg-popover px-3 py-2 text-sm text-popover-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={areaForm.data.parent_id}
                                    onChange={(e) => areaForm.setData('parent_id', e.target.value)}
                                >
                                    <option value="">— Ninguna (raíz) —</option>
                                    {treeToOptions(tree).map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="area-numbering">Área de numeración (opcional)</Label>
                            <select
                                id="area-numbering"
                                    className="flex h-9 w-full rounded-md border border-input bg-popover px-3 py-2 text-sm text-popover-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={areaForm.data.numbering_area_id}
                                onChange={(e) => areaForm.setData('numbering_area_id', e.target.value)}
                            >
                                <option value="">— Propia (por defecto) —</option>
                                {treeToOptions(tree)
                                    .filter((o) => o.value !== editing?.id)
                                    .map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                            </select>
                            <p className="text-xs text-muted-foreground">
                                Si se configura, esta área usará la secuencia y el prefijo del área
                                elegida (ej. un área configurada para numerar como CARTOGRAFIA genera
                                ci.carto.000X).
                            </p>
                            {areaForm.errors.numbering_area_id && (
                                <p className="text-sm text-destructive">
                                    {areaForm.errors.numbering_area_id}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="area-desc">Descripción</Label>
                            <Textarea
                                id="area-desc"
                                value={areaForm.data.description}
                                onChange={(e) => areaForm.setData('description', e.target.value)}
                            />
                        </div>
                        <div className="flex items-center justify-between rounded-md border p-3">
                            <div>
                                <p className="font-medium">Área activa</p>
                                <p className="text-xs text-muted-foreground">Inactiva se oculta de nuevas asignaciones.</p>
                            </div>
                            <Switch
                                checked={areaForm.data.is_active}
                                onCheckedChange={(v) => areaForm.setData('is_active', v)}
                            />
                        </div>
                        <div className="flex items-center justify-between rounded-md border p-3">
                            <div>
                                <p className="font-medium">Reiniciar numeración cada año</p>
                                <p className="text-xs text-muted-foreground">
                                    Activado por defecto: el correlativo reinicia en 0001 cada año. Si lo
                                    desactivas, la secuencia continúa de un año a otro.
                                </p>
                            </div>
                            <Switch
                                checked={areaForm.data.reset_annually}
                                onCheckedChange={(v) => areaForm.setData('reset_annually', v)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAreaDialogOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={areaForm.processing}>
                                {areaForm.processing ? 'Guardando…' : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={positionDialogOpen} onOpenChange={setPositionDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingPosition ? 'Editar puesto' : 'Nuevo puesto'}</DialogTitle>
                        <DialogDescription>
                            {editingPosition
                                ? 'Modifica los datos del puesto.'
                                : `Crea un nuevo puesto en el área ${positionArea?.name ?? ''}.`}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitPosition} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="position-name">Nombre</Label>
                            <Input
                                id="position-name"
                                value={positionForm.data.name}
                                onChange={(e) => positionForm.setData('name', e.target.value)}
                                placeholder="Ej. JEFE"
                            />
                            {positionForm.errors.name && (
                                <p className="text-sm text-destructive">{positionForm.errors.name}</p>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-2">
                                <Label htmlFor="position-code">Código (opcional)</Label>
                                <Input
                                    id="position-code"
                                    value={positionForm.data.code}
                                    onChange={(e) =>
                                        positionForm.setData('code', e.target.value.toLowerCase())
                                    }
                                    placeholder="Ej. jefe"
                                />
                                {positionForm.errors.code && (
                                    <p className="text-sm text-destructive">{positionForm.errors.code}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="position-order">Orden</Label>
                                <Input
                                    id="position-order"
                                    type="number"
                                    min={0}
                                    value={positionForm.data.sort_order}
                                    onChange={(e) => positionForm.setData('sort_order', e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-between rounded-md border p-3">
                            <div>
                                <p className="font-medium">Puesto activo</p>
                                <p className="text-xs text-muted-foreground">
                                    Inactivo no aparece en nuevas asignaciones.
                                </p>
                            </div>
                            <Switch
                                checked={positionForm.data.is_active}
                                onCheckedChange={(v) => positionForm.setData('is_active', v)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setPositionDialogOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={positionForm.processing}>
                                {positionForm.processing ? 'Guardando…' : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={deleting !== null} onOpenChange={(o) => !o && setDeleting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar área</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar <strong>{deleting?.name}</strong>? Solo se
                            eliminará si no tiene subáreas, puestos ni comunicaciones.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancelar
                        </Button>
                        <Button variant="destructive" onClick={confirmDeleteArea} disabled={areaForm.processing}>
                            {areaForm.processing ? 'Eliminando…' : 'Eliminar área'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={deletingPosition !== null} onOpenChange={(o) => !o && setDeletingPosition(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar puesto</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar el puesto{' '}
                            <strong>{deletingPosition?.name}</strong>? Solo se eliminará si no tiene
                            usuarios asignados ni historial.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeletingPosition(null)}>
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDeletePosition}
                            disabled={positionForm.processing}
                        >
                            {positionForm.processing ? 'Eliminando…' : 'Eliminar puesto'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function collectExpandableIds(nodes: AreaNode[], acc: string[] = []): string[] {
    for (const node of nodes) {
        if ((node.children?.length ?? 0) > 0 || (node.positions?.length ?? 0) > 0) {
            acc.push(node.id);
        }
        collectExpandableIds(node.children ?? [], acc);
    }
    return acc;
}

function filterTree(nodes: AreaNode[], query: string): AreaNode[] {
    const q = query.trim().toLowerCase();
    const matches = (node: AreaNode) =>
        node.name.toLowerCase().includes(q) || (node.code ?? '').toLowerCase().includes(q);

    const prune = (node: AreaNode): AreaNode | null => {
        const children = (node.children ?? [])
            .map((child) => prune(child))
            .filter((child): child is AreaNode => child !== null);

        if (children.length > 0 || matches(node)) {
            return { ...node, children };
        }

        return null;
    };

    return nodes.map((node) => prune(node)).filter((node): node is AreaNode => node !== null);
}

function AreasSkeleton() {
    return (
        <div className="space-y-2 py-2">
            {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center gap-2" style={{ paddingLeft: (i % 3) * 20 }}>
                    <Skeleton className="h-5 w-5" />
                    <Skeleton className="h-4 w-40" />
                    <Skeleton className="h-4 w-12" />
                </div>
            ))}
        </div>
    );
}

function FolderIcon({ color }: { color: string }) {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5 shrink-0" fill="none" stroke={color} strokeWidth="2">
            <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
        </svg>
    );
}
