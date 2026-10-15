import type { CommunicationTypeRef } from '@/types';

export interface TypeSeries {
    key: string;
    code: string;
    name: string;
    color: string;
}

/**
 * Read a per-type count from a stat row. Stat rows carry one dynamic key
 * per communication type code (e.g. `ci`, `of.ext`), so access must be by
 * brackets and missing keys count as zero (never NaN).
 */
export function statValue(row: Record<string, unknown>, code: string): number {
    const v = row[code];
    return typeof v === 'number' && Number.isFinite(v) ? v : 0;
}

/** File-name friendly slug for a type code. */
export function slugifyCode(code: string): string {
    const slug = code
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    return slug === '' ? 'tipo' : slug;
}

/**
 * One chart series per type. Keys are positional (`s0`, `s1`…) instead of
 * raw codes so recharts `dataKey` never interprets dots as nested paths.
 */
export function buildTypeSeries(
    types: CommunicationTypeRef[],
    palette: string[],
): TypeSeries[] {
    return types.map((t, i) => ({
        key: `s${i}`,
        code: t.code,
        name: t.name,
        color: palette[i % palette.length],
    }));
}
