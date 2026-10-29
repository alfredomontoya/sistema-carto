<?php

namespace Tests\Feature;

use App\Models\Communication;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_renders_themed_error_page(): void
    {
        $this->get('/ruta-que-no-existe')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Show')
                ->where('status', 404)
                ->where('title', 'No encontramos esta página'));
    }

    public function test_user_without_permission_sees_themed_403_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Usuario Basico',
            'ci' => '9999999',
            'email' => 'basico@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Show')
                ->where('status', 403)
                ->where('title', 'No tienes acceso a esta sección'));
    }

    public function test_jefe_cannot_assign_administrator_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $jefe = $this->makeUser('jefe@carto.com', 'jefe');
        $target = $this->makeUser('objetivo@carto.com', 'usuario');

        $adminRoleId = \Spatie\Permission\Models\Role::findByName('administrador')->id;

        $this->actingAs($jefe)
            ->put("/admin/users/{$target->id}", [
                'ci' => $target->ci,
                'name' => $target->name,
                'username' => 'objetivo',
                'role_ids' => [$adminRoleId],
            ])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole('administrador'));
    }

    public function test_last_administrator_cannot_be_deleted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = $this->makeUser('admin@carto.com', 'administrador');
        $jefe = $this->makeUser('jefe2@carto.com', 'jefe');

        $this->actingAs($jefe)
            ->delete("/admin/users/{$admin->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_only_allowed_users_can_download_attachments(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('communications/test.pdf', 'contenido');

        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $communication = Communication::factory()->create([
            'user_id' => $owner->id,
            'area_destino_id' => null,
            'recipient_user_id' => null,
            'file_path' => 'communications/test.pdf',
            'file_name' => 'test.pdf',
        ]);

        $this->actingAs($outsider)
            ->get("/comunicaciones/{$communication->id}/descargar")
            ->assertForbidden();

        $this->actingAs($owner)
            ->get("/comunicaciones/{$communication->id}/descargar")
            ->assertOk();
    }

    private function makeUser(string $email, string $role): User    {
        $user = User::create([
            'name' => ucfirst($role),
            'ci' => (string) random_int(1000000, 9999999),
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
