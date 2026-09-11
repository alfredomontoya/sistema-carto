import { Inbox, Send, TrendingUp } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { LazySection } from '@/components/LazySection';
import { ChartCard, MonthlyBarChart, YearlyLineChart, YearSelector } from '@/components/charts';
import { memo } from 'react';

const MemoMonthlyBarChart = memo(MonthlyBarChart);
const MemoYearlyLineChart = memo(YearlyLineChart);

interface MonthStat {
    month: number;
    ci: number;
    of: number;
    total: number;
}

export function ActividadSection({
    stats,
    selectedYear,
    availableYears,
    isAdmin,
    onYearChange,
}: {
    stats: MonthStat[];
    selectedYear: number;
    availableYears: number[];
    isAdmin: boolean;
    onYearChange: (year: number) => void;
}) {
    const hasData = stats.some((s) => s.total > 0);
    const yearTotal = stats.reduce((acc, s) => acc + s.total, 0);

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
                    <div className="grid gap-4 md:grid-cols-3">
                        <ChartCard
                            icon={<Inbox className="h-4 w-4 text-muted-foreground" />}
                            title="Mensual comunicaciones"
                            fileName={`mensual-comunicaciones-${selectedYear}`}
                        >
                            <MemoMonthlyBarChart data={stats} year={selectedYear} metric="ci" />
                        </ChartCard>
                        <ChartCard
                            icon={<Send className="h-4 w-4 text-muted-foreground" />}
                            title="Mensual oficios"
                            fileName={`mensual-oficios-${selectedYear}`}
                        >
                            <MemoMonthlyBarChart data={stats} year={selectedYear} metric="of" />
                        </ChartCard>
                        <ChartCard
                            icon={<TrendingUp className="h-4 w-4 text-muted-foreground" />}
                            title="Tendencia anual"
                            fileName={`tendencia-anual-${selectedYear}`}
                        >
                            <MemoYearlyLineChart data={stats} year={selectedYear} />
                        </ChartCard>
                    </div>
                </LazySection>
            )}
        </div>
    );
}
