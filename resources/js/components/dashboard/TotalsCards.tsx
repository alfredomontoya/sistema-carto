import type { LucideIcon } from 'lucide-react';
import { Archive, FileText, Files, Mail, MailOpen } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export interface TypeCount {
    code: string;
    name: string;
    value: number;
}

const ICONS: LucideIcon[] = [Mail, MailOpen, FileText, Archive, Files];

export function TotalsCards({ counts, year }: { counts: TypeCount[]; year: number }) {
    const visible = counts.filter((c) => c.value > 0);
    const total = counts.reduce((acc, c) => acc + c.value, 0);

    if (visible.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center text-sm text-muted-foreground">
                    Sin registros para el año {year}.
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {visible.map((c, i) => {
                const Icon = ICONS[i % ICONS.length];
                return (
                    <Card key={c.code}>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div
                                    className="flex h-10 w-10 items-center justify-center rounded-lg text-primary-foreground"
                                    style={{
                                        background:
                                            'linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))',
                                    }}
                                >
                                    <Icon className="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-base">{c.name}</CardTitle>
                                    <CardDescription>Año {year}</CardDescription>
                                </div>
                            </div>
                            <p className="mt-2 text-3xl font-bold tabular-nums text-foreground">
                                {c.value}
                            </p>
                        </CardHeader>
                    </Card>
                );
            })}
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div
                            className="flex h-10 w-10 items-center justify-center rounded-lg text-primary-foreground"
                            style={{
                                background:
                                    'linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))',
                            }}
                        >
                            <Files className="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle className="text-base">Total documentos</CardTitle>
                            <CardDescription>Año {year}</CardDescription>
                        </div>
                    </div>
                    <p className="mt-2 text-3xl font-bold tabular-nums text-foreground">
                        {total}
                    </p>
                </CardHeader>
            </Card>
        </div>
    );
}
