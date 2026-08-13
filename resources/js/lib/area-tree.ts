import type { AreaNode } from '@/types';

/**
 * Flattens a nested area tree into a selectable list with indentation.
 */
export function flattenAreas(nodes: AreaNode[], depth = 0): Array<{ node: AreaNode } & { depth: number }> {
    const out: Array<{ node: AreaNode; depth: number }> = [];

    for (const node of nodes) {
        out.push({ node, depth });
        out.push(...flattenAreas(node.children ?? [], depth + 1));
    }

    return out;
}

export function treeToOptions(nodes: AreaNode[]): Array<{ value: string; label: string }> {
    return flattenAreas(nodes).map(({ node, depth }) => ({
        value: node.id,
        label: `${'—'.repeat(depth)}${depth > 0 ? ' ' : ''}${node.name}${node.is_active ? '' : ' (inactiva)'}`,
    }));
}

/**
 * Flattens all positions of every area into a selectable list, grouped by
 * area name (e.g. "CARTOGRAFIA / JEFE").
 */
export function positionOptions(nodes: AreaNode[]): Array<{ value: string; label: string; areaId: string; areaName: string }> {
    const out: Array<{ value: string; label: string; areaId: string; areaName: string }> = [];

    for (const node of nodes) {
        for (const position of node.positions ?? []) {
            out.push({
                value: position.id,
                label: `${node.name} / ${position.name}`,
                areaId: node.id,
                areaName: node.name,
            });
        }
        out.push(...positionOptions(node.children ?? []));
    }

    return out;
}
