import * as React from 'react';
import { CommunicationEditForm } from '@/components/communications/CommunicationEditForm';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CommunicationData } from '@/types';

export function CommunicationEditDialog({
    communication,
    open,
    onOpenChange,
}: {
    communication: CommunicationData | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Editar comunicación</DialogTitle>
                    <DialogDescription>
                        Solo se pueden modificar los datos del documento. El número
                        correlativo y el tipo no se pueden cambiar.
                    </DialogDescription>
                </DialogHeader>
                {communication && (
                    <CommunicationEditForm
                        key={communication.id}
                        communication={communication}
                        onSuccess={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
