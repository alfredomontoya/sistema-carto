import { useEffect, useRef } from 'react';

/**
 * Like useEffect, but skipped on the first render.
 * Useful for reacting to user-driven filter changes without
 * firing redundant requests on mount.
 */
export function useEffectOnUpdate(effect: React.EffectCallback, deps: React.DependencyList): void {
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        return effect();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
}
