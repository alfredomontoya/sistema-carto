<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Admin Test',
            'ci' => fake()->unique()->numerify('#######'),
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('administrador');

        return $user;
    }

    private function basicUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Basic',
            'ci' => fake()->unique()->numerify('#######'),
            'email' => 'basic@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        return $user;
    }

    private function cleanBackups(): void
    {
        $dir = app(DatabaseBackupService::class)->directory();

        foreach (glob($dir.DIRECTORY_SEPARATOR.'respaldo-*.sql*') ?: [] as $file) {
            @unlink($file);
        }
    }

    public function test_index_requires_manage_backups_permission(): void
    {
        $this->actingAs($this->basicUser())->get('/admin/backups')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/backups')->assertOk();
    }

    public function test_store_creates_backup_file(): void
    {
        $this->cleanBackups();

        $this->actingAs($this->admin())->post('/admin/backups')
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('success');

        $this->assertCount(1, app(DatabaseBackupService::class)->list());

        $this->cleanBackups();
    }

    public function test_store_prunes_beyond_max(): void
    {
        $service = app(DatabaseBackupService::class);
        $this->cleanBackups();
        $service->setMax(2);

        $dir = $service->directory();
        file_put_contents($dir.DIRECTORY_SEPARATOR.'respaldo-20250101-000000.sql', 'x');
        file_put_contents($dir.DIRECTORY_SEPARATOR.'respaldo-20250102-000000.sql', 'x');

        $this->actingAs($this->admin())->post('/admin/backups')
            ->assertSessionHas('success');

        $names = array_column($service->list(), 'name');
        $this->assertCount(2, $names);
        $this->assertNotContains('respaldo-20250101-000000.sql', $names);

        $this->cleanBackups();
    }

    public function test_restore_requires_confirmation_and_valid_file(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/backups/restaurar', [
            'file' => 'respaldo-20250101-000000.sql',
        ])->assertSessionHasErrors('confirm');

        $this->actingAs($admin)->post('/admin/backups/restaurar', [
            'file' => '../.env',
            'confirm' => true,
        ])->assertSessionHasErrors('file');

        $this->actingAs($admin)->post('/admin/backups/restaurar', [
            'file' => 'respaldo-20250101-000000.sql',
            'confirm' => true,
        ])->assertSessionHas('error');
    }

    public function test_upload_restore_requires_permission(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('respaldo.sql', 10, 'application/sql');

        $this->actingAs($this->basicUser())->post('/admin/backups/restaurar-archivo', [
            'backup_file' => $file,
            'confirm' => true,
        ])->assertForbidden();
    }

    public function test_upload_restore_validates_file_and_confirmation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/backups/restaurar-archivo', [
            'confirm' => true,
        ])->assertSessionHasErrors('backup_file');

        $sql = \Illuminate\Http\UploadedFile::fake()->create('respaldo.sql', 10, 'application/sql');

        $this->actingAs($admin)->post('/admin/backups/restaurar-archivo', [
            'backup_file' => $sql,
        ])->assertSessionHasErrors('confirm');

        $txt = \Illuminate\Http\UploadedFile::fake()->create('notas.txt', 10, 'text/plain');

        $this->actingAs($admin)->post('/admin/backups/restaurar-archivo', [
            'backup_file' => $txt,
            'confirm' => true,
        ])->assertSessionHasErrors('backup_file');
    }

    public function test_update_settings_validates_drive(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/backups/ajustes', [
            'drive' => 'ZZ',
            'max' => 5,
        ])->assertSessionHasErrors('drive');

        $this->actingAs($admin)->put('/admin/backups/ajustes', [
            'drive' => null,
            'max' => 5,
        ])->assertSessionHas('success');

        $this->assertSame(5, app(DatabaseBackupService::class)->max());
    }

    public function test_destination_info_reports_disk_status(): void
    {
        $this->admin();

        $info = app(DatabaseBackupService::class)->destinationInfo();

        $this->assertContains($info['status'], ['ok', 'low', 'critical']);
        $this->assertArrayHasKey('free', $info);
    }

    public function test_sql_splitter_respects_quotes(): void
    {
        $dumper = app(\App\Services\DatabaseDumper::class);
        $method = new \ReflectionMethod($dumper, 'splitStatements');

        $parts = $method->invoke($dumper, "INSERT INTO `t` (`a`) VALUES ('x;y');\nINSERT INTO `t` (`a`) VALUES ('z');\n");

        $this->assertCount(2, $parts);
        $this->assertStringContainsString("'x;y'", $parts[0]);
    }
}
