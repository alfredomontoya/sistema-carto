import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { MONTHS } from '@/lib/constants';

interface MonthlyStackedBarChartProps {
  data: { month: number; ci: number; of: number; total: number }[];
  year: number;
}

export function MonthlyStackedBarChart({ data, year }: MonthlyStackedBarChartProps) {
  const monthsWithData = Array.from({ length: 12 }, (_, i) => {
    const month = i + 1;
    const found = data.find(d => d.month === month);
    return found ?? { month, ci: 0, of: 0, total: 0 };
  });

  return (
    <div className="h-80">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={monthsWithData} layout="vertical" margin={{ top: 10, right: 30, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
          <XAxis type="number" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#6b7280' }} />
          <YAxis
            type="category"
            dataKey="month"
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 12, fill: '#374151' }}
            tickFormatter={(month) => MONTHS[month - 1]}
            width={80}
          />
          <Tooltip
            labelFormatter={(month) => `${MONTHS[(month as number) - 1]} ${year}`}
            contentStyle={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
          />
          <Legend
            layout="horizontal"
            align="center"
            verticalAlign="bottom"
            iconType="square"
            wrapperStyle={{ paddingTop: '10px' }}
            formatter={(value) => value === 'ci' ? 'Comunicaciones internas' : 'Oficios externos'}
          />
          <Bar dataKey="ci" stackId="a" fill="var(--brand-primary)" radius={[0, 4, 4, 0]} name="ci" />
          <Bar dataKey="of" stackId="a" fill="var(--brand-secondary)" radius={[4, 0, 0, 4]} name="of" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}