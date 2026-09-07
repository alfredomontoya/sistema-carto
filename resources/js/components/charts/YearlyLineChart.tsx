import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { MONTHS } from '@/lib/constants';

interface YearlyLineChartProps {
  data: { month: number; ci: number; of: number; total: number }[];
  year: number;
}

export function YearlyLineChart({ data, year }: YearlyLineChartProps) {
  const monthsWithData = Array.from({ length: 12 }, (_, i) => {
    const month = i + 1;
    const found = data.find(d => d.month === month);
    return found ?? { month, ci: 0, of: 0, total: 0 };
  });

  return (
    <div className="h-80">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={monthsWithData} margin={{ top: 10, right: 30, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
          <XAxis
            dataKey="month"
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 12, fill: '#6b7280' }}
            tickFormatter={(month) => MONTHS[(month as number) - 1].substring(0, 3)}
          />
          <YAxis type="number" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#6b7280' }} />
          <Tooltip
            labelFormatter={(month) => `${MONTHS[(month as number) - 1]} ${year}`}
            contentStyle={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
          />
          <Legend
            layout="horizontal"
            align="center"
            verticalAlign="bottom"
            wrapperStyle={{ paddingTop: '10px' }}
            formatter={(value) => value === 'ci' ? 'Comunicaciones internas' : value === 'of' ? 'Oficios externos' : 'Total'}
          />
          <Line
            type="monotone"
            dataKey="ci"
            stroke="var(--brand-primary)"
            strokeWidth={2}
            dot={{ fill: 'var(--brand-primary)', strokeWidth: 2, r: 4 }}
            activeDot={{ r: 6, strokeWidth: 2 }}
            name="ci"
          />
          <Line
            type="monotone"
            dataKey="of"
            stroke="var(--brand-secondary)"
            strokeWidth={2}
            dot={{ fill: 'var(--brand-secondary)', strokeWidth: 2, r: 4 }}
            activeDot={{ r: 6, strokeWidth: 2 }}
            name="of"
          />
          <Line
            type="monotone"
            dataKey="total"
            stroke="#6b7280"
            strokeWidth={2}
            strokeDasharray="5 5"
            dot={false}
            activeDot={{ r: 6, strokeWidth: 2 }}
            name="total"
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}