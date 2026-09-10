import GuestLayout from '@/layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { FormEventHandler } from 'react';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('recovery.update'));
    };

    return (
        <GuestLayout>
            <Head title="Restablecer contraseña" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Restablecer contraseña</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Elige una contraseña nueva para {email || 'tu cuenta'}. Con ella podrás
                    iniciar sesión directamente.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="password">Nueva contraseña</Label>
                    <PasswordInput
                        id="password"
                        value={data.password}
                        autoComplete="new-password"
                        autoFocus
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    {errors.password && (
                        <p className="text-sm text-destructive">{errors.password}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                    <PasswordInput
                        id="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                </div>

                {errors.email && (
                    <p className="text-sm text-destructive">{errors.email}</p>
                )}

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? 'Guardando…' : 'Restablecer contraseña'}
                </Button>
            </form>
        </GuestLayout>
    );
}
