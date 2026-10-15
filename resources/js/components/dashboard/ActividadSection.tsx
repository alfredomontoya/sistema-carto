import { Inbox } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { LazySection } from '@/components/LazySection';
import { ChartCard, MonthlyBarChart, YearlyLineChart, YearSelector } from '@/components/charts';
import { CHART_DARK_PALETTE, TYPE_PALETTE } from '@/lib/chart-theme';
import { useTheme } from '@/components/brand/ThemeProvider';
import { buildTypeSeries, statValue } from '@/lib/type-stats';
import type { CommunicationTypeRef, MonthStatRow } from '@/types';
import { memo } from 'react';

const MemoMonthlyBarChart = memo(MonthlyBarChart);
const MemoYearlyLineChart = memo(YearlyLineChart);

export function ActividadSection({
    stats,
    types,
    selectedYear,
    availableYears,
    isAdmin,
    onYearChange,
}: {
    stats: MonthStatRow[];
    types: CommunicationTypeRef[];
    selectedYear: number;
    availableYears: number[];
    isAdmin: boolean;
    onYearChange: (year: number) => void;
}) {
    const { theme } = useTheme();
    const palette = theme === 'dark' ? CHART_DARK_PALETTE : TYPE_PALETTE;

    const activeTypes = types.filter(
        (t) => stats.reduce((acc, s) => acc + statValue(s, t.code), 0) > 0,
    );
    const series = buildTypeSeries(activeTypes, palette);

    const hasData = stats.some((s) => (s.total ?? 0) > 0);
    const yearTotal = stats.reduce((acc, s) => acc + (s.total ?? 0), 0);

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold">
                    Actividad de comunicaciones{' '}
                    <span className="text-sm font-normal text-muted-foreground">
                        {selectedYear} · Total: {yearTotal}
                    </span>
                </h2>
                <YearSelector value={selectedYear} options={availableYears} onChange={onYearChange} />
            </div>

            {!hasData ? (
                <Card>
                    <CardContent className="py-12 text-center">
                        <Inbox className="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p className="mt-4 text-muted-foreground">
                            {isAdmin
                                ? 'No hay comunicaciones registradas para este año.'
                                : 'No hay comunicaciones en tu área para este año.'}
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <LazySection>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {series.map((s) => (
                            <ChartCard
                                key={s.key}
                                title={`Mensual ${s.name.toLowerCase()}`}
                                fileName={`mensual-${s.code.toLowerCase()}-${selectedYear}`}
                            >
                                <MemoMonthlyBarChart
                                    data={stats}
                                    year={selectedYear}
                                    metric={s.code}
                                    label={s.name}
                                    color={s.color}
                                />
                            </ChartCard>
                        ))}
                        <ChartCard
                            title="Tendencia anual"
                            fileName={`tendencia-anual-${selectedYear}`}
                        >
                            <MemoYearlyLineChart data={stats} year={selectedYear} series={series} />
                        </ChartCard>
                    </div>
                </LazySection>
            )}
        </div>
    );
}
