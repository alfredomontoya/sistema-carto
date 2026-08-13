import { Head, useForm } from '@inertiajs/react';
import { Image, Paintbrush, Palette } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout';
import type { BrandData } from '@/types';

export default function SettingsIndex({ brand }: { brand: BrandData }) {
    const { data, setData, put, processing, errors } = useForm({
        app_name: brand.app_name,
        primary_color: brand.primary_color,
        secondary_color: brand.secondary_color,
        logo: null as File | null,
        favicon: null as File | null,
    });

    const [logoFile, setLogoFile] = React.useState<File | null>(null);
    const [faviconFile, setFaviconFile] = React.useState<File | null>(null);

    const logoPreview = logoFile ? URL.createObjectURL(logoFile) : (brand.logo_url ?? null);
    const faviconPreview = faviconFile
        ? URL.createObjectURL(faviconFile)
        : (brand.favicon_url ?? null);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('app_name', data.app_name);
        formData.append('primary_color', data.primary_color);
        formData.append('secondary_color', data.secondary_color);
        if (logoFile) formData.append('logo', logoFile);
        if (faviconFile) formData.append('favicon', faviconFile);

        put(route('admin.settings.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setLogoFile(null);
                setFaviconFile(null);
            },
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Ajustes de marca" />

            <div className="mx-auto max-w-3xl space-y-6">
                <PageHeader
                    title="Ajustes de marca"
                    description="Personaliza los colores, el logo y el favicon del sistema."
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
                                <Palette className="h-4 w-4 text-muted-foreground" /> Colores
                            </CardTitle>
                            <CardDescription>
                                Los cambios se aplican al instante en toda la aplicación.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <ColorField
                                    label="Color principal"
                                    hint="Botones, enlaces y acentos principales."
                                    value={data.primary_color}
                                    onChange={(v) => setData('primary_color', v)}
                                    error={errors.primary_color}
                                />
                                <ColorField
                                    label="Color secundario"
                                    hint="Elementos secundarios y resaltados."
                                    value={data.secondary_color}
                                    onChange={(v) => setData('secondary_color', v)}
                                    error={errors.secondary_color}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Image className="h-4 w-4 text-muted-foreground" /> Logo y favicon
                            </CardTitle>
                            <CardDescription>
                                Sube el logo del sistema y el favicon del navegador.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="space-y-3">
                                    <Label>Logo</Label>
                                    {logoPreview && (
                                        <img
                                            src={logoPreview}
                                            alt="Vista previa del logo"
                                            className="h-16 w-16 rounded-lg border bg-card object-contain"
                                        />
                                    )}
                                    <Input
                                        type="file"
                                        accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                        onChange={(e) => setLogoFile(e.target.files?.[0] ?? null)}
                                    />
                                    {errors.logo && (
                                        <p className="text-sm text-destructive">{errors.logo}</p>
                                    )}
                                </div>
                                <div className="space-y-3">
                                    <Label>Favicon</Label>
                                    <div className="flex items-center gap-3">
                                        {faviconPreview && (
                                            <img
                                                src={faviconPreview}
                                                alt="Favicon"
                                                className="h-8 w-8 rounded border bg-card object-contain"
                                            />
                                        )}
                                        <Input
                                            type="file"
                                            accept="image/png,image/svg+xml,image/x-icon"
                                            onChange={(e) => setFaviconFile(e.target.files?.[0] ?? null)}
                                        />
                                    </div>
                                    {errors.favicon && (
                                        <p className="text-sm text-destructive">{errors.favicon}</p>
                                    )}
                                </div>
                            </div>
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

function ColorField({
    label,
    hint,
    value,
    onChange,
    error,
}: {
    label: string;
    hint: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="space-y-3">
            <Label>{label}</Label>
            <div className="flex items-center gap-3">
                <input
                    type="color"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="h-10 w-14 cursor-pointer rounded-md border border-input bg-transparent p-1"
                />
                <Input value={value} onChange={(e) => onChange(e.target.value)} />
            </div>
            <p className="text-xs text-muted-foreground">{hint}</p>
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
