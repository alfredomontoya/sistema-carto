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

export function DestinoBarChart({
    data,
    type,
}: {
    data: DestinoStat[];
    type: 'ci' | 'of';
}) {
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
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                    <XAxis
                        type="number"
                        allowDecimals={false}
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 12, fill: '#6b7280' }}
                    />
                    <YAxis
                        type="category"
                        dataKey="destino"
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 12, fill: '#374151' }}
                        width={120}
                        tickFormatter={(v: string) =>
                            v.length > 18 ? `${v.slice(0, 17)}…` : v
                        }
                    />
                    <Tooltip
                        contentStyle={{
                            backgroundColor: '#fff',
                            border: '1px solid #e5e7eb',
                            borderRadius: '8px',
                            boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                        }}
                        formatter={(value) => [
                            value,
                            type === 'ci' ? 'Comunicaciones' : 'Oficios',
                        ]}
                        labelFormatter={(label) => label as string}
                    />
                    <Bar dataKey={type} radius={[0, 4, 4, 0]}>
                        {data.map((entry, i) => (
                            <Cell key={entry.destino} fill={PALETTE[i % PALETTE.length]} />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
