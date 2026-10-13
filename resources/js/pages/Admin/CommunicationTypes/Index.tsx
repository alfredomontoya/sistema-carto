import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import * as React from 'react';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import type { CommunicationTypeData } from '@/types';

interface TypeFormState {
    code: string;
    name: string;
    description: string;
    digits: number;
    format_template: string;
    reset_annually: boolean;
    is_active: boolean;
    sort_order: number;
}

export default function CommunicationTypesIndex({ types }: { types: CommunicationTypeData[] }) {
    const [dialogOpen, setDialogOpen] = React.useState(false);
    const [editing, setEditing] = React.useState<CommunicationTypeData | null>(null);
    const [deleting, setDeleting] = React.useState<CommunicationTypeData | null>(null);

    const form = useForm<TypeFormState>({
        code: '',
        name: '',
        description: '',
        digits: 4,
        format_template: '',
        reset_annually: true,
        is_active: true,
        sort_order: 0,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        setDialogOpen(true);
    };

    const openEdit = (type: CommunicationTypeData) => {
        setEditing(type);
        form.setData({
            code: type.code,
            name: type.name,
            description: type.description ?? '',
            digits: type.digits,
            format_template: type.format_template ?? '',
            reset_annually: type.reset_annually,
            is_active: type.is_active,
            sort_order: type.sort_order,
        });
        setDialogOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            form.put(route('admin.communication-types.update', editing.id), {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            form.post(route('admin.communication-types.store'), {
                onSuccess: () => setDialogOpen(false),
            });
        }
    };

    const confirmDelete = () => {
        if (!deleting) return;
        form.delete(route('admin.communication-types.destroy', deleting.id), {
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Tipos de comunicación" />

            <div className="space-y-6">
                <PageHeader
                    title="Tipos de comunicación"
                    description="Cada tipo genera su propia numeración por área y año (p. ej. ci.carto.0001/2026)."
                    actions={
                        <Button onClick={openCreate}>
                            <Plus /> Nuevo tipo
                        </Button>
                    }
                />

                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Dígitos</TableHead>
                                    <TableHead>Reinicio anual</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {types.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                                            No hay tipos registrados. Crea el primero para habilitar la numeración.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {types.map((t) => (
                                    <TableRow key={t.id}>
                                        <TableCell>
                                            <span className="font-mono text-sm font-semibold">{t.code}</span>
                                            {t.format_template && (
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {t.format_template}
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <p className="font-medium">{t.name}</p>
                                            {t.description && (
                                                <p className="text-xs text-muted-foreground">{t.description}</p>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-mono">{t.digits}</TableCell>
                                        <TableCell>
                                            <Badge variant={t.reset_annually ? 'secondary' : 'outline'}>
                                                {t.reset_annually ? 'Anual' : 'Continuo'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={t.is_active ? 'success' : 'warning'}>
                                                {t.is_active ? 'Activo' : 'Inactivo'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(t)} title="Editar">
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive hover:text-destructive" onClick={() => setDeleting(t)} title="Eliminar">
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Editar tipo' : 'Nuevo tipo'}</DialogTitle>
                        <DialogDescription>
                            El código se usa como prefijo del correlativo (p. ej. <span className="font-mono">ci</span> →{' '}
                            <span className="font-mono">ci.carto.0001/2026</span>).
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="type-code">Código *</Label>
                                <Input
                                    id="type-code"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value.toLowerCase())}
                                    placeholder="ci"
                                    className="font-mono"
                                />
                                {form.errors.code && <p className="text-sm text-destructive">{form.errors.code}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type-digits">Dígitos *</Label>
                                <Input
                                    id="type-digits"
                                    type="number"
                                    min={1}
                                    max={6}
                                    value={form.data.digits}
                                    onChange={(e) => form.setData('digits', Number(e.target.value))}
                                />
                                {form.errors.digits && <p className="text-sm text-destructive">{form.errors.digits}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type-name">Nombre *</Label>
                            <Input
                                id="type-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Comunicación interna"
                            />
                            {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type-description">Descripción</Label>
                            <Textarea
                                id="type-description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                rows={2}
                            />
                            {form.errors.description && <p className="text-sm text-destructive">{form.errors.description}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type-template">Plantilla de formato</Label>
                            <Input
                                id="type-template"
                                value={form.data.format_template}
                                onChange={(e) => form.setData('format_template', e.target.value)}
                                placeholder="{prefix}.{area}.{seq}/{year}"
                                className="font-mono"
                            />
                            <p className="text-xs text-muted-foreground">
                                Vacía usa el formato canónico. Variables: {'{prefix} {area} {seq} {year}'}.
                            </p>
                            {form.errors.format_template && <p className="text-sm text-destructive">{form.errors.format_template}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="type-sort">Orden</Label>
                                <Input
                                    id="type-sort"
                                    type="number"
                                    min={0}
                                    value={form.data.sort_order}
                                    onChange={(e) => form.setData('sort_order', Number(e.target.value))}
                                />
                            </div>
                        </div>
                        <div className="flex flex-col gap-3">
                            <label className="flex items-center gap-2 text-sm">
                                <Switch
                                    checked={form.data.reset_annually}
                                    onCheckedChange={(v) => form.setData('reset_annually', v)}
                                />
                                Reiniciar numeración cada año
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Switch
                                    checked={form.data.is_active}
                                    onCheckedChange={(v) => form.setData('is_active', v)}
                                />
                                Activo (disponible al crear comunicaciones)
                            </label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Guardando…' : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={deleting !== null} onOpenChange={(o) => !o && setDeleting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar tipo</DialogTitle>
                        <DialogDescription>
                            ¿Eliminar <strong>{deleting?.name}</strong> (<span className="font-mono">{deleting?.code}</span>)?
                            Solo es posible si nunca emitió numeración.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancelar
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete} disabled={form.processing}>
                            {form.processing ? 'Eliminando…' : 'Eliminar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
