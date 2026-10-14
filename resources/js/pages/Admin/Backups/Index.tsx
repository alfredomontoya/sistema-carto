import { Head, useForm } from '@inertiajs/react';
import { DatabaseBackup, Download, HardDrive, Plus, RotateCcw, TriangleAlert, Upload } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';

interface BackupRow {
    name: string;
    created_at: string | null;
    size: number;
    size_human: string;
}

interface DestinationInfo {
    drive: string | null;
    path: string;
    free: number | null;
    total: number | null;
    used_percent: number | null;
    status: 'ok' | 'low' | 'critical';
}

function formatBytes(bytes: number | null): string {
    if (bytes === null) return '—';
    if (bytes < 1024) return `${bytes} B`;
    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    for (const unit of units) {
        value /= 1024;
        if (value < 1024) return `${value.toFixed(1)} ${unit}`;
    }
    return `${value.toFixed(1)} PB`;
}

export default function BackupsIndex({
    backups,
    destination,
    drives,
    max,
}: {
    backups: BackupRow[];
    destination: DestinationInfo;
    drives: string[];
    max: number;
}) {
    const settings = useForm({ drive: destination.drive ?? '', max });
    const generate = useForm({});
    const restore = useForm({ file: '', confirm: false });
    const upload = useForm<{ backup_file: File | null; confirm: boolean }>({
        backup_file: null,
        confirm: false,
    });
    const [restoreTarget, setRestoreTarget] = React.useState<BackupRow | null>(null);

    const saveSettings = (e: React.FormEvent) => {
        e.preventDefault();
        settings.put(route('admin.backups.update'), { preserveScroll: true });
    };

    const openRestore = (row: BackupRow) => {
        restore.setData({ file: row.name, confirm: false });
        setRestoreTarget(row);
    };

    const doRestore = (e: React.FormEvent) => {
        e.preventDefault();
        restore.post(route('admin.backups.restore'), {
            preserveScroll: true,
            onSuccess: () => setRestoreTarget(null),
        });
    };

    const doUploadRestore = (e: React.FormEvent) => {
        e.preventDefault();
        upload.post(route('admin.backups.upload-restore'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.reset(),
        });
    };

    const barColor =
        destination.status === 'critical'
            ? 'bg-destructive'
            : destination.status === 'low'
              ? 'bg-warning'
              : 'bg-success';

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Respaldos" />

            <div className="mx-auto max-w-4xl space-y-6">
                <PageHeader
                    title="Respaldos"
                    description="Genera y restaura copias de la base de datos. Solo administradores."
                    actions={
                        <Button
                            onClick={() => generate.post(route('admin.backups.store'))}
                            disabled={generate.processing || destination.status === 'critical'}
                        >
                            <Plus /> {generate.processing ? 'Generando…' : 'Generar respaldo'}
                        </Button>
                    }
                />

                {destination.status !== 'ok' && (
                    <Card className={destination.status === 'critical' ? 'border-destructive' : 'border-warning'}>
                        <CardContent className="flex items-center gap-3 p-4">
                            <TriangleAlert
                                className={`h-5 w-5 shrink-0 ${destination.status === 'critical' ? 'text-destructive' : 'text-warning'}`}
                            />
                            <p className="text-sm font-medium">
                                {destination.status === 'critical'
                                    ? 'Espacio crítico en el disco seleccionado: no se pueden generar respaldos. Libera espacio o cambia de unidad.'
                                    : 'Poco espacio libre en el disco seleccionado. Considera liberar espacio o cambiar de unidad.'}
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <HardDrive className="h-4 w-4 text-muted-foreground" /> Destino y retención
                        </CardTitle>
                        <CardDescription>
                            Carpeta del servidor donde se guardan los respaldos y cuántos conservar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={saveSettings} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Unidad del servidor</Label>
                                    <Select
                                        value={settings.data.drive === '' ? '__local' : settings.data.drive}
                                        onValueChange={(v) => settings.setData('drive', v === '__local' ? '' : v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Almacenamiento local" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__local">
                                                Local del sistema (storage/app/backups)
                                            </SelectItem>
                                            {drives.map((d) => (
                                                <SelectItem key={d} value={d}>
                                                    Unidad {d}: ({d}:\respaldos)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {settings.errors.drive && (
                                        <p className="text-sm text-destructive">{settings.errors.drive}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="max">Máximo de respaldos</Label>
                                    <Input
                                        id="max"
                                        type="number"
                                        min={1}
                                        max={100}
                                        value={settings.data.max}
                                        onChange={(e) => settings.setData('max', Number(e.target.value))}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Al superar el límite se elimina el más antiguo.
                                    </p>
                                    {settings.errors.max && (
                                        <p className="text-sm text-destructive">{settings.errors.max}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span className="font-mono">{destination.path}</span>
                                    <span>
                                        {formatBytes(destination.free)} libres de {formatBytes(destination.total)}
                                        {destination.used_percent !== null && ` (${destination.used_percent}% usado)`}
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${barColor}`}
                                        style={{ width: `${destination.used_percent ?? 0}%` }}
                                    />
                                </div>
                            </div>

                            <Button type="submit" disabled={settings.processing}>
                                {settings.processing ? 'Guardando…' : 'Guardar configuración'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Upload className="h-4 w-4 text-muted-foreground" /> Restaurar archivo
                            externo
                        </CardTitle>
                        <CardDescription>
                            Sube un respaldo .sql o .sql.gz descargado antes y restáuralo en la base de
                            datos actual. Reemplaza todos los datos y no se puede deshacer.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={doUploadRestore} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="backup_file">Archivo de respaldo</Label>
                                <Input
                                    id="backup_file"
                                    type="file"
                                    accept=".sql,.gz"
                                    onChange={(e) =>
                                        upload.setData('backup_file', e.target.files?.[0] ?? null)
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Formatos aceptados: .sql y .gz (máx. 100 MB).
                                    {upload.data.backup_file
                                        ? ` Seleccionado: ${upload.data.backup_file.name}`
                                        : ''}
                                </p>
                                {upload.errors.backup_file && (
                                    <p className="text-sm text-destructive">
                                        {upload.errors.backup_file}
                                    </p>
                                )}
                            </div>
                            <label className="flex items-start gap-2 text-sm">
                                <Checkbox
                                    checked={upload.data.confirm}
                                    onCheckedChange={(v) => upload.setData('confirm', v === true)}
                                />
                                <span>
                                    Entiendo que se perderán los datos actuales y deseo continuar con la
                                    restauración.
                                </span>
                            </label>
                            {upload.errors.confirm && (
                                <p className="text-sm text-destructive">{upload.errors.confirm}</p>
                            )}
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={
                                    upload.processing || !upload.data.confirm || !upload.data.backup_file
                                }
                            >
                                <RotateCcw />
                                {upload.processing ? 'Restaurando…' : 'Restaurar archivo'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <DatabaseBackup className="h-4 w-4 text-muted-foreground" /> Respaldos
                            generados
                        </CardTitle>
                        <CardDescription>
                            {backups.length === 0
                                ? 'Aún no hay respaldos en el destino seleccionado.'
                                : `${backups.length} respaldo(s) en ${destination.path}.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Archivo</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Tamaño</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {backups.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-16 text-center text-muted-foreground">
                                            Sin respaldos. Genera el primero con el botón superior.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {backups.map((b) => (
                                    <TableRow key={b.name}>
                                        <TableCell className="font-mono text-xs">{b.name}</TableCell>
                                        <TableCell className="text-sm">{b.created_at ?? '—'}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{b.size_human}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <a href={route('admin.backups.download', b.name)}>
                                                        <Download /> Descargar
                                                    </a>
                                                </Button>
                                                <Button variant="destructive" size="sm" onClick={() => openRestore(b)}>
                                                    <RotateCcw /> Restaurar
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Dialog open={restoreTarget !== null} onOpenChange={(o) => !o && setRestoreTarget(null)}>
                    <DialogContent className="max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-destructive">
                                <TriangleAlert className="h-5 w-5" /> Advertencia: pérdida de datos
                            </DialogTitle>
                            <DialogDescription>
                                Estás por restaurar el respaldo{' '}
                                <strong className="font-mono">{restoreTarget?.name}</strong>. Esta acción{' '}
                                <strong>reemplazará todos los datos actuales</strong> de la base de datos y no se
                                puede deshacer. Se cerrarán las sesiones activas.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={doRestore} className="space-y-4">
                            <label className="flex items-start gap-2 text-sm">
                                <Checkbox
                                    checked={restore.data.confirm as boolean}
                                    onCheckedChange={(v) => restore.setData('confirm', v === true)}
                                />
                                <span>
                                    Entiendo que se perderán los datos actuales y deseo continuar con la
                                    restauración.
                                </span>
                            </label>
                            {restore.errors.confirm && (
                                <p className="text-sm text-destructive">{restore.errors.confirm}</p>
                            )}
                            {restore.errors.file && (
                                <p className="text-sm text-destructive">{restore.errors.file}</p>
                            )}
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setRestoreTarget(null)}>
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={restore.processing || !restore.data.confirm}
                                >
                                    {restore.processing ? 'Restaurando…' : 'Sí, restaurar'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
