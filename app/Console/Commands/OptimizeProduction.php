<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

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

        return [
            $this->check('APP_ENV=production', config('app.env') === 'production', 'Actual: '.config('app.env')),
            $this->check('APP_DEBUG=false', config('app.debug') === false, 'Actual: '.var_export(config('app.debug'), true)),
            $this->check('CACHE_STORE=file', config('cache.default') === 'file', 'Actual: '.config('cache.default')),
            $this->check(
                'SESSION_DRIVER=database (borrado directo de sesiones)',
                config('session.driver') === 'database',
                'Actual: '.config('session.driver')
            ),
            $this->check(
                'OPcache activo',
                ini_get('opcache.enable') === '1' || ini_get('opcache.enable') === true,
                'opcache.enable='.var_export(ini_get('opcache.enable'), true)
            ),
            $this->check(
                'OPcache sin revalidación por timestamp',
                in_array(ini_get('opcache.validate_timestamps'), ['0', false, '', 0], true),
                'validate_timestamps='.var_export(ini_get('opcache.validate_timestamps'), true).' (reinicia Apache en cada deploy)'
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
                'Cachés de Laravel vigentes',
                $configCached
                    && is_file(base_path('bootstrap/cache/routes-v7.php'))
                    && is_file(base_path('bootstrap/cache/events.php')),
                $configCached ? $this->cachedViews() : 'Falta bootstrap/cache/config.php (corre app:optimize-produccion)'
            ),
            $this->check(
                'Autoload optimizado sin paquetes dev',
                $this->autoloaderOptimized(),
                $this->autoloaderOptimized() ? 'classmap generado, sin phpunit' : 'Corre composer install --no-dev --optimize-autoloader'
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

        foreach ($this->checks() as $check) {
            $this->components->twoColumnDetail(
                $check['label'],
                ($check['ok'] ? '<fg=green>OK</> ' : '<fg=red>FAIL</> ').$check['detail']
            );

            if (! $check['ok']) {
                $failed++;
            }
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

        $this->step('Enlace storage', fn () => Artisan::call('storage:link', ['--force' => true]));

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
     * @return array{label: string, ok: bool, detail: string}
     */
    private function check(string $label, bool $ok, string $detail): array
    {
        return ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    }

    private function cachedViews(): string
    {
        $compiled = glob(storage_path('framework/views/*.php')) ?: [];

        return count($compiled).' vistas compiladas';
    }

    private function autoloaderOptimized(): bool
    {
        $classmap = file_get_contents(base_path('vendor/composer/autoload_classmap.php') ?: '') ?: '';

        return str_contains($classmap, 'App\\Console\\Commands\\OptimizeProduction')
            && ! is_dir(base_path('vendor/phpunit'));
    }
}
