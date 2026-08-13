import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileUp, Hash, Save } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { RecipientInput } from '@/components/communications/RecipientInput';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout';
import type { CountersData, CounterState } from '@/types';

export default function Create({
    counters,
    current_area,
    year,
}: {
    counters: CountersData;
    current_area: string | null;
    year: number;
}) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'ci' as 'ci' | 'of',
        reference: '',
        recipient_name: '',
        recipient_position: '',
        recipient_user_id: '',
        file: null as File | null,
    });

    const setType = (type: 'ci' | 'of') => setData('type', type);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('communications.store'));
    };

    const activeCounter = counters[data.type];

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Nueva comunicación" />

            <div className="mx-auto max-w-3xl space-y-6">
                <PageHeader
                    title="Nueva comunicación"
                    description={`Genera un correlativo interno (ci) u oficio externo (of) para el año ${year}.`}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/comunicaciones">
                                <ArrowLeft /> Volver
                            </Link>
                        </Button>
                    }
                />

                {current_area === null ? (
                    <Card>
                        <CardContent className="p-6">
                            <p className="text-muted-foreground">
                                No tienes un área asignada. Contacta al administrador para poder
                                generar correlativos.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Hash className="h-4 w-4 text-muted-foreground" /> Números correlativos
                                    del área <span className="text-primary">{current_area}</span>
                                </CardTitle>
                                <CardDescription>
                                    El siguiente número se asignará automáticamente al guardar.
                                    {counters.numbering_area && (
                                        <span>
                                            {' '}
                                            La numeración sigue el área{' '}
                                            <strong className="text-foreground">
                                                {counters.numbering_area.name}
                                            </strong>
                                            .
                                        </span>
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {(['ci', 'of'] as const).map((t) => (
                                        <button
                                            key={t}
                                            type="button"
                                            onClick={() => setType(t)}
                                            className={cn(
                                                'rounded-lg border p-4 text-left transition-colors',
                                                data.type === t
                                                    ? 'border-primary bg-accent ring-2 ring-ring'
                                                    : 'hover:bg-muted/50',
                                            )}
                                        >
                                            <div className="flex items-center justify-between">
                                                <Badge variant={data.type === t ? 'default' : 'secondary'}>
                                                    {t === 'ci' ? 'Comunicación interna' : 'Oficio externo'}
                                                </Badge>
                                            </div>
                                            <p className="mt-3 font-mono text-lg font-semibold text-foreground">
                                                {counters[t].next_number ?? '—'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Nro. actual: {counters[t].sequence} · siguiente al guardar
                                            </p>
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

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
                                            placeholder="Motivo o asunto de la comunicación…"
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
                                        <p className="text-sm text-destructive">
                                            {errors.recipient_name}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileUp className="h-4 w-4 text-muted-foreground" /> Documento adjunto
                                    </CardTitle>
                                    <CardDescription>
                                        PDF, Word o imagen (máx. 10 MB).
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Input
                                        type="file"
                                        accept=".pdf,.doc,.docx,image/jpeg,image/png,image/gif,image/webp"
                                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                                    />
                                    {errors.file && (
                                        <p className="mt-2 text-sm text-destructive">{errors.file}</p>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing} size="lg">
                                    <Save /> {processing ? 'Guardando…' : 'Guardar comunicación'}
                                </Button>
                            </div>
                        </form>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
