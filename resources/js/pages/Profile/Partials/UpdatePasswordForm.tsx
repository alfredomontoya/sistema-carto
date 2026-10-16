import { useForm } from '@inertiajs/react';
import { CircleCheck, X } from 'lucide-react';
import * as React from 'react';
import { PasswordConfirmHint, PasswordLengthHint } from '@/components/PasswordLengthHint';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { FormEventHandler } from 'react';

export default function UpdatePasswordForm() {
    const { data, setData, put, errors, processing, reset, clearErrors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [showAlert, setShowAlert] = React.useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('profile.password'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                reset();
                setShowAlert(true);
            },
        });
    };

    return (
        <form onSubmit={submit} className="max-w-md space-y-4">
            {showAlert && (
                <div
                    role="alert"
                    className="flex items-start gap-2 rounded-md border border-success/50 bg-success/10 p-3"
                >
                    <CircleCheck className="mt-0.5 h-4 w-4 shrink-0 text-success" />
                    <p className="flex-1 text-sm font-medium text-success">
                        Contraseña actualizada correctamente.
                    </p>
                    <button
                        type="button"
                        onClick={() => setShowAlert(false)}
                        aria-label="Cerrar aviso"
                        className="rounded text-success hover:opacity-70"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
            )}

            <div className="space-y-2">
                <Label htmlFor="current_password">Contraseña actual</Label>
                <PasswordInput
                    id="current_password"
                    value={data.current_password}
                    onChange={(e) => {
                        setData('current_password', e.target.value);
                        clearErrors('current_password');
                    }}
                    autoComplete="current-password"
                />
                {errors.current_password && (
                    <p className="text-sm text-destructive">{errors.current_password}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password">Nueva contraseña</Label>
                <PasswordInput
                    id="password"
                    value={data.password}
                    onChange={(e) => {
                        setData('password', e.target.value);
                        clearErrors('password');
                    }}
                    autoComplete="new-password"
                />
                <PasswordLengthHint value={data.password} />
                {errors.password && <p className="text-sm text-destructive">{errors.password}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    value={data.password_confirmation}
                    onChange={(e) => {
                        setData('password_confirmation', e.target.value);
                        clearErrors('password_confirmation');
                    }}
                    autoComplete="new-password"
                />
                <PasswordConfirmHint
                    password={data.password}
                    confirmation={data.password_confirmation}
                />
                {errors.password_confirmation && (
                    <p className="text-sm text-destructive">{errors.password_confirmation}</p>
                )}
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando…' : 'Guardar contraseña'}
                </Button>
            </div>
        </form>
    );
}
