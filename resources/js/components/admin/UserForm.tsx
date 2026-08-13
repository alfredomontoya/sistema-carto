import { useForm } from '@inertiajs/react';
import * as React from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { positionOptions } from '@/lib/area-tree';
import type { AreaNode, RoleData, UserData } from '@/types';

export function UserForm({
    user,
    roles,
    areas,
    mode,
}: {
    user?: UserData;
    roles: RoleData[];
    areas: AreaNode[];
    mode: 'create' | 'edit';
}) {
    const isEdit = mode === 'edit';

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        address: user?.address ?? '',
        password: '',
        password_confirmation: '',
        is_active: user?.is_active ?? true,
        role_ids: user?.role_ids ?? [],
        position_id: user?.current_position?.id ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && user) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    const toggleRole = (id: number) => {
        const current = data.role_ids as number[];
        setData(
            'role_ids',
            current.includes(id) ? current.filter((r) => r !== id) : [...current, id],
        );
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="name">Nombre completo</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="email">Correo electrónico</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="phone">Teléfono</Label>
                    <Input
                        id="phone"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                    />
                    {errors.phone && <p className="text-sm text-destructive">{errors.phone}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="address">Dirección</Label>
                    <Input
                        id="address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                    />
                    {errors.address && <p className="text-sm text-destructive">{errors.address}</p>}
                </div>

                {!isEdit && (
                    <>
                        <div className="space-y-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">{errors.password}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                            />
                        </div>
                    </>
                )}
            </div>

            <div className="space-y-3">
                <Label>Roles</Label>
                <div className="flex flex-wrap gap-4">
                    {roles.map((role) => (
                        <label
                            key={role.id}
                            className="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                checked={(data.role_ids as number[]).includes(role.id)}
                                onCheckedChange={() => toggleRole(role.id)}
                            />
                            <span className="capitalize">{role.name}</span>
                        </label>
                    ))}
                </div>
            </div>

            <div className="space-y-2">
                <Label>Puesto</Label>
                <Select
                    value={data.position_id as string}
                    onValueChange={(v) => setData('position_id', v === '__none' ? '' : v)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un puesto" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__none">Sin puesto</SelectItem>
                        {positionOptions(areas).map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.position_id && (
                    <p className="text-sm text-destructive">{errors.position_id}</p>
                )}
            </div>

            {isEdit && (
                <div className="flex items-center justify-between rounded-md border p-4">
                    <div>
                        <p className="font-medium">Cuenta activa</p>
                        <p className="text-sm text-muted-foreground">
                            Desactiva para impedir el acceso al sistema.
                        </p>
                    </div>
                    <Switch
                        checked={data.is_active as boolean}
                        onCheckedChange={(v) => setData('is_active', v)}
                    />
                </div>
            )}

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Crear usuario'}
                </Button>
            </div>
        </form>
    );
}
