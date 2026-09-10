import { Head, usePage } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout';
import UpdateAvatarForm from '@/pages/Profile/Partials/UpdateAvatarForm';
import UpdatePasswordForm from '@/pages/Profile/Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from '@/pages/Profile/Partials/UpdateProfileInformationForm';
import type { AvatarGalleryEntry } from '@/components/AvatarPicker';

const VALID_TABS = ['info', 'avatar', 'password'] as const;

function initialTab(): string {
    const tab = new URLSearchParams(window.location.search).get('tab');
    return (VALID_TABS as readonly string[]).includes(tab ?? '') ? tab as string : 'info';
}

export default function Edit({
    avatar_gallery,
    password_days_left,
    password_expiry_days,
}: {
    avatar_gallery: AvatarGalleryEntry;
    password_days_left: number;
    password_expiry_days: number;
}) {
    const user = usePage().props.auth.user!;
    const [tab, setTab] = React.useState(initialTab);

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Mi perfil" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Mi perfil</h1>
                    <p className="text-muted-foreground">
                        Administra tus datos personales, contraseña y avatar.
                    </p>
                </div>

                {user.must_change_password && (
                    <Card className="border-warning/50 bg-warning/10">
                        <CardContent className="flex items-center gap-3 p-4">
                            <TriangleAlert className="h-5 w-5 shrink-0 text-warning" />
                            <p className="text-sm font-medium">
                                Debes actualizar tu contraseña para continuar usando el sistema.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="info">Información</TabsTrigger>
                        <TabsTrigger value="avatar">Avatar</TabsTrigger>
                        <TabsTrigger value="password">Contraseña</TabsTrigger>
                    </TabsList>

                    <TabsContent value="info">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información personal</CardTitle>
                            <CardDescription>
                                Actualiza tu teléfono y dirección.
                            </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <UpdateProfileInformationForm
                                    password_days_left={password_days_left}
                                    password_expiry_days={password_expiry_days}
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="avatar">
                        <Card>
                            <CardHeader>
                                <CardTitle>Avatar</CardTitle>
                                <CardDescription>
                                    Elige un avatar de la galería o sube una imagen propia.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <UpdateAvatarForm gallery={avatar_gallery} userName={user.name} />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="password">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cambiar contraseña</CardTitle>
                                <CardDescription>
                                    Usa una contraseña segura que no utilices en otros servicios.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <UpdatePasswordForm />
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
