import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation({
    password_days_left,
    password_expiry_days,
}: {
    password_days_left: number;
    password_expiry_days: number;
}) {
    const user = usePage().props.auth.user!;
    const userDomain = usePage().props.app.user_domain;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        phone: user.phone ?? '',
        address: user.address ?? '',
        recovery_email: user.recovery_email ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label>Nombre completo</Label>
                    <p className="text-sm font-medium text-foreground">{user.name}</p>
                </div>
                <div className="space-y-2">
                    <Label>Usuario</Label>
                    <p className="text-sm font-medium text-foreground">
                        {user.username}@{userDomain}
                    </p>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label>Puesto actual</Label>
                    <p className="text-sm font-medium text-foreground">
                        {user.current_position?.name ?? 'Sin puesto asignado'}
                    </p>
                </div>
                <div className="space-y-2">
                    <Label>Área actual</Label>
                    <p className="text-sm font-medium text-foreground">
                        {user.current_area?.name ?? '—'}
                    </p>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label>Vigencia de contraseña</Label>
                    <p className="text-sm font-medium text-foreground">
                        {password_days_left <= 0 ? (
                            <span className="text-destructive">Vencida: actualízala cuanto antes.</span>
                        ) : (
                            <>Te quedan {password_days_left} {password_days_left === 1 ? 'día' : 'días'} de {password_expiry_days}.</>
                        )}
                    </p>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="recovery-email">Correo para recuperación</Label>
                    <Input
                        id="recovery-email"
                        type="email"
                        value={data.recovery_email}
                        onChange={(e) => setData('recovery_email', e.target.value)}
                        placeholder="tucorreo@ejemplo.com"
                    />
                    <p className="text-xs text-muted-foreground">
                        {user.recovery_email_verified
                            ? 'Verificado: sirve para recuperar tu contraseña.'
                            : 'Te enviaremos un enlace para verificarlo.'}
                    </p>
                    {errors.recovery_email && (
                        <p className="text-sm text-destructive">{errors.recovery_email}</p>
                    )}
                </div>
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
