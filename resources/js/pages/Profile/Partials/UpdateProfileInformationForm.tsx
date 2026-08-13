import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation() {
    const user = usePage().props.auth.user!;
    const userDomain = usePage().props.app.user_domain;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: user.name,
        username: user.username,
        phone: user.phone ?? '',
        address: user.address ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="name">Nombre completo</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    autoComplete="name"
                />
                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="username">Usuario</Label>
                <Input
                    id="username"
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value)}
                    autoComplete="username"
                />
                <p className="text-xs text-muted-foreground">
                    Tu usuario de acceso. El correo será {data.username || 'usuario'}@{userDomain}
                </p>
                {errors.username && (
                    <p className="text-sm text-destructive">{errors.username}</p>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="phone">Teléfono</Label>
                    <Input
                        id="phone"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        placeholder="999 999 999"
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
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando…' : 'Guardar cambios'}
                </Button>
                {recentlySuccessful && (
                    <span className="text-sm text-success">Guardado correctamente.</span>
                )}
            </div>
        </form>
    );
}
