import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { UserForm } from '@/components/admin/UserForm';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import type { AreaNode, RoleData } from '@/types';

export default function Create({ roles, areas }: { roles: RoleData[]; areas: AreaNode[] }) {
    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Nuevo usuario" />

            <div className="mx-auto max-w-3xl space-y-6">
                <PageHeader
                    title="Nuevo usuario"
                    description="Registra un nuevo usuario y asigna rol y área."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/users">
                                <ArrowLeft /> Volver
                            </Link>
                        </Button>
                    }
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del usuario</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <UserForm roles={roles} areas={areas} mode="create" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
