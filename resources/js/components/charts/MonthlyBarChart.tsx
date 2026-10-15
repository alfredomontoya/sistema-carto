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
import { getChartTheme } from '@/lib/chart-theme';
import { useTheme } from '@/components/brand/ThemeProvider';
import { statValue } from '@/lib/type-stats';
import type { MonthStatRow } from '@/types';

interface MonthlyBarChartProps {
  data: MonthStatRow[];
  year: number;
  metric: string;
  label: string;
  color: string;
}

export function MonthlyBarChart({ data, year, metric, label, color }: MonthlyBarChartProps) {
  const { theme } = useTheme();
  const chart = getChartTheme(theme);
  const monthsWithData = Array.from({ length: 12 }, (_, i) => {
    const month = i + 1;
    const found = data.find(d => d.month === month);
    return { month, value: found ? statValue(found, metric) : 0 };
  });

  return (
    <div className="h-[26rem]">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={monthsWithData} layout="vertical" margin={{ top: 10, right: 16, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={chart.grid} />
          <XAxis type="number" allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: chart.tick }} />
          <YAxis
            type="category"
            dataKey="month"
            tickLine={false}
            axisLine={false}
            interval={0}
            tick={{ fontSize: 12, fill: chart.tickStrong }}
            tickFormatter={(month) => MONTHS[month - 1]}
            width={80}
          />
          <Tooltip
            labelFormatter={(month) => `${MONTHS[(month as number) - 1]} ${year}`}
            contentStyle={{ backgroundColor: chart.tooltipBg, border: `1px solid ${chart.tooltipBorder}`, borderRadius: '8px', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
            labelStyle={{ color: chart.tooltipText }}
            itemStyle={{ color: chart.tooltipText }}
            cursor={{ fill: chart.cursor }}
            formatter={(value) => [value, label]}
          />
          <Legend
            layout="horizontal"
            align="center"
            verticalAlign="bottom"
            iconType="square"
            wrapperStyle={{ paddingTop: '10px', color: chart.legend }}
            formatter={() => label}
          />
          <Bar
            dataKey="value"
            fill={color}
            radius={[0, 4, 4, 0]}
            name={metric}
            minPointSize={2}
          />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
