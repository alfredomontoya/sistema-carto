import * as React from 'react';
import { CommunicationCreateForm } from '@/components/communications/CommunicationCreateForm';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CountersData } from '@/types';

export function CommunicationCreateDialog({
    open,
    onOpenChange,
    counters,
    current_area,
    year,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    counters: CountersData;
    current_area: string | null;
    year: number;
}) {
    const [dirty, setDirty] = React.useState(false);
    const [confirmOpen, setConfirmOpen] = React.useState(false);

    React.useEffect(() => {
        if (open) {
            setDirty(false);
            setConfirmOpen(false);
        }
    }, [open ]);

    const requestClose = () => {
        if (dirty) {
            setConfirmOpen(true);
        } else {
            onOpenChange(false);
        }
    };

    const discard = () => {
        setConfirmOpen(false);
        onOpenChange(false);
    };

    return (
        <>
            <Dialog open={open} onOpenChange={(o) => { if (!o) requestClose(); }}>
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Nueva comunicación</DialogTitle>
                        <DialogDescription>
                            Genera un correlativo interno (ci) u oficio externo (of) para el año {year}.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-6">
                        <CommunicationCreateForm
                            counters={counters}
                            current_area={current_area}
                            year={year}
                            onSuccess={() => onOpenChange(false)}
                            onDirtyChange={setDirty}
                        />
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>¿Descartar cambios?</DialogTitle>
                        <DialogDescription>
                            Tienes datos sin guardar en la nueva comunicación. Si la cierras ahora, se perderán.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmOpen(false)}>
                            Seguir editando
                        </Button>
                        <Button variant="destructive" onClick={discard}>
                            Descartar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
