export interface CompressedImage {
    blob: Blob;
    size: number;
}

function canvasToBlob(canvas: HTMLCanvasElement, quality: number): Promise<Blob | null> {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}

/**
 * Reduce una imagen para que quepa en maxBytes: reescala el lado mayor a
 * maxDim píxeles y baja la calidad JPEG hasta lograr el tamaño.
 * Rellena en blanco (el JPEG no tiene transparencia).
 */
export async function compressImage(
    file: File,
    maxBytes: number = 2 * 1024 * 1024,
    maxDim: number = 512,
): Promise<CompressedImage> {
    const bitmap = await createImageBitmap(file);

    try {
        const scale = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('El navegador no soporta compresión de imágenes.');

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(bitmap, 0, 0, width, height);

        let quality = 0.85;
        let blob = await canvasToBlob(canvas, quality);

        while (blob !== null && blob.size > maxBytes && quality > 0.3) {
            quality = Math.round((quality - 0.1) * 10) / 10;
            blob = await canvasToBlob(canvas, quality);
        }

        if (blob === null) throw new Error('No se pudo comprimir la imagen.');

        return { blob, size: blob.size };
    } finally {
        bitmap.close();
    }
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
