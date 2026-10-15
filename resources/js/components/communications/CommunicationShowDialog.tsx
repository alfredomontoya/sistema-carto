import { Bold, CaseLower, CaseUpper, Download, Pencil } from 'lucide-react';
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
import { cn } from '@/lib/utils';
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
    onEdit,
}: {
    communication: CommunicationData | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onEdit?: () => void;
}) {
    const c = communication;
    const [upper, setUpper] = React.useState(true);
    const [bold, setBold] = React.useState(true);

    React.useEffect(() => {
        if (open) {
            setUpper(true);
            setBold(true);
        }
    }, [open, communication?.id]);

    const formattedNumber = c === null ? '' : upper ? c.number.toUpperCase() : c.number.toLowerCase();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex flex-wrap items-center gap-2">
                        {c !== null && (
                            <>
                                <Badge variant="secondary">
                                    {c.communication_type?.name ?? c.type ?? '—'}
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
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-muted-foreground">Número correlativo</p>
                                <p
                                    className={cn(
                                        'font-mono text-lg break-all text-foreground',
                                        bold ? 'font-bold' : 'font-normal',
                                    )}
                                >
                                    {formattedNumber}
                                </p>
                                <div className="mt-2 flex gap-1.5">
                                    <Button
                                        type="button"
                                        variant={upper ? 'default' : 'outline'}
                                        size="icon"
                                        className="h-7 w-7"
                                        onClick={() => setUpper((v) => !v)}
                                        title={upper ? 'Cambiar a minúsculas' : 'Cambiar a mayúsculas'}
                                        aria-pressed={upper}
                                        aria-label={upper ? 'Cambiar a minúsculas' : 'Cambiar a mayúsculas'}
                                    >
                                        {upper ? (
                                            <CaseUpper className="h-4 w-4" />
                                        ) : (
                                            <CaseLower className="h-4 w-4" />
                                        )}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={bold ? 'default' : 'outline'}
                                        size="icon"
                                        className="h-7 w-7"
                                        onClick={() => setBold((v) => !v)}
                                        title={bold ? 'Quitar negrita' : 'Poner negrita'}
                                        aria-pressed={bold}
                                        aria-label={bold ? 'Quitar negrita' : 'Poner negrita'}
                                    >
                                        <Bold className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <CopyNumberButton value={formattedNumber} bold={bold} />
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
                    {c?.can_edit && onEdit && (
                        <Button variant="outline" onClick={onEdit}>
                            <Pencil className="h-4 w-4" /> Editar
                        </Button>
                    )}
                    <Button onClick={() => onOpenChange(false)}>Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}