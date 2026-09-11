import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { CHART_DARK_PALETTE, getChartTheme } from '@/lib/chart-theme';
import { useTheme } from '@/components/brand/ThemeProvider';

export interface DestinoStat {
    destino: string;
    ci: number;
    of: number;
    total: number;
}

const PALETTE = [
    'var(--brand-primary)',
    'var(--brand-secondary)',
    '#7c3aed',
    '#db2777',
    '#ea580c',
    '#65a30d',
    '#0891b2',
    '#4f46e5',
    '#b45309',
    '#0d9488',
    '#be123c',
    '#1d4ed8',
    '#6d28d9',
    '#0e7490',
    '#4d7c0f',
];

function ColoredTick({
    x,
    y,
    payload,
    index,
    dark,
}: {
    x?: number;
    y?: number;
    payload?: { value: string };
    index?: number;
    dark?: boolean;
}) {
    const value = payload?.value ?? '';
    const label = value.length > 18 ? `${value.slice(0, 17)}…` : value;
    const palette = dark ? CHART_DARK_PALETTE : PALETTE;

    return (
        <text
            x={x}
            y={y}
            dy={4}
            textAnchor="end"
            fontSize={12}
            fontWeight={600}
            fill={palette[(index ?? 0) % palette.length]}
        >
            {label}
        </text>
    );
}

export function DestinoBarChart({
    data,
    type,
}: {
    data: DestinoStat[];
    type: 'ci' | 'of' | 'total';
}) {
    const { theme } = useTheme();
    const chart = getChartTheme(theme);
    const dark = theme === 'dark';
    const palette = dark ? CHART_DARK_PALETTE : PALETTE;

    if (data.every((d) => d[type] === 0)) {
        return (
            <div className="flex h-80 items-center justify-center text-sm text-muted-foreground">
                Sin registros para este filtro.
            </div>
        );
    }

    return (
        <div className="h-80">
            <ResponsiveContainer width="100%" height="100%">
                <BarChart
                    data={data}
                    layout="vertical"
                    margin={{ top: 10, right: 30, left: 0, bottom: 0 }}
                >
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={chart.grid} />
                    <XAxis
                        type="number"
                        allowDecimals={false}
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 12, fill: chart.tick }}
                    />
                    <YAxis
                        type="category"
                        dataKey="destino"
                        tickLine={false}
                        axisLine={false}
                        width={120}
                        tick={<ColoredTick dark={dark} />}
                    />
                    <Tooltip
                        contentStyle={{
                            backgroundColor: chart.tooltipBg,
                            border: `1px solid ${chart.tooltipBorder}`,
                            borderRadius: '8px',
                            boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                        }}
                        labelStyle={{ color: chart.tooltipText }}
                        itemStyle={{ color: chart.tooltipText }}
                        cursor={{ fill: chart.cursor }}
                        formatter={(value) => [
                            value,
                            type === 'ci'
                                ? 'Comunicaciones'
                                : type === 'of'
                                  ? 'Oficios'
                                  : 'Total',
                        ]}
                        labelFormatter={(label) => label as string}
                    />
                    <Bar dataKey={type} radius={[0, 4, 4, 0]}>
                        {data.map((entry, i) => (
                            <Cell key={entry.destino} fill={palette[i % palette.length]} />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
