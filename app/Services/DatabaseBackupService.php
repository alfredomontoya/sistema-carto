<?php

namespace App\Services;

use App\Repositories\Contracts\SettingsRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseBackupService
{
    public const MAX_DEFAULT = 10;

    public const FILE_PATTERN = '/^respaldo-\d{8}-\d{6}\.sql(\.gz)?$/';

    public const LOW_FREE_PERCENT = 15;

    public const LOW_FREE_BYTES = 500 * 1024 * 1024;

    public const CRITICAL_FREE_PERCENT = 5;

    public const CRITICAL_FREE_BYTES = 100 * 1024 * 1024;

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly DatabaseDumper $dumper,
    ) {}

    /**
     * @return array<int, array{name: string, created_at: string|null, size: int, size_human: string}>
     */
    public function list(): array
    {
        $dir = $this->directory();

        if (! is_dir($dir)) {
            return [];
        }

        $backups = [];

        foreach (scandir($dir) ?: [] as $file) {
            if (! preg_match(self::FILE_PATTERN, $file)) {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;
            $size = filesize($path);

            if ($size === false) {
                continue;
            }

            $backups[] = [
                'name' => $file,
                'created_at' => $this->createdAtFromName($file),
                'size' => $size,
                'size_human' => $this->humanSize($size),
            ];
        }

        usort($backups, fn (array $a, array $b) => strcmp($b['name'], $a['name']));

        return array_values($backups);
    }

    /**
     * @return array{name: string, size: int, size_human: string, pruned: int}
     */
    public function create(): array
    {
        $dir = $this->ensureDirectory();
        $this->assertWritable($dir);

        $driver = (string) config('database.default');

        if ($driver === 'sqlite') {
            $name = $this->fileName();
            copy($this->sqlitePath(), $dir.DIRECTORY_SEPARATOR.$name);
        } else {
            $dump = $this->dumpMysql();
            $name = $this->fileName().'.gz';
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, gzencode($dump, 6));
        }

        $path = $dir.DIRECTORY_SEPARATOR.$name;
        $size = filesize($path) ?: 0;
        $pruned = $this->prune();

        return [
            'name' => $name,
            'size' => $size,
            'size_human' => $this->humanSize($size),
            'pruned' => $pruned,
        ];
    }

    public function restore(string $file): void
    {
        $path = $this->resolveFile($file);
        $driver = (string) config('database.default');

        if ($driver === 'sqlite') {
            $this->restoreSqlite($path);
        } else {
            $this->restoreMysql($path);
        }

        DB::table('sessions')->delete();
        Cache::flush();
    }

    public function downloadPath(string $file): string
    {
        return $this->resolveFile($file);
    }

    public function max(): int
    {
        return max(1, (int) $this->settings->get('backup.max', self::MAX_DEFAULT));
    }

    public function setMax(int $max): void
    {
        $this->settings->set('backup.max', (string) max(1, min($max, 100)));
    }

    public function drive(): ?string
    {
        $drive = $this->settings->get('backup.drive');

        return is_string($drive) && $drive !== '' ? strtoupper($drive) : null;
    }

    public function setDrive(?string $drive): void
    {
        $drive = is_string($drive) ? strtoupper(trim($drive)) : null;

        if ($drive !== null && ! in_array($drive, $this->availableDrives(), true)) {
            throw new \InvalidArgumentException('La unidad seleccionada no está disponible.');
        }

        $this->settings->set('backup.drive', $drive ?? '');
        $this->ensureDirectory();
    }

    /**
     * @return array<int, string>
     */
    public function availableDrives(): array
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return [];
        }

        $drives = [];

        foreach (range('C', 'Z') as $letter) {
            if (@is_dir($letter.':/')) {
                $drives[] = $letter;
            }
        }

        return $drives;
    }

    /**
     * @return array{drive: string|null, path: string, free: int|null, total: int|null, used_percent: float|null, status: string}
     */
    public function destinationInfo(): array
    {
        $drive = $this->drive();
        $path = $this->directory();
        $free = @disk_free_space($path) ?: null;
        $total = @disk_total_space($path) ?: null;

        $status = 'ok';
        $usedPercent = null;

        if ($free !== null && $total !== null && $total > 0) {
            $usedPercent = round((1 - $free / $total) * 100, 1);

            if ($free < self::CRITICAL_FREE_BYTES || ($free / $total) * 100 < self::CRITICAL_FREE_PERCENT) {
                $status = 'critical';
            } elseif ($free < self::LOW_FREE_BYTES || ($free / $total) * 100 < self::LOW_FREE_PERCENT) {
                $status = 'low';
            }
        }

        return [
            'drive' => $drive,
            'path' => $path,
            'free' => $free,
            'total' => $total,
            'used_percent' => $usedPercent,
            'status' => $status,
        ];
    }

    public function directory(): string
    {
        $drive = $this->drive();

        if ($drive === null) {
            return storage_path('app'.DIRECTORY_SEPARATOR.'backups');
        }

        return $drive.':'.DIRECTORY_SEPARATOR.'respaldos';
    }

    private function ensureDirectory(): string
    {
        $dir = $this->directory();

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("No se pudo crear la carpeta de respaldos: {$dir}");
        }

        return $dir;
    }

    private function assertWritable(string $dir): void
    {
        if (! is_writable($dir)) {
            throw new \RuntimeException("La carpeta de respaldos no tiene permiso de escritura: {$dir}");
        }
    }

    private function fileName(): string
    {
        return 'respaldo-'.now()->format('Ymd-His').'.sql';
    }

    private function createdAtFromName(string $file): ?string
    {
        if (! preg_match('/^respaldo-(\d{8})-(\d{6})\.sql/', $file, $m)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd-His', $m[1].'-'.$m[2])->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveFile(string $file): string
    {
        if (! preg_match(self::FILE_PATTERN, $file)) {
            throw new \InvalidArgumentException('Archivo de respaldo inválido.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$file;

        if (! is_file($path)) {
            throw new \InvalidArgumentException('El respaldo no existe.');
        }

        return $path;
    }

    private function prune(): int
    {
        $backups = $this->list();
        $pruned = 0;

        foreach (array_slice($backups, $this->max()) as $backup) {
            @unlink($this->directory().DIRECTORY_SEPARATOR.$backup['name']);
            $pruned++;
        }

        return $pruned;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];

        foreach ($units as $unit) {
            $bytes /= 1024;

            if ($bytes < 1024) {
                return round($bytes, 1).' '.$unit;
            }
        }

        return round($bytes, 1).' PB';
    }

    private function dumpMysql(): string
    {
        $dump = $this->dumper->dump();

        if (trim($dump) === '') {
            throw new \RuntimeException('El respaldo generado está vacío.');
        }

        return $dump;
    }

    private function restoreMysql(string $path): void
    {
        $sql = str_ends_with($path, '.gz')
            ? gzdecode(file_get_contents($path) ?: '')
            : file_get_contents($path);

        if (! is_string($sql) || trim($sql) === '') {
            throw new \RuntimeException('El respaldo está vacío o ilegible.');
        }

        $this->dumper->restore($sql);
    }

    private function sqlitePath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        if (! is_file($path)) {
            throw new \RuntimeException('No se encontró el archivo SQLite.');
        }

        return $path;
    }

    private function restoreSqlite(string $path): void
    {
        copy($path, $this->sqlitePath());
    }
}
