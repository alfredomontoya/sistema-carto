import { Check, Copy } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Icon button that copies the given text to the clipboard and shows a
 * confirmation (icon change + toast). When `bold` is true it also writes an
 * HTML version so rich-text targets (Word, Gmail…) paste it in bold.
 */
export function CopyNumberButton({ value, bold = false }: { value: string; bold?: boolean }) {
    const [copied, setCopied] = React.useState(false);

    const copy = async (e: React.MouseEvent) => {
        e.stopPropagation();

        const html = bold ? `<b>${escapeHtml(value)}</b>` : escapeHtml(value);

        try {
            if (typeof ClipboardItem !== 'undefined' && navigator.clipboard.write) {
                await navigator.clipboard.write([
                    new ClipboardItem({
                        'text/plain': new Blob([value], { type: 'text/plain' }),
                        'text/html': new Blob([html], { type: 'text/html' }),
                    }),
                ]);
            } else {
                await navigator.clipboard.writeText(value);
            }
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