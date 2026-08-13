import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileUp, Save } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { RecipientInput } from '@/components/communications/RecipientInput';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import type { CommunicationData } from '@/types';

export default function Edit({
    communication,
}: {
    communication: CommunicationData;
}) {
    const { data, setData, put, processing, errors } = useForm({
        reference: communication.reference,
        recipient_name: communication.recipient_name,
        recipient_position: communication.recipient_position ?? '',
        recipient_user_id: communication.recipient_user?.id ?? '',
        remove_file: false,
        file: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('communications.update', communication.id));
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title={`Editar: ${communication.number}`} />

            <div className="mx-auto max-w-3xl space-y-6">
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

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del documento</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="reference">Referencia</Label>
                                <Textarea
                                    id="reference"
                                    value={data.reference}
                                    onChange={(e) => setData('reference', e.target.value)}
                                />
                                {errors.reference && (
                                    <p className="text-sm text-destructive">{errors.reference}</p>
                                )}
                            </div>

                            <RecipientInput
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
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileUp className="h-4 w-4 text-muted-foreground" /> Documento adjunto
                            </CardTitle>
                            <CardDescription>
                                {communication.file_name
                                    ? `Adjunto actual: ${communication.file_name}`
                                    : 'Sin adjunto en este registro.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
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
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} size="lg">
                            <Save /> {processing ? 'Guardando…' : 'Guardar cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
