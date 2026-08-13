import { Head, usePage } from '@inertiajs/react';
import { FlashMessages } from '@/components/FlashMessages';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout';
import UpdateAvatarForm from '@/pages/Profile/Partials/UpdateAvatarForm';
import UpdatePasswordForm from '@/pages/Profile/Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from '@/pages/Profile/Partials/UpdateProfileInformationForm';
import type { AvatarGalleryEntry } from '@/components/AvatarPicker';

export default function Edit({ avatar_gallery }: { avatar_gallery: AvatarGalleryEntry }) {
    const user = usePage().props.auth.user!;

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

                <Tabs defaultValue="info">
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
                                    Actualiza tu nombre, usuario, teléfono y dirección.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <UpdateProfileInformationForm />
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
