import { Head, useForm } from '@inertiajs/react';
import { KeyRound, Paintbrush } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout';
import type { BrandData } from '@/types';

export default function SettingsIndex({
    brand,
    password_expiry_days,
    password_recovery_enabled,
}: {
    brand: BrandData;
    password_expiry_days: number;
    password_recovery_enabled: boolean;
}) {
    const { data, setData, put, processing, errors } = useForm({
        app_name: brand.app_name,
        password_expiry_days,
        password_recovery_enabled,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.settings.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Ajustes" />

            <div className="mx-auto max-w-3xl space-y-6">
                <PageHeader
                    title="Ajustes"
                    description="Nombre visible del sistema."
                />

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Paintbrush className="h-4 w-4 text-muted-foreground" /> Identidad
                            </CardTitle>
                            <CardDescription>Nombre visible del sistema.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="app-name">Nombre del sistema</Label>
                                <Input
                                    id="app-name"
                                    value={data.app_name}
                                    onChange={(e) => setData('app_name', e.target.value)}
                                />
                                {errors.app_name && (
                                    <p className="text-sm text-destructive">{errors.app_name}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <KeyRound className="h-4 w-4 text-muted-foreground" /> Seguridad
                            </CardTitle>
                            <CardDescription>Vigencia de las contraseñas de los usuarios.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="password-expiry-days">Días de vigencia de contraseña</Label>
                                <Input
                                    id="password-expiry-days"
                                    type="number"
                                    min={1}
                                    max={365}
                                    value={data.password_expiry_days}
                                    onChange={(e) => setData('password_expiry_days', Number(e.target.value))}
                                />
                                {errors.password_expiry_days && (
                                    <p className="text-sm text-destructive">{errors.password_expiry_days}</p>
                                )}
                            </div>

                            <div className="flex items-center justify-between rounded-md border p-3">
                                <div>
                                    <p className="font-medium">Recuperación por correo</p>
                                    <p className="text-sm text-muted-foreground">
                                        Permite a los usuarios restablecer su contraseña con su correo personal.
                                    </p>
                                </div>
                                <Switch
                                    checked={data.password_recovery_enabled as boolean}
                                    onCheckedChange={(v) => setData('password_recovery_enabled', v)}
                                />
                            </div>
                            {errors.password_recovery_enabled && (
                                <p className="text-sm text-destructive">{errors.password_recovery_enabled}</p>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando…' : 'Guardar ajustes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
