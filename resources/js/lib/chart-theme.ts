export interface ChartTheme {
    tick: string;
    tickStrong: string;
    grid: string;
    tooltipBg: string;
    tooltipBorder: string;
    tooltipText: string;
    legend: string;
    cursor: string;
    totalLine: string;
}

const LIGHT: ChartTheme = {
    tick: '#6b7280',
    tickStrong: '#374151',
    grid: '#e5e7eb',
    tooltipBg: '#fff',
    tooltipBorder: '#e5e7eb',
    tooltipText: '#0f172a',
    legend: '#374151',
    cursor: 'rgba(100, 116, 139, 0.1)',
    totalLine: '#6b7280',
};

const DARK: ChartTheme = {
    tick: '#94a3b8',
    tickStrong: '#e2e8f0',
    grid: '#334155',
    tooltipBg: '#1e293b',
    tooltipBorder: '#334155',
    tooltipText: '#f1f5f9',
    legend: '#cbd5e1',
    cursor: 'rgba(148, 163, 184, 0.12)',
    totalLine: '#94a3b8',
};

export function getChartTheme(theme: 'light' | 'dark'): ChartTheme {
    return theme === 'dark' ? DARK : LIGHT;
}

export const CHART_DARK_PALETTE = [
    '#60a5fa',
    '#2dd4bf',
    '#a78bfa',
    '#f472b6',
    '#fb923c',
    '#a3e635',
    '#22d3ee',
    '#818cf8',
    '#fbbf24',
    '#2dd4bf',
    '#fb7185',
    '#60a5fa',
    '#a78bfa',
    '#22d3ee',
    '#a3e635',
];
