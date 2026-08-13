import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, History, KeyRound, Trash2 } from 'lucide-react';
import * as React from 'react';
import { UserForm } from '@/components/admin/UserForm';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';
import type { AreaNode, PositionHistoryEntry, RoleData, UserData } from '@/types';

export default function Edit({
    user,
    roles,
    areas,
    position_history,
}: {
    user: UserData;
    roles: RoleData[];
    areas: AreaNode[];
    position_history: PositionHistoryEntry[];
}) {
    const userDomain = usePage().props.app.user_domain;

    const [resetOpen, setResetOpen] = React.useState(false);
    const [deleteOpen, setDeleteOpen] = React.useState(false);

    const resetForm = useForm({ password: '', password_confirmation: '' });
    const deleteForm = useForm({});

    const resetPassword = (e: React.FormEvent) => {
        e.preventDefault();
        resetForm.post(route('admin.users.reset-password', user.id), {
            onSuccess: () => {
                resetForm.reset();
                setResetOpen(false);
            },
        });
    };

    const deleteUser = () => {
        deleteForm.delete(route('admin.users.destroy', user.id), {
            onSuccess: () => setDeleteOpen(false),
        });
    };

    return (
        <AppLayout>
            <FlashMessages />
            <Head title={`Editar: ${user.name}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                <PageHeader
                    title={user.name}
                    description={`${user.username}@${userDomain}`}
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href="/admin/users">
                                    <ArrowLeft /> Volver
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={() => setDeleteOpen(true)}
                            >
                                <Trash2 /> Eliminar
                            </Button>
                        </>
                    }
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Editar usuario</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <UserForm user={user} roles={roles} areas={areas} mode="edit" />
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <History className="h-4 w-4 text-muted-foreground" />
                                Historial de puestos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Puesto</TableHead>
                                        <TableHead>Área</TableHead>
                                        <TableHead>Desde</TableHead>
                                        <TableHead>Hasta</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {position_history.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={4} className="h-16 text-center text-muted-foreground">
                                                Sin asignaciones previas.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {position_history.map((h) => (
                                        <TableRow key={h.id}>
                                            <TableCell className="font-medium">{h.position_name}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{h.area_name}</TableCell>
                                            <TableCell className="text-sm">
                                                {h.started_at ? new Date(h.started_at).toLocaleDateString() : '—'}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {h.ended_at
                                                    ? new Date(h.ended_at).toLocaleDateString()
                                                    : <Badge variant="success">Actual</Badge>}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <KeyRound className="h-4 w-4 text-muted-foreground" />
                                Restablecer contraseña
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="text-sm text-muted-foreground">
                                Genera una nueva contraseña temporal. El usuario deberá volver a
                                iniciar sesión.
                            </p>
                            <Dialog open={resetOpen} onOpenChange={setResetOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="secondary">
                                        <KeyRound /> Nueva contraseña
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Restablecer contraseña</DialogTitle>
                                        <DialogDescription>
                                            Establece la nueva contraseña para {user.name}.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form onSubmit={resetPassword} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="reset-pass">Nueva contraseña</Label>
                                            <PasswordInput
                                                id="reset-pass"
                                                value={resetForm.data.password}
                                                onChange={(e) => resetForm.setData('password', e.target.value)}
                                            />
                                            {resetForm.errors.password && (
                                                <p className="text-sm text-destructive">{resetForm.errors.password}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="reset-pass-confirm">Confirmar contraseña</Label>
                                            <PasswordInput
                                                id="reset-pass-confirm"
                                                value={resetForm.data.password_confirmation}
                                                onChange={(e) =>
                                                    resetForm.setData('password_confirmation', e.target.value)
                                                }
                                            />
                                        </div>
                                        <DialogFooter>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => setResetOpen(false)}
                                            >
                                                Cancelar
                                            </Button>
                                            <Button type="submit" disabled={resetForm.processing}>
                                                {resetForm.processing ? 'Guardando…' : 'Guardar contraseña'}
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </CardContent>
                    </Card>
                </div>

                <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Eliminar usuario</DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de eliminar a <strong>{user.name}</strong>? Esta acción
                                no se puede deshacer.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDeleteOpen(false)}>
                                Cancelar
                            </Button>
                            <Button variant="destructive" onClick={deleteUser} disabled={deleteForm.processing}>
                                {deleteForm.processing ? 'Eliminando…' : 'Eliminar usuario'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
