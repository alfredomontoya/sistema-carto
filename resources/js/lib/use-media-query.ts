import * as React from 'react';

export function useMediaQuery(query: string) {
    const [matches, setMatches] = React.useState(() =>
        typeof window !== 'undefined' ? window.matchMedia(query).matches : false,
    );

    React.useEffect(() => {
        const mql = window.matchMedia(query);
        const handler = (event: MediaQueryListEvent) => setMatches(event.matches);
        setMatches(mql.matches);
        mql.addEventListener('change', handler);
        return () => mql.removeEventListener('change', handler);
    }, [query]);

    return matches;
}

export function useIsDesktop() {
    return useMediaQuery('(min-width: 1024px)');
}
