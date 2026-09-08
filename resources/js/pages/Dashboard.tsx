import { CalendarDays, BarChart2, TrendingUp, Files, Mail, MailOpen } from 'lucide-react';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState } from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { ChartDataTable, DestinoBarChart, MonthlyStackedBarChart, YearlyLineChart, YearSelector } from '@/components/charts';
import type { DestinoStat } from '@/components/charts';
import AppLayout from '@/layouts/AppLayout';
import type { UserData } from '@/types';

type Period = 'hoy' | 'ayer' | 'semana' | 'mes' | 'rango';

const PERIOD_LABELS: Record<Period, string> = {
    hoy: 'Hoy',
    ayer: 'Ayer',
    semana: 'Semana',
    mes: 'Mes',
    rango: 'Fecha',
};

function formatShort(date: string): string {
    const [y, m, d] = date.split('-');
    return `${d}/${m}/${y}`;
}

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
        includeAnnulled: boolean;
    };
    const { auth, stats, selectedYear, availableYears, isAdmin } = props;
    const { destinoStats, userStats, period, dateFrom, dateTo, includeAnnulled } = props;
    const user = auth.user;
    const isBasic =
        !user.can.manage_users && !user.can.manage_areas && !user.can.manage_settings;
    const [from, setFrom] = useState(dateFrom);
    const [to, setTo] = useState(dateTo);

    const yearCi = stats.reduce((acc, s) => acc + s.ci, 0);
    const yearOf = stats.reduce((acc, s) => acc + s.of, 0);

    const totals = [
        {
            label: 'Comunicaciones internas',
            value: yearCi,
            icon: Mail,
            description: `Año ${selectedYear}`,
        },
        {
            label: 'Oficios externos',
            value: yearOf,
            icon: MailOpen,
            description: `Año ${selectedYear}`,
        },
        {
            label: 'Total documentos',
            value: yearCi + yearOf,
            icon: Files,
            description: `Año ${selectedYear}`,
        },
    ];

    const handleYearChange = (year: number) => {
        router.get(route('dashboard'), { year }, { preserveState: true, preserveScroll: true });
    };

    const applyPeriod = (p: Period, rangeFrom?: string, rangeTo?: string, annulled?: boolean) => {
        router.get(
            route('dashboard'),
            {
                year: selectedYear,
                period: p,
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

    const hasData = stats.some(s => s.total > 0);
    const yearTotal = stats.reduce((acc, s) => acc + s.total, 0);
    const ciTotal = destinoStats.reduce((acc, d) => acc + d.ci, 0);
    const ofTotal = destinoStats.reduce((acc, d) => acc + d.of, 0);
    const rangeLabel = `${formatShort(dateFrom)} al ${formatShort(dateTo)}`;
    const scopeLabel = includeAnnulled ? 'Todas' : 'Activas';

    return (
        <AppLayout>
            <FlashMessages />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Hola, {user.name.split(' ')[0]}
                    </h1>
                    <p className="text-muted-foreground">
                        Panel principal del sistema de comunicaciones internas y oficios externos.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {totals.map((t) => (
                        <Card key={t.label}>
                            <CardHeader>
                                <div className="flex items-center gap-3">
                                    <div
                                        className="flex h-10 w-10 items-center justify-center rounded-lg text-primary-foreground"
                                        style={{
                                            background:
                                                'linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))',
                                        }}
                                    >
                                        <t.icon className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-base">{t.label}</CardTitle>
                                        <CardDescription>{t.description}</CardDescription>
                                    </div>
                                </div>
                                <p className="mt-2 text-3xl font-bold tabular-nums text-foreground">
                                    {t.value}
                                </p>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                {!isBasic && (
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Actividad de comunicaciones{' '}
                            <span className="text-sm font-normal text-muted-foreground">
                                {selectedYear} · Total: {yearTotal}
                            </span>
                        </h2>
                        <YearSelector value={selectedYear} options={availableYears} onChange={handleYearChange} />
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
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                        Mensual (barras apiladas)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <MonthlyStackedBarChart data={stats} year={selectedYear} />
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
                                    <YearlyLineChart data={stats} year={selectedYear} />
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>
                )}

                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-1.5">
                                {(Object.keys(PERIOD_LABELS) as Period[]).map((p) => (
                                    <Button
                                        key={p}
                                        variant={period === p ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => applyPeriod(p, from, to)}
                                    >
                                        {p === 'rango' && (
                                            <CalendarDays className="mr-1 h-3.5 w-3.5" />
                                        )}
                                        {PERIOD_LABELS[p]}
                                    </Button>
                                ))}
                                <label className="ml-1 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Switch
                                        checked={includeAnnulled}
                                        onCheckedChange={(v) =>
                                            applyPeriod(period, from, to, v === true)
                                        }
                                    />
                                    Incluir anuladas
                                </label>
                        </div>

                        {period === 'rango' && (
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="space-y-1">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Fecha inicial
                                    </p>
                                    <Input
                                        type="date"
                                        value={from}
                                        max={to || undefined}
                                        onChange={(e) => applyRange(e.target.value, to)}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Fecha final
                                    </p>
                                    <Input
                                        type="date"
                                        value={to}
                                        min={from || undefined}
                                        onChange={(e) => applyRange(from, e.target.value)}
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {!isBasic && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Por destino</h2>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                        Comunicaciones por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={destinoStats} type="ci" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                        Oficios por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={destinoStats} type="of" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                        Total por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal + ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={destinoStats} type="total" />
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: comunicaciones por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={destinoStats} valueKey="ci" labelHeader="Destino" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: oficios por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={destinoStats} valueKey="of" labelHeader="Destino" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: total por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal + ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={destinoStats} valueKey="total" labelHeader="Destino" />
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}

                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold">Por usuario</h2>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                        Comunicaciones por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={userStats} type="ci" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                        Oficios por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={userStats} type="of" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                        Total por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal + ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={userStats} type="total" />
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: comunicaciones por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={userStats} valueKey="ci" labelHeader="Usuario" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: oficios por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={userStats} valueKey="of" labelHeader="Usuario" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Detalle: total por usuario
                                    </CardTitle>
                                    <CardDescription>
                                        {rangeLabel} · Total: {ciTotal + ofTotal} · {scopeLabel}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ChartDataTable data={userStats} valueKey="total" labelHeader="Usuario" />
                                </CardContent>
                            </Card>
                        </div>
                    </div>

            </div>
        </AppLayout>
    );
}