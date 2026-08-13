import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Search, UserPlus } from 'lucide-react';
import * as React from 'react';
import { useEffect } from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { UserAvatar } from '@/components/UserAvatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DataTablePagination } from '@/components/DataTablePagination';
import AppLayout from '@/layouts/AppLayout';
import { treeToOptions } from '@/lib/area-tree';
import type { AreaNode, PaginationData, RoleData, UserData } from '@/types';

export default function UsersIndex({
    users,
    filters,
    roles,
    areas,
    pagination,
}: {
    users: UserData[];
    filters: { search?: string; role?: string; area_id?: string };
    roles: RoleData[];
    areas: AreaNode[];
    pagination: PaginationData;
}) {
    const user = usePage().props.auth.user!;
    const [search, setSearch] = React.useState(filters.search ?? '');
    const [role, setRole] = React.useState(filters.role ?? '');
    const [areaId, setAreaId] = React.useState(filters.area_id ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                '/admin/users',
                { search, role, area_id: areaId },
                { preserveState: true, replace: true },
            );
        }, 350);
        return () => clearTimeout(timeout);
    }, [search, role, areaId]);

    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Usuarios" />

            <div className="space-y-6">
                <PageHeader
                    title="Usuarios"
                    description="Crea, edita y administra los usuarios del sistema."
                    actions={
                        <Button asChild>
                            <Link href="/admin/users/create">
                                <UserPlus /> Nuevo usuario
                            </Link>
                        </Button>
                    }
                />

                <Card>
                    <div className="border-b p-4">
                        <div className="grid gap-3 md:grid-cols-4">
                            <div className="relative md:col-span-2">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    placeholder="Buscar por nombre o correo…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <Select
                                value={role}
                                onValueChange={(v) => setRole(v === '__all' ? '' : v)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Filtrar por rol" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all">Todos los roles</SelectItem>
                                    {roles.map((r) => (
                                        <SelectItem key={r.id} value={r.name}>
                                            {r.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={areaId}
                                onValueChange={(v) => setAreaId(v === '__all' ? '' : v)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Filtrar por área" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all">Todas las áreas</SelectItem>
                                    {treeToOptions(areas).map((o) => (
                                        <SelectItem key={o.value} value={o.value}>
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead>Puesto / Área</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                            No se encontraron usuarios.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {users.map((u) => (
                                    <TableRow key={u.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <UserAvatar user={u} />
                                                <div>
                                                    <p className="font-medium text-foreground">{u.name}</p>
                                                    <p className="text-xs text-muted-foreground">{u.email}</p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {u.roles.length === 0 && (
                                                    <span className="text-xs text-muted-foreground">—</span>
                                                )}
                                                {u.roles.map((r) => (
                                                    <Badge key={r} variant="secondary" className="capitalize">
                                                        {r}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {u.current_position ? (
                                                <div className="text-sm">
                                                    <span className="font-medium text-foreground">
                                                        {u.current_position.name}
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        {u.current_area ? ` · ${u.current_area.name}` : ''}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={u.is_active ? 'success' : 'destructive'}>
                                                {u.is_active ? 'Activo' : 'Inactivo'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/users/${u.id}/edit`}>
                                                    <Pencil /> Editar
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <DataTablePagination pagination={pagination} filters={{ search, role, area_id: areaId }} />
                </Card>
            </div>
        </AppLayout>
    );
}
