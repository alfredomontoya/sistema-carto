import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { AvatarPicker, type AvatarGalleryEntry } from '@/components/AvatarPicker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { compressImage, formatBytes } from '@/lib/compress-image';

const MAX_AVATAR_BYTES = 2 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

interface PendingCompression {
    originalName: string;
    originalSize: number;
    blob: Blob;
    compressedSize: number;
    previewUrl: string;
}

export default function UpdateAvatarForm({
    gallery,
    userName,
}: {
    gallery: AvatarGalleryEntry;
    userName: string;
}) {
    const user = usePage().props.auth.user!;
    const [uploading, setUploading] = useState(false);
    const [compressing, setCompressing] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [pending, setPending] = useState<PendingCompression | null>(null);

    const galleryForm = useForm({
        avatar_kind: user.avatar_kind,
        avatar_value: user.avatar_kind === 'gallery' ? user.avatar_value : null,
    });

    const selectGallery = (key: string) => {
        galleryForm.setData({
            avatar_kind: 'gallery',
            avatar_value: key,
        });
        galleryForm.patch(route('profile.update'));
    };

    const uploadFile = (file: File) => {
        setUploading(true);
        const form = new FormData();
        form.append('avatar', file);
        router.post(route('profile.avatar'), form, {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                if (errors.avatar) setUploadError(errors.avatar);
            },
            onFinish: () => setUploading(false),
        });
    };

    const handleUpload = async (file: File) => {
        setUploadError(null);
        setPending(null);

        if (!ALLOWED_TYPES.includes(file.type)) {
            const message = 'El archivo debe ser una imagen JPG, PNG, WEBP o GIF.';
            setUploadError(message);
            toast.error('Archivo no válido', { description: message });
            return;
        }

        if (file.size <= MAX_AVATAR_BYTES) {
            uploadFile(file);
            return;
        }

        setCompressing(true);
        try {
            const { blob, size } = await compressImage(file, MAX_AVATAR_BYTES);
            setPending({
                originalName: file.name,
                originalSize: file.size,
                blob,
                compressedSize: size,
                previewUrl: URL.createObjectURL(blob),
            });
        } catch {
            const message = 'No se pudo comprimir la imagen. Prueba con un archivo más liviano.';
            setUploadError(message);
            toast.error('Compresión fallida', { description: message });
        } finally {
            setCompressing(false);
        }
    };

    const confirmCompressedUpload = () => {
        if (!pending) return;
        const base = pending.originalName.replace(/\.[^.]+$/, '') || 'avatar';
        uploadFile(new File([pending.blob], `${base}.jpg`, { type: 'image/jpeg' }));
        URL.revokeObjectURL(pending.previewUrl);
        setPending(null);
    };

    const cancelCompressedUpload = () => {
        if (pending) URL.revokeObjectURL(pending.previewUrl);
        setPending(null);
    };

    return (
        <>
            <AvatarPicker
                user={user}
                gallery={gallery}
                selected={galleryForm.data.avatar_kind === 'gallery' ? galleryForm.data.avatar_value : null}
                uploading={uploading || compressing}
                uploadError={uploadError}
                onSelect={selectGallery}
                onUpload={handleUpload}
            />

            <Dialog open={pending !== null} onOpenChange={(o) => !o && cancelCompressedUpload()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Comprimir imagen</DialogTitle>
                        <DialogDescription>
                            La imagen supera el máximo de 2 MB y se comprimirá antes de guardarla.
                        </DialogDescription>
                    </DialogHeader>
                    {pending && (
                        <div className="flex items-center gap-4">
                            <img
                                src={pending.previewUrl}
                                alt="Vista previa comprimida"
                                className="h-20 w-20 rounded-lg border object-cover"
                            />
                            <div className="text-sm">
                                <p className="text-muted-foreground">
                                    Original: <span className="font-medium text-foreground">{formatBytes(pending.originalSize)}</span>
                                </p>
                                <p className="text-muted-foreground">
                                    Comprimida: <span className="font-medium text-foreground">{formatBytes(pending.compressedSize)}</span>
                                </p>
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={cancelCompressedUpload}>
                            Cancelar
                        </Button>
                        <Button onClick={confirmCompressedUpload} disabled={uploading}>
                            Comprimir y guardar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
