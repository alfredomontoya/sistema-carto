import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { AreaDestinoInput } from '@/components/communications/AreaDestinoInput';
import { RecipientInput } from '@/components/communications/RecipientInput';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import type { CountersData } from '@/types';

export function CommunicationCreateForm({
    counters,
    current_area,
    year,
    onSuccess,
}: {
    counters: CountersData;
    current_area: string | null;
    year: number;
    onSuccess?: () => void;
}) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({
        type: 'ci' as 'ci' | 'of',
        reference: '',
        recipient_name: '',
        recipient_position: '',
        recipient_user_id: '',
        area_destino_id: '',
        area_destino_nombre: '',
    });

    const setType = (type: 'ci' | 'of') => setData('type', type);

    const validateLocal = (): boolean => {
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
        if (!ok) {
            toast.error('Hay errores de validación', {
                description: 'Revisa los campos marcados en el formulario.',
            });
        }
        return ok;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!validateLocal()) return;
        post(route('communications.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => onSuccess?.(),
        });
    };

    if (current_area === null) {
        return (
            <Card>
                <CardContent className="p-4">
                    <p className="text-muted-foreground">
                        No tienes un área asignada. Contacta al administrador para poder
                        generar correlativos.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardContent className="space-y-4 p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <div
                        className="flex rounded-lg border p-0.5"
                        role="group"
                        aria-label="Tipo de documento"
                    >
                        {(['ci', 'of'] as const).map((t) => (
                            <button
                                key={t}
                                type="button"
                                onClick={() => setType(t)}
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    data.type === t
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:bg-muted',
                                )}
                            >
                                {t === 'ci' ? 'Interna' : 'Oficio'}
                            </button>
                        ))}
                    </div>
                    <p className="font-mono text-sm font-semibold uppercase text-foreground">
                        {counters[data.type].next_number ?? '—'}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {current_area} · {year}
                        {counters.numbering_area &&
                            ` · numera como ${counters.numbering_area.name}`}
                    </p>
                </div>

                <Separator />

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="reference">
                            Referencia <span className="text-destructive">*</span>
                        </Label>
                        <Input
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
                        <p className="text-sm text-destructive">
                            {errors.recipient_name}
                        </p>
                    )}
                    {errors.recipient_position && (
                        <p className="text-sm text-destructive">
                            {errors.recipient_position}
                        </p>
                    )}

                    <AreaDestinoInput
                        value={data.area_destino_nombre}
                        areaId={data.area_destino_id}
                        onChange={(name, areaId) => {
                            setData('area_destino_nombre', name);
                            setData('area_destino_id', areaId ?? '');
                        }}
                    />
                    {errors.area_destino_nombre && (
                        <p className="text-sm text-destructive">
                            {errors.area_destino_nombre}
                        </p>
                    )}
                    {errors.area_destino_id && (
                        <p className="text-sm text-destructive">
                            {errors.area_destino_id}
                        </p>
                    )}

                    <div className="flex justify-end gap-2">
                        <Badge variant="secondary" className="self-center">
                            {data.type === 'ci' ? 'Comunicación interna' : 'Oficio externo'}
                        </Badge>
                        <Button type="submit" disabled={processing}>
                            <Save /> {processing ? 'Guardando…' : 'Guardar'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
