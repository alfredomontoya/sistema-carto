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
import { getChartTheme } from '@/lib/chart-theme';
import { useTheme } from '@/components/brand/ThemeProvider';
import { statValue } from '@/lib/type-stats';
import type { MonthStatRow } from '@/types';
import type { TypeSeries } from '@/lib/type-stats';

interface YearlyLineChartProps {
  data: MonthStatRow[];
  year: number;
  series: TypeSeries[];
}

export function YearlyLineChart({ data, year, series }: YearlyLineChartProps) {
  const { theme } = useTheme();
  const chart = getChartTheme(theme);
  const monthsWithData = Array.from({ length: 12 }, (_, i) => {
    const month = i + 1;
    const found: MonthStatRow = data.find(d => d.month === month) ?? { month, total: 0 };
    return {
      month,
      total: found.total,
      ...Object.fromEntries(series.map((s) => [s.key, statValue(found, s.code)])),
    };
  });

  const legendName = (value: string) =>
    value === 'total' ? 'Total' : series.find((s) => s.key === value)?.name ?? value;

  return (
    <div className="h-80">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={monthsWithData} margin={{ top: 10, right: 30, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={chart.grid} />
          <XAxis
            dataKey="month"
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 12, fill: chart.tick }}
            tickFormatter={(month) => MONTHS[(month as number) - 1].substring(0, 3)}
          />
          <YAxis type="number" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: chart.tick }} />
          <Tooltip
            labelFormatter={(month) => `${MONTHS[(month as number) - 1]} ${year}`}
            contentStyle={{ backgroundColor: chart.tooltipBg, border: `1px solid ${chart.tooltipBorder}`, borderRadius: '8px', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
            labelStyle={{ color: chart.tooltipText }}
            itemStyle={{ color: chart.tooltipText }}
            cursor={{ stroke: chart.grid }}
          />
          <Legend
            layout="horizontal"
            align="center"
            verticalAlign="bottom"
            wrapperStyle={{ paddingTop: '10px', color: chart.legend }}
            formatter={(value) => legendName(value as string)}
          />
          {series.map((s) => (
            <Line
              key={s.key}
              type="monotone"
              dataKey={s.key}
              stroke={s.color}
              strokeWidth={2}
              dot={{ fill: s.color, strokeWidth: 2, r: 4 }}
              activeDot={{ r: 6, strokeWidth: 2 }}
              name={s.key}
            />
          ))}
          <Line
            type="monotone"
            dataKey="total"
            stroke={chart.totalLine}
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
