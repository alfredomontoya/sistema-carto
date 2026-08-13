import GuestLayout from '@/layouts/GuestLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { FormEventHandler } from 'react';

export default function Login({ status }: { status?: string }) {
    const userDomain = usePage().props.app.user_domain;

    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Iniciar sesión</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Ingresa tus credenciales para acceder al sistema.
                </p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">{status}</div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="username">Usuario</Label>
                    <Input
                        id="username"
                        type="text"
                        name="username"
                        value={data.username}
                        autoComplete="username"
                        autoFocus
                        onChange={(e) => setData('username', e.target.value)}
                        placeholder="amontoya"
                    />
                    <p className="text-xs text-muted-foreground">
                        Tu usuario de @{userDomain}
                    </p>
                    {errors.username && (
                        <p className="text-sm text-destructive">{errors.username}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password">Contraseña</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    {errors.password && (
                        <p className="text-sm text-destructive">{errors.password}</p>
                    )}
                </div>

                <label className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Checkbox
                        name="remember"
                        checked={data.remember}
                        onCheckedChange={(checked) => setData('remember', checked === true)}
                    />
                    Recordarme
                </label>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? 'Ingresando…' : 'Ingresar'}
                </Button>
            </form>
        </GuestLayout>
    );
}
