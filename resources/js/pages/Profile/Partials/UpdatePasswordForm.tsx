import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormEventHandler } from 'react';

export default function UpdatePasswordForm() {
    const { data, setData, put, errors, processing, recentlySuccessful, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('profile.password'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="max-w-md space-y-4">
            <div className="space-y-2">
                <Label htmlFor="current_password">Contraseña actual</Label>
                <Input
                    id="current_password"
                    type="password"
                    value={data.current_password}
                    onChange={(e) => setData('current_password', e.target.value)}
                    autoComplete="current-password"
                />
                {errors.current_password && (
                    <p className="text-sm text-destructive">{errors.current_password}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password">Nueva contraseña</Label>
                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="new-password"
                />
                {errors.password && <p className="text-sm text-destructive">{errors.password}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    autoComplete="new-password"
                />
                {errors.password_confirmation && (
                    <p className="text-sm text-destructive">{errors.password_confirmation}</p>
                )}
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando…' : 'Guardar contraseña'}
                </Button>
                {recentlySuccessful && (
                    <span className="text-sm text-success">Contraseña actualizada.</span>
                )}
            </div>
        </form>
    );
}
