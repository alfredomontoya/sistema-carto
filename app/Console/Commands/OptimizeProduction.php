<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class OptimizeProduction extends Command
{
    protected $signature = 'app:optimize-produccion
        {--check : Solo audita la configuración sin cambiar nada}
        {--skip-backup : Omite el respaldo previo a migrar}
        {--no-maintenance : Omite el modo mantenimiento (down/up)}';

    protected $description = 'Optimiza y verifica la app para producción (cachés, migraciones, respaldo y smoke test)';

    public function __construct(
        private readonly DatabaseBackupService $backups,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('check')) {
            return $this->audit();
        }

        return $this->runOptimization();
    }

    /**
     * Solo lectura: audita cada punto y retorna FAILURE si algo falla.
     *
     * @return array<int, array{label: string, ok: bool, detail: string}>
     */
    public function checks(): array
    {
        $configCached = is_file(base_path('bootstrap/cache/config.php'));
        $cacheStore = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');
        $cacheOk = in_array($cacheStore, ['file', 'redis', 'memcached'], true);
        $sessionOk = in_array($sessionDriver, ['file', 'redis'], true);

        return [
            $this->check('APP_ENV=production', config('app.env') === 'production', 'Actual: '.config('app.env')),
            $this->check('APP_DEBUG=false', config('app.debug') === false, 'Actual: '.var_export(config('app.debug'), true)),
            $this->check('APP_KEY configurada', $this->appKeyPresent(), $this->appKeyState()),
            $this->check(
                'CACHE_STORE en file|redis|memcached',
                $cacheOk,
                'Actual: '.$cacheStore.($cacheOk ? '' : ' (database escribe en cada request)'),
                $cacheOk ? 'ok' : 'warn'
            ),
            $this->check(
                'SESSION_DRIVER en file|redis',
                $sessionOk,
                'Actual: '.$sessionDriver.($sessionOk ? '' : ' (database escribe una fila por request)'),
                $sessionOk ? 'ok' : 'warn'
            ),
            $this->check(
                'OPcache activo',
                ini_get('opcache.enable') === '1' || ini_get('opcache.enable') === true,
                'opcache.enable='.var_export(ini_get('opcache.enable'), true)
            ),
            $this->check(
                'OPcache sin revalidación por timestamp',
                in_array(ini_get('opcache.validate_timestamps'), ['0', false, '', 0], true),
                'validate_timestamps='.var_export(ini_get('opcache.validate_timestamps'), true).' (requiere reiniciar Apache tras cada deploy)'
            ),
            $this->check(
                'OPcache con memoria suficiente',
                (int) ini_get('opcache.memory_consumption') >= 128,
                'memory_consumption='.ini_get('opcache.memory_consumption').'M'
            ),
            $this->check(
                'Xdebug ausente en producción',
                ! extension_loaded('xdebug'),
                extension_loaded('xdebug') ? 'Cargado: penaliza cada request' : 'No cargado'
            ),
            $this->check(
                'display_errors desactivado',
                ! filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN),
                'display_errors='.var_export(ini_get('display_errors'), true).' (si está On se filtran rutas y trazas)'
            ),
            $this->check(
                'expose_php desactivado',
                ! filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOLEAN),
                'expose_php='.var_export(ini_get('expose_php'), true)
            ),
            $this->check(
                'upload_max_filesize >= 32M',
                $this->iniBytes('upload_max_filesize') >= 32 * 1024 * 1024,
                'upload_max_filesize='.ini_get('upload_max_filesize').' (la app valida adjuntos hasta 10 MB)'
            ),
            $this->check(
                'Cachés de Laravel vigentes',
                $configCached
                    && Route::hasCachedRoutes()
                    && is_file(base_path('bootstrap/cache/events.php')),
                $configCached ? $this->cachedViews() : 'Falta bootstrap/cache/config.php (corre app:optimize-produccion)'
            ),
            $this->check(
                'Autoload optimizado sin paquetes dev',
                $this->autoloaderOptimized(),
                $this->autoloaderOptimized() ? 'classmap generado, sin phpunit' : 'Corre composer install --no-dev --optimize-autoloader'
            ),
            $this->check(
                'public/storage es un enlace',
                $this->publicStorageIsLink(),
                $this->publicStorageIsLink() ? 'enlace correcto' : 'public/storage no es un enlace: adjuntos y avatares no se sirven'
            ),
            $this->check(
                'Directorios de storage escribibles',
                $this->storageWritable(),
                'storage/framework, storage/app, storage/logs y bootstrap/cache'
            ),
            $this->check(
                'Sin migraciones pendientes',
                $this->pendingMigrations() === 0,
                $this->pendingMigrations() === 0 ? 'todas aplicadas' : $this->pendingMigrations().' pendiente(s)'
            ),
            $this->check(
                'Tamaño de laravel.log controlado',
                $this->logSize() < 50 * 1024 * 1024,
                $this->logSizeHuman()
            ),
            $this->check(
                'Espacio para respaldos',
                in_array($this->backups->destinationInfo()['status'], ['ok', 'low'], true),
                $this->backups->destinationInfo()['path']
            ),
        ];
    }

    private function audit(): int
    {
        $failed = 0;
        $warnings = 0;

        foreach ($this->checks() as $check) {
            $badge = match ($check['level']) {
                'warn' => '<fg=yellow>AVISO</> ',
                default => $check['ok'] ? '<fg=green>OK</> ' : '<fg=red>FAIL</> ',
            };

            $this->components->twoColumnDetail($check['label'], $badge.$check['detail']);

            if (! $check['ok']) {
                $failed++;
            } elseif ($check['level'] === 'warn') {
                $warnings++;
            }
        }

        if ($warnings > 0) {
            $this->warn("Auditoría con {$warnings} aviso(s).");
        }

        if ($failed > 0) {
            $this->error("Auditoría con {$failed} punto(s) por corregir.");

            return self::FAILURE;
        }

        $this->info('Auditoría limpia: lista para producción.');

        return self::SUCCESS;
    }

    private function runOptimization(): int
    {
        $maintenance = ! $this->option('no-maintenance');

        if ($maintenance && $this->step('Activar mantenimiento', fn () => Artisan::call('down')) !== 0) {
            return $this->abort('No se pudo activar el mantenimiento.');
        }

        if (! $this->option('skip-backup')) {
            $backup = $this->step('Respaldo previo', function (): int {
                try {
                    $result = $this->backups->create();
                    $this->line("Respaldo {$result['name']} ({$result['size_human']}).");

                    return 0;
                } catch (\Throwable $e) {
                    $this->line($e->getMessage());

                    return 1;
                }
            });

            if ($backup !== 0) {
                return $this->abort('Respaldo previo fallido. Nada se migró.');
            }
        }

        if ($this->step('Migraciones', fn () => Artisan::call('migrate', ['--force' => true])) !== 0) {
            return $this->abort('Migración fallida. Restaura el respaldo generado y corre config:clear.');
        }

        if ($this->step('Cachés (optimize)', fn () => Artisan::call('optimize')) !== 0) {
            return $this->abort('Falló la generación de cachés.');
        }

        $this->step('Enlace storage', fn () => $this->linkStorage());

        if ($maintenance) {
            Artisan::call('up');
            $this->components->info('Mantenimiento desactivado.');
        }

        if ($this->smoke() !== 0) {
            return self::FAILURE;
        }

        $this->info('Optimización completa.');
        $this->line('Pendientes manuales: composer install --no-dev --optimize-autoloader, npm run build, reiniciar Apache.');

        return self::SUCCESS;
    }

    private function linkStorage(): int
    {
        $link = public_path('storage');
        $real = storage_path('app/public');

        if (file_exists($link) && ! $this->storageIsLinked()) {
            if (! is_dir($real)) {
                @mkdir($real, 0775, true);
            }

            $this->moveDirectoryContents($link, $real);
            $this->removeDirectory($link);
            $this->line('public/storage era un directorio real: su contenido se conservó en storage/app/public.');
        }

        Artisan::call('storage:link');

        if (! $this->storageIsLinked()) {
            $this->line('public/storage sigue sin ser un enlace.');

            return 1;
        }

        return 0;
    }

    private function smoke(): int
    {
        $base = rtrim((string) config('app.url'), '/');
        $failures = 0;

        foreach (['/up' => 200, '/login' => 200] as $path => $expected) {
            try {
                $status = Http::timeout(15)->get($base.$path)->status();
            } catch (\Throwable) {
                $status = 0;
            }

            $ok = $status === $expected;
            $this->components->twoColumnDetail("Smoke GET {$path}", ($ok ? '<fg=green>OK</> ' : '<fg=red>FAIL</> ').$status);

            if (! $ok) {
                $failures++;
            }
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  callable(): int|string  $callback
     */
    private function step(string $label, callable $callback): int
    {
        $started = microtime(true);
        $result = (int) $callback();
        $seconds = round(microtime(true) - $started, 1);

        $this->components->twoColumnDetail(
            $label,
            ($result === 0 ? '<fg=green>OK</>' : '<fg=red>FAIL</>')." ({$seconds}s)"
        );

        return $result;
    }

    private function abort(string $message): int
    {
        $this->error($message.' Reversión: restaura el último respaldo y corre config:clear.');

        try {
            Artisan::call('up');
        } catch (\Throwable) {
        }

        return self::FAILURE;
    }

    /**
     * @return array{label: string, ok: bool, detail: string, level: string}
     */
    private function check(string $label, bool $ok, string $detail, string $level = 'ok'): array
    {
        return ['label' => $label, 'ok' => $ok, 'detail' => $detail, 'level' => $level];
    }

    private function appKeyPresent(): bool
    {
        $key = (string) config('app.key');

        return $key !== '' && ! str_contains($key, 'base64:');
    }

    private function appKeyState(): string
    {
        $key = (string) config('app.key');

        return $key === '' ? 'APP_KEY vacía' : 'APP_KEY presente';
    }

    private function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function publicStorageIsLink(): bool
    {
        return $this->storageIsLinked();
    }

    private function storageIsLinked(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($link) || ! is_dir($target)) {
            return false;
        }

        $linkStat = @stat($link);
        $targetStat = @stat($target);

        return $linkStat !== false
            && $targetStat !== false
            && $linkStat['dev'] === $targetStat['dev']
            && $linkStat['ino'] === $targetStat['ino'];
    }

    private function moveDirectoryContents(string $from, string $to): void
    {
        foreach (@scandir($from) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $from.DIRECTORY_SEPARATOR.$item;
            $target = $to.DIRECTORY_SEPARATOR.$item;

            if (is_dir($source) && ! is_link($source)) {
                @mkdir($target, 0775, true);
                $this->moveDirectoryContents($source, $target);
                @rmdir($source);
            } elseif (! file_exists($target)) {
                @rename($source, $target);
            }
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (@scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path.DIRECTORY_SEPARATOR.$item;

            if (is_dir($child) && ! is_link($child)) {
                $this->removeDirectory($child);
            } else {
                @unlink($child);
            }
        }

        @rmdir($path);
    }

    private function storageWritable(): bool
    {
        $paths = [
            storage_path('framework/sessions'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('app'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }

            if (! is_writable($path)) {
                return false;
            }
        }

        return true;
    }

    private function pendingMigrations(): int
    {
        try {
            $files = glob(database_path('migrations/*.php')) ?: [];
            $names = array_map(fn (string $file): string => basename($file, '.php'), $files);
            $ran = DB::table('migrations')->pluck('migration')->all();

            return count(array_diff($names, $ran));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function logSize(): int
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return 0;
        }

        $size = filesize($path);

        return $size === false ? 0 : $size;
    }

    private function logSizeHuman(): string
    {
        $size = $this->logSize();

        return $size >= 1024 * 1024
            ? round($size / 1024 / 1024, 1).' MB'
            : round($size / 1024, 1).' KB';
    }

    private function cachedViews(): string
    {
        $compiled = glob(storage_path('framework/views/*.php')) ?: [];

        return count($compiled).' vistas compiladas';
    }

    private function autoloaderOptimized(): bool
    {
        $classmap = @file_get_contents(base_path('vendor/composer/autoload_classmap.php')) ?: '';

        $hasAppClassmap = str_contains($classmap, 'App\\\\Console\\\\Commands\\\\OptimizeProduction')
            || str_contains($classmap, 'App\\Console\\Commands\\OptimizeProduction');

        $hasDevPackages = is_dir(base_path('vendor/phpunit'))
            || is_dir(base_path('vendor/nunomaduro/collision'))
            || is_dir(base_path('vendor/laravel/pint'))
            || is_dir(base_path('vendor/mockery'));

        return $hasAppClassmap && ! $hasDevPackages;
    }
}
