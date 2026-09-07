import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as React from 'react';
import { FlashMessages } from '@/components/FlashMessages';
import { PageHeader } from '@/components/PageHeader';
import { CommunicationCreateForm } from '@/components/communications/CommunicationCreateForm';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import type { CountersData } from '@/types';

export default function Create({
    counters,
    current_area,
    year,
}: {
    counters: CountersData;
    current_area: string | null;
    year: number;
}) {
    return (
        <AppLayout>
            <FlashMessages />
            <Head title="Nueva comunicación" />

            <div className="mx-auto max-w-3xl space-y-6">
                <PageHeader
                    title="Nueva comunicación"
                    description={`Genera un correlativo interno (ci) u oficio externo (of) para el año ${year}.`}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/comunicaciones">
                                <ArrowLeft /> Volver
                            </Link>
                        </Button>
                    }
                />

                <CommunicationCreateForm
                    counters={counters}
                    current_area={current_area}
                    year={year}
                />
            </div>
        </AppLayout>
    );
}
