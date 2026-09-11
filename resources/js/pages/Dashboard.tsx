import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState } from 'react';
import { memo } from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { CelebrateConfetti } from '@/components/CelebrateConfetti';
import { LazySection } from '@/components/LazySection';
import { ChartDataTable, ChartCard, DestinoBarChart } from '@/components/charts';
import type { DestinoStat } from '@/components/charts';
import { ActividadSection } from '@/components/dashboard/ActividadSection';
import { PeriodFilters } from '@/components/dashboard/PeriodFilters';
import type { Period } from '@/components/dashboard/PeriodFilters';
import { TotalsCards } from '@/components/dashboard/TotalsCards';
import { BarChart2, TrendingUp } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import type { FlashData, UserData } from '@/types';

function formatShort(date: string): string {
    const [y, m, d] = date.split('-');
    return `${d}/${m}/${y}`;
}

const MemoDestinoBarChart = memo(DestinoBarChart);

export default function Dashboard() {
    const props = usePage().props as unknown as {
        auth: { user: UserData };
        stats: { month: number; ci: number; of: number; total: number }[];
        selectedYear: number;
        availableYears: number[];
        isAdmin: boolean;
        destinoStats: DestinoStat[];
        userStats: DestinoStat[];
        period: Period;
        dateFrom: string;
        dateTo: string;
        periodYear: number;
        includeAnnulled: boolean;
    };
    const { auth, stats, selectedYear, availableYears, isAdmin } = props;
    const {
        destinoStats = [],
        userStats = [],
        period,
        dateFrom,
        dateTo,
        periodYear,
        includeAnnulled,
    } = props;
    const flash = usePage().props.flash as FlashData | undefined;
    const celebrateCount = typeof flash?.celebrate === 'number' ? flash.celebrate : 0;
    const user = auth.user;
    const isBasic =
        !user.can.manage_users && !user.can.manage_areas && !user.can.manage_settings;
    const [from, setFrom] = useState(dateFrom);
    const [to, setTo] = useState(dateTo);

    const yearCi = stats.reduce((acc, s) => acc + s.ci, 0);
    const yearOf = stats.reduce((acc, s) => acc + s.of, 0);

    const handleYearChange = (year: number) => {
        router.get(route('dashboard'), { year }, { preserveState: true, preserveScroll: true });
    };

    const applyPeriod = (p: Period, rangeFrom?: string, rangeTo?: string, annulled?: boolean, year?: number) => {
        router.get(
            route('dashboard'),
            {
                year: selectedYear,
                period: p,
                period_year: year ?? periodYear,
                annulled: (annulled ?? includeAnnulled) ? 1 : 0,
                ...(p === 'rango' ? { from: rangeFrom, to: rangeTo } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const applyRange = (nextFrom: string, nextTo: string) => {
        setFrom(nextFrom);
        setTo(nextTo);
        if (nextFrom && nextTo) {
            applyPeriod('rango', nextFrom, nextTo);
        }
    };

    const ciTotal = destinoStats.reduce((acc, d) => acc + d.ci, 0);
    const ofTotal = destinoStats.reduce((acc, d) => acc + d.of, 0);
    const rangeLabel = `${formatShort(dateFrom)} al ${formatShort(dateTo)}`;
    const scopeLabel = includeAnnulled ? 'Todas' : 'Activas';

    return (
        <AppLayout>
            <FlashMessages />
            <CelebrateConfetti count={celebrateCount} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Hola, {user.name.split(' ')[0]}
                    </h1>
                    <p className="text-muted-foreground">
                        Panel principal del sistema de comunicaciones internas y oficios externos.
                    </p>
                </div>

                <TotalsCards ci={yearCi} of={yearOf} year={selectedYear} />

                {!isBasic && (
                    <ActividadSection
                        stats={stats}
                        selectedYear={selectedYear}
                        availableYears={availableYears}
                        isAdmin={isAdmin}
                        onYearChange={handleYearChange}
                    />
                )}

                <PeriodFilters
                    period={period}
                    from={from}
                    to={to}
                    periodYear={periodYear}
                    availableYears={availableYears}
                    includeAnnulled={includeAnnulled}
                    onPeriodChange={(p) => applyPeriod(p, from, to)}
                    onRangeChange={applyRange}
                    onYearChange={(y) => applyPeriod(period, from, to, undefined, y)}
                    onAnnulledChange={(v) => applyPeriod(period, from, to, v)}
                />

                    {!isBasic && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Por destino</h2>

                        <LazySection>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <ChartCard
                                icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                title="Comunicaciones por destino"
                                description={`${rangeLabel} · Total: ${ciTotal} · ${scopeLabel}`}
                                fileName="comunicaciones-por-destino"
                            >
                                <MemoDestinoBarChart data={destinoStats} type="ci" />
                            </ChartCard>
                            <ChartCard
                                icon={<TrendingUp className="h-4 w-4 text-muted-foreground" />}
                                title="Oficios por destino"
                                description={`${rangeLabel} · Total: ${ofTotal} · ${scopeLabel}`}
                                fileName="oficios-por-destino"
                            >
                                <MemoDestinoBarChart data={destinoStats} type="of" />
                            </ChartCard>
                            <ChartCard
                                icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                title="Total por destino"
                                description={`${rangeLabel} · Total: ${ciTotal + ofTotal} · ${scopeLabel}`}
                                fileName="total-por-destino"
                            >
                                <MemoDestinoBarChart data={destinoStats} type="total" />
                            </ChartCard>
                        </div>
                        </LazySection>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <ChartCard
                                title="Detalle: comunicaciones por destino"
                                description={`${rangeLabel} · Total: ${ciTotal} · ${scopeLabel}`}
                                fileName="detalle-comunicaciones-por-destino"
                            >
                                <ChartDataTable data={destinoStats} valueKey="ci" labelHeader="Destino" />
                            </ChartCard>
                            <ChartCard
                                title="Detalle: oficios por destino"
                                description={`${rangeLabel} · Total: ${ofTotal} · ${scopeLabel}`}
                                fileName="detalle-oficios-por-destino"
                            >
                                <ChartDataTable data={destinoStats} valueKey="of" labelHeader="Destino" />
                            </ChartCard>
                            <ChartCard
                                title="Detalle: total por destino"
                                description={`${rangeLabel} · Total: ${ciTotal + ofTotal} · ${scopeLabel}`}
                                fileName="detalle-total-por-destino"
                            >
                                <ChartDataTable data={destinoStats} valueKey="total" labelHeader="Destino" />
                            </ChartCard>
                        </div>
                    </div>
                )}

                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold">Por usuario</h2>
                        </div>

                        <LazySection>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <ChartCard
                                icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                title="Comunicaciones por usuario"
                                description={`${rangeLabel} · Total: ${ciTotal} · ${scopeLabel}`}
                                fileName="comunicaciones-por-usuario"
                            >
                                <MemoDestinoBarChart data={userStats} type="ci" />
                            </ChartCard>
                            <ChartCard
                                icon={<TrendingUp className="h-4 w-4 text-muted-foreground" />}
                                title="Oficios por usuario"
                                description={`${rangeLabel} · Total: ${ofTotal} · ${scopeLabel}`}
                                fileName="oficios-por-usuario"
                            >
                                <MemoDestinoBarChart data={userStats} type="of" />
                            </ChartCard>
                            <ChartCard
                                icon={<TrendingUp className="h-4 w-4 text-muted-foreground" />}
                                title="Total por usuario"
                                description={`${rangeLabel} · Total: ${ciTotal + ofTotal} · ${scopeLabel}`}
                                fileName="total-por-usuario"
                            >
                                <MemoDestinoBarChart data={userStats} type="total" />
                            </ChartCard>
                        </div>
                        </LazySection>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <ChartCard
                                title="Detalle: comunicaciones por usuario"
                                description={`${rangeLabel} · Total: ${ciTotal} · ${scopeLabel}`}
                                fileName="detalle-comunicaciones-por-usuario"
                            >
                                <ChartDataTable data={userStats} valueKey="ci" labelHeader="Usuario" />
                            </ChartCard>
                            <ChartCard
                                title="Detalle: oficios por usuario"
                                description={`${rangeLabel} · Total: ${ofTotal} · ${scopeLabel}`}
                                fileName="detalle-oficios-por-usuario"
                            >
                                <ChartDataTable data={userStats} valueKey="of" labelHeader="Usuario" />
                            </ChartCard>
                            <ChartCard
                                title="Detalle: total por usuario"
                                description={`${rangeLabel} · Total: ${ciTotal + ofTotal} · ${scopeLabel}`}
                                fileName="detalle-total-por-usuario"
                            >
                                <ChartDataTable data={userStats} valueKey="total" labelHeader="Usuario" />
                            </ChartCard>
                        </div>
                    </div>

            </div>
        </AppLayout>
    );
}