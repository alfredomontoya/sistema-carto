import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statValue } from '@/lib/type-stats';
import type { DestinoStat } from './DestinoBarChart';

export function ChartDataTable({
    data,
    valueKey,
    labelHeader,
}: {
    data: DestinoStat[];
    valueKey: string;
    labelHeader: string;
}) {
    const num = (d: DestinoStat) => statValue(d as unknown as Record<string, unknown>, valueKey);
    const total = data.reduce((acc, d) => acc + num(d), 0);

    if (data.every((d) => num(d) === 0)) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                Sin registros para este filtro.
            </p>
        );
    }

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{labelHeader}</TableHead>
                    <TableHead className="w-24 text-right">Cantidad</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {data
                    .filter((d) => num(d) > 0)
                    .map((d) => (
                        <TableRow key={d.destino}>
                            <TableCell className="font-medium">{d.destino}</TableCell>
                            <TableCell className="text-right tabular-nums">{num(d)}</TableCell>
                        </TableRow>
                    ))}
            </TableBody>
            <TableFooter>
                <TableRow>
                    <TableCell className="font-semibold">Total</TableCell>
                    <TableCell className="text-right font-semibold tabular-nums">{total}</TableCell>
                </TableRow>
            </TableFooter>
        </Table>
    );
}
