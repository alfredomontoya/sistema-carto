import { BarChart2, TrendingUp } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LazySection } from '@/components/LazySection';
import { MonthlyStackedBarChart, YearlyLineChart, YearSelector } from '@/components/charts';
import { memo } from 'react';

const MemoMonthlyStackedBarChart = memo(MonthlyStackedBarChart);
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
                        <BarChart2 className="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p className="mt-4 text-muted-foreground">
                            {isAdmin
                                ? 'No hay comunicaciones registradas para este año.'
                                : 'No hay comunicaciones en tu área para este año.'}
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <LazySection>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                    Mensual (barras apiladas)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <MemoMonthlyStackedBarChart data={stats} year={selectedYear} />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                    Tendencia anual
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <MemoYearlyLineChart data={stats} year={selectedYear} />
                            </CardContent>
                        </Card>
                    </div>
                </LazySection>
            )}
        </div>
    );
}
