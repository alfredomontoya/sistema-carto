type HighlightTextProps = {
    text?: string | null;
    query?: string | null;
};

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

export function HighlightText({ text, query }: HighlightTextProps) {
    const value = text ?? '';
    const term = query?.trim() ?? '';

    if (term === '' || value === '') {
        return <>{value}</>;
    }

    const parts = value.split(new RegExp(`(${escapeRegExp(term)})`, 'gi'));

    return (
        <>
            {parts.map((part, index) =>
                part.toLowerCase() === term.toLowerCase() ? (
                    <mark
                        key={index}
                        className="rounded-sm bg-amber-300/80 px-1 font-semibold text-foreground dark:bg-amber-400/40 dark:text-foreground"
                    >
                        {part}
                    </mark>
                ) : (
                    part
                ),
            )}
        </>
    );
}
