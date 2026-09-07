import * as React from 'react';
import { CommunicationCreateForm } from '@/components/communications/CommunicationCreateForm';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
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
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
