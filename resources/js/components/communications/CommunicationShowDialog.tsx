import { Download } from 'lucide-react';
import * as React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTime } from '@/lib/dates';
import type { CommunicationData } from '@/types';
import { CopyNumberButton } from './CopyNumberButton';

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <div className="text-sm">{children}</div>
        </div>
    );
}

/**
 * Read-only detail dialog for a communication. Exposes the correlative number
 * with a copy button.
 */
export function CommunicationShowDialog({
    communication,
    open,
    onOpenChange,
}: {
    communication: CommunicationData | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const c = communication;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex flex-wrap items-center gap-2">
                        {c !== null && (
                            <>
                                <Badge variant={c.type === 'ci' ? 'secondary' : 'outline'}>
                                    {c.type === 'ci' ? 'Comunicación interna' : 'Oficio externo'}
                                </Badge>
                                <Badge variant={c.status === 'activo' ? 'success' : 'warning'}>
                                    {c.status === 'activo' ? 'Activo' : 'Anulado'}
                                </Badge>
                            </>
                        )}
                    </DialogTitle>
                    <DialogDescription>Detalle del registro correlativo {c?.number ?? ''}.</DialogDescription>
                </DialogHeader>

                {c !== null && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between gap-3 rounded-md border p-3">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Número correlativo</p>
                                <p className="font-mono text-lg font-semibold text-foreground">{c.number}</p>
                            </div>
                            <CopyNumberButton value={c.number} />
                        </div>

                        <Row label="Referencia">{c.reference || '—'}</Row>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Row label="Remitente">
                                <p className="font-medium">{c.user?.name ?? '—'}</p>
                                <p className="text-muted-foreground">
                                    {c.position?.name ?? '—'}
                                    {c.user?.current_area ? ` · ${c.user.current_area.name}` : ''}
                                </p>
                            </Row>
                            <Row label="Destinatario">
                                <p className="font-medium">{c.recipient_name || '—'}</p>
                                {c.recipient_position && (
                                    <p className="text-muted-foreground">{c.recipient_position}</p>
                                )}
                                {c.recipient_user?.name && (
                                    <p className="text-muted-foreground">Usuario: {c.recipient_user.name}</p>
                                )}
                            </Row>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Row label="Área">{c.area?.name ?? '—'}</Row>
                            <Row label="Fecha y hora">{formatDateTime(c.created_at)}</Row>
                        </div>

                        {c.file_name && (
                            <Row label="Documento adjunto">
                                <Button variant="outline" size="sm" asChild>
                                    <a href={c.download_url ?? '#'} target="_blank" rel="noreferrer">
                                        <Download className="h-4 w-4" /> {c.file_name}
                                    </a>
                                </Button>
                            </Row>
                        )}
                    </div>
                )}

                <DialogFooter>
                    <Button onClick={() => onOpenChange(false)}>Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}