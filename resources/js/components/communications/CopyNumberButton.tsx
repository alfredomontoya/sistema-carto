import { Check, Copy } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

/**
 * Icon button that copies the correlative number to the clipboard and shows a
 * confirmation (icon change + toast).
 */
export function CopyNumberButton({ value }: { value: string }) {
    const [copied, setCopied] = React.useState(false);

    const copy = async (e: React.MouseEvent) => {
        e.stopPropagation();

        try {
            await navigator.clipboard.writeText(value);
        } catch {
            const textarea = document.createElement('textarea');
            textarea.value = value;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        setCopied(true);
        toast.success(`Número copiado: ${value}`);

        window.setTimeout(() => setCopied(false), 1500);
    };

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-7 w-7 shrink-0"
            onClick={copy}
            title={copied ? '¡Copiado!' : 'Copiar número'}
        >
            {copied ? (
                <Check className="h-4 w-4 text-emerald-500" />
            ) : (
                <Copy className="h-4 w-4" />
            )}
        </Button>
    );
}