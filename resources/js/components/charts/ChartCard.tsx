import * as React from 'react';
import { Check, Copy, Download, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTheme } from '@/components/brand/ThemeProvider';
import { cn } from '@/lib/utils';
import {
    CHART_IMAGE_ACTIONS_CLASS,
    captureChartCard,
    copyPngToClipboard,
    downloadChartCard,
    hasCapturableContent,
} from '@/lib/chart-image';

export type ChartRef = React.RefObject<HTMLDivElement | null>;

export function ChartImageButtons({
    targetRef,
    fileName,
}: {
    targetRef: ChartRef;
    fileName: string;
}) {
    const { theme } = useTheme();
    const [busy, setBusy] = React.useState<'copy' | 'download' | null>(null);
    const [copied, setCopied] = React.useState(false);
    const dark = theme === 'dark';

    const resolve = () => {
        const card = targetRef.current;
        if (!card || !hasCapturableContent(card)) {
            toast.error('No hay contenido para capturar', {
                description: 'El contenido aún no se ha dibujado.',
            });
            throw new Error('no-content');
        }
        return card;
    };

    const handleCopy = async () => {
        if (busy) return;
        setBusy('copy');
        try {
            await copyPngToClipboard(await captureChartCard(resolve(), { dark }));
            setCopied(true);
            toast.success('Imagen copiada', { description: 'Pégala donde la necesites.' });
            window.setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            if (error instanceof Error && error.message !== 'no-content') {
                toast.error('No se pudo copiar', {
                    description: 'Tu navegador no permite copiar imágenes. Usa Descargar.',
                });
            }
        } finally {
            setBusy(null);
        }
    };

    const handleDownload = async () => {
        if (busy) return;
        setBusy('download');
        try {
            await downloadChartCard(resolve(), { dark, fileName });
            toast.success('Imagen descargada');
        } catch (error) {
            if (error instanceof Error && error.message !== 'no-content') {
                toast.error('No se pudo generar la imagen');
            }
        } finally {
            setBusy(null);
        }
    };

    return (
        <div className={cn('flex items-center gap-1', CHART_IMAGE_ACTIONS_CLASS)}>
            <Button
                variant="ghost"
                size="icon"
                onClick={handleCopy}
                disabled={busy !== null}
                aria-label="Copiar como imagen"
                title="Copiar como imagen"
                className="h-8 w-8 text-muted-foreground"
            >
                {busy === 'copy' ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : copied ? (
                    <Check className="h-4 w-4 text-green-600" />
                ) : (
                    <Copy className="h-4 w-4" />
                )}
            </Button>
            <Button
                variant="ghost"
                size="icon"
                onClick={handleDownload}
                disabled={busy !== null}
                aria-label="Descargar como PNG"
                title="Descargar como PNG"
                className="h-8 w-8 text-muted-foreground"
            >
                {busy === 'download' ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <Download className="h-4 w-4" />
                )}
            </Button>
        </div>
    );
}

export function ChartCard({
    icon,
    title,
    description,
    fileName,
    children,
}: {
    icon?: React.ReactNode;
    title: string;
    description?: string;
    fileName: string;
    children: React.ReactNode;
}) {
    const ref = React.useRef<HTMLDivElement | null>(null);

    return (
        <Card ref={ref}>
            <CardHeader>
                <div className="flex items-start justify-between gap-2">
                    <div className="space-y-1.5">
                        <CardTitle className="flex items-center gap-2 text-base">
                            {icon}
                            {title}
                        </CardTitle>
                        {description ? <CardDescription>{description}</CardDescription> : null}
                    </div>
                    <ChartImageButtons targetRef={ref} fileName={fileName} />
                </div>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
