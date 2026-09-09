import * as React from 'react';
import { useRef } from 'react';
import confetti from 'canvas-confetti';
import { toast } from 'sonner';

function brandColor(name: string, fallback: string): string {
    if (typeof document === 'undefined') return fallback;
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

export function CelebrateConfetti({ count }: { count: number }) {
    const fired = useRef(false);

    React.useEffect(() => {
        if (count <= 0 || fired.current) return;
        fired.current = true;

        const colors = [
            brandColor('--brand-primary', '#1d4ed8'),
            brandColor('--brand-secondary', '#0d9488'),
            '#fbbf24',
            '#ffffff',
        ];

        toast.success('¡Meta alcanzada!', {
            description: `Ayer creaste ${count} ${
                count === 1 ? 'comunicación' : 'comunicaciones'
            }: fuiste quien más registros generó. ¡Sigue así!`,
            duration: 8000,
        });

        confetti({
            particleCount: 120,
            spread: 100,
            origin: { y: 0.6 },
            colors,
        });
        window.setTimeout(
            () => confetti({ particleCount: 60, angle: 60, spread: 60, origin: { x: 0 }, colors }),
            250,
        );
        window.setTimeout(
            () => confetti({ particleCount: 60, angle: 120, spread: 60, origin: { x: 1 }, colors }),
            400,
        );
    }, [count]);

    return null;
}
