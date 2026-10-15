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
import { CHART_DARK_PALETTE, TYPE_PALETTE, getChartTheme } from '@/lib/chart-theme';
import { useTheme } from '@/components/brand/ThemeProvider';
import { statValue } from '@/lib/type-stats';

export interface DestinoStat {
    destino: string;
    total: number;
    [typeCode: string]: number | string;
}

const PALETTE = TYPE_PALETTE;

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
    dataKey,
    label,
}: {
    data: DestinoStat[];
    dataKey: string;
    label: string;
}) {
    const { theme } = useTheme();
    const chart = getChartTheme(theme);
    const dark = theme === 'dark';
    const palette = dark ? CHART_DARK_PALETTE : PALETTE;

    const rows = data.map((d) => ({
        destino: d.destino,
        value: statValue(d as unknown as Record<string, unknown>, dataKey),
    }));

    if (rows.every((r) => r.value === 0)) {
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
                    data={rows}
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
                        formatter={(value) => [value, label]}
                        labelFormatter={(label) => label as string}
                    />
                    <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                        {rows.map((entry, i) => (
                            <Cell key={entry.destino} fill={palette[i % palette.length]} />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
