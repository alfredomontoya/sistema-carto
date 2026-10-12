<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizeProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_mode_reports_without_changing_anything(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Dev no cumple el perfil de producción (APP_ENV=local, sin cachés).
        $this->artisan('app:optimize-produccion', ['--check' => true])
            ->assertExitCode(1);
    }

    public function test_checks_method_returns_structured_results(): void
    {
        $command = app(\App\Console\Commands\OptimizeProduction::class);

        foreach ($command->checks() as $check) {
            $this->assertArrayHasKey('label', $check);
            $this->assertArrayHasKey('ok', $check);
            $this->assertArrayHasKey('detail', $check);
            $this->assertIsBool($check['ok']);
        }

        $this->assertNotEmpty($command->checks());
    }
}
