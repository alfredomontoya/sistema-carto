import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AvatarPicker, type AvatarGalleryEntry } from '@/components/AvatarPicker';

export default function UpdateAvatarForm({
    gallery,
    userName,
}: {
    gallery: AvatarGalleryEntry;
    userName: string;
}) {
    const user = usePage().props.auth.user!;
    const [uploading, setUploading] = useState(false);

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

    const handleUpload = (file: File) => {
        setUploading(true);
        const form = new FormData();
        form.append('avatar', file);
        router.post(route('profile.avatar'), form, {
            onFinish: () => setUploading(false),
        });
    };

    return (
        <AvatarPicker
            user={user}
            gallery={gallery}
            selected={galleryForm.data.avatar_kind === 'gallery' ? galleryForm.data.avatar_value : null}
            uploading={uploading}
            onSelect={selectGallery}
            onUpload={handleUpload}
        />
    );
}
