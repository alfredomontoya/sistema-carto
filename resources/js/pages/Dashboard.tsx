import { Link } from '@inertiajs/react';
import { FileText, Layers, Plus, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { UserAvatar } from '@/components/UserAvatar';
import { FlashMessages } from '@/components/FlashMessages';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import type { UserData } from '@/types';

export default function Dashboard() {
    const user = usePage().props.auth.user as UserData;

    const quickActions = [
        {
            label: 'Nueva comunicación',
            href: '/comunicaciones/crear',
            icon: Plus,
            description: 'Generar correlativo interno o externo',
        },
        ...(user.can.manage_users
            ? [
                  {
                      label: 'Gestionar usuarios',
                      href: '/admin/users',
                      icon: Users,
                      description: 'Crear usuarios y asignar roles/áreas',
                  },
              ]
            : []),
        ...(user.can.manage_areas
            ? [
                  {
                      label: 'Administrar áreas',
                      href: '/admin/areas',
                      icon: Layers,
                      description: 'Organizar el árbol de áreas',
                  },
              ]
            : []),
    ];

    return (
        <AppLayout>
            <FlashMessages />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Hola, {user.name.split(' ')[0]}
                    </h1>
                    <p className="text-muted-foreground">
                        Panel principal del sistema de comunicaciones internas y oficios externos.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {quickActions.map((action) => (
                        <Link
                            key={action.href}
                            href={action.href}
                            className="group"
                        >
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="flex h-10 w-10 items-center justify-center rounded-lg text-primary-foreground"
                                            style={{
                                                background:
                                                    'linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))',
                                            }}
                                        >
                                            <action.icon className="h-5 w-5" />
                                        </div>
                                        <CardTitle className="text-base">
                                            {action.label}
                                        </CardTitle>
                                    </div>
                                    <CardDescription>{action.description}</CardDescription>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <FileText className="h-4 w-4 text-muted-foreground" /> Tu perfil
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                            <UserAvatar user={user} className="h-16 w-16" />
                            <div className="space-y-1 text-center sm:text-left">
                                <p className="font-medium text-foreground">{user.name}</p>
                                <p className="text-sm text-muted-foreground">{user.email}</p>
                                {user.current_position && (
                                    <p className="text-sm text-muted-foreground">
                                        Puesto:{' '}
                                        <span className="font-medium text-foreground">
                                            {user.current_position.name}
                                        </span>
                                        {user.current_area && (
                                            <>
                                                {' '}· Área:{' '}
                                                <span className="font-medium text-foreground">
                                                    {user.current_area.name}
                                                </span>
                                            </>
                                        )}
                                    </p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    Roles:{' '}
                                    <span className="font-medium capitalize text-foreground">
                                        {user.roles.join(', ') || 'usuario'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
