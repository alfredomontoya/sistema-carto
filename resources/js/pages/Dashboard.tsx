import { Link } from '@inertiajs/react';
import { CalendarDays, FileText, Layers, Plus, Users, BarChart2, TrendingUp } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState } from 'react';
import { UserAvatar } from '@/components/UserAvatar';
import { FlashMessages } from '@/components/FlashMessages';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { DestinoBarChart, MonthlyStackedBarChart, YearlyLineChart, YearSelector } from '@/components/charts';
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
        period: Period;
        dateFrom: string;
        dateTo: string;
    };
    const { auth, stats, selectedYear, availableYears, isAdmin } = props;
    const { destinoStats, period, dateFrom, dateTo } = props;
    const user = auth.user;
    const [from, setFrom] = useState(dateFrom);
    const [to, setTo] = useState(dateTo);

    const quickActions = [
        {
            label: 'Nueva comunicación',
            href: '/comunicaciones/crear',
            icon: Plus,
            description: 'Generar correlativo interno o externo',
        },
        ...(user.can.manage_users
            ? [
                  {
                      label: 'Gestionar usuarios',
                      href: '/admin/users',
                      icon: Users,
                      description: 'Crear usuarios y asignar roles/áreas',
                  },
              ]
            : []),
        ...(user.can.manage_areas
            ? [
                  {
                      label: 'Administrar áreas',
                      href: '/admin/areas',
                      icon: Layers,
                      description: 'Organizar el árbol de áreas',
                  },
              ]
            : []),
    ];

    const handleYearChange = (year: number) => {
        router.get(route('dashboard'), { year }, { preserveState: true, preserveScroll: true });
    };

    const applyPeriod = (p: Period, rangeFrom?: string, rangeTo?: string) => {
        router.get(
            route('dashboard'),
            {
                year: selectedYear,
                period: p,
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

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {quickActions.map((action) => (
                        <Link
                            key={action.href}
                            href={action.href}
                            className="group"
                        >
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="flex h-10 w-10 items-center justify-center rounded-lg text-primary-foreground"
                                            style={{
                                                background:
                                                    'linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))',
                                            }}
                                        >
                                            <action.icon className="h-5 w-5" />
                                        </div>
                                        <CardTitle className="text-base">
                                            {action.label}
                                        </CardTitle>
                                    </div>
                                    <CardDescription>{action.description}</CardDescription>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>

                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Actividad de comunicaciones</h2>
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
                        <div className="grid gap-4 lg:grid-cols-2">
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

                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold">Por destino</h2>
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
                            </div>
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

                        <div className="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                                        Comunicaciones por destino
                                    </CardTitle>
                                    <CardDescription>
                                        {formatShort(dateFrom)} al {formatShort(dateTo)}
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
                                        {formatShort(dateFrom)} al {formatShort(dateTo)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DestinoBarChart data={destinoStats} type="of" />
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <FileText className="h-4 w-4 text-muted-foreground" /> Tu perfil
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <UserAvatar user={user} className="h-16 w-16" />
                                <div className="space-y-1 text-center sm:text-left">
                                    <p className="font-medium text-foreground">{user.name}</p>
                                    <p className="text-sm text-muted-foreground">{user.email}</p>
                                    {user.current_position && (
                                        <p className="text-sm text-muted-foreground">
                                            Puesto:{' '}
                                            <span className="font-medium text-foreground">
                                                {user.current_position.name}
                                            </span>
                                            {user.current_area && (
                                                <>
                                                    {' '}· Área:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {user.current_area.name}
                                                    </span>
                                                </>
                                            )}
                                        </p>
                                    )}
                                    <p className="text-sm text-muted-foreground">
                                        Roles:{' '}
                                        <span className="font-medium capitalize text-foreground">
                                            {user.roles.join(', ') || 'usuario'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}