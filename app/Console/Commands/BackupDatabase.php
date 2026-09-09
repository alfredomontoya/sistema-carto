<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--keep=7 : Dumps to retain}';

    protected $description = 'Dump the database to storage/app/backups with rotation';

    public function handle(): int
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/backups'),
        ]);

        $stamp = now()->format('Y-m-d_H-i-s');
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            $target = "backup-sqlite-{$stamp}.sqlite";
            $disk->put($target, file_get_contents($source));
        } else {
            $target = "backup-{$driver}-{$stamp}.sql";
            $binary = $this->dumpBinary();
            $command = sprintf(
                '%s --user=%s --password=%s --host=%s --port=%s %s 2>&1',
                escapeshellarg($binary),
                escapeshellarg((string) config('database.connections.mysql.username')),
                escapeshellarg((string) config('database.connections.mysql.password')),
                escapeshellarg((string) config('database.connections.mysql.host')),
                escapeshellarg((string) config('database.connections.mysql.port')),
                escapeshellarg((string) config('database.connections.mysql.database')),
            );

            exec($command, $output, $exit);

            if ($exit !== 0) {
                $this->error('mysqldump falló: '.implode("\n", $output));

                return self::FAILURE;
            }

            $disk->put($target, implode("\n", $output));
        }

        $keep = max(1, (int) $this->option('keep'));
        $dumps = collect($disk->files())->sort()->values();

        foreach ($dumps->slice(0, max(0, $dumps->count() - $keep)) as $old) {
            $disk->delete($old);
        }

        $this->info("Respaldo guardado: {$target}");

        return self::SUCCESS;
    }

    private function dumpBinary(): string
    {
        $configured = trim((string) env('MYSQLDUMP_BIN', ''));

        if ($configured !== '') {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $found = glob('C:\\wamp64\\bin\\mysql\\*\\bin\\mysqldump.exe') ?: [];

            if ($found !== []) {
                return $found[0];
            }
        }

        return 'mysqldump';
    }
}
