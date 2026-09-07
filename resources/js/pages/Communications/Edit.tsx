import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileUp, Save } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { AreaDestinoInput } from '@/components/communications/AreaDestinoInput';
import { RecipientInput } from '@/components/communications/RecipientInput';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout';
import type { CommunicationData } from '@/types';

export default function Edit({
    communication,
}: {
    communication: CommunicationData;
}) {
    const { data, setData, put, processing, errors, setError, clearErrors } = useForm({
        reference: communication.reference,
        recipient_name: communication.recipient_name,
        recipient_position: communication.recipient_position ?? '',
        recipient_user_id: communication.recipient_user?.id ?? '',
        area_destino_id: communication.area_destino?.id ?? '',
        area_destino_nombre: communication.area_destino_nombre ?? '',
        remove_file: false,
        file: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        clearErrors();
        let ok = true;
        if (!data.reference.trim()) {
            setError('reference', 'La referencia es obligatoria.');
            ok = false;
        }
        if (!data.recipient_name.trim()) {
            setError('recipient_name', 'El destinatario es obligatorio.');
            ok = false;
        }
        if (!data.recipient_position.trim()) {
            setError('recipient_position', 'El puesto del destinatario es obligatorio.');
            ok = false;
        }
        if (communication.type === 'ci' && !data.area_destino_nombre.trim()) {
            setError(
                'area_destino_nombre',
                'El área destino es obligatoria para comunicaciones internas.',
            );
            ok = false;
        }
        if (!ok) {
            toast.error('Hay errores de validación', {
                description: 'Revisa los campos marcados en el formulario.',
            });
            return;
        }
        put(route('communications.update', communication.id), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title={`Editar: ${communication.number}`} />

            <div className="mx-auto max-w-3xl space-y-4">
                <PageHeader
                    title={
                        <span className="font-mono text-xl">{communication.number}</span>
                    }
                    description="Solo el autor puede editar un registro activo."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/comunicaciones">
                                <ArrowLeft /> Volver
                            </Link>
                        </Button>
                    }
                />

                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={communication.type === 'ci' ? 'secondary' : 'outline'}>
                        {communication.type === 'ci' ? 'Comunicación interna' : 'Oficio externo'}
                    </Badge>
                    <Badge variant="secondary">{communication.area?.name ?? '—'}</Badge>
                </div>

                <form onSubmit={submit}>
                    <Card>
                        <CardContent className="space-y-4 p-4">
                            <div className="space-y-2">
                                <Label htmlFor="reference">
                                    Referencia <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="reference"
                                    value={data.reference}
                                    onChange={(e) => setData('reference', e.target.value)}
                                />
                                {errors.reference && (
                                    <p className="text-sm text-destructive">{errors.reference}</p>
                                )}
                            </div>

                            <RecipientInput
                                layout="grid"
                                value={data.recipient_name}
                                position={data.recipient_position}
                                onChange={(name, userId) => {
                                    setData('recipient_name', name);
                                    setData('recipient_user_id', userId ?? '');
                                }}
                                onPositionChange={(p) => setData('recipient_position', p)}
                            />
                            {errors.recipient_name && (
                                <p className="text-sm text-destructive">{errors.recipient_name}</p>
                            )}
                            {errors.recipient_position && (
                                <p className="text-sm text-destructive">
                                    {errors.recipient_position}
                                </p>
                            )}

                            <Separator />

                            <div className="grid gap-4 sm:grid-cols-2">
                                <AreaDestinoInput
                                    value={data.area_destino_nombre}
                                    areaId={data.area_destino_id}
                                    onChange={(name, areaId) => {
                                        setData('area_destino_nombre', name);
                                        setData('area_destino_id', areaId ?? '');
                                    }}
                                />
                                <details className="space-y-2 rounded-lg border p-3">
                                    <summary className="flex cursor-pointer list-none items-center gap-2 text-sm font-medium">
                                        <FileUp className="h-4 w-4 text-muted-foreground" />
                                        Adjunto
                                        <span className="font-normal text-muted-foreground">
                                            ({communication.file_name ?? 'opcional'}
                                            {data.file ? `: ${data.file.name}` : ''})
                                        </span>
                                    </summary>
                                    <Input
                                        type="file"
                                        accept=".pdf,.doc,.docx,image/jpeg,image/png,image/gif,image/webp"
                                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                                    />
                                    {errors.file && (
                                        <p className="text-sm text-destructive">{errors.file}</p>
                                    )}
                                    {communication.file_name && (
                                        <label className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={data.remove_file}
                                                onCheckedChange={(v) => setData('remove_file', v === true)}
                                            />
                                            Quitar el adjunto actual
                                        </label>
                                    )}
                                </details>
                            </div>
                            {errors.area_destino_nombre && (
                                <p className="text-sm text-destructive">
                                    {errors.area_destino_nombre}
                                </p>
                            )}

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    <Save /> {processing ? 'Guardando…' : 'Guardar cambios'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
