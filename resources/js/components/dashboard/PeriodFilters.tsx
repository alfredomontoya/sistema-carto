import { CalendarDays } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { YearSelector } from '@/components/charts';

export type Period = 'hoy' | 'ayer' | 'semana' | 'mes' | 'año' | 'rango';

export const PERIOD_LABELS: Record<Period, string> = {
    hoy: 'Hoy',
    ayer: 'Ayer',
    semana: 'Semana',
    mes: 'Mes',
    año: 'Año',
    rango: 'Fecha',
};

export function PeriodFilters({
    period,
    from,
    to,
    periodYear,
    availableYears,
    includeAnnulled,
    onPeriodChange,
    onRangeChange,
    onYearChange,
    onAnnulledChange,
}: {
    period: Period;
    from: string;
    to: string;
    periodYear: number;
    availableYears: number[];
    includeAnnulled: boolean;
    onPeriodChange: (period: Period) => void;
    onRangeChange: (from: string, to: string) => void;
    onYearChange: (year: number) => void;
    onAnnulledChange: (value: boolean) => void;
}) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-1.5">
                {(Object.keys(PERIOD_LABELS) as Period[]).map((p) => (
                    <Button
                        key={p}
                        variant={period === p ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => onPeriodChange(p)}
                    >
                        {p === 'rango' && <CalendarDays className="mr-1 h-3.5 w-3.5" />}
                        {PERIOD_LABELS[p]}
                    </Button>
                ))}
                <label className="ml-1 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <Switch checked={includeAnnulled} onCheckedChange={(v) => onAnnulledChange(v === true)} />
                    Incluir anuladas
                </label>
                <YearSelector value={periodYear} options={availableYears} onChange={onYearChange} />
            </div>

            {period === 'rango' && (
                <div className="flex flex-wrap items-end gap-3">
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-muted-foreground">Fecha inicial</p>
                        <Input
                            type="date"
                            value={from}
                            max={to || undefined}
                            onChange={(e) => onRangeChange(e.target.value, to)}
                        />
                    </div>
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-muted-foreground">Fecha final</p>
                        <Input
                            type="date"
                            value={to}
                            min={from || undefined}
                            onChange={(e) => onRangeChange(from, e.target.value)}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
