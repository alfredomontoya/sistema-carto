import { toBlob, toPng } from 'html-to-image';

export const CHART_IMAGE_ACTIONS_CLASS = 'chart-image-actions';

function shouldKeepNode(node: HTMLElement): boolean {
    return !node.classList?.contains(CHART_IMAGE_ACTIONS_CLASS);
}

export function hasCapturableContent(container: HTMLElement | null): boolean {
    if (!container) return false;
    return container.querySelector('svg, table') !== null;
}

export async function captureChartCard(
    card: HTMLElement,
    options: { dark: boolean },
): Promise<Blob> {
    const blob = await toBlob(card, {
        pixelRatio: 2,
        cacheBust: true,
        backgroundColor: options.dark ? '#1e293b' : '#ffffff',
        filter: shouldKeepNode,
    });
    if (!blob) throw new Error('to-blob');
    return blob;
}

export async function downloadChartCard(
    card: HTMLElement,
    options: { dark: boolean; fileName: string },
): Promise<string> {
    const dataUrl = await toPng(card, {
        pixelRatio: 2,
        cacheBust: true,
        backgroundColor: options.dark ? '#1e293b' : '#ffffff',
        filter: shouldKeepNode,
    });
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = options.fileName.endsWith('.png')
        ? options.fileName
        : `${options.fileName}.png`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    return link.download;
}

export async function copyPngToClipboard(blob: Blob): Promise<void> {
    if (
        typeof ClipboardItem === 'undefined' ||
        !navigator.clipboard ||
        typeof navigator.clipboard.write !== 'function'
    ) {
        throw new Error('clipboard-unsupported');
    }
    await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
}
