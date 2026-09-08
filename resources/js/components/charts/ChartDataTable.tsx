import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { DestinoStat } from './DestinoBarChart';

export function ChartDataTable({
    data,
    valueKey,
    labelHeader,
}: {
    data: DestinoStat[];
    valueKey: 'ci' | 'of' | 'total';
    labelHeader: string;
}) {
    const total = data.reduce((acc, d) => acc + d[valueKey], 0);

    if (data.every((d) => d[valueKey] === 0)) {
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
                    .filter((d) => d[valueKey] > 0)
                    .map((d) => (
                        <TableRow key={d.destino}>
                            <TableCell className="font-medium">{d.destino}</TableCell>
                            <TableCell className="text-right tabular-nums">{d[valueKey]}</TableCell>
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
