import * as React from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import type { FlashData } from '@/types';

export function FlashMessages() {
    const flash = usePage().props.flash as FlashData;

    React.useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return null;
}
