import * as React from 'react';
import { Check, Upload } from 'lucide-react';
import { UserAvatar } from '@/components/UserAvatar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { UserData } from '@/types';

export interface AvatarGalleryEntry {
    [key: string]: { from: string; to: string };
}

export function AvatarPicker({
    user,
    gallery,
    selected,
    uploading,
    uploadError,
    onSelect,
    onUpload,
}: {
    user: Pick<UserData, 'name' | 'avatar_kind' | 'avatar_value' | 'avatar_url'>;
    gallery: AvatarGalleryEntry;
    selected: string | null;
    uploading: boolean;
    uploadError?: string | null;
    onSelect: (key: string) => void;
    onUpload: (file: File) => void;
}) {
    const inputRef = React.useRef<HTMLInputElement>(null);

    const isSelected = (key: string) =>
        (selected !== null && selected === key) ||
        (selected === null && user.avatar_kind === 'gallery' && user.avatar_value === key);

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <UserAvatar user={user} className="h-16 w-16" />
                <div className="space-y-1">
                    <p className="text-sm font-medium">Selecciona un avatar</p>
                    <p className="text-xs text-muted-foreground">
                        Elige un diseño de la galería o sube tu propia imagen.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-6 gap-2 sm:grid-cols-8">
                {Object.entries(gallery).map(([key, gradient]) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onSelect(key)}
                        className={cn(
                            'relative flex h-10 w-10 items-center justify-center rounded-full transition-transform hover:scale-105',
                            isSelected(key) && 'ring-2 ring-ring ring-offset-2 ring-offset-background',
                        )}
                        style={{ background: `linear-gradient(135deg, ${gradient.from}, ${gradient.to})` }}
                        aria-label={`Avatar ${key}`}
                    >
                        {isSelected(key) && <Check className="h-4 w-4 text-white" />}
                    </button>
                ))}
            </div>

            <div>
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    className="hidden"
                    onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) onUpload(file);
                        e.target.value = '';
                    }}
                />
                <Button
                    type="button"
                    variant="outline"
                    disabled={uploading}
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload />
                    {uploading ? 'Subiendo…' : 'Subir foto propia'}
                </Button>
                <p className="text-xs text-muted-foreground">
                    JPG, PNG, WEBP o GIF de hasta 2 MB.
                </p>
                {uploadError && (
                    <p className="text-sm text-destructive">{uploadError}</p>
                )}
            </div>
        </div>
    );
}
