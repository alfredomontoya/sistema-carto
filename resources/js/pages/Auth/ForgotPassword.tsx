import GuestLayout from '@/layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('recovery.send'));
    };

    return (
        <GuestLayout>
            <Head title="Recuperar contraseña" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Recuperar contraseña</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Escribe tu correo personal registrado y te enviaremos un enlace para
                    restablecer tu contraseña.
                </p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">{status}</div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="email">Correo personal</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        autoComplete="email"
                        autoFocus
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="tucorreo@ejemplo.com"
                    />
                    {errors.email && (
                        <p className="text-sm text-destructive">{errors.email}</p>
                    )}
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? 'Enviando…' : 'Enviar enlace'}
                </Button>

                <p className="text-center text-sm text-muted-foreground">
                    <Link href="/login" className="underline hover:text-foreground">
                        Volver al inicio de sesión
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
