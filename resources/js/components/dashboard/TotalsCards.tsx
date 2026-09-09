import type { LucideIcon } from 'lucide-react';
import { Files, Mail, MailOpen } from 'lucide-react';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export function TotalsCards({ ci, of, year }: { ci: number; of: number; year: number }) {
    const totals: { label: string; value: number; icon: LucideIcon }[] = [
        { label: 'Comunicaciones internas', value: ci, icon: Mail },
        { label: 'Oficios externos', value: of, icon: MailOpen },
        { label: 'Total documentos', value: ci + of, icon: Files },
    ];

    return (
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
                                <CardDescription>Año {year}</CardDescription>
                            </div>
                        </div>
                        <p className="mt-2 text-3xl font-bold tabular-nums text-foreground">
                            {t.value}
                        </p>
                    </CardHeader>
                </Card>
            ))}
        </div>
    );
}
