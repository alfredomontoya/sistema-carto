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
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { BarChart2 } from 'lucide-react';
import { FileText } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { slugifyCode, statValue } from '@/lib/type-stats';
import type { CommunicationTypeRef, FlashData, MonthStatRow, UserData } from '@/types';

function formatShort(date: string): string {
    const [y, m, d] = date.split('-');
    return `${d}/${m}/${y}`;
}

const MemoDestinoBarChart = memo(DestinoBarChart);

interface FilterArea {
    id: string;
    code: string;
    name: string;
}

export default function Dashboard() {
    const props = usePage().props as unknown as {
        auth: { user: UserData };
        stats: MonthStatRow[];
        communicationTypes: CommunicationTypeRef[];
        canFilterAreas: boolean;
        selectedAreaId: string | null;
        filterAreas: FilterArea[];
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
    const {
        auth,
        stats,
        selectedYear,
        availableYears,
        isAdmin,
        communicationTypes = [],
        canFilterAreas = false,
        selectedAreaId = null,
        filterAreas = [],
    } = props;
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

    const areaParams = selectedAreaId ? { area_id: selectedAreaId } : {};

    const yearCounts = communicationTypes.map((t) => ({
        ...t,
        value: stats.reduce((acc, s) => acc + statValue(s, t.code), 0),
    }));

    const destinoTypes = communicationTypes.filter((t) =>
        destinoStats.some((d) => statValue(d as unknown as Record<string, unknown>, t.code) > 0),
    );
    const userTypes = communicationTypes.filter((t) =>
        userStats.some((d) => statValue(d as unknown as Record<string, unknown>, t.code) > 0),
    );

    const typeTotal = (data: DestinoStat[], code: string) =>
        data.reduce((acc, d) => acc + statValue(d as unknown as Record<string, unknown>, code), 0);
    const grandTotal = (data: DestinoStat[]) =>
        data.reduce((acc, d) => acc + (d.total ?? 0), 0);

    const handleYearChange = (year: number) => {
        router.get(route('dashboard'), { year, ...areaParams }, { preserveState: true, preserveScroll: true });
    };

    const applyAreaFilter = (areaId: string) => {
        router.get(
            route('dashboard'),
            { year: selectedYear, ...(areaId ? { area_id: areaId } : {}) },
            { preserveState: true, preserveScroll: true },
        );
    };

    const applyPeriod = (p: Period, rangeFrom?: string, rangeTo?: string, annulled?: boolean, year?: number) => {
        router.get(
            route('dashboard'),
            {
                year: selectedYear,
                ...areaParams,
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

    const rangeLabel = `${formatShort(dateFrom)} al ${formatShort(dateTo)}`;
    const scopeStateLabel = includeAnnulled ? 'Todas' : 'Activas';
    const scopeAreaLabel = selectedAreaId === 'all'
        ? 'Todas las áreas'
        : selectedAreaId !== null
            ? 'Área y subáreas'
            : 'Mi área y subáreas';
    const scopeLabel = (total: number) =>
        `${rangeLabel} · Total: ${total} · ${scopeStateLabel} · ${scopeAreaLabel}`;

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
                        Panel principal del sistema de comunicaciones.
                    </p>
                </div>

                {canFilterAreas && (
                    <Card>
                        <CardContent className="flex flex-wrap items-center gap-3 py-4">
                            <Label htmlFor="dashboard-area">Área</Label>
                            <select
                                id="dashboard-area"
                                className="flex h-9 min-w-56 rounded-md border border-input bg-popover px-3 py-2 text-sm text-popover-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                value={selectedAreaId ?? ''}
                                onChange={(e) => applyAreaFilter(e.target.value)}
                            >
                                <option value="">Mi área</option>
                                <option value="all">Todas las áreas</option>
                                {filterAreas.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.name}
                                    </option>
                                ))}
                            </select>
                        </CardContent>
                    </Card>
                )}

                <TotalsCards counts={yearCounts} year={selectedYear} />

                {!isBasic && (
                    <ActividadSection
                        stats={stats}
                        types={communicationTypes}
                        selectedYear={selectedYear}
                        availableYears={availableYears}
                        isAdmin={isAdmin}
                        onYearChange={handleYearChange}
                    />
                )}

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
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
                    </div>
                    <Button
                        variant="secondary"
                        onClick={() =>
                            window.open(
                                route('dashboard.report', {
                                    from: dateFrom,
                                    to: dateTo,
                                    ...(includeAnnulled ? { annulled: 1 } : {}),
                                }),
                                '_blank',
                                'noopener',
                            )
                        }
                    >
                        <FileText /> Generar reporte
                    </Button>
                </div>

                    {!isBasic && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Por destino</h2>

                        <LazySection>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {destinoTypes.map((t) => (
                                <ChartCard
                                    key={t.code}
                                    icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                    title={`${t.name} por destino`}
                                    description={scopeLabel(typeTotal(destinoStats, t.code))}
                                    fileName={`por-destino-${slugifyCode(t.code)}`}
                                >
                                    <MemoDestinoBarChart data={destinoStats} dataKey={t.code} label={t.name} />
                                </ChartCard>
                            ))}
                            <ChartCard
                                icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                title="Total por destino"
                                description={scopeLabel(grandTotal(destinoStats))}
                                fileName="total-por-destino"
                            >
                                <MemoDestinoBarChart data={destinoStats} dataKey="total" label="Total" />
                            </ChartCard>
                        </div>
                        </LazySection>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {destinoTypes.map((t) => (
                                <ChartCard
                                    key={t.code}
                                    title={`Detalle: ${t.name.toLowerCase()} por destino`}
                                    description={scopeLabel(typeTotal(destinoStats, t.code))}
                                    fileName={`detalle-por-destino-${slugifyCode(t.code)}`}
                                >
                                    <ChartDataTable data={destinoStats} valueKey={t.code} labelHeader="Destino" />
                                </ChartCard>
                            ))}
                            <ChartCard
                                title="Detalle: total por destino"
                                description={scopeLabel(grandTotal(destinoStats))}
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
                            {userTypes.map((t) => (
                                <ChartCard
                                    key={t.code}
                                    icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                    title={`${t.name} por usuario`}
                                    description={scopeLabel(typeTotal(userStats, t.code))}
                                    fileName={`por-usuario-${slugifyCode(t.code)}`}
                                >
                                    <MemoDestinoBarChart data={userStats} dataKey={t.code} label={t.name} />
                                </ChartCard>
                            ))}
                            <ChartCard
                                icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
                                title="Total por usuario"
                                description={scopeLabel(grandTotal(userStats))}
                                fileName="total-por-usuario"
                            >
                                <MemoDestinoBarChart data={userStats} dataKey="total" label="Total" />
                            </ChartCard>
                        </div>
                        </LazySection>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {userTypes.map((t) => (
                                <ChartCard
                                    key={t.code}
                                    title={`Detalle: ${t.name.toLowerCase()} por usuario`}
                                    description={scopeLabel(typeTotal(userStats, t.code))}
                                    fileName={`detalle-por-usuario-${slugifyCode(t.code)}`}
                                >
                                    <ChartDataTable data={userStats} valueKey={t.code} labelHeader="Usuario" />
                                </ChartCard>
                            ))}
                            <ChartCard
                                title="Detalle: total por usuario"
                                description={scopeLabel(grandTotal(userStats))}
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
