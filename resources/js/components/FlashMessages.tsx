import * as React from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import type { FlashData } from '@/types';

export function FlashMessages() {
    const { flash, errors } = usePage().props as unknown as {
        flash: FlashData;
        errors: Record<string, string>;
    };
    const errorKey = JSON.stringify(errors ?? {});

    React.useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    React.useEffect(() => {
        if (Object.keys(errors ?? {}).length > 0) {
            toast.error('Hay errores de validación', {
                description: 'Revisa los campos marcados en el formulario.',
            });
        }
    }, [errorKey]);

    return null;
}
