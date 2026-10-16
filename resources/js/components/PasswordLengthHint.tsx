import { CircleAlert, CircleCheck, Info } from 'lucide-react';
import { PASSWORD_MIN_LENGTH } from '@/lib/constants';
import { cn } from '@/lib/utils';

/**
 * Live password-length hint. Shows a neutral requirement message while
 * empty, an error while too short, and a success state once valid.
 * Visual feedback only; the server enforces the rule.
 */
export function PasswordLengthHint({
    value,
    minLength = PASSWORD_MIN_LENGTH,
}: {
    value: string;
    minLength?: number;
}) {
    if (value.length === 0) {
        return (
            <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Info className="h-3.5 w-3.5 shrink-0" />
                Debe tener como mínimo {minLength} caracteres.
            </p>
        );
    }

    if (value.length < minLength) {
        return (
            <p className="flex items-center gap-1.5 text-xs font-medium text-destructive">
                <CircleAlert className="h-3.5 w-3.5 shrink-0" />
                Incorrecta, debe tener como mínimo {minLength} caracteres.
            </p>
        );
    }

    return (
        <p className={cn('flex items-center gap-1.5 text-xs font-medium text-success')}>
            <CircleCheck className="h-3.5 w-3.5 shrink-0" />
            Correcto.
        </p>
    );
}

/**
 * Live confirmation-match hint. Neutral while empty, error while it
 * differs from the new password, success once they match.
 */
export function PasswordConfirmHint({
    password,
    confirmation,
}: {
    password: string;
    confirmation: string;
}) {
    if (confirmation.length === 0) {
        return (
            <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Info className="h-3.5 w-3.5 shrink-0" />
                Repite la nueva contraseña.
            </p>
        );
    }

    if (confirmation !== password) {
        return (
            <p className="flex items-center gap-1.5 text-xs font-medium text-destructive">
                <CircleAlert className="h-3.5 w-3.5 shrink-0" />
                Incorrecto, no coincide con la nueva contraseña.
            </p>
        );
    }

    return (
        <p className={cn('flex items-center gap-1.5 text-xs font-medium text-success')}>
            <CircleCheck className="h-3.5 w-3.5 shrink-0" />
            Correcto, coincide.
        </p>
    );
}
