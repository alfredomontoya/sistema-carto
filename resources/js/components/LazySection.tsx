import * as React from 'react';
import { useEffect, useRef, useState } from 'react';

export function LazySection({
    children,
    minHeight = 320,
}: {
    children: React.ReactNode;
    minHeight?: number;
}) {
    const ref = useRef<HTMLDivElement | null>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = ref.current;
        if (!el || typeof IntersectionObserver === 'undefined') {
            setVisible(true);
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: '200px' },
        );
        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    return (
        <div ref={ref} style={visible ? undefined : { minHeight }}>
            {visible ? children : null}
        </div>
    );
}
