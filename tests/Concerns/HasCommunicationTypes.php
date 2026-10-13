<?php

namespace Tests\Concerns;

use App\Models\CommunicationType;

trait HasCommunicationTypes
{
    protected function communicationType(string $code = 'ci'): CommunicationType
    {
        return CommunicationType::firstOrCreate(
            ['code' => $code],
            [
                'name' => match ($code) {
                    'ci' => 'Comunicación interna',
                    'of' => 'Oficio externo',
                    default => strtoupper($code),
                },
                'digits' => 4,
                'reset_annually' => true,
                'is_active' => true,
                'sort_order' => $code === 'of' ? 1 : 0,
            ],
        );
    }
}
