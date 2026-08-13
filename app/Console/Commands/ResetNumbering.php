<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\CommunicationRepository;
use App\Services\NumberSequenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ResetNumbering extends Command
{
    protected $signature = 'numbering:reset {--force : Skip the confirmation prompt}';

    protected $description = 'Delete every communication and reset all numbering counters to zero';

    public function __construct(
        private readonly CommunicationRepository $communications,
        private readonly NumberSequenceService $numbers,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Se eliminarán todas las comunicaciones y se reiniciarán todas las numeraciones. ¿Continuar?')) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $deleted = $this->communications->deleteAll();

        Storage::disk('public')->deleteDirectory('communications');

        $this->numbers->resetAll();

        $this->info("Comunicaciones eliminadas: {$deleted}");
        $this->info('Contadores reiniciados a cero para todas las áreas y tipos.');

        return self::SUCCESS;
    }
}
